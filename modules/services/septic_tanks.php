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

// Sample Septic Tanks Data
$septicTanks = [
    [
        'id' => 1,
        'tank_id' => 'ST-001',
        'owner_name' => 'Pedro Garcia',
        'address' => '123 Rizal St., Barangay San Jose',
        'barangay' => 'Barangay San Jose',
        'latitude' => 14.6542,
        'longitude' => 120.9821,
        'capacity' => '1200L',
        'type' => 'Concrete',
        'installation_year' => 2018,
        'last_maintenance' => '2026-03-15',
        'maintenance_frequency' => 12,
        'status' => 'good',
        'notes' => 'Regular maintenance schedule followed',
        'created_at' => '2018-06-15 08:30:00',
        'history' => [
            ['date' => '2026-03-15', 'type' => 'Desludging', 'notes' => 'Complete desludging performed'],
            ['date' => '2025-03-10', 'type' => 'Inspection', 'notes' => 'Routine inspection - all clear'],
            ['date' => '2024-03-05', 'type' => 'Maintenance', 'notes' => 'Minor repairs done'],
        ]
    ],
    [
        'id' => 2,
        'tank_id' => 'ST-002',
        'owner_name' => 'Rosa Mendoza',
        'address' => '456 Mabini Ave., Barangay Poblacion',
        'barangay' => 'Barangay Poblacion',
        'latitude' => 14.6610,
        'longitude' => 120.9755,
        'capacity' => '800L',
        'type' => 'Plastic',
        'installation_year' => 2020,
        'last_maintenance' => '2026-04-20',
        'maintenance_frequency' => 18,
        'status' => 'needs_maintenance',
        'notes' => 'Frequent blockages reported',
        'created_at' => '2020-04-20 10:15:00',
        'history' => [
            ['date' => '2026-04-20', 'type' => 'Maintenance', 'notes' => 'Cleared blockage'],
            ['date' => '2025-10-15', 'type' => 'Inspection', 'notes' => 'Found minor cracks'],
            ['date' => '2024-04-20', 'type' => 'Desludging', 'notes' => 'Routine desludging'],
        ]
    ],
    [
        'id' => 3,
        'tank_id' => 'ST-003',
        'owner_name' => 'Carlos Lim',
        'address' => '789 Bonifacio Rd., Barangay Riverside',
        'barangay' => 'Barangay Riverside',
        'latitude' => 14.6488,
        'longitude' => 120.9882,
        'capacity' => '1500L',
        'type' => 'Fiberglass',
        'installation_year' => 2015,
        'last_maintenance' => '2026-01-10',
        'maintenance_frequency' => 12,
        'status' => 'critical',
        'notes' => 'Cracked tank - needs immediate replacement',
        'created_at' => '2015-01-10 14:00:00',
        'history' => [
            ['date' => '2026-01-10', 'type' => 'Inspection', 'notes' => 'Found major crack'],
            ['date' => '2025-01-05', 'type' => 'Maintenance', 'notes' => 'Routine inspection'],
            ['date' => '2024-01-02', 'type' => 'Desludging', 'notes' => 'Regular service'],
        ]
    ],
    [
        'id' => 4,
        'tank_id' => 'ST-004',
        'owner_name' => 'Elena Torres',
        'address' => '202 Santos St., Barangay Sta. Cruz',
        'barangay' => 'Barangay Sta. Cruz',
        'latitude' => 14.6555,
        'longitude' => 120.9790,
        'capacity' => '1000L',
        'type' => 'Concrete',
        'installation_year' => 2022,
        'last_maintenance' => '2026-05-20',
        'maintenance_frequency' => 24,
        'status' => 'good',
        'notes' => 'Newly installed, well maintained',
        'created_at' => '2022-05-20 09:45:00',
        'history' => [
            ['date' => '2026-05-20', 'type' => 'Inspection', 'notes' => 'First inspection - all good'],
            ['date' => '2025-05-15', 'type' => 'Maintenance', 'notes' => 'Regular checkup'],
        ]
    ],
    [
        'id' => 5,
        'tank_id' => 'ST-005',
        'owner_name' => 'Ramon Garcia',
        'address' => '505 Bonifacio Rd., Barangay Riverside',
        'barangay' => 'Barangay Riverside',
        'latitude' => 14.6450,
        'longitude' => 120.9910,
        'capacity' => '2000L',
        'type' => 'Concrete',
        'installation_year' => 2010,
        'last_maintenance' => '2025-12-01',
        'maintenance_frequency' => 12,
        'status' => 'needs_maintenance',
        'notes' => 'Overdue for maintenance',
        'created_at' => '2010-12-01 11:20:00',
        'history' => [
            ['date' => '2025-12-01', 'type' => 'Inspection', 'notes' => 'Needs maintenance'],
            ['date' => '2024-12-05', 'type' => 'Desludging', 'notes' => 'Regular desludging'],
            ['date' => '2023-12-10', 'type' => 'Maintenance', 'notes' => 'Minor repairs'],
        ]
    ],
];

