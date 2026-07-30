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

// Sample Children Data
$children = [
    ['id' => 1, 'child_id' => 'CH-001', 'name' => 'Sofia Garcia', 'gender' => 'Female', 'birth_date' => '2024-03-15', 'age' => '2 yrs 4 mos'],
    ['id' => 2, 'child_id' => 'CH-002', 'name' => 'Luis Mendoza', 'gender' => 'Male', 'birth_date' => '2025-04-20', 'age' => '1 yr 3 mos'],
    ['id' => 3, 'child_id' => 'CH-003', 'name' => 'Emma Lim', 'gender' => 'Female', 'birth_date' => '2023-06-01', 'age' => '3 yrs 1 mo'],
    ['id' => 4, 'child_id' => 'CH-004', 'name' => 'Noah Torres', 'gender' => 'Male', 'birth_date' => '2025-10-10', 'age' => '9 mos'],
    ['id' => 5, 'child_id' => 'CH-005', 'name' => 'Isabella Cruz', 'gender' => 'Female', 'birth_date' => '2024-08-25', 'age' => '1 yr 11 mos'],
];

// Sample Growth Data (Weight and Height over time)
$growthData = [
    // Sofia Garcia (Female)
    ['child_id' => 1, 'date' => '2024-03-15', 'weight' => 3.2, 'height' => 50, 'head_circumference' => 35, 'notes' => 'Birth'],
    ['child_id' => 1, 'date' => '2024-04-15', 'weight' => 4.0, 'height' => 53, 'head_circumference' => 37, 'notes' => '1 month'],
    ['child_id' => 1, 'date' => '2024-06-15', 'weight' => 5.5, 'height' => 58, 'head_circumference' => 39, 'notes' => '3 months'],
    ['child_id' => 1, 'date' => '2024-09-15', 'weight' => 7.2, 'height' => 63, 'head_circumference' => 41, 'notes' => '6 months'],
    ['child_id' => 1, 'date' => '2024-12-15', 'weight' => 8.5, 'height' => 68, 'head_circumference' => 43, 'notes' => '9 months'],
    ['child_id' => 1, 'date' => '2025-03-15', 'weight' => 9.8, 'height' => 72, 'head_circumference' => 44, 'notes' => '12 months'],
    ['child_id' => 1, 'date' => '2025-09-15', 'weight' => 11.5, 'height' => 78, 'head_circumference' => 46, 'notes' => '18 months'],
    ['child_id' => 1, 'date' => '2026-03-15', 'weight' => 13.2, 'height' => 84, 'head_circumference' => 47, 'notes' => '24 months'],
    
    // Luis Mendoza (Male)
    ['child_id' => 2, 'date' => '2025-04-20', 'weight' => 3.0, 'height' => 48, 'head_circumference' => 34, 'notes' => 'Birth'],
    ['child_id' => 2, 'date' => '2025-05-20', 'weight' => 3.8, 'height' => 51, 'head_circumference' => 36, 'notes' => '1 month'],
    ['child_id' => 2, 'date' => '2025-07-20', 'weight' => 5.2, 'height' => 56, 'head_circumference' => 38, 'notes' => '3 months'],
    ['child_id' => 2, 'date' => '2025-10-20', 'weight' => 6.8, 'height' => 61, 'head_circumference' => 40, 'notes' => '6 months'],
    ['child_id' => 2, 'date' => '2026-01-20', 'weight' => 8.0, 'height' => 66, 'head_circumference' => 42, 'notes' => '9 months'],
    ['child_id' => 2, 'date' => '2026-04-20', 'weight' => 9.2, 'height' => 70, 'head_circumference' => 43, 'notes' => '12 months'],
    ['child_id' => 2, 'date' => '2026-06-20', 'weight' => 10.0, 'height' => 73, 'head_circumference' => 44, 'notes' => '14 months'],
    
    // Emma Lim (Female)
    ['child_id' => 3, 'date' => '2023-06-01', 'weight' => 3.5, 'height' => 52, 'head_circumference' => 36, 'notes' => 'Birth'],
    ['child_id' => 3, 'date' => '2023-09-01', 'weight' => 6.0, 'height' => 60, 'head_circumference' => 40, 'notes' => '3 months'],
    ['child_id' => 3, 'date' => '2023-12-01', 'weight' => 7.8, 'height' => 65, 'head_circumference' => 42, 'notes' => '6 months'],
    ['child_id' => 3, 'date' => '2024-03-01', 'weight' => 9.0, 'height' => 70, 'head_circumference' => 44, 'notes' => '9 months'],
    ['child_id' => 3, 'date' => '2024-06-01', 'weight' => 10.5, 'height' => 75, 'head_circumference' => 45, 'notes' => '12 months'],
    ['child_id' => 3, 'date' => '2024-12-01', 'weight' => 12.0, 'height' => 82, 'head_circumference' => 47, 'notes' => '18 months'],
    ['child_id' => 3, 'date' => '2025-06-01', 'weight' => 14.0, 'height' => 88, 'head_circumference' => 48, 'notes' => '24 months'],
    ['child_id' => 3, 'date' => '2026-06-01', 'weight' => 16.0, 'height' => 95, 'head_circumference' => 49, 'notes' => '36 months'],
];

