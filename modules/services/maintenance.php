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
    echo json_encode(['success' => true, 'action' => $action, 'message' => 'Operation processed successfully.']);
    exit;
}

// Sample Technicians
$technicians = [
    ['id' => 1, 'name' => 'Roberto Silva', 'status' => 'on_site', 'assignment' => 'ST-002'],
    ['id' => 2, 'name' => 'Jose Mendoza', 'status' => 'available', 'assignment' => null],
    ['id' => 3, 'name' => 'Luis Torres', 'status' => 'en_route', 'assignment' => 'ST-001'],
    ['id' => 4, 'name' => 'Carlos Santos', 'status' => 'available', 'assignment' => null],
    ['id' => 5, 'name' => 'Ana Reyes', 'status' => 'on_site', 'assignment' => 'ST-003'],
];

require_once __DIR__ . '/../../app/Models/MaintenanceRecord.php';
require_once __DIR__ . '/../../app/Models/SepticTank.php';
$maintenanceModel = new MaintenanceRecord();
$septicTankModel = new SepticTank();

// Fetch live Maintenance Records from Supabase
$maintenanceRecords = $maintenanceModel->all();

// Fetch Septic Tanks for Coordinate Enrichment in Map Routing and de-duplicate by tank_id
$rawSepticTanks = $septicTankModel->all();
$allSepticTanks = [];
$seenTankIds = [];
foreach ($rawSepticTanks as $st) {
    $tid = trim($st['tank_id'] ?? '');
    if ($tid !== '' && !isset($seenTankIds[$tid])) {
        $seenTankIds[$tid] = true;
        $allSepticTanks[] = $st;
    }
}

$tankLookup = [];
foreach ($allSepticTanks as $st) {
    if (!empty($st['tank_id'])) {
        $tankLookup[$st['tank_id']] = [
            'lat' => (!empty($st['latitude']) && is_numeric($st['latitude'])) ? (float)$st['latitude'] : null,
            'lng' => (!empty($st['longitude']) && is_numeric($st['longitude'])) ? (float)$st['longitude'] : null,
            'address' => $st['address'] ?? '',
            'owner' => $st['owner_name'] ?? ''
        ];
    }
}

// Technicians
$technicians = [
    ['id' => 1, 'name' => 'Roberto Silva', 'status' => 'on_site', 'assignment' => 'ST-002'],
    ['id' => 2, 'name' => 'Jose Mendoza', 'status' => 'available', 'assignment' => null],
    ['id' => 3, 'name' => 'Luis Torres', 'status' => 'en_route', 'assignment' => 'ST-001'],
    ['id' => 4, 'name' => 'Carlos Santos', 'status' => 'available', 'assignment' => null],
    ['id' => 5, 'name' => 'Ana Reyes', 'status' => 'on_site', 'assignment' => 'ST-003'],
];

// Route Planning Data
$routeData = [
    ['technician' => 'Roberto Silva', 'tank' => 'ST-002', 'address' => '456 Mabini Ave., Poblacion', 'status' => 'In Progress'],
    ['technician' => 'Jose Mendoza', 'tank' => 'ST-005', 'address' => '505 Bonifacio Rd., Riverside', 'status' => 'En Route'],
    ['technician' => 'Luis Torres', 'tank' => 'ST-001', 'address' => '123 Rizal St., San Jose', 'status' => 'Completed'],
];

// Stats
$counts = $maintenanceModel->countByStatus();
$totalServices = count($maintenanceRecords);
$scheduledServices = $counts['scheduled'];
$inProgress = $counts['in_progress'];
$completedServices = $counts['completed'];
$totalRevenue = array_sum(array_column($maintenanceRecords, 'cost'));

