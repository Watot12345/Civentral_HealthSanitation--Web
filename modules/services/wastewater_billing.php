<?php
// ============================================================
// COLOR PALETTE USED ON THIS PAGE
// ============================================================
//   'brand-dark':   '#0B4F4A',
//   'brand-medium': '#14807A',
//   'brand-light':  '#E6F5F3',
//   'brand-border': '#B8E0DC',
// ============================================================

// ============================================================
// 1. PHP BACKEND - Fetch Data
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('wastewater services');

// Role-Based Authorization for Fee Structure Management (Head Roles & Admins Only)
$userRoleDesc = trim($_SESSION['role_description'] ?? '');
$userRole     = trim($_SESSION['role'] ?? '');
$combinedRole = strtolower($userRole . ' ' . $userRoleDesc);

$isAdminRole = (bool) preg_match('/admin|administrator|superadmin|system administrator|system admin/i', $combinedRole);
$isHeadRole  = (bool) preg_match('/health center director|sanitation director|immunization lead|immunization coordinator|waste\s*water lead|surveil{1,2}ance lead|surveillance coordinator|director|coordinator|lead|head|department head|manager/i', $combinedRole);
$canManageFees = $isAdminRole || $isHeadRole || (function_exists('hasPermission') && hasPermission('wastewater.manage'));

// Fee Structure Config File
$feeConfigFile = __DIR__ . '/../../config/fee_structure.json';
$defaultFees = [
    ['category' => 'Desludging (Residential)', 'base_fee' => 1200.00, 'per_unit' => null, 'description' => 'Standard residential desludging service'],
    ['category' => 'Desludging (Commercial)', 'base_fee' => 2000.00, 'per_unit' => null, 'description' => 'Commercial establishment desludging'],
    ['category' => 'Septic Tank Inspection', 'base_fee' => 800.00, 'per_unit' => null, 'description' => 'Complete septic tank inspection'],
    ['category' => 'Septic Tank Maintenance', 'base_fee' => 1500.00, 'per_unit' => null, 'description' => 'Regular maintenance service'],
    ['category' => 'Installation (New Tank)', 'base_fee' => 5000.00, 'per_unit' => null, 'description' => 'New septic tank installation'],
    ['category' => 'Emergency Service', 'base_fee' => 2500.00, 'per_unit' => null, 'description' => 'Emergency call-out service'],
    ['category' => 'Pipe Inspection', 'base_fee' => 600.00, 'per_unit' => null, 'description' => 'CCTV pipe inspection'],
    ['category' => 'Wastewater Treatment', 'base_fee' => 3000.00, 'per_unit' => null, 'description' => 'Wastewater treatment service'],
];

$feeStructure = $defaultFees;
if (file_exists($feeConfigFile)) {
    $loaded = json_decode(file_get_contents($feeConfigFile), true);
    if (is_array($loaded) && !empty($loaded)) {
        $feeStructure = $loaded;
    }
}

