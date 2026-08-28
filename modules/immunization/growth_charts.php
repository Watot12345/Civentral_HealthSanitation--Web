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
            $firstName = $c['first_name'] ?? '';
            $lastName  = $c['last_name']  ?? '';

            $children[] = [
                'id'         => $cId,
                'child_id'   => $c['child_id'] ?? ('CH-' . sprintf('%03d', $cId)),
                'name'       => trim($firstName . ' ' . $lastName),
                'gender'     => !empty($c['gender']) ? ucfirst(strtolower($c['gender'])) : 'Female',
                'birth_date' => $birthDate,
                'age'        => $ageStr
            ];
        }
    }

    // Load recorded growth measurements
    if (!empty($dbGrowth) && is_array($dbGrowth)) {
        foreach ($dbGrowth as $g) {
            $growthData[] = [
                'id'                => (int)$g['id'],
                'child_id'          => (int)$g['child_id'],
                'date'              => $g['measurement_date'],
                'weight'            => (float)$g['weight'],
                'height'            => (float)$g['height'],
                'head_circumference' => !empty($g['head_circumference']) ? (float)$g['head_circumference'] : null,
                'notes'             => $g['notes'] ?? 'Routine Checkup'
            ];
        }
    }

    // ============================================================
    // TRIAGE ASSESSMENT BRIDGE
    // For each child, pull their latest triage assessment vitals
    // via the patients table (matched by first+last name) and
    // inject as a baseline "From Triage Assessment" entry so the
    // chart starts with real data. Skipped if a growth_measurements
    // record already exists for the same date (no duplicates).
    // ============================================================
    $existingDates = [];
    foreach ($growthData as $gd) {
        $existingDates[$gd['child_id']][] = substr($gd['date'], 0, 10);
    }

    foreach ($children as $child) {
        $firstName = explode(' ', $child['name'])[0] ?? '';
        $lastName  = trim(str_replace($firstName, '', $child['name']));

        try {
            // Find the matching patient by name
            $patients = $db->select('patients', [
                'first_name' => $firstName,
                'last_name'  => $lastName
            ], ['limit' => 1]);

            if (empty($patients)) continue;
            $patientId = (int)$patients[0]['id'];

            // Get their latest assessment with weight & height
            $assessments = $db->select('assessment', [
                'patient_id' => $patientId
            ], ['order' => 'created_at.desc', 'limit' => 5]);

            foreach ($assessments as $a) {
                if (empty($a['weight']) || empty($a['height'])) continue;

                $assessDate = substr($a['created_at'] ?? date('Y-m-d'), 0, 10);
                $childId    = $child['id'];

                // Skip if we already have a growth_measurement on this date
                if (in_array($assessDate, $existingDates[$childId] ?? [])) continue;
                // Skip if assessment date = birth date (bad data)
                if ($assessDate === substr($child['birth_date'], 0, 10)) continue;

                $growthData[] = [
                    'id'                => null, // read-only, not from growth_measurements
                    'child_id'          => $childId,
                    'date'              => $assessDate,
                    'weight'            => (float)$a['weight'],
                    'height'            => (float)$a['height'],
                    'head_circumference' => null,
                    'notes'             => 'From Triage Assessment'
                ];
                break; // only latest assessment per child
            }
        } catch (\Throwable $ex) {
            // silently skip if assessment table can't be queried
        }
    }

    // Sort all growth data by date ascending
    usort($growthData, fn($a, $b) => strcmp($a['date'], $b['date']));

} catch (\Throwable $e) {
    error_log('Supabase children/growth query exception: ' . $e->getMessage());
}

// ============================================================
// REAL WHO-BASED GROWTH ALERTS
// Checks each child's latest measurement against WHO P3/P15/P97
// thresholds (weight-for-age). Also detects growth faltering.
// ============================================================

// Helper: get WHO P3/P15/P97 ref for a given gender + age in months
function getWhoRef(array $percentileTable, string $gender, float $ageMonths): ?array {
    $gKey = strtolower($gender) === 'male' ? 'male' : 'female';
    if (!isset($percentileTable[$gKey])) return null;
    $ref = null;
    foreach ($percentileTable[$gKey] as $refAge => $values) {
        if ($ageMonths >= (float)$refAge) $ref = $values;
    }
    return $ref;
}

