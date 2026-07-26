<?php
session_start();


 include '../includes/header.php';
 include '../includes/sidebar.php'; 
?>
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
        .dashboard-content {
            padding-bottom: 90px;
        }
        @media (max-width: 1023px) {
            .dashboard-content {
                padding-bottom: 100px;
            }
        }
    </style>

    <!-- ===== PAGE CONTAINER ===== -->
    <div class="flex-1 px-6 pt-[26px] pb-4 flex flex-col min-h-0 overflow-y-auto custom-scroll animate-fadeOverlay dashboard-content">

        <!-- ============================================================ -->
        <!-- PAGE HEADER                                                  -->
        <!-- ============================================================ -->
        <div class="flex-shrink-0 mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-xl font-bold text-c3 flex items-center gap-2">
                        <i class="fas fa-gauge-high text-c2" aria-hidden="true"></i>
                        System Overview
                    </h1>
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[9px] font-semibold">
                        <i class="fas fa-user-shield text-[8px] mr-1" aria-hidden="true"></i> Admin
                    </span>
                    <span class="flex items-center gap-1.5 text-[10px] text-emerald-600 ml-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse2" aria-hidden="true"></span>
                        Live
                    </span>
                </div>
                <p class="text-sm text-[#4a6080] mt-0.5">Real-time snapshot across all modules and system health</p>
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
        <!-- KPI ROW (6 cards)                                            -->
        <!-- ============================================================ -->
        <div class="kpi-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6 flex-shrink-0">

            <!-- KPI 1: Health Center Services -->
            <a href="health-center.php" 
               class="kpi-card relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-c2/20 hover:border-c2/40 cursor-pointer group block"
               aria-label="Health Center Services: 1,847 patients served, 12.5% increase">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-c1/30 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-hospital kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-c2/10 rotate-[-8deg] pointer-events-none" aria-hidden="true"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-c3 to-c2"></div>
                <div class="relative p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-c2">
                                <i class="fas fa-hospital text-[7px] mr-1" aria-hidden="true"></i>Health Center
                            </p>
                            <p class="kpi-number text-xl font-black text-slate-900 mt-1 leading-none">1,847</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Patients Served</p>
                        </div>
                        <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#176B87" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:16" transform="rotate(-90 18 18)"/>
                            <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#176B87">84%</text>
                        </svg>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                            <i class="fas fa-arrow-up text-[5px] mr-0.5" aria-hidden="true"></i> 12.5%
                        </span>
                        <span class="text-[7px] text-slate-400">vs last month</span>
                        <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70" aria-hidden="true">
                            <polyline class="kpi-spark" points="0,16 10,14 20,15 30,10 40,11 50,4 60,3" fill="none" stroke="#176B87" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- KPI 2: Sanitation Permit & Inspection -->
            <a href="sanitation-permits.php" 
               class="kpi-card relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-amber-200/50 hover:border-amber-300 cursor-pointer group block"
               aria-label="Sanitation: 156 active permits, 3 pending">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-amber-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-file-signature kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-amber-500/10 rotate-[-8deg] pointer-events-none" aria-hidden="true"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-amber-400 to-amber-600"></div>
                <div class="relative p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-amber-600">
                                <i class="fas fa-file-signature text-[7px] mr-1" aria-hidden="true"></i>Sanitation
                            </p>
                            <p class="kpi-number text-xl font-black text-amber-600 mt-1 leading-none">156</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Active Permits</p>
                        </div>
                        <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#fde8c8" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#d97706" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:13" transform="rotate(-90 18 18)"/>
                            <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#d97706">87%</text>
                        </svg>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[7px] font-bold">
                            <i class="fas fa-clock text-[5px] mr-0.5" aria-hidden="true"></i> 3 pending
                        </span>
                        <span class="text-[7px] text-slate-400">87% approval</span>
                        <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70" aria-hidden="true">
                            <polyline class="kpi-spark" points="0,10 10,12 20,8 30,9 40,6 50,7 60,4" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- KPI 3: Immunization & Nutrition Tracker -->
            <a href="immunization-nutrition.php" 
               class="kpi-card relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-blue-200/50 hover:border-blue-300 cursor-pointer group block"
               aria-label="Immunization: 1,924 immunized, 2 low stock items">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-syringe kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-blue-500/10 rotate-[-8deg] pointer-events-none" aria-hidden="true"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-blue-400 to-blue-600"></div>
                <div class="relative p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-blue-600">
                                <i class="fas fa-syringe text-[7px] mr-1" aria-hidden="true"></i>Immunization
                            </p>
                            <p class="kpi-number text-xl font-black text-blue-600 mt-1 leading-none">1,924</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Immunized</p>
                        </div>
                        <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#dbeafe" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#2563eb" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:8" transform="rotate(-90 18 18)"/>
                            <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#2563eb">92%</text>
                        </svg>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[7px] font-bold">
                            <i class="fas fa-exclamation-triangle text-[5px] mr-0.5" aria-hidden="true"></i> 2 low stock
                        </span>
                        <span class="text-[7px] text-slate-400">92% coverage</span>
                        <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70" aria-hidden="true">
                            <polyline class="kpi-spark" points="0,8 10,10 20,7 30,11 40,9 50,13 60,12" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- KPI 4: Wastewater & Septic Services -->
            <a href="wastewater-septic.php" 
               class="kpi-card relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-purple-200/50 hover:border-purple-300 cursor-pointer group block"
               aria-label="Wastewater: 23 service requests, 5% increase">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-water kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-purple-500/10 rotate-[-8deg] pointer-events-none" aria-hidden="true"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-purple-400 to-purple-600"></div>
                <div class="relative p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-purple-600">
                                <i class="fas fa-water text-[7px] mr-1" aria-hidden="true"></i>Wastewater
                            </p>
                            <p class="kpi-number text-xl font-black text-purple-600 mt-1 leading-none">23</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Service Requests</p>
                        </div>
                        <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#f1e3fb" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#9333ea" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:23" transform="rotate(-90 18 18)"/>
                            <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#9333ea">77%</text>
                        </svg>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                            <i class="fas fa-arrow-up text-[5px] mr-0.5" aria-hidden="true"></i> 5%
                        </span>
                        <span class="text-[7px] text-slate-400">vs last month</span>
                        <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70" aria-hidden="true">
                            <polyline class="kpi-spark" points="0,14 10,13 20,15 30,10 40,12 50,8 60,9" fill="none" stroke="#9333ea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- KPI 5: Health Surveillance System -->
            <a href="health-surveillance.php" 
               class="kpi-card relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-rose-200/50 hover:border-rose-300 cursor-pointer group block"
               aria-label="Surveillance: 234 active cases, 1 outbreak">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-rose-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-binoculars kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-rose-500/10 rotate-[-8deg] pointer-events-none" aria-hidden="true"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-rose-400 to-rose-600"></div>
                <div class="relative p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-rose-600">
                                <i class="fas fa-binoculars text-[7px] mr-1" aria-hidden="true"></i>Surveillance
                            </p>
                            <p class="kpi-number text-xl font-black text-rose-600 mt-1 leading-none">234</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Active Cases</p>
                        </div>
                        <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#fce1e7" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e11d48" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:32" transform="rotate(-90 18 18)"/>
                            <text x="18" y="20.5" text-anchor="middle" font-size="8" font-weight="700" fill="#e11d48">68%</text>
                        </svg>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[7px] font-bold">
                            <i class="fas fa-exclamation-triangle text-[5px] mr-0.5" aria-hidden="true"></i> 1 outbreak
                        </span>
                        <span class="text-[7px] text-slate-400">68% resolved</span>
                        <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70" aria-hidden="true">
                            <polyline class="kpi-spark" points="0,6 10,9 20,7 30,12 40,10 50,15 60,17" fill="none" stroke="#e11d48" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
            </a>

            <!-- KPI 6: System Uptime -->
            <a href="system-status.php" 
               class="kpi-card relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-indigo-200/50 hover:border-indigo-300 cursor-pointer group block"
               aria-label="System uptime: 99.97%, 199 days running">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-server kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-indigo-500/10 rotate-[-8deg] pointer-events-none" aria-hidden="true"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-indigo-400 to-indigo-600"></div>
                <div class="relative p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-indigo-600">
                                <i class="fas fa-server text-[7px] mr-1" aria-hidden="true"></i>System Uptime
                            </p>
                            <p class="kpi-number text-xl font-black text-indigo-600 mt-1 leading-none">99.97%</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">199d 02h 14m running</p>
                        </div>
                        <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0" aria-hidden="true">
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#E0E7FF" stroke-width="3"/>
                            <circle cx="18" cy="18" r="15.5" fill="none" stroke="#4F46E5" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:1" transform="rotate(-90 18 18)"/>
                            <text x="18" y="20.5" text-anchor="middle" font-size="7.5" font-weight="700" fill="#4F46E5">99.9%</text>
                        </svg>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                        <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                            <i class="fas fa-check-circle text-[5px] mr-0.5" aria-hidden="true"></i> Operational
                        </span>
                        <span class="text-[7px] text-slate-400">Last check: 2 min ago</span>
                        <div class="flex items-center gap-0.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse2" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>
            </a>

        </div>

        <!-- ============================================================ -->
        <!-- 3-COLUMN LAYOUT: Module Summary | Alerts | System Health     -->
        <!-- ============================================================ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 flex-shrink-0">

            <!-- ========================================================== -->
            <!-- COLUMN 1: Module Activity Summary (STANDARDIZED)          -->
            <!-- ========================================================== -->
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

                    <!-- Module 1: Health Center Services -->
                    <div class="module-card p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-hospital text-emerald-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Health Center Services</p>
                                    <div class="flex items-center gap-2 text-[9px] text-slate-400">
                                        <span><i class="fas fa-users text-[7px] mr-0.5" aria-hidden="true"></i>1,847 total</span>
                                        <span><i class="fas fa-calendar-day text-[7px] mr-0.5" aria-hidden="true"></i>125 today</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold text-emerald-600 block">84%</span>
                                <span class="text-[7px] text-slate-400">completion</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex gap-3 text-[8px]">
                                <span class="text-amber-600"><i class="fas fa-clock text-[6px] mr-0.5" aria-hidden="true"></i>12 pending</span>
                                <span class="text-emerald-600"><i class="fas fa-check-circle text-[6px] mr-0.5" aria-hidden="true"></i>342 consults</span>
                            </div>
                            <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                                <i class="fas fa-heartbeat text-[5px] mr-0.5" aria-hidden="true"></i> Healthy
                            </span>
                        </div>
                        <div class="mt-1.5">
                            <div class="w-full h-1 bg-slate-200 rounded overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded" style="width:84%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Module 2: Sanitation -->
                    <div class="module-card p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-clipboard-check text-amber-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Sanitation Permit</p>
                                    <div class="flex items-center gap-2 text-[9px] text-slate-400">
                                        <span><i class="fas fa-file-signature text-[7px] mr-0.5" aria-hidden="true"></i>156 total</span>
                                        <span><i class="fas fa-calendar-day text-[7px] mr-0.5" aria-hidden="true"></i>86 today</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold text-amber-600 block">87%</span>
                                <span class="text-[7px] text-slate-400">completion</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex gap-3 text-[8px]">
                                <span class="text-amber-600"><i class="fas fa-clock text-[6px] mr-0.5" aria-hidden="true"></i>3 pending</span>
                                <span class="text-blue-600"><i class="fas fa-search text-[6px] mr-0.5" aria-hidden="true"></i>89 inspections</span>
                            </div>
                            <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[7px] font-bold">
                                <i class="fas fa-exclamation-triangle text-[5px] mr-0.5" aria-hidden="true"></i> Attention
                            </span>
                        </div>
                        <div class="mt-1.5">
                            <div class="w-full h-1 bg-slate-200 rounded overflow-hidden">
                                <div class="h-full bg-amber-500 rounded" style="width:87%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Module 3: Immunization -->
                    <div class="module-card p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-syringe text-blue-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Immunization Tracker</p>
                                    <div class="flex items-center gap-2 text-[9px] text-slate-400">
                                        <span><i class="fas fa-syringe text-[7px] mr-0.5" aria-hidden="true"></i>1,924 total</span>
                                        <span><i class="fas fa-calendar-day text-[7px] mr-0.5" aria-hidden="true"></i>104 today</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold text-blue-600 block">92%</span>
                                <span class="text-[7px] text-slate-400">completion</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex gap-3 text-[8px]">
                                <span class="text-rose-600"><i class="fas fa-exclamation-triangle text-[6px] mr-0.5" aria-hidden="true"></i>2 low stock</span>
                                <span class="text-emerald-600"><i class="fas fa-check-circle text-[6px] mr-0.5" aria-hidden="true"></i>92% coverage</span>
                            </div>
                            <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[7px] font-bold">
                                <i class="fas fa-exclamation-triangle text-[5px] mr-0.5" aria-hidden="true"></i> Critical
                            </span>
                        </div>
                        <div class="mt-1.5">
                            <div class="w-full h-1 bg-slate-200 rounded overflow-hidden">
                                <div class="h-full bg-blue-500 rounded" style="width:92%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Module 4: Wastewater -->
                    <div class="module-card p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-water text-purple-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Wastewater Services</p>
                                    <div class="flex items-center gap-2 text-[9px] text-slate-400">
                                        <span><i class="fas fa-tools text-[7px] mr-0.5" aria-hidden="true"></i>23 total</span>
                                        <span><i class="fas fa-calendar-day text-[7px] mr-0.5" aria-hidden="true"></i>42 today</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold text-purple-600 block">77%</span>
                                <span class="text-[7px] text-slate-400">completion</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex gap-3 text-[8px]">
                                <span class="text-amber-600"><i class="fas fa-clock text-[6px] mr-0.5" aria-hidden="true"></i>5 pending</span>
                                <span class="text-purple-600"><i class="fas fa-flask text-[6px] mr-0.5" aria-hidden="true"></i>1,284 tanks</span>
                            </div>
                            <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                                <i class="fas fa-check-circle text-[5px] mr-0.5" aria-hidden="true"></i> Healthy
                            </span>
                        </div>
                        <div class="mt-1.5">
                            <div class="w-full h-1 bg-slate-200 rounded overflow-hidden">
                                <div class="h-full bg-purple-500 rounded" style="width:77%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Module 5: Surveillance -->
                    <div class="module-card p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-binoculars text-rose-600 text-sm" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-[#1a2e44]">Health Surveillance</p>
                                    <div class="flex items-center gap-2 text-[9px] text-slate-400">
                                        <span><i class="fas fa-chart-bar text-[7px] mr-0.5" aria-hidden="true"></i>234 total</span>
                                        <span><i class="fas fa-calendar-day text-[7px] mr-0.5" aria-hidden="true"></i>71 today</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] font-bold text-rose-600 block">68%</span>
                                <span class="text-[7px] text-slate-400">completion</span>
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex gap-3 text-[8px]">
                                <span class="text-rose-600"><i class="fas fa-exclamation-triangle text-[6px] mr-0.5" aria-hidden="true"></i>1 outbreak</span>
                                <span class="text-blue-600"><i class="fas fa-check-circle text-[6px] mr-0.5" aria-hidden="true"></i>166 resolved</span>
                            </div>
                            <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[7px] font-bold">
                                <i class="fas fa-exclamation-triangle text-[5px] mr-0.5" aria-hidden="true"></i> Critical
                            </span>
                        </div>
                        <div class="mt-1.5">
                            <div class="w-full h-1 bg-slate-200 rounded overflow-hidden">
                                <div class="h-full bg-rose-500 rounded" style="width:68%"></div>
                            </div>
                        </div>
                    </div>

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

                </div>
            </div>

            <!-- ============================================================ -->
            <!-- COLUMN 3: System Health Status                             -->
            <!-- ============================================================ -->
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
                <!-- Activity 1 -->
                <div class="activity-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                    <div class="activity-avatar bg-emerald-500">JD</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between flex-wrap gap-1">
                            <p class="text-xs font-semibold text-slate-800">Juan Dela Cruz</p>
                            <span class="text-[9px] text-slate-400">Today, 10:42 AM</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-[8px] font-semibold">
                                <i class="fas fa-check-circle text-[7px] mr-0.5" aria-hidden="true"></i> Approved
                            </span>
                            <span class="text-[9px] text-slate-500">Sanitation Permit</span>
                            <span class="text-[9px] text-slate-400">Permit #0156</span>
                        </div>
                    </div>
                </div>
                
                <!-- Activity 2 -->
                <div class="activity-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                    <div class="activity-avatar bg-blue-500">MS</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between flex-wrap gap-1">
                            <p class="text-xs font-semibold text-slate-800">Maria Santos</p>
                            <span class="text-[9px] text-slate-400">Today, 10:15 AM</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded-full text-[8px] font-semibold">
                                <i class="fas fa-syringe text-[7px] mr-0.5" aria-hidden="true"></i> Logged
                            </span>
                            <span class="text-[9px] text-slate-500">Immunization</span>
                            <span class="text-[9px] text-slate-400">Patient #2211</span>
                        </div>
                    </div>
                </div>
                
                <!-- Activity 3 -->
                <div class="activity-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                    <div class="activity-avatar bg-rose-500">PR</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between flex-wrap gap-1">
                            <p class="text-xs font-semibold text-slate-800">Pedro Reyes</p>
                            <span class="text-[9px] text-slate-400">Today, 9:02 AM</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-1.5 py-0.5 bg-rose-50 text-rose-700 rounded-full text-[8px] font-semibold">
                                <i class="fas fa-flag text-[7px] mr-0.5" aria-hidden="true"></i> Flagged
                            </span>
                            <span class="text-[9px] text-slate-500">Health Surveillance</span>
                            <span class="text-[9px] text-slate-400">Case #0234 (Dengue)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Activity 4 -->
                <div class="activity-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                    <div class="activity-avatar bg-amber-500">AL</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between flex-wrap gap-1">
                            <p class="text-xs font-semibold text-slate-800">Ana Lim</p>
                            <span class="text-[9px] text-slate-400">Yesterday, 4:30 PM</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 rounded-full text-[8px] font-semibold">
                                <i class="fas fa-calendar text-[7px] mr-0.5" aria-hidden="true"></i> Scheduled
                            </span>
                            <span class="text-[9px] text-slate-500">Sanitation Permit</span>
                            <span class="text-[9px] text-slate-400">Inspection #089</span>
                        </div>
                    </div>
                </div>
                
                <!-- Activity 5 -->
                <div class="activity-item flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                    <div class="activity-avatar bg-slate-500">SY</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between flex-wrap gap-1">
                            <p class="text-xs font-semibold text-slate-800">System</p>
                            <span class="text-[9px] text-slate-400">Today, 2:00 AM</span>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[8px] font-semibold">
                                <i class="fas fa-database text-[7px] mr-0.5" aria-hidden="true"></i> Backup
                            </span>
                            <span class="text-[9px] text-slate-500">System</span>
                            <span class="text-[9px] text-slate-400">Full DB Backup</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>   
       <!-- ============================================================ -->
