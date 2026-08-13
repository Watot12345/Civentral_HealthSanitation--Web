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

require_once __DIR__ . '/../../app/Models/SurveillanceResponse.php';

try {
    $respModel = new SurveillanceResponse();
    $rawTeams = $respModel->getTeams();
    $rawResources = $respModel->getResources();
    $rawInterventions = $respModel->getInterventions();

    $responseTeams = array_map(function($t) {
        $membersRaw = $t['members'] ?? '';
        $membersArr = is_array($membersRaw) ? $membersRaw : array_map('trim', explode(',', (string)$membersRaw));
        if (empty($membersArr) || (count($membersArr) === 1 && $membersArr[0] === '')) {
            $membersArr = ['Officer ' . ($t['leader'] ?? 'Lead')];
        }
        return [
            'id' => $t['team_code'] ?? ('RT-' . $t['id']),
            'db_id' => (int) $t['id'],
            'name' => $t['name'] ?? 'Response Team',
            'leader' => $t['leader'] ?? 'Team Leader',
            'members' => $membersArr,
            'specialization' => $t['specialization'] ?? 'Epidemiology',
            'status' => $t['status'] ?? 'Available',
            'deployed_to' => $t['deployed_to'] ?? null,
            'last_deployment' => $t['last_deployment'] ?? date('Y-m-d'),
            'contact' => $t['contact'] ?? '0917-000-0000'
        ];
    }, $rawTeams);

    $resources = array_map(function($r) {
        return [
            'id' => $r['resource_code'] ?? ('RES-' . $r['id']),
            'db_id' => (int) $r['id'],
            'name' => $r['name'] ?? 'Resource Item',
            'category' => $r['category'] ?? 'Supplies',
            'quantity' => (int) ($r['quantity'] ?? 0),
            'unit' => $r['unit'] ?? 'pcs',
            'location' => $r['location'] ?? 'Central Warehouse',
            'status' => $r['status'] ?? 'Available',
            'last_restock' => $r['last_restock'] ?? date('Y-m-d'),
            'threshold' => (int) ($r['threshold'] ?? 10)
        ];
    }, $rawResources);

    $interventions = array_map(function($i) {
        $actRaw = $i['activities'] ?? '';
        $actArr = is_array($actRaw) ? $actRaw : array_map('trim', explode(',', (string)$actRaw));
        if (empty($actArr) || (count($actArr) === 1 && $actArr[0] === '')) {
            $actArr = ['Field Intervention', 'Community Health Monitoring'];
        }

        $resRaw = $i['resources_used'] ?? '';
        $resArr = is_array($resRaw) ? $resRaw : array_map('trim', explode(',', (string)$resRaw));

        $outRaw = $i['outcomes'] ?? '';
        $outArr = is_array($outRaw) ? $outRaw : array_map('trim', explode(',', (string)$outRaw));

        return [
            'id' => $i['intervention_code'] ?? ('INT-' . $i['id']),
            'db_id' => (int) $i['id'],
            'title' => $i['title'] ?? 'Intervention Operation',
            'type' => $i['type'] ?? 'Vector Control',
            'location' => $i['location'] ?? 'San Jose',
            'status' => $i['status'] ?? 'In Progress',
            'start_date' => $i['start_date'] ?? date('Y-m-d'),
            'end_date' => $i['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'team_lead' => $i['team_lead'] ?? 'Team Lead',
            'progress' => (int) ($i['progress'] ?? 50),
            'activities' => $actArr,
            'resources_used' => $resArr,
            'outcomes' => $outArr
        ];
    }, $rawInterventions);

} catch (Throwable $e) {
    error_log("Response management fetch error: " . $e->getMessage());
    $responseTeams = [];
    $resources = [];
    $interventions = [];
}


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
            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-rotate"></i> In Progress</span>
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
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-arrow-trend-up"></i> <?php echo $effectivenessMetrics['containment_rate'] > 70 ? 'Good' : 'Needs Improvement'; ?></span>
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
                        'Available' => '<i class="fa-solid fa-circle text-[8px] text-emerald-500"></i> Available',
                        'Deployed' => '<i class="fa-solid fa-circle text-[8px] text-amber-500"></i> Deployed',
                        'Standby' => '<i class="fa-solid fa-circle text-[8px] text-slate-400"></i> Standby'
                    ];
                ?>
                <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition team-card" data-team-id="<?php echo $team['id']; ?>" data-status="<?php echo $team['status']; ?>">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm"><?php echo $team['name']; ?></h4>
                            <p class="text-xs text-slate-500"><?php echo $team['specialization']; ?></p>
                            <p class="text-xs text-slate-500 mt-1"><i class="fa-solid fa-user-doctor"></i> Lead: <?php echo $team['leader']; ?></p>
                        </div>
                        <span class="team-status-badge px-2 py-0.5 <?php echo $statusColors[$team['status']] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-[10px] font-bold">
                            <?php echo $statusBadges[$team['status']] ?? $team['status']; ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-slate-600">Members: <?php echo implode(', ', array_slice($team['members'], 0, 3)); ?><?php echo count($team['members']) > 3 ? ' +' . (count($team['members']) - 3) . ' more' : ''; ?></p>
                        <?php if ($team['deployed_to']): ?>
                        <p class="text-xs text-amber-600 mt-1"><i class="fa-solid fa-location-dot"></i> Deployed to: <?php echo $team['deployed_to']; ?></p>
                        <?php endif; ?>
                        <p class="text-xs text-slate-400 mt-1"><i class="fa-solid fa-phone"></i> <?php echo $team['contact']; ?></p>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <?php if ($team['status'] == 'Available'): ?>
                        <button onclick="deployTeam('<?php echo $team['id']; ?>')" class="deploy-btn flex-1 px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold">
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
                <div class="border-l-4 <?php echo $statusColors[$intervention['status']] ?? 'bg-slate-100 text-slate-700 border-slate-500'; ?> rounded-lg p-4 hover:shadow-md transition intervention-item" data-intervention-id="<?php echo $intervention['id']; ?>" data-status="<?php echo $intervention['status']; ?>">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-slate-800"><?php echo $intervention['title']; ?></h4>
                                <span class="intervention-status-badge px-2 py-0.5 <?php echo $statusColors[$intervention['status']] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-[10px] font-semibold">
                                    <?php echo $intervention['status']; ?>
                                </span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-slate-600">
                                <span><i class="fa-solid fa-location-dot"></i> <?php echo $intervention['location']; ?></span>
                                <span><i class="fa-solid fa-user-doctor"></i> <?php echo $intervention['team_lead']; ?></span>
                                <span><i class="fa-solid fa-calendar-days"></i> <?php echo date('M d', strtotime($intervention['start_date'])); ?> - <?php echo date('M d', strtotime($intervention['end_date'])); ?></span>
                                <span><i class="fa-solid fa-tag"></i> <?php echo $intervention['type']; ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="intervention-progress-label text-sm font-bold text-slate-700"><?php echo $intervention['progress']; ?>%</span>
                                <div class="w-24 bg-slate-200 rounded-full h-1.5">
                                    <div class="intervention-progress-bar <?php echo $progressColors[$intervention['status']] ?? 'bg-slate-500'; ?> h-1.5 rounded-full" style="width: <?php echo $intervention['progress']; ?>%"></div>
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
                        <p class="text-xs text-emerald-700"><i class="fa-solid fa-circle-check"></i> <?php echo implode(' • ', $intervention['outcomes']); ?></p>
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
            <button onclick="openModal('reportModal')" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
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
                        <?php echo $effectivenessMetrics['response_time_avg'] < 60 ? '<i class="fa-solid fa-circle-check"></i> Within target' : '<i class="fa-solid fa-triangle-exclamation"></i> Needs improvement'; ?>
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
                        <?php echo $effectivenessMetrics['containment_rate'] > 70 ? '<i class="fa-solid fa-circle-check"></i> Good' : '<i class="fa-solid fa-triangle-exclamation"></i> Needs improvement'; ?>
                    </span>
                </div>

                <!-- Metric 3: Recovery Rate -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 mx-auto mb-2">
                        <i class="fa-solid fa-heart-pulse text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900"><?php echo $effectivenessMetrics['recovery_rate']; ?>%</p>
                    <p class="text-xs font-medium text-slate-500">Recovery Rate</p>
                    <span class="text-[10px] text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check"></i> Good</span>
                </div>

                <!-- Metric 4: Community Coverage -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 mx-auto mb-2">
                        <i class="fa-solid fa-people-group text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-slate-900"><?php echo $effectivenessMetrics['community_coverage']; ?>%</p>
                    <p class="text-xs font-medium text-slate-500">Community Coverage</p>
                    <span class="text-[10px] <?php echo $effectivenessMetrics['community_coverage'] > 60 ? 'text-emerald-600' : 'text-amber-600'; ?> font-semibold">
                        <?php echo $effectivenessMetrics['community_coverage'] > 60 ? '<i class="fa-solid fa-circle-check"></i> Good' : '<i class="fa-solid fa-triangle-exclamation"></i> Needs improvement'; ?>
                    </span>
                </div>

                <!-- Metric 5: Overall Score -->
                <div class="border border-slate-200 rounded-lg p-4 text-center hover:shadow-md transition">
                    <div class="w-12 h-12 bg-brand-light rounded-full flex items-center justify-center text-brand-dark mx-auto mb-2">
                        <i class="fa-solid fa-star text-xl"></i>
                    </div>
                    <p class="text-2xl font-black text-brand-dark"><?php echo round(($effectivenessMetrics['containment_rate'] + $effectivenessMetrics['recovery_rate'] + $effectivenessMetrics['community_coverage']) / 3); ?>%</p>
                    <p class="text-xs font-medium text-slate-500">Overall Score</p>
                    <span class="text-[10px] text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check"></i> Effective</span>
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
                            <option value="High"><i class="fa-solid fa-triangle-exclamation"></i> High Priority</option>
                            <option value="Medium">Medium Priority</option>
                            <option value="Low">Low Priority</option>
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
                            <option value="High"><i class="fa-solid fa-triangle-exclamation"></i> High Priority</option>
                            <option value="Medium">Medium Priority</option>
                            <option value="Low">Low Priority</option>
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