// WHO Growth Reference Percentiles (simplified)
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

// Calculate age in months from birth date
function getAgeInMonths($birthDate, $measureDate) {
    $birth = new DateTime($birthDate);
    $measure = new DateTime($measureDate);
    $diff = $birth->diff($measure);
    return $diff->y * 12 + $diff->m;
}

// Get percentile for weight
function getWeightPercentile($weight, $gender, $ageMonths) {
    global $weightPercentiles;
    $data = $weightPercentiles[strtolower($gender)];
    $closestAge = '0';
    foreach ($data as $age => $values) {
        if ($ageMonths >= (int)$age) {
            $closestAge = $age;
        }
    }
    $ref = $data[$closestAge];
    if ($weight <= $ref['p3']) return 'Below 3rd';
    if ($weight <= $ref['p15']) return '3rd - 15th';
    if ($weight <= $ref['p50']) return '15th - 50th';
    if ($weight <= $ref['p85']) return '50th - 85th';
    if ($weight <= $ref['p97']) return '85th - 97th';
    return 'Above 97th';
}

// Sample Alerts
$growthAlerts = [
    ['child' => 'Noah Torres', 'type' => 'weight', 'message' => 'Weight below 3rd percentile', 'severity' => 'high'],
    ['child' => 'Luis Mendoza', 'type' => 'height', 'message' => 'Height below 15th percentile', 'severity' => 'medium'],
    ['child' => 'Emma Lim', 'type' => 'weight', 'message' => 'Weight above 85th percentile', 'severity' => 'low'],
];

// Count children with alerts
$childrenWithAlerts = count(array_filter($children, function($c) use ($growthAlerts) {
    return in_array($c['name'], array_column($growthAlerts, 'child'));
}));

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
                <button onclick="resetChildFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Child List Results -->
        <div class="mt-3 pt-3 border-t border-slate-100">
            <div class="flex flex-wrap gap-2" id="childListContainer">
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

    <!-- Chart Type Buttons -->
    <div class="flex gap-2 mb-4">
        <button onclick="setChartType('weight')" id="btnWeight" class="px-4 py-2 text-sm font-semibold rounded-lg bg-brand-dark text-white hover:bg-brand-medium transition">Weight</button>
        <button onclick="setChartType('height')" id="btnHeight" class="px-4 py-2 text-sm font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition">Height</button>
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
                    <input type="number" id="growth_weight" step="0.1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Height (cm)</label>
                    <input type="number" id="growth_height" step="0.1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
    // PHP Data to JavaScript
    const CHILDREN = <?php echo json_encode($children, JSON_PRETTY_PRINT); ?>;
    const GROWTH_DATA = <?php echo json_encode($growthData, JSON_PRETTY_PRINT); ?>;
    const WEIGHT_PERCENTILES = <?php echo json_encode($weightPercentiles, JSON_PRETTY_PRINT); ?>;
    const GROWTH_ALERTS = <?php echo json_encode($growthAlerts, JSON_PRETTY_PRINT); ?>;

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
        const data = WEIGHT_PERCENTILES[gender.toLowerCase()];
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

        if (filteredChildren.length === 0) {
            container.innerHTML = '';
            noResults.classList.remove('hidden');
            return;
        }

        noResults.classList.add('hidden');

        container.innerHTML = filteredChildren.map(child => {
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

        if (!selectedChildId && filteredChildren.length > 0) {
            selectChild(filteredChildren[0].id);
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
        filterChildrenList();
    }

    // ============================================================
    // CHART FUNCTIONS
    // ============================================================
    function updateCharts() {
        const childId = document.getElementById('childSelector').value;
        const child = getChildById(childId);
        if (!child) return;

        const data = getGrowthDataForChild(childId);
        if (data.length === 0) {
            document.getElementById('growthChart').innerHTML = '<div class="flex items-center justify-center h-full text-slate-400">No growth data available</div>';
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

        const gender = child.gender.toLowerCase();
        const ageMonths = data.map(d => getAgeInMonths(child.birth_date, d.date));
        const percentileData = WEIGHT_PERCENTILES[gender];
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
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">No measurements recorded</td></tr>';
            return;
        }

        tbody.innerHTML = data.map(d => {
            const ageMonths = getAgeInMonths(child.birth_date, d.date);
            const percentile = getWeightPercentile(parseFloat(d.weight), child.gender, ageMonths);
            const ageDisplay = ageMonths < 12 ? Math.round(ageMonths) + ' months' : (Math.round(ageMonths / 12) + ' yrs ' + Math.round(ageMonths % 12) + ' mos');
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
                </tr>
            `;
        }).join('');
    }

    // ============================================================
    // SAVE GROWTH MEASUREMENT
    // ============================================================
    function saveGrowthMeasurement(event) {
        event.preventDefault();
        showToast('Growth measurement added successfully!', 'success');
        closeModal('addGrowthModal');
        setTimeout(updateCharts, 500);
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