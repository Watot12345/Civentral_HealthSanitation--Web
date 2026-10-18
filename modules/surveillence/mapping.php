<?php
// ============================================================
// modules/surveillence/mapping.php
// Production-Ready Unified Disease Surveillance & Geospatial Mapping
// Standard-Compliant (ISO/IEC 25010) & Enhanced UX
// Sources: surveillance_cases, consultations (Health Center), immunizations
// ============================================================

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
requireDepartmentAccess('health surveillance');

require_once __DIR__ . '/../../app/services/SurveillanceService.php';
require_once __DIR__ . '/../../app/Models/Barangay.php';

$service = new SurveillanceService();
$barangayModel = new Barangay();

// ── 1. Filter Parameters ─────────────────────────────────────
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-180 days'));
$dateTo   = $_GET['date_to']   ?? date('Y-m-d');
$barangayFilter = $_GET['barangay'] ?? null;
if ($barangayFilter === '') $barangayFilter = null;

// ── 2. Unified Epidemiological Data ──────────────────────────
$cases        = $service->getUnifiedCases($dateFrom, $dateTo, $barangayFilter);
$clusters     = $service->detectClusters($cases);
$summary      = $service->getSummary($cases);
$timelineData = $service->getTimelineData($cases);

// ── 3. Centroids & Barangay Dictionary (All 193 Barangays across 16 Zones) ──
$allBarangays = $barangayModel->allForSurveillance();
$barangayCentroids = [];
$barangayCaseCounts = [];

foreach ($allBarangays as $b) {
    $num = (int)$b['barangay_no'];
    $barangayCentroids[$num] = [
        'lat'        => (float)$b['lat'],
        'lng'        => (float)$b['lng'],
        'name'       => $b['name'],
        'zone'       => $b['zone'],
        'district'   => (int)($b['district'] ?? 1),
        'region'     => $b['region'] ?? 'South Caloocan',
        'landmark'   => $b['landmark'] ?? '',
        'population' => (int)($b['population'] ?? 10000)
    ];
    $barangayCaseCounts[$num] = 0;
}

// ── 4. Deterministic Geo-Jittering Engine ─────────────────────
function jitteredPoint(float $lat, float $lng, string $seed): array
{
    $hash = crc32($seed);
    mt_srand($hash);
    $offsetLat = (mt_rand(-40, 40) / 100000.0);
    $offsetLng = (mt_rand(-40, 40) / 100000.0);
    mt_srand(); // Restore randomness
    return [$lat + $offsetLat, $lng + $offsetLng];
}

// ── 5. Build Map Points, Heatmap Data & Unmapped Triages ──────
$mapPoints   = [];
$heatmapData = [];
$unmapped    = [];

$diseaseColors = [
    'dengue'                => '#ef4444',
    'influenza'             => '#3b82f6',
    'leptospirosis'         => '#f97316',
    'measles'               => '#a855f7',
    'acute gastroenteritis' => '#10b981',
    'tuberculosis'          => '#06b6d4',
    'covid-19'              => '#e11d48',
    'hypertension'          => '#64748b',
    'diabetes'              => '#8b5cf6',
    'vpd risk'              => '#f59e0b'
];

foreach ($cases as $case) {
    $rawBrgy = (string)($case['barangay'] ?? ($case['raw_barangay'] ?? ''));
    $bNum = is_numeric($rawBrgy) ? (int)$rawBrgy : (int)preg_replace('/\D/', '', $rawBrgy);

    $centroid = null;
    if ($bNum && isset($barangayCentroids[$bNum])) {
        $centroid = $barangayCentroids[$bNum];
    } else {
        // Fallback string matching across all 193 barangay names and landmarks (e.g. Bagong Silang, Camarin, Maypajo)
        $cleanRaw = strtolower(trim($rawBrgy));
        if (!empty($cleanRaw)) {
            foreach ($barangayCentroids as $bKey => $bMeta) {
                if (str_contains(strtolower($bMeta['name']), $cleanRaw) || (!empty($bMeta['landmark']) && str_contains(strtolower($bMeta['landmark']), $cleanRaw))) {
                    $centroid = $bMeta;
                    $bNum = $bKey;
                    break;
                }
            }
        }
    }

    if (!$centroid) {
        // Triage out-of-district / unmapped cases into dedicated drawer
        $unmapped[] = $case;
        continue;
    }

    $barangayCaseCounts[$bNum] = ($barangayCaseCounts[$bNum] ?? 0) + 1;
    [$pLat, $pLng] = jitteredPoint($centroid['lat'], $centroid['lng'], (string)$case['id']);

    $mapPoints[] = [
        'id'           => $case['id'],
        'case_code'    => $case['case_code'] ?? 'CS-N/A',
        'lat'          => $pLat,
        'lng'          => $pLng,
        'disease'      => $case['disease'],
        'patient_name' => $case['patient_name'],
        'age'          => $case['age'] ?? 0,
        'gender'       => $case['gender'] ?? 'Unknown',
        'barangay'     => $bNum,
        'barangay_name'=> $centroid['name'],
        'landmark'     => $centroid['landmark'],
        'date'         => $case['case_date'],
        'month'        => date('M Y', strtotime($case['case_date'])),
        'source'       => $case['source'],
        'source_type'  => $case['source_type'],
        'status'       => $case['status'],
        'severity'     => $case['severity'],
        'symptoms'     => $case['symptoms']
    ];

    $heatmapData[] = [$pLat, $pLng, 1];
}

// ── 6. Prepare Outbreak Cluster Overlays (Mathematically Centered on Actual Cases) ──
$clusterOverlays = [];
$activeClusterBarangayMap = [];