// Stats
$totalTanks = count($septicTanks);
$goodStatus = count(array_filter($septicTanks, fn($t) => $t['status'] === 'good'));
$needsMaintenance = count(array_filter($septicTanks, fn($t) => $t['status'] === 'needs_maintenance'));
$criticalStatus = count(array_filter($septicTanks, fn($t) => $t['status'] === 'critical'));

$title = 'Septic Tank Registry';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Septic Tank Registry</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage septic tank registrations, details & maintenance history</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('registerTankModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Register Tank
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Tanks -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-water text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalTanks; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Tanks</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🪣 All tanks</span>
                    <span class="text-[10px] text-slate-400"><?php echo $goodStatus; ?> in good condition</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Good Status -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $goodStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Good Status</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Healthy</span>
                    <span class="text-[10px] text-slate-400">No issues detected</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Needs Maintenance -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-tools text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $needsMaintenance; ?></p>
                        <p class="text-xs font-medium text-slate-500">Needs Maintenance</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">🔧 Attention</span>
                    <span class="text-[10px] text-slate-400">Action required</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Critical -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $criticalStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Critical</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
                    <span class="text-[10px] text-slate-400">Immediate response</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Alert -->
    <?php if ($criticalStatus > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo $criticalStatus; ?></span> septic tank(s) require immediate attention
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='critical'; filterTanks();" 
                class="text-xs font-semibold text-rose-700 hover:text-rose-900 underline">
            View critical
        </button>
    </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchTank"
                       placeholder="Search by tank ID, owner, or address..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="good">Good</option>
                    <option value="needs_maintenance">Needs Maintenance</option>
                    <option value="critical">Critical</option>
                </select>
                <select id="filterType" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="Concrete">Concrete</option>
                    <option value="Plastic">Plastic</option>
                    <option value="Fiberglass">Fiberglass</option>
                </select>
                <select id="filterBarangay" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Barangays</option>
                    <option value="Barangay San Jose">San Jose</option>
                    <option value="Barangay Poblacion">Poblacion</option>
                    <option value="Barangay Riverside">Riverside</option>
                    <option value="Barangay San Roque">San Roque</option>
                    <option value="Barangay Sta. Cruz">Sta. Cruz</option>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Maintenance date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Maintenance date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Tanks Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="tanksGrid">
        <?php foreach ($septicTanks as $tank): ?>
        <div class="tank-card bg-white rounded-xl shadow-xs border border-slate-200 p-4 hover:shadow-md transition-all duration-200 <?php echo $tank['status'] === 'critical' ? 'border-l-4 border-l-rose-500' : ($tank['status'] === 'needs_maintenance' ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-emerald-500'); ?>"
             data-owner="<?php echo strtolower($tank['owner_name']); ?>"
             data-id="<?php echo $tank['tank_id']; ?>"
             data-status="<?php echo $tank['status']; ?>"
             data-type="<?php echo $tank['type']; ?>"
             data-barangay="<?php echo $tank['barangay']; ?>"
             data-maintenance-date="<?php echo $tank['last_maintenance']; ?>">
            
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($tank['owner_name'], 0, 2)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm"><?php echo $tank['owner_name']; ?></p>
                        <p class="text-xs text-slate-400"><?php echo $tank['tank_id']; ?></p>
                    </div>
                </div>
                <?php
                    $statusColors = [
                        'good' => 'bg-emerald-100 text-emerald-700',
                        'needs_maintenance' => 'bg-amber-100 text-amber-700',
                        'critical' => 'bg-rose-100 text-rose-700'
                    ];
                ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$tank['status']] ?? $statusColors['good']; ?>">
                    <?php echo str_replace('_', ' ', ucfirst($tank['status'])); ?>
                </span>
            </div>
            
            <!-- Details -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Address</span>
                    <span class="text-slate-800 text-xs text-right"><?php echo $tank['address']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Type</span>
                    <span class="text-slate-800 text-xs"><?php echo $tank['type']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Capacity</span>
                    <span class="text-slate-800 text-xs font-semibold"><?php echo $tank['capacity']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Last Maintenance</span>
                    <span class="text-slate-800 text-xs"><?php echo date('M d, Y', strtotime($tank['last_maintenance'])); ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Barangay</span>
                    <span class="text-slate-800 text-xs"><?php echo str_replace('Barangay ', '', $tank['barangay']); ?></span>
                </div>
            </div>
            
            <!-- Location -->
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-400">
                <i class="fa-solid fa-location-dot text-brand-medium"></i>
                <span><?php echo $tank['latitude']; ?>, <?php echo $tank['longitude']; ?></span>
                <button onclick="viewMap(<?php echo $tank['latitude']; ?>, <?php echo $tank['longitude']; ?>, '<?php echo $tank['owner_name']; ?>')" 
                        class="ml-auto text-brand-medium hover:text-brand-dark transition">
                    <i class="fa-solid fa-map"></i> View Map
                </button>
            </div>
            
            <!-- Actions -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button onclick="viewTank(<?php echo $tank['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-brand-medium hover:bg-brand-light rounded-lg transition">
                    <i class="fa-solid fa-eye mr-1"></i> View
                </button>
                <button onclick="editTank(<?php echo $tank['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-lg transition">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
                <button onclick="viewHistory(<?php echo $tank['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition">
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i> History
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="fa-solid fa-water text-slate-400"></i>
        </div>
        <p class="text-sm font-semibold text-slate-600">No tanks match your filters</p>
        <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
        <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
    </div>

    <!-- Pagination -->
    <div class="mt-4 px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-white rounded-xl shadow-xs">
        <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700">1</span> to
            <span class="font-semibold text-slate-700"><?php echo $totalTanks; ?></span> of
            <span class="font-semibold text-slate-700"><?php echo $totalTanks; ?></span> tanks
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
<!-- REGISTER TANK MODAL                                          -->
<!-- ============================================================ -->
<div id="registerTankModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-medium"></i>
                Register Septic Tank
            </h3>
            <button onclick="closeModal('registerTankModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="registerTankForm" class="p-6 space-y-4" onsubmit="saveTankRegistration(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label>
                <input type="text" id="tank_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="tank_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label>
                <select id="tank_barangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Barangay San Jose">Barangay San Jose</option>
                    <option value="Barangay Poblacion">Barangay Poblacion</option>
                    <option value="Barangay Riverside">Barangay Riverside</option>
                    <option value="Barangay San Roque">Barangay San Roque</option>
                    <option value="Barangay Sta. Cruz">Barangay Sta. Cruz</option>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Capacity</label>
                    <select id="tank_capacity" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="500L">500L</option>
                        <option value="800L">800L</option>
                        <option value="1000L">1000L</option>
                        <option value="1200L" selected>1200L</option>
                        <option value="1500L">1500L</option>
                        <option value="2000L">2000L</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Type</label>
                    <select id="tank_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="Concrete">Concrete</option>
                        <option value="Plastic">Plastic</option>
                        <option value="Fiberglass">Fiberglass</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Installation Year</label>
                <input type="number" id="tank_year" min="2000" max="<?php echo date('Y'); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Maintenance Frequency (months)</label>
                    <select id="tank_frequency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="6">6 months</option>
                        <option value="12" selected>12 months</option>
                        <option value="18">18 months</option>
                        <option value="24">24 months</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="tank_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="good">Good</option>
                        <option value="needs_maintenance">Needs Maintenance</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="tank_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('registerTankModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Register
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT TANK MODAL                                              -->
<!-- ============================================================ -->
<div id="editTankModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Septic Tank</h3>
            <button type="button" onclick="closeModal('editTankModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveTankEdit(event)">
            <input type="hidden" id="edit_tank_id">
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label><input type="text" id="edit_tank_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label><input type="text" id="edit_tank_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label><select id="edit_tank_barangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option>Barangay San Jose</option><option>Barangay Poblacion</option><option>Barangay Riverside</option><option>Barangay San Roque</option><option>Barangay Sta. Cruz</option></select></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Capacity</label><select id="edit_tank_capacity" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option>500L</option><option>800L</option><option>1000L</option><option>1200L</option><option>1500L</option><option>2000L</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Type</label><select id="edit_tank_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option>Concrete</option><option>Plastic</option><option>Fiberglass</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Maintenance</label><input type="date" id="edit_tank_maintenance" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Frequency (months)</label><select id="edit_tank_frequency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="6">6 months</option><option value="12">12 months</option><option value="18">18 months</option><option value="24">24 months</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_tank_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="good">Good</option><option value="needs_maintenance">Needs Maintenance</option><option value="critical">Critical</option></select></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_tank_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editTankModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW TANK MODAL                                              -->
<!-- ============================================================ -->
<div id="viewTankModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Septic Tank Details</h3>
            <button onclick="closeModal('viewTankModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="tankDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- HISTORY MODAL                                                -->
<!-- ============================================================ -->
<div id="historyModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-brand-medium"></i>
                Tank History
            </h3>
            <button onclick="closeModal('historyModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="historyContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MAP MODAL                                                    -->
<!-- ============================================================ -->
<div id="mapModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-map text-brand-medium"></i>
                Location Map
            </h3>
            <button onclick="closeModal('mapModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="mapContent" class="p-6">
            <div class="bg-slate-100 rounded-xl h-80 flex items-center justify-center">
                <div class="text-center text-slate-400">
                    <i class="fa-solid fa-map-location-dot text-4xl block mb-3 text-brand-medium"></i>
                    <p id="mapLocation" class="text-sm font-semibold text-slate-700">Loading location...</p>
                    <p id="mapCoordinates" class="text-xs text-slate-500 mt-1">Latitude: 0.0000, Longitude: 0.0000</p>
                    <div class="mt-4 p-4 bg-white rounded-lg border border-slate-200 inline-block">
                        <i class="fa-solid fa-location-dot text-2xl text-rose-500"></i>
                        <p class="text-xs text-slate-500 mt-1">📍 Location marker</p>
                    </div>
                    <p class="text-xs text-slate-400 mt-4">In production, this would display an interactive map with the exact location.</p>
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
    const TANKS = <?php echo json_encode(array_column($septicTanks, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

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
    // VIEW TANK
    // ============================================================
    function viewTank(id) {
        openModal('viewTankModal');
        const t = TANKS[id];
        if (!t) return;

        setTimeout(() => {
            const statusColors = {
                good: 'bg-emerald-100 text-emerald-700',
                needs_maintenance: 'bg-amber-100 text-amber-700',
                critical: 'bg-rose-100 text-rose-700'
            };
            const historyHtml = t.history.map(h => `
                <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${h.type}</p>
                        <p class="text-xs text-slate-500">${h.notes}</p>
                    </div>
                    <span class="text-xs text-slate-400">${new Date(h.date).toLocaleDateString()}</span>
                </div>
            `).join('');

            document.getElementById('tankDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${t.owner_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${t.owner_name}</h4>
                            <p class="text-sm text-slate-500">${t.tank_id} • ${t.type}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[t.status] || statusColors.good}">
                                ${t.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Address</p><p class="text-sm text-slate-800">${t.address}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Barangay</p><p class="text-sm text-slate-800">${t.barangay}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Capacity</p><p class="text-sm text-slate-800 font-bold">${t.capacity}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Type</p><p class="text-sm text-slate-800">${t.type}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Installed</p><p class="text-sm text-slate-800">${t.installation_year}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Last Maintenance</p><p class="text-sm text-slate-800">${new Date(t.last_maintenance).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Frequency</p><p class="text-sm text-slate-800">Every ${t.maintenance_frequency} months</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Location</p><p class="text-sm text-slate-800 font-mono text-xs">${t.latitude}, ${t.longitude}</p></div>
                    </div>
                    ${t.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${t.notes}</p></div>` : ''}
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">🔄 Maintenance History</h5>
                        <div class="space-y-2">${historyHtml}</div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewTankModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <button onclick="closeModal('viewTankModal'); viewMap(${t.latitude}, ${t.longitude}, '${t.owner_name}')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-map mr-1.5"></i> View Map</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // VIEW HISTORY
    // ============================================================
    function viewHistory(id) {
        openModal('historyModal');
        const t = TANKS[id];
        if (!t) return;

        setTimeout(() => {
            const historyHtml = t.history.map(h => `
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${h.type}</p>
                        <p class="text-xs text-slate-500">${h.notes}</p>
                    </div>
                    <span class="text-xs text-slate-400">${new Date(h.date).toLocaleDateString()}</span>
                </div>
            `).join('');

            document.getElementById('historyContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                        <div>
                            <p class="font-semibold text-slate-800 text-sm">${t.owner_name}</p>
                            <p class="text-xs text-slate-400">${t.tank_id}</p>
                        </div>
                        <span class="ml-auto text-xs text-slate-500">${t.history.length} records</span>
                    </div>
                    <div class="space-y-2">${historyHtml}</div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // VIEW MAP
    // ============================================================
    function viewMap(lat, lng, owner) {
        document.getElementById('mapLocation').textContent = owner + '\'s Septic Tank Location';
        document.getElementById('mapCoordinates').textContent = 'Latitude: ' + lat + ', Longitude: ' + lng;
        openModal('mapModal');
    }

    // ============================================================
    // EDIT TANK
    // ============================================================
    function editTank(id) {
        const tank = TANKS[id];
        if (!tank) return;
        document.getElementById('edit_tank_id').value = tank.id;
        document.getElementById('edit_tank_owner').value = tank.owner_name;
        document.getElementById('edit_tank_address').value = tank.address;
        document.getElementById('edit_tank_barangay').value = tank.barangay;
        document.getElementById('edit_tank_capacity').value = tank.capacity;
        document.getElementById('edit_tank_type').value = tank.type;
        document.getElementById('edit_tank_maintenance').value = tank.last_maintenance;
        document.getElementById('edit_tank_frequency').value = tank.maintenance_frequency;
        document.getElementById('edit_tank_status').value = tank.status;
        document.getElementById('edit_tank_notes').value = tank.notes || '';
        openModal('editTankModal');
    }

    function saveTankEdit(event) {
        event.preventDefault();
        const id = document.getElementById('edit_tank_id').value;
        const tank = TANKS[id];
        if (!tank) return;
        tank.owner_name = document.getElementById('edit_tank_owner').value.trim();
        tank.address = document.getElementById('edit_tank_address').value.trim();
        tank.barangay = document.getElementById('edit_tank_barangay').value;
        tank.capacity = document.getElementById('edit_tank_capacity').value;
        tank.type = document.getElementById('edit_tank_type').value;
        tank.last_maintenance = document.getElementById('edit_tank_maintenance').value;
        tank.maintenance_frequency = Number(document.getElementById('edit_tank_frequency').value);
        tank.status = document.getElementById('edit_tank_status').value;
        tank.notes = document.getElementById('edit_tank_notes').value.trim();
        const card = document.querySelector(`.tank-card[data-id="${id}"]`);
        if (card) {
            card.dataset.owner = tank.owner_name.toLowerCase();
            card.dataset.status = tank.status;
            card.dataset.type = tank.type;
            card.dataset.barangay = tank.barangay;
            card.dataset.maintenanceDate = tank.last_maintenance;
            const owner = card.querySelector('.font-semibold.text-slate-800.text-sm');
            if (owner) owner.textContent = tank.owner_name;
        }
        closeModal('editTankModal');
        showToast('Septic tank updated successfully!', 'success');
        filterTanks();
    }

    // ============================================================
    // REGISTER TANK
    // ============================================================
    function saveTankRegistration(event) {
        event.preventDefault();
        showToast('Septic tank registered successfully!', 'success');
        closeModal('registerTankModal');
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
    document.getElementById('searchTank').addEventListener('input', filterTanks);
    document.getElementById('filterStatus').addEventListener('change', filterTanks);
    document.getElementById('filterType').addEventListener('change', filterTanks);
    document.getElementById('filterBarangay').addEventListener('change', filterTanks);
    document.getElementById('filterDateFrom').addEventListener('change', filterTanks);
    document.getElementById('filterDateTo').addEventListener('change', filterTanks);

    function filterTanks() {
        const search = document.getElementById('searchTank').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const type = document.getElementById('filterType').value;
        const barangay = document.getElementById('filterBarangay').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.tank-card').forEach(card => {
            const owner = card.dataset.owner;
            const id = card.dataset.id.toLowerCase();
            const cardStatus = card.dataset.status;
            const cardType = card.dataset.type;
            const cardBarangay = card.dataset.barangay;
            const maintenanceDate = card.dataset.maintenanceDate || '';

            const matchesSearch = owner.includes(search) || id.includes(search);
            const matchesStatus = !status || cardStatus === status;
            const matchesType = !type || cardType === type;
            const matchesBarangay = !barangay || cardBarangay === barangay;
            const matchesDateFrom = !dateFrom || (maintenanceDate && maintenanceDate >= dateFrom);
            const matchesDateTo = !dateTo || (maintenanceDate && maintenanceDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesType && matchesBarangay && matchesDateFrom && matchesDateTo;

            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchTank').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterBarangay').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.tank-card').forEach(card => card.style.display = '');
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
</script>

<?php include_once '../../includes/footer.php'; ?>