// Group growth measurements by child_id, sorted by date ascending
$growthByChild = [];
foreach ($growthData as $g) {
    $growthByChild[$g['child_id']][] = $g;
}

$growthAlerts = [];

foreach ($children as $c) {
    $cId   = $c['id'];
    $name  = $c['name'];
    $birth = new DateTime($c['birth_date']);

    $measurements = $growthByChild[$cId] ?? [];
    if (empty($measurements)) continue;

    // Sort by date ascending
    usort($measurements, fn($a, $b) => strcmp($a['date'], $b['date']));
    $latest   = end($measurements);
    $previous = count($measurements) >= 2 ? $measurements[count($measurements) - 2] : null;

    $measDate  = new DateTime($latest['date']);
    $ageMonths = (float)(($measDate->getTimestamp() - $birth->getTimestamp()) / (86400 * 30.44));
    $ageMonths = max(0, $ageMonths);

    // Guard: skip if measurement date is the same as birth date
    // (indicates a bad/synced record using birth date as measurement date)
    if ($measDate->format('Y-m-d') === $birth->format('Y-m-d')) continue;

    // Guard: skip physiologically impossible combinations
    // (e.g. age < 1 month but weight > 6 kg = wrong date on file)
    $weight = (float)$latest['weight'];
    if ($ageMonths < 1 && $weight > 6.0) continue;

    $gender = $c['gender'] ?? 'Female';

    // We need $weightPercentiles defined below — forward-reference workaround:
    // build a minimal inline copy for the alert check
    $whoTable = [
        'male' => [
            0  => ['p3' => 2.5, 'p15' => 2.8, 'p50' => 3.3, 'p85' => 3.8, 'p97' => 4.2],
            1  => ['p3' => 3.4, 'p15' => 3.8, 'p50' => 4.3, 'p85' => 4.9, 'p97' => 5.4],
            3  => ['p3' => 4.8, 'p15' => 5.2, 'p50' => 5.8, 'p85' => 6.4, 'p97' => 7.0],
            6  => ['p3' => 6.4, 'p15' => 6.9, 'p50' => 7.6, 'p85' => 8.4, 'p97' => 9.2],
            9  => ['p3' => 7.2, 'p15' => 7.8, 'p50' => 8.6, 'p85' => 9.4, 'p97' => 10.2],
            12 => ['p3' => 8.0, 'p15' => 8.6, 'p50' => 9.6, 'p85' => 10.5, 'p97' => 11.5],
            18 => ['p3' => 9.2, 'p15' => 10.0, 'p50' => 11.0, 'p85' => 12.2, 'p97' => 13.2],
            24 => ['p3' => 10.5, 'p15' => 11.2, 'p50' => 12.5, 'p85' => 13.8, 'p97' => 14.8],
            36 => ['p3' => 12.5, 'p15' => 13.2, 'p50' => 14.5, 'p85' => 16.0, 'p97' => 17.5],
        ],
        'female' => [
            0  => ['p3' => 2.4, 'p15' => 2.7, 'p50' => 3.2, 'p85' => 3.7, 'p97' => 4.1],
            1  => ['p3' => 3.2, 'p15' => 3.6, 'p50' => 4.1, 'p85' => 4.6, 'p97' => 5.1],
            3  => ['p3' => 4.5, 'p15' => 4.9, 'p50' => 5.5, 'p85' => 6.1, 'p97' => 6.7],
            6  => ['p3' => 6.0, 'p15' => 6.5, 'p50' => 7.2, 'p85' => 7.9, 'p97' => 8.7],
            9  => ['p3' => 6.8, 'p15' => 7.3, 'p50' => 8.0, 'p85' => 8.8, 'p97' => 9.6],
            12 => ['p3' => 7.5, 'p15' => 8.1, 'p50' => 9.0, 'p85' => 9.8, 'p97' => 10.8],
            18 => ['p3' => 8.8, 'p15' => 9.4, 'p50' => 10.5, 'p85' => 11.6, 'p97' => 12.6],
            24 => ['p3' => 10.0, 'p15' => 10.8, 'p50' => 12.0, 'p85' => 13.2, 'p97' => 14.2],
            36 => ['p3' => 12.0, 'p15' => 12.8, 'p50' => 14.0, 'p85' => 15.4, 'p97' => 16.8],
        ],
    ];

    $ref = getWhoRef($whoTable, $gender, $ageMonths);

    if ($ref) {
        $ageLabel = $ageMonths < 12
            ? round($ageMonths) . ' months'
            : floor($ageMonths / 12) . ' yr ' . (round($ageMonths) % 12) . ' mos';

        if ($weight <= $ref['p3']) {
            $growthAlerts[] = [
                'child'    => $name,
                'child_id' => $c['child_id'],
                'type'     => 'underweight',
                'message'  => "Weight {$weight} kg is below the 3rd WHO percentile (P3: {$ref['p3']} kg) at {$ageLabel} — Severe Underweight",
                'severity' => 'high',
                'date'     => $latest['date'],
            ];
        } elseif ($weight <= $ref['p15']) {
            $growthAlerts[] = [
                'child'    => $name,
                'child_id' => $c['child_id'],
                'type'     => 'at_risk',
                'message'  => "Weight {$weight} kg is between P3–P15 WHO percentiles at {$ageLabel} — At Risk of Underweight",
                'severity' => 'medium',
                'date'     => $latest['date'],
            ];
        } elseif ($weight >= $ref['p97']) {
            $growthAlerts[] = [
                'child'    => $name,
                'child_id' => $c['child_id'],
                'type'     => 'overweight',
                'message'  => "Weight {$weight} kg exceeds the 97th WHO percentile (P97: {$ref['p97']} kg) at {$ageLabel} — Overweight Risk",
                'severity' => 'medium',
                'date'     => $latest['date'],
            ];
        }
    }

    // Growth faltering: latest weight ≤ previous weight (no gain between visits)
    if ($previous !== null && (float)$latest['weight'] <= (float)$previous['weight']) {
        $alreadyFlagged = array_filter($growthAlerts, fn($a) => $a['child'] === $name && $a['type'] === 'faltering');
        if (empty($alreadyFlagged)) {
            $growthAlerts[] = [
                'child'    => $name,
                'child_id' => $c['child_id'],
                'type'     => 'faltering',
                'message'  => 'No weight gain between last two visits (' . $previous['date'] . ' → ' . $latest['date'] . ') — Growth Faltering',
                'severity' => 'medium',
                'date'     => $latest['date'],
            ];
        }
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
            <div class="flex gap-2 flex-wrap items-center">
                <select id="filterGenderGrowth" onchange="filterChildrenList()" 
                        class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select id="filterAgeGroup" onchange="filterChildrenList()" 
                        class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Ages</option>
                    <option value="0-1">0–1 yr</option>
                    <option value="1-2">1–2 yrs</option>
                    <option value="2-3">2–3 yrs</option>
                    <option value="3-5">3–5 yrs</option>
                </select>
                <select id="filterAlertStatus" onchange="filterChildrenList()" 
                        class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="alert">With Alerts</option>
                    <option value="normal">Normal</option>
                </select>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500">From</label>
                    <input type="date" id="filterDateFrom" aria-label="Measurement date from"
                        class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white"
                        onchange="updateCharts()">
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500">To</label>
                    <input type="date" id="filterDateTo" aria-label="Measurement date to"
                        class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white"
                        onchange="updateCharts()">
                </div>
                <button onclick="resetChildFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Child Card Grid Results -->
        <div class="mt-3 pt-3 border-t border-slate-100">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Select Child Profile</span>
                <span id="childCountInfo" class="text-xs font-semibold text-slate-400"></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-52 overflow-y-auto pr-1" id="childListContainer">
                <!-- Populated by JavaScript -->
            </div>
            <div id="noChildrenFound" class="hidden text-center py-6 text-sm text-slate-400">
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
        <div class="flex gap-2 flex-wrap">
            <button onclick="setChartType('weight')" id="btnWeight"
                class="px-4 py-2 text-sm font-semibold rounded-lg bg-brand-dark text-white hover:bg-brand-medium transition flex items-center gap-1.5">
                <i class="fa-solid fa-weight-scale text-xs"></i> Weight
            </button>
            <button onclick="setChartType('height')" id="btnHeight"
                class="px-4 py-2 text-sm font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                <i class="fa-solid fa-ruler-vertical text-xs"></i> Height
            </button>
            <button onclick="setChartType('bmi')" id="btnBmi"
                class="px-4 py-2 text-sm font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5">
                <i class="fa-solid fa-chart-pie text-xs"></i> BMI
            </button>
        </div>
        <span class="text-xs font-semibold text-slate-400 hidden sm:block">WHO 2006 Child Growth Standards</span>
    </div>

    <!-- Chart Container -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-4 mb-6 relative">
        <div id="growthChart" style="height: 400px;"></div>
    </div>

    <!-- Growth Data Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden mb-6">
        <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h4 class="text-sm font-bold text-slate-700">Measurement History</h4>
            <button onclick="openAddGrowthForSelected()" class="text-xs font-semibold text-brand-medium hover:text-brand-dark flex items-center gap-1 transition">
                <i class="fa-solid fa-plus"></i> Add Measurement
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Age</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Weight (kg)</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Height (cm)</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">BMI</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">WHO Percentile</th>
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
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden mb-6">
        <div class="px-4 py-3 bg-rose-50 border-b border-rose-200 flex items-center justify-between">
            <h4 class="text-sm font-bold text-rose-700 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> Growth Alerts
                <?php if (count($growthAlerts) > 0): ?>
                <span class="px-2 py-0.5 bg-rose-600 text-white rounded-full text-[10px] font-bold"><?php echo count($growthAlerts); ?></span>
                <?php endif; ?>
            </h4>
            <span class="text-[10px] text-rose-500 font-semibold">Based on WHO 2006 weight-for-age percentiles</span>
        </div>

        <?php if (empty($growthAlerts)): ?>
        <div class="flex flex-col items-center justify-center py-10 px-4 text-center">
            <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                <i class="fa-solid fa-shield-heart text-emerald-400 text-2xl"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No growth alerts detected</p>
            <p class="text-xs text-slate-400 mt-1">All children with recorded measurements are within normal WHO weight-for-age ranges.</p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-slate-100">
            <?php foreach ($growthAlerts as $alert): ?>
            <?php
                $severityStyles = [
                    'high'   => ['bg' => 'bg-rose-50',   'badge' => 'bg-rose-100 text-rose-700',   'icon' => 'text-rose-500',   'label' => 'High'],
                    'medium' => ['bg' => 'bg-amber-50',  'badge' => 'bg-amber-100 text-amber-700', 'icon' => 'text-amber-500', 'label' => 'Medium'],
                    'low'    => ['bg' => 'bg-blue-50',   'badge' => 'bg-blue-100 text-blue-700',   'icon' => 'text-blue-500',  'label' => 'Low'],
                ];
                $sev = $severityStyles[$alert['severity']] ?? $severityStyles['low'];
                $typeIcons = [
                    'underweight' => 'fa-arrow-trend-down',
                    'at_risk'     => 'fa-circle-exclamation',
                    'overweight'  => 'fa-arrow-trend-up',
                    'faltering'   => 'fa-ban',
                ];
                $icon = $typeIcons[$alert['type']] ?? 'fa-triangle-exclamation';
            ?>
            <div class="px-4 py-3 flex items-start gap-3 <?php echo $sev['bg']; ?>">
                <div class="mt-0.5 w-7 h-7 rounded-lg bg-white flex items-center justify-center flex-shrink-0 shadow-sm">
                    <i class="fa-solid <?php echo $icon; ?> text-xs <?php echo $sev['icon']; ?>"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($alert['child']); ?></p>
                        <span class="text-[10px] text-slate-400"><?php echo $alert['child_id'] ?? ''; ?></span>
                        <span class="text-[10px] text-slate-400">· <?php echo $alert['date'] ?? ''; ?></span>
                    </div>
                    <p class="text-xs text-slate-600 mt-0.5"><?php echo htmlspecialchars($alert['message']); ?></p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold flex-shrink-0 <?php echo $sev['badge']; ?>">
                    <?php echo $sev['label']; ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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
            const initials = child.name.split(' ').map(p => p[0]).join('').slice(0,2).toUpperCase();
            const genderColor = child.gender === 'Male'
                ? 'from-blue-500 to-blue-600 shadow-blue-200'
                : 'from-pink-500 to-pink-600 shadow-pink-200';
            return `
                <button onclick="selectChild(${child.id})"
                    class="child-card group flex items-center gap-3 p-3 rounded-xl border text-left transition-all w-full
                    ${isSelected
                        ? 'bg-brand-light border-brand-medium shadow-md shadow-brand-medium/10'
                        : 'bg-white border-slate-200 hover:border-brand-medium hover:bg-brand-light/40 hover:shadow-sm'}
                    ${hasAlert ? 'ring-2 ring-rose-300' : ''}">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br ${genderColor} flex items-center justify-center text-white font-black text-sm flex-shrink-0 shadow-lg">
                        ${initials}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-900 truncate flex items-center gap-1">
                            ${child.name}
                            ${isSelected ? '<i class="fa-solid fa-check-circle text-brand-medium text-xs"></i>' : ''}
                            ${hasAlert ? '<i class="fa-solid fa-triangle-exclamation text-rose-500 text-xs"></i>' : ''}
                        </p>
                        <p class="text-[10px] text-slate-500">${child.child_id} &bull; ${child.age}</p>
                    </div>
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
    const WHO_STANDARDS = {
        weight: {
            male: [
                { age: 0, label: '0m', p3: 2.5, p15: 2.8, p50: 3.3, p85: 3.8, p97: 4.2 },
                { age: 1, label: '1m', p3: 3.4, p15: 3.8, p50: 4.3, p85: 4.9, p97: 5.4 },
                { age: 3, label: '3m', p3: 4.8, p15: 5.2, p50: 5.8, p85: 6.4, p97: 7.0 },
                { age: 6, label: '6m', p3: 6.4, p15: 6.9, p50: 7.6, p85: 8.4, p97: 9.2 },
                { age: 9, label: '9m', p3: 7.2, p15: 7.8, p50: 8.6, p85: 9.4, p97: 10.2 },
                { age: 12, label: '12m', p3: 8.0, p15: 8.6, p50: 9.6, p85: 10.5, p97: 11.5 },
                { age: 18, label: '18m', p3: 9.2, p15: 10.0, p50: 11.0, p85: 12.2, p97: 13.2 },
                { age: 24, label: '24m', p3: 10.5, p15: 11.2, p50: 12.5, p85: 13.8, p97: 14.8 },
                { age: 36, label: '36m', p3: 12.5, p15: 13.2, p50: 14.5, p85: 16.0, p97: 17.5 },
                { age: 48, label: '48m', p3: 14.2, p15: 15.1, p50: 16.5, p85: 18.2, p97: 20.0 },
                { age: 60, label: '60m', p3: 16.0, p15: 17.1, p50: 18.7, p85: 20.7, p97: 22.8 }
            ],
            female: [
                { age: 0, label: '0m', p3: 2.4, p15: 2.7, p50: 3.2, p85: 3.7, p97: 4.1 },
                { age: 1, label: '1m', p3: 3.2, p15: 3.6, p50: 4.1, p85: 4.6, p97: 5.1 },
                { age: 3, label: '3m', p3: 4.5, p15: 4.9, p50: 5.5, p85: 6.1, p97: 6.7 },
                { age: 6, label: '6m', p3: 6.0, p15: 6.5, p50: 7.2, p85: 7.9, p97: 8.7 },
                { age: 9, label: '9m', p3: 6.8, p15: 7.3, p50: 8.0, p85: 8.8, p97: 9.6 },
                { age: 12, label: '12m', p3: 7.5, p15: 8.1, p50: 9.0, p85: 9.8, p97: 10.8 },
                { age: 18, label: '18m', p3: 8.8, p15: 9.4, p50: 10.5, p85: 11.6, p97: 12.6 },
                { age: 24, label: '24m', p3: 10.0, p15: 10.8, p50: 12.0, p85: 13.2, p97: 14.2 },
                { age: 36, label: '36m', p3: 12.0, p15: 12.8, p50: 14.0, p85: 15.4, p97: 16.8 },
                { age: 48, label: '48m', p3: 13.8, p15: 14.8, p50: 16.1, p85: 17.8, p97: 19.6 },
                { age: 60, label: '60m', p3: 15.5, p15: 16.7, p50: 18.2, p85: 20.3, p97: 22.4 }
            ]
        },
        height: {
            male: [
                { age: 0, label: '0m', p3: 46.1, p15: 48.0, p50: 49.9, p85: 51.8, p97: 53.7 },
                { age: 1, label: '1m', p3: 50.8, p15: 52.8, p50: 54.7, p85: 56.7, p97: 58.6 },
                { age: 3, label: '3m', p3: 57.3, p15: 59.4, p50: 61.4, p85: 63.5, p97: 65.5 },
                { age: 6, label: '6m', p3: 63.3, p15: 65.5, p50: 67.6, p85: 69.8, p97: 71.9 },
                { age: 9, label: '9m', p3: 67.7, p15: 69.7, p50: 72.0, p85: 74.2, p97: 76.5 },
                { age: 12, label: '12m', p3: 71.0, p15: 73.4, p50: 75.7, p85: 78.1, p97: 80.5 },
                { age: 18, label: '18m', p3: 76.9, p15: 79.6, p50: 82.3, p85: 85.0, p97: 87.7 },
                { age: 24, label: '24m', p3: 82.1, p15: 85.0, p50: 87.8, p85: 90.7, p97: 93.6 },
                { age: 36, label: '36m', p3: 90.4, p15: 93.2, p50: 96.1, p85: 99.2, p97: 102.3 },
                { age: 48, label: '48m', p3: 97.4, p15: 100.4, p50: 103.3, p85: 106.6, p97: 109.8 },
                { age: 60, label: '60m', p3: 104.1, p15: 107.0, p50: 110.0, p85: 113.6, p97: 117.0 }
            ],
            female: [
                { age: 0, label: '0m', p3: 45.4, p15: 47.2, p50: 49.1, p85: 51.0, p97: 52.9 },
                { age: 1, label: '1m', p3: 49.8, p15: 51.7, p50: 53.7, p85: 55.6, p97: 57.6 },
                { age: 3, label: '3m', p3: 55.6, p15: 57.7, p50: 59.8, p85: 61.9, p97: 64.0 },
                { age: 6, label: '6m', p3: 61.2, p15: 63.5, p50: 65.7, p85: 68.0, p97: 70.3 },
                { age: 9, label: '9m', p3: 65.3, p15: 67.7, p50: 70.1, p85: 72.6, p97: 75.0 },
                { age: 12, label: '12m', p3: 68.9, p15: 71.4, p50: 74.0, p85: 76.6, p97: 79.2 },
                { age: 18, label: '18m', p3: 74.9, p15: 77.8, p50: 80.7, p85: 83.6, p97: 86.5 },
                { age: 24, label: '24m', p3: 80.8, p15: 83.6, p50: 86.4, p85: 89.5, p97: 92.5 },
                { age: 36, label: '36m', p3: 89.3, p15: 92.2, p50: 95.1, p85: 98.3, p97: 101.4 },
                { age: 48, label: '48m', p3: 96.2, p15: 99.4, p50: 102.7, p85: 106.0, p97: 109.3 },
                { age: 60, label: '60m', p3: 102.7, p15: 106.0, p50: 109.4, p85: 113.0, p97: 116.6 }
            ]
        },
        bmi: {
            male: [
                { age: 0, label: '0m', p3: 11.2, p15: 12.2, p50: 13.4, p85: 14.8, p97: 16.0 },
                { age: 6, label: '6m', p3: 14.8, p15: 15.8, p50: 17.2, p85: 18.7, p97: 19.8 },
                { age: 12, label: '12m', p3: 14.6, p15: 15.6, p50: 16.8, p85: 18.2, p97: 19.4 },
                { age: 24, label: '24m', p3: 14.1, p15: 14.9, p50: 16.0, p85: 17.3, p97: 18.4 },
                { age: 36, label: '36m', p3: 13.7, p15: 14.5, p50: 15.6, p85: 16.8, p97: 17.8 },
                { age: 48, label: '48m', p3: 13.5, p15: 14.2, p50: 15.3, p85: 16.5, p97: 17.6 },
                { age: 60, label: '60m', p3: 13.3, p15: 14.0, p50: 15.3, p85: 16.6, p97: 17.9 }
            ],
            female: [
                { age: 0, label: '0m', p3: 11.0, p15: 12.0, p50: 13.3, p85: 14.6, p97: 15.8 },
                { age: 6, label: '6m', p3: 14.4, p15: 15.4, p50: 16.8, p85: 18.3, p97: 19.5 },
                { age: 12, label: '12m', p3: 14.2, p15: 15.1, p50: 16.4, p85: 17.8, p97: 19.0 },
                { age: 24, label: '24m', p3: 13.7, p15: 14.6, p50: 15.7, p85: 17.0, p97: 18.1 },
                { age: 36, label: '36m', p3: 13.4, p15: 14.2, p50: 15.3, p85: 16.6, p97: 17.6 },
                { age: 48, label: '48m', p3: 13.2, p15: 13.9, p50: 15.1, p85: 16.4, p97: 17.5 },
                { age: 60, label: '60m', p3: 13.1, p15: 13.8, p50: 15.2, p85: 16.7, p97: 18.0 }
            ]
        }
    };

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

        // Update header summary
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
            document.getElementById('growthChart').innerHTML = '<div class="flex flex-col items-center justify-center h-full text-slate-400 gap-2"><i class="fa-solid fa-chart-line text-3xl opacity-30"></i><p>No growth measurements recorded yet for this child.</p></div>';
            updateGrowthTable([], child);
            return;
        }

        const metricMeta = {
            weight: { name: 'Weight (kg)', unit: 'kg', color: '#0B4F4A', fillColor: '#14807A' },
            height: { name: 'Height (cm)', unit: 'cm', color: '#2563EB', fillColor: '#3B82F6' },
            bmi:    { name: 'BMI (kg/m²)', unit: 'kg/m²', color: '#7C3AED', fillColor: '#8B5CF6' }
        };
        const meta = metricMeta[currentChartType] || metricMeta.weight;

        const categories = [];
        const seriesData = [];

        data.forEach(d => {
            const dateFormatted = new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const ageMonths = getAgeInMonths(child.birth_date, d.date);
            const ageLabel = ageMonths < 12 ? Math.round(ageMonths) + 'm' : Math.floor(ageMonths / 12) + 'y ' + Math.round(ageMonths % 12) + 'm';
            categories.push(`${dateFormatted} (${ageLabel})`);

            let val = null;
            if (currentChartType === 'weight') val = parseFloat(d.weight);
            else if (currentChartType === 'height') val = parseFloat(d.height);
            else if (currentChartType === 'bmi') {
                val = (d.weight && d.height) ? parseFloat((parseFloat(d.weight) / Math.pow(parseFloat(d.height) / 100, 2)).toFixed(1)) : null;
            }
            seriesData.push(val);
        });

        const series = [
            {
                name: `${child.name} — ${meta.name}`,
                data: seriesData
            }
        ];

        const options = {
            series: series,
            chart: {
                type: 'area',
                height: 400,
                toolbar: {
                    show: true,
                    tools: { download: true, zoom: true, zoomin: true, zoomout: true, reset: true }
                },
                animations: { enabled: true, speed: 450 }
            },
            title: {
                text: `${child.name} — ${meta.name} Growth Timeline`,
                align: 'center',
                style: { fontSize: '15px', fontWeight: '700', color: '#0f172a' }
            },
            subtitle: {
                text: `Birth Date: ${child.birth_date} • Gender: ${child.gender} • Total Visits: ${data.length}`,
                align: 'center',
                style: { fontSize: '11px', color: '#64748b' }
            },
            xaxis: {
                categories: categories,
                title: { text: 'Visit Date & Age', style: { fontSize: '11px', fontWeight: '600', color: '#475569' } },
                labels: { style: { fontSize: '11px', fontWeight: '500' } }
            },
            yaxis: {
                title: { text: meta.name, style: { fontSize: '11px', fontWeight: '600', color: '#475569' } },
                labels: {
                    formatter: val => val !== null && val !== undefined ? `${val.toFixed(1)} ${meta.unit}` : ''
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3.5
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 95, 100]
                }
            },
            markers: {
                size: 7,
                strokeColors: meta.color,
                strokeWidth: 3,
                fillColors: ['#ffffff'],
                hover: { size: 10 }
            },
            colors: [meta.color],
            tooltip: {
                y: {
                    formatter: (val, opts) => {
                        const d = data[opts.dataPointIndex];
                        const noteStr = (d && d.notes) ? ` (${d.notes})` : '';
                        return `${val.toFixed(1)} ${meta.unit}${noteStr}`;
                    }
                }
            },
            grid: {
                borderColor: '#f1f5f9',
                strokeDashArray: 3
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
        const btnIds = ['btnWeight', 'btnHeight', 'btnBmi'];
        const activeClass = 'px-4 py-2 text-sm font-semibold rounded-lg bg-brand-dark text-white hover:bg-brand-medium transition flex items-center gap-1.5 shadow-xs';
        const inactiveClass = 'px-4 py-2 text-sm font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition flex items-center gap-1.5';
        const typeMap = { weight: 'btnWeight', height: 'btnHeight', bmi: 'btnBmi' };
        btnIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.className = (typeMap[type] === id) ? activeClass : inactiveClass;
        });

        updateCharts();
    }


    // ============================================================
    // GROWTH TABLE UPDATE
    // ============================================================
    function updateGrowthTable(data, child) {
        const tbody = document.getElementById('growthTableBody');
        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center">
                                <i class="fa-solid fa-chart-line text-slate-300 text-2xl"></i>
                            </div>
                            <p class="text-sm font-semibold text-slate-500">No measurements recorded yet for <span class="text-brand-dark">${child.name}</span></p>
                            <p class="text-xs text-slate-400">Start tracking their growth by logging the first measurement.</p>
                            <button onclick="openAddGrowthForSelected()" class="mt-1 px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold hover:bg-brand-medium transition flex items-center gap-2">
                                <i class="fa-solid fa-plus text-xs"></i> Log First Measurement
                            </button>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = data.map(d => {
            const ageMonths = getAgeInMonths(child.birth_date, d.date);
            const percentile = getWeightPercentile(parseFloat(d.weight), child.gender, ageMonths);
            const ageDisplay = ageMonths < 12 ? Math.round(ageMonths) + ' months' : (Math.round(ageMonths / 12) + ' yrs ' + Math.round(ageMonths % 12) + ' mos');
            const bmi = (d.weight && d.height) ? (parseFloat(d.weight) / Math.pow(parseFloat(d.height) / 100, 2)).toFixed(1) : '—';
            const bmiColor = bmi !== '—' ? (bmi < 14 ? 'text-rose-600 font-bold' : bmi > 18 ? 'text-blue-600 font-bold' : 'text-emerald-600') : '';
            const percentileColor = percentile.includes('Below') ? 'bg-rose-100 text-rose-700' : percentile.includes('Above') ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700';
            const deleteBtn = d.id ? `<button onclick="deleteGrowthMeasurement(${d.id})" class="p-1 text-rose-500 hover:bg-rose-50 rounded transition" title="Delete"><i class="fa-solid fa-trash text-xs"></i></button>` : '';
            return `
                <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors">
                    <td class="px-4 py-2 text-slate-600 text-xs">${new Date(d.date).toLocaleDateString()}</td>
                    <td class="px-4 py-2 text-slate-600 text-xs">${ageDisplay}</td>
                    <td class="px-4 py-2 text-slate-700 text-xs font-semibold">${d.weight} kg</td>
                    <td class="px-4 py-2 text-slate-600 text-xs">${d.height} cm</td>
                    <td class="px-4 py-2 text-xs ${bmiColor}">${bmi}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${percentileColor}">${percentile}</span>
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
            const res = await fetch(`<?php echo site_url('api/growth.php'); ?>?id=${id}`, { method: 'DELETE' });
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

    // Pre-fill current child when modal is opened from the table header
    function openAddGrowthForSelected() {
        if (selectedChildId) {
            const sel = document.getElementById('growth_child');
            if (sel) sel.value = selectedChildId;
        }
        openModal('addGrowthModal');
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
            const res = await fetch('<?php echo site_url('api/growth.php'); ?>', {
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

        // Pre-fill child selector when main Add Measurement button is clicked
        const addBtn = document.querySelector('[onclick="openModal(\'addGrowthModal\')"]');
        if (addBtn) {
            addBtn.setAttribute('onclick', 'openAddGrowthForSelected()');
        }
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