foreach ($clusters as $c) {
    $rawBrgy = (string)$c['barangay'];
    $bNum = is_numeric($rawBrgy) ? (int)$rawBrgy : (int)preg_replace('/\D/', '', $rawBrgy);
    if (!$bNum) {
        $cleanRaw = strtolower(trim($rawBrgy));
        foreach ($barangayCentroids as $bKey => $bMeta) {
            if (str_contains(strtolower($bMeta['name']), $cleanRaw) || (!empty($bMeta['landmark']) && str_contains(strtolower($bMeta['landmark']), $cleanRaw))) {
                $bNum = $bKey;
                break;
            }
        }
    }

    // Match actual case points for this cluster
    $matchedPoints = [];
    $clusterDiseaseLower = strtolower(trim($c['disease']));

    foreach ($mapPoints as $p) {
        $pDiseaseLower = strtolower(trim($p['disease']));
        $diseaseMatch = str_contains($pDiseaseLower, $clusterDiseaseLower) || str_contains($clusterDiseaseLower, $pDiseaseLower) || (str_contains($clusterDiseaseLower, 'dengue') && str_contains($pDiseaseLower, 'dengue'));
        
        $barangayMatch = false;
        if ($bNum && (int)$p['barangay'] === $bNum) {
            $barangayMatch = true;
        } elseif (!$bNum && str_contains(strtolower($p['barangay_name']), strtolower($rawBrgy))) {
            $barangayMatch = true;
        }

        if ($diseaseMatch && $barangayMatch) {
            $matchedPoints[] = $p;
        }
    }

    // Secondary fallback: if cluster is flagged for a barangay that has cases
    if (empty($matchedPoints) && $bNum) {
        foreach ($mapPoints as $p) {
            if ((int)$p['barangay'] === $bNum) {
                $matchedPoints[] = $p;
            }
        }
    }

    // Never render a ghost cluster with 0 cases inside District 1
    if (empty($matchedPoints)) {
        continue;
    }

    // True mathematical average of case coordinates (100% concentric alignment)
    $avgLat = array_sum(array_column($matchedPoints, 'lat')) / count($matchedPoints);
    $avgLng = array_sum(array_column($matchedPoints, 'lng')) / count($matchedPoints);

    // Calculate maximum distance to member cases to ensure complete enclosure
    $maxDistMeters = 0;
    foreach ($matchedPoints as $p) {
        $dLat = ($p['lat'] - $avgLat) * 111320;
        $dLng = ($p['lng'] - $avgLng) * (111320 * cos(deg2rad($avgLat)));
        $dist = sqrt($dLat * $dLat + $dLng * $dLng);
        if ($dist > $maxDistMeters) $maxDistMeters = $dist;
    }
    $radiusMeters = max(110, round($maxDistMeters + 35));
    $bResolved = (int)$matchedPoints[0]['barangay'];

    $clusterOverlays[] = [
        'lat'          => $avgLat,
        'lng'          => $avgLng,
        'radius_meters'=> $radiusMeters,
        'barangay_no'  => $bResolved,
        'barangay'     => $matchedPoints[0]['barangay_name'] ?? ('Brgy ' . $bResolved),
        'disease'      => $c['disease'],
        'case_count'   => count($matchedPoints),
        'window_start' => $c['window_start'],
        'window_end'   => $c['window_end'],
        'risk_level'   => $c['risk_level']
    ];

    $activeClusterBarangayMap[$bResolved] = $c;
}

// ── 7. Build Harmonized Barangay Risk Buffers ─────────────────
$barangayBuffers = [];
foreach ($barangayCentroids as $bNum => $c) {
    $total = $barangayCaseCounts[$bNum] ?? 0;
    if ($total > 0) {
        $attackRate = $c['population'] > 0 ? round(($total / $c['population']) * 1000, 2) : 0;
        $hasActiveCluster = isset($activeClusterBarangayMap[$bNum]);

        // Center buffer precisely on the cases of this barangay
        $brgyCases = array_filter($mapPoints, fn($p) => (int)$p['barangay'] === $bNum);
        $bLat = !empty($brgyCases) ? (array_sum(array_column($brgyCases, 'lat')) / count($brgyCases)) : $c['lat'];
        $bLng = !empty($brgyCases) ? (array_sum(array_column($brgyCases, 'lng')) / count($brgyCases)) : $c['lng'];

        if ($hasActiveCluster || $total >= 20) {
            $risk = 'High Outbreak';
            $color = '#ef4444'; // Red (harmonized with outbreak alert)
        } elseif ($total >= 10) {
            $risk = 'Moderate';
            $color = '#f59e0b'; // Amber
        } else {
            $risk = 'Low';
            $color = '#3b82f6'; // Blue
        }

        $barangayBuffers[] = [
            'barangay_no' => $bNum,
            'name'        => $c['name'],
            'lat'         => $bLat,
            'lng'         => $bLng,
            'total'       => $total,
            'attack_rate' => $attackRate,
            'population'  => $c['population'],
            'risk'        => $risk,
            'color'       => $color,
            'has_outbreak'=> $hasActiveCluster,
            'landmark'    => $c['landmark']
        ];
    }
}

