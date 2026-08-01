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

// Sample Service Requests Data
$serviceRequests = [
    [
        'id' => 1,
        'request_id' => 'SR-001',
        'tank_id' => 'ST-001',
        'owner_name' => 'Pedro Garcia',
        'address' => '123 Rizal St., Barangay San Jose',
        'barangay' => 'Barangay San Jose',
        'service_type' => 'desludging',
        'preferred_date' => '2026-07-25',
        'preferred_time' => '09:00 AM',
        'assigned_to' => 'Roberto Silva',
        'status' => 'pending',
        'priority' => 'medium',
        'notes' => 'Tank is full, need immediate desludging',
        'created_at' => '2026-07-20 08:30:00',
        'completed_at' => null,
        'feedback' => null,
        'rating' => null
    ],
    [
        'id' => 2,
        'request_id' => 'SR-002',
        'tank_id' => 'ST-003',
        'owner_name' => 'Carlos Lim',
        'address' => '789 Bonifacio Rd., Barangay Riverside',
        'barangay' => 'Barangay Riverside',
        'service_type' => 'maintenance',
        'preferred_date' => '2026-07-18',
        'preferred_time' => '10:30 AM',
        'assigned_to' => 'Ana Reyes',
        'status' => 'in_progress',
        'priority' => 'high',
        'notes' => 'Cracked tank - urgent repair needed',
        'created_at' => '2026-07-17 14:15:00',
        'completed_at' => null,
        'feedback' => null,
        'rating' => null
    ],
    [
        'id' => 3,
        'request_id' => 'SR-003',
        'tank_id' => 'ST-005',
        'owner_name' => 'Ramon Garcia',
        'address' => '505 Bonifacio Rd., Barangay Riverside',
        'barangay' => 'Barangay Riverside',
        'service_type' => 'desludging',
        'preferred_date' => '2026-07-15',
        'preferred_time' => '08:00 AM',
        'assigned_to' => 'Jose Mendoza',
        'status' => 'completed',
        'priority' => 'low',
        'notes' => 'Regular desludging service',
        'created_at' => '2026-07-14 09:00:00',
        'completed_at' => '2026-07-15 11:30:00',
        'feedback' => 'Very professional and thorough service. Tank is now clean.',
        'rating' => 5
    ],
    [
        'id' => 4,
        'request_id' => 'SR-004',
        'tank_id' => 'ST-002',
        'owner_name' => 'Rosa Mendoza',
        'address' => '456 Mabini Ave., Barangay Poblacion',
        'barangay' => 'Barangay Poblacion',
        'service_type' => 'inspection',
        'preferred_date' => '2026-07-12',
        'preferred_time' => '02:00 PM',
        'assigned_to' => 'Luis Torres',
        'status' => 'completed',
        'priority' => 'medium',
        'notes' => 'Routine inspection',
        'created_at' => '2026-07-11 11:20:00',
        'completed_at' => '2026-07-12 03:15:00',
        'feedback' => 'Inspector was punctual and thorough. Found minor issues.',
        'rating' => 4
    ],
    [
        'id' => 5,
        'request_id' => 'SR-005',
        'tank_id' => 'ST-004',
        'owner_name' => 'Elena Torres',
        'address' => '202 Santos St., Barangay Sta. Cruz',
        'barangay' => 'Barangay Sta. Cruz',
        'service_type' => 'installation',
        'preferred_date' => '2026-07-28',
        'preferred_time' => '09:30 AM',
        'assigned_to' => 'Carlos Santos',
        'status' => 'pending',
        'priority' => 'high',
        'notes' => 'New septic tank installation',
        'created_at' => '2026-07-21 16:45:00',
        'completed_at' => null,
        'feedback' => null,
        'rating' => null
    ],
    [
        'id' => 6,
        'request_id' => 'SR-006',
        'tank_id' => 'ST-006',
        'owner_name' => 'Miguel Reyes',
        'address' => '303 Rizal St., Barangay San Jose',
        'barangay' => 'Barangay San Jose',
        'service_type' => 'maintenance',
        'preferred_date' => '2026-07-10',
        'preferred_time' => '08:00 AM',
        'assigned_to' => 'Jose Mendoza',
        'status' => 'cancelled',
        'priority' => 'low',
        'notes' => 'Cancelled - customer rescheduled',
        'created_at' => '2026-07-09 10:00:00',
        'completed_at' => '2026-07-10 07:30:00',
        'feedback' => 'Rescheduled due to conflict',
        'rating' => null
    ],
    [
        'id' => 7,
        'request_id' => 'SR-007',
        'tank_id' => 'ST-001',
        'owner_name' => 'Pedro Garcia',
        'address' => '123 Rizal St., Barangay San Jose',
        'barangay' => 'Barangay San Jose',
        'service_type' => 'maintenance',
        'preferred_date' => '2026-07-22',
        'preferred_time' => '11:00 AM',
        'assigned_to' => 'Roberto Silva',
        'status' => 'in_progress',
        'priority' => 'medium',
        'notes' => 'Check for leaks',
        'created_at' => '2026-07-21 08:00:00',
        'completed_at' => null,
        'feedback' => null,
        'rating' => null
    ],
];

