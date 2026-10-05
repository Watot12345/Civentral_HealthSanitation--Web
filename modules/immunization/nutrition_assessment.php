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
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
requireDepartmentAccess('immunization & nutrition');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/TriageQueue.php';
require_once __DIR__ . '/../../app/Models/Patient.php';
require_once __DIR__ . '/../../app/Models/Child.php';

$db = Database::getInstance();
$triageQueueModel = new TriageQueue();
$patientModel = new Patient();
$childModel = new Child();

// Fetch Active Patients Waiting for Nutrition Assessment Visits
$nutritionVisitsRaw = [];
try {
    $nutritionVisitsRaw = $triageQueueModel->getVisitsByReason('Nutrition Assessment', date('Y-m-d'));
} catch (\Throwable $e) {
    error_log('Error fetching nutrition visits: ' . $e->getMessage());
}

$nutritionVisits = [];
foreach ($nutritionVisitsRaw as $v) {
    $pId = (int)($v['patient_id'] ?? 0);
    $p = null;
    try { if ($pId > 0) $p = $patientModel->find($pId); } catch (\Throwable $e) {}
    
    $name = 'Patient #' . $pId;
    $pCode = 'P-' . $pId;
    if ($p) {
        $firstName = $p['first_name'] ?? '';
        $lastName = $p['last_name'] ?? '';
        $name = trim($firstName . ' ' . $lastName) ?: ($p['name'] ?? $name);
        $pCode = $p['patient_id'] ?? $pCode;
    }
    
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }

    // Pull triage intake vitals (weight, height, BMI)
    $triageVitals = null;
    try {
        $assRows = $db->select('assessment', ['patient_id' => $pId], ['order' => 'id.desc', 'limit' => 1]);
        if (!empty($assRows)) {
            $triageVitals = $assRows[0];
        }
    } catch (\Throwable $e) {}

    $weight = $triageVitals['weight'] ?? null;
    $height = $triageVitals['height'] ?? null;
    $bmi = $triageVitals['bmi'] ?? null;
    
    $nutritionVisits[] = [
        'id' => $v['id'],
        'patient_id' => $pId,
        'patient_name' => $name,
        'patient_code' => $pCode,
        'avatar' => substr($initials, 0, 2) ?: 'P',
        'weight' => $weight ? (float)$weight : null,
        'height' => $height ? (float)$height : null,
        'bmi' => $bmi ? (float)$bmi : null,
        'check_in_time' => isset($v['check_in_time']) ? date('h:i A', strtotime($v['check_in_time'])) : (isset($v['created_at']) ? date('h:i A', strtotime($v['created_at'])) : date('h:i A')),
        'status' => $v['status'] ?? 'waiting'
    ];
}

// Base Children for Pediatric Screening Selectors
$children = [];
try {
    $dbChildren = $db->select('children', [], ['order' => 'first_name.asc']);

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
                'age' => $ageStr,
                'birth_date' => $birthDate,
                'source' => 'child'
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Error querying children: ' . $e->getMessage());
}

// Fetch Adult & Senior Patients from Health Center Services
$adultSeniorPatients = [];
try {
    $rawPatients = $db->select('patients', [], ['limit' => 150, 'order' => 'first_name.asc']);
    foreach ($rawPatients as $rp) {
        $dob = $rp['date_of_birth'] ?? $rp['birth_date'] ?? null;
        $age = !empty($dob) ? (int)date_diff(date_create($dob), date_create('today'))->y : 30;
        $ptType = ($age >= 60) ? 'Senior' : 'Adult';
        $adultSeniorPatients[] = [
            'id' => (int)$rp['id'],
            'name' => trim(($rp['first_name'] ?? '') . ' ' . ($rp['last_name'] ?? '')),
            'code' => $rp['patient_id'] ?? ('P-' . $rp['id']),
            'type' => $ptType,
            'age' => $age . ' yrs',
            'age_years' => $age
        ];
    }
} catch (\Throwable $e) {
    error_log('Error fetching adult/senior patients: ' . $e->getMessage());
}

