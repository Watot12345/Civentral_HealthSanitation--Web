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
    echo json_encode(['success' => true, 'action' => $action, 'message' => 'Service request action processed successfully.']);
    exit;
}

require_once __DIR__ . '/../../app/Models/ServiceRequest.php';
require_once __DIR__ . '/../../app/Models/SepticTank.php';
$requestModel = new ServiceRequest();
$septicTankModel = new SepticTank();

// Fetch live Service Requests from Supabase
$serviceRequests = $requestModel->all();

// Fetch Septic Tanks and de-duplicate by tank_id for scalable, accurate selection
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
            'barangay' => $st['barangay'] ?? '',
            'owner' => $st['owner_name'] ?? ''
        ];
    }
}

// Stats
$counts = $requestModel->countByStatus();
$totalRequests = count($serviceRequests);
$pendingRequests = $counts['pending'];
$inProgressRequests = $counts['in_progress'];
$completedRequests = $counts['completed'];
$cancelledRequests = $counts['cancelled'];
$avgRating = array_sum(array_filter(array_column($serviceRequests, 'rating'))) / max(1, count(array_filter($serviceRequests, fn($r) => $r['rating'])));

$title = 'Service Requests';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Service Requests</h2>
            <p class="text-sm text-slate-500 mt-0.5">Submit, track, and manage service requests</p>
        </div>
        <div class="flex gap-3">
            <button onclick="exportTableToCSV('#requestTableBody', 'service_requests')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2"
                    title="Export to CSV" aria-label="Export service requests to CSV">
                <i class="fa-solid fa-file-csv text-xs"></i> Export
            </button>
            <button onclick="openModal('newRequestModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> New Request
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Requests -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-clipboard-list text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalRequests; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Requests</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All requests</span>
                    <span class="text-[10px] text-slate-400">Including <?php echo $cancelledRequests; ?> cancelled</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Pending -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $pendingRequests; ?></p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400">Needs attention</span>
                </div>
            </div>
        </div>

        <!-- Card 3: In Progress -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-play text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-blue-600"><?php echo $inProgressRequests; ?></p>
                        <p class="text-xs font-medium text-slate-500">In Progress</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🔄 Active</span>
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
                        <p class="text-2xl font-black text-emerald-600"><?php echo $completedRequests; ?></p>
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Done</span>
                    <span class="text-[10px] text-slate-400">Successfully finished</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Avg Rating -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-star text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo number_format($avgRating, 1); ?></p>
                        <p class="text-xs font-medium text-slate-500">Avg Rating</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⭐ Quality</span>
                    <span class="text-[10px] text-slate-400"><?php echo $avgRating >= 4 ? 'Excellent' : 'Good'; ?></span>
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
                       id="searchRequest"
                       placeholder="Search by request ID, owner, or technician..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select id="filterType" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="desludging">Desludging</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="installation">Installation</option>
                </select>
                <select id="filterPriority" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Preferred date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Preferred date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
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
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Technician</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Priority</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="requestTableBody">
                    <?php foreach ($serviceRequests as $request): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors request-row <?php echo $request['status'] === 'pending' ? 'bg-amber-50/30' : ''; ?>"
                        data-request-id="<?php echo htmlspecialchars(strtolower($request['request_id']), ENT_QUOTES, 'UTF-8'); ?>"
                        data-owner="<?php echo htmlspecialchars(strtolower($request['owner_name']), ENT_QUOTES, 'UTF-8'); ?>"
                        data-tank="<?php echo htmlspecialchars($request['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-technician="<?php echo htmlspecialchars(strtolower($request['assigned_to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-status="<?php echo htmlspecialchars($request['status'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-type="<?php echo htmlspecialchars($request['service_type'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-priority="<?php echo htmlspecialchars($request['priority'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-preferred-date="<?php echo htmlspecialchars($request['preferred_date'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-row-id="<?php echo (int)$request['id']; ?>"
                        data-id="<?php echo (int)$request['id']; ?>"
                        id="request-row-<?php echo (int)$request['id']; ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo $request['request_id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $request['owner_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $request['tank_id']; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $request['service_type'] === 'desludging' ? 'bg-violet-100 text-violet-700' : ($request['service_type'] === 'maintenance' ? 'bg-blue-100 text-blue-700' : ($request['service_type'] === 'inspection' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700')); ?>">
                                <?php echo ucfirst($request['service_type']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $request['assigned_to'] ?? 'Unassigned'; ?></td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'in_progress' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'cancelled' => 'bg-slate-100 text-slate-500'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$request['status']] ?? $statusColors['pending']; ?>">
                                <?php echo str_replace('_', ' ', ucfirst($request['status'])); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $priorityColors = [
                                    'low' => 'bg-slate-100 text-slate-500',
                                    'medium' => 'bg-amber-100 text-amber-700',
                                    'high' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $priorityColors[$request['priority']] ?? $priorityColors['medium']; ?>">
                                <?php echo ucfirst($request['priority']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewRequest(<?php echo $request['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="viewRequestRoute(<?php echo $request['id']; ?>)"
                                        class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Route / Location Map">
                                    <i class="fa-solid fa-route text-sm"></i>
                                </button>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <button onclick="updateRequestStatus(<?php echo $request['id']; ?>, 'in_progress')"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Start">
                                        <i class="fa-solid fa-play text-sm"></i>
                                    </button>
                                    <button onclick="updateRequestStatus(<?php echo $request['id']; ?>, 'cancelled')"
                                            class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Cancel">
                                        <i class="fa-solid fa-xmark text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($request['status'] === 'in_progress'): ?>
                                    <button onclick="completeRequest(<?php echo $request['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete">
                                        <i class="fa-solid fa-check text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($request['status'] === 'completed' && !$request['feedback']): ?>
                                    <button onclick="addFeedback(<?php echo $request['id']; ?>)"
                                            class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition" title="Feedback">
                                        <i class="fa-solid fa-comment text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <a href="wastewater_billing.php?client=<?php echo urlencode($request['owner_name']); ?>&tank_id=<?php echo urlencode($request['tank_id']); ?>&service_type=<?php echo urlencode($request['service_type']); ?>&action=new_quote"
                                   class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Proceed to Wastewater Billing">
                                    <i class="fa-solid fa-file-invoice-dollar text-sm"></i>
                                </a>
                                <button onclick="editRequest(<?php echo $request['id']; ?>)"
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
                <i class="fa-solid fa-clipboard-list text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No requests match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo $totalRequests; ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalRequests; ?></span> requests
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
<!-- NEW REQUEST MODAL                                            -->
<!-- ============================================================ -->
<div id="newRequestModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-medium"></i>
                New Service Request
            </h3>
            <button onclick="closeModal('newRequestModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="newRequestForm" class="p-6 space-y-4" onsubmit="saveNewRequest(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            
            <!-- Quick Live Filter / Search Bar for Scalable Tank Selection -->
            <div class="p-3 bg-brand-light/30 rounded-xl border border-brand-border">
                <div class="flex items-center gap-2 mb-1.5">
                    <i class="fa-solid fa-magnifying-glass text-brand-medium text-xs"></i>
                    <label for="reqTankQuickSearch" class="text-xs font-bold text-slate-700">Quick Tank or Owner Search</label>
                    <span class="text-[10px] text-slate-400 ml-auto">Filters dropdowns instantly</span>
                </div>
                <input type="text" id="reqTankQuickSearch" oninput="filterRequestTankDropdowns(this.value, 'req_tank', 'req_owner')" placeholder="Type Tank ID, Owner name, or Address..." class="w-full px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <!-- Real Septic Tank Data Linked Dropdowns -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Septic Tank <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="req_tank" required onchange="onRequestTankChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Septic Tank</option>
                            <?php foreach ($allSepticTanks as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-owner="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-address="<?php echo htmlspecialchars($st['address'] . (isset($st['barangay']) ? ', ' . $st['barangay'] : ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-barangay="<?php echo htmlspecialchars($st['barangay'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                        <select id="req_owner" required onchange="onRequestOwnerChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Registered Owner</option>
                            <?php foreach ($allSepticTanks as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-tank="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-address="<?php echo htmlspecialchars($st['address'] . (isset($st['barangay']) ? ', ' . $st['barangay'] : ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        data-barangay="<?php echo htmlspecialchars($st['barangay'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        data-type="<?php echo htmlspecialchars($st['type'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
            </div>

            <!-- Duplicate Request Warning Alert -->
            <div id="request_duplicate_warning" class="hidden p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i>
                <div id="request_duplicate_warning_text">This tank already has an active service request.</div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="req_address" readonly class="w-full px-3 py-2 border border-slate-200 bg-slate-50 text-slate-600 rounded-lg text-sm outline-none" placeholder="Auto-populated from selected tank">
                <input type="hidden" id="req_barangay">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label>
                <select id="req_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="desludging">Desludging</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="installation">Installation</option>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="req_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Time <span class="text-rose-500">*</span></label>
                    <input type="time" id="req_time" value="09:00" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority</label>
                <select id="req_priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="req_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional details..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('newRequestModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT REQUEST MODAL                                           -->
<!-- ============================================================ -->
<div id="editRequestModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Service Request</h3>
            <button type="button" onclick="closeModal('editRequestModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveRequestEdit(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="edit_request_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="edit_request_tank" required onchange="onEditRequestTankChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Tank ID</option>
                            <?php foreach ($allSepticTanks as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-owner="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
                <div class="relative">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <select id="edit_request_owner" required onchange="onEditRequestOwnerChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none appearance-none pr-8">
                            <option value="">Select Registered Owner</option>
                            <?php foreach ($allSepticTanks as $st): ?>
                                <option value="<?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        data-tank="<?php echo htmlspecialchars($st['tank_id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($st['owner_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-xs"></i>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label><select id="edit_request_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="desludging">Desludging</option><option value="maintenance">Maintenance</option><option value="inspection">Inspection</option><option value="installation">Installation</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Date</label><input type="date" id="edit_request_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Time</label><input type="time" id="edit_request_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority</label><select id="edit_request_priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_request_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_request_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editRequestModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- SERVICE REQUEST ROUTE / LOCATION MAP MODAL                   -->
<!-- ============================================================ -->
<div id="requestRouteModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-route text-brand-medium"></i>
                Request Route & Location Map
            </h3>
            <button onclick="closeModal('requestRouteModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-3 flex justify-between items-center text-xs text-slate-500">
                <span id="reqRouteMapTitle" class="font-semibold text-slate-700">Request Location</span>
                <span id="reqRouteMapCoordinates" class="font-mono text-slate-400">Lat: 0, Lng: 0</span>
            </div>
            <div id="leafletRequestMap" class="w-full h-80 rounded-xl border border-slate-200 shadow-inner z-10"></div>
            <div id="reqRouteServiceInfo" class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 flex justify-between items-center">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>
</div>

<div id="viewRequestModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Request Details</h3>
            <button onclick="closeModal('viewRequestModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="requestDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- STATUS UPDATE MODAL                                          -->
<!-- ============================================================ -->
<div id="statusUpdateModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clock text-brand-medium"></i>
                Update Status
            </h3>
            <button onclick="closeModal('statusUpdateModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="statusUpdateForm" class="p-6 space-y-4" onsubmit="saveStatusUpdate(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="update_request_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">New Status</label>
                <select id="update_status" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Update Notes</label>
                <textarea id="update_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Status update details..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('statusUpdateModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- FEEDBACK MODAL                                               -->
<!-- ============================================================ -->
<div id="feedbackModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-comment text-brand-medium"></i>
                Customer Feedback
            </h3>
            <button onclick="closeModal('feedbackModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="feedbackForm" class="p-6 space-y-4" onsubmit="saveFeedback(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="feedback_request_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Rating</label>
                <div class="flex gap-2" id="ratingStars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" onclick="setRating(<?php echo $i; ?>)" 
                            class="rating-star text-2xl text-slate-300 hover:text-amber-400 transition" data-rating="<?php echo $i; ?>">
                        ☆
                    </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="feedback_rating" value="0">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Feedback</label>
                <textarea id="feedback_text" rows="3" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Share your experience..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('feedbackModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Submit Feedback
                </button>
            </div>
        </form>
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
<style>
    .rating-star.active {
        color: #f59e0b;
    }
    .rating-star.active ~ .rating-star {
        color: #d1d5db;
    }
</style>

<!-- Leaflet CSS & JS for Interactive Map -->
<link rel="stylesheet" href="<?= site_url('assets/css/leaflet.css'); ?>" />
<script src="<?= site_url('assets/js/leaflet.js'); ?>"></script>
<script src="<?= site_url('assets/js/common.js'); ?>"></script>
<script>
    const REQUESTS = <?php echo json_encode(array_column($serviceRequests, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;
    const TANKS_GEO = <?php echo json_encode($tankLookup, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

    // Modal functions, toast, sanitizeHTML provided by common.js

    // ============================================================
    // VIEW REQUEST
    // ============================================================
    function viewRequest(id) {
        openModal('viewRequestModal');
        const r = REQUESTS[id];
        if (!r) return;

        setTimeout(() => {
            const statusColors = {
                pending: 'bg-amber-100 text-amber-700',
                in_progress: 'bg-blue-100 text-blue-700',
                completed: 'bg-emerald-100 text-emerald-700',
                cancelled: 'bg-slate-100 text-slate-500'
            };
            const priorityColors = {
                low: 'bg-slate-100 text-slate-500',
                medium: 'bg-amber-100 text-amber-700',
                high: 'bg-rose-100 text-rose-700'
            };

            // Use sanitizeHTML() to prevent XSS
            const rOwner = sanitizeHTML(r.owner_name);
            const rReqId = sanitizeHTML(r.request_id);
            const rTankId = sanitizeHTML(r.tank_id);
            const rType = sanitizeHTML(r.service_type);
            const rAddress = sanitizeHTML(r.address);
            const rAssigned = sanitizeHTML(r.assigned_to || 'Unassigned');
            const rNotes = sanitizeHTML(r.notes);
            const rFeedback = sanitizeHTML(r.feedback);
            const rStatus = sanitizeHTML(r.status);
            const rPriority = sanitizeHTML(r.priority);
            const rTimeStr = sanitizeHTML(r.preferred_time);

            document.getElementById('requestDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${rOwner.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${rOwner}</h4>
                            <p class="text-sm text-slate-500">${rReqId} &bull; ${rTankId}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[r.status] || statusColors.pending}">
                                ${rStatus.replace('_', ' ').toUpperCase()}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ml-1 ${priorityColors[r.priority] || priorityColors.medium}">
                                ${rPriority.toUpperCase()} PRIORITY
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Service Type</p><p class="text-sm text-slate-800 capitalize">${rType}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Address</p><p class="text-sm text-slate-800">${rAddress}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Preferred Date</p><p class="text-sm text-slate-800">${new Date(r.preferred_date).toLocaleDateString()} at ${rTimeStr}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Assigned To</p><p class="text-sm text-slate-800">${rAssigned}</p></div>
                        ${r.completed_at ? `<div><p class="text-xs text-slate-400 font-semibold">Completed</p><p class="text-sm text-slate-800">${new Date(r.completed_at).toLocaleDateString()}</p></div>` : ''}
                        ${r.rating ? `<div><p class="text-xs text-slate-400 font-semibold">Rating</p><p class="text-sm text-amber-500">${'⭐'.repeat(r.rating)}</p></div>` : ''}
                    </div>
                    ${rNotes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${rNotes}</p></div>` : ''}
                    ${rFeedback ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">💬 Feedback</h5><p class="text-sm text-slate-800">${rFeedback}</p></div>` : ''}
                    <div class="flex flex-wrap justify-end items-center gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewRequestModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <a href="wastewater_billing.php?client=${encodeURIComponent(r.owner_name)}&tank_id=${encodeURIComponent(r.tank_id)}&service_type=${encodeURIComponent(r.service_type)}&action=new_quote"
                           class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-file-invoice-dollar text-sm"></i> Proceed to Billing
                        </a>
                        ${r.status === 'pending' ? `<button onclick="closeModal('viewRequestModal'); updateRequestStatus(${r.id}, 'in_progress')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold"><i class="fa-solid fa-play mr-1.5"></i> Start</button>` : ''}
                        ${r.status === 'in_progress' ? `<button onclick="closeModal('viewRequestModal'); completeRequest(${r.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Complete</button>` : ''}
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // UPDATE REQUEST STATUS
    // ============================================================
    function updateRequestStatus(id, status) {
        const r = REQUESTS[id];
        if (!r) return;
        
        document.getElementById('update_request_id').value = id;
        document.getElementById('update_status').value = status;
        document.getElementById('update_notes').value = '';
        
        openModal('statusUpdateModal');
    }

    async function saveStatusUpdate(event) {
        event.preventDefault();
        try {
            const id = document.getElementById('update_request_id').value;
            const r = REQUESTS[id];
            if (!r) { showToast('Request record not found.', 'danger'); return; }
            
            r.status = document.getElementById('update_status').value;
            const notes = document.getElementById('update_notes').value.trim();
            if (notes) {
                r.notes = r.notes ? r.notes + '\n' + notes : notes;
            }
            if (r.status === 'completed') {
                r.completed_at = new Date().toISOString();
            }
            
            updateRequestRow(r);
            await fetch(`../../api/service_requests.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    status: r.status,
                    notes: r.notes,
                    completed_at: r.completed_at || null
                })
            });
            closeModal('statusUpdateModal');
            if (r.status === 'completed') {
                showToast(`Request marked completed! Redirecting to Wastewater Billing for ${r.owner_name}...`, 'success');
                setTimeout(() => {
                    window.location.href = `wastewater_billing.php?client=${encodeURIComponent(r.owner_name)}&tank_id=${encodeURIComponent(r.tank_id)}&service_type=${encodeURIComponent(r.service_type)}&action=new_quote`;
                }, 600);
            } else {
                showToast('Request #' + r.request_id + ' updated to ' + r.status.replace('_', ' '), 'success');
            }
        } catch (err) {
            console.error('saveStatusUpdate error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // COMPLETE REQUEST
    // ============================================================
    function completeRequest(id) {
        updateRequestStatus(id, 'completed');
    }

    // ============================================================
    // UPDATE REQUEST ROW
    // ============================================================
    function updateRequestRow(r) {
        const row = document.getElementById('request-row-' + r.id);
        if (!row) return;

        // Update status badge
        const statusColors = {
            pending:     'bg-amber-100 text-amber-700',
            in_progress: 'bg-blue-100 text-blue-700',
            completed:   'bg-emerald-100 text-emerald-700',
            cancelled:   'bg-slate-100 text-slate-500'
        };
        const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
        if (statusBadge) {
            statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[r.status] || statusColors.pending}`;
            statusBadge.textContent = r.status.replace('_', ' ').toUpperCase();
        }

        // Update dataset for filters
        row.dataset.status   = r.status;
        row.dataset.priority = r.priority || row.dataset.priority;

        // Rebuild action buttons based on new status
        const tds = row.querySelectorAll('td');
        const actionsTd = tds[tds.length - 1];
        if (actionsTd) {
            const isPending    = r.status === 'pending';
            const isInProgress = r.status === 'in_progress';
            const isCompleted  = r.status === 'completed';
            const needsFeedback = isCompleted && !r.feedback;

            actionsTd.innerHTML = `
                <div class="flex items-center justify-center gap-1">
                    <button onclick="viewRequest(${r.id})"
                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>
                    <button onclick="viewRequestRoute(${r.id})"
                            class="p-1.5 text-brand-dark hover:bg-brand-light rounded-lg transition" title="Route / Location Map">
                        <i class="fa-solid fa-route text-sm"></i>
                    </button>
                    ${isPending ? `
                        <button onclick="updateRequestStatus(${r.id}, 'in_progress')"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Start">
                            <i class="fa-solid fa-play text-sm"></i>
                        </button>
                        <button onclick="updateRequestStatus(${r.id}, 'cancelled')"
                                class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Cancel">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    ` : ''}
                    ${isInProgress ? `
                        <button onclick="completeRequest(${r.id})"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete">
                            <i class="fa-solid fa-check text-sm"></i>
                        </button>
                    ` : ''}
                    ${needsFeedback ? `
                        <button onclick="addFeedback(${r.id})"
                                class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition" title="Feedback">
                            <i class="fa-solid fa-comment text-sm"></i>
                        </button>
                    ` : ''}
                    <button onclick="editRequest(${r.id})"
                            class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                        <i class="fa-solid fa-pen text-sm"></i>
                    </button>
                </div>
            `;
        }
    }

    // ============================================================
    // FEEDBACK
    // ============================================================
    let selectedRating = 0;

    function setRating(rating) {
        selectedRating = rating;
        document.getElementById('feedback_rating').value = rating;
        document.querySelectorAll('.rating-star').forEach(star => {
            const starRating = parseInt(star.dataset.rating);
            star.classList.toggle('active', starRating <= rating);
            star.textContent = starRating <= rating ? '★' : '☆';
        });
    }

    function addFeedback(id) {
        const r = REQUESTS[id];
        if (!r) return;
        
        document.getElementById('feedback_request_id').value = id;
        document.getElementById('feedback_text').value = '';
        selectedRating = 0;
        document.getElementById('feedback_rating').value = 0;
        document.querySelectorAll('.rating-star').forEach(star => {
            star.classList.remove('active');
            star.textContent = '☆';
        });
        
        openModal('feedbackModal');
    }

    async function saveFeedback(event) {
        event.preventDefault();
        try {
            const id = document.getElementById('feedback_request_id').value;
            const r = REQUESTS[id];
            if (!r) { showToast('Request record not found.', 'danger'); return; }
            
            r.rating = parseInt(document.getElementById('feedback_rating').value);
            r.feedback = document.getElementById('feedback_text').value.trim();
            
            await sendAjaxRequest('save_feedback', { id: id, rating: r.rating, feedback: r.feedback });
            closeModal('feedbackModal');
            showToast('Thank you for your feedback!', 'success');
        } catch (err) {
            console.error('saveFeedback error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // SERVICE REQUEST ROUTE / LOCATION MAP
    // ============================================================
    let _reqRouteMapInstance = null;
    let _reqRouteMarker = null;

    function viewRequestRoute(id) {
        const r = REQUESTS[id];
        if (!r) return;

        const geo = (typeof TANKS_GEO !== 'undefined' && TANKS_GEO[r.tank_id]) ? TANKS_GEO[r.tank_id] : {};
        const safeLat = (geo.lat && !isNaN(geo.lat)) ? Number(geo.lat) : 14.6538;
        const safeLng = (geo.lng && !isNaN(geo.lng)) ? Number(geo.lng) : 120.9820;
        const owner = r.owner_name || 'Client';
        const tankId = r.tank_id || 'Tank';
        const assigned = r.assigned_to || 'Unassigned';
        const status = r.status || 'pending';

        document.getElementById('reqRouteMapTitle').textContent = `${sanitizeHTML(owner)} — ${sanitizeHTML(tankId)}`;
        document.getElementById('reqRouteMapCoordinates').textContent = `Lat: ${safeLat.toFixed(4)}, Lng: ${safeLng.toFixed(4)}`;

        const statusBadges = {
            pending: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Pending</span>',
            in_progress: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">In Progress</span>',
            completed: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Completed</span>',
            cancelled: '<span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">Cancelled</span>'
        };

        document.getElementById('reqRouteServiceInfo').innerHTML = `
            <div>
                <p class="font-bold text-slate-800">${sanitizeHTML(tankId)} &bull; ${sanitizeHTML(owner)}</p>
                <p class="text-slate-500 mt-0.5"><i class="fa-solid fa-user-gear mr-1 text-brand-medium"></i> Assigned: <strong class="text-slate-700">${sanitizeHTML(assigned)}</strong></p>
            </div>
            <div class="text-right">
                ${statusBadges[status] || ''}
                <p class="text-slate-400 text-[11px] mt-1">${new Date(r.preferred_date).toLocaleDateString()} ${sanitizeHTML(r.preferred_time || '')}</p>
            </div>
        `;

        openModal('requestRouteModal');

        setTimeout(() => {
            if (typeof L === 'undefined') return;
            const container = document.getElementById('leafletRequestMap');
            if (!container) return;

            const customPinIcon = L.divIcon({
                className: 'custom-req-marker',
                html: `<div style="width:36px; height:36px; background:linear-gradient(135deg, #0B4F4A, #14807A); border:3px solid #ffffff; border-radius:50%; box-shadow:0 4px 12px rgba(11,79,74,0.45); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:15px; transform:translate(-2px, -2px);"><i class="fa-solid fa-location-dot"></i></div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
                popupAnchor: [0, -20]
            });

            if (!_reqRouteMapInstance) {
                _reqRouteMapInstance = L.map('leafletRequestMap').setView([safeLat, safeLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(_reqRouteMapInstance);
                _reqRouteMarker = L.marker([safeLat, safeLng], { icon: customPinIcon }).addTo(_reqRouteMapInstance);
            } else {
                _reqRouteMapInstance.setView([safeLat, safeLng], 15);
                if (_reqRouteMarker) {
                    _reqRouteMarker.setIcon(customPinIcon);
                    _reqRouteMarker.setLatLng([safeLat, safeLng]);
                } else {
                    _reqRouteMarker = L.marker([safeLat, safeLng], { icon: customPinIcon }).addTo(_reqRouteMapInstance);
                }
            }
            if (_reqRouteMarker) {
                _reqRouteMarker.bindPopup(`
                    <div style="font-family:inherit; padding:4px;">
                        <strong style="color:#0B4F4A; font-size:13px;">${sanitizeHTML(owner)}</strong><br>
                        <span style="font-size:11px; color:#64748b;">${sanitizeHTML(tankId)} &bull; ${sanitizeHTML(r.service_type || 'Service')}</span>
                    </div>
                `).openPopup();
            }
            _reqRouteMapInstance.invalidateSize();
        }, 200);
    }

    // ============================================================
    // REAL SEPTIC TANK DATA & OWNER DROPDOWN SYNCHRONIZATION
    // ============================================================
    function filterRequestTankDropdowns(query, tankSelectId = 'req_tank', ownerSelectId = 'req_owner') {
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
            if (tankSelectId === 'req_tank') {
                onRequestTankChange(firstMatchedTank);
            } else if (tankSelectId === 'edit_request_tank') {
                onEditRequestTankChange(firstMatchedTank);
            }
        }
    }

    function checkDuplicateRequest(tankId) {
        const warningBox = document.getElementById('request_duplicate_warning');
        const warningText = document.getElementById('request_duplicate_warning_text');
        if (!warningBox || !warningText) return null;

        if (!tankId) {
            warningBox.classList.add('hidden');
            return null;
        }

        const active = Object.values(REQUESTS).find(r => 
            r.tank_id === tankId && 
            (r.status === 'pending' || r.status === 'in_progress' || r.status === 'approved')
        );

        if (active) {
            const dateStr = active.preferred_date ? new Date(active.preferred_date).toLocaleDateString() : '';
            const statusLabel = active.status === 'in_progress' ? 'In Progress' : 'Pending';
            warningText.innerHTML = `<strong>Active Request Notice:</strong> Tank <strong>${sanitizeHTML(tankId)}</strong> already has an active <strong>${statusLabel}</strong> service request (<strong>${sanitizeHTML(active.request_id || '')}</strong>) for <strong>${dateStr}</strong>.`;
            warningBox.classList.remove('hidden');
            return active;
        } else {
            warningBox.classList.add('hidden');
            return null;
        }
    }

    function onRequestTankChange(tankId) {
        if (!tankId) {
            checkDuplicateRequest('');
            return;
        }
        const select = document.getElementById('req_tank');
        const opt = select.options[select.selectedIndex];
        if (opt) {
            const owner = opt.dataset.owner || '';
            const address = opt.dataset.address || '';
            const barangay = opt.dataset.barangay || '';
            
            const ownerSelect = document.getElementById('req_owner');
            if (ownerSelect && owner) ownerSelect.value = owner;

            const addressInput = document.getElementById('req_address');
            if (addressInput) addressInput.value = address;

            const brgyInput = document.getElementById('req_barangay');
            if (brgyInput) brgyInput.value = barangay;
        }
        checkDuplicateRequest(tankId);
    }

    function onRequestOwnerChange(ownerName) {
        if (!ownerName) {
            checkDuplicateRequest('');
            return;
        }
        const select = document.getElementById('req_owner');
        const opt = select.options[select.selectedIndex];
        let currentTank = '';
        if (opt) {
            const tank = opt.dataset.tank || '';
            const address = opt.dataset.address || '';
            const barangay = opt.dataset.barangay || '';
            
            const tankSelect = document.getElementById('req_tank');
            if (tankSelect && tank) {
                tankSelect.value = tank;
                currentTank = tank;
            }

            const addressInput = document.getElementById('req_address');
            if (addressInput) addressInput.value = address;

            const brgyInput = document.getElementById('req_barangay');
            if (brgyInput) brgyInput.value = barangay;
        }
        checkDuplicateRequest(currentTank || document.getElementById('req_tank')?.value);
    }

    function onEditRequestTankChange(tankId) {
        if (!tankId) return;
        const select = document.getElementById('edit_request_tank');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.owner) {
            const ownerSelect = document.getElementById('edit_request_owner');
            if (ownerSelect) ownerSelect.value = opt.dataset.owner;
        }
    }

    function onEditRequestOwnerChange(ownerName) {
        if (!ownerName) return;
        const select = document.getElementById('edit_request_owner');
        const opt = select.options[select.selectedIndex];
        if (opt && opt.dataset.tank) {
            const tankSelect = document.getElementById('edit_request_tank');
            if (tankSelect) tankSelect.value = opt.dataset.tank;
        }
    }

    // ============================================================
    // EDIT REQUEST
    // ============================================================
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

    function editRequest(id) {
        const request = REQUESTS[id];
        if (!request) return;
        document.getElementById('edit_request_id').value = request.id;
        document.getElementById('edit_request_tank').value = request.tank_id;
        document.getElementById('edit_request_owner').value = request.owner_name;
        document.getElementById('edit_request_type').value = request.service_type;
        document.getElementById('edit_request_date').value = request.preferred_date;
        document.getElementById('edit_request_time').value = parseTimeTo24(request.preferred_time);
        document.getElementById('edit_request_priority').value = request.priority;
        document.getElementById('edit_request_status').value = request.status;
        document.getElementById('edit_request_notes').value = request.notes || '';
        openModal('editRequestModal');
    }

    async function saveRequestEdit(event) {
        event.preventDefault();
        try {
            const id = document.getElementById('edit_request_id').value;
            const timeRaw = document.getElementById('edit_request_time').value;
            const payload = {
                tank_id: document.getElementById('edit_request_tank').value.trim(),
                owner_name: document.getElementById('edit_request_owner').value.trim(),
                service_type: document.getElementById('edit_request_type').value,
                preferred_date: document.getElementById('edit_request_date').value,
                preferred_time: formatTime24to12(timeRaw),
                priority: document.getElementById('edit_request_priority').value,
                status: document.getElementById('edit_request_status').value,
                notes: document.getElementById('edit_request_notes').value.trim()
            };

            const res = await fetch(`../../api/service_requests.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                closeModal('editRequestModal');
                showToast('Service request updated successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to update service request', 'danger');
            }
        } catch (err) {
            console.error('saveRequestEdit error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // NEW REQUEST
    // ============================================================
    async function saveNewRequest(event) {
        event.preventDefault();
        try {
            const tankId = document.getElementById('req_tank')?.value?.trim();
            const ownerName = document.getElementById('req_owner')?.value?.trim();
            if (!tankId || !ownerName) {
                showToast('Please select a Septic Tank and Owner.', 'warning');
                return;
            }

            // ⚡ DUPLICATE REQUEST PREVENTION: Block active request overlaps
            const duplicateConflict = Object.values(REQUESTS).find(r => 
                r.tank_id === tankId && 
                (r.status === 'pending' || r.status === 'in_progress' || r.status === 'approved')
            );
            if (duplicateConflict) {
                const confStatus = duplicateConflict.status === 'in_progress' ? 'In Progress' : 'Pending';
                showToast(`Duplicate Prevention: Tank ${tankId} already has an active ${confStatus} request (#${duplicateConflict.request_id}).`, 'warning');
                return;
            }

            const payload = {
                tank_id: tankId,
                owner_name: ownerName,
                address: document.getElementById('req_address')?.value?.trim() || '',
                barangay: document.getElementById('req_barangay')?.value || 'Barangay San Jose',
                service_type: document.getElementById('req_type')?.value || 'desludging',
                preferred_date: document.getElementById('req_date')?.value || new Date().toISOString().split('T')[0],
                preferred_time: formatTime24to12(document.getElementById('req_time')?.value || '09:00'),
                priority: document.getElementById('req_priority')?.value || 'medium',
                notes: document.getElementById('req_notes')?.value?.trim() || ''
            };

            const res = await fetch('../../api/service_requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                showToast('Service request submitted successfully!', 'success');
                closeModal('newRequestModal');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to submit request', 'danger');
            }
        } catch (err) {
            console.error('saveNewRequest error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // Toast, openModal, closeModal, sanitizeHTML, exportTableToCSV provided by common.js

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchRequest').addEventListener('input', filterRequests);
    document.getElementById('filterStatus').addEventListener('change', filterRequests);
    document.getElementById('filterType').addEventListener('change', filterRequests);
    document.getElementById('filterPriority').addEventListener('change', filterRequests);
    document.getElementById('filterDateFrom').addEventListener('change', filterRequests);
    document.getElementById('filterDateTo').addEventListener('change', filterRequests);

    function filterRequests() {
        const search = document.getElementById('searchRequest').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const type = document.getElementById('filterType').value;
        const priority = document.getElementById('filterPriority').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.request-row').forEach(row => {
            const owner = (row.dataset.owner || '').toLowerCase();
            const tank = (row.dataset.tank || '').toLowerCase();
            const requestId = (row.dataset.requestId || '').toLowerCase();
            const technician = (row.dataset.technician || '').toLowerCase();
            const rowStatus = row.dataset.status || '';
            const rowType = (row.dataset.type || '').toLowerCase();
            const preferredDate = row.dataset.preferredDate || '';
            const rowPriority = row.dataset.priority || '';
            const rowText = (row.textContent || row.innerText || '').toLowerCase();

            const matchesSearch = !search || 
                                  owner.includes(search) || 
                                  tank.includes(search) || 
                                  requestId.includes(search) || 
                                  technician.includes(search) || 
                                  rowText.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesType = !type || rowType === type.toLowerCase();
            const matchesPriority = !priority || rowPriority === priority;
            const matchesDateFrom = !dateFrom || (preferredDate && preferredDate >= dateFrom);
            const matchesDateTo = !dateTo || (preferredDate && preferredDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesType && matchesPriority && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        const emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        }
    }

    function resetFilters() {
        document.getElementById('searchRequest').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterPriority').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.request-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    // ESC key and backdrop-click handled by common.js

    // ============================================================
    // SET DEFAULT DATE
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('req_date');
        if (dateInput) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.value = tomorrow.toISOString().split('T')[0];
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>