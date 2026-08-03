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

$barangayCases = [
    ['name' => 'San Jose', 'lat' => 14.5794, 'lng' => 121.0359],
    ['name' => 'Poblacion', 'lat' => 14.5810, 'lng' => 121.0400],
    ['name' => 'Riverside', 'lat' => 14.5750, 'lng' => 121.0420],
    ['name' => 'San Antonio', 'lat' => 14.5830, 'lng' => 121.0380],
    ['name' => 'Bagong Silang', 'lat' => 14.5770, 'lng' => 121.0450],
    ['name' => 'Mabini', 'lat' => 14.5850, 'lng' => 121.0360],
    ['name' => 'Kaybiga', 'lat' => 14.5765, 'lng' => 121.0435],
    ['name' => 'Bagumbong', 'lat' => 14.5840, 'lng' => 121.0410],
    ['name' => 'Camarin', 'lat' => 14.5785, 'lng' => 121.0470],
];

require_once __DIR__ . '/../../config/database.php';

// Base Disease Data
$diseaseData = [];

try {
    $db = Database::getInstance();
    $dbCases = $db->query('surveillance_cases', 'GET');
    if (!empty($dbCases) && is_array($dbCases)) {
        $grouped = [];
        foreach ($dbCases as $c) {
            $disease = $c['disease'] ?? 'Dengue';
            $brgy = $c['barangay'] ?? 'San Jose';
            if (!isset($grouped[$disease])) {
                $grouped[$disease] = [];
            }
            if (!isset($grouped[$disease][$brgy])) {
                $grouped[$disease][$brgy] = 0;
            }
            $grouped[$disease][$brgy]++;
        }

        foreach ($grouped as $dis => $brgys) {
            $bList = [];
            $totalCount = 0;
            foreach ($brgys as $bName => $cnt) {
                $totalCount += $cnt;
                $status = $cnt >= 10 ? 'Critical' : ($cnt >= 5 ? 'Warning' : 'Normal');
                $bList[] = [
                    'name' => $bName,
                    'cases' => $cnt,
                    'baseline' => max(1, (int)round($cnt * 0.4)),
                    'threshold' => max(3, (int)round($cnt * 0.8)),
                    'status' => $status
                ];
            }
            $diseaseData[$dis] = [
                'barangays' => $bList,
                'total' => $totalCount,
                'trend' => '↑ ' . min(40, max(5, $totalCount * 2)) . '%',
                'status' => $totalCount >= 15 ? 'Outbreak Warning' : 'Normal'
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Supabase surveillance_cases query exception: ' . $e->getMessage());
}

if (empty($diseaseData)) {
    $diseaseData = [
        'Dengue' => [
            'barangays' => [
                ['name' => 'San Jose', 'cases' => 12, 'baseline' => 5, 'threshold' => 10, 'status' => 'Critical'],
                ['name' => 'Poblacion', 'cases' => 5, 'baseline' => 4, 'threshold' => 8, 'status' => 'Warning'],
                ['name' => 'Riverside', 'cases' => 3, 'baseline' => 3, 'threshold' => 6, 'status' => 'Normal'],
                ['name' => 'San Antonio', 'cases' => 8, 'baseline' => 4, 'threshold' => 8, 'status' => 'Warning'],
                ['name' => 'Bagong Silang', 'cases' => 2, 'baseline' => 2, 'threshold' => 5, 'status' => 'Normal'],
                ['name' => 'Mabini', 'cases' => 1, 'baseline' => 1, 'threshold' => 3, 'status' => 'Normal'],
                ['name' => 'Kaybiga', 'cases' => 4, 'baseline' => 3, 'threshold' => 6, 'status' => 'Normal'],
                ['name' => 'Bagumbong', 'cases' => 2, 'baseline' => 2, 'threshold' => 4, 'status' => 'Normal'],
                ['name' => 'Camarin', 'cases' => 7, 'baseline' => 4, 'threshold' => 8, 'status' => 'Warning'],
            ],
            'total' => 44,
            'trend' => '↑ 15%',
            'status' => 'Elevated'
        ],
        'Influenza' => [
            'barangays' => [
                ['name' => 'San Jose', 'cases' => 8, 'baseline' => 6, 'threshold' => 12, 'status' => 'Normal'],
                ['name' => 'Poblacion', 'cases' => 15, 'baseline' => 7, 'threshold' => 14, 'status' => 'Critical'],
                ['name' => 'Riverside', 'cases' => 5, 'baseline' => 5, 'threshold' => 10, 'status' => 'Normal'],
                ['name' => 'San Antonio', 'cases' => 3, 'baseline' => 4, 'threshold' => 8, 'status' => 'Normal'],
                ['name' => 'Bagong Silang', 'cases' => 4, 'baseline' => 3, 'threshold' => 6, 'status' => 'Warning'],
                ['name' => 'Mabini', 'cases' => 2, 'baseline' => 2, 'threshold' => 4, 'status' => 'Normal'],
                ['name' => 'Kaybiga', 'cases' => 6, 'baseline' => 4, 'threshold' => 8, 'status' => 'Warning'],
                ['name' => 'Bagumbong', 'cases' => 3, 'baseline' => 3, 'threshold' => 6, 'status' => 'Normal'],
                ['name' => 'Camarin', 'cases' => 4, 'baseline' => 4, 'threshold' => 8, 'status' => 'Normal'],
            ],
            'total' => 50,
            'trend' => '↑ 8%',
            'status' => 'Elevated'
        ],
        'Leptospirosis' => [
            'barangays' => [
                ['name' => 'San Jose', 'cases' => 0, 'baseline' => 1, 'threshold' => 3, 'status' => 'Normal'],
                ['name' => 'Poblacion', 'cases' => 0, 'baseline' => 1, 'threshold' => 3, 'status' => 'Normal'],
                ['name' => 'Riverside', 'cases' => 5, 'baseline' => 2, 'threshold' => 4, 'status' => 'Critical'],
                ['name' => 'San Antonio', 'cases' => 0, 'baseline' => 1, 'threshold' => 2, 'status' => 'Normal'],
                ['name' => 'Bagong Silang', 'cases' => 0, 'baseline' => 1, 'threshold' => 2, 'status' => 'Normal'],
                ['name' => 'Mabini', 'cases' => 0, 'baseline' => 0, 'threshold' => 2, 'status' => 'Normal'],
                ['name' => 'Kaybiga', 'cases' => 1, 'baseline' => 1, 'threshold' => 3, 'status' => 'Normal'],
                ['name' => 'Bagumbong', 'cases' => 0, 'baseline' => 0, 'threshold' => 2, 'status' => 'Normal'],
                ['name' => 'Camarin', 'cases' => 0, 'baseline' => 1, 'threshold' => 3, 'status' => 'Normal'],
            ],
            'total' => 6,
            'trend' => '↑ 20%',
            'status' => 'Normal'
        ]
    ];
}

// Generate alerts based on threshold monitoring
$alerts = [];
$barangayAlerts = [];
foreach ($diseaseData as $disease => $data) {
    foreach ($data['barangays'] as $b) {
        if ($b['cases'] >= $b['threshold']) {
            $severity = $b['cases'] >= $b['threshold'] * 1.5 ? 'Critical' : 'Warning';
            $alerts[] = [
                'disease' => $disease,
                'barangay' => $b['name'],
                'cases' => $b['cases'],
                'threshold' => $b['threshold'],
                'severity' => $severity,
                'message' => $b['cases'] >= $b['threshold'] * 1.5 
                    ? "Critical outbreak detected in {$b['name']}!" 
                    : "Alert: {$b['name']} has exceeded threshold for $disease"
            ];
            $barangayAlerts[] = $b['name'];
        }
    }
}

// Sort alerts by severity
usort($alerts, function($a, $b) {
    $order = ['Critical' => 0, 'Warning' => 1];
    return $order[$a['severity']] - $order[$b['severity']];
});

$totalAlerts = count($alerts);
$criticalAlerts = count(array_filter($alerts, function($a) { return $a['severity'] == 'Critical'; }));

// Pattern recognition data (weekly trends) - Extended to 12 weeks
$weeks = ['W1', 'W2', 'W3', 'W4', 'W5', 'W6', 'W7', 'W8', 'W9', 'W10', 'W11', 'W12'];
$patternData = [
    'Dengue' => [5, 7, 8, 12, 15, 18, 22, 25, 20, 16, 12, 10],
    'Influenza' => [8, 10, 13, 11, 15, 18, 16, 20, 24, 22, 18, 14],
    'Leptospirosis' => [1, 1, 2, 3, 3, 4, 5, 4, 3, 2, 2, 1]
];

$title = 'Outbreak Detection';
?>
<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Outbreak Detection</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-location-dot"></i> Caloocan City
                </span>
                <?php if ($totalAlerts > 0): ?>
                <span id="activeAlertsBadge" class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold flex items-center gap-1 animate-pulse">
                    <i class="fa-solid fa-circle text-[6px]"></i> <span id="totalAlertsBadgeCount"><?php echo $totalAlerts; ?></span> Active Alerts
                </span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">Automated detection, pattern recognition & real-time outbreak monitoring</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="runDetection()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-rotate text-xs"></i> Run Detection
            </button>
            <button onclick="refreshData()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- KPI CARDS - Outbreak Overview                              -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Alerts -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 <?php echo $totalAlerts > 0 ? 'bg-gradient-to-br from-red-500 to-red-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600'; ?> rounded-xl flex items-center justify-center text-white shadow-lg <?php echo $totalAlerts > 0 ? 'shadow-red-200' : 'shadow-emerald-200'; ?>">
                        <i class="fa-solid fa-bell text-lg"></i>
                    </div>
                    <div>
                        <p id="totalAlertsKPI" class="text-2xl font-black <?php echo $totalAlerts > 0 ? 'text-red-600' : 'text-emerald-600'; ?>"><?php echo $totalAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 <?php echo $totalAlerts > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $totalAlerts > 0 ? '⚠️ Needs Action' : '✅ All Clear'; ?>
                    </span>
                    <span id="criticalAlertsKPI" class="text-[10px] text-slate-400"><?php echo $criticalAlerts; ?> critical</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Diseases Monitored -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-virus text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo count($diseaseData); ?></p>
                        <p class="text-xs font-medium text-slate-500">Diseases Monitored</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🔬 Active</span>
                    <span class="text-[10px] text-slate-400"><?php echo implode(', ', array_keys($diseaseData)); ?></span>
                </div>
            </div>
        </div>

        <!-- Card 3: Threshold Breaches -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-chart-bar text-lg"></i>
                    </div>
                    <div>
                        <p id="thresholdBreachesKPI" class="text-2xl font-black text-amber-600"><?php echo count($barangayAlerts); ?></p>
                        <p class="text-xs font-medium text-slate-500">Threshold Breaches</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">📊 Exceeded</span>
                    <span class="text-[10px] text-slate-400">Above baseline</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Detection Status -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-robot text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark">Auto</p>
                        <p class="text-xs font-medium text-slate-500">Detection Mode</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold flex items-center gap-1">
                        <i class="fa-solid fa-circle text-[6px]"></i> Active
                    </span>
                    <span id="lastDetectionTime" class="text-[10px] text-slate-400">Real-time monitoring</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ALERT SECTION - Real-time Alerts                           -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-bell text-brand-medium"></i>
                Real-time Alerts
                <span id="alertCountLabel" class="text-xs font-normal text-slate-400">(<?php echo $totalAlerts; ?> active)</span>
            </h3>
            <div class="flex items-center gap-3">
                <button onclick="confirmMarkAllRead()" class="text-xs text-brand-dark hover:text-brand-medium font-medium">
                    Mark all read
                </button>
                <button onclick="confirmClearAlerts()" class="text-xs text-slate-400 hover:text-slate-600 font-medium">
                    Clear all
                </button>
            </div>
        </div>
        <div id="alertListContainer" class="p-4 max-h-[300px] overflow-y-auto">
            <?php if ($totalAlerts > 0): ?>
                <?php foreach ($alerts as $alert): ?>
                <div class="flex items-start gap-3 p-3 <?php echo $alert['severity'] == 'Critical' ? 'bg-red-50 border-l-4 border-red-500' : 'bg-amber-50 border-l-4 border-amber-500'; ?> rounded-lg mb-2 hover:shadow-sm transition" data-alert-id="<?php echo md5($alert['disease'].$alert['barangay']); ?>">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 <?php echo $alert['severity'] == 'Critical' ? 'bg-red-500' : 'bg-amber-500'; ?> rounded-full inline-block animate-pulse"></span>
                            <span class="text-sm font-bold <?php echo $alert['severity'] == 'Critical' ? 'text-red-700' : 'text-amber-700'; ?>">
                                <?php echo $alert['message']; ?>
                            </span>
                            <span class="px-2 py-0.5 <?php echo $alert['severity'] == 'Critical' ? 'bg-red-200 text-red-700' : 'bg-amber-200 text-amber-700'; ?> rounded-full text-[10px] font-bold">
                                <?php echo $alert['severity']; ?>
                            </span>
                        </div>
                        <div class="mt-1 flex items-center gap-4 text-xs text-slate-600">
                            <span>📍 <?php echo $alert['barangay']; ?></span>
                            <span>🦟 <?php echo $alert['disease']; ?></span>
                            <span>📊 <?php echo $alert['cases']; ?> cases (Threshold: <?php echo $alert['threshold']; ?>)</span>
                            <span class="text-slate-400"><?php echo date('h:i A'); ?></span>
                        </div>
                    </div>
                    <button onclick="dismissAlert(this)" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-check-circle text-3xl block mb-2 text-emerald-500"></i>
                    <p class="text-sm font-medium">No active alerts</p>
                    <p class="text-xs">All diseases are within normal thresholds</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DISEASE MONITORING CARDS                                   -->
    <!-- ============================================================ -->
    <div id="diseaseCardsContainer" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <?php foreach ($diseaseData as $disease => $data): 
            $statusColors = [
                'Critical' => 'border-red-500 bg-red-50',
                'Elevated' => 'border-amber-500 bg-amber-50',
                'Normal' => 'border-emerald-500 bg-emerald-50'
            ];
            $statusBadges = [
                'Critical' => 'bg-red-500 text-white',
                'Elevated' => 'bg-amber-500 text-white',
                'Normal' => 'bg-emerald-500 text-white'
            ];
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-lg transition disease-card" data-disease="<?php echo $disease; ?>">
            <div class="px-5 py-4 <?php echo $statusColors[$data['status']] ?? 'bg-slate-50'; ?> border-b flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800"><?php echo $disease; ?></h3>
                    <p class="text-xs text-slate-500"><?php echo count($data['barangays']); ?> barangays monitored</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="px-2 py-1 <?php echo $statusBadges[$data['status']] ?? 'bg-slate-500 text-white'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $data['status']; ?>
                    </span>
                    <span class="text-sm font-semibold text-slate-700"><?php echo $data['trend']; ?></span>
                </div>
            </div>
            <div class="p-4">
                <div class="flex items-center gap-4 mb-3">
                    <div>
                        <p class="text-2xl font-black text-slate-900 disease-total" data-disease="<?php echo $disease; ?>"><?php echo $data['total']; ?></p>
                        <p class="text-xs text-slate-500">total cases</p>
                    </div>
                    <div class="flex-1">
                        <div class="w-full bg-slate-200 rounded-full h-2">
                            <?php 
                                $maxTotal = max(array_column($diseaseData, 'total'));
                                $percentage = $maxTotal > 0 ? ($data['total'] / $maxTotal) * 100 : 0;
                            ?>
                            <div class="h-2 rounded-full <?php echo $data['status'] == 'Critical' ? 'bg-red-500' : ($data['status'] == 'Elevated' ? 'bg-amber-500' : 'bg-emerald-500'); ?>" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
                            <span>0</span>
                            <span><?php echo $maxTotal; ?> max</span>
                        </div>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <?php foreach ($data['barangays'] as $b): ?>
                    <div class="flex items-center justify-between text-xs barangay-row" data-disease="<?php echo $disease; ?>" data-barangay="<?php echo $b['name']; ?>">
                        <div class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo $b['status'] == 'Critical' ? 'bg-red-500' : ($b['status'] == 'Warning' ? 'bg-amber-500' : 'bg-emerald-500'); ?>"></span>
                            <span class="text-slate-700"><?php echo $b['name']; ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-semibold <?php echo $b['cases'] >= $b['threshold'] ? 'text-red-600' : 'text-slate-700'; ?> barangay-cases" data-disease="<?php echo $disease; ?>" data-barangay="<?php echo $b['name']; ?>">
                                <?php echo $b['cases']; ?>
                            </span>
                            <span class="text-slate-400">/ <?php echo $b['threshold']; ?></span>
                            <?php if ($b['cases'] >= $b['threshold']): ?>
                                <span class="text-[10px] <?php echo $b['cases'] >= $b['threshold'] * 1.5 ? 'text-red-500' : 'text-amber-500'; ?>">
                                    <i class="fa-solid fa-circle text-[6px]"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ============================================================ -->
    <!-- CHARTS SECTION - Pattern Recognition & Threshold           -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pattern Recognition Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-brand-medium"></i>
                    Pattern Recognition
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">12-week trend</span>
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold flex items-center gap-1">
                        <i class="fa-solid fa-circle text-[4px]"></i> AI Analysis
                    </span>
                </div>
            </div>
            <div class="p-4">
                <div id="pattern-chart" style="min-height: 320px;"></div>
            </div>
        </div>

        <!-- Threshold Monitoring Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-chart-bar text-brand-medium"></i>
                    Threshold Monitoring
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">Current vs Threshold</span>
                    <button onclick="toggleThresholdView()" class="px-2 py-1 bg-brand-light text-brand-dark rounded text-[10px] font-semibold hover:bg-brand-dark hover:text-white transition">
                        <i class="fa-solid fa-arrows-left-right"></i> Toggle View
                    </button>
                </div>
            </div>
            <div class="p-4">
                <div id="threshold-chart" style="min-height: 320px;"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- AUTOMATED DETECTION LOG                                     -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-6">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-robot text-brand-medium"></i>
                Automated Detection Log
            </h3>
            <span id="lastLogTimestamp" class="text-xs text-slate-400">Last run: <?php echo date('h:i A'); ?></span>
        </div>
        <div id="detectionLogContainer" class="p-4 max-h-[200px] overflow-y-auto">
            <div class="space-y-2 text-sm">
                <div class="flex items-start gap-3 p-2 bg-slate-50 rounded-lg">
                    <span class="text-emerald-500 mt-0.5"><i class="fa-solid fa-check-circle"></i></span>
                    <div>
                        <span class="font-medium text-slate-700">Detection scan completed</span>
                        <span class="text-slate-400 text-xs ml-2"><?php echo date('h:i A'); ?></span>
                        <p class="text-slate-500 text-xs mt-0.5">Scanned <?php echo count($diseaseData); ?> diseases across <?php echo count($barangayCases); ?> barangays</p>
                    </div>
                </div>
                <?php if ($totalAlerts > 0): ?>
                <div class="flex items-start gap-3 p-2 bg-amber-50 rounded-lg">
                    <span class="text-amber-500 mt-0.5"><i class="fa-solid fa-exclamation-triangle"></i></span>
                    <div>
                        <span class="font-medium text-amber-700"><?php echo $totalAlerts; ?> alerts generated</span>
                        <span class="text-slate-400 text-xs ml-2"><?php echo date('h:i A'); ?></span>
                        <p class="text-slate-500 text-xs mt-0.5"><?php echo $criticalAlerts; ?> critical, <?php echo $totalAlerts - $criticalAlerts; ?> warnings</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="flex items-start gap-3 p-2 bg-emerald-50 rounded-lg">
                    <span class="text-emerald-500 mt-0.5"><i class="fa-solid fa-shield-halved"></i></span>
                    <div>
                        <span class="font-medium text-emerald-700">All clear - No threats detected</span>
                        <span class="text-slate-400 text-xs ml-2"><?php echo date('h:i A'); ?></span>
                        <p class="text-slate-500 text-xs mt-0.5">All diseases are within normal thresholds</p>
                    </div>
                </div>
                <?php endif; ?>
                <div class="flex items-start gap-3 p-2 bg-slate-50 rounded-lg">
                    <span class="text-blue-500 mt-0.5"><i class="fa-solid fa-microchip"></i></span>
                    <div>
                        <span class="font-medium text-slate-700">Pattern analysis complete</span>
                        <span class="text-slate-400 text-xs ml-2"><?php echo date('h:i A'); ?></span>
                        <p class="text-slate-500 text-xs mt-0.5">Identified <?php echo count($diseaseData); ?> disease patterns</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL OVERLAY (for Mark all read / Clear all)               -->
<!-- ============================================================ -->
<div id="confirmationModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6 transform transition-all scale-95">
        <div class="flex items-start justify-between mb-4">
            <h3 id="modalTitle" class="text-lg font-bold text-slate-800">Confirm Action</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <p id="modalMessage" class="text-sm text-slate-600 mb-6">Are you sure you want to proceed?</p>
        <div class="flex justify-end gap-3">
            <button onclick="closeModal()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">
                Cancel
            </button>
            <button id="modalConfirmBtn" onclick="executeModalAction()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                Confirm
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- Local libraries                                              -->
<!-- ============================================================ -->
<script src="../../assets/js/apexcharts.min.js"></script>

<script>
    // PHP Data to JavaScript
    const PATTERN_DATA = <?php echo json_encode($patternData); ?>;
    const WEEKS = <?php echo json_encode($weeks); ?>;
    let DISEASE_DATA = <?php echo json_encode($diseaseData); ?>;  // Make it mutable

    let patternChart = null;
    let thresholdChart = null;
    let thresholdViewMode = 'barangay';

    // ============================================================
    // MODAL CONTROLS
    // ============================================================
    let modalConfirmCallback = null;

    function showModal(title, message, confirmCallback) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalMessage').textContent = message;
        modalConfirmCallback = confirmCallback;
        document.getElementById('confirmationModal').classList.remove('hidden');
        document.getElementById('confirmationModal').classList.add('flex');
        // animate in
        document.querySelector('#confirmationModal > div').classList.remove('scale-95');
        document.querySelector('#confirmationModal > div').classList.add('scale-100');
    }

    function closeModal() {
        document.getElementById('confirmationModal').classList.add('hidden');
        document.getElementById('confirmationModal').classList.remove('flex');
        modalConfirmCallback = null;
    }

    function executeModalAction() {
        if (typeof modalConfirmCallback === 'function') {
            modalConfirmCallback();
        }
        closeModal();
    }

    // ============================================================
    // CONFIRMATION WRAPPERS
    // ============================================================
    function confirmMarkAllRead() {
        showModal('Mark All as Read', 'This will dismiss all active alerts. Are you sure?', function() {
            markAllRead();
        });
    }

    function confirmClearAlerts() {
        showModal('Clear All Alerts', 'This will permanently remove all alerts from the list. Continue?', function() {
            clearAlerts();
        });
    }

    // ============================================================
    // PATTERN RECOGNITION CHART
    // ============================================================
    function initPatternChart() {
        const el = document.getElementById('pattern-chart');
        if (!el) return;

        const calculateTrend = (data, window = 3) => {
            const trend = [];
            for (let i = 0; i < data.length; i++) {
                let sum = 0;
                let count = 0;
                for (let j = Math.max(0, i - window + 1); j <= i; j++) {
                    sum += data[j];
                    count++;
                }
                trend.push(Math.round(sum / count));
            }
            return trend;
        };

        const colors = {
            'Dengue': '#ef4444',
            'Influenza': '#3b82f6',
            'Leptospirosis': '#8b5cf6'
        };

        const series = [];
        Object.keys(PATTERN_DATA).forEach(disease => {
            const data = PATTERN_DATA[disease];
            const trend = calculateTrend(data);
            series.push({
                name: disease,
                data: data,
                color: colors[disease]
            });
            series.push({
                name: disease + ' Trend',
                data: trend,
                color: colors[disease],
                dashArray: [5, 5]
            });
        });

        patternChart = new ApexCharts(el, {
            series: series,
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: true },
                zoom: { enabled: true },
                animations: { enabled: true, easing: 'easeinout', speed: 600 },
                background: 'transparent'
            },
            stroke: {
                curve: 'smooth',
                width: [3, 1.5, 3, 1.5, 3, 1.5]
            },
            markers: {
                size: [4, 0, 4, 0, 4, 0],
                hover: { size: 6 }
            },
            xaxis: {
                categories: WEEKS,
                title: { text: 'Week', style: { fontSize: '11px', fontWeight: 600 } },
                labels: { style: { fontSize: '10px' } }
            },
            yaxis: {
                title: { text: 'Cases', style: { fontSize: '11px', fontWeight: 600 } },
                min: 0
            },
            colors: ['#ef4444', '#ef4444', '#3b82f6', '#3b82f6', '#8b5cf6', '#8b5cf6'],
            legend: {
                position: 'top',
                fontSize: '10px',
                horizontalAlign: 'left',
                labels: { colors: '#475569' }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' cases';
                    }
                }
            },
            grid: {
                borderColor: '#E2E8F0',
                strokeDashArray: 4
            },
            annotations: {
                yaxis: [{
                    y: 20,
                    borderColor: '#ef4444',
                    label: {
                        borderColor: '#ef4444',
                        style: {
                            color: '#fff',
                            background: '#ef4444',
                            fontSize: '10px',
                            fontWeight: 600
                        },
                        text: '⚠️ Alert Threshold'
                    }
                }]
            }
        });

        patternChart.render();
    }

    // ============================================================
    // THRESHOLD MONITORING CHART
    // ============================================================
    function initThresholdChart() {
        renderThresholdChart('barangay');
    }

    function renderThresholdChart(mode) {
        const el = document.getElementById('threshold-chart');
        if (!el) return;

        let categories = [];
        let currentData = [];
        let thresholdData = [];
        let barColors = [];

        if (mode === 'barangay') {
            Object.keys(DISEASE_DATA).forEach(disease => {
                DISEASE_DATA[disease].barangays.forEach(b => {
                    categories.push(b.name);
                    currentData.push(b.cases);
                    thresholdData.push(b.threshold);
                    barColors.push(
                        b.status === 'Critical' ? '#ef4444' :
                        b.status === 'Warning' ? '#f59e0b' : '#22c55e'
                    );
                });
            });
        } else {
            categories = Object.keys(DISEASE_DATA);
            currentData = categories.map(d => DISEASE_DATA[d].total);
            thresholdData = categories.map(d => {
                const thresholds = DISEASE_DATA[d].barangays.map(b => b.threshold);
                return Math.round(thresholds.reduce((a, b) => a + b, 0) / thresholds.length);
            });
            barColors = ['#ef4444', '#3b82f6', '#8b5cf6'];
        }

        if (thresholdChart) {
            thresholdChart.destroy();
        }

        thresholdChart = new ApexCharts(el, {
            series: [
                {
                    name: 'Current Cases',
                    data: currentData,
                    type: 'bar'
                },
                {
                    name: 'Threshold',
                    data: thresholdData,
                    type: 'line'
                }
            ],
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: true },
                animations: { enabled: true, easing: 'easeinout', speed: 600 },
                background: 'transparent'
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: mode === 'barangay' ? '45%' : '60%',
                    dataLabels: {
                        position: 'top'
                    },
                    colors: {
                        ranges: barColors.map((color, index) => ({
                            from: 0,
                            to: 100,
                            color: color
                        }))
                    }
                }
            },
            dataLabels: {
                enabled: true,
                offsetY: -15,
                style: {
                    fontSize: '9px',
                    colors: ['#475569']
                },
                formatter: function(val) {
                    return val;
                }
            },
            xaxis: {
                categories: categories,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: mode === 'barangay' ? '9px' : '11px',
                        fontWeight: 600
                    }
                },
                title: {
                    text: mode === 'barangay' ? 'Barangay' : 'Disease',
                    style: { fontSize: '11px', fontWeight: 600 }
                }
            },
            yaxis: {
                title: { text: 'Cases', style: { fontSize: '11px', fontWeight: 600 } },
                min: 0
            },
            colors: ['#14807A', '#ef4444'],
            legend: {
                position: 'top',
                fontSize: '11px',
                horizontalAlign: 'left'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' cases';
                    }
                }
            },
            grid: {
                borderColor: '#E2E8F0',
                strokeDashArray: 4
            }
        });

        thresholdChart.render();
    }

    // ============================================================
    // TOGGLE THRESHOLD VIEW
    // ============================================================
    function toggleThresholdView() {
        thresholdViewMode = thresholdViewMode === 'barangay' ? 'disease' : 'barangay';
        renderThresholdChart(thresholdViewMode);
        showToast('Switched to ' + (thresholdViewMode === 'barangay' ? 'Barangay' : 'Disease') + ' view', 'info');
    }

    // ============================================================
    // RUN DETECTION
    // ============================================================
    function runDetection() {
        showToast('🔍 Running outbreak detection...', 'info');
        // Simulate detection process (re‑evaluate thresholds and refresh UI)
        setTimeout(() => {
            // Re‑generate alerts from current DISEASE_DATA
            const alerts = generateAlerts(DISEASE_DATA);
            const total = alerts.length;
            const critical = alerts.filter(a => a.severity === 'Critical').length;

            // Update KPI
            document.getElementById('totalAlertsKPI').textContent = total;
            document.getElementById('criticalAlertsKPI').textContent = critical + ' critical';
            document.getElementById('alertCountLabel').textContent = `(${total} active)`;
            document.getElementById('totalAlertsBadgeCount').textContent = total;

            // Update active badge visibility
            const badge = document.getElementById('activeAlertsBadge');
            if (badge) {
                if (total > 0) {
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }

            // Update alert list
            renderAlertList(alerts);

            // Update threshold breaches KPI (number of barangays with alerts)
            const breachedBarangays = new Set();
            alerts.forEach(a => breachedBarangays.add(a.barangay));
            document.getElementById('thresholdBreachesKPI').textContent = breachedBarangays.size;

            // Update threshold chart (data may have changed)
            renderThresholdChart(thresholdViewMode);

            // Update disease cards (total cases, barangay cases, statuses)
            updateDiseaseCards(DISEASE_DATA);

            // Update log
            updateDetectionLog(alerts, 'Detection scan completed (manual run)');

            // Update timestamp
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('lastDetectionTime').textContent = 'Last run: ' + timeStr;
            document.getElementById('lastLogTimestamp').textContent = 'Last run: ' + timeStr;

            showToast('✅ Detection complete! ' + total + ' alerts found', 'success');
        }, 1200);
    }

    // ============================================================
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('🔄 Refreshing data...', 'info');
        setTimeout(() => {
            location.reload();
        }, 600);
    }

    // ============================================================
    // MARK ALL READ
    // ============================================================
    function markAllRead() {
        // Remove all alerts from list and update counters
        const container = document.getElementById('alertListContainer');
        container.innerHTML = `
            <div class="text-center py-8 text-slate-400">
                <i class="fa-solid fa-check-circle text-3xl block mb-2 text-emerald-500"></i>
                <p class="text-sm font-medium">No active alerts</p>
                <p class="text-xs">All alerts have been marked as read</p>
            </div>
        `;
        document.getElementById('totalAlertsKPI').textContent = '0';
        document.getElementById('criticalAlertsKPI').textContent = '0 critical';
        document.getElementById('alertCountLabel').textContent = '(0 active)';
        document.getElementById('totalAlertsBadgeCount').textContent = '0';
        document.getElementById('activeAlertsBadge').classList.add('hidden');
        document.getElementById('thresholdBreachesKPI').textContent = '0';

        // Also update log
        const log = document.getElementById('detectionLogContainer');
        const entry = document.createElement('div');
        entry.className = 'flex items-start gap-3 p-2 bg-slate-50 rounded-lg';
        entry.innerHTML = `
            <span class="text-blue-500 mt-0.5"><i class="fa-solid fa-check-double"></i></span>
            <div>
                <span class="font-medium text-slate-700">All alerts marked as read</span>
                <span class="text-slate-400 text-xs ml-2">${new Date().toLocaleTimeString()}</span>
            </div>
        `;
        log.prepend(entry);

        showToast('✅ All alerts marked as read', 'success');
    }

    // ============================================================
    // CLEAR ALL ALERTS
    // ============================================================
    function clearAlerts() {
        // Same as markAllRead but with a different message
        markAllRead(); // reuse, but we can change the log message
        const log = document.getElementById('detectionLogContainer');
        // Remove the last entry (which is "All alerts marked as read") and replace with "Cleared all alerts"
        const firstEntry = log.querySelector('.flex');
        if (firstEntry) {
            firstEntry.querySelector('.font-medium').textContent = 'All alerts cleared';
            firstEntry.querySelector('.fa-check-double').className = 'fa-solid fa-trash-can text-rose-500';
            firstEntry.querySelector('span.text-blue-500').className = 'text-rose-500 mt-0.5';
        }
        showToast('🗑️ All alerts cleared', 'info');
    }

    // ============================================================
    // DISMISS SINGLE ALERT
    // ============================================================
    function dismissAlert(btn) {
        const alertDiv = btn.closest('.flex');
        alertDiv.style.opacity = '0';
        alertDiv.style.transform = 'translateX(20px)';
        setTimeout(() => {
            alertDiv.remove();
            // Update counters
            const remaining = document.querySelectorAll('#alertListContainer .flex').length;
            document.getElementById('totalAlertsKPI').textContent = remaining;
            document.getElementById('alertCountLabel').textContent = `(${remaining} active)`;
            document.getElementById('totalAlertsBadgeCount').textContent = remaining;
            if (remaining === 0) {
                document.getElementById('activeAlertsBadge').classList.add('hidden');
                document.getElementById('criticalAlertsKPI').textContent = '0 critical';
                document.getElementById('thresholdBreachesKPI').textContent = '0';
                // Show empty state
                const container = document.getElementById('alertListContainer');
                if (container.children.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-8 text-slate-400">
                            <i class="fa-solid fa-check-circle text-3xl block mb-2 text-emerald-500"></i>
                            <p class="text-sm font-medium">No active alerts</p>
                            <p class="text-xs">All diseases are within normal thresholds</p>
                        </div>
                    `;
                }
            }
            showToast('Alert dismissed', 'info');
        }, 300);
    }

    // ============================================================
    // HELPERS: Generate alerts from data
    // ============================================================
    function generateAlerts(data) {
        const alerts = [];
        Object.keys(data).forEach(disease => {
            data[disease].barangays.forEach(b => {
                if (b.cases >= b.threshold) {
                    const severity = b.cases >= b.threshold * 1.5 ? 'Critical' : 'Warning';
                    alerts.push({
                        disease: disease,
                        barangay: b.name,
                        cases: b.cases,
                        threshold: b.threshold,
                        severity: severity,
                        message: b.cases >= b.threshold * 1.5 
                            ? `Critical outbreak detected in ${b.name}!` 
                            : `Alert: ${b.name} has exceeded threshold for ${disease}`
                    });
                }
            });
        });
        // Sort: Critical first
        alerts.sort((a, b) => {
            const order = { 'Critical': 0, 'Warning': 1 };
            return order[a.severity] - order[b.severity];
        });
        return alerts;
    }

    // ============================================================
    // RENDER ALERT LIST
    // ============================================================
    function renderAlertList(alerts) {
        const container = document.getElementById('alertListContainer');
        if (alerts.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-check-circle text-3xl block mb-2 text-emerald-500"></i>
                    <p class="text-sm font-medium">No active alerts</p>
                    <p class="text-xs">All diseases are within normal thresholds</p>
                </div>
            `;
            return;
        }

        let html = '';
        alerts.forEach(alert => {
            const severityClass = alert.severity === 'Critical' ? 'bg-red-50 border-l-4 border-red-500' : 'bg-amber-50 border-l-4 border-amber-500';
            const textColor = alert.severity === 'Critical' ? 'text-red-700' : 'text-amber-700';
            const badgeColor = alert.severity === 'Critical' ? 'bg-red-200 text-red-700' : 'bg-amber-200 text-amber-700';
            const dotColor = alert.severity === 'Critical' ? 'bg-red-500' : 'bg-amber-500';
            const id = alert.disease + alert.barangay;
            html += `
                <div class="flex items-start gap-3 p-3 ${severityClass} rounded-lg mb-2 hover:shadow-sm transition" data-alert-id="${id}">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 ${dotColor} rounded-full inline-block animate-pulse"></span>
                            <span class="text-sm font-bold ${textColor}">${alert.message}</span>
                            <span class="px-2 py-0.5 ${badgeColor} rounded-full text-[10px] font-bold">${alert.severity}</span>
                        </div>
                        <div class="mt-1 flex items-center gap-4 text-xs text-slate-600">
                            <span>📍 ${alert.barangay}</span>
                            <span>🦟 ${alert.disease}</span>
                            <span>📊 ${alert.cases} cases (Threshold: ${alert.threshold})</span>
                            <span class="text-slate-400">${new Date().toLocaleTimeString()}</span>
                        </div>
                    </div>
                    <button onclick="dismissAlert(this)" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            `;
        });
        container.innerHTML = html;
    }

    // ============================================================
    // UPDATE DISEASE CARDS (totals and barangay rows)
    // ============================================================
    function updateDiseaseCards(data) {
        // Update total cases per disease
        document.querySelectorAll('.disease-total').forEach(el => {
            const disease = el.dataset.disease;
            if (data[disease]) {
                el.textContent = data[disease].total;
            }
        });

        // Update each barangay cases
        document.querySelectorAll('.barangay-cases').forEach(el => {
            const disease = el.dataset.disease;
            const barangay = el.dataset.barangay;
            if (data[disease]) {
                const b = data[disease].barangays.find(b => b.name === barangay);
                if (b) {
                    el.textContent = b.cases;
                    // Update color based on threshold
                    const threshold = b.threshold;
                    if (b.cases >= threshold) {
                        el.classList.add('text-red-600');
                        el.classList.remove('text-slate-700');
                    } else {
                        el.classList.add('text-slate-700');
                        el.classList.remove('text-red-600');
                    }
                }
            }
        });

        // Update status dot for each barangay (the small circle)
        document.querySelectorAll('.barangay-row').forEach(row => {
            const disease = row.dataset.disease;
            const barangay = row.dataset.barangay;
            if (data[disease]) {
                const b = data[disease].barangays.find(b => b.name === barangay);
                if (b) {
                    const dot = row.querySelector('.w-1\\.5');
                    if (dot) {
                        dot.className = 'w-1.5 h-1.5 rounded-full ' + (
                            b.status === 'Critical' ? 'bg-red-500' :
                            b.status === 'Warning' ? 'bg-amber-500' : 'bg-emerald-500'
                        );
                    }
                }
            }
        });

        // Update card header status badges and trend (if changed, but we keep original)
        // For simplicity we don't update status badges as they depend on overall disease status,
        // which we don't recalculate here. Could be added.
    }

    // ============================================================
    // UPDATE DETECTION LOG
    // ============================================================
    function updateDetectionLog(alerts, scanMessage) {
        const container = document.getElementById('detectionLogContainer');
        const now = new Date();
        const timeStr = now.toLocaleTimeString();

        // Remove old "scan completed" entry if exists (first entry)
        const firstEntry = container.querySelector('.flex');
        if (firstEntry) {
            // We'll keep it and prepend new entry
        }

        // Prepend new scan entry
        const entry = document.createElement('div');
        entry.className = 'flex items-start gap-3 p-2 bg-slate-50 rounded-lg';
        entry.innerHTML = `
            <span class="text-emerald-500 mt-0.5"><i class="fa-solid fa-check-circle"></i></span>
            <div>
                <span class="font-medium text-slate-700">${scanMessage}</span>
                <span class="text-slate-400 text-xs ml-2">${timeStr}</span>
                <p class="text-slate-500 text-xs mt-0.5">Scanned ${Object.keys(DISEASE_DATA).length} diseases across ${DISEASE_DATA[Object.keys(DISEASE_DATA)[0]].barangays.length} barangays</p>
            </div>
        `;
        container.prepend(entry);

        // Update alerts summary entry (second entry if exists)
        const alertSummary = container.querySelectorAll('.flex')[1];
        if (alertSummary) {
            // Update it if it's the alert summary
            if (alertSummary.querySelector('.text-amber-500') || alertSummary.querySelector('.text-emerald-500')) {
                if (alerts.length > 0) {
                    const critical = alerts.filter(a => a.severity === 'Critical').length;
                    alertSummary.innerHTML = `
                        <span class="text-amber-500 mt-0.5"><i class="fa-solid fa-exclamation-triangle"></i></span>
                        <div>
                            <span class="font-medium text-amber-700">${alerts.length} alerts generated</span>
                            <span class="text-slate-400 text-xs ml-2">${timeStr}</span>
                            <p class="text-slate-500 text-xs mt-0.5">${critical} critical, ${alerts.length - critical} warnings</p>
                        </div>
                    `;
                } else {
                    alertSummary.innerHTML = `
                        <span class="text-emerald-500 mt-0.5"><i class="fa-solid fa-shield-halved"></i></span>
                        <div>
                            <span class="font-medium text-emerald-700">All clear - No threats detected</span>
                            <span class="text-slate-400 text-xs ml-2">${timeStr}</span>
                            <p class="text-slate-500 text-xs mt-0.5">All diseases are within normal thresholds</p>
                        </div>
                    `;
                }
            }
        }
    }

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
    // INITIALIZE
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            initPatternChart();
            initThresholdChart();
        }, 500);
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
    #confirmationModal {
        transition: opacity 0.2s ease;
    }
    #confirmationModal > div {
        transition: transform 0.2s ease;
    }
</style>

<?php include_once '../../includes/footer.php'; ?>