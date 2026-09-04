<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- ADD FONT AWESOME CDN (If not already in header.php) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<style>
    /* ===== CSS VARIABLES (From System Overview) ===== */
    :root {
        --color-primary: #176B87;
        --color-primary-dark: #0F4A5E;
        --color-secondary: #86B6F6;
        --color-success: #10B981;
        --color-warning: #F59E0B;
        --color-danger: #EF4444;
        --color-info: #3B82F6;
        
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

    /* ===== BASE CARD STYLES ===== */
    .report-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(180, 212, 255, 0.3);
        box-shadow: 0 10px 40px -10px rgba(23, 107, 135, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }

    /* Tab Animations */
    .tab-content {
        animation: fadeInSlide 0.5s ease-in-out;
    }
    @keyframes fadeInSlide {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Button Animations */
    .btn-primary {
        background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(23, 107, 135, 0.3);
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(23, 107, 135, 0.4);
    }
    .btn-primary:active { transform: translateY(0); }

    .btn-outline-primary {
        border: 1px solid rgba(180, 212, 255, 0.5);
        color: var(--color-primary);
        transition: all 0.2s ease;
    }
    .btn-outline-primary:hover {
        background: rgba(180, 212, 255, 0.2);
        border-color: var(--color-primary);
    }

    /* Filter Chips */
    .filter-chip {
        border: 1px solid rgba(180, 212, 255, 0.4);
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .filter-chip:hover { background: rgba(180, 212, 255, 0.2); }
    .filter-chip.active {
        background: var(--color-primary);
        color: white;
        border-color: var(--color-primary);
    }

    /* Tab Buttons */
    .tab-btn {
        color: #64748b;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
        white-space: nowrap;
    }
    .tab-btn:hover { color: var(--color-primary); }
    .tab-btn.active {
        color: var(--color-primary);
        border-color: var(--color-primary);
        font-weight: 600;
    }

    /* Table Hover */
    .table-row-hover { transition: background-color 0.2s ease; }
    .table-row-hover:hover { background-color: rgba(180, 212, 255, 0.1); }

    /* Modal Animations */
    .modal-overlay {
        background: rgba(23, 107, 135, 0.4);
        backdrop-filter: blur(4px);
        transition: opacity 0.3s ease;
    }
    .modal-content {
        background: rgba(255, 255, 255, 0.95);
        animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes scaleIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Spinner */
    .spinner {
        width: 16px; height: 16px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .toast-show {
        transform: translateY(0) !important;
        opacity: 1 !important;
    }

    /* Decorative Shapes */
    .card-shape {
        position: absolute;
        border-radius: 50%;
        filter: blur(40px);
        z-index: 0;
    }
    .card-shape-1 { background: #B4D4FF; width: 200px; height: 200px; top: -50px; right: -50px; opacity: 0.15; }
    .card-shape-2 { background: #86B6F6; width: 150px; height: 150px; bottom: -30px; left: -30px; opacity: 0.15; }
    .card-shape-3 { background: #176B87; width: 100px; height: 100px; top: 20%; right: 10%; opacity: 0.08; }
    .card-shape-4 { background: #86B6F6; width: 180px; height: 180px; top: 0; right: 0; opacity: 0.1; }
    .card-shape-sm { width: 80px; height: 80px; background: #B4D4FF; opacity: 0.2; top: -10px; right: -10px; }

    .dot-pattern {
        background-image: radial-gradient(#176B8710 1px, transparent 1px);
        background-size: 20px 20px;
        z-index: 0;
    }

    /* Form Inputs */
    select, input[type="date"], input[type="time"], input[type="text"] {
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(180, 212, 255, 0.5);
        transition: all 0.2s ease;
    }
    select:focus, input:focus {
        outline: none;
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(23, 107, 135, 0.1);
        background: #fff;
    }

    /* ===== KPI CARDS ===== */
    .kpi-card {
        position: relative;
        overflow: hidden;
        background: white;
        border: 1px solid slate-100;
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
        top: 0; left: 0;
        width: 40%; height: 100%;
        background: linear-gradient(120deg, transparent, rgba(255,255,255,0.55), transparent);
        opacity: 0;
        pointer-events: none;
    }
    .kpi-card:hover .kpi-shine {
        opacity: 1;
        animation: shine 0.85s ease forwards;
    }
    @keyframes shine {
        0% { transform: translateX(-120%) skewX(-20deg); }
        100% { transform: translateX(220%) skewX(-20deg); }
    }
    
    .kpi-value {
        transition: transform 0.22s ease;
        display: inline-block;
    }
    .kpi-card:hover .kpi-value {
        transform: scale(1.06);
    }
    
    .kpi-watermark {
        transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
    }
    .kpi-card:hover .kpi-watermark {
        transform: scale(1.12) rotate(-3deg);
    }
    
    .kpi-ring-progress {
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        animation: ringFill 1s cubic-bezier(0.65,0,0.35,1) forwards;
    }
    @keyframes ringFill {
        to { stroke-dashoffset: var(--offset, 0); }
    }
    .kpi-ring {
        transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1);
    }
    .kpi-card:hover .kpi-ring {
        transform: scale(1.08);
    }

    /* Staggered entrance */
    .kpi-grid > a {
        opacity: 0;
        animation: slideUp 0.45s cubic-bezier(0.34,1.56,0.64,1) forwards;
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(36px) scale(0.95); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .kpi-grid > a:nth-child(1) { animation-delay: 0.05s; }
    .kpi-grid > a:nth-child(1) .kpi-ring-progress { animation-delay: 0.35s; }
    .kpi-grid > a:nth-child(2) { animation-delay: 0.12s; }
    .kpi-grid > a:nth-child(2) .kpi-ring-progress { animation-delay: 0.42s; }
    .kpi-grid > a:nth-child(3) { animation-delay: 0.19s; }
    .kpi-grid > a:nth-child(3) .kpi-ring-progress { animation-delay: 0.49s; }
    .kpi-grid > a:nth-child(4) { animation-delay: 0.26s; }
    .kpi-grid > a:nth-child(4) .kpi-ring-progress { animation-delay: 0.56s; }

    /* Glow effects */
    .glow-blue { border-color: rgba(59, 130, 246, 0.1); }
    .glow-blue:hover { border-color: rgba(59, 130, 246, 0.4); box-shadow: 0 15px 40px -10px rgba(59, 130, 246, 0.15); }
    .glow-emerald { border-color: rgba(16, 185, 129, 0.1); }
    .glow-emerald:hover { border-color: rgba(16, 185, 129, 0.4); box-shadow: 0 15px 40px -10px rgba(16, 185, 129, 0.15); }
    .glow-red { border-color: rgba(239, 68, 68, 0.1); }
    .glow-red:hover { border-color: rgba(239, 68, 68, 0.4); box-shadow: 0 15px 40px -10px rgba(239, 68, 68, 0.15); }
    .glow-purple { border-color: rgba(139, 92, 246, 0.1); }
    .glow-purple:hover { border-color: rgba(139, 92, 246, 0.4); box-shadow: 0 15px 40px -10px rgba(139, 92, 246, 0.15); }

    /* Template list items */
    .template-item {
        transition: all 0.2s ease;
        cursor: pointer;
    }
    .template-item:hover {
        background: rgba(180, 212, 255, 0.15);
        transform: translateX(4px);
    }
    .template-item .delete-btn {
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    .template-item:hover .delete-btn {
        opacity: 1;
    }

    /* Action Buttons */
    .action-btn {
        transition: all 0.2s ease;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .action-btn:hover {
        transform: scale(1.1);
    }
    .action-btn-view {
        color: #176B87;
    }
    .action-btn-view:hover {
        color: #0F4A5E;
    }
    .action-btn-download {
        color: #64748b;
    }
    .action-btn-download:hover {
        color: #176B87;
    }

    /* Prevent export helpers from covering the report controls. */
    .html2pdf__overlay {
        display: none !important;
    }

    /* ===== FORMAL REPORT PRINT LAYOUT ===== */
    #printReportHeader {
        display: none;
    }

    @page {
        margin: 0.75in;
    }

    @media print {
        header:not(#printReportHeader),
        aside,
        .sidebar,
        #sidebar,
        footer,
        .footer,
        #footer,
        #reportConfiguration,
        #recentReports,
        #reportFooter,
        #reportTabsBar,
        #reportPreview .action-btn,
        #tablePagination,
        #tabTable th:last-child,
        #tabTable td:last-child,
        .modal-overlay {
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

        #reportPreview {
            display: block !important;
            width: 100% !important;
            margin: 0 !important;
            overflow: visible !important;
            box-shadow: none !important;
            border: 0 !important;
            background: #ffffff !important;
        }

        #printReportHeader {
            display: block !important;
            text-align: center;
            font-family: "Times New Roman", Times, serif;
            margin: 0 0 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }

        #printReportHeader img {
            width: 120px;
            height: auto;
            display: block;
            margin: 0 auto 10px;
        }

        #printReportHeader h1 {
            margin: 0;
            color: #000;
            font-size: 20pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        #printReportHeader h2 {
            margin: 5px 0 0;
            color: #000;
            font-size: 14pt;
            font-weight: normal;
        }

        #tabChart,
        #tabTable,
        #tabSummary {
            display: block !important;
            height: auto !important;
            min-height: 0 !important;
            opacity: 1 !important;
            overflow: visible !important;
            transform: none !important;
            animation: none !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        #reportPreview .overflow-x-auto,
        #reportPreview .overflow-hidden {
            overflow: visible !important;
        }

        #reportPreview canvas {
            max-width: 100% !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<main class="flex-1 bg-dash-bg h-screen m-5 rounded-2xl font-sans overflow-y-auto scrollbar-track-transparent">

    <!-- ─── CONFIGURATION CARD ─── -->
    <div id="reportConfiguration" class="report-card rounded-3xl p-5 sm:p-7 mb-8">
        <div class="card-shape card-shape-1"></div>
        <div class="card-shape card-shape-2"></div>
        <div class="dot-pattern absolute inset-0"></div>

        <div class="relative z-10">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-5">
                <div>
                    <h3 class="text-base font-semibold text-[#176B87] flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-[#86B6F6] text-sm"></i>
                        Report Configuration
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Customize your report parameters below</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="btn-outline-primary px-4 py-1.5 rounded-xl text-xs font-medium flex items-center gap-2" onclick="openSaveTemplateModal()">
                        <i class="fa-regular fa-floppy-disk"></i> Save Template
                    </button>
                    <button class="btn-outline-primary px-4 py-1.5 rounded-xl text-xs font-medium flex items-center gap-2" onclick="openTemplateModal()">
                        <i class="fa-regular fa-folder-open"></i> Load Template
                    </button>
                </div>
            </div>

            <!-- filters grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">
                        <i class="fa-regular fa-file-lines mr-1"></i> Report Type
                    </label>
                    <select id="reportType" class="w-full rounded-xl px-4 py-2.5 text-sm" onchange="refreshUI()">
                        <option value="health_center">Health Center Services &amp; Consultations</option>
                        <option value="sanitation">Sanitation Inspections &amp; Permits</option>
                        <option value="immunization">Immunization &amp; Nutrition</option>
                        <option value="wastewater">Wastewater &amp; Water Quality Analysis</option>
                        <option value="surveillance">Disease Surveillance &amp; Outbreak Reports</option>
                        <option value="compliance">Overall Compliance Summary</option>
                        <option value="custom">Custom Report</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">
                        <i class="fa-regular fa-calendar mr-1"></i> Date Range
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-90 days')) ?>" class="w-full rounded-xl px-4 py-2.5 text-sm" onchange="refreshUI()" />
                        <span class="text-slate-400 text-xs">to</span>
                        <input type="date" id="endDate" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="w-full rounded-xl px-4 py-2.5 text-sm" onchange="refreshUI()" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">
                        <i class="fa-regular fa-building mr-1"></i> Department / Facility
                    </label>
                    <select id="facility" class="w-full rounded-xl px-4 py-2.5 text-sm" onchange="refreshUI()">
                        <option value="all">All Departments &amp; Facilities</option>
                        <optgroup label="Core Health Departments">
                            <option value="Health Center Services">Health Center Services</option>
                            <option value="Sanitation Permits">Sanitation Permits</option>
                            <option value="Immunization & Nutrition">Immunization &amp; Nutrition</option>
                            <option value="Wastewater Services">Wastewater Services</option>
                            <option value="Health Surveillance">Health Surveillance</option>
                        </optgroup>
                        <optgroup label="Facilities &amp; Clinics">
                            <option value="Central Health Center">Central Health Center</option>
                            <option value="Eastside Clinic">Eastside Clinic</option>
                            <option value="West District Hospital">West District Hospital</option>
                            <option value="North Community Hub">North Community Hub</option>
                            <option value="South Sanitation Depot">South Sanitation Depot</option>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">
                        <i class="fa-regular fa-user mr-1"></i> Inspector / Officer
                    </label>
                    <select id="inspector" class="w-full rounded-xl px-4 py-2.5 text-sm" onchange="refreshUI()">
                        <option value="all">Loading Staff &amp; Inspectors...</option>
                    </select>
                </div>
            </div>

            <!-- second row -->
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-[#B4D4FF]/30">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-xs font-medium text-slate-500">Status:</span>
                    <div class="flex flex-wrap gap-1.5" id="statusChips">
                        <span class="filter-chip active px-3 py-1 rounded-full text-xs font-medium" data-status="all">All</span>
                        <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium" data-status="Compliant">Compliant</span>
                        <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium" data-status="Non-Compliant">Non-Compliant</span>
                        <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium" data-status="Pending">Pending</span>
                        <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium" data-status="Urgent">Urgent</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button id="generateBtn" onclick="generateReport()" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center gap-2">
                        <i class="fa-solid fa-play"></i> Generate Report
                    </button>
                    <button id="resetBtn" onclick="resetFilters()" class="px-4 py-2.5 rounded-xl text-sm font-medium border border-[#B4D4FF]/40 bg-white/50 text-slate-600 hover:bg-[#B4D4FF]/20 transition flex items-center gap-2">
                        <i class="fa-regular fa-circle-xmark"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PRINT HEADER (Hidden on screen, shown before Quick Stats when printing) -->
    <div id="printReportHeader">
        <img src="../assets/images/logo.png" alt="Logo">
        <h1>Health Sanitation Management Caloocan</h1>
        <h2>Custom Compliance Report</h2>
    </div>

    <!-- ─── QUICK STATS ─── -->
    <div id="quickStats" class="kpi-grid grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <!-- KPI 1: Total Inspections -->
        <a href="#" class="kpi-card glow-blue relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-clipboard kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-blue-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-blue-400 to-blue-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-blue-600">Total Inspections</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiTotal">0</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">Conducted</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:15" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#3b82f6" id="kpiTotalPercent">85%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-arrow-up text-[5px] mr-0.5"></i> <span id="kpiTotalTrend">12.5%</span>
                    </span>
                    <span class="text-[7px] text-slate-400">vs last month</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,16 10,14 20,15 30,10 40,11 50,4 60,3" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- KPI 2: Compliance Rate -->
        <a href="#" class="kpi-card glow-emerald relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-circle-check kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-emerald-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-emerald-400 to-emerald-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-emerald-600">Compliance Rate</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiCompliance">0%</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">Overall</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:5" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#10b981" id="kpiCompliancePercent">95%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-arrow-up text-[5px] mr-0.5"></i> <span id="kpiComplianceTrend">2.3%</span>
                    </span>
                    <span class="text-[7px] text-slate-400">vs last month</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,12 10,11 20,9 30,8 40,5 50,4 60,2" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- KPI 3: Urgent Issues -->
        <a href="#" class="kpi-card glow-red relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-rose-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-circle-exclamation kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-rose-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-rose-400 to-rose-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-rose-600">Urgent Issues</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiUrgent">0</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">Need Attention</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:80" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#ef4444" id="kpiUrgentPercent">20%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-arrow-up text-[5px] mr-0.5"></i> <span id="kpiUrgentTrend">+4</span>
                    </span>
                    <span class="text-[7px] text-slate-400">vs last month</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,16 10,14 20,15 30,10 40,11 50,4 60,3" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- KPI 4: Facilities Covered -->
        <a href="#" class="kpi-card glow-purple relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-purple-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-hospital kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-purple-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-purple-400 to-purple-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-purple-600">Facilities Covered</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiFacilities">0</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">of 52 total</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#9333ea" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:10" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#9333ea" id="kpiFacilitiesPercent">90%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-check-circle text-[5px] mr-0.5"></i> Active
                    </span>
                    <span class="text-[7px] text-slate-400" id="kpiFacilitiesCoverage">90.4% coverage</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,14 10,13 20,15 30,10 40,12 50,8 60,9" fill="none" stroke="#9333ea" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>
    </div>

    <!-- ─── REPORT PREVIEW CARD ─── -->
    <div id="reportPreview" class="report-card rounded-3xl overflow-hidden mb-8">
        <div class="card-shape card-shape-4"></div>
        <div class="dot-pattern absolute inset-0"></div>

        <div class="relative z-10">
            <!-- tabs -->
            <div id="reportTabsBar" class="flex items-center justify-between px-5 sm:px-7 pt-4 pb-0 border-b border-[#B4D4FF]/30 flex-wrap gap-2">
                <div class="flex gap-5 text-sm overflow-x-auto">
                    <button class="tab-btn active pb-3" data-tab="chart" onclick="switchTab('chart')">
                        <i class="fa-regular fa-chart-bar"></i> Chart View
                    </button>
                    <button class="tab-btn pb-3" data-tab="table" onclick="switchTab('table')">
                        <i class="fa-solid fa-table"></i> Table View
                    </button>
                    <button class="tab-btn pb-3" data-tab="summary" onclick="switchTab('summary')">
                        <i class="fa-regular fa-file-lines"></i> Summary
                    </button>
                </div>
                <div id="reportExportActions" class="flex items-center gap-2 pb-2">
                    <button onclick="exportPDF()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export PDF"><i class="fa-solid fa-file-pdf"></i></button>
                    <button onclick="exportExcel()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export Excel"><i class="fa-solid fa-file-excel"></i></button>
                    <button onclick="exportWord()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export Word"><i class="fa-solid fa-file-word"></i></button>
                    <button onclick="window.print()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Print"><i class="fa-solid fa-print"></i></button>
                    <button onclick="openScheduleModal()" class="ml-1 h-8 px-3 rounded-lg bg-[#B4D4FF]/30 text-[#176B87] text-xs font-medium hover:bg-[#86B6F6]/40 transition inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-clock"></i> Schedule
                    </button>
                </div>
            </div>

            <!-- tab content -->
            <div class="p-5 sm:p-7">
                <!-- Chart View -->
                <div id="tabChart" class="tab-content">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 bg-white/40 backdrop-blur-sm rounded-xl p-4 border border-[#B4D4FF]/20 relative overflow-hidden">
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-semibold text-[#176B87]">Sanitation Compliance by Facility</h4>
                                    <span class="text-[10px] text-slate-400 bg-white/50 px-2 py-0.5 rounded-full border border-[#B4D4FF]/20">Jul 2026</span>
                                </div>
                                <div class="chart-container h-64">
                                    <canvas id="barChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-5">
                            <div class="bg-white/40 backdrop-blur-sm rounded-xl p-4 border border-[#B4D4FF]/20 relative overflow-hidden">
                                <div class="relative z-10">
                                    <h4 class="text-sm font-semibold text-[#176B87] mb-2">Overall Status</h4>
                                    <div class="chart-container h-36">
                                        <canvas id="doughnutChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/40 backdrop-blur-sm rounded-xl p-4 border border-[#B4D4FF]/20 relative overflow-hidden">
                                <div class="relative z-10">
                                    <h4 class="text-sm font-semibold text-[#176B87] mb-1">Trend (last 6 mo)</h4>
                                    <div class="chart-container h-20">
                                        <canvas id="lineChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div id="tabTable" class="tab-content hidden">
                    <div class="table-wrap overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-[#176B87] uppercase tracking-wider border-b border-[#B4D4FF]/30">
                                    <th class="pb-3 pr-4">Facility</th>
                                    <th class="pb-3 pr-4">Inspector</th>
                                    <th class="pb-3 pr-4">Date</th>
                                    <th class="pb-3 pr-4">Score</th>
                                    <th class="pb-3 pr-4">Status</th>
                                    <th class="pb-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#B4D4FF]/20" id="tableViewBody"></tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-slate-400 border-t border-[#B4D4FF]/30 pt-3 gap-2">
                        <span id="tableViewSummary">Showing 0 of 0 entries</span>
                        <div class="flex gap-2" id="tablePagination">
                            <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" onclick="goToPage(currentPage - 1)">Prev</button>
                            <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" data-page="1" onclick="goToPage(1)">1</button>
                            <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" data-page="2" onclick="goToPage(2)">2</button>
                            <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" data-page="3" onclick="goToPage(3)">3</button>
                            <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" onclick="goToPage(currentPage + 1)">Next</button>
                        </div>
                    </div>
                </div>

                <!-- Summary View -->
                <div id="tabSummary" class="tab-content hidden">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/80">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold flex items-center gap-1.5 border border-indigo-100">
                                <i class="fas fa-brain text-indigo-600"></i> AI Department Intelligence
                            </span>
                            <span id="aiRiskBadge" class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">Optimal</span>
                        </div>
                        <button type="button" onclick="fetchAiReportSummary(true)" id="btnGenerateAiSummary" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <i class="fas fa-wand-magic-sparkles text-xs"></i> Generate AI Summary
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                <i class="fas fa-file-contract text-indigo-600"></i> Executive Summary Narrative
                            </h4>
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 text-xs text-slate-700 leading-relaxed shadow-xs" id="summaryText">
                                <p>Loading dynamic report executive summary...</p>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2" id="summaryTags"></div>

                            <div class="mt-4 pt-3 border-t border-slate-200/80">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                    <i class="fas fa-lightbulb text-amber-500"></i> Actionable Recommendations
                                </h4>
                                <ul class="space-y-1.5 text-xs text-slate-600" id="aiRecommendationsList">
                                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-500 text-xs"></i> Reallocate response staff to high-density zones.</li>
                                    <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-500 text-xs"></i> Conduct weekly supervisory audit reviews.</li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                <i class="fas fa-chart-line text-[#176B87]"></i> Department Performance Metrics
                            </h4>
                            <div class="space-y-3 p-4 bg-slate-50 rounded-2xl border border-slate-200/80 mb-4" id="summaryMetrics">
                                <div>
                                    <div class="flex justify-between text-xs font-bold text-slate-700"><span>Compliance Rate</span><span class="text-[#176B87]" id="metricCompliance">0%</span></div>
                                    <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-[#176B87] rounded-full transition-all duration-500" style="width:0%" id="metricComplianceBar"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs font-bold text-slate-700"><span>Inspection / Encounter Coverage</span><span class="text-[#176B87]" id="metricCoverage">0%</span></div>
                                    <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-sky-500 rounded-full transition-all duration-500" style="width:0%" id="metricCoverageBar"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs font-bold text-slate-700"><span>Issue Resolution Rate</span><span class="text-[#176B87]" id="metricResolution">0%</span></div>
                                    <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-indigo-500 rounded-full transition-all duration-500" style="width:0%" id="metricResolutionBar"></div></div>
                                </div>
                                <div>
                                    <div class="flex justify-between text-xs font-bold text-slate-700"><span>Department Operational Index</span><span class="text-[#176B87]" id="metricParticipation">0%</span></div>
                                    <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-emerald-500 rounded-full transition-all duration-500" style="width:0%" id="metricParticipationBar"></div></div>
                                </div>
                            </div>

                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                <i class="fas fa-magnifying-glass-chart text-indigo-600"></i> Key AI Findings
                            </h4>
                            <div class="space-y-2 text-xs" id="aiKeyFindings">
                                <div class="p-2.5 bg-indigo-50/60 rounded-xl border border-indigo-100 text-indigo-900 font-semibold">
                                    Department compliance efficiency evaluated across all active records.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── RECENT REPORTS CARD ─── -->
    <div id="recentReports" class="report-card rounded-3xl p-5 sm:p-7 mb-8">
        <div class="card-shape card-shape-1"></div>
        <div class="dot-pattern absolute inset-0"></div>

        <div class="relative z-10">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-[#176B87] flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-[#86B6F6] text-sm"></i>
                        Recent Report Logs
                    </h3>
                    <p class="text-xs text-slate-400">Audit trail of users who generated and exported reports</p>
                </div>
                <button id="viewAllReportsBtn" onclick="toggleViewAllReports()" class="text-sm font-medium text-[#176B87] hover:underline">View All →</button>
            </div>
            <div class="table-wrap overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs font-semibold text-[#176B87] uppercase tracking-wider border-b border-[#B4D4FF]/30">
                        <tr>
                            <th class="pb-2 pr-4">Report Name</th>
                            <th class="pb-2 pr-4">Format / Type</th>
                            <th class="pb-2 pr-4">Generated By</th>
                            <th class="pb-2 pr-4">Date & Time</th>
                            <th class="pb-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#B4D4FF]/20" id="recentReportsBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- footer note -->
    <div id="reportFooter" class="mt-8 text-center text-xs text-slate-400/70 border-t border-[#B4D4FF]/20 pt-6">
        Health Sanitation Management System · Report Generator v2.0
    </div>

    <!-- ─── VIEW DETAIL MODAL ─── -->
    <div id="viewDetailModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeViewDetailModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Report Details</h3>
                        <p class="text-xs text-slate-400">Full inspection record</p>
                    </div>
                </div>
                <button onclick="closeViewDetailModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5" id="viewDetailContent">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Facility</span><span class="font-medium text-[#176B87]" id="detailFacility">—</span></div>
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Inspector</span><span class="font-medium text-[#176B87]" id="detailInspector">—</span></div>
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Date</span><span class="font-medium text-[#176B87]" id="detailDate">—</span></div>
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Score</span><span class="font-medium text-[#176B87]" id="detailScore">—</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-medium" id="detailStatus">—</span></div>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end">
                <button onclick="closeViewDetailModal()" class="text-sm text-slate-400 hover:text-slate-600 transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ─── REPORT RESULT MODAL (PDF / EXCEL / WORD) ─── -->
    <div id="reportResultModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeResultModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class="fa-regular fa-circle-check text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Report Ready!</h3>
                        <p class="text-xs text-slate-400">Choose format to download</p>
                    </div>
                </div>
                <button onclick="closeResultModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-6 space-y-3">
                <p class="text-sm text-slate-500 mb-2">Your report has been generated. Select a format to save it locally:</p>
                <button onclick="downloadPDF()" class="w-full flex items-center gap-4 px-4 py-3 rounded-xl border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/10 transition group">
                    <span class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition"><i class="fa-solid fa-file-pdf text-lg"></i></span>
                    <div class="flex-1 text-left">
                        <span class="font-medium text-slate-700">PDF Document</span>
                        <p class="text-xs text-slate-400">Portable Document Format</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:translate-x-1 transition"></i>
                </button>
                <button onclick="downloadExcel()" class="w-full flex items-center gap-4 px-4 py-3 rounded-xl border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/10 transition group">
                    <span class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition"><i class="fa-solid fa-file-excel text-lg"></i></span>
                    <div class="flex-1 text-left">
                        <span class="font-medium text-slate-700">Excel Spreadsheet</span>
                        <p class="text-xs text-slate-400">.xls format – compatible with Excel</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:translate-x-1 transition"></i>
                </button>
                <button onclick="downloadWord()" class="w-full flex items-center gap-4 px-4 py-3 rounded-xl border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/10 transition group">
                    <span class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition"><i class="fa-solid fa-file-word text-lg"></i></span>
                    <div class="flex-1 text-left">
                        <span class="font-medium text-slate-700">Word Document</span>
                        <p class="text-xs text-slate-400">.doc format – compatible with Word</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:translate-x-1 transition"></i>
                </button>
            </div>
            <div class="px-6 py-3 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end">
                <button onclick="closeResultModal()" class="text-sm text-slate-400 hover:text-slate-600 transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ─── SAVE TEMPLATE MODAL ─── -->
    <div id="saveTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeSaveTemplateModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-floppy-disk"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Save Template</h3>
                        <p class="text-xs text-slate-400">Enter a name for your template</p>
                    </div>
                </div>
                <button onclick="closeSaveTemplateModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5">
                <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Template Name</label>
                <input type="text" id="templateNameInput" placeholder="e.g. Weekly Compliance Report" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 focus:border-[#176B87] focus:ring-2 focus:ring-[#176B87]/20 outline-none transition" />
                <p class="text-xs text-slate-400 mt-1.5">This will save all current filter settings.</p>
            </div>
            <div class="px-6 py-4 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end gap-3">
                <button onclick="closeSaveTemplateModal()" class="px-5 py-2 rounded-xl text-sm font-medium border border-[#B4D4FF]/40 bg-white/50 text-slate-600 hover:bg-[#B4D4FF]/20 transition">Cancel</button>
                <button onclick="saveTemplate()" class="btn-primary px-6 py-2 rounded-xl text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk"></i> Save
                </button>
            </div>
        </div>
    </div>

    <!-- ─── LOAD TEMPLATE MODAL ─── -->
    <div id="templateModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeTemplateModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Load Template</h3>
                        <p class="text-xs text-slate-400">Select a saved template to apply</p>
                    </div>
                </div>
                <button onclick="closeTemplateModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5">
                <div id="templateList" class="space-y-2 max-h-72 overflow-y-auto">
                    <!-- Templates will be rendered here -->
                    <p class="text-sm text-slate-400 text-center py-8">No saved templates found.</p>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end">
                <button onclick="closeTemplateModal()" class="text-sm text-slate-400 hover:text-slate-600 transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ─── SCHEDULE MODAL ─── -->
    <div id="scheduleModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeScheduleModal()">
        <div class="modal-content rounded-3xl max-w-lg w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Schedule Report</h3>
                        <p class="text-xs text-slate-400">Automate report generation &amp; delivery</p>
                    </div>
                </div>
                <button onclick="closeScheduleModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Frequency</label>
                    <select class="w-full rounded-xl px-4 py-2.5 text-sm">
                        <option>Daily</option>
                        <option selected>Weekly</option>
                        <option>Monthly</option>
                        <option>Quarterly</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Start Date</label>
                        <input type="date" value="2026-07-20" class="w-full rounded-xl px-4 py-2.5 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Time</label>
                        <input type="time" value="08:00" class="w-full rounded-xl px-4 py-2.5 text-sm" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Recipients (email)</label>
                    <input type="text" placeholder="admin@hsms.gov, team@hsms.gov" class="w-full rounded-xl px-4 py-2.5 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Format</label>
                    <div class="flex gap-4 text-sm">
                        <label class="flex items-center gap-2 text-slate-600"><input type="radio" name="format" checked class="accent-[#176B87]" /> PDF</label>
                        <label class="flex items-center gap-2 text-slate-600"><input type="radio" name="format" class="accent-[#176B87]" /> Excel</label>
                        <label class="flex items-center gap-2 text-slate-600"><input type="radio" name="format" class="accent-[#176B87]" /> Word</label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end gap-3">
                <button onclick="closeScheduleModal()" class="px-5 py-2 rounded-xl text-sm font-medium border border-[#B4D4FF]/40 bg-white/50 text-slate-600 hover:bg-[#B4D4FF]/20 transition">Cancel</button>
                <button onclick="scheduleReport()" class="btn-primary px-6 py-2 rounded-xl text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk"></i> Schedule
                </button>
            </div>
        </div>
    </div>

    <!-- ─── TOAST ─── -->
    <div id="toast" class="fixed bottom-6 right-6 z-[60] text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 translate-y-20 opacity-0 transition-all duration-500 pointer-events-none" style="background: #176B87;">
        <i id="toastIcon" class="fa-regular fa-circle-check text-[#B4D4FF] text-lg"></i>
        <span class="text-sm font-medium" id="toastMessage">Report generated successfully!</span>
        <button onclick="hideToast()" class="ml-2 text-white/60 hover:text-white transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
   <!-- bypass datamask for non data masking -->
<p class="kpi-number text-xl font-black text-slate-900 mt-1 leading-none"></p>
</main>

<?php include '../includes/footer.php'; ?>

<!-- ─── CHART.JS ─── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- ─── jsPDF (real client-side PDF export) ─── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
// ================================================================
//  CODE ARCHITECTURE – MODULAR FUNCTIONS
// ================================================================

// ─── DYNAMIC DATA STORE (FETCHED FROM SUPABASE) ───────────────────
let allReportRows = [];
let baseRecentReports = [];
let extraRecentReports = [];
let activeEmployeesList = [];

// ─── STATE ──────────────────────────────────────────────────────
let currentPage = 1;
const PAGE_SIZE = 5;
let currentStatusFilter = 'all';

// Chart instances
let barChart, doughnutChart, lineChart;

// ─── FILTERING ENGINE ──────────────────────────────────────────
function getFilteredData() {
    const facility = document.getElementById('facility').value;
    const inspector = document.getElementById('inspector').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    return allReportRows.filter(row => {
        if (currentStatusFilter !== 'all' && row.status !== currentStatusFilter) return false;
        if (facility !== 'all') {
            const target = facility.toLowerCase().replace(' services', '');
            const rowFacility = row.facility.toLowerCase();
            if (rowFacility !== facility.toLowerCase() && !rowFacility.includes(target) && !target.includes(rowFacility)) {
                return false;
            }
        }
        if (inspector !== 'all' && row.inspector !== inspector) return false;
        if (startDate && row.date < startDate) return false;
        if (endDate && row.date > endDate) return false;
        return true;
    });
}

// ─── GET CURRENT CONFIG (for templates) ──────────────────────
function getCurrentConfig() {
    return {
        reportType: document.getElementById('reportType').value,
        startDate: document.getElementById('startDate').value,
        endDate: document.getElementById('endDate').value,
        facility: document.getElementById('facility').value,
        inspector: document.getElementById('inspector').value,
        status: currentStatusFilter
    };
}

function applyConfig(config) {
    if (config.reportType) document.getElementById('reportType').value = config.reportType;
    if (config.startDate) document.getElementById('startDate').value = config.startDate;
    if (config.endDate) document.getElementById('endDate').value = config.endDate;
    if (config.facility) document.getElementById('facility').value = config.facility;
    if (config.inspector) document.getElementById('inspector').value = config.inspector;

    if (config.status) {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        const chip = document.querySelector(`.filter-chip[data-status="${config.status}"]`);
        if (chip) chip.classList.add('active');
        currentStatusFilter = config.status;
        currentPage = 1;
    }
    refreshUI();
}

// ─── UI REFRESH ─────────────────────────────────────────────────
function refreshUI() {
    const facilityVal = document.getElementById('facility') ? document.getElementById('facility').value : 'all';
    populateInspectorDropdown(activeEmployeesList, facilityVal);

    const data = getFilteredData();
    const total = data.length;
    const compliant = data.filter(r => r.status === 'Compliant').length;
    const urgent = data.filter(r => r.status === 'Urgent').length;
    const nonCompliant = data.filter(r => r.status === 'Non-Compliant').length;
    const pending = data.filter(r => r.status === 'Pending').length;
    const facilities = new Set(data.map(r => r.facility)).size;

    document.getElementById('kpiTotal').textContent = total;
    document.getElementById('kpiUrgent').textContent = urgent;
    document.getElementById('kpiFacilities').textContent = facilities;

    const complianceRate = total > 0 ? ((compliant / total) * 100).toFixed(1) : 0;
    document.getElementById('kpiCompliance').textContent = complianceRate + '%';
    document.getElementById('kpiTotalPercent').textContent = total > 0 ? Math.round((total / allReportRows.length) * 100) + '%' : '0%';
    document.getElementById('kpiCompliancePercent').textContent = complianceRate + '%';
    document.getElementById('kpiUrgentPercent').textContent = total > 0 ? Math.round((urgent / total) * 100) + '%' : '0%';
    document.getElementById('kpiFacilitiesPercent').textContent = total > 0 ? Math.round((facilities / 52) * 100) + '%' : '0%';
    document.getElementById('kpiFacilitiesCoverage').textContent = total > 0 ? Math.round((facilities / 52) * 100) + '% coverage' : '0% coverage';

    document.getElementById('kpiTotalTrend').textContent = total > 0 ? (Math.random() * 10).toFixed(1) + '%' : '0%';
    document.getElementById('kpiComplianceTrend').textContent = complianceRate > 0 ? (Math.random() * 2 + 0.5).toFixed(1) + '%' : '0%';
    document.getElementById('kpiUrgentTrend').textContent = urgent > 0 ? '+' + Math.floor(Math.random() * 5) : '0';

    // Summary metrics & fallback UI
    document.getElementById('summaryText').innerHTML = `
        <p>This report covers <strong class="text-[#176B87]">${facilities} facilities</strong> across the region, with a total of <strong class="text-[#176B87]">${total} inspections/transactions</strong> conducted between the selected date range.</p>
        <p>The overall compliance rate stands at <strong class="text-emerald-600">${complianceRate}%</strong>.</p>
        <p>Key areas of concern: ${urgent} urgent issues, ${nonCompliant} non-compliant, ${pending} pending.</p>
    `;
    document.getElementById('summaryTags').innerHTML = `
        <span class="px-3 py-1 bg-[#B4D4FF]/30 text-[#176B87] rounded-full text-xs font-medium">🔹 Compliance ${complianceRate}%</span>
        <span class="px-3 py-1 bg-amber-100/60 text-amber-700 rounded-full text-xs font-medium">⚠️ Pending: ${pending}</span>
        <span class="px-3 py-1 bg-red-100/60 text-red-700 rounded-full text-xs font-medium">🚨 Urgent: ${urgent}</span>
    `;
    document.getElementById('metricCompliance').textContent = complianceRate + '%';
    document.getElementById('metricComplianceBar').style.width = complianceRate + '%';
    const coverage = total > 0 ? Math.round((facilities / 52) * 100) : 0;
    document.getElementById('metricCoverage').textContent = coverage + '%';
    document.getElementById('metricCoverageBar').style.width = coverage + '%';
    const resolution = total > 0 ? Math.round(((compliant) / total) * 100) : 0;
    document.getElementById('metricResolution').textContent = resolution + '%';
    document.getElementById('metricResolutionBar').style.width = resolution + '%';
    document.getElementById('metricParticipation').textContent = total > 0 ? '100%' : '0%';
    document.getElementById('metricParticipationBar').style.width = total > 0 ? '100%' : '0%';

    updateCharts(data);
    renderTableView(data);

    // Fetch AI Executive Summary per department
    fetchAiReportSummary(false);
}

// ─── AI REPORT SUMMARY GENERATION ENGINE ─────────────────────────
async function fetchAiReportSummary(isManual = false) {
    const btn = document.getElementById('btnGenerateAiSummary');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Analyzing...';
    }

    const deptSelect = document.getElementById('facility') || document.getElementById('filterFacility');
    const selectedDept = deptSelect ? deptSelect.value : 'all';
    
    const data = getFilteredData();
    const total = data.length;
    const compliant = data.filter(r => r.status === 'Compliant').length;
    const urgent = data.filter(r => r.status === 'Urgent').length;
    const pending = data.filter(r => r.status === 'Pending').length;

    try {
        const url = `<?= site_url('api/reports/ai-summary.php') ?>?department=${encodeURIComponent(selectedDept)}&total=${total}&compliant=${compliant}&urgent=${urgent}&pending=${pending}`;
        const resp = await fetch(url);
        const res = await resp.json();

        if (res && res.success) {
            // Update Summary Text
            document.getElementById('summaryText').innerHTML = `
                <p class="font-bold text-slate-800 mb-1.5 text-xs">${res.department} Executive Overview:</p>
                <p class="leading-relaxed text-xs text-slate-700">${res.summary}</p>
            `;

            // Update Risk Badge
            const riskBadge = document.getElementById('aiRiskBadge');
            if (riskBadge) {
                riskBadge.textContent = res.risk_level || 'Optimal';
                riskBadge.className = res.risk_level === 'High Risk' 
                    ? 'px-2.5 py-1 bg-rose-100 text-rose-700 rounded-lg text-xs font-bold'
                    : (res.risk_level === 'Moderate Risk' ? 'px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold' : 'px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold');
            }

            // Update Tags
            const complianceRate = res.metrics ? res.metrics.compliance_rate : 0;
            document.getElementById('summaryTags').innerHTML = `
                <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold border border-indigo-100">✨ ${res.ai_generated ? 'AI Model Summary' : 'Rule Engine Summary'}</span>
                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold border border-blue-100">🔹 Compliance ${complianceRate}%</span>
                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold border border-amber-100">⚠️ Pending: ${pending}</span>
                <span class="px-2.5 py-1 bg-rose-50 text-rose-700 rounded-full text-xs font-bold border border-rose-100">🚨 Urgent: ${urgent}</span>
            `;

            // Update Key Findings
            if (res.key_findings && res.key_findings.length > 0) {
                const findingsHtml = res.key_findings.map(f => `
                    <div class="p-2.5 bg-indigo-50/60 rounded-xl border border-indigo-100 text-indigo-950 font-semibold flex items-start gap-2 text-xs">
                        <i class="fas fa-circle-info text-indigo-600 mt-0.5 flex-shrink-0"></i>
                        <span>${f}</span>
                    </div>
                `).join('');
                document.getElementById('aiKeyFindings').innerHTML = findingsHtml;
            }

            // Update Actionable Recommendations
            if (res.recommendations && res.recommendations.length > 0) {
                const recsHtml = res.recommendations.map(r => `
                    <li class="flex items-start gap-2 font-medium text-slate-700 text-xs">
                        <i class="fas fa-check-circle text-emerald-500 text-xs mt-0.5 flex-shrink-0"></i>
                        <span>${r}</span>
                    </li>
                `).join('');
                document.getElementById('aiRecommendationsList').innerHTML = recsHtml;
            }

            if (isManual && typeof showToast === 'function') {
                showToast('AI Executive Summary generated successfully!', 'success');
            }
        }
    } catch (e) {
        console.error('Failed to fetch AI report summary:', e);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wand-magic-sparkles text-xs"></i> Generate AI Summary';
        }
    }
}

// ─── CHART UPDATES ─────────────────────────────────────────────
function updateCharts(data) {
    if (!data || data.length === 0) {
        if (barChart) {
            barChart.data.labels = ['No Data'];
            barChart.data.datasets[0].data = [0];
            barChart.data.datasets[0].backgroundColor = ['#cbd5e1'];
            barChart.update();
        }
        if (doughnutChart) {
            doughnutChart.data.labels = ['No Data'];
            doughnutChart.data.datasets[0].data = [1];
            doughnutChart.data.datasets[0].backgroundColor = ['#cbd5e1'];
            doughnutChart.update();
        }
        if (lineChart) {
            lineChart.data.labels = ['No Data'];
            lineChart.data.datasets[0].data = [0];
            lineChart.update();
        }
        return;
    }

    const selectedDept = document.getElementById('facility') ? document.getElementById('facility').value : 'all';
    
    const groupMap = {};
    data.forEach(r => {
        const key = selectedDept !== 'all' ? (r.inspector || r.facility) : r.facility;
        if (!groupMap[key]) groupMap[key] = { sum: 0, count: 0 };
        groupMap[key].sum += r.score;
        groupMap[key].count++;
    });

    const labels = Object.keys(groupMap);
    const scores = labels.map(k => Math.round(groupMap[k].sum / groupMap[k].count));
    const colors = scores.map(s => s >= 90 ? '#176B87' : s >= 75 ? '#3b82f6' : s >= 60 ? '#f59e0b' : '#ef4444');

    if (barChart) {
        barChart.data.labels = labels;
        barChart.data.datasets[0].data = scores;
        barChart.data.datasets[0].backgroundColor = colors;
        barChart.update();
    }

    const statusCounts = { Compliant: 0, Pending: 0, Urgent: 0, 'Non-Compliant': 0 };
    data.forEach(r => { if (statusCounts.hasOwnProperty(r.status)) statusCounts[r.status]++; });
    
    const statusLabels = [];
    const statusValues = [];
    const statusColorMap = { Compliant: '#10b981', Pending: '#f59e0b', Urgent: '#ef4444', 'Non-Compliant': '#f43f5e' };
    const doughnutColors = [];

    Object.keys(statusCounts).forEach(st => {
        if (statusCounts[st] > 0) {
            statusLabels.push(st);
            statusValues.push(statusCounts[st]);
            doughnutColors.push(statusColorMap[st] || '#176B87');
        }
    });

    if (doughnutChart) {
        doughnutChart.data.labels = statusLabels.length ? statusLabels : ['All Clear'];
        doughnutChart.data.datasets[0].data = statusValues.length ? statusValues : [1];
        doughnutChart.data.datasets[0].backgroundColor = doughnutColors.length ? doughnutColors : ['#10b981'];
        doughnutChart.update();
    }

    const monthMap = {};
    data.forEach(r => {
        const m = r.date.substring(0, 7);
        if (!monthMap[m]) monthMap[m] = [];
        monthMap[m].push(r.score);
    });
    const months = Object.keys(monthMap).sort();
    const avgScores = months.map(m => Math.round(monthMap[m].reduce((a,b) => a + b, 0) / monthMap[m].length));
    
    if (lineChart) {
        lineChart.data.labels = months.length ? months : ['Current Period'];
        lineChart.data.datasets[0].data = months.length ? avgScores : [90];
        lineChart.update();
    }
}

// ─── TABLE RENDER ──────────────────────────────────────────────
function renderTableView(data) {
    const tbody = document.getElementById('tableViewBody');
    if (!tbody) return;
    const total = data.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * PAGE_SIZE;
    const pageRows = data.slice(start, start + PAGE_SIZE);

    const statusBadgeClass = {
        'Compliant': 'bg-emerald-100/70 text-emerald-700',
        'Pending': 'bg-amber-100/70 text-amber-700',
        'Urgent': 'bg-red-100/70 text-red-700',
        'Non-Compliant': 'bg-red-100/70 text-red-700'
    };

    tbody.innerHTML = pageRows.map((r, index) => {
        const rowIndex = start + index;
        return `
        <tr class="table-row-hover">
            <td class="py-3 pr-4 font-medium text-[#176B87]">${r.facility}</td>
            <td class="py-3 pr-4 text-slate-600">${r.inspector}</td>
            <td class="py-3 pr-4 text-slate-500">${r.date}</td>
            <td class="py-3 pr-4 font-semibold">${r.score} / 100</td>
            <td class="py-3 pr-4"><span class="status-badge ${statusBadgeClass[r.status] || 'bg-gray-100 text-gray-700'} px-2 py-1 rounded-full text-xs">${r.status}</span></td>
            <td class="py-3 pr-4">
                <div class="flex items-center gap-2">
                    <button onclick="viewRow(${rowIndex})" class="action-btn action-btn-view text-xs font-medium flex items-center gap-1" title="View Details">
                        <i class="fa-regular fa-eye"></i> View
                    </button>
                    <button onclick="downloadRow(${rowIndex})" class="action-btn action-btn-download text-xs font-medium flex items-center gap-1" title="Download CSV">
                        <i class="fa-solid fa-download"></i>
                    </button>
                </div>
            </td>
        </tr>
    `}).join('') || '<tr><td colspan="6" class="py-6 text-center text-slate-400">No records match this filter.</td></tr>';

    document.getElementById('tableViewSummary').textContent = total === 0
        ? 'No entries match this filter'
        : `Showing ${start + 1}-${Math.min(start + PAGE_SIZE, total)} of ${total} entries`;

    document.querySelectorAll('#tablePagination [data-page]').forEach(btn => {
        const page = parseInt(btn.dataset.page, 10);
        const isActive = page === currentPage;
        btn.classList.toggle('bg-[#176B87]', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('shadow-sm', isActive);
        btn.classList.toggle('shadow-[#176B87]/20', isActive);
        btn.classList.toggle('border', !isActive);
        btn.classList.toggle('border-[#B4D4FF]/30', !isActive);
    });
}

// ─── VIEW ROW ──────────────────────────────────────────────────
function viewRow(index) {
    const data = getFilteredData();
    if (index < 0 || index >= data.length) {
        showToast('Record not found.', 'info');
        return;
    }
    const row = data[index];
    document.getElementById('detailFacility').textContent = row.facility;
    document.getElementById('detailInspector').textContent = row.inspector;
    document.getElementById('detailDate').textContent = row.date;
    document.getElementById('detailScore').textContent = row.score + ' / 100';
    const statusEl = document.getElementById('detailStatus');
    statusEl.textContent = row.status;
    const statusColors = {
        'Compliant': 'text-emerald-600',
        'Pending': 'text-amber-600',
        'Urgent': 'text-red-600',
        'Non-Compliant': 'text-red-600'
    };
    statusEl.className = 'font-medium ' + (statusColors[row.status] || 'text-slate-600');
    
    const modal = document.getElementById('viewDetailModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeViewDetailModal() {
    const modal = document.getElementById('viewDetailModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

// ─── DOWNLOAD ROW ──────────────────────────────────────────────
function downloadRow(index) {
    const data = getFilteredData();
    if (index < 0 || index >= data.length) {
        showToast('Record not found.', 'info');
        return;
    }
    const row = data[index];
    const headers = ['Facility', 'Inspector', 'Date', 'Score', 'Status'];
    const values = [row.facility, row.inspector, row.date, row.score + '/100', row.status];
    const csvContent = headers.join(',') + '\n' + values.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',');
    
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `report_${row.facility.replace(/\s+/g, '_')}_${row.date}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    
    showToast(`Report for ${row.facility} downloaded!`, 'success');
}

function goToPage(page) {
    const data = getFilteredData();
    const totalPages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
    currentPage = Math.min(Math.max(1, page), totalPages);
    renderTableView(data);
}

// ─── TEMPLATE MANAGEMENT ─────────────────────────────────────
const TEMPLATE_STORAGE_KEY = 'hsms_report_templates';

function getSavedTemplates() {
    try {
        const raw = localStorage.getItem(TEMPLATE_STORAGE_KEY);
        if (!raw) return [];
        return JSON.parse(raw);
    } catch (e) {
        return [];
    }
}

function saveTemplates(templates) {
    try {
        localStorage.setItem(TEMPLATE_STORAGE_KEY, JSON.stringify(templates));
    } catch (e) {
        showToast('Could not save templates. Storage may be full.', 'info');
    }
}

// ─── SAVE TEMPLATE ────────────────────────────────────────────
function openSaveTemplateModal() {
    const modal = document.getElementById('saveTemplateModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
    document.getElementById('templateNameInput').value = '';
    document.getElementById('templateNameInput').focus();
}

function closeSaveTemplateModal() {
    const modal = document.getElementById('saveTemplateModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function saveTemplate() {
    const input = document.getElementById('templateNameInput');
    const name = input.value.trim();
    if (!name) {
        showToast('Please enter a template name.', 'info');
        input.focus();
        return;
    }
    
    const templates = getSavedTemplates();
    if (templates.some(t => t.name === name)) {
        if (!confirm(`A template named "${name}" already exists. Overwrite?`)) {
            return;
        }
        const filtered = templates.filter(t => t.name !== name);
        templates.length = 0;
        templates.push(...filtered);
    }
    
    const config = getCurrentConfig();
    templates.push({ name, data: config, savedAt: new Date().toISOString() });
    saveTemplates(templates);
    closeSaveTemplateModal();
    showToast(`Template "${name}" saved successfully!`, 'success');
}

// ─── LOAD TEMPLATE ────────────────────────────────────────────
function openTemplateModal() {
    const modal = document.getElementById('templateModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
    renderTemplateList();
}

function closeTemplateModal() {
    const modal = document.getElementById('templateModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function renderTemplateList() {
    const container = document.getElementById('templateList');
    const templates = getSavedTemplates();
    
    if (templates.length === 0) {
        container.innerHTML = `<p class="text-sm text-slate-400 text-center py-8">No saved templates found.</p>`;
        return;
    }
    
    container.innerHTML = templates.map((t, index) => `
        <div class="template-item flex items-center justify-between px-3 py-2.5 rounded-xl border border-[#B4D4FF]/20 hover:border-[#B4D4FF]/50 transition">
            <div class="flex items-center gap-3 flex-1 min-w-0" onclick="loadTemplateByName('${t.name}')">
                <i class="fa-regular fa-file-lines text-[#176B87]"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 truncate">${t.name}</p>
                    <p class="text-[10px] text-slate-400">${new Date(t.savedAt).toLocaleDateString()}</p>
                </div>
            </div>
            <button onclick="deleteTemplate('${t.name}')" class="delete-btn p-1.5 rounded-lg hover:bg-red-50 text-slate-300 hover:text-red-500 transition ml-2" title="Delete template">
                <i class="fa-regular fa-trash-can text-xs"></i>
            </button>
        </div>
    `).join('');
}

function loadTemplateByName(name) {
    const templates = getSavedTemplates();
    const template = templates.find(t => t.name === name);
    if (!template) {
        showToast(`Template "${name}" not found.`, 'info');
        return;
    }
    applyConfig(template.data);
    closeTemplateModal();
    showToast(`Template "${name}" loaded successfully!`, 'success');
}

function deleteTemplate(name) {
    if (!confirm(`Delete template "${name}"?`)) return;
    const templates = getSavedTemplates();
    const filtered = templates.filter(t => t.name !== name);
    if (filtered.length === templates.length) {
        showToast(`Template "${name}" not found.`, 'info');
        return;
    }
    saveTemplates(filtered);
    renderTemplateList();
    showToast(`Template "${name}" deleted.`, 'info');
}

// ─── REPORT GENERATION AUDIT LOGGER ───────────────────────────
async function logReportGeneration(reportName, format) {
    try {
        const facilitySelect = document.getElementById('facility');
        const facility = facilitySelect ? (facilitySelect.selectedOptions[0]?.textContent || facilitySelect.value) : 'All Core Departments';
        const start = document.getElementById('startDate')?.value || '';
        const end = document.getElementById('endDate')?.value || '';
        const dateRange = (start && end) ? `${start} to ${end}` : '';

        await fetch('<?= site_url('api/reports/log_export.php') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                report_name: reportName || 'Compliance & Operational Report',
                export_type: format || 'Custom Report',
                department: facility,
                date_range: dateRange
            })
        });

        // Silently reload live report audit logs
        loadLiveReportData();
    } catch (err) {
        console.error('Failed to record report generation audit log:', err);
    }
}

// ─── GENERATE REPORT ──────────────────────────────────────────
function generateReport() {
    const btn = document.getElementById('generateBtn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<div class="spinner"></div> Generating...';
    btn.disabled = true;

    logReportGeneration('Custom Operational & Compliance Report', 'Custom Query Generated');

    setTimeout(() => {
        refreshUI();
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Report Ready!';
        showToast('Report generated successfully!', 'success');
        openResultModal();

        setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }, 2000);
    }, 800);
}

// ─── RESULT MODAL ─────────────────────────────────────────────
function openResultModal() {
    const modal = document.getElementById('reportResultModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeResultModal() {
    const modal = document.getElementById('reportResultModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

// ─── DOWNLOAD WRAPPERS ──────────────────────────────────────
function downloadPDF() {
    closeResultModal();
    exportPDF();
}

function downloadExcel() {
    closeResultModal();
    setTimeout(() => { exportExcel(); showToast('Excel downloaded successfully!', 'success'); }, 300);
}

function downloadWord() {
    closeResultModal();
    setTimeout(() => { exportWord(); showToast('Word document downloaded successfully!', 'success'); }, 300);
}

// ─── EXPORT FUNCTIONS ─────────────────────────────────────────
function currentReportData() {
    return getFilteredData();
}

function downloadBlob(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function escapeExportHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getReportExportMarkup() {
    const reportType = document.getElementById('reportType')?.selectedOptions[0]?.textContent || 'Custom Report';
    const startDate = document.getElementById('startDate')?.value || '';
    const endDate = document.getElementById('endDate')?.value || '';
    if (!document.getElementById('tabChart')) return '';

    const sections = ['tabChart', 'tabTable', 'tabSummary'].map((id, index) => {
        const source = document.getElementById(id);
        if (!source) return '';

        const clone = source.cloneNode(true);
        clone.classList.remove('hidden', 'tab-content');
        clone.style.cssText = 'display:block;opacity:1;transform:none;animation:none;';
        clone.querySelectorAll('#tablePagination, .action-btn, button').forEach(element => element.remove());

        clone.querySelectorAll('canvas').forEach(canvas => {
            const sourceCanvas = document.getElementById(canvas.id);
            if (!sourceCanvas) return;
            const image = document.createElement('img');
            image.src = sourceCanvas.toDataURL('image/png');
            image.alt = canvas.id;
            image.style.cssText = 'display:block;width:100%;height:auto;max-height:280px;object-fit:contain;';
            canvas.replaceWith(image);
        });

        const titles = ['Chart View', 'Table View', 'Summary'];
        return `<section class="export-section"><h2>${titles[index]}</h2>${clone.innerHTML}</section>`;
    }).join('');

    return `
        <article class="export-report">
            <div class="export-header">
                <img src="${new URL('../assets/images/logo.png', window.location.href).href}" alt="Logo">
                <h1>Health Sanitation Management Caloocan</h1>
                <h3>${escapeExportHtml(reportType)}</h3>
                <p>${escapeExportHtml(startDate)} to ${escapeExportHtml(endDate)}</p>
            </div>
            ${sections}
        </article>
    `;
}

function getExportDocument(content, title, extraStyles = '') {
    return `<!doctype html><html><head><meta charset="UTF-8"><title>${escapeExportHtml(title)}</title>
        <style>
            @page { margin: 0.75in; }
            body { margin: 0; color: #1e293b; font-family: Arial, sans-serif; font-size: 12px; }
            .export-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 14px; margin-bottom: 24px; }
            .export-header img { width: 90px; display: block; margin: 0 auto 8px; }
            .export-header h1 { margin: 0; color: #000; font: bold 18pt 'Times New Roman', serif; text-transform: uppercase; }
            .export-header h3 { margin: 6px 0 0; color: #176B87; font-size: 14pt; }
            .export-header p { margin: 5px 0 0; color: #64748b; }
            .export-section { page-break-inside: auto; break-inside: auto; margin-bottom: 24px; }
            .export-section h2 { page-break-after: avoid; break-after: avoid; }
            .export-section h2 { color: #176B87; font-size: 14pt; border-bottom: 1px solid #B4D4FF; padding-bottom: 6px; }
            .export-section .backdrop-blur-sm, .export-section .bg-white\\/40 { background: #fff !important; }
            .export-section .grid { display: block !important; }
            .export-section .grid > * { margin-bottom: 14px; }
            .export-section table { border-collapse: collapse; width: 100%; }
            .export-section th { background: #176B87; color: #fff; padding: 7px; text-align: left; }
            .export-section td { border: 1px solid #cbd5e1; padding: 7px; }
            .export-section svg { max-width: 100%; }
            ${extraStyles}
        </style></head><body>${content}</body></html>`;
}

function exportCSV() {
    const data = currentReportData();
    const headers = ['Facility', 'Inspector', 'Date', 'Score', 'Status'];
    const lines = [headers.join(',')];
    data.forEach(r => {
        lines.push([r.facility, r.inspector, r.date, r.score + '/100', r.status].map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
    });
    downloadBlob('\uFEFF' + lines.join('\n'), 'compliance_report.csv', 'text/csv;charset=utf-8;');
    logReportGeneration('Sanitation Compliance & Inspection Report', 'CSV Export');
    showToast('CSV exported successfully!', 'success');
}

function exportExcel() {
    const data = currentReportData();
    const headers = ['Facility', 'Inspector', 'Date', 'Score', 'Status'];
    const rows = data.map(r => [r.facility, r.inspector, r.date, r.score + '/100', r.status]);
    const stamp = new Date().toISOString().slice(0, 10);

    showToast('Generating Excel report...', 'info');
    fetch('../api/reports/export.php?format=excel&title=Custom_Compliance_Report&module=Reports', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ headers, rows })
    }).then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.blob();
    }).then(blob => {
        downloadBlob(blob, `compliance_report_${stamp}.xlsx`, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        logReportGeneration('Sanitation Compliance & Inspection Report', 'Excel Export');
        showToast('Excel report downloaded successfully!', 'success');
    }).catch(err => {
        showToast('Excel export failed: ' + err.message, 'danger');
    });
}

function exportWord() {
    const html = getExportDocument(getReportExportMarkup(), 'Sanitation Compliance Report', 'body { font-family: Arial, sans-serif; }');
    downloadBlob(html, 'compliance_report.doc', 'application/msword');
    logReportGeneration('Sanitation Compliance & Inspection Report', 'Word Export');
    showToast('Word document exported successfully!', 'success');
}

function exportPDF() {
    const data = currentReportData();
    const headers = ['Facility', 'Inspector', 'Date', 'Score', 'Status'];
    const rows = data.map(r => [r.facility, r.inspector, r.date, r.score + '/100', r.status]);
    const stamp = new Date().toISOString().slice(0, 10);

    showToast('Generating PDF report...', 'info');
    fetch('../api/reports/export.php?format=pdf&title=Custom_Compliance_Report&module=Reports', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ headers, rows })
    }).then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.blob();
    }).then(blob => {
        downloadBlob(blob, `compliance_report_${stamp}.pdf`, 'application/pdf');
        logReportGeneration('Sanitation Compliance & Inspection Report', 'PDF Export');
        showToast('PDF report downloaded successfully!', 'success');
    }).catch(err => {
        showToast('PDF export failed: ' + err.message, 'danger');
    });
}

// ─── RESET FILTERS ────────────────────────────────────────────
function resetFilters() {
    document.getElementById('reportType').value = 'inspection';
    document.getElementById('facility').value = 'all';
    document.getElementById('inspector').value = 'all';
    const today = new Date();
    const start = new Date(today);
    start.setDate(today.getDate() - 45);
    document.getElementById('startDate').value = start.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];

    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    const allChip = document.querySelector('.filter-chip[data-status="all"]');
    if (allChip) allChip.classList.add('active');
    currentStatusFilter = 'all';
    currentPage = 1;

    refreshUI();
    showToast('Filters reset to default.', 'info');
}

// ─── STATUS CHIPS ─────────────────────────────────────────────
document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentStatusFilter = chip.dataset.status || 'all';
        currentPage = 1;
        refreshUI();
    });
});

// ─── FILTER CHANGE EVENTS ──────────────────────────────────
document.getElementById('reportType').addEventListener('change', refreshUI);
document.getElementById('facility').addEventListener('change', refreshUI);
document.getElementById('inspector').addEventListener('change', refreshUI);
document.getElementById('startDate').addEventListener('change', refreshUI);
document.getElementById('endDate').addEventListener('change', refreshUI);

// ─── TAB SWITCH ──────────────────────────────────────────────
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    const selectedTab = document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
    if (selectedTab) selectedTab.classList.remove('hidden');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}

// ─── MODAL CONTROLS ───────────────────────────────────────────
function openScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function scheduleReport() {
    closeScheduleModal();
    setTimeout(() => showToast('Report scheduled successfully!', 'success'), 300);
}

// ─── TOAST ──────────────────────────────────────────────────────
let toastTimer;
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');

    toastMessage.textContent = message;
    if (type === 'success') {
        toast.style.background = '#176B87';
        toastIcon.className = 'fa-regular fa-circle-check text-[#B4D4FF] text-lg';
    } else if (type === 'info') {
        toast.style.background = '#64748b';
        toastIcon.className = 'fa-regular fa-circle-info text-white text-lg';
    }

    toast.classList.add('toast-show');
    toast.style.pointerEvents = 'auto';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, 3000);
}

function hideToast() {
    const toast = document.getElementById('toast');
    toast.classList.remove('toast-show');
    toast.style.pointerEvents = 'none';
}

let viewingAllReports = false;

async function loadLiveReportData() {
    try {
        const resp = await fetch('<?= site_url('api/reports/data.php') ?>');
        const res = await resp.json();

        if (res && res.success) {
            allReportRows = res.report_rows || [];
            activeEmployeesList = res.employees || [];
            
            const recentLogs = res.recent_reports || [];
            baseRecentReports = recentLogs.slice(0, 5);
            extraRecentReports = recentLogs.slice(5, 10);

            populateInspectorDropdown(activeEmployeesList);
            autoSelectRoleDefaults(<?= json_encode(strtolower($_SESSION['role_description'] ?? ($_SESSION['role'] ?? 'admin'))) ?>);
            renderRecentReports();
            refreshUI();
        }
    } catch (e) {
        console.error('Failed to load live database report records:', e);
    }
}

function populateInspectorDropdown(employees, filterDepartment = 'all') {
    const inspectorSelect = document.getElementById('inspector');
    if (!inspectorSelect || !Array.isArray(employees)) return;

    const currentVal = inspectorSelect.value;
    const selectedDept = filterDepartment !== 'all' ? filterDepartment : (document.getElementById('facility') ? document.getElementById('facility').value : 'all');

    let filteredEmployees = employees;
    if (selectedDept !== 'all') {
        const targetDept = selectedDept.toLowerCase().replace(' services', '');
        filteredEmployees = employees.filter(emp => {
            const empDept = (emp.department || '').toLowerCase();
            return empDept === selectedDept.toLowerCase() || empDept.includes(targetDept) || targetDept.includes(empDept);
        });
    }

    const grouped = {};
    filteredEmployees.forEach(emp => {
        const d = emp.department || 'Health Center Services';
        if (!grouped[d]) grouped[d] = [];
        grouped[d].push(emp);
    });

    let html = '<option value="all">All Staff &amp; Officers</option>';
    
    Object.keys(grouped).forEach(deptName => {
        html += `<optgroup label="🏢 ${deptName}">`;
        grouped[deptName].forEach(emp => {
            const roleDesc = emp.role_description || emp.role || 'Staff Member';
            html += `<option value="${emp.name}">${emp.name} — ${roleDesc}</option>`;
        });
        html += `</optgroup>`;
    });

    inspectorSelect.innerHTML = html;
    if (currentVal && Array.from(inspectorSelect.options).some(o => o.value === currentVal)) {
        inspectorSelect.value = currentVal;
    }
}

function recentStatusBadge(status) {
    if (status === 'Generated') return 'bg-emerald-100/70 text-emerald-700';
    if (status === 'Processing') return 'bg-amber-100/70 text-amber-700';
    return 'bg-red-100/70 text-red-700';
}

function renderRecentReports() {
    const tbody = document.getElementById('recentReportsBody');
    if (!tbody) return;
    const list = viewingAllReports ? baseRecentReports.concat(extraRecentReports) : baseRecentReports;
    if (list.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="py-8 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-1.5">
                        <i class="fa-solid fa-file-circle-check text-2xl text-[#86B6F6]/60 mb-1"></i>
                        <span class="text-xs font-medium text-slate-500">No report generation logs recorded</span>
                        <span class="text-[11px] text-slate-400">Export or generate a report above to automatically log audit entries</span>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    tbody.innerHTML = list.map(r => `
        <tr class="table-row-hover transition-colors">
            <td class="py-3 pr-4 font-medium text-[#176B87]">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-file-lines text-[#86B6F6]"></i>
                    <span class="truncate max-w-[220px]" title="${escapeExportHtml(r.name || 'Compliance Report')}">${escapeExportHtml(r.name || 'Compliance Report')}</span>
                </div>
            </td>
            <td class="py-3 pr-4 text-xs font-medium text-slate-600">
                <span class="px-2.5 py-0.5 rounded-md bg-[#B4D4FF]/20 text-[#176B87] border border-[#B4D4FF]/40 whitespace-nowrap">
                    ${escapeExportHtml(r.type || 'Custom Report')}
                </span>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-700 font-medium">
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-[10px] text-slate-600 font-semibold uppercase">
                        ${escapeExportHtml((r.user || 'S').charAt(0))}
                    </div>
                    <span class="whitespace-nowrap">${escapeExportHtml(r.user || 'Staff Member')}</span>
                </div>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-500 whitespace-nowrap">${escapeExportHtml(r.date)}</td>
            <td class="py-3">
                <span class="status-badge ${recentStatusBadge(r.status)} px-2.5 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                    ${escapeExportHtml(r.status || 'Generated')}
                </span>
            </td>
        </tr>
    `).join('');
}

function toggleViewAllReports() {
    viewingAllReports = !viewingAllReports;
    document.getElementById('viewAllReportsBtn').textContent = viewingAllReports ? '↑ Show Less' : 'View All →';
    renderRecentReports();
}

// ─── KEYBOARD SHORTCUTS ──────────────────────────────────────
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const saveModal = document.getElementById('saveTemplateModal');
        if (!saveModal.classList.contains('hidden')) {
            saveTemplate();
            e.preventDefault();
        }
    }
    if (e.key === 'Escape') {
        if (!document.getElementById('viewDetailModal').classList.contains('hidden')) {
            closeViewDetailModal();
        }
    }
});

// ─── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const ctxBar = document.getElementById('barChart');
    if (ctxBar) {
        barChart = new Chart(ctxBar, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Compliance Score', data: [], backgroundColor: [], borderRadius: 8, borderSkipped: false }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100, grid: { color: 'rgba(180, 212, 255, 0.2)' } }, x: { grid: { display: false } } }
            }
        });
    }

    const ctxDoughnut = document.getElementById('doughnutChart');
    if (ctxDoughnut) {
        doughnutChart = new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#176B87', '#f59e0b', '#ef4444', '#f59e0b'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
        });
    }

    const ctxLine = document.getElementById('lineChart');
    if (ctxLine) {
        lineChart = new Chart(ctxLine, {
            type: 'line',
            data: { labels: [], datasets: [{ data: [], borderColor: '#176B87', backgroundColor: 'rgba(23, 107, 135, 0.1)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { display: false } } }
        });
    }

    loadLiveReportData();
});

function autoSelectRoleDefaults(role) {
    if (!role) return;
    const reportTypeSelect = document.getElementById('reportType');
    const facilitySelect = document.getElementById('facility');

    if (!reportTypeSelect || !facilitySelect) return;

    if (role.includes('doctor') || role.includes('nurse') || role.includes('health') || role.includes('clerk')) {
        reportTypeSelect.value = 'health_center';
        facilitySelect.value = 'Health Center Services';
    } else if (role.includes('sanitation') || role.includes('inspector')) {
        reportTypeSelect.value = 'sanitation';
        facilitySelect.value = 'Sanitation Permits';
    } else if (role.includes('surveillance') || role.includes('epidemiolog')) {
        reportTypeSelect.value = 'surveillance';
        facilitySelect.value = 'Health Surveillance';
    } else if (role.includes('nutrition') || role.includes('immuniz')) {
        reportTypeSelect.value = 'immunization';
        facilitySelect.value = 'Immunization & Nutrition';
    } else if (role.includes('wastewater') || role.includes('water')) {
        reportTypeSelect.value = 'wastewater';
        facilitySelect.value = 'Wastewater Services';
    }
}
</script>