// Real Nutrition Assessments from Database (Polymorphic: Child, Adult, Senior)
$nutritionAssessments = [];
try {
    $db = Database::getInstance();
    $dbAssessments = $db->select('nutrition_assessments', [], ['order' => 'assessment_date.desc']);
    $childrenMap = array_column($children, null, 'id');
    $patientsMap = array_column($adultSeniorPatients, null, 'id');

    if (!empty($dbAssessments) && is_array($dbAssessments)) {
        foreach ($dbAssessments as $a) {
            $cId = (int)($a['child_id'] ?? 0);
            $pId = (int)($a['patient_id'] ?? 0);
            $patientType = strtolower($a['patient_type'] ?? ($pId > 0 ? 'adult' : 'child'));

            if ($patientType !== 'child' && $pId > 0) {
                $pt = $patientsMap[$pId] ?? null;
                $cName = $pt ? $pt['name'] : ('Patient #' . $pId);
                $cAge = $pt ? $pt['age'] : '—';
                $badgeType = ucfirst($patientType);
            } else {
                $child = $childrenMap[$cId] ?? null;
                $cName = $child ? $child['name'] : ('Child #' . $cId);
                $cAge = $child ? $child['age'] : '—';
                $badgeType = 'Pediatric';
            }

            $initials = ($badgeType === 'Pediatric') ? 'CH' : ($badgeType === 'Senior' ? 'SR' : 'AD');
            if (!empty($cName)) {
                $parts = explode(' ', $cName);
                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
            }

            $supplements = [];
            if (!empty($a['supplements'])) {
                $supplements = is_array($a['supplements']) ? $a['supplements'] : json_decode($a['supplements'], true);
            }
            if (!is_array($supplements)) $supplements = [];

            $nutritionAssessments[] = [
                'id' => (int)$a['id'],
                'child_id' => $cId,
                'patient_id' => $pId,
                'patient_type' => $patientType,
                'badge_type' => $badgeType,
                'child_name' => $cName,
                'child_avatar' => $initials,
                'date' => $a['assessment_date'],
                'age' => $cAge,
                'weight' => (float)$a['weight'],
                'height' => (float)$a['height'],
                'bmi' => (float)($a['bmi'] ?? 0),
                'weight_percentile' => (int)($a['weight_percentile'] ?? 50),
                'height_percentile' => (int)($a['height_percentile'] ?? 50),
                'nutrition_status' => strtolower($a['nutrition_status'] ?? 'normal'),
                'risk_level' => strtolower($a['risk_level'] ?? 'low'),
                'assessment_notes' => $a['assessment_notes'] ?? '',
                'plan_of_action' => $a['plan_of_action'] ?? '',
                'supplements' => $supplements,
                'next_assessment' => $a['next_assessment_date'] ?? null,
                'assessed_by' => $a['assessed_by'] ?? 'Staff',
                'status' => $a['status'] ?? 'active'
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Error querying nutrition_assessments: ' . $e->getMessage());
}

// Sample Supplement Inventory
$supplementInventory = [
    ['id' => 1, 'name' => 'Vitamin A', 'category' => 'Vitamin', 'stock' => 150, 'min_stock' => 50, 'unit' => 'capsules'],
    ['id' => 2, 'name' => 'Iron', 'category' => 'Mineral', 'stock' => 200, 'min_stock' => 60, 'unit' => 'tablets'],
    ['id' => 3, 'name' => 'Zinc', 'category' => 'Mineral', 'stock' => 120, 'min_stock' => 40, 'unit' => 'tablets'],
    ['id' => 4, 'name' => 'Vitamin D', 'category' => 'Vitamin', 'stock' => 180, 'min_stock' => 50, 'unit' => 'capsules'],
    ['id' => 5, 'name' => 'Multivitamin', 'category' => 'Vitamin', 'stock' => 90, 'min_stock' => 30, 'unit' => 'tablets'],
    ['id' => 6, 'name' => 'Calcium', 'category' => 'Mineral', 'stock' => 160, 'min_stock' => 40, 'unit' => 'tablets'],
    ['id' => 7, 'name' => 'Folic Acid', 'category' => 'Vitamin', 'stock' => 75, 'min_stock' => 25, 'unit' => 'tablets'],
];

// Stats
$totalAssessments = count($nutritionAssessments);
$normalStatus = count(array_filter($nutritionAssessments, fn($a) => $a['nutrition_status'] === 'normal'));
$moderateStatus = count(array_filter($nutritionAssessments, fn($a) => $a['nutrition_status'] === 'moderate'));
$criticalStatus = count(array_filter($nutritionAssessments, fn($a) => $a['nutrition_status'] === 'critical'));
$activePlans = count(array_filter($nutritionAssessments, fn($a) => $a['status'] === 'active'));

// ============================================================
// GROWTH MEASUREMENTS & WHO PERCENTILES DATA
// ============================================================
$growthData = [];
$dbGrowth = [];
try {
    $dbGrowth = $db->select('growth_measurements', [], ['order' => 'measurement_date.asc']);
} catch (\Throwable $ex) {
    $dbGrowth = [];
}

if (!empty($dbGrowth) && is_array($dbGrowth)) {
    foreach ($dbGrowth as $g) {
        $growthData[] = [
            'id'                => (int)$g['id'],
            'child_id'          => (int)$g['child_id'],
            'date'              => $g['measurement_date'],
            'weight'            => (float)$g['weight'],
            'height'            => (float)$g['height'],
            'head_circumference'=> !empty($g['head_circumference']) ? (float)$g['head_circumference'] : null,
            'notes'             => $g['notes'] ?? 'Routine Checkup'
        ];
    }
}

// Triage Assessment Bridge for Children
$existingGrowthDates = [];
foreach ($growthData as $gd) {
    $existingGrowthDates[$gd['child_id']][] = substr($gd['date'], 0, 10);
}

foreach ($children as $childItem) {
    $firstName = explode(' ', $childItem['name'])[0] ?? '';
    $lastName  = trim(str_replace($firstName, '', $childItem['name']));

    try {
        $pMatches = $db->select('patients', [
            'first_name' => $firstName,
            'last_name'  => $lastName
        ], ['limit' => 1]);

        if (empty($pMatches)) continue;
        $pId = (int)$pMatches[0]['id'];

        $tAssessments = $db->select('assessment', [
            'patient_id' => $pId
        ], ['order' => 'created_at.desc', 'limit' => 3]);

        foreach ($tAssessments as $ta) {
            if (empty($ta['weight']) || empty($ta['height'])) continue;
            $assessDate = substr($ta['created_at'] ?? date('Y-m-d'), 0, 10);
            $cId = $childItem['id'];
            if (in_array($assessDate, $existingGrowthDates[$cId] ?? [])) continue;
            if ($assessDate === substr($childItem['birth_date'], 0, 10)) continue;

            $growthData[] = [
                'id'                => null,
                'child_id'          => $cId,
                'date'              => $assessDate,
                'weight'            => (float)$ta['weight'],
                'height'            => (float)$ta['height'],
                'head_circumference'=> null,
                'notes'             => 'From Triage Assessment'
            ];
            break;
        }
    } catch (\Throwable $ex) {}
}

// Nutrition Assessment Bridge for Children
foreach ($nutritionAssessments as $na) {
    if (empty($na['child_id']) || empty($na['weight']) || empty($na['height'])) continue;
    $cId = (int)$na['child_id'];
    $assessDate = substr($na['date'] ?? date('Y-m-d'), 0, 10);
    if (in_array($assessDate, $existingGrowthDates[$cId] ?? [])) continue;
    $existingGrowthDates[$cId][] = $assessDate;

    $growthData[] = [
        'id'                => (int)$na['id'],
        'child_id'          => $cId,
        'date'              => $assessDate,
        'weight'            => (float)$na['weight'],
        'height'            => (float)$na['height'],
        'head_circumference'=> null,
        'notes'             => $na['assessment_notes'] ?? 'From Nutrition Assessment'
    ];
}

usort($growthData, fn($a, $b) => strcmp($a['date'], $b['date']));

// WHO Growth Reference Percentiles (Standard DOH Growth Chart Benchmarks)
$weightPercentiles = [
    'male' => [
        '0'  => ['p3' => 2.5, 'p15' => 2.8, 'p50' => 3.3, 'p85' => 3.8, 'p97' => 4.2],
        '1'  => ['p3' => 3.4, 'p15' => 3.8, 'p50' => 4.3, 'p85' => 4.9, 'p97' => 5.4],
        '3'  => ['p3' => 4.8, 'p15' => 5.2, 'p50' => 5.8, 'p85' => 6.4, 'p97' => 7.0],
        '6'  => ['p3' => 6.4, 'p15' => 6.9, 'p50' => 7.6, 'p85' => 8.4, 'p97' => 9.2],
        '9'  => ['p3' => 7.2, 'p15' => 7.8, 'p50' => 8.6, 'p85' => 9.4, 'p97' => 10.2],
        '12' => ['p3' => 8.0, 'p15' => 8.6, 'p50' => 9.6, 'p85' => 10.5, 'p97' => 11.5],
        '18' => ['p3' => 9.2, 'p15' => 10.0, 'p50' => 11.0, 'p85' => 12.2, 'p97' => 13.2],
        '24' => ['p3' => 10.5, 'p15' => 11.2, 'p50' => 12.5, 'p85' => 13.8, 'p97' => 14.8],
        '36' => ['p3' => 12.5, 'p15' => 13.2, 'p50' => 14.5, 'p85' => 16.0, 'p97' => 17.5],
    ],
    'female' => [
        '0'  => ['p3' => 2.4, 'p15' => 2.7, 'p50' => 3.2, 'p85' => 3.7, 'p97' => 4.1],
        '1'  => ['p3' => 3.2, 'p15' => 3.6, 'p50' => 4.1, 'p85' => 4.6, 'p97' => 5.1],
        '3'  => ['p3' => 4.5, 'p15' => 4.9, 'p50' => 5.5, 'p85' => 6.1, 'p97' => 6.7],
        '6'  => ['p3' => 6.0, 'p15' => 6.5, 'p50' => 7.2, 'p85' => 7.9, 'p97' => 8.7],
        '9'  => ['p3' => 6.8, 'p15' => 7.3, 'p50' => 8.0, 'p85' => 8.8, 'p97' => 9.6],
        '12' => ['p3' => 7.5, 'p15' => 8.1, 'p50' => 9.0, 'p85' => 9.8, 'p97' => 10.8],
        '18' => ['p3' => 8.8, 'p15' => 9.4, 'p50' => 10.5, 'p85' => 11.6, 'p97' => 12.6],
        '24' => ['p3' => 10.0, 'p15' => 10.8, 'p50' => 12.0, 'p85' => 13.2, 'p97' => 14.2],
        '36' => ['p3' => 12.0, 'p15' => 12.8, 'p50' => 14.0, 'p85' => 15.4, 'p97' => 16.8],
    ]
];

function getWhoPercentileRef(array $percentileTable, string $gender, float $ageMonths): ?array {
    $gKey = strtolower($gender) === 'male' ? 'male' : 'female';
    if (!isset($percentileTable[$gKey])) return null;
    $ref = null;
    foreach ($percentileTable[$gKey] as $refAge => $values) {
        if ($ageMonths >= (float)$refAge) $ref = $values;
    }
    return $ref;
}

$growthByChild = [];
foreach ($growthData as $g) {
    $growthByChild[$g['child_id']][] = $g;
}

$growthAlerts = [];
foreach ($children as $c) {
    $cId = $c['id'];
    $name = $c['name'];
    $birth = new DateTime($c['birth_date']);
    $measurements = $growthByChild[$cId] ?? [];
    if (empty($measurements)) continue;

    usort($measurements, fn($a, $b) => strcmp($a['date'], $b['date']));
    $latest = end($measurements);
    $measDate = new DateTime($latest['date']);
    $ageMonths = max(0, (float)(($measDate->getTimestamp() - $birth->getTimestamp()) / (86400 * 30.44)));

    if ($measDate->format('Y-m-d') === $birth->format('Y-m-d')) continue;
    $weight = (float)$latest['weight'];
    if ($ageMonths < 1 && $weight > 6.0) continue;

    $ref = getWhoPercentileRef($weightPercentiles, $c['gender'] ?? 'Female', $ageMonths);
    if ($ref) {
        $ageLabel = $ageMonths < 12 ? round($ageMonths) . ' months' : floor($ageMonths / 12) . ' yr ' . (round($ageMonths) % 12) . ' mos';
        if ($weight <= $ref['p3']) {
            $growthAlerts[] = [
                'child'    => $name,
                'child_id' => $c['child_id'],
                'type'     => 'underweight',
                'message'  => "Weight {$weight} kg is below 3rd WHO percentile (P3: {$ref['p3']} kg) at {$ageLabel} — Severe Underweight",
                'severity' => 'high',
                'date'     => $latest['date']
            ];
        } elseif ($weight <= $ref['p15']) {
            $growthAlerts[] = [
                'child'    => $name,
                'child_id' => $c['child_id'],
                'type'     => 'at_risk',
                'message'  => "Weight {$weight} kg is between P3–P15 WHO percentiles at {$ageLabel} — At Risk of Underweight",
                'severity' => 'medium',
                'date'     => $latest['date']
            ];
        }
    }
}
$childrenWithAlerts = count(array_unique(array_column($growthAlerts, 'child')));
$totalGrowthMeasurements = count($growthData);

$title = 'Nutrition & Growth Monitoring';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fa-solid fa-heart-pulse text-brand-medium text-xl"></i>
                Nutrition & Growth Monitoring
            </h2>
            <p class="text-sm text-slate-500 mt-0.5">Clinical nutritional screening, WHO growth percentiles & dietary intervention plans</p>
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <button onclick="openModal('nutritionQueueModal')"
                    class="px-3.5 py-2 bg-white border border-emerald-200 text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-apple-whole text-xs text-emerald-600"></i> Waiting Queue
                <?php if (count($nutritionVisits) > 0): ?>
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[10px] font-bold">
                        <?php echo count($nutritionVisits); ?>
                    </span>
                <?php endif; ?>
            </button>
            <button onclick="openModal('nutritionScreeningModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-clipboard-list text-xs"></i> New Screening
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Assessments -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-clipboard-list text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalAssessments; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Assessments</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All assessments</span>
                    <span class="text-[10px] text-slate-400"><?php echo $activePlans; ?> active plans</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Normal -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $normalStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Normal</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Healthy</span>
                    <span class="text-[10px] text-slate-400">On track</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Moderate -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $moderateStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Moderate</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⚠️ Monitor</span>
                    <span class="text-[10px] text-slate-400">Needs attention</span>
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
                    <span class="text-[10px] text-slate-400">Immediate intervention</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Active Plans -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-clipboard text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark"><?php echo $activePlans; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active Plans</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-brand-light text-brand-dark rounded-full text-[10px] font-bold">📋 In progress</span>
                    <span class="text-[10px] text-slate-400">Currently monitored</span>
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
                <span class="font-bold"><?php echo $criticalStatus; ?></span> child(ren) require immediate nutrition intervention
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='critical'; filterAssessments();" 
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
                       id="searchNutrition"
                       placeholder="Search by child name or ID..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="normal">Normal</option>
                    <option value="moderate">Moderate</option>
                    <option value="critical">Critical</option>
                </select>
                <select id="filterRisk" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Risk</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
                <button type="button" onclick="openModal('assessmentDateFilterModal')" title="Filter by assessment date"
                        class="px-3 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition-colors text-sm">
                    <i class="fa-solid fa-calendar-days"></i>
                </button>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Nutrition Assessments Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient / Child</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Weight (kg)</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Height (cm)</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Risk</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Next Assessment</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="nutritionTableBody">
                    <?php foreach ($nutritionAssessments as $assessment): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors nutrition-row <?php echo $assessment['nutrition_status'] === 'critical' ? 'bg-rose-50/50' : ''; ?>"
                        data-child="<?php echo strtolower($assessment['child_name']); ?>"
                        data-status="<?php echo $assessment['nutrition_status']; ?>"
                        data-risk="<?php echo $assessment['risk_level']; ?>"
                        data-date="<?php echo htmlspecialchars($assessment['date']); ?>"
                        data-id="<?php echo htmlspecialchars($assessment['child_id'] ?: $assessment['patient_id']); ?>"
                        data-assessment-id="<?php echo (int)$assessment['id']; ?>">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                                    <?php echo $assessment['child_avatar']; ?>
                                </div>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($assessment['child_name']); ?></p>
                                        <?php if (!empty($assessment['badge_type'])): ?>
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded <?php echo $assessment['badge_type'] === 'Senior' ? 'bg-purple-100 text-purple-700' : ($assessment['badge_type'] === 'Adult' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'); ?>">
                                                <?php echo $assessment['badge_type']; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-slate-400"><?php echo $assessment['age']; ?><?php if (!empty($assessment['bmi'])): ?> • BMI: <?php echo $assessment['bmi']; ?><?php endif; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo date('M d, Y', strtotime($assessment['date'])); ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs font-medium"><?php echo $assessment['weight']; ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $assessment['height']; ?></td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'normal' => 'bg-emerald-100 text-emerald-700',
                                    'moderate' => 'bg-amber-100 text-amber-700',
                                    'critical' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$assessment['nutrition_status']] ?? $statusColors['normal']; ?>">
                                <?php echo ucfirst($assessment['nutrition_status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $riskColors = [
                                    'low' => 'bg-emerald-100 text-emerald-700',
                                    'medium' => 'bg-amber-100 text-amber-700',
                                    'high' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $riskColors[$assessment['risk_level']] ?? $riskColors['low']; ?>">
                                <?php echo ucfirst($assessment['risk_level']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php echo $assessment['next_assessment'] ? date('M d, Y', strtotime($assessment['next_assessment'])) : '—'; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewAssessment(<?php echo $assessment['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="editAssessment(<?php echo $assessment['id']; ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                                <?php if (!empty($assessment['child_id'])): ?>
                                    <button onclick="showGrowthChart(<?php echo (int)$assessment['child_id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition flex items-center gap-1" title="View WHO Growth Chart">
                                        <i class="fa-solid fa-chart-line text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($assessment['nutrition_status'] === 'critical'): ?>
                                    <button onclick="referToDoctorTriage(<?php echo htmlspecialchars(json_encode($assessment), ENT_QUOTES); ?>)"
                                            class="p-1.5 text-rose-600 hover:bg-rose-100 rounded-lg transition" title="1-Click Refer to Doctor Triage">
                                        <i class="fa-solid fa-user-doctor text-sm"></i>
                                    </button>
                                    <button onclick="emergencyIntervention(<?php echo $assessment['id']; ?>)"
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Emergency Intervention">
                                        <i class="fa-solid fa-truck-medical text-sm"></i>
                                    </button>
                                <?php endif; ?>
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
                <i class="fa-solid fa-apple-alt text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No assessments match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo $totalAssessments; ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalAssessments; ?></span> assessments
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
<!-- NUTRITION SCREENING MODAL                                    -->
<!-- ============================================================ -->
<div id="nutritionScreeningModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-brand-medium"></i>
                Nutrition Screening
            </h3>
            <button onclick="closeModal('nutritionScreeningModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="nutritionScreeningForm" class="p-6 space-y-4" onsubmit="saveNutritionScreening(event)">
            <input type="hidden" id="screen_patient_type" value="child">

            <!-- Category Switcher -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Target Patient Category</label>
                <div class="flex p-1 bg-slate-100 rounded-lg gap-1">
                    <button type="button" id="tab_screen_child_btn" onclick="switchNutritionCategory('child')" class="flex-1 py-1.5 text-xs font-bold rounded-md bg-white text-brand-dark shadow-xs transition">
                        👶 Pediatric (0–5 yrs)
                    </button>
                    <button type="button" id="tab_screen_adult_btn" onclick="switchNutritionCategory('adult')" class="flex-1 py-1.5 text-xs font-bold rounded-md text-slate-600 hover:text-slate-900 transition">
                        👨‍👩‍👧 Adult & Senior Patient
                    </button>
                </div>
            </div>

            <!-- Pediatric Child Selector -->
            <div id="wrapper_screen_child">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Child</label>
                <select id="screen_child" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">-- Select Child --</option>
                    <?php foreach ($children as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['child_id']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Adult / Senior Patient Selector -->
            <div id="wrapper_screen_patient" class="hidden">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Patient (Health Center Registry)</label>
                <select id="screen_patient" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" onchange="const opt = this.options[this.selectedIndex]; if(opt) document.getElementById('screen_patient_type').value = opt.getAttribute('data-type') || 'adult';">
                    <option value="">-- Choose Adult or Senior Patient --</option>
                    <?php foreach ($adultSeniorPatients as $ap): ?>
                        <option value="<?php echo $ap['id']; ?>" data-type="<?php echo strtolower($ap['type']); ?>">
                            [<?php echo htmlspecialchars($ap['type']); ?>] <?php echo htmlspecialchars($ap['name']); ?> (<?php echo htmlspecialchars($ap['code']); ?> - <?php echo htmlspecialchars($ap['age']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Date</label>
                <input type="date" id="screen_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Weight (kg)</label>
                    <input type="number" id="screen_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitNutritionMeasurement(this); calculateLiveBmi();" required title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Height (cm)</label>
                    <input type="number" id="screen_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitNutritionMeasurement(this); calculateLiveBmi();" required title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <!-- Dynamic Live BMI Display & Guideline -->
            <div id="bmi_indicator_box" class="p-3 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-500 font-medium">Calculated BMI:</span>
                    <span id="calculated_bmi_val" class="font-bold text-slate-800 ml-1">—</span>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Category:</span>
                    <span id="calculated_bmi_category" class="font-bold text-slate-700 ml-1">Awaiting measurements</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Nutrition Status</label>
                <select id="screen_status" onchange="checkCriticalAlert()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="normal">Normal</option>
                    <option value="moderate">Moderate</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Risk Level</label>
                <select id="screen_risk" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>

            <!-- Critical Status Alert Banner -->
            <div id="critical_referral_alert" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800 flex items-start gap-2">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5 flex-shrink-0"></i>
                <div>
                    <p class="font-bold">Severe Malnutrition / Critical Risk Detected</p>
                    <p class="text-rose-700 mt-0.5">Saving this assessment will enable immediate 1-click triage referral to Health Center Services Doctor Consultation.</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Notes</label>
                <textarea id="screen_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Observations and findings..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Next Assessment Date</label>
                <input type="date" id="screen_next" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('nutritionScreeningModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Save Assessment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ASSESSMENT DATE FILTER MODAL -->
<div id="assessmentDateFilterModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-calendar-days text-brand-medium"></i> Assessment Date Filter</h3>
            <button type="button" onclick="closeModal('assessmentDateFilterModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div><label for="assessmentDateFrom" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">From</label><input type="date" id="assessmentDateFrom" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
            <div><label for="assessmentDateTo" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">To</label><input type="date" id="assessmentDateTo" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="clearAssessmentDateFilter()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">Clear</button>
                <button type="button" onclick="applyAssessmentDateFilter()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">Apply Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT ASSESSMENT MODAL -->
<div id="editAssessmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Assessment</h3>
            <button type="button" onclick="closeModal('editAssessmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveEditedAssessment(event)">
            <input type="hidden" id="edit_assessment_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Date</label><input type="date" id="edit_assessment_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Weight (kg)</label><input type="number" id="edit_assessment_weight" min="0.1" max="999" step="0.1" required oninput="limitNutritionMeasurement(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Height (cm)</label><input type="number" id="edit_assessment_height" min="20" max="999" step="0.1" required oninput="limitNutritionMeasurement(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_assessment_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="normal">Normal</option><option value="moderate">Moderate</option><option value="critical">Critical</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Risk Level</label><select id="edit_assessment_risk" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Notes</label><textarea id="edit_assessment_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editAssessmentModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<!-- EMERGENCY INTERVENTION MODAL -->
<div id="emergencyInterventionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center">
                    <i class="fa-solid fa-truck-medical text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900">Emergency Intervention</h3>
                    <p id="emergencyAssessmentChild" class="text-sm text-slate-500"></p>
                </div>
            </div>
            
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1">Intervention Notes</label>
            <textarea id="emergencyInterventionNotes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Describe the immediate action...">Immediate nutrition intervention required.</textarea>
            
            <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg flex items-center gap-2.5">
                <input type="checkbox" id="queue_for_doctor_triage" checked class="w-4 h-4 text-brand-dark rounded border-slate-300 focus:ring-brand-medium">
                <label for="queue_for_doctor_triage" class="text-xs font-semibold text-emerald-900 cursor-pointer">
                    Queue for Doctor Consultation Triage (Health Center Services)
                </label>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" onclick="closeModal('emergencyInterventionModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button>
                <button type="button" onclick="confirmEmergencyIntervention()" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-semibold hover:bg-rose-700 transition">
                    <i class="fa-solid fa-paper-plane mr-1.5"></i> Submit & Refer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW ASSESSMENT MODAL                                        -->
<!-- ============================================================ -->
<div id="viewAssessmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Nutrition Assessment Details</h3>
            <button onclick="closeModal('viewAssessmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="assessmentDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SUPPLEMENT TRACKING MODAL                                    -->
<!-- ============================================================ -->
<div id="supplementTrackingModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pills text-brand-medium"></i>
                Supplement Tracking
            </h3>
            <button onclick="closeModal('supplementTrackingModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                <?php foreach ($supplementInventory as $supp): ?>
                <div class="bg-white rounded-xl shadow-xs p-3 border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-800 text-sm"><?php echo $supp['name']; ?></p>
                            <p class="text-xs text-slate-400"><?php echo $supp['category']; ?></p>
                        </div>
                        <span class="text-xs font-bold <?php echo $supp['stock'] <= $supp['min_stock'] ? 'text-rose-600' : 'text-slate-600'; ?>">
                            <?php echo $supp['stock']; ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full <?php echo $supp['stock'] <= $supp['min_stock'] ? 'bg-rose-500' : 'bg-emerald-500'; ?>" 
                                 style="width: <?php echo min(100, ($supp['stock'] / ($supp['min_stock'] * 2)) * 100); ?>%"></div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[10px] text-slate-400">Min: <?php echo $supp['min_stock']; ?></span>
                        <button onclick="adjustSupplementStock(<?php echo $supp['id']; ?>)" class="text-[10px] text-brand-medium hover:text-brand-dark">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-sm font-bold text-slate-700 mb-2">📋 Supplement Distribution Summary</h4>
                <div class="space-y-2">
                    <?php 
                        $distributed = [];
                        foreach ($nutritionAssessments as $a) {
                            foreach ($a['supplements'] as $s) {
                                $distributed[$s] = ($distributed[$s] ?? 0) + 1;
                            }
                        }
                        arsort($distributed);
                    ?>
                    <?php foreach (array_slice($distributed, 0, 5) as $name => $count): ?>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-600"><?php echo $name; ?></span>
                        <span class="font-semibold text-brand-dark"><?php echo $count; ?> children</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- WAITING QUEUE MODAL (TODAY'S VISITS)                         -->
<!-- ============================================================ -->
<div id="nutritionQueueModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-emerald-200 sticky top-0 bg-emerald-50/90 backdrop-blur-xs rounded-t-2xl z-10">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700">
                    <i class="fa-solid fa-apple-whole text-sm"></i>
                </div>
                <div>
                    <h3 class="font-bold text-emerald-950 text-base">Patients Waiting for Nutrition Assessment</h3>
                    <p class="text-xs text-emerald-700">Today's active triage queue entries for nutrition screening</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold"><?php echo count($nutritionVisits); ?> active visit(s)</span>
                <button onclick="closeModal('nutritionQueueModal')" class="w-8 h-8 rounded-lg hover:bg-emerald-200/50 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($nutritionVisits)): ?>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-slate-500 uppercase">Patient ID</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-slate-500 uppercase">Patient Name</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-slate-500 uppercase">Triage Intake Vitals</th>
                            <th class="px-4 py-2.5 text-left text-[10px] font-bold text-slate-500 uppercase">Check-in Time</th>
                            <th class="px-4 py-2.5 text-center text-[10px] font-bold text-slate-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($nutritionVisits as $visit): ?>
                        <tr class="hover:bg-emerald-50/20 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-emerald-700"><?php echo htmlspecialchars($visit['patient_code']); ?></td>
                            <td class="px-4 py-3 font-semibold text-slate-800">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs"><?php echo htmlspecialchars($visit['avatar']); ?></div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900"><?php echo htmlspecialchars($visit['patient_name']); ?></p>
                                        <span class="text-[11px] text-emerald-700 font-medium inline-flex items-center gap-1">
                                            <i class="fa-solid fa-circle-check text-[8px]"></i> Ready for Clinical Screening
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <?php if ($visit['weight'] || $visit['height']): ?>
                                    <div class="text-xs space-y-0.5">
                                        <span class="font-bold text-slate-800"><?php echo $visit['weight'] ? $visit['weight'] . ' kg' : '—'; ?></span>
                                        <span class="text-slate-400">•</span>
                                        <span class="font-bold text-slate-800"><?php echo $visit['height'] ? $visit['height'] . ' cm' : '—'; ?></span>
                                        <?php if ($visit['bmi']): ?>
                                            <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded font-semibold ml-1">BMI: <?php echo $visit['bmi']; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Vitals pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs font-medium"><?php echo htmlspecialchars($visit['check_in_time']); ?></td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="closeModal('nutritionQueueModal'); startAssessmentFor(<?php echo htmlspecialchars(json_encode($visit), ENT_QUOTES); ?>)" 
                                        class="px-4 py-2 text-xs font-bold text-white bg-brand-dark rounded-xl hover:bg-brand-medium transition shadow-xs inline-flex items-center gap-1.5">
                                    <i class="fa-solid fa-weight-scale text-xs"></i> Start Assessment
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="w-14 h-14 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 mb-3">
                    <i class="fa-solid fa-apple-whole text-2xl"></i>
                </div>
                <h4 class="text-base font-bold text-slate-800">No Patients in Queue Today</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-sm">There are currently no patients checked in with reason "Nutrition Assessment" for today.</p>
            </div>
            <?php endif; ?>
            <div class="flex justify-end mt-4 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('nutritionQueueModal')" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 text-sm font-semibold transition">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- GROWTH MEASUREMENTS & WHO TRAJECTORY MODAL                   -->
<!-- ============================================================ -->
<!-- ============================================================ -->
<!-- GROWTH MEASUREMENTS & WHO TRAJECTORY MODAL                   -->
<!-- ============================================================ -->
<div id="growthMeasurementModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
        <!-- Modal Top Bar -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white sticky top-0 z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-light flex items-center justify-center text-brand-dark shadow-xs">
                    <i class="fa-solid fa-chart-line text-lg text-brand-medium"></i>
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                        Pediatric Growth Trajectory & Monitoring
                    </h3>
                    <p class="text-xs text-slate-500">WHO Child Growth Standards percentiles and longitudinal visit records</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="openAddGrowthModal()"
                        class="px-3.5 py-2 bg-brand-dark text-white hover:bg-brand-medium rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>Log Measurement</span>
                </button>
                <button type="button" onclick="closeModal('growthMeasurementModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
        </div>

        <!-- Child Selector & Quick Status Banner -->
        <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-center gap-3 flex-1 min-w-0">
                <label for="growth_child" class="text-xs font-bold text-slate-600 uppercase tracking-wider flex-shrink-0 flex items-center gap-1.5">
                    <i class="fa-solid fa-child text-brand-medium"></i> Child:
                </label>
                <select id="growth_child" onchange="selectChild(this.value)" class="max-w-md w-full px-3 py-1.5 border border-slate-300 rounded-lg text-sm bg-white font-semibold text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none cursor-pointer">
                    <?php foreach ($children as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['child_id']); ?> - <?php echo htmlspecialchars($c['age']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center gap-2 flex-wrap" id="modalChildStatusBadges">
                <span id="childSelectedBadge" class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-xs"></i> <span>WHO: Loading...</span>
                </span>
                <span id="childGrowthSummary" class="px-2.5 py-1 bg-white border border-slate-200 text-slate-600 rounded-full text-xs font-medium">
                    Latest: — kg, — cm
                </span>
            </div>
        </div>

        <!-- Scrollable Modal Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4">
            <!-- Trajectory Controls & Metric Switchers -->
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-2xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div>
                        <h4 id="childSelectedName" class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                            <i class="fa-solid fa-chart-line text-brand-medium"></i> Growth Trajectory
                        </h4>
                        <p class="text-[11px] text-slate-400">Longitudinal anthropometric curve compared against WHO standards. Hover over points for checkup history.</p>
                    </div>
                    <div class="inline-flex p-1 bg-slate-100 rounded-lg text-xs gap-1 border border-slate-200/80">
                        <button type="button" id="btnWeight" onclick="setChartType('weight')"
                                class="px-3 py-1.5 rounded-md font-bold transition bg-brand-dark text-white shadow-2xs cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-weight-scale text-xs"></i>
                            <span>Weight-for-Age</span>
                        </button>
                        <button type="button" id="btnHeight" onclick="setChartType('height')"
                                class="px-3 py-1.5 rounded-md font-semibold text-slate-600 hover:text-slate-900 transition cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-ruler-vertical text-xs"></i>
                            <span>Height-for-Age</span>
                        </button>
                        <button type="button" id="btnBmi" onclick="setChartType('bmi')"
                                class="px-3 py-1.5 rounded-md font-semibold text-slate-600 hover:text-slate-900 transition cursor-pointer flex items-center gap-1.5">
                            <i class="fa-solid fa-calculator text-xs"></i>
                            <span>BMI-for-Age</span>
                        </button>
                    </div>
                </div>

                <!-- Empty State Alert (shown when child has 0 visits, reference curve still visible) -->
                <div id="growthEmptyAlert" class="hidden mb-4 p-3 bg-brand-light/60 border border-brand-border rounded-xl text-xs text-brand-dark flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-info text-brand-medium text-sm"></i>
                        <span>No checkup visits logged yet for this child. The WHO standard reference curves are displayed below.</span>
                    </span>
                    <button type="button" onclick="openAddGrowthModal()" class="px-3 py-1 bg-brand-dark text-white rounded-lg text-xs font-bold hover:bg-brand-medium transition shrink-0 cursor-pointer">
                        + Log First Visit
                    </button>
                </div>

                <!-- Chart Canvas (Exact layout and style as Patient Management Age Group Distribution) -->
                <div class="h-64 sm:h-72 w-full relative">
                    <canvas id="growthCanvas"></canvas>
                </div>

                <!-- WHO Standard Curve Legend & Clinical Interpretation -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-[11px] text-slate-500">
                    <div class="flex items-center gap-3.5 flex-wrap font-medium">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-brand-dark inline-block border-2 border-white shadow-xs"></span> <strong class="text-slate-700">Child Recorded Visits</strong></span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 bg-emerald-500 inline-block"></span> <span class="text-emerald-700 font-semibold">P50 (Median Standard)</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 border-b-2 border-amber-500 border-dashed inline-block"></span> <span class="text-amber-700 font-semibold">P15 / P85 (Normal Range)</span></span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-0.5 border-b-2 border-rose-500 border-dashed inline-block"></span> <span class="text-rose-700 font-semibold">P3 / P97 (Alert Bounds)</span></span>
                    </div>
                    <span class="text-slate-400 font-medium flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved text-xs text-brand-medium"></i> WHO Child Growth Standards (0–5 yrs)
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- LOG NEW GROWTH MEASUREMENT MODAL                             -->
<!-- ============================================================ -->
<div id="addGrowthModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-[60] items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100 animate-in fade-in zoom-in-95 duration-150">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-white">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-brand-light flex items-center justify-center text-brand-dark">
                    <i class="fa-solid fa-weight-scale text-base text-brand-medium"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Log Growth Measurement</h3>
                    <p class="text-xs text-slate-500">Record checkup and plot on WHO trajectory</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('addGrowthModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Form Body -->
        <form id="addGrowthForm" onsubmit="saveGrowthMeasurement(event)" class="p-6 space-y-4">
            <div>
                <label for="growth_entry_child_name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Child Patient</label>
                <input type="text" id="growth_entry_child_name" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold text-slate-700 outline-none">
                <input type="hidden" id="growth_entry_child_id">
            </div>

            <div>
                <label for="growth_date" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Checkup / Measurement Date <span class="text-rose-500">*</span></label>
                <input type="date" id="growth_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="growth_weight" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Weight (kg) <span class="text-rose-500">*</span></label>
                    <input type="number" id="growth_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitGrowthMeasurement(this)" required placeholder="e.g. 8.5" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label for="growth_height" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Height (cm) <span class="text-rose-500">*</span></label>
                    <input type="number" id="growth_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitGrowthMeasurement(this)" required placeholder="e.g. 72.0" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm font-semibold focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div>
                <label for="growth_head" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Head Circumference (cm) <span class="text-slate-400 font-normal">(Optional)</span></label>
                <input type="number" id="growth_head" step="0.1" placeholder="e.g. 43.5" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div>
                <label for="growth_notes" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Clinical Remarks / Observations</label>
                <input type="text" id="growth_notes" placeholder="e.g. Routine checkup, normal motor milestones" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('addGrowthModal')" class="px-4 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-lg text-sm font-semibold transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" id="btnSaveGrowth" class="px-5 py-2 bg-brand-dark hover:bg-brand-medium text-white rounded-lg text-sm font-bold shadow-sm transition flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-check text-xs"></i>
                    <span>Save & Update Curve</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[70] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- Chart.js Library (Matching Patient Management) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<!-- Local ApexCharts Library (Fallback) -->
<script src="<?php echo site_url('assets/js/apexcharts.min.js'); ?>"></script>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const ASSESSMENTS = <?php echo json_encode(array_column($nutritionAssessments, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;
    const CHILDREN = <?php echo json_encode($children, JSON_PRETTY_PRINT); ?> || [];
    const GROWTH_DATA = <?php echo json_encode($growthData, JSON_PRETTY_PRINT); ?> || [];
    const WEIGHT_PERCENTILES = <?php echo json_encode($weightPercentiles, JSON_PRETTY_PRINT); ?>;
    const GROWTH_ALERTS = <?php echo json_encode($growthAlerts, JSON_PRETTY_PRINT); ?> || [];

    let currentChartType = 'weight';
    let growthChartInstance = null;
    let selectedChildId = null;

    // WHO Child Growth Standards Benchmarks (0 to 60 Months)
    const WHO_GROWTH_STANDARDS = {
        weight: {
            male: [
                { x: 0,  p3: 2.5, p15: 2.8, p50: 3.3, p85: 3.8, p97: 4.2 },
                { x: 1,  p3: 3.4, p15: 3.8, p50: 4.3, p85: 4.9, p97: 5.4 },
                { x: 2,  p3: 4.3, p15: 4.7, p50: 5.6, p85: 6.3, p97: 7.1 },
                { x: 3,  p3: 4.8, p15: 5.2, p50: 6.4, p85: 7.2, p97: 8.0 },
                { x: 6,  p3: 6.4, p15: 6.9, p50: 7.9, p85: 8.8, p97: 9.8 },
                { x: 9,  p3: 7.2, p15: 7.8, p50: 8.9, p85: 9.9, p97: 11.0 },
                { x: 12, p3: 7.8, p15: 8.4, p50: 9.6, p85: 10.8, p97: 12.0 },
                { x: 18, p3: 8.8, p15: 9.5, p50: 10.9, p85: 12.2, p97: 13.7 },
                { x: 24, p3: 9.7, p15: 10.5, p50: 12.2, p85: 13.6, p97: 15.3 },
                { x: 36, p3: 11.3, p15: 12.3, p50: 14.3, p85: 16.2, p97: 18.3 },
                { x: 48, p3: 12.7, p15: 13.8, p50: 16.3, p85: 18.6, p97: 21.2 },
                { x: 60, p3: 14.1, p15: 15.3, p50: 18.3, p85: 21.0, p97: 24.2 }
            ],
            female: [
                { x: 0,  p3: 2.4, p15: 2.7, p50: 3.2, p85: 3.7, p97: 4.1 },
                { x: 1,  p3: 3.2, p15: 3.6, p50: 4.2, p85: 4.8, p97: 5.3 },
                { x: 2,  p3: 3.9, p15: 4.4, p50: 5.1, p85: 5.8, p97: 6.6 },
                { x: 3,  p3: 4.5, p15: 4.9, p50: 5.8, p85: 6.6, p97: 7.5 },
                { x: 6,  p3: 5.7, p15: 6.3, p50: 7.3, p85: 8.2, p97: 9.3 },
                { x: 9,  p3: 6.5, p15: 7.2, p50: 8.2, p85: 9.3, p97: 10.5 },
                { x: 12, p3: 7.0, p15: 7.7, p50: 8.9, p85: 10.1, p97: 11.5 },
                { x: 18, p3: 8.1, p15: 8.8, p50: 10.2, p85: 11.6, p97: 13.2 },
                { x: 24, p3: 9.0, p15: 9.9, p50: 11.5, p85: 13.0, p97: 14.8 },
                { x: 36, p3: 10.8, p15: 11.8, p50: 13.9, p85: 15.8, p97: 18.1 },
                { x: 48, p3: 12.3, p15: 13.4, p50: 16.1, p85: 18.5, p97: 21.2 },
                { x: 60, p3: 13.7, p15: 15.0, p50: 18.2, p85: 21.0, p97: 24.4 }
            ]
        },
        height: {
            male: [
                { x: 0,  p3: 46.3, p15: 47.9, p50: 49.9, p85: 51.8, p97: 53.4 },
                { x: 1,  p3: 51.1, p15: 52.8, p50: 54.7, p85: 56.7, p97: 58.4 },
                { x: 2,  p3: 54.7, p15: 56.4, p50: 58.4, p85: 60.4, p97: 62.2 },
                { x: 3,  p3: 57.6, p15: 59.4, p50: 61.4, p85: 63.5, p97: 65.3 },
                { x: 6,  p3: 63.6, p15: 65.5, p50: 67.6, p85: 69.8, p97: 71.6 },
                { x: 9,  p3: 67.7, p15: 69.7, p50: 72.0, p85: 74.2, p97: 76.2 },
                { x: 12, p3: 71.0, p15: 73.1, p50: 75.7, p85: 78.1, p97: 80.2 },
                { x: 18, p3: 76.9, p15: 79.2, p50: 82.3, p85: 85.0, p97: 87.3 },
                { x: 24, p3: 82.1, p15: 84.8, p50: 87.8, p85: 90.9, p97: 93.6 },
                { x: 36, p3: 90.1, p15: 93.1, p50: 96.1, p85: 99.8, p97: 102.7 },
                { x: 48, p3: 97.4, p15: 100.5, p50: 103.3, p85: 106.8, p97: 109.8 },
                { x: 60, p3: 103.3, p15: 106.8, p50: 110.0, p85: 113.8, p97: 117.0 }
            ],
            female: [
                { x: 0,  p3: 45.6, p15: 47.2, p50: 49.1, p85: 51.0, p97: 52.7 },
                { x: 1,  p3: 50.0, p15: 51.7, p50: 53.7, p85: 55.6, p97: 57.4 },
                { x: 2,  p3: 53.5, p15: 55.2, p50: 57.1, p85: 59.1, p97: 60.9 },
                { x: 3,  p3: 56.0, p15: 57.7, p50: 59.8, p85: 61.9, p97: 63.7 },
                { x: 6,  p3: 61.5, p15: 63.5, p50: 65.7, p85: 68.0, p97: 70.0 },
                { x: 9,  p3: 65.6, p15: 67.7, p50: 70.1, p85: 72.6, p97: 74.7 },
                { x: 12, p3: 69.2, p15: 71.4, p50: 74.0, p85: 76.6, p97: 78.9 },
                { x: 18, p3: 75.2, p15: 77.6, p50: 80.7, p85: 83.6, p97: 86.0 },
                { x: 24, p3: 80.8, p15: 83.5, p50: 86.4, p85: 89.6, p97: 92.2 },
                { x: 36, p3: 88.8, p15: 91.9, p50: 95.1, p85: 98.6, p97: 101.6 },
                { x: 48, p3: 96.4, p15: 99.4, p50: 102.7, p85: 106.2, p97: 109.1 },
                { x: 60, p3: 102.5, p15: 105.8, p50: 109.4, p85: 113.2, p97: 116.3 }
            ]
        },
        bmi: {
            male: [
                { x: 0,  p3: 11.3, p15: 12.2, p50: 13.4, p85: 14.8, p97: 15.8 },
                { x: 3,  p3: 14.4, p15: 15.4, p50: 16.8, p85: 18.3, p97: 19.5 },
                { x: 6,  p3: 14.8, p15: 15.9, p50: 17.3, p85: 18.8, p97: 20.0 },
                { x: 9,  p3: 14.7, p15: 15.7, p50: 17.1, p85: 18.6, p97: 19.8 },
                { x: 12, p3: 14.6, p15: 15.6, p50: 16.9, p85: 18.4, p97: 19.5 },
                { x: 18, p3: 14.3, p15: 15.2, p50: 16.4, p85: 17.8, p97: 18.9 },
                { x: 24, p3: 14.1, p15: 14.9, p50: 16.0, p85: 17.3, p97: 18.4 },
                { x: 36, p3: 13.7, p15: 14.5, p50: 15.6, p85: 16.9, p97: 17.9 },
                { x: 48, p3: 13.4, p15: 14.2, p50: 15.3, p85: 16.7, p97: 17.8 },
                { x: 60, p3: 13.2, p15: 14.0, p50: 15.2, p85: 16.7, p97: 18.0 }
            ],
            female: [
                { x: 0,  p3: 11.1, p15: 12.0, p50: 13.3, p85: 14.6, p97: 15.6 },
                { x: 3,  p3: 14.1, p15: 15.1, p50: 16.4, p85: 17.9, p97: 19.1 },
                { x: 6,  p3: 14.4, p15: 15.5, p50: 16.9, p85: 18.4, p97: 19.6 },
                { x: 9,  p3: 14.3, p15: 15.3, p50: 16.7, p85: 18.2, p97: 19.3 },
                { x: 12, p3: 14.2, p15: 15.1, p50: 16.4, p85: 17.9, p97: 19.0 },
                { x: 18, p3: 14.0, p15: 14.8, p50: 16.0, p85: 17.4, p97: 18.5 },
                { x: 24, p3: 13.8, p15: 14.6, p50: 15.7, p85: 17.0, p97: 18.1 },
                { x: 36, p3: 13.4, p15: 14.2, p50: 15.3, p85: 16.7, p97: 17.7 },
                { x: 48, p3: 13.1, p15: 14.0, p50: 15.1, p85: 16.5, p97: 17.7 },
                { x: 60, p3: 13.0, p15: 13.9, p50: 15.0, p85: 16.6, p97: 18.1 }
            ]
        }
    };

    // ============================================================
    // GROWTH MEASUREMENTS & TRAJECTORY MODAL
    // ============================================================
    function openGrowthModal(childId) {
        openModal('growthMeasurementModal');
        const sel = document.getElementById('growth_child');
        if (childId && sel) {
            sel.value = childId;
        } else if (sel && !sel.value && CHILDREN.length > 0) {
            sel.value = CHILDREN[0].id;
        }
        const targetId = sel && sel.value ? sel.value : (childId || (CHILDREN[0] ? CHILDREN[0].id : null));
        selectedChildId = targetId;

        // Allow modal DOM to finish layout reflow before rendering ApexCharts
        setTimeout(() => {
            if (targetId) {
                selectChild(targetId);
            } else {
                renderProperGrowthChart();
            }
        }, 80);
    }

    function showGrowthChart(childId) {
        openGrowthModal(childId);
    }

    function openAddGrowthModal() {
        const sel = document.getElementById('growth_child');
        const childId = sel ? sel.value : selectedChildId;
        const child = getChildById(childId);
        if (child) {
            const nameEl = document.getElementById('growth_entry_child_name');
            const idEl = document.getElementById('growth_entry_child_id');
            if (nameEl) nameEl.value = `${child.name} (${child.child_id} • ${child.age || ''})`;
            if (idEl) idEl.value = child.id;
        }
        const dInput = document.getElementById('growth_date');
        if (dInput && !dInput.value) {
            dInput.value = new Date().toISOString().split('T')[0];
        }
        openModal('addGrowthModal');
    }

    function openAddGrowthForSelected() {
        openAddGrowthModal();
    }

    function openScreeningForSelectedChild() {
        switchNutritionCategory('child');
        openModal('nutritionScreeningModal');
        if (selectedChildId) {
            const sc = document.getElementById('screen_child');
            if (sc) sc.value = selectedChildId;
        }
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
    // VIEW ASSESSMENT
    // ============================================================
    function viewAssessment(id) {
        openModal('viewAssessmentModal');
        const a = ASSESSMENTS[id];
        if (!a) return;

        setTimeout(() => {
            const statusColors = {
                normal: 'bg-emerald-100 text-emerald-700',
                moderate: 'bg-amber-100 text-amber-700',
                critical: 'bg-rose-100 text-rose-700'
            };
            const riskColors = {
                low: 'bg-emerald-100 text-emerald-700',
                medium: 'bg-amber-100 text-amber-700',
                high: 'bg-rose-100 text-rose-700'
            };
            const supplementsHtml = a.supplements.map(s => `<span class="px-2 py-1 bg-brand-light/40 rounded text-xs border border-brand-border">${s}</span>`).join('');

            document.getElementById('assessmentDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${a.child_avatar}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${a.child_name}</h4>
                            <p class="text-sm text-slate-500">${a.age} • Assessment Date: ${new Date(a.date).toLocaleDateString()}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[a.nutrition_status] || statusColors.normal}">
                                ${a.nutrition_status.toUpperCase()}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ml-1 ${riskColors[a.risk_level] || riskColors.low}">
                                Risk: ${a.risk_level.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Weight</p><p class="text-sm font-bold text-slate-800">${a.weight} kg</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Height</p><p class="text-sm font-bold text-slate-800">${a.height} cm</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">BMI</p><p class="text-sm font-bold text-slate-800">${a.bmi}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Percentiles</p><p class="text-sm font-bold text-slate-800">W: ${a.weight_percentile}% / H: ${a.height_percentile}%</p></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">📝 Assessment Notes</h5>
                        <p class="text-sm text-slate-800">${a.assessment_notes}</p>
                    </div>
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">📋 Nutrition Plan</h5>
                        <p class="text-sm text-slate-800">${a.plan_of_action}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">💊 Supplements</h5>
                        <div class="flex flex-wrap gap-2">${supplementsHtml}</div>
                        <p class="text-xs text-slate-400 mt-2">Next Assessment: ${a.next_assessment ? new Date(a.next_assessment).toLocaleDateString() : '—'}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewAssessmentModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        ${a.nutrition_status === 'critical' ? `<button onclick="closeModal('viewAssessmentModal'); emergencyIntervention(${a.id})" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition text-sm font-semibold"><i class="fa-solid fa-truck-medical mr-1.5"></i> Emergency Intervention</button>` : ''}
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // EDIT ASSESSMENT
    // ============================================================
    function editAssessment(id) {
        const a = ASSESSMENTS[id];
        if (!a) return;
        document.getElementById('edit_assessment_id').value = a.id;
        document.getElementById('edit_assessment_date').value = a.date;
        document.getElementById('edit_assessment_weight').value = a.weight;
        document.getElementById('edit_assessment_height').value = a.height;
        document.getElementById('edit_assessment_status').value = a.nutrition_status;
        document.getElementById('edit_assessment_risk').value = a.risk_level;
        document.getElementById('edit_assessment_notes').value = a.assessment_notes || '';
        openModal('editAssessmentModal');
    }

    async function saveEditedAssessment(event) {
        event.preventDefault();
        const id = document.getElementById('edit_assessment_id').value;
        const weight = document.getElementById('edit_assessment_weight').value;
        const height = document.getElementById('edit_assessment_height').value;
        const validMeasurement = (value, minimum) =>
            /^\d{1,3}(\.\d{1,2})?$/.test(value) && Number(value) >= minimum && Number(value) <= 999;
        if (!validMeasurement(weight, 0.1) || !validMeasurement(height, 20)) {
            showToast('Weight must be 0.1-999 kg and height must be 20-999 cm.', 'warning');
            return;
        }

        const payload = {
            date: document.getElementById('edit_assessment_date').value,
            weight: Number(weight),
            height: Number(height),
            nutrition_status: document.getElementById('edit_assessment_status').value,
            risk_level: document.getElementById('edit_assessment_risk').value,
            assessment_notes: document.getElementById('edit_assessment_notes').value.trim()
        };

        try {
            const res = await fetch(`<?php echo site_url('api/nutrition.php'); ?>?id=${id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeModal('editAssessmentModal');
                showToast('Nutrition assessment updated successfully!', 'success');
                const a = ASSESSMENTS[id];
                if (a) {
                    a.date = payload.date;
                    a.weight = payload.weight;
                    a.height = payload.height;
                    a.nutrition_status = payload.nutrition_status;
                    a.risk_level = payload.risk_level;
                    a.assessment_notes = payload.assessment_notes;
                }
                filterAssessments();
            } else {
                showToast(data.message || 'Failed to update assessment.', 'danger');
            }
        } catch (err) {
            console.error('Update assessment error:', err);
            showToast('Error connecting to server.', 'danger');
        }
    }

    // ============================================================
    // EMERGENCY INTERVENTION
    // ============================================================
    function switchNutritionCategory(type) {
        const isChild = (type === 'child');
        document.getElementById('screen_patient_type').value = isChild ? 'child' : 'adult';
        document.getElementById('wrapper_screen_child').classList.toggle('hidden', !isChild);
        document.getElementById('wrapper_screen_patient').classList.toggle('hidden', isChild);

        const tabChildBtn = document.getElementById('tab_screen_child_btn');
        const tabAdultBtn = document.getElementById('tab_screen_adult_btn');

        if (isChild) {
            tabChildBtn.className = 'flex-1 py-1.5 text-xs font-bold rounded-md bg-white text-brand-dark shadow-xs transition';
            tabAdultBtn.className = 'flex-1 py-1.5 text-xs font-bold rounded-md text-slate-600 hover:text-slate-900 transition';
        } else {
            tabAdultBtn.className = 'flex-1 py-1.5 text-xs font-bold rounded-md bg-white text-brand-dark shadow-xs transition';
            tabChildBtn.className = 'flex-1 py-1.5 text-xs font-bold rounded-md text-slate-600 hover:text-slate-900 transition';
            const patSel = document.getElementById('screen_patient');
            if (patSel && patSel.selectedIndex > 0) {
                const opt = patSel.options[patSel.selectedIndex];
                document.getElementById('screen_patient_type').value = opt.getAttribute('data-type') || 'adult';
            }
        }
        calculateLiveBmi();
    }

    function calculateLiveBmi() {
        const weight = parseFloat(document.getElementById('screen_weight')?.value);
        const height = parseFloat(document.getElementById('screen_height')?.value);
        const bmiValEl = document.getElementById('calculated_bmi_val');
        const bmiCatEl = document.getElementById('calculated_bmi_category');
        const isChild = document.getElementById('screen_patient_type')?.value === 'child';
        const statusEl = document.getElementById('screen_status');
        const riskEl = document.getElementById('screen_risk');

        if (!weight || !height || height <= 0 || weight <= 0) {
            if (bmiValEl) bmiValEl.textContent = '—';
            if (bmiCatEl) {
                bmiCatEl.textContent = 'Awaiting measurements';
                bmiCatEl.className = 'font-bold text-slate-700 ml-1';
            }
            checkCriticalAlert();
            return;
        }

        const hM = height / 100;
        const bmi = +(weight / (hM * hM)).toFixed(1);
        if (bmiValEl) bmiValEl.textContent = bmi + ' kg/m²';

        if (isChild) {
            if (bmi < 14.0) {
                if (bmiCatEl) { bmiCatEl.textContent = 'Severe Malnutrition (SAM)'; bmiCatEl.className = 'font-bold text-rose-600 ml-1'; }
                if (statusEl) statusEl.value = 'critical';
                if (riskEl) riskEl.value = 'high';
            } else if (bmi < 15.5) {
                if (bmiCatEl) { bmiCatEl.textContent = 'Moderate Malnutrition (MAM)'; bmiCatEl.className = 'font-bold text-amber-600 ml-1'; }
                if (statusEl) statusEl.value = 'moderate';
                if (riskEl) riskEl.value = 'medium';
            } else {
                if (bmiCatEl) { bmiCatEl.textContent = 'Normal Growth'; bmiCatEl.className = 'font-bold text-emerald-600 ml-1'; }
                if (statusEl) statusEl.value = 'normal';
                if (riskEl) riskEl.value = 'low';
            }
        } else {
            // Adult WHO cutoffs
            if (bmi < 16.0) {
                if (bmiCatEl) { bmiCatEl.textContent = 'Severe Underweight (WHO SAM)'; bmiCatEl.className = 'font-bold text-rose-600 ml-1'; }
                if (statusEl) statusEl.value = 'critical';
                if (riskEl) riskEl.value = 'high';
            } else if (bmi < 18.5) {
                if (bmiCatEl) { bmiCatEl.textContent = 'Underweight (WHO <18.5)'; bmiCatEl.className = 'font-bold text-amber-600 ml-1'; }
                if (statusEl) statusEl.value = 'moderate';
                if (riskEl) riskEl.value = 'medium';
            } else if (bmi < 25.0) {
                if (bmiCatEl) { bmiCatEl.textContent = 'Normal Weight (WHO 18.5–24.9)'; bmiCatEl.className = 'font-bold text-emerald-600 ml-1'; }
                if (statusEl) statusEl.value = 'normal';
                if (riskEl) riskEl.value = 'low';
            } else if (bmi < 30.0) {
                if (bmiCatEl) { bmiCatEl.textContent = 'Overweight (WHO 25.0–29.9)'; bmiCatEl.className = 'font-bold text-amber-600 ml-1'; }
                if (statusEl) statusEl.value = 'moderate';
                if (riskEl) riskEl.value = 'medium';
            } else {
                if (bmiCatEl) { bmiCatEl.textContent = 'Obese (WHO ≥30.0)'; bmiCatEl.className = 'font-bold text-rose-600 ml-1'; }
                if (statusEl) statusEl.value = 'critical';
                if (riskEl) riskEl.value = 'high';
            }
        }
        checkCriticalAlert();
    }

    function checkCriticalAlert() {
        const statusEl = document.getElementById('screen_status');
        const alertEl = document.getElementById('critical_referral_alert');
        if (alertEl && statusEl) {
            alertEl.classList.toggle('hidden', statusEl.value !== 'critical');
        }
    }

    async function referToDoctorTriage(a) {
        if (!a) return;
        const ptId = a.patient_id || a.child_id;
        if (!ptId) {
            showToast('No valid patient ID found for referral.', 'warning');
            return;
        }
        const ptName = a.child_name || 'Patient';
        if (!confirm(`Refer ${ptName} to Doctor Consultation Triage for urgent malnutrition assessment?`)) {
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
            const res = await fetch('<?php echo site_url('api/triage-queue.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    patient_id: Number(ptId),
                    reason_for_visit: `Critical Malnutrition - Urgent Physician Assessment (${a.badge_type || 'Patient'})`,
                    csrf_token: csrfToken
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast(`✓ ${ptName} successfully queued in Doctor Consultation Triage!`, 'success');
            } else {
                showToast(data.message || 'Could not queue patient in Triage.', 'danger');
            }
        } catch (err) {
            console.error('Triage referral error:', err);
            showToast('Network error while referring to triage.', 'danger');
        }
    }

    // ============================================================
    // EMERGENCY INTERVENTION
    // ============================================================
    function emergencyIntervention(id) {
        const a = ASSESSMENTS[id];
        if (!a) return;
        document.getElementById('emergencyInterventionModal').dataset.assessmentId = id;
        document.getElementById('emergencyAssessmentChild').textContent = a.child_name + ' requires immediate attention.';
        document.getElementById('emergencyInterventionNotes').value = a.plan_of_action || 'Immediate nutrition intervention required.';
        openModal('emergencyInterventionModal');
    }

    async function confirmEmergencyIntervention() {
        const modal = document.getElementById('emergencyInterventionModal');
        const id = modal.dataset.assessmentId;
        const notes = document.getElementById('emergencyInterventionNotes').value.trim();

        try {
            const res = await fetch(`<?php echo site_url('api/nutrition.php'); ?>?id=${id}&action=emergency`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ plan_of_action: notes })
            });
            const data = await res.json();
            if (data.success) {
                closeModal('emergencyInterventionModal');
                showToast('Emergency intervention recorded!', 'success');
                const a = ASSESSMENTS[id];
                if (a) {
                    a.plan_of_action = notes;
                    a.nutrition_status = 'critical';
                    a.risk_level = 'high';
                }
                filterAssessments();
            } else {
                showToast(data.message || 'Failed to record intervention.', 'danger');
            }
        } catch (err) {
            console.error('Emergency intervention error:', err);
            showToast('Error connecting to server.', 'danger');
        }
    }

    // ============================================================
    // START ASSESSMENT FROM ACTIVE TRIAGE VISIT
    // ============================================================
    function startAssessmentFor(visit) {
        if (!visit) return;
        
        const sel = document.getElementById('screen_child');
        if (sel) {
            let matched = false;
            for (let i = 0; i < sel.options.length; i++) {
                const optText = sel.options[i].text.toLowerCase();
                const optVal = sel.options[i].value;
                if (optVal == visit.patient_id || optText.includes(visit.patient_name.toLowerCase())) {
                    sel.selectedIndex = i;
                    matched = true;
                    break;
                }
            }
            if (!matched && sel.options.length > 1) {
                sel.selectedIndex = 1;
            }
        }

        document.getElementById('screen_date').value = new Date().toISOString().split('T')[0];
        
        const weightInput = document.getElementById('screen_weight');
        const heightInput = document.getElementById('screen_height');
        
        if (visit.weight) weightInput.value = visit.weight;
        if (visit.height) heightInput.value = visit.height;

        // Auto-compute status and risk based on BMI / measurements
        if (visit.weight && visit.height) {
            const hM = visit.height / 100;
            const bmi = visit.weight / (hM * hM);
            const statusEl = document.getElementById('screen_status');
            const riskEl = document.getElementById('screen_risk');
            if (bmi < 14) {
                if (statusEl) statusEl.value = 'critical';
                if (riskEl) riskEl.value = 'high';
            } else if (bmi < 15.5) {
                if (statusEl) statusEl.value = 'moderate';
                if (riskEl) riskEl.value = 'medium';
            } else {
                if (statusEl) statusEl.value = 'normal';
                if (riskEl) riskEl.value = 'low';
            }
        }

        const notesEl = document.getElementById('screen_notes');
        if (notesEl && !notesEl.value) {
            notesEl.value = 'Intake from Triage: Patient presented for Nutrition Assessment.';
        }

        openModal('nutritionScreeningModal');
    }

    // ============================================================
    // NUTRITION SCREENING
    // ============================================================
    async function saveNutritionScreening(event) {
        event.preventDefault();
        const childId = document.getElementById('screen_child').value;
        const weight = document.getElementById('screen_weight').value;
        const height = document.getElementById('screen_height').value;
        const date = document.getElementById('screen_date').value;
        const status = document.getElementById('screen_status').value;
        const risk = document.getElementById('screen_risk').value;
        const notes = document.getElementById('screen_notes').value;
        const nextDate = document.getElementById('screen_next').value;

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

        const payload = {
            child_id: Number(childId),
            date: date || new Date().toISOString().split('T')[0],
            weight: Number(weight),
            height: Number(height),
            nutrition_status: status,
            risk_level: risk,
            assessment_notes: notes,
            next_assessment: nextDate,
            supplements: []
        };

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
            const res = await fetch('<?php echo site_url('api/nutrition.php'); ?>', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ ...payload, csrf_token: csrfToken })
            });
            const data = await res.json();
            if (data.success) {
                closeModal('nutritionScreeningModal');
                const saved = data.record || data.data || { ...payload, id: Date.now() };
                if (saved.id) {
                    ASSESSMENTS[saved.id] = saved;
                    upsertNutritionRow(saved);
                }
                // Mirror server-side dual-sync so the Growth tab updates without reload
                const gDate = payload.date;
                const alreadyLogged = GROWTH_DATA.some(g => Number(g.child_id) === payload.child_id && g.date === gDate);
                if (!alreadyLogged) {
                    GROWTH_DATA.push({
                        id: Date.now(),
                        child_id: payload.child_id,
                        date: gDate,
                        weight: payload.weight,
                        height: payload.height,
                        head_circumference: null,
                        notes: 'Auto-recorded from Nutrition Assessment (' + (payload.nutrition_status || 'Routine') + ')'
                    });
                    if (Number(selectedChildId) === payload.child_id) selectChild(payload.child_id);
                }
                showToast('Nutrition assessment saved successfully!', 'success');
            } else {
                showToast(data.message || 'Failed to save assessment.', 'danger');
            }
        } catch (err) {
            console.error('Save assessment error:', err);
            showToast('Error saving assessment to server.', 'danger');
        }
    }

    function upsertNutritionRow(a) {
        if (!a || !a.id) return;

        const row = document.querySelector(`.nutrition-row[data-assessment-id="${a.id}"]`);
        if (row) {
            row.dataset.child = (a.child_name || '').toLowerCase();
            row.dataset.status = (a.nutrition_status || '').toLowerCase();
            row.dataset.risk = (a.risk_level || '').toLowerCase();
            row.dataset.date = a.date || '';
            row.dataset.id = a.child_id || '';

            const cells = row.children;
            if (cells.length >= 8) {
                const dateCell = cells[1];
                if (dateCell) dateCell.textContent = a.date ? new Date(a.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';

                const weightCell = cells[2];
                if (weightCell) weightCell.textContent = a.weight ?? '—';

                const heightCell = cells[3];
                if (heightCell) heightCell.textContent = a.height ?? '—';

                const statusCell = cells[4];
                const riskCell = cells[5];
                const nextCell = cells[6];

                const statusColors = {
                    normal: 'bg-emerald-100 text-emerald-700',
                    moderate: 'bg-amber-100 text-amber-700',
                    critical: 'bg-rose-100 text-rose-700'
                };
                const riskColors = {
                    low: 'bg-emerald-100 text-emerald-700',
                    medium: 'bg-amber-100 text-amber-700',
                    high: 'bg-rose-100 text-rose-700'
                };
                if (statusCell) {
                    const badge = statusCell.querySelector('span');
                    const status = (a.nutrition_status || 'normal').toLowerCase();
                    const className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[status] || statusColors.normal}`;
                    if (badge) {
                        badge.className = className;
                        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
                    }
                }
                if (riskCell) {
                    const badge = riskCell.querySelector('span');
                    const risk = (a.risk_level || 'low').toLowerCase();
                    const className = `px-2 py-1 rounded-full text-xs font-semibold ${riskColors[risk] || riskColors.low}`;
                    if (badge) {
                        badge.className = className;
                        badge.textContent = risk.charAt(0).toUpperCase() + risk.slice(1);
                    }
                }
                if (nextCell) {
                    nextCell.textContent = a.next_assessment ? new Date(a.next_assessment).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
                }
            }
            row.classList.toggle('bg-rose-50/50', (a.nutrition_status || '').toLowerCase() === 'critical');
            return;
        }

        const tbody = document.getElementById('nutritionTableBody');
        if (!tbody) return;
        const tr = document.createElement('tr');
        tr.className = 'border-b border-slate-100 hover:bg-brand-light/40 transition-colors nutrition-row ' + ((a.nutrition_status || '').toLowerCase() === 'critical' ? 'bg-rose-50/50' : '');
        tr.dataset.assessmentId = a.id;
        tr.dataset.child = (a.child_name || '').toLowerCase();
        tr.dataset.status = (a.nutrition_status || '').toLowerCase();
        tr.dataset.risk = (a.risk_level || '').toLowerCase();
        tr.dataset.date = a.date || '';
        tr.dataset.id = a.child_id || '';
        tr.innerHTML = `
            <td class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">${a.child_avatar || 'N'}</div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <p class="font-semibold text-slate-800 text-sm">${a.child_name || 'Unknown'}</p>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded ${(a.badge_type === 'Senior' ? 'bg-purple-100 text-purple-700' : (a.badge_type === 'Adult' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'))}">${a.badge_type || (a.patient_type === 'child' ? 'Pediatric' : 'Patient')}</span>
                        </div>
                        <p class="text-xs text-slate-400">${a.age || '—'}${a.bmi ? ` • BMI: ${a.bmi}` : ''}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-slate-600 text-xs">${a.date ? new Date(a.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
            <td class="px-4 py-3 text-slate-600 text-xs font-medium">${a.weight ?? '—'}</td>
            <td class="px-4 py-3 text-slate-600 text-xs">${a.height ?? '—'}</td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-semibold ${((a.nutrition_status || 'normal').toLowerCase() === 'normal' ? 'bg-emerald-100 text-emerald-700' : (a.nutrition_status || '').toLowerCase() === 'moderate' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')}">${(a.nutrition_status || 'Normal').toString().charAt(0).toUpperCase() + String(a.nutrition_status || 'Normal').slice(1)}</span></td>
            <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-semibold ${((a.risk_level || 'low').toLowerCase() === 'low' ? 'bg-emerald-100 text-emerald-700' : (a.risk_level || '').toLowerCase() === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700')}">${(a.risk_level || 'Low').toString().charAt(0).toUpperCase() + String(a.risk_level || 'Low').slice(1)}</span></td>
            <td class="px-4 py-3 text-slate-500 text-xs">${a.next_assessment ? new Date(a.next_assessment).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="viewAssessment(${a.id})" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View"><i class="fa-solid fa-eye text-sm"></i></button>
                    <button onclick="editAssessment(${a.id})" class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit"><i class="fa-solid fa-pen text-sm"></i></button>
                    ${(a.nutrition_status || '').toLowerCase() === 'critical' ? `<button onclick="referToDoctorTriage(ASSESSMENTS[${a.id}] || ${JSON.stringify(a).replace(/"/g, '&quot;')})" class="p-1.5 text-rose-600 hover:bg-rose-100 rounded-lg transition" title="1-Click Refer to Doctor Triage"><i class="fa-solid fa-user-doctor text-sm"></i></button><button onclick="emergencyIntervention(${a.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Emergency Intervention"><i class="fa-solid fa-truck-medical text-sm"></i></button>` : ''}
                </div>
            </td>`;
        tbody.insertBefore(tr, tbody.firstChild);
    }

    function limitNutritionMeasurement(input) {
        const parts = String(input.value || '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 3);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    // ============================================================
    // SUPPLEMENT TRACKING
    // ============================================================
    function adjustSupplementStock(id) {
        showToast('Adjust supplement stock for ID: ' + id, 'info');
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
    document.getElementById('searchNutrition').addEventListener('input', filterAssessments);
    document.getElementById('filterStatus').addEventListener('change', filterAssessments);
    document.getElementById('filterRisk').addEventListener('change', filterAssessments);

    function filterAssessments() {
        const search = document.getElementById('searchNutrition').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const risk = document.getElementById('filterRisk').value;
        const dateFrom = document.getElementById('assessmentDateFrom').value;
        const dateTo = document.getElementById('assessmentDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.nutrition-row').forEach(row => {
            const child = row.dataset.child;
            const rowStatus = row.dataset.status;
            const rowRisk = row.dataset.risk;
            const rowDate = row.dataset.date || '';
            const rowId = (row.dataset.id || '').toLowerCase();

            const matchesSearch = !search || child.includes(search) || rowId.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesRisk = !risk || rowRisk === risk;
            const matchesDateFrom = !dateFrom || (rowDate && rowDate >= dateFrom);
            const matchesDateTo = !dateTo || (rowDate && rowDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesRisk && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchNutrition').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterRisk').value = '';
        document.getElementById('assessmentDateFrom').value = '';
        document.getElementById('assessmentDateTo').value = '';
        document.querySelectorAll('.nutrition-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    function applyAssessmentDateFilter() {
        const dateFrom = document.getElementById('assessmentDateFrom').value;
        const dateTo = document.getElementById('assessmentDateTo').value;
        if (dateFrom && dateTo && dateFrom > dateTo) {
            showToast('The start date must be before the end date.', 'warning');
            return;
        }
        closeModal('assessmentDateFilterModal');
        filterAssessments();
    }

    function clearAssessmentDateFilter() {
        document.getElementById('assessmentDateFrom').value = '';
        document.getElementById('assessmentDateTo').value = '';
        closeModal('assessmentDateFilterModal');
        filterAssessments();
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
    // WHO GROWTH TRAJECTORIES JAVASCRIPT ENGINE
    // ============================================================
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
        return Math.max(0, diff / (1000 * 60 * 60 * 24 * 30.44));
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

    function selectChild(id) {
        selectedChildId = id;
        const selector = document.getElementById('growth_child');
        if (selector && selector.value != id) selector.value = id;
        renderProperGrowthChart();
    }

    function setChartType(type) {
        currentChartType = type;
        const btnIds = ['btnWeight', 'btnHeight', 'btnBmi'];
        const activeClass = 'px-3 py-1.5 rounded-md font-bold transition bg-brand-dark text-white shadow-2xs cursor-pointer flex items-center gap-1.5';
        const inactiveClass = 'px-3 py-1.5 rounded-md font-semibold text-slate-600 hover:text-slate-900 transition cursor-pointer flex items-center gap-1.5';
        const typeMap = { weight: 'btnWeight', height: 'btnHeight', bmi: 'btnBmi' };
        btnIds.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) btn.className = (typeMap[type] === id) ? activeClass : inactiveClass;
        });
        renderProperGrowthChart();
    }

    function renderProperGrowthChart() {
        const selector = document.getElementById('growth_child');
        const childId = (selector && selector.value) ? selector.value : selectedChildId;
        const child = getChildById(childId);
        if (!child) return;
        selectedChildId = child.id;

        const data = getGrowthDataForChild(child.id);

        // Update clinical header summary
        const nameEl = document.getElementById('childSelectedName');
        const badgeEl = document.getElementById('childSelectedBadge');
        const summaryEl = document.getElementById('childGrowthSummary');
        const emptyAlert = document.getElementById('growthEmptyAlert');
        if (nameEl) nameEl.innerHTML = `<i class="fa-solid fa-chart-line text-brand-medium"></i> ${child.name} (${child.age || 'Child'}) — Trajectory`;

        const latestMeas = data && data.length > 0 ? data[data.length - 1] : null;
        if (latestMeas && summaryEl && badgeEl) {
            const latestAge = getAgeInMonths(child.birth_date, latestMeas.date);
            const percStr = getWeightPercentile(latestMeas.weight, child.gender, latestAge);
            let badgeClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
            if (percStr.includes('Below')) badgeClass = 'bg-rose-100 text-rose-800 border border-rose-200';
            else if (percStr.includes('Above')) badgeClass = 'bg-amber-100 text-amber-800 border border-amber-200';

            badgeEl.className = `px-2.5 py-1 ${badgeClass} rounded-full text-xs font-bold flex items-center gap-1`;
            badgeEl.innerHTML = `<i class="fa-solid fa-circle-check text-xs"></i> <span>WHO: ${percStr}</span>`;
            summaryEl.textContent = `Latest: ${latestMeas.weight} kg, ${latestMeas.height} cm (${latestMeas.date}) • ${data.length} checkup${data.length > 1 ? 's' : ''}`;
            if (emptyAlert) emptyAlert.classList.add('hidden');
        } else if (summaryEl && badgeEl) {
            badgeEl.className = 'px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-bold flex items-center gap-1 border border-slate-200';
            badgeEl.textContent = 'No Checkups Logged';
            summaryEl.textContent = `Age: ${child.age || '—'} • Click "Log Measurement" to record checkup`;
            if (emptyAlert) emptyAlert.classList.remove('hidden');
        }

        const canvas = document.getElementById('growthCanvas');
        if (!canvas) return;

        // Verify Chart.js availability
        if (typeof window.Chart === 'undefined') {
            setTimeout(renderProperGrowthChart, 80);
            return;
        }

        const ctx = canvas.getContext('2d');

        // Clean up previous Chart instance before redrawing
        if (growthChartInstance) {
            try {
                growthChartInstance.destroy();
            } catch (e) {
                console.warn('Chart destroy error:', e);
            }
            growthChartInstance = null;
        }

        const metricMeta = {
            weight: { title: 'Weight', unit: 'kg' },
            height: { title: 'Height', unit: 'cm' },
            bmi:    { title: 'BMI', unit: 'kg/m²' }
        };
        const meta = metricMeta[currentChartType] || metricMeta.weight;

        const gKey = (child.gender && child.gender.toLowerCase() === 'male') ? 'male' : 'female';
        const standards = (WHO_GROWTH_STANDARDS[currentChartType] && WHO_GROWTH_STANDARDS[currentChartType][gKey])
            ? WHO_GROWTH_STANDARDS[currentChartType][gKey]
            : WHO_GROWTH_STANDARDS.weight.female;

        let labels = [];
        let childValues = [];
        let whoMedianValues = [];

        if (data.length > 0) {
            data.forEach(d => {
                const ageMonths = getAgeInMonths(child.birth_date, d.date);
                const ageLabel = ageMonths < 12
                    ? Math.round(ageMonths) + 'm'
                    : Math.floor(ageMonths / 12) + 'y ' + Math.round(ageMonths % 12) + 'm';
                const dateFormatted = new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                labels.push(`${dateFormatted} (${ageLabel})`);

                let val = null;
                if (currentChartType === 'weight') val = parseFloat(d.weight);
                else if (currentChartType === 'height') val = parseFloat(d.height);
                else if (currentChartType === 'bmi') {
                    val = (d.weight && d.height) ? parseFloat((parseFloat(d.weight) / Math.pow(parseFloat(d.height) / 100, 2)).toFixed(1)) : null;
                }
                childValues.push(val);

                // Find matching WHO P50 standard
                let closestRef = standards[0];
                for (let i = 0; i < standards.length; i++) {
                    if (ageMonths >= standards[i].x) closestRef = standards[i];
                }
                whoMedianValues.push(closestRef.p50);
            });
        } else {
            // Milestone baseline when child has no visits yet
            const milestones = standards.slice(0, 9);
            labels = milestones.map(m => m.x === 0 ? 'Birth' : (m.x < 12 ? `${m.x} mos` : `${m.x / 12} yrs`));
            childValues = milestones.map(() => null);
            whoMedianValues = milestones.map(m => m.p50);
        }

        // Exact styling matching Patient Management Age Group Distribution
        const TOOLTIP_STYLE = {
            backgroundColor: '#1E293B',
            titleFont: { weight: '600', size: 12 },
            bodyFont: { size: 11 },
            padding: 10,
            cornerRadius: 8,
            displayColors: true,
            boxPadding: 4,
            callbacks: {
                label: function(context) {
                    const val = context.parsed.y;
                    if (val === null || val === undefined) return '';
                    return ` ${context.dataset.label}: ${val} ${meta.unit}`;
                }
            }
        };

        const datasets = [
            {
                label: `${child.name} (${meta.title})`,
                data: childValues,
                borderColor: '#14807A',                      // BRAND.medium
                backgroundColor: 'rgba(20, 128, 122, 0.18)', // BRAND.medium + '20' translucent area fill
                borderWidth: 3,
                pointBackgroundColor: '#0B4F4A',             // BRAND.dark
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: 6,
                pointHoverRadius: 8,
                fill: true,                                  // Smooth area fill matching Age Group Distribution
                tension: 0.4                                 // Smooth curved line
            },
            {
                label: 'WHO P50 Median Benchmark',
                data: whoMedianValues,
                borderColor: '#10B981',                      // Emerald reference median
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                pointHoverRadius: 4,
                fill: false,
                tension: 0.4
            }
        ];

        growthChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { family: "'Inter', 'Segoe UI', system-ui, sans-serif", size: 11, weight: '600' },
                            color: '#64748B',
                            padding: 12
                        }
                    },
                    tooltip: TOOLTIP_STYLE
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            color: '#64748B',
                            font: { family: "'Inter', 'Segoe UI', system-ui, sans-serif", size: 11, weight: '500' }
                        }
                    },
                    y: {
                        beginAtZero: false,
                        grid: { color: '#F1F5F9' },
                        border: { display: false },
                        ticks: {
                            color: '#64748B',
                            font: { family: "'Inter', 'Segoe UI', system-ui, sans-serif", size: 11 },
                            callback: function(val) {
                                return val + ' ' + meta.unit;
                            }
                        }
                    }
                }
            }
        });
    }

    async function deleteGrowthMeasurement(id) {
        if (!confirm('Are you sure you want to delete this growth measurement?')) return;
        try {
            const res = await fetch(`<?php echo site_url('api/growth.php'); ?>?id=${id}`, { method: 'DELETE' });
            const data = await res.json();
            if (data.success) {
                const idx = GROWTH_DATA.findIndex(g => g.id == id);
                if (idx !== -1) GROWTH_DATA.splice(idx, 1);
                renderProperGrowthChart();
                showToast('Measurement deleted successfully!', 'success');
            } else {
                showToast(data.message || 'Failed to delete measurement.', 'danger');
            }
        } catch (err) {
            console.error('Delete growth error:', err);
            showToast('Error deleting measurement.', 'danger');
        }
    }

    function limitGrowthMeasurement(input) {
        const parts = String(input.value || '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 3);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    async function saveGrowthMeasurement(event) {
        event.preventDefault();
        const childId = document.getElementById('growth_entry_child_id')?.value || document.getElementById('growth_child')?.value;
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

                closeModal('addGrowthModal');
                selectChild(childId);
                showToast('Growth measurement saved and trajectory updated!', 'success');

                // Reset form
                document.getElementById('growth_weight').value = '';
                document.getElementById('growth_height').value = '';
                if (document.getElementById('growth_head')) document.getElementById('growth_head').value = '';
                if (document.getElementById('growth_notes')) document.getElementById('growth_notes').value = '';
                if (document.getElementById('growth_date')) {
                    document.getElementById('growth_date').value = new Date().toISOString().split('T')[0];
                }
            } else {
                showToast(data.message || 'Failed to save growth measurement.', 'danger');
            }
        } catch (err) {
            console.error('Save growth measurement error:', err);
            showToast('Error saving growth measurement to server.', 'danger');
        }
    }

    // ============================================================
    // INITIALIZATION & URL ROUTING
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('screen_date');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        const nextInput = document.getElementById('screen_next');
        if (nextInput) {
            const date = new Date();
            date.setMonth(date.getMonth() + 3);
            nextInput.value = date.toISOString().split('T')[0];
        }

        const growthDateInput = document.getElementById('growth_date');
        if (growthDateInput) {
            growthDateInput.value = new Date().toISOString().split('T')[0];
        }

        // Support URL parameter navigation: ?open_growth=1&child_id=X or ?tab=growth
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('open_growth') === '1' || urlParams.get('tab') === 'growth') {
            setTimeout(() => {
                openGrowthModal(urlParams.get('child_id'));
            }, 150);
        }
    });
</script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>