<!-- QUICK ACTION BAR - Right Aligned with Clean Animation        -->
<!-- ============================================================ -->
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
            
            <!-- Action 1: New Patient -->
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
            
            <!-- Action 2: New Permit -->
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
            
            <!-- Action 3: Vaccinate (Highlighted) -->
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
            
            <!-- Action 4: Report Case -->
            <button onclick="openModal('report-case')" 
                    class="action-btn group relative flex items-center gap-1.5 px-2 py-1.5 rounded-xl hover:bg-rose-50 transition-all duration-200"
                    aria-label="Report new health case"
                    data-label="Report">
                <div class="w-7 h-7 rounded-lg bg-rose-50 group-hover:bg-rose-100 flex items-center justify-center transition-all duration-200 group-hover:scale-105">
                    <i class="fas fa-flag text-rose-600 text-xs" aria-hidden="true"></i>
                </div>
                <span class="action-label text-[9px] font-medium text-slate-700 group-hover:text-rose-700 transition-all duration-300 max-w-0 opacity-0 group-hover:max-w-[60px] group-hover:opacity-100 overflow-hidden whitespace-nowrap">
                   flag Report
                </span>
            </button>
            
            <!-- Action 5: Schedule -->
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
            
            <div class="w-px h-6 bg-slate-200/60 mx-0.5"></div>
            
            <!-- Action 6: More (Dropdown) -->
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
                
                <!-- Dropdown Menu - Adjusted for right alignment -->
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
        </div>
    </div>
</div>     
       
 <!-- bypass datamask for non data masking -->
<p class="kpi-number text-xl font-black text-slate-900 mt-1 leading-none"></p>
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
        const container = document.getElementById('toast-container');
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

    // ===== MODAL HANDLER =====
    function openModal(modalId) {
        const modalNames = {
            'new-patient': 'Register New Patient',
            'new-permit': 'Issue New Sanitation Permit',
            'vaccinate': 'Record Vaccination',
            'report-case': 'Report New Health Case',
            'schedule': 'Schedule Inspection',
            'report': 'Generate Report',
            'export-data': 'Export Data',
            'bulk-action': 'Bulk Actions',
            'settings': 'Settings'
        };
        showToast(`Opening: ${modalNames[modalId] || modalId}`, 'info');
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

<?php include '../includes/footer.php'; ?>