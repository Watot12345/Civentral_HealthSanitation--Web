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

// Sample Service Providers Data
$serviceProviders = [
    [
        'id' => 1,
        'provider_id' => 'PRV-001',
        'name' => 'EcoWaste Services',
        'contact' => '099123456789',
        'email' => 'ecowaste@email.com',
        'address' => '123 Service Rd., Barangay San Jose',
        'license_number' => 'NO8-24-000001',
        'specialization' => 'desludging',
        'rating' => 4.8,
        'status' => 'active',
        'equipment_count' => 5,
        'completed_jobs' => 342,
        'response_time' => '2.3 hrs',
        'certification' => 'DOH Accredited',
        'joined_date' => '2024-01-15',
        'notes' => 'Reliable desludging service provider'
    ],
    [
        'id' => 2,
        'provider_id' => 'PRV-002',
        'name' => 'AquaSafe Solutions',
        'contact' => '099123456788',
        'email' => 'aquasafe@email.com',
        'address' => '456 Maintenance Ave., Barangay Poblacion',
        'license_number' => 'NO8-24-000002',
        'specialization' => 'maintenance',
        'rating' => 4.5,
        'status' => 'active',
        'equipment_count' => 3,
        'completed_jobs' => 215,
        'response_time' => '3.1 hrs',
        'certification' => 'DENR Approved',
        'joined_date' => '2024-03-20',
        'notes' => 'Specializes in tank maintenance'
    ],
    [
        'id' => 3,
        'provider_id' => 'PRV-003',
        'name' => 'PipePro Services',
        'contact' => '099123456787',
        'email' => 'pipepro@email.com',
        'address' => '789 Inspection St., Barangay Riverside',
        'license_number' => 'NO8-24-000003',
        'specialization' => 'inspection',
        'rating' => 4.9,
        'status' => 'active',
        'equipment_count' => 4,
        'completed_jobs' => 156,
        'response_time' => '1.8 hrs',
        'certification' => 'ISO Certified',
        'joined_date' => '2024-06-01',
        'notes' => 'Top-rated inspection service'
    ],
    [
        'id' => 4,
        'provider_id' => 'PRV-004',
        'name' => 'InstallAll Tech',
        'contact' => '099123456786',
        'email' => 'installall@email.com',
        'address' => '101 Installation Rd., Barangay San Roque',
        'license_number' => 'NO8-24-000004',
        'specialization' => 'installation',
        'rating' => 4.3,
        'status' => 'active',
        'equipment_count' => 6,
        'completed_jobs' => 98,
        'response_time' => '4.2 hrs',
        'certification' => 'PCAB Registered',
        'joined_date' => '2024-08-10',
        'notes' => 'New tank installation specialists'
    ],
    [
        'id' => 5,
        'provider_id' => 'PRV-005',
        'name' => 'CleanFlow Services',
        'contact' => '099123456785',
        'email' => 'cleanflow@email.com',
        'address' => '202 Cleanup St., Barangay Sta. Cruz',
        'license_number' => 'NO8-24-000005',
        'specialization' => 'desludging',
        'rating' => 4.6,
        'status' => 'inactive',
        'equipment_count' => 2,
        'completed_jobs' => 67,
        'response_time' => '5.0 hrs',
        'certification' => 'DOH Accredited',
        'joined_date' => '2024-09-05',
        'notes' => 'Currently on leave - equipment maintenance'
    ],
    [
        'id' => 6,
        'provider_id' => 'PRV-006',
        'name' => 'HydroTech Solutions',
        'contact' => '099123456784',
        'email' => 'hydrotech@email.com',
        'address' => '303 Tech Park, Barangay San Jose',
        'license_number' => 'NO8-24-000006',
        'specialization' => 'maintenance',
        'rating' => 4.7,
        'status' => 'active',
        'equipment_count' => 4,
        'completed_jobs' => 189,
        'response_time' => '2.5 hrs',
        'certification' => 'DENR Approved',
        'joined_date' => '2024-10-15',
        'notes' => 'Full-service maintenance provider'
    ],
];

