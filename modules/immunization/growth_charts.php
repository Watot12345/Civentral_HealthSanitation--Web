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
requireDepartmentAccess('immunization & nutrition');

require_once __DIR__ . '/../../config/database.php';

// Base Children & Growth Data
$children = [];
$growthData = [];

try {
    $db = Database::getInstance();
    $dbChildren = $db->query('children', 'GET');

    $dbGrowth = [];
    try {
        $dbGrowth = $db->select('growth_measurements', [], ['order' => 'measurement_date.asc']);
    } catch (\Throwable $ex) {
        $dbGrowth = [];
    }

    if (!empty($dbChildren) && is_array($dbChildren)) {
        foreach ($dbChildren as $c) {
            $cId = (int)$c['id'];
            $birthDate = $c['birth_date'] ?? date('Y-m-d');
            $birth = new DateTime($birthDate);
            $today = new DateTime();
            $diff = $today->diff($birth);
            $ageStr = $diff->y > 0 ? "{$diff->y} yrs {$diff->m} mos" : "{$diff->m} mos";

            $children[] = [
                'id' => $cId,
                'child_id' => $c['child_id'] ?? ('CH-' . sprintf('%03d', $cId)),
                'name' => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
                'gender' => !empty($c['gender']) ? ucfirst(strtolower($c['gender'])) : 'Female',
                'birth_date' => $birthDate,
                'age' => $ageStr
            ];

            if (isset($c['birth_weight']) || isset($c['birth_height'])) {
                $growthData[] = [
                    'child_id' => $cId,
                    'date' => $birthDate,
                    'weight' => (float)($c['birth_weight'] ?? 3.2),
                    'height' => (float)($c['birth_height'] ?? 50),
                    'head_circumference' => 35,
                    'notes' => 'Birth Record'
                ];
            }
        }
    }

    if (!empty($dbGrowth) && is_array($dbGrowth)) {
        foreach ($dbGrowth as $g) {
            $growthData[] = [
                'id' => (int)$g['id'],
                'child_id' => (int)$g['child_id'],
                'date' => $g['measurement_date'],
                'weight' => (float)$g['weight'],
                'height' => (float)$g['height'],
                'head_circumference' => !empty($g['head_circumference']) ? (float)$g['head_circumference'] : null,
                'notes' => $g['notes'] ?? 'Routine Checkup'
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Supabase children/growth query exception: ' . $e->getMessage());
}

// Compute dynamic growth alerts from database records
$growthAlerts = [];
foreach ($children as $c) {
    if (isset($c['birth_weight']) && (float)$c['birth_weight'] < 2.5) {
        $growthAlerts[] = ['child' => $c['name'], 'type' => 'weight', 'message' => 'Low birth weight (< 2.5 kg)', 'severity' => 'high'];
    }
}

// Count children with alerts
$childrenWithAlerts = count(array_unique(array_column($growthAlerts, 'child')));
// WHO Growth Reference Percentiles (Standard DOH Growth Chart Benchmarks)
$weightPercentiles = [
    'male' => [
        '0' => ['p3' => 2.5, 'p15' => 2.8, 'p50' => 3.3, 'p85' => 3.8, 'p97' => 4.2],
        '1' => ['p3' => 3.4, 'p15' => 3.8, 'p50' => 4.3, 'p85' => 4.9, 'p97' => 5.4],
        '3' => ['p3' => 4.8, 'p15' => 5.2, 'p50' => 5.8, 'p85' => 6.4, 'p97' => 7.0],
        '6' => ['p3' => 6.4, 'p15' => 6.9, 'p50' => 7.6, 'p85' => 8.4, 'p97' => 9.2],
        '9' => ['p3' => 7.2, 'p15' => 7.8, 'p50' => 8.6, 'p85' => 9.4, 'p97' => 10.2],
        '12' => ['p3' => 8.0, 'p15' => 8.6, 'p50' => 9.6, 'p85' => 10.5, 'p97' => 11.5],
        '18' => ['p3' => 9.2, 'p15' => 10.0, 'p50' => 11.0, 'p85' => 12.2, 'p97' => 13.2],
        '24' => ['p3' => 10.5, 'p15' => 11.2, 'p50' => 12.5, 'p85' => 13.8, 'p97' => 14.8],
        '36' => ['p3' => 12.5, 'p15' => 13.2, 'p50' => 14.5, 'p85' => 16.0, 'p97' => 17.5],
    ],
    'female' => [
        '0' => ['p3' => 2.4, 'p15' => 2.7, 'p50' => 3.2, 'p85' => 3.7, 'p97' => 4.1],
        '1' => ['p3' => 3.2, 'p15' => 3.6, 'p50' => 4.1, 'p85' => 4.6, 'p97' => 5.1],
        '3' => ['p3' => 4.5, 'p15' => 4.9, 'p50' => 5.5, 'p85' => 6.1, 'p97' => 6.7],
        '6' => ['p3' => 6.0, 'p15' => 6.5, 'p50' => 7.2, 'p85' => 7.9, 'p97' => 8.7],
        '9' => ['p3' => 6.8, 'p15' => 7.3, 'p50' => 8.0, 'p85' => 8.8, 'p97' => 9.6],
        '12' => ['p3' => 7.5, 'p15' => 8.1, 'p50' => 9.0, 'p85' => 9.8, 'p97' => 10.8],
        '18' => ['p3' => 8.8, 'p15' => 9.4, 'p50' => 10.5, 'p85' => 11.6, 'p97' => 12.6],
        '24' => ['p3' => 10.0, 'p15' => 10.8, 'p50' => 12.0, 'p85' => 13.2, 'p97' => 14.2],
        '36' => ['p3' => 12.0, 'p15' => 12.8, 'p50' => 14.0, 'p85' => 15.4, 'p97' => 16.8],
    ]
];

$title = 'Growth Charts';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Growth Charts</h2>
            <p class="text-sm text-slate-500 mt-0.5">Track child growth, weight, height & percentiles</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('addGrowthModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Add Measurement
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Children -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-child text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo count($children); ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Children</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">👶 All children</span>
                    <span class="text-[10px] text-slate-400"><?php echo $childrenWithAlerts; ?> with alerts</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Measurements -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-chart-line text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo count($growthData); ?></p>
                        <p class="text-xs font-medium text-slate-500">Measurements</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">📊 Records</span>
                    <span class="text-[10px] text-slate-400">Growth tracking</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Alerts -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo count($growthAlerts); ?></p>
                        <p class="text-xs font-medium text-slate-500">Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">⚠️ Attention</span>
                    <span class="text-[10px] text-slate-400">Needs review</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Normal Growth -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-heart-pulse text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo count($children) - $childrenWithAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Normal Growth</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Healthy</span>
                    <span class="text-[10px] text-slate-400">On track</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Growth Alerts -->
    <?php if (count($growthAlerts) > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo count($growthAlerts); ?></span> growth alert(s) require attention
            </span>
        </div>
        <button onclick="document.getElementById('growthAlertsSection').classList.toggle('hidden')" 
                class="text-xs font-semibold text-rose-700 hover:text-rose-900 underline">
            View alerts
        </button>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- ENHANCED SEARCH & FILTER SECTION                            -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <!-- Search Input -->
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchChildGrowth"
                       placeholder="Search by name or ID..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition"
                       oninput="filterChildrenList()">
            </div>
            
            <!-- Filters -->
            <div class="flex gap-2 flex-wrap">
                <select id="filterGenderGrowth" onchange="filterChildrenList()" 
                        class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select id="filterAgeGroup" onchange="filterChildrenList()" 
                        class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Ages</option>
                    <option value="0-1">0-1 yr</option>
                    <option value="1-2">1-2 yrs</option>
                    <option value="2-3">2-3 yrs</option>
                    <option value="3-5">3-5 yrs</option>
                </select>
                <select id="filterAlertStatus" onchange="filterChildrenList()" 
                        class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="alert">With Alerts</option>
                    <option value="normal">Normal</option>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Measurement date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white"
                          onchange="updateCharts()">
                      <input type="date" id="filterDateTo" aria-label="Measurement date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white"
                          onchange="updateCharts()">
                <button onclick="resetChildFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Child List Results -->
        <div class="mt-3 pt-3 border-t border-slate-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Quick Profile Selector</span>
                <span id="childCountInfo" class="text-xs font-semibold text-slate-400">Showing top 12 children</span>
            </div>
            <div class="flex flex-wrap gap-2 max-h-32 overflow-y-auto p-1.5 border border-slate-100 rounded-xl bg-slate-50/50" id="childListContainer">
                <!-- Populated by JavaScript -->
            </div>
            <div id="noChildrenFound" class="hidden text-center py-4 text-sm text-slate-400">
                <i class="fa-solid fa-child text-2xl block mb-2 opacity-30"></i>
                No children found matching your criteria
            </div>
        </div>
    </div>

    <!-- Hidden child selector (for backward compatibility) -->
    <select id="childSelector" class="hidden" onchange="updateCharts()">
        <?php foreach ($children as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Clinical WHO Growth Status Header Card -->
    <div id="clinicalStatusCard" class="bg-gradient-to-r from-teal-900 via-emerald-800 to-teal-900 text-white rounded-2xl p-5 mb-6 shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-4 border border-teal-700/40">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white/10 backdrop-blur-md rounded-2xl flex items-center justify-center text-white text-xl font-black border border-white/20 shadow-inner flex-shrink-0">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 id="childSelectedName" class="text-lg font-black tracking-tight text-white">Select a Child</h3>
                    <span id="childSelectedBadge" class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-400/20 text-emerald-300 border border-emerald-400/30">WHO Normal</span>
                </div>
                <p id="childGrowthSummary" class="text-xs text-teal-100/90 mt-0.5">Select a child profile above to review WHO percentiles and growth velocity.</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openModal('addGrowthModal')" class="px-4 py-2 bg-white text-teal-900 hover:bg-teal-50 text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-plus text-xs"></i> Log New Measurement
            </button>
        </div>
    </div>

    <!-- Chart Type Buttons -->
    <div class="flex items-center justify-between gap-2 mb-4">
        <div class="flex gap-2">
            <button onclick="setChartType('weight')" id="btnWeight" class="px-4 py-2 text-sm font-semibold rounded-lg bg-brand-dark text-white hover:bg-brand-medium transition">Weight for Age (kg)</button>
            <button onclick="setChartType('height')" id="btnHeight" class="px-4 py-2 text-sm font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Height for Age (cm)</button>
        </div>
        <span class="text-xs font-semibold text-slate-400">WHO 2006 Child Growth Standards</span>
    </div>

    <!-- Chart Container -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-4 mb-6">
        <div id="growthChart" style="height: 400px;"></div>
    </div>

    <!-- Growth Data Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
            <h4 class="text-sm font-bold text-slate-700">Measurement History</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Age</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Weight (kg)</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Height (cm)</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Head (cm)</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Percentile</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Notes</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="growthTableBody">
                    <!-- Populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Growth Alerts Section -->
    <div id="growthAlertsSection" class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 bg-rose-50 border-b border-rose-200">
            <h4 class="text-sm font-bold text-rose-700 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> Growth Alerts
            </h4>
        </div>
        <div class="divide-y divide-slate-100">
            <?php foreach ($growthAlerts as $alert): ?>
            <div class="px-4 py-3 flex items-center justify-between">
                <div>
                    <p class="font-semibold text-slate-800 text-sm"><?php echo $alert['child']; ?></p>
                    <p class="text-xs text-slate-600"><?php echo $alert['message']; ?></p>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $alert['severity'] === 'high' ? 'bg-rose-100 text-rose-700' : ($alert['severity'] === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'); ?>">
                    <?php echo ucfirst($alert['severity']); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD GROWTH MEASUREMENT MODAL                                -->
<!-- ============================================================ -->
<div id="addGrowthModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-brand-medium"></i>
                Add Growth Measurement
            </h3>
            <button onclick="closeModal('addGrowthModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addGrowthForm" class="p-6 space-y-4" onsubmit="saveGrowthMeasurement(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Child</label>
                <select id="growth_child" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Child</option>
                    <?php foreach ($children as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?> (<?php echo $c['child_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                <input type="date" id="growth_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Weight (kg)</label>
                    <input type="number" id="growth_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitGrowthMeasurement(this)" required title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Height (cm)</label>
                    <input type="number" id="growth_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitGrowthMeasurement(this)" required title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Head Circumference (cm)</label>
                <input type="number" id="growth_head" step="0.1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <input type="text" id="growth_notes" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. 3 months checkup">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('addGrowthModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Add Measurement
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
<!-- Local libraries                                              -->
<!-- ============================================================ -->
<script src="../../assets/js/apexcharts.min.js"></script>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<style>
    .child-chip {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .child-chip:hover {
        transform: translateY(-1px);
    }
    .child-chip:active {
        transform: scale(0.95);
    }
</style>

<script>
    const DEFAULT_WEIGHT_PERCENTILES = {
        male: {
            '0': { p3: 2.5, p15: 2.8, p50: 3.3, p85: 3.8, p97: 4.2 },
            '1': { p3: 3.4, p15: 3.8, p50: 4.3, p85: 4.9, p97: 5.4 },
            '3': { p3: 4.8, p15: 5.2, p50: 5.8, p85: 6.4, p97: 7.0 },
            '6': { p3: 6.4, p15: 6.9, p50: 7.6, p85: 8.4, p97: 9.2 },
            '12': { p3: 8.0, p15: 8.6, p50: 9.6, p85: 10.5, p97: 11.5 },
            '24': { p3: 10.5, p15: 11.2, p50: 12.5, p85: 13.8, p97: 14.8 }
        },
        female: {
            '0': { p3: 2.4, p15: 2.7, p50: 3.2, p85: 3.7, p97: 4.1 },
            '1': { p3: 3.2, p15: 3.6, p50: 4.1, p85: 4.6, p97: 5.1 },
            '3': { p3: 4.5, p15: 4.9, p50: 5.5, p85: 6.1, p97: 6.7 },
            '6': { p3: 6.0, p15: 6.5, p50: 7.2, p85: 7.9, p97: 8.7 },
            '12': { p3: 7.5, p15: 8.1, p50: 9.0, p85: 9.8, p97: 10.8 },
            '24': { p3: 10.0, p15: 10.8, p50: 12.0, p85: 13.2, p97: 14.2 }
        }
    };

    // PHP Data to JavaScript
    const CHILDREN = <?php echo json_encode($children, JSON_PRETTY_PRINT); ?> || [];
    const GROWTH_DATA = <?php echo json_encode($growthData, JSON_PRETTY_PRINT); ?> || [];
    const WEIGHT_PERCENTILES = <?php echo json_encode($weightPercentiles, JSON_PRETTY_PRINT); ?> || DEFAULT_WEIGHT_PERCENTILES;
    const GROWTH_ALERTS = <?php echo json_encode($growthAlerts, JSON_PRETTY_PRINT); ?> || [];

    let currentChartType = 'weight';
    let growthChart = null;
    let selectedChildId = null;
    let filteredChildren = [];

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
    // HELPER FUNCTIONS
    // ============================================================
    function getChildById(id) {
        return CHILDREN.find(c => c.id == id);
    }

    function getGrowthDataForChild(childId) {
        return GROWTH_DATA.filter(d => d.child_id == childId).sort((a, b) => new Date(a.date) - new Date(b.date));
    }

    function getAgeInMonths(birthDate, measureDate) {
        const birth = new Date(birthDate);
        const measure = new Date(measureDate);
        const diff = measure - birth;
        return diff / (1000 * 60 * 60 * 24 * 30.44);
    }

    function getWeightPercentile(weight, gender, ageMonths) {
        const genderKey = (gender && typeof gender === 'string') ? gender.toLowerCase() : 'female';
        const data = (WEIGHT_PERCENTILES && WEIGHT_PERCENTILES[genderKey]) ? WEIGHT_PERCENTILES[genderKey] : DEFAULT_WEIGHT_PERCENTILES.female;
        let closestAge = '0';
        for (const age in data) {
            if (ageMonths >= parseInt(age)) {
                closestAge = age;
            }
        }
        const ref = data[closestAge];
        if (!ref) return 'N/A';
        if (weight <= ref.p3) return 'Below 3rd';
        if (weight <= ref.p15) return '3rd - 15th';
        if (weight <= ref.p50) return '15th - 50th';
        if (weight <= ref.p85) return '50th - 85th';
        if (weight <= ref.p97) return '85th - 97th';
        return 'Above 97th';
    }

    // ============================================================
    // CHILD SEARCH & FILTER
    // ============================================================
    function filterChildrenList() {
        const search = document.getElementById('searchChildGrowth').value.toLowerCase().trim();
        const gender = document.getElementById('filterGenderGrowth').value;
        const ageGroup = document.getElementById('filterAgeGroup').value;
        const alertStatus = document.getElementById('filterAlertStatus').value;

        filteredChildren = CHILDREN.filter(child => {
            const nameMatch = child.name.toLowerCase().includes(search) || child.child_id.toLowerCase().includes(search);
            if (!nameMatch) return false;

            if (gender && child.gender !== gender) return false;

            if (ageGroup) {
                const ageNum = parseInt(child.age);
                if (ageGroup === '0-1' && ageNum >= 1) return false;
                if (ageGroup === '1-2' && (ageNum < 1 || ageNum >= 2)) return false;
                if (ageGroup === '2-3' && (ageNum < 2 || ageNum >= 3)) return false;
                if (ageGroup === '3-5' && (ageNum < 3 || ageNum >= 5)) return false;
            }

            if (alertStatus) {
                const hasAlert = GROWTH_ALERTS.some(a => a.child === child.name);
                if (alertStatus === 'alert' && !hasAlert) return false;
                if (alertStatus === 'normal' && hasAlert) return false;
            }

            return true;
        });

        renderChildList();
    }

    function renderChildList() {
        const container = document.getElementById('childListContainer');
        const noResults = document.getElementById('noChildrenFound');
        const countInfo = document.getElementById('childCountInfo');

        if (filteredChildren.length === 0) {
            container.innerHTML = '';
            noResults.classList.remove('hidden');
            if (countInfo) countInfo.textContent = '0 children found';
            return;
        }

        noResults.classList.add('hidden');
        if (countInfo) {
            countInfo.textContent = `Showing ${Math.min(12, filteredChildren.length)} of ${filteredChildren.length} children`;
        }

        const visibleList = filteredChildren.slice(0, 12);

        container.innerHTML = visibleList.map(child => {
            const hasAlert = GROWTH_ALERTS.some(a => a.child === child.name);
            const isSelected = selectedChildId == child.id;
            return `
                <button onclick="selectChild(${child.id})" 
                        class="child-chip px-3 py-1.5 rounded-full text-xs font-medium border transition-all ${isSelected ? 'bg-brand-dark text-white border-brand-dark' : 'bg-white border-slate-200 text-slate-600 hover:border-brand-medium hover:bg-brand-light/40'} ${hasAlert ? 'ring-2 ring-rose-300' : ''}">
                    ${child.name}
                    <span class="text-[10px] opacity-60">${child.child_id}</span>
                    ${hasAlert ? '<i class="fa-solid fa-triangle-exclamation text-rose-500 ml-1 text-[10px]"></i>' : ''}
                    ${isSelected ? ' ✓' : ''}
                </button>
            `;
        }).join('');

        const urlParams = new URLSearchParams(window.location.search);
        const urlChildId = urlParams.get('child_id');
        if (!selectedChildId && filteredChildren.length > 0) {
            if (urlChildId && getChildById(urlChildId)) {
                selectChild(urlChildId);
            } else {
                selectChild(filteredChildren[0].id);
            }
        }
    }

    function selectChild(id) {
        selectedChildId = id;
        document.getElementById('childSelector').value = id;
        renderChildList();
        updateCharts();
    }

    function resetChildFilters() {
        document.getElementById('searchChildGrowth').value = '';
        document.getElementById('filterGenderGrowth').value = '';
        document.getElementById('filterAgeGroup').value = '';
        document.getElementById('filterAlertStatus').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        filterChildrenList();
    }

    // ============================================================
    // CHART FUNCTIONS
    // ============================================================
    function updateCharts() {
        const childId = document.getElementById('childSelector').value;
        const child = getChildById(childId);
        if (!child) return;

        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        if (dateFrom && dateTo && dateFrom > dateTo) {
            showToast('The start date must be before the end date.', 'warning');
            return;
        }

        const data = getGrowthDataForChild(childId).filter(measurement =>
            (!dateFrom || measurement.date >= dateFrom) &&
            (!dateTo || measurement.date <= dateTo)
        );
        if (child) {
            const nameEl = document.getElementById('childSelectedName');
            const badgeEl = document.getElementById('childSelectedBadge');
            const summaryEl = document.getElementById('childGrowthSummary');
            if (nameEl) nameEl.textContent = child.name + ' (' + (child.age || 'Child') + ')';

            const latestMeas = data && data.length > 0 ? data[data.length - 1] : null;
            if (latestMeas && summaryEl && badgeEl) {
                const latestAge = getAgeInMonths(child.birth_date, latestMeas.date);
                const percStr = getWeightPercentile(latestMeas.weight, child.gender, latestAge);
                badgeEl.textContent = 'WHO: ' + percStr;
                summaryEl.textContent = 'Latest record: ' + latestMeas.weight + ' kg, ' + latestMeas.height + ' cm (Recorded: ' + latestMeas.date + ').';
            } else if (summaryEl && badgeEl) {
                badgeEl.textContent = 'No Records';
                summaryEl.textContent = 'No growth measurements logged yet. Click "Log New Measurement" to start tracking.';
            }
        }

        if (data.length === 0) {
            document.getElementById('growthChart').innerHTML = '<div class="flex items-center justify-center h-full text-slate-400">No growth data available for this child.</div>';
            return;
        }

        const dates = data.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }));
        const weightData = data.map(d => parseFloat(d.weight));
        const heightData = data.map(d => parseFloat(d.height));
        const headData = data.map(d => parseFloat(d.head_circumference || 0));

        const series = currentChartType === 'weight' 
            ? [{ name: 'Weight (kg)', data: weightData }]
            : currentChartType === 'height'
            ? [{ name: 'Height (cm)', data: heightData }]
            : [{ name: 'Head Circumference (cm)', data: headData }];

        const gender = (child.gender && typeof child.gender === 'string') ? child.gender.toLowerCase() : 'female';
        const ageMonths = data.map(d => getAgeInMonths(child.birth_date, d.date));
        const percentileData = (WEIGHT_PERCENTILES && WEIGHT_PERCENTILES[gender]) ? WEIGHT_PERCENTILES[gender] : DEFAULT_WEIGHT_PERCENTILES.female;
        const p3Data = [], p50Data = [], p97Data = [];

        ageMonths.forEach(months => {
            let closestAge = '0';
            for (const age in percentileData) {
                if (months >= parseInt(age)) {
                    closestAge = age;
                }
            }
            const ref = percentileData[closestAge];
            p3Data.push(ref ? ref.p3 : null);
            p50Data.push(ref ? ref.p50 : null);
            p97Data.push(ref ? ref.p97 : null);
        });

        if (currentChartType === 'weight') {
            series.push({ name: '3rd Percentile', data: p3Data, type: 'line', dashArray: 5, color: '#94A3B8' });
            series.push({ name: '50th Percentile', data: p50Data, type: 'line', dashArray: 5, color: '#14807A' });
            series.push({ name: '97th Percentile', data: p97Data, type: 'line', dashArray: 5, color: '#94A3B8' });
        }

        const options = {
            series: series,
            chart: {
                type: 'line',
                height: 400,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            title: {
                text: `${child.name} - ${currentChartType === 'weight' ? 'Weight' : currentChartType === 'height' ? 'Height' : 'Head Circumference'} Growth Chart`,
                align: 'center',
                style: { fontSize: '16px', fontWeight: 'bold' }
            },
            xaxis: {
                categories: dates,
                title: { text: 'Date' }
            },
            yaxis: {
                title: { text: currentChartType === 'weight' ? 'Weight (kg)' : currentChartType === 'height' ? 'Height (cm)' : 'Head Circumference (cm)' }
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            markers: {
                size: 5
            },
            legend: {
                position: 'top'
            },
            colors: ['#0B4F4A', '#14807A', '#94A3B8', '#94A3B8'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(1);
                    }
                }
            }
        };

        if (growthChart) {
            growthChart.updateOptions(options);
            growthChart.updateSeries(series);
        } else {
            growthChart = new ApexCharts(document.getElementById('growthChart'), options);
            growthChart.render();
        }

        updateGrowthTable(data, child);
    }

    function setChartType(type) {
        currentChartType = type;
        document.getElementById('btnWeight').className = type === 'weight' 
            ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-brand-dark text-white hover:bg-brand-medium transition'
            : 'px-4 py-2 text-sm font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition';
        document.getElementById('btnHeight').className = type === 'height'
            ? 'px-4 py-2 text-sm font-semibold rounded-lg bg-brand-dark text-white hover:bg-brand-medium transition'
            : 'px-4 py-2 text-sm font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition';
        updateCharts();
    }

    // ============================================================
    // GROWTH TABLE UPDATE
    // ============================================================
    function updateGrowthTable(data, child) {
        const tbody = document.getElementById('growthTableBody');
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">No measurements recorded</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(d => {
            const ageMonths = getAgeInMonths(child.birth_date, d.date);
            const percentile = getWeightPercentile(parseFloat(d.weight), child.gender, ageMonths);
            const ageDisplay = ageMonths < 12 ? Math.round(ageMonths) + ' months' : (Math.round(ageMonths / 12) + ' yrs ' + Math.round(ageMonths % 12) + ' mos');
            const deleteBtn = d.id ? `<button onclick="deleteGrowthMeasurement(${d.id})" class="p-1 text-rose-500 hover:bg-rose-50 rounded transition" title="Delete"><i class="fa-solid fa-trash text-xs"></i></button>` : '';
            return `
                <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors">
                    <td class="px-4 py-2 text-slate-600 text-xs">${new Date(d.date).toLocaleDateString()}</td>
                    <td class="px-4 py-2 text-slate-600 text-xs">${ageDisplay}</td>
                    <td class="px-4 py-2 text-slate-600 text-xs font-medium">${d.weight}</td>
                    <td class="px-4 py-2 text-slate-600 text-xs">${d.height}</td>
                    <td class="px-4 py-2 text-slate-600 text-xs">${d.head_circumference || '—'}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${percentile.includes('Below') || percentile.includes('Above') ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'}">
                            ${percentile}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-slate-400 text-xs">${d.notes || '—'}</td>
                    <td class="px-4 py-2 text-center">${deleteBtn}</td>
                </tr>
            `;
        }).join('');
    }

    async function deleteGrowthMeasurement(id) {
        if (!confirm('Are you sure you want to delete this growth measurement?')) return;
        try {
            const res = await fetch(`/api/growth.php?id=${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.success) {
                const idx = GROWTH_DATA.findIndex(g => g.id == id);
                if (idx !== -1) GROWTH_DATA.splice(idx, 1);
                if (selectedChildId) selectChild(selectedChildId);
                showToast('Measurement deleted successfully!', 'success');
            } else {
                showToast(data.message || 'Failed to delete measurement.', 'danger');
            }
        } catch (err) {
            console.error('Delete growth error:', err);
            showToast('Error deleting measurement.', 'danger');
        }
    }

    // ============================================================
    // SAVE GROWTH MEASUREMENT
    // ============================================================
    function limitGrowthMeasurement(input) {
        const parts = String(input.value || '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 3);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    async function saveGrowthMeasurement(event) {
        event.preventDefault();
        const childId = document.getElementById('growth_child').value;
        const date = document.getElementById('growth_date').value;
        const weight = document.getElementById('growth_weight').value;
        const height = document.getElementById('growth_height').value;
        const head = document.getElementById('growth_head').value;
        const notes = document.getElementById('growth_notes').value;

        if (!childId) {
            showToast('Please select a child profile.', 'warning');
            return;
        }

        const validMeasurement = (value, minimum) =>
            /^\d{1,3}(\.\d{1,2})?$/.test(value) && Number(value) >= minimum && Number(value) <= 999;

        if (!validMeasurement(weight, 0.1) || !validMeasurement(height, 20)) {
            showToast('Weight must be 0.1-999 kg and height must be 20-999 cm.', 'warning');
            return;
        }

        const newRecord = {
            child_id: Number(childId),
            date: date || new Date().toISOString().split('T')[0],
            weight: parseFloat(weight),
            height: parseFloat(height),
            head_circumference: head ? parseFloat(head) : null,
            notes: notes || 'Routine Checkup'
        };

        try {
            const res = await fetch('/api/growth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newRecord)
            });
            const data = await res.json();
            if (data.success) {
                const saved = data.data && data.data[0] ? data.data[0] : newRecord;
                GROWTH_DATA.push({
                    id: saved.id || Date.now(),
                    child_id: Number(childId),
                    date: saved.measurement_date || newRecord.date,
                    weight: parseFloat(saved.weight || newRecord.weight),
                    height: parseFloat(saved.height || newRecord.height),
                    head_circumference: saved.head_circumference ? parseFloat(saved.head_circumference) : newRecord.head_circumference,
                    notes: saved.notes || newRecord.notes
                });

                selectChild(childId);
                showToast('Growth measurement saved successfully!', 'success');
                closeModal('addGrowthModal');
                document.getElementById('addGrowthForm').reset();
            } else {
                showToast(data.message || 'Failed to save growth measurement.', 'danger');
            }
        } catch (err) {
            console.error('Save growth measurement error:', err);
            showToast('Error saving growth measurement to server.', 'danger');
        }
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
    // INITIALIZATION
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('growth_date');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        filterChildrenList();
    });

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