<?php
// ============================================================
// COLOR PALETTE
// ============================================================
//   'brand-dark':   '#0B4F4A'
//   'brand-medium': '#14807A'
//   'brand-light':  '#E6F5F3'
//   'brand-border': '#B8E0DC'
// ============================================================

// ============================================================
// 1. PHP BACKEND
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('wastewater services');

// Active tab: 'requests' (default) or 'maintenance'
$activeTab = $_GET['tab'] ?? 'requests';
if (!in_array($activeTab, ['requests', 'maintenance'])) {
    $activeTab = 'requests';
}

// AJAX Stub — real writes go through /api/ endpoints
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
        exit;
    }
    echo json_encode(['success' => true, 'action' => $_POST['action'], 'message' => 'Action processed.']);
    exit;
}

// ── Models ────────────────────────────────────────────────────
require_once __DIR__ . '/../../app/Models/ServiceRequest.php';
require_once __DIR__ . '/../../app/Models/MaintenanceRecord.php';
require_once __DIR__ . '/../../app/Models/SepticTank.php';
require_once __DIR__ . '/../../app/Models/Technician.php';

$requestModel    = new ServiceRequest();
$maintenanceModel= new MaintenanceRecord();
$septicTankModel = new SepticTank();
$technicianModel = new Technician();

// ── Data fetch ────────────────────────────────────────────────
$serviceRequests    = $requestModel->all();
$maintenanceRecords = $maintenanceModel->all();
$technicians        = $technicianModel->all();

// De-duplicate septic tanks by tank_id
$rawTanks      = $septicTankModel->all();
$allSepticTanks= [];
$seenTankIds   = [];
foreach ($rawTanks as $st) {
    $tid = trim($st['tank_id'] ?? '');
    if ($tid !== '' && !isset($seenTankIds[$tid])) {
        $seenTankIds[$tid] = true;
        $allSepticTanks[]  = $st;
    }
}
$tankLookup = [];
foreach ($allSepticTanks as $st) {
    if (!empty($st['tank_id'])) {
        $tankLookup[$st['tank_id']] = [
            'lat'      => (is_numeric($st['latitude']  ?? '')) ? (float)$st['latitude']  : null,
            'lng'      => (is_numeric($st['longitude'] ?? '')) ? (float)$st['longitude'] : null,
            'address'  => $st['address']    ?? '',
            'barangay' => $st['barangay']   ?? '',
            'owner'    => $st['owner_name'] ?? ''
        ];
    }
}

// ── Service Request stats ─────────────────────────────────────
$srCounts         = $requestModel->countByStatus();
$totalRequests    = count($serviceRequests);
$pendingRequests  = $srCounts['pending'];
$inProgressReqs   = $srCounts['in_progress'];
$completedReqs    = $srCounts['completed'];
$cancelledReqs    = $srCounts['cancelled'];

$completedForStats = array_filter($serviceRequests, fn($r) =>
    $r['status'] === 'completed' && !empty($r['completed_at']) && !empty($r['created_at'])
);
$avgCompletionDays = null;
if (count($completedForStats) > 0) {
    $totalDays = array_sum(array_map(fn($r) =>
        max(0, round((strtotime($r['completed_at']) - strtotime($r['created_at'])) / 86400, 1)),
        $completedForStats
    ));
    $avgCompletionDays = round($totalDays / count($completedForStats), 1);
}

// ── Maintenance stats ─────────────────────────────────────────
$mCounts           = $maintenanceModel->countByStatus();
$totalServices     = count($maintenanceRecords);
$scheduledServices = $mCounts['scheduled'];
$inProgressSvcs    = $mCounts['in_progress'];
$completedServices = $mCounts['completed'];
$totalRevenue      = array_sum(array_column($maintenanceRecords, 'cost'));

$title = 'Service Requests & Maintenance';
?>