// AJAX API Endpoint Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) || isset($_GET['action']))) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // Verify CSRF
    $providedCsrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($providedCsrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $providedCsrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
        exit;
    }

    // Role-Guarded Fee Management Actions (Head Roles & Admins only)
    if (in_array($action, ['add_fee', 'update_fee', 'delete_fee', 'reset_fees'], true)) {
        if (!$canManageFees) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Access Restricted: Only Department Heads, Leads, and Administrators have permission to modify municipal fee structures.'
            ]);
            exit;
        }

        if ($action === 'add_fee') {
            $category    = trim($_POST['category'] ?? '');
            $baseFee     = filter_var($_POST['base_fee'] ?? null, FILTER_VALIDATE_FLOAT);
            $perUnit     = trim($_POST['per_unit'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($category === '') {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid Fee Category name.']);
                exit;
            }

            if ($baseFee === false || $baseFee < 0 || $baseFee > 99999999999.99) {
                echo json_encode(['success' => false, 'message' => 'Base Fee must be a positive number up to 11 digits.']);
                exit;
            }

            // Check if category already exists (case-insensitive)
            foreach ($feeStructure as $f) {
                if (strcasecmp(trim($f['category'] ?? ''), $category) === 0) {
                    echo json_encode([
                        'success' => false, 
                        'message' => "Fee category '{$category}' already exists. Please edit the existing rate or use a unique category name."
                    ]);
                    exit;
                }
            }

            $newFee = [
                'category'    => htmlspecialchars($category, ENT_QUOTES, 'UTF-8'),
                'base_fee'    => round((float)$baseFee, 2),
                'per_unit'    => $perUnit !== '' ? htmlspecialchars($perUnit, ENT_QUOTES, 'UTF-8') : null,
                'description' => htmlspecialchars($description, ENT_QUOTES, 'UTF-8')
            ];

            $feeStructure[] = $newFee;
            file_put_contents($feeConfigFile, json_encode($feeStructure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if (class_exists('ActivityLog')) {
                try {
                    $log = new ActivityLog();
                    $log->log("Added fee category: {$category} (₱" . number_format($baseFee, 2) . ")", [
                        'module'   => 'Wastewater Billing',
                        'category' => $category,
                        'base_fee' => $baseFee
                    ]);
                } catch (Throwable $e) {}
            }

            echo json_encode([
                'success' => true, 
                'message' => "Fee category '{$category}' added successfully.",
                'fee'     => $newFee,
                'fees'    => $feeStructure
            ]);
            exit;
        }

        if ($action === 'update_fee') {
            $index   = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
            $baseFee = filter_var($_POST['base_fee'] ?? null, FILTER_VALIDATE_FLOAT);
            $desc    = trim($_POST['description'] ?? '');

            if ($index === false || !isset($feeStructure[$index])) {
                echo json_encode(['success' => false, 'message' => 'Invalid fee structure index.']);
                exit;
            }

            if ($baseFee === false || $baseFee < 0 || $baseFee > 99999999999.99) {
                echo json_encode(['success' => false, 'message' => 'Base Fee must be a positive number.']);
                exit;
            }

            $feeStructure[$index]['base_fee'] = round((float)$baseFee, 2);
            if ($desc !== '') {
                $feeStructure[$index]['description'] = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
            }
            if (isset($_POST['category']) && trim($_POST['category']) !== '') {
                $feeStructure[$index]['category'] = htmlspecialchars(trim($_POST['category']), ENT_QUOTES, 'UTF-8');
            }

            file_put_contents($feeConfigFile, json_encode($feeStructure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            echo json_encode([
                'success' => true,
                'message' => "Fee rate updated successfully to ₱" . number_format($baseFee, 2) . ".",
                'fees'    => $feeStructure
            ]);
            exit;
        }

        if ($action === 'delete_fee') {
            $index = filter_var($_POST['index'] ?? null, FILTER_VALIDATE_INT);
            if ($index === false || !isset($feeStructure[$index])) {
                echo json_encode(['success' => false, 'message' => 'Invalid fee structure index.']);
                exit;
            }

            $deletedName = $feeStructure[$index]['category'];
            array_splice($feeStructure, $index, 1);
            file_put_contents($feeConfigFile, json_encode($feeStructure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            echo json_encode([
                'success' => true,
                'message' => "Fee category '{$deletedName}' removed successfully.",
                'fees'    => $feeStructure
            ]);
            exit;
        }
    }

    echo json_encode(['success' => true, 'action' => $action, 'message' => 'Billing action processed successfully.']);
    exit;
}

require_once __DIR__ . '/../../app/Models/WastewaterInvoice.php';
require_once __DIR__ . '/../../app/Models/SepticTank.php';
$invoiceModel = new WastewaterInvoice();
$septicTankModel = new SepticTank();

// Fetch registered septic tanks for quotation dropdown and de-duplicate by tank_id
$rawSepticTanks = [];
try {
    $rawSepticTanks = $septicTankModel->all(['order' => 'owner_name.asc']);
} catch (Throwable $e) {
    error_log('Error fetching septic tanks for quotation: ' . $e->getMessage());
}

$septicTanks = [];
$seenTankIds = [];
foreach ($rawSepticTanks as $st) {
    $tid = trim($st['tank_id'] ?? '');
    if ($tid !== '' && !isset($seenTankIds[$tid])) {
        $seenTankIds[$tid] = true;
        $septicTanks[] = $st;
    }
}

// Fetch live Invoices from Supabase
$invoices = $invoiceModel->all();

// Build lookup of latest billing status per septic tank
$tankBillingStatus = [];
foreach ($invoices as $inv) {
    $tid = trim($inv['tank_id'] ?? '');
    if ($tid !== '' && !isset($tankBillingStatus[$tid])) {
        $tankBillingStatus[$tid] = [
            'status'       => $inv['status'] ?? 'pending',
            'invoice_id'   => $inv['invoice_id'] ?? '',
            'total_amount' => $inv['total_amount'] ?? 0,
            'paid_at'      => $inv['paid_at'] ?? null,
            'date'         => $inv['invoice_date'] ?? ''
        ];
    }
}

// Stats
$counts = $invoiceModel->countByStatus();
$totalInvoices = count($invoices);
$totalPaid = $counts['paid'];
$totalPending = $counts['pending'];
$totalOverdue = $counts['overdue'];
$totalRevenue = $counts['revenue'];
$totalOutstanding = $counts['outstanding'];

$title = 'Wastewater Billing';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Wastewater Billing</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage fee structure, quotations, payments & invoices</p>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match Case Reports design   -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Invoices -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-file-invoice text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalInvoices; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Invoices</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All invoices</span>
                    <span class="text-[10px] text-slate-400">Including <?php echo $totalPaid; ?> paid</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Paid -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $totalPaid; ?></p>
                        <p class="text-xs font-medium text-slate-500">Paid</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Completed</span>
                    <span class="text-[10px] text-slate-400">Payment confirmed</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Pending -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $totalPending; ?></p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400">Payment processing</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Overdue -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $totalOverdue; ?></p>
                        <p class="text-xs font-medium text-slate-500">Overdue</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
                    <span class="text-[10px] text-slate-400">Immediate action</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Revenue -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-coins text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark">₱<?php echo number_format($totalRevenue, 0); ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Revenue</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">💰 Collected</span>
                    <span class="text-[10px] text-slate-400">From <?php echo $totalPaid; ?> invoices</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Alert -->
    <?php if ($totalOverdue > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo $totalOverdue; ?></span> invoice(s) are overdue. Immediate payment required.
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='overdue'; filterInvoices();" 
                class="text-xs font-semibold text-rose-700 hover:text-rose-900 underline">
            View overdue
        </button>
    </div>
    <?php endif; ?>

    <!-- Fee Structure Collapsible Reference (Collapsed by Default so it doesn't interrupt staff) -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 mb-6 overflow-hidden">
        <button type="button" onclick="toggleFeeStructure()" class="w-full px-4 py-3 bg-slate-50/70 hover:bg-slate-100/70 transition flex items-center justify-between text-left">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-brand-light flex items-center justify-center text-brand-dark flex-shrink-0">
                    <i class="fa-solid fa-table-list text-sm"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800">Fee Structure Reference</h4>
                    <p class="text-xs text-slate-400">Standard municipal rates for desludging, inspection, and maintenance</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span id="feeToggleText" class="text-xs font-semibold text-brand-medium">Show Rates</span>
                <i id="feeToggleIcon" class="fa-solid fa-chevron-down text-slate-400 text-xs transition-transform duration-200"></i>
            </div>
        </button>
        <div id="feeStructureBody" class="hidden p-4 border-t border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Standard Rates (Tax: 6% calculated at quotation)</span>
                </div>
                <?php if ($canManageFees): ?>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-light text-brand-dark border border-brand-border">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Authorized (Head & Admin)
                    </span>
                    <button type="button" onclick="openModal('addFeeModal')" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-plus text-xs"></i> Add Fee Structure
                    </button>
                    <button type="button" onclick="openModal('feeStructureModal')" class="px-3 py-1.5 bg-slate-100 text-slate-700 hover:bg-slate-200 transition rounded-lg text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-sliders text-xs"></i> Manage All Rates
                    </button>
                </div>
                <?php else: ?>
                <span class="text-[11px] text-slate-400 italic">
                    <i class="fa-solid fa-lock text-xs mr-1"></i> Rates management restricted to Department Heads and Administrators
                </span>
                <?php endif; ?>
            </div>
            <div id="feeGridContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($feeStructure as $idx => $fee): ?>
                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 hover:shadow-xs hover:border-brand-border transition group relative">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($fee['category'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($fee['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php if (!empty($fee['per_unit'])): ?>
                            <span class="inline-block mt-1 text-[10px] font-semibold text-slate-500 bg-slate-200/60 px-1.5 py-0.5 rounded"><?php echo htmlspecialchars($fee['per_unit'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($canManageFees): ?>
                        <button onclick="editFeeAtIndex(<?php echo $idx; ?>)" title="Quick Edit Rate" class="opacity-0 group-hover:opacity-100 transition w-6 h-6 rounded-md bg-white border border-slate-200 text-slate-500 hover:text-brand-dark flex items-center justify-center text-xs shadow-2xs">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2.5 flex items-baseline justify-between pt-2 border-t border-slate-200/60">
                        <span class="text-xs text-slate-400 font-medium">Base Fee:</span>
                        <p class="text-base font-extrabold text-brand-dark">₱<?php echo number_format($fee['base_fee'], 2); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Search, Filters, Date Modal & Integrated Action Buttons Toolbar -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <!-- Search Box -->
            <div class="flex-1 relative min-w-[240px]">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchInvoice"
                       placeholder="Search by invoice ID, client, or tank..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>

            <!-- Integrated Control Bar: Filters, Date Picker Modal, Generate Quotation & Export -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Status Filter -->
                <select id="filterStatus" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white text-slate-700">
                    <option value="">All Status</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="overdue">Overdue</option>
                </select>

                <!-- Method Filter -->
                <select id="filterMethod" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white text-slate-700">
                    <option value="">All Methods</option>
                    <option value="Over-the-Counter">Over-the-Counter</option>
                    <option value="GCash">GCash</option>
                    <option value="Maya">Maya</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>

                <!-- Date Range Filter Modal Trigger (Replaces raw mm/dd/yyyy text inputs) -->
                <button type="button" onclick="openModal('dateFilterModal')" id="dateFilterBtn" class="px-3 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold flex items-center gap-2 transition whitespace-nowrap" title="Filter by date range">
                    <i class="fa-regular fa-calendar text-slate-400 text-xs"></i>
                    <span id="dateFilterLabel">Date Filter</span>
                    <span id="dateFilterActiveDot" class="hidden w-2 h-2 rounded-full bg-brand-medium"></span>
                </button>

                <!-- Hidden inputs to store active date range for the filter -->
                <input type="hidden" id="filterDateFrom">
                <input type="hidden" id="filterDateTo">

                <div class="h-6 w-[1px] bg-slate-200 hidden sm:block"></div>

                <!-- Generate Quotation Fitted in Toolbar -->
                <button onclick="openModal('quotationModal')"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm whitespace-nowrap">
                    <i class="fa-solid fa-file-invoice text-xs"></i> Generate Quotation
                </button>

                <!-- Export Fitted in Toolbar -->
                <button onclick="exportTableToCSV('#invoiceTableBody', 'wastewater_invoices')"
                        class="px-3 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2 whitespace-nowrap"
                        title="Export to CSV" aria-label="Export invoices to CSV">
                    <i class="fa-solid fa-file-csv text-xs"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Invoice ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Service</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="invoiceTableBody">
                    <?php foreach ($invoices as $invoice): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors invoice-row <?php echo $invoice['status'] === 'overdue' ? 'bg-rose-50/50' : ''; ?>"
                        data-client="<?php echo htmlspecialchars(strtolower($invoice['client_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-tank="<?php echo htmlspecialchars(strtolower($invoice['tank_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-service="<?php echo htmlspecialchars(strtolower($invoice['service_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-id="<?php echo htmlspecialchars(strtolower($invoice['invoice_id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-row-id="<?php echo (int)$invoice['id']; ?>"
                        data-status="<?php echo htmlspecialchars($invoice['status'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-method="<?php echo htmlspecialchars(strtolower($invoice['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-invoice-date="<?php echo htmlspecialchars($invoice['invoice_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        id="invoice-row-<?php echo (int)$invoice['id']; ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo $invoice['invoice_id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $invoice['client_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $invoice['tank_id']; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $invoice['service_type']; ?></td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-bold text-slate-800">₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                            <?php if ($invoice['status'] === 'overdue'): ?>
                                <span class="block text-[10px] text-rose-500">Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'overdue' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$invoice['status']] ?? $statusColors['pending']; ?>">
                                <?php echo ucfirst($invoice['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php if ($invoice['payment_method']): ?>
                                <span class="text-emerald-600"><?php echo $invoice['payment_method']; ?></span>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewInvoice(<?php echo $invoice['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <?php if ($invoice['status'] === 'pending' || $invoice['status'] === 'overdue'): ?>
                                    <button onclick="processPayment(<?php echo $invoice['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Pay">
                                        <i class="fa-solid fa-credit-card text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <button onclick="editInvoice(<?php echo $invoice['id']; ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-file-invoice text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No invoices match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo $totalInvoices; ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalInvoices; ?></span> invoices
            </p>
            <div class="flex gap-1">
                <button class="px-3 py-1.5 rounded-lg text-sm bg-slate-100 text-slate-300 cursor-not-allowed" disabled>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-brand-dark text-white">1</button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QUOTATION GENERATION MODAL                                   -->
<!-- ============================================================ -->
<div id="quotationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-brand-medium"></i>
                Generate Quotation
            </h3>
            <button onclick="closeModal('quotationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="quotationForm" class="p-6 space-y-4" onsubmit="saveQuotation(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            
            <!-- Quick Live Filter / Search Bar for Scalable Client and Tank Selection -->
            <div class="p-3 bg-brand-light/30 rounded-xl border border-brand-border">
                <div class="flex items-center gap-2 mb-1.5">
                    <i class="fa-solid fa-magnifying-glass text-brand-medium text-xs"></i>
                    <label for="quoteQuickSearch" class="text-xs font-bold text-slate-700">Quick Client or Tank Search</label>
                    <span class="text-[10px] text-slate-400 ml-auto">Type to filter dropdowns instantly</span>
                </div>
                <input type="text" id="quoteQuickSearch" oninput="filterQuoteDropdowns(this.value)" placeholder="Type Owner Name, Tank ID, or Address..." class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <!-- Linked Dropdowns: Client & Septic Tank -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Client (Owner Name) <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="quote_client" required onchange="onQuoteClientChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Client</option>
                            <?php 
                                $uniqueOwners = [];
                                foreach ($septicTanks as $tank) {
                                    $owner = trim($tank['owner_name'] ?? '');
                                    $tid = $tank['tank_id'] ?? '';
                                    if ($owner !== '' && !isset($uniqueOwners[$owner])) {
                                        $uniqueOwners[$owner] = true;
                                        echo '<option value="' . htmlspecialchars($owner, ENT_QUOTES, 'UTF-8') . '" data-tank="' . htmlspecialchars($tid, ENT_QUOTES, 'UTF-8') . '" data-address="' . htmlspecialchars($tank['address'] . (isset($tank['barangay']) ? ', ' . $tank['barangay'] : ''), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($owner, ENT_QUOTES, 'UTF-8') . '</option>';
                                    }
                                }
                            ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="quote_tank" required onchange="onQuoteTankChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Septic Tank</option>
                            <?php foreach ($septicTanks as $tank): 
                                $tid = $tank['tank_id'] ?? '';
                            ?>
                                <option value="<?php echo htmlspecialchars($tid, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-owner="<?php echo htmlspecialchars($tank['owner_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-address="<?php echo htmlspecialchars($tank['address'] . (isset($tank['barangay']) ? ', ' . $tank['barangay'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars(!empty($tid) ? $tid : ($tank['id'] ?? 'Septic Tank'), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Auto-populated Address -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Address</label>
                <input type="text" id="quote_address" readonly class="w-full px-3 py-2 border border-slate-200 bg-slate-50 text-slate-600 rounded-lg text-sm outline-none" placeholder="Auto-populated from selected tank">
            </div>

            <!-- Active Unpaid Invoice Duplicate Warning -->
            <div id="quote_duplicate_warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl space-y-2 text-xs text-amber-800">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 text-sm flex-shrink-0"></i>
                    <div class="flex-1">
                        <strong class="font-bold block text-amber-900">Active Unpaid Invoice Detected</strong>
                        <span id="quote_duplicate_msg">This septic tank already has an active unpaid invoice.</span>
                    </div>
                </div>
                <div class="pt-1.5 border-t border-amber-200/70 flex items-center gap-2">
                    <input type="checkbox" id="quote_allow_override" onchange="toggleQuoteOverride(this.checked)" class="rounded text-brand-dark focus:ring-brand-medium">
                    <label for="quote_allow_override" class="text-[11px] text-amber-900 font-semibold cursor-pointer">
                        Allow generating additional quotation for a separate new service
                    </label>
                </div>
            </div>

            <!-- Paid Status Info Notice -->
            <div id="quote_paid_info" class="hidden p-3 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-2.5 text-xs text-emerald-800">
                <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 text-sm flex-shrink-0"></i>
                <div class="flex-1">
                    <strong class="font-bold block text-emerald-900">Previous Account Cleared (Paid)</strong>
                    <span id="quote_paid_msg">Previous invoice has been paid in full. You are generating a quotation for a NEW service cycle.</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label>
                <select id="quote_service" required onchange="updateQuotePricing(); checkDuplicateInvoice();" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <?php foreach ($feeStructure as $fee): ?>
                        <option value="<?php echo $fee['category']; ?>" data-fee="<?php echo $fee['base_fee']; ?>">
                            <?php echo $fee['category']; ?> (₱<?php echo number_format($fee['base_fee'], 2); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dynamic Pricing Summary Card -->
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row justify-between sm:items-center gap-2 text-xs">
                <div>
                    <span class="text-slate-500">Base Fee:</span>
                    <strong id="quote_fee_display" class="text-slate-800 ml-1">₱1,200.00</strong>
                    <span class="text-slate-400 ml-2">(+ 6% Tax: <span id="quote_tax_display">₱72.00</span>)</span>
                </div>
                <div class="sm:text-right">
                    <span class="text-slate-500">Estimated Total:</span>
                    <strong id="quote_total_display" class="text-brand-dark font-bold text-sm ml-1">₱1,272.00</strong>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Additional Items (Optional)</label>
                <textarea id="quote_items" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional items or specifications..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="quote_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Quotation notes..."></textarea>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <label class="flex items-center gap-2 text-xs text-slate-700 font-semibold cursor-pointer">
                    <input type="checkbox" id="quote_auto_pay" checked class="rounded text-brand-dark focus:ring-brand-medium">
                    <span>Proceed directly to <strong>Process Payment</strong> after generation</span>
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeModal('quotationModal')"
                            class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" id="generate_quote_submit_btn"
                            class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-file-invoice-dollar text-xs"></i> Generate Quotation
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW INVOICE MODAL                                           -->
<!-- ============================================================ -->
<div id="viewInvoiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Invoice Details</h3>
            <button onclick="closeModal('viewInvoiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="invoiceDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- PAYMENT PROCESSING MODAL                                     -->
<!-- ============================================================ -->
<div id="editInvoiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Invoice</h3>
            <button type="button" onclick="closeModal('editInvoiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveInvoiceEdit(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="edit_invoice_id">
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Client Name</label><input type="text" id="edit_invoice_client" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID</label><input type="text" id="edit_invoice_tank" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label><input type="text" id="edit_invoice_service" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fee Amount</label><input type="number" id="edit_invoice_amount" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitFeeInput(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tax</label><input type="number" id="edit_invoice_tax" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitFeeInput(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Due Date</label><input type="date" id="edit_invoice_due_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_invoice_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"><option value="paid">Paid</option><option value="pending">Pending</option><option value="overdue">Overdue</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_invoice_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editInvoiceModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<div id="paymentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-brand-medium"></i>
                Process Payment
            </h3>
            <button onclick="closeModal('paymentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="paymentForm" class="p-6 space-y-4" onsubmit="savePayment(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="pay_invoice_id">
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Invoice ID</label>
                    <input type="text" id="pay_invoice_display" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700 font-semibold outline-none cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Amount Due</label>
                    <input type="text" id="pay_amount_display" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-brand-dark font-black outline-none cursor-not-allowed">
                    <input type="hidden" id="pay_amount">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Payment Method</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-2.5" id="wastewaterPaymentMethods">
                    <!-- Municipal Hall Over-the-Counter (Default for face-to-face cash) -->
                    <label class="flex items-center justify-center gap-1.5 p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="ww_pay_method_radio" value="Over-the-Counter" class="sr-only" checked onchange="onPaymentMethodChange(this.value)">
                        <i class="fa-solid fa-landmark text-slate-700 text-sm"></i>
                        <span class="text-xs font-semibold text-slate-800">Over-the-Counter</span>
                    </label>

                    <!-- GCash -->
                    <label class="flex items-center justify-center p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="ww_pay_method_radio" value="GCash" class="sr-only" onchange="onPaymentMethodChange(this.value)">
                        <img src="<?php echo site_url('assets/images/payments/gcash.png'); ?>" alt="GCash" class="h-6 w-auto object-contain">
                    </label>

                    <!-- Maya -->
                    <label class="flex items-center justify-center p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="ww_pay_method_radio" value="Maya" class="sr-only" onchange="onPaymentMethodChange(this.value)">
                        <img src="<?php echo site_url('assets/images/payments/maya.png'); ?>" alt="Maya" class="h-6 w-auto object-contain">
                    </label>

                    <!-- Bank Transfer -->
                    <label class="flex items-center justify-center p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="ww_pay_method_radio" value="Bank Transfer" class="sr-only" onchange="onPaymentMethodChange(this.value)">
                        <img src="<?php echo site_url('assets/images/payments/landbank.png'); ?>" alt="Landbank" class="h-7 w-auto object-contain">
                    </label>
                </div>
                <input type="hidden" id="pay_method" value="Over-the-Counter" required>
            </div>

            <!-- Over-The-Counter Face-to-Face Cash Verification & Change Calculator -->
            <div id="otc_cash_box" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl space-y-2.5">
                <div class="flex items-center gap-2 text-xs font-bold text-emerald-800">
                    <i class="fa-solid fa-hand-holding-dollar text-emerald-600 text-sm"></i>
                    <span>Face-to-Face Cash Verification</span>
                </div>
                <p class="text-[11px] text-emerald-700 leading-relaxed">
                    Physical cash collected face-to-face. Official Receipt (OR) reference code is assigned for official record keeping.
                </p>
                <div class="grid grid-cols-2 gap-2 pt-1 border-t border-emerald-200/60">
                    <div>
                        <label class="block text-[10px] font-semibold text-emerald-800 uppercase mb-0.5">Cash Tendered (₱)</label>
                        <input type="number" id="pay_cash_tendered" step="0.01" oninput="calculateOtcChange()" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white border border-emerald-300 rounded-lg text-xs font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-emerald-800 uppercase mb-0.5">Change Due</label>
                        <div id="pay_cash_change" class="px-2.5 py-1.5 bg-emerald-100/80 border border-emerald-300 rounded-lg text-xs font-black text-emerald-900 flex items-center h-[34px]">₱0.00</div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Official Receipt / Reference Number</label>
                <input type="text" id="pay_reference" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-mono font-semibold text-slate-800" placeholder="Official Receipt No.">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('paymentModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="pay_submit_btn"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-check mr-1"></i> Confirm Cash Received & Mark Paid
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- FEE STRUCTURE MANAGEMENT MODAL (HEAD & ADMIN ONLY)           -->
<!-- ============================================================ -->
<?php if ($canManageFees): ?>
<div id="feeStructureModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-brand-light flex items-center justify-center text-brand-dark">
                    <i class="fa-solid fa-table-list text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-tight">Municipal Fee Rates Management</h3>
                    <p class="text-xs text-slate-400">Head & Admin Official Price Configuration</p>
                </div>
            </div>
            <button onclick="closeModal('feeStructureModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="flex items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                <p class="text-xs text-slate-500">Edit base prices or manage service categories:</p>
                <button type="button" onclick="closeModal('feeStructureModal'); openModal('addFeeModal');" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-plus text-xs"></i> Add New Category
                </button>
            </div>

            <div class="space-y-3" id="feeManagementList">
                <?php foreach ($feeStructure as $index => $fee): ?>
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3" id="fee_row_<?php echo $index; ?>">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($fee['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if (!empty($fee['per_unit'])): ?>
                            <span class="text-[10px] font-semibold text-slate-500 bg-slate-200/60 px-1.5 py-0.5 rounded"><?php echo htmlspecialchars($fee['per_unit'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5"><?php echo htmlspecialchars($fee['description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400">₱</span>
                            <input type="number" id="fee_<?php echo $index; ?>" value="<?php echo $fee['base_fee']; ?>" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitFeeInput(this)" title="Maximum 11 digits"
                                   class="w-28 pl-6 pr-2 py-1.5 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <button type="button" onclick="updateFee(<?php echo $index; ?>)" title="Save Rate Changes" class="px-2.5 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1 shadow-2xs">
                            <i class="fa-solid fa-check text-xs"></i> Save
                        </button>
                        <?php if ($index >= 8): // Allow deletion of custom added fees ?>
                        <button type="button" onclick="deleteFee(<?php echo $index; ?>)" title="Remove Fee Category" class="w-8 h-8 rounded-lg border border-rose-200 bg-white text-rose-600 hover:bg-rose-50 transition flex items-center justify-center text-xs">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                <button type="button" onclick="closeModal('feeStructureModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD FEE CATEGORY MODAL (HEAD & ADMIN ONLY)                   -->
<!-- ============================================================ -->
<div id="addFeeModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-brand-medium"></i>
                Add New Fee Structure
            </h3>
            <button type="button" onclick="closeModal('addFeeModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveNewFeeStructure(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="p-3 bg-brand-light/70 border border-brand-border rounded-xl text-xs text-brand-dark flex items-center gap-2">
                <i class="fa-solid fa-user-shield text-brand-medium text-sm flex-shrink-0"></i>
                <span>Authorized Role: <strong><?php echo htmlspecialchars($userRoleDesc ?: $userRole ?: 'Department Head/Admin', ENT_QUOTES, 'UTF-8'); ?></strong></span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fee / Service Category Name <span class="text-rose-500">*</span></label>
                <input type="text" id="new_fee_category" required placeholder="e.g. Emergency Desludging (Weekend / 24-7)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Base Fee (PHP) <span class="text-rose-500">*</span></label>
                    <input type="number" id="new_fee_amount" required min="0" max="99999999999" step="0.01" inputmode="decimal" placeholder="0.00" oninput="limitFeeInput(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Per Unit Note (Optional)</label>
                    <input type="text" id="new_fee_per_unit" placeholder="e.g. per cu.m / trip" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Description / Municipal Reference</label>
                <textarea id="new_fee_description" rows="2" placeholder="Brief service details, ordinance or resolution reference..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('addFeeModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-plus text-xs"></i> Add Fee Structure
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- DATE RANGE FILTER MODAL                                      -->
<!-- ============================================================ -->
<div id="dateFilterModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-regular fa-calendar-days text-brand-medium"></i>
                Filter Invoices by Date Range
            </h3>
            <button onclick="closeModal('dateFilterModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <!-- Quick Presets -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Quick Presets</label>
                <div class="grid grid-cols-3 gap-2 text-xs">
                    <button type="button" onclick="setDatePreset('today')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-brand-light hover:text-brand-dark border border-slate-200 rounded-lg font-semibold text-slate-700 transition text-center">Today</button>
                    <button type="button" onclick="setDatePreset('last7')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-brand-light hover:text-brand-dark border border-slate-200 rounded-lg font-semibold text-slate-700 transition text-center">Last 7 Days</button>
                    <button type="button" onclick="setDatePreset('thisMonth')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-brand-light hover:text-brand-dark border border-slate-200 rounded-lg font-semibold text-slate-700 transition text-center">This Month</button>
                    <button type="button" onclick="setDatePreset('last30')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-brand-light hover:text-brand-dark border border-slate-200 rounded-lg font-semibold text-slate-700 transition text-center">Last 30 Days</button>
                    <button type="button" onclick="setDatePreset('thisYear')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-brand-light hover:text-brand-dark border border-slate-200 rounded-lg font-semibold text-slate-700 transition text-center">This Year</button>
                    <button type="button" onclick="setDatePreset('all')" class="px-2.5 py-1.5 bg-slate-50 hover:bg-rose-50 hover:text-rose-700 border border-slate-200 rounded-lg font-semibold text-slate-700 transition text-center">All Time</button>
                </div>
            </div>

            <!-- Specific Date Range Pickers -->
            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date From</label>
                    <input type="date" id="modalDateFrom" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date To</label>
                    <input type="date" id="modalDateTo" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                <button type="button" onclick="clearDateFilterModal()" class="text-xs font-semibold text-slate-500 hover:text-rose-600 transition">
                    Clear Date Filter
                </button>
                <div class="flex gap-2">
                    <button type="button" onclick="closeModal('dateFilterModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="button" onclick="applyDateFilterModal()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-filter text-xs"></i> Apply Filter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const INVOICES = <?php echo json_encode(array_column($invoices, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

    // Modal functions, toast, sanitizeHTML provided by common.js

    // ============================================================
    // VIEW INVOICE
    // ============================================================
    function viewInvoice(id) {
        openModal('viewInvoiceModal');
        const i = INVOICES[id];
        if (!i) return;

        setTimeout(() => {
            const statusColors = {
                paid: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                overdue: 'bg-rose-100 text-rose-700'
            };

            // Use sanitizeHTML() to prevent XSS
            const iClient = sanitizeHTML(i.client_name);
            const iInvId = sanitizeHTML(i.invoice_id);
            const iTankId = sanitizeHTML(i.tank_id);
            const iService = sanitizeHTML(i.service_type);
            const iMethod = sanitizeHTML(i.payment_method || '—');
            const iRef = sanitizeHTML(i.payment_reference || '');
            const iNotes = sanitizeHTML(i.notes);
            const iStatus = sanitizeHTML(i.status);
            const isPaid = i.status === 'paid';

            const itemsHtml = (i.items || []).map(item => `
                <div class="flex justify-between items-center p-2 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="text-sm text-slate-800">${sanitizeHTML(item.description)}</p>
                        <p class="text-xs text-slate-400">${item.quantity} x ₱${Number(item.unit_price).toFixed(2)}</p>
                    </div>
                    <span class="font-semibold text-slate-800">₱${Number(item.total).toFixed(2)}</span>
                </div>
            `).join('');

            document.getElementById('invoiceDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${iClient.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${iClient}</h4>
                            <p class="text-sm text-slate-500">${iInvId} &bull; ${iTankId}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[i.status] || statusColors.pending}">
                                ${iStatus.toUpperCase()}
                            </span>
                        </div>
                    </div>

                    ${isPaid ? `
                        <!-- Official Receipt Details Section -->
                        <div class="bg-emerald-50/80 rounded-xl p-4 border border-emerald-200 space-y-3">
                            <div class="flex items-center justify-between pb-2 border-b border-emerald-200/70">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-receipt text-emerald-700 text-base"></i>
                                    <h5 class="text-sm font-bold text-emerald-900">Official Payment Receipt</h5>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-200 text-emerald-900 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check text-[10px]"></i> Verified Paid
                                </span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                                <div>
                                    <span class="text-slate-500 font-medium block">Receipt / Ref #:</span>
                                    <strong class="font-mono text-slate-900 text-sm">${iRef || 'OTC-' + iInvId}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-medium block">Payment Method:</span>
                                    <strong class="text-slate-800">${iMethod}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-medium block">Date & Time Paid:</span>
                                    <strong class="text-slate-800">${i.paid_at ? new Date(i.paid_at).toLocaleString() : 'Recorded'}</strong>
                                </div>
                                <div>
                                    <span class="text-slate-500 font-medium block">Amount Paid:</span>
                                    <strong class="text-emerald-800 text-sm font-black">₱${Number(i.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>
                                </div>
                            </div>
                        </div>
                    ` : ''}

                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Service Type</p><p class="text-sm text-slate-800">${iService}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Invoice Date</p><p class="text-sm text-slate-800">${new Date(i.invoice_date).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Due Date</p><p class="text-sm text-slate-800">${new Date(i.due_date).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Status</p><p class="text-sm font-semibold capitalize ${isPaid ? 'text-emerald-700' : 'text-amber-700'}">${iStatus}</p></div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Service Breakdown</h5>
                        <div class="space-y-2">${itemsHtml || `<div class="flex justify-between text-sm text-slate-700"><span>${iService}</span><span>₱${Number(i.amount || (i.total_amount / 1.06)).toFixed(2)}</span></div><div class="flex justify-between text-xs text-slate-400"><span>Government Tax (6%)</span><span>₱${Number(i.tax || (i.total_amount - (i.total_amount / 1.06))).toFixed(2)}</span></div>`}</div>
                        <div class="mt-2 pt-2 border-t border-slate-200 flex justify-between">
                            <span class="font-semibold text-slate-700">Total Amount</span>
                            <span class="font-bold text-brand-dark">₱${Number(i.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                        </div>
                    </div>

                    ${iNotes ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${iNotes}</p></div>` : ''}

                    <div class="flex flex-wrap justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewInvoiceModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        ${isPaid ? `
                            <button onclick="printOfficialReceipt(${i.id})" class="px-4 py-2 bg-emerald-700 text-white rounded-lg hover:bg-emerald-800 transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
                                <i class="fa-solid fa-receipt mr-1"></i> Print Official Receipt
                            </button>
                        ` : `
                            <button onclick="closeModal('viewInvoiceModal'); processPayment(${i.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold flex items-center gap-1.5">
                                <i class="fa-solid fa-credit-card mr-1"></i> Pay Now
                            </button>
                        `}
                    </div>
                </div>
            `;
        }, 300);
    }

    function printOfficialReceipt(id) {
        const i = INVOICES[id];
        if (!i) return;

        const client = i.client_name || 'Client';
        const invId = i.invoice_id || '';
        const tankId = i.tank_id || '';
        const service = i.service_type || 'Wastewater Service';
        const total = Number(i.total_amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fee = Number(i.amount || (i.total_amount / 1.06)).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const tax = Number(i.tax || (i.total_amount - (i.total_amount / 1.06))).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const method = i.payment_method || 'Over-the-Counter';
        const ref = i.payment_reference || ('OTC-' + invId);
        const datePaid = i.paid_at ? new Date(i.paid_at).toLocaleString() : new Date().toLocaleString();

        const printWindow = window.open('', '_blank', 'width=650,height=750');
        if (!printWindow) {
            showToast('Popup blocked. Please allow popups to print receipt.', 'warning');
            return;
        }

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Official Receipt - ${invId}</title>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 28px; color: #1e293b; line-height: 1.5; font-size: 13px; }
                    .header { text-align: center; border-bottom: 2px solid #0B4F4A; padding-bottom: 12px; margin-bottom: 18px; }
                    .header h2 { margin: 0 0 2px 0; color: #0B4F4A; font-size: 18px; text-transform: uppercase; letter-spacing: 0.5px; }
                    .header p { margin: 0; color: #64748b; font-size: 11px; }
                    .receipt-badge { text-align: center; margin-bottom: 16px; }
                    .receipt-badge span { background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 9999px; font-weight: 700; font-size: 12px; display: inline-block; }
                    .grid { display: flex; justify-content: space-between; margin-bottom: 14px; }
                    .col { flex: 1; }
                    .col p { margin: 3px 0; }
                    .table { width: 100%; border-collapse: collapse; margin: 16px 0; }
                    .table th { background: #f1f5f9; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #cbd5e1; }
                    .table td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
                    .table .text-right { text-align: right; }
                    .total-box { margin-top: 14px; text-align: right; border-top: 2px solid #0B4F4A; padding-top: 8px; }
                    .total-box p { margin: 3px 0; }
                    .total-box .grand-total { font-size: 16px; font-weight: 800; color: #0B4F4A; }
                    .footer { margin-top: 36px; padding-top: 12px; border-top: 1px dashed #cbd5e1; font-size: 11px; color: #64748b; text-align: center; }
                    .signature-box { margin-top: 32px; display: flex; justify-content: space-between; }
                    .sig-line { width: 180px; border-top: 1px solid #94a3b8; text-align: center; padding-top: 4px; font-size: 10px; color: #64748b; }
                    @media print { body { padding: 15px; } }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>City Health & Sanitation Office</h2>
                    <p>Wastewater Management & Environmental Services Division</p>
                    <p>Official Payment Receipt</p>
                </div>
                <div class="receipt-badge">
                    <span>OFFICIAL RECEIPT — PAID</span>
                </div>
                <div class="grid">
                    <div class="col">
                        <p><strong>Client Name:</strong> ${client}</p>
                        <p><strong>Septic Tank ID:</strong> ${tankId}</p>
                        <p><strong>Payment Method:</strong> ${method}</p>
                    </div>
                    <div class="col text-right">
                        <p><strong>Receipt / OR #:</strong> <span style="font-family:monospace; color:#0B4F4A; font-weight:bold;">${ref}</span></p>
                        <p><strong>Invoice #:</strong> ${invId}</p>
                        <p><strong>Date & Time Paid:</strong> ${datePaid}</p>
                    </div>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>${service}</td>
                            <td class="text-right">₱${fee}</td>
                        </tr>
                        <tr>
                            <td>Government Service Tax (6%)</td>
                            <td class="text-right">₱${tax}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="total-box">
                    <p class="grand-total">Total Paid: ₱${total}</p>
                </div>
                <div class="signature-box">
                    <div class="sig-line">Collecting Staff Signature</div>
                    <div class="sig-line">Payor / Client Signature</div>
                </div>
                <div class="footer">
                    <p>This serves as an Official Receipt for wastewater treatment, desludging, and maintenance services.</p>
                    <p>Generated on ${new Date().toLocaleString()}</p>
                </div>
                <script>
                    window.onload = function() {
                        window.print();
                    };
                </scr` + `ipt>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    // ============================================================
    // PROCESS PAYMENT & OVER-THE-COUNTER CASH VERIFICATION
    // ============================================================
    function generateOtcReceiptCode() {
        const d = new Date();
        const ymd = String(d.getFullYear()).slice(-2) + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0');
        const rand = Math.random().toString(36).substring(2, 6).toUpperCase();
        return `OTC-${ymd}-${rand}`;
    }

    function calculateOtcChange() {
        const amountDue = parseFloat(document.getElementById('pay_amount')?.value || 0);
        const tendered = parseFloat(document.getElementById('pay_cash_tendered')?.value || 0);
        const changeEl = document.getElementById('pay_cash_change');
        if (!changeEl) return;

        if (isNaN(tendered) || tendered <= 0) {
            changeEl.textContent = '₱0.00';
            changeEl.className = 'px-2.5 py-1.5 bg-emerald-100/80 border border-emerald-300 rounded-lg text-xs font-black text-emerald-900 flex items-center h-[34px]';
            return;
        }

        const change = tendered - amountDue;
        if (change >= 0) {
            changeEl.textContent = '₱' + change.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            changeEl.className = 'px-2.5 py-1.5 bg-emerald-100/80 border border-emerald-300 rounded-lg text-xs font-black text-emerald-900 flex items-center h-[34px]';
        } else {
            changeEl.textContent = '- ₱' + Math.abs(change).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' (Short)';
            changeEl.className = 'px-2.5 py-1.5 bg-rose-100 border border-rose-300 rounded-lg text-xs font-black text-rose-700 flex items-center h-[34px]';
        }
    }

    function onPaymentMethodChange(method) {
        document.getElementById('pay_method').value = method;
        const refInput = document.getElementById('pay_reference');
        const otcBox = document.getElementById('otc_cash_box');
        const submitBtn = document.getElementById('pay_submit_btn');
        const amountDue = parseFloat(document.getElementById('pay_amount')?.value || 0);

        if (method === 'Over-the-Counter') {
            if (otcBox) otcBox.classList.remove('hidden');
            if (refInput) {
                refInput.value = generateOtcReceiptCode();
                refInput.placeholder = 'Official Receipt No.';
            }
            const tenderInput = document.getElementById('pay_cash_tendered');
            if (tenderInput) {
                tenderInput.value = amountDue > 0 ? amountDue.toFixed(2) : '';
                calculateOtcChange();
            }
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Confirm Cash Received & Mark Paid';
            }
        } else {
            if (otcBox) otcBox.classList.add('hidden');
            if (refInput) {
                refInput.value = '';
                if (method === 'GCash') {
                    refInput.placeholder = 'Enter GCash 13-digit Reference No.';
                } else if (method === 'Maya') {
                    refInput.placeholder = 'Enter Maya Reference ID';
                } else if (method === 'Bank Transfer') {
                    refInput.placeholder = 'Enter Bank / InstaPay Transaction No.';
                }
            }
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fa-solid fa-check mr-1"></i> Verify & Process Payment';
            }
        }
    }

    function processPayment(id) {
        const i = INVOICES[id];
        if (!i) return;
        
        document.getElementById('pay_invoice_id').value = id;
        document.getElementById('pay_invoice_display').value = i.invoice_id;
        document.getElementById('pay_amount').value = i.total_amount;
        document.getElementById('pay_amount_display').value = '₱' + Number(i.total_amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        // Reset method radio to Over-the-Counter by default
        const otcRadio = document.querySelector('input[name="ww_pay_method_radio"][value="Over-the-Counter"]');
        if (otcRadio) otcRadio.checked = true;
        
        onPaymentMethodChange('Over-the-Counter');
        openModal('paymentModal');
    }

    async function savePayment(event) {
        event.preventDefault();
        try {
            const id = document.getElementById('pay_invoice_id').value;
            const method = document.getElementById('pay_method').value;
            let ref = document.getElementById('pay_reference').value.trim();

            if (!ref && method === 'Over-the-Counter') {
                ref = generateOtcReceiptCode();
            }

            const payload = {
                payment_method: method,
                payment_reference: ref
            };

            const res = await fetch(`../../api/wastewater_billing.php?id=${id}&action=mark_paid`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                closeModal('paymentModal');
                showToast('Payment confirmed & invoice marked as Paid!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to process payment', 'danger');
            }
        } catch (err) {
            console.error('savePayment error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    function updateInvoiceRow(i) {
        const row = document.getElementById('invoice-row-' + i.id);
        if (!row) return;

        const statusColors = {
            paid: 'bg-emerald-100 text-emerald-700',
            pending: 'bg-amber-100 text-amber-700',
            overdue: 'bg-rose-100 text-rose-700'
        };

        const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
        if (statusBadge) {
            statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[i.status] || statusColors.pending}`;
            statusBadge.textContent = i.status.charAt(0).toUpperCase() + i.status.slice(1);
        }

        row.dataset.status = i.status;
        row.dataset.method = i.payment_method || '';
    }

    // ============================================================
    // QUOTATION, SCALABLE SEARCH & TANK-CLIENT SYNCHRONIZATION
    // ============================================================
    const TANKS_BY_OWNER = {};
    const OWNER_BY_TANK  = {};
    <?php foreach ($septicTanks as $tank): ?>
    {
        const owner = <?php echo json_encode($tank['owner_name'] ?? ''); ?>;
        const tankId = <?php echo json_encode($tank['tank_id'] ?? ''); ?>;
        const address = <?php echo json_encode($tank['address'] . (isset($tank['barangay']) ? ', ' . $tank['barangay'] : '')); ?>;
        if (owner) {
            if (!TANKS_BY_OWNER[owner]) TANKS_BY_OWNER[owner] = [];
            TANKS_BY_OWNER[owner].push({
                tank_id: tankId,
                address: address,
                capacity: <?php echo json_encode($tank['capacity'] ?? ''); ?>
            });
        }
        if (tankId) {
            OWNER_BY_TANK[tankId] = {
                owner: owner,
                address: address
            };
        }
    }
    <?php endforeach; ?>

    function filterQuoteDropdowns(query) {
        const q = (query || '').toLowerCase().trim();
        const clientSelect = document.getElementById('quote_client');
        const tankSelect = document.getElementById('quote_tank');
        if (!clientSelect || !tankSelect) return;

        let matchCount = 0;
        let firstMatchedTank = '';
        let firstMatchedOwner = '';

        for (let i = 1; i < clientSelect.options.length; i++) {
            const opt = clientSelect.options[i];
            const text = (opt.textContent + ' ' + (opt.dataset.address || '') + ' ' + (opt.dataset.tank || '')).toLowerCase();
            const matches = !q || text.includes(q);
            opt.style.display = matches ? '' : 'none';
            if (matches && !firstMatchedOwner) firstMatchedOwner = opt.value;
        }

        for (let i = 1; i < tankSelect.options.length; i++) {
            const opt = tankSelect.options[i];
            const text = (opt.textContent + ' ' + (opt.dataset.address || '') + ' ' + (opt.dataset.owner || '')).toLowerCase();
            const matches = !q || text.includes(q);
            opt.style.display = matches ? '' : 'none';
            if (matches) {
                matchCount++;
                if (!firstMatchedTank) firstMatchedTank = opt.value;
            }
        }

        // If exactly 1 match while searching, auto-select
        if (q && matchCount === 1 && firstMatchedTank && tankSelect.value !== firstMatchedTank) {
            tankSelect.value = firstMatchedTank;
            onQuoteTankChange(firstMatchedTank);
        } else if (q && !tankSelect.value && firstMatchedOwner && clientSelect.value !== firstMatchedOwner) {
            clientSelect.value = firstMatchedOwner;
            onQuoteClientChange(firstMatchedOwner);
        }
    }

    function checkDuplicateInvoice() {
        const tankId = document.getElementById('quote_tank')?.value.trim();
        const warnBox = document.getElementById('quote_duplicate_warning');
        const warnMsg = document.getElementById('quote_duplicate_msg');
        const paidBox = document.getElementById('quote_paid_info');
        const paidMsg = document.getElementById('quote_paid_msg');
        const overrideBox = document.getElementById('quote_allow_override');
        const submitBtn = document.querySelector('#quotationForm button[type="submit"]');

        if (!warnBox || !warnMsg) return null;

        if (!tankId) {
            warnBox.classList.add('hidden');
            if (paidBox) paidBox.classList.add('hidden');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            return null;
        }

        // Find active unpaid invoice (pending or overdue)
        const activeInv = Object.values(INVOICES).find(inv => 
            inv.tank_id === tankId && 
            (inv.status === 'pending' || inv.status === 'overdue')
        );

        if (activeInv) {
            warnMsg.innerHTML = `Tank <strong>${activeInv.tank_id}</strong> already has an active unpaid invoice (<strong>${activeInv.invoice_id}</strong>, ₱${Number(activeInv.total_amount).toFixed(2)}) with status <span class="capitalize font-bold text-amber-900">${activeInv.status}</span>. Generation is restricted to prevent duplicate billing.`;
            warnBox.classList.remove('hidden');
            if (paidBox) paidBox.classList.add('hidden');

            const isAllowed = overrideBox ? overrideBox.checked : false;
            if (submitBtn) {
                submitBtn.disabled = !isAllowed;
                if (!isAllowed) {
                    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            return activeInv;
        } else {
            warnBox.classList.add('hidden');
            if (overrideBox) overrideBox.checked = false;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            // Check if tank has a previously paid invoice
            const paidInv = Object.values(INVOICES).find(inv => 
                inv.tank_id === tankId && inv.status === 'paid'
            );

            if (paidInv && paidBox && paidMsg) {
                const paidDate = paidInv.paid_at ? new Date(paidInv.paid_at).toLocaleDateString() : '';
                paidMsg.innerHTML = `Tank <strong>${paidInv.tank_id}</strong> completed and cleared previous invoice (<strong>${paidInv.invoice_id}</strong>${paidDate ? ' on ' + paidDate : ''}). This quotation will start a <strong>NEW service cycle</strong>.`;
                paidBox.classList.remove('hidden');
            } else if (paidBox) {
                paidBox.classList.add('hidden');
            }

            return null;
        }
    }

    function toggleQuoteOverride(isAllowed) {
        const submitBtn = document.querySelector('#quotationForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = !isAllowed;
            if (!isAllowed) {
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    function onQuoteClientChange(ownerName) {
        const addressInput = document.getElementById('quote_address');
        const tankSelect = document.getElementById('quote_tank');
        if (!ownerName) {
            if (addressInput) addressInput.value = '';
            checkDuplicateInvoice();
            return;
        }

        const clientSelect = document.getElementById('quote_client');
        const opt = clientSelect ? clientSelect.options[clientSelect.selectedIndex] : null;
        if (opt) {
            const tank = opt.dataset.tank || (TANKS_BY_OWNER[ownerName]?.[0]?.tank_id || '');
            const address = opt.dataset.address || (TANKS_BY_OWNER[ownerName]?.[0]?.address || '');
            if (tankSelect && tank) tankSelect.value = tank;
            if (addressInput) addressInput.value = address;
        } else if (TANKS_BY_OWNER[ownerName]?.[0]) {
            if (tankSelect) tankSelect.value = TANKS_BY_OWNER[ownerName][0].tank_id;
            if (addressInput) addressInput.value = TANKS_BY_OWNER[ownerName][0].address;
        }
        checkDuplicateInvoice();
    }

    function onQuoteTankChange(tankId) {
        const addressInput = document.getElementById('quote_address');
        const clientSelect = document.getElementById('quote_client');
        if (!tankId) {
            if (addressInput) addressInput.value = '';
            checkDuplicateInvoice();
            return;
        }

        const tankSelect = document.getElementById('quote_tank');
        const opt = tankSelect ? tankSelect.options[tankSelect.selectedIndex] : null;
        if (opt) {
            const owner = opt.dataset.owner || (OWNER_BY_TANK[tankId]?.owner || '');
            const address = opt.dataset.address || (OWNER_BY_TANK[tankId]?.address || '');
            if (clientSelect && owner) clientSelect.value = owner;
            if (addressInput) addressInput.value = address;
        } else if (OWNER_BY_TANK[tankId]) {
            if (clientSelect) clientSelect.value = OWNER_BY_TANK[tankId].owner;
            if (addressInput) addressInput.value = OWNER_BY_TANK[tankId].address;
        }
        checkDuplicateInvoice();
    }

    function updateQuotePricing() {
        const serviceSelect = document.getElementById('quote_service');
        const selectedOption = serviceSelect ? serviceSelect.options[serviceSelect.selectedIndex] : null;
        const autoFee = selectedOption ? parseFloat(selectedOption.dataset.fee || 0) : 1200;
        const tax = autoFee * 0.06;
        const total = autoFee + tax;

        const feeEl = document.getElementById('quote_fee_display');
        const taxEl = document.getElementById('quote_tax_display');
        const totalEl = document.getElementById('quote_total_display');

        if (feeEl) feeEl.textContent = '₱' + autoFee.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (taxEl) taxEl.textContent = '₱' + tax.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        if (totalEl) totalEl.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function saveQuotation(event) {
        event.preventDefault();
        try {
            const client = document.getElementById('quote_client').value.trim();
            const tank   = document.getElementById('quote_tank').value.trim();
            if (!client || !tank) {
                showToast('Please select a Client and Septic Tank.', 'warning');
                return;
            }

            const processSave = async (allowDuplicate) => {
                const serviceSelect = document.getElementById('quote_service');
                const selectedOption = serviceSelect ? serviceSelect.options[serviceSelect.selectedIndex] : null;
                const autoFee = selectedOption ? parseFloat(selectedOption.dataset.fee || 0) : 0;

                const payload = {
                    client_name: client,
                    tank_id: tank,
                    service_type: serviceSelect ? serviceSelect.value : 'Desludging (Residential)',
                    amount: autoFee,
                    tax: autoFee * 0.06,
                    notes: document.getElementById('quote_notes').value.trim(),
                    allow_duplicate: allowDuplicate
                };
                const autoPay = document.getElementById('quote_auto_pay')?.checked;
                const res = await fetch('../../api/wastewater_billing.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) {
                    const newInv = json.data;
                    closeModal('quotationModal');
                    showToast('Quotation invoice generated successfully!', 'success');
                    
                    if (autoPay && newInv && newInv.id) {
                        INVOICES[newInv.id] = newInv;
                        setTimeout(() => {
                            processPayment(newInv.id);
                        }, 350);
                    } else {
                        setTimeout(() => location.reload(), 700);
                    }
                } else {
                    showToast(json.message || 'Failed to create invoice', 'danger');
                }
            };

            // 🛡️ Duplication Guard Check
            const duplicate = checkDuplicateInvoice();
            if (duplicate) {
                ModalSystem.confirm(
                    `Notice: An active unpaid invoice (${duplicate.invoice_id}) already exists for Tank ${duplicate.tank_id}.<br><br>Do you want to proceed and generate another invoice anyway?`,
                    async () => {
                        await processSave(true);
                    }
                );
                return;
            }

            await processSave(false);
        } catch (err) {
            console.error('saveQuotation error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // FEE STRUCTURE MANAGEMENT (HEAD & ADMIN ONLY)
    // ============================================================
    const CAN_MANAGE_FEES = <?php echo json_encode($canManageFees); ?>;

    function limitFeeInput(input) {
        const parts = String(input.value || '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 11);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    function isValidFee(value) {
        return /^\d{1,11}(\.\d{1,2})?$/.test(String(value)) && Number(value) >= 0 && Number(value) <= 99999999999.99;
    }

    function editFeeAtIndex(index) {
        openModal('feeStructureModal');
        setTimeout(() => {
            const input = document.getElementById('fee_' + index);
            if (input) {
                input.focus();
                input.select();
            }
        }, 150);
    }

    async function saveNewFeeStructure(event) {
        event.preventDefault();
        if (!CAN_MANAGE_FEES) {
            showToast('Access Restricted: Only Department Heads and Admins can add fee structures.', 'warning');
            return;
        }

        const category = document.getElementById('new_fee_category')?.value.trim();
        const baseFee  = document.getElementById('new_fee_amount')?.value.trim();
        const perUnit  = document.getElementById('new_fee_per_unit')?.value.trim() || '';
        const desc     = document.getElementById('new_fee_description')?.value.trim() || '';

        if (!category) {
            showToast('Please enter a Fee Category name.', 'warning');
            return;
        }

        if (!isValidFee(baseFee) || Number(baseFee) <= 0) {
            showToast('Base fee must be a valid positive number.', 'warning');
            return;
        }

        const csrfToken = document.querySelector('#addFeeModal input[name="csrf_token"]')?.value || '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';

        try {
            const formData = new FormData();
            formData.append('action', 'add_fee');
            formData.append('csrf_token', csrfToken);
            formData.append('category', category);
            formData.append('base_fee', baseFee);
            formData.append('per_unit', perUnit);
            formData.append('description', desc);

            const res = await fetch('wastewater_billing.php', {
                method: 'POST',
                body: formData
            });

            const json = await res.json();
            if (json.success) {
                showToast(json.message || 'Fee category added successfully!', 'success');
                closeModal('addFeeModal');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(json.message || 'Failed to add fee category', 'danger');
            }
        } catch (err) {
            console.error('saveNewFeeStructure error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    async function updateFee(index) {
        if (!CAN_MANAGE_FEES) {
            showToast('Access Restricted: Only Department Heads and Admins can update fees.', 'warning');
            return;
        }

        const input = document.getElementById('fee_' + index);
        const value = input ? input.value.trim() : '';
        if (!isValidFee(value)) {
            showToast('Fee must be a valid number with no more than 11 whole-number digits.', 'warning');
            return;
        }

        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';

        try {
            const formData = new FormData();
            formData.append('action', 'update_fee');
            formData.append('csrf_token', csrfToken);
            formData.append('index', index);
            formData.append('base_fee', value);

            const res = await fetch('wastewater_billing.php', {
                method: 'POST',
                body: formData
            });

            const json = await res.json();
            if (json.success) {
                showToast(json.message || 'Fee updated to ₱' + parseFloat(value).toFixed(2), 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(json.message || 'Failed to update fee', 'danger');
            }
        } catch (err) {
            console.error('updateFee error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    async function deleteFee(index) {
        if (!CAN_MANAGE_FEES) {
            showToast('Access Restricted: Only Department Heads and Admins can delete fee categories.', 'warning');
            return;
        }

        ModalSystem.confirm('Are you sure you want to remove this fee category?', async () => {
            const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>';

            try {
                const formData = new FormData();
                formData.append('action', 'delete_fee');
                formData.append('csrf_token', csrfToken);
                formData.append('index', index);

                const res = await fetch('wastewater_billing.php', {
                    method: 'POST',
                    body: formData
                });

                const json = await res.json();
                if (json.success) {
                    showToast(json.message || 'Fee category removed.', 'success');
                    setTimeout(() => location.reload(), 700);
                } else {
                    showToast(json.message || 'Failed to remove fee', 'danger');
                }
            } catch (err) {
                console.error('deleteFee error:', err);
                showToast('An error occurred: ' + err.message, 'danger');
            }
        });
    }

    // ============================================================
    // DOWNLOAD INVOICE
    // ============================================================
    function downloadInvoice(invoiceId) {
        showToast('Printing invoice...', 'info');
    }

    // ============================================================
    // EDIT INVOICE
    // ============================================================
    function editInvoice(id) {
        const invoice = INVOICES[id];
        if (!invoice) return;
        document.getElementById('edit_invoice_id').value = invoice.id;
        document.getElementById('edit_invoice_client').value = invoice.client_name;
        document.getElementById('edit_invoice_tank').value = invoice.tank_id;
        document.getElementById('edit_invoice_service').value = invoice.service_type;
        document.getElementById('edit_invoice_amount').value = invoice.amount;
        document.getElementById('edit_invoice_tax').value = invoice.tax;
        document.getElementById('edit_invoice_due_date').value = invoice.due_date;
        document.getElementById('edit_invoice_status').value = invoice.status;
        document.getElementById('edit_invoice_notes').value = invoice.notes || '';
        openModal('editInvoiceModal');
    }

    async function saveInvoiceEdit(event) {
        event.preventDefault();
        try {
            const amount = document.getElementById('edit_invoice_amount').value;
            const tax = document.getElementById('edit_invoice_tax').value;
            if (!isValidFee(amount) || !isValidFee(tax)) {
                showToast('Fee and tax must contain no more than 11 whole-number digits.', 'warning');
                return;
            }
            const id = document.getElementById('edit_invoice_id').value;
            const payload = {
                client_name: document.getElementById('edit_invoice_client').value.trim(),
                tank_id: document.getElementById('edit_invoice_tank').value.trim(),
                service_type: document.getElementById('edit_invoice_service').value.trim(),
                amount: Number(amount),
                tax: Number(tax),
                due_date: document.getElementById('edit_invoice_due_date').value,
                status: document.getElementById('edit_invoice_status').value,
                notes: document.getElementById('edit_invoice_notes').value.trim()
            };

            const res = await fetch(`../../api/wastewater_billing.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                closeModal('editInvoiceModal');
                showToast('Invoice updated successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to update invoice', 'danger');
            }
        } catch (err) {
            console.error('saveInvoiceEdit error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // Toast, openModal, closeModal, sanitizeHTML, exportTableToCSV provided by common.js

    // ============================================================
    // FEE STRUCTURE TOGGLE
    // ============================================================
    function toggleFeeStructure() {
        const body = document.getElementById('feeStructureBody');
        const text = document.getElementById('feeToggleText');
        const icon = document.getElementById('feeToggleIcon');
        if (!body) return;

        if (body.classList.contains('hidden')) {
            body.classList.remove('hidden');
            if (text) text.textContent = 'Hide Rates';
            if (icon) icon.classList.add('rotate-180');
        } else {
            body.classList.add('hidden');
            if (text) text.textContent = 'Show Rates';
            if (icon) icon.classList.remove('rotate-180');
        }
    }

    // ============================================================
    // DATE RANGE FILTER MODAL HANDLERS
    // ============================================================
    function setDatePreset(preset) {
        const fromInput = document.getElementById('modalDateFrom');
        const toInput = document.getElementById('modalDateTo');
        const today = new Date();

        const formatDate = (d) => {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        };

        if (preset === 'today') {
            const todayStr = formatDate(today);
            fromInput.value = todayStr;
            toInput.value = todayStr;
        } else if (preset === 'last7') {
            const past = new Date();
            past.setDate(today.getDate() - 7);
            fromInput.value = formatDate(past);
            toInput.value = formatDate(today);
        } else if (preset === 'thisMonth') {
            const start = new Date(today.getFullYear(), today.getMonth(), 1);
            const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (preset === 'last30') {
            const past = new Date();
            past.setDate(today.getDate() - 30);
            fromInput.value = formatDate(past);
            toInput.value = formatDate(today);
        } else if (preset === 'thisYear') {
            const start = new Date(today.getFullYear(), 0, 1);
            const end = new Date(today.getFullYear(), 11, 31);
            fromInput.value = formatDate(start);
            toInput.value = formatDate(end);
        } else if (preset === 'all') {
            fromInput.value = '';
            toInput.value = '';
        }
    }

    function applyDateFilterModal() {
        const modalFrom = document.getElementById('modalDateFrom').value;
        const modalTo = document.getElementById('modalDateTo').value;

        document.getElementById('filterDateFrom').value = modalFrom;
        document.getElementById('filterDateTo').value = modalTo;

        updateDateFilterButtonLabel();
        filterInvoices();
        closeModal('dateFilterModal');
    }

    function clearDateFilterModal() {
        document.getElementById('modalDateFrom').value = '';
        document.getElementById('modalDateTo').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';

        updateDateFilterButtonLabel();
        filterInvoices();
        closeModal('dateFilterModal');
    }

    function updateDateFilterButtonLabel() {
        const from = document.getElementById('filterDateFrom').value;
        const to = document.getElementById('filterDateTo').value;
        const label = document.getElementById('dateFilterLabel');
        const dot = document.getElementById('dateFilterActiveDot');
        const btn = document.getElementById('dateFilterBtn');

        if (!from && !to) {
            if (label) label.textContent = 'Date Filter';
            if (dot) dot.classList.add('hidden');
            if (btn) btn.className = 'px-3 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold flex items-center gap-2 transition whitespace-nowrap';
        } else {
            if (dot) dot.classList.remove('hidden');
            if (btn) btn.className = 'px-3 py-2 border border-brand-medium bg-brand-light/60 text-brand-dark rounded-lg text-sm font-bold flex items-center gap-2 transition whitespace-nowrap shadow-xs';

            if (from && to) {
                if (from === to) {
                    if (label) label.textContent = from;
                } else {
                    if (label) label.textContent = `${from} to ${to}`;
                }
            } else if (from) {
                if (label) label.textContent = `From ${from}`;
            } else if (to) {
                if (label) label.textContent = `Up to ${to}`;
            }
        }
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchInvoice').addEventListener('input', filterInvoices);
    document.getElementById('filterStatus').addEventListener('change', filterInvoices);
    document.getElementById('filterMethod').addEventListener('change', filterInvoices);

    function filterInvoices() {
        const searchInput = document.getElementById('searchInvoice');
        const search = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const status = document.getElementById('filterStatus')?.value || '';
        const method = (document.getElementById('filterMethod')?.value || '').toLowerCase();
        const dateFrom = document.getElementById('filterDateFrom')?.value || '';
        const dateTo = document.getElementById('filterDateTo')?.value || '';
        let visibleCount = 0;

        document.querySelectorAll('.invoice-row').forEach(row => {
            const client = (row.dataset.client || '').toLowerCase();
            const tank = (row.dataset.tank || '').toLowerCase();
            const service = (row.dataset.service || '').toLowerCase();
            const id = (row.dataset.id || '').toLowerCase();
            const rowStatus = row.dataset.status || '';
            const rowMethod = (row.dataset.method || '').toLowerCase();
            const invoiceDate = row.dataset.invoiceDate || '';
            const rowText = (row.textContent || row.innerText || '').toLowerCase();

            const matchesSearch = !search || 
                                  client.includes(search) || 
                                  tank.includes(search) || 
                                  service.includes(search) || 
                                  id.includes(search) || 
                                  rowText.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesMethod = !method || rowMethod === method;
            const matchesDateFrom = !dateFrom || (invoiceDate && invoiceDate >= dateFrom);
            const matchesDateTo = !dateTo || (invoiceDate && invoiceDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesMethod && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        const emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        }
    }

    function resetFilters() {
        document.getElementById('searchInvoice').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterMethod').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        const modalFrom = document.getElementById('modalDateFrom');
        const modalTo = document.getElementById('modalDateTo');
        if (modalFrom) modalFrom.value = '';
        if (modalTo) modalTo.value = '';
        updateDateFilterButtonLabel();
        document.querySelectorAll('.invoice-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    // ============================================================
    // INCOMING SERVICE / MAINTENANCE ROUTING HANDLER
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const action = urlParams.get('action');
        const client = urlParams.get('client');
        const tankId = urlParams.get('tank_id');
        const serviceType = urlParams.get('service_type');

        if (action === 'new_quote') {
            // Immediately clean URL so page reloads do not trigger recurring popups
            window.history.replaceState({}, document.title, window.location.pathname);

            if (client && tankId) {
                showToast(`Auto-generating invoice for ${client}...`, 'info');
                
                // Approximate standard fee, can be overridden later
                const autoFee = 1500; 
                const tax = autoFee * 0.06;

                fetch('../../api/wastewater_billing.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        client_name: client,
                        tank_id: tankId,
                        service_type: serviceType || 'Maintenance',
                        amount: autoFee,
                        tax: tax,
                        notes: 'Auto-generated invoice from service completion.',
                        allow_duplicate: false
                    })
                }).then(r => r.json()).then(json => {
                    if (json.success) {
                        showToast('Invoice generated successfully! Please process payment.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else if (json.code === 409) {
                        // Already exists
                        showToast('An active unpaid invoice already exists. Please process payment.', 'info');
                    } else {
                        showToast(json.message || 'Failed to auto-generate invoice', 'danger');
                    }
                }).catch(err => {
                    console.error('Invoice auto-generation error:', err);
                    showToast('Failed to auto-generate invoice.', 'danger');
                });
            } else {
                showToast('Missing client or tank information for auto-billing.', 'warning');
            }
        } else if (action === 'manage_fees') {
            window.history.replaceState({}, document.title, window.location.pathname);
            setTimeout(() => {
                openModal('feeStructureModal');
            }, 250);
        }
    });

    // ESC key and backdrop-click handled by common.js
</script>

<?php include_once '../../includes/footer.php'; ?>