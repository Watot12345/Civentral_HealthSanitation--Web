<?php
session_start();

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

<main class="bg-white flex-1 h-full flex flex-col overflow-hidden" role="main" aria-label="Dashboard content">

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

if (hasPermission('dashboard.health_center')) {
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
} elseif (hasPermission('dashboard.system_admin')) {
    $dashTitle = 'System Overview';
    $dashSubtitle = 'Real-time snapshot across all modules and system health';
    $dashBadge = 'System Administrator';
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
// Use permission-based checks (same as page header) — single source of truth
$_isHcRole  = hasPermission('dashboard.health_center');
$_isSanRole = hasPermission('dashboard.sanitation');

if ($_isHcRole) {
    $kpiCards = [
        [
            'title' => 'Patients Served',
            'value' => '3,812',
            'label' => 'Total Patients This Month',
            'badge' => '+9.4%',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/healthservices/patients.php'),
            'icon' => 'fa-users',
            'color' => 'emerald-600',
            'border_color' => 'from-emerald-400 to-emerald-600',
            'offset' => '8',
            'pct' => '92%'
        ],
        [
            'title' => 'Consultations',
            'value' => '2,134',
            'label' => 'Consultations Completed',
            'badge' => '+6.7%',
            'badge_bg' => 'bg-sky-100 text-sky-700',
            'sub' => 'this month',
            'url' => site_url('modules/healthservices/consultations.php'),
            'icon' => 'fa-stethoscope',
            'color' => 'sky-600',
            'border_color' => 'from-sky-400 to-sky-600',
            'offset' => '12',
            'pct' => '88%'
        ],
        [
            'title' => 'Triage Visits',
            'value' => '1,245',
            'label' => 'Patients Triaged',
            'badge' => '+12.3%',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/healthservices/triage.php'),
            'icon' => 'fa-heart-pulse',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => '10',
            'pct' => '90%'
        ],
        [
            'title' => 'Prescriptions Issued',
            'value' => '489',
            'label' => 'Prescriptions Dispensed',
            'badge' => '+8.2%',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'pharmacy fulfilled',
            'url' => site_url('modules/healthservices/prescriptions.php'),
            'icon' => 'fa-prescription-bottle',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => '15',
            'pct' => '85%'
        ],
        [
            'title' => 'Health Surveillance',
            'value' => '234',
            'label' => 'Active Case Reports',
            'badge' => '166 resolved',
            'badge_bg' => 'bg-indigo-100 text-indigo-700',
            'sub' => 'disease monitoring',
            'url' => site_url('modules/surveillence/case_reports.php'),
            'icon' => 'fa-binoculars',
            'color' => 'indigo-600',
            'border_color' => 'from-indigo-400 to-indigo-600',
            'offset' => '32',
            'pct' => '68%'
        ],
        [
            'title' => 'Real-time Alerts',
            'value' => '1',
            'label' => 'Outbreak Watch Alert',
            'badge' => 'Critical Watch',
            'badge_bg' => 'bg-rose-100 text-rose-700',
            'sub' => 'immediate response',
            'url' => site_url('modules/surveillence/alerts.php'),
            'icon' => 'fa-bell',
            'color' => 'rose-600',
            'border_color' => 'from-rose-500 to-red-600',
            'offset' => '5',
            'pct' => '95%'
        ],
    ];
}
// Sanitation & Wastewater KPI cards
elseif ($_isSanRole) {
    $kpiCards = [
        [
            'title' => 'Sanitation Permits',
            'value' => '156',
            'label' => 'Active Permits Issued',
            'badge' => '3 pending',
            'badge_bg' => 'bg-amber-100 text-amber-700',
            'sub' => '87% approval',
            'url' => site_url('modules/sanitation/permit_applications.php'),
            'icon' => 'fa-file-signature',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => '13',
            'pct' => '87%'
        ],
        [
            'title' => 'Field Inspections',
            'value' => '89',
            'label' => 'Inspections Conducted',
            'badge' => '12 today',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'sanitary compliance',
            'url' => site_url('modules/sanitation/inspections.php'),
            'icon' => 'fa-search',
            'color' => 'emerald-600',
            'border_color' => 'from-emerald-400 to-emerald-600',
            'offset' => '10',
            'pct' => '90%'
        ],
        [
            'title' => 'Permit Renewals',
            'value' => '42',
            'label' => 'Renewals Processing',
            'badge' => '5 due soon',
            'badge_bg' => 'bg-blue-100 text-blue-700',
            'sub' => 'annual renewal',
            'url' => site_url('modules/sanitation/renewals.php'),
            'icon' => 'fa-rotate',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => '18',
            'pct' => '82%'
        ],
        [
            'title' => 'Wastewater Requests',
            'value' => '23',
            'label' => 'Desludging Requests',
            'badge' => '5 pending',
            'badge_bg' => 'bg-purple-100 text-purple-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/services/service_requests.php'),
            'icon' => 'fa-water',
            'color' => 'purple-600',
            'border_color' => 'from-purple-400 to-purple-600',
            'offset' => '23',
            'pct' => '77%'
        ],
        [
            'title' => 'Septic Registry',
            'value' => '1,284',
            'label' => 'Registered Tanks',
            'badge' => '+4.1%',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'total recorded',
            'url' => site_url('modules/services/septic_tanks.php'),
            'icon' => 'fa-flask',
            'color' => 'indigo-600',
            'border_color' => 'from-indigo-400 to-indigo-600',
            'offset' => '8',
            'pct' => '92%'
        ],
        [
            'title' => 'Compliance Violations',
            'value' => '5',
            'label' => 'Corrective Action Orders',
            'badge' => '2 unresolved',
            'badge_bg' => 'bg-rose-100 text-rose-700',
            'sub' => 'enforcement active',
            'url' => site_url('pages/compliance_monitoring.php'),
            'icon' => 'fa-gavel',
            'color' => 'rose-600',
            'border_color' => 'from-rose-400 to-rose-600',
            'offset' => '20',
            'pct' => '80%'
        ],
    ];
}
// Default system overview cards (Admin / System-wide)
else {
    $kpiCards = [
        [
            'title' => 'Health Center',
            'value' => '1,847',
            'label' => 'Patients Served',
            'badge' => '+12.5%',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/healthservices/patients.php'),
            'icon' => 'fa-hospital',
            'color' => 'c2',
            'border_color' => 'from-c3 to-c2',
            'offset' => '16',
            'pct' => '84%'
        ],
        [
            'title' => 'Sanitation',
            'value' => '156',
            'label' => 'Active Permits',
            'badge' => '3 pending',
            'badge_bg' => 'bg-amber-100 text-amber-700',
            'sub' => '87% approval',
            'url' => site_url('modules/sanitation/permit_applications.php'),
            'icon' => 'fa-file-signature',
            'color' => 'amber-600',
            'border_color' => 'from-amber-400 to-amber-600',
            'offset' => '13',
            'pct' => '87%'
        ],
        [
            'title' => 'Immunization',
            'value' => '1,924',
            'label' => 'Immunized',
            'badge' => '2 low stock',
            'badge_bg' => 'bg-rose-100 text-rose-700',
            'sub' => '92% coverage',
            'url' => site_url('modules/immunization/child_records.php'),
            'icon' => 'fa-syringe',
            'color' => 'blue-600',
            'border_color' => 'from-blue-400 to-blue-600',
            'offset' => '8',
            'pct' => '92%'
        ],
        [
            'title' => 'Wastewater',
            'value' => '23',
            'label' => 'Service Requests',
            'badge' => '+5%',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => 'vs last month',
            'url' => site_url('modules/services/septic_tanks.php'),
            'icon' => 'fa-water',
            'color' => 'purple-600',
            'border_color' => 'from-purple-400 to-purple-600',
            'offset' => '23',
            'pct' => '77%'
        ],
        [
            'title' => 'Surveillance',
            'value' => '234',
            'label' => 'Active Cases',
            'badge' => '1 outbreak',
            'badge_bg' => 'bg-rose-100 text-rose-700',
            'sub' => '68% resolved',
            'url' => site_url('modules/surveillence/case_reports.php'),
            'icon' => 'fa-binoculars',
            'color' => 'rose-600',
            'border_color' => 'from-rose-400 to-rose-600',
            'offset' => '32',
            'pct' => '68%'
        ],
        [
            'title' => 'System Uptime',
            'value' => '99.97%',
            'label' => 'Running Smoothly',
            'badge' => 'Operational',
            'badge_bg' => 'bg-emerald-100 text-emerald-700',
            'sub' => '199d uptime',
            'url' => site_url('management/system_logs.php'),
            'icon' => 'fa-server',
            'color' => 'indigo-600',
            'border_color' => 'from-indigo-400 to-indigo-600',
            'offset' => '1',
            'pct' => '99.9%'
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
                        'total' => '1,847 registered',
                        'today' => '125 today',
                        'pct' => '84%',
                        'bar' => 'bg-emerald-500',
                        'badge' => 'Healthy',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'stat1' => '12 pending vitals',
                        'stat2' => '342 consults',
                        'bar_width' => '84%'
                    ],
                    [
                        'name' => 'Medical Consultations',
                        'icon' => 'fa-stethoscope',
                        'color' => 'teal-600',
                        'bg' => 'bg-teal-50',
                        'total' => '342 completed',
                        'today' => '34 active today',
                        'pct' => '88%',
                        'bar' => 'bg-teal-500',
                        'badge' => 'Active Queue',
                        'badge_bg' => 'bg-teal-100 text-teal-700',
                        'stat1' => '5 waiting rooms',
                        'stat2' => '5 doctors duty',
                        'bar_width' => '88%'
                    ],
                    [
                        'name' => 'Triage & Screenings',
                        'icon' => 'fa-heart-pulse',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50',
                        'total' => '125 screened',
                        'today' => '2 emergency P1',
                        'pct' => '90%',
                        'bar' => 'bg-amber-500',
                        'badge' => 'Priority Active',
                        'badge_bg' => 'bg-amber-100 text-amber-700',
                        'stat1' => '2 P1 emergency',
                        'stat2' => '5 P2 urgent',
                        'bar_width' => '90%'
                    ],
                    [
                        'name' => 'Prescriptions & Pharmacy',
                        'icon' => 'fa-prescription-bottle',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50',
                        'total' => '489 dispensed',
                        'today' => '42 fulfilled today',
                        'pct' => '85%',
                        'bar' => 'bg-blue-500',
                        'badge' => 'Stock Normal',
                        'badge_bg' => 'bg-blue-100 text-blue-700',
                        'stat1' => '12 reorder alerts',
                        'stat2' => '92% fulfilled',
                        'bar_width' => '85%'
                    ],
                    [
                        'name' => 'Disease Surveillance',
                        'icon' => 'fa-binoculars',
                        'color' => 'rose-600',
                        'bg' => 'bg-rose-50',
                        'total' => '234 cases',
                        'today' => '71 monitoring',
                        'pct' => '68%',
                        'bar' => 'bg-rose-500',
                        'badge' => 'Critical Watch',
                        'badge_bg' => 'bg-rose-100 text-rose-700',
                        'stat1' => '1 outbreak watch',
                        'stat2' => '166 resolved',
                        'bar_width' => '68%'
                    ]
                ];
            } elseif ($_isSanRole) {
                $moduleSummaryCards = [
                    [
                        'name' => 'Sanitation Permits',
                        'icon' => 'fa-file-signature',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50',
                        'total' => '156 active',
                        'today' => '86 today',
                        'pct' => '87%',
                        'bar' => 'bg-amber-500',
                        'badge' => 'Attention',
                        'badge_bg' => 'bg-amber-100 text-amber-700',
                        'stat1' => '3 pending',
                        'stat2' => '89 inspections',
                        'bar_width' => '87%'
                    ],
                    [
                        'name' => 'Field Inspections',
                        'icon' => 'fa-search',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50',
                        'total' => '89 completed',
                        'today' => '12 today',
                        'pct' => '90%',
                        'bar' => 'bg-emerald-500',
                        'badge' => 'Compliant',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'stat1' => '2 follow-ups',
                        'stat2' => '87 passing',
                        'bar_width' => '90%'
                    ],
                    [
                        'name' => 'Permit Renewals',
                        'icon' => 'fa-rotate',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50',
                        'total' => '42 renewals',
                        'today' => '15 today',
                        'pct' => '82%',
                        'bar' => 'bg-blue-500',
                        'badge' => 'Processing',
                        'badge_bg' => 'bg-blue-100 text-blue-700',
                        'stat1' => '5 expiring',
                        'stat2' => '37 approved',
                        'bar_width' => '82%'
                    ],
                    [
                        'name' => 'Wastewater & Septic',
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'bg' => 'bg-purple-50',
                        'total' => '23 requests',
                        'today' => '42 today',
                        'pct' => '77%',
                        'bar' => 'bg-purple-500',
                        'badge' => 'Healthy',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'stat1' => '5 pending',
                        'stat2' => '1,284 tanks',
                        'bar_width' => '77%'
                    ],
                    [
                        'name' => 'Regulatory Compliance',
                        'icon' => 'fa-gavel',
                        'color' => 'rose-600',
                        'bg' => 'bg-rose-50',
                        'total' => '5 orders',
                        'today' => '1 today',
                        'pct' => '80%',
                        'bar' => 'bg-rose-500',
                        'badge' => 'Enforcing',
                        'badge_bg' => 'bg-rose-100 text-rose-700',
                        'stat1' => '2 open',
                        'stat2' => '3 corrected',
                        'bar_width' => '80%'
                    ]
                ];
            } else {
                $moduleSummaryCards = [
                    [
                        'name' => 'Health Center Services',
                        'icon' => 'fa-hospital',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50',
                        'total' => '1,847 total',
                        'today' => '125 today',
                        'pct' => '84%',
                        'bar' => 'bg-emerald-500',
                        'badge' => 'Healthy',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'stat1' => '12 pending',
                        'stat2' => '342 consults',
                        'bar_width' => '84%'
                    ],
                    [
                        'name' => 'Sanitation Permit',
                        'icon' => 'fa-clipboard-check',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50',
                        'total' => '156 total',
                        'today' => '86 today',
                        'pct' => '87%',
                        'bar' => 'bg-amber-500',
                        'badge' => 'Attention',
                        'badge_bg' => 'bg-amber-100 text-amber-700',
                        'stat1' => '3 pending',
                        'stat2' => '89 inspections',
                        'bar_width' => '87%'
                    ],
                    [
                        'name' => 'Immunization Tracker',
                        'icon' => 'fa-syringe',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50',
                        'total' => '1,924 total',
                        'today' => '104 today',
                        'pct' => '92%',
                        'bar' => 'bg-blue-500',
                        'badge' => 'Critical',
                        'badge_bg' => 'bg-rose-100 text-rose-700',
                        'stat1' => '2 low stock',
                        'stat2' => '92% coverage',
                        'bar_width' => '92%'
                    ],
                    [
                        'name' => 'Wastewater Services',
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'bg' => 'bg-purple-50',
                        'total' => '23 total',
                        'today' => '42 today',
                        'pct' => '77%',
                        'bar' => 'bg-purple-500',
                        'badge' => 'Healthy',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'stat1' => '5 pending',
                        'stat2' => '1,284 tanks',
                        'bar_width' => '77%'
                    ],
                    [
                        'name' => 'Health Surveillance',
                        'icon' => 'fa-binoculars',
                        'color' => 'rose-600',
                        'bg' => 'bg-rose-50',
                        'total' => '234 total',
                        'today' => '71 today',
                        'pct' => '68%',
                        'bar' => 'bg-rose-500',
                        'badge' => 'Critical',
                        'badge_bg' => 'bg-rose-100 text-rose-700',
                        'stat1' => '1 outbreak',
                        'stat2' => '166 resolved',
                        'bar_width' => '68%'
                    ]
                ];
            }
            ?>
            <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
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
            <!-- COLUMN 2: Alerts & Notifications                            -->
            <!-- ============================================================ -->
            <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm flex flex-col h-[400px] lg:h-[420px]">
                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-c3">
                        <i class="fas fa-bell" aria-hidden="true"></i> Alerts &amp; Notifications
                        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[9px] font-bold ml-1">
                            <i class="fas fa-exclamation-circle text-[8px] mr-1" aria-hidden="true"></i> 4 New
                        </span>
                    </div>
                    <button onclick="markAllRead()"
                            class="text-[10px] text-c2 hover:text-c3 font-semibold transition-colors"
                            aria-label="Mark all notifications as read">
                        <i class="fas fa-check-circle text-[10px] mr-1" aria-hidden="true"></i> Mark all read
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto space-y-3 pr-1 custom-scroll">

                    <?php if ($_isSanRole): ?>

                    <!-- SANITATION ALERTS -->

                    <!-- Alert 1: Critical - Permit Expiry -->
                    <div class="p-3 bg-rose-50 border-l-4 border-rose-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-rose-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-rose-700">Permit Expiry Alert</p>
                                        <span class="px-1.5 py-0.5 bg-rose-200 text-rose-800 rounded text-[7px] font-bold">CRITICAL</span>
                                    </div>
                                    <span class="text-[9px] text-rose-500 flex-shrink-0">1 hour ago</span>
                                </div>
                                <p class="text-[10px] text-rose-600 mt-0.5">5 sanitation permits expire within 7 days</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-rose-600 text-white rounded text-[9px] font-semibold hover:bg-rose-700 transition">
                                        <i class="fas fa-eye text-[8px] mr-1" aria-hidden="true"></i> View
                                    </button>
                                    <button class="px-2.5 py-1 bg-white text-rose-600 rounded text-[9px] font-semibold border border-rose-200 hover:bg-rose-50 transition">
                                        <i class="fas fa-times text-[8px] mr-1" aria-hidden="true"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 2: Warning - Compliance Violation -->
                    <div class="p-3 bg-amber-50 border-l-4 border-amber-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-amber-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-amber-700">Compliance Violation Found</p>
                                        <span class="px-1.5 py-0.5 bg-amber-200 text-amber-800 rounded text-[7px] font-bold">WARNING</span>
                                    </div>
                                    <span class="text-[9px] text-amber-500 flex-shrink-0">2 hours ago</span>
                                </div>
                                <p class="text-[10px] text-amber-600 mt-0.5">2 establishments failed sanitary inspection — Brgy. Poblacion</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-amber-600 text-white rounded text-[9px] font-semibold hover:bg-amber-700 transition">
                                        <i class="fas fa-gavel text-[8px] mr-1" aria-hidden="true"></i> Issue Order
                                    </button>
                                    <button class="px-2.5 py-1 bg-white text-amber-600 rounded text-[9px] font-semibold border border-amber-200 hover:bg-amber-50 transition">
                                        <i class="fas fa-times text-[8px] mr-1" aria-hidden="true"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 3: Info - Inspection Schedule -->
                    <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-calendar-check text-blue-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-blue-700">Inspection Schedule Tomorrow</p>
                                        <span class="px-1.5 py-0.5 bg-blue-200 text-blue-800 rounded text-[7px] font-bold">INFO</span>
                                    </div>
                                    <span class="text-[9px] text-blue-500 flex-shrink-0">3 hours ago</span>
                                </div>
                                <p class="text-[10px] text-blue-600 mt-0.5">12 field inspections scheduled for Brgy. San Miguel & Sta. Cruz</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-[9px] font-semibold hover:bg-blue-700 transition">
                                        <i class="fas fa-eye text-[8px] mr-1" aria-hidden="true"></i> View
                                    </button>
                                    <button class="px-2.5 py-1 bg-white text-blue-600 rounded text-[9px] font-semibold border border-blue-200 hover:bg-blue-50 transition">
                                        <i class="fas fa-times text-[8px] mr-1" aria-hidden="true"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 4: Success - New Permit Application -->
                    <div class="p-3 bg-emerald-50 border-l-4 border-emerald-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check-circle text-emerald-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-emerald-700">New Permit Application</p>
                                        <span class="px-1.5 py-0.5 bg-emerald-200 text-emerald-800 rounded text-[7px] font-bold">SUCCESS</span>
                                    </div>
                                    <span class="text-[9px] text-emerald-500 flex-shrink-0">15 min ago</span>
                                </div>
                                <p class="text-[10px] text-emerald-600 mt-0.5">Permit #SP-2026-0189 submitted — Aling Maria's Carinderia</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-emerald-600 text-white rounded text-[9px] font-semibold hover:bg-emerald-700 transition">
                                        <i class="fas fa-eye text-[8px] mr-1" aria-hidden="true"></i> Review
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 5: Desludging Request -->
                    <div class="p-3 bg-purple-50 border-l-4 border-purple-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-water text-purple-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-purple-700">Desludging Request Pending</p>
                                        <span class="px-1.5 py-0.5 bg-purple-200 text-purple-800 rounded text-[7px] font-bold">PENDING</span>
                                    </div>
                                    <span class="text-[9px] text-purple-500 flex-shrink-0">30 min ago</span>
                                </div>
                                <p class="text-[10px] text-purple-600 mt-0.5">5 wastewater desludging requests awaiting assignment</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-purple-600 text-white rounded text-[9px] font-semibold hover:bg-purple-700 transition">
                                        <i class="fas fa-tasks text-[8px] mr-1" aria-hidden="true"></i> Assign
                                    </button>
                                    <button class="px-2.5 py-1 bg-white text-purple-600 rounded text-[9px] font-semibold border border-purple-200 hover:bg-purple-50 transition">
                                        <i class="fas fa-times text-[8px] mr-1" aria-hidden="true"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php else: ?>

                    <!-- HEALTH CENTER / DEFAULT ALERTS -->

                    <!-- Alert 1: Critical -->
                    <div class="p-3 bg-rose-50 border-l-4 border-rose-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-rose-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-rose-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-rose-700">Disease Outbreak Alert</p>
                                        <span class="px-1.5 py-0.5 bg-rose-200 text-rose-800 rounded text-[7px] font-bold">CRITICAL</span>
                                    </div>
                                    <span class="text-[9px] text-rose-500 flex-shrink-0">2 hours ago</span>
                                </div>
                                <p class="text-[10px] text-rose-600 mt-0.5">Dengue outbreak detected in Barangay San Jose</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-rose-600 text-white rounded text-[9px] font-semibold hover:bg-rose-700 transition">
                                        <i class="fas fa-eye text-[8px] mr-1" aria-hidden="true"></i> View
                                    </button>
                                    <button class="px-2.5 py-1 bg-white text-rose-600 rounded text-[9px] font-semibold border border-rose-200 hover:bg-rose-50 transition">
                                        <i class="fas fa-times text-[8px] mr-1" aria-hidden="true"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 2: Warning -->
                    <div class="p-3 bg-amber-50 border-l-4 border-amber-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-amber-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-amber-700">Vaccine Stocks Running Low</p>
                                        <span class="px-1.5 py-0.5 bg-amber-200 text-amber-800 rounded text-[7px] font-bold">WARNING</span>
                                    </div>
                                    <span class="text-[9px] text-amber-500 flex-shrink-0">1 hour ago</span>
                                </div>
                                <p class="text-[10px] text-amber-600 mt-0.5">MMR and Dengue vaccines below threshold levels</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-amber-600 text-white rounded text-[9px] font-semibold hover:bg-amber-700 transition">
                                        <i class="fas fa-cart-plus text-[8px] mr-1" aria-hidden="true"></i> Reorder
                                    </button>
                                    <button class="px-2.5 py-1 bg-white text-amber-600 rounded text-[9px] font-semibold border border-amber-200 hover:bg-amber-50 transition">
                                        <i class="fas fa-times text-[8px] mr-1" aria-hidden="true"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 3: Info -->
                    <div class="p-3 bg-emerald-50 border-l-4 border-emerald-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check-circle text-emerald-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-emerald-700">Pending Permit Inspections</p>
                                        <span class="px-1.5 py-0.5 bg-emerald-200 text-emerald-800 rounded text-[7px] font-bold">INFO</span>
                                    </div>
                                    <span class="text-[9px] text-emerald-500 flex-shrink-0">3 hours ago</span>
                                </div>
                                <p class="text-[10px] text-emerald-600 mt-0.5">Inspections scheduled for tomorrow</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-emerald-600 text-white rounded text-[9px] font-semibold hover:bg-emerald-700 transition">
                                        <i class="fas fa-eye text-[8px] mr-1" aria-hidden="true"></i> View
                                    </button>
                                    <button class="px-2.5 py-1 bg-white text-emerald-600 rounded text-[9px] font-semibold border border-emerald-200 hover:bg-emerald-50 transition">
                                        <i class="fas fa-times text-[8px] mr-1" aria-hidden="true"></i> Dismiss
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 4: System -->
                    <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-info-circle text-blue-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-blue-700">System Backup Completed</p>
                                        <span class="px-1.5 py-0.5 bg-blue-200 text-blue-800 rounded text-[7px] font-bold">SYSTEM</span>
                                    </div>
                                    <span class="text-[9px] text-blue-500 flex-shrink-0">5 hours ago</span>
                                </div>
                                <p class="text-[10px] text-blue-600 mt-0.5">Scheduled backup completed successfully</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-blue-600 text-white rounded text-[9px] font-semibold hover:bg-blue-700 transition">
                                        <i class="fas fa-file-alt text-[8px] mr-1" aria-hidden="true"></i> View Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert 5: Success -->
                    <div class="p-3 bg-emerald-50 border-l-4 border-emerald-500 rounded-xl" role="alert">
                        <div class="flex items-start gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check-circle text-emerald-500 text-sm" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-xs font-bold text-emerald-700">New Patient Registered</p>
                                        <span class="px-1.5 py-0.5 bg-emerald-200 text-emerald-800 rounded text-[7px] font-bold">SUCCESS</span>
                                    </div>
                                    <span class="text-[9px] text-emerald-500 flex-shrink-0">10 min ago</span>
                                </div>
                                <p class="text-[10px] text-emerald-600 mt-0.5">Patient #1123 successfully registered</p>
                                <div class="flex gap-2 mt-2">
                                    <button class="px-2.5 py-1 bg-emerald-600 text-white rounded text-[9px] font-semibold hover:bg-emerald-700 transition">
                                        <i class="fas fa-eye text-[8px] mr-1" aria-hidden="true"></i> View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php endif; ?>

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
            <?php else: ?>
            <?php if ($_isSanRole): ?>
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
            <?php endif; ?>


        </div>

        <!-- ============================================================ -->
        <!-- ACTIVITY FEED                                                 -->
        <!-- ============================================================ -->
        <div class="bg-white rounded-2xl p-4 border border-c1/25 shadow-sm mt-4 flex-shrink-0">
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

    function handleQuickActionSubmit(event) {
        event.preventDefault();
        const actionId = document.getElementById('quickActionType').value;
        const schema = QUICK_ACTION_SCHEMAS[actionId];
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

    const actionBar = document.getElementById('bottomActionBar');

    // Function to show bar with animation
    function showActionBar() {
        if (!actionBar) return;
        clearTimeout(hideTimer);
        
        // Remove hidden class and show with animation
        actionBar.style.opacity = '1';
        actionBar.style.transform = 'translateX(-50%) translateY(0)';
        actionBar.style.pointerEvents = 'auto';
        actionBar.classList.remove('hidden');
        
        isBarVisible = true;
        barHidden = false;
        
        // Log for debugging
        console.log('🔽 Action bar shown');
    }

    // Function to hide bar with animation
    function hideActionBar(instant = false) {
        if (!actionBar) return;
        clearTimeout(hideTimer);
        
        if (instant) {
            actionBar.style.opacity = '0';
            actionBar.style.transform = 'translateX(-50%) translateY(30px)';
            actionBar.style.pointerEvents = 'none';
            // Don't hide completely, just make invisible
        } else {
            actionBar.style.opacity = '0';
            actionBar.style.transform = 'translateX(-50%) translateY(30px)';
            actionBar.style.pointerEvents = 'none';
        }
        
        isBarVisible = false;
        barHidden = true;
        
        console.log('⬆️ Action bar hidden');
    }

    // Schedule auto-hide after 4 seconds of inactivity
    function scheduleHide(delay = 4000) {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            if (!mouseNearBottom && !actionBar?.matches(':hover')) {
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
        else if (!isNearBottom && !barHidden && !actionBar?.matches(':hover')) {
            scheduleHide(3000);
        }
    });

    // ===== HOVER ON BAR =====
    if (actionBar) {
        actionBar.addEventListener('mouseenter', function() {
            clearTimeout(hideTimer);
            if (barHidden) {
                showActionBar();
            }
            console.log('🖱️ Mouse entered action bar');
        });

        actionBar.addEventListener('mouseleave', function() {
            if (!mouseNearBottom) {
                scheduleHide(3000);
            }
            console.log('🖱️ Mouse left action bar');
        });
    }

    // ===== KEYBOARD SHORTCUTS =====
    document.addEventListener('keydown', function(e) {
        // Alt+B to toggle bar
        if (e.altKey && (e.key === 'b' || e.key === 'B')) {
            e.preventDefault();
            if (isBarVisible) {
                hideActionBar(true);
                console.log('⌨️ Bar hidden via Alt+B');
            } else {
                showActionBar();
                scheduleHide(4000);
                console.log('⌨️ Bar shown via Alt+B');
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

    // ===== ENSURE BAR IS HIDDEN ON PAGE LOAD =====
    document.addEventListener('DOMContentLoaded', function() {
        if (actionBar) {
            actionBar.style.opacity = '0';
            actionBar.style.transform = 'translateX(-50%) translateY(30px)';
            actionBar.style.pointerEvents = 'none';
            barHidden = true;
            isBarVisible = false;
            console.log('🔽 Quick action bar initialized - hidden by default');
            console.log('💡 Scroll down or move mouse near bottom to show');
            console.log('⌨️ Press Alt+B to toggle');
            
            // Start data age counter
            ageInterval = setInterval(updateDataAge, 1000);
        } else {
            console.error('❌ bottomActionBar element not found!');
        }
    });

    // Also ensure hidden after full page load
    window.addEventListener('load', function() {
        if (actionBar && barHidden) {
            actionBar.style.opacity = '0';
            actionBar.style.transform = 'translateX(-50%) translateY(30px)';
            actionBar.style.pointerEvents = 'none';
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
    console.log('  Alt+7  - More Menu');
    console.log('  ESC    - Close menus & dismiss toasts');
</script>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>