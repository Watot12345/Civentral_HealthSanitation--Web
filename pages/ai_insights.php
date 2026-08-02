<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- ADD FONT AWESOME CDN (If not already in header.php) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<!-- ADD APEXCHARTS CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

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

        --module-health: #176B87;
        --module-sanitation: #D97706;
        --module-immunization: #2563EB;
        --module-wastewater: #9333EA;
        --module-surveillance: #E11D48;

        --radius-sm: 0.5rem;
        --radius-md: 0.75rem;
        --radius-lg: 1rem;
        --radius-xl: 1.5rem;

        --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.07);
        --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);

        --transition-fast: 0.15s ease;
        --transition-normal: 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
        --transition-slow: 0.35s ease;

        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.2);
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
        /* Hide application chrome while keeping the report print header/logo. */
        header,
        aside,
        .sidebar,
        #sidebar,
        footer,
        .footer,
        #footer {
            display: none !important;
        }

        /* Expand main content area to take up the full page width. */
        main,
        .main-content,
        #content {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            float: none !important;
        }

        .no-print {
            display: none !important;
        }

        /* 1. Allow full page printing without scroll crop to show ALL cards */
        body,
        html,
        main {
            height: auto !important;
            min-height: auto !important;
            overflow: visible !important;
            background: #ffffff !important;
        }

        main {
            display: block !important;
        }

        /* 2. Print Header Styling (Research Format) */
        #printHeader {
            display: block !important;
            text-align: center;
            font-family: "Times New Roman", Times, serif;
            margin-top: 0;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }

        #printHeader img {
            width: 120px;
            height: auto;
            margin: 0 auto 10px auto;
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
            margin: 5px 0 0 0;
        }

        /* 3. Ensure all cards print and don't split vertically across pages */
        .insight-card,
        .predictive-card,
        .kpi-card,
        .rounded-2xl {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            box-shadow: none !important;
            border: 1px solid #d4d4d8 !important;
            background: #ffffff !important;
        }

        /* 4. Fix overflow for predictive cards container so nothing gets cut off */
        #predictiveCards {
            overflow: visible !important;
            max-height: none !important;
        }

        /* 5. Ensure background colors and gradients show up in print */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }

    /* Modern Scrollbar styling */
    .scrollbar-thin::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #e4e4e7;
        border-radius: 10px;
    }

    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: var(--color-primary);
    }

    /* Advanced Fade-in Stagger */
    .fade-in {
        opacity: 0;
        transform: translateY(15px);
        animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes fadeInUp {
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .delay-1 {
        animation-delay: 0.1s;
    }

    .delay-2 {
        animation-delay: 0.2s;
    }

    .delay-3 {
        animation-delay: 0.3s;
    }

    .delay-4 {
        animation-delay: 0.4s;
    }

    /* AI Glow Cursor Effect */
    .ai-glow-container {
        position: relative;
        overflow: hidden;
    }

    .ai-glow-container::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(59, 130, 246, 0.08) 0%, rgba(139, 92, 246, 0.05) 40%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
        z-index: 0;
        transform: translate(-50%, -50%);
        transition: opacity 0.3s ease;
        opacity: 0;
        left: var(--mouse-x, 50%);
        top: var(--mouse-y, 50%);
    }

    .ai-glow-container:hover::before {
        opacity: 1;
    }

    .ai-glow-container>* {
        position: relative;
        z-index: 1;
    }

    .pulse-dot {
        animation: pulse2 2.5s infinite;
    }

    @keyframes pulse2 {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.4;
            transform: scale(0.9);
        }
    }

    /* Glassmorphism Hover Lift */
    .hover-lift {
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }

    .hover-lift:hover {
        transform: translateY(-3px);
        border-color: #e4e4e7;
        box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(255, 255, 255, 0.8) inset;
    }

    .status-dot {
        display: inline-block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        margin-right: 6px;
    }

    .status-dot.online {
        background: #10b981;
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        animation: statusPulse 2s infinite;
    }

    /* Highlight Specific Calculated Numbers Only (Clean Text Only) */
    .highlight-danger {
        color: #dc2626;
        font-weight: 700;
        background: transparent;
        padding: 0;
        display: inline;
    }
    .highlight-warning {
        color: #d97706;
        font-weight: 700;
        background: transparent;
        padding: 0;
        display: inline;
    }
    .highlight-success {
        color: #059669;
        font-weight: 700;
        background: transparent;
        padding: 0;
        display: inline;
    }
    .highlight-info {
        color: #2563eb;
        font-weight: 700;
        background: transparent;
        padding: 0;
        display: inline;
    }

    @keyframes statusPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
        }

        70% {
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
        }
    }

    /* ===== KPI CARDS (Applied from System Overview) ===== */
    .kpi-card {
        position: relative;
        overflow: hidden;
        transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1),
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
        background: linear-gradient(120deg, transparent, rgba(255, 255, 255, 0.55), transparent);
        opacity: 0;
        pointer-events: none;
    }

    .kpi-card:hover .kpi-shine {
        opacity: 1;
        animation: shine 0.85s ease forwards;
    }

    @keyframes shine {
        0% {
            transform: translateX(-120%) skewX(-20deg);
        }

        100% {
            transform: translateX(220%) skewX(-20deg);
        }
    }

    .kpi-value {
        transition: transform 0.22s ease;
        display: inline-block;
        background: linear-gradient(135deg, #09090b 0%, #52525b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .kpi-card:hover .kpi-value {
        transform: scale(1.06);
    }

    .kpi-watermark {
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .kpi-card:hover .kpi-watermark {
        transform: scale(1.12) rotate(-3deg);
    }

    .kpi-ring-progress {
        stroke-dasharray: 100;
        stroke-dashoffset: 100;
        animation: ringFill 1s cubic-bezier(0.65, 0, 0.35, 1) forwards;
    }

    @keyframes ringFill {
        to {
            stroke-dashoffset: var(--offset, 0);
        }
    }

    .kpi-ring {
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .kpi-card:hover .kpi-ring {
        transform: scale(1.08);
    }

    .kpi-card .kpi-change {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 9999px;
        font-size: 10px;
        font-weight: 600;
        transition: transform 0.3s ease;
    }

    .kpi-card:hover .kpi-change {
        transform: scale(1.1);
    }

    .kpi-card .kpi-change.positive {
        background: #ecfdf5;
        color: #059669;
    }

    .kpi-card .kpi-change.negative {
        background: #fef2f2;
        color: #dc2626;
    }

    /* Glow effects */
    .kpi-card.glow-green {
        border-color: rgba(16, 185, 129, 0.1);
    }

    .kpi-card.glow-green:hover {
        border-color: rgba(16, 185, 129, 0.4);
        box-shadow: 0 15px 40px -10px rgba(16, 185, 129, 0.15);
    }

    .kpi-card.glow-blue {
        border-color: rgba(59, 130, 246, 0.1);
    }

    .kpi-card.glow-blue:hover {
        border-color: rgba(59, 130, 246, 0.4);
        box-shadow: 0 15px 40px -10px rgba(59, 130, 246, 0.15);
    }

    .kpi-card.glow-purple {
        border-color: rgba(139, 92, 246, 0.1);
    }

    .kpi-card.glow-purple:hover {
        border-color: rgba(139, 92, 246, 0.4);
        box-shadow: 0 15px 40px -10px rgba(139, 92, 246, 0.15);
    }

    .kpi-card.glow-amber {
        border-color: rgba(245, 158, 11, 0.1);
    }

    .kpi-card.glow-amber:hover {
        border-color: rgba(245, 158, 11, 0.4);
        box-shadow: 0 15px 40px -10px rgba(245, 158, 11, 0.15);
    }

    .kpi-card.glow-teal {
        border-color: rgba(20, 184, 166, 0.1);
    }

    .kpi-card.glow-teal:hover {
        border-color: rgba(20, 184, 166, 0.4);
        box-shadow: 0 15px 40px -10px rgba(20, 184, 166, 0.15);
    }

    /* Staggered entrance for metrics */
    .metrics-grid>div {
        opacity: 0;
        animation: slideUp 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(36px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .metrics-grid>div:nth-child(1) {
        animation-delay: 0.05s;
    }

    .metrics-grid>div:nth-child(1) .kpi-ring-progress {
        animation-delay: 0.35s;
    }

    .metrics-grid>div:nth-child(2) {
        animation-delay: 0.12s;
    }

    .metrics-grid>div:nth-child(2) .kpi-ring-progress {
        animation-delay: 0.42s;
    }

    .metrics-grid>div:nth-child(3) {
        animation-delay: 0.19s;
    }

    .metrics-grid>div:nth-child(3) .kpi-ring-progress {
        animation-delay: 0.49s;
    }

    .metrics-grid>div:nth-child(4) {
        animation-delay: 0.26s;
    }

    .metrics-grid>div:nth-child(4) .kpi-ring-progress {
        animation-delay: 0.56s;
    }

    .metrics-grid>div:nth-child(5) {
        animation-delay: 0.33s;
    }

    .metrics-grid>div:nth-child(5) .kpi-ring-progress {
        animation-delay: 0.63s;
    }

    /* Modern Glassmorphic Tooltip */
    .kpi-tooltip {
        position: fixed;
        z-index: 1000;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 16px;
        box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        border: 1px solid var(--glass-border);
        padding: 18px;
        min-width: 320px;
        max-width: 360px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(12px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }

    .kpi-tooltip.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    .kpi-tooltip .tooltip-title {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: -0.01em;
        color: #18181b;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(228, 228, 231, 0.7);
    }

    .kpi-tooltip .tooltip-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        font-size: 12px;
        color: #52525b;
    }

    .kpi-tooltip .tooltip-row .label {
        color: #71717a;
    }

    .kpi-tooltip .tooltip-row .value {
        font-weight: 600;
        color: #18181b;
    }

    .kpi-tooltip .mini-chart {
        margin-top: 10px;
        height: 120px;
        border-radius: 8px;
        background: rgba(250, 250, 250, 0.5);
        padding: 6px;
        display: flex;
        justify-content: center;
        align-items: center;
        overflow: visible;
    }

    .kpi-tooltip .mini-chart>div {
        margin: 0 auto !important;
    }

    .kpi-tooltip .tooltip-arrow {
        position: absolute;
        width: 12px;
        height: 12px;
        background: var(--glass-bg);
        transform: rotate(45deg);
    }

    .kpi-tooltip .tooltip-arrow.bottom {
        bottom: -6px;
        left: 50%;
        margin-left: -6px;
        border-right: 1px solid rgba(228, 228, 231, 0.5);
        border-bottom: 1px solid rgba(228, 228, 231, 0.5);
    }

    .kpi-tooltip .tooltip-arrow.top {
        top: -6px;
        left: 50%;
        margin-left: -6px;
        border-left: 1px solid rgba(228, 228, 231, 0.5);
        border-top: 1px solid rgba(228, 228, 231, 0.5);
    }

    .kpi-tooltip .tooltip-pie-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: center;
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid rgba(228, 228, 231, 0.7);
    }

    .kpi-tooltip .tooltip-pie-legend span {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 10px;
        color: #52525b;
    }

    .kpi-tooltip .tooltip-pie-legend .dot {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }

    /* AI Icon Animation */
    .ai-icon {
        display: inline-block;
        animation: aiPulse 4s cubic-bezier(0.4, 0, 0.2, 1) infinite;
    }

    @keyframes aiPulse {

        0%,
        100% {
            transform: scale(1) rotate(0deg);
            filter: drop-shadow(0 0 0px rgba(134, 182, 246, 0));
        }

        50% {
            transform: scale(1.08) rotate(-3deg);
            filter: drop-shadow(0 8px 16px rgba(134, 182, 246, 0.3));
        }
    }

    .ai-gradient {
        background: linear-gradient(135deg, #0f766e 0%, #3b82f6 50%, #8b5cf6 100%);
        background-size: 200% 200%;
        animation: gradientMove 6s ease-in-out infinite;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    @keyframes gradientMove {

        0%,
        100% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }
    }

    /* AI Loading Skeleton */
    .ai-skeleton {
        background: linear-gradient(90deg, #f4f4f5 25%, #e4e4e7 50%, #f4f4f5 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 8px;
    }

    @keyframes shimmer {
        0% {
            background-position: 200% 0;
        }

        100% {
            background-position: -200% 0;
        }
    }

    /* Insight cards styling */
    .insight-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid rgba(228, 228, 231, 0.5);
    }

    .insight-card:hover {
        border-color: rgba(59, 130, 246, 0.3);
        box-shadow: 0 10px 30px -10px rgba(59, 130, 246, 0.1);
        transform: translateY(-2px);
    }

    /* Predictive cards */
    .predictive-card {
        background: rgba(255, 255, 255, 0.6);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        border: 1px solid transparent;
    }

    .predictive-card:hover {
        background: rgba(255, 255, 255, 0.9);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.05);
        border-color: #e4e4e7;
    }

    .module-item {
        transition: all 0.2s ease;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid transparent;
    }

    .module-item:hover {
        background: rgba(244, 244, 245, 0.7);
        border-color: #e4e4e7;
        transform: translateX(4px);
    }

    .module-tooltip {
        position: fixed;
        z-index: 999;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 12px;
        box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        border: 1px solid var(--glass-border);
        padding: 16px;
        min-width: 220px;
        max-width: 280px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(8px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        pointer-events: none;
    }

    .module-tooltip.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0) scale(1);
    }

    /* Service Distribution donut tooltip (positioned relative to its own container, not the viewport) */
    .donut-tooltip {
        position: absolute;
        z-index: 50;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 10px;
        box-shadow: 0 15px 35px -8px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        border: 1px solid var(--glass-border);
        padding: 7px 12px;
        font-size: 11px;
        font-weight: 700;
        color: #18181b;
        white-space: nowrap;
        pointer-events: none;
    }
</style>

<main class="flex-1 h-screen flex flex-col m-0 overflow-y-auto overflow-x-hidden bg-zinc-50/50 rounded-none font-sans scrollbar-thin">

    <!-- PRINT HEADER (Hidden on screen, shown in print) -->
    <div id="printHeader">
        <!-- Update the src path below to point to your actual logo image -->
        <img src="../assets/images/logo.png" alt="Logo">
        <h1>Health Sanitation Management Caloocan</h1>
        <h2>AI Analytics & Performance Report</h2>
    </div>

    <div class="p-8 max-w-[1600px] w-full mx-auto">
        <!-- Page Header -->
        <div class="mb-8 flex items-start justify-between flex-wrap gap-4 fade-in">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-zinc-900 flex items-center gap-3">
                    <span class="ai-icon no-print">
                        <svg class="w-8 h-8 text-[#3b82f6]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" fill="none" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9l1.5 1.5M15 9l-1.5 1.5M9 15l1.5-1.5M15 15l-1.5-1.5" />
                        </svg>
                    </span>
                    <span class="ai-gradient">AI Analytics</span>
                    <span class="text-[11px] font-semibold text-zinc-500 bg-zinc-100 px-2.5 py-0.5 rounded-full border border-zinc-200/50 no-print">v2.5.0</span>
                </h1>
                <p class="text-sm text-zinc-500 mt-1.5 font-medium">AI-powered insights and advanced analytics for data-driven decisions</p>
            </div>
            <div class="flex items-center gap-3 bg-white/80 backdrop-blur-md px-4 py-2.5 rounded-xl border border-zinc-200 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] no-print">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Data Freshness</span>
                <span class="flex items-center gap-1.5">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-semibold text-zinc-700" id="headerTimestamp">Live</span>
                </span>
                <span class="w-px h-4 bg-zinc-200"></span>
                <span class="text-xs font-semibold text-zinc-500 flex items-center">
                    <span class="status-dot online"></span> All systems go
                </span>
            </div>
        </div>

        <!-- Toolbar -->


        <!-- AI Insights (With Cursor Glow) -->
        <div class="ai-glow-container mb-8 rounded-2xl border border-zinc-200 bg-white/60 backdrop-blur-md p-6 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.02)] hover-lift fade-in delay-2" id="aiInsightPanel">
            <div class="flex items-center gap-2.5 mb-5">
                <div class="p-1.5 bg-purple-50 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500">AI Insights</h2>
                <span class="text-[10px] px-2.5 py-0.5 bg-purple-50 text-purple-600 rounded-full font-bold flex items-center gap-1.5 border border-purple-100">
                    <span class="pulse-dot inline-block w-1.5 h-1.5 rounded-full bg-purple-500"></span> Live Analysis
                </span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4" id="insightsGrid"></div>
        </div>



        <!-- Trend + Predictive + Modules -->
        <div class="mb-8 grid grid-cols-1 lg:grid-cols-3 gap-6 fade-in delay-3">
            <!-- Trend Analysis -->
            <div class="rounded-2xl border border-zinc-200 bg-white/80 backdrop-blur-md p-6 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.02)] hover-lift flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 bg-blue-50 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Trend Analysis</h2>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3.5 no-print">
                        <select id="trendFilter" class="text-xs font-semibold bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-zinc-100 flex-1 cursor-pointer">
                            <option value="disease" selected>Disease Surveillance</option>
                            <option value="service">Service Requests</option>
                            <option value="combined">Combined View</option>
                        </select>
                    </div>
                    <p id="trendSubtitle" class="text-xs font-semibold text-zinc-400 mt-4 mb-2">Disease Cases Trend</p>
                </div>
                <div id="trendChart" class="h-56 mt-2"></div>
                <div id="trendLegend" class="mt-4 flex flex-wrap items-center gap-3 text-xs font-medium text-zinc-500"></div>
                <!-- 1-Line Correlation Insight Callout -->
                <div id="correlationInsightCallout" class="mt-3 pt-2.5 border-t border-zinc-100 text-[11px] font-semibold text-rose-800 bg-rose-50/60 px-3 py-1.5 rounded-lg border border-rose-100/80 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span id="correlationInsightText">Disease Surveillance and Health Center Services move together (+84% co-movement correlation) over the last 6 months.</span>
                </div>
            </div>

            <!-- Predictive Analytics (Enhanced Line Chart + KPI Badges) -->
            <div class="rounded-2xl border border-zinc-200 bg-white/80 backdrop-blur-md p-6 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.02)] hover-lift flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2.5">
                            <div class="p-1.5 bg-emerald-50 rounded-lg">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Predictive Analytics</h2>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded-full border border-emerald-100">AI Forecast</span>
                    </div>
                    <p class="text-xs font-semibold text-zinc-400 mt-1 mb-4">Next Month Forecast · Confidence interval ±5%</p>
                </div>
                
                <!-- KPI Summary Badges -->
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <div class="bg-red-50/60 border border-red-100 p-2 rounded-xl text-center cursor-help" title="High certainty (92% statistical confidence based on 6-month historical trend)">
                        <div class="text-[9px] font-bold text-red-600 uppercase tracking-wider">Disease Cases</div>
                        <div id="pred-cases-val" class="text-sm font-extrabold text-red-900 mt-0.5">198</div>
                        <div class="text-[9px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-full inline-block mt-0.5">92% High Certainty</div>
                    </div>
                    <div class="bg-blue-50/60 border border-blue-100 p-2 rounded-xl text-center cursor-help" title="Moderate certainty (89% statistical confidence based on historical permit requests)">
                        <div class="text-[9px] font-bold text-blue-600 uppercase tracking-wider">Permit Requests</div>
                        <div id="pred-permits-val" class="text-sm font-extrabold text-blue-900 mt-0.5">435</div>
                        <div class="text-[9px] font-bold text-amber-700 bg-amber-100/70 px-1.5 py-0.5 rounded-full inline-block mt-0.5">89% Moderate Certainty</div>
                    </div>
                    <div class="bg-emerald-50/60 border border-emerald-100 p-2 rounded-xl text-center cursor-help" title="High certainty (95% statistical confidence based on vaccination demand logs)">
                        <div class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider">Vaccine Demand</div>
                        <div id="pred-vaccines-val" class="text-sm font-extrabold text-emerald-900 mt-0.5">273</div>
                        <div class="text-[9px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-full inline-block mt-0.5">95% High Certainty</div>
                    </div>
                </div>

                <!-- ApexCharts Line Graph Container -->
                <div id="predictiveLineChart" class="h-36"></div>

                <!-- 1-Line Forecast Insight Callout -->
                <div id="forecastInsightCallout" class="mt-2 pt-2 border-t border-zinc-100 text-[11px] font-semibold text-emerald-800 bg-emerald-50/60 px-3 py-1.5 rounded-lg border border-emerald-100/80 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span id="forecastInsightText">Disease Cases forecasted at 198 (+8.3% vs current) — recommend increasing satellite triage staff next month.</span>
                </div>
            </div>

            <!-- Operational Modules -->
            <div id="modulesContainerCard" class="rounded-2xl border border-zinc-200 bg-white/80 backdrop-blur-md p-6 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.02)] hover-lift flex flex-col justify-between transition-all">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="p-1.5 bg-rose-50 rounded-lg">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.024 9.024 0 0120.488 9z"></path>
                            </svg>
                        </div>
                        <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Operational Modules</h2>
                        <!-- Interactive Toggle: Current vs Projected Next Month -->
                        <div class="ml-auto inline-flex items-center rounded-full bg-zinc-100 p-0.5 border border-zinc-200/80 text-[10px] font-bold">
                            <button id="btnModuleCurrent" type="button" class="px-2.5 py-0.5 rounded-full bg-white text-zinc-800 shadow-xs transition-all cursor-pointer font-bold">Current</button>
                            <button id="btnModuleProjected" type="button" class="px-2.5 py-0.5 rounded-full text-zinc-500 hover:text-zinc-800 transition-all cursor-pointer">Projected Next Month</button>
                        </div>
                    </div>
                    <p class="text-xs font-semibold text-zinc-400 mt-1 mb-3">Share of activity by module · hover for details</p>
                </div>
                <div id="modulesChart" class="h-44"></div>
                <div class="mt-2 space-y-1.5 text-xs font-semibold text-zinc-600" id="moduleLegend"></div>
                <!-- 1-Line Module Insight Callout -->
                <div id="moduleInsightCallout" class="mt-3 pt-2.5 border-t border-zinc-100 text-[11px] font-semibold text-amber-800 bg-amber-50/60 px-3 py-1.5 rounded-lg border border-amber-100/80 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span id="moduleInsightText">Sanitation Permits forecasted at 31.4% (+3.3pts vs current) — reassign 2 health inspectors to commercial permit reviews.</span>
                </div>
            </div>
        </div>







        <!-- Performance Metrics (Restyled to match System Overview KPIs) -->
        <div class="rounded-2xl mb-10 border border-zinc-200 bg-white/80 backdrop-blur-md p-6 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.02)] hover-lift fade-in delay-4">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2.5">
                    <div class="p-1.5 bg-blue-50 border border-blue-100 rounded-lg transition-all">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Performance Metrics</h2>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-semibold text-zinc-400">vs last month</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse no-print"></span>
                    <span class="text-[10px] font-bold text-blue-500 uppercase tracking-wider no-print">Hover for details</span>
                </div>
            </div>
            <div class="metrics-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4" id="metricsGrid"></div>
        </div>

        <div class="no-print mb-8 rounded-2xl border border-zinc-200 bg-white/80 backdrop-blur-md p-4 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.03)] flex flex-wrap items-center gap-4 fade-in delay-1">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <select id="dateRangeSelect" class="text-xs font-medium bg-zinc-50 hover:bg-zinc-100 text-zinc-700 border border-zinc-200 rounded-lg px-3 py-2 transition-all focus:outline-none focus:ring-2 focus:ring-zinc-100 cursor-pointer">
                    <option value="today">Today</option>
                    <option value="7d">Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                    <option value="90d">Last 90 Days</option>
                    <option value="6m" selected>Last 6 Months</option>
                    <option value="12m">Last 12 Months</option>
                    <option value="custom">Custom Range</option>
                </select>
                <div id="customDateWrap" class="hidden items-center gap-1.5">
                    <input type="date" id="dateFrom" class="text-xs bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-zinc-100">
                    <span class="text-zinc-300 font-bold">–</span>
                    <input type="date" id="dateTo" class="text-xs bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-zinc-100">
                </div>
            </div>

            <div class="h-6 w-px bg-zinc-200"></div>

            <label class="flex items-center gap-2.5 text-xs font-semibold text-zinc-600 cursor-pointer select-none">
                <span class="relative inline-flex items-center">
                    <input type="checkbox" id="yoyToggle" class="sr-only peer">
                    <span class="w-9 h-5 bg-zinc-200 peer-checked:bg-zinc-800 rounded-full transition-colors duration-200"></span>
                    <span class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-4"></span>
                </span>
                Compare YoY
            </label>

            <div class="h-6 w-px bg-zinc-200"></div>

            <div class="flex items-center gap-3 text-xs text-zinc-600 font-semibold">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <span class="relative inline-flex items-center">
                        <input type="checkbox" id="autoRefreshToggle" class="sr-only peer" checked>
                        <span class="w-9 h-5 bg-zinc-200 peer-checked:bg-emerald-600 rounded-full transition-colors duration-200"></span>
                        <span class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-4"></span>
                    </span>
                    Auto-refresh
                </label>
                <select id="refreshIntervalSelect" class="text-xs font-medium bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-2 py-1.5 focus:outline-none cursor-pointer">
                    <option value="30" selected>30s</option>
                    <option value="60">1m</option>
                    <option value="300">5m</option>
                </select>
                <span id="lastUpdatedLabel" class="text-zinc-400 font-medium whitespace-nowrap">Updated just now</span>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <button onclick="refreshData()" class="flex items-center gap-2 text-xs font-bold text-zinc-700 border border-zinc-200 bg-white rounded-lg px-3.5 py-2 hover:bg-zinc-50 active:scale-95 transition-all">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
                <button onclick="window.print()" class="flex items-center gap-2 text-xs font-bold text-zinc-700 border border-zinc-200 bg-white rounded-lg px-3.5 py-2 hover:bg-zinc-50 active:scale-95 transition-all">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"></path>
                    </svg>
                    Export
                </button>
            </div>
        </div>

        <!-- Staff Performance -->
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white/80 backdrop-blur-md p-6 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.02)] hover-lift fade-in delay-4">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2.5">
                    <div class="p-1.5 bg-indigo-50 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Staff Performance</h2>
                    <span class="text-[10px] font-bold px-2.5 py-0.5 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">Q2 2026</span>
                </div>
                <div class="flex items-center gap-2 no-print">
                    <select id="staffSort" class="text-xs font-semibold bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-zinc-100 cursor-pointer">
                        <option value="desc" selected>Highest First</option>
                        <option value="asc">Lowest First</option>
                    </select>
                </div>
            </div>
            <p class="text-xs font-semibold text-zinc-400 mt-1 mb-4">Overall performance score by staff member · hover for detail</p>
            <div id="staffChart" class="h-80"></div>
        </div>

        <!-- Footer -->
        <div class="mt-12 pt-6 border-t border-zinc-200 flex items-center justify-between flex-wrap gap-4 text-[10px] text-zinc-400 font-semibold tracking-wide">
            <div class="flex items-center gap-4 flex-wrap">
                <span>Health Center DB · Sanitation DB · Immunization DB · Surveillance DB</span>
                <span class="w-px h-4 bg-zinc-200"></span>
                <span class="flex items-center gap-1.5">API Status: <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span> All operational</span>
            </div>
            <div>
                <span>Report generated: <span id="footerTimestamp" class="text-zinc-500"><?php echo date('Y-m-d H:i:s'); ?></span></span>
            </div>
        </div>
    </div>
</main>

<!-- Hover Tooltip Container for Performance Metrics -->
<div id="hoverTooltip" class="kpi-tooltip">
    <div class="tooltip-arrow bottom"></div>
    <div class="tooltip-title" id="tooltipTitle">Metric Details</div>
    <div id="tooltipContent"></div>
</div>

<!-- Module Tooltip -->
<div id="moduleTooltip" class="module-tooltip">
    <div style="font-weight:700;font-size:13px;color:#18181b;margin-bottom:10px;letter-spacing:-0.01em;" id="moduleTooltipTitle"></div>
    <div id="moduleTooltipContent"></div>
</div>

<!-- bypass datamask for non data masking -->
<p class="kpi-number text-xl font-black text-slate-900 mt-1 leading-none"></p>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // =====================================================================
        // DATA
        // =====================================================================
        const InsightsData = [{
                id: 'dengue',
                title: 'Dengue cases increased <span class="text-red-600 font-bold">18%</span> compared to last month.',
                priority: 'High Priority',
                priorityColor: 'red',
                icon: 'alert',
                detail: 'Dengue Cases – Barangay Breakdown',
                subtitle: '18% increase vs last month',
                rows: [{
                        label: 'Barangay 172',
                        value: '42 cases'
                    },
                    {
                        label: 'Barangay 176',
                        value: '31 cases'
                    },
                    {
                        label: 'Barangay 168',
                        value: '19 cases'
                    },
                    {
                        label: 'Barangay 174',
                        value: '12 cases'
                    },
                    {
                        label: 'Total this month',
                        value: '104 cases'
                    }
                ]
            },
            {
                id: 'barangay',
                title: 'Barangay 172 has the <span class="text-amber-600 font-bold">highest patient volume</span>.',
                priority: 'Medium',
                priorityColor: 'amber',
                icon: 'users',
                detail: 'Barangay 172 – Patient Volume',
                subtitle: 'Highest volume this month',
                rows: [{
                        label: 'Health Center visits',
                        value: '268'
                    },
                    {
                        label: 'Immunization visits',
                        value: '94'
                    },
                    {
                        label: 'Sanitation requests',
                        value: '37'
                    },
                    {
                        label: 'Staff on duty',
                        value: '6'
                    }
                ]
            },
            {
                id: 'permits',
                title: 'Permit processing time improved by <span class="text-emerald-600 font-bold">21%</span>.',
                priority: 'Positive',
                priorityColor: 'emerald',
                icon: 'check',
                detail: 'Permit Processing – Efficiency Gain',
                subtitle: '21% faster vs last month',
                rows: [{
                        label: 'Avg. time (this month)',
                        value: '2.3 days'
                    },
                    {
                        label: 'Avg. time (last month)',
                        value: '2.9 days'
                    },
                    {
                        label: 'Permits processed',
                        value: '356'
                    },
                    {
                        label: 'Backlog reduced by',
                        value: '44 permits'
                    }
                ]
            },
            {
                id: 'vaccination',
                title: 'Recommend increasing vaccination staff next week based on trends.',
                priority: 'AI Suggestion',
                priorityColor: 'blue',
                icon: 'ai',
                detail: 'Vaccination Staffing Recommendation',
                subtitle: 'Predictive recommendation',
                rows: [{
                        label: 'Projected demand',
                        value: '2,150 doses'
                    },
                    {
                        label: 'Current staff capacity',
                        value: '1,680 doses'
                    },
                    {
                        label: 'Suggested additional staff',
                        value: '3 nurses'
                    },
                    {
                        label: 'Confidence level',
                        value: '58%'
                    }
                ]
            }
        ];

        const PredictiveData = [{
                title: 'Expected Disease Cases',
                value: '185',
                confidence: '92%',
                trend: '↑ 8.3% vs current month',
                color: 'indigo',
                icon: 'alert',
                detail: 'Disease Cases Forecast',
                subtitle: 'Next month projection',
                rows: [{
                        label: 'Expected cases',
                        value: '185'
                    },
                    {
                        label: 'Confidence interval',
                        value: '±5%'
                    },
                    {
                        label: 'Trend direction',
                        value: '↑ 8.3%'
                    },
                    {
                        label: 'Risk level',
                        value: 'Moderate'
                    }
                ],
                pieData: [{
                        label: 'Low Risk',
                        value: 25,
                        color: '#10b981'
                    },
                    {
                        label: 'Moderate Risk',
                        value: 55,
                        color: '#f59e0b'
                    },
                    {
                        label: 'High Risk',
                        value: 20,
                        color: '#ef4444'
                    }
                ]
            },
            {
                title: 'Estimated Permit Requests',
                value: '420',
                confidence: '89%',
                trend: '↑ 12.1% vs current month',
                color: 'blue',
                icon: 'document',
                detail: 'Permit Requests Forecast',
                subtitle: 'Next month projection',
                rows: [{
                        label: 'Estimated requests',
                        value: '420'
                    },
                    {
                        label: 'Confidence interval',
                        value: '±4%'
                    },
                    {
                        label: 'Trend direction',
                        value: '↑ 12.1%'
                    },
                    {
                        label: 'Staff required',
                        value: '8 officers'
                    }
                ],
                pieData: [{
                        label: 'Residential',
                        value: 60,
                        color: '#3b82f6'
                    },
                    {
                        label: 'Commercial',
                        value: 25,
                        color: '#8b5cf6'
                    },
                    {
                        label: 'Industrial',
                        value: 15,
                        color: '#f59e0b'
                    }
                ]
            },
            {
                title: 'Vaccination Demand',
                value: '2,150',
                unit: 'doses',
                confidence: '58%',
                trend: '↑ 15.7% vs current month',
                color: 'amber',
                icon: 'health',
                detail: 'Vaccination Demand Forecast',
                subtitle: 'Next month projection',
                rows: [{
                        label: 'Doses needed',
                        value: '2,150'
                    },
                    {
                        label: 'Confidence interval',
                        value: '±8%'
                    },
                    {
                        label: 'Trend direction',
                        value: '↑ 15.7%'
                    },
                    {
                        label: 'Stock status',
                        value: 'Sufficient'
                    }
                ],
                pieData: [{
                        label: 'Children',
                        value: 40,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Adults',
                        value: 35,
                        color: '#10b981'
                    },
                    {
                        label: 'Seniors',
                        value: 25,
                        color: '#8b5cf6'
                    }
                ]
            }
        ];

        const ModuleData = [{
                label: 'Health Center Services',
                share: 32,
                color: '#3b82f6',
                trend: '▲ 3.2%',
                status: 'On track'
            },
            {
                label: 'Sanitation Permits',
                share: 24,
                color: '#10b981',
                trend: '▼ 1.1%',
                status: 'On track'
            },
            {
                label: 'Immunization & Nutrition',
                share: 20,
                color: '#f59e0b',
                trend: '▲ 2.4%',
                status: 'On track'
            },
            {
                label: 'Health Surveillance',
                share: 16,
                color: '#f43f5e',
                trend: '▼ 0.8%',
                status: 'On track'
            },
            {
                label: 'Wastewater Services',
                share: 8,
                color: '#a855f7',
                trend: '▲ 4.2%',
                status: 'Needs attention'
            }
        ];

        const MetricsData = [{
                label: 'Permit Processing',
                value: 2.3,
                unit: 'Days',
                change: '↓ 21%',
                changeColor: 'emerald',
                progress: 78,
                glow: 'glow-green',
                watermark: 'fa-file-signature',
                details: [{
                        label: 'Current Average',
                        value: '2.3 Days'
                    },
                    {
                        label: 'Previous Month',
                        value: '2.9 Days'
                    },
                    {
                        label: 'Improvement',
                        value: '21%'
                    },
                    {
                        label: 'Target',
                        value: '< 2.5 Days'
                    },
                    {
                        label: 'Backlog',
                        value: '12 permits'
                    }
                ],
                pieData: [{
                        label: 'Completed',
                        value: 78,
                        color: '#10b981'
                    },
                    {
                        label: 'In Progress',
                        value: 12,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Pending',
                        value: 10,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'AI Report Accuracy',
                value: 96,
                unit: '%',
                change: '↑ 5%',
                changeColor: 'blue',
                progress: 96,
                glow: 'glow-blue',
                watermark: 'fa-robot',
                details: [{
                        label: 'Current Accuracy',
                        value: '96%'
                    },
                    {
                        label: 'Previous Month',
                        value: '91%'
                    },
                    {
                        label: 'Improvement',
                        value: '+5%'
                    },
                    {
                        label: 'Target',
                        value: '> 95%'
                    },
                    {
                        label: 'Total Reports',
                        value: '1,247'
                    }
                ],
                pieData: [{
                        label: 'Accurate',
                        value: 96,
                        color: '#3b82f6'
                    },
                    {
                        label: 'Needs Review',
                        value: 3,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Inaccurate',
                        value: 1,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'System Response',
                value: 0.4,
                unit: 'sec',
                change: '↓ 0.2s',
                changeColor: 'teal',
                progress: 92,
                glow: 'glow-teal',
                watermark: 'fa-server',
                details: [{
                        label: 'Current Avg.',
                        value: '0.4 sec'
                    },
                    {
                        label: 'Previous Month',
                        value: '0.6 sec'
                    },
                    {
                        label: 'Improvement',
                        value: '-33%'
                    },
                    {
                        label: 'Target',
                        value: '< 0.5 sec'
                    },
                    {
                        label: 'Peak Load',
                        value: '1.2 sec'
                    }
                ],
                pieData: [{
                        label: 'Under 0.5s',
                        value: 92,
                        color: '#14b8a6'
                    },
                    {
                        label: '0.5-1.0s',
                        value: 6,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Above 1.0s',
                        value: 2,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'Monthly Active Users',
                value: 1248,
                unit: '',
                change: '↑ 14%',
                changeColor: 'purple',
                progress: 85,
                glow: 'glow-purple',
                watermark: 'fa-users',
                details: [{
                        label: 'Current Users',
                        value: '1,248'
                    },
                    {
                        label: 'Previous Month',
                        value: '1,094'
                    },
                    {
                        label: 'Growth',
                        value: '+14%'
                    },
                    {
                        label: 'Target',
                        value: '> 1,200'
                    },
                    {
                        label: 'New Users',
                        value: '156'
                    }
                ],
                pieData: [{
                        label: 'Active',
                        value: 85,
                        color: '#8b5cf6'
                    },
                    {
                        label: 'Semi-Active',
                        value: 10,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Inactive',
                        value: 5,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'User Satisfaction',
                value: 94,
                unit: '%',
                change: '↑ 3%',
                changeColor: 'amber',
                progress: 94,
                glow: 'glow-amber',
                watermark: 'fa-face-smile',
                details: [{
                        label: 'Current Satisfaction',
                        value: '94%'
                    },
                    {
                        label: 'Previous Month',
                        value: '91%'
                    },
                    {
                        label: 'Improvement',
                        value: '+3%'
                    },
                    {
                        label: 'Target',
                        value: '> 92%'
                    },
                    {
                        label: 'Survey Responses',
                        value: '892'
                    }
                ],
                pieData: [{
                        label: 'Satisfied',
                        value: 94,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Neutral',
                        value: 4,
                        color: '#a1a1aa'
                    },
                    {
                        label: 'Unsatisfied',
                        value: 2,
                        color: '#ef4444'
                    }
                ]
            }
        ];

        const StaffData = [{
                name: 'Juan Dela Cruz',
                score: 94,
                cases: 112,
                response: 4.2
            },
            {
                name: 'Ana Reyes',
                score: 91,
                cases: 98,
                response: 4.8
            },
            {
                name: 'Carlos Tan',
                score: 88,
                cases: 85,
                response: 5.1
            },
            {
                name: 'Elena Santos',
                score: 85,
                cases: 76,
                response: 5.6
            },
            {
                name: 'Roberto Silva',
                score: 82,
                cases: 68,
                response: 6.2
            },
            {
                name: 'Jose Mendoza',
                score: 78,
                cases: 59,
                response: 6.8
            }
        ];

        // =====================================================================
        // AI GLOW CURSOR TRACKING
        // =====================================================================
        const aiPanel = document.getElementById('aiInsightPanel');
        aiPanel.addEventListener('mousemove', (e) => {
            const rect = aiPanel.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            aiPanel.style.setProperty('--mouse-x', `${x}px`);
            aiPanel.style.setProperty('--mouse-y', `${y}px`);
        });

        // =====================================================================
        // TOOLTIP SYSTEM
        // =====================================================================
        const tooltip = document.getElementById('hoverTooltip');
        let tooltipTimeout = null;
        let tooltipHideTimeout = null;
        let isTooltipVisible = false;
        let currentTarget = null;

        function showTooltip(event, title, content, isPieChart = false) {
            if (!tooltip) return;

            if (tooltipHideTimeout) {
                clearTimeout(tooltipHideTimeout);
                tooltipHideTimeout = null;
            }

            const rect = event.currentTarget.getBoundingClientRect();
            currentTarget = event.currentTarget;

            let left = rect.left + rect.width / 2 - 160;
            let top = rect.top - 20;

            const tooltipWidth = 340;
            const tooltipHeight = 340;

            if (left + tooltipWidth > window.innerWidth - 20) left = window.innerWidth - tooltipWidth - 20;
            if (left < 20) left = 20;

            if (top - tooltipHeight < 20) {
                top = rect.bottom + 20;
                const arrow = tooltip.querySelector('.tooltip-arrow');
                if (arrow) arrow.className = 'tooltip-arrow bottom';
            } else {
                const arrow = tooltip.querySelector('.tooltip-arrow');
                if (arrow) arrow.className = 'tooltip-arrow top';
            }

            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';

            const titleEl = document.getElementById('tooltipTitle');
            if (titleEl) titleEl.textContent = title;

            let contentHtml = '';

            if (isPieChart && content.pieData) {
                contentHtml = `
                <div class="mini-chart" id="miniPieChart"></div>
                <div class="tooltip-pie-legend">
                    ${content.pieData.map(d => `<span><span class="dot" style="background: ${d.color};"></span>${d.label} (${d.value}%)</span>`).join('')}
                </div>
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #f4f4f5;">
                    ${content.details.map(d => `<div class="tooltip-row"><span class="label">${d.label}</span><span class="value">${d.value}</span></div>`).join('')}
                </div>
            `;
            } else {
                contentHtml = content.map(d => `<div class="tooltip-row"><span class="label">${d.label}</span><span class="value">${d.value}</span></div>`).join('');
            }

            const contentEl = document.getElementById('tooltipContent');
            if (contentEl) contentEl.innerHTML = contentHtml;

            tooltip.classList.add('active');
            isTooltipVisible = true;

            if (isPieChart && content.pieData) {
                if (window._miniChart) {
                    window._miniChart.destroy();
                    window._miniChart = null;
                }
                setTimeout(() => {
                    renderMiniPieChart(content.pieData);
                }, 50);
            }
        }

        function hideTooltip() {
            if (tooltipHideTimeout) clearTimeout(tooltipHideTimeout);
            tooltipHideTimeout = setTimeout(() => {
                if (tooltip) {
                    tooltip.classList.remove('active');
                    isTooltipVisible = false;
                    currentTarget = null;
                }
                if (window._miniChart) {
                    window._miniChart.destroy();
                    window._miniChart = null;
                }
                tooltipHideTimeout = null;
            }, 200);
        }

        function renderMiniPieChart(data) {
            const container = document.getElementById('miniPieChart');
            if (!container) return;
            if (window._miniChart) {
                window._miniChart.destroy();
                window._miniChart = null;
            }

            window._miniChart = new ApexCharts(container, {
                series: data.map(d => d.value),
                chart: {
                    type: 'donut',
                    height: 120,
                    width: 120,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        speed: 500
                    }
                },
                colors: data.map(d => d.color),
                labels: data.map(d => d.label),
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 2,
                    colors: ['#ffffff']
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%'
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                    style: {
                        fontSize: '10px'
                    }
                }
            });
            window._miniChart.render();
        }

        // =====================================================================
        // RENDER FUNCTIONS
        // =====================================================================
        function renderInsights(liveInsights) {
            const grid = document.getElementById('insightsGrid');
            if (!grid) return;

            const items = (Array.isArray(liveInsights) && liveInsights.length > 0) ? liveInsights : InsightsData;

            const iconMap = {
                'alert': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'users': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>',
                'check': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'ai': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>'
            };

            const bgClassMap = {
                'rose': 'bg-rose-50/70 text-rose-700 border-rose-100',
                'red': 'bg-red-50/70 text-red-700 border-red-100',
                'amber': 'bg-amber-50/70 text-amber-700 border-amber-100',
                'emerald': 'bg-emerald-50/70 text-emerald-700 border-emerald-100',
                'blue': 'bg-blue-50/70 text-blue-700 border-blue-100'
            };

            grid.innerHTML = items.map(function(insight, idx) {
                const colorKey = insight.color || insight.priorityColor || 'blue';
                const wrapperBg = bgClassMap[colorKey] || 'bg-zinc-50 border-zinc-100';
                const badgeLabel = insight.badge || insight.priority || 'Live AI Insight';
                const cardTitle = insight.title || insight.ai_summary || 'AI Processing metrics...';

                return '<div class="insight-card text-left rounded-xl p-5 flex flex-col justify-between h-full border border-zinc-200/80 bg-white/90 hover:shadow-md transition-all cursor-pointer" onclick="openInsightModal(' + JSON.stringify(insight).replace(/"/g, '&quot;') + ')">' +
                    '<div>' +
                    '<div class="flex items-start justify-between">' +
                    '<div class="p-2 ' + wrapperBg + ' rounded-lg w-fit border">' +
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + (iconMap[insight.icon] || iconMap['ai']) + '</svg>' +
                    '</div>' +
                    '<span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border ' + wrapperBg + '">' + badgeLabel + '</span>' +
                    '</div>' +
                    '<p class="text-sm font-semibold text-zinc-800 mt-4 leading-relaxed">' + cardTitle + '</p>' +
                    '</div>' +
                    '<div class="flex items-center justify-between mt-4 pt-3 border-t border-zinc-100">' +
                    '<span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">AI Processed</span>' +
                    '<span class="text-[10px] font-semibold text-purple-600">Click details →</span>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }
        renderInsights(); // Start Insights render

        // =====================================================================
        // PREDICTIVE WITH PIE CHART HOVER
        // =====================================================================
        function renderPredictive() {
            const container = document.getElementById('predictiveCards');
            if (!container) return;
            const iconMap = {
                'alert': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
                'document': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>',
                'health': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 2a1 1 0 000 2h1v2.101a7.002 7.002 0 00-5.998 8.267l-1.06 1.06a1 1 0 001.415 1.415l.96-.96A6.99 6.99 0 0012 18a6.99 6.99 0 004.683-1.117l.96.96a1 1 0 001.415-1.415l-1.06-1.06A7.002 7.002 0 0014 6.101V4h1a1 1 0 100-2H9z"></path>'
            };

            const badgeMap = {
                'indigo': 'bg-indigo-50 border-indigo-100 text-indigo-700',
                'blue': 'bg-blue-50 border-blue-100 text-blue-700',
                'amber': 'bg-amber-50 border-amber-100 text-amber-700'
            };

            container.innerHTML = PredictiveData.map(function(item) {
                var unitHtml = item.unit ? ' <span class="text-xs font-normal text-zinc-400">' + item.unit + '</span>' : '';
                const indicatorColor = parseInt(item.confidence) >= 80 ? 'emerald' : 'amber';
                const badgeBg = badgeMap[item.color] || 'bg-zinc-50 border-zinc-200 text-zinc-700';

                return '<div class="predictive-card rounded-xl border border-zinc-200/80 p-4 transition-all duration-200 cursor-pointer" ' +
                    'onmouseenter="showPredictiveTooltip(event, \'' + item.detail + '\', ' + JSON.stringify(item.rows).replace(/"/g, '&quot;') + ', ' + JSON.stringify(item.pieData).replace(/"/g, '&quot;') + ')" ' +
                    'onmouseleave="hidePredictiveTooltip()">' +
                    '<div class="flex items-center gap-3">' +
                    '<div class="p-2 ' + badgeBg + ' border rounded-lg shrink-0">' +
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + iconMap[item.icon] + '</svg>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                    '<p class="text-xs font-semibold text-zinc-400 truncate uppercase tracking-wider">' + item.title + '</p>' +
                    '<p class="text-xl font-extrabold text-zinc-900 mt-0.5">' + item.value + unitHtml + '</p>' +
                    '</div>' +
                    '<span class="text-xs font-bold text-' + indicatorColor + '-600 shrink-0 bg-' + indicatorColor + '-50/50 px-2 py-0.5 rounded-md border border-' + indicatorColor + '-100">' + item.confidence + '</span>' +
                    '</div>' +
                    '<div class="h-1.5 w-full bg-zinc-100 rounded-full overflow-hidden mt-3">' +
                    '<div class="h-full bg-' + indicatorColor + '-500 rounded-full transition-all duration-1000" style="width:' + item.confidence + '"></div>' +
                    '</div>' +
                    '<p class="text-[10px] font-bold text-zinc-400 mt-2 flex items-center justify-between">' +
                    '<span>' + item.trend + '</span>' +
                    '<span class="text-blue-500 font-semibold uppercase tracking-wider no-print">Hover for details</span>' +
                    '</p>' +
                    '</div>';
            }).join('');
        }
        renderPredictive(); // Start Predictive render

        window.showPredictiveTooltip = function(event, title, rows, pieData) {
            showTooltip(event, title, {
                details: rows,
                pieData
            }, true);
        };
        window.hidePredictiveTooltip = function() {
            hideTooltip();
        };

        function renderModuleLegend() {
            const container = document.getElementById('moduleLegend');
            if (!container) return;
            container.innerHTML = ModuleData.map(function(m) {
                return '<div class="module-item flex items-center justify-between" ' +
                    'data-label="' + m.label + '" data-share="' + m.share + '%" data-trend="' + m.trend + '" data-status="' + m.status + '" data-color="' + m.color + '">' +
                    '<span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full" style="background:' + m.color + '"></span> ' + m.label + '</span>' +
                    '<span class="font-bold text-zinc-800">' + m.share + '%</span>' +
                    '</div>';
            }).join('');
            bindModuleLegendEvents();
        }
        renderModuleLegend(); // Start module legend render

        window.showModuleTooltip = function(event, label, share, trend, status, color) {
            const tooltip = document.getElementById('moduleTooltip');
            if (!tooltip) return;
            const rect = event.currentTarget.getBoundingClientRect();

            const titleEl = document.getElementById('moduleTooltipTitle');
            if (titleEl) titleEl.textContent = label;

            const contentEl = document.getElementById('moduleTooltipContent');
            if (contentEl) {
                contentEl.innerHTML = `
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Share of Activity</span><span style="font-weight:700;color:#18181b;">${share}</span></div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Trend</span><span style="font-weight:700;color:#18181b;">${trend}</span></div>
                <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;"><span style="color:#71717a;">Status</span><span style="font-weight:700;color:#18181b;">${status}</span></div>
                <div style="margin-top:10px;height:5px;background:#f4f4f5;border-radius:9999px;overflow:hidden;"><div style="height:100%;width:${share};background:${color};border-radius:9999px;"></div></div>
            `;
            }

            let left = rect.left + rect.width / 2 - 100;
            let top = rect.bottom + 10;
            if (left + 200 > window.innerWidth - 20) left = window.innerWidth - 220;
            if (left < 20) left = 20;
            if (top + 180 > window.innerHeight - 20) top = rect.top - 180;

            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
            tooltip.classList.add('active');
        };

        window.hideModuleTooltip = function() {
            const tooltip = document.getElementById('moduleTooltip');
            if (tooltip) tooltip.classList.remove('active');
        };

        function renderMetrics() {
            const grid = document.getElementById('metricsGrid');
            if (!grid) return;

            // Map the colors to Tailwind text- classes for the icons
            const iconColorMap = {
                'emerald': 'text-emerald-600 bg-emerald-50 border-emerald-100',
                'blue': 'text-blue-600 bg-blue-50 border-blue-100',
                'teal': 'text-teal-600 bg-teal-50 border-teal-100',
                'purple': 'text-purple-600 bg-purple-50 border-purple-100',
                'amber': 'text-amber-600 bg-amber-50 border-amber-100'
            };

            const ringColorMap = {
                'emerald': '#10b981',
                'blue': '#3b82f6',
                'teal': '#14b8a6',
                'purple': '#8b5cf6',
                'amber': '#f59e0b'
            };

            // Calculating stroke-dashoffset for SVG Ring
            // 100 - progress = offset
            grid.innerHTML = MetricsData.map(function(m) {
                const isPositive = m.change.includes('↑');
                const changeClass = isPositive ? 'positive' : 'negative';
                const changeIcon = isPositive ? '↑' : '↓';
                const iconColors = iconColorMap[m.changeColor] || 'text-zinc-600 bg-zinc-50 border-zinc-200';
                const ringColor = ringColorMap[m.changeColor] || '#3b82f6';
                const ringOffset = 100 - m.progress;

                return '<div class="kpi-card rounded-2xl border border-slate-100 bg-white p-3 cursor-pointer group ' + m.glow + '" ' +
                    'onmouseenter="showMetricTooltip(event, \'' + m.label + '\', ' + JSON.stringify(m.details).replace(/"/g, '&quot;') + ', ' + JSON.stringify(m.pieData).replace(/"/g, '&quot;') + ')" ' +
                    'onmouseleave="hideMetricTooltip()">' +
                    '<div class="kpi-shine no-print"></div>' +
                    '<div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-' + m.changeColor + '-400 to-' + m.changeColor + '-600"></div>' +
                    '<i class="fas ' + m.watermark + ' kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-' + m.changeColor + '-500/10 rotate-[-8deg] pointer-events-none no-print"></i>' +
                    '<div class="relative p-1">' +
                    '<div class="flex items-start justify-between gap-2">' +
                    '<div>' +
                    '<p class="text-[8px] font-bold uppercase tracking-wider text-' + m.changeColor + '-600">' + m.label + '</p>' +
                    '<p class="kpi-value text-xl font-black mt-1 leading-none" data-target="' + m.value + '">0' + (m.unit ? '<span class="text-xs font-semibold text-slate-400"> ' + m.unit + '</span>' : '') + '</p>' +
                    '</div>' +
                    '<svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">' +
                    '<circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>' +
                    '<circle cx="18" cy="18" r="15.5" fill="none" stroke="' + ringColor + '" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:' + ringOffset + '" transform="rotate(-90 18 18)"/>' +
                    '<text x="18" y="20.5" text-anchor="middle" font-size="8" font-weight="700" fill="' + ringColor + '">' + m.progress + '%</text>' +
                    '</svg>' +
                    '</div>' +
                    '<div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">' +
                    '<span class="kpi-change ' + changeClass + '">' + changeIcon + ' ' + m.change.replace(/[↑↓]\s*/, '') + '</span>' +
                    '<span class="text-[8px] text-slate-400">vs last month</span>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }).join('');

            // Animate Counters
            document.querySelectorAll('.kpi-value').forEach(el => {
                const target = parseFloat(el.getAttribute('data-target'));
                const duration = 1500;
                const start = 0;
                const startTime = performance.now();

                function updateNumber(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const current = Math.floor(progress * target);
                    // Preserve the unit span tag during count up
                    const unitHtml = el.innerHTML.includes('<span') ? el.innerHTML.substring(el.innerHTML.indexOf('<span')) : '';
                    el.innerHTML = current.toLocaleString() + unitHtml;
                    if (progress < 1) requestAnimationFrame(updateNumber);
                    else el.innerHTML = target.toLocaleString() + unitHtml;
                }
                requestAnimationFrame(updateNumber);
            });
        }
        renderMetrics(); // Start Metric render

        window.showMetricTooltip = function(event, title, details, pieData) {
            showTooltip(event, title, {
                details,
                pieData
            }, true);
        };
        window.hideMetricTooltip = function() {
            hideTooltip();
        };

        // Staff tooltip
        window.showStaffTooltip = function(event, name, score, cases, response) {
            const tooltip = document.getElementById('staffTooltip') || createStaffTooltip();
            if (!tooltip) return;
            const rect = event.currentTarget.getBoundingClientRect();
            const status = score >= 85 ? '✅ Exceeds expectations' : score >= 80 ? '✅ Meets expectations' : '⚠️ Needs improvement';

            tooltip.innerHTML = `
            <div style="font-weight:700;font-size:13px;color:#18181b;margin-bottom:10px;letter-spacing:-0.01em;">${name}</div>
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Overall Score</span><span style="font-weight:700;color:#18181b;">${score}%</span></div>
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Cases Handled</span><span style="font-weight:700;color:#18181b;">${cases}</span></div>
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Avg. Response Time</span><span style="font-weight:700;color:#18181b;">${response} hrs</span></div>
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;"><span style="color:#71717a;">Status</span><span style="font-weight:700;color:#18181b;">${status}</span></div>
        `;

            let left = rect.left + rect.width / 2 - 125;
            let top = rect.bottom + 10;
            if (left + 250 > window.innerWidth - 20) left = window.innerWidth - 270;
            if (left < 20) left = 20;
            if (top + 200 > window.innerHeight - 20) top = rect.top - 200;

            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
            tooltip.classList.add('active');
        };

        window.hideStaffTooltip = function() {
            const tooltip = document.getElementById('staffTooltip');
            if (tooltip) tooltip.classList.remove('active');
        };

        function createStaffTooltip() {
            const div = document.createElement('div');
            div.id = 'staffTooltip';
            div.className = 'module-tooltip';
            document.body.appendChild(div);
            return div;
        }

        // =====================================================================
        // TREND CHART
        // =====================================================================
        var trendDatasets = {
            disease: {
                subtitle: 'Disease Cases Trend',
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                colors: ['#ef4444', '#f59e0b', '#10b981', '#a855f7'],
                series: [{
                        name: 'Dengue',
                        data: [2, 3, 5, 4, 7, 8]
                    },
                    {
                        name: 'Influenza',
                        data: [1, 2, 3, 2, 4, 5]
                    },
                    {
                        name: 'Food Poisoning',
                        data: [0, 1, 2, 1, 3, 2]
                    },
                    {
                        name: 'Leptospirosis',
                        data: [0, 0, 1, 1, 2, 1]
                    }
                ],
                legend: [{
                        label: 'Dengue',
                        color: 'bg-red-500'
                    },
                    {
                        label: 'Influenza',
                        color: 'bg-amber-500'
                    },
                    {
                        label: 'Food Poisoning',
                        color: 'bg-emerald-500'
                    },
                    {
                        label: 'Leptospirosis',
                        color: 'bg-purple-500'
                    }
                ]
            },
            service: {
                subtitle: 'Service Requests Trend',
                categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                colors: ['#3b82f6', '#10b981', '#f59e0b'],
                series: [{
                        name: 'Patients',
                        data: [4, 6, 8, 7, 9, 12]
                    },
                    {
                        name: 'Vaccination',
                        data: [3, 5, 7, 6, 8, 10]
                    },
                    {
                        name: 'Requests',
                        data: [2, 4, 6, 5, 7, 9]
                    }
                ],
                legend: [{
                        label: 'Patients',
                        color: 'bg-blue-500'
                    },
                    {
                        label: 'Vaccination',
                        color: 'bg-emerald-500'
                    },
                    {
                        label: 'Requests',
                        color: 'bg-amber-500'
                    }
                ]
            },
            combined: {
                subtitle: 'Combined View',
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                colors: ['#ef4444', '#3b82f6', '#10b981'],
                series: [{
                        name: 'Disease Cases',
                        data: [3, 5, 8, 7, 11, 13]
                    },
                    {
                        name: 'Service Requests',
                        data: [9, 15, 21, 18, 24, 31]
                    },
                    {
                        name: 'Permits Issued',
                        data: [4, 6, 8, 7, 9, 12]
                    }
                ],
                legend: [{
                        label: 'Disease Cases',
                        color: 'bg-red-500'
                    },
                    {
                        label: 'Service Requests',
                        color: 'bg-blue-500'
                    },
                    {
                        label: 'Permits Issued',
                        color: 'bg-emerald-500'
                    }
                ]
            }
        };

        var rangeLabels = {
            'today': 'Today',
            '7d': 'Last 7 Days',
            '30d': 'Last 30 Days',
            '90d': 'Last 90 Days',
            '6m': 'Last 6 Months',
            '12m': 'Last 12 Months',
            'custom': 'Custom Range'
        };

        function interpolateSeries(values, targetLen) {
            var result = [];
            for (var i = 0; i < targetLen; i++) {
                var t = targetLen === 1 ? 0 : i / (targetLen - 1) * (values.length - 1);
                var i0 = Math.floor(t),
                    i1 = Math.min(i0 + 1, values.length - 1);
                var frac = t - i0;
                var v = values[i0] * (1 - frac) + values[i1] * frac;
                var noise = Math.round(Math.sin(i * 12.9898 + values[0]) * 4) / 10;
                result.push(Math.max(0, Math.round((v + noise) * 10) / 10));
            }
            return result;
        }

        function rangeLength(rangeKey) {
            if (rangeKey === 'today') return 1;
            if (rangeKey === '7d') return 7;
            if (rangeKey === '30d') return 30;
            if (rangeKey === '90d') return 13;
            if (rangeKey === '12m') return 12;
            if (rangeKey === 'custom') {
                var from = document.getElementById('dateFrom').value;
                var to = document.getElementById('dateTo').value;
                if (from && to) {
                    var days = Math.max(2, Math.round((new Date(to) - new Date(from)) / 86400000) + 1);
                    return Math.min(days, 60);
                }
                return 14;
            }
            return 6;
        }

        function makeCategories(rangeKey, len) {
            if (rangeKey === 'today') return ['Today'];
            if (rangeKey === '7d') return Array.from({
                length: len
            }, function(_, i) {
                var d = new Date();
                d.setDate(d.getDate() - (len - 1 - i));
                return d.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            });
            if (rangeKey === '12m') return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (rangeKey === '90d') return Array.from({
                length: len
            }, function(_, i) {
                return 'Wk ' + (i + 1);
            });
            return Array.from({
                length: len
            }, function(_, i) {
                return 'Day ' + (i + 1);
            });
        }

        function buildTrendSeries(typeKey, rangeKey, yoy) {
            var base = trendDatasets[typeKey] || trendDatasets.disease;
            var len = rangeLength(rangeKey);
            var categories = makeCategories(rangeKey, len);
            var series = base.series.map(function(s) {
                return {
                    name: s.name,
                    data: interpolateSeries(s.data, len)
                };
            });
            var colors = base.colors.slice();
            var legend = base.legend.slice();
            if (yoy && series.length > 0) {
                var primary = series[0];
                var prevYear = primary.data.map(function(v, i) {
                    return Math.max(0, Math.round((v * 0.82 + Math.sin(i * 7.13) * 0.6) * 10) / 10);
                });
                series.push({
                    name: primary.name + ' (YoY)',
                    data: prevYear
                });
                colors.push('#a1a1aa');
                legend.push({
                    label: primary.name + ' (YoY)',
                    color: 'bg-zinc-400'
                });
            }
            return {
                categories: categories,
                series: series,
                colors: colors,
                subtitle: base.subtitle,
                legend: legend,
                yoy: yoy
            };
        }

        var trendOptions = {
            series: [],
            chart: {
                type: 'line',
                height: 224,
                toolbar: {
                    show: false
                },
                background: 'transparent',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3,
                lineCap: 'round'
            },
            grid: {
                borderColor: '#f4f4f5',
                strokeDashArray: 3,
                padding: {
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 10
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: '#a1a1aa',
                        fontSize: '10px',
                        fontWeight: '500'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#a1a1aa',
                        fontSize: '10px',
                        fontWeight: '500'
                    }
                },
                min: 0
            },
            legend: {
                show: false
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontSize: '11px'
                },
                marker: {
                    show: true
                }
            },
            markers: {
                size: 4,
                hover: {
                    size: 6,
                    sizeOffset: 3
                }
            }
        };
        var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
        trendChart.render();

        var predictiveOptions = {
            series: [
                { name: 'Expected Cases', data: [145, 160, 167, 175, 177, 191, 198] },
                { name: 'Permit Requests', data: [310, 338, 353, 372, 388, 419, 435] },
                { name: 'Vaccine Demand', data: [180, 195, 210, 225, 240, 260, 273] }
            ],
            chart: {
                type: 'line',
                height: 180,
                toolbar: { show: false },
                background: 'transparent',
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },
            colors: ['#ef4444', '#3b82f6', '#10b981'],
            stroke: {
                curve: 'smooth',
                width: 3,
                dashArray: [0, 0, 0]
            },
            xaxis: {
                categories: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May (AI Forecast)'],
                labels: { style: { fontSize: '10px', colors: '#a1a1aa' } }
            },
            yaxis: { show: false },
            grid: { borderColor: '#f4f4f5' },
            legend: { show: false },
            tooltip: { theme: 'light', style: { fontSize: '11px' } },
            markers: { size: 3, hover: { size: 5 } }
        };
        var predictiveChart = new ApexCharts(document.querySelector("#predictiveLineChart"), predictiveOptions);
        predictiveChart.render();

        function renderLegend(items) {
            const legendEl = document.getElementById('trendLegend');
            if (!legendEl) return;
            legendEl.innerHTML = items.map(function(item) {
                return '<span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full ' + item.color + '"></span> ' + item.label + '</span>';
            }).join('');
        }

        function updateTrendChart() {
            var typeKey = document.getElementById('trendFilter').value;
            var rangeKey = document.getElementById('dateRangeSelect').value;
            var yoy = document.getElementById('yoyToggle').checked;
            var built = buildTrendSeries(typeKey, rangeKey, yoy);
            document.getElementById('trendSubtitle').textContent = built.subtitle + ' · ' + rangeLabels[rangeKey];
            var dashArray = built.series.map(function(s, i) {
                return (yoy && i === built.series.length - 1) ? 6 : 0;
            });
            trendChart.updateOptions({
                series: built.series,
                colors: built.colors,
                xaxis: {
                    categories: built.categories
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    dashArray: dashArray,
                    lineCap: 'round'
                }
            });
            renderLegend(built.legend);

            var now = new Date();
            if (document.getElementById('headerTimestamp')) document.getElementById('headerTimestamp').textContent = now.toLocaleTimeString();
            if (document.getElementById('footerTimestamp')) document.getElementById('footerTimestamp').textContent = now.toLocaleString();
        }
        updateTrendChart(); // Initialize Trend Chart

        async function fetchLiveAnalytics() {
            var range = document.getElementById('dateRangeSelect').value;
            var filter = document.getElementById('trendFilter').value;
            var yoy = document.getElementById('yoyToggle').checked;

            try {
                var res = await fetch('../api/analytics.php?range=' + range + '&filter=' + filter + '&yoy=' + yoy);
                var data = await res.json();
                if (data && data.success) {
                    // Update Trend Chart
                    if (data.trend) {
                        trendChart.updateOptions({
                            series: data.trend.series,
                            colors: data.trend.colors,
                            xaxis: { categories: data.trend.categories }
                        });
                        renderLegend(data.trend.legend);
                    }
                    // Update Predictive Chart & Badges
                    if (data.predictive && typeof predictiveChart !== 'undefined') {
                        predictiveChart.updateOptions({
                            series: data.predictive.series,
                            xaxis: { categories: data.predictive.categories }
                        });
                        if (data.predictive.series && data.predictive.series.length >= 3) {
                            var casesLast = data.predictive.series[0].data.slice(-1)[0];
                            var permitsLast = data.predictive.series[1].data.slice(-1)[0];
                            var vaccinesLast = data.predictive.series[2].data.slice(-1)[0];
                            if (document.getElementById('pred-cases-val')) document.getElementById('pred-cases-val').textContent = casesLast;
                            if (document.getElementById('pred-permits-val')) document.getElementById('pred-permits-val').textContent = permitsLast;
                            if (document.getElementById('pred-vaccines-val')) document.getElementById('pred-vaccines-val').textContent = vaccinesLast;
                        }
                    }
                    // Update AI Insights Grid
                    if (data.insights) {
                        renderInsights(data.insights);
                    }
                    // Update Operational Modules & Callout Insights
                    if (data.modules) {
                        renderModulesView(data.modules);
                    }
                    if (data.forecast_insight && document.getElementById('forecastInsightText')) {
                        document.getElementById('forecastInsightText').textContent = data.forecast_insight;
                    }
                    if (data.module_insight && document.getElementById('moduleInsightText')) {
                        document.getElementById('moduleInsightText').textContent = data.module_insight;
                    }
                    if (data.correlation_insight && document.getElementById('correlationInsightText')) {
                        document.getElementById('correlationInsightText').textContent = data.correlation_insight;
                    }
                    // Update KPI summary numbers if elements exist
                    if (data.kpis && Array.isArray(data.kpis)) {
                        data.kpis.forEach(function(kpi) {
                            var el = document.getElementById('kpi-' + kpi.key);
                            if (el) el.textContent = kpi.value;
                        });
                    }
                }
            } catch (err) {
                console.log('API Fetch Error:', err);
            }
        }

        var moduleMode = 'current'; // 'current' or 'projected'
        var moduleRawData = [];

        function renderModulesView(data) {
            if (data && Array.isArray(data)) moduleRawData = data;
            var legendEl = document.getElementById('moduleLegend');
            if (!legendEl) return;

            var isProjected = (moduleMode === 'projected');
            
            var btnCurr = document.getElementById('btnModuleCurrent');
            var btnProj = document.getElementById('btnModuleProjected');
            if (btnCurr && btnProj) {
                if (isProjected) {
                    btnCurr.className = "px-2.5 py-0.5 rounded-full text-zinc-500 hover:text-zinc-800 transition-all cursor-pointer font-medium";
                    btnProj.className = "px-2.5 py-0.5 rounded-full bg-indigo-600 text-white shadow-xs transition-all cursor-pointer font-bold";
                } else {
                    btnCurr.className = "px-2.5 py-0.5 rounded-full bg-white text-zinc-800 shadow-xs transition-all cursor-pointer font-bold";
                    btnProj.className = "px-2.5 py-0.5 rounded-full text-zinc-500 hover:text-zinc-800 transition-all cursor-pointer font-medium";
                }
            }

            if (!moduleRawData || moduleRawData.length === 0) return;

            if (typeof modulesChart !== 'undefined') {
                modulesChart.updateOptions({
                    series: moduleRawData.map(function(m) { return isProjected ? m.projected_share : m.share; })
                });
            }

            legendEl.innerHTML = moduleRawData.map(function(m) {
                var val = isProjected ? m.projected_share : m.share;
                var isLow = (m.confidence === 'low');
                var cardBorder = (isProjected && isLow) ? 'border-dashed border-amber-300 bg-amber-50/40 p-1.5 rounded-lg' : '';
                var badgeHtml = (isProjected && isLow) ? '<span title="Limited historical data (<15 logs). Projection is estimated." class="ml-1 text-[9px] px-1.5 py-0.5 bg-amber-100 text-amber-800 border border-amber-200 rounded font-bold">⚠️ Low Data</span>' : '';
                
                return '<div class="flex items-center justify-between text-xs font-semibold ' + cardBorder + '">' +
                    '<span class="flex items-center gap-2"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:' + m.color + '"></span> ' + m.label + badgeHtml + '</span>' +
                    '<div class="flex items-center gap-2">' +
                    '<span class="font-bold text-zinc-800">' + val + '%</span>' +
                    '<span class="text-[10px] text-zinc-400 font-medium">(' + (m.delta || '↑ 2.1pts') + ')</span>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        if (document.getElementById('btnModuleCurrent')) {
            document.getElementById('btnModuleCurrent').addEventListener('click', function() {
                moduleMode = 'current';
                renderModulesView();
            });
        }
        if (document.getElementById('btnModuleProjected')) {
            document.getElementById('btnModuleProjected').addEventListener('click', function() {
                moduleMode = 'projected';
                renderModulesView();
            });
        }

        // Initialize live analytics immediately on page load
        fetchLiveAnalytics();

        document.getElementById('trendFilter').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });
        document.getElementById('yoyToggle').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });
        document.getElementById('dateRangeSelect').addEventListener('change', function(e) {
            document.getElementById('customDateWrap').classList.toggle('hidden', e.target.value !== 'custom');
            document.getElementById('customDateWrap').classList.toggle('flex', e.target.value === 'custom');
            if (e.target.value !== 'custom') { updateTrendChart(); fetchLiveAnalytics(); }
        });
        document.getElementById('dateFrom').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });
        document.getElementById('dateTo').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });

        // =====================================================================
        // OPERATIONAL MODULES PIE CHART
        // =====================================================================
        var modulesOptions = {
            series: ModuleData.map(function(m) {
                return m.share;
            }),
            chart: {
                type: 'pie',
                height: 224,
                toolbar: {
                    show: false
                },
                background: 'transparent',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                events: {
                    dataPointMouseEnter: function(event, chartContext, config) {
                        const m = ModuleData[config.dataPointIndex];
                        if (m) showModuleTooltip(event, m.label, m.share + '%', m.trend, m.status, m.color);
                    },
                    dataPointMouseLeave: function() {
                        hideModuleTooltip();
                    }
                }
            },
            labels: ModuleData.map(function(m) {
                return m.label;
            }),
            colors: ModuleData.map(function(m) {
                return m.color;
            }),
            stroke: {
                width: 3,
                colors: ['#ffffff']
            },
            legend: {
                show: false
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '11px',
                    fontWeight: 'bold'
                },
                dropShadow: {
                    enabled: false
                }
            },
            tooltip: {
                enabled: false
            }
        };
        var modulesChart = new ApexCharts(document.querySelector("#modulesChart"), modulesOptions);
        modulesChart.render();

        function bindModuleLegendEvents() {
            const items = document.querySelectorAll('#moduleLegend .module-item');
            items.forEach(function(item) {
                item.addEventListener('mouseenter', function(event) {
                    showModuleTooltip(event, item.getAttribute('data-label'), item.getAttribute('data-share'), item.getAttribute('data-trend'), item.getAttribute('data-status'), item.getAttribute('data-color'));
                });
                item.addEventListener('mouseleave', function() {
                    hideModuleTooltip();
                });
            });
        }

        // =====================================================================
        // STAFF PERFORMANCE
        // =====================================================================
        var staffData = StaffData.map(function(s) {
            return {
                ...s
            };
        });

        function sortedStaff() {
            var dir = document.getElementById('staffSort').value;
            return staffData.slice().sort(function(a, b) {
                return dir === 'asc' ? a.score - b.score : b.score - a.score;
            });
        }

        function buildStaffOptions(data) {
            return {
                series: [{
                    name: 'Performance',
                    data: data.map(function(d) {
                        return d.score;
                    })
                }],
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: {
                        show: false
                    },
                    background: 'transparent',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    },
                    events: {
                        dataPointMouseEnter: function(event, chartContext, config) {
                            clearTimeout(window.__staffHideTimer);
                            const d = data[config.dataPointIndex];
                            showStaffTooltip(event, d.name, d.score, d.cases, d.response);
                        },
                        dataPointMouseLeave: function() {
                            window.__staffHideTimer = setTimeout(function() {
                                hideStaffTooltip();
                            }, 120);
                        }
                    }
                },
                colors: ['#6366f1'],
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        horizontal: true,
                        barHeight: '50%'
                    }
                },
                grid: {
                    borderColor: '#f4f4f5',
                    strokeDashArray: 3
                },
                xaxis: {
                    categories: data.map(function(d) {
                        return d.name;
                    }),
                    labels: {
                        style: {
                            colors: '#a1a1aa',
                            fontSize: '11px',
                            fontWeight: '500'
                        }
                    },
                    max: 100,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#27272a',
                            fontSize: '11px',
                            fontWeight: '600'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val + '%';
                    },
                    style: {
                        fontSize: '10px',
                        fontWeight: 'bold',
                        colors: ['#4338ca']
                    },
                    offsetX: 20
                },
                tooltip: {
                    enabled: false
                },
                annotations: {
                    xaxis: [{
                        x: 80,
                        borderColor: '#f59e0b',
                        label: {
                            borderColor: '#f59e0b',
                            style: {
                                color: '#fff',
                                background: '#f59e0b',
                                fontSize: '9px',
                                fontWeight: '700'
                            },
                            text: 'Target: 80%'
                        }
                    }]
                }
            };
        }

        var staffChart = new ApexCharts(document.querySelector("#staffChart"), buildStaffOptions(sortedStaff()));
        staffChart.render();

        document.getElementById('staffSort').addEventListener('change', function() {
            staffChart.updateOptions(buildStaffOptions(sortedStaff()), true, true);
        });

        // =====================================================================
        // SERVICE / DISEASE TREND + SERVICE DISTRIBUTION
        // =====================================================================
        const serviceMonths = ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
        var serviceData = {
            appointments: [6, 8, 10, 11, 9, 7],
            emails: [4, 5, 6, 7, 5, 3],
            requests: [3, 4, 5, 6, 4, 2]
        };
        const serviceColors = {
            appointments: '#3b82f6',
            emails: '#10b981',
            requests: '#f59e0b'
        };
        var serviceVisibility = {
            appointments: true,
            emails: true,
            requests: true
        };

        function drawServiceChart() {
            const group = document.getElementById('serviceLineGroup');
            const dots = document.getElementById('serviceDotsGroup');
            if (!group || !dots) return;
            group.innerHTML = '';
            dots.innerHTML = '';

            const width = 500,
                height = 200;
            const margin = {
                top: 20,
                bottom: 30,
                left: 40,
                right: 20
            };
            const chartWidth = width - margin.left - margin.right;
            const chartHeight = height - margin.top - margin.bottom;
            const maxVal = 12;

            function getX(idx) {
                return margin.left + (idx / (serviceMonths.length - 1)) * chartWidth;
            }

            function getY(val) {
                return margin.top + chartHeight - (val / maxVal) * chartHeight;
            }

            Object.keys(serviceData).forEach(series => {
                if (!serviceVisibility[series]) return;
                const values = serviceData[series];
                const color = serviceColors[series];
                let pathD = '';
                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    pathD += (idx === 0 ? `M ${x} ${y}` : ` L ${x} ${y}`);
                });
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathD);
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-linejoin', 'round');
                group.appendChild(path);

                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', x);
                    circle.setAttribute('cy', y);
                    circle.setAttribute('r', '4');
                    circle.setAttribute('fill', color);
                    circle.setAttribute('stroke', '#fff');
                    circle.setAttribute('stroke-width', '1');
                    dots.appendChild(circle);
                });
            });
        }
        window.drawServiceChart = drawServiceChart;
        drawServiceChart();

        document.querySelectorAll('.service-legend').forEach(item => {
            item.addEventListener('click', function() {
                const series = this.dataset.series;
                serviceVisibility[series] = !serviceVisibility[series];
                const dot = this.querySelector('.inline-block');
                dot.style.opacity = serviceVisibility[series] ? '1' : '0.3';
                drawServiceChart();
            });
        });

        const diseaseMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        var diseaseData = {
            dengue: [2, 4, 3, 5, 7, 4],
            influenza: [5, 7, 6, 8, 6, 3],
            foodPoisoning: [1, 2, 4, 3, 5, 2],
            leptospirosis: [0, 1, 2, 1, 3, 1]
        };
        const diseaseColors = {
            dengue: '#ef4444',
            influenza: '#eab308',
            foodPoisoning: '#22c55e',
            leptospirosis: '#a855f7'
        };
        var diseaseVisibility = {
            dengue: true,
            influenza: true,
            foodPoisoning: true,
            leptospirosis: true
        };

        function drawDiseaseChart() {
            const group = document.getElementById('diseaseLineGroup');
            const dots = document.getElementById('diseaseDotsGroup');
            if (!group || !dots) return;
            group.innerHTML = '';
            dots.innerHTML = '';

            const width = 500,
                height = 200;
            const margin = {
                top: 20,
                bottom: 30,
                left: 40,
                right: 20
            };
            const chartWidth = width - margin.left - margin.right;
            const chartHeight = height - margin.top - margin.bottom;
            const maxVal = 10;

            function getX(idx) {
                return margin.left + (idx / (diseaseMonths.length - 1)) * chartWidth;
            }

            function getY(val) {
                return margin.top + chartHeight - (val / maxVal) * chartHeight;
            }

            Object.keys(diseaseData).forEach(series => {
                if (!diseaseVisibility[series]) return;
                const values = diseaseData[series];
                const color = diseaseColors[series];
                let pathD = '';
                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    pathD += (idx === 0 ? `M ${x} ${y}` : ` L ${x} ${y}`);
                });
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathD);
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-linejoin', 'round');
                group.appendChild(path);

                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', x);
                    circle.setAttribute('cy', y);
                    circle.setAttribute('r', '4');
                    circle.setAttribute('fill', color);
                    circle.setAttribute('stroke', '#fff');
                    circle.setAttribute('stroke-width', '1');
                    dots.appendChild(circle);
                });
            });
        }
        window.drawDiseaseChart = drawDiseaseChart;
        drawDiseaseChart();

        document.querySelectorAll('.disease-legend').forEach(item => {
            item.addEventListener('click', function() {
                const series = this.dataset.series;
                diseaseVisibility[series] = !diseaseVisibility[series];
                const dot = this.querySelector('.inline-block');
                dot.style.opacity = diseaseVisibility[series] ? '1' : '0.3';
                drawDiseaseChart();
            });
        });

        var donutData = [{
                label: 'Health Center',
                percentage: 35.7,
                color: '#3b82f6'
            },
            {
                label: 'Sanitation',
                percentage: 42.9,
                color: '#10b981'
            },
            {
                label: 'Immunization',
                percentage: 21.4,
                color: '#f59e0b'
            },
            {
                label: 'Wastewater',
                percentage: 0,
                color: '#8b5cf6'
            }
        ];

        function drawDonut() {
            const container = document.getElementById('donutSegments');
            if (!container) return;
            container.innerHTML = '';

            const radius = 80;
            const circumference = 2 * Math.PI * radius;
            let cumulativeOffset = 0;

            donutData.forEach(seg => {
                const percent = seg.percentage;
                if (percent === 0) return;

                const dashLength = (percent / 100) * circumference;
                const dashArray = dashLength + ' ' + (circumference - dashLength);
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', '100');
                circle.setAttribute('cy', '100');
                circle.setAttribute('r', radius);
                circle.setAttribute('fill', 'none');
                circle.setAttribute('stroke', seg.color);
                circle.setAttribute('stroke-width', '30');
                circle.setAttribute('stroke-dasharray', dashArray);
                circle.setAttribute('stroke-dashoffset', -cumulativeOffset);
                circle.setAttribute('stroke-linecap', 'round');
                circle.style.cursor = 'pointer';

                circle.dataset.label = seg.label;
                circle.dataset.percentage = seg.percentage;

                const startAngleFrom3 = (-cumulativeOffset / circumference) * 360;
                const midAngleFrom3 = startAngleFrom3 + (dashLength / 2 / circumference) * 360;
                let midAngleFromTop = midAngleFrom3 + 90;
                midAngleFromTop = ((midAngleFromTop % 360) + 360) % 360;
                circle.dataset.midpointAngle = midAngleFromTop;

                container.appendChild(circle);
                cumulativeOffset += dashLength;
            });
        }
        window.drawDonut = drawDonut;
        drawDonut();
    });
</script>
<?php include '../includes/footer.php'; ?>