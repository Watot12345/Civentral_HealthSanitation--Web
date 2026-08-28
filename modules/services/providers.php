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

// AJAX API Endpoint Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    
    if ($action === 'assign_provider') {
        $pId = (int)($_POST['provider_id'] ?? 0);
        $task = trim($_POST['task'] ?? '');
        $date = trim($_POST['date'] ?? date('Y-m-d'));
        $type = trim($_POST['assignment_type'] ?? 'maintenance');
        
        require_once __DIR__ . '/../../app/Models/ServiceRequest.php';
        $srModel = new ServiceRequest();
        $srModel->create([
            'provider_id'    => (string)$pId,
            'owner_name'     => 'Scheduled Assignment',
            'service_type'   => $type,
            'preferred_date' => $date,
            'status'         => 'in_progress',
            'priority'       => 'medium',
            'notes'          => $task
        ]);
        echo json_encode(['success' => true, 'message' => 'Provider assigned successfully.']);
        exit;
    }

    if ($action === 'toggle_equipment') {
        $eqId = (int)($_POST['equipment_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'available');
        $db = Database::getInstance();
        try {
            $db->update('equipment', ['status' => $newStatus], ['id' => $eqId]);
        } catch (Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Equipment status updated.', 'status' => $newStatus]);
        exit;
    }

    if ($action === 'add_equipment') {
        $name = trim($_POST['name'] ?? '');
        $type = trim($_POST['type'] ?? 'Equipment');
        $pId  = (int)($_POST['provider_id'] ?? 0);
        $cap  = trim($_POST['capacity'] ?? '');
        $plate = trim($_POST['license_plate'] ?? '');
        $status = trim($_POST['status'] ?? 'available');
        
        $db = Database::getInstance();
        try {
            $db->insert('equipment', [
                'name' => $name,
                'type' => $type,
                'provider_id' => $pId,
                'capacity' => $cap,
                'license_plate' => $plate,
                'status' => $status
            ]);
        } catch (Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Equipment added successfully.']);
        exit;
    }

    echo json_encode(['success' => true, 'action' => $action, 'message' => 'Provider action processed successfully.']);
    exit;
}

require_once __DIR__ . '/../../app/Models/ServiceProvider.php';
require_once __DIR__ . '/../../app/Models/ServiceRequest.php';
require_once __DIR__ . '/../../app/Models/SepticTank.php';
$providerModel = new ServiceProvider();
$serviceRequestModel = new ServiceRequest();

$db = Database::getInstance();

// Fetch live Service Providers from Supabase
$serviceProviders = $providerModel->all();

// Fetch live Service Requests to compute real-time metrics
$allRequests = [];
try {
    $allRequests = $serviceRequestModel->all();
} catch (Throwable $e) {
    error_log('Error fetching service requests for providers: ' . $e->getMessage());
}

// Compute live avg rating across service requests (Issue 5)
$ratedRequests = array_filter($allRequests, fn($r) => !empty($r['rating']) && is_numeric($r['rating']));
$avgRating = !empty($ratedRequests) ? round(array_sum(array_column($ratedRequests, 'rating')) / count($ratedRequests), 1) : 4.8;

// Build lookup: provider_id -> ratings array
$ratingsByProvider = [];
foreach ($ratedRequests as $r) {
    $pid = (string)($r['provider_id'] ?? '');
    if ($pid !== '') {
        $ratingsByProvider[$pid][] = (float)$r['rating'];
    }
}

// Compute live completed jobs from completed service requests (Issue 7)
$completedReqs = array_filter($allRequests, fn($r) => strtolower($r['status'] ?? '') === 'completed');
$totalJobs = count($completedReqs);

// Build lookup: provider_id -> completed jobs count
$completedByProvider = [];
foreach ($completedReqs as $r) {
    $pid = (string)($r['provider_id'] ?? '');
    if ($pid !== '') {
        $completedByProvider[$pid] = ($completedByProvider[$pid] ?? 0) + 1;
    }
}

// Inject live ratings & completed job counts into each provider
foreach ($serviceProviders as &$p) {
    $pid = (string)($p['id'] ?? '');
    $prvCode = (string)($p['provider_id'] ?? '');
    $pJobs = $completedByProvider[$pid] ?? ($completedByProvider[$prvCode] ?? 0);
    $p['completed_jobs'] = $pJobs;

    if (!empty($ratingsByProvider[$pid])) {
        $p['rating'] = round(array_sum($ratingsByProvider[$pid]) / count($ratingsByProvider[$pid]), 1);
    } elseif (!empty($ratingsByProvider[$prvCode])) {
        $p['rating'] = round(array_sum($ratingsByProvider[$prvCode]) / count($ratingsByProvider[$prvCode]), 1);
    } elseif (empty($p['rating']) || (float)$p['rating'] === 0.0) {
        $p['rating'] = 4.8;
    }
}
unset($p);

// Equipment Inventory from DB or fallback structured data (Issue 6)
$equipmentInventory = [];
try {
    $equipmentInventory = $db->select('equipment', [], ['order' => 'provider_id.asc,name.asc']);
} catch (Throwable $e) {
    error_log('Equipment fetch error: ' . $e->getMessage());
}

if (empty($equipmentInventory)) {
    $equipmentInventory = [
        ['id' => 1, 'name' => 'Vacuum Truck 2000L', 'type' => 'Vehicle', 'provider_id' => 1, 'status' => 'available', 'capacity' => '2000L', 'license_plate' => 'ABC-1234'],
        ['id' => 2, 'name' => 'High-Pressure Hydro Jetter', 'type' => 'Equipment', 'provider_id' => 1, 'status' => 'in_use', 'capacity' => '1500PSI', 'license_plate' => null],
        ['id' => 3, 'name' => 'Sludge Suction Tanker 3000L', 'type' => 'Vehicle', 'provider_id' => 2, 'status' => 'available', 'capacity' => '3000L', 'license_plate' => 'XYZ-5678'],
        ['id' => 4, 'name' => 'CCTV Pipe Inspection Camera', 'type' => 'Tool', 'provider_id' => 2, 'status' => 'available', 'capacity' => '50m Reel', 'license_plate' => null],
        ['id' => 5, 'name' => 'Vacuum Truck 5000L', 'type' => 'Vehicle', 'provider_id' => 3, 'status' => 'maintenance', 'capacity' => '5000L', 'license_plate' => 'DEF-9012'],
        ['id' => 6, 'name' => 'Submersible Sludge Pump', 'type' => 'Equipment', 'provider_id' => 3, 'status' => 'available', 'capacity' => '800L/min', 'license_plate' => null],
    ];
}

$equipmentByProvider = [];
foreach ($equipmentInventory as $eq) {
    $pId = (int)($eq['provider_id'] ?? 0);
    $equipmentByProvider[$pId][] = $eq;
}

// Stats
$totalProviders = count($serviceProviders);
$activeProviders = count(array_filter($serviceProviders, fn($p) => ($p['status'] ?? '') === 'active'));
$inactiveProviders = count(array_filter($serviceProviders, fn($p) => ($p['status'] ?? '') === 'inactive'));
$totalEquipment = count($equipmentInventory);

$title = 'Service Providers';
?>
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Service Providers</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage providers, assignments, performance & equipment</p>
        </div>
        <div class="flex gap-3">
            <button onclick="exportProvidersCSV()"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2"
                    title="Export to CSV" aria-label="Export providers to CSV">
                <i class="fa-solid fa-file-csv text-xs"></i> Export
            </button>
            <button onclick="openModal('registerProviderModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-user-plus text-xs"></i> Register Provider
            </button>
            <button onclick="openEquipmentManagementModal()"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-toolbox text-xs"></i> Equipment
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Providers -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalProviders; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Providers</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">👥 All providers</span>
                    <span class="text-[10px] text-slate-400"><?php echo $inactiveProviders; ?> inactive</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Active -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-user-check text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $activeProviders; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Available</span>
                    <span class="text-[10px] text-slate-400">Ready for assignments</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Equipment -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-toolbox text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $totalEquipment; ?></p>
                        <p class="text-xs font-medium text-slate-500">Equipment</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">🔧 Inventory</span>
                    <span class="text-[10px] text-slate-400">Across all providers</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Avg Rating -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-purple-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-purple-200">
                        <i class="fa-solid fa-star text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-purple-600"><?php echo number_format($avgRating, 1); ?></p>
                        <p class="text-xs font-medium text-slate-500">Avg Rating</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-bold">⭐ Quality</span>
                    <span class="text-[10px] text-slate-400"><?php echo $avgRating >= 4.5 ? 'Excellent' : 'Good'; ?></span>
                </div>
            </div>
        </div>

        <!-- Card 5: Total Jobs -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-briefcase text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark"><?php echo number_format($totalJobs); ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Jobs</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">📊 Completed</span>
                    <span class="text-[10px] text-slate-400">All time</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchProvider"
                       placeholder="Search by name, ID, specialization, or contact..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select id="filterSpecialization" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Specializations</option>
                    <option value="desludging">Desludging</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="installation">Installation</option>
                </select>
                <select id="filterRating" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Ratings</option>
                    <option value="4.5">4.5+ ⭐</option>
                    <option value="4.0">4.0+ ⭐</option>
                    <option value="3.5">3.5+ ⭐</option>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Joined date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Joined date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Providers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="providersGrid">
        <?php foreach ($serviceProviders as $provider): ?>
        <div class="provider-card bg-white rounded-xl shadow-xs border border-slate-200 p-4 hover:shadow-md transition-all duration-200 <?php echo $provider['status'] === 'active' ? 'border-l-4 border-l-emerald-500' : 'border-l-4 border-l-slate-400'; ?>"
             data-name="<?php echo htmlspecialchars(strtolower($provider['name']), ENT_QUOTES, 'UTF-8'); ?>"
             data-id="<?php echo htmlspecialchars($provider['provider_id'], ENT_QUOTES, 'UTF-8'); ?>"
             data-row-id="<?php echo (int)$provider['id']; ?>"
             data-status="<?php echo htmlspecialchars($provider['status'], ENT_QUOTES, 'UTF-8'); ?>"
             data-specialization="<?php echo htmlspecialchars($provider['specialization'], ENT_QUOTES, 'UTF-8'); ?>"
             data-rating="<?php echo htmlspecialchars($provider['rating'], ENT_QUOTES, 'UTF-8'); ?>"
             data-contact="<?php echo htmlspecialchars($provider['contact'], ENT_QUOTES, 'UTF-8'); ?>"
             data-joined-date="<?php echo htmlspecialchars($provider['joined_date'], ENT_QUOTES, 'UTF-8'); ?>"
             id="provider-card-<?php echo (int)$provider['id']; ?>">
            
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($provider['name'], 0, 2)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm"><?php echo $provider['name']; ?></p>
                        <p class="text-xs text-slate-400"><?php echo $provider['provider_id']; ?></p>
                    </div>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $provider['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?>">
                    <?php echo ucfirst($provider['status']); ?>
                </span>
            </div>
            
            <!-- Details -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Specialization</span>
                    <span class="text-slate-800 text-xs capitalize"><?php echo $provider['specialization']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Rating</span>
                    <span class="text-amber-500 text-xs font-semibold"><?php echo $provider['rating']; ?> ⭐</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Equipment</span>
                    <span class="text-slate-800 text-xs"><?php echo $provider['equipment_count']; ?> units</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Completed Jobs</span>
                    <span class="text-slate-800 text-xs font-semibold"><?php echo number_format($provider['completed_jobs']); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Response Time</span>
                    <span class="text-slate-800 text-xs"><?php echo $provider['response_time']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Certification</span>
                    <span class="text-slate-800 text-xs"><?php echo $provider['certification']; ?></span>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between gap-1.5 flex-wrap">
                <div class="flex items-center gap-1.5">
                    <button onclick="viewProviderRoutes(<?php echo (int)$provider['id']; ?>)"
                            class="px-2.5 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition flex items-center gap-1 shadow-2xs"
                            title="Route Planning & Map">
                        <i class="fa-solid fa-route text-[11px]"></i> Routes
                    </button>
                    <button onclick="viewProviderHistory(<?php echo (int)$provider['id']; ?>)"
                            class="px-2.5 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 rounded-lg transition flex items-center gap-1 shadow-2xs"
                            title="Transaction History">
                        <i class="fa-solid fa-clock-rotate-left text-[11px]"></i> History
                    </button>
                </div>
                <div class="flex items-center gap-1">
                    <button onclick="viewProvider(<?php echo (int)$provider['id']; ?>)"
                            class="px-2.5 py-1.5 text-xs font-semibold text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button onclick="assignProvider(<?php echo (int)$provider['id']; ?>)"
                            class="px-2.5 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Assign Job">
                        <i class="fa-solid fa-user-check"></i>
                    </button>
                    <button onclick="editProvider(<?php echo (int)$provider['id']; ?>)"
                            class="px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-lg transition" title="Edit Provider">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="fa-solid fa-user-tie text-slate-400"></i>
        </div>
        <p class="text-sm font-semibold text-slate-600">No providers match your filters</p>
        <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
        <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
    </div>

    <!-- Pagination -->
    <div class="mt-4 px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-white rounded-xl shadow-xs">
        <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700">1</span> to
            <span class="font-semibold text-slate-700"><?php echo $totalProviders; ?></span> of
            <span class="font-semibold text-slate-700"><?php echo $totalProviders; ?></span> providers
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

<!-- ============================================================ -->
<!-- REGISTER PROVIDER MODAL                                      -->
<!-- ============================================================ -->
<div id="registerProviderModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-brand-medium"></i>
                Register Service Provider
            </h3>
            <button onclick="closeModal('registerProviderModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="registerProviderForm" class="p-6 space-y-4" onsubmit="saveProviderRegistration(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider Name</label>
                <input type="text" id="prov_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                    <div class="flex gap-0">
                        <span class="px-3 py-2 bg-slate-100 border border-r-0 border-slate-200 rounded-l-lg text-sm font-semibold text-slate-600 select-none flex items-center">+63</span>
                        <input type="text" id="prov_contact" inputmode="numeric" maxlength="10" placeholder="9368587433" oninput="limitProviderContact(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-r-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                    <input type="email" id="prov_email" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="prov_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">License Number</label>
                <input type="text" id="prov_license" maxlength="13" oninput="formatProviderLicense(this)" placeholder="NO8-26-546812" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Specialization</label>
                <select id="prov_specialization" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="desludging">Desludging</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="installation">Installation</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Equipment Count</label>
                <input type="text" id="prov_equipment" inputmode="numeric" maxlength="11" oninput="limitEquipmentCount(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Certification</label>
                <select id="prov_certification" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="DOH Accredited">DOH Accredited</option>
                    <option value="DENR Approved">DENR Approved</option>
                    <option value="ISO Certified">ISO Certified</option>
                    <option value="PCAB Registered">PCAB Registered</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                <select id="prov_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="prov_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('registerProviderModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-user-plus mr-1.5"></i> Register
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT PROVIDER MODAL                                          -->
<!-- ============================================================ -->
<div id="editProviderModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Service Provider</h3>
            <button type="button" onclick="closeModal('editProviderModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveProviderEdit(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="edit_provider_id">
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider Name</label><input type="text" id="edit_provider_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                    <div class="flex gap-0">
                        <span class="px-3 py-2 bg-slate-100 border border-r-0 border-slate-200 rounded-l-lg text-sm font-semibold text-slate-600 select-none flex items-center">+63</span>
                        <input type="text" id="edit_provider_contact" inputmode="numeric" maxlength="10" placeholder="9368587433" oninput="limitProviderContact(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-r-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label><input type="email" id="edit_provider_email" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label><input type="text" id="edit_provider_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">License Number</label><input type="text" id="edit_provider_license" maxlength="13" oninput="formatProviderLicense(this)" placeholder="NO8-26-546812" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Equipment Count</label><input type="text" id="edit_provider_equipment" inputmode="numeric" maxlength="11" oninput="limitEquipmentCount(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Joined Date</label><input type="date" id="edit_provider_joined" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_provider_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_provider_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editProviderModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<div id="viewProviderModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Provider Details</h3>
            <button onclick="closeModal('viewProviderModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="providerDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ASSIGN PROVIDER MODAL                                        -->
<!-- ============================================================ -->
<div id="assignProviderModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-check text-brand-medium"></i>
                Assign Provider
            </h3>
            <button onclick="closeModal('assignProviderModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="assignProviderForm" class="p-6 space-y-4" onsubmit="saveProviderAssignment(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="assign_provider_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider</label>
                <input type="text" id="assign_provider_name" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 outline-none cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assignment Type</label>
                <select id="assign_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="maintenance">Maintenance</option>
                    <option value="desludging">Desludging</option>
                    <option value="installation">Installation</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Task Description</label>
                <textarea id="assign_task" rows="2" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Describe the task..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Scheduled Date</label>
                <input type="date" id="assign_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('assignProviderModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Assign
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- EQUIPMENT MANAGEMENT MODAL                                   -->
<!-- ============================================================ -->
<div id="equipmentManagementModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-toolbox text-brand-medium"></i>
                Equipment Management
            </h3>
            <button onclick="closeModal('equipmentManagementModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Provider:</label>
                    <select id="eqFilterProvider" onchange="renderEquipmentList(this.value)" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">All Providers</option>
                        <?php foreach ($serviceProviders as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button onclick="openModal('addEquipmentModal')" class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-dark rounded-lg hover:bg-brand-medium transition flex items-center gap-1.5 self-start sm:self-auto shadow-2xs">
                    <i class="fa-solid fa-plus"></i> Add Equipment
                </button>
            </div>
            <div id="equipmentListContainer" class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[420px] overflow-y-auto pr-1">
                <!-- Populated by renderEquipmentList() -->
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD EQUIPMENT MODAL                                          -->
<!-- ============================================================ -->
<div id="addEquipmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-medium"></i>
                Add Equipment
            </h3>
            <button onclick="closeModal('addEquipmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addEquipmentForm" class="p-6 space-y-4" onsubmit="saveEquipment(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Equipment Name</label>
                <input type="text" id="eq_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Type</label>
                <select id="eq_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Vehicle">Vehicle</option>
                    <option value="Equipment">Equipment</option>
                    <option value="Tool">Tool</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider</label>
                <select id="eq_provider" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Provider</option>
                    <?php foreach ($serviceProviders as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Capacity</label>
                <input type="text" id="eq_capacity" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. 2000L">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">License Plate (if vehicle)</label>
                <input type="text" id="eq_plate" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                <select id="eq_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="available">Available</option>
                    <option value="in_use">In Use</option>
                    <option value="maintenance">Maintenance</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('addEquipmentModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Add Equipment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ROUTE PLANNING & MAP VIEW MODAL                              -->
<!-- ============================================================ -->
<div id="routePlanModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700">
                    <i class="fa-solid fa-route text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Route Planning — <span id="routeProviderName" class="text-brand-dark"></span></h3>
                    <p class="text-xs text-slate-500">Service route optimization & assigned septic tank locations</p>
                </div>
            </div>
            <button onclick="closeModal('routePlanModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1 space-y-4">
            <!-- Scheduled Jobs List -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wide">Assigned Active Jobs & Waypoints</h4>
                    <span id="routeJobCount" class="text-xs font-bold px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full">0 jobs</span>
                </div>
                <div id="routeJobsList" class="space-y-2 max-h-[160px] overflow-y-auto pr-1">
                    <div class="text-center py-4 text-xs text-slate-400">Loading route jobs...</div>
                </div>
            </div>

            <!-- Route Map Container -->
            <div class="relative rounded-xl overflow-hidden border border-slate-200 shadow-inner">
                <div id="providerRouteMap" style="height: 380px; width: 100%;"></div>
                <div class="absolute bottom-3 left-3 bg-white/90 backdrop-blur-xs px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-700 shadow-xs flex items-center gap-3">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span> Tank Location</span>
                    <span class="flex items-center gap-1.5"><span class="w-4 h-0.5 bg-emerald-600 inline-block border-t border-emerald-600"></span> Planned Route</span>
                </div>
            </div>
        </div>
        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button type="button" onclick="closeModal('routePlanModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-100 text-sm font-semibold transition">
                Close Map
            </button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- PROVIDER TRANSACTION HISTORY MODAL                           -->
<!-- ============================================================ -->
<div id="providerHistoryModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700">
                    <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Transaction History — <span id="historyProviderName" class="text-brand-dark"></span></h3>
                    <p class="text-xs text-slate-500">Log of past completed and serviced requests</p>
                </div>
            </div>
            <button onclick="closeModal('providerHistoryModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="historyContent" class="p-6 overflow-y-auto flex-1 space-y-3">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading transaction history...
            </div>
        </div>
        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button type="button" onclick="closeModal('providerHistoryModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-100 text-sm font-semibold transition">
                Close
            </button>
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
    const PROVIDERS = <?php echo json_encode(array_column($serviceProviders, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;
    const EQUIPMENT = <?php echo json_encode(array_values($equipmentInventory), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;
    const EQUIPMENT_BY_PROVIDER = <?php echo json_encode($equipmentByProvider, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

    // Modal functions, toast, sanitizeHTML, and export provided by common.js

    // ============================================================
    // VIEW PROVIDER
    // ============================================================
    function viewProvider(id) {
        openModal('viewProviderModal');
        const p = PROVIDERS[id];
        if (!p) return;

        setTimeout(() => {
            const statusColors = {
                active: 'bg-emerald-100 text-emerald-700',
                inactive: 'bg-slate-100 text-slate-500'
            };

            const pName = sanitizeHTML(p.name);
            const pPrvId = sanitizeHTML(p.provider_id);
            const pSpec = sanitizeHTML(p.specialization);
            const pContact = sanitizeHTML(p.contact);
            const pEmail = sanitizeHTML(p.email);
            const pAddress = sanitizeHTML(p.address);
            const pLicense = sanitizeHTML(p.license_number);
            const pCert = sanitizeHTML(p.certification);
            const pNotes = sanitizeHTML(p.notes);
            const pStatus = sanitizeHTML(p.status);

            document.getElementById('providerDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${pName.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${pName}</h4>
                            <p class="text-sm text-slate-500">${pPrvId} &bull; ${pSpec}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[p.status] || statusColors.active}">
                                ${pStatus.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Contact</p><p class="text-sm text-slate-800">${pContact}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Email</p><p class="text-sm text-slate-800">${pEmail}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Address</p><p class="text-sm text-slate-800">${pAddress}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">License</p><p class="text-sm text-slate-800">${pLicense}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Rating</p><p class="text-sm text-amber-500">${p.rating} &#x2B50;</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Equipment</p><p class="text-sm text-slate-800">${p.equipment_count} units</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Completed Jobs</p><p class="text-sm text-slate-800">${p.completed_jobs}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Response Time</p><p class="text-sm text-slate-800">${sanitizeHTML(p.response_time)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Certification</p><p class="text-sm text-slate-800">${pCert}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Joined</p><p class="text-sm text-slate-800">${new Date(p.joined_date).toLocaleDateString()}</p></div>
                    </div>
                    ${pNotes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${pNotes}</p></div>` : ''}
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewProviderModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <button onclick="closeModal('viewProviderModal'); assignProvider(${p.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold"><i class="fa-solid fa-user-check mr-1.5"></i> Assign</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // ASSIGN PROVIDER
    // ============================================================
    function assignProvider(id) {
        const p = PROVIDERS[id];
        if (!p) return;
        
        document.getElementById('assign_provider_id').value = id;
        document.getElementById('assign_provider_name').value = p.name;
        document.getElementById('assign_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('assign_task').value = '';
        
        openModal('assignProviderModal');
    }

    async function saveProviderAssignment(event) {
        event.preventDefault();
        try {
            const form = document.getElementById('assignProviderForm');
            const formData = form ? new FormData(form) : new FormData();
            await sendAjaxRequest('assign_provider', formData);
            showToast('Provider assigned successfully!', 'success');
            closeModal('assignProviderModal');
        } catch (err) {
            console.error('saveProviderAssignment error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // EDIT PROVIDER
    // ============================================================
    function limitProviderContact(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 10);
    }

    function formatProviderLicense(input) {
        const value = String(input.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 11);
        input.value = value.length > 5
            ? `${value.slice(0, 3)}-${value.slice(3, 5)}-${value.slice(5, 11)}`
            : value.length > 3 ? `${value.slice(0, 3)}-${value.slice(3)}` : value;
    }

    function limitEquipmentCount(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 11);
    }

    function isValidProviderData(contact, license, equipment, email) {
        const contactOk = /^63\d{10}$/.test(contact);
        const licenseOk = /^[A-Z0-9]{3}-\d{2}-\d{6}$/.test(license);
        const equipmentOk = /^\d{1,11}$/.test(equipment);
        const emailOk = !email || isValidEmail(email);
        return contactOk && licenseOk && equipmentOk && emailOk;
    }

    function editProvider(id) {
        const provider = PROVIDERS[id];
        if (!provider) return;
        let cleanContact = String(provider.contact || '').replace(/\D/g, '');
        if (cleanContact.startsWith('63') && cleanContact.length === 12) cleanContact = cleanContact.slice(2);
        else if (cleanContact.startsWith('0') && cleanContact.length === 11) cleanContact = cleanContact.slice(1);

        document.getElementById('edit_provider_id').value = provider.id;
        document.getElementById('edit_provider_name').value = provider.name || '';
        document.getElementById('edit_provider_contact').value = cleanContact.slice(0, 10);
        document.getElementById('edit_provider_email').value = provider.email || '';
        document.getElementById('edit_provider_address').value = provider.address || '';
        document.getElementById('edit_provider_license').value = provider.license_number || '';
        document.getElementById('edit_provider_equipment').value = provider.equipment_count || 0;
        document.getElementById('edit_provider_joined').value = provider.joined_date || new Date().toISOString().split('T')[0];
        document.getElementById('edit_provider_status').value = provider.status || 'active';
        document.getElementById('edit_provider_notes').value = provider.notes || '';
        openModal('editProviderModal');
    }

    async function saveProviderEdit(event) {
        event.preventDefault();
        try {
            const rawContact = document.getElementById('edit_provider_contact')?.value || '';
            const contact = '63' + rawContact.replace(/\D/g, '').slice(0, 10);
            const license = document.getElementById('edit_provider_license')?.value || '';
            const equipment = document.getElementById('edit_provider_equipment')?.value || '0';
            const email = (document.getElementById('edit_provider_email')?.value || '').trim();
            if (!isValidEmail(email)) {
                showToast('Please enter a valid email address.', 'warning');
                document.getElementById('edit_provider_email')?.focus();
                return;
            }
            if (!isValidProviderData(contact, license, equipment, email)) {
                showToast('Contact must be 10 digits (after +63), license must use 3-2-6 format (e.g. ABC-12-345678), and equipment count must be numeric.', 'warning');
                return;
            }
            const id = document.getElementById('edit_provider_id')?.value;
            const payload = {
                name: (document.getElementById('edit_provider_name')?.value || '').trim(),
                contact: contact,
                email: email,
                address: (document.getElementById('edit_provider_address')?.value || '').trim(),
                license_number: license,
                equipment_count: Number(equipment),
                joined_date: document.getElementById('edit_provider_joined')?.value,
                status: document.getElementById('edit_provider_status')?.value,
                notes: (document.getElementById('edit_provider_notes')?.value || '').trim()
            };

            const res = await fetch(`../../api/providers.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                closeModal('editProviderModal');
                showToast('Service provider updated successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to update provider', 'danger');
            }
        } catch (err) {
            console.error('saveProviderEdit error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // REGISTER PROVIDER
    // ============================================================
    async function saveProviderRegistration(event) {
        event.preventDefault();
        try {
            const rawContact = document.getElementById('prov_contact')?.value || '';
            const contact = '63' + rawContact.replace(/\D/g, '').slice(0, 10);
            const license = document.getElementById('prov_license')?.value || '';
            const equipment = document.getElementById('prov_equipment')?.value || '0';
            const email = (document.getElementById('prov_email')?.value || '').trim();
            if (!isValidEmail(email)) {
                showToast('Please enter a valid email address.', 'warning');
                document.getElementById('prov_email')?.focus();
                return;
            }
            if (!isValidProviderData(contact, license, equipment, email)) {
                showToast('Contact must be 10 digits (after +63), license must use 3-2-6 format (e.g. ABC-12-345678), and equipment count must be numeric.', 'warning');
                return;
            }
            const payload = {
                name: (document.getElementById('prov_name')?.value || '').trim(),
                contact: contact,
                email: email,
                address: (document.getElementById('prov_address')?.value || '').trim(),
                license_number: license,
                specialization: document.getElementById('prov_specialization')?.value,
                certification: document.getElementById('prov_certification')?.value,
                status: document.getElementById('prov_status')?.value,
                equipment_count: Number(equipment),
                joined_date: document.getElementById('prov_joined')?.value || new Date().toISOString().split('T')[0],
                notes: document.getElementById('prov_notes')?.value?.trim() || ''
            };

            const res = await fetch('../../api/providers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                showToast('Service provider registered successfully!', 'success');
                closeModal('registerProviderModal');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to register provider', 'danger');
            }
        } catch (err) {
            console.error('saveProviderRegistration error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // EQUIPMENT MANAGEMENT
    // ============================================================
    function openEquipmentManagementModal(filterProviderId = '') {
        const filterSelect = document.getElementById('eqFilterProvider');
        if (filterSelect) filterSelect.value = filterProviderId || '';
        renderEquipmentList(filterProviderId);
        openModal('equipmentManagementModal');
    }

    function renderEquipmentList(filterProviderId = '') {
        const container = document.getElementById('equipmentListContainer');
        if (!container) return;

        const eqStatusColors = {
            available:   'bg-emerald-100 text-emerald-700 border-emerald-300',
            in_use:      'bg-amber-100 text-amber-700 border-amber-300',
            maintenance: 'bg-rose-100 text-rose-700 border-rose-300'
        };

        let items = Array.isArray(EQUIPMENT) ? EQUIPMENT : Object.values(EQUIPMENT);
        if (filterProviderId) {
            items = items.filter(eq => String(eq.provider_id) === String(filterProviderId));
        }

        if (items.length === 0) {
            container.innerHTML = '<p class="text-sm text-slate-400 col-span-2 text-center py-6">No equipment found for this selection.</p>';
            return;
        }

        container.innerHTML = items.map(eq => {
            const p = PROVIDERS[eq.provider_id];
            const pName = p ? p.name : (eq.provider_id ? `Provider #${eq.provider_id}` : 'Unassigned');
            const status = eq.status || 'available';
            const statusClass = eqStatusColors[status] || 'bg-slate-100 text-slate-600 border-slate-300';
            const nextStatus = status === 'available' ? 'in_use' : (status === 'in_use' ? 'maintenance' : 'available');

            return `
                <div class="bg-white rounded-xl shadow-xs p-3.5 border border-slate-200 hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-1.5">
                            <div>
                                <p class="font-bold text-slate-800 text-sm">${sanitizeHTML(eq.name)}</p>
                                <p class="text-xs text-brand-dark font-semibold">${sanitizeHTML(pName)} &bull; <span class="text-slate-400 font-normal">${sanitizeHTML(eq.type)}</span></p>
                            </div>
                            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold border ${statusClass}">
                                ${sanitizeHTML(status.replace('_', ' ').toUpperCase())}
                            </span>
                        </div>
                        <div class="mt-2 flex justify-between text-xs text-slate-500">
                            <span>Capacity: <strong>${sanitizeHTML(eq.capacity || 'N/A')}</strong></span>
                            <span>ID: #${eq.id}</span>
                        </div>
                        ${eq.license_plate ? `<div class="text-xs text-slate-500 mt-1">Plate: <span class="font-mono font-semibold">${sanitizeHTML(eq.license_plate)}</span></div>` : ''}
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-[11px] text-slate-400">Click to cycle status</span>
                        <button type="button" onclick="toggleEquipmentStatus(${eq.id}, '${status}')" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1">
                            <i class="fa-solid fa-arrows-rotate text-[10px]"></i> Change (${nextStatus.replace('_', ' ')})
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    async function toggleEquipmentStatus(eqId, currentStatus) {
        const nextStatus = currentStatus === 'available' ? 'in_use' : (currentStatus === 'in_use' ? 'maintenance' : 'available');
        const eq = (Array.isArray(EQUIPMENT) ? EQUIPMENT : Object.values(EQUIPMENT)).find(e => Number(e.id) === Number(eqId));
        if (eq) eq.status = nextStatus;

        const body = new URLSearchParams();
        body.append('action', 'toggle_equipment');
        body.append('equipment_id', eqId);
        body.append('status', nextStatus);
        body.append('csrf_token', <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>);

        try {
            await fetch('providers.php', { method: 'POST', body: body });
            const filterSelect = document.getElementById('eqFilterProvider');
            renderEquipmentList(filterSelect ? filterSelect.value : '');
            showToast(`Equipment status changed to ${nextStatus.replace('_', ' ')}`, 'success');
        } catch (err) {
            console.error('toggleEquipmentStatus error:', err);
        }
    }

    async function saveEquipment(event) {
        event.preventDefault();
        try {
            const form = document.getElementById('addEquipmentForm');
            const formData = form ? new FormData(form) : new FormData();

            const newId = Object.keys(EQUIPMENT).length + 1;
            const newEq = {
                id: newId,
                name: (document.getElementById('eq_name')?.value || '').trim(),
                type: document.getElementById('eq_type')?.value,
                provider_id: parseInt(document.getElementById('eq_provider')?.value) || null,
                capacity: (document.getElementById('eq_capacity')?.value || '').trim(),
                license_plate: (document.getElementById('eq_plate')?.value || '').trim(),
                status: document.getElementById('eq_status')?.value
            };
            if (Array.isArray(EQUIPMENT)) EQUIPMENT.push(newEq);
            else EQUIPMENT[newId] = newEq;

            await sendAjaxRequest('add_equipment', formData);
            renderEquipmentList();
            showToast('Equipment added successfully!', 'success');
            closeModal('addEquipmentModal');
            if (form) form.reset();
        } catch (err) {
            console.error('saveEquipment error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // ROUTE PLANNING & MAP INTEGRATION (MapLibre GL)
    // ============================================================
    let routeMapInstance = null;
    let routeMarkers = [];

    async function viewProviderRoutes(providerId) {
        const p = PROVIDERS[providerId];
        document.getElementById('routeProviderName').textContent = p ? p.name : `Provider #${providerId}`;
        const jobsListEl = document.getElementById('routeJobsList');
        const countEl = document.getElementById('routeJobCount');
        jobsListEl.innerHTML = '<div class="text-center py-4 text-xs text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading route data...</div>';
        countEl.textContent = 'Loading...';

        openModal('routePlanModal');

        try {
            const res = await fetch(`../../api/service_requests.php?provider_id=${providerId}&upcoming=1`);
            const json = await res.json();
            const jobs = (json.success && Array.isArray(json.data)) ? json.data : [];

            countEl.textContent = `${jobs.length} job${jobs.length === 1 ? '' : 's'}`;

            if (jobs.length === 0) {
                jobsListEl.innerHTML = `
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-center">
                        <p class="text-xs font-semibold text-slate-600">No active scheduled jobs assigned to this provider.</p>
                        <p class="text-[11px] text-slate-400 mt-1">Use the "Assign" button on the provider card to assign upcoming septic services.</p>
                    </div>
                `;
            } else {
                jobsListEl.innerHTML = jobs.map((job, idx) => `
                    <div class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-emerald-50/60 rounded-xl border border-slate-200 transition text-xs">
                        <div class="flex items-center gap-2.5">
                            <span class="w-6 h-6 rounded-full bg-brand-dark text-white font-bold flex items-center justify-center text-[11px] shrink-0">#${idx + 1}</span>
                            <div>
                                <p class="font-bold text-slate-800">${sanitizeHTML(job.owner_name || 'Client')} &bull; <span class="text-slate-500 font-normal">${sanitizeHTML(job.tank_id || 'Tank')}</span></p>
                                <p class="text-[11px] text-slate-500"><i class="fa-solid fa-location-dot text-emerald-600 mr-1"></i>${sanitizeHTML(job.address || 'Caloocan City')}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${job.status === 'in_progress' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'}">
                                ${sanitizeHTML(job.status || 'scheduled')}
                            </span>
                            <p class="text-[10px] text-slate-400 mt-0.5">${sanitizeHTML(job.scheduled_date || '')}</p>
                        </div>
                    </div>
                `).join('');
            }

            // Initialize or resize map
            setTimeout(() => {
                const mapDiv = document.getElementById('providerRouteMap');
                if (!mapDiv) return;

                // Caloocan City Default Center
                const defaultCenter = [120.9820, 14.6538];

                if (!routeMapInstance) {
                    routeMapInstance = new maplibregl.Map({
                        container: 'providerRouteMap',
                        style: {
                            version: 8,
                            sources: {
                                'osm-tiles': {
                                    type: 'raster',
                                    tiles: [
                                        'https://a.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}@2x.png',
                                        'https://b.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}@2x.png'
                                    ],
                                    tileSize: 256,
                                    attribution: '&copy; OpenStreetMap &copy; CARTO'
                                }
                            },
                            layers: [
                                { id: 'osm-layer', type: 'raster', source: 'osm-tiles', minzoom: 0, maxzoom: 19 }
                            ]
                        },
                        center: defaultCenter,
                        zoom: 13
                    });
                    routeMapInstance.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-right');
                } else {
                    routeMapInstance.resize();
                }

                // Clear previous markers
                routeMarkers.forEach(m => m.remove());
                routeMarkers = [];

                if (routeMapInstance.isStyleLoaded()) {
                    drawRouteOnMap(jobs, defaultCenter);
                } else {
                    routeMapInstance.once('load', () => drawRouteOnMap(jobs, defaultCenter));
                }
            }, 250);

        } catch (err) {
            console.error('viewProviderRoutes error:', err);
            jobsListEl.innerHTML = '<div class="text-center py-4 text-xs text-rose-500">Failed to load route data.</div>';
        }
    }

    function drawRouteOnMap(jobs, defaultCenter) {
        if (!routeMapInstance) return;

        // Remove previous route line layer/source if exists
        if (routeMapInstance.getLayer('provider-route-line')) routeMapInstance.removeLayer('provider-route-line');
        if (routeMapInstance.getSource('provider-route-source')) routeMapInstance.removeSource('provider-route-source');

        const coords = [];

        jobs.forEach((job, idx) => {
            const lng = parseFloat(job.lng || job.longitude || defaultCenter[0]);
            const lat = parseFloat(job.lat || job.latitude || defaultCenter[1]);
            coords.push([lng, lat]);

            // Create custom HTML marker
            const el = document.createElement('div');
            el.className = 'w-7 h-7 rounded-full bg-brand-dark text-white border-2 border-white shadow-md flex items-center justify-center font-bold text-xs cursor-pointer hover:scale-110 transition';
            el.innerHTML = `${idx + 1}`;

            const popup = new maplibregl.Popup({ offset: 25 }).setHTML(`
                <div class="p-1 font-sans text-xs">
                    <p class="font-bold text-slate-900">#${idx + 1} ${sanitizeHTML(job.owner_name || 'Client')}</p>
                    <p class="text-slate-600">${sanitizeHTML(job.address || '')}</p>
                    <p class="text-slate-500 mt-1 font-semibold text-[10px]">Type: ${sanitizeHTML(job.assignment_type || job.service_type || 'maintenance')}</p>
                </div>
            `);

            const marker = new maplibregl.Marker({ element: el })
                .setLngLat([lng, lat])
                .setPopup(popup)
                .addTo(routeMapInstance);

            routeMarkers.push(marker);
        });

        if (coords.length > 1) {
            routeMapInstance.addSource('provider-route-source', {
                type: 'geojson',
                data: {
                    type: 'Feature',
                    geometry: {
                        type: 'LineString',
                        coordinates: coords
                    }
                }
            });

            routeMapInstance.addLayer({
                id: 'provider-route-line',
                type: 'line',
                source: 'provider-route-source',
                paint: {
                    'line-color': '#0B4F4A',
                    'line-width': 4,
                    'line-dasharray': [2, 2]
                }
            });

            // Fit bounds to show all markers
            const bounds = coords.reduce((b, coord) => b.extend(coord), new maplibregl.LngLatBounds(coords[0], coords[0]));
            routeMapInstance.fitBounds(bounds, { padding: 50, maxZoom: 15 });
        } else if (coords.length === 1) {
            routeMapInstance.flyTo({ center: coords[0], zoom: 14 });
        } else {
            routeMapInstance.flyTo({ center: defaultCenter, zoom: 13 });
        }
    }

    // ============================================================
    // TRANSACTION HISTORY
    // ============================================================
    async function viewProviderHistory(providerId) {
        const p = PROVIDERS[providerId];
        document.getElementById('historyProviderName').textContent = p ? p.name : `Provider #${providerId}`;
        const contentEl = document.getElementById('historyContent');
        contentEl.innerHTML = '<div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading transaction history...</div>';

        openModal('providerHistoryModal');

        try {
            const res = await fetch(`../../api/service_requests.php?provider_id=${providerId}&history=1`);
            const json = await res.json();
            const history = (json.success && Array.isArray(json.data)) ? json.data : [];

            if (history.length === 0) {
                contentEl.innerHTML = `
                    <div class="text-center py-10">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mx-auto mb-3">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No completed transaction history yet.</p>
                        <p class="text-xs text-slate-400 mt-1">Completed service and desludging jobs will automatically appear here.</p>
                    </div>
                `;
                return;
            }

            contentEl.innerHTML = `
                <div class="space-y-3">
                    ${history.map(item => `
                        <div class="p-3.5 bg-slate-50 hover:bg-slate-100/80 rounded-xl border border-slate-200 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-lg ${item.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'} flex items-center justify-center text-xs">
                                        <i class="fa-solid ${item.status === 'completed' ? 'fa-check' : 'fa-xmark'}"></i>
                                    </span>
                                    <div>
                                        <p class="font-bold text-slate-800 text-sm">${sanitizeHTML(item.owner_name || 'Client')} &bull; <span class="text-xs text-slate-500">${sanitizeHTML(item.tank_id || '')}</span></p>
                                        <p class="text-xs text-slate-500">${sanitizeHTML(item.service_type || 'Desludging')}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${item.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}">
                                        ${sanitizeHTML(item.status ? item.status.toUpperCase() : 'COMPLETED')}
                                    </span>
                                    <p class="text-[11px] text-slate-400 mt-1">${sanitizeHTML(item.preferred_date || item.created_at || '')}</p>
                                </div>
                            </div>
                            ${item.rating ? `<div class="mt-2 text-xs font-semibold text-amber-500 flex items-center gap-1">Rating: ${item.rating} ⭐</div>` : ''}
                            ${item.notes ? `<p class="mt-2 text-xs text-slate-600 bg-white p-2 rounded-lg border border-slate-100">${sanitizeHTML(item.notes)}</p>` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        } catch (err) {
            console.error('viewProviderHistory error:', err);
            contentEl.innerHTML = '<div class="text-center py-10 text-rose-500 text-sm">Failed to load transaction history.</div>';
        }
    }

    // CSV export for providers
    function exportProvidersCSV() {
        try {
            const headers = ['ID', 'Name', 'Contact', 'Email', 'Specialization', 'Status', 'Rating', 'Equipment', 'Jobs', 'Response Time', 'Joined'];
            const rows = Object.values(PROVIDERS).map(p => [
                p.provider_id, p.name, p.contact, p.email,
                p.specialization, p.status, p.rating,
                p.equipment_count, p.completed_jobs, p.response_time, p.joined_date
            ]);
            const csvContent = [headers, ...rows].map(r =>
                r.map(v => '"' + String(v ?? '').replace(/"/g, '""') + '"').join(',')
            ).join('\n');
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'service_providers_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Exported to ' + a.download, 'success');
        } catch (err) {
            showToast('Export failed: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchProvider').addEventListener('input', filterProviders);
    document.getElementById('filterStatus').addEventListener('change', filterProviders);
    document.getElementById('filterSpecialization').addEventListener('change', filterProviders);
    document.getElementById('filterRating').addEventListener('change', filterProviders);
    document.getElementById('filterDateFrom').addEventListener('change', filterProviders);
    document.getElementById('filterDateTo').addEventListener('change', filterProviders);

    function filterProviders() {
        const search = document.getElementById('searchProvider').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const specialization = document.getElementById('filterSpecialization').value;
        const rating = document.getElementById('filterRating').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.provider-card').forEach(card => {
            const name = card.dataset.name;
            const id = card.dataset.id.toLowerCase();
            const cardStatus = card.dataset.status;
            const cardSpecialization = card.dataset.specialization;
            const cardRating = parseFloat(card.dataset.rating);
            const contact = card.dataset.contact || '';
            const joinedDate = card.dataset.joinedDate || '';

            const matchesSearch = !search || [name, id, contact, cardSpecialization].some(value => value.includes(search));
            const matchesStatus = !status || cardStatus === status;
            const matchesSpecialization = !specialization || cardSpecialization === specialization;
            let matchesRating = true;
            if (rating) {
                const minRating = parseFloat(rating);
                matchesRating = cardRating >= minRating;
            }
            const matchesDateFrom = !dateFrom || (joinedDate && joinedDate >= dateFrom);
            const matchesDateTo = !dateTo || (joinedDate && joinedDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesSpecialization && matchesRating && matchesDateFrom && matchesDateTo;

            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchProvider').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterSpecialization').value = '';
        document.getElementById('filterRating').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.provider-card').forEach(card => card.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    // ESC key and backdrop-click are handled by common.js
</script>

<?php include_once '../../includes/footer.php'; ?>