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

require_once __DIR__ . '/../../app/Models/SurveillanceCase.php';

// STRICTLY CALOOCAN CITY BARANGAYS
$barangayCases = [
    ['name' => 'San Jose', 'lat' => 14.5794, 'lng' => 121.0359, 'dengue' => 12, 'influenza' => 8, 'leptospirosis' => 0, 'total' => 22, 'risk' => 'High', 'population' => 8500],
    ['name' => 'Poblacion', 'lat' => 14.5810, 'lng' => 121.0400, 'dengue' => 5, 'influenza' => 15, 'leptospirosis' => 0, 'total' => 20, 'risk' => 'High', 'population' => 12000],
    ['name' => 'Riverside', 'lat' => 14.5750, 'lng' => 121.0420, 'dengue' => 3, 'influenza' => 5, 'leptospirosis' => 5, 'total' => 13, 'risk' => 'Moderate', 'population' => 6200],
    ['name' => 'San Antonio', 'lat' => 14.5830, 'lng' => 121.0380, 'dengue' => 8, 'influenza' => 3, 'leptospirosis' => 0, 'total' => 11, 'risk' => 'Moderate', 'population' => 5400],
    ['name' => 'Bagong Silang', 'lat' => 14.5770, 'lng' => 121.0450, 'dengue' => 2, 'influenza' => 4, 'leptospirosis' => 0, 'total' => 6, 'risk' => 'Low', 'population' => 3800],
    ['name' => 'Mabini', 'lat' => 14.5850, 'lng' => 121.0360, 'dengue' => 1, 'influenza' => 2, 'leptospirosis' => 0, 'total' => 3, 'risk' => 'Low', 'population' => 2900],
    ['name' => 'Kaybiga', 'lat' => 14.5765, 'lng' => 121.0435, 'dengue' => 4, 'influenza' => 6, 'leptospirosis' => 1, 'total' => 11, 'risk' => 'Moderate', 'population' => 4900],
    ['name' => 'Bagumbong', 'lat' => 14.5840, 'lng' => 121.0410, 'dengue' => 2, 'influenza' => 3, 'leptospirosis' => 0, 'total' => 5, 'risk' => 'Low', 'population' => 3500],
    ['name' => 'Camarin', 'lat' => 14.5785, 'lng' => 121.0470, 'dengue' => 7, 'influenza' => 4, 'leptospirosis' => 0, 'total' => 11, 'risk' => 'Moderate', 'population' => 7200],
];

try {
    $caseModel = new SurveillanceCase();
    $dbCases = $caseModel->all();
    if (!empty($dbCases)) {
        $countsByBarangay = [];
        foreach ($dbCases as $c) {
            $bName = trim(str_ireplace('Barangay', '', $c['barangay'] ?? ''));
            if (!isset($countsByBarangay[$bName])) $countsByBarangay[$bName] = 0;
            $countsByBarangay[$bName]++;
        }
        foreach ($barangayCases as &$b) {
            if (isset($countsByBarangay[$b['name']])) {
                $b['total'] = $countsByBarangay[$b['name']];
            }
        }
        unset($b);
    }
} catch (Throwable $e) {
    error_log("Mapping cases query error: " . $e->getMessage());
}

// Calculate case rates
foreach ($barangayCases as &$b) {
    $b['case_rate'] = $b['population'] > 0 ? round(($b['total'] / $b['population']) * 1000, 1) : 0;
}
unset($b);

// Summary stats
$totalCases = array_sum(array_column($barangayCases, 'total'));
$highRisk = count(array_filter($barangayCases, function($b) { return $b['risk'] == 'High'; }));
$totalBarangays = count($barangayCases);
$avgRate = $totalBarangays > 0 ? round(array_sum(array_column($barangayCases, 'case_rate')) / $totalBarangays, 1) : 0;

$title = 'Mapping & Clustering';
?>


