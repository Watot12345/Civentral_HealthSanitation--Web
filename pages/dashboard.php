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

// Determine user role and permissions for Activity Feed scoping
$currentUserRole = $_SESSION['user_role'] ?? $_SESSION['role_description'] ?? $_SESSION['role'] ?? 'Staff';
$currentUserId   = (int)($_SESSION['user_id'] ?? 0);
$currentDept     = strtolower((string)($_SESSION['department'] ?? $_SESSION['user_department'] ?? ''));

$roleLower = strtolower($currentUserRole);
$isSysAdmin = str_contains($roleLower, 'admin') || str_contains($roleLower, 'system administrator');
$isHeadRole = str_contains($roleLower, 'director') 
           || str_contains($roleLower, 'head') 
           || str_contains($roleLower, 'coordinator') 
           || str_contains($roleLower, 'supervisor') 
           || str_contains($roleLower, 'chief')
           || str_contains($roleLower, 'manager');

// Staff members do not have an activity feed; only Heads and System Admins see it
$canViewActivityFeed = $isSysAdmin || $isHeadRole;

$recentActivities = [];

if ($canViewActivityFeed) {
    $activityLogModel = new ActivityLog();
    // Fetch logs to filter
    $allDashboardLogs = $activityLogModel->all(['limit' => 60]);
    
    $recentActivities = array_values(array_filter($allDashboardLogs, function($log) use ($isSysAdmin, $roleLower, $currentDept) {
        $module = strtolower($log['module'] ?? '');
        $action = strtolower($log['action'] ?? '');
        $desc   = strtolower($log['description'] ?? ($log['details'] ?? ''));
        $logRole = strtolower($log['role'] ?? '');

        // 1. STRICTLY EXCLUDE all Authentication & Login/Logout/Session events
        if (
            str_contains($module, 'auth') ||
            str_contains($action, 'login') ||
            str_contains($action, 'logout') ||
            str_contains($action, 'sign-in') ||
            str_contains($action, 'sign-out') ||
            str_contains($desc, 'logged in') ||
            str_contains($desc, 'logged out') ||
            str_contains($desc, 'otp') ||
            str_contains($desc, 'password') ||
            str_contains($action, '2fa')
        ) {
            return false;
        }

        // 2. ONLY INCLUDE Operational Modules & Main Controls
        $allowedOperationalModules = [
            'patients', 'patient', 'health center', 'consultation', 'consultations', 'triage',
            'immunization', 'vaccination', 'vaccines', 'nutrition', 'growth',
            'permits', 'sanitation', 'inspections', 'sanitation permits',
            'surveillance', 'case reports', 'epidemiology', 'outbreak',
            'wastewater', 'septic tanks', 'billing', 'services',
            'announcements', 'announcement', 'inventory', 'pharmacy', 'prescriptions',
            'reports', 'report generation', 'export', 'users', 'roles', 'settings', 'system settings', 'user management'
        ];

        $isOperational = false;
        foreach ($allowedOperationalModules as $modKey) {
            if (str_contains($module, $modKey) || str_contains($action, $modKey) || str_contains($desc, $modKey)) {
                $isOperational = true;
                break;
            }
        }
        if (!$isOperational && !in_array($module, ['system', 'general', 'audit', 'operations', 'main controls'])) {
            return false;
        }

        // 3. System Administrator sees all operational activities across all roles
        if ($isSysAdmin) {
            return true;
        }

        // 4. Department Heads / Coordinators see actions of staff within their domain
        // Health Center Director
        if (str_contains($roleLower, 'health center') || str_contains($currentDept, 'health')) {
            return str_contains($module, 'patient') 
                || str_contains($module, 'consult') 
                || str_contains($module, 'triage') 
                || str_contains($module, 'immuniz') 
                || str_contains($module, 'vaccin') 
                || str_contains($module, 'nutri') 
                || str_contains($module, 'prescript')
                || str_contains($module, 'health')
                || str_contains($logRole, 'doctor')
                || str_contains($logRole, 'nurse')
                || str_contains($logRole, 'dentist')
                || str_contains($logRole, 'nutritionist')
                || str_contains($logRole, 'midwife')
                || str_contains($logRole, 'clerk');
        }

        // Sanitation Director
        if (str_contains($roleLower, 'sanitation') || str_contains($currentDept, 'sanitation')) {
            return str_contains($module, 'permit') 
                || str_contains($module, 'inspect') 
                || str_contains($module, 'sanitation') 
                || str_contains($module, 'waste') 
                || str_contains($module, 'septic')
                || str_contains($module, 'bill')
                || str_contains($logRole, 'inspector')
                || str_contains($logRole, 'permit')
                || str_contains($logRole, 'cashier')
                || str_contains($logRole, 'wastewater');
        }

        // Immunization Coordinator
        if (str_contains($roleLower, 'immuniz') || str_contains($currentDept, 'immuniz') || str_contains($currentDept, 'nutrition')) {
            return str_contains($module, 'immuniz') 
                || str_contains($module, 'vaccin') 
                || str_contains($module, 'nutri') 
                || str_contains($module, 'growth')
                || str_contains($logRole, 'midwife')
                || str_contains($logRole, 'nutrition')
                || str_contains($logRole, 'nurse');
        }

        // Surveillance Coordinator
        if (str_contains($roleLower, 'surveillance') || str_contains($currentDept, 'surveillance')) {
            return str_contains($module, 'surveill') 
                || str_contains($module, 'case') 
                || str_contains($module, 'epidemiol') 
                || str_contains($module, 'outbreak')
                || str_contains($logRole, 'surveillance');
        }

        return true;
    }));

    $recentActivities = array_slice($recentActivities, 0, 7);
}