// Sample Equipment Inventory
$equipmentInventory = [
    ['id' => 1, 'name' => 'Vacuum Truck', 'type' => 'Vehicle', 'provider_id' => 1, 'status' => 'available', 'capacity' => '2000L', 'license_plate' => 'ABC-1234'],
    ['id' => 2, 'name' => 'High-Pressure Pump', 'type' => 'Equipment', 'provider_id' => 1, 'status' => 'in_use', 'capacity' => '1500PSI', 'license_plate' => null],
    ['id' => 3, 'name' => 'Inspection Camera', 'type' => 'Equipment', 'provider_id' => 3, 'status' => 'available', 'capacity' => 'HD 1080p', 'license_plate' => null],
    ['id' => 4, 'name' => 'Excavator', 'type' => 'Vehicle', 'provider_id' => 4, 'status' => 'in_use', 'capacity' => '2.5 tons', 'license_plate' => 'XYZ-5678'],
    ['id' => 5, 'name' => 'Desludging Pump', 'type' => 'Equipment', 'provider_id' => 2, 'status' => 'available', 'capacity' => '3000L/hr', 'license_plate' => null],
    ['id' => 6, 'name' => 'Transport Truck', 'type' => 'Vehicle', 'provider_id' => 4, 'status' => 'available', 'capacity' => '5 tons', 'license_plate' => 'DEF-9012'],
    ['id' => 7, 'name' => 'Water Jet Machine', 'type' => 'Equipment', 'provider_id' => 2, 'status' => 'maintenance', 'capacity' => '2000PSI', 'license_plate' => null],
];

// Stats
$totalProviders = count($serviceProviders);
$activeProviders = count(array_filter($serviceProviders, fn($p) => $p['status'] === 'active'));
$inactiveProviders = count(array_filter($serviceProviders, fn($p) => $p['status'] === 'inactive'));
$totalEquipment = count($equipmentInventory);
$avgRating = array_sum(array_column($serviceProviders, 'rating')) / $totalProviders;
$totalJobs = array_sum(array_column($serviceProviders, 'completed_jobs'));