<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Mapping & Clustering</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-location-dot"></i> Caloocan City
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">Disease mapping and cluster analysis for Caloocan City</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="toggleHeatmap()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-fire text-xs"></i> Heatmap
            </button>
            <button onclick="toggleClusters()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-circle-nodes text-xs"></i> Clusters
            </button>
            <button onclick="refreshMap()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-rotate text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS                                             -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Cases -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-notes-medical text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalCases; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Cases</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">↑ 12%</span>
                    <span class="text-[10px] text-slate-400">vs last month</span>
                </div>
            </div>
        </div>

        <!-- Card 2: High Risk Areas -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-red-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-red-600"><?php echo $highRisk; ?></p>
                        <p class="text-xs font-medium text-slate-500">High Risk Areas</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">⚠️ Alert</span>
                    <span class="text-[10px] text-slate-400">Needs attention</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Avg Case Rate -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-chart-line text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $avgRate; ?><span class="text-sm text-slate-400 font-medium">/1k</span></p>
                        <p class="text-xs font-medium text-slate-500">Avg Case Rate</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 <?php echo $avgRate > 2 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $avgRate > 2 ? '⬆ Above' : '⬇ Below'; ?> baseline
                    </span>
                    <span class="text-[10px] text-slate-400">Baseline: 2.0</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Barangays -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-location-dot text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark"><?php echo $totalBarangays; ?></p>
                        <p class="text-xs font-medium text-slate-500">Barangays</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-brand-light text-brand-dark rounded-full text-[10px] font-bold">📍 Caloocan</span>
                    <span class="text-[10px] text-slate-400">All monitored</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MAP SECTION - Full Width                                    -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-map text-brand-medium"></i>
                Caloocan City Disease Map
                <span class="text-xs font-normal text-slate-400">(<?php echo $totalBarangays; ?> barangays)</span>
            </h3>
            <div class="flex items-center gap-4 text-xs flex-wrap">
                <label class="flex items-center gap-1.5 text-slate-600 cursor-pointer">
                    <input type="checkbox" id="showHeatmap" onchange="toggleHeatmap()" class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                    Heatmap
                </label>
                <label class="flex items-center gap-1.5 text-slate-600 cursor-pointer">
                    <input type="checkbox" id="showClusters" onchange="toggleClusters()" class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                    Clusters
                </label>
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-calendar text-slate-400"></i>
                    <input type="range" id="timeSlider" min="0" max="5" value="5" step="1" class="w-20 h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer">
                    <span id="timeLabel" class="text-xs font-semibold text-brand-dark min-w-[50px]">Current</span>
                    <button onclick="playAnimation()" class="px-2.5 py-1 bg-brand-dark text-white rounded text-xs hover:bg-brand-medium transition">
                        <i class="fa-solid fa-play"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="p-4 relative">
            <!-- Map Container -->
            <div id="cluster-map" style="height: 500px; border-radius: 12px; position: relative;"></div>
            
            <!-- Tooltip that appears INSIDE the map -->
            <div id="map-tooltip" style="display: none; position: absolute; z-index: 1000; pointer-events: none; background: rgba(255,255,255,0.97); border: 1px solid #E2E8F0; border-radius: 12px; padding: 12px 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); min-width: 180px; backdrop-filter: blur(4px); font-family: system-ui, sans-serif;">
                <div id="tooltip-name" style="font-weight: 700; font-size: 14px; color: #1E293B;"></div>
                <div><span id="tooltip-cases" style="font-size: 20px; font-weight: 800; color: #0B4F4A;"></span> <span style="font-size: 11px; color: #64748B;">cases</span></div>
                <div style="border-top: 1px solid #E2E8F0; margin: 5px 0;"></div>
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: #475569; padding: 1px 0;">
                    <span>🦟 Dengue</span>
                    <span><strong id="tooltip-dengue"></strong></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 11px; color: #475569; padding: 1px 0;">
                    <span>🤧 Influenza</span>
                    <span><strong id="tooltip-influenza"></strong></span>
                </div>
                <div id="tooltip-lepto-row" style="display: none; justify-content: space-between; font-size: 11px; color: #475569; padding: 1px 0;">
                    <span>🧫 Lepto</span>
                    <span><strong id="tooltip-lepto"></strong></span>
                </div>
                <div style="border-top: 1px solid #E2E8F0; margin: 5px 0;"></div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2px;">
                    <span style="font-size: 11px; color: #64748B;">Risk</span>
                    <span id="tooltip-risk" style="padding: 1px 8px; border-radius: 10px; font-size: 10px; font-weight: 600;"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- CHARTS SECTION                                              -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-chart-line text-brand-medium"></i>
                    Spread Tracking Over Time
                </h3>
            </div>
            <div class="p-4">
                <div id="spread-chart" style="min-height: 280px;"></div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-brand-medium"></i>
                    Disease Distribution
                </h3>
            </div>
            <div class="p-4">
                <div id="disease-distribution" style="min-height: 280px;"></div>
            </div>
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
<link rel="stylesheet" href="../../assets/css/leaflet.css" />
<script src="../../assets/js/leaflet.js"></script>
<script src="../../assets/js/apexcharts.min.js"></script>
<script src="../../assets/js/leaflet-heat.js"></script>