// Stats
$totalRequests = count($serviceRequests);
$pendingRequests = count(array_filter($serviceRequests, fn($r) => $r['status'] === 'pending'));
$inProgressRequests = count(array_filter($serviceRequests, fn($r) => $r['status'] === 'in_progress'));
$completedRequests = count(array_filter($serviceRequests, fn($r) => $r['status'] === 'completed'));
$cancelledRequests = count(array_filter($serviceRequests, fn($r) => $r['status'] === 'cancelled'));
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
                        data-request-id="<?php echo strtolower($request['request_id']); ?>"
                        data-owner="<?php echo strtolower($request['owner_name']); ?>"
                        data-tank="<?php echo $request['tank_id']; ?>"
                        data-technician="<?php echo strtolower($request['assigned_to'] ?? ''); ?>"
                        data-status="<?php echo $request['status']; ?>"
                        data-type="<?php echo $request['service_type']; ?>"
                        data-preferred-date="<?php echo $request['preferred_date']; ?>"
                        data-id="<?php echo $request['id']; ?>">
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
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID</label>
                <input type="text" id="req_tank" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. ST-001">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label>
                <input type="text" id="req_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="req_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label>
                <select id="req_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="desludging">Desludging</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="installation">Installation</option>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Date</label>
                    <input type="date" id="req_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Time</label>
                    <input type="time" id="req_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
<!-- VIEW REQUEST MODAL                                           -->
<!-- ============================================================ -->
<div id="editRequestModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Service Request</h3>
            <button type="button" onclick="closeModal('editRequestModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveRequestEdit(event)">
            <input type="hidden" id="edit_request_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID</label><input type="text" id="edit_request_tank" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label><input type="text" id="edit_request_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label><select id="edit_request_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="desludging">Desludging</option><option value="maintenance">Maintenance</option><option value="inspection">Inspection</option><option value="installation">Installation</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Date</label><input type="date" id="edit_request_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Preferred Time</label><input type="text" id="edit_request_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority</label><select id="edit_request_priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_request_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="pending">Pending</option><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_request_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editRequestModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
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

