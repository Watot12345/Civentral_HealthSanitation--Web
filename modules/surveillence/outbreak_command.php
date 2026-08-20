<?php
// modules/surveillence/outbreak_command.php
// Pure Epidemiological Surveillance & Outbreak Early Warning Dashboard
// Scope: Statistical Anomaly Analysis, 2-SD Moving Baseline Alerts & 12-Week Epidemic Curves.
// Spatial GIS Mapping is strictly centralized in mapping.php.

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../app/services/AlertService.php';

use App\Services\AlertService;

requireDepartmentAccess('health surveillance');

$alertService = AlertService::getInstance();

// Synchronize thresholds on page load
try {
    $alertService->syncThresholdBreaches();
} catch (\Throwable $e) {}

$activeAlerts = $alertService->getActiveAlerts();
$trendData    = $alertService->get12WeekTrendData();

// Calculate summary KPI stats
$totalAlertsCount = count($activeAlerts);
$criticalAlertsCount = 0;
$watchCount = 0;
$totalSpikeCases = 0;

foreach ($activeAlerts as $a) {
    if ($a['plain_status'] === '🔴 Outbreak Alert' || $a['severity'] === 'Critical') {
        $criticalAlertsCount++;
    } elseif ($a['plain_status'] === '🟡 Watch') {
        $watchCount++;
    }
    $totalSpikeCases += (int)$a['cases'];
}

// Load case counts
$totalCases = 0;
try {
    $cases = Database::getInstance()->select('surveillance_cases', [], ['limit' => 2000]);
    $totalCases = count($cases);
} catch (\Throwable $e) {}

