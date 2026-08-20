<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/paths.php';

// Auto-restore session from active 12h/7d civentral_session cookie if PHP session expired
if (empty($_SESSION['logged_in']) && !empty($_COOKIE['civentral_session'])) {
    require_once __DIR__ . '/../app/services/SessionAuthService.php';
    $authSvc = new SessionAuthService();
    $authSvc->validateActiveToken($_COOKIE['civentral_session']);
}

if (empty($_SESSION['logged_in'])) {
    $_SESSION['flash_error'] = 'Access Denied: Please log in to access the dashboard.';
    header('Location: ' . site_url('login.php'));
    exit;
}

require_once __DIR__ . '/../app/Models/ActivityLog.php';
$activityLogModel = new ActivityLog();
$allDashboardLogs = $activityLogModel->all(['limit' => 30]);
$recentActivities = array_values(array_filter($allDashboardLogs, function($log) {
    $module = strtolower($log['module'] ?? '');
    return !str_contains($module, 'authentication') 
        && !str_contains($module, 'user management') 
        && !str_contains($module, 'system management');
}));
$recentActivities = array_slice($recentActivities, 0, 5);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php'; 

 if (!empty($_SESSION['flash_error'])):
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof toast !== 'undefined') {
        toast.error(<?php echo json_encode($_SESSION['flash_error']); ?>, { title: 'Access Denied' });
    } else if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
        ModalSystem.toast.error(<?php echo json_encode($_SESSION['flash_error']); ?>, { title: 'Access Denied' });
    }
});
</script>
<?php unset($_SESSION['flash_error']); endif; ?>
<!-- ADD FONT AWESOME CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<!-- ADD APEXCHARTS CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<!-- ADD SUPABASE JS CDN FOR REALTIME WEBSOCKET PUSH -->
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