<!-- ============================================================ -->
<!-- 2. HTML                                                      -->
<!-- ============================================================ -->
<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-5">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Service Requests &amp; Maintenance</h1>
            <p class="text-sm text-slate-500 mt-0.5">Manage all wastewater service workflows in one place</p>
        </div>
        <!-- Header action button changes per active tab -->
        <div class="flex gap-3" id="headerActions">
            <button id="exportBtn"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-file-csv text-xs"></i> Export
            </button>
            <button id="primaryActionBtn"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> <span id="primaryActionLabel">New Request</span>
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB NAV                                                      -->
    <!-- ============================================================ -->
    <div class="flex gap-1 bg-slate-100 p-1 rounded-xl mb-5 self-start">
        <button id="tab-requests" onclick="switchTab('requests')"
                class="tab-btn px-5 py-2 rounded-lg text-sm font-semibold transition <?php echo $activeTab === 'requests' ? 'bg-white text-brand-dark shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
            <i class="fa-solid fa-clipboard-list mr-1.5"></i> Service Requests
            <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700"><?php echo $pendingRequests; ?></span>
        </button>
        <button id="tab-maintenance" onclick="switchTab('maintenance')"
                class="tab-btn px-5 py-2 rounded-lg text-sm font-semibold transition <?php echo $activeTab === 'maintenance' ? 'bg-white text-brand-dark shadow-sm' : 'text-slate-500 hover:text-slate-700'; ?>">
            <i class="fa-solid fa-wrench mr-1.5"></i> Maintenance &amp; Desludging
            <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700"><?php echo $scheduledServices; ?></span>
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- TAB: SERVICE REQUESTS                                        -->
    <!-- ============================================================ -->
    <div id="panel-requests" class="tab-panel <?php echo $activeTab !== 'requests' ? 'hidden' : ''; ?>">

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
            <?php
            $srCards = [
                ['icon'=>'fa-clipboard-list','from'=>'from-blue-500','to'=>'to-blue-600','shadow'=>'shadow-blue-200','bg'=>'bg-blue-100','value'=>$totalRequests,    'label'=>'Total Requests',  'tag'=>'📋 All requests','sub'=>"Incl. {$cancelledReqs} cancelled"],
                ['icon'=>'fa-clock',         'from'=>'from-amber-500','to'=>'to-amber-600','shadow'=>'shadow-amber-200','bg'=>'bg-amber-100','value'=>$pendingRequests, 'label'=>'Pending',         'tag'=>'⏳ Awaiting',     'sub'=>'Needs attention'],
                ['icon'=>'fa-play',          'from'=>'from-blue-500','to'=>'to-blue-600','shadow'=>'shadow-blue-200','bg'=>'bg-blue-100','value'=>$inProgressReqs,  'label'=>'In Progress',     'tag'=>'🔄 Active',       'sub'=>'Being worked on'],
                ['icon'=>'fa-check-circle',  'from'=>'from-emerald-500','to'=>'to-emerald-600','shadow'=>'shadow-emerald-200','bg'=>'bg-emerald-100','value'=>$completedReqs, 'label'=>'Completed', 'tag'=>'✅ Done',        'sub'=>'Successfully finished'],
                ['icon'=>'fa-stopwatch',     'from'=>'from-violet-500','to'=>'to-violet-600','shadow'=>'shadow-violet-200','bg'=>'bg-violet-100','value'=>($avgCompletionDays !== null ? $avgCompletionDays.'d' : '—'), 'label'=>'Avg Completion','tag'=>'⏱ Speed','sub'=>($avgCompletionDays !== null ? ($avgCompletionDays<=3?'Fast response':'Track turnaround') : 'No data yet')],
            ];
            foreach ($srCards as $card): ?>
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 <?php echo $card['bg']; ?> rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br <?php echo $card['from'].' '.$card['to']; ?> rounded-xl flex items-center justify-center text-white shadow-lg <?php echo $card['shadow']; ?>">
                            <i class="fa-solid <?php echo $card['icon']; ?> text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900"><?php echo $card['value']; ?></p>
                            <p class="text-xs font-medium text-slate-500"><?php echo $card['label']; ?></p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 <?php echo $card['bg']; ?> text-slate-700 rounded-full text-[10px] font-bold"><?php echo $card['tag']; ?></span>
                        <span class="text-[10px] text-slate-400"><?php echo $card['sub']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="searchRequest" placeholder="Search by ID, owner, or tank..."
                           class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
                </div>
                <div class="flex gap-2 flex-wrap">
                    <select id="srFilterStatus" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="srFilterType" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">All Types</option>
                        <option value="desludging">Desludging</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inspection">Inspection</option>
                        <option value="installation">Installation</option>
                    </select>
                    <select id="srFilterPriority" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">All Priority</option>
                        <option value="urgent">Urgent</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                        <option value="low">Low</option>
                    </select>
                    <button onclick="resetSRFilters()" class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 transition text-sm" title="Reset">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Request ID</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tank / Owner</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Priority</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Preferred Date</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Assigned</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="requestTableBody">
                        <?php foreach ($serviceRequests as $request):
                            $srStatusColors = ['pending'=>'bg-amber-100 text-amber-700','in_progress'=>'bg-blue-100 text-blue-700','completed'=>'bg-emerald-100 text-emerald-700','cancelled'=>'bg-rose-100 text-rose-700'];
                            $srPriColors    = ['urgent'=>'bg-rose-100 text-rose-700','high'=>'bg-orange-100 text-orange-700','normal'=>'bg-slate-100 text-slate-600','low'=>'bg-teal-100 text-teal-700'];
                            $srTypeColors   = ['desludging'=>'bg-violet-100 text-violet-700','maintenance'=>'bg-blue-100 text-blue-700','inspection'=>'bg-emerald-100 text-emerald-700','installation'=>'bg-amber-100 text-amber-700'];
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition request-row"
                            data-id="<?php echo (int)$request['id']; ?>"
                            data-status="<?php echo htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-type="<?php echo htmlspecialchars($request['service_type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-priority="<?php echo htmlspecialchars($request['priority'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-owner="<?php echo htmlspecialchars(strtolower($request['owner_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-tank="<?php echo htmlspecialchars($request['tank_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            id="request-row-<?php echo (int)$request['id']; ?>">
                            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo htmlspecialchars($request['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($request['owner_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($request['tank_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $srTypeColors[$request['service_type'] ?? ''] ?? 'bg-slate-100 text-slate-600'; ?>">
                                    <?php echo ucfirst($request['service_type'] ?? ''); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $srPriColors[$request['priority'] ?? ''] ?? 'bg-slate-100 text-slate-600'; ?>">
                                    <?php echo ucfirst($request['priority'] ?? 'normal'); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">
                                <?php echo !empty($request['preferred_date']) ? date('M d, Y', strtotime($request['preferred_date'])) : '—'; ?>
                                <?php if (!empty($request['preferred_time'])): ?>
                                    <br><span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($request['preferred_time'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <?php if (!empty($request['assigned_to'])): ?>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-6 h-6 rounded-full bg-brand-light flex items-center justify-center text-brand-dark text-[10px] font-bold">
                                            <?php echo strtoupper(substr($request['assigned_to'], 0, 1)); ?>
                                        </div>
                                        <span class="text-xs text-slate-700"><?php echo htmlspecialchars($request['assigned_to'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-400 text-[10px] font-semibold">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $srStatusColors[$request['status']] ?? 'bg-slate-100 text-slate-600'; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($request['status'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <button onclick="updateSRStatus(<?php echo $request['id']; ?>, 'in_progress')"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Start">
                                            <i class="fa-solid fa-play text-sm"></i>
                                        </button>
                                        <button onclick="updateSRStatus(<?php echo $request['id']; ?>, 'cancelled')"
                                                class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Cancel">
                                            <i class="fa-solid fa-xmark text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($request['status'] === 'in_progress'): ?>
                                        <button onclick="completeSR(<?php echo $request['id']; ?>)"
                                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete">
                                            <i class="fa-solid fa-check text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (in_array($request['status'], ['completed', 'in_progress'])): ?>
                                        <a href="wastewater_billing.php?client=<?php echo urlencode($request['owner_name']); ?>&tank_id=<?php echo urlencode($request['tank_id']); ?>&service_type=<?php echo urlencode($request['service_type']); ?>&action=new_quote"
                                           class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Proceed to Billing">
                                            <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="editSR(<?php echo $request['id']; ?>)"
                                            class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg transition" title="Edit">
                                        <i class="fa-solid fa-pen text-sm"></i>
                                    </button>
                                    <button onclick="logMaintenance(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['tank_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($request['owner_name'], ENT_QUOTES); ?>')"
                                            class="p-1.5 text-violet-600 hover:bg-violet-50 rounded-lg transition" title="Log Maintenance Record">
                                        <i class="fa-solid fa-wrench text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Empty state -->
            <div id="srEmptyState" class="hidden flex-col items-center justify-center py-14 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-clipboard-list text-slate-400 text-xl"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">No requests match your filters</p>
                <p class="text-xs text-slate-400 mt-1">Try adjusting or clearing your filters</p>
                <button onclick="resetSRFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear filters</button>
            </div>
            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-slate-200 flex justify-between items-center bg-slate-50">
                <p class="text-xs text-slate-500" id="srPaginationInfo">
                    Showing <span id="srPageStart">0</span>–<span id="srPageEnd">0</span> of <span id="srPageTotal">0</span> requests
                </p>
                <div class="flex gap-1" id="srPaginationBtns"></div>
            </div>
        </div>
    </div><!-- /panel-requests -->

    <!-- ============================================================ -->
    <!-- TAB: MAINTENANCE & DESLUDGING                                -->
    <!-- ============================================================ -->
    <div id="panel-maintenance" class="tab-panel <?php echo $activeTab !== 'maintenance' ? 'hidden' : ''; ?>">

        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-5">
            <?php
            $mCards = [
                ['icon'=>'fa-tools',        'from'=>'from-blue-500',   'to'=>'to-blue-600',    'shadow'=>'shadow-blue-200',    'bg'=>'bg-blue-100',    'value'=>$totalServices,    'label'=>'Total Services','tag'=>'🔧 All services','sub'=>"Incl. {$completedServices} completed"],
                ['icon'=>'fa-calendar-check','from'=>'from-blue-500',   'to'=>'to-blue-600',    'shadow'=>'shadow-blue-200',    'bg'=>'bg-blue-100',    'value'=>$scheduledServices, 'label'=>'Scheduled',     'tag'=>'📅 Upcoming',     'sub'=>'Awaiting execution'],
                ['icon'=>'fa-spinner',       'from'=>'from-amber-500',  'to'=>'to-amber-600',   'shadow'=>'shadow-amber-200',   'bg'=>'bg-amber-100',   'value'=>$inProgressSvcs,   'label'=>'In Progress',   'tag'=>'🔄 Active',       'sub'=>'Being worked on'],
                ['icon'=>'fa-check-circle',  'from'=>'from-emerald-500','to'=>'to-emerald-600', 'shadow'=>'shadow-emerald-200', 'bg'=>'bg-emerald-100', 'value'=>$completedServices, 'label'=>'Completed',     'tag'=>'✅ Done',         'sub'=>'Successfully finished'],
                ['icon'=>'fa-peso-sign',     'from'=>'from-teal-500',   'to'=>'to-teal-600',    'shadow'=>'shadow-teal-200',    'bg'=>'bg-teal-100',    'value'=>'₱'.number_format($totalRevenue,0), 'label'=>'Total Revenue','tag'=>'💰 Collected','sub'=>"From {$completedServices} services"],
            ];
            foreach ($mCards as $card): ?>
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 <?php echo $card['bg']; ?> rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br <?php echo $card['from'].' '.$card['to']; ?> rounded-xl flex items-center justify-center text-white shadow-lg <?php echo $card['shadow']; ?>">
                            <i class="fa-solid <?php echo $card['icon']; ?> text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900"><?php echo $card['value']; ?></p>
                            <p class="text-xs font-medium text-slate-500"><?php echo $card['label']; ?></p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 <?php echo $card['bg']; ?> text-slate-700 rounded-full text-[10px] font-bold"><?php echo $card['tag']; ?></span>
                        <span class="text-[10px] text-slate-400"><?php echo $card['sub']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Technician Status Bar -->
        <?php if (!empty($technicians)): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-3 mb-4">
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">👷 Technician Status</h4>
            <div class="flex flex-wrap gap-4">
                <?php foreach ($technicians as $tech):
                    $dot = $tech['status'] === 'available' ? 'bg-emerald-500' : ($tech['status'] === 'on_site' ? 'bg-blue-500' : 'bg-amber-500');
                    $badge = $tech['status'] === 'available' ? 'bg-emerald-100 text-emerald-700' : ($tech['status'] === 'on_site' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700');
                ?>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full <?php echo $dot; ?>"></span>
                    <span class="text-xs font-medium text-slate-700"><?php echo htmlspecialchars($tech['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full <?php echo $badge; ?>">
                        <?php echo str_replace('_', ' ', ucfirst($tech['status'])); ?>
                        <?php if (!empty($tech['current_assignment'])): ?>(<?php echo htmlspecialchars($tech['current_assignment'], ENT_QUOTES, 'UTF-8'); ?>)<?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Search & Filter -->
        <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="searchMaintenance" placeholder="Search by service ID, tank ID, or owner..."
                           class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
                </div>
                <div class="flex gap-2 flex-wrap">
                    <select id="mFilterStatus" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                    </select>
                    <select id="mFilterType" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">All Types</option>
                        <option value="desludging">Desludging</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inspection">Inspection</option>
                        <option value="installation">Installation</option>
                    </select>
                    <select id="mFilterTechnician" class="px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">All Technicians</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo htmlspecialchars(strtolower($tech['name']), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tech['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button onclick="resetMFilters()" class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 transition text-sm" title="Reset">
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
                        <?php foreach ($maintenanceRecords as $record):
                            $mStatusColors = ['scheduled'=>'bg-blue-100 text-blue-700','in_progress'=>'bg-amber-100 text-amber-700','completed'=>'bg-emerald-100 text-emerald-700'];
                            $mTypeColors   = ['desludging'=>'bg-violet-100 text-violet-700','maintenance'=>'bg-blue-100 text-blue-700','inspection'=>'bg-emerald-100 text-emerald-700','installation'=>'bg-amber-100 text-amber-700'];
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition maintenance-row"
                            data-id="<?php echo (int)$record['id']; ?>"
                            data-status="<?php echo htmlspecialchars($record['status'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-type="<?php echo htmlspecialchars($record['service_type'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-technician="<?php echo htmlspecialchars(strtolower($record['technician'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-owner="<?php echo htmlspecialchars(strtolower($record['owner_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            data-tank="<?php echo htmlspecialchars($record['tank_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            id="maintenance-row-<?php echo (int)$record['id']; ?>">
                            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo htmlspecialchars($record['service_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($record['owner_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($record['tank_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $mTypeColors[$record['service_type']] ?? 'bg-slate-100 text-slate-600'; ?>">
                                    <?php echo ucfirst($record['service_type']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs"><?php echo htmlspecialchars($record['technician'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="px-4 py-3 text-slate-500 text-xs">
                                <?php echo !empty($record['scheduled_date']) ? date('M d, Y', strtotime($record['scheduled_date'])) : '—'; ?>
                                <?php if (!empty($record['scheduled_time'])): ?>
                                    <br><span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($record['scheduled_time'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $mStatusColors[$record['status']] ?? 'bg-slate-100 text-slate-600'; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($record['status'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-slate-700">₱<?php echo number_format($record['cost'] ?? 0, 2); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
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
                                    <?php if (in_array($record['status'], ['in_progress', 'completed'])): ?>
                                        <a href="wastewater_billing.php?client=<?php echo urlencode($record['owner_name']); ?>&tank_id=<?php echo urlencode($record['tank_id']); ?>&service_type=<?php echo urlencode($record['service_type']); ?>&action=new_quote"
                                           class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Proceed to Billing">
                                            <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button onclick="editService(<?php echo $record['id']; ?>)"
                                            class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg transition" title="Edit">
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
            <div id="mEmptyState" class="hidden flex-col items-center justify-center py-14 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-tools text-slate-400 text-xl"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">No services match your filters</p>
                <button onclick="resetMFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear filters</button>
            </div>
            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-slate-200 flex justify-between items-center bg-slate-50">
                <p class="text-xs text-slate-500" id="mPaginationInfo">
                    Showing <span id="mPageStart">0</span>–<span id="mPageEnd">0</span> of <span id="mPageTotal">0</span> services
                </p>
                <div class="flex gap-1" id="mPaginationBtns"></div>
            </div>
        </div>
    </div><!-- /panel-maintenance -->

</div><!-- /flex-1 -->

<!-- ============================================================ -->
<!-- NEW REQUEST MODAL (reused from service_requests.php)         -->
<!-- ============================================================ -->
<div id="newRequestModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-medium"></i> New Service Request
            </h3>
            <button onclick="closeModal('newRequestModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="newRequestForm" class="p-6 space-y-4" onsubmit="saveNewRequest(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID <span class="text-rose-500">*</span></label>
                    <select id="new_tank_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">Select Tank</option>
                        <?php foreach ($allSepticTanks as $st): ?>
                            <option value="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-owner="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="new_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none" placeholder="Auto-filled from tank">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type <span class="text-rose-500">*</span></label>
                    <select id="new_service_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">Select Type</option>
                        <option value="desludging">Desludging</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inspection">Inspection</option>
                        <option value="installation">Installation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority</label>
                    <select id="new_priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="normal">Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Date</label>
                    <input type="date" id="new_preferred_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Time</label>
                    <input type="time" id="new_preferred_time" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="new_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none" placeholder="Additional notes..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('newRequestModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- SCHEDULE MAINTENANCE MODAL                                   -->
<!-- ============================================================ -->
<div id="scheduleServiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-brand-medium"></i> Schedule Service
            </h3>
            <button onclick="closeModal('scheduleServiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="scheduleServiceForm" class="p-6 space-y-4" onsubmit="saveScheduleService(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="sched_linked_sr_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID <span class="text-rose-500">*</span></label>
                    <select id="sched_tank_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">Select Tank</option>
                        <?php foreach ($allSepticTanks as $st): ?>
                            <option value="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-owner="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner</label>
                    <input type="text" id="sched_owner" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none" placeholder="Auto-filled">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type <span class="text-rose-500">*</span></label>
                    <select id="sched_service_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">Select Type</option>
                        <option value="desludging">Desludging</option>
                        <option value="maintenance">Maintenance</option>
                        <option value="inspection">Inspection</option>
                        <option value="installation">Installation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Technician</label>
                    <select id="sched_technician" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="">Assign Technician</option>
                        <?php foreach ($technicians as $tech): ?>
                            <option value="<?php echo htmlspecialchars($tech['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo ($tech['status'] !== 'available') ? 'class="text-slate-400"' : ''; ?>>
                                <?php echo htmlspecialchars($tech['name'], ENT_QUOTES, 'UTF-8'); ?>
                                (<?php echo str_replace('_', ' ', $tech['status']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Scheduled Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="sched_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Scheduled Time</label>
                    <input type="time" id="sched_time" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Cost (₱)</label>
                    <input type="number" id="sched_cost" min="0" step="0.01" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none" placeholder="0.00">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="sched_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none" placeholder="Service notes..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('scheduleServiceModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-calendar-plus mr-1.5"></i> Schedule Service
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white items-center gap-2"></div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
// ── Injected PHP data ─────────────────────────────────────────
const REQUESTS    = <?php echo json_encode(array_column($serviceRequests, null, 'id'), JSON_UNESCAPED_UNICODE); ?>;
const MAINTENANCE = <?php echo json_encode(array_column($maintenanceRecords, null, 'id'), JSON_UNESCAPED_UNICODE); ?>;
const TANKS_GEO   = <?php echo json_encode($tankLookup, JSON_UNESCAPED_UNICODE); ?>;

// ── Page state ────────────────────────────────────────────────
const PAGE_SIZE = 25;
let srPage = 1, mPage = 1;
const csrfToken = document.querySelector('[name="csrf_token"]')?.value ?? '';

// ── Helpers ───────────────────────────────────────────────────
function openModal(id)  { const m = document.getElementById(id); if(m){ m.classList.remove('hidden'); m.classList.add('flex'); } }
function closeModal(id) { const m = document.getElementById(id); if(m){ m.classList.add('hidden'); m.classList.remove('flex'); } }

function showToast(msg, type='success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.className = `fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ${type==='success'?'bg-emerald-500':'bg-rose-500'}`;
    setTimeout(() => { t.className = t.className.replace(' flex ',' hidden '); }, 3500);
}

async function postAPI(url, payload) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ ...payload, csrf_token: csrfToken })
    });
    return res.json();
}

// ── Tab switching ─────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    document.getElementById('panel-' + tab).classList.remove('hidden');
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('bg-white','text-brand-dark','shadow-sm');
        b.classList.add('text-slate-500');
    });
    const active = document.getElementById('tab-' + tab);
    active.classList.remove('text-slate-500');
    active.classList.add('bg-white','text-brand-dark','shadow-sm');
    // Update header button
    if (tab === 'requests') {
        document.getElementById('primaryActionLabel').textContent = 'New Request';
        document.getElementById('primaryActionBtn').onclick = () => openModal('newRequestModal');
        document.getElementById('exportBtn').onclick = () => exportTableToCSV('#requestTableBody', 'service_requests');
    } else {
        document.getElementById('primaryActionLabel').textContent = 'Schedule Service';
        document.getElementById('primaryActionBtn').onclick = () => openModal('scheduleServiceModal');
        document.getElementById('exportBtn').onclick = () => exportTableToCSV('#maintenanceTableBody', 'maintenance_records');
    }
    history.replaceState(null, '', '?tab=' + tab);
}

// ── Pagination helper ─────────────────────────────────────────
function applyPagination(rows, page, infoStartId, infoEndId, infoTotalId, btnsId, emptyId, onPageChange) {
    const total = rows.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    page = Math.min(page, totalPages);
    const start = (page - 1) * PAGE_SIZE;
    const end   = Math.min(start + PAGE_SIZE, total);
    rows.forEach((r, i) => r.style.display = (i >= start && i < end) ? '' : 'none');
    document.getElementById(infoStartId).textContent  = total === 0 ? 0 : start + 1;
    document.getElementById(infoEndId).textContent    = end;
    document.getElementById(infoTotalId).textContent  = total;
    document.getElementById(emptyId).classList.toggle('hidden', total > 0);
    document.getElementById(emptyId).classList.toggle('flex', total === 0);
    // Render page buttons
    const btns = document.getElementById(btnsId);
    btns.innerHTML = '';
    const makeBtn = (label, p, active) => {
        const b = document.createElement('button');
        b.innerHTML = label;
        b.className = `px-3 py-1.5 rounded-lg text-sm font-medium transition ${active ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}`;
        if (!active) b.onclick = () => onPageChange(p);
        btns.appendChild(b);
    };
    if (page > 1) makeBtn('<i class="fa-solid fa-chevron-left text-xs"></i>', page - 1, false);
    for (let i = Math.max(1, page-1); i <= Math.min(totalPages, page+1); i++) makeBtn(i, i, i === page);
    if (page < totalPages) makeBtn('<i class="fa-solid fa-chevron-right text-xs"></i>', page + 1, false);
}

// ── Service Request filtering ─────────────────────────────────
function filterRequests() {
    const q  = document.getElementById('searchRequest').value.toLowerCase();
    const st = document.getElementById('srFilterStatus').value;
    const ty = document.getElementById('srFilterType').value;
    const pr = document.getElementById('srFilterPriority').value;
    const rows = [...document.querySelectorAll('#requestTableBody .request-row')].filter(r => {
        r.style.display = '';
        const ok = (!q  || r.dataset.owner.includes(q) || r.dataset.tank.toLowerCase().includes(q))
                && (!st || r.dataset.status === st)
                && (!ty || r.dataset.type   === ty)
                && (!pr || r.dataset.priority === pr);
        return ok;
    });
    srPage = 1;
    applyPagination(rows, srPage, 'srPageStart','srPageEnd','srPageTotal','srPaginationBtns','srEmptyState', p => { srPage = p; filterRequests(); });
}
function resetSRFilters() {
    ['searchRequest','srFilterStatus','srFilterType','srFilterPriority'].forEach(id => {
        const el = document.getElementById(id); if(el) el.value = '';
    });
    filterRequests();
}

// ── Maintenance filtering ─────────────────────────────────────
function filterMaintenance() {
    const q  = document.getElementById('searchMaintenance').value.toLowerCase();
    const st = document.getElementById('mFilterStatus').value;
    const ty = document.getElementById('mFilterType').value;
    const tc = document.getElementById('mFilterTechnician').value;
    const rows = [...document.querySelectorAll('#maintenanceTableBody .maintenance-row')].filter(r => {
        r.style.display = '';
        return (!q  || r.dataset.owner.includes(q) || r.dataset.tank.toLowerCase().includes(q))
            && (!st || r.dataset.status === st)
            && (!ty || r.dataset.type === ty)
            && (!tc || r.dataset.technician.includes(tc));
    });
    mPage = 1;
    applyPagination(rows, mPage, 'mPageStart','mPageEnd','mPageTotal','mPaginationBtns','mEmptyState', p => { mPage = p; filterMaintenance(); });
}
function resetMFilters() {
    ['searchMaintenance','mFilterStatus','mFilterType','mFilterTechnician'].forEach(id => {
        const el = document.getElementById(id); if(el) el.value = '';
    });
    filterMaintenance();
}

// ── Auto-fill owner from tank select ─────────────────────────
document.getElementById('new_tank_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const owner = document.getElementById('new_owner');
    if (owner) owner.value = opt.dataset.owner || '';
});
document.getElementById('sched_tank_id')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const owner = document.getElementById('sched_owner');
    if (owner) owner.value = opt.dataset.owner || '';
});

// ── Service Request actions ───────────────────────────────────
async function updateSRStatus(id, status) {
    try {
        const j = await postAPI(`../../api/service_requests.php?id=${id}&action=update`, { status });
        if (j.success) { showToast('Status updated.'); setTimeout(() => location.reload(), 1000); }
        else showToast(j.message || 'Error', 'error');
    } catch(e) { showToast('Request failed', 'error'); }
}
async function completeSR(id) {
    if (!confirm('Mark this request as completed?')) return;
    await updateSRStatus(id, 'completed');
}
function editSR(id) { showToast('Edit functionality — coming soon', 'success'); }

function saveNewRequest(event) {
    event.preventDefault();
    showToast('Request submitted — reloading...', 'success');
    setTimeout(() => location.reload(), 1200);
}

// ── Log Maintenance from a Service Request ────────────────────
function logMaintenance(srId, tankId, ownerName) {
    // Switch to Maintenance tab and pre-fill the schedule modal
    switchTab('maintenance');
    const tankSel = document.getElementById('sched_tank_id');
    if (tankSel) { [...tankSel.options].forEach(o => { if (o.value === tankId) tankSel.value = tankId; }); }
    const ownerEl = document.getElementById('sched_owner');
    if (ownerEl) ownerEl.value = ownerName;
    const linkEl = document.getElementById('sched_linked_sr_id');
    if (linkEl) linkEl.value = srId;
    openModal('scheduleServiceModal');
}

// ── Maintenance actions ───────────────────────────────────────
async function startService(id) {
    try {
        const j = await postAPI(`../../api/service_requests.php?id=${id}&action=update`, { status: 'in_progress' });
        if (j.success) { showToast('Service started.'); setTimeout(() => location.reload(), 1000); }
        else showToast(j.message || 'Error', 'error');
    } catch(e) { showToast('Request failed', 'error'); }
}
async function completeService(id) {
    if (!confirm('Mark this service as completed?')) return;
    try {
        const j = await postAPI(`../../api/service_requests.php?id=${id}&action=update`, { status: 'completed' });
        if (j.success) { showToast('Service completed.'); setTimeout(() => location.reload(), 1000); }
        else showToast(j.message || 'Error', 'error');
    } catch(e) { showToast('Request failed', 'error'); }
}
function editService(id) { showToast('Edit functionality — coming soon', 'success'); }

function saveScheduleService(event) {
    event.preventDefault();
    showToast('Service scheduled — reloading...', 'success');
    setTimeout(() => location.reload(), 1200);
}

// ── Export CSV ────────────────────────────────────────────────
function exportTableToCSV(tableSelector, filename) {
    const table = document.querySelector(tableSelector);
    if (!table) return;
    const rows  = [...table.querySelectorAll('tr')];
    const csv   = rows.map(r => [...r.querySelectorAll('td,th')].map(c => `"${c.innerText.replace(/"/g,'""')}"`).join(',')).join('\n');
    const a = document.createElement('a');
    a.href = 'data:text/csv,' + encodeURIComponent(csv);
    a.download = filename + '_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
}

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Set initial tab state (from URL param preserved in PHP)
    const initTab = '<?php echo $activeTab; ?>';
    switchTab(initTab);
    filterRequests();
    filterMaintenance();
    // Wire filter inputs
    ['searchRequest','srFilterStatus','srFilterType','srFilterPriority'].forEach(id =>
        document.getElementById(id)?.addEventListener('input', filterRequests)
    );
    ['searchMaintenance','mFilterStatus','mFilterType','mFilterTechnician'].forEach(id =>
        document.getElementById(id)?.addEventListener('input', filterMaintenance)
    );
});
</script>

<?php include_once '../../includes/footer.php'; ?>