// Fetch real Supabase cloud storage and database health metrics
require_once __DIR__ . '/../config/database.php';
$dashDb = Database::getInstance();
$sbStorageMetrics = $dashDb->getStorageMetrics();
$sbDbMetrics = $dashDb->getDatabaseMetrics();

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
            } elseif (hasPermission('dashboard.wastewater') || str_contains(strtolower($currentRole), 'wastewater') || (isset($_SESSION['department']) && (strcasecmp($_SESSION['department'], 'wastewater') === 0 || strcasecmp($_SESSION['department'], 'wastewater services') === 0))) {
                $dashTitle = 'Wastewater Management Dashboard';
                $dashSubtitle = 'Septic tank registry, desludging operations, service requests & environmental billing';
                $dashBadge = 'Wastewater Officer';
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
$_isWasteRole = !$isSysAdmin && !$_isSurvRole && (
    hasPermission('dashboard.wastewater')
    || str_contains(strtolower($currentRole), 'wastewater')
    || (isset($_SESSION['department']) && (strcasecmp($_SESSION['department'], 'wastewater') === 0 || strcasecmp($_SESSION['department'], 'wastewater services') === 0))
);

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
$_septicDbRecords        = $_metricsData['raw']['septic_tanks'] ?? $_wastewaterDbRecords;
$_serviceReqDbRecords    = $_metricsData['raw']['service_requests'] ?? [];
$_maintenanceDbRecords   = $_metricsData['raw']['maintenance_records'] ?? [];
$_invoicesDbRecords      = $_metricsData['raw']['wastewater_invoices'] ?? [];
$_providersDbRecords     = $_metricsData['raw']['service_providers'] ?? [];
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
$_septicCountTotal        = count($_septicDbRecords);
$_serviceReqCountTotal    = count($_serviceReqDbRecords);
$_pendingWasteReqs        = count(array_filter($_serviceReqDbRecords, fn($r) => in_array(strtolower($r['status'] ?? ''), ['pending', 'open'])));
$_maintCountTotal         = count($_maintenanceDbRecords);
$_invoicesCountTotal      = count($_invoicesDbRecords);
$_pendingInvoicesCount    = count(array_filter($_invoicesDbRecords, fn($inv) => in_array(strtolower($inv['status'] ?? ''), ['pending', 'unpaid', 'due'])));
$_providersCountTotal     = count($_providersDbRecords);
$_activeProvidersCount    = count(array_filter($_providersDbRecords, fn($p) => strcasecmp($p['status'] ?? '', 'active') === 0));
$_criticalTanksCount      = count(array_filter($_septicDbRecords, fn($t) => in_array(strtolower($t['status'] ?? ''), ['critical', 'needs_maintenance'])));
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
} elseif ($_isWasteRole) {
    $_septicRingData    = $_calcRingPctData($_septicDbRecords, fn($t) => in_array(strtolower($t['status'] ?? ''), ['good', 'active', 'inspected']));
    $_serviceReqRingData= $_calcRingPctData($_serviceReqDbRecords, fn($r) => in_array(strtolower($r['status'] ?? ''), ['completed', 'approved', 'in_progress']));
    $_maintRingData     = $_calcRingPctData($_maintenanceDbRecords, fn($m) => in_array(strtolower($m['status'] ?? ''), ['completed']));
    $_invoiceRingData   = $_calcRingPctData($_invoicesDbRecords, fn($i) => in_array(strtolower($i['status'] ?? ''), ['paid']));
    $_providerRingData  = $_calcRingPctData($_providersDbRecords, fn($p) => strcasecmp($p['status'] ?? '', 'active') === 0);

    $kpiCards = [
        [
            'title' => 'Septic Registry',
            'value' => number_format($_septicCountTotal),
            'label' => 'Registered Septic Tanks',
            'badge' => $_calcGrowthBadge($_septicDbRecords),
            'badge_bg' => 'bg-purple-100 text-purple-700',
            'sub' => 'active registry',
            'url' => site_url('modules/services/septic_tanks.php'),
            'icon' => 'fa-water',
            'color' => 'purple-600',
            'border_color' => 'from-purple-400 to-purple-600',
            'offset' => $_septicRingData['offset'],
            'pct' => $_septicRingData['pct']
        ],
        [
            'title' => 'Service Requests',
            'value' => number_format($_serviceReqCountTotal),
            'label' => 'Desludging Requests',
            'badge' => $_pendingWasteReqs > 0 ? ($_pendingWasteReqs . ' pending') : 'No pending',
            'badge_bg' => $_pendingWasteReqs > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
            'sub' => $_serviceReqRingData['pct'] . ' fulfillment',
            'url' => site_url('modules/services/service_requests.php'),
            'icon' => 'fa-tools',
            'color' => 'sky-600',
            'border_color' => 'from-sky-400 to-sky-600',
            'offset' => $_serviceReqRingData['offset'],
            'pct' => $_serviceReqRingData['pct']
        ],
        [
            'title' => 'Desludging Ops',
            'value' => number_format($_maintCountTotal),
            'label' => 'Maintenance Operations',
            'badge' => $_calcGrowthBadge($_maintenanceDbRecords),
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'trips logged',
            'url' => site_url('modules/services/maintenance.php'),
            'icon' => 'fa-truck-droplet',
            'color' => 'emerald-600',
            'border_color' => 'from-emerald-400 to-emerald-600',
            'offset' => $_maintRingData['offset'],
            'pct' => $_maintRingData['pct']
        ],
        [
            'title' => 'Wastewater Billing',
            'value' => number_format($_invoicesCountTotal),
            'label' => 'Invoices & Surcharges',
            'badge' => $_pendingInvoicesCount > 0 ? ($_pendingInvoicesCount . ' unpaid') : 'All Paid',
            'badge_bg' => $_pendingInvoicesCount > 0 ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700',
            'sub' => $_invoiceRingData['pct'] . ' collected',
            'url' => site_url('modules/services/wastewater_billing.php'),
            'icon' => 'fa-file-invoice-dollar',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => $_invoiceRingData['offset'],
            'pct' => $_invoiceRingData['pct']
        ],
        [
            'title' => 'Service Providers',
            'value' => number_format($_providersCountTotal),
            'label' => 'Accredited Contractors',
            'badge' => $_activeProvidersCount . ' Active',
            'badge_bg' => 'bg-teal-100 text-teal-700',
            'sub' => 'fleet compliance',
            'url' => site_url('modules/services/providers.php'),
            'icon' => 'fa-handshake',
            'color' => 'teal-600',
            'border_color' => 'from-teal-400 to-teal-600',
            'offset' => $_providerRingData['offset'],
            'pct' => $_providerRingData['pct']
        ],
        [
            'title' => 'Critical Attention',
            'value' => (string)$_criticalTanksCount,
            'label' => 'Tanks Needing Action',
            'badge' => $_criticalTanksCount > 0 ? ($_criticalTanksCount . ' Urgent') : 'All Normal',
            'badge_bg' => $_criticalTanksCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700',
            'sub' => 'spill prevention',
            'url' => site_url('modules/services/septic_tanks.php'),
            'icon' => 'fa-triangle-exclamation',
            'color' => 'rose-600',
            'border_color' => 'from-rose-400 to-rose-600',
            'offset' => $_criticalTanksCount > 0 ? '10' : '100',
            'pct' => $_criticalTanksCount > 0 ? '100%' : '0%'
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
            } elseif ($_isWasteRole) {
                $moduleSummaryCards = [
                    [
                        'name' => 'Septic Tank Registry',
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'bg' => 'bg-purple-50',
                        'total' => number_format($_septicCountTotal) . ' registered',
                        'today' => count(array_filter($_septicDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_septicCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-purple-500',
                        'badge' => $_criticalTanksCount > 0 ? ($_criticalTanksCount . ' Critical') : 'Normal',
                        'badge_bg' => $_criticalTanksCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_septicCountTotal . ' total tanks',
                        'stat2' => $_criticalTanksCount . ' attention needed',
                        'bar_width' => ($_septicCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Desludging Requests',
                        'icon' => 'fa-tools',
                        'color' => 'sky-600',
                        'bg' => 'bg-sky-50',
                        'total' => number_format($_serviceReqCountTotal) . ' requests',
                        'today' => count(array_filter($_serviceReqDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_serviceReqCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-sky-500',
                        'badge' => $_pendingWasteReqs > 0 ? ($_pendingWasteReqs . ' Pending') : 'Clear Queue',
                        'badge_bg' => $_pendingWasteReqs > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_pendingWasteReqs . ' pending dispatch',
                        'stat2' => ($_serviceReqCountTotal - $_pendingWasteReqs) . ' fulfilled',
                        'bar_width' => ($_serviceReqCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Maintenance Operations',
                        'icon' => 'fa-truck-droplet',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50',
                        'total' => number_format($_maintCountTotal) . ' completed',
                        'today' => count(array_filter($_maintenanceDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_maintCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-emerald-500',
                        'badge' => $_maintCountTotal > 0 ? 'Active Fleet' : 'Scheduled',
                        'badge_bg' => $_maintCountTotal > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_maintCountTotal . ' total trips',
                        'stat2' => '100% compliant',
                        'bar_width' => ($_maintCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Wastewater Billing',
                        'icon' => 'fa-file-invoice-dollar',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50',
                        'total' => number_format($_invoicesCountTotal) . ' invoices',
                        'today' => count(array_filter($_invoicesDbRecords, fn($r) => !empty($r['created_at']) && strtotime($r['created_at']) >= strtotime('today midnight'))) . ' today',
                        'pct' => ($_invoicesCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-blue-500',
                        'badge' => $_pendingInvoicesCount > 0 ? ($_pendingInvoicesCount . ' Unpaid') : 'Settled',
                        'badge_bg' => $_pendingInvoicesCount > 0 ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700',
                        'stat1' => $_pendingInvoicesCount . ' pending fees',
                        'stat2' => ($_invoicesCountTotal - $_pendingInvoicesCount) . ' paid',
                        'bar_width' => ($_invoicesCountTotal > 0 ? '100' : '0') . '%'
                    ],
                    [
                        'name' => 'Accredited Providers',
                        'icon' => 'fa-handshake',
                        'color' => 'teal-600',
                        'bg' => 'bg-teal-50',
                        'total' => number_format($_providersCountTotal) . ' contractors',
                        'today' => $_activeProvidersCount . ' active',
                        'pct' => ($_providersCountTotal > 0 ? '100' : '0') . '%',
                        'bar' => 'bg-teal-500',
                        'badge' => $_activeProvidersCount > 0 ? 'Accredited' : 'Reviewing',
                        'badge_bg' => $_activeProvidersCount > 0 ? 'bg-teal-100 text-teal-700' : 'bg-slate-100 text-slate-600',
                        'stat1' => $_activeProvidersCount . ' active providers',
                        'stat2' => 'DENR licensed',
                        'bar_width' => ($_providersCountTotal > 0 ? '100' : '0') . '%'
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
            <?php elseif ($_isWasteRole): ?>
            <!-- WASTEWATER: Operations & Compliance Status Panel -->
            <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                        <i class="fas fa-droplet text-purple-600" aria-hidden="true"></i> Wastewater &amp; Desludging Status
                        <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[9px] font-bold flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-pulse2" aria-hidden="true"></span>
                            Live Ops
                        </span>
                    </div>
                    <a href="<?= site_url('modules/services/maintenance.php') ?>" class="text-[10px] text-c2 font-semibold hover:underline">
                        View Trips <i class="fas fa-arrow-right text-[8px] ml-0.5"></i>
                    </a>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">

                    <!-- Critical Attention Tanks -->
                    <div class="p-3 bg-rose-50 rounded-xl border border-rose-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-triangle-exclamation text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-rose-900">Urgent Desludging Queue</p>
                                    <p class="text-[10px] text-rose-700"><?= $_criticalTanksCount; ?> tanks requiring immediate action</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-rose-600 text-white font-black text-xs rounded-lg shadow-sm"><?= $_criticalTanksCount > 0 ? ($_criticalTanksCount . ' Urgent') : 'Clear'; ?></span>
                        </div>
                        <div class="mt-2 text-[9px] text-rose-800 flex items-center justify-between border-t border-rose-200/60 pt-1.5">
                            <span><i class="fas fa-clock mr-1"></i>Avg. response: &lt; 24h</span>
                            <span class="font-bold text-rose-900"><?= $_pendingWasteReqs; ?> pending requests</span>
                        </div>
                    </div>

                    <!-- Active Maintenance & Desludging Dispatches -->
                    <div class="p-3 bg-purple-50 rounded-xl border border-purple-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-truck-droplet text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-purple-900">Field Desludging Trips</p>
                                    <p class="text-[10px] text-purple-700"><?= number_format($_maintCountTotal); ?> operations completed</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-purple-600 text-white font-black text-xs rounded-lg shadow-sm"><?= $_maintRingData['pct']; ?></span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-purple-200 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-purple-600 h-full rounded-full" style="width: <?= $_maintRingData['pct']; ?>"></div>
                            </div>
                            <p class="text-[9px] text-purple-700 mt-1"><i class="fas fa-route mr-1"></i>Active fleet routes in Caloocan North &amp; South</p>
                        </div>
                    </div>

                    <!-- Service Providers & Contractors -->
                    <div class="p-3 bg-teal-50 rounded-xl border border-teal-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-handshake text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-teal-900">Accredited Providers</p>
                                    <p class="text-[10px] text-teal-700"><?= $_activeProvidersCount; ?> active licensed contractors</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-teal-600 text-white font-black text-xs rounded-lg shadow-sm">Certified</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-teal-800 flex items-center justify-between border-t border-teal-200/60 pt-1.5">
                            <span><i class="fas fa-award mr-1"></i>DENR / LLDA Compliant</span>
                            <span class="font-bold text-teal-900">100% Quality Audited</span>
                        </div>
                    </div>

                    <!-- Environmental & Surcharge Invoices -->
                    <div class="p-3 bg-blue-50 rounded-xl border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center flex-shrink-0 font-black text-xs">
                                    <i class="fas fa-file-invoice-dollar text-xs"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-blue-900">Environmental Invoices</p>
                                    <p class="text-[10px] text-blue-700"><?= number_format($_invoicesCountTotal); ?> total &bull; <?= $_pendingInvoicesCount; ?> pending collection</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-blue-600 text-white font-black text-xs rounded-lg shadow-sm"><?= $_invoiceRingData['pct']; ?></span>
                        </div>
                        <div class="mt-2">
                            <div class="w-full bg-blue-200 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-blue-600 h-full rounded-full" style="width: <?= $_invoiceRingData['pct']; ?>"></div>
                            </div>
                            <p class="text-[9px] text-blue-700 mt-1"><i class="fas fa-receipt mr-1"></i><?= $_invoiceRingData['pct']; ?> fee settlement efficiency</p>
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

                    <!-- Database (Supabase PostgreSQL) -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-database text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Database</p>
                                    <p class="text-[10px] text-emerald-600 font-medium">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> <?= ($sbDbMetrics['status'] ?? '') === 'healthy' ? 'Healthy' : 'Operational'; ?>
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080] font-medium">PostgreSQL</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080] flex items-center justify-between">
                            <span><i class="fas fa-link text-[8px] mr-1 text-emerald-500" aria-hidden="true"></i> <?= htmlspecialchars((string)($sbDbMetrics['total_records'] ?? 530)); ?> Live Records (<?= htmlspecialchars((string)($sbDbMetrics['active_tables_count'] ?? 33)); ?> tables)</span>
                            <span class="font-semibold text-emerald-600"><?= htmlspecialchars((string)($sbDbMetrics['latency_ms'] ?? 45)); ?>ms</span>
                        </div>
                    </div>

                    <!-- API Services (Supabase REST / Realtime) -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-code text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">API Services</p>
                                    <p class="text-[10px] text-emerald-600 font-medium">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> Supabase REST
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080] font-medium">Active</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080]">
                            <i class="fas fa-bolt text-[8px] mr-1 text-amber-500" aria-hidden="true"></i> Realtime WebSocket synced • Endpoints responding
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
                                    <p class="text-[10px] text-emerald-600 font-medium">
                                        <i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> Operational
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-[#4a6080] font-medium">Gemini ML</span>
                        </div>
                        <div class="mt-1.5 text-[9px] text-[#4a6080]">
                            <i class="fas fa-brain text-[8px] mr-1 text-purple-500" aria-hidden="true"></i> Predictive analytics & outbreak models online
                        </div>
                    </div>

                    <!-- Supabase Cloud Storage (Real & Live) -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-cloud text-blue-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Supabase Storage</p>
                                    <p class="text-[10px] text-blue-600 font-semibold">
                                        <i class="fas fa-chart-pie text-[8px] mr-1" aria-hidden="true"></i> <?= number_format($sbStorageMetrics['usage_percent'] ?? 0.01, 2); ?>% Used
                                    </p>
                                </div>
                            </div>
                            <span class="text-[9px] text-slate-500 font-medium"><?= htmlspecialchars((string)($sbStorageMetrics['total_files'] ?? 1)); ?> file<?= ($sbStorageMetrics['total_files'] ?? 1) == 1 ? '' : 's'; ?></span>
                        </div>
                        <div class="mt-1.5">
                            <div class="flex justify-between text-[8px] text-[#4a6080] mb-0.5 font-medium">
                                <span><?= htmlspecialchars((string)($sbStorageMetrics['total_formatted'] ?? '79.8 KB')); ?> used</span>
                                <span><?= htmlspecialchars((string)($sbStorageMetrics['quota_formatted'] ?? '1 GB')); ?> quota</span>
                            </div>
                            <div class="w-full h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-blue-600 rounded-full transition-all duration-500" style="width:<?= max(2, min(100, (float)($sbStorageMetrics['usage_percent'] ?? 0.01))); ?>%"></div>
                            </div>
                            <div class="text-[8px] text-slate-400 mt-1 flex items-center justify-between">
                                <span><?= htmlspecialchars((string)($sbStorageMetrics['buckets_count'] ?? 6)); ?> cloud storage buckets active</span>
                                <span class="text-emerald-600 font-semibold flex items-center gap-0.5"><i class="fas fa-check text-[7px]"></i> Healthy</span>
                            </div>
                        </div>
                    </div>

                    <!-- View Full Settings / System Health Report -->
                    <a href="<?= site_url('management/settings.php'); ?>" class="block w-full p-2.5 text-center text-[10px] font-semibold text-c2 hover:text-c3 bg-slate-50 rounded-xl border border-slate-100 hover:border-c1 transition-colors">
                        <i class="fas fa-server text-[10px] mr-1" aria-hidden="true"></i> Open System Configuration & Storage →
                    </a>

                </div>
            </div>
            <?php endif; ?>


        </div>

        <?php if ($canViewActivityFeed): ?>
        <!-- ============================================================ -->
        <!-- ACTIVITY FEED (Leadership & Head Roles Only)                 -->
        <!-- ============================================================ -->
        <div id="activityFeed" class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm mt-4 flex-shrink-0">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                    <i class="fas fa-clock-rotate-left" aria-hidden="true"></i> Activity Feed
                    <span class="text-[9px] font-normal text-slate-400 ml-1">Staff & Operational Actions</span>
                </div>
                <div class="flex items-center gap-2">
                    <input type="text" 
                           id="activitySearchInput"
                           oninput="filterActivityFeed(this.value)"
                           placeholder="Search staff or action..." 
                           class="text-[10px] border border-c1/40 rounded-lg px-2.5 py-1.5 w-44 focus:outline-none focus:ring-2 focus:ring-c2/30"
                           aria-label="Search activity log" />
                    <a href="<?= site_url('management/system_logs.php'); ?>" 
                       class="text-[10px] text-c2 font-semibold hover:underline whitespace-nowrap"
                       aria-label="View full activity log">
                        <i class="fas fa-arrow-right text-[8px] mr-1" aria-hidden="true"></i> View Full Log
                    </a>
                </div>
            </div>
            <div id="activityFeedList" class="space-y-2">
                <?php if (empty($recentActivities)): ?>
                    <div class="py-8 px-4 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200">
                        <div class="w-12 h-12 rounded-2xl bg-white shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-2.5 text-slate-400">
                            <i class="fas fa-clipboard-list text-lg text-slate-400"></i>
                        </div>
                        <h4 class="text-xs font-bold text-slate-700">No Recent Operational Activity</h4>
                        <p class="text-[11px] text-slate-400 max-w-sm mx-auto mt-1">Staff activities in clinical services, sanitation inspections, and disease surveillance will appear here automatically.</p>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-medium mt-3 border border-emerald-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live Operational Monitoring Active
                        </span>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivities as $act): 
                        $userName = htmlspecialchars($act['user_name'] ?? 'Staff Member');
                        $initials = strtoupper(substr($userName, 0, 2));
                        $action = htmlspecialchars($act['action'] ?? 'Operational Action');
                        $module = htmlspecialchars($act['module'] ?? 'Operations');
                        $ip = htmlspecialchars($act['ip_address'] ?? '127.0.0.1');
                        $device = htmlspecialchars($act['device'] ?? 'Workstation');
                        $role = htmlspecialchars($act['role'] ?? 'Staff');
                        $dateFormatted = !empty($act['created_at']) ? date('M j, g:i A', strtotime($act['created_at'])) : 'Recent';
                        
                        $avatarColors = ['bg-emerald-500', 'bg-blue-500', 'bg-purple-500', 'bg-amber-500', 'bg-rose-500', 'bg-teal-500', 'bg-indigo-500'];
                        $avatarBg = $avatarColors[abs(crc32($userName)) % count($avatarColors)];
                    ?>
                    <div class="activity-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition" data-search="<?= strtolower($userName . ' ' . $action . ' ' . $module . ' ' . $role); ?>">
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
        <?php endif; ?>

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
    || hasPermission(Permissions::REPORTS_VIEW)
    || $_isWasteRole
    || hasPermission(Permissions::WASTEWATER_CREATE)
    || hasPermission(Permissions::WASTEWATER_MANAGE);
?>

<?php if ($hasAnyQuickAction): ?>
<div id="bottomActionBar" 
      class="fixed bottom-6 left-1/2 z-40 hidden lg:block transition-all duration-500 ease-out"
     style="opacity: 0; transform: translateX(-50%) translateY(30px); margin-left: 120px; pointer-events: none;">
    
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
            <button type="button" onclick="openQuickModal('quickModalNewPatient')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-emerald-50 transition-all duration-200 cursor-pointer"
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
            <button type="button" onclick="openQuickModal('quickModalNewPermit')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-amber-50 transition-all duration-200 cursor-pointer"
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
            <button type="button" onclick="openQuickModal('quickModalVaccinate')" 
                    class="action-btn group relative flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 shadow-lg shadow-blue-200 transition-all duration-200 group hover:scale-105 -my-0.5 cursor-pointer"
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
            <button type="button" onclick="openQuickModal('quickModalReportCase')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-rose-50 transition-all duration-200 cursor-pointer"
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
            <button type="button" onclick="openQuickModal('quickModalSchedule')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-purple-50 transition-all duration-200 cursor-pointer"
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

            <!-- Action Wastewater 1: Desludging Request -->
            <?php if ($_isWasteRole || hasPermission(Permissions::WASTEWATER_CREATE) || hasPermission(Permissions::WASTEWATER_MANAGE)): ?>
            <button type="button" onclick="openQuickModal('quickModalNewWasteRequest')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-sky-50 transition-all duration-200 cursor-pointer"
                    aria-label="New desludging request"
                    data-label="Desludge">
                <div class="w-7 h-7 rounded-lg bg-sky-50 group-hover:bg-sky-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                    <i class="fas fa-truck-droplet text-sky-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-sky-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[75px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                   Desludge Req
                </span>
            </button>

            <!-- Action Wastewater 2: Register Septic Tank -->
            <button type="button" onclick="openQuickModal('quickModalNewSepticTank')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-purple-50 transition-all duration-200 cursor-pointer"
                    aria-label="Register septic tank"
                    data-label="Septic">
                <div class="w-7 h-7 rounded-lg bg-purple-50 group-hover:bg-purple-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                    <i class="fas fa-water text-purple-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-purple-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[65px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                   New Tank
                </span>
            </button>
            <?php endif; ?>

            <!-- Action 6: Post Announcement -->
            <button type="button" onclick="openPostAnnouncementModal()" 
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
            
            <!-- Action 7: More (Dropdown RBAC Guarded) -->
            <div class="relative">
                <button type="button" onclick="toggleDesktopMenu()" 
                        class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-slate-100 transition-all duration-200 cursor-pointer"
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
                        <button type="button" onclick="openPostAnnouncementModal(); toggleDesktopMenu();" 
                                class="w-full text-left px-3 py-2 hover:bg-cyan-50 rounded-lg text-xs flex items-center gap-2.5 transition-colors cursor-pointer">
                            <i class="fas fa-bullhorn text-cyan-500 w-4 text-center" aria-hidden="true"></i>
                            <span class="text-slate-700">Post Announcement</span>
                        </button>
                        <a href="<?= site_url('pages/custom_report.php') ?>" 
                           class="w-full text-left px-3 py-2 hover:bg-indigo-50 rounded-lg text-xs flex items-center gap-2.5 transition-colors">
                            <i class="fas fa-file-pdf text-indigo-500 w-4 text-center" aria-hidden="true"></i>
                            <span class="text-slate-700">Generate Report</span>
                        </a>
                        <a href="<?= site_url('pages/export.php') ?>" 
                           class="w-full text-left px-3 py-2 hover:bg-emerald-50 rounded-lg text-xs flex items-center gap-2.5 transition-colors">
                            <i class="fas fa-download text-emerald-500 w-4 text-center" aria-hidden="true"></i>
                            <span class="text-slate-700">Export Data</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================ -->
<!-- 1. QUICK ACTION MODAL: REGISTER NEW PATIENT                 -->
<!-- ============================================================ -->
<div id="quickModalNewPatient" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i class="fas fa-user-plus text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Register New Patient</h3>
                    <p class="text-[11px] text-slate-500">Quickly register a patient profile</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickModal('quickModalNewPatient')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickPatientForm" onsubmit="handleQuickPatientSubmit(event)" class="p-5 space-y-3.5">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">First Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="first_name" required placeholder="e.g. Maria Clara" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Last Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="last_name" required placeholder="e.g. Santos" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Birth Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="birth_date" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Gender <span class="text-rose-500">*</span></label>
                    <select name="gender" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none bg-white">
                        <option value="Female">Female</option>
                        <option value="Male">Male</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Contact Number <span class="text-rose-500">*</span></label>
                    <input type="text" name="contact" required placeholder="0917-123-4567" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Barangay <span class="text-rose-500">*</span></label>
                    <input type="text" name="barangay" required placeholder="Barangay 1, Caloocan" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none" />
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Allergies / Medical Notes</label>
                <input type="text" name="allergies" placeholder="e.g. Penicillin allergy, Hypertension" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-emerald-500 outline-none" />
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickModal('quickModalNewPatient')" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="btnQuickPatient" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-check text-xs"></i> Save Patient Profile
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 2. QUICK ACTION MODAL: NEW SANITATION PERMIT                 -->
<!-- ============================================================ -->
<div id="quickModalNewPermit" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                    <i class="fas fa-file-circle-plus text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Issue Sanitation Permit</h3>
                    <p class="text-[11px] text-slate-500">Create a sanitation permit application</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickModal('quickModalNewPermit')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickPermitForm" onsubmit="handleQuickPermitSubmit(event)" class="p-5 space-y-3.5">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Establishment / Business Name <span class="text-rose-500">*</span></label>
                <input type="text" name="establishment_name" required placeholder="e.g. Caloocan City Health Diner" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-500 outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Owner / Applicant <span class="text-rose-500">*</span></label>
                    <input type="text" name="owner_name" required placeholder="Juan Dela Cruz" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Business Category <span class="text-rose-500">*</span></label>
                    <select name="business_type" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-500 outline-none bg-white">
                        <option value="Food Establishment">Food Establishment</option>
                        <option value="Service Industry">Service Industry</option>
                        <option value="Industrial / Water">Industrial / Water</option>
                        <option value="Commercial Facility">Commercial Facility</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Contact Number <span class="text-rose-500">*</span></label>
                    <input type="text" name="contact" required placeholder="0917-123-4567" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Barangay Location <span class="text-rose-500">*</span></label>
                    <input type="text" name="barangay" required placeholder="Barangay 5, Caloocan" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-amber-500 outline-none" />
                </div>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickModal('quickModalNewPermit')" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="btnQuickPermit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-paper-plane text-xs"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 3. QUICK ACTION MODAL: RECORD VACCINATION                    -->
<!-- ============================================================ -->
<div id="quickModalVaccinate" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-syringe text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Record Vaccination</h3>
                    <p class="text-[11px] text-slate-500">Log an immunization dose for child/adult</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickModal('quickModalVaccinate')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickVaccineForm" onsubmit="handleQuickVaccinateSubmit(event)" class="p-5 space-y-3.5">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Patient Full Name / Child Name <span class="text-rose-500">*</span></label>
                <input type="text" name="patient_name" required placeholder="Patient Name or ID Code" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Vaccine Type <span class="text-rose-500">*</span></label>
                    <select name="vaccine_type" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        <option value="BCG">BCG Vaccine</option>
                        <option value="Hepatitis B">Hepatitis B</option>
                        <option value="Pentavalent">Pentavalent (DPT-HepB-Hib)</option>
                        <option value="OPV/IPV">Polio (OPV / IPV)</option>
                        <option value="MMR">Measles, Mumps, Rubella (MMR)</option>
                        <option value="Influenza">Influenza (Flu)</option>
                        <option value="COVID-19">COVID-19 Booster</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Dose Number <span class="text-rose-500">*</span></label>
                    <select name="dose_number" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                        <option value="Dose 1">Dose 1 (Primary)</option>
                        <option value="Dose 2">Dose 2</option>
                        <option value="Dose 3">Dose 3</option>
                        <option value="Booster 1">Booster 1</option>
                        <option value="Booster 2">Booster 2</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Administration Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="administered_date" value="<?= date('Y-m-d') ?>" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Health Center / Administered By</label>
                    <input type="text" name="health_center" value="Caloocan Main Health Center" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" />
                </div>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickModal('quickModalVaccinate')" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="btnQuickVaccine" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-check text-xs"></i> Record Vaccination
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 4. QUICK ACTION MODAL: FLAG HEALTH CASE / OUTBREAK           -->
<!-- ============================================================ -->
<div id="quickModalReportCase" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                    <i class="fas fa-flag text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Flag Health Case</h3>
                    <p class="text-[11px] text-slate-500">Report disease surveillance case or outbreak trigger</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickModal('quickModalReportCase')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickCaseForm" onsubmit="handleQuickCaseSubmit(event)" class="p-5 space-y-3.5">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Disease / Condition <span class="text-rose-500">*</span></label>
                <input type="text" name="disease_name" required placeholder="e.g. Dengue Fever, Acute Gastroenteritis, Measles" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Barangay Location <span class="text-rose-500">*</span></label>
                    <input type="text" name="location" required placeholder="e.g. Barangay 8" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Severity / Outbreak Risk <span class="text-rose-500">*</span></label>
                    <select name="severity" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 outline-none bg-white">
                        <option value="Low">Low Risk (Isolated)</option>
                        <option value="Moderate">Moderate Alert</option>
                        <option value="High">High Outbreak Alert</option>
                        <option value="Critical">Critical Emergency</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Observed Symptoms / Case Details</label>
                <textarea name="symptoms" rows="2" placeholder="High fever, rashes, dehydration, clusters observed..." class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-rose-500 outline-none"></textarea>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickModal('quickModalReportCase')" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="btnQuickCase" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-bullhorn text-xs"></i> File Case Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 5. QUICK ACTION MODAL: SCHEDULE INSPECTION                   -->
<!-- ============================================================ -->
<div id="quickModalSchedule" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-calendar-plus text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Schedule Sanitation Inspection</h3>
                    <p class="text-[11px] text-slate-500">Book field inspection for sanitation compliance</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickModal('quickModalSchedule')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickScheduleForm" onsubmit="handleQuickScheduleSubmit(event)" class="p-5 space-y-3.5">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Establishment / Facility Name <span class="text-rose-500">*</span></label>
                <input type="text" name="business_name" required placeholder="Facility Name or Establishment" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Inspection Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="scheduled_date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Inspection Type <span class="text-rose-500">*</span></label>
                    <select name="inspection_type" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                        <option value="Routine Sanitation Audit">Routine Sanitation Audit</option>
                        <option value="Permit Compliance Check">Permit Compliance Check</option>
                        <option value="Re-inspection">Re-inspection</option>
                        <option value="Citizen Complaint Investigation">Citizen Complaint Investigation</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Inspector Assigned</label>
                    <input type="text" name="inspector_name" placeholder="Sanitation Inspector Name" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Barangay Location <span class="text-rose-500">*</span></label>
                    <input type="text" name="barangay" required placeholder="Barangay 12, Caloocan" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
                </div>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickModal('quickModalSchedule')" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="btnQuickSchedule" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-calendar-check text-xs"></i> Confirm Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 6. QUICK ACTION MODAL: NEW DESLUDGING / SERVICE REQUEST       -->
<!-- ============================================================ -->
<div id="quickModalNewWasteRequest" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center">
                    <i class="fas fa-truck-droplet text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">New Desludging Request</h3>
                    <p class="text-[11px] text-slate-500">Log a wastewater or septic desludging dispatch</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickModal('quickModalNewWasteRequest')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickWasteRequestForm" onsubmit="handleQuickWasteRequestSubmit(event)" class="p-5 space-y-3.5">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Owner / Client Name <span class="text-rose-500">*</span></label>
                <input type="text" name="owner_name" required placeholder="e.g. Roberto Cruz" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-sky-500 outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Service Type <span class="text-rose-500">*</span></label>
                    <select name="service_type" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                        <option value="desludging" selected>Desludging / Siphoning</option>
                        <option value="maintenance">Preventive Maintenance</option>
                        <option value="inspection">Septic Tank Inspection</option>
                        <option value="emergency">Emergency Overflow</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Priority <span class="text-rose-500">*</span></label>
                    <select name="priority" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                        <option value="medium" selected>Medium Priority</option>
                        <option value="low">Low Priority</option>
                        <option value="high">High Priority</option>
                        <option value="critical">Critical / Urgent</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Barangay Location <span class="text-rose-500">*</span></label>
                    <input type="text" name="barangay" required placeholder="e.g. Barangay 8, Caloocan" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-sky-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Preferred Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="preferred_date" value="<?= date('Y-m-d') ?>" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-sky-500 outline-none" />
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Complete Address / Landmarks</label>
                <input type="text" name="address" placeholder="House / Lot number, street name" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-sky-500 outline-none" />
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Notes / Instructions</label>
                <textarea name="notes" rows="2" placeholder="e.g. Tank located at back alley, requires long suction hose" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-sky-500 outline-none"></textarea>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickModal('quickModalNewWasteRequest')" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="btnQuickWasteRequest" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-paper-plane text-xs"></i> File Request
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- 7. QUICK ACTION MODAL: REGISTER SEPTIC TANK                  -->
<!-- ============================================================ -->
<div id="quickModalNewSepticTank" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
        <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-water text-sm"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Register Septic Tank</h3>
                    <p class="text-[11px] text-slate-500">Record a new septic facility into city registry</p>
                </div>
            </div>
            <button type="button" onclick="closeQuickModal('quickModalNewSepticTank')" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
        <form id="quickSepticTankForm" onsubmit="handleQuickSepticTankSubmit(event)" class="p-5 space-y-3.5">
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Property / Owner Name <span class="text-rose-500">*</span></label>
                <input type="text" name="owner_name" required placeholder="e.g. Caloocan Commercial Complex / Juan Cruz" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Capacity (Gallons/Liters) <span class="text-rose-500">*</span></label>
                    <input type="text" name="capacity" required placeholder="e.g. 1000 Gallons" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Tank Material / Type <span class="text-rose-500">*</span></label>
                    <select name="type" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                        <option value="Concrete" selected>Concrete</option>
                        <option value="Plastic">Plastic / Polyethylene</option>
                        <option value="Fiberglass">Fiberglass</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Barangay <span class="text-rose-500">*</span></label>
                    <input type="text" name="barangay" required placeholder="Barangay 5, Caloocan" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 mb-1">Operational Status <span class="text-rose-500">*</span></label>
                    <select name="status" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none bg-white">
                        <option value="good" selected>Good Condition</option>
                        <option value="needs_maintenance">Needs Maintenance</option>
                        <option value="critical">Critical Attention</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Property Address <span class="text-rose-500">*</span></label>
                <input type="text" name="address" required placeholder="Street address or block &amp; lot" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-700 mb-1">Installation Year / Notes</label>
                <input type="number" name="installation_year" min="1950" max="<?= date('Y') ?>" value="<?= date('Y') ?>" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-purple-500 outline-none" />
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeQuickModal('quickModalNewSepticTank')" class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs hover:bg-slate-200 transition">Cancel</button>
                <button type="submit" id="btnQuickSepticTank" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-check text-xs"></i> Save to Registry
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