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
// 1. PHP BACKEND - Initialize
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('sanitation permits');
require_once __DIR__ . '/../../includes/toast.php'; // <-- ADDED to enable toasts

// Role-Based Authorization for Fee Structure Management (Head Roles & Admins Only)
$userRoleDesc = trim($_SESSION['role_description'] ?? '');
$userRole     = trim($_SESSION['role'] ?? '');
$combinedRole = strtolower($userRole . ' ' . $userRoleDesc);

$isAdminRole = (bool) preg_match('/admin|administrator|superadmin|system administrator|system admin/i', $combinedRole);
$isHeadRole  = (bool) preg_match('/health center director|sanitation director|immunization lead|immunization coordinator|waste\s*water lead|surveil{1,2}ance lead|surveillance coordinator|director|coordinator|lead|head|department head|manager/i', $combinedRole);
$canManageFees = $isAdminRole || $isHeadRole || (function_exists('hasPermission') && hasPermission('sanitation.manage'));

$title = 'Payments';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
?>

<!-- ============================================================ -->
<!-- 2. HTML + Tailwind CSS                                      -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Payments</h2>
            <p class="text-sm text-slate-500 mt-0.5">Fee structure, payment processing & history</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openProcessPaymentModal()"
                class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-credit-card text-xs"></i> Process Payment
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS                                           -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Payments -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-credit-card text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900" id="statTotal">0</p>
                        <p class="text-xs font-medium text-slate-500">Total Payments</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">💳 All payments</span>
                    <span class="text-[10px] text-slate-400"><span id="statCompletedMini">0</span> completed</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Completed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600" id="statCompleted">0</p>
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Success</span>
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
                        <p class="text-2xl font-black text-amber-600" id="statPending">0</p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400">Payment processing</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Failed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-circle-xmark text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600" id="statFailed">0</p>
                        <p class="text-xs font-medium text-slate-500">Failed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">❌ Failed</span>
                    <span class="text-[10px] text-slate-400">Requires attention</span>
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
                        <p class="text-2xl font-black text-brand-dark" id="statRevenue">₱0</p>
                        <p class="text-xs font-medium text-slate-500">Total Revenue</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">💰 Collected</span>
                    <span class="text-[10px] text-slate-400">From payments</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Permits Alert -->
    <div id="pendingAlert" class="hidden bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-lg"></i>
            <span class="text-sm text-amber-700"><span class="font-bold" id="pendingCount">0</span> permits require payment</span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='pending'; loadPayments(1);"
            class="text-xs font-semibold text-amber-700 hover:text-amber-900 underline">
            View pending
        </button>
    </div>

    <!-- Fee Structure Collapsible Reference (Collapsed by Default so it doesn't interrupt staff) -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 mb-6 overflow-hidden">
        <button type="button" onclick="toggleFeeStructure()" class="w-full px-4 py-3 bg-slate-50/70 hover:bg-slate-100/70 transition flex items-center justify-between text-left">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-brand-light flex items-center justify-center text-brand-dark flex-shrink-0">
                    <i class="fa-solid fa-table-list text-sm"></i>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-slate-800">Fee Structure Reference</h4>
                    <p class="text-xs text-slate-400">Official municipal fees for sanitary inspection, health certificates, and sanitary permits</p>
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
                    <span class="text-xs text-slate-500 font-medium">Standard Municipal Rates (Based on Ordinance No. 0386)</span>
                </div>
                <?php if ($canManageFees): ?>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-brand-light text-brand-dark border border-brand-border">
                        <i class="fa-solid fa-shield-halved mr-1"></i> Authorized (Head & Admin)
                    </span>
                    <button type="button" onclick="openFeeStructureModal()" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-pen text-xs"></i> Manage Rates
                    </button>
                </div>
                <?php else: ?>
                <span class="text-[11px] text-slate-400 italic">
                    <i class="fa-solid fa-lock text-xs mr-1"></i> Rates management restricted to Department Heads and Administrators
                </span>
                <?php endif; ?>
            </div>
            <div id="feeGridContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <div class="col-span-full text-center py-6 text-slate-400 text-xs flex items-center justify-center gap-2">
                    <div class="w-4 h-4 border-2 border-brand-light border-t-brand-dark rounded-full animate-spin"></div>
                    Loading fee categories...
                </div>
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
                    id="searchPayment"
                    placeholder="Search by permit ID, applicant, or reference..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>

            <!-- Integrated Control Bar: Filters, Date Picker Modal, Process Payment & Export -->
            <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                <!-- Status Filter -->
                <select id="filterStatus" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white text-slate-700">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                    <option value="refunded">Refunded</option>
                </select>

                <!-- Method Filter -->
                <select id="filterMethod" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white text-slate-700">
                    <option value="">All Methods</option>
                    <option value="cash">Cash / OTC</option>
                    <option value="gcash">GCash</option>
                    <option value="paymaya">PayMaya</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="over_the_counter">Over-the-Counter</option>
                </select>

                <!-- Date Range Filter Modal Trigger (Replaces raw native date inputs) -->
                <button type="button" onclick="openModal('dateFilterModal')" id="dateFilterBtn" class="px-3 py-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 rounded-lg text-sm font-semibold flex items-center gap-2 transition whitespace-nowrap" title="Filter by date range">
                    <i class="fa-regular fa-calendar text-slate-400 text-xs"></i>
                    <span id="dateFilterLabel">Date Filter</span>
                    <span id="dateFilterActiveDot" class="hidden w-2 h-2 rounded-full bg-brand-medium"></span>
                </button>

                <!-- Hidden inputs to store active date range for the filter -->
                <input type="hidden" id="filterDateFrom">
                <input type="hidden" id="filterDateTo">

                <!-- Reset Filter Button -->
                <button type="button" onclick="resetFilters()" title="Reset all filters"
                    class="p-2 border border-slate-200 bg-white hover:bg-slate-50 text-slate-500 hover:text-slate-700 rounded-lg text-sm transition">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>

                <div class="h-6 w-[1px] bg-slate-200 hidden sm:block"></div>

                <!-- Export Fitted in Toolbar -->
                <button onclick="exportPaymentsCSV()"
                    class="px-3 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2 whitespace-nowrap"
                    title="Export to CSV" aria-label="Export payments to CSV">
                    <i class="fa-solid fa-file-csv text-xs"></i> Export
                </button>
            </div>
        </div>
    </div>

    <!-- Payment History Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Payment ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Permit/Applicant</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Method</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="paymentTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-10">
                            <div class="w-10 h-10 border-4 border-brand-light border-t-brand-dark rounded-full animate-spin mx-auto mb-3"></div>
                            <p class="text-sm text-slate-500">Loading payments...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-credit-card text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600" id="emptyStateTitle">No payments match your filters</p>
            <p class="text-xs text-slate-400 mt-1" id="emptyStateSubtitle">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" id="emptyStateClearBtn" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500" id="paginationInfo">Loading...</p>
            <div class="flex gap-1" id="paginationButtons"></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- PROCESS PAYMENT MODAL                                        -->
<!-- ============================================================ -->
<div id="processPaymentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-brand-medium"></i>
                Process Payment
            </h3>
            <button onclick="closeModal('processPaymentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="processPaymentForm" class="p-6 space-y-4" onsubmit="savePayment(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Permit / Applicant</label>
                <select id="payment_permit" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Loading permits...</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Amount</label>
                <input type="number" id="payment_amount" required min="0.01" max="1000000" step="0.01" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none maskable">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Payment Method</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2" id="paymentMethods">
                    <!-- GCash -->
                    <label class="flex items-center justify-center p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="payment_method_radio" value="gcash" class="sr-only">
                        <img src="<?php echo site_url('assets/images/payments/gcash.png'); ?>" alt="GCash" class="h-6 w-auto object-contain">
                    </label>

                    <!-- Maya -->
                    <label class="flex items-center justify-center p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="payment_method_radio" value="paymaya" class="sr-only">
                        <img src="<?php echo site_url('assets/images/payments/maya.png'); ?>" alt="Maya" class="h-6 w-auto object-contain">
                    </label>

                    <!-- Landbank -->
                    <label class="flex items-center justify-center p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="payment_method_radio" value="bank_transfer" class="sr-only">
                        <img src="<?php echo site_url('assets/images/payments/landbank.png'); ?>" alt="Landbank" class="h-7 w-auto object-contain">
                    </label>

                    <!-- Municipal OTC -->
                    <label class="flex items-center justify-center gap-1.5 p-2 h-12 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-400 has-[:checked]:border-brand-dark has-[:checked]:ring-2 has-[:checked]:ring-brand-dark/20 transition">
                        <input type="radio" name="payment_method_radio" value="over_the_counter" class="sr-only" checked>
                        <i class="fa-solid fa-landmark text-slate-700 text-sm"></i>
                        <span class="text-xs font-semibold text-slate-800">Over-the-Counter</span>
                    </label>
                </div>
                <input type="hidden" id="payment_method" value="over_the_counter">
            </div>
            <div id="referenceSection" class="hidden">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Transaction / Reference Number <span class="text-rose-500">*</span></label>
                <input type="text" id="payment_reference" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium" placeholder="Enter reference number from payment app/receipt">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="payment_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Payment notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('processPaymentModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-credit-card mr-1.5"></i> Process Payment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW PAYMENT MODAL                                           -->
<!-- ============================================================ -->
<div id="viewPaymentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Payment Details</h3>
            <button onclick="closeModal('viewPaymentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="paymentDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- FEE STRUCTURE MODAL                                          -->
<!-- ============================================================ -->
<div id="feeStructureModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-table-list text-brand-medium"></i>
                Fee Structure
            </h3>
            <button onclick="closeModal('feeStructureModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6" id="feeStructureModalContent">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- DATE RANGE FILTER MODAL                                      -->
<!-- ============================================================ -->
<div id="dateFilterModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-regular fa-calendar-days text-brand-medium"></i>
                Filter Payments by Date Range
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

<!-- ============================================================ -->
<!-- JAVASCRIPT - Full API Integration                           -->
<!-- ============================================================ -->
<script>
    // ============================================================
    // API CLIENTS - query-string based (matches api/inspections.php
    // and the fixed api/payments.php router - no PATH_INFO required)
    // ============================================================
    class PaymentApi {
        constructor(baseUrl = '../../api/payments.php') {
            this.baseUrl = baseUrl;
            this.token = localStorage.getItem('auth_token');
        }

        async request(params = {}, options = {}) {
            const query = new URLSearchParams(params).toString();
            const url = query ? `${this.baseUrl}?${query}` : this.baseUrl;
            const csrfToken = window.CrudAjax ? window.CrudAjax.getCsrfToken() : '';
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    ...(csrfToken && { 'X-CSRF-Token': csrfToken }),
                    ...(this.token && {
                        'Authorization': `Bearer ${this.token}`
                    }),
                },
                ...options,
            };
            const response = await fetch(url, config);
            const data = await response.json();
            if (!response.ok || data.success === false) throw new Error(data.message || 'API request failed');
            return data;
        }

        async getPayments(filters = {}) {
            const params = {};
            Object.entries(filters).forEach(([key, value]) => {
                if (value) params[key] = value;
            });
            return this.request(params);
        }

        async getPayment(id) {
            return this.request({
                id
            });
        }

        async createPayment(data) {
            return this.request({}, {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }

        async completePayment(id) {
            return this.request({
                id,
                action: 'complete'
            }, {
                method: 'POST'
            });
        }

        async getStats() {
            return this.request({
                stats: 'true'
            });
        }
        async getFeeStructure() {
            return this.request({
                fee_structure: 'true'
            });
        }
    }

    class PermitApi {
        constructor(baseUrl = '../../api/permits.php') {
            this.baseUrl = baseUrl;
            this.token = localStorage.getItem('auth_token');
        }

        async request(params = {}, options = {}) {
            const query = new URLSearchParams(params).toString();
            const url = query ? `${this.baseUrl}?${query}` : this.baseUrl;
            const csrfToken = window.CrudAjax ? window.CrudAjax.getCsrfToken() : '';
            const config = {
                headers: {
                    'Content-Type': 'application/json',
                    ...(csrfToken && { 'X-CSRF-Token': csrfToken }),
                    ...(this.token && {
                        'Authorization': `Bearer ${this.token}`
                    }),
                },
                ...options,
            };
            const response = await fetch(url, config);
            const data = await response.json();
            if (!response.ok || data.success === false) throw new Error(data.message || 'API request failed');
            return data;
        }

        async getPermits(filters = {}) {
            const params = {};
            Object.entries(filters).forEach(([key, value]) => {
                if (value) params[key] = value;
            });
            return this.request(params);
        }
    }

    const paymentApi = new PaymentApi();
    const permitApi = new PermitApi();

    // ============================================================
    // GLOBAL STATE & ACCESS CONTROL
    // ============================================================
    const CAN_MANAGE_FEES = <?php echo json_encode($canManageFees); ?>;
    let currentPage = <?php echo $page; ?>;
    let currentLimit = <?php echo $limit; ?>;
    let totalPages = 1;

    // ============================================================
    // LOAD PAYMENTS
    // ============================================================
    async function loadPayments(page = currentPage) {
        try {
            const filters = {
                page: page,
                limit: currentLimit,
                status: document.getElementById('filterStatus').value,
                method: document.getElementById('filterMethod').value,
                search: document.getElementById('searchPayment').value,
                date_from: document.getElementById('filterDateFrom').value,
                date_to: document.getElementById('filterDateTo').value,
            };

            if (filters.date_from && filters.date_to && filters.date_from > filters.date_to) {
                showToast('The start date cannot be after the end date', 'danger');
                return;
            }

            const result = await paymentApi.getPayments(filters);
            totalPages = result.total_pages || 1;
            currentPage = page;

            renderPaymentTable(result.data, hasActiveFilters());
            updatePagination(result.page, result.total_pages, result.total);
        } catch (error) {
            console.error('Failed to load payments:', error);
            document.getElementById('paymentTableBody').innerHTML =
                '<tr><td colspan="8" class="text-center py-10 text-rose-500">Failed to load payments: ' + error.message + '</td></tr>';
        }
    }

    function hasActiveFilters() {
        return !!(document.getElementById('searchPayment').value.trim() ||
            document.getElementById('filterStatus').value ||
            document.getElementById('filterMethod').value ||
            document.getElementById('filterDateFrom').value ||
            document.getElementById('filterDateTo').value);
    }

    // ============================================================
    // LOAD STATISTICS
    // ============================================================
    async function loadStats() {
        try {
            const result = await paymentApi.getStats();
            const stats = result.data;

            document.getElementById('statTotal').textContent = stats.total || 0;
            document.getElementById('statCompleted').textContent = stats.completed || 0;
            document.getElementById('statCompletedMini').textContent = stats.completed || 0;
            document.getElementById('statPending').textContent = stats.pending || 0;
            document.getElementById('statFailed').textContent = stats.failed || 0;
            document.getElementById('statRevenue').textContent = '₱' + (stats.total_revenue || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            if (stats.pending_permits > 0) {
                document.getElementById('pendingAlert').classList.remove('hidden');
                document.getElementById('pendingCount').textContent = stats.pending_permits;
            } else {
                document.getElementById('pendingAlert').classList.add('hidden');
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

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
    // LOAD FEE STRUCTURE
    // ============================================================
    async function loadFeeStructure() {
        try {
            const result = await paymentApi.getFeeStructure();
            const fees = result.data || [];
            const grid = document.getElementById('feeGridContainer');
            if (!grid) return;

            if (fees.length === 0) {
                grid.innerHTML = '<div class="col-span-full text-center py-6 text-slate-400 text-xs">No fee categories configured</div>';
                return;
            }

            grid.innerHTML = fees.map((fee, idx) => `
                <div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200 hover:shadow-xs hover:border-brand-border transition group relative">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-bold text-slate-800 text-sm">${escapeHtml(fee.category)}</p>
                            <div class="flex items-center gap-2 mt-1 text-xs text-slate-500">
                                <span>Base: ₱${parseFloat(fee.base_fee || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                                <span>•</span>
                                <span>Inspection: ₱${parseFloat(fee.inspection_fee || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                            </div>
                        </div>
                        ${CAN_MANAGE_FEES ? `
                        <button onclick="openFeeStructureModal()" title="Edit Rates" class="opacity-0 group-hover:opacity-100 transition w-6 h-6 rounded-md bg-white border border-slate-200 text-slate-500 hover:text-brand-dark flex items-center justify-center text-xs shadow-2xs">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        ` : ''}
                    </div>
                    <div class="mt-2.5 flex items-baseline justify-between pt-2 border-t border-slate-200/60">
                        <span class="text-xs text-slate-400 font-medium">Total Municipal Rate:</span>
                        <p class="text-base font-extrabold text-brand-dark">₱${parseFloat(fee.total || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                    </div>
                </div>
            `).join('');

            window.feeStructureData = fees;
        } catch (error) {
            const grid = document.getElementById('feeGridContainer');
            if (grid) {
                grid.innerHTML = '<div class="col-span-full text-center py-4 text-rose-500 text-xs">Failed to load fee structure</div>';
            }
        }
    }

    // ============================================================
    // LOAD PERMITS FOR DROPDOWN
    // ============================================================
    async function loadPermitsForDropdown() {
        try {
            const result = await permitApi.getPermits({
                limit: 100
            });

            const select = document.getElementById('payment_permit');
            select.innerHTML = '<option value="">Select Permit</option>';

            result.data.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.dataset.fee = p.fee || 0;
                option.textContent = `${p.permit_id} - ${p.applicant} (₱${parseFloat(p.fee || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})})`;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Failed to load permits:', error);
        }
    }

    // ============================================================
    // RENDER PAYMENT TABLE
    // ============================================================
    function renderPaymentTable(payments, filtersActive = false) {
        const tbody = document.getElementById('paymentTableBody');
        const emptyState = document.getElementById('emptyState');
        const emptyTitle = document.getElementById('emptyStateTitle');
        const emptySubtitle = document.getElementById('emptyStateSubtitle');
        const emptyClearBtn = document.getElementById('emptyStateClearBtn');

        if (!payments || payments.length === 0) {
            tbody.innerHTML = '';
            if (filtersActive) {
                emptyTitle.textContent = 'No payments match your filters';
                emptySubtitle.textContent = 'Try adjusting your search or clearing filters';
                emptyClearBtn.style.display = 'inline-block';
            } else {
                emptyTitle.textContent = 'No payments yet';
                emptySubtitle.textContent = 'Processed payments will show up here';
                emptyClearBtn.style.display = 'none';
            }
            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
            return;
        }

        emptyState.classList.add('hidden');
        emptyState.classList.remove('flex');

        const statusColors = {
            completed: 'bg-emerald-100 text-emerald-700',
            pending: 'bg-amber-100 text-amber-700',
            failed: 'bg-rose-100 text-rose-700',
            refunded: 'bg-purple-100 text-purple-700'
        };

        const methodIcons = {
            cash: 'fa-money-bill-wave',
            gcash: 'fa-mobile-screen',
            paymaya: 'fa-mobile-screen',
            bank_transfer: 'fa-building-columns',
            over_the_counter: 'fa-store'
        };

        const methodColors = {
            cash: 'text-emerald-600',
            gcash: 'text-blue-600',
            paymaya: 'text-green-600',
            bank_transfer: 'text-amber-600',
            over_the_counter: 'text-slate-600'
        };

        const methodNames = {
            cash: 'Cash',
            gcash: 'GCash',
            paymaya: 'PayMaya',
            bank_transfer: 'Bank Transfer',
            over_the_counter: 'OTC'
        };

        tbody.innerHTML = payments.map(payment => {
            const permitInfo = payment.permits || {};
            const applicant = permitInfo.applicant || 'Unknown';
            const permitId = permitInfo.permit_id || '—';
            const maskedApplicant = maskName(applicant);
            const maskedPermitId = maskId(permitId);
            const maskedRef = maskId(payment.reference_number || '—');
            const formattedAmount = '₱' + parseFloat(payment.amount).toLocaleString('en-PH', {minimumFractionDigits: 2});

            return `
        <tr class="border-b border-slate-100 hover:bg-brand-light/30 transition-colors payment-row"
            data-id="${payment.id}">
            <td class="px-4 py-3"><span class="font-mono text-xs font-bold text-brand-dark bg-brand-light/70 px-2 py-0.5 rounded border border-brand-border/40">${escapeHtml(payment.payment_id || '')}</span></td>
            <td class="px-4 py-3">
                <div>
                    <p class="font-semibold text-slate-800 text-sm maskable" data-real="${escapeHtml(applicant)}" data-masked="${escapeHtml(maskedApplicant)}">${escapeHtml(applicant)}</p>
                    <p class="text-xs text-slate-400 maskable" data-real="${escapeHtml(permitId)}" data-masked="${escapeHtml(maskedPermitId)}">${escapeHtml(permitId)}</p>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="text-sm font-extrabold text-slate-800 maskable" data-real="${escapeHtml(formattedAmount)}" data-masked="₱••••••">${escapeHtml(formattedAmount)}</span>
            </td>
            <td class="px-4 py-3 text-slate-600 text-xs">
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-100 font-medium">
                    <i class="fa-solid ${methodIcons[payment.method] || 'fa-credit-card'} ${methodColors[payment.method] || ''}"></i>
                    ${escapeHtml(methodNames[payment.method] || payment.method || '')}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs font-mono maskable" data-real="${escapeHtml(payment.reference_number || '—')}" data-masked="${escapeHtml(maskedRef)}">${escapeHtml(payment.reference_number || '—')}</td>
            <td class="px-4 py-3">
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold ${statusColors[payment.status] || statusColors.pending}">
                    ${escapeHtml((payment.status || '').charAt(0).toUpperCase() + (payment.status || '').slice(1))}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs">${escapeHtml(new Date(payment.created_at).toLocaleString())}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="viewPayment(${payment.id})"
                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>
                    ${payment.receipt_path ? `
                        <button onclick="downloadReceipt('${payment.receipt_path}')"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Download Receipt">
                            <i class="fa-solid fa-receipt text-sm"></i>
                        </button>
                    ` : ''}
                    ${payment.status === 'pending' ? `
                        <button onclick="completePayment(${payment.id})"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Mark as Completed">
                            <i class="fa-solid fa-check text-sm"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>`;
        }).join('');
    }

    // ============================================================
    // UPDATE PAGINATION
    // ============================================================
    function updatePagination(page, totalPagesCount, total) {
        const start = (page - 1) * currentLimit + 1;
        const end = Math.min(page * currentLimit, total);

        document.getElementById('paginationInfo').textContent = `Showing ${start} to ${end} of ${total} payments`;

        let html = '';
        html += `<button onclick="changePage(${page - 1})" class="px-3 py-1.5 rounded-lg text-sm ${page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${page <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left text-xs"></i></button>`;

        for (let i = 1; i <= totalPagesCount; i++) {
            html += `<button onclick="changePage(${i})" class="px-3 py-1.5 rounded-lg text-sm font-medium ${i === page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}">${i}</button>`;
        }

        html += `<button onclick="changePage(${page + 1})" class="px-3 py-1.5 rounded-lg text-sm ${page >= totalPagesCount ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${page >= totalPagesCount ? 'disabled' : ''}><i class="fa-solid fa-chevron-right text-xs"></i></button>`;

        document.getElementById('paginationButtons').innerHTML = html;
    }

    // ============================================================
    // MODAL FUNCTIONS (using ModalSystem with fallback)
    // ============================================================
    function openModal(id) {
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.open(id);
        } else {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeModal(id) {
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.close(id);
        } else {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    }

    async function openProcessPaymentModal() {
        await loadPermitsForDropdown();
        document.getElementById('processPaymentForm').reset();
        // default radio is cash – trigger change to show/hide reference
        document.querySelector('input[name="payment_method_radio"]:checked')
            ?.dispatchEvent(new Event('change'));
        openModal('processPaymentModal');
    }

    async function openFeeStructureModal() {
        openModal('feeStructureModal');
        const content = document.getElementById('feeStructureModalContent');

        if (window.feeStructureData) {
            content.innerHTML = `
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-2 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">Base Fee</th>
                            <th class="px-4 py-2 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">Inspection Fee</th>
                            <th class="px-4 py-2 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${window.feeStructureData.map(fee => `
                            <tr class="border-b border-slate-100">
                                <td class="px-4 py-2 text-slate-700 text-xs">${fee.category}</td>
                                <td class="px-4 py-2 text-right text-xs font-medium text-slate-700">₱${parseFloat(fee.base_fee).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-2 text-right text-xs font-medium text-slate-700">₱${parseFloat(fee.inspection_fee).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                                <td class="px-4 py-2 text-right text-xs font-bold text-brand-dark">₱${parseFloat(fee.total).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <p class="text-[10px] text-slate-400 mt-4"><i class="fa-solid fa-info-circle mr-1"></i>Fees are based on Ordinance No. 0386 (Section 137). Subject to change.</p>
        `;
        }
    }

    // ============================================================
    // PAYMENT METHOD TOGGLE (reference number required for digital)
    // ============================================================
    document.querySelectorAll('input[name="payment_method_radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('payment_method').value = this.value;
            const refSection = document.getElementById('referenceSection');
            const refInput = document.getElementById('payment_reference');
            const isOffline = this.value === 'cash' || this.value === 'over_the_counter';

            if (isOffline) {
                refSection.classList.add('hidden');
                refInput.required = false;
                refInput.value = '';
            } else {
                refSection.classList.remove('hidden');
                refInput.required = true;
            }
        });
    });

    // ============================================================
    // AUTO-FILL AMOUNT
    // ============================================================
    document.getElementById('payment_permit').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        document.getElementById('payment_amount').value = selected.dataset.fee || 0;
    });

    // ============================================================
    // SAVE PAYMENT (via API)
    // ============================================================
    async function savePayment(event) {
        event.preventDefault();

        const data = {
            permit_id: parseInt(document.getElementById('payment_permit').value),
            amount: parseFloat(document.getElementById('payment_amount').value),
            method: document.getElementById('payment_method').value,
            reference_number: document.getElementById('payment_reference').value || null,
            notes: document.getElementById('payment_notes').value || null
        };

        if (!Number.isFinite(data.amount) || data.amount < 0.01 || data.amount > 1000000) {
            showToast('Amount must be between ₱0.01 and ₱1,000,000.00', 'danger');
            return;
        }

        // Validate reference for digital methods
        const method = data.method;
        if (!['cash', 'over_the_counter'].includes(method)) {
            if (!data.reference_number || data.reference_number.trim() === '') {
                showToast('Reference number is required for digital payments.', 'danger');
                return;
            }
        }

        try {
            await paymentApi.createPayment(data);
            closeModal('processPaymentModal');
            showToast('Payment processed successfully!', 'success');
            loadPayments(1);
            loadStats();
        } catch (error) {
            showToast('Payment failed: ' + error.message, 'danger');
        }
    }

    // ============================================================
    // VIEW PAYMENT (via API)
    // ============================================================
    async function viewPayment(id) {
        openModal('viewPaymentModal');

        try {
            const result = await paymentApi.getPayment(id);
            const p = result.data;

            if (!p) {
                document.getElementById('paymentDetailsContent').innerHTML = '<p class="text-center text-slate-500">Payment not found</p>';
                return;
            }

            const permitInfo = p.permits || {};
            const statusColors = {
                completed: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                failed: 'bg-rose-100 text-rose-700',
                refunded: 'bg-purple-100 text-purple-700'
            };

            const modalApplicant = permitInfo.applicant || 'Unknown';
            const maskedModalApplicant = maskName(modalApplicant);
            const modalPermitId = permitInfo.permit_id || '—';
            const maskedModalPermitId = maskId(modalPermitId);
            const modalAmount = '₱' + parseFloat(p.amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
            const maskedModalRef = maskId(p.reference_number || '—');
            const maskedPaidBy = maskName(p.paid_by || '—');

            document.getElementById('paymentDetailsContent').innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0">
                        ${escapeHtml((permitInfo.applicant || 'P').charAt(0))}
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 maskable" data-real="${escapeHtml(modalApplicant)}" data-masked="${escapeHtml(maskedModalApplicant)}">${escapeHtml(modalApplicant)}</h4>
                        <p class="text-sm text-slate-500">${escapeHtml(p.payment_id || '')} • ${escapeHtml(modalPermitId)}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[p.status] || statusColors.pending}">
                            ${escapeHtml((p.status || '').toUpperCase())}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="text-xs text-slate-400 font-semibold">Amount</p><p class="text-sm font-bold text-slate-800 maskable" data-real="${escapeHtml(modalAmount)}" data-masked="₱••••••">${escapeHtml(modalAmount)}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Method</p><p class="text-sm text-slate-800 capitalize">${escapeHtml((p.method || '').replace('_', ' '))}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Reference</p><p class="text-sm text-slate-800 font-mono maskable" data-real="${escapeHtml(p.reference_number || '—')}" data-masked="${escapeHtml(maskedModalRef)}">${escapeHtml(p.reference_number || '—')}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Date</p><p class="text-sm text-slate-800">${escapeHtml(new Date(p.created_at).toLocaleString())}</p></div>
                    ${p.paid_at ? `<div><p class="text-xs text-slate-400 font-semibold">Paid At</p><p class="text-sm text-slate-800">${escapeHtml(new Date(p.paid_at).toLocaleString())}</p></div>` : ''}
                    <div><p class="text-xs text-slate-400 font-semibold">Paid By</p><p class="text-sm text-slate-800 maskable" data-real="${escapeHtml(p.paid_by || '—')}" data-masked="${escapeHtml(maskedPaidBy)}">${escapeHtml(p.paid_by || '—')}</p></div>
                </div>
                ${p.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">📝 Notes</h5><p class="text-sm text-slate-800">${escapeHtml(p.notes)}</p></div>` : ''}
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('viewPaymentModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    ${p.status === 'pending' ? `<button onclick="closeModal('viewPaymentModal'); completePayment(${p.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Complete</button>` : ''}
                </div>
            </div>
        `;
        } catch (error) {
            document.getElementById('paymentDetailsContent').innerHTML = `<p class="text-center text-rose-500">Failed to load: ${error.message}</p>`;
        }
    }

    // ============================================================
    // COMPLETE PAYMENT (via API)
    // ============================================================
    async function completePayment(id) {
        if (!confirm('Mark this payment as completed?')) return;

        try {
            await paymentApi.completePayment(id);
            showToast('Payment completed successfully!', 'success');
            loadPayments(currentPage);
            loadStats();
        } catch (error) {
            showToast('Failed to complete payment: ' + error.message, 'danger');
        }
    }

    // ============================================================
    // DOWNLOAD RECEIPT
    // ============================================================
    function downloadReceipt(receiptPath) {
        showToast('Downloading receipt: ' + receiptPath, 'success');
    }

    // ============================================================
    // TOAST NOTIFICATIONS (using ModalSystem.toast or global toast)
    // ============================================================
    function showToast(message, type = 'success') {
        if (typeof toast !== 'undefined') {
            const typeMap = {
                success: 'success',
                danger: 'error',
                info: 'info',
                warning: 'warning'
            };
            toast[typeMap[type] || 'info'](message, {
                duration: 4000
            });
            return;
        }
        if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
            const typeMap = {
                success: 'success',
                danger: 'error',
                info: 'info',
                warning: 'warning'
            };
            ModalSystem.toast[typeMap[type] || 'info'](message, {
                duration: 4000
            });
            return;
        }
        console.log('[' + type + '] ' + message);
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function maskName(name) {
        if (!name || name === '—') return name || '';
        return name.split(' ').map(function(p) {
            if (!p) return '';
            return p.charAt(0).toUpperCase() + '*'.repeat(Math.max(0, p.length - 1));
        }).join(' ');
    }

    function maskId(id) {
        if (!id || id === '—') return id || '—';
        if (id.length <= 2) return id;
        return id.substring(0, 2) + '*'.repeat(id.length - 2);
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

        if (modalFrom && modalTo && modalFrom > modalTo) {
            showToast('The start date cannot be after the end date', 'danger');
            return;
        }

        document.getElementById('filterDateFrom').value = modalFrom;
        document.getElementById('filterDateTo').value = modalTo;

        updateDateFilterButtonLabel();
        loadPayments(1);
        closeModal('dateFilterModal');
    }

    function clearDateFilterModal() {
        document.getElementById('modalDateFrom').value = '';
        document.getElementById('modalDateTo').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';

        updateDateFilterButtonLabel();
        loadPayments(1);
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
    // EXPORT PAYMENTS TO CSV
    // ============================================================
    function exportPaymentsCSV() {
        const rows = document.querySelectorAll('#paymentTableBody tr.payment-row');
        if (!rows || rows.length === 0) {
            showToast('No payment records available to export.', 'warning');
            return;
        }

        let csv = 'Payment ID,Applicant,Permit ID,Amount,Method,Reference,Status,Date\n';
        rows.forEach(r => {
            const cells = r.querySelectorAll('td');
            if (cells.length < 7) return;
            const pId = cells[0].textContent.trim();
            const applicant = cells[1].querySelector('p:first-child')?.textContent.trim() || '';
            const permitId = cells[1].querySelector('p:last-child')?.textContent.trim() || '';
            const amount = cells[2].textContent.trim().replace(/[₱,]/g, '');
            const method = cells[3].textContent.trim();
            const ref = cells[4].textContent.trim();
            const status = cells[5].textContent.trim();
            const date = cells[6].textContent.trim();

            const clean = str => `"${(str || '').replace(/"/g, '""')}"`;
            csv += [clean(pId), clean(applicant), clean(permitId), clean(amount), clean(method), clean(ref), clean(status), clean(date)].join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `sanitation_payments_${new Date().toISOString().slice(0, 10)}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('Payments exported to CSV successfully!', 'success');
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    let searchTimeout;
    document.getElementById('searchPayment').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadPayments(1), 300);
    });

    document.getElementById('filterStatus').addEventListener('change', () => loadPayments(1));
    document.getElementById('filterMethod').addEventListener('change', () => loadPayments(1));
    document.getElementById('filterDateFrom').addEventListener('change', () => loadPayments(1));
    document.getElementById('filterDateTo').addEventListener('change', () => loadPayments(1));

    function resetFilters() {
        document.getElementById('searchPayment').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterMethod').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        const modalFrom = document.getElementById('modalDateFrom');
        if (modalFrom) modalFrom.value = '';
        const modalTo = document.getElementById('modalDateTo');
        if (modalTo) modalTo.value = '';
        updateDateFilterButtonLabel();
        loadPayments(1);
    }

    function changePage(page) {
        if (page < 1 || page > totalPages) return;
        loadPayments(page);
    }

    // ESC to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => {
                closeModal(modal.id);
            });
        }
    });

    // ============================================================
    // INITIALIZE
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        loadStats();
        loadFeeStructure();
        loadPayments(currentPage);
    });
</script>

<?php include_once '../../includes/footer.php'; ?>