$title = 'Service Providers';
?>

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
            <button onclick="openModal('registerProviderModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-user-plus text-xs"></i> Register Provider
            </button>
            <button onclick="openModal('equipmentManagementModal')"
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
             data-name="<?php echo strtolower($provider['name']); ?>"
             data-id="<?php echo $provider['provider_id']; ?>"
             data-status="<?php echo $provider['status']; ?>"
             data-specialization="<?php echo $provider['specialization']; ?>"
             data-rating="<?php echo $provider['rating']; ?>"
             data-contact="<?php echo htmlspecialchars($provider['contact']); ?>"
             data-joined-date="<?php echo $provider['joined_date']; ?>">
            
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
            <div class="mt-3 pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button onclick="viewProvider(<?php echo $provider['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-brand-medium hover:bg-brand-light rounded-lg transition">
                    <i class="fa-solid fa-eye mr-1"></i> View
                </button>
                <button onclick="assignProvider(<?php echo $provider['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition">
                    <i class="fa-solid fa-user-check mr-1"></i> Assign
                </button>
                <button onclick="editProvider(<?php echo $provider['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-lg transition">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
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
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider Name</label>
                <input type="text" id="prov_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                    <input type="text" id="prov_contact" inputmode="numeric" maxlength="12" oninput="limitProviderContact(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
<!-- VIEW PROVIDER MODAL                                          -->
<!-- ============================================================ -->
<div id="editProviderModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Service Provider</h3>
            <button type="button" onclick="closeModal('editProviderModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveProviderEdit(event)">
            <input type="hidden" id="edit_provider_id">
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider Name</label><input type="text" id="edit_provider_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact (12 digits)</label><input type="text" id="edit_provider_contact" inputmode="numeric" maxlength="12" oninput="limitProviderContact(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
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
            <input type="hidden" id="assign_provider_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider</label>
                <input type="text" id="assign_provider_name" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 outline-none cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assignment Type</label>
                <select id="assign_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="service_request">Service Request</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="emergency">Emergency</option>
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
        <div class="p-6">
            <div class="mb-4 flex justify-between items-center">
                <h4 class="text-sm font-bold text-slate-700">Equipment Inventory</h4>
                <button onclick="openModal('addEquipmentModal')" class="px-3 py-1.5 text-xs font-semibold text-white bg-brand-dark rounded-lg hover:bg-brand-medium transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Equipment
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($equipmentInventory as $eq): ?>
                <div class="bg-white rounded-xl shadow-xs p-3 border border-slate-200 hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-800 text-sm"><?php echo $eq['name']; ?></p>
                            <p class="text-xs text-slate-400"><?php echo $eq['type']; ?></p>
                        </div>
                        <?php
                            $eqStatusColors = [
                                'available' => 'bg-emerald-100 text-emerald-700',
                                'in_use' => 'bg-amber-100 text-amber-700',
                                'maintenance' => 'bg-rose-100 text-rose-700'
                            ];
                        ?>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $eqStatusColors[$eq['status']] ?? 'bg-slate-100 text-slate-500'; ?>">
                            <?php echo str_replace('_', ' ', ucfirst($eq['status'])); ?>
                        </span>
                    </div>
                    <div class="mt-2 flex justify-between text-xs text-slate-500">
                        <span>Capacity: <?php echo $eq['capacity']; ?></span>
                        <span>ID: <?php echo $eq['id']; ?></span>
                    </div>
                    <?php if ($eq['license_plate']): ?>
                    <div class="text-xs text-slate-400 mt-1">Plate: <?php echo $eq['license_plate']; ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
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
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
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

            document.getElementById('providerDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${p.name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${p.name}</h4>
                            <p class="text-sm text-slate-500">${p.provider_id} • ${p.specialization}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[p.status] || statusColors.active}">
                                ${p.status.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Contact</p><p class="text-sm text-slate-800">${p.contact}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Email</p><p class="text-sm text-slate-800">${p.email}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Address</p><p class="text-sm text-slate-800">${p.address}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">License</p><p class="text-sm text-slate-800">${p.license_number}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Rating</p><p class="text-sm text-amber-500">${p.rating} ⭐</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Equipment</p><p class="text-sm text-slate-800">${p.equipment_count} units</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Completed Jobs</p><p class="text-sm text-slate-800">${p.completed_jobs}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Response Time</p><p class="text-sm text-slate-800">${p.response_time}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Certification</p><p class="text-sm text-slate-800">${p.certification}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Joined</p><p class="text-sm text-slate-800">${new Date(p.joined_date).toLocaleDateString()}</p></div>
                    </div>
                    ${p.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${p.notes}</p></div>` : ''}
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

    function saveProviderAssignment(event) {
        event.preventDefault();
        showToast('Provider assigned successfully!', 'success');
        closeModal('assignProviderModal');
    }

    // ============================================================
    // EDIT PROVIDER
    // ============================================================
    function limitProviderContact(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 12);
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

    function isValidProviderData(contact, license, equipment) {
        return /^\d{12}$/.test(contact) && /^[A-Z0-9]{3}-\d{2}-\d{6}$/.test(license) && /^\d{1,11}$/.test(equipment);
    }

    function editProvider(id) {
        const provider = PROVIDERS[id];
        if (!provider) return;
        document.getElementById('edit_provider_id').value = provider.id;
        document.getElementById('edit_provider_name').value = provider.name;
        document.getElementById('edit_provider_contact').value = provider.contact;
        document.getElementById('edit_provider_email').value = provider.email;
        document.getElementById('edit_provider_address').value = provider.address;
        document.getElementById('edit_provider_license').value = provider.license_number;
        document.getElementById('edit_provider_equipment').value = provider.equipment_count;
        document.getElementById('edit_provider_joined').value = provider.joined_date;
        document.getElementById('edit_provider_status').value = provider.status;
        document.getElementById('edit_provider_notes').value = provider.notes || '';
        openModal('editProviderModal');
    }

    function saveProviderEdit(event) {
        event.preventDefault();
        const contact = document.getElementById('edit_provider_contact').value;
        const license = document.getElementById('edit_provider_license').value;
        const equipment = document.getElementById('edit_provider_equipment').value;
        if (!isValidProviderData(contact, license, equipment)) {
            showToast('Contact must be 12 digits, license must use 3-2-6 format, and equipment count must be up to 11 digits.', 'warning');
            return;
        }
        const id = document.getElementById('edit_provider_id').value;
        const provider = PROVIDERS[id];
        if (!provider) return;
        provider.name = document.getElementById('edit_provider_name').value.trim();
        provider.contact = contact;
        provider.email = document.getElementById('edit_provider_email').value.trim();
        provider.address = document.getElementById('edit_provider_address').value.trim();
        provider.license_number = license;
        provider.equipment_count = Number(equipment);
        provider.joined_date = document.getElementById('edit_provider_joined').value;
        provider.status = document.getElementById('edit_provider_status').value;
        provider.notes = document.getElementById('edit_provider_notes').value.trim();
        const card = document.querySelector(`.provider-card[data-id="${provider.provider_id}"]`);
        if (card) {
            card.dataset.name = provider.name.toLowerCase();
            card.dataset.status = provider.status;
            card.dataset.joinedDate = provider.joined_date;
            const name = card.querySelector('.font-semibold.text-slate-800.text-sm');
            if (name) name.textContent = provider.name;
        }
        closeModal('editProviderModal');
        showToast('Service provider updated successfully!', 'success');
        filterProviders();
    }

    // ============================================================
    // REGISTER PROVIDER
    // ============================================================
    function saveProviderRegistration(event) {
        event.preventDefault();
        if (!isValidProviderData(
            document.getElementById('prov_contact').value,
            document.getElementById('prov_license').value,
            document.getElementById('prov_equipment').value || '0'
        )) {
            showToast('Contact must be 12 digits, license must use 3-2-6 format, and equipment count must be up to 11 digits.', 'warning');
            return;
        }
        showToast('Service provider registered successfully!', 'success');
        closeModal('registerProviderModal');
    }

    // ============================================================
    // EQUIPMENT MANAGEMENT
    // ============================================================
    function saveEquipment(event) {
        event.preventDefault();
        showToast('Equipment added successfully!', 'success');
        closeModal('addEquipmentModal');
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