$alertsJson = json_encode($activeAlerts);
$trendsJson = json_encode($trendData);
?>

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-y-auto space-y-6">
    
    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark shadow-sm">
                    <i class="fa-solid fa-shield-virus text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Outbreak Surveillance & Early Warning</h1>
                    <p class="text-xs text-slate-500 font-medium">Caloocan District 1 Epidemiological Anomaly Engine (2-SD Baseline Monitoring)</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Live 15s Heartbeat Active</span>
            </div>
            <a href="<?= site_url('modules/surveillence/mapping.php') ?>" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-xl text-xs font-semibold transition shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-map-location-dot text-brand-dark"></i> Geospatial Map
            </a>
            <a href="<?= site_url('modules/surveillence/case_reports.php') ?>" class="px-3.5 py-2 bg-brand-dark text-white rounded-xl text-xs font-semibold hover:bg-brand-medium transition shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-file-medical"></i> Case Intake
            </a>
        </div>
    </div>

    <!-- KPI STATS CARDS (MATCHING MAPPING.PHP PREMIUM DESIGN) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Outbreak Alerts -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center text-white shadow-md shadow-red-200">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900" id="kpi_critical_alerts"><?= $criticalAlertsCount ?></p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Outbreak Alerts</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 <?= $criticalAlertsCount > 0 ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'; ?> rounded-full text-[10px] font-bold">
                    <?= $criticalAlertsCount > 0 ? '🚨 Exceeds 2-SD Baseline' : '✅ Baseline Clear'; ?>
                </span>
            </div>
        </div>

        <!-- 2. Active Watch Signals -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-700 rounded-xl flex items-center justify-center text-white shadow-md shadow-amber-200">
                    <i class="fa-solid fa-binoculars text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900" id="kpi_watch_alerts"><?= $watchCount ?></p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Watch Signals</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 <?= $watchCount > 0 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-50 text-slate-600 border border-slate-200'; ?> rounded-full text-[10px] font-bold">
                    <?= $watchCount > 0 ? '🟡 Elevated Cluster Activity' : '✅ No Active Watch'; ?>
                </span>
            </div>
        </div>

        <!-- 3. Monitored Cases -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-teal-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-teal-500 to-teal-700 rounded-xl flex items-center justify-center text-white shadow-md shadow-teal-200">
                    <i class="fa-solid fa-notes-medical text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900" id="kpi_total_cases"><?= number_format($totalCases) ?></p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Monitored Cases</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-teal-50 text-teal-700 border border-teal-200 rounded-full text-[10px] font-bold">
                    District 1 Central Registry
                </span>
            </div>
        </div>

        <!-- 4. Surveillance Zones -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-xs border border-slate-200 p-5 hover:shadow-md transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-indigo-100 rounded-full opacity-40 group-hover:scale-110 transition"></div>
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-xl flex items-center justify-center text-white shadow-md shadow-indigo-200">
                    <i class="fa-solid fa-map-location-dot text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900">7 Zones</p>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Surveillance Zones</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full text-[10px] font-bold">
                    46 Monitored Barangays
                </span>
            </div>
        </div>
    </div>

    <!-- MAIN DASHBOARD TABS -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <!-- TAB BUTTONS -->
        <div class="flex border-b border-slate-200 bg-slate-50/50 px-6 pt-3 gap-2">
            <button onclick="switchCommandTab('alerts')" id="tab_btn_alerts" class="px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 border-b-2 border-brand-dark text-brand-dark bg-white shadow-sm">
                <i class="fa-solid fa-bell"></i>
                <span>Active Alerts Feed</span>
                <span class="px-1.5 py-0.5 text-[10px] rounded-full bg-red-100 text-red-700 font-black" id="tab_alert_badge"><?= $totalAlertsCount ?></span>
            </button>
            <button onclick="switchCommandTab('curves')" id="tab_btn_curves" class="px-4 py-2.5 text-xs font-semibold rounded-t-xl transition flex items-center gap-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100/60">
                <i class="fa-solid fa-chart-line"></i>
                <span>12-Week Disease Trends</span>
            </button>
        </div>

        <!-- TAB 1: ACTIVE ALERTS FEED (DEFAULT) -->
        <div id="tab_panel_alerts" class="p-6 space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Live Surveillance Threshold Signals</h3>
                    <p class="text-xs text-slate-400">Plain-language status feeds evaluated against dynamic 2-SD moving baselines.</p>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" id="alertSearchInput" oninput="filterAlertsTable()" placeholder="Search disease or zone..." class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs outline-none focus:ring-2 focus:ring-brand-medium/40 w-64 bg-slate-50/50">
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs" id="alertsTable">
                    <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Alert Code</th>
                            <th class="px-4 py-3">Tracked Disease</th>
                            <th class="px-4 py-3">Geographic Zone</th>
                            <th class="px-4 py-3">Public Health Status</th>
                            <th class="px-4 py-3">Cases vs Threshold</th>
                            <th class="px-4 py-3">Detected At</th>
                            <th class="px-4 py-3 text-right">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700" id="alertsTableBody">
                        <?php if (empty($activeAlerts)): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                    <i class="fa-solid fa-shield-halved text-3xl mb-2 opacity-30 text-emerald-500 block"></i>
                                    <span>All District 1 zones are within normal baseline thresholds (0 Active Outbreak Spikes).</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeAlerts as $alert): ?>
                                <tr class="hover:bg-slate-50/80 transition" data-disease="<?= strtolower($alert['disease']) ?>" data-zone="<?= strtolower($alert['zone']) ?>">
                                    <td class="px-4 py-3.5 font-mono text-[11px] font-bold text-slate-500">
                                        <?= htmlspecialchars($alert['alert_code']) ?>
                                    </td>
                                    <td class="px-4 py-3.5 font-bold text-slate-900 flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full <?= $alert['dot_color'] ?>"></span>
                                        <?= htmlspecialchars($alert['disease']) ?>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($alert['zone']) ?></span>
                                        <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($alert['barangay']) ?></span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border <?= $alert['badge_class'] ?>">
                                            <?= htmlspecialchars($alert['plain_status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-black text-slate-900 text-sm"><?= $alert['cases'] ?></span>
                                            <span class="text-slate-400">/ <?= $alert['threshold'] ?> threshold</span>
                                            <?php if ($alert['variance'] > 0): ?>
                                                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">+<?= $alert['variance'] ?> spike</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-500 text-[11px]">
                                        <?= date('M d, Y h:i A', strtotime($alert['created_at'])) ?>
                                    </td>
                                    <td class="px-4 py-3.5 text-right">
                                        <button onclick="viewAlertDetails(<?= htmlspecialchars(json_encode($alert), ENT_QUOTES) ?>)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5">
                                            <i class="fa-solid fa-eye text-[11px]"></i> Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: 12-WEEK DISEASE TRENDS -->
        <div id="tab_panel_curves" class="hidden p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Longitudinal Disease Trends (12-Week Moving Window)</h3>
                    <p class="text-xs text-slate-400">Weekly incident patterns for reportable conditions across Caloocan District 1 Zones.</p>
                </div>
            </div>
            <div class="w-full h-[420px] p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                <canvas id="epiCurvesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ALERT DETAILS MODAL (READ-ONLY) -->
<div id="alertDetailsModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in-95 duration-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50/50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-light text-brand-dark flex items-center justify-center font-bold">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <h3 class="font-bold text-slate-900" id="detailModalTitle">Epidemiological Alert Details</h3>
            </div>
            <button onclick="ModalSystem.close('alertDetailsModal')" class="w-8 h-8 rounded-lg hover:bg-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4 text-xs" id="detailModalContent">
            <!-- Dynamic content populated by JS -->
        </div>
        <div class="flex justify-end px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            <button type="button" onclick="ModalSystem.close('alertDetailsModal')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold">
                Close
            </button>
        </div>
    </div>
</div>

<!-- CHART.JS SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    let ACTIVE_ALERTS = <?= $alertsJson ?>;
    let TREND_DATA    = <?= $trendsJson ?>;
    let chartInstance = null;

    function switchCommandTab(tabKey) {
        ['alerts', 'curves'].forEach(k => {
            const btn = document.getElementById('tab_btn_' + k);
            const panel = document.getElementById('tab_panel_' + k);
            if (btn && panel) {
                if (k === tabKey) {
                    panel.classList.remove('hidden');
                    btn.className = 'px-4 py-2.5 text-xs font-bold rounded-t-xl transition flex items-center gap-2 border-b-2 border-brand-dark text-brand-dark bg-white shadow-sm';
                } else {
                    panel.classList.add('hidden');
                    btn.className = 'px-4 py-2.5 text-xs font-semibold rounded-t-xl transition flex items-center gap-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100/60';
                }
            }
        });

        if (tabKey === 'curves') {
            setTimeout(initEpiCurves, 100);
        }
    }

    function initEpiCurves() {
        if (chartInstance) return;
        const ctx = document.getElementById('epiCurvesChart');
        if (!ctx) return;

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: TREND_DATA.labels,
                datasets: [
                    {
                        label: 'Dengue Cases',
                        data: TREND_DATA.series.Dengue || [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5
                    },
                    {
                        label: 'Leptospirosis Cases',
                        data: TREND_DATA.series.Leptospirosis || [],
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.05)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    },
                    {
                        label: 'Influenza Cases',
                        data: TREND_DATA.series.Influenza || [],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function viewAlertDetails(alert) {
        document.getElementById('detailModalTitle').textContent = `${alert.disease} (${alert.alert_code})`;
        
        const isOutbreak = alert.plain_status.includes('Outbreak') || alert.severity === 'Critical';
        const protocolText = isOutbreak 
            ? '🚨 <strong>Outbreak Escalation Protocol</strong>: Case volume has crossed the +2 Standard Deviation upper epidemic threshold. Recommended action: Active field contact investigation, water/vector sampling in ' + alert.zone + ', and heightened syndromic monitoring.'
            : '🟡 <strong>Active Surveillance Watch Protocol</strong>: Early cluster signal (+1 Standard Deviation above baseline). Recommended action: Intensify sentinel surveillance, verify triage entries, and monitor for additional cases in ' + alert.zone + '.';

        const content = `
            <div class="grid grid-cols-2 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                <div><span class="text-slate-400 font-bold uppercase text-[10px]">Tracked Disease</span><p class="font-black text-slate-900 text-sm mt-0.5">${escapeHtml(alert.disease)}</p></div>
                <div><span class="text-slate-400 font-bold uppercase text-[10px]">Surveillance Zone</span><p class="font-black text-slate-900 text-sm mt-0.5">${escapeHtml(alert.zone)}</p></div>
                <div><span class="text-slate-400 font-bold uppercase text-[10px]">Active Cases</span><p class="font-black text-slate-900 text-sm mt-0.5">${alert.cases} Recorded</p></div>
                <div><span class="text-slate-400 font-bold uppercase text-[10px]">Calculated Baseline Threshold</span><p class="font-black text-slate-900 text-sm mt-0.5">${alert.threshold} Threshold</p></div>
            </div>
            
            <div class="space-y-1.5">
                <span class="text-slate-400 font-bold uppercase text-[10px]">Current Public Health Status</span>
                <div class="p-2.5 rounded-xl border ${alert.badge_class} font-bold text-xs flex items-center justify-between">
                    <span>${alert.plain_status}</span>
                    <span class="text-[11px] font-semibold">${alert.severity} Severity</span>
                </div>
            </div>

            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 leading-relaxed">
                ${protocolText}
            </div>

            <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
                <a href="<?= site_url('modules/surveillence/mapping.php') ?>" class="px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg font-semibold transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-map-location-dot text-brand-dark"></i> View on GIS Map
                </a>
                <a href="<?= site_url('modules/surveillence/case_reports.php') ?>" class="px-3 py-2 bg-brand-dark text-white rounded-lg font-semibold hover:bg-brand-medium transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-file-medical"></i> Open Case Registry
                </a>
            </div>
        `;
        document.getElementById('detailModalContent').innerHTML = content;
        ModalSystem.open('alertDetailsModal');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function filterAlertsTable() {
        const query = (document.getElementById('alertSearchInput')?.value || '').toLowerCase();
        const rows = document.querySelectorAll('#alertsTableBody tr');
        rows.forEach(r => {
            const disease = r.dataset.disease || '';
            const zone = r.dataset.zone || '';
            if (disease.includes(query) || zone.includes(query)) {
                r.style.display = '';
            } else {
                r.style.display = 'none';
            }
        });
    }

    // 15-Second Background Polling Heartbeat
    setInterval(() => {
        fetch('<?= site_url("modules/surveillence/api/alerts.php?action=heartbeat") ?>')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('kpi_critical_alerts').textContent = data.critical_count;
                    document.getElementById('tab_alert_badge').textContent = data.total_active;
                }
            })
            .catch(() => {});
    }, 15000);
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
