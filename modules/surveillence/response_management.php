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
requireDepartmentAccess('health surveillance');

// Response Teams
$responseTeams = [
    [
        'id' => 'RT-001',
        'name' => 'Rapid Response Team Alpha',
        'leader' => 'Dr. Maria Reyes',
        'members' => ['Nurse John Santos', 'Med Tech Anna Cruz', 'Sanitation Officer Ben Chen', 'Epidemiologist Dr. Lee'],
        'specialization' => 'Outbreak Control',
        'status' => 'Available',
        'deployed_to' => null,
        'last_deployment' => '2024-01-10',
        'contact' => '+63 912 345 6789'
    ],
    [
        'id' => 'RT-002',
        'name' => 'Rapid Response Team Bravo',
        'leader' => 'Dr. Carlos Garcia',
        'members' => ['Nurse Maria Lopez', 'Med Tech Jose Ramos', 'Sanitation Officer Ana Tan', 'Epidemiologist Dr. Wu'],
        'specialization' => 'Emergency Response',
        'status' => 'Deployed',
        'deployed_to' => 'San Jose',
        'last_deployment' => '2024-01-15',
        'contact' => '+63 912 345 6790'
    ],
    [
        'id' => 'RT-003',
        'name' => 'Surveillance & Monitoring Team',
        'leader' => 'Dr. Sofia Santos',
        'members' => ['Epidemiologist Mark Lim', 'Data Analyst Jane Cruz', 'Field Worker Pedro Gomez', 'Nurse Rosa Perez'],
        'specialization' => 'Data Collection & Analysis',
        'status' => 'Available',
        'deployed_to' => null,
        'last_deployment' => '2024-01-12',
        'contact' => '+63 912 345 6791'
    ],
    [
        'id' => 'RT-004',
        'name' => 'Community Health Team',
        'leader' => 'Dr. Elena Rivera',
        'members' => ['Health Worker Mario Santos', 'Nurse Lisa Tan', 'Social Worker Ana Reyes', 'Barangay Health Worker Juan Cruz'],
        'specialization' => 'Community Engagement',
        'status' => 'Standby',
        'deployed_to' => null,
        'last_deployment' => '2024-01-08',
        'contact' => '+63 912 345 6792'
    ],
    [
        'id' => 'RT-005',
        'name' => 'Medical Logistics Team',
        'leader' => 'Dr. Ramon Velasco',
        'members' => ['Logistics Officer Mark Reyes', 'Supply Chain Anna Santos', 'Pharmacist Jose Cruz', 'Driver Ben Tan'],
        'specialization' => 'Logistics & Supply',
        'status' => 'Available',
        'deployed_to' => null,
        'last_deployment' => '2024-01-14',
        'contact' => '+63 912 345 6793'
    ],
];

// Resources Inventory
$resources = [
    [
        'id' => 'RES-001',
        'name' => 'Medical Supplies Kit',
        'category' => 'Medical Supplies',
        'quantity' => 150,
        'unit' => 'kits',
        'location' => 'Main Warehouse',
        'status' => 'Available',
        'last_restock' => '2024-01-10',
        'threshold' => 50
    ],
    [
        'id' => 'RES-002',
        'name' => 'PPE Sets',
        'category' => 'Protective Equipment',
        'quantity' => 500,
        'unit' => 'sets',
        'location' => 'Main Warehouse',
        'status' => 'Available',
        'last_restock' => '2024-01-12',
        'threshold' => 100
    ],
    [
        'id' => 'RES-003',
        'name' => 'Testing Kits - Dengue',
        'category' => 'Testing Kits',
        'quantity' => 200,
        'unit' => 'kits',
        'location' => 'Lab Facility',
        'status' => 'Available',
        'last_restock' => '2024-01-14',
        'threshold' => 50
    ],
    [
        'id' => 'RES-004',
        'name' => 'Testing Kits - Influenza',
        'category' => 'Testing Kits',
        'quantity' => 180,
        'unit' => 'kits',
        'location' => 'Lab Facility',
        'status' => 'Available',
        'last_restock' => '2024-01-13',
        'threshold' => 40
    ],
    [
        'id' => 'RES-005',
        'name' => 'Antiviral Medications',
        'category' => 'Medications',
        'quantity' => 300,
        'unit' => 'doses',
        'location' => 'Pharmacy',
        'status' => 'Low Stock',
        'last_restock' => '2024-01-08',
        'threshold' => 100
    ],
    [
        'id' => 'RES-006',
        'name' => 'Disinfectants',
        'category' => 'Sanitation',
        'quantity' => 100,
        'unit' => 'gallons',
        'location' => 'Warehouse B',
        'status' => 'Available',
        'last_restock' => '2024-01-15',
        'threshold' => 30
    ],
    [
        'id' => 'RES-007',
        'name' => 'Vehicles',
        'category' => 'Transport',
        'quantity' => 8,
        'unit' => 'units',
        'location' => 'Fleet Garage',
        'status' => 'Available',
        'last_restock' => '2024-01-01',
        'threshold' => 3
    ],
];

