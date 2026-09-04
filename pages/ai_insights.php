<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<!-- ADD FONT AWESOME CDN (If not already in header.php) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<!-- ADD APEXCHARTS CDN -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<!-- ADD SUPABASE JS CDN FOR REALTIME WEBSOCKET PUSH -->
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>

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
            <div class="flex flex-wrap items-center gap-3 bg-white/80 backdrop-blur-md px-4 py-2.5 rounded-xl border border-zinc-200 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] no-print">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">Data Freshness</span>
                <span class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded-lg text-xs font-bold" title="WebSocket Push active from Supabase">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <span>Supabase Realtime</span>
                </span>

                <span class="w-px h-4 bg-zinc-200"></span>

                <!-- Date Range Selector -->
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <select id="dateRangeSelect" class="text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 text-zinc-700 border border-zinc-200 rounded-lg px-2.5 py-1 transition-all focus:outline-none focus:ring-2 focus:ring-zinc-100 cursor-pointer">
                        <option value="today">Today</option>
                        <option value="7d">Last 7 Days</option>
                        <option value="30d">Last 30 Days</option>
                        <option value="90d">Last 90 Days</option>
                        <option value="6m" selected>Last 6 Months</option>
                        <option value="12m">Last 12 Months</option>
                        <option value="custom">Custom Range</option>
                    </select>
                    <div id="customDateWrap" class="hidden items-center gap-1.5">
                        <input type="date" id="dateFrom" class="text-xs bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-2 py-1 focus:outline-none">
                        <span class="text-zinc-300 font-bold">–</span>
                        <input type="date" id="dateTo" class="text-xs bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-2 py-1 focus:outline-none">
                    </div>
                </div>

                <span class="w-px h-4 bg-zinc-200"></span>

                <!-- YoY Toggle -->
                <label class="flex items-center gap-2 text-xs font-semibold text-zinc-600 cursor-pointer select-none">
                    <span class="relative inline-flex items-center">
                        <input type="checkbox" id="yoyToggle" class="sr-only peer">
                        <span class="w-8 h-4 bg-zinc-200 peer-checked:bg-zinc-800 rounded-full transition-colors duration-200"></span>
                        <span class="absolute left-0.5 top-0.5 w-3 h-3 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-4"></span>
                    </span>
                    Compare YoY
                </label>

                <span class="w-px h-4 bg-zinc-200"></span>

                <!-- Export Button -->
                <button onclick="window.print()" class="text-xs font-bold text-zinc-700 hover:text-zinc-900 transition flex items-center gap-1 cursor-pointer" title="Export PDF Report">
                    <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"></path></svg>
                    Export
                </button>
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
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4" id="insightsGrid">
                <!-- Skeletal Loading State (Replaced immediately on live fetch) -->
                <div class="rounded-xl p-5 border border-zinc-200/80 bg-white/70 animate-pulse flex flex-col justify-between h-40">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-8 h-8 rounded-lg bg-zinc-200"></div>
                            <div class="w-20 h-4 rounded-full bg-zinc-200"></div>
                        </div>
                        <div class="w-full h-3.5 bg-zinc-200 rounded mb-2"></div>
                        <div class="w-3/4 h-3.5 bg-zinc-200 rounded"></div>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                    </div>
                </div>
                <div class="rounded-xl p-5 border border-zinc-200/80 bg-white/70 animate-pulse flex flex-col justify-between h-40">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-8 h-8 rounded-lg bg-zinc-200"></div>
                            <div class="w-20 h-4 rounded-full bg-zinc-200"></div>
                        </div>
                        <div class="w-full h-3.5 bg-zinc-200 rounded mb-2"></div>
                        <div class="w-3/4 h-3.5 bg-zinc-200 rounded"></div>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                    </div>
                </div>
                <div class="rounded-xl p-5 border border-zinc-200/80 bg-white/70 animate-pulse flex flex-col justify-between h-40">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-8 h-8 rounded-lg bg-zinc-200"></div>
                            <div class="w-20 h-4 rounded-full bg-zinc-200"></div>
                        </div>
                        <div class="w-full h-3.5 bg-zinc-200 rounded mb-2"></div>
                        <div class="w-3/4 h-3.5 bg-zinc-200 rounded"></div>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                    </div>
                </div>
                <div class="rounded-xl p-5 border border-zinc-200/80 bg-white/70 animate-pulse flex flex-col justify-between h-40">
                    <div>
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-8 h-8 rounded-lg bg-zinc-200"></div>
                            <div class="w-20 h-4 rounded-full bg-zinc-200"></div>
                        </div>
                        <div class="w-full h-3.5 bg-zinc-200 rounded mb-2"></div>
                        <div class="w-3/4 h-3.5 bg-zinc-200 rounded"></div>
                    </div>
                    <div class="flex items-center justify-between pt-3 border-t border-zinc-100">
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-16 h-3 bg-zinc-200 rounded"></div>
                    </div>
                </div>
            </div>
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
                        <span class="text-[10px] font-bold px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full border border-blue-100">Historical Record</span>
                    </div>
                    <p id="trendSubtitle" class="text-xs font-semibold text-zinc-400 mt-1 mb-2">City-Wide Multi-Module Historical Records</p>
                </div>
                <div class="relative min-h-[224px] mt-2">
                    <!-- Trend Skeletal Loader -->
                    <div id="trendSkeleton" class="absolute inset-0 flex flex-col justify-between p-4 bg-zinc-50/70 rounded-xl border border-dashed border-zinc-200 animate-pulse">
                        <div class="flex items-center justify-between">
                            <div class="w-24 h-4 bg-zinc-200 rounded"></div>
                            <div class="w-16 h-4 bg-zinc-200 rounded"></div>
                        </div>
                        <div class="space-y-2 py-4">
                            <div class="w-full h-8 bg-zinc-200/60 rounded"></div>
                            <div class="w-5/6 h-10 bg-zinc-200/80 rounded"></div>
                            <div class="w-4/6 h-12 bg-zinc-200/50 rounded"></div>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-zinc-200/60">
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                        </div>
                    </div>
                    <!-- Trend Empty State -->
                    <div id="trendEmptyState" class="hidden absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-zinc-50/80 rounded-xl border border-dashed border-zinc-200 z-10">
                        <div class="w-10 h-10 rounded-full bg-zinc-100 flex items-center justify-center text-zinc-400 mb-2">
                            <i class="fa-solid fa-chart-line text-lg"></i>
                        </div>
                        <p class="text-xs font-bold text-zinc-700">No Historical Records Found</p>
                        <p class="text-[11px] text-zinc-400 mt-0.5">No logged data points match the selected date window in Supabase.</p>
                    </div>
                    <!-- Trend ApexChart -->
                    <div id="trendChart" class="h-56"></div>
                </div>
                <div id="trendLegend" class="mt-4 flex flex-wrap items-center gap-3 text-xs font-medium text-zinc-500"></div>
                <!-- 1-Line Correlation Insight Callout -->
                <div id="correlationInsightCallout" class="mt-3 pt-2.5 border-t border-zinc-100 text-[11px] font-semibold text-rose-800 bg-rose-50/60 px-3 py-1.5 rounded-lg border border-rose-100/80 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span id="correlationInsightText">Calculating correlation models from live Supabase data...</span>
                </div>
            </div>

            <!-- Predictive Analytics -->
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
                    <p id="predictiveSubtitle" class="text-xs font-semibold text-zinc-400 mt-1 mb-2">6-Month Forward Horizon · Ordinary Least Squares Statistical Projection</p>
                </div>
                
                <!-- ApexCharts Line Graph Container with Skeleton -->
                <div class="relative min-h-[224px] mt-2">
                    <div id="predictiveSkeleton" class="absolute inset-0 flex flex-col justify-between p-4 bg-zinc-50/70 rounded-xl border border-dashed border-zinc-200 animate-pulse">
                        <div class="flex items-center justify-between">
                            <div class="w-24 h-4 bg-zinc-200 rounded"></div>
                            <div class="w-16 h-4 bg-zinc-200 rounded"></div>
                        </div>
                        <div class="space-y-2 py-4">
                            <div class="w-full h-8 bg-zinc-200/60 rounded"></div>
                            <div class="w-5/6 h-10 bg-zinc-200/80 rounded"></div>
                            <div class="w-4/6 h-12 bg-zinc-200/50 rounded"></div>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-zinc-200/60">
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                            <div class="w-8 h-3 bg-zinc-200 rounded"></div>
                        </div>
                    </div>
                    <div id="predictiveEmptyState" class="hidden absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-zinc-50/80 rounded-xl border border-dashed border-zinc-200 z-10">
                        <div class="w-10 h-10 rounded-full bg-zinc-100 flex items-center justify-center text-zinc-400 mb-2">
                            <i class="fa-solid fa-chart-line text-lg"></i>
                        </div>
                        <p class="text-xs font-bold text-zinc-700">No Forecast Baseline</p>
                        <p class="text-[11px] text-zinc-400 mt-0.5">Add records to generate regression models.</p>
                    </div>
                    <div id="predictiveLineChart" class="h-56"></div>
                </div>

                <!-- Dynamic Department-Scoped Forecast Legend -->
                <div id="predictiveLegend" class="mt-4 flex flex-wrap items-center gap-3 text-xs font-medium text-zinc-500"></div>

                <!-- 1-Line Forecast Insight Callout -->
                <div id="forecastInsightCallout" class="mt-3 pt-2.5 border-t border-zinc-100 text-[11px] font-semibold text-emerald-800 bg-emerald-50/60 px-3 py-1.5 rounded-lg border border-emerald-100/80 flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span id="forecastInsightText">Generating regression trajectory from live records...</span>
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
                    <span id="moduleInsightText">Aggregating departmental activity records from Supabase...</span>
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
            <div class="metrics-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4" id="metricsGrid">
                <!-- Skeletal Loading State for Performance Metrics -->
                <div class="rounded-xl border border-zinc-200/80 p-4 bg-white/70 animate-pulse">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-20 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-5 h-5 rounded-md bg-zinc-200"></div>
                    </div>
                    <div class="w-16 h-6 bg-zinc-200 rounded mb-2"></div>
                    <div class="w-24 h-3 bg-zinc-200 rounded"></div>
                </div>
                <div class="rounded-xl border border-zinc-200/80 p-4 bg-white/70 animate-pulse">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-20 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-5 h-5 rounded-md bg-zinc-200"></div>
                    </div>
                    <div class="w-16 h-6 bg-zinc-200 rounded mb-2"></div>
                    <div class="w-24 h-3 bg-zinc-200 rounded"></div>
                </div>
                <div class="rounded-xl border border-zinc-200/80 p-4 bg-white/70 animate-pulse">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-20 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-5 h-5 rounded-md bg-zinc-200"></div>
                    </div>
                    <div class="w-16 h-6 bg-zinc-200 rounded mb-2"></div>
                    <div class="w-24 h-3 bg-zinc-200 rounded"></div>
                </div>
                <div class="rounded-xl border border-zinc-200/80 p-4 bg-white/70 animate-pulse">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-20 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-5 h-5 rounded-md bg-zinc-200"></div>
                    </div>
                    <div class="w-16 h-6 bg-zinc-200 rounded mb-2"></div>
                    <div class="w-24 h-3 bg-zinc-200 rounded"></div>
                </div>
                <div class="rounded-xl border border-zinc-200/80 p-4 bg-white/70 animate-pulse">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-20 h-3 bg-zinc-200 rounded"></div>
                        <div class="w-5 h-5 rounded-md bg-zinc-200"></div>
                    </div>
                    <div class="w-16 h-6 bg-zinc-200 rounded mb-2"></div>
                    <div class="w-24 h-3 bg-zinc-200 rounded"></div>
                </div>
            </div>
        </div>



        <?php
        $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? '');
        $userRole     = trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? '');
        $permService  = \App\Services\PermissionService::getInstance();
        $canViewStaffPerformance = $permService->isHeadOrAdminRole($userRoleDesc) || $permService->isHeadOrAdminRole($userRole);
        ?>

        <?php if ($canViewStaffPerformance): ?>
        <!-- Staff Performance (Department Heads & Admins Only) -->
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white/80 backdrop-blur-md p-6 shadow-[0_4px_25px_-5px_rgba(0,0,0,0.02)] hover-lift fade-in delay-4">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2.5">
                    <div class="p-1.5 bg-indigo-50 rounded-lg">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-zinc-500">Staff Performance</h2>
                    <span id="staffCountBadge" class="text-[10px] font-bold px-2.5 py-0.5 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">Leadership View</span>
                </div>
                <div class="flex items-center gap-2 no-print">
                    <select id="staffSort" class="text-xs font-semibold bg-zinc-50 text-zinc-700 border border-zinc-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-zinc-100 cursor-pointer">
                        <option value="desc" selected>Highest First</option>
                        <option value="asc">Lowest First</option>
                    </select>
                </div>
            </div>
            <p class="text-xs font-semibold text-zinc-400 mt-1 mb-3">Overall performance score by staff member · scrollable list · hover for detail</p>
            <div class="max-h-[380px] overflow-y-auto pr-2 overflow-x-hidden rounded-xl border border-zinc-100/80 bg-zinc-50/40 p-2" style="scrollbar-width: thin; scrollbar-color: #cbd5e1 #f1f5f9;">
                <div id="staffChart"></div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-12 pt-6 border-t border-zinc-200 flex items-center justify-between flex-wrap gap-4 text-[10px] text-zinc-400 font-semibold tracking-wide">
            <div class="flex items-center gap-4 flex-wrap">
                <span>Health & Sanitation Management</span>
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
window.SUPABASE_CONFIG = {
    url: <?= json_encode(Env::get('SUPABASE_URL')) ?>,
    anonKey: <?= json_encode(Env::get('SUPABASE_KEY')) ?>
};
</script>
<script src="<?= site_url('assets/js/ai-insights-app.js') ?>"></script>
<!-- AI Insight Detail Modal -->
<div id="insightDetailModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-zinc-100 transform transition-all scale-100">
        <div class="flex items-start justify-between border-b border-zinc-100 pb-4 mb-4">
            <div class="flex items-center gap-2">
                <div id="modalCategoryBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100">AI Insight</div>
            </div>
            <button onclick="closeInsightModal()" class="text-zinc-400 hover:text-zinc-600 p-1 rounded-lg">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <h3 id="modalInsightTitle" class="text-lg font-extrabold text-zinc-900 leading-snug mb-3">Insight Title</h3>
        <div class="bg-zinc-50 border border-zinc-100 rounded-xl p-4 mb-4">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Recommended Operational Action</p>
            <p id="modalInsightAction" class="text-sm font-medium text-zinc-800">Operational recommendation text...</p>
        </div>
        <div class="grid grid-cols-3 gap-2 mb-6" id="modalInsightMetrics">
            <!-- Dynamic Metrics -->
        </div>
        <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-100">
            <button onclick="closeInsightModal()" class="px-4 py-2 text-xs font-semibold text-zinc-600 hover:bg-zinc-100 rounded-xl transition-all">Close</button>
            <button id="modalGoToModuleBtn" onclick="navigateToInsightModule()" class="px-4 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-md transition-all">Go to Module Page →</button>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>