<!-- ============================================================ -->
<!-- TEAM DETAILS MODAL                                         -->
<!-- ============================================================ -->
<div id="teamDetailsModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-group text-brand-medium"></i>
                Team Details
            </h3>
            <button onclick="closeModal('teamDetailsModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6" id="teamDetailsContent">
            <!-- Dynamic content loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- UPDATE STATUS MODAL                                        -->
<!-- ============================================================ -->
<div id="updateStatusModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen text-brand-medium"></i>
                Update Intervention Status
            </h3>
            <button onclick="closeModal('updateStatusModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form onsubmit="submitStatusUpdate(event)">
                <input type="hidden" id="updateStatusInterventionId">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                        <select id="updateStatusSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Active">Active</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Progress (%)</label>
                        <input id="updateStatusProgress" type="number" min="0" max="100" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="0-100" required oninput="this.value = Math.max(0, Math.min(100, this.value))">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                        <textarea rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Status update notes..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeModal('updateStatusModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-check mr-1.5"></i> Save Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- GENERATE REPORT MODAL                                      -->
<!-- ============================================================ -->
<div id="reportModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-pdf text-brand-medium"></i>
                Generate Full Report
            </h3>
            <button onclick="closeModal('reportModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <button onclick="generateReport('pdf')" class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-sm font-semibold text-slate-700">
                <i class="fa-solid fa-file-pdf text-red-500"></i> Export as PDF
            </button>
            <button onclick="generateReport('docx')" class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-sm font-semibold text-slate-700">
                <i class="fa-solid fa-file-word text-blue-600"></i> Export as DOCX
            </button>
            <button onclick="generateReport('excel')" class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-sm font-semibold text-slate-700">
                <i class="fa-solid fa-file-excel text-emerald-600"></i> Export as CSV (Excel)
            </button>
            <button onclick="closeModal('reportModal')" class="w-full px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                Cancel
            </button>
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
        const team = TEAMS.find(t => t.id === teamId);
        if (!team) {
            showToast('Team not found.', 'danger');
            return;
        }
        if (team.status !== 'Available') {
            showToast(team.name + ' is not currently available for deployment.', 'warning');
            return;
        }

        team.status = 'Deployed';
        team.deployed_to = team.deployed_to || 'San Jose';
        team.last_deployment = new Date().toISOString().slice(0, 10);

        const card = document.querySelector(`.team-card[data-team-id="${teamId}"]`);
        if (card) {
            card.dataset.status = 'Deployed';
            const badge = card.querySelector('.team-status-badge');
            if (badge) {
                badge.className = 'team-status-badge px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold';
                badge.innerHTML = '<i class="fa-solid fa-circle text-[8px] text-amber-500"></i> Deployed';
            }
            const deployBtn = card.querySelector('.deploy-btn');
            if (deployBtn) deployBtn.remove();
        }

        showToast(team.name + ' deployed successfully!', 'success');
    }

    // ============================================================
    // VIEW TEAM DETAILS
    // ============================================================
    function viewTeamDetails(teamId) {
        const team = TEAMS.find(t => t.id === teamId);
        const content = document.getElementById('teamDetailsContent');
        if (!team || !content) {
            showToast('Team not found.', 'danger');
            return;
        }

        content.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="font-bold text-slate-800 text-lg">${team.name}</h4>
                    <span class="px-2 py-1 ${team.status === 'Available' ? 'bg-emerald-100 text-emerald-700' : team.status === 'Deployed' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'} rounded-full text-xs font-semibold">
                        ${team.status}
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-slate-500">Specialization</p>
                        <p class="font-medium text-slate-700">${team.specialization}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Team Leader</p>
                        <p class="font-medium text-slate-700">${team.leader}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Contact</p>
                        <p class="font-medium text-slate-700"><i class="fa-solid fa-phone"></i> ${team.contact}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Last Deployment</p>
                        <p class="font-medium text-slate-700">${new Date(team.last_deployment).toLocaleDateString()}</p>
                    </div>
                    ${team.deployed_to ? `
                    <div class="col-span-2">
                        <p class="text-xs text-slate-500">Deployed To</p>
                        <p class="font-medium text-amber-600"><i class="fa-solid fa-location-dot"></i> ${team.deployed_to}</p>
                    </div>` : ''}
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <p class="text-xs text-slate-500 mb-1">Members</p>
                    <div class="flex flex-wrap gap-1">
                        ${team.members.map(m => `<span class="px-2 py-1 bg-slate-100 rounded-full text-xs">${m}</span>`).join('')}
                    </div>
                </div>
                <div class="flex gap-2 pt-2">
                    <button onclick="closeModal('teamDetailsModal')" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Close
                    </button>
                    ${team.status === 'Available' ? `
                    <button onclick="closeModal('teamDetailsModal'); deployTeam('${team.id}')" class="flex-1 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-rocket"></i> Deploy
                    </button>` : ''}
                </div>
            </div>
        `;

        openModal('teamDetailsModal');
    }

    // ============================================================
    // ALLOCATE RESOURCE
    // ============================================================
    function allocateResource(resourceId) {
        openModal('allocateResourceModal');
    }

    function allocateResourceSubmit(e) {
        e.preventDefault();
        showToast('Resources allocated successfully!', 'success');
        closeModal('allocateResourceModal');
    }

    // ============================================================
    // ACTIVATE TEAM
    // ============================================================
    function activateTeam(e) {
        e.preventDefault();
        showToast('Team activated successfully!', 'success');
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
        const intervention = INTERVENTIONS.find(i => i.id === interventionId);
        if (!intervention) {
            showToast('Intervention not found.', 'danger');
            return;
        }
        document.getElementById('updateStatusInterventionId').value = interventionId;
        document.getElementById('updateStatusSelect').value = intervention.status;
        document.getElementById('updateStatusProgress').value = intervention.progress;
        closeModal('interventionDetailsModal');
        openModal('updateStatusModal');
    }

    function submitStatusUpdate(e) {
        e.preventDefault();
        const interventionId = document.getElementById('updateStatusInterventionId').value;
        const newStatus = document.getElementById('updateStatusSelect').value;
        const newProgress = Math.max(0, Math.min(100, parseInt(document.getElementById('updateStatusProgress').value, 10) || 0));

        const intervention = INTERVENTIONS.find(i => i.id === interventionId);
        if (!intervention) {
            showToast('Intervention not found.', 'danger');
            return;
        }
        intervention.status = newStatus;
        intervention.progress = newProgress;

        const item = document.querySelector(`.intervention-item[data-intervention-id="${interventionId}"]`);
        if (item) {
            item.dataset.status = newStatus;
            const badge = item.querySelector('.intervention-status-badge');
            const badgeColor = newStatus === 'Active' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
            if (badge) {
                badge.className = 'intervention-status-badge px-2 py-0.5 ' + badgeColor + ' rounded-full text-[10px] font-semibold';
                badge.textContent = newStatus;
            }
            const borderColor = newStatus === 'Active' ? 'border-amber-500' : 'border-emerald-500';
            item.className = item.className.replace(/border-(amber|emerald|slate)-500/, borderColor);

            const progressLabel = item.querySelector('.intervention-progress-label');
            if (progressLabel) progressLabel.textContent = newProgress + '%';
            const progressBar = item.querySelector('.intervention-progress-bar');
            if (progressBar) {
                progressBar.style.width = newProgress + '%';
                progressBar.className = 'intervention-progress-bar ' + (newStatus === 'Active' ? 'bg-amber-500' : 'bg-emerald-500') + ' h-1.5 rounded-full';
            }
        }

        closeModal('updateStatusModal');
        showToast('Intervention status updated!', 'success');
    }

    // ============================================================
    // GENERATE REPORT
    // ============================================================
    function generateReport(format) {
        closeModal('reportModal');
        if (format === 'pdf') {
            const reportHTML = buildReportHTML();
            const printWindow = window.open('', '_blank', 'width=1000,height=800');
            printWindow.document.open();
            printWindow.document.write(reportHTML);
            printWindow.document.close();

            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 250);
            };
            showToast('PDF report opened in a new tab. Choose "Save as PDF" in the print dialog.', 'info');
        } else if (format === 'docx') {
            const reportHTML = buildReportHTML();
            const blob = new Blob([reportHTML], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Response_Management_Report.doc';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('DOCX report downloaded!', 'success');
        } else if (format === 'excel') {
            let csv = "Team ID,Name,Specialization,Status,Leader,Contact\n";
            TEAMS.forEach(t => {
                csv += `${t.id},"${t.name}",${t.specialization},${t.status},"${t.leader}",${t.contact}\n`;
            });
            csv += "\nResource ID,Name,Category,Quantity,Unit,Location,Status\n";
            RESOURCES.forEach(r => {
                csv += `${r.id},"${r.name}",${r.category},${r.quantity},${r.unit},"${r.location}",${r.status}\n`;
            });
            csv += "\nIntervention ID,Title,Type,Location,Status,Progress,Team Lead\n";
            INTERVENTIONS.forEach(i => {
                csv += `${i.id},"${i.title}",${i.type},"${i.location}",${i.status},${i.progress}%,"${i.team_lead}"\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Response_Management_Report.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('CSV (Excel) report downloaded!', 'success');
        }
    }

    // ============================================================
    // BUILD REPORT HTML for PDF / DOCX
    // ============================================================
    function buildReportHTML() {
        const teamRows = TEAMS.map((t, i) => `
            <tr style="background:${i % 2 === 1 ? '#f5fafa' : '#ffffff'};">
                <td style="padding:5px 9px; border:1px solid #cccccc;">${t.id}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${t.name}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${t.specialization}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${t.leader}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${t.status}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${t.deployed_to || '—'}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${t.contact}</td>
            </tr>
        `).join('');

        const resourceRows = RESOURCES.map((r, i) => `
            <tr style="background:${i % 2 === 1 ? '#f5fafa' : '#ffffff'};">
                <td style="padding:5px 9px; border:1px solid #cccccc;">${r.id}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${r.name}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${r.category}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${r.quantity} ${r.unit}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${r.location}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${r.status}</td>
            </tr>
        `).join('');

        const interventionRows = INTERVENTIONS.map((int, i) => `
            <tr style="background:${i % 2 === 1 ? '#f5fafa' : '#ffffff'};">
                <td style="padding:5px 9px; border:1px solid #cccccc;">${int.id}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${int.title}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${int.type}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${int.location}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${int.status}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${int.progress}%</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${int.team_lead}</td>
            </tr>
        `).join('');

        return `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <title>Response Management Report</title>
            <!--[if gte mso 9]>
            <xml>
                <w:WordDocument>
                    <w:View>Print</w:View>
                    <w:Zoom>90</w:Zoom>
                    <w:DoNotOptimizeForBrowser/>
                </w:WordDocument>
            </xml>
            <![endif]-->
            <style>
                @page WordSection1 {
                    size: 297mm 210mm;
                    mso-page-orientation: landscape;
                    margin: 15mm 12mm;
                }
                div.WordSection1 { page: WordSection1; }
                body { font-family: 'Times New Roman', serif; margin: 0; background: #fff; }
                .report-wrapper { max-width: 1100px; margin: 0 auto; }
                .header { text-align: center; margin-bottom: 28px; }
                .logo-img { width: 64px; height: 64px; margin: 0 auto 12px; display: block; object-fit: contain; }
                h1 {
                    font-size: 17px; font-weight: 900; color: #1a1a1a;
                    letter-spacing: 1.5px; text-transform: uppercase;
                    margin: 0 0 6px; font-family: 'Times New Roman', serif;
                }
                .report-subtitle {
                    font-size: 14px; font-weight: 700; color: #14807A;
                    margin: 0 0 14px; font-family: 'Times New Roman', serif;
                    letter-spacing: 0.5px;
                }
                .header-divider { border: none; border-top: 1.5px solid #1a1a1a; margin: 0 0 18px; }
                .generated-on { font-size: 12px; color: #555; margin: 0; }
                .summary { margin-bottom: 25px; font-size: 14px; }
                .summary p { margin: 4px 0; }
                h3 {
                    font-size: 14pt; font-weight: 700; color: #0B4F4A;
                    margin: 28px 0 8px; border-bottom: 1px solid #aaa;
                    padding-bottom: 4px; font-family: 'Times New Roman', serif;
                }
                table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 28px; }
                th {
                    background-color: #0B4F4A; color: #ffffff;
                    padding: 7px 9px; text-align: left;
                    border: 1px solid #0B4F4A; font-size: 8.5pt; letter-spacing: 0.3px;
                }
                td { padding: 5px 9px; border: 1px solid #cccccc; vertical-align: top; }
                .footer {
                    text-align: center; font-size: 9pt; color: #888;
                    margin-top: 28px; border-top: 1px solid #ccc; padding-top: 12px;
                }
            </style>
        </head>
        <body>
        <div class="WordSection1">
        <div class="report-wrapper">
            <div class="header">
                <img class="logo-img" src="<?= site_url('assets/images/logo.png') ?>" alt="Logo" width="64" height="64" style="width:64px; height:64px;">
                <h1>Health Sanitation Management Caloocan</h1>
                <p class="report-subtitle">Response Management</p>
                <hr class="header-divider">
                <p class="generated-on">Generated on: ${new Date().toLocaleString()}</p>
            </div>

            <div class="summary">
                <p><strong>Total Teams:</strong> ${TEAMS.length}</p>
                <p><strong>Available Teams:</strong> ${TEAMS.filter(t => t.status === 'Available').length}</p>
                <p><strong>Deployed Teams:</strong> ${TEAMS.filter(t => t.status === 'Deployed').length}</p>
                <p><strong>Active Interventions:</strong> ${INTERVENTIONS.filter(i => i.status === 'Active').length}</p>
                <p><strong>Completed Interventions:</strong> ${INTERVENTIONS.filter(i => i.status === 'Completed').length}</p>
            </div>

            <h3>Response Teams</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Specialization</th><th>Leader</th>
                        <th>Status</th><th>Deployed To</th><th>Contact</th>
                    </tr>
                </thead>
                <tbody>${teamRows}</tbody>
            </table>

            <h3>Resource Inventory</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Category</th><th>Quantity</th>
                        <th>Location</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>${resourceRows}</tbody>
            </table>

            <h3>Interventions</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Title</th><th>Type</th><th>Location</th>
                        <th>Status</th><th>Progress</th><th>Team Lead</th>
                    </tr>
                </thead>
                <tbody>${interventionRows}</tbody>
            </table>

            <div class="footer">This is a computer-generated report. For official use only.</div>
        </div>
        </div>
        </body>
        </html>
        `;
    }

    // ============================================================
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('Refreshing data...', 'info');
        window.location.reload();
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