// Active Interventions
$interventions = [
    [
        'id' => 'INT-001',
        'title' => 'Dengue Outbreak Response - San Jose',
        'type' => 'Outbreak Response',
        'location' => 'San Jose',
        'status' => 'Active',
        'start_date' => '2024-01-15',
        'end_date' => '2024-02-15',
        'team_lead' => 'Dr. Maria Reyes',
        'progress' => 65,
        'activities' => ['Fogging Operations', 'Contact Tracing', 'Community Education', 'Medical Checkups'],
        'resources_used' => ['PPE Sets', 'Testing Kits - Dengue', 'Disinfectants'],
        'outcomes' => ['32 cases identified', '15 patients treated', '85% coverage achieved']
    ],
    [
        'id' => 'INT-002',
        'title' => 'Influenza Containment - Poblacion',
        'type' => 'Containment',
        'location' => 'Poblacion',
        'status' => 'Active',
        'start_date' => '2024-01-16',
        'end_date' => '2024-02-16',
        'team_lead' => 'Dr. Carlos Garcia',
        'progress' => 45,
        'activities' => ['Isolation Protocol', 'Antiviral Distribution', 'School Monitoring', 'Public Awareness'],
        'resources_used' => ['Antiviral Medications', 'PPE Sets', 'Testing Kits - Influenza'],
        'outcomes' => ['20 cases identified', '8 patients treated', '60% coverage achieved']
    ],
    [
        'id' => 'INT-003',
        'title' => 'Leptospirosis Prevention - Riverside',
        'type' => 'Prevention',
        'location' => 'Riverside',
        'status' => 'Active',
        'start_date' => '2024-01-17',
        'end_date' => '2024-02-17',
        'team_lead' => 'Dr. Sofia Santos',
        'progress' => 30,
        'activities' => ['Flood Control', 'Water Testing', 'Health Education', 'Medical Checkups'],
        'resources_used' => ['Disinfectants', 'PPE Sets', 'Medical Supplies Kit'],
        'outcomes' => ['5 cases identified', '45 households reached', '40% coverage achieved']
    ],
    [
        'id' => 'INT-004',
        'title' => 'Community Health Outreach - Bagong Silang',
        'type' => 'Health Outreach',
        'location' => 'Bagong Silang',
        'status' => 'Completed',
        'start_date' => '2024-01-05',
        'end_date' => '2024-01-20',
        'team_lead' => 'Dr. Elena Rivera',
        'progress' => 100,
        'activities' => ['Health Education', 'Screening', 'Vaccination Drive', 'Nutrition Assessment'],
        'resources_used' => ['Medical Supplies Kit', 'PPE Sets'],
        'outcomes' => ['200 patients screened', '50 vaccinated', '95% satisfaction rate']
    ],
];

// Effectiveness Metrics
$effectivenessMetrics = [
    'response_time_avg' => 45, // minutes
    'containment_rate' => 78, // percentage
    'recovery_rate' => 82, // percentage
    'community_coverage' => 65, // percentage
    'resource_utilization' => 72, // percentage
];