$title = 'Geospatial Disease Surveillance & Outbreak Clustering';
?>

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-y-auto">

    <!-- Page Header & Action Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Geospatial Disease Surveillance</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-location-dot"></i> Caloocan City
                </span>
            </div>
            <p class="text-sm text-slate-500">Real-time epidemiological point density, multi-source ingestion & 14-day rolling outbreak clustering</p>
        </div>

        <!-- Quick Navigation Actions -->
        <div class="flex items-center gap-2.5">
            <a href="outbreak_command.php" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold transition shadow-2xs flex items-center gap-1.5">
                <i class="fa-solid fa-chart-line text-brand-dark"></i> Outbreak Intelligence
            </a>
            <a href="case_reports.php" class="px-3.5 py-2 bg-brand-dark text-white rounded-xl text-xs font-semibold hover:bg-brand-medium transition shadow-2xs flex items-center gap-1.5">
                <i class="fa-solid fa-file-medical"></i> Case Intake
            </a>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS                                             -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Cases -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-teal-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-teal-500 to-teal-700 rounded-xl flex items-center justify-center text-white shadow-md shadow-teal-200">
                    <i class="fa-solid fa-notes-medical text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900"><?= $summary['total_cases']; ?></p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Active Cases</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-teal-50 text-teal-700 border border-teal-200 rounded-full text-[10px] font-bold">
                    <?= count($mapPoints); ?> Mapped on GIS
                </span>
            </div>
        </div>

        <!-- Card 2: Top Disease -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-700 rounded-xl flex items-center justify-center text-white shadow-md shadow-rose-200">
                    <i class="fa-solid fa-virus text-lg"></i>
                </div>
                <div>
                    <p class="text-xl font-black text-slate-900 truncate max-w-[140px]"><?= htmlspecialchars((string)$summary['top_disease']); ?></p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Top Disease</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[10px] font-bold">
                    <?= $summary['by_disease'][$summary['top_disease']] ?? 0; ?> Cases Recorded
                </span>
            </div>
        </div>

        <!-- Card 3: Top Hotspot Area -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-700 rounded-xl flex items-center justify-center text-white shadow-md shadow-amber-200">
                    <i class="fa-solid fa-map-location-dot text-lg"></i>
                </div>
                <div>
                    <p class="text-xl font-black text-slate-900">Brgy <?= htmlspecialchars((string)$summary['top_barangay']); ?></p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Top Hotspot Area</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-bold">
                    <?= $summary['by_barangay'][$summary['top_barangay']] ?? 0; ?> Reported Incidents
                </span>
            </div>
        </div>

        <!-- Card 4: Active Clusters -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center text-white shadow-md shadow-red-200">
                    <i class="fa-solid fa-circle-nodes text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black <?= count($clusterOverlays) > 0 ? 'text-red-600' : 'text-slate-900'; ?>"><?= count($clusterOverlays); ?></p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">14-Day Clusters</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 <?= count($clusterOverlays) > 0 ? 'bg-red-100 text-red-800' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?> rounded-full text-[10px] font-bold">
                    <?= count($clusterOverlays) > 0 ? '🚨 Active Outbreak Threshold' : '✅ Baseline Clear'; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Unmapped Cases Warning Drawer -->
    <?php if (!empty($unmapped)): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 text-xs text-amber-800">
        <div class="flex items-center justify-between cursor-pointer" onclick="document.getElementById('unmappedList').classList.toggle('hidden')">
            <div class="flex items-center gap-2 font-bold">
                <i class="fa-solid fa-circle-info text-amber-600"></i>
                <span><?= count($unmapped); ?> Out-of-District / Unmapped Record(s) Triaged</span>
            </div>
            <button type="button" class="text-amber-700 hover:underline font-bold text-[11px]">View Triaged Cases <i class="fa-solid fa-chevron-down text-[10px] ml-1"></i></button>
        </div>
        <div id="unmappedList" class="hidden mt-3 pt-3 border-t border-amber-200 max-h-40 overflow-y-auto space-y-1">
            <?php foreach ($unmapped as $um): ?>
            <div class="flex items-center justify-between py-1 border-b border-amber-100 last:border-0">
                <span><strong><?= htmlspecialchars($um['disease']); ?></strong> — <?= htmlspecialchars($um['patient_name']); ?> (Location: "<?= htmlspecialchars((string)$um['raw_barangay']); ?>")</span>
                <span class="text-amber-600 font-mono text-[10px]"><?= $um['case_date']; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- INTERACTIVE MAP CONTAINER & CONTROLS                         -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-map text-brand-medium"></i>
                    <h3 class="font-bold text-slate-800 text-sm">Caloocan Spatial Epidemiology GIS</h3>
                    <span class="text-xs text-brand-dark font-bold bg-brand-light/70 px-2 py-0.5 rounded-full border border-brand-medium/20">193 Barangays &bull; 16 Zones</span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Dual Enclaves: South (13.63 km² | 7×6 km) &bull; North (39.71 km² | 10×8 km) &bull; Total Area: 53.33 km²</p>
            </div>

            <!-- Region & Territory Switcher -->
            <div class="flex items-center gap-1.5 bg-slate-200/60 p-1 rounded-xl border border-slate-200">
                <button type="button" id="btnRegion_whole" onclick="switchCaloocanRegion('whole')" class="px-2.5 py-1 font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition text-[11px]">
                    <i class="fa-solid fa-earth-asia text-[10px] mr-1"></i> Whole City
                </button>
                <button type="button" id="btnRegion_south" onclick="switchCaloocanRegion('south')" class="px-2.5 py-1 font-bold rounded-lg bg-brand-dark text-white shadow-2xs transition text-[11px]">
                    <i class="fa-solid fa-city text-[10px] mr-1"></i> South (13.63 km²)
                </button>
                <button type="button" id="btnRegion_north" onclick="switchCaloocanRegion('north')" class="px-2.5 py-1 font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition text-[11px]">
                    <i class="fa-solid fa-mountain-sun text-[10px] mr-1"></i> North (39.71 km²)
                </button>
            </div>

            <!-- Layer & Timeline Controls -->
            <div class="flex items-center gap-3 flex-wrap text-xs">
                <!-- Street / Satellite Switch -->
                <div class="flex items-center bg-slate-200/70 p-0.5 rounded-lg border border-slate-200">
                    <button type="button" id="btnStreet" onclick="switchBaseLayer('street')" class="px-2.5 py-1 font-bold rounded-md bg-white text-brand-dark shadow-2xs transition text-[11px]">
                        <i class="fa-solid fa-map"></i> Street
                    </button>
                    <button type="button" id="btnSatellite" onclick="switchBaseLayer('satellite')" class="px-2.5 py-1 font-bold rounded-md text-slate-500 hover:text-slate-800 transition text-[11px]">
                        <i class="fa-solid fa-satellite"></i> Satellite
                    </button>
                </div>

                <label class="flex items-center gap-1.5 text-slate-600 font-semibold cursor-pointer select-none">
                    <input type="checkbox" id="togglePoints" checked onchange="toggleLayer('points')" class="rounded text-brand-dark focus:ring-brand-medium">
                    Case Points
                </label>

                <label class="flex items-center gap-1.5 text-slate-600 font-semibold cursor-pointer select-none">
                    <input type="checkbox" id="toggleClusters" checked onchange="toggleLayer('clusters')" class="rounded text-brand-dark focus:ring-brand-medium">
                    <span class="flex items-center gap-1 text-red-600 font-bold">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                        Outbreak Clusters
                    </span>
                </label>

                <label class="flex items-center gap-1.5 text-slate-600 font-semibold cursor-pointer select-none">
                    <input type="checkbox" id="toggleBuffers" checked onchange="toggleLayer('buffers')" class="rounded text-brand-dark focus:ring-brand-medium">
                    Attack Rate
                </label>

                <label class="flex items-center gap-1.5 text-slate-600 font-semibold cursor-pointer select-none">
                    <input type="checkbox" id="toggleHeatmap" onchange="toggleLayer('heatmap')" class="rounded text-brand-dark focus:ring-brand-medium">
                    Heatmap
                </label>

                <!-- Interactive Time Slider & Animated Playback -->
                <div class="flex items-center gap-2 bg-slate-100 px-3 py-1 rounded-lg border border-slate-200">
                    <i class="fa-regular fa-clock text-slate-400"></i>
                    <input type="range" id="timeSlider" min="0" max="5" value="5" step="1" oninput="handleTimeSlider(this.value)" class="w-20 h-1.5 bg-slate-300 rounded-lg appearance-none cursor-pointer">
                    <span id="timeLabel" class="font-bold text-brand-dark text-[11px] min-w-[55px]">All Months</span>
                    <button type="button" id="btnPlayTimeline" onclick="toggleTimelineAnimation()" class="px-2 py-0.5 bg-brand-dark text-white rounded text-[10px] hover:bg-brand-medium transition">
                        <i class="fa-solid fa-play"></i>
                    </button>
                </div>

                <button onclick="resetMapView()" class="px-3 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-lg transition text-[11px] flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-arrows-rotate text-[10px]"></i> Reset
                </button>
            </div>
        </div>

        <div class="p-4 relative">
            <div id="surveillance-map" style="height: 540px; border-radius: 12px; z-index: 1;"></div>

            <!-- Floating Geospatial Surveillance Map Legend -->
            <div class="absolute bottom-7 left-7 bg-white/95 backdrop-blur-md px-3.5 py-2.5 rounded-xl border border-slate-200/90 shadow-lg text-[11px] z-10 pointer-events-auto">
                <div class="font-black text-slate-800 text-[10px] uppercase tracking-wider border-b border-slate-100 pb-1 mb-2 flex items-center justify-between gap-4">
                    <span>GIS Map Legend</span>
                    <span class="text-[9px] text-slate-400 font-normal">District 1</span>
                </div>
                <div class="space-y-1.5 text-slate-600">
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full border-2 border-red-500 bg-red-500/20 inline-block shrink-0"></span>
                        <span><strong>Red Circle:</strong> Active 14-Day Outbreak Cluster</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full border border-blue-500 bg-blue-500/20 inline-block shrink-0"></span>
                        <span><strong>Blue Circle:</strong> Low-Risk Attack Rate (&lt;10 cases)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3.5 h-3.5 rounded-full border border-amber-500 bg-amber-500/20 inline-block shrink-0"></span>
                        <span><strong>Amber Circle:</strong> Moderate Risk (10–19 cases)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500 border border-white inline-block shrink-0"></span>
                        <span><strong>Dots:</strong> Individual Onset Cases (Color by Disease)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ANALYTICS CHARTS SECTION (APEXCHARTS)                        -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Spread Tracking Timeline Chart -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-brand-medium"></i>
                    6-Month Infection Spread Trend
                </h3>
            </div>
            <div class="p-4">
                <div id="spread-chart" style="min-height: 280px;"></div>
            </div>
        </div>

        <!-- Disease Distribution Donut Chart -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-brand-medium"></i>
                    Disease Distribution Breakdown
                </h3>
            </div>
            <div class="p-4">
                <div id="disease-distribution-chart" style="min-height: 280px;"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- UNIFIED PRIMARY CASES DATA TABLE                             -->
    <!-- ============================================================ -->
    <?php
        $tableDiseases = array_unique(array_filter(array_column($cases, 'disease')));
        sort($tableDiseases);
        $tableSources = array_unique(array_filter(array_column($cases, 'source')));
        sort($tableSources);
        $tableZones = [
            'Zone 1', 'Zone 2', 'Zone 3', 'Zone 4', 'Zone 5', 'Zone 6',
            'Zone 7', 'Zone 8', 'Zone 9', 'Zone 10', 'Zone 11', 'Zone 12',
            'Zone 13', 'Zone 14', 'Zone 15', 'Zone 16'
        ];
    ?>
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-brand-medium"></i>
                    Unified Primary Surveillance Feed
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Aggregated cases from Epidemiological Reports, Consultations & Immunizations across Caloocan City</p>
            </div>
            
            <!-- Quick Filters & Search Bar -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Zone Filter -->
                <select id="tableFilterZone" onchange="handleZoneChange()" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:ring-1 focus:ring-brand-dark outline-none cursor-pointer">
                    <option value="">All 16 Zones</option>
                    <?php foreach ($tableZones as $tz): ?>
                        <option value="<?= htmlspecialchars($tz); ?>"><?= htmlspecialchars($tz); ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Barangay Filter (Cascading based on Zone) -->
                <select id="tableFilterBarangay" onchange="changeTableFilter()" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:ring-1 focus:ring-brand-dark outline-none cursor-pointer">
                    <option value="">All 193 Barangays</option>
                    <?php foreach ($allBarangays as $b): ?>
                        <option value="<?= $b['barangay_no']; ?>" data-zone="<?= htmlspecialchars($b['zone']); ?>"><?= htmlspecialchars($b['name']); ?> (<?= $b['zone']; ?>)</option>
                    <?php endforeach; ?>
                </select>

                <!-- Disease Filter -->
                <select id="tableFilterDisease" onchange="changeTableFilter()" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:ring-1 focus:ring-brand-dark outline-none cursor-pointer">
                    <option value="">All Diseases</option>
                    <?php foreach ($tableDiseases as $td): ?>
                        <option value="<?= htmlspecialchars(strtolower($td)); ?>"><?= htmlspecialchars($td); ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Source Filter -->
                <select id="tableFilterSource" onchange="changeTableFilter()" class="px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:ring-1 focus:ring-brand-dark outline-none cursor-pointer">
                    <option value="">All Channels</option>
                    <?php foreach ($tableSources as $ts): ?>
                        <option value="<?= htmlspecialchars(strtolower($ts)); ?>"><?= htmlspecialchars($ts); ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Search Input -->
                <div class="relative w-48">
                    <input type="text" id="tableSearch" oninput="changeTableFilter()" placeholder="Search patient, code..." class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs focus:ring-1 focus:ring-brand-dark outline-none">
                    <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-[11px]"></i>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-slate-700" id="casesTable">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                    <tr>
                        <th class="py-3 px-4 text-left">Case / Date</th>
                        <th class="py-3 px-4 text-left">Disease</th>
                        <th class="py-3 px-4 text-left">Patient & Address</th>
                        <th class="py-3 px-4 text-left">Zone & Barangay</th>
                        <th class="py-3 px-4 text-left">Source Channel</th>
                        <th class="py-3 px-4 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <?php if (empty($cases)): ?>
                    <tr id="tableEmptyRow">
                        <td colspan="6" class="py-8 text-center text-slate-400 font-bold">No surveillance incidents found in the selected date range.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($cases as $c): ?>
                    <?php
                        $dKey = strtolower($c['disease']);
                        $dotColor = '#64748b';
                        foreach ($diseaseColors as $k => $hex) {
                            if (str_contains($dKey, $k)) {
                                $dotColor = $hex;
                                break;
                            }
                        }
                        $bNum = is_numeric($c['barangay']) ? (int)$c['barangay'] : 0;
                        $cZone = isset($barangayCentroids[$bNum]) ? $barangayCentroids[$bNum]['zone'] : 'Zone 1';
                    ?>
                    <tr class="hover:bg-slate-50/80 transition" 
                        data-zone="<?= htmlspecialchars($cZone); ?>"
                        data-disease="<?= strtolower(htmlspecialchars($c['disease'])); ?>" 
                        data-barangay="<?= htmlspecialchars((string)$c['barangay']); ?>" 
                        data-source="<?= strtolower(htmlspecialchars($c['source'])); ?>"
                        data-month="<?= date('M Y', strtotime($c['case_date'])); ?>">
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-900"><?= htmlspecialchars($c['case_code'] ?? 'CS-N/A'); ?></span>
                            <div class="text-[10px] text-slate-400"><?= $c['case_date']; ?></div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-bold" style="background-color: <?= $dotColor; ?>15; color: <?= $dotColor; ?>;">
                                <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= $dotColor; ?>;"></span>
                                <?= htmlspecialchars($c['disease']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900"><?= htmlspecialchars($c['patient_name']); ?></div>
                            <div class="text-[10px] text-slate-400 truncate max-w-xs"><?= htmlspecialchars($c['address'] ?? 'Caloocan City'); ?></div>
                        </td>
                        <td class="py-3 px-4">
                            <span class="font-bold text-slate-800">Brgy <?= htmlspecialchars((string)$c['barangay']); ?></span>
                            <span class="text-[10px] text-slate-400 block font-semibold"><?= htmlspecialchars($cZone); ?></span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-md text-[10px] font-semibold border border-slate-200">
                                <?= htmlspecialchars($c['source']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 bg-teal-50 text-teal-800 border border-teal-200 rounded-md text-[10px] font-bold">
                                <?= htmlspecialchars($c['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TABLE FOOTER & CLIENT-SIDE PAGINATION -->
        <div class="px-5 py-3.5 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div id="tablePaginationInfo">
                Showing <span class="font-bold text-slate-800" id="pageShowingFrom">0</span> to <span class="font-bold text-slate-800" id="pageShowingTo">0</span> of <span class="font-bold text-slate-800" id="pageTotalCount">0</span> cases
            </div>
            <div id="tablePaginationControls" class="flex items-center gap-1">
                <!-- Injected via JavaScript -->
            </div>
        </div>
    </div>

</div>

<!-- MAPLIBRE GL JS & APEXCHARTS -->
<link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
<script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>
<script src="../../assets/js/apexcharts.min.js"></script>

<style>
    .maplibregl-popup-content {
        padding: 0;
        border-radius: 0.875rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(226, 232, 240, 0.9);
        overflow: hidden;
    }
    .maplibregl-popup-close-button {
        padding: 4px 8px;
        color: #64748b;
        font-size: 14px;
    }
</style>

<script>
    const MAP_POINTS       = <?= json_encode($mapPoints, JSON_PRETTY_PRINT); ?>;
    const CLUSTER_DATA     = <?= json_encode($clusterOverlays, JSON_PRETTY_PRINT); ?>;
    const BARANGAY_BUFFERS = <?= json_encode($barangayBuffers, JSON_PRETTY_PRINT); ?>;
    const TIMELINE_MONTHS  = <?= json_encode($timelineData['months'], JSON_PRETTY_PRINT); ?>;
    const TOTAL_TREND      = <?= json_encode($timelineData['total_trend'], JSON_PRETTY_PRINT); ?>;
    const DISEASE_SUMMARY  = <?= json_encode($summary['by_disease'], JSON_PRETTY_PRINT); ?>;
    const DISEASE_COLORS   = <?= json_encode($diseaseColors, JSON_PRETTY_PRINT); ?>;

    let map = null;
    let spreadChart = null, diseasePieChart = null;
    let animationInterval = null;
    let currentBaseStyle = 'street';

    function colorForDisease(disease) {
        const d = (disease || '').toLowerCase();
        for (const k in DISEASE_COLORS) {
            if (d.includes(k)) return DISEASE_COLORS[k];
        }
        return '#64748b';
    }

    const COMBINED_STYLE = {
        version: 8,
        sources: {
            'osm-tiles': {
                type: 'raster',
                tiles: [
                    'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png'
                ],
                tileSize: 256,
                attribution: '&copy; OpenStreetMap contributors'
            },
            'esri-satellite': {
                type: 'raster',
                tiles: [
                    'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
                ],
                tileSize: 256,
                attribution: 'Tiles &copy; Esri'
            }
        },
        layers: [
            { id: 'satellite-layer', type: 'raster', source: 'esri-satellite', layout: { visibility: 'none' }, minzoom: 0, maxzoom: 19 },
            { id: 'osm-layer', type: 'raster', source: 'osm-tiles', layout: { visibility: 'visible' }, minzoom: 0, maxzoom: 19 }
        ]
    };

    // Caloocan City Geographical Bounds (Encompassing Both Non-Contiguous South & North Enclaves)
    const CALOOCAN_WHOLE_BOUNDS = [
        [120.9400, 14.6250], // Southwest Coordinates (Maypajo / Kaunlaran Waterfront edge)
        [121.1150, 14.8100]  // Northeast Coordinates (Tala North / Pangarap edge)
    ];

    // South Caloocan (Urban Center - 13.63 km² - 7km E-W × 6km N-S)
    const SOUTH_CALOOCAN_POLYGON = {
        type: 'Feature',
        geometry: {
            type: 'Polygon',
            coordinates: [[
                [120.9620, 14.6380], // Maypajo West
                [120.9670, 14.6540], // Dagat-Dagatan / Zone 1
                [120.9710, 14.6610],
                [120.9740, 14.6655], // Zone 8 North
                [120.9850, 14.6670], // EDSA
                [120.9920, 14.6710], // Zone 14 Baesa
                [120.9960, 14.6775], // Zone 15 Santa Quiteria
                [121.0040, 14.6740], // East edge
                [121.0005, 14.6670],
                [120.9960, 14.6620], // Bagong Barrio East
                [120.9890, 14.6500], // Grace Park East
                [120.9820, 14.6360], // Manila border (R. Papa / 2nd Ave)
                [120.9700, 14.6380], // Maypajo South
                [120.9620, 14.6380]  // Loop
            ]]
        },
        properties: { name: 'South Caloocan (Urban Center - 13.63 km²)' }
    };

    // North Caloocan (Residential Subdivisions & Rolling Hills - 39.71 km² - 10km E-W × 8km N-S)
    const NORTH_CALOOCAN_POLYGON = {
        type: 'Feature',
        geometry: {
            type: 'Polygon',
            coordinates: [[
                [121.0050, 14.7180], // Bagbaguin West
                [121.0180, 14.7300], // Kaybiga
                [121.0220, 14.7450], // Llano
                [121.0260, 14.7580], // Bagumbong North
                [121.0380, 14.7750], // Bagong Silang West
                [121.0500, 14.7880], // Bagong Silang North (San Jose Del Monte border)
                [121.0700, 14.7850], // Santo Niño North
                [121.0880, 14.7780], // Pangarap Village North
                [121.0850, 14.7550], // Quirino Highway / Amparo
                [121.0650, 14.7450], // Bankers Village
                [121.0480, 14.7380], // Urduja / Zabarte
                [121.0250, 14.7250], // Deparo South
                [121.0050, 14.7180]  // Loop
            ]]
        },
        properties: { name: 'North Caloocan (Subdivisions - 39.71 km²)' }
    };

    const REGION_PRESETS = {
        whole: {
            center: [121.0150, 14.7100],
            zoom: 11.4,
            pitch: 20,
            bearing: 0,
            label: 'Whole Caloocan (193 Barangays)'
        },
        south: {
            center: [120.9845, 14.6560],
            zoom: 13.5,
            pitch: 28,
            bearing: -6,
            label: 'South Caloocan (Zones 1–15 | 13.63 km²)'
        },
        north: {
            center: [121.0450, 14.7580],
            zoom: 12.8,
            pitch: 24,
            bearing: -4,
            label: 'North Caloocan (Zone 16 | 39.71 km²)'
        }
    };

    function initMap() {
        const centerCoords = [120.9845, 14.6560]; // South Caloocan default centroid

        map = new maplibregl.Map({
            container: 'surveillance-map',
            style: COMBINED_STYLE,
            center: centerCoords,
            zoom: 13.5,
            minZoom: 10.5, // Allows zooming out to view both non-contiguous areas
            maxZoom: 18.5,
            maxBounds: CALOOCAN_WHOLE_BOUNDS, // Restricts camera strictly to Caloocan City borders
            pitch: 28,
            bearing: -6,
            antialias: true
        });

        map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');
        map.addControl(new maplibregl.FullscreenControl(), 'top-right');

        // Accessibility 7.8: Screen Reader ARIA-Live Map Announcer
        let liveRegion = document.getElementById('gisMapLiveRegion');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'gisMapLiveRegion';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only text-xs';
            document.body.appendChild(liveRegion);
        }
        window.announceGisMapState = function(msg) {
            if (liveRegion) liveRegion.textContent = msg;
        };

        map.on('load', () => {
            setupMapSourcesAndLayers();
            announceGisMapState('GIS Surveillance Map loaded with Caloocan South and North boundaries, barangays, and disease clusters.');
        });
    }

    function switchCaloocanRegion(reg) {
        if (!map || !REGION_PRESETS[reg]) return;
        const p = REGION_PRESETS[reg];
        map.flyTo({ center: p.center, zoom: p.zoom, pitch: p.pitch, bearing: p.bearing, speed: 1.2 });

        ['whole', 'south', 'north'].forEach(k => {
            const btn = document.getElementById('btnRegion_' + k);
            if (btn) {
                if (k === reg) {
                    btn.className = 'px-2.5 py-1 font-bold rounded-lg bg-brand-dark text-white shadow-2xs transition text-[11px]';
                } else {
                    btn.className = 'px-2.5 py-1 font-semibold rounded-lg bg-white border border-slate-200 text-slate-600 hover:text-slate-900 transition text-[11px]';
                }
            }
        });
        announceGisMapState('Camera transitioned to ' + p.label);
    }

    function setupMapSourcesAndLayers() {
        // South Caloocan Boundary Source & Enclosure
        map.addSource('caloocan-south-src', {
            type: 'geojson',
            data: SOUTH_CALOOCAN_POLYGON
        });

        map.addLayer({
            id: 'caloocan-south-fill',
            type: 'fill',
            source: 'caloocan-south-src',
            paint: {
                'fill-color': '#0B4F4A',
                'fill-opacity': 0.04
            }
        });

        map.addLayer({
            id: 'caloocan-south-line',
            type: 'line',
            source: 'caloocan-south-src',
            paint: {
                'line-color': '#0B4F4A',
                'line-width': 2.5,
                'line-dasharray': [3, 2],
                'line-opacity': 0.8
            }
        });

        // North Caloocan Boundary Source & Enclosure
        map.addSource('caloocan-north-src', {
            type: 'geojson',
            data: NORTH_CALOOCAN_POLYGON
        });

        map.addLayer({
            id: 'caloocan-north-fill',
            type: 'fill',
            source: 'caloocan-north-src',
            paint: {
                'fill-color': '#0B4F4A',
                'fill-opacity': 0.04
            }
        });

        map.addLayer({
            id: 'caloocan-north-line',
            type: 'line',
            source: 'caloocan-north-src',
            paint: {
                'line-color': '#0B4F4A',
                'line-width': 2.5,
                'line-dasharray': [3, 2],
                'line-opacity': 0.8
            }
        });

        // 1. Case Points GeoJSON
        const caseFeatures = MAP_POINTS.map(p => ({
            type: 'Feature',
            geometry: { type: 'Point', coordinates: [p.lng, p.lat] },
            properties: {
                ...p,
                color: colorForDisease(p.disease)
            }
        }));

        map.addSource('case-points-src', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: caseFeatures }
        });

        // 2. Barangay Buffers GeoJSON
        const bufferFeatures = BARANGAY_BUFFERS.map(b => ({
            type: 'Feature',
            geometry: { type: 'Point', coordinates: [b.lng, b.lat] },
            properties: {
                ...b,
                pixelRadius: Math.min(60, Math.max(16, Math.sqrt(b.total) * 9))
            }
        }));

        map.addSource('barangay-buffers-src', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: bufferFeatures }
        });

        // 3. Cluster Overlays GeoJSON
        const clusterFeatures = CLUSTER_DATA.map(c => ({
            type: 'Feature',
            geometry: { type: 'Point', coordinates: [c.lng, c.lat] },
            properties: { ...c }
        }));

        map.addSource('clusters-src', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: clusterFeatures }
        });

        // --- ADD LAYERS ---

        // Layer: Barangay Risk Buffers (Case Attack Rate)
        map.addLayer({
            id: 'layer-buffers',
            type: 'circle',
            source: 'barangay-buffers-src',
            paint: {
                'circle-radius': [
                    'interpolate', ['exponential', 2], ['zoom'],
                    12, 9,
                    14, 24,
                    16, 58,
                    18, 140
                ],
                'circle-color': ['get', 'color'],
                'circle-opacity': 0.16,
                'circle-stroke-width': 1.5,
                'circle-stroke-color': ['get', 'color'],
                'circle-stroke-opacity': 0.65
            }
        });

        // Layer: GPU Heatmap Layer
        map.addLayer({
            id: 'layer-heatmap',
            type: 'heatmap',
            source: 'case-points-src',
            layout: { visibility: 'none' },
            paint: {
                'heatmap-weight': 1,
                'heatmap-intensity': 1.2,
                'heatmap-color': [
                    'interpolate',
                    ['linear'],
                    ['heatmap-density'],
                    0, 'rgba(34, 197, 94, 0)',
                    0.2, 'rgba(34, 197, 94, 0.5)',
                    0.5, 'rgba(234, 179, 8, 0.7)',
                    0.8, 'rgba(239, 68, 68, 0.85)',
                    1, 'rgba(220, 38, 38, 1)'
                ],
                'heatmap-radius': 30,
                'heatmap-opacity': 0.8
            }
        });

        // Layer: Outbreak Cluster Halo (Red Circle - Centered on Case Cluster)
        map.addLayer({
            id: 'layer-clusters-halo',
            type: 'circle',
            source: 'clusters-src',
            paint: {
                'circle-radius': [
                    'interpolate', ['exponential', 2], ['zoom'],
                    12, 14,
                    14, 38,
                    16, 95,
                    18, 220
                ],
                'circle-color': '#ef4444',
                'circle-opacity': 0.18,
                'circle-stroke-width': 2.5,
                'circle-stroke-color': '#ef4444',
                'circle-stroke-opacity': 0.9
            }
        });

        // Layer: Cluster Core Anchor Dot
        map.addLayer({
            id: 'layer-clusters-pulse',
            type: 'circle',
            source: 'clusters-src',
            paint: {
                'circle-radius': 4.5,
                'circle-color': '#ef4444',
                'circle-stroke-width': 2,
                'circle-stroke-color': '#ffffff'
            }
        });

        // Layer: Individual Case Points
        map.addLayer({
            id: 'layer-points',
            type: 'circle',
            source: 'case-points-src',
            paint: {
                'circle-radius': 6.5,
                'circle-color': ['get', 'color'],
                'circle-opacity': 0.95,
                'circle-stroke-width': 1.8,
                'circle-stroke-color': '#ffffff'
            }
        });

        // Setup Interactive Tooltips
        const popup = new maplibregl.Popup({ closeButton: true, closeOnClick: false, maxWidth: '290px' });

        // Tooltip for Outbreak Clusters
        map.on('mouseenter', 'layer-clusters-halo', (e) => {
            map.getCanvas().style.cursor = 'pointer';
            const c = e.features[0].properties;
            popup.setLngLat(e.features[0].geometry.coordinates).setHTML(`
                <div class="p-3 bg-white space-y-1.5 border-l-4 border-red-500">
                    <div class="font-black text-red-600 text-xs flex items-center justify-between">
                        <span>🚨 ${escapeHtml(c.disease)} Outbreak</span>
                        <span class="text-[10px] bg-red-100 text-red-800 px-1.5 py-0.5 rounded font-bold">${escapeHtml(c.risk_level || 'High')}</span>
                    </div>
                    <div class="text-[11px] text-slate-700 font-semibold">
                        ${escapeHtml(c.barangay)} &bull; Spatial Cluster
                    </div>
                    <div class="border-t border-slate-100 pt-1 text-[10px] space-y-0.5 text-slate-500">
                        <div><strong>Cluster Cases:</strong> <span class="font-bold text-red-600">${c.case_count} cases</span> centered on cluster</div>
                        <div><strong>Containment Window:</strong> ${escapeHtml(c.window_start || '')} to ${escapeHtml(c.window_end || '')}</div>
                    </div>
                </div>
            `).addTo(map);
        });

        map.on('mouseleave', 'layer-clusters-halo', () => {
            map.getCanvas().style.cursor = '';
        });

        // Tooltip for Attack Rate Buffers
        map.on('mouseenter', 'layer-buffers', (e) => {
            map.getCanvas().style.cursor = 'pointer';
            const b = e.features[0].properties;
            popup.setLngLat(e.features[0].geometry.coordinates).setHTML(`
                <div class="p-3 bg-white space-y-1.5 border-l-4" style="border-color: ${b.color};">
                    <div class="font-black text-slate-900 text-xs flex items-center justify-between">
                        <span>${escapeHtml(b.name)}</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded font-bold" style="background-color: ${b.color}20; color: ${b.color};">${escapeHtml(b.risk)}</span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-medium">
                        ${b.landmark ? escapeHtml(b.landmark) : 'District 1 Monitored Area'}
                    </div>
                    <div class="border-t border-slate-100 pt-1 text-[10px] space-y-0.5 text-slate-500">
                        <div><strong>Active Cases:</strong> <span class="font-bold text-slate-800">${b.total}</span></div>
                        <div><strong>Attack Rate:</strong> ${b.attack_rate} per 1,000 population</div>
                        <div><strong>Population:</strong> ${Number(b.population).toLocaleString()}</div>
                    </div>
                </div>
            `).addTo(map);
        });

        map.on('mouseleave', 'layer-buffers', () => {
            map.getCanvas().style.cursor = '';
        });

        // Tooltip for Individual Cases
        map.on('mouseenter', 'layer-points', (e) => {
            map.getCanvas().style.cursor = 'pointer';
            const p = e.features[0].properties;
            popup.setLngLat(e.features[0].geometry.coordinates).setHTML(`
                <div class="p-3 bg-white space-y-1.5">
                    <div class="font-black text-slate-900 text-xs flex items-center justify-between">
                        <span>${escapeHtml(p.disease)}</span>
                        <span class="text-[10px] text-slate-400 font-mono">${escapeHtml(p.case_code || '')}</span>
                    </div>
                    <div class="text-[11px] text-slate-600 font-medium">
                        ${escapeHtml(p.barangay_name)} ${p.landmark ? '&bull; ' + escapeHtml(p.landmark) : ''}
                    </div>
                    <div class="border-t border-slate-100 pt-1.5 text-[10px] space-y-0.5 text-slate-500">
                        <div><strong>Patient:</strong> ${escapeHtml(p.patient_name)}</div>
                        <div><strong>Onset:</strong> ${escapeHtml(p.date)}</div>
                        <div><strong>Source:</strong> <span class="px-1.5 py-0.5 bg-slate-100 font-bold text-slate-700 rounded">${escapeHtml(p.source)}</span></div>
                    </div>
                </div>
            `).addTo(map);
        });

        map.on('mouseleave', 'layer-points', () => {
            map.getCanvas().style.cursor = '';
        });

        map.on('click', 'layer-points', (e) => {
            map.flyTo({ center: e.features[0].geometry.coordinates, zoom: 15.5, pitch: 40, speed: 1.2 });
        });
    }

    function switchBaseLayer(type) {
        currentBaseStyle = type;
        if (!map || !map.isStyleLoaded()) return;

        if (type === 'satellite') {
            map.setLayoutProperty('satellite-layer', 'visibility', 'visible');
            map.setLayoutProperty('osm-layer', 'visibility', 'none');
            document.getElementById('btnSatellite').className = 'px-2.5 py-1 font-bold rounded-md bg-white text-brand-dark shadow-2xs transition text-[11px]';
            document.getElementById('btnStreet').className = 'px-2.5 py-1 font-bold rounded-md text-slate-500 hover:text-slate-800 transition text-[11px]';
        } else {
            map.setLayoutProperty('osm-layer', 'visibility', 'visible');
            map.setLayoutProperty('satellite-layer', 'visibility', 'none');
            document.getElementById('btnStreet').className = 'px-2.5 py-1 font-bold rounded-md bg-white text-brand-dark shadow-2xs transition text-[11px]';
            document.getElementById('btnSatellite').className = 'px-2.5 py-1 font-bold rounded-md text-slate-500 hover:text-slate-800 transition text-[11px]';
        }
    }

    function toggleLayer(layerName) {
        if (!map || !map.isStyleLoaded()) return;

        if (layerName === 'points') {
            const el = document.getElementById('togglePoints');
            const vis = (el && el.checked) ? 'visible' : 'none';
            if (map.getLayer('layer-points')) map.setLayoutProperty('layer-points', 'visibility', vis);
        } else if (layerName === 'buffers') {
            const el = document.getElementById('toggleBuffers');
            const vis = (el && el.checked) ? 'visible' : 'none';
            if (map.getLayer('layer-buffers')) map.setLayoutProperty('layer-buffers', 'visibility', vis);
        } else if (layerName === 'heatmap') {
            const el = document.getElementById('toggleHeatmap');
            const vis = (el && el.checked) ? 'visible' : 'none';
            if (map.getLayer('layer-heatmap')) map.setLayoutProperty('layer-heatmap', 'visibility', vis);
        } else if (layerName === 'clusters') {
            const el = document.getElementById('toggleClusters');
            const vis = (el && el.checked) ? 'visible' : 'none';
            if (map.getLayer('layer-clusters-halo')) map.setLayoutProperty('layer-clusters-halo', 'visibility', vis);
            if (map.getLayer('layer-clusters-pulse')) map.setLayoutProperty('layer-clusters-pulse', 'visibility', vis);
        }
    }

    function handleTimeSlider(val) {
        val = parseInt(val);
        const label = document.getElementById('timeLabel');
        if (val === 5) {
            label.textContent = 'All Months';
            if (map && map.getLayer('layer-points')) {
                map.setFilter('layer-points', null);
            }
        } else {
            const m = TIMELINE_MONTHS[val] || 'Current';
            label.textContent = m;
            if (map && map.getLayer('layer-points')) {
                map.setFilter('layer-points', ['==', ['get', 'month'], m]);
            }
        }
    }

    function toggleTimelineAnimation() {
        const btn = document.getElementById('btnPlayTimeline');
        if (animationInterval) {
            clearInterval(animationInterval);
            animationInterval = null;
            btn.innerHTML = '<i class="fa-solid fa-play"></i>';
            return;
        }

        btn.innerHTML = '<i class="fa-solid fa-pause"></i>';
        let idx = 0;
        document.getElementById('timeSlider').value = idx;
        handleTimeSlider(idx);

        animationInterval = setInterval(() => {
            idx++;
            if (idx > 5) {
                clearInterval(animationInterval);
                animationInterval = null;
                btn.innerHTML = '<i class="fa-solid fa-play"></i>';
                document.getElementById('timeSlider').value = 5;
                handleTimeSlider(5);
                return;
            }
            document.getElementById('timeSlider').value = idx;
            handleTimeSlider(idx);
        }, 1300);
    }

    function initCharts() {
        const spreadEl = document.getElementById('spread-chart');
        if (spreadEl) {
            spreadChart = new ApexCharts(spreadEl, {
                chart: { type: 'area', height: 280, toolbar: { show: false } },
                series: [{ name: 'Surveillance Cases', data: TOTAL_TREND }],
                xaxis: { categories: TIMELINE_MONTHS },
                colors: ['#0B4F4A'],
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [20, 100] } },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 }
            });
            spreadChart.render();
        }

        const pieEl = document.getElementById('disease-distribution-chart');
        if (pieEl && Object.keys(DISEASE_SUMMARY).length > 0) {
            const labels = Object.keys(DISEASE_SUMMARY);
            const series = Object.values(DISEASE_SUMMARY);
            const colors = labels.map(l => colorForDisease(l));

            diseasePieChart = new ApexCharts(pieEl, {
                chart: { type: 'donut', height: 280 },
                series: series,
                labels: labels,
                colors: colors,
                legend: { position: 'bottom' },
                dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' }
            });
            diseasePieChart.render();
        }
    }

    function resetMapView() {
        if (map) {
            map.flyTo({ center: [120.9850, 14.6610], zoom: 13.6, pitch: 28, bearing: -6, speed: 1.2 });
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    const ALL_BARANGAYS_DATA = <?= json_encode($allBarangays, JSON_PRETTY_PRINT); ?>;
    let tableCurrentPage = 1;
    const tableItemsPerPage = 10;

    function handleZoneChange() {
        const selectedZone = document.getElementById('tableFilterZone')?.value || '';
        const brgySelect = document.getElementById('tableFilterBarangay');
        if (brgySelect) {
            brgySelect.innerHTML = '<option value="">All Barangays</option>';
            ALL_BARANGAYS_DATA.forEach(b => {
                if (!selectedZone || b.zone === selectedZone) {
                    const opt = document.createElement('option');
                    opt.value = b.barangay_no;
                    opt.textContent = `Brgy ${b.barangay_no} (${b.zone})`;
                    brgySelect.appendChild(opt);
                }
            });
        }
        tableCurrentPage = 1;
        filterTable();
    }

    function changeTableFilter() {
        tableCurrentPage = 1;
        filterTable();
    }

    const debouncedChangeTableFilter = (typeof debounce === 'function') 
        ? debounce(changeTableFilter, 180) 
        : changeTableFilter;

    function filterTable() {
        const searchInput = (document.getElementById('tableSearch')?.value || '').toLowerCase().trim();
        const zoneFilter = (document.getElementById('tableFilterZone')?.value || '').toLowerCase().trim();
        const diseaseFilter = (document.getElementById('tableFilterDisease')?.value || '').toLowerCase().trim();
        const brgyFilter = (document.getElementById('tableFilterBarangay')?.value || '').trim();
        const sourceFilter = (document.getElementById('tableFilterSource')?.value || '').toLowerCase().trim();

        const allRows = Array.from(document.querySelectorAll('#casesTable tbody tr:not(#tableEmptyRow)'));
        const matchingRows = [];

        allRows.forEach(r => {
            const text = r.textContent.toLowerCase();
            const z = (r.getAttribute('data-zone') || '').toLowerCase();
            const d = (r.getAttribute('data-disease') || '').toLowerCase();
            const b = r.getAttribute('data-barangay') || '';
            const s = (r.getAttribute('data-source') || '').toLowerCase();

            const matchSearch = !searchInput || text.includes(searchInput);
            const matchZone = !zoneFilter || z.includes(zoneFilter);
            const matchDisease = !diseaseFilter || d.includes(diseaseFilter);
            const matchBrgy = !brgyFilter || b === brgyFilter;
            const matchSource = !sourceFilter || s.includes(sourceFilter);

            if (matchSearch && matchZone && matchDisease && matchBrgy && matchSource) {
                matchingRows.push(r);
            } else {
                r.style.display = 'none';
            }
        });

        renderTablePagination(matchingRows);
    }

    function renderTablePagination(matchingRows) {
        const totalMatching = matchingRows.length;
        const totalPages = Math.ceil(totalMatching / tableItemsPerPage) || 1;
        if (tableCurrentPage > totalPages) tableCurrentPage = totalPages;
        if (tableCurrentPage < 1) tableCurrentPage = 1;

        const startIdx = (tableCurrentPage - 1) * tableItemsPerPage;
        const endIdx = startIdx + tableItemsPerPage;

        matchingRows.forEach((row, index) => {
            if (index >= startIdx && index < endIdx) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const emptyRow = document.getElementById('tableEmptyRow');
        if (emptyRow) {
            emptyRow.style.display = totalMatching === 0 ? '' : 'none';
        }

        const showingFromEl = document.getElementById('pageShowingFrom');
        const showingToEl = document.getElementById('pageShowingTo');
        const totalCountEl = document.getElementById('pageTotalCount');

        if (showingFromEl) showingFromEl.textContent = totalMatching === 0 ? 0 : startIdx + 1;
        if (showingToEl) showingToEl.textContent = Math.min(endIdx, totalMatching);
        if (totalCountEl) totalCountEl.textContent = totalMatching;

        const controlsEl = document.getElementById('tablePaginationControls');
        if (controlsEl) {
            let buttonsHtml = '';

            // Previous button
            buttonsHtml += `
                <button onclick="changeTablePage(${tableCurrentPage - 1})" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition ${tableCurrentPage === 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-brand-dark'}" ${tableCurrentPage === 1 ? 'disabled' : ''}>
                    <i class="fa-solid fa-chevron-left text-[10px]"></i>
                </button>
            `;

            // Up to 3 visible page numbers window
            const maxVisible = 3;
            const blockIndex = Math.floor((tableCurrentPage - 1) / maxVisible);
            const startPage = blockIndex * maxVisible + 1;
            const endPage = Math.min(startPage + maxVisible - 1, totalPages);

            for (let p = startPage; p <= endPage; p++) {
                if (p === tableCurrentPage) {
                    buttonsHtml += `<button class="px-3 py-1 rounded-lg text-xs font-bold bg-brand-dark text-white shadow-2xs">${p}</button>`;
                } else {
                    buttonsHtml += `<button onclick="changeTablePage(${p})" class="px-3 py-1 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-brand-dark transition">${p}</button>`;
                }
            }

            // Next button
            buttonsHtml += `
                <button onclick="changeTablePage(${tableCurrentPage + 1})" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition ${tableCurrentPage === totalPages || totalMatching === 0 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-brand-dark'}" ${tableCurrentPage === totalPages || totalMatching === 0 ? 'disabled' : ''}>
                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                </button>
            `;

            controlsEl.innerHTML = buttonsHtml;
        }
    }

    function changeTablePage(page) {
        tableCurrentPage = page;
        filterTable();
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            initMap();
            initCharts();
            filterTable();
        }, 150);
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>