<main class="bg-white flex-1 h-full flex flex-col overflow-hidden" role="main" aria-label="Dashboard content">

    <!-- PRINT HEADER (Hidden on screen, shown in print) -->
    <div id="printHeader">
        <img src="../assets/images/logo.png" alt="Logo">
        <h1>Health Sanitation Management Caloocan</h1>
        <h2>Dashboard Performance Report</h2>
    </div>

    <!-- Dashboard Core Keyframes & Print Styles -->
    <style>
        #printHeader { display: none; }
        @page { margin: 0.75in; }
        @media print {
            header, aside, .sidebar, #sidebar, footer, .footer, #footer, #bottomActionBar, .no-print, #activityFeed, #refreshBtn, a[href="ai_insights.php"] { display: none !important; }
            html, body, main { height: auto !important; min-height: auto !important; overflow: visible !important; background: #ffffff !important; }
            #printHeader { display: block !important; text-align: center; margin: 0 0 25px; border-bottom: 2px solid #000; padding-bottom: 15px; }
            #printHeader img { width: 120px; height: auto; margin: 0 auto 10px; display: block; }
            #printHeader h1 { font-size: 20pt; font-weight: bold; margin: 0; text-transform: uppercase; }
            #printHeader h2 { font-size: 14pt; font-weight: normal; margin: 5px 0 0; }
        }
        @keyframes pulse2 { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.4; transform: scale(1.15); } }
        .animate-pulse2 { animation: pulse2 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        .kpi-grid { transition: opacity 0.2s ease, filter 0.2s ease; }
        .kpi-updating { opacity: 0.45; filter: blur(2px); pointer-events: none; }
        .custom-scroll::-webkit-scrollbar { width: 3px; height: 3px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>

    <!-- ===== PAGE CONTAINER ===== -->
    <div class="flex-1 px-6 pt-[26px] pb-4 flex flex-col min-h-0 overflow-y-auto custom-scroll animate-fadeOverlay dashboard-content">

        <!-- ============================================================ -->
        <!-- PAGE HEADER (Role-Aware Custom Dashboard)                    -->
        <!-- ============================================================ -->
        <?php
            $currentRole = trim($_SESSION['role'] ?? $_SESSION['role_description'] ?? 'System Admin');
            $userRoleDesc = trim($_SESSION['role_description'] ?? '');
            $userRole     = trim($_SESSION['role'] ?? '');
            $isSysAdmin   = getPermissionService()->isAdminRole($userRoleDesc) 
                || getPermissionService()->isAdminRole($userRole)
                || (isset($_SESSION['department']) && strcasecmp($_SESSION['department'], 'Administration') === 0);

            if ($isSysAdmin) {
                $dashTitle = 'System Overview';
                $dashSubtitle = 'Real-time snapshot across all modules and system health';
                $dashBadge = 'System Administrator';
            } elseif (hasPermission('dashboard.surveillance') || str_contains(strtolower($currentRole), 'surveillance') || (isset($_SESSION['department']) && strcasecmp($_SESSION['department'], 'health surveillance') === 0)) {
                $dashTitle = 'Health Surveillance Command Center';
                $dashSubtitle = 'Disease monitoring, outbreak detection & epidemiological analytics';
                $dashBadge = htmlspecialchars($currentRole);
            } elseif (hasPermission('dashboard.health_center')) {
                $dashTitle = 'Health Center Services Dashboard';
                $dashSubtitle = 'Operational analytics, patient consultations & health center performance overview';
                $dashBadge = 'Health Center Director';
            } elseif (hasPermission('dashboard.sanitation')) {
                $dashTitle = 'Sanitation Permits Dashboard';
                $dashSubtitle = 'Permit applications, environmental inspections & sanitation compliance metrics';
                $dashBadge = 'Sanitation Director';
            } elseif (hasPermission('dashboard.immunization')) {
                $dashTitle = 'Immunization & Nutrition Dashboard';
                $dashSubtitle = 'Child vaccination tracking, growth charts & nutrition assessment analytics';
                $dashBadge = 'Immunization Coordinator';
            } else {
                $dashTitle = htmlspecialchars($currentRole) . ' Dashboard';
                $dashSubtitle = 'Role-specific operational activity & module metrics';
                $dashBadge = htmlspecialchars($currentRole);
            }
        ?>

        <div class="flex-shrink-0 mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl font-bold text-c3 flex items-center gap-2">
                        <i class="fas fa-gauge-high text-c2" aria-hidden="true"></i>
                        <?php echo $dashTitle; ?>
                    </h1>
                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-semibold flex items-center gap-1">
                        <i class="fas fa-user-shield text-[9px]" aria-hidden="true"></i> <?php echo $dashBadge; ?>
                    </span>
                    <span class="flex items-center gap-1.5 text-[10px] text-emerald-600 ml-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse2" aria-hidden="true"></span>
                        Live
                    </span>
                </div>
                <p class="text-sm text-[#4a6080] mt-0.5"><?php echo $dashSubtitle; ?></p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                    <span id="lastUpdated">
                        <i class="fas fa-clock text-[9px] mr-1" aria-hidden="true"></i> 
                        Updated just now
                    </span>
                    <span id="dataAge" class="text-[10px] text-slate-400">
                        <i class="fas fa-sync text-[9px] mr-1" aria-hidden="true"></i> 
                        <span id="dataAgeText">0s ago</span>
                    </span>
                    <button onclick="refreshDashboard()" 
                            id="refreshBtn" 
                            class="w-6 h-6 rounded-lg bg-slate-50 hover:bg-slate-100 flex items-center justify-center text-slate-500 transition"
                            aria-label="Refresh dashboard data"
                            title="Refresh dashboard data">
                        <i class="fas fa-rotate text-[10px]" aria-hidden="true"></i>
                    </button>
                </div>
                <a href="ai_insights.php" 
                   class="flex items-center gap-1.5 px-3 py-2 bg-c3 text-white rounded-xl text-xs font-semibold hover:bg-c3d transition shadow-sm"
                   aria-label="View detailed analytics">
                    <i class="fas fa-chart-line text-[11px]" aria-hidden="true"></i> View Analytics
                </a>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- ============================================================ -->
        <!-- KPI ROW (Role-Specific 6 Dedicated Cards)                    -->
        <!-- ============================================================ -->
        <?php
// Use permission-based and role/department checks with admin precedence — single source of truth
$_isSurvRole = !$isSysAdmin && (
    hasPermission('dashboard.surveillance') 
    || str_contains(strtolower($currentRole), 'surveillance')
    || (isset($_SESSION['department']) && strcasecmp($_SESSION['department'], 'health surveillance') === 0)
);
$_isHcRole   = !$isSysAdmin && !$_isSurvRole && hasPermission('dashboard.health_center');
$_isSanRole  = !$isSysAdmin && !$_isSurvRole && hasPermission('dashboard.sanitation');
$_isImmRole  = !$isSysAdmin && !$_isSurvRole && hasPermission('dashboard.immunization');

// ============================================================
// SUPABASE BACKEND DATA QUERIES (Optimized with Session Cache)
// ============================================================
require_once __DIR__ . '/../app/services/DashboardService.php';
$_dashService = \App\Services\DashboardService::getInstance();
$_forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';
if ($_forceRefresh) {
    $_dashService->clearCache();
}
$_metricsData = $_dashService->getDashboardMetrics($_forceRefresh);

$_patientsDbRecords      = $_metricsData['raw']['patients'] ?? [];
$_consultationsDbRecords = $_metricsData['raw']['consultations'] ?? [];
$_prescriptionsDbRecords = $_metricsData['raw']['prescriptions'] ?? [];
$_permitsDbRecords       = $_metricsData['raw']['permits'] ?? [];
$_inspectionsDbRecords   = $_metricsData['raw']['inspections'] ?? [];
$_triageDbRecords        = $_metricsData['raw']['triage'] ?? [];
$_childDbRecords         = $_metricsData['raw']['child_records'] ?? [];
$_wastewaterDbRecords    = $_metricsData['raw']['wastewater'] ?? [];
$_survCasesDbRecords     = $_metricsData['raw']['surveillance'] ?? [];

// Dynamic Counts & Metrics strictly from Real Database
$_patientCountTotal       = count($_patientsDbRecords);
$_consultationCountTotal  = count($_consultationsDbRecords);
$_prescriptionCountTotal  = count($_prescriptionsDbRecords);
$_permitCountTotal        = count($_permitsDbRecords);
$_pendingPermitsCount     = count(array_filter($_permitsDbRecords, fn($p) => strcasecmp($p['status'] ?? '', 'Pending') === 0));
$_inspectionCountTotal    = count($_inspectionsDbRecords);
$_triageCountTotal        = count($_triageDbRecords);
$_childCountTotal         = count($_childDbRecords);
$_wasteCountTotal         = count($_wastewaterDbRecords);
$_survCountTotal          = count($_survCasesDbRecords);
$_outbreakCountTotal      = count(array_filter($_survCasesDbRecords, fn($c) => in_array(strtolower($c['status'] ?? ''), ['outbreak', 'critical', 'alert'])));

// Helper to calculate monthly growth badge % from Supabase created_at timestamp
$_calcGrowthBadge = function(array $records, string $defaultBadge = '0.0%') {
    if (empty($records)) return '0.0%';
    $thisMonthStart = strtotime('first day of this month 00:00:00');
    $lastMonthStart = strtotime('first day of last month 00:00:00');
    $lastMonthEnd   = strtotime('last day of last month 23:59:59');

    $thisMonthCount = 0;
    $lastMonthCount = 0;

    foreach ($records as $r) {
        $dateStr = $r['created_at'] ?? $r['date'] ?? $r['created_date'] ?? $r['issued_date'] ?? '';
        $t = strtotime((string)$dateStr);
        if (!$t) continue;
        if ($t >= $thisMonthStart) {
            $thisMonthCount++;
        } elseif ($t >= $lastMonthStart && $t <= $lastMonthEnd) {
            $lastMonthCount++;
        }
    }

    if ($thisMonthCount === 0 && $lastMonthCount === 0) return '0.0%';
    if ($lastMonthCount === 0) {
        return $thisMonthCount > 0 ? '+100.0%' : '0.0%';
    }

    $diff = (($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100;
    $prefix = $diff >= 0 ? '+' : '';
    return $prefix . number_format($diff, 1) . '%';
};

// Helper to calculate circular SVG ring percentage & offset dynamically
$_calcRingPctData = function(array $records, callable $filterFn = null, int $defaultPct = 0) {
    if (empty($records)) {
        return ['pct' => '0%', 'offset' => '100'];
    }
    if ($filterFn !== null) {
        $matched = count(array_filter($records, $filterFn));
        $total   = count($records);
        $val = $total > 0 ? (int)round(($matched / $total) * 100) : 0;
    } else {
        $val = 100;
    }
    $val = max(0, min(100, $val));
    return [
        'pct' => $val . '%',
        'offset' => (string)(100 - $val)
    ];
};

if ($_isHcRole) {
    $_hcConsultData = $_calcRingPctData($_consultationsDbRecords);
    $_hcTriageData  = $_calcRingPctData($_triageDbRecords);
    $_hcPrescData   = $_calcRingPctData($_prescriptionsDbRecords);
    $_hcSurvData    = $_calcRingPctData($_survCasesDbRecords);
    $_hcPatientData = $_calcRingPctData($_patientsDbRecords);

    $kpiCards = [
        [
            'title' => 'Patients Served',
            'value' => number_format($_patientCountTotal),
            'label' => 'Total Patients In System',
            'badge' => $_calcGrowthBadge($_patientsDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/healthservices/patients.php'),
            'icon' => 'fa-users',
            'color' => 'emerald-600',
            'border_color' => 'from-emerald-400 to-emerald-600',
            'offset' => $_hcPatientData['offset'],
            'pct' => $_hcPatientData['pct']
        ],
        [
            'title' => 'Consultations',
            'value' => number_format($_consultationCountTotal),
            'label' => 'Consultations Completed',
            'badge' => $_calcGrowthBadge($_consultationsDbRecords),
            'badge_bg' => 'bg-sky-100 text-sky-700',
            'sub' => 'completed consults',
            'url' => site_url('modules/healthservices/consultations.php'),
            'icon' => 'fa-stethoscope',
            'color' => 'sky-600',
            'border_color' => 'from-sky-400 to-sky-600',
            'offset' => $_hcConsultData['offset'],
            'pct' => $_hcConsultData['pct']
        ],
        [
            'title' => 'Triage Visits',
            'value' => number_format($_triageCountTotal),
            'label' => 'Patients Triaged',
            'badge' => $_calcGrowthBadge($_triageDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'triaged queue',
            'url' => site_url('modules/healthservices/triage.php'),
            'icon' => 'fa-heart-pulse',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => $_hcTriageData['offset'],
            'pct' => $_hcTriageData['pct']
        ],
        [
            'title' => 'Prescriptions Issued',
            'value' => number_format($_prescriptionCountTotal),
            'label' => 'Prescriptions Dispensed',
            'badge' => $_calcGrowthBadge($_prescriptionsDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'pharmacy fulfilled',
            'url' => site_url('modules/healthservices/prescriptions.php'),
            'icon' => 'fa-prescription-bottle',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => $_hcPrescData['offset'],
            'pct' => $_hcPrescData['pct']
        ],
        [
            'title' => 'Health Surveillance',
            'value' => number_format($_survCountTotal),
            'label' => 'Active Case Reports',
            'badge' => $_survCountTotal > 0 ? ($_survCountTotal . ' active') : '0 active',
            'badge_bg' => 'bg-indigo-100 text-indigo-700',
            'sub' => 'disease monitoring',
            'url' => site_url('modules/surveillence/case_reports.php'),
            'icon' => 'fa-binoculars',
            'color' => 'indigo-600',
            'border_color' => 'from-indigo-400 to-indigo-600',
            'offset' => $_hcSurvData['offset'],
            'pct' => $_hcSurvData['pct']
        ],
        [
            'title' => 'Real-time Alerts',
            'value' => (string)$_outbreakCountTotal,
            'label' => 'Outbreak Watch Alerts',
            'badge' => $_outbreakCountTotal > 0 ? ($_outbreakCountTotal . ' Critical') : 'Normal',
            'badge_bg' => $_outbreakCountTotal > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700',
            'sub' => 'immediate response',
            'url' => site_url('modules/surveillence/alerts.php'),
            'icon' => 'fa-bell',
            'color' => 'rose-600',
            'border_color' => 'from-rose-500 to-red-600',
            'offset' => $_outbreakCountTotal > 0 ? '5' : '100',
            'pct' => $_outbreakCountTotal > 0 ? '100%' : '0%'
        ],
    ];
} elseif ($_isSanRole) {
    $_sanRingData = $_calcRingPctData($_permitsDbRecords, fn($p) => in_array(strtolower($p['status'] ?? ''), ['approved', 'active', 'issued']));
    $_inspRingData = $_calcRingPctData($_inspectionsDbRecords);
    $_wasteRingData = $_calcRingPctData($_wastewaterDbRecords);

    $kpiCards = [
        [
            'title' => 'Sanitation Permits',
            'value' => number_format($_permitCountTotal),
            'label' => 'Active Permits Issued',
            'badge' => $_pendingPermitsCount > 0 ? ($_pendingPermitsCount . ' pending') : 'No pending',
            'badge_bg' => $_pendingPermitsCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
            'sub' => $_sanRingData['pct'] . ' approval',
            'url' => site_url('modules/sanitation/permit_applications.php'),
            'icon' => 'fa-file-signature',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => $_sanRingData['offset'],
            'pct' => $_sanRingData['pct']
        ],
        [
            'title' => 'Field Inspections',
            'value' => number_format($_inspectionCountTotal),
            'label' => 'Inspections Conducted',
            'badge' => $_calcGrowthBadge($_inspectionsDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'sanitary compliance',
            'url' => site_url('modules/sanitation/inspections.php'),
            'icon' => 'fa-search',
            'color' => 'emerald-600',
            'border_color' => 'from-emerald-400 to-emerald-600',
            'offset' => $_inspRingData['offset'],
            'pct' => $_inspRingData['pct']
        ],
        [
            'title' => 'Permit Renewals',
            'value' => number_format($_permitCountTotal),
            'label' => 'Total Permit Records',
            'badge' => $_pendingPermitsCount > 0 ? ($_pendingPermitsCount . ' pending') : 'No pending',
            'badge_bg' => 'bg-blue-100 text-blue-700',
            'sub' => 'annual renewal',
            'url' => site_url('modules/sanitation/renewals.php'),
            'icon' => 'fa-rotate',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => $_sanRingData['offset'],
            'pct' => $_sanRingData['pct']
        ],
        [
            'title' => 'Wastewater Requests',
            'value' => number_format($_wasteCountTotal),
            'label' => 'Septic Tank Services',
            'badge' => $_calcGrowthBadge($_wastewaterDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'service requests',
            'url' => site_url('modules/services/septic_tanks.php'),
            'icon' => 'fa-water',
            'color' => 'purple-600',
            'border_color' => 'from-purple-400 to-purple-600',
            'offset' => $_wasteRingData['offset'],
            'pct' => $_wasteRingData['pct']
        ],
        [
            'title' => 'Sanitation Complaints',
            'value' => '0',
            'label' => 'Public Sanitation Reports',
            'badge' => '0 pending',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'field investigation',
            'url' => site_url('modules/sanitation/inspections.php'),
            'icon' => 'fa-triangle-exclamation',
            'color' => 'rose-600',
            'border_color' => 'from-rose-400 to-rose-600',
            'offset' => '100',
            'pct' => '0%'
        ],
        [
            'title' => 'Establishment Audit',
            'value' => number_format($_permitCountTotal),
            'label' => 'Commercial Audits',
            'badge' => $_permitCountTotal > 0 ? ($_permitCountTotal . ' audited') : '0 audited',
            'badge_bg' => 'bg-teal-100 text-teal-700',
            'sub' => 'compliance monitoring',
            'url' => site_url('modules/sanitation/inspections.php'),
            'icon' => 'fa-building-circle-check',
            'color' => 'teal-600',
            'border_color' => 'from-teal-400 to-teal-600',
            'offset' => $_sanRingData['offset'],
            'pct' => $_sanRingData['pct']
        ],
    ];
} elseif ($_isImmRole) {
    $_immRingData = $_calcRingPctData($_childDbRecords);
    $kpiCards = [
        [
            'title' => 'Child Immunization',
            'value' => number_format($_childCountTotal),
            'label' => 'Children Enrolled',
            'badge' => $_calcGrowthBadge($_childDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'health coverage',
            'url' => site_url('modules/immunization/child_records.php'),
            'icon' => 'fa-syringe',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => $_immRingData['offset'],
            'pct' => $_immRingData['pct']
        ],
        [
            'title' => 'Vaccine Inventory',
            'value' => '0',
            'label' => 'Doses in Stock',
            'badge' => '0 low stock',
            'badge_bg' => 'bg-slate-100 text-slate-600',
            'sub' => 'cold chain active',
            'url' => site_url('modules/immunization/vaccine_inventory.php'),
            'icon' => 'fa-vial-circle-check',
            'color' => 'purple-600',
            'border_color' => 'from-purple-400 to-purple-600',
            'offset' => '100',
            'pct' => '0%'
        ],
        [
            'title' => 'Nutrition Alerts',
            'value' => '0',
            'label' => 'Malnutrition Cases',
            'badge' => '0 critical',
            'badge_bg' => 'bg-slate-100 text-slate-600',
            'sub' => 'under surveillance',
            'url' => site_url('modules/immunization/nutrition_records.php'),
            'icon' => 'fa-apple-whole',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => '100',
            'pct' => '0%'
        ],
        [
            'title' => 'Defaulter Tracing',
            'value' => '0',
            'label' => 'Missed Second Doses',
            'badge' => '0 pending',
            'badge_bg' => 'bg-slate-100 text-slate-600',
            'sub' => 'follow-up pending',
            'url' => site_url('modules/immunization/defaulter_tracking.php'),
            'icon' => 'fa-user-clock',
            'color' => 'teal-600',
            'border_color' => 'from-teal-400 to-teal-600',
            'offset' => '100',
            'pct' => '0%'
        ],
        [
            'title' => 'Target Coverage',
            'value' => $_immRingData['pct'],
            'label' => 'Barangay Target',
            'badge' => $_childCountTotal > 0 ? 'Active' : 'No Data',
            'badge_bg' => $_childCountTotal > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
            'sub' => 'annual goal',
            'url' => site_url('modules/immunization/coverage_reports.php'),
            'icon' => 'fa-bullseye',
            'color' => 'indigo-600',
            'border_color' => 'from-indigo-400 to-indigo-600',
            'offset' => $_immRingData['offset'],
            'pct' => $_immRingData['pct']
        ],
        [
            'title' => 'Immunization Sessions',
            'value' => number_format($_childCountTotal),
            'label' => 'Completed Sessions',
            'badge' => $_calcGrowthBadge($_childDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'this month',
            'url' => site_url('modules/immunization/child_records.php'),
            'icon' => 'fa-calendar-check',
            'color' => 'sky-600',
            'border_color' => 'from-sky-400 to-sky-600',
            'offset' => $_immRingData['offset'],
            'pct' => $_immRingData['pct']
        ],
    ];
} elseif ($_isSurvRole) {
    $_survResData = $_calcRingPctData($_survCasesDbRecords);
    $kpiCards = [
        [
            'title' => 'Active Cases',
            'value' => number_format($_survCountTotal),
            'label' => 'Cases Under Monitoring',
            'badge' => $_calcGrowthBadge($_survCasesDbRecords),
            'badge_bg' => 'bg-rose-100 text-rose-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/surveillence/case_reports.php'),
            'icon' => 'fa-binoculars',
            'color' => 'rose-600',
            'border_color' => 'from-rose-400 to-rose-600',
            'offset' => $_survResData['offset'],
            'pct' => $_survResData['pct']
        ],
        [
            'title' => 'Outbreak Alerts',
            'value' => (string)$_outbreakCountTotal,
            'label' => 'Active Outbreak Watches',
            'badge' => $_outbreakCountTotal > 0 ? ($_outbreakCountTotal . ' Critical') : '0 Critical',
            'badge_bg' => $_outbreakCountTotal > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600',
            'sub' => '2-SD threshold signals',
            'url' => site_url('modules/surveillence/outbreak_command.php'),
            'icon' => 'fa-bell',
            'color' => 'red-600',
            'border_color' => 'from-red-400 to-red-600',
            'offset' => $_outbreakCountTotal > 0 ? '8' : '100',
            'pct' => $_outbreakCountTotal > 0 ? '100%' : '0%'
        ],
        [
            'title' => 'Investigations',
            'value' => number_format($_survCountTotal),
            'label' => 'Active Field Investigations',
            'badge' => $_survCountTotal . ' active',
            'badge_bg' => 'bg-amber-100 text-amber-700',
            'sub' => 'field cases',
            'url' => site_url('modules/surveillence/investigations.php'),
            'icon' => 'fa-magnifying-glass',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => $_survResData['offset'],
            'pct' => $_survResData['pct']
        ],
        [
            'title' => 'Lab Results',
            'value' => '0',
            'label' => 'Pending Lab Confirmations',
            'badge' => '0 urgent',
            'badge_bg' => 'bg-slate-100 text-slate-600',
            'sub' => 'awaiting results',
            'url' => site_url('modules/surveillence/lab_results.php'),
            'icon' => 'fa-flask-vial',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => '100',
            'pct' => '0%'
        ],
        [
            'title' => 'Contact Tracing',
            'value' => '0',
            'label' => 'Contacts Tracked',
            'badge' => '0 reached',
            'badge_bg' => 'bg-slate-100 text-slate-600',
            'sub' => 'active monitoring',
            'url' => site_url('modules/surveillence/contact_tracing.php'),
            'icon' => 'fa-people-arrows',
            'color' => 'emerald-600',
            'border_color' => 'from-emerald-400 to-emerald-600',
            'offset' => '100',
            'pct' => '0%'
        ],
        [
            'title' => 'Reports Filed',
            'value' => number_format($_survCountTotal),
            'label' => 'Epi Reports Filed',
            'badge' => $_calcGrowthBadge($_survCasesDbRecords),
            'badge_bg' => 'bg-purple-100 text-purple-700',
            'sub' => 'epidemiological data',
            'url' => site_url('modules/surveillence/reports.php'),
            'icon' => 'fa-file-medical',
            'color' => 'purple-600',
            'border_color' => 'from-purple-400 to-purple-600',
            'offset' => $_survResData['offset'],
            'pct' => $_survResData['pct']
        ],
    ];
} else {
    $hcValFormatted = number_format($_patientCountTotal);
    $sanValFormatted = number_format($_permitCountTotal);
    $pendingBadgeText = $_pendingPermitsCount > 0 ? ($_pendingPermitsCount . ' pending') : 'No pending';
    $pendingBadgeBg = $_pendingPermitsCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
    $immValFormatted = number_format($_childCountTotal);
    $wasteValFormatted = number_format($_wasteCountTotal);
    $survValFormatted = number_format($_survCountTotal);
    $outbreakBadgeText = $_outbreakCountTotal > 0 ? ($_outbreakCountTotal . ' outbreak') : '0 outbreak';
    $logsTotalCount = count($allDashboardLogs);
    $logsSubText = $logsTotalCount . ' logs rec.';

    $_hcRingData    = $_calcRingPctData($_patientsDbRecords);
    $_sanRingData   = $_calcRingPctData($_permitsDbRecords, fn($p) => in_array(strtolower($p['status'] ?? ''), ['approved', 'active', 'issued']));
    $_immRingData   = $_calcRingPctData($_childDbRecords);
    $_wasteRingData = $_calcRingPctData($_wastewaterDbRecords);
    $_survRingData  = $_calcRingPctData($_survCasesDbRecords);
    $_sysRingData   = $_calcRingPctData($allDashboardLogs);

    $_hcGrowthBadge    = $_calcGrowthBadge($_patientsDbRecords);
    $_wasteGrowthBadge = $_calcGrowthBadge($_wastewaterDbRecords);

    $kpiCards = [
        [
            'title' => 'Health Center',
            'value' => $hcValFormatted,
            'label' => 'Patients Served',
            'badge' => $_hcGrowthBadge,
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/healthservices/patients.php'),
            'icon' => 'fa-hospital',
            'color' => 'c2',
            'border_color' => 'from-c3 to-c2',
            'offset' => $_hcRingData['offset'],
            'pct' => $_hcRingData['pct']
        ],
        [
            'title' => 'Sanitation',
            'value' => $sanValFormatted,
            'label' => 'Active Permits',
            'badge' => $pendingBadgeText,
            'badge_bg' => $pendingBadgeBg,
            'sub' => $_sanRingData['pct'] . ' approval',
            'url' => site_url('modules/sanitation/permit_applications.php'),
            'icon' => 'fa-file-signature',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => $_sanRingData['offset'],
            'pct' => $_sanRingData['pct']
        ],
        [
            'title' => 'Immunization',
            'value' => $immValFormatted,
            'label' => 'Immunized',
            'badge' => $_childCountTotal > 0 ? 'Active' : '0 records',
            'badge_bg' => $_childCountTotal > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
            'sub' => $_immRingData['pct'] . ' coverage',
            'url' => site_url('modules/immunization/child_records.php'),
            'icon' => 'fa-syringe',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => $_immRingData['offset'],
            'pct' => $_immRingData['pct']
        ],
        [
            'title' => 'Wastewater',
            'value' => $wasteValFormatted,
            'label' => 'Service Requests',
            'badge' => $_wasteGrowthBadge,
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/services/septic_tanks.php'),
            'icon' => 'fa-water',
            'color' => 'purple-600',
            'border_color' => 'from-purple-400 to-purple-600',
            'offset' => $_wasteRingData['offset'],
            'pct' => $_wasteRingData['pct']
        ],
        [
            'title' => 'Surveillance',
            'value' => $survValFormatted,
            'label' => 'Active Cases',
            'badge' => $outbreakBadgeText,
            'badge_bg' => $_outbreakCountTotal > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600',
            'sub' => $_survRingData['pct'] . ' resolved',
            'url' => site_url('modules/surveillence/case_reports.php'),
            'icon' => 'fa-binoculars',
            'color' => 'rose-600',
            'border_color' => 'from-rose-400 to-rose-600',
            'offset' => $_survRingData['offset'],
            'pct' => $_survRingData['pct']
        ],
        [
            'title' => 'System Activity',
            'value' => number_format($logsTotalCount),
            'label' => 'Logs Recorded',
            'badge' => 'Operational',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => $logsSubText,
            'url' => site_url('management/system_logs.php'),
            'icon' => 'fa-server',
            'color' => 'indigo-600',
            'border_color' => 'from-indigo-400 to-indigo-600',
            'offset' => $_sysRingData['offset'],
            'pct' => $_sysRingData['pct']
        ],
    ];
}
?>
        <div class="kpi-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6 flex-shrink-0">
            <?php foreach ($kpiCards as $card): ?>
            <a href="<?php echo $card['url']; ?>" 
               class="kpi-card relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-<?php echo $card['color']; ?>/20 cursor-pointer group block"
               aria-label="<?php echo $card['title']; ?>: <?php echo $card['value']; ?> <?php echo $card['label']; ?>">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-slate-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas <?php echo $card['icon']; ?> kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-slate-400/10 rotate-[-8deg] pointer-events-none" aria-hidden="true"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b <?php echo $card['border_color']; ?>"></div>
                <div class="relative p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-slate-600">
                                <i class="fas <?php echo $card['icon']; ?> text-[7px] mr-1" aria-hidden="true"></i><?php echo $card['title']; ?>
                            </p>
                            <p class="kpi-number text-xl font-black text-slate-900 mt-1 leading-none" data-base-val="<?php echo htmlspecialchars($card['value']); ?>"><?php echo $card['value']; ?></p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5"><?php echo $card['label']; ?></p>
                        </div>
                        <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#176B87" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:<?php echo $card['offset']; ?>" transform="rotate(-90 18 18)"/>
                            <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#176B87"><?php echo $card['pct']; ?></text>
                        </svg>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="px-1.5 py-0.5 <?php echo $card['badge_bg']; ?> rounded-full text-[7px] font-bold">
                            <?php echo $card['badge']; ?>
                        </span>
                        <span class="text-[7px] text-slate-400"><?php echo $card['sub']; ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- ============================================================ -->
        <!-- DATE FILTER BAR (below KPI cards)                            -->
        <!-- ============================================================ -->
        <div class="flex-shrink-0 mb-6 flex flex-wrap items-center justify-between gap-2 bg-slate-50 border border-slate-100 rounded-xl px-3 py-2">
            <div class="flex items-center gap-1.5 flex-wrap" id="dateFilterChips" role="group" aria-label="Filter dashboard by date range">
                <span class="text-[10px] font-semibold text-slate-500 mr-1">
                    <i class="fas fa-calendar text-[9px] mr-1" aria-hidden="true"></i>Showing:
                </span>
                <button type="button" class="date-filter-chip active px-2.5 py-1 rounded-lg text-[10px] font-semibold text-slate-600" data-range="today" onclick="setDateFilter('today', this)">Today</button>
                <button type="button" class="date-filter-chip px-2.5 py-1 rounded-lg text-[10px] font-semibold text-slate-600" data-range="7d" onclick="setDateFilter('7d', this)">Last 7 Days</button>
                <button type="button" class="date-filter-chip px-2.5 py-1 rounded-lg text-[10px] font-semibold text-slate-600" data-range="30d" onclick="setDateFilter('30d', this)">Last 30 Days</button>
                <button type="button" class="date-filter-chip px-2.5 py-1 rounded-lg text-[10px] font-semibold text-slate-600" data-range="month" onclick="setDateFilter('month', this)">This Month</button>
                <button type="button" class="date-filter-chip px-2.5 py-1 rounded-lg text-[10px] font-semibold text-slate-600" data-range="custom" onclick="openCustomDateRange(this)">
                    <i class="fas fa-calendar-days text-[9px] mr-1" aria-hidden="true"></i>Custom
                </button>
            </div>
            <div class="flex items-center gap-2">
                <span id="activeRangeLabel" class="text-[10px] text-slate-400"><?php echo date('M j, Y'); ?></span>
                <input type="date" id="customDateStart" class="hidden text-[10px] border border-slate-200 rounded-lg px-2 py-1" aria-label="Custom range start date" />
                <span id="customDateSep" class="hidden text-[10px] text-slate-400">to</span>
                <input type="date" id="customDateEnd" class="hidden text-[10px] border border-slate-200 rounded-lg px-2 py-1" aria-label="Custom range end date" />
                <button id="customDateApply" onclick="applyCustomDateRange()" class="hidden text-[10px] font-semibold text-white bg-c3 hover:bg-c3d px-2.5 py-1 rounded-lg transition">Apply</button>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 3-COLUMN LAYOUT: Module Summary | Alerts | System Health     -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-shrink-0">

            <!-- ========================================================== -->
            <!-- COLUMN 1: Module Activity Summary (Role-Specific)         -->
            <!-- ========================================================== -->
            <?php
            if ($_isHcRole) {
                $moduleSummaryCards = [
                    [
                        'name' => 'Patient Management',
                        'icon' => 'fa-users',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50',
                        'total' => number_format($_patientCountTotal) . ' registered',
                        'today' => count(array_filter($_patientsDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_patientCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-emerald-500',
                        'badge' => $_patientCountTotal > 0 ? 'Active' : 'No Data',
                        'badge_bg' => $_patientCountTotal > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_patientCountTotal . ' total patients',
                        'stat2' => $_consultationCountTotal . ' consults',
                        'bar_width' => ($_patientCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Medical Consultations',
                        'icon' => 'fa-stethoscope',
                        'color' => 'teal-600',
                        'bg' => 'bg-teal-50',
                        'total' => number_format($_consultationCountTotal) . ' completed',
                        'today' => count(array_filter($_consultationsDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_consultationCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-teal-500',
                        'badge' => $_consultationCountTotal > 0 ? 'Active Queue' : 'Empty Queue',
                        'badge_bg' => $_consultationCountTotal > 0 ? 'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_triageCountTotal . ' triage queue',
                        'stat2' => $_consultationCountTotal . ' total consults',
                        'bar_width' => ($_consultationCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Triage & Screenings',
                        'icon' => 'fa-heart-pulse',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50',
                        'total' => number_format($_triageCountTotal) . ' screened',
                        'today' => count(array_filter($_triageDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_triageCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-amber-500',
                        'badge' => $_triageCountTotal > 0 ? 'Priority Active' : 'Clear Queue',
                        'badge_bg' => $_triageCountTotal > 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_triageCountTotal . ' in queue',
                        'stat2' => $_patientCountTotal . ' screened',
                        'bar_width' => ($_triageCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Prescriptions & Pharmacy',
                        'icon' => 'fa-prescription-bottle',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50',
                        'total' => number_format($_prescriptionCountTotal) . ' dispensed',
                        'today' => count(array_filter($_prescriptionsDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_prescriptionCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-blue-500',
                        'badge' => $_prescriptionCountTotal > 0 ? 'Active' : 'No Orders',
                        'badge_bg' => $_prescriptionCountTotal > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_prescriptionCountTotal . ' active orders',
                        'stat2' => '100% fulfilled',
                        'bar_width' => ($_prescriptionCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Disease Surveillance',
                        'icon' => 'fa-binoculars',
                        'color' => 'rose-600',
                        'bg' => 'bg-rose-50',
                        'total' => number_format($_survCountTotal) . ' cases',
                        'today' => $_outbreakCountTotal . ' alerts',
                        'pct' => ($_survCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-rose-500',
                        'badge' => $_survCountTotal > 0 ? 'Monitoring' : 'Normal',
                        'badge_bg' => $_survCountTotal > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_outbreakCountTotal . ' outbreak watch',
                        'stat2' => $_survCountTotal . ' total cases',
                        'bar_width' => ($_survCountTotal > 0 ? '100' : '0') . '%'
                    ]
                ];
            } elseif ($_isSanRole) {
                $moduleSummaryCards = [
                    [
                        'name' => 'Sanitation Permits',
                        'icon' => 'fa-file-signature',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50',
                        'total' => number_format($_permitCountTotal) . ' active',
                        'today' => count(array_filter($_permitsDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_permitCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-amber-500',
                        'badge' => $_pendingPermitsCount > 0 ? ($_pendingPermitsCount . ' Pending') : 'Up to Date',
                        'badge_bg' => $_pendingPermitsCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_pendingPermitsCount . ' pending approval',
                        'stat2' => $_inspectionCountTotal . ' inspections',
                        'bar_width' => ($_permitCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Field Inspections',
                        'icon' => 'fa-search',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50',
                        'total' => number_format($_inspectionCountTotal) . ' completed',
                        'today' => count(array_filter($_inspectionsDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_inspectionCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-emerald-500',
                        'badge' => $_inspectionCountTotal > 0 ? 'Compliant' : 'No Data',
                        'badge_bg' => $_inspectionCountTotal > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_inspectionCountTotal . ' Total Inspections',
                        'stat2' => $_permitCountTotal . ' Active Permits',
                        'bar_width' => ($_inspectionCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Wastewater & Septic',
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'bg' => 'bg-purple-50',
                        'total' => number_format($_wasteCountTotal) . ' requests',
                        'today' => count(array_filter($_wastewaterDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_wasteCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-purple-500',
                        'badge' => $_wasteCountTotal > 0 ? 'Active' : 'Normal',
                        'badge_bg' => $_wasteCountTotal > 0 ? 'bg-purple-100 text-purple-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_wasteCountTotal . ' total requests',
                        'stat2' => $_wasteCountTotal . ' serviced',
                        'bar_width' => ($_wasteCountTotal > 0 ? '100' : '0') . '%'
                    ]
                ];
            } else {
                $moduleSummaryCards = [
                    [
                        'name' => 'Health Center Services',
                        'icon' => 'fa-hospital',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50',
                        'total' => number_format($_patientCountTotal) . ' total',
                        'today' => count(array_filter($_patientsDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_patientCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-emerald-500',
                        'badge' => $_patientCountTotal > 0 ? 'Active' : 'No Data',
                        'badge_bg' => $_patientCountTotal > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_patientCountTotal . ' patients',
                        'stat2' => $_consultationCountTotal . ' consults',
                        'bar_width' => ($_patientCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Sanitation Permit',
                        'icon' => 'fa-clipboard-check',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50',
                        'total' => number_format($_permitCountTotal) . ' total',
                        'today' => count(array_filter($_permitsDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_permitCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-amber-500',
                        'badge' => $_pendingPermitsCount > 0 ? ($_pendingPermitsCount . ' Pending') : 'Normal',
                        'badge_bg' => $_pendingPermitsCount > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_pendingPermitsCount . ' pending',
                        'stat2' => $_inspectionCountTotal . ' inspections',
                        'bar_width' => ($_permitCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Immunization Tracker',
                        'icon' => 'fa-syringe',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50',
                        'total' => number_format($_childCountTotal) . ' total',
                        'today' => count(array_filter($_childDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_childCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-blue-500',
                        'badge' => $_childCountTotal > 0 ? 'Active' : 'No Data',
                        'badge_bg' => $_childCountTotal > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_childCountTotal . ' children',
                        'stat2' => '100% recorded',
                        'bar_width' => ($_childCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Wastewater Services',
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'bg' => 'bg-purple-50',
                        'total' => number_format($_wasteCountTotal) . ' total',
                        'today' => count(array_filter($_wastewaterDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_wasteCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-purple-500',
                        'badge' => $_wasteCountTotal > 0 ? 'Active' : 'Normal',
                        'badge_bg' => $_wasteCountTotal > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_wasteCountTotal . ' requests',
                        'stat2' => $_wasteCountTotal . ' serviced',
                        'bar_width' => ($_wasteCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Health Surveillance',
                        'icon' => 'fa-binoculars',
                        'color' => 'rose-600',
                        'bg' => 'bg-rose-50',
                        'total' => number_format($_survCountTotal) . ' total',
                        'today' => $_outbreakCountTotal . ' alerts',
                        'pct' => ($_survCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-rose-500',
                        'badge' => $_survCountTotal > 0 ? 'Monitoring' : 'Clear',
                        'badge_bg' => $_survCountTotal > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_outbreakCountTotal . ' outbreaks',
                        'stat2' => $_survCountTotal . ' cases',
                        'bar_width' => ($_survCountTotal > 0 ? '100' : '0') . '%'
                    ]
                ];
            }
            ?>
            <div id="moduleActivitySummary" class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                        <i class="fas fa-puzzle-piece" aria-hidden="true"></i> Module Activity Summary
                    </div>
                    <a href="module_activity.php" 
                       class="text-[10px] text-c2 font-semibold hover:underline"
                       aria-label="View all modules">
                        <i class="fas fa-arrow-right text-[8px] mr-1" aria-hidden="true"></i> View All
                    </a>
                </div>
                <div class="flex-1 overflow-y-auto space-y-2.5 pr-1 custom-scroll">
                    <?php foreach ($moduleSummaryCards as $m): ?>
                    <div class="module-card p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg <?php echo $m['bg']; ?> flex items-center justify-center flex-shrink-0">
                                    <i class="fas <?php echo $m['icon']; ?> text-<?php echo $m['color']; ?> text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]"><?php echo $m['name']; ?></p>
                                    <div class="flex items-center gap-2 text-[9px] text-slate-400">
                                        <span><i class="fas fa-layer-group text-[7px] mr-0.5" aria-hidden="true"></i><?php echo $m['total']; ?></span>
                                        <span><i class="fas fa-calendar-day text-[7px] mr-0.5" aria-hidden="true"></i><?php echo $m['today']; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold text-<?php echo $m['color']; ?> block"><?php echo $m['pct']; ?></span>
                                <span class="text-[7px] text-slate-400">completion</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex gap-3 text-[8px]">
                                <span class="text-slate-600"><i class="fas fa-clock text-[6px] mr-0.5" aria-hidden="true"></i><?php echo $m['stat1']; ?></span>
                                <span class="text-slate-600"><i class="fas fa-check-circle text-[6px] mr-0.5" aria-hidden="true"></i><?php echo $m['stat2']; ?></span>
                            </div>
                            <span class="px-1.5 py-0.5 <?php echo $m['badge_bg']; ?> rounded-full text-[7px] font-bold">
                                <?php echo $m['badge']; ?>
                            </span>
                        </div>
                        <div class="mt-1.5">
                            <div class="w-full h-1 bg-slate-200 rounded overflow-hidden">
                                <div class="h-full <?php echo $m['bar']; ?> rounded" style="width:<?php echo $m['bar_width']; ?>"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- COLUMN 2: Announcements Board                               -->
            <!-- ============================================================ -->
            <div id="announcementsBoard" class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0 flex-wrap gap-2">
                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
                        <i class="fas fa-bullhorn text-c2 text-sm" aria-hidden="true"></i> Announcements Board
                        <span id="announcementsCountBadge" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[9px] font-extrabold ml-1">
                            0 Active
                        </span>
                    </div>

                    <!-- DATE RANGE & CATEGORY FILTERS -->
                    <div class="flex items-center gap-1.5">
                        <select id="announcementTimeRangeFilter" onchange="loadAnnouncements()" class="text-[10px] bg-slate-50 border border-slate-200 text-slate-700 rounded-lg px-2 py-1 focus:ring-1 focus:ring-c3 focus:border-transparent outline-none cursor-pointer font-semibold transition">
                            <option value="all" selected>All Time</option>
                            <option value="today">Today (24h)</option>
                            <option value="7days">Last 7 Days</option>
                            <option value="30days">Last 30 Days</option>
                        </select>

                        <select id="announcementCategoryFilter" onchange="loadAnnouncements()" class="text-[10px] bg-slate-50 border border-slate-200 text-slate-700 rounded-lg px-2 py-1 focus:ring-1 focus:ring-c3 focus:border-transparent outline-none cursor-pointer font-semibold transition">
                            <option value="all" selected>All Categories</option>
                            <option value="Urgent Advisory">Urgent Advisory</option>
                            <option value="Operational Notice">Operational Notice</option>
                            <option value="General Notice">General Notice</option>
                            <option value="Emergency Alert">Emergency Alert</option>
                        </select>
                    </div>
                </div>
                
                <div id="announcementsList" class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">
                    <div class="flex items-center justify-center h-32 text-xs text-slate-400 gap-2">
                        <i class="fas fa-spinner fa-spin"></i> Loading live city health announcements...
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- COLUMN 3: Role-Specific Right Panel -->
            <!-- ============================================================ -->
            <?php if ($_isHcRole): ?>
            <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                        <i class="fas fa-heart-pulse text-rose-500" aria-hidden="true"></i> Triage Status &amp; Patient Queue
                        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[9px] font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse2" aria-hidden="true"></span>
                            125 Active Today
                        </span>
                    </div>
                    <a href="<?= site_url('modules/healthservices/triage.php') ?>" class="text-[10px] text-c2 font-semibold hover:underline">
                        View Triage <i class="fas fa-arrow-right text-[8px] ml-0.5"></i>
                    </a>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">

                    <!-- Priority 1: Emergency -->
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    P1
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-rose-900">Emergency (Priority 1)</p>
                                    <p class="text-[10px] text-rose-700">Resuscitation &amp; Immediate Care</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-rose-600 text-white font-black text-xs rounded-lg shadow-sm">2 Queue</span>
                        </div>
                        <div class="mt-2 text-[9px] text-rose-800 flex items-center justify-between border-t border-rose-200/60 pt-1.5">
                            <span><i class="fas fa-clock mr-1"></i>Avg Wait: &lt; 2 mins</span>
                            <span class="font-bold text-rose-900">2 Patients Being Treated</span>
                        </div>
                    </div>

                    <!-- Priority 2: Urgent -->
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    P2
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-amber-900">Urgent (Priority 2)</p>
                                    <p class="text-[10px] text-amber-700">High-risk conditions &amp; severe pain</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-amber-500 text-white font-black text-xs rounded-lg shadow-sm">5 Queue</span>
                        </div>
                        <div class="mt-2 text-[9px] text-amber-800 flex items-center justify-between border-t border-amber-200/60 pt-1.5">
                            <span><i class="fas fa-clock mr-1"></i>Avg Wait: 15 mins</span>
                            <span class="font-bold text-amber-900">Room 3 &amp; 4 Active</span>
                        </div>
                    </div>

                    <!-- Priority 3: Non-Urgent -->
                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    P3
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-emerald-900">Standard / Non-Urgent</p>
                                    <p class="text-[10px] text-emerald-700">Routine consultation &amp; vitals check</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-600 text-white font-black text-xs rounded-lg shadow-sm">18 Queue</span>
                        </div>
                        <div class="mt-2 text-[9px] text-emerald-800 flex items-center justify-between border-t border-emerald-200/60 pt-1.5">
                            <span><i class="fas fa-clock mr-1"></i>Avg Wait: 25 mins</span>
                            <span class="font-bold text-emerald-900">5 Doctors On Duty</span>
                        </div>
                    </div>

                    <!-- Vital Signs Progress -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-notes-medical text-blue-600 text-xs"></i>
                                <span class="text-xs font-bold text-slate-800">Vital Signs Screenings</span>
                            </div>
                            <span class="text-[10px] font-bold text-blue-600">80% Completed</span>
                        </div>
                        <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-blue-600 h-full rounded-full" style="width: 80%"></div>
                        </div>
                    </div>

                </div>
            </div>
            <?php elseif ($_isSanRole): ?>
            <!-- SANITATION: Compliance & Permit Status Panel -->
            <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                        <i class="fas fa-clipboard-check text-amber-500" aria-hidden="true"></i> Sanitation Compliance Status
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[9px] font-bold flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse2" aria-hidden="true"></span>
                            <i class="fas fa-check-circle text-[8px]" aria-hidden="true"></i> Operational
                        </span>
                    </div>
                    <a href="<?= site_url('modules/sanitation/permit_applications.php') ?>" class="text-[10px] text-c2 font-semibold hover:underline">
                        View Permits <i class="fas fa-arrow-right text-[8px] ml-0.5"></i>
                    </a>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">

                    <!-- Permit Processing -->
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-file-signature text-amber-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-amber-900">Permit Processing</p>
                                    <p class="text-[10px] text-amber-700">156 active &bull; 3 pending approval</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-amber-500 text-white font-black text-xs rounded-lg shadow-sm">87%</span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-amber-200 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full" style="width:87%"></div>
                            </div>
                            <p class="text-[9px] text-amber-700 mt-1"><i class="fas fa-clock mr-1"></i>Avg. processing: 3.2 days</p>
                        </div>
                    </div>

                    <!-- Field Inspections -->
                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-search text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-emerald-900">Field Inspections</p>
                                    <p class="text-[10px] text-emerald-700">89 completed &bull; 12 scheduled today</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-600 text-white font-black text-xs rounded-lg shadow-sm">90%</span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-emerald-200 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-500 h-full rounded-full" style="width:90%"></div>
                            </div>
                            <p class="text-[9px] text-emerald-700 mt-1"><i class="fas fa-map-marker-alt mr-1"></i>2 follow-ups pending &bull; 87 passing</p>
                        </div>
                    </div>

                    <!-- Compliance Violations -->
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-gavel text-rose-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-rose-900">Compliance Violations</p>
                                    <p class="text-[10px] text-rose-700">5 corrective orders &bull; 2 unresolved</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-rose-600 text-white font-black text-xs rounded-lg shadow-sm">2 Open</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-rose-800 flex items-center justify-between border-t border-rose-200/60 pt-1.5">
                            <span><i class="fas fa-exclamation-triangle mr-1"></i>Enforcement active</span>
                            <span class="font-bold text-rose-900">3 Corrected</span>
                        </div>
                    </div>

                    <!-- Wastewater & Septic -->
                    <div class="p-3 bg-purple-50 rounded-xl border border-purple-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-water text-purple-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-purple-900">Wastewater & Septic</p>
                                    <p class="text-[10px] text-purple-700">23 requests &bull; 1,284 registered tanks</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-purple-600 text-white font-black text-xs rounded-lg shadow-sm">5 Pending</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-purple-800 flex items-center justify-between border-t border-purple-200/60 pt-1.5">
                            <span><i class="fas fa-flask mr-1"></i>Septic registry current</span>
                            <span class="font-bold text-purple-900">+4.1% this month</span>
                        </div>
                    </div>

                    <!-- Compliance Rate Summary -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-chart-line text-blue-600 text-xs"></i>
                                <span class="text-xs font-bold text-slate-800">Overall Compliance Rate</span>
                            </div>
                            <span class="text-[10px] font-bold text-blue-600">92% Compliant</span>
                        </div>
                        <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-blue-500 h-full rounded-full" style="width: 92%"></div>
                        </div>
                        <p class="text-[9px] text-slate-500 mt-1"><i class="fas fa-calendar mr-1"></i>This month vs. 88% last month</p>
                    </div>

                </div>
            </div>
            <?php elseif ($_isSurvRole): ?>
            <!-- SURVEILLANCE LEAD RIGHT PANEL (Column 3) -->
            <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                        <i class="fas fa-chart-map text-rose-500" aria-hidden="true"></i> Surveillance Map &amp; Hotspots
                        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[9px] font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse2" aria-hidden="true"></span>
                            Live Tracking
                        </span>
                    </div>
                    <a href="<?= site_url('modules/surveillence/map.php') ?>" class="text-[10px] text-c2 font-semibold hover:underline">
                        View Map <i class="fas fa-arrow-right text-[8px] ml-0.5"></i>
                    </a>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">

                    <!-- Disease Hotspots -->
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-location-dot text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-rose-900">Dengue Hotspots</p>
                                    <p class="text-[10px] text-rose-700">3 Active Clusters • 28 Cases</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-rose-600 text-white font-black text-xs rounded-lg shadow-sm">P1 Alert</span>
                        </div>
                        <div class="mt-2 text-[9px] text-rose-800 space-y-1">
                            <div class="flex justify-between">
                                <span><i class="fas fa-map-pin mr-1"></i>Brgy. San Jose</span>
                                <span class="font-bold">12 cases</span>
                            </div>
                            <div class="flex justify-between">
                                <span><i class="fas fa-map-pin mr-1"></i>Brgy. Poblacion</span>
                                <span class="font-bold">9 cases</span>
                            </div>
                            <div class="flex justify-between">
                                <span><i class="fas fa-map-pin mr-1"></i>Brgy. Santa Cruz</span>
                                <span class="font-bold">7 cases</span>
                            </div>
                        </div>
                    </div>

                    <!-- Active Investigations -->
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-magnifying-glass-chart text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-amber-900">Priority Investigations</p>
                                    <p class="text-[10px] text-amber-700">5 Critical • 42 Routine</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-amber-500 text-white font-black text-xs rounded-lg shadow-sm">47 Active</span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-amber-200 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 h-full rounded-full" style="width:72%"></div>
                            </div>
                            <p class="text-[9px] text-amber-700 mt-1"><i class="fas fa-user-md mr-1"></i>8 Field Epidemiologists deployed</p>
                        </div>
                    </div>

                    <!-- Lab Network Status -->
                    <div class="p-3 bg-blue-50 rounded-xl border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-flask-vial text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-blue-900">Laboratory Network</p>
                                    <p class="text-[10px] text-blue-700">3 Labs Active • 89 Pending</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-blue-600 text-white font-black text-xs rounded-lg shadow-sm">85% Capacity</span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-blue-200 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-blue-500 h-full rounded-full" style="width:85%"></div>
                            </div>
                            <p class="text-[9px] text-blue-700 mt-1"><i class="fas fa-clock mr-1"></i>Avg. turnaround: 48 hours</p>
                        </div>
                    </div>

                    <!-- Contact Tracing Metrics -->
                    <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-people-arrows text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-emerald-900">Contact Tracing</p>
                                    <p class="text-[10px] text-emerald-700">94% Contact Rate • 12 Tracers</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-emerald-600 text-white font-black text-xs rounded-lg shadow-sm">412 Traced</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-emerald-800 flex items-center justify-between border-t border-emerald-200/60 pt-1.5">
                            <span><i class="fas fa-phone-volume mr-1"></i>388 reached</span>
                            <span class="font-bold text-emerald-900">24 unreachable</span>
                        </div>
                    </div>

                </div>
            </div>
            <?php elseif (hasPermission('dashboard.system_admin')): ?>
            <!-- DEFAULT: System Health Status (Admin ONLY) -->
            <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                        <i class="fas fa-heartbeat" aria-hidden="true"></i> System Health Status
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[9px] font-bold flex items-center gap-1">
                            <span class="w-1 h-1 rounded-full bg-emerald-500 animate-pulse2" aria-hidden="true"></span>
                            <i class="fas fa-check-circle text-[8px]" aria-hidden="true"></i> Operational
                        </span>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">

                    <!-- Server Status -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-server text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Server Status</p>
                                    <p class="text-[10px] text-emerald-600">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> Healthy
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080]">10 min ago</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080]">
                            <i class="fas fa-clock text-[8px] mr-1" aria-hidden="true"></i> Uptime: 199d 02h 14m
                        </div>
                    </div>

                    <!-- Database -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-database text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Database</p>
                                    <p class="text-[10px] text-emerald-600">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> Healthy
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080]">1 hour ago</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080]">
                            <i class="fas fa-link text-[8px] mr-1" aria-hidden="true"></i> Connection stable • Response: 45ms
                        </div>
                    </div>

                    <!-- API Services -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-code text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">API Services</p>
                                    <p class="text-[10px] text-emerald-600">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> Healthy
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080]">2 hours ago</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080]">
                            <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> All API services running • Avg: 120ms
                        </div>
                    </div>

                    <!-- AI Engine -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-robot text-purple-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">AI Engine</p>
                                    <p class="text-[10px] text-emerald-600">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> 96% Accuracy
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080]">4 hours ago</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080]">
                            <i class="fas fa-brain text-[8px] mr-1" aria-hidden="true"></i> AI analytics engine connected
                        </div>
                    </div>

                    <!-- Backup Status -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-database text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Backup Status</p>
                                    <p class="text-[10px] text-emerald-600">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> Healthy
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080]">5 hours ago</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080]">
                            <i class="fas fa-clock text-[8px] mr-1" aria-hidden="true"></i> Last backup: Today, 2:00 AM
                        </div>
                    </div>

                    <!-- Storage Usage -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-hdd text-blue-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Storage Usage</p>
                                    <p class="text-[10px] text-blue-600">
                                        <i class="fas fa-chart-bar text-[8px] mr-1" aria-hidden="true"></i> 64% Used
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080]">Today</span>
                        </div>
                        <div class="mt-1.5">
                            <div class="flex justify-between text-[8px] text-[#4a6080] mb-0.5">
                                <span>64% used</span>
                                <span>28.4 GB / 44 GB</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-200 rounded overflow-hidden">
                                <div class="h-full bg-blue-500 rounded" style="width:64%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- View Full Report -->
                    <button class="w-full p-2.5 text-center text-[10px] font-semibold text-c2 hover:text-c3 bg-slate-50 rounded-xl border border-slate-100 hover:border-c1 transition-colors">
                        <i class="fas fa-file-alt text-[10px] mr-1" aria-hidden="true"></i> View full report →
                    </button>

                </div>
            </div>
            <?php endif; ?>


        </div>

        <!-- ============================================================ -->
        <!-- ACTIVITY FEED                                                 -->
        <!-- ============================================================ -->
        <div id="activityFeed" class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm mt-4 flex-shrink-0">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                    <i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Activity Feed
                    <span class="text-[9px] font-normal text-slate-400 ml-1">Who did what, and when</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" 
                           placeholder="Search staff or action..." 
                           class="text-[10px] border border-c1/40 rounded-lg px-2.5 py-1.5 w-44 focus:outline-none focus:ring-2 focus:ring-c2/30"
                           aria-label="Search activity log" />
                    <a href="activity-log.php" 
                       class="text-[10px] text-c2 font-semibold hover:underline whitespace-nowrap"
                       aria-label="View full activity log">
                        <i class="fas fa-arrow-right text-[8px] mr-1" aria-hidden="true"></i> View Full Log
                    </a>
                </div>
            </div>
            <div class="space-y-2">
                <?php if (empty($recentActivities)): ?>
                    <div class="p-4 text-center text-xs text-slate-400">No activity logs recorded yet.</div>
                <?php else: ?>
                    <?php foreach ($recentActivities as $act): 
                        $userName = htmlspecialchars($act['user_name'] ?? 'System');
                        $initials = strtoupper(substr($userName, 0, 2));
                        $action = htmlspecialchars($act['action'] ?? '');
                        $module = htmlspecialchars($act['module'] ?? 'System');
                        $ip = htmlspecialchars($act['ip_address'] ?? '127.0.0.1');
                        $device = htmlspecialchars($act['device'] ?? 'Desktop');
                        $role = htmlspecialchars($act['role'] ?? 'Staff');
                        $dateFormatted = !empty($act['created_at']) ? date('M j, g:i A', strtotime($act['created_at'])) : 'Just now';
                        
                        $avatarColors = ['bg-emerald-500', 'bg-blue-500', 'bg-purple-500', 'bg-amber-500', 'bg-rose-500'];
                        $avatarBg = $avatarColors[abs(crc32($userName)) % count($avatarColors)];
                    ?>
                    <div class="activity-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-semibold text-white <?php echo $avatarBg; ?>"><?php echo $initials; ?></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between flex-wrap gap-1">
                                <p class="text-xs font-semibold text-slate-800"><?php echo $userName; ?> <span class="text-[9px] font-normal text-slate-400">(<?php echo $role; ?>)</span></p>
                                <span class="text-[9px] text-slate-400"><?php echo $dateFormatted; ?></span>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap mt-0.5">
                                <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[8px] font-semibold">
                                    <i class="fas fa-history text-[7px] mr-0.5" aria-hidden="true"></i> <?php echo $action; ?>
                                </span>
                                <span class="text-[9px] text-slate-500 font-medium"><?php echo $module; ?></span>
                                <span class="text-[9px] font-mono text-slate-400 ml-auto"><i class="fas fa-network-wired text-[7px] mr-1"></i><?php echo $ip; ?> • <?php echo $device; ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>   
       <!-- ============================================================ -->
<!-- QUICK ACTION BAR - Right Aligned with Clean Animation        -->
<!-- ============================================================ -->
<?php
use App\Constants\Permissions;

$hasAnyQuickAction = hasPermission(Permissions::PATIENTS_CREATE)
    || hasPermission(Permissions::PERMITS_CREATE)
    || hasPermission(Permissions::IMMUNIZATION_CREATE)
    || hasPermission(Permissions::COMPLIANCE_VIEW)
    || hasPermission(Permissions::INSPECTIONS_CONDUCT)
    || hasPermission(Permissions::REPORTS_VIEW);
?>

<?php if ($hasAnyQuickAction): ?>
<div id="bottomActionBar" 
      class="fixed bottom-6 left-1/2 z-40 hidden lg:block transition-all duration-500 ease-out"
     style="opacity: 0 !important; transform: translateX(-50%) translateY(30px) !important; margin-left: 200px; pointer-events: none !important;">
    
    <div class="bg-white/95 backdrop-blur-xl border border-slate-200/80 shadow-2xl shadow-slate-200/50 rounded-2xl px-3 py-2 hover:shadow-2xl hover:shadow-slate-300/50 transition-all duration-300 hover:-translate-y-1">
        <div class="flex items-center gap-0.5">
            
            <!-- Label - Compact Mode -->
            <div class="flex items-center gap-1.5 px-2">
                <div class="w-1 h-1 rounded-full bg-emerald-400 animate-pulse2" aria-hidden="true"></div>
                <span class="text-[8px] font-semibold text-slate-400 tracking-wider uppercase">
                    <span class="hidden group-hover:inline">Quick </span>Actions
                </span>
                <span class="text-[7px] text-slate-300 font-mono">⌘B</span>
            </div>
            
            <div class="w-px h-6 bg-slate-200/60 mx-0.5"></div>
            
            <!-- Action 1: New Patient (RBAC Guarded) -->
            <?php if (hasPermission(Permissions::PATIENTS_CREATE)): ?>
            <button onclick="openModal('new-patient')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-emerald-50 transition-all duration-200"
                    aria-label="Register new patient"
                    data-label="Patient">
                <div class="w-7 h-7 rounded-lg bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                    <i class="fas fa-user-plus text-emerald-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-emerald-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[60px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                   New Patient
                </span>
            </button>
            <?php endif; ?>
            
            <!-- Action 2: New Permit (RBAC Guarded) -->
            <?php if (hasPermission(Permissions::PERMITS_CREATE)): ?>
            <button onclick="openModal('new-permit')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-amber-50 transition-all duration-200"
                    aria-label="Issue new sanitation permit"
                    data-label="Permit">
                <div class="w-7 h-7 rounded-lg bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                   <i class="fas fa-file-circle-plus text-amber-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-amber-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[60px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                  New Permit
                </span>
            </button>
            <?php endif; ?>
            
            <!-- Action 3: Vaccinate (RBAC Guarded - Highlighted) -->
            <?php if (hasPermission(Permissions::IMMUNIZATION_CREATE)): ?>
            <button onclick="openModal('vaccinate')" 
                    class="action-btn group relative flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 shadow-lg shadow-blue-200 transition-all duration-200 group hover:scale-105 -my-0.5"
                    aria-label="Record vaccination"
                    data-label="Vaccinate">
                <div class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center">
                    <i class="fas fa-syringe text-white text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-semibold text-white transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[70px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                    Vaccinate
                </span>
                <span class="absolute -top-1 -right-1 w-2 h-2 bg-emerald-400 rounded-full animate-pulse2"></span>
            </button>
            <?php endif; ?>
            
            <!-- Action 4: Report Case (RBAC Guarded) -->
            <?php if (hasPermission(Permissions::COMPLIANCE_VIEW)): ?>
            <button onclick="openModal('report-case')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-rose-50 transition-all duration-200"
                    aria-label="Report new health case"
                    data-label="Report">
                <div class="w-7 h-7 rounded-lg bg-rose-50 group-hover:bg-rose-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                    <i class="fas fa-flag text-rose-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-rose-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[60px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                   Flag Report
                </span>
            </button>
            <?php endif; ?>
            
            <!-- Action 5: Schedule (RBAC Guarded) -->
            <?php if (hasPermission(Permissions::INSPECTIONS_CONDUCT)): ?>
            <button onclick="openModal('schedule')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-purple-50 transition-all duration-200"
                    aria-label="Schedule inspection"
                    data-label="Schedule">
                <div class="w-7 h-7 rounded-lg bg-purple-50 group-hover:bg-purple-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                    <i class="fas fa-calendar-plus text-purple-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-purple-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[70px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                   New Schedule
                </span>
            </button>
            <?php endif; ?>

            <!-- Action 6: Post Announcement -->
            <button onclick="openPostAnnouncementModal()" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-cyan-50 transition-all duration-200 cursor-pointer"
                    aria-label="Post city health announcement"
                    data-label="Announcement">
                <div class="w-7 h-7 rounded-lg bg-cyan-50 group-hover:bg-cyan-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                    <i class="fas fa-bullhorn text-cyan-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-cyan-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[100px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                   Announcement
                </span>
            </button>
            
            <?php if (hasPermission(Permissions::REPORTS_VIEW)): ?>
            <div class="w-px h-6 bg-slate-200/60 mx-0.5"></div>
            
            <!-- Action 6: More (Dropdown RBAC Guarded) -->
            <div class="relative">
                <button onclick="toggleDesktopMenu()" 
                        class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-slate-100 transition-all duration-200"
                        aria-label="More actions"
                        id="desktopMoreBtn"
                        data-label="More">
                    <div class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-slate-200 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                        <i class="fas fa-ellipsis-h text-slate-500 text-xs" aria-hidden="true"></i>
                    </div>
                    <span class="action-label text-[9px] font-medium text-slate-500 group-hover:text-slate-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[50px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                        More
                    </span>
                    <i class="fas fa-chevron-down text-[7px] text-slate-400 ml-0.5" aria-hidden="true"></i>
                </button>
                
                <!-- Dropdown Menu -->
                <div id="desktopMoreMenu" 
                     class="absolute bottom-full right-0 mb-2 bg-white rounded-xl shadow-2xl border border-slate-100 p-2 min-w-[180px] hidden opacity-0 scale-95 transition-all duration-200"
                     style="transform-origin: bottom right;">
                    <div class="space-y-0.5">
                        <button onclick="openPostAnnouncementModal(); toggleDesktopMenu();" 
                                class="w-full text-left px-3 py-2 hover:bg-cyan-50 rounded-lg text-xs flex items-center gap-2.5 transition-colors cursor-pointer">
                            <i class="fas fa-bullhorn text-cyan-500 w-4 text-center" aria-hidden="true"></i>
                            <span class="text-slate-700">Post Announcement</span>
                        </button>
                        <button onclick="openModal('report'); toggleDesktopMenu();" 
                                class="w-full text-left px-3 py-2 hover:bg-indigo-50 rounded-lg text-xs flex items-center gap-2.5 transition-colors">
                            <i class="fas fa-file-pdf text-indigo-500 w-4 text-center" aria-hidden="true"></i>
                            <span class="text-slate-700">Generate Report</span>
                        </button>
                        <button onclick="openModal('export-data'); toggleDesktopMenu();" 
                                class="w-full text-left px-3 py-2 hover:bg-emerald-50 rounded-lg text-xs flex items-center gap-2.5 transition-colors">
                            <i class="fas fa-download text-emerald-500 w-4 text-center" aria-hidden="true"></i>
                            <span class="text-slate-700">Export Data</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- QUICK ACTION MODAL OVERLAY -->
<div id="quickActionModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden transform transition-all duration-300 scale-95 opacity-0" id="quickActionModalBox">
        
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center space-x-3">
                <div id="quickActionModalIconContainer" class="w-9 h-9 rounded-xl flex items-center justify-center bg-emerald-100 text-emerald-600">
                    <i id="quickActionModalIcon" class="fas fa-plus text-sm"></i>
                </div>
                <div>
                    <h3 id="quickActionModalTitle" class="text-base font-bold text-slate-800">Quick Action</h3>
                    <p id="quickActionModalSubtitle" class="text-xs text-slate-500">Fill in the required information to proceed</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickActionModal()" class="w-8 h-8 rounded-lg hover:bg-slate-200/60 text-slate-400 hover:text-slate-600 flex items-center justify-center transition">
                <i class="fas fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Modal Body (Dynamic Form) -->
        <form id="quickActionForm" onsubmit="handleQuickActionSubmit(event)" class="p-6 space-y-4">
            <input type="hidden" id="quickActionType" name="action_type" value="" />
            
            <div id="quickActionDynamicFields" class="space-y-4">
                <!-- Dynamic form fields injected here -->
            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeQuickActionModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" id="quickActionSubmitBtn" class="px-5 py-2 text-xs font-semibold text-white bg-brand-medium hover:bg-brand-dark rounded-xl shadow-md transition flex items-center space-x-2">
                    <span id="quickActionSubmitText">Submit Request</span>
                </button>
            </div>
        </form>
    </div>
</div>     
       
<!-- Toast container -->
<div id="toast-container" class="toast-container"></div>


<!-- ============================================================ -->
<!-- POST NEW ANNOUNCEMENT MODAL (UI/UX)                          -->
<!-- ============================================================ -->
<div id="postAnnouncementModal" class="fixed inset-0 z-[999] hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        
        <!-- MODAL HEADER -->
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
                <div class="w-8 h-8 rounded-xl bg-c3/10 text-c3 flex items-center justify-center">
                    <i class="fas fa-bullhorn text-sm"></i>
                </div>
                <span>Post New Announcement</span>
            </div>
            <button type="button" onclick="closePostAnnouncementModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>

        <!-- MODAL FORM BODY -->
        <form id="postAnnouncementForm" onsubmit="handlePostAnnouncementSubmit(event)" class="p-5 space-y-4">
            
            <!-- In-Modal Validation Error Banner -->
            <div id="postAnnouncementErrorAlert" class="hidden p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-rose-500 flex-shrink-0"></i>
                <span id="postAnnouncementErrorMessage">Please complete all required fields.</span>
            </div>

            <!-- Title -->
            <div>
                <label for="announcementTitle" class="block text-xs font-bold text-slate-700 mb-1">
                    Announcement Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" id="announcementTitle" name="title" required
                       placeholder="e.g., City-Wide Dengue Vector Control Schedule"
                       class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-c3 focus:border-transparent outline-none transition" />
            </div>

            <!-- Priority & Target Audience Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label for="announcementCategory" class="block text-xs font-bold text-slate-700 mb-1">
                        Category / Priority <span class="text-rose-500">*</span>
                    </label>
                    <select id="announcementCategory" name="category" required
                            class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-c3 focus:border-transparent outline-none transition bg-white">
                        <option value="Urgent Advisory">Urgent Advisory</option>
                        <option value="Operational Notice">Operational Notice</option>
                        <option value="General Notice" selected>General Notice</option>
                        <option value="Emergency Alert">Emergency Alert</option>
                    </select>
                </div>

                <div>
                    <label for="announcementAudience" class="block text-xs font-bold text-slate-700 mb-1">
                        Target Audience <span class="text-rose-500">*</span>
                    </label>
                    <select id="announcementAudience" name="audience" required
                            class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-c3 focus:border-transparent outline-none transition bg-white">
                        <option value="All Staff" selected>All Staff &amp; Departments</option>
                        <option value="Health Center">Health Center Staff</option>
                        <option value="Sanitation Dept">Sanitation Officers</option>
                        <option value="Surveillance">Surveillance Officers</option>
                        <option value="Immunization">Immunization Team</option>
                    </select>
                </div>
            </div>

            <!-- Content Message -->
            <div>
                <label for="announcementBody" class="block text-xs font-bold text-slate-700 mb-1">
                    Announcement Details / Message <span class="text-rose-500">*</span>
                </label>
                <textarea id="announcementBody" name="body" rows="4" required
                          placeholder="Write complete announcement details, instructions, schedules, or guidelines..."
                          class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-c3 focus:border-transparent outline-none transition resize-none"></textarea>
            </div>

            <!-- Attachment / Image Upload Preview -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Attachment / Memo Image <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <div class="border-2 border-dashed border-slate-200 rounded-xl p-3 text-center bg-slate-50 hover:bg-slate-100/80 transition cursor-pointer" onclick="document.getElementById('announcementFile').click()">
                    <i class="fas fa-cloud-arrow-up text-c2 text-lg mb-1"></i>
                    <p class="text-[10px] font-semibold text-slate-600">Click to upload official memo or flyer</p>
                    <p class="text-[8px] text-slate-400">PDF, PNG, JPG up to 5MB</p>
                    <input type="file" id="announcementFile" name="announcementFile" class="hidden" accept="image/*,.pdf" onchange="previewAnnouncementFile(this)" />
                    <input type="hidden" id="announcementFileBase64" name="file_base64" />
                </div>
                
                <!-- Live Image Upload Preview Box -->
                <div id="filePreviewContainer" class="hidden mt-2 p-2 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="flex items-center justify-between mb-1.5 px-1">
                        <span id="fileNameText" class="text-[10px] font-semibold text-slate-700 truncate max-w-[200px]"></span>
                        <button type="button" onclick="removeAnnouncementFile()" class="text-rose-500 hover:text-rose-700 text-[10px] font-bold cursor-pointer">
                            <i class="fas fa-times-circle"></i> Remove
                        </button>
                    </div>
                    <div id="imagePreviewBox" class="hidden overflow-hidden rounded-lg border border-slate-200 max-h-36 bg-slate-900/5">
                        <img id="imagePreviewImg" src="" alt="Upload Preview" class="w-full h-32 object-cover" />
                    </div>
                </div>
            </div>

            <!-- MODAL FOOTER BUTTONS -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" onclick="closePostAnnouncementModal()"
                        class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-c3 hover:bg-c3d text-white font-bold rounded-xl text-xs shadow-md transition cursor-pointer flex items-center gap-1.5">
                    <i class="fas fa-paper-plane text-xs"></i> Publish Announcement
                </button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE ANNOUNCEMENT CONFIRMATION MODAL -->
<div id="deleteAnnouncementModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden">
    <div class="bg-white rounded-2xl p-5 max-w-xs w-full shadow-2xl border border-slate-100 transform transition-all animate-scaleUp">
        <div class="w-10 h-10 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-trash-alt text-sm"></i>
        </div>
        <h3 class="text-xs font-bold text-slate-800 text-center">Delete Announcement?</h3>
        <p class="text-[10px] text-slate-500 text-center mt-1 leading-relaxed">
            Are you sure you want to delete this announcement? This action cannot be undone.
        </p>
        <div class="mt-4 flex items-center justify-end gap-2">
            <button type="button" onclick="closeDeleteAnnouncementModal()" class="w-1/2 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition cursor-pointer">
                Cancel
            </button>
            <button type="button" id="confirmDeleteAnnouncementBtn" class="w-1/2 py-2 bg-rose-600 text-white font-bold rounded-xl text-xs hover:bg-rose-700 shadow-md transition cursor-pointer flex items-center justify-center gap-1">
                <i class="fas fa-trash-alt text-xs"></i> Delete
            </button>
        </div>
    </div>
</div>

<!-- ANNOUNCEMENT IMAGE ZOOM & FULLSCREEN LIGHTBOX MODAL -->
<div id="imageZoomModal" class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-950/90 backdrop-blur-md hidden transition-all duration-300">
    <!-- Header Controls Bar -->
    <div class="w-full px-5 py-3 bg-slate-900/90 border-b border-slate-800 flex items-center justify-between z-10 text-white shadow-md">
        <div class="flex items-center gap-2">
            <i class="fas fa-image text-c2 text-sm"></i>
            <span id="zoomModalTitle" class="text-xs font-bold truncate max-w-xs sm:max-w-md text-slate-200">Announcement Image Viewer</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="zoomOutImage()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition cursor-pointer text-xs" title="Zoom Out">
                <i class="fas fa-minus"></i>
            </button>
            <span id="zoomLevelBadge" class="text-[10px] font-mono text-slate-300 px-1.5 font-bold">100%</span>
            <button onclick="zoomInImage()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center transition cursor-pointer text-xs" title="Zoom In">
                <i class="fas fa-plus"></i>
            </button>
            <button onclick="resetZoomImage()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition cursor-pointer text-xs" title="Reset Zoom">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button onclick="toggleFullscreenImage()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition cursor-pointer text-xs ml-1" title="Fullscreen Mode">
                <i class="fas fa-expand"></i>
            </button>
            <button onclick="closeImageZoomModal()" class="w-8 h-8 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white flex items-center justify-center transition cursor-pointer text-xs ml-2" title="Close Viewer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- Image Viewport -->
    <div id="zoomViewport" class="flex-1 w-full overflow-auto flex items-center justify-center p-4 cursor-grab active:cursor-grabbing select-none" onclick="closeImageZoomModalOnBg(event)">
        <img id="zoomModalImage" src="" alt="Announcement Full Preview" class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl transition-transform duration-150 ease-out" />
    </div>
</div>

<script>
window.USER_PERMISSIONS = <?= json_encode(getUserGrantedPermissions()) ?>;
window.IS_ADMIN = <?= (hasPermission(App\Constants\Permissions::ROLES_MANAGE) || getPermissionService()->isAdminRole($_SESSION['role'] ?? '')) ? 'true' : 'false' ?>;
window.SUPABASE_CONFIG = {
    url: <?= json_encode(Env::get('SUPABASE_URL')) ?>,
    anonKey: <?= json_encode(Env::get('SUPABASE_KEY')) ?>
};
</script>
<script src="<?= site_url('assets/js/dashboard-app.js') ?>"></script>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>