// Statistics
$totalTeams = count($responseTeams);
$availableTeams = count(array_filter($responseTeams, function($t) { return $t['status'] == 'Available'; }));
$deployedTeams = count(array_filter($responseTeams, function($t) { return $t['status'] == 'Deployed'; }));
$activeInterventions = count(array_filter($interventions, function($i) { return $i['status'] == 'Active'; }));
$completedInterventions = count(array_filter($interventions, function($i) { return $i['status'] == 'Completed'; }));

$title = 'Response Management';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Response Management</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-location-dot"></i> Caloocan City
                </span>
                <?php if ($activeInterventions > 0): ?>
                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold flex items-center gap-1 animate-pulse">
                    <i class="fa-solid fa-circle text-[6px]"></i> <?php echo $activeInterventions; ?> Active Interventions
                </span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">Team activation, resource allocation, intervention tracking & effectiveness reporting</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="openModal('activateTeamModal')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-user-plus text-xs"></i> Activate Team
            </button>
            <button onclick="openModal('allocateResourceModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-truck-fast text-xs"></i> Allocate Resources
            </button>
            <button onclick="refreshData()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- KPI CARDS - Response Overview                             -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Teams -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalTeams; ?></p>
                        <p class="text-xs font-medium text-slate-500">Response Teams</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold"><?php echo $availableTeams; ?> Available</span>
                    <span class="text-[10px] text-slate-400"><?php echo $deployedTeams; ?> Deployed</span>
                </div>
            </div>
        </div>

       <!-- Card 2: Active Interventions -->
<div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
    <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
    <div class="relative">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                <i class="fa-solid fa-bolt text-lg"></i>
            </div>
            <div>
                <p class="text-2xl font-black text-amber-600"><?php echo $activeInterventions; ?></p>
                <p class="text-xs font-medium text-slate-500">Active Interventions</p>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-2">
            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">🔄 In Progress</span>
            <span class="text-[10px] text-slate-400"><?php echo $completedInterventions; ?> Completed</span>
        </div>
    </div>
