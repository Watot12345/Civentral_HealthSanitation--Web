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

// Sample Technicians
$technicians = [
    ['id' => 1, 'name' => 'Roberto Silva', 'status' => 'on_site', 'assignment' => 'ST-002'],
    ['id' => 2, 'name' => 'Jose Mendoza', 'status' => 'available', 'assignment' => null],
    ['id' => 3, 'name' => 'Luis Torres', 'status' => 'en_route', 'assignment' => 'ST-001'],
    ['id' => 4, 'name' => 'Carlos Santos', 'status' => 'available', 'assignment' => null],
    ['id' => 5, 'name' => 'Ana Reyes', 'status' => 'on_site', 'assignment' => 'ST-003'],
];

// Sample Maintenance/Desludging Records
$maintenanceRecords = [
    [
        'id' => 1,
        'service_id' => 'SRV-001',
        'tank_id' => 'ST-001',
        'owner_name' => 'Pedro Garcia',
        'address' => '123 Rizal St., Barangay San Jose',
        'service_type' => 'desludging',
        'scheduled_date' => '2026-07-20',
        'scheduled_time' => '09:00 AM',
        'technician' => 'Roberto Silva',
        'status' => 'scheduled',
        'completed_date' => null,
        'completed_time' => null,
        'findings' => null,
        'recommendations' => null,
        'notes' => 'Regular scheduled desludging',
        'cost' => 1500.00,
        'rating' => null
    ],
    [
        'id' => 2,
        'service_id' => 'SRV-002',
        'tank_id' => 'ST-003',
        'owner_name' => 'Carlos Lim',
        'address' => '789 Bonifacio Rd., Barangay Riverside',
        'service_type' => 'maintenance',
        'scheduled_date' => '2026-07-18',
        'scheduled_time' => '10:30 AM',
        'technician' => 'Ana Reyes',
        'status' => 'in_progress',
        'completed_date' => null,
        'completed_time' => null,
        'findings' => 'Cracked tank detected',
        'recommendations' => 'Immediate replacement recommended',
        'notes' => 'Emergency maintenance call',
        'cost' => 2500.00,
        'rating' => null
    ],
    [
        'id' => 3,
        'service_id' => 'SRV-003',
        'tank_id' => 'ST-005',
        'owner_name' => 'Ramon Garcia',
        'address' => '505 Bonifacio Rd., Barangay Riverside',
        'service_type' => 'desludging',
        'scheduled_date' => '2026-07-15',
        'scheduled_time' => '08:00 AM',
        'technician' => 'Jose Mendoza',
        'status' => 'completed',
        'completed_date' => '2026-07-15',
        'completed_time' => '11:30 AM',
        'findings' => 'Complete desludging performed. Tank in good condition.',
        'recommendations' => 'Next service in 12 months',
        'notes' => 'Regular maintenance',
        'cost' => 1200.00,
        'rating' => 5
    ],
    [
        'id' => 4,
        'service_id' => 'SRV-004',
        'tank_id' => 'ST-002',
        'owner_name' => 'Rosa Mendoza',
        'address' => '456 Mabini Ave., Barangay Poblacion',
        'service_type' => 'inspection',
        'scheduled_date' => '2026-07-12',
        'scheduled_time' => '02:00 PM',
        'technician' => 'Luis Torres',
        'status' => 'completed',
        'completed_date' => '2026-07-12',
        'completed_time' => '03:15 PM',
        'findings' => 'Minor blockage found and cleared',
        'recommendations' => 'Monitor regularly',
        'notes' => 'Routine inspection',
        'cost' => 800.00,
        'rating' => 4
    ],
    [
        'id' => 5,
        'service_id' => 'SRV-005',
        'tank_id' => 'ST-004',
        'owner_name' => 'Elena Torres',
        'address' => '202 Santos St., Barangay Sta. Cruz',
        'service_type' => 'maintenance',
        'scheduled_date' => '2026-07-25',
        'scheduled_time' => '11:00 AM',
        'technician' => 'Carlos Santos',
        'status' => 'scheduled',
        'completed_date' => null,
        'completed_time' => null,
        'findings' => null,
        'recommendations' => null,
        'notes' => 'Quarterly maintenance check',
        'cost' => 1000.00,
        'rating' => null
    ],
    [
        'id' => 6,
        'service_id' => 'SRV-006',
        'tank_id' => 'ST-001',
        'owner_name' => 'Pedro Garcia',
        'address' => '123 Rizal St., Barangay San Jose',
        'service_type' => 'desludging',
        'scheduled_date' => '2026-07-10',
        'scheduled_time' => '09:30 AM',
        'technician' => 'Roberto Silva',
        'status' => 'completed',
        'completed_date' => '2026-07-10',
        'completed_time' => '12:00 PM',
        'findings' => 'Full desludging completed. System functioning properly.',
        'recommendations' => 'Schedule next service in 6 months',
        'notes' => 'Regular service',
        'cost' => 1500.00,
        'rating' => 5
    ],
    [
        'id' => 7,
        'service_id' => 'SRV-007',
        'tank_id' => 'ST-006',
        'owner_name' => 'Miguel Reyes',
        'address' => '303 Rizal St., Barangay San Jose',
        'service_type' => 'installation',
        'scheduled_date' => '2026-07-08',
        'scheduled_time' => '08:00 AM',
        'technician' => 'Jose Mendoza',
        'status' => 'completed',
        'completed_date' => '2026-07-08',
        'completed_time' => '04:30 PM',
        'findings' => 'New septic tank installed. Full plumbing connection completed.',
        'recommendations' => 'First inspection in 6 months',
        'notes' => 'New tank installation',
        'cost' => 5000.00,
        'rating' => 5
    ],
];

