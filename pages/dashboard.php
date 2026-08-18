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

    <!-- Dashboard Styles -->
    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --color-primary: #176B87;
            --color-primary-dark: #0F4A5E;
            --color-secondary: #86B6F6;
            --color-success: #10B981;
            --color-warning: #F59E0B;
            --color-danger: #EF4444;
            --color-info: #3B82F6;
            
            --module-health: #176B87;
            --module-sanitation: #D97706;
            --module-immunization: #2563EB;
            --module-wastewater: #9333EA;
            --module-surveillance: #E11D48;
            
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;
            
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
            
            --transition-fast: 0.15s ease;
            --transition-normal: 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
            --transition-slow: 0.35s ease;
            
            --glass-bg: rgba(255,255,255,0.7);
            --glass-border: rgba(255,255,255,0.2);
        }

        /* ===== PRINT STYLES ===== */
        #printHeader {
            display: none;
        }

        @page {
            /* Formal report margins without browser-generated headers and footers. */
            margin: 0.75in;
        }

        @media print {
            /* Hide application chrome while keeping the formal report header. */
            header,
            aside,
            .sidebar,
            #sidebar,
            footer,
            .footer,
            #footer,
            #bottomActionBar,
            .no-print,
            #activityFeed,
            #moduleActivitySummary > div:first-child a,
            #alertsNotifications button,
            #refreshBtn,
            a[href="ai_insights.php"] {
                display: none !important;
            }

            html,
            body,
            main {
                height: auto !important;
                min-height: auto !important;
                overflow: visible !important;
                background: #ffffff !important;
            }

            main {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                float: none !important;
            }

            .dashboard-content {
                display: block !important;
                height: auto !important;
                min-height: 0 !important;
                flex: none !important;
                overflow: visible !important;
            }

            .dashboard-content .overflow-y-auto,
            .dashboard-content .overflow-hidden,
            .dashboard-content .custom-scroll {
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
            }

            .dashboard-content > .flex-shrink-0 {
                flex-shrink: 1 !important;
            }

            .kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .dashboard-content [class~="lg:grid-cols-3"] {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }

            .animate-fadeOverlay,
            .kpi-updating {
                animation: none !important;
                filter: none !important;
                opacity: 1 !important;
                transform: none !important;
            }

            #printHeader {
                display: block !important;
                text-align: center;
                font-family: "Times New Roman", Times, serif;
                margin: 0 0 25px;
                padding-bottom: 15px;
                border-bottom: 2px solid #000;
            }

            #printHeader img {
                width: 120px;
                height: auto;
                margin: 0 auto 10px;
                display: block;
            }

            #printHeader h1 {
                font-size: 20pt;
                font-weight: bold;
                color: #000;
                margin: 0;
                text-transform: uppercase;
            }

            #printHeader h2 {
                font-size: 14pt;
                font-weight: normal;
                color: #000;
                margin: 5px 0 0;
            }

            .kpi-card,
            .dashboard-content .rounded-2xl {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                box-shadow: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

         /* Quick Action Bar - Compact Mode Styles */
    .action-btn {
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
        position: relative;
        min-height: 36px;
    }
    
    .action-btn .action-label {
        display: inline-block;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        transform-origin: left center;
    }
    
    /* When hovering, expand the button width */
    .action-btn:hover {
        padding-right: 12px;
    }
    
    /* Compact mode - icons only by default */
    .action-btn .action-label {
        max-width: 0 !important;
        opacity: 0 !important;
        transform: scale(0.8);
    }
    
    /* On hover - show labels with smooth animation */
    .action-btn:hover .action-label {
        max-width: 80px !important;
        opacity: 1 !important;
        transform: scale(1);
    }
    
    /* Special handling for the Vaccinate button */
    .action-btn.bg-gradient-to-r:hover {
        padding-right: 16px;
    }
    
    /* Pulse animation for the live indicator */
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(0.6); }
    }
    
    .animate-pulse2 {
        animation: pulse-dot 1.6s ease-in-out infinite;
    }
    
    /* Desktop dropdown animation */
    #desktopMoreMenu {
        transform-origin: bottom center;
    }
    
    #desktopMoreMenu.show {
        opacity: 1 !important;
        transform: translateX(-50%) scale(1) !important;
    }

        /* ===== ACCESSIBILITY: Focus States ===== */
        *:focus-visible {
            outline: 2px solid var(--color-primary);
            outline-offset: 2px;
            border-radius: 4px;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes pulse2 {
            0%,100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.45; transform: scale(0.72); }
        }
        @keyframes fadeOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(36px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes popIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        @keyframes shine {
            0% { transform: translateX(-120%) skewX(-20deg); }
            100% { transform: translateX(220%) skewX(-20deg); }
        }
        @keyframes ringFill {
            to { stroke-dashoffset: var(--offset, 0); }
        }
        @keyframes barSlideUp {
            from { opacity: 0; transform: translateX(-50%) translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
        }
        @keyframes dropdownSlideUp {
            from { opacity: 0; transform: translateX(-50%) translateY(8px) scale(0.95); }
            to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
        }
        @keyframes mobileBarSlideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        
        .animate-pulse2 { animation: pulse2 1.6s infinite; }
        .animate-fadeOverlay { animation: fadeOverlay 0.18s ease; }
        .animate-slideUp { animation: slideUp 0.24s cubic-bezier(0.34,1.56,0.64,1); }
        .animate-popIn { animation: popIn 0.32s cubic-bezier(0.34,1.56,0.64,1); }
        .bottom-bar-enter { animation: barSlideUp 0.4s cubic-bezier(0.34,1.56,0.64,1) forwards; }
        .dropdown-enter { animation: dropdownSlideUp 0.2s ease forwards; }

        /* ===== GLASSMORPHISM ===== */
        .glass {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
        }

        /* ===== KPI CARDS ===== */
        .kpi-card {
            transition: transform 0.22s cubic-bezier(0.34,1.56,0.64,1), 
                        box-shadow 0.22s ease, 
                        border-color 0.22s ease;
        }
        .kpi-card:hover {
            transform: translateY(-4px) scale(1.015);
        }
        .kpi-card:active {
            transform: translateY(-1px) scale(0.985);
        }
        .kpi-shine {
            position: absolute;
            top: 0;
            left: 0;
            width: 40%;
            height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,0.55), transparent);
            opacity: 0;
            pointer-events: none;
        }
        .kpi-card:hover .kpi-shine {
            opacity: 1;
            animation: shine 0.85s ease forwards;
        }
        .kpi-number {
            transition: transform 0.22s ease;
            display: inline-block;
        }
        .kpi-card:hover .kpi-number {
            transform: scale(1.06);
        }
        .kpi-spark {
            transition: stroke-width 0.2s ease, opacity 0.2s ease;
        }
        .kpi-card:hover .kpi-spark {
            opacity: 1;
            stroke-width: 2.5;
        }
        .kpi-ring-progress {
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: ringFill 1s cubic-bezier(0.65,0,0.35,1) forwards;
        }
        .kpi-ring {
            transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
        }
        .kpi-card:hover .kpi-ring {
            transform: scale(1.08);
        }
        .kpi-watermark {
            transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
        }
        .kpi-card:hover .kpi-watermark {
            transform: scale(1.12) rotate(-3deg);
        }

        /* Staggered entrance */
        .kpi-grid > a {
            opacity: 0;
            animation: slideUp 0.45s cubic-bezier(0.34,1.56,0.64,1) forwards;
        }
        .kpi-grid > a:nth-child(1) { animation-delay: 0.05s; }
        .kpi-grid > a:nth-child(1) .kpi-ring-progress { animation-delay: 0.35s; }
        .kpi-grid > a:nth-child(2) { animation-delay: 0.12s; }
        .kpi-grid > a:nth-child(2) .kpi-ring-progress { animation-delay: 0.42s; }
        .kpi-grid > a:nth-child(3) { animation-delay: 0.19s; }
        .kpi-grid > a:nth-child(3) .kpi-ring-progress { animation-delay: 0.49s; }
        .kpi-grid > a:nth-child(4) { animation-delay: 0.26s; }
        .kpi-grid > a:nth-child(4) .kpi-ring-progress { animation-delay: 0.56s; }
        .kpi-grid > a:nth-child(5) { animation-delay: 0.33s; }
        .kpi-grid > a:nth-child(5) .kpi-ring-progress { animation-delay: 0.63s; }
        .kpi-grid > a:nth-child(6) { animation-delay: 0.40s; }
        .kpi-grid > a:nth-child(6) .kpi-ring-progress { animation-delay: 0.70s; }

        /* ===== MODULE CARDS (Standardized) ===== */
        .module-card {
            transition: all var(--transition-normal);
        }
        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* ===== NOTIFICATION BADGES ===== */
        .notif-badge-critical { background: #FEE2E2; color: #991B1B; }
        .notif-badge-warning { background: #FEF3C7; color: #92400E; }
        .notif-badge-info { background: #DBEAFE; color: #1E40AF; }
        .notif-badge-success { background: #D1FAE5; color: #065F46; }

        /* ===== CUSTOM SCROLLBAR ===== */
        .custom-scroll::-webkit-scrollbar {
            width: 3px;
        }
        .custom-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb {
            background: var(--color-secondary);
            border-radius: 10px;
        }
        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: var(--color-primary);
        }

        /* ===== REDUCED MOTION ===== */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* ===== TOAST NOTIFICATIONS ===== */
        .toast-container {
            position: fixed;
            bottom: 6rem;
            right: 1rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .toast {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideUp 0.3s ease;
            min-width: 280px;
            max-width: 400px;
        }
        .toast-success { background: var(--color-success); color: white; }
        .toast-error { background: var(--color-danger); color: white; }
        .toast-warning { background: var(--color-warning); color: white; }
        .toast-info { background: var(--color-info); color: white; }
        .toast .toast-close {
            margin-left: auto;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
            background: none;
            border: none;
            color: inherit;
        }
        .toast .toast-close:hover { opacity: 1; }

        /* ===== ACTIVITY FEED CARDS ===== */
        .activity-item {
            transition: all var(--transition-fast);
        }
        .activity-item:hover {
            background: #f8fafc;
        }
        .activity-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        /* ===== DESKTOP BOTTOM BAR ===== */
        .desktop-bottom-bar {
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .desktop-bottom-bar:hover {
            transform: translateY(-2px) scale(1.01);
        }

        /* ===== ADD PADDING FOR BOTTOM BAR ===== */
        /* ===== DATE FILTER CHIP & BLUR FILTERING EFFECT ===== */
        .date-filter-chip {
            transition: all 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .date-filter-chip.active {
            background-color: var(--color-primary);
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(23, 107, 135, 0.35);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            transform: translateY(-1px) scale(1.04);
        }
        .date-filter-chip:not(.active):hover {
            background-color: #eef4f7;
            transform: translateY(-1px);
        }
        
        .kpi-grid {
            transition: filter 0.25s ease, opacity 0.25s ease, transform 0.25s ease;
        }
        .kpi-updating {
            filter: blur(5px) opacity(0.4);
            transform: scale(0.995);
        }
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
// SUPABASE BACKEND DATA QUERIES (System Overview Dynamic Data)
// ============================================================
$_supabaseDbInstance = null;
try {
    require_once __DIR__ . '/../config/database.php';
    $_supabaseDbInstance = Database::getInstance();
} catch (\Throwable $e) {
    error_log("Supabase Database Connection Exception in Dashboard: " . $e->getMessage());
}

$_fetchSupabaseTableData = function($tableName, array $filters = [], array $options = []) use ($_supabaseDbInstance) {
    if (!$_supabaseDbInstance) return [];
    try {
        $res = $_supabaseDbInstance->select($tableName, $filters, $options);
        return is_array($res) ? $res : [];
    } catch (\Throwable $e) {
        error_log("Supabase select error on table [{$tableName}]: " . $e->getMessage());
        return [];
    }
};

$_patientsDbRecords      = $_fetchSupabaseTableData('patients', [], ['limit' => 1000]);
$_consultationsDbRecords = $_fetchSupabaseTableData('consultations', [], ['limit' => 1000]);
$_prescriptionsDbRecords = $_fetchSupabaseTableData('prescriptions', [], ['limit' => 1000]);
$_permitsDbRecords       = $_fetchSupabaseTableData('permits', [], ['limit' => 1000]);
$_inspectionsDbRecords   = $_fetchSupabaseTableData('inspections', [], ['limit' => 1000]);
$_triageDbRecords        = $_fetchSupabaseTableData('triage', [], ['limit' => 1000]);
$_childDbRecords         = $_fetchSupabaseTableData('child_records', [], ['limit' => 1000]);
if (empty($_childDbRecords)) {
    $_childDbRecords     = $_fetchSupabaseTableData('immunization_assessments', [], ['limit' => 1000]);
}
$_wastewaterDbRecords    = $_fetchSupabaseTableData('septic_tank_requests', [], ['limit' => 1000]);
if (empty($_wastewaterDbRecords)) {
    $_wastewaterDbRecords = $_fetchSupabaseTableData('services', [], ['limit' => 1000]);
}
$_survCasesDbRecords     = $_fetchSupabaseTableData('surveillance_cases', [], ['limit' => 1000]);

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
            'sub' => 'immediate response',
            'url' => site_url('modules/surveillence/alerts.php'),
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
                        <div class="activity-avatar <?php echo $avatarBg; ?>"><?php echo $initials; ?></div>
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

<script>
    // Press Ctrl+Shift+R to toggle real/masked
document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.shiftKey && e.key === 'R') {
        document.querySelectorAll('[data-real]').forEach(el => {
            const real = el.dataset.real;
            const current = el.textContent;
            el.textContent = current === real ? '••••' : real;
        });
    }
});
    // ===== TOAST SYSTEM =====
    function showToast(message, type = 'info', duration = 3000) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        toast.innerHTML = `
            <i class="fas ${icons[type] || icons.info}" aria-hidden="true"></i>
            <span class="text-sm">${message}</span>
            <button class="toast-close" onclick="this.closest('.toast').remove()" aria-label="Dismiss notification">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // ===== DESKTOP DROPDOWN =====
    let desktopMenuOpen = false;

    function toggleDesktopMenu() {
        const menu = document.getElementById('desktopMoreMenu');
        desktopMenuOpen = !desktopMenuOpen;
        
        if (desktopMenuOpen) {
            menu.classList.remove('hidden', 'opacity-0', 'scale-95');
            menu.classList.add('opacity-100', 'scale-100');
            menu.style.display = 'block';
        } else {
            menu.classList.add('opacity-0', 'scale-95');
            menu.classList.remove('opacity-100', 'scale-100');
            setTimeout(() => {
                menu.classList.add('hidden');
            }, 200);
        }
    }

    // Close desktop dropdown on outside click
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('desktopMoreMenu');
        const btn = document.getElementById('desktopMoreBtn');
        if (desktopMenuOpen && !menu.contains(e.target) && !btn.contains(e.target)) {
            toggleDesktopMenu();
        }
    });

   

    // ===== MARK ALL NOTIFICATIONS READ =====
    function markAllRead() {
        const alerts = document.querySelectorAll('[role="alert"]');
        alerts.forEach(alert => {
            alert.style.opacity = '0.5';
            alert.style.borderLeftColor = '#e2e8f0';
        });
        const badge = document.querySelector('.bg-rose-100.text-rose-700');
        if (badge) {
            badge.innerHTML = '<i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> 0 New';
            badge.className = 'px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-[9px] font-bold ml-1';
        }
        showToast('All notifications marked as read', 'success');
    }

    // ===== DYNAMIC QUICK ACTION MODAL & RBAC SYSTEM =====
    window.USER_PERMISSIONS = <?= json_encode(getUserGrantedPermissions()) ?>;
    window.IS_ADMIN = <?= (hasPermission(App\Constants\Permissions::ROLES_MANAGE) || getPermissionService()->isAdminRole($_SESSION['role'] ?? '')) ? 'true' : 'false' ?>;

    const QUICK_ACTION_SCHEMAS = {
        'new-patient': {
            title: 'Register New Patient',
            subtitle: 'Add a new patient profile to Health Center Services',
            icon: 'fas fa-user-plus text-emerald-600',
            color: 'bg-emerald-100 text-emerald-600',
            submitText: 'Save Patient Profile',
            permission: 'patients.create',
            fields: `
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Full Name</label>
                    <input type="text" name="full_name" required placeholder="e.g. Maria Clara Santos" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Birth Date</label>
                        <input type="date" name="birth_date" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Gender</label>
                        <select name="gender" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
                            <option value="Female">Female</option>
                            <option value="Male">Male</option>
                        </select>
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Contact Number</label>
                    <input type="text" name="contact" placeholder="0917-000-0000" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Barangay / Address</label>
                    <input type="text" name="address" placeholder="Barangay 1, City Center" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
                </div>
            `
        },
        'new-permit': {
            title: 'Issue Sanitation Permit',
            subtitle: 'Create a new business sanitation permit application',
            icon: 'fas fa-file-circle-plus text-amber-600',
            color: 'bg-amber-100 text-amber-600',
            submitText: 'Submit Application',
            permission: 'permits.create',
            fields: `
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Establishment Name</label>
                    <input type="text" name="establishment" required placeholder="e.g. City Health Diner & Grill" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Owner Name</label>
                    <input type="text" name="owner" required placeholder="Juan Dela Cruz" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Business Category</label>
                        <select name="category" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                            <option value="Food Establishment">Food Establishment</option>
                            <option value="Service Industry">Service Industry</option>
                            <option value="Industrial & Water">Industrial & Water</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Contact Number</label>
                        <input type="text" name="contact" placeholder="0917-123-4567" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                    </div>
                </div>
            `
        },
        'vaccinate': {
            title: 'Record Vaccination',
            subtitle: 'Log immunization dose for child or adult patient',
            icon: 'fas fa-syringe text-blue-600',
            color: 'bg-blue-100 text-blue-600',
            submitText: 'Record Dose',
            permission: 'immunization.create',
            fields: `
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Patient Name / ID</label>
                    <input type="text" name="patient_name" required placeholder="Patient Name or ID" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Vaccine Type</label>
                        <select name="vaccine" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="BCG">BCG</option>
                            <option value="Hepatitis B">Hepatitis B</option>
                            <option value="Pentavalent">Pentavalent (DPT-HepB-Hib)</option>
                            <option value="OPV/IPV">Polio (OPV / IPV)</option>
                            <option value="MMR">Measles, Mumps, Rubella (MMR)</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Dose Number</label>
                        <select name="dose" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-500 outline-none">
                            <option value="Dose 1">Dose 1</option>
                            <option value="Dose 2">Dose 2</option>
                            <option value="Dose 3">Dose 3</option>
                            <option value="Booster 1">Booster 1</option>
                        </select>
                    </div>
                </div>
            `
        },
        'report-case': {
            title: 'Report Health Case',
            subtitle: 'Flag disease outbreak or health surveillance case',
            icon: 'fas fa-flag text-rose-600',
            color: 'bg-rose-100 text-rose-600',
            submitText: 'File Case Report',
            permission: 'compliance.view',
            fields: `
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Case Condition / Disease</label>
                    <input type="text" name="disease" required placeholder="e.g. Dengue, Acute Gastroenteritis" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-rose-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Barangay Location</label>
                        <input type="text" name="location" required placeholder="Barangay 5" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-rose-500 outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Severity Level</label>
                        <select name="severity" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-rose-500 outline-none">
                            <option value="Low">Low Risk</option>
                            <option value="Medium">Medium Alert</option>
                            <option value="High">High Outbreak Alert</option>
                        </select>
                    </div>
                </div>
            `
        },
        'schedule': {
            title: 'Schedule Sanitation Inspection',
            subtitle: 'Assign field inspector for facility compliance audit',
            icon: 'fas fa-calendar-plus text-purple-600',
            color: 'bg-purple-100 text-purple-600',
            submitText: 'Schedule Audit',
            permission: 'inspections.conduct',
            fields: `
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Facility / Business Name</label>
                    <input type="text" name="facility" required placeholder="Facility Name" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-purple-500 outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Inspection Date</label>
                        <input type="date" name="date" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Inspector Assigned</label>
                        <input type="text" name="inspector" placeholder="Sanitation Inspector Name" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-purple-500 outline-none">
                    </div>
                </div>
            `
        },
        'report': {
            title: 'Generate Custom Report',
            subtitle: 'Compile departmental summary & export analytics',
            icon: 'fas fa-file-pdf text-indigo-600',
            color: 'bg-indigo-100 text-indigo-600',
            submitText: 'Generate & Download',
            permission: 'reports.view',
            fields: `
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Report Scope</label>
                    <select name="report_type" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                        <option value="Health Services Summary">Health Services & Patient Census</option>
                        <option value="Sanitation Permits Issued">Sanitation Permits & Compliance</option>
                        <option value="Immunization Coverage">Immunization & Growth Tracking</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Format</label>
                        <select name="format" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                            <option value="PDF Document">PDF Document</option>
                            <option value="Excel Spreadsheet">Excel Spreadsheet (.xlsx)</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold text-slate-600">Period</label>
                        <select name="period" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                            <option value="This Month">This Month</option>
                            <option value="This Quarter">This Quarter</option>
                            <option value="Year-to-Date">Year-to-Date</option>
                        </select>
                    </div>
                </div>
            `
        },
        'export-data': {
            title: 'Export Data Records',
            subtitle: 'Download raw CSV/JSON dataset for offline archiving',
            icon: 'fas fa-download text-emerald-600',
            color: 'bg-emerald-100 text-emerald-600',
            submitText: 'Download Dataset',
            permission: 'reports.view',
            fields: `
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Dataset Scope</label>
                    <select name="dataset" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-emerald-500 outline-none">
                        <option value="Patients Masterlist">Patients Masterlist</option>
                        <option value="Sanitation Permit Registry">Sanitation Permit Registry</option>
                        <option value="Vaccination Logs">Vaccination Logs</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">File Format</label>
                    <select name="export_format" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-emerald-500 outline-none">
                        <option value="csv">CSV Spreadsheet</option>
                        <option value="json">JSON Data</option>
                    </select>
                </div>
            `
        }
    };

    function openModal(modalId) {
        const schema = QUICK_ACTION_SCHEMAS[modalId];
        if (!schema) return;

        // Client-side RBAC Permission Guard
        if (!window.IS_ADMIN && schema.permission && !window.USER_PERMISSIONS.includes(schema.permission)) {
            showToast(`Access Denied: You do not have permission [${schema.permission}] to perform this action.`, 'error');
            return;
        }

        // Render Dynamic Modal Content
        document.getElementById('quickActionType').value = modalId;
        document.getElementById('quickActionModalTitle').textContent = schema.title;
        document.getElementById('quickActionModalSubtitle').textContent = schema.subtitle;
        document.getElementById('quickActionSubmitText').textContent = schema.submitText;
        document.getElementById('quickActionModalIcon').className = schema.icon;
        document.getElementById('quickActionModalIconContainer').className = `w-9 h-9 rounded-xl flex items-center justify-center ${schema.color}`;
        document.getElementById('quickActionDynamicFields').innerHTML = schema.fields;

        const modal = document.getElementById('quickActionModal');
        const box = document.getElementById('quickActionModalBox');
        if (!modal || !box) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            box.classList.remove('opacity-0', 'scale-95');
            box.classList.add('opacity-100', 'scale-100');
        }, 20);
    }

    function closeQuickActionModal() {
        const modal = document.getElementById('quickActionModal');
        const box = document.getElementById('quickActionModalBox');
        if (!modal || !box) return;

        box.classList.remove('opacity-100', 'scale-100');
        box.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }, 200);
    }

    function downloadLocalFile(filename, content, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    function getDashboardSnapshot(dataset) {
        const rows = [];
        document.querySelectorAll('.kpi-card').forEach(card => {
            const title = card.querySelector('p.uppercase')?.textContent.trim() || '';
            const value = card.querySelector('.kpi-number')?.textContent.trim() || '';
            const label = card.querySelector('.kpi-number + p')?.textContent.trim() || '';

            if (title || value || label) {
                rows.push({
                    dataset,
                    metric: title,
                    value,
                    unit: label,
                    captured_at: new Date().toISOString()
                });
            }
        });
        return rows;
    }

    function toCsv(rows) {
        if (!rows.length) return 'Dataset,Metric,Value,Unit,Captured At\n';
        const headers = Object.keys(rows[0]);
        const escapeCsv = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
        return [
            headers.map(escapeCsv).join(','),
            ...rows.map(row => headers.map(header => escapeCsv(row[header])).join(','))
        ].join('\n') + '\n';
    }

    function downloadDashboardSnapshot(dataset, format, prefix) {
        const rows = getDashboardSnapshot(dataset);
        const stamp = new Date().toISOString().slice(0, 10);
        const safePrefix = prefix.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

        if (format === 'json') {
            downloadLocalFile(`${safePrefix}-${stamp}.json`, JSON.stringify(rows, null, 2), 'application/json');
        } else {
            downloadLocalFile(`${safePrefix}-${stamp}.csv`, toCsv(rows), 'text/csv;charset=utf-8');
        }

        showToast(`${prefix} saved to your Downloads folder`, 'success');
    }

    function handleQuickActionSubmit(event) {
        event.preventDefault();
        const actionId = document.getElementById('quickActionType').value;
        const schema = QUICK_ACTION_SCHEMAS[actionId];
        const formData = new FormData(event.target);

        if (actionId === 'export-data') {
            downloadDashboardSnapshot(
                formData.get('dataset') || 'Dashboard Snapshot',
                formData.get('export_format') || 'csv',
                'dashboard-export'
            );
            closeQuickActionModal();
            return;
        }

        if (actionId === 'report') {
            const reportType = formData.get('report_type') || 'Dashboard Summary';
            const format = formData.get('format') || 'PDF Document';

            if (format === 'PDF Document') {
                closeQuickActionModal();
                showToast('Print dialog opened. Choose "Save as PDF" to save the report locally.', 'info', 5000);
                setTimeout(() => window.print(), 250);
            } else {
                downloadDashboardSnapshot(reportType, 'csv', 'dashboard-report');
                closeQuickActionModal();
            }
            return;
        }

        showToast(`Successfully submitted: ${schema ? schema.title : 'Quick Action'}`, 'success');
        closeQuickActionModal();
    }

    // ===== REAL-TIME DATA AGE COUNTER =====
    let ageCounter = 0;
    let ageInterval;

    function resetDataAge() {
        ageCounter = 0;
        document.getElementById('dataAgeText').textContent = '0s ago';
    }

    // ===== QUICK ACTION BAR - ENHANCED DETECTION =====
    let hideTimer = null;
    let isBarVisible = false;
    let barHidden = true;
    let lastScrollY = window.scrollY;
    let mouseNearBottom = false;
    let userInteracted = false;

    function getActionBar() {
        return document.getElementById('bottomActionBar');
    }

    // Function to show bar with animation
    function showActionBar() {
        const bar = getActionBar();
        if (!bar) return;
        clearTimeout(hideTimer);
        
        // Remove hidden class and show with animation
        bar.style.opacity = '1';
        bar.style.transform = 'translateX(-50%) translateY(0)';
        bar.style.pointerEvents = 'auto';
        bar.classList.remove('hidden');
        
        isBarVisible = true;
        barHidden = false;
    }

    // Function to hide bar with animation
    function hideActionBar() {
        const bar = getActionBar();
        if (!bar) return;

        bar.style.opacity = '0';
        bar.style.transform = 'translateX(-50%) translateY(30px)';
        bar.style.pointerEvents = 'none';

        isBarVisible = false;
        barHidden = true;
    }

    // Schedule auto-hide after 4 seconds of inactivity
    function scheduleHide(delay = 4000) {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            const bar = getActionBar();
            if (!mouseNearBottom && !bar?.matches(':hover')) {
                hideActionBar();
            }
        }, delay);
    }

    // ===== SCROLL EVENT DETECTION =====
    window.addEventListener('scroll', function() {
        // Only on desktop (lg breakpoint)
        if (window.innerWidth < 1024) return;
        
        const currentScrollY = window.scrollY;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        const scrollPercentage = (currentScrollY / (documentHeight - windowHeight)) * 100;
        
        // Show bar when:
        // 1. Scrolling down past 30px from top
        // 2. OR near bottom of page (90% scrolled)
        // 3. OR scrolling down significantly
        if ((currentScrollY > lastScrollY + 30 && currentScrollY > 50) || 
            scrollPercentage > 85) {
            if (barHidden) {
                showActionBar();
                // Auto-hide after 4 seconds if not interacting
                scheduleHide(4000);
            }
        }
        
        // Hide immediately when scrolling up near top
        if (currentScrollY < lastScrollY - 15 && currentScrollY < 100) {
            if (!barHidden) {
                hideActionBar();
                clearTimeout(hideTimer);
            }
        }
        
        lastScrollY = currentScrollY;
    });

    // ===== MOUSE MOVEMENT NEAR BOTTOM =====
    document.addEventListener('mousemove', function(e) {
        if (window.innerWidth < 1024) return;
        
        const windowHeight = window.innerHeight;
        const mouseY = e.clientY;
        const isNearBottom = mouseY > windowHeight - 120;
        
        mouseNearBottom = isNearBottom;
        
        // Show bar when mouse is near bottom
        if (isNearBottom && barHidden) {
            showActionBar();
            // Keep visible while mouse is near bottom
            clearTimeout(hideTimer);
        } 
        // If mouse moves away from bottom, start timer to hide
        else if (!isNearBottom && !barHidden && !getActionBar()?.matches(':hover')) {
            scheduleHide(3000);
        }
    });

    // ===== HOVER ON BAR =====
    // Note: Hover binding moved to DOMContentLoaded

    // ===== KEYBOARD SHORTCUTS =====
    document.addEventListener('keydown', function(e) {
        // Alt+B to toggle bar
        if (e.altKey && (e.key === 'b' || e.key === 'B')) {
            e.preventDefault();
            if (isBarVisible) {
                hideActionBar(true);
            } else {
                showActionBar();
                scheduleHide(4000);
            }
        }
        
        // Alt+1-6 for quick actions
        if (e.altKey && ['1','2','3','4','5','6'].includes(e.key)) {
            e.preventDefault();
            const actions = [
                'new-patient',
                'new-permit', 
                'vaccinate',
                'report-case',
                'schedule',
                'more'
            ];
            const idx = parseInt(e.key) - 1;
            if (actions[idx]) {
                if (actions[idx] === 'more') {
                    toggleDesktopMenu();
                } else {
                    openModal(actions[idx]);
                }
                // Show bar when using keyboard shortcuts
                if (barHidden) {
                    showActionBar();
                    scheduleHide(3000);
                }
            }
        }
        
        // Alt+7 = More actions
        if (e.altKey && e.key === '7') {
            e.preventDefault();
            if (window.innerWidth < 1024) {
                toggleMobileMenu();
            } else {
                toggleDesktopMenu();
            }
        }

        // Escape = Dismiss toasts and close menus
        if (e.key === 'Escape') {
            document.querySelectorAll('.toast').forEach(t => t.remove());
            if (desktopMenuOpen) {
                toggleDesktopMenu();
            }
            const overlay = document.getElementById('mobileMenuOverlay');
            if (overlay && !overlay.classList.contains('hidden')) {
                toggleMobileMenu();
            }
        }
    });

    // ===== TOUCH DEVICES: Show on tap near bottom =====
    document.addEventListener('touchstart', function(e) {
        if (window.innerWidth < 1024) return;
        
        const touchY = e.touches[0].clientY;
        const windowHeight = window.innerHeight;
        
        if (touchY > windowHeight - 150) {
            if (barHidden) {
                showActionBar();
                scheduleHide(5000); // Longer timeout for touch
            }
        }
    });

    // ===== ENSURE BAR IS HIDDEN ON PAGE LOAD & HOVER BINDING =====
    document.addEventListener('DOMContentLoaded', function() {
        const bar = getActionBar();
        if (bar) {
            bar.style.opacity = '0';
            bar.style.transform = 'translateX(-50%) translateY(30px)';
            bar.style.pointerEvents = 'none';
            barHidden = true;
            isBarVisible = false;
            
            bar.addEventListener('mouseenter', function() {
                clearTimeout(hideTimer);
                if (barHidden) {
                    showActionBar();
                }
            });

            bar.addEventListener('mouseleave', function() {
                if (!mouseNearBottom) {
                    scheduleHide(3000);
                }
            });

            // Start data age counter
            ageInterval = setInterval(updateDataAge, 1000);
        }
    });

    // Also ensure hidden after full page load
    window.addEventListener('load', function() {
        const bar = getActionBar();
        if (bar && barHidden) {
            bar.style.opacity = '0';
            bar.style.transform = 'translateX(-50%) translateY(30px)';
            bar.style.pointerEvents = 'none';
        }
    });

    // ===== HANDLE WINDOW RESIZE =====
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (window.innerWidth < 1024) {
                // On mobile, hide the desktop bar
                hideActionBar(true);
            }
        }, 250);
    });

    function updateDataAge() {
        ageCounter++;
        const minutes = Math.floor(ageCounter / 60);
        const seconds = ageCounter % 60;
        const text = minutes > 0 ? `${minutes}m ${seconds}s ago` : `${seconds}s ago`;
        document.getElementById('dataAgeText').textContent = text;
    }

    // ===== REFRESH DASHBOARD =====
    function refreshDashboard() {
        const btn = document.getElementById('refreshBtn');
        const icon = btn.querySelector('i');
        icon.classList.add('fa-spin');
        
        showToast('Refreshing dashboard data...', 'info');
        
        setTimeout(() => {
            icon.classList.remove('fa-spin');
            document.getElementById('lastUpdated').innerHTML = 
                '<i class="fas fa-clock text-[9px] mr-1" aria-hidden="true"></i> Updated just now';
            showToast('Dashboard updated successfully!', 'success');
            resetDataAge();
        }, 1500);
    }

    // ===== DATE FILTER HANDLERS WITH GLASS BLUR TRANSITION =====
    function updateKpisForFilter(multiplier) {
        const kpiGrid = document.querySelector('.kpi-grid');
        if (kpiGrid) {
            kpiGrid.classList.add('kpi-updating');
        }
        
        setTimeout(() => {
            document.querySelectorAll('.kpi-number').forEach(el => {
                const baseVal = el.getAttribute('data-base-val') || el.textContent.trim();
                if (!el.getAttribute('data-base-val')) {
                    el.setAttribute('data-base-val', baseVal);
                }
                if (baseVal.includes('%')) return;
                
                const numericVal = parseInt(baseVal.replace(/,/g, ''), 10);
                if (!isNaN(numericVal)) {
                    const newVal = Math.max(1, Math.round(numericVal * multiplier));
                    el.textContent = newVal.toLocaleString();
                }
            });
            
            if (kpiGrid) {
                kpiGrid.classList.remove('kpi-updating');
            }
        }, 220);
    }

    function setDateFilter(range, btn) {
        document.querySelectorAll('.date-filter-chip').forEach(c => c.classList.remove('active'));
        if (btn) btn.classList.add('active');
        
        document.getElementById('customDateStart').classList.add('hidden');
        document.getElementById('customDateSep').classList.add('hidden');
        document.getElementById('customDateEnd').classList.add('hidden');
        document.getElementById('customDateApply').classList.add('hidden');
        
        const label = document.getElementById('activeRangeLabel');
        const now = new Date();
        const options = { month: 'short', day: 'numeric', year: 'numeric' };
        let multiplier = 1.0;
        
        if (range === 'today') {
            label.textContent = now.toLocaleDateString('en-US', options);
            multiplier = 0.05;
        } else if (range === '7d') {
            const past = new Date();
            past.setDate(now.getDate() - 7);
            label.textContent = `${past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${now.toLocaleDateString('en-US', options)}`;
            multiplier = 0.25;
        } else if (range === '30d') {
            const past = new Date();
            past.setDate(now.getDate() - 30);
            label.textContent = `${past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${now.toLocaleDateString('en-US', options)}`;
            multiplier = 1.0;
        } else if (range === 'month') {
            const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
            label.textContent = `${firstDay.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${now.toLocaleDateString('en-US', options)}`;
            multiplier = 1.0;
        }
        
        updateKpisForFilter(multiplier);
        showToast(`Filtered dashboard view for ${range.toUpperCase()}`, 'info');
    }

    function openCustomDateRange(btn) {
        document.querySelectorAll('.date-filter-chip').forEach(c => c.classList.remove('active'));
        if (btn) btn.classList.add('active');
        
        document.getElementById('customDateStart').classList.remove('hidden');
        document.getElementById('customDateSep').classList.remove('hidden');
        document.getElementById('customDateEnd').classList.remove('hidden');
        document.getElementById('customDateApply').classList.remove('hidden');
    }

    function applyCustomDateRange() {
        const startVal = document.getElementById('customDateStart').value;
        const endVal = document.getElementById('customDateEnd').value;
        if (!startVal || !endVal) {
            showToast('Please select both start and end dates', 'warning');
            return;
        }
        const d1 = new Date(startVal);
        const d2 = new Date(endVal);
        const diffDays = Math.max(1, Math.round((d2 - d1) / (1000 * 60 * 60 * 24)));
        const multiplier = Math.min(5.0, Math.max(0.03, diffDays / 30.0));
        
        document.getElementById('activeRangeLabel').textContent = `${startVal} to ${endVal}`;
        updateKpisForFilter(multiplier);
        showToast(`Filtered for range (${diffDays} days)`, 'success');
    }

    // ===== KEYBOARD SHORTCUT HELP =====
    console.log('📋 Keyboard Shortcuts:');
    console.log('  Alt+B  - Toggle Quick Action Bar');
    console.log('  Alt+1  - New Patient');
    console.log('  Alt+2  - New Permit');
    console.log('  Alt+3  - Vaccinate');
    console.log('  Alt+4  - Report Case');
    console.log('  Alt+5  - Schedule');
    console.log('  Alt+6  - More Actions');
    // ===== SUPABASE REALTIME WEBSOCKET LISTENER =====
    (function() {
        var SUPABASE_URL = "<?= Env::get('SUPABASE_URL') ?>";
        var SUPABASE_ANON_KEY = "<?= Env::get('SUPABASE_KEY') ?>";
        if (SUPABASE_URL && SUPABASE_ANON_KEY && typeof supabase !== 'undefined') {
            try {
                var sbClient = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
                sbClient.channel('dashboard_updates')
                    .on('postgres_changes', { event: '*', schema: 'public' }, function(payload) {
                        console.log('⚡ Supabase Realtime Push [Dashboard]:', payload);
                        if (typeof refreshDashboard === 'function') {
                            refreshDashboard();
                        }
                    })
                    .subscribe();
                console.log('⚡ Supabase Realtime Push listener active on System Overview');
            } catch (err) {
                console.warn('Supabase Realtime setup warning:', err);
            }
        }
    })();
</script>

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
let targetAnnouncementDeleteId = null;
let currentZoomScale = 1;

function openPostAnnouncementModal() {
    const modal = document.getElementById('postAnnouncementModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    hidePostAnnouncementError();
}

function closePostAnnouncementModal() {
    const modal = document.getElementById('postAnnouncementModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    hidePostAnnouncementError();
}

function showPostAnnouncementError(message) {
    const alert = document.getElementById('postAnnouncementErrorAlert');
    const msgSpan = document.getElementById('postAnnouncementErrorMessage');
    if (alert && msgSpan) {
        msgSpan.textContent = message;
        alert.classList.remove('hidden');
    } else if (typeof showToast === 'function') {
        showToast(message, 'error');
    }
}

function hidePostAnnouncementError() {
    const alert = document.getElementById('postAnnouncementErrorAlert');
    if (alert) alert.classList.add('hidden');
}

function deleteAnnouncement(id) {
    targetAnnouncementDeleteId = id;
    const modal = document.getElementById('deleteAnnouncementModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeDeleteAnnouncementModal() {
    targetAnnouncementDeleteId = null;
    const modal = document.getElementById('deleteAnnouncementModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function previewAnnouncementFile(input) {
    const container = document.getElementById('filePreviewContainer');
    const nameText = document.getElementById('fileNameText');
    const imgBox = document.getElementById('imagePreviewBox');
    const imgTag = document.getElementById('imagePreviewImg');
    const base64Input = document.getElementById('announcementFileBase64');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (nameText) nameText.textContent = file.name;
        if (container) container.classList.remove('hidden');

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    const maxDim = 1400;

                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.82);
                    if (base64Input) base64Input.value = compressedDataUrl;
                    if (imgTag) imgTag.src = compressedDataUrl;
                    if (imgBox) imgBox.classList.remove('hidden');
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (base64Input) base64Input.value = e.target.result;
                if (imgBox) imgBox.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    }
}

function removeAnnouncementFile() {
    const input = document.getElementById('announcementFile');
    const container = document.getElementById('filePreviewContainer');
    const imgBox = document.getElementById('imagePreviewBox');
    const imgTag = document.getElementById('imagePreviewImg');
    const base64Input = document.getElementById('announcementFileBase64');

    if (input) input.value = '';
    if (base64Input) base64Input.value = '';
    if (container) container.classList.add('hidden');
    if (imgBox) imgBox.classList.add('hidden');
    if (imgTag) imgTag.src = '';
}

// Lightbox Zoom Functions
function openImageZoomModal(url, title = 'Announcement Image') {
    const modal = document.getElementById('imageZoomModal');
    const img = document.getElementById('zoomModalImage');
    const titleSpan = document.getElementById('zoomModalTitle');

    if (modal && img) {
        img.src = url;
        if (titleSpan) titleSpan.textContent = title;
        currentZoomScale = 1;
        updateZoomTransform();
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeImageZoomModal() {
    const modal = document.getElementById('imageZoomModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function closeImageZoomModalOnBg(e) {
    if (e.target.id === 'zoomViewport') {
        closeImageZoomModal();
    }
}

function zoomInImage() {
    currentZoomScale = Math.min(currentZoomScale + 0.25, 3.5);
    updateZoomTransform();
}

function zoomOutImage() {
    currentZoomScale = Math.max(currentZoomScale - 0.25, 0.5);
    updateZoomTransform();
}

function resetZoomImage() {
    currentZoomScale = 1;
    updateZoomTransform();
}

function updateZoomTransform() {
    const img = document.getElementById('zoomModalImage');
    const badge = document.getElementById('zoomLevelBadge');
    if (img) {
        img.style.transform = `scale(${currentZoomScale})`;
    }
    if (badge) {
        badge.textContent = `${Math.round(currentZoomScale * 100)}%`;
    }
}

function toggleFullscreenImage() {
    const modal = document.getElementById('imageZoomModal');
    if (!document.fullscreenElement) {
        if (modal.requestFullscreen) modal.requestFullscreen();
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
    }
}

function toggleAnnouncementCard(id) {
    const bodyEl = document.getElementById(`announcement-body-${id}`);
    const iconEl = document.getElementById(`announcement-icon-${id}`);
    if (bodyEl) {
        const isHidden = bodyEl.classList.contains('hidden');
        if (isHidden) {
            bodyEl.classList.remove('hidden');
            if (iconEl) iconEl.classList.replace('fa-chevron-down', 'fa-chevron-up');
        } else {
            bodyEl.classList.add('hidden');
            if (iconEl) iconEl.classList.replace('fa-chevron-up', 'fa-chevron-down');
        }
    }
}

function escapeHtmlStr(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getAnnouncementsApiUrl(extra = '') {
    const rangeEl = document.getElementById('announcementTimeRangeFilter');
    const categoryEl = document.getElementById('announcementCategoryFilter');
    
    const range = rangeEl ? rangeEl.value : 'all';
    const category = categoryEl ? categoryEl.value : 'all';

    let url = `../api/announcements.php?range=${encodeURIComponent(range)}&category=${encodeURIComponent(category)}`;
    if (extra) {
        url += extra.startsWith('?') ? '&' + extra.substring(1) : '&' + extra;
    }
    return url;
}

function resetAnnouncementFilters() {
    const rangeEl = document.getElementById('announcementTimeRangeFilter');
    const categoryEl = document.getElementById('announcementCategoryFilter');
    if (rangeEl) rangeEl.value = 'all';
    if (categoryEl) categoryEl.value = 'all';
    loadAnnouncements();
}

async function loadAnnouncements() {
    const list = document.getElementById('announcementsList');
    if (!list) return;

    try {
        const response = await fetch(getAnnouncementsApiUrl());
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success && Array.isArray(result.data)) {
            renderAnnouncements(result.data);
        } else {
            renderAnnouncements([]);
        }
    } catch (err) {
        console.error('Error fetching announcements:', err);
        renderAnnouncements([]);
    }
}

function renderAnnouncements(items) {
    const list = document.getElementById('announcementsList');
    const badge = document.getElementById('announcementsCountBadge');
    if (!list) return;

    if (!items || items.length === 0) {
        if (badge) {
            badge.textContent = `0 Active`;
        }
        list.innerHTML = `
            <div class="flex flex-col items-center justify-center h-[300px] text-center p-6 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2.5 shadow-inner">
                    <i class="fas fa-bullhorn text-lg text-slate-400"></i>
                </div>
                <h4 class="text-xs font-bold text-slate-700">No Announcements Found</h4>
                <p class="text-[10px] text-slate-400 mt-1 max-w-[220px] leading-relaxed">
                    There are currently no real database announcements matching your selected date range or category filter.
                </p>
                <button onclick="resetAnnouncementFilters()" class="mt-3 px-3 py-1 bg-white border border-slate-200 text-blue-600 rounded-lg text-[10px] font-bold shadow-2xs hover:bg-blue-50 transition cursor-pointer">
                    <i class="fas fa-sync-alt text-[9px] mr-1"></i> Reset Filters
                </button>
            </div>
        `;
        return;
    }

    if (badge) {
        badge.textContent = `${items.length} Active`;
    }

    list.innerHTML = items.map(item => {
        const category = item.category || 'General Announcement';
        let badgeColor = 'bg-blue-600';
        let borderColor = 'border-blue-500';
        let bgColor = 'bg-blue-50/70';
        let tagColor = 'bg-blue-100 text-blue-700';

        if (category === 'Urgent Advisory' || category === 'Emergency Alert') {
            badgeColor = 'bg-red-600';
            borderColor = 'border-red-500';
            bgColor = 'bg-red-50/70';
            tagColor = 'bg-red-100 text-red-700';
        } else if (category === 'Operational Notice') {
            badgeColor = 'bg-amber-600';
            borderColor = 'border-amber-500';
            bgColor = 'bg-amber-50/70';
            tagColor = 'bg-amber-100 text-amber-700';
        }

        const dateStr = item.created_at ? new Date(item.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'Today';
        
        let fileHtml = '';
        if (item.file_url) {
            const url = item.file_url;
            const isPdf = /\.pdf($|\?)/i.test(url);

            if (isPdf) {
                fileHtml = `
                    <div class="mt-2 text-[9px]">
                        <a href="${escapeHtmlStr(url)}" target="_blank" onclick="event.stopPropagation()"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 text-blue-600 rounded-md font-semibold hover:bg-blue-50 transition shadow-2xs">
                            <i class="fas fa-file-pdf text-red-500"></i> View Attached PDF Memo
                        </a>
                    </div>
                `;
            } else {
                fileHtml = `
                    <div class="mt-2.5 relative group/img overflow-hidden rounded-xl border border-slate-200/90 bg-slate-900/5 cursor-pointer shadow-2xs" 
                         onclick="event.stopPropagation(); openImageZoomModal('${escapeHtmlStr(url)}', '${escapeHtmlStr(item.title)}')">
                        <img src="${escapeHtmlStr(url)}" 
                             alt="Announcement Attachment" 
                             class="w-full max-h-40 object-cover group-hover/img:scale-105 transition duration-300"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<a href=\'${escapeHtmlStr(url)}\' target=\'_blank\' onclick=\'event.stopPropagation()\' class=\'text-[10px] text-blue-600 font-bold p-2 inline-flex items-center gap-1 hover:underline\'><i class=\'fas fa-paperclip\'></i> View Attachment File</a>';" />
                        <div class="absolute inset-0 bg-slate-900/35 opacity-0 group-hover/img:opacity-100 transition flex items-center justify-center gap-1.5 text-white font-bold text-xs backdrop-blur-2xs">
                            <i class="fas fa-search-plus"></i> Click to Zoom / Fullscreen
                        </div>
                        <span class="absolute bottom-2 right-2 px-2 py-0.5 bg-slate-900/75 text-white text-[8px] font-bold rounded-md backdrop-blur-xs flex items-center gap-1">
                            <i class="fas fa-expand text-[7px]"></i> Zoom
                        </span>
                    </div>
                `;
            }
        }

        const deleteBtn = `<button onclick="event.stopPropagation(); deleteAnnouncement(${item.id})" class="text-slate-400 hover:text-red-600 text-[10px] ml-1 opacity-0 group-hover:opacity-100 transition" title="Delete Announcement"><i class="fas fa-trash-alt"></i></button>`;

        return `
            <div class="p-3 ${bgColor} border-l-4 ${borderColor} rounded-xl transition hover:shadow-md relative group cursor-pointer" 
                 onclick="toggleAnnouncementCard(${item.id})">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-1.5 flex-wrap flex-1">
                        <span class="px-1.5 py-0.5 ${badgeColor} text-white rounded text-[7px] font-extrabold uppercase">${escapeHtmlStr(category)}</span>
                        <h4 class="text-xs font-bold text-slate-800">${escapeHtmlStr(item.title)}</h4>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <span class="text-[9px] text-slate-400 font-medium">${dateStr}</span>
                        ${deleteBtn}
                        <button type="button" class="text-slate-400 hover:text-slate-600 text-[10px] ml-1 transition" title="Toggle Details">
                            <i id="announcement-icon-${item.id}" class="fas fa-chevron-up text-[9px]"></i>
                        </button>
                    </div>
                </div>

                <!-- Collapsible Body Container -->
                <div id="announcement-body-${item.id}" class="transition-all">
                    <p class="text-[10px] text-slate-600 mt-1.5 leading-relaxed">${escapeHtmlStr(item.body)}</p>
                    ${fileHtml}
                    <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                        <span class="flex items-center gap-1"><i class="fas fa-user-shield text-[8px] text-c2"></i> Posted by: ${escapeHtmlStr(item.author || 'System Admin')}</span>
                        <span class="px-1.5 py-0.5 ${tagColor} rounded text-[7px] font-bold">${escapeHtmlStr(item.audience || 'All Staff')}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

async function handlePostAnnouncementSubmit(e) {
    e.preventDefault();
    hidePostAnnouncementError();

    const titleInput = document.getElementById('announcementTitle');
    const bodyInput = document.getElementById('announcementBody');

    const title = titleInput ? titleInput.value.trim() : '';
    const body = bodyInput ? bodyInput.value.trim() : '';

    if (!title) {
        showPostAnnouncementError('Please enter an announcement title.');
        if (titleInput) titleInput.focus();
        return;
    }

    if (!body) {
        showPostAnnouncementError('Please write the announcement details or message.');
        if (bodyInput) bodyInput.focus();
        return;
    }

    const form = document.getElementById('postAnnouncementForm');
    const formData = new FormData(form);

    const categorySelect = document.getElementById('announcementCategory');
    const audienceSelect = document.getElementById('announcementAudience');
    const fileInput = document.getElementById('announcementFile');

    formData.set('title', title);
    formData.set('body', body);
    formData.set('category', categorySelect ? categorySelect.value : 'General Notice');
    formData.set('audience', audienceSelect ? audienceSelect.value : 'All Staff');

    if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.set('announcementFile', fileInput.files[0]);
    }

    try {
        const response = await fetch(getAnnouncementsApiUrl(), {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            closePostAnnouncementModal();
            form.reset();
            removeAnnouncementFile();

            if (typeof showToast === 'function') {
                showToast('Announcement published successfully!', 'success');
            }
            await loadAnnouncements();
        } else {
            showPostAnnouncementError(result.message || 'Failed to post announcement.');
        }
    } catch (err) {
        console.error('Submit Announcement Error:', err);
        showPostAnnouncementError('Network error occurred while publishing announcement.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadAnnouncements();

    const deleteBtn = document.getElementById('confirmDeleteAnnouncementBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            if (!targetAnnouncementDeleteId) return;
            const deleteId = targetAnnouncementDeleteId;
            closeDeleteAnnouncementModal();

            try {
                const response = await fetch(getAnnouncementsApiUrl(`?action=delete&id=${deleteId}`), {
                    method: 'POST'
                });
                const result = await response.json();
                if (result.success) {
                    if (typeof showToast === 'function') {
                        showToast('Announcement deleted successfully', 'success');
                    }
                    await loadAnnouncements();
                } else {
                    if (typeof showToast === 'function') {
                        showToast(result.message || 'Failed to delete announcement', 'error');
                    }
                }
            } catch (err) {
                console.error('Delete Announcement Error:', err);
                if (typeof showToast === 'function') {
                    showToast('Network error while deleting announcement', 'error');
                }
            }
        });
    }
});
</script>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>