</div>

        <!-- Card 3: Resource Utilization -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-green-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-green-200">
                        <i class="fa-solid fa-boxes text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $effectivenessMetrics['resource_utilization']; ?>%</p>
                        <p class="text-xs font-medium text-slate-500">Resource Utilization</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <div class="flex-1 bg-slate-200 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width: <?php echo $effectivenessMetrics['resource_utilization']; ?>%"></div>
                    </div>
                    <span class="text-[10px] text-slate-400">Efficient</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Effectiveness Score -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-purple-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-purple-200">
                        <i class="fa-solid fa-star text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-purple-600"><?php echo $effectivenessMetrics['containment_rate']; ?>%</p>
                        <p class="text-xs font-medium text-slate-500">Containment Rate</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-bold">📈 <?php echo $effectivenessMetrics['containment_rate'] > 70 ? 'Good' : 'Needs Improvement'; ?></span>
                    <span class="text-[10px] text-slate-400">Target: 80%</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- RESPONSE TEAMS SECTION - Team Activation                  -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-group text-brand-medium"></i>
                Response Teams
                <span class="text-xs font-normal text-slate-400">(<?php echo $totalTeams; ?> teams)</span>
            </h3>
            <div class="flex items-center gap-2">
                <button onclick="filterTeams('all')" class="filter-btn-team active px-3 py-1 text-xs font-semibold rounded-full bg-brand-dark text-white hover:bg-brand-medium transition" id="team-all">All</button>
                <button onclick="filterTeams('Available')" class="filter-btn-team px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition" id="team-available">Available</button>
                <button onclick="filterTeams('Deployed')" class="filter-btn-team px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 transition" id="team-deployed">Deployed</button>
                <button onclick="filterTeams('Standby')" class="filter-btn-team px-3 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-700 hover:bg-slate-200 transition" id="team-standby">Standby</button>
            </div>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="teamsGrid">
                <?php foreach ($responseTeams as $team): 
                    $statusColors = [
                        'Available' => 'bg-emerald-100 text-emerald-700',
                        'Deployed' => 'bg-amber-100 text-amber-700',
                        'Standby' => 'bg-slate-100 text-slate-700'
                    ];
                    $statusBadges = [
                        'Available' => '🟢 Available',
                        'Deployed' => '🟡 Deployed',
                        'Standby' => '⚪ Standby'
                    ];
                ?>
                <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition team-card" data-status="<?php echo $team['status']; ?>">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm"><?php echo $team['name']; ?></h4>
                            <p class="text-xs text-slate-500"><?php echo $team['specialization']; ?></p>
                            <p class="text-xs text-slate-500 mt-1">👨‍⚕️ Lead: <?php echo $team['leader']; ?></p>
                        </div>
                        <span class="px-2 py-0.5 <?php echo $statusColors[$team['status']] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-[10px] font-bold">
                            <?php echo $statusBadges[$team['status']] ?? $team['status']; ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-slate-600">Members: <?php echo implode(', ', array_slice($team['members'], 0, 3)); ?><?php echo count($team['members']) > 3 ? ' +' . (count($team['members']) - 3) . ' more' : ''; ?></p>
                        <?php if ($team['deployed_to']): ?>
                        <p class="text-xs text-amber-600 mt-1">📍 Deployed to: <?php echo $team['deployed_to']; ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-slate-400 mt-1">📞 <?php echo $team['contact']; ?></p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <?php if ($team['status'] == 'Available'): ?>
                        <button onclick="deployTeam('<?php echo $team['id']; ?>')" class="flex-1 px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold">
                            <i class="fa-solid fa-rocket"></i> Deploy
                        </button>
                        <?php endif; ?>
                        <button onclick="viewTeamDetails('<?php echo $team['id']; ?>')" class="flex-1 px-3 py-1.5 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-xs font-semibold">
                            <i class="fa-solid fa-eye"></i> Details
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- RESOURCE ALLOCATION SECTION                               -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-boxes text-brand-medium"></i>
                Resource Inventory
                <span class="text-xs font-normal text-slate-400">(<?php echo count($resources); ?> items)</span>
            </h3>
            <div class="flex items-center gap-2">
                <select id="resourceCategoryFilter" onchange="filterResources()" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="all">All Categories</option>
                    <?php 
                    $categories = array_unique(array_column($resources, 'category'));
                    foreach ($categories as $category): 
                    ?>
                    <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="resourceStatusFilter" onchange="filterResources()" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="all">All Status</option>
                    <option value="Available">Available</option>
                    <option value="Low Stock">Low Stock</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Resource</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Category</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Quantity</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Location</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="resourceTableBody">
                    <?php foreach ($resources as $resource): ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition resource-row" data-category="<?php echo $resource['category']; ?>" data-status="<?php echo $resource['status']; ?>">
                        <td class="px-4 py-3">
                            <div>
                                <span class="font-medium text-slate-800"><?php echo $resource['name']; ?></span>
                                <span class="text-xs text-slate-400 block"><?php echo $resource['id']; ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium"><?php echo $resource['category']; ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-semibold <?php echo $resource['quantity'] < $resource['threshold'] ? 'text-red-600' : 'text-slate-700'; ?>">
                                <?php echo $resource['quantity']; ?>
                            </span>
                            <span class="text-xs text-slate-400"><?php echo $resource['unit']; ?></span>
                            <?php if ($resource['quantity'] < $resource['threshold']): ?>
                            <span class="text-[10px] text-red-500 block">Below threshold</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $resource['location']; ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 <?php echo $resource['status'] == 'Available' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?> rounded-full text-xs font-semibold">
                                <?php echo $resource['status']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button onclick="allocateResource('<?php echo $resource['id']; ?>')" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition">
                                <i class="fa-solid fa-truck-fast"></i> Allocate
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- INTERVENTION TRACKING SECTION                             -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-tasks text-brand-medium"></i>
                Intervention Tracking
                <span class="text-xs font-normal text-slate-400">(<?php echo count($interventions); ?> interventions)</span>
            </h3>
            <div class="flex items-center gap-2">
                <button onclick="filterInterventions('all')" class="filter-btn-int active px-3 py-1 text-xs font-semibold rounded-full bg-brand-dark text-white hover:bg-brand-medium transition" id="int-all">All</button>
                <button onclick="filterInterventions('Active')" class="filter-btn-int px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 transition" id="int-active">Active</button>
                <button onclick="filterInterventions('Completed')" class="filter-btn-int px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition" id="int-completed">Completed</button>
            </div>
        </div>
        <div class="p-4">
            <div class="space-y-4" id="interventionsList">
                <?php foreach ($interventions as $intervention): 
                    $statusColors = [
                        'Active' => 'bg-amber-100 text-amber-700 border-amber-500',
                        'Completed' => 'bg-emerald-100 text-emerald-700 border-emerald-500'
                    ];
                    $progressColors = [
                        'Active' => 'bg-amber-500',
                        'Completed' => 'bg-emerald-500'
                    ];
                ?>
                <div class="border-l-4 <?php echo $statusColors[$intervention['status']] ?? 'bg-slate-100 text-slate-700 border-slate-500'; ?> rounded-lg p-4 hover:shadow-md transition intervention-item" data-status="<?php echo $intervention['status']; ?>">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-slate-800"><?php echo $intervention['title']; ?></h4>
                                <span class="px-2 py-0.5 <?php echo $statusColors[$intervention['status']] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-[10px] font-semibold">
                                    <?php echo $intervention['status']; ?>
                                </span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-600">
                                <span>📍 <?php echo $intervention['location']; ?></span>
                                <span>👨‍⚕️ <?php echo $intervention['team_lead']; ?></span>
                                <span>📅 <?php echo date('M d', strtotime($intervention['start_date'])); ?> - <?php echo date('M d', strtotime($intervention['end_date'])); ?></span>
                                <span>🏷️ <?php echo $intervention['type']; ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="text-sm font-bold text-slate-700"><?php echo $intervention['progress']; ?>%</span>
                                <div class="w-24 bg-slate-200 rounded-full h-1.5">
                                    <div class="<?php echo $progressColors[$intervention['status']] ?? 'bg-slate-500'; ?> h-1.5 rounded-full" style="width: <?php echo $intervention['progress']; ?>%"></div>
                                </div>
                            </div>
                            <button onclick="viewInterventionDetails('<?php echo $intervention['id']; ?>')" class="px-3 py-1.5 bg-brand-light text-brand-dark rounded-lg hover:bg-brand-dark hover:text-white transition text-xs font-semibold">
                                <i class="fa-solid fa-eye"></i> View
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php foreach ($intervention['activities'] as $activity): ?>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px]">
                            <i class="fa-solid fa-check-circle text-[8px] text-emerald-500"></i> <?php echo $activity; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($intervention['status'] == 'Completed'): ?>
                    <div class="mt-2 p-2 bg-emerald-50 rounded-lg">
                        <p class="text-xs text-emerald-700">✅ <?php echo implode(' • ', $intervention['outcomes']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- EFFECTIVENESS REPORTS SECTION                             -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-chart-pie text-brand-medium"></i>
                Effectiveness Reports
                <span class="text-xs font-normal text-slate-400">(Performance metrics)</span>
            </h3>
            <button onclick="generateReport()" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-file-pdf"></i> Generate Full Report
            </button>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Metric 1: Response Time -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 mx-auto mb-2">
                        <i class="fa-solid fa-clock text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900"><?php echo $effectivenessMetrics['response_time_avg']; ?><span class="text-sm text-slate-400">m</span></p>
                    <p class="text-xs font-medium text-slate-500">Avg Response Time</p>
                    <span class="text-[10px] <?php echo $effectivenessMetrics['response_time_avg'] < 60 ? 'text-emerald-600' : 'text-amber-600'; ?> font-semibold">
                        <?php echo $effectivenessMetrics['response_time_avg'] < 60 ? '✅ Within target' : '⚠️ Needs improvement'; ?>
                    </span>
                </div>

                <!-- Metric 2: Containment Rate -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center text-amber-600 mx-auto mb-2">
                        <i class="fa-solid fa-shield-halved text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900"><?php echo $effectivenessMetrics['containment_rate']; ?>%</p>
                    <p class="text-xs font-medium text-slate-500">Containment Rate</p>
                    <span class="text-[10px] <?php echo $effectivenessMetrics['containment_rate'] > 70 ? 'text-emerald-600' : 'text-amber-600'; ?> font-semibold">
                        <?php echo $effectivenessMetrics['containment_rate'] > 70 ? '✅ Good' : '⚠️ Needs improvement'; ?>
                    </span>
                </div>

                <!-- Metric 3: Recovery Rate -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 mx-auto mb-2">
                        <i class="fa-solid fa-heart-pulse text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900"><?php echo $effectivenessMetrics['recovery_rate']; ?>%</p>
                    <p class="text-xs font-medium text-slate-500">Recovery Rate</p>
                    <span class="text-[10px] text-emerald-600 font-semibold">✅ Good</span>
                </div>

                <!-- Metric 4: Community Coverage -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 mx-auto mb-2">
                        <i class="fa-solid fa-people-group text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900"><?php echo $effectivenessMetrics['community_coverage']; ?>%</p>
                    <p class="text-xs font-medium text-slate-500">Community Coverage</p>
                    <span class="text-[10px] <?php echo $effectivenessMetrics['community_coverage'] > 60 ? 'text-emerald-600' : 'text-amber-600'; ?> font-semibold">
                        <?php echo $effectivenessMetrics['community_coverage'] > 60 ? '✅ Good' : '⚠️ Needs improvement'; ?>
                    </span>
                </div>

                <!-- Metric 5: Overall Score -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-dark mx-auto mb-2">
                        <i class="fa-solid fa-star text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-brand-dark"><?php echo round(($effectivenessMetrics['containment_rate'] + $effectivenessMetrics['recovery_rate'] + $effectivenessMetrics['community_coverage']) / 3); ?>%</p>
                    <p class="text-xs font-medium text-slate-500">Overall Score</p>
                    <span class="text-[10px] text-emerald-600 font-semibold">✅ Effective</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ACTIVATE TEAM MODAL                                        -->
<!-- ============================================================ -->
<div id="activateTeamModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-brand-medium"></i>
                Activate Response Team
            </h3>
            <button onclick="closeModal('activateTeamModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form onsubmit="activateTeam(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Team</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <?php foreach ($responseTeams as $team): ?>
                            <option value="<?php echo $team['id']; ?>"><?php echo $team['name']; ?> (<?php echo $team['status']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Deployment Location</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option>San Jose</option>
                            <option>Poblacion</option>
                            <option>Riverside</option>
                            <option>San Antonio</option>
                            <option>Bagong Silang</option>
                            <option>Mabini</option>
                            <option>Kaybiga</option>
                            <option>Bagumbong</option>
                            <option>Camarin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Activation Reason</label>
                        <textarea rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Describe the situation requiring team activation..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority Level</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="High">🚨 High Priority</option>
                            <option value="Medium">⚠️ Medium Priority</option>
                            <option value="Low">ℹ️ Low Priority</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeModal('activateTeamModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-rocket mr-1.5"></i> Activate Team
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ALLOCATE RESOURCE MODAL                                   -->
<!-- ============================================================ -->
<div id="allocateResourceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-truck-fast text-brand-medium"></i>
                Allocate Resources
            </h3>
            <button onclick="closeModal('allocateResourceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form onsubmit="allocateResourceSubmit(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Resource</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <?php foreach ($resources as $resource): ?>
                            <option value="<?php echo $resource['id']; ?>"><?php echo $resource['name']; ?> (<?php echo $resource['quantity']; ?> <?php echo $resource['unit']; ?> available)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Quantity to Allocate</label>
                        <input type="number" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Enter quantity" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Destination / Barangay</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option>San Jose</option>
                            <option>Poblacion</option>
                            <option>Riverside</option>
                            <option>San Antonio</option>
                            <option>Bagong Silang</option>
                            <option>Mabini</option>
                            <option>Kaybiga</option>
                            <option>Bagumbong</option>
                            <option>Camarin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Purpose</label>
                        <input type="text" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g., Outbreak response, Medical mission">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority Level</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="High">🚨 High Priority</option>
                            <option value="Medium">⚠️ Medium Priority</option>
                            <option value="Low">ℹ️ Low Priority</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeModal('allocateResourceModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-check mr-1.5"></i> Allocate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- INTERVENTION DETAILS MODAL                                 -->
<!-- ============================================================ -->
<div id="interventionDetailsModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-tasks text-brand-medium"></i>
                Intervention Details
            </h3>
            <button onclick="closeModal('interventionDetailsModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6" id="interventionDetailsContent">
            <!-- Dynamic content loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<script>
    // PHP Data to JavaScript
    const INTERVENTIONS = <?php echo json_encode($interventions); ?>;
    const TEAMS = <?php echo json_encode($responseTeams); ?>;
    const RESOURCES = <?php echo json_encode($resources); ?>;

    // ============================================================
    // FILTER TEAMS
    // ============================================================
    function filterTeams(status) {
        document.querySelectorAll('.filter-btn-team').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-dark', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700');
        });
        
        if (status === 'all') {
            document.getElementById('team-all').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Available') {
            document.getElementById('team-available').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Deployed') {
            document.getElementById('team-deployed').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Standby') {
            document.getElementById('team-standby').classList.add('active', 'bg-brand-dark', 'text-white');
        }
        
        const cards = document.querySelectorAll('.team-card');
        cards.forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // ============================================================
    // FILTER RESOURCES
    // ============================================================
    function filterResources() {
        const categoryFilter = document.getElementById('resourceCategoryFilter').value;
        const statusFilter = document.getElementById('resourceStatusFilter').value;
        
        const rows = document.querySelectorAll('.resource-row');
        rows.forEach(row => {
            const category = row.dataset.category;
            const status = row.dataset.status;
            
            let show = true;
            if (categoryFilter !== 'all' && category !== categoryFilter) show = false;
            if (statusFilter !== 'all' && status !== statusFilter) show = false;
            
            row.style.display = show ? 'table-row' : 'none';
        });
    }

    // ============================================================
    // FILTER INTERVENTIONS
    // ============================================================
    function filterInterventions(status) {
        document.querySelectorAll('.filter-btn-int').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-dark', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700');
        });
        
        if (status === 'all') {
            document.getElementById('int-all').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Active') {
            document.getElementById('int-active').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Completed') {
            document.getElementById('int-completed').classList.add('active', 'bg-brand-dark', 'text-white');
        }
        
        const items = document.querySelectorAll('.intervention-item');
        items.forEach(item => {
            if (status === 'all' || item.dataset.status === status) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // ============================================================
    // DEPLOY TEAM
    // ============================================================
    function deployTeam(teamId) {
        showToast('🚀 Team ' + teamId + ' deployed successfully!', 'success');
    }

    // ============================================================
    // VIEW TEAM DETAILS
    // ============================================================
    function viewTeamDetails(teamId) {
        const team = TEAMS.find(t => t.id === teamId);
        if (team) {
            showToast('👨‍⚕️ Viewing details for ' + team.name, 'info');
        }
    }

    // ============================================================
    // ALLOCATE RESOURCE
    // ============================================================
    function allocateResource(resourceId) {
        openModal('allocateResourceModal');
    }

    function allocateResourceSubmit(e) {
        e.preventDefault();
        showToast('✅ Resources allocated successfully!', 'success');
        closeModal('allocateResourceModal');
    }

    // ============================================================
    // ACTIVATE TEAM
    // ============================================================
    function activateTeam(e) {
        e.preventDefault();
        showToast('✅ Team activated successfully!', 'success');
        closeModal('activateTeamModal');
    }

    // ============================================================
    // VIEW INTERVENTION DETAILS
    // ============================================================
    function viewInterventionDetails(interventionId) {
        const intervention = INTERVENTIONS.find(i => i.id === interventionId);
        const content = document.getElementById('interventionDetailsContent');
        
        if (intervention) {
            content.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-slate-800 text-lg">${intervention.title}</h4>
                        <span class="px-2 py-1 ${intervention.status === 'Active' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'} rounded-full text-xs font-semibold">
                            ${intervention.status}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-slate-500">Type</p>
                            <p class="font-medium text-slate-700">${intervention.type}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Location</p>
                            <p class="font-medium text-slate-700">${intervention.location}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Team Lead</p>
                            <p class="font-medium text-slate-700">${intervention.team_lead}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Progress</p>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-slate-200 rounded-full h-2">
                                    <div class="${intervention.status === 'Active' ? 'bg-amber-500' : 'bg-emerald-500'} h-2 rounded-full" style="width: ${intervention.progress}%"></div>
                                </div>
                                <span class="text-sm font-bold">${intervention.progress}%</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Activities</p>
                        <div class="flex flex-wrap gap-1">
                            ${intervention.activities.map(a => `<span class="px-2 py-1 bg-slate-100 rounded-full text-xs">${a}</span>`).join('')}
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Resources Used</p>
                        <div class="flex flex-wrap gap-1">
                            ${intervention.resources_used.map(r => `<span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">${r}</span>`).join('')}
                        </div>
                    </div>
                    
                    <div class="border-t border-slate-100 pt-3">
                        <p class="text-xs text-slate-500 mb-1">Outcomes</p>
                        ${intervention.outcomes.map(o => `<div class="flex items-center gap-2 text-sm text-slate-700"><i class="fa-solid fa-check-circle text-emerald-500 text-xs"></i> ${o}</div>`).join('')}
                    </div>
                    
                    <div class="flex gap-2 pt-2">
                        <button onclick="closeModal('interventionDetailsModal')" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                            Close
                        </button>
                        <button onclick="updateIntervention('${intervention.id}')" class="flex-1 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                            <i class="fa-solid fa-pen"></i> Update Status
                        </button>
                    </div>
                </div>
            `;
        }
        
        openModal('interventionDetailsModal');
    }

    // ============================================================
    // UPDATE INTERVENTION
    // ============================================================
    function updateIntervention(interventionId) {
        showToast('📊 Updating intervention ' + interventionId, 'info');
    }

    // ============================================================
    // GENERATE REPORT
    // ============================================================
    function generateReport() {
        showToast('📄 Generating effectiveness report...', 'info');
        setTimeout(() => {
            showToast('✅ Report generated successfully!', 'success');
        }, 2000);
    }

    // ============================================================
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('🔄 Refreshing data...', 'info');
        setTimeout(() => {
            showToast('✅ Data refreshed!', 'success');
        }, 1000);
    }

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
    // TOAST
    // ============================================================
    let toastTimer = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-rose-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        t.className = `fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ${colors[type] || colors.success}`;
        t.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.add('hidden'), 3000);
    }

    // ============================================================
    // ESC KEY TO CLOSE MODALS
    // ============================================================
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

<style>
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .filter-btn-team.active,
    .filter-btn-int.active {
        background: #0B4F4A !important;
        color: white !important;
    }
    .filter-btn-team:not(.active):hover,
    .filter-btn-int:not(.active):hover {
        opacity: 0.8;
    }
    
    .team-card, .intervention-item {
        transition: all 0.3s ease;
    }
    .team-card:hover, .intervention-item:hover {
        transform: translateY(-2px);
    }
</style>

<?php include_once '../../includes/footer.php'; ?>