// Route Planning Data
$routeData = [
    ['technician' => 'Roberto Silva', 'tank' => 'ST-002', 'address' => '456 Mabini Ave., Poblacion', 'status' => 'In Progress'],
    ['technician' => 'Jose Mendoza', 'tank' => 'ST-005', 'address' => '505 Bonifacio Rd., Riverside', 'status' => 'En Route'],
    ['technician' => 'Luis Torres', 'tank' => 'ST-001', 'address' => '123 Rizal St., San Jose', 'status' => 'Completed'],
];

// Stats
$totalServices = count($maintenanceRecords);
$scheduledServices = count(array_filter($maintenanceRecords, fn($s) => $s['status'] === 'scheduled'));
$inProgress = count(array_filter($maintenanceRecords, fn($s) => $s['status'] === 'in_progress'));
$completedServices = count(array_filter($maintenanceRecords, fn($s) => $s['status'] === 'completed'));
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
            <p class="text-sm text-slate-500 mt-0.5">Schedule services, manage records, plan routes & track completions</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('scheduleServiceModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-calendar-plus text-xs"></i> Schedule Service
            </button>
            <button onclick="openModal('routePlanningModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-route text-xs"></i> Route Planning
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
                        data-owner="<?php echo strtolower($record['owner_name']); ?>"
                        data-tank="<?php echo $record['tank_id']; ?>"
                        data-status="<?php echo $record['status']; ?>"
                        data-type="<?php echo $record['service_type']; ?>"
                        data-technician="<?php echo strtolower($record['technician']); ?>">
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
                                <?php if ($record['status'] === 'scheduled' || $record['status'] === 'in_progress'): ?>
                                    <button onclick="completeService(<?php echo $record['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete">
                                        <i class="fa-solid fa-check text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($record['status'] === 'completed' && !$record['rating']): ?>
                                    <button onclick="rateService(<?php echo $record['id']; ?>)"
                                            class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition" title="Rate">
                                        <i class="fa-solid fa-star text-sm"></i>
                                    </button>
                                <?php endif; ?>
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
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID</label>
                <input type="text" id="schedule_tank" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. ST-001">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label>
                <input type="text" id="schedule_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label>
                <select id="schedule_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="desludging">Desludging</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inspection">Inspection</option>
                    <option value="installation">Installation</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Technician</label>
                <select id="schedule_technician" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Technician</option>
                    <?php foreach ($technicians as $tech): ?>
                        <option value="<?php echo $tech['name']; ?>"><?php echo $tech['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                    <input type="date" id="schedule_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time</label>
                    <input type="time" id="schedule_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Estimated Cost (₱)</label>
                <input type="number" id="schedule_cost" required min="0" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="schedule_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Special instructions..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('scheduleServiceModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-calendar-plus mr-1.5"></i> Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ROUTE PLANNING MODAL                                         -->
<!-- ============================================================ -->
<div id="routePlanningModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-route text-brand-medium"></i>
                Route Planning
            </h3>
            <button onclick="closeModal('routePlanningModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-700">Today's Routes</h4>
                    <p class="text-xs text-slate-400"><?php echo date('F d, Y'); ?></p>
                </div>
                <button onclick="optimizeRoutes()" class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-dark rounded-lg hover:bg-brand-medium transition">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Optimize Routes
                </button>
            </div>
            
            <div class="space-y-3">
                <?php foreach ($routeData as $route): ?>
                <div class="flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 hover:shadow-md transition">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                            <?php echo strtoupper(substr($route['technician'], 0, 2)); ?>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800 text-sm"><?php echo $route['technician']; ?></p>
                            <p class="text-xs text-slate-400"><?php echo $route['tank']; ?> • <?php echo $route['address']; ?></p>
                        </div>
                    </div>
                    <?php
                        $routeStatusColors = [
                            'In Progress' => 'bg-blue-100 text-blue-700',
                            'En Route' => 'bg-amber-100 text-amber-700',
                            'Completed' => 'bg-emerald-100 text-emerald-700',
                        ];
                    ?>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $routeStatusColors[$route['status']] ?? 'bg-slate-100 text-slate-500'; ?>">
                        <?php echo $route['status']; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Route Map Preview -->
            <div class="mt-4 bg-slate-100 rounded-xl p-4 text-center">
                <i class="fa-solid fa-map-location-dot text-3xl text-brand-medium block mb-2"></i>
                <p class="text-sm text-slate-600">Route map visualization</p>
                <p class="text-xs text-slate-400">3 technicians • 3 active routes</p>
                <div class="mt-2 flex justify-center gap-4">
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-xs text-slate-500">In Progress</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                        <span class="text-xs text-slate-500">En Route</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                        <span class="text-xs text-slate-500">Completed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW SERVICE MODAL                                           -->
<!-- ============================================================ -->
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
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-check text-brand-medium"></i>
                Completion Report
            </h3>
            <button onclick="closeModal('completionReportModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="completionReportForm" class="p-6 space-y-4" onsubmit="saveCompletionReport(event)">
            <input type="hidden" id="complete_service_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Findings</label>
                <textarea id="complete_findings" rows="3" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Describe what was done..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recommendations</label>
                <textarea id="complete_recommendations" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Next steps or suggestions..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Actual Cost (₱)</label>
                <input type="number" id="complete_cost" required min="0" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('completionReportModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Submit Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const SERVICES = <?php echo json_encode(array_column($maintenanceRecords, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

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

            document.getElementById('serviceDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${s.owner_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${s.owner_name}</h4>
                            <p class="text-sm text-slate-500">${s.service_id} • ${s.tank_id}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[s.status] || statusColors.scheduled}">
                                ${s.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Service Type</p><p class="text-sm text-slate-800 capitalize">${s.service_type}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Technician</p><p class="text-sm text-slate-800">${s.technician}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Scheduled Date</p><p class="text-sm text-slate-800">${new Date(s.scheduled_date).toLocaleDateString()} at ${s.scheduled_time}</p></div>
                        ${s.completed_date ? `<div><p class="text-xs text-slate-400 font-semibold">Completed</p><p class="text-sm text-slate-800">${new Date(s.completed_date).toLocaleDateString()} at ${s.completed_time}</p></div>` : ''}
                        <div><p class="text-xs text-slate-400 font-semibold">Cost</p><p class="text-sm font-bold text-slate-800">₱${Number(s.cost).toFixed(2)}</p></div>
                        ${s.rating ? `<div><p class="text-xs text-slate-400 font-semibold">Rating</p><p class="text-sm text-amber-500">${'⭐'.repeat(s.rating)}</p></div>` : ''}
                    </div>
                    ${s.findings ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Findings</h5><p class="text-sm text-slate-800">${s.findings}</p></div>` : ''}
                    ${s.recommendations ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">Recommendations</h5><p class="text-sm text-slate-800">${s.recommendations}</p></div>` : ''}
                    ${s.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${s.notes}</p></div>` : ''}
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewServiceModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        ${s.status === 'scheduled' || s.status === 'in_progress' ? `<button onclick="closeModal('viewServiceModal'); completeService(${s.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Complete</button>` : ''}
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // COMPLETE SERVICE
    // ============================================================
    function completeService(id) {
        const s = SERVICES[id];
        if (!s) return;
        
        document.getElementById('complete_service_id').value = id;
        document.getElementById('complete_findings').value = s.findings || '';
        document.getElementById('complete_recommendations').value = s.recommendations || '';
        document.getElementById('complete_cost').value = s.cost || 0;
        
        openModal('completionReportModal');
    }

    function saveCompletionReport(event) {
        event.preventDefault();
        const id = document.getElementById('complete_service_id').value;
        const s = SERVICES[id];
        if (!s) return;
        
        s.status = 'completed';
        s.completed_date = new Date().toISOString().split('T')[0];
        s.completed_time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
        s.findings = document.getElementById('complete_findings').value.trim();
        s.recommendations = document.getElementById('complete_recommendations').value.trim();
        s.cost = parseFloat(document.getElementById('complete_cost').value);
        
        updateServiceRow(s);
        closeModal('completionReportModal');
        showToast('Service #' + s.service_id + ' completed!', 'success');
    }

    function updateServiceRow(s) {
        const rows = document.querySelectorAll('.maintenance-row');
        rows.forEach(row => {
            const owner = row.querySelector('.font-semibold.text-slate-800.text-sm')?.textContent;
            if (owner === s.owner_name) {
                const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
                const statusColors = {
                    scheduled: 'bg-blue-100 text-blue-700',
                    in_progress: 'bg-amber-100 text-amber-700',
                    completed: 'bg-emerald-100 text-emerald-700'
                };
                statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[s.status] || statusColors.scheduled}`;
                statusBadge.textContent = s.status.replace('_', ' ').toUpperCase();
                
                const costCell = row.querySelector('.text-sm.font-bold.text-slate-700');
                if (costCell) costCell.textContent = '₱' + Number(s.cost).toFixed(2);
            }
        });
    }

    // ============================================================
    // EDIT SERVICE
    // ============================================================
    function editService(id) {
        showToast('Edit service ID: ' + id + ' (Edit modal coming soon)', 'info');
    }

    // ============================================================
    // RATE SERVICE
    // ============================================================
    function rateService(id) {
        const rating = prompt('Rate this service (1-5 stars):', '5');
        if (rating && rating >= 1 && rating <= 5) {
            const s = SERVICES[id];
            if (s) {
                s.rating = parseInt(rating);
                showToast('Service rated ' + rating + ' stars!', 'success');
            }
        }
    }

    // ============================================================
    // ROUTE PLANNING
    // ============================================================
    function optimizeRoutes() {
        showToast('🗺️ Routes optimized successfully!', 'success');
    }

    // ============================================================
    // SCHEDULE SERVICE
    // ============================================================
    function saveScheduleService(event) {
        event.preventDefault();
        showToast('Service scheduled successfully!', 'success');
        closeModal('scheduleServiceModal');
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
    document.getElementById('searchMaintenance').addEventListener('input', filterMaintenance);
    document.getElementById('filterStatus').addEventListener('change', filterMaintenance);
    document.getElementById('filterType').addEventListener('change', filterMaintenance);
    document.getElementById('filterTechnician').addEventListener('change', filterMaintenance);

    function filterMaintenance() {
        const search = document.getElementById('searchMaintenance').value.toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const type = document.getElementById('filterType').value;
        const technician = document.getElementById('filterTechnician').value.toLowerCase();
        let visibleCount = 0;

        document.querySelectorAll('.maintenance-row').forEach(row => {
            const owner = row.dataset.owner;
            const tank = row.dataset.tank;
            const rowStatus = row.dataset.status;
            const rowType = row.dataset.type;
            const rowTechnician = row.dataset.technician;

            const matchesSearch = owner.includes(search) || tank.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesType = !type || rowType === type;
            const matchesTechnician = !technician || rowTechnician.includes(technician);
            const isVisible = matchesSearch && matchesStatus && matchesType && matchesTechnician;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchMaintenance').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterTechnician').value = '';
        document.querySelectorAll('.maintenance-row').forEach(row => row.style.display = '');
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
        const dateInput = document.getElementById('schedule_date');
        if (dateInput) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            dateInput.value = tomorrow.toISOString().split('T')[0];
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>