<style>
    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #14807A;
        cursor: pointer;
    }
    input[type="range"]::-moz-range-thumb {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #14807A;
        cursor: pointer;
        border: none;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
    }
    .cluster-pulse { animation: pulse 2s infinite; }
    
    .leaflet-popup { display: none !important; }
</style>

<script>
    const BARANGAY_CASES = <?php echo json_encode($barangayCases, JSON_PRETTY_PRINT); ?>;
    const HEATMAP_DATA = <?php echo json_encode(array_map(function($b) { return [$b['lat'], $b['lng'], $b['total']]; }, $barangayCases), JSON_PRETTY_PRINT); ?>;

    let map = null, chart = null, diseaseChart = null;
    let heatmapLayer = null, clusterLayers = [];
    let isHeatmapVisible = false, isClustersVisible = false;
    let animationInterval = null;
    let circleRefs = [];

    // ============================================================
    // TOOLTIP - INSIDE MAP
    // ============================================================
    function showTooltip(e, b) {
        const tooltip = document.getElementById('map-tooltip');
        const riskColors = {
            'High': { bg: '#FEE2E2', color: '#DC2626' },
            'Moderate': { bg: '#FEF3C7', color: '#D97706' },
            'Low': { bg: '#D1FAE5', color: '#059669' }
        };
        
        // Get map container position
        const mapContainer = document.getElementById('cluster-map');
        const mapRect = mapContainer.getBoundingClientRect();
        
        // Calculate position relative to map container
        let x = e.clientX - mapRect.left + 15;
        let y = e.clientY - mapRect.top - 10;
        
        // Fill tooltip data
        document.getElementById('tooltip-name').textContent = '📍 ' + b.name;
        document.getElementById('tooltip-cases').textContent = b.total;
        document.getElementById('tooltip-dengue').textContent = b.dengue;
        document.getElementById('tooltip-influenza').textContent = b.influenza;
        
        if (b.leptospirosis && b.leptospirosis > 0) {
            document.getElementById('tooltip-lepto-row').style.display = 'flex';
            document.getElementById('tooltip-lepto').textContent = b.leptospirosis;
        } else {
            document.getElementById('tooltip-lepto-row').style.display = 'none';
        }
        
        const risk = riskColors[b.risk] || { bg: '#E2E8F0', color: '#64748B' };
        document.getElementById('tooltip-risk').textContent = b.risk;
        document.getElementById('tooltip-risk').style.background = risk.bg;
        document.getElementById('tooltip-risk').style.color = risk.color;
        
        // Position tooltip inside map
        const tooltipWidth = 200;
        const tooltipHeight = 180;
        
        // Keep tooltip inside map bounds
        if (x + tooltipWidth > mapRect.width) {
            x = e.clientX - mapRect.left - tooltipWidth - 15;
        }
        if (y + tooltipHeight > mapRect.height) {
            y = mapRect.height - tooltipHeight - 10;
        }
        if (y < 10) y = 10;
        if (x < 10) x = 10;
        
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
        tooltip.style.display = 'block';
    }

    function hideTooltip() {
        document.getElementById('map-tooltip').style.display = 'none';
    }

    // ============================================================
    // MAP - STRICTLY CALOOCAN
    // ============================================================
    function initMap() {
        const el = document.getElementById('cluster-map');
        if (!el) return;

        const bounds = L.latLngBounds([14.5740, 121.0340], [14.5870, 121.0470]);
        const center = bounds.getCenter();

        map = L.map('cluster-map', {
            center: center, zoom: 14, zoomControl: true,
            maxBounds: bounds, minZoom: 13, maxZoom: 17,
            fadeAnimation: true, zoomAnimation: true
        });
        map.fitBounds(bounds);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap | Caloocan City Health Office'
        }).addTo(map);

        L.rectangle(bounds, {
            color: '#14807A', weight: 2, opacity: 0.3,
            fillColor: '#E6F5F3', fillOpacity: 0.05, interactive: false
        }).addTo(map);

        const riskColors = { High: '#ef4444', Moderate: '#eab308', Low: '#22c55e' };
        circleRefs = [];

        BARANGAY_CASES.forEach((b, i) => {
            const color = riskColors[b.risk] || '#6B7280';
            const radius = Math.max(b.total * 25, 15);

            const circle = L.circle([b.lat, b.lng], {
                color, fillColor: color, fillOpacity: 0.5,
                radius, weight: 3, opacity: 0.8, interactive: true
            }).addTo(map);

            circleRefs.push({ circle, data: b, radius, color });

            circle.on('mouseover', function(e) {
                this.setStyle({ fillOpacity: 0.8, weight: 4, radius: radius * 1.15 });
                showTooltip(e.originalEvent, b);
                el.style.cursor = 'pointer';
            });
            
            circle.on('mousemove', function(e) {
                showTooltip(e.originalEvent, b);
            });
            
            circle.on('mouseout', function() {
                this.setStyle({ fillOpacity: 0.5, weight: 3, radius });
                hideTooltip();
                el.style.cursor = 'default';
            });

            L.marker([b.lat + 0.0018, b.lng], {
                icon: L.divIcon({
                    html: `<div style="font-size:9px;font-weight:600;color:#1E293B;background:rgba(255,255,255,0.85);padding:2px 6px;border-radius:4px;border:1px solid #E2E8F0;box-shadow:0 1px 3px rgba(0,0,0,0.05);">${b.name}</div>`,
                    iconSize: [0, 0], iconAnchor: [0, 0]
                })
            }).addTo(map);
        });

        // Legend
        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function() {
            const div = L.DomUtil.create('div', 'bg-white p-3 rounded-lg shadow-md border border-slate-200');
            div.innerHTML = `
                <div style="font-size:11px;font-weight:700;color:#1E293B;margin-bottom:4px;">📍 Caloocan City</div>
                <div style="font-size:11px;font-weight:600;color:#1E293B;margin-bottom:3px;">Risk</div>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:1px;"><span style="width:12px;height:12px;border-radius:50%;background:#ef4444;"></span><span style="font-size:11px;color:#475569;">High</span></div>
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:1px;"><span style="width:12px;height:12px;border-radius:50%;background:#eab308;"></span><span style="font-size:11px;color:#475569;">Moderate</span></div>
                <div style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:#22c55e;"></span><span style="font-size:11px;color:#475569;">Low</span></div>
                <div style="border-top:1px solid #E2E8F0;margin-top:5px;padding-top:4px;"><span style="font-size:10px;color:#94A3B8;">${BARANGAY_CASES.length} barangays</span></div>
            `;
            return div;
        };
        legend.addTo(map);

        initHeatmap();
        initClusters();
        setupTimeSlider();
    }

    // ============================================================
    // HEATMAP
    // ============================================================
    function initHeatmap() {
        heatmapLayer = L.heatLayer(HEATMAP_DATA, {
            radius: 30, blur: 20, maxZoom: 17,
            gradient: { 0.2: '#22c55e', 0.4: '#84cc16', 0.6: '#eab308', 0.8: '#f97316', 1.0: '#ef4444' },
            minOpacity: 0.3
        });
    }
    function toggleHeatmap() {
        isHeatmapVisible = !isHeatmapVisible;
        document.getElementById('showHeatmap').checked = isHeatmapVisible;
        isHeatmapVisible ? (heatmapLayer.addTo(map), showToast('🔥 Heatmap on', 'info')) : (map.removeLayer(heatmapLayer), showToast('Heatmap off', 'info'));
    }

    // ============================================================
    // CLUSTERS
    // ============================================================
    function initClusters() {
        clusterLayers.forEach(l => map.hasLayer(l) && map.removeLayer(l));
        clusterLayers = [];
        const used = new Set(), groups = [];
        BARANGAY_CASES.forEach((b, i) => {
            if (used.has(i)) return;
            const g = [i]; used.add(i);
            BARANGAY_CASES.forEach((c, j) => {
                if (i === j || used.has(j)) return;
                if (Math.sqrt(Math.pow(b.lat - c.lat, 2) + Math.pow(b.lng - c.lng, 2)) < 0.008) {
                    g.push(j); used.add(j);
                }
            });
            if (g.length > 1) groups.push(g);
        });

        const colors = { High: '#ef4444', Moderate: '#eab308', Low: '#22c55e' };
        groups.forEach((g, idx) => {
            const total = g.reduce((s, i) => s + BARANGAY_CASES[i].total, 0);
            const lat = g.reduce((s, i) => s + BARANGAY_CASES[i].lat, 0) / g.length;
            const lng = g.reduce((s, i) => s + BARANGAY_CASES[i].lng, 0) / g.length;
            const risk = total > 30 ? 'High' : total > 15 ? 'Moderate' : 'Low';
            const color = colors[risk];
            const c = L.circle([lat, lng], { color, fillColor: color, fillOpacity: 0.15, radius: 200 + total * 8, weight: 3, opacity: 0.8, dashArray: '8,6', className: 'cluster-pulse' });
            c.bindTooltip(`<strong>Cluster ${idx+1}</strong><br>📊 ${total} cases<br>📍 ${g.length} barangays<br>⚠️ ${risk} risk`, { className: 'bg-white p-2 rounded shadow-lg text-xs' });
            c.addTo(map); clusterLayers.push(c);
            g.forEach(i => {
                const b = BARANGAY_CASES[i];
                const m = L.circleMarker([b.lat, b.lng], { color, fillColor: color, fillOpacity: 0.3, radius: 4, weight: 1.5 });
                m.addTo(map); clusterLayers.push(m);
            });
        });
        clusterLayers.forEach(l => map.hasLayer(l) && map.removeLayer(l));
        isClustersVisible = false;
    }
    function toggleClusters() {
        isClustersVisible = !isClustersVisible;
        document.getElementById('showClusters').checked = isClustersVisible;
        clusterLayers.forEach(l => isClustersVisible ? l.addTo(map) : map.removeLayer(l));
        showToast(isClustersVisible ? '🔵 Clusters on' : 'Clusters off', 'info');
    }

    // ============================================================
    // TIME SLIDER
    // ============================================================
    function setupTimeSlider() {
        const s = document.getElementById('timeSlider'), l = document.getElementById('timeLabel');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        s.addEventListener('input', function() { l.textContent = months[parseInt(this.value)]; });
    }
    function playAnimation() {
        if (animationInterval) {
            clearInterval(animationInterval); animationInterval = null;
            document.querySelector('#timeSlider + button i').className = 'fa-solid fa-play';
            return;
        }
        document.querySelector('#timeSlider + button i').className = 'fa-solid fa-pause';
        let m = parseInt(document.getElementById('timeSlider').value);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        animationInterval = setInterval(() => {
            m = (m + 1) % 6;
            document.getElementById('timeSlider').value = m;
            document.getElementById('timeLabel').textContent = months[m];
            if (m === 5) { 
                clearInterval(animationInterval); 
                animationInterval = null;
                document.querySelector('#timeSlider + button i').className = 'fa-solid fa-play';
                showToast('🔄 Complete', 'success'); 
            }
        }, 1500);
    }

    // ============================================================
    // CHARTS
    // ============================================================
    function initChart() {
        const el = document.getElementById('spread-chart');
        if (!el) return;
        const months = ['Jan','Feb','Mar','Apr','May','Jun'];
        chart = new ApexCharts(el, {
            series: BARANGAY_CASES.map(b => ({
                name: b.name,
                data: months.map((_,i) => Math.max(0, Math.floor(b.total/6) + (i*0.5) + Math.floor(Math.random() * Math.floor(b.total/6) * 0.3)))
            })),
            chart: { type: 'line', height: 280, toolbar: { show: false }, zoom: { enabled: true }, animations: { enabled: true, easing: 'easeinout', speed: 600 } },
            stroke: { curve: 'smooth', width: 2.5 },
            markers: { size: 3, hover: { size: 5 } },
            xaxis: { categories: months, title: { text: 'Month' } },
            yaxis: { title: { text: 'Cases' }, min: 0 },
            colors: BARANGAY_CASES.map(b => ({ High: '#ef4444', Moderate: '#eab308', Low: '#22c55e' }[b.risk] || '#6B7280')),
            legend: { position: 'top', fontSize: '10px', horizontalAlign: 'left' },
            tooltip: { y: { formatter: v => Math.round(v) + ' cases' } },
            grid: { borderColor: '#E2E8F0', strokeDashArray: 4 }
        });
        chart.render();
    }

    function initDiseaseChart() {
        const el = document.getElementById('disease-distribution');
        if (!el) return;
        let d=0, i=0, l=0;
        BARANGAY_CASES.forEach(b => { d += b.dengue; i += b.influenza; l += b.leptospirosis || 0; });
        diseaseChart = new ApexCharts(el, {
            series: [d, i, l],
            chart: { type: 'donut', height: 280, animations: { enabled: true, easing: 'easeinout', speed: 600 } },
            labels: ['Dengue', 'Influenza', 'Leptospirosis'],
            colors: ['#ef4444', '#3b82f6', '#8b5cf6'],
            legend: { position: 'bottom', fontSize: '11px' },
            tooltip: { y: { formatter: v => v + ' cases' } },
            plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => BARANGAY_CASES.reduce((s,b) => s + b.total, 0) } } } } }
        });
        diseaseChart.render();
    }

    // ============================================================
    // HELPERS
    // ============================================================
    function refreshMap() {
        if (map) map.setView([14.5800, 121.0400], 14);
        if (chart) chart.updateOptions({ animations: { enabled: true } });
        if (diseaseChart) diseaseChart.updateOptions({ animations: { enabled: true } });
        showToast('🗺️ Refreshed!', 'success');
    }

    let toastTimer = null;
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        const colors = { success: 'bg-brand-dark', danger: 'bg-rose-600', info: 'bg-blue-600', warning: 'bg-amber-600' };
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
            initMap(); 
            initChart(); 
            initDiseaseChart(); 
        }, 500);
    });
</script>

<?php include_once '../../includes/footer.php'; ?>