<script>
    const REQUESTS = <?php echo json_encode(array_column($serviceRequests, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // Close modal on backdrop click
    document.querySelectorAll('.fixed.inset-0').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });

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

            document.getElementById('requestDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${r.owner_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${r.owner_name}</h4>
                            <p class="text-sm text-slate-500">${r.request_id} • ${r.tank_id}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[r.status] || statusColors.pending}">
                                ${r.status.replace('_', ' ').toUpperCase()}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ml-1 ${priorityColors[r.priority] || priorityColors.medium}">
                                ${r.priority.toUpperCase()} PRIORITY
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Service Type</p><p class="text-sm text-slate-800 capitalize">${r.service_type}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Address</p><p class="text-sm text-slate-800">${r.address}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Preferred Date</p><p class="text-sm text-slate-800">${new Date(r.preferred_date).toLocaleDateString()} at ${r.preferred_time}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Assigned To</p><p class="text-sm text-slate-800">${r.assigned_to || 'Unassigned'}</p></div>
                        ${r.completed_at ? `<div><p class="text-xs text-slate-400 font-semibold">Completed</p><p class="text-sm text-slate-800">${new Date(r.completed_at).toLocaleDateString()}</p></div>` : ''}
                        ${r.rating ? `<div><p class="text-xs text-slate-400 font-semibold">Rating</p><p class="text-sm text-amber-500">${'⭐'.repeat(r.rating)}</p></div>` : ''}
                    </div>
                    ${r.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${r.notes}</p></div>` : ''}
                    ${r.feedback ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">💬 Feedback</h5><p class="text-sm text-slate-800">${r.feedback}</p></div>` : ''}
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewRequestModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
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

    function saveStatusUpdate(event) {
        event.preventDefault();
        const id = document.getElementById('update_request_id').value;
        const r = REQUESTS[id];
        if (!r) return;
        
        r.status = document.getElementById('update_status').value;
        const notes = document.getElementById('update_notes').value.trim();
        if (notes) {
            r.notes = r.notes ? r.notes + '\n' + notes : notes;
        }
        
        if (r.status === 'completed') {
            r.completed_at = new Date().toISOString();
        }
        
        updateRequestRow(r);
        closeModal('statusUpdateModal');
        showToast('Request #' + r.request_id + ' updated to ' + r.status.replace('_', ' '), 'success');
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
        const rows = document.querySelectorAll('.request-row');
        rows.forEach(row => {
            const owner = row.querySelector('.font-semibold.text-slate-800.text-sm')?.textContent;
            if (owner === r.owner_name) {
                const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
                const statusColors = {
                    pending: 'bg-amber-100 text-amber-700',
                    in_progress: 'bg-blue-100 text-blue-700',
                    completed: 'bg-emerald-100 text-emerald-700',
                    cancelled: 'bg-slate-100 text-slate-500'
                };
                statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[r.status] || statusColors.pending}`;
                statusBadge.textContent = r.status.replace('_', ' ').toUpperCase();
            }
        });
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

    function saveFeedback(event) {
        event.preventDefault();
        const id = document.getElementById('feedback_request_id').value;
        const r = REQUESTS[id];
        if (!r) return;
        
        r.rating = parseInt(document.getElementById('feedback_rating').value);
        r.feedback = document.getElementById('feedback_text').value.trim();
        
        closeModal('feedbackModal');
        showToast('Thank you for your feedback!', 'success');
    }

    // ============================================================
    // EDIT REQUEST
    // ============================================================
    function editRequest(id) {
        const request = REQUESTS[id];
        if (!request) return;
        document.getElementById('edit_request_id').value = request.id;
        document.getElementById('edit_request_tank').value = request.tank_id;
        document.getElementById('edit_request_owner').value = request.owner_name;
        document.getElementById('edit_request_type').value = request.service_type;
        document.getElementById('edit_request_date').value = request.preferred_date;
        document.getElementById('edit_request_time').value = request.preferred_time;
        document.getElementById('edit_request_priority').value = request.priority;
        document.getElementById('edit_request_status').value = request.status;
        document.getElementById('edit_request_notes').value = request.notes || '';
        openModal('editRequestModal');
    }

    function saveRequestEdit(event) {
        event.preventDefault();
        const id = document.getElementById('edit_request_id').value;
        const request = REQUESTS[id];
        if (!request) return;
        request.tank_id = document.getElementById('edit_request_tank').value.trim();
        request.owner_name = document.getElementById('edit_request_owner').value.trim();
        request.service_type = document.getElementById('edit_request_type').value;
        request.preferred_date = document.getElementById('edit_request_date').value;
        request.preferred_time = document.getElementById('edit_request_time').value;
        request.priority = document.getElementById('edit_request_priority').value;
        request.status = document.getElementById('edit_request_status').value;
        request.notes = document.getElementById('edit_request_notes').value.trim();
        const row = document.querySelector(`.request-row[data-id="${id}"]`);
        if (row) {
            row.dataset.owner = request.owner_name.toLowerCase();
            row.dataset.tank = request.tank_id;
            row.dataset.status = request.status;
            row.dataset.type = request.service_type;
            row.dataset.preferredDate = request.preferred_date;
        }
        closeModal('editRequestModal');
        showToast('Service request updated successfully!', 'success');
        filterRequests();
    }

    // ============================================================
    // NEW REQUEST
    // ============================================================
    function saveNewRequest(event) {
        event.preventDefault();
        showToast('Service request submitted successfully!', 'success');
        closeModal('newRequestModal');
    }

    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
    let toastTimer = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-rose-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        toast.className = 'fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ' + (colors[type] || colors.success);
        toast.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('hidden');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

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
            const owner = row.dataset.owner;
            const tank = row.dataset.tank;
            const requestId = row.dataset.requestId || '';
            const technician = row.dataset.technician || '';
            const rowStatus = row.dataset.status;
            const rowType = row.dataset.type;
            const preferredDate = row.dataset.preferredDate || '';
            const rowPriority = row.querySelector('.px-2.py-1.rounded-full:last-child')?.textContent?.toLowerCase().trim() || '';

            const matchesSearch = !search || [requestId, owner, tank, technician].some(value => value.includes(search));
            const matchesStatus = !status || rowStatus === status;
            const matchesType = !type || rowType === type;
            const matchesPriority = !priority || rowPriority.includes(priority);
            const matchesDateFrom = !dateFrom || (preferredDate && preferredDate >= dateFrom);
            const matchesDateTo = !dateTo || (preferredDate && preferredDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesType && matchesPriority && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
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

    // ESC to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });

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