$title = 'Maintenance & Desludging';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Maintenance & Desludging</h2>
            <p class="text-sm text-slate-500 mt-0.5">Schedule services, manage records & track completions</p>
        </div>
        <div class="flex gap-3">
            <button onclick="exportTableToCSV('#maintenanceTableBody', 'maintenance_services')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2"
                    title="Export to CSV" aria-label="Export maintenance records to CSV">
                <i class="fa-solid fa-file-csv text-xs"></i> Export
            </button>
            <button onclick="openModal('scheduleServiceModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-calendar-plus text-xs"></i> Schedule Service
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Services -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-tools text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalServices; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Services</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🔧 All services</span>
                    <span class="text-[10px] text-slate-400">Including <?php echo $completedServices; ?> completed</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Scheduled -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-blue-600"><?php echo $scheduledServices; ?></p>
                        <p class="text-xs font-medium text-slate-500">Scheduled</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📅 Upcoming</span>
                    <span class="text-[10px] text-slate-400">Awaiting execution</span>
                </div>
            </div>
        </div>

        <!-- Card 3: In Progress -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-spinner text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $inProgress; ?></p>
                        <p class="text-xs font-medium text-slate-500">In Progress</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">🔄 Active</span>
                    <span class="text-[10px] text-slate-400">Being worked on</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Completed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $completedServices; ?></p>
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Done</span>
                    <span class="text-[10px] text-slate-400">Successfully finished</span>
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
                    <span class="text-[10px] text-slate-400">From <?php echo $completedServices; ?> services</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Technician Status -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-3 mb-4">
        <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">👷 Technician Status</h4>
        <div class="flex flex-wrap gap-4">
            <?php foreach ($technicians as $tech): ?>
                <?php
                    $statusColors = [
                        'available' => 'bg-emerald-100 text-emerald-700',
                        'on_site' => 'bg-blue-100 text-blue-700',
                        'en_route' => 'bg-amber-100 text-amber-700',
                    ];
                ?>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full <?php echo $tech['status'] === 'available' ? 'bg-emerald-500' : ($tech['status'] === 'on_site' ? 'bg-blue-500' : 'bg-amber-500'); ?>"></span>
                    <span class="text-xs font-medium text-slate-700"><?php echo $tech['name']; ?></span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full <?php echo $statusColors[$tech['status']] ?? 'bg-slate-100 text-slate-500'; ?>">
                        <?php echo str_replace('_', ' ', ucfirst($tech['status'])); ?>
                        <?php if ($tech['assignment']): ?>
                            (<?php echo $tech['assignment']; ?>)
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchMaintenance"
                       placeholder="Search by service ID, tank ID, or owner..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
                <select id="filterType" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="desludging">Desludging</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="installation">Installation</option>
                </select>
                <select id="filterTechnician" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Technicians</option>
                    <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo $tech['name']; ?>"><?php echo $tech['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Scheduled date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Scheduled date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Maintenance Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Service ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tank / Owner</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Technician</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Scheduled</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cost</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="maintenanceTableBody">
                    <?php foreach ($maintenanceRecords as $record): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors maintenance-row <?php echo $record['status'] === 'in_progress' ? 'bg-amber-50/30' : ''; ?>"
                        data-owner="<?php echo htmlspecialchars(strtolower($record['owner_name']), ENT_QUOTES, 'UTF-8'); ?>"
                        data-tank="<?php echo htmlspecialchars($record['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-status="<?php echo htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-type="<?php echo htmlspecialchars($record['service_type'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-technician="<?php echo htmlspecialchars(strtolower($record['technician']), ENT_QUOTES, 'UTF-8'); ?>"
                        data-scheduled-date="<?php echo htmlspecialchars($record['scheduled_date'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-row-id="<?php echo (int)$record['id']; ?>"
                        data-id="<?php echo (int)$record['id']; ?>"
                        id="maintenance-row-<?php echo (int)$record['id']; ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo $record['service_id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $record['owner_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $record['tank_id']; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $record['service_type'] === 'desludging' ? 'bg-violet-100 text-violet-700' : ($record['service_type'] === 'maintenance' ? 'bg-blue-100 text-blue-700' : ($record['service_type'] === 'inspection' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700')); ?>">
                                <?php echo ucfirst($record['service_type']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $record['technician']; ?></td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php echo date('M d, Y', strtotime($record['scheduled_date'])); ?>
                            <br><span class="text-[10px] text-slate-400"><?php echo $record['scheduled_time']; ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'scheduled' => 'bg-blue-100 text-blue-700',
                                    'in_progress' => 'bg-amber-100 text-amber-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$record['status']] ?? $statusColors['scheduled']; ?>">
                                <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm font-bold text-slate-700">₱<?php echo number_format($record['cost'], 2); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewService(<?php echo $record['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="viewServiceRoute(<?php echo $record['id']; ?>)"
                                        class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Route / Location Map">
                                    <i class="fa-solid fa-route text-sm"></i>
                                </button>
                                <?php if ($record['status'] === 'scheduled'): ?>
                                    <button onclick="startService(<?php echo $record['id']; ?>)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Start Service">
                                        <i class="fa-solid fa-play text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($record['status'] === 'in_progress'): ?>
                                    <button onclick="completeService(<?php echo $record['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete Service">
                                        <i class="fa-solid fa-check text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($record['status'] === 'completed' && !$record['rating']): ?>
                                    <button onclick="rateService(<?php echo $record['id']; ?>)"
                                            class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition" title="Rate">
                                        <i class="fa-solid fa-star text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="wastewater_billing.php?client=<?php echo urlencode($record['owner_name']); ?>&tank_id=<?php echo urlencode($record['tank_id']); ?>&service_type=<?php echo urlencode($record['service_type']); ?>&action=new_quote"
                                   class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Proceed to Wastewater Billing">
                                    <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                                </a>
                                <button onclick="editService(<?php echo $record['id']; ?>)"
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
                <i class="fa-solid fa-tools text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No services match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo $totalServices; ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalServices; ?></span> services
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
<!-- SCHEDULE SERVICE MODAL                                       -->
<!-- ============================================================ -->
<div id="scheduleServiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-brand-medium"></i>
                Schedule Service
            </h3>
            <button onclick="closeModal('scheduleServiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="scheduleServiceForm" class="p-6 space-y-4" onsubmit="saveScheduleService(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            
            <!-- Quick Live Filter / Search Bar for Scalable Tank Selection -->
            <div class="p-3 bg-brand-light/30 rounded-xl border border-brand-border">
                <div class="flex items-center gap-2 mb-1.5">
                    <i class="fa-solid fa-magnifying-glass text-brand-medium text-xs"></i>
                    <label for="tankQuickSearch" class="text-xs font-bold text-slate-700">Quick Tank or Owner Search</label>
                    <span class="text-[10px] text-slate-400 ml-auto">Filters dropdowns instantly</span>
                </div>
                <input type="text" id="tankQuickSearch" oninput="filterTankDropdowns(this.value, 'schedule_tank', 'schedule_owner')" placeholder="Type Tank ID, Owner name, or Address..." class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <!-- Real Septic Tank Data Linked Dropdowns -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Septic Tank <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="schedule_tank" required onchange="onScheduleTankChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Septic Tank</option>
                            <?php foreach ($allSepticTanks as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-owner="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-address="<?php echo htmlspecialchars($st['address'] . (isset($st['barangay']) ? ', ' . $st['barangay'] : ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-type="<?php echo htmlspecialchars($st['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="schedule_owner" required onchange="onScheduleOwnerChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Registered Owner</option>
                            <?php foreach ($allSepticTanks as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-tank="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-address="<?php echo htmlspecialchars($st['address'] . (isset($st['barangay']) ? ', ' . $st['barangay'] : ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-type="<?php echo htmlspecialchars($st['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Duplicate Schedule Warning Alert -->
            <div id="schedule_duplicate_warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i>
                <div id="schedule_duplicate_warning_text">This tank already has an active scheduled service.</div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Location Address</label>
                <input type="text" id="schedule_address" readonly class="w-full px-3 py-2 border border-slate-200 bg-slate-50 text-slate-600 rounded-lg text-sm outline-none" placeholder="Auto-populated from selected tank">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type <span class="text-rose-500">*</span></label>
                    <select id="schedule_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="desludging">Desludging</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inspection">Inspection</option>
                        <option value="installation">Installation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assign Technician <span class="text-rose-500">*</span></label>
                    <select id="schedule_technician" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">Select Technician</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo htmlspecialchars($tech['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($tech['name'] . ' (' . $tech['role'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Scheduled Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="schedule_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Scheduled Time <span class="text-rose-500">*</span></label>
                    <input type="time" id="schedule_time" value="09:00" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Estimated Cost (PHP)</label>
                    <input type="number" id="schedule_cost" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitCostInput(this)" title="Maximum 11 whole-number digits" value="1500" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes / Instructions</label>
                <textarea id="schedule_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Special instructions for the technician..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('scheduleServiceModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-calendar-check mr-1.5"></i> Confirm Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT SERVICE MODAL                                           -->
<!-- ============================================================ -->
<div id="editServiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Edit Scheduled Service</h3>
            <button onclick="closeModal('editServiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editServiceForm" class="p-6 space-y-4" onsubmit="saveServiceEdit(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="edit_service_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID</label>
                    <div class="relative">
                        <select id="edit_service_tank" onchange="onEditServiceTankChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8 font-mono">
                            <?php foreach ($allSepticTanks as $tank): ?>
                            <option value="<?php echo $tank['tank_id']; ?>" data-owner="<?php echo htmlspecialchars($tank['owner_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tank['owner_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Registered Owner</label>
                    <div class="relative">
                        <select id="edit_service_owner" onchange="onEditServiceOwnerChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <?php foreach ($allSepticTanks as $tank): ?>
                            <option value="<?php echo htmlspecialchars($tank['owner_name'], ENT_QUOTES, 'UTF-8'); ?>" data-tank="<?php echo htmlspecialchars($tank['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tank['owner_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label><select id="edit_service_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="desludging">Desludging</option><option value="maintenance">Maintenance</option><option value="inspection">Inspection</option><option value="installation">Installation</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Technician</label><select id="edit_service_technician" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><?php foreach ($technicians as $tech): ?><option value="<?php echo $tech['name']; ?>"><?php echo $tech['name']; ?></option><?php endforeach; ?></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Scheduled Date</label><input type="date" id="edit_service_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Scheduled Time</label><input type="time" id="edit_service_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_service_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="scheduled">Scheduled</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Cost (PHP)</label><input type="number" id="edit_service_cost" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitCostInput(this)" title="Maximum 11 whole-number digits" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_service_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editServiceModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<div id="viewServiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Service Details</h3>
            <button onclick="closeModal('viewServiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="serviceDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- COMPLETION REPORT MODAL                                      -->
<!-- ============================================================ -->
<div id="completionReportModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-file-circle-check text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-tight">Service Completion Report</h3>
                    <p class="text-xs text-slate-400">Record technician findings before proceeding to billing</p>
                </div>
            </div>
            <button onclick="closeModal('completionReportModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="completionReportForm" class="p-6 space-y-4" onsubmit="saveCompletionReport(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="complete_service_id">
            
            <div class="p-3 bg-brand-light/60 border border-brand-border/80 rounded-xl flex items-center gap-2.5 text-xs text-brand-dark">
                <i class="fa-solid fa-circle-info text-brand-medium text-sm flex-shrink-0"></i>
                <span>Submitting this report finalizes field service and opens <strong>Wastewater Billing</strong> to generate quotation and collect payment.</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Technician Findings / Work Done <span class="text-rose-500">*</span></label>
                <textarea id="complete_findings" rows="3" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Describe the desludging/maintenance conducted, sludge depth, inlet/outlet condition..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recommendations & Next Service Schedule</label>
                <textarea id="complete_recommendations" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Recommended desludging interval (e.g. 3-5 years), preventive maintenance notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('completionReportModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-arrow-right-to-bracket text-xs"></i> Submit Report & Proceed to Billing
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- SERVICE ROUTE / LOCATION MAP MODAL                           -->
<!-- ============================================================ -->
<div id="serviceRouteModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-route text-brand-medium"></i>
                Service Route & Location Map
            </h3>
            <button onclick="closeModal('serviceRouteModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-3 flex justify-between items-center text-xs text-slate-500">
                <span id="routeMapTitle" class="font-semibold text-slate-700">Tank Service Location</span>
                <span id="routeMapCoordinates" class="font-mono text-slate-400">Lat: 0, Lng: 0</span>
            </div>
            <div id="leafletRouteMap" class="w-full h-80 rounded-xl border border-slate-200 shadow-inner z-10"></div>
            <div id="routeServiceInfo" class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 flex justify-between items-center">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- Leaflet CSS & JS for Interactive Map -->
<link rel="stylesheet" href="<?= site_url('assets/css/leaflet.css'); ?>" />
<script src="<?= site_url('assets/js/leaflet.js'); ?>"></script>
<script>
    const SERVICES = <?php echo json_encode(array_column($maintenanceRecords, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;
    const TANKS_GEO = <?php echo json_encode($tankLookup, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

    // Modal functions, toast, sanitizeHTML, and export provided by common.js

    // ============================================================
    // VIEW SERVICE
    // ============================================================
    function viewService(id) {
        openModal('viewServiceModal');
        const s = SERVICES[id];
        if (!s) return;

        setTimeout(() => {
            const statusColors = {
                scheduled: 'bg-blue-100 text-blue-700',
                in_progress: 'bg-amber-100 text-amber-700',
                completed: 'bg-emerald-100 text-emerald-700'
            };

            // Use sanitizeHTML() from common.js to prevent XSS
            const sOwner = sanitizeHTML(s.owner_name);
            const sSvcId = sanitizeHTML(s.service_id);
            const sTankId = sanitizeHTML(s.tank_id);
            const sType = sanitizeHTML(s.service_type);
            const sTech = sanitizeHTML(s.technician);
            const sFindings = sanitizeHTML(s.findings);
            const sRecs = sanitizeHTML(s.recommendations);
            const sNotes = sanitizeHTML(s.notes);
            const sStatus = sanitizeHTML(s.status);
            const sTimeStr = sanitizeHTML(s.scheduled_time);

            document.getElementById('serviceDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${sOwner.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${sOwner}</h4>
                            <p class="text-sm text-slate-500">${sSvcId} &bull; ${sTankId}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[s.status] || statusColors.scheduled}">
                                ${sStatus.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Service Type</p><p class="text-sm text-slate-800 capitalize">${sType}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Technician</p><p class="text-sm text-slate-800">${sTech}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Scheduled Date</p><p class="text-sm text-slate-800">${new Date(s.scheduled_date).toLocaleDateString()} at ${sTimeStr}</p></div>
                        ${s.completed_date ? `<div><p class="text-xs text-slate-400 font-semibold">Completed</p><p class="text-sm text-slate-800">${new Date(s.completed_date).toLocaleDateString()} at ${sanitizeHTML(s.completed_time)}</p></div>` : ''}
                        <div><p class="text-xs text-slate-400 font-semibold">Cost</p><p class="text-sm font-bold text-slate-800">&curren;${Number(s.cost).toFixed(2)}</p></div>
                        ${s.rating ? `<div><p class="text-xs text-slate-400 font-semibold">Rating</p><p class="text-sm text-amber-500">${'⭐'.repeat(s.rating)}</p></div>` : ''}
                    </div>
                    ${sFindings ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Findings</h5><p class="text-sm text-slate-800">${sFindings}</p></div>` : ''}
                    ${sRecs ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">Recommendations</h5><p class="text-sm text-slate-800">${sRecs}</p></div>` : ''}
                    ${sNotes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${sNotes}</p></div>` : ''}
                    <div class="flex flex-wrap justify-end items-center gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewServiceModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <a href="wastewater_billing.php?client=${encodeURIComponent(s.owner_name)}&tank_id=${encodeURIComponent(s.tank_id)}&service_type=${encodeURIComponent(s.service_type)}&action=new_quote"
                           class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-file-invoice-dollar text-sm"></i> Proceed to Billing
                        </a>
                        ${s.status === 'scheduled' ? `<button onclick="closeModal('viewServiceModal'); startService(${s.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold"><i class="fa-solid fa-play mr-1.5"></i> Start Service</button>` : ''}
                        ${s.status === 'in_progress' ? `<button onclick="closeModal('viewServiceModal'); completeService(${s.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Complete</button>` : ''}
                    </div>
                </div>
            `;
        }, 300);
    }

    async function startService(id) {
        try {
            const s = SERVICES[id];
            if (!s) return;
            if (s.status !== 'scheduled') {
                showToast('Only scheduled services can be started.', 'warning');
                return;
            }
            s.status = 'in_progress';
            updateServiceRow(s);
            await fetch(`../../api/maintenance.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'in_progress' })
            });
            showToast('Service #' + s.service_id + ' started! Status set to In Progress.', 'info');
            filterMaintenance();
        } catch (err) {
            console.error('startService error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    function completeService(id) {
        const s = SERVICES[id];
        if (!s) return;
        if (s.status !== 'in_progress') {
            showToast('Service must be moved to In Progress before it can be marked as completed.', 'warning');
            return;
        }
        
        const serviceIdInput = document.getElementById('complete_service_id');
        if (serviceIdInput) serviceIdInput.value = id;
        
        const findingsInput = document.getElementById('complete_findings');
        if (findingsInput) findingsInput.value = s.findings || '';
        
        const recsInput = document.getElementById('complete_recommendations');
        if (recsInput) recsInput.value = s.recommendations || '';
        
        openModal('completionReportModal');
    }

    async function saveCompletionReport(event) {
        event.preventDefault();
        try {
            const findings = document.getElementById('complete_findings').value.trim();
            if (!findings) {
                showToast('Findings are required to complete the report.', 'warning');
                document.getElementById('complete_findings').focus();
                return;
            }
            const id = document.getElementById('complete_service_id').value;
            const s = SERVICES[id];
            if (!s) { showToast('Service record not found.', 'danger'); return; }
            if (s.status !== 'in_progress') {
                showToast('Service must be in progress before completing.', 'warning');
                return;
            }

            s.completed_date = new Date().toISOString().split('T')[0];
            s.completed_time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            s.findings = findings;
            s.recommendations = document.getElementById('complete_recommendations')?.value?.trim() || '';

            updateServiceRow(s);
            await fetch(`../../api/maintenance.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    completed_date: s.completed_date,
                    completed_time: s.completed_time,
                    findings: s.findings,
                    recommendations: s.recommendations
                })
            });
            closeModal('completionReportModal');
            showToast(`Report submitted! Redirecting to Wastewater Billing for ${s.owner_name}...`, 'success');

            setTimeout(() => {
                window.location.href = `wastewater_billing.php?client=${encodeURIComponent(s.owner_name)}&tank_id=${encodeURIComponent(s.tank_id)}&service_type=${encodeURIComponent(s.service_type)}&action=new_quote&service_id=${encodeURIComponent(s.service_id)}`;
            }, 600);
        } catch (err) {
            console.error('saveCompletionReport error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    function updateServiceRow(s) {
        const row = document.getElementById('maintenance-row-' + s.id);
        if (!row) return;

        // Update status badge
        const statusColors = {
            scheduled:   'bg-blue-100 text-blue-700',
            in_progress: 'bg-amber-100 text-amber-700',
            completed:   'bg-emerald-100 text-emerald-700',
            cancelled:   'bg-slate-100 text-slate-500'
        };
        const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
        if (statusBadge) {
            statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[s.status] || statusColors.scheduled}`;
            statusBadge.textContent = s.status.replace('_', ' ').toUpperCase();
        }

        // Update dataset for filters
        row.dataset.status    = s.status;
        row.dataset.technician = (s.technician || '').toLowerCase();

        // Update cost cell
        const costCell = row.querySelector('.text-sm.font-bold.text-slate-700');
        if (costCell) costCell.textContent = '\u20b1' + Number(s.cost).toFixed(2);

        // Rebuild action buttons based on status (linear: scheduled -> in_progress -> completed)
        const tds = row.querySelectorAll('td');
        const actionsTd = tds[tds.length - 1];
        if (actionsTd) {
            const isScheduled  = s.status === 'scheduled';
            const isInProgress = s.status === 'in_progress';
            const isCompleted  = s.status === 'completed';

            actionsTd.innerHTML = `
                <div class="flex items-center justify-center gap-1">
                    <button onclick="viewService(${s.id})"
                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>
                    <button onclick="viewServiceRoute(${s.id})"
                            class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Route / Location Map">
                        <i class="fa-solid fa-route text-sm"></i>
                    </button>
                    ${isScheduled ? `
                        <button onclick="startService(${s.id})"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Start">
                            <i class="fa-solid fa-play text-sm"></i>
                        </button>
                    ` : ''}
                    ${isInProgress ? `
                        <button onclick="completeService(${s.id})"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete">
                            <i class="fa-solid fa-check text-sm"></i>
                        </button>
                    ` : ''}
                    ${isCompleted && !s.rating ? `
                        <button onclick="rateService(${s.id})"
                                class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition" title="Rate">
                            <i class="fa-solid fa-star text-sm"></i>
                        </button>
                    ` : ''}
                    <button onclick="editService(${s.id})"
                            class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                        <i class="fa-solid fa-pen text-sm"></i>
                    </button>
                </div>
            `;
        }
    }

    // ============================================================
    // EDIT SERVICE
    // ============================================================
    function limitCostInput(input) {
        const parts = String(input.value || '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 11);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    function isValidCost(value) {
        return /^\d{1,11}(\.\d{1,2})?$/.test(String(value)) && Number(value) >= 0 && Number(value) <= 99999999999.99;
    }

    function parseTimeTo24(timeStr) {
        if (!timeStr) return '09:00';
        timeStr = timeStr.trim();
        if (/^\d{2}:\d{2}$/.test(timeStr)) return timeStr;
        const match = timeStr.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)?/i);
        if (!match) return '09:00';
        let hours = parseInt(match[1], 10);
        const mins = match[2];
        const modifier = (match[3] || '').toUpperCase();
        if (modifier === 'PM' && hours < 12) hours += 12;
        if (modifier === 'AM' && hours === 12) hours = 0;
        return String(hours).padStart(2, '0') + ':' + mins;
    }

    function formatTime24to12(timeStr) {
        if (!timeStr) return '09:00 AM';
        timeStr = timeStr.trim();
        const parts = timeStr.split(':');
        if (parts.length < 2) return timeStr;
        let hours = parseInt(parts[0], 10);
        const mins = parts[1].slice(0, 2);
        if (isNaN(hours)) return timeStr;
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return String(hours).padStart(2, '0') + ':' + mins + ' ' + ampm;
    }

    function editService(id) {
        const service = SERVICES[id];
        if (!service) return;
        document.getElementById('edit_service_id').value = service.id;
        document.getElementById('edit_service_tank').value = service.tank_id;
        document.getElementById('edit_service_owner').value = service.owner_name;
        document.getElementById('edit_service_type').value = service.service_type;
        document.getElementById('edit_service_technician').value = service.technician;
        document.getElementById('edit_service_date').value = service.scheduled_date;
        document.getElementById('edit_service_time').value = parseTimeTo24(service.scheduled_time);
        document.getElementById('edit_service_status').value = service.status;
        document.getElementById('edit_service_cost').value = service.cost;
        document.getElementById('edit_service_notes').value = service.notes || '';
        openModal('editServiceModal');
    }

    async function saveServiceEdit(event) {
        event.preventDefault();
        try {
            const costVal = document.getElementById('edit_service_cost').value;
            if (!isValidCost(costVal)) {
                showToast('Cost must be a valid positive number (max 11 digits).', 'warning');
                return;
            }
            const id = document.getElementById('edit_service_id').value;
            const timeRaw = document.getElementById('edit_service_time').value;
            const payload = {
                tank_id: document.getElementById('edit_service_tank').value.trim(),
                owner_name: document.getElementById('edit_service_owner').value.trim(),
                service_type: document.getElementById('edit_service_type').value,
                scheduled_date: document.getElementById('edit_service_date').value,
                scheduled_time: formatTime24to12(timeRaw),
                technician: document.getElementById('edit_service_technician').value,
                status: document.getElementById('edit_service_status').value,
                cost: Number(costVal),
                notes: document.getElementById('edit_service_notes').value.trim()
            };

            const res = await fetch(`../../api/maintenance.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                closeModal('editServiceModal');
                showToast('Maintenance service updated successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to update service', 'danger');
            }
        } catch (err) {
            console.error('saveServiceEdit error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // RATE SERVICE
    // ============================================================
    async function rateService(id) {
        const rating = prompt('Rate this service (1-5 stars):', '5');
        if (rating && rating >= 1 && rating <= 5) {
            try {
                const res = await fetch(`../../api/maintenance.php?id=${id}&action=update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ rating: parseInt(rating) })
                });
                const json = await res.json();
                if (json.success) {
                    showToast('Service rated ' + rating + ' stars!', 'success');
                    setTimeout(() => location.reload(), 800);
                }
            } catch (err) {
                console.error(err);
            }
        }
    }

    // ============================================================
    // SERVICE ROUTE / LOCATION MAP
    // ============================================================
    let _routeMapInstance = null;
    let _routeMarker = null;

    function viewServiceRoute(id) {
        const s = SERVICES[id];
        if (!s) return;

        const geo = (typeof TANKS_GEO !== 'undefined' && TANKS_GEO[s.tank_id]) ? TANKS_GEO[s.tank_id] : {};
        const safeLat = (geo.lat && !isNaN(geo.lat)) ? Number(geo.lat) : 14.6538;
        const safeLng = (geo.lng && !isNaN(geo.lng)) ? Number(geo.lng) : 120.9820;
        const owner = s.owner_name || 'Client';
        const tankId = s.tank_id || 'Tank';
        const tech = s.technician || 'Unassigned';
        const status = s.status || 'scheduled';

        document.getElementById('routeMapTitle').textContent = `${sanitizeHTML(owner)} — ${sanitizeHTML(tankId)}`;
        document.getElementById('routeMapCoordinates').textContent = `Lat: ${safeLat.toFixed(4)}, Lng: ${safeLng.toFixed(4)}`;

        const statusBadges = {
            scheduled: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">Scheduled</span>',
            in_progress: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">In Progress</span>',
            completed: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Completed</span>'
        };

        document.getElementById('routeServiceInfo').innerHTML = `
            <div>
                <p class="font-bold text-slate-800">${sanitizeHTML(tankId)} &bull; ${sanitizeHTML(owner)}</p>
                <p class="text-slate-500 mt-0.5"><i class="fa-solid fa-user-gear mr-1 text-brand-medium"></i> Technician: <strong class="text-slate-700">${sanitizeHTML(tech)}</strong></p>
            </div>
            <div class="text-right">
                ${statusBadges[status] || ''}
                <p class="text-slate-400 text-[11px] mt-1">${new Date(s.scheduled_date).toLocaleDateString()} ${sanitizeHTML(s.scheduled_time || '')}</p>
            </div>
        `;

        openModal('serviceRouteModal');

        setTimeout(() => {
            if (typeof L === 'undefined') return;
            const container = document.getElementById('leafletRouteMap');
            if (!container) return;

            const customPinIcon = L.divIcon({
                className: 'custom-route-marker',
                html: `<div style="width:36px; height:36px; background:linear-gradient(135deg, #0B4F4A, #14807A); border:3px solid #ffffff; border-radius:50%; box-shadow:0 4px 12px rgba(11,79,74,0.45); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:15px; transform:translate(-2px, -2px);"><i class="fa-solid fa-location-dot"></i></div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
                popupAnchor: [0, -20]
            });

            if (!_routeMapInstance) {
                _routeMapInstance = L.map('leafletRouteMap').setView([safeLat, safeLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(_routeMapInstance);
                _routeMarker = L.marker([safeLat, safeLng], { icon: customPinIcon }).addTo(_routeMapInstance);
            } else {
                _routeMapInstance.setView([safeLat, safeLng], 15);
                if (_routeMarker) {
                    _routeMarker.setIcon(customPinIcon);
                    _routeMarker.setLatLng([safeLat, safeLng]);
                } else {
                    _routeMarker = L.marker([safeLat, safeLng], { icon: customPinIcon }).addTo(_routeMapInstance);
                }
            }
            if (_routeMarker) {
                _routeMarker.bindPopup(`
                    <div style="font-family:inherit; padding:4px;">
                        <strong style="color:#0B4F4A; font-size:13px;">${sanitizeHTML(owner)}</strong><br>
                        <span style="font-size:11px; color:#64748b;">${sanitizeHTML(tankId)} &bull; Assigned: ${sanitizeHTML(tech)}</span>
                    </div>
                `).openPopup();
            }
            _routeMapInstance.invalidateSize();
        }, 200);
    }

    // ============================================================
    // REAL SEPTIC TANK DATA & OWNER DROPDOWN SYNCHRONIZATION
    // ============================================================
    function filterTankDropdowns(query, tankSelectId = 'schedule_tank', ownerSelectId = 'schedule_owner') {
        const q = (query || '').toLowerCase().trim();
        const tankSelect = document.getElementById(tankSelectId);
        const ownerSelect = document.getElementById(ownerSelectId);
        if (!tankSelect || !ownerSelect) return;

        let matchCount = 0;
        let firstMatchedTank = '';

        for (let i = 1; i < tankSelect.options.length; i++) {
            const opt = tankSelect.options[i];
            const text = (opt.textContent + ' ' + (opt.dataset.address || '')).toLowerCase();
            const matches = !q || text.includes(q);
            opt.style.display = matches ? '' : 'none';
            if (matches) {
                matchCount++;
                if (!firstMatchedTank) firstMatchedTank = opt.value;
            }
        }

        for (let i = 1; i < ownerSelect.options.length; i++) {
            const opt = ownerSelect.options[i];
            const text = (opt.textContent + ' ' + (opt.dataset.address || '')).toLowerCase();
            const matches = !q || text.includes(q);
            opt.style.display = matches ? '' : 'none';
        }

        // If exactly 1 match while typing, automatically select and sync it
        if (q && matchCount === 1 && firstMatchedTank && tankSelect.value !== firstMatchedTank) {
            tankSelect.value = firstMatchedTank;
            if (tankSelectId === 'schedule_tank') {
                onScheduleTankChange(firstMatchedTank);
            } else if (tankSelectId === 'edit_service_tank') {
                onEditTankChange(firstMatchedTank);
            }
        }
    }

    function checkDuplicateSchedule(tankId) {
        const warningBox = document.getElementById('schedule_duplicate_warning');
        const warningText = document.getElementById('schedule_duplicate_warning_text');
        if (!warningBox || !warningText) return null;

        if (!tankId) {
            warningBox.classList.add('hidden');
            return null;
        }

        const active = Object.values(SERVICES).find(s => 
            s.tank_id === tankId && 
            (s.status === 'scheduled' || s.status === 'in_progress')
        );

        if (active) {
            const dateStr = active.scheduled_date ? new Date(active.scheduled_date).toLocaleDateString() : '';
            const statusLabel = active.status === 'in_progress' ? 'In Progress' : 'Scheduled';
            warningText.innerHTML = `<strong>Active Schedule Notice:</strong> Tank <strong>${sanitizeHTML(tankId)}</strong> already has an active <strong>${statusLabel}</strong> service (<strong>${sanitizeHTML(active.service_id || '')}</strong>) set for <strong>${dateStr}</strong>.`;
            warningBox.classList.remove('hidden');
            return active;
        } else {
            warningBox.classList.add('hidden');
            return null;
        }
    }

    function onScheduleTankChange(tankId) {
        if (!tankId) {
            checkDuplicateSchedule('');
            return;
        }
        const select = document.getElementById('schedule_tank');
        const opt = select.options[select.selectedIndex];
        if (opt) {
            const owner = opt.dataset.owner || '';
            const address = opt.dataset.address || '';
            
            const ownerSelect = document.getElementById('schedule_owner');
            if (ownerSelect && owner) ownerSelect.value = owner;

            const addressInput = document.getElementById('schedule_address');
            if (addressInput) addressInput.value = address;
        }
        checkDuplicateSchedule(tankId);
    }

    function onScheduleOwnerChange(ownerName) {
        if (!ownerName) {
            checkDuplicateSchedule('');
            return;
        }
        const select = document.getElementById('schedule_owner');
        const opt = select.options[select.selectedIndex];
        let currentTank = '';
        if (opt) {
            const tank = opt.dataset.tank || '';
            const address = opt.dataset.address || '';
            
            const tankSelect = document.getElementById('schedule_tank');
            if (tankSelect && tank) {
                tankSelect.value = tank;
                currentTank = tank;
            }

            const addressInput = document.getElementById('schedule_address');
            if (addressInput) addressInput.value = address;
        }
        checkDuplicateSchedule(currentTank || document.getElementById('schedule_tank')?.value);
    }

    function onEditTankChange(tankId) {
        if (!tankId) return;
        const select = document.getElementById('edit_service_tank');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.owner) {
            const ownerSelect = document.getElementById('edit_service_owner');
            if (ownerSelect) ownerSelect.value = opt.dataset.owner;
        }
    }

    function onEditOwnerChange(ownerName) {
        if (!ownerName) return;
        const select = document.getElementById('edit_service_owner');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.tank) {
            const tankSelect = document.getElementById('edit_service_tank');
            if (tankSelect) tankSelect.value = opt.dataset.tank;
        }
    }

    // ============================================================
    // SCHEDULE SERVICE
    // ============================================================
    async function saveScheduleService(event) {
        event.preventDefault();
        try {
            const tankId = document.getElementById('schedule_tank')?.value?.trim();
            const ownerName = document.getElementById('schedule_owner')?.value?.trim();
            if (!tankId || !ownerName) {
                showToast('Please select a Septic Tank and Owner.', 'warning');
                return;
            }

            const scheduledDate = document.getElementById('schedule_date')?.value || new Date().toISOString().split('T')[0];

            // ⚡ DUPLICATE SCHEDULE PREVENTION: Block overlapping active schedule for the same tank
            const duplicateConflict = Object.values(SERVICES).find(s => 
                s.tank_id === tankId && 
                (s.status === 'scheduled' || s.status === 'in_progress')
            );
            if (duplicateConflict) {
                const confStatus = duplicateConflict.status === 'in_progress' ? 'In Progress' : 'Scheduled';
                showToast(`Duplicate Prevention: Tank ${tankId} already has a ${confStatus} service (#${duplicateConflict.service_id}) on ${duplicateConflict.scheduled_date}.`, 'warning');
                return;
            }

            const costVal = document.getElementById('schedule_cost')?.value || '1500';
            if (!isValidCost(costVal)) {
                showToast('Estimated cost must be a valid positive number (max 11 digits).', 'warning');
                return;
            }

            const payload = {
                tank_id: tankId,
                owner_name: ownerName,
                address: document.getElementById('schedule_address')?.value?.trim() || '',
                service_type: document.getElementById('schedule_type')?.value || 'desludging',
                scheduled_date: scheduledDate,
                scheduled_time: formatTime24to12(document.getElementById('schedule_time')?.value || '09:00'),
                technician: document.getElementById('schedule_technician')?.value || '',
                cost: Number(costVal),
                notes: document.getElementById('schedule_notes')?.value?.trim() || ''
            };

            const res = await fetch('../../api/maintenance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                showToast('Service scheduled successfully!', 'success');
                closeModal('scheduleServiceModal');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to schedule service', 'danger');
            }
        } catch (err) {
            console.error('saveScheduleService error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // Toast, openModal, closeModal, sanitizeHTML, exportTableToCSV provided by common.js

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchMaintenance').addEventListener('input', filterMaintenance);
    document.getElementById('filterStatus').addEventListener('change', filterMaintenance);
    document.getElementById('filterType').addEventListener('change', filterMaintenance);
    document.getElementById('filterTechnician').addEventListener('change', filterMaintenance);
    document.getElementById('filterDateFrom').addEventListener('change', filterMaintenance);
    document.getElementById('filterDateTo').addEventListener('change', filterMaintenance);

    function filterMaintenance() {
        const searchInput = document.getElementById('searchMaintenance');
        const search = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const status = document.getElementById('filterStatus')?.value || '';
        const type = document.getElementById('filterType')?.value || '';
        const technician = (document.getElementById('filterTechnician')?.value || '').toLowerCase();
        const dateFrom = document.getElementById('filterDateFrom')?.value || '';
        const dateTo = document.getElementById('filterDateTo')?.value || '';
        let visibleCount = 0;

        document.querySelectorAll('.maintenance-row').forEach(row => {
            const owner = (row.dataset.owner || '').toLowerCase();
            const tank = (row.dataset.tank || '').toLowerCase();
            const rowStatus = row.dataset.status || '';
            const rowType = (row.dataset.type || '').toLowerCase();
            const rowTechnician = (row.dataset.technician || '').toLowerCase();
            const scheduledDate = row.dataset.scheduledDate || row.dataset.scheduled_date || '';
            const rowText = (row.textContent || row.innerText || '').toLowerCase();

            const matchesSearch = !search || 
                                  owner.includes(search) || 
                                  tank.includes(search) || 
                                  rowTechnician.includes(search) || 
                                  rowText.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesType = !type || rowType === type.toLowerCase();
            const matchesTechnician = !technician || rowTechnician.includes(technician);
            const matchesDateFrom = !dateFrom || (scheduledDate && scheduledDate >= dateFrom);
            const matchesDateTo = !dateTo || (scheduledDate && scheduledDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesType && matchesTechnician && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        const emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        }
    }

    function resetFilters() {
        document.getElementById('searchMaintenance').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterTechnician').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.maintenance-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    // ESC key and backdrop-click are handled by common.js

    // ============================================================
    // SET DEFAULT DATE
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('schedule_date');
        if (dateInput) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.value = tomorrow.toISOString().split('T')[0];
        }

        const urlParams = new URLSearchParams(window.location.search);
        const prefillTank = urlParams.get('tank_id');
        if (prefillTank) {
            openModal('scheduleServiceModal');
            const tankInput = document.getElementById('schedule_tank');
            if (tankInput) tankInput.value = prefillTank;
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>