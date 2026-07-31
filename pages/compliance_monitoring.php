<?php 
/* ============================================================
   REPORT GENERATOR FUNCTION (clean Excel-friendly CSV)
   ============================================================ */
function generateAuditReport($from, $to) {
    global $violations, $actions, $permits, $stats, $violationTimestamps;

    // Filter violations by date range
    $filteredViolations = array_filter($violations, function($v) use ($from, $to) {
        $vDate = date('Y-m-d', strtotime($v['timestamp']));
        return $vDate >= $from && $vDate <= $to;
    });

    // Filter actions by their violation date
    $filteredActions = array_filter($actions, function($a) use ($from, $to, $violationTimestamps) {
        $vDate = isset($violationTimestamps[$a['violation_id']]) 
            ? date('Y-m-d', strtotime($violationTimestamps[$a['violation_id']])) 
            : '1970-01-01';
        return $vDate >= $from && $vDate <= $to;
    });

    // Build CSV content - flat table structure (Excel-friendly)
    $csv = [];
    
    // Header row
    $csv[] = ['Section', 'ID', 'Title', 'Date', 'Status', 'Detail'];

    // ---- Violations ----
    foreach ($filteredViolations as $v) {
        $csv[] = [
            'Violation',
            $v['id'],
            $v['location'],
            date('Y-m-d H:i', strtotime($v['timestamp'])),
            $v['severity'],
            $v['description']
        ];
    }

    // ---- Corrective Actions ----
    foreach ($filteredActions as $a) {
        $csv[] = [
            'Action',
            $a['id'],
            $a['assigned_to'],
            date('Y-m-d H:i', strtotime($a['due_date'])),
            $a['status'],
            'Violation #' . $a['violation_id']
        ];
    }

    // ---- Permits ----
    foreach ($permits as $p) {
        $csv[] = [
            'Permit',
            '',
            $p['name'],
            $p['expiry'],
            $p['status'],
            ''
        ];
    }

    // ---- Summary Stats ----
    $summaryItems = [
        ['Compliance Score', $stats['compliance_score'] . '%'],
        ['Open Violations', $stats['open_violations']],
        ['Overdue Actions', $stats['overdue_actions']],
        ['Pending Inspections', $stats['pending_inspections']]
    ];
    foreach ($summaryItems as $item) {
        $csv[] = [
            'Summary',
            '',
            $item[0],
            '',
            '',
            $item[1]
        ];
    }

    // Output CSV with UTF-8 BOM (Excel-friendly)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_report_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    // Add UTF-8 BOM for proper character display in Excel
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    foreach ($csv as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

/* ============================================================
   DUMMY DATA (simulates database records)
   ============================================================ */
 $stats = [
    'compliance_score' => 94,
    'open_violations'  => 3,
    'overdue_actions'  => 1,
    'pending_inspections' => 2
];

 $violations = [
    [
        'id'          => 1,
        'location'    => 'Kitchen Area A',
        'description' => 'Fridge temp above 40°F for > 15 min',
        'severity'    => 'critical',
        'status'      => 'open',
        'timestamp'   => '2026-07-18 09:30 AM'
    ],
    [
        'id'          => 2,
        'location'    => 'Pool Deck',
        'description' => 'Chlorine level below 1.0 ppm',
        'severity'    => 'critical',
        'status'      => 'in_progress',
        'timestamp'   => '2026-07-17 04:15 PM'
    ],
    [
        'id'          => 3,
        'location'    => 'Dishwashing Station',
        'description' => 'Sanitizer concentration too low (50 ppm)',
        'severity'    => 'non-critical',
        'status'      => 'open',
        'timestamp'   => '2026-07-18 08:00 AM'
    ],
    [
        'id'          => 4,
        'location'    => 'Dry Storage',
        'description' => 'Pest droppings found near flour bins',
        'severity'    => 'critical',
        'status'      => 'resolved',
        'timestamp'   => '2026-07-16 11:20 AM'
    ],
];

 $actions = [
    [
        'id'           => 101,
        'violation_id' => 1,
        'assigned_to'  => 'John Doe (Maintenance)',
        'due_date'     => '2026-07-18 04:00 PM',
        'status'       => 'in_progress'
    ],
    [
        'id'           => 102,
        'violation_id' => 2,
        'assigned_to'  => 'Sarah Lee (Pool Ops)',
        'due_date'     => '2026-07-17 06:00 PM',
        'status'       => 'overdue'
    ],
    [
        'id'           => 103,
        'violation_id' => 3,
        'assigned_to'  => 'Mike Chen (Kitchen)',
        'due_date'     => '2026-07-18 12:00 PM',
        'status'       => 'open'
    ],
];

/* Build lookup for violation dates (used for action filtering) */
$violationTimestamps = [];
foreach ($violations as $v) {
    $violationTimestamps[$v['id']] = $v['timestamp'];
}

 $permits = [
    ['name' => 'Food Service License', 'expiry' => '2026-12-31', 'status' => 'active'],
    ['name' => 'Pool Sanitation Permit', 'expiry' => '2026-08-15', 'status' => 'expiring_soon'],
    ['name' => 'Waste Disposal Certificate', 'expiry' => '2025-11-01', 'status' => 'expired'],
];

/* Helper to get action status badge color (Semantic Colors) */
function getActionBadge($status) {
    return match ($status) {
        'open'        => 'bg-blue-100 text-blue-800',
        'in_progress' => 'bg-yellow-100 text-yellow-800',
        'overdue'     => 'bg-red-100 text-red-800',
        'resolved'    => 'bg-green-100 text-green-800',
        default       => 'bg-gray-100 text-gray-800',
    };
}

/* ============================================================
   HANDLE REPORT GENERATION REQUEST
   ============================================================ */
if (isset($_GET['generate_report']) && isset($_GET['from']) && isset($_GET['to'])) {
    $from = $_GET['from'];
    $to = $_GET['to'];
    if ($from && $to) {
        generateAuditReport($from, $to);
    }
}

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<!-- ADD FONT AWESOME CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

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

    .report-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(180, 212, 255, 0.3);
        box-shadow: 0 10px 40px -10px rgba(23, 107, 135, 0.15);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
    }
    
    .report-card:hover {
        box-shadow: 0 15px 40px -10px rgba(23, 107, 135, 0.15);
    }

    .tab-content {
        animation: fadeInSlide 0.5s ease-in-out;
    }
    @keyframes fadeInSlide {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .table-row-hover { transition: background-color 0.2s ease; }
    .table-row-hover:hover { background-color: rgba(180, 212, 255, 0.1); }

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

    .toast-show {
        transform: translateY(0) !important;
        opacity: 1 !important;
    }

    select, input[type="date"], input[type="time"], input[type="text"], input[type="datetime-local"] {
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

    .glow-emerald { border-color: rgba(16, 185, 129, 0.1); }
    .glow-emerald:hover { border-color: rgba(16, 185, 129, 0.4); box-shadow: 0 15px 40px -10px rgba(16, 185, 129, 0.15); }
    .glow-red { border-color: rgba(239, 68, 68, 0.1); }
    .glow-red:hover { border-color: rgba(239, 68, 68, 0.4); box-shadow: 0 15px 40px -10px rgba(239, 68, 68, 0.15); }
    .glow-amber { border-color: rgba(245, 158, 11, 0.1); }
    .glow-amber:hover { border-color: rgba(245, 158, 11, 0.4); box-shadow: 0 15px 40px -10px rgba(245, 158, 11, 0.15); }
    .glow-blue { border-color: rgba(59, 130, 246, 0.1); }
    .glow-blue:hover { border-color: rgba(59, 130, 246, 0.4); box-shadow: 0 15px 40px -10px rgba(59, 130, 246, 0.15); }
</style>

<main class="flex-1 m-5 overflow-hidden rounded-2xl font-sans scrollbar-track-transparent">

    <!-- ROW 1: KPI STATS CARDS -->
    <div class="kpi-grid grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        
        <!-- KPI 1: Compliance Score -->
        <a href="#" class="kpi-card glow-emerald relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-shield-check kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-emerald-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-emerald-400 to-emerald-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-emerald-600">Compliance Score</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none">94%</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">Excellent</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:6" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#10b981">94%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-arrow-up text-[5px] mr-0.5"></i> High
                    </span>
                    <span class="text-[7px] text-slate-400">Audit ready</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,12 10,11 20,9 30,8 40,5 50,4 60,2" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- KPI 2: Open Violations -->
        <a href="#" class="kpi-card glow-red relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-rose-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-triangle-exclamation kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-rose-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-rose-400 to-rose-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-rose-600">Open Violations</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none">3</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">Requires attention</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:75" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#ef4444">25%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-exclamation text-[5px] mr-0.5"></i> Critical
                    </span>
                    <span class="text-[7px] text-slate-400">2 unresolved</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,6 10,9 20,7 30,12 40,10 50,15 60,17" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- KPI 3: Overdue Actions -->
        <a href="#" class="kpi-card glow-amber relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-clock kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-amber-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-amber-400 to-amber-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-amber-600">Overdue Actions</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none">1</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">Past due date</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:90" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#f59e0b">10%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-clock text-[5px] mr-0.5"></i> Delayed
                    </span>
                    <span class="text-[7px] text-slate-400">Needs follow-up</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,14 10,13 20,15 30,10 40,12 50,8 60,9" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>

        <!-- KPI 4: Pending Inspections -->
        <a href="#" class="kpi-card glow-blue relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
            <div class="kpi-shine"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-transparent to-transparent pointer-events-none"></div>
            <i class="fas fa-clipboard-check kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-blue-500/10 rotate-[-8deg] pointer-events-none"></i>
            <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-blue-400 to-blue-600"></div>
            <div class="relative p-4">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-[8px] font-bold uppercase tracking-wider text-blue-600">Pending Inspections</p>
                        <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none">2</p>
                        <p class="text-[8px] font-medium text-slate-400 mt-0.5">Scheduled soon</p>
                    </div>
                    <svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#3b82f6" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:50" transform="rotate(-90 18 18)"/>
                        <text x="18" y="20.5" text-anchor="middle" font-size="8.5" font-weight="700" fill="#3b82f6">50%</text>
                    </svg>
                </div>
                <div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">
                    <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[7px] font-bold">
                        <i class="fas fa-calendar text-[5px] mr-0.5"></i> Scheduled
                    </span>
                    <span class="text-[7px] text-slate-400">This week</span>
                    <svg viewBox="0 0 60 20" class="w-8 h-3 opacity-70">
                        <polyline points="0,16 10,14 20,15 30,10 40,11 50,4 60,3" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </a>

    </div>

    <!-- ============================================================
         ROW 2: VIOLATION TRACKING
         ============================================================ -->
    <div class="report-card rounded-xl p-4 mb-6">
        <div class="flex flex-wrap items-center justify-between mb-3 gap-2">
            <h2 class="font-semibold flex items-center gap-2 text-[#0d4f64]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#176B87]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Violations & Corrective Actions
            </h2>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 text-xs">
                    <label for="dateFrom" class="text-[#0d4f64] font-medium">From</label>
                    <input type="date" id="dateFrom" class="border rounded-md px-2 py-1 text-sm bg-white focus:ring-2 focus:ring-transparent transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] outline-none">
                    <label for="dateTo" class="text-[#0d4f64] font-medium ml-1">To</label>
                    <input type="date" id="dateTo" value="2026-07-31" class="border rounded-md px-2 py-1 text-sm bg-white focus:ring-2 focus:ring-transparent transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] outline-none">
                </div>
                <input type="text" id="tableSearch" placeholder="Search location..." class="text-sm border rounded-md px-3 py-1 bg-white focus:ring-2 focus:ring-transparent transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] outline-none">
                <select id="severityFilter" class="text-sm border rounded-md px-2 py-1 bg-white focus:ring-2 focus:ring-transparent transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] outline-none">
                    <option value="all">All</option>
                    <option value="critical">Critical</option>
                    <option value="non-critical">Non‑Critical</option>
                </select>
                <button id="exportCsvBtn" class="text-sm font-medium border px-3 py-1 rounded-md transition-all duration-200 hover:bg-[#176B87] hover:text-white hover:border-[#176B87] hover:shadow-sm text-[#0d4f64] border-[#86B6F6] bg-transparent flex items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                    Export
                </button>
            </div>
        </div>

        <!-- Violation Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-gray-600 uppercase text-xs border-b bg-[#EEF5FF] border-[#B4D4FF]">
                    <tr>
                        <th class="px-3 py-2 text-left text-[#0d4f64]">Location</th>
                        <th class="px-3 py-2 text-left text-[#0d4f64]">Description</th>
                        <th class="px-3 py-2 text-left text-[#0d4f64]">Date Detected</th>
                        <th class="px-3 py-2 text-center text-[#0d4f64]">Severity</th>
                        <th class="px-3 py-2 text-center text-[#0d4f64]">Status</th>
                        <th class="px-3 py-2 text-right text-[#0d4f64]">Action</th>
                    </tr>
                </thead>
                <tbody id="violationTableBody">
                    <?php foreach ($violations as $v): ?>
                    <tr class="border-b transition-all duration-150 hover:bg-[#EEF5FF] violation-row border-[#B4D4FF]" 
                        data-severity="<?= $v['severity'] ?>" 
                        data-location="<?= strtolower(htmlspecialchars($v['location'])) ?>"
                        data-date="<?= date('Y-m-d', strtotime($v['timestamp'])) ?>">
                        <td class="px-3 py-3 font-medium text-[#0d4f64]"><?= htmlspecialchars($v['location']) ?></td>
                        <td class="px-3 py-3 max-w-[220px] truncate text-[#176B87]" title="<?= htmlspecialchars($v['description']) ?>">
                            <?= htmlspecialchars($v['description']) ?>
                        </td>
                        <td class="px-3 py-3 text-gray-500 whitespace-nowrap">
                            <?= date('M d, Y g:i A', strtotime($v['timestamp'])) ?>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold transition-all duration-200 hover:scale-105 <?= $v['severity'] === 'critical' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' ?>">
                                <?= ucfirst($v['severity']) ?>
                            </span>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <?php 
                                $statusClass = 'bg-gray-100 text-gray-800';
                                if ($v['status'] == 'open') $statusClass = 'bg-red-100 text-red-800';
                                if ($v['status'] == 'in_progress') $statusClass = 'bg-yellow-100 text-yellow-800';
                                if ($v['status'] == 'resolved') $statusClass = 'bg-green-100 text-green-800';
                            ?>
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold transition-all duration-200 hover:scale-105 <?= $statusClass ?>">
                                <?= ucfirst(str_replace('_', ' ', $v['status'])) ?>
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right">
                            <button class="assignActionBtn text-xs font-medium border px-3 py-1 rounded-md transition-all duration-200 hover:bg-[#176B87] hover:text-white hover:border-[#176B87] hover:shadow-sm text-[#0d4f64] border-[#86B6F6] bg-transparent" data-violation-id="<?= $v['id'] ?>" data-location="<?= htmlspecialchars($v['location']) ?>">
                                Assign Action
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Quick Corrective Actions overview -->
        <div class="mt-4 pt-3 border-t border-[#B4D4FF]">
            <p class="text-xs mb-2 text-[#176B87] font-medium">Pending Corrective Actions</p>
            <div class="flex flex-wrap gap-2" id="actionsContainer">
                <?php foreach ($actions as $a): 
                    $vTimestamp = $violationTimestamps[$a['violation_id']] ?? '2026-07-31';
                    $dataDate = date('Y-m-d', strtotime($vTimestamp));
                ?>
                <div class="action-pill border rounded-md px-3 py-1.5 text-xs flex items-center gap-2 transition-all duration-200 hover:scale-105 hover:shadow-sm bg-[#EEF5FF] border-[#B4D4FF]" data-date="<?= $dataDate ?>">
                    <span class="font-medium text-[#0d4f64]">#<?= $a['id'] ?></span>
                    <span class="text-[#176B87]"><?= htmlspecialchars($a['assigned_to']) ?></span>
                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold <?= getActionBadge($a['status']) ?>">
                        <?= strtoupper(str_replace('_', ' ', $a['status'])) ?>
                    </span>
                    <span class="text-[#86B6F6]">due <?= date('M d', strtotime($a['due_date'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================
         ROW 3: REGULATORY COMPLIANCE + AUDIT TOOLS
         ============================================================ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Permits & Expiry -->
        <div class="report-card rounded-xl p-4">
            <h2 class="font-semibold mb-3 flex items-center gap-2 text-[#0d4f64]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#176B87]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Regulatory Compliance – Permits
            </h2>
            <ul class="divide-y divide-[#B4D4FF]">
                <?php foreach ($permits as $p): ?>
                <li class="py-3 flex items-center justify-between transition-all duration-200 hover:pl-2">
                    <span class="text-[#0d4f64] font-medium"><?= htmlspecialchars($p['name']) ?></span>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">Exp: <?= date('M d, Y', strtotime($p['expiry'])) ?></span>
                        <?php if ($p['status'] === 'expiring_soon'): ?>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-yellow-100 text-yellow-800">EXPIRING SOON</span>
                        <?php elseif ($p['status'] === 'expired'): ?>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-red-100 text-red-800">EXPIRED</span>
                        <?php else: ?>
                            <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-green-100 text-green-800">ACTIVE</span>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <button class="mt-3 text-sm font-medium flex items-center gap-1 transition-all duration-200 hover:translate-x-1 text-[#176B87]">
                Renew / upload new permit →
            </button>
        </div>

        <!-- Audit Report Generator -->
        <div class="report-card rounded-xl p-4">
            <h2 class="font-semibold mb-3 flex items-center gap-2 text-[#0d4f64]">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-[#176B87]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                Compliance Audit & Reporting
            </h2>
            <div class="rounded-lg p-4 border transition-all duration-200 hover:bg-[#e6f0fa] bg-[#EEF5FF] border-[#B4D4FF]">
                <p class="text-sm mb-3 text-[#176B87]">Generate a complete audit trail with all monitoring logs, violations, and corrective actions for any date range.</p>
                <form method="get" action="" class="flex flex-wrap items-center gap-3">
                    <input type="hidden" name="generate_report" value="1">
                    <div>
                        <label class="text-xs block text-[#176B87]">From</label>
                        <input type="date" name="from" id="reportFrom" value="2026-07-01" class="border rounded-md px-2 py-1 text-sm transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] bg-white">
                    </div>
                    <div>
                        <label class="text-xs block text-[#176B87]">To</label>
                        <input type="date" name="to" id="reportTo" value="2026-07-31" class="border rounded-md px-2 py-1 text-sm transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] bg-white">
                    </div>
                    <button type="submit" class="mt-1 text-white text-sm font-medium px-4 py-2 rounded-md transition-all duration-200 hover:scale-105 hover:shadow-md flex items-center gap-2 bg-[#176B87]">
                        <i class="fas fa-file-csv"></i> Generate Report
                    </button>
                </form>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs border-t pt-3 border-[#B4D4FF] text-gray-500">
                <span>Last audit: July 16, 2026 (all clear)</span>
                <span class="font-medium flex items-center gap-1 text-amber-600">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    Attention Needed
                </span>
            </div>
        </div>
    </div>

</main>

<!-- ============================================================
     MODAL: Assign Corrective Action
     ============================================================ -->
<div id="actionModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 hidden transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6 animate-fadeIn scale-95 transition-all duration-300">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-[#0d4f64]">Assign Corrective Action</h3>
            <button id="closeModalBtn" class="text-2xl leading-none transition-all duration-200 hover:rotate-90 text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form id="actionForm" onsubmit="return false;">
            <input type="hidden" id="modalViolationId">
            <div class="mb-3">
                <label class="block text-sm font-medium text-[#0d4f64]">Violation</label>
                <p id="modalViolationDesc" class="text-sm p-2 rounded border mt-1 bg-[#EEF5FF] border-[#B4D4FF] text-[#176B87]">—</p>
            </div>
            <div class="mb-3">
                <label for="assignedTo" class="block text-sm font-medium text-[#0d4f64]">Assign to</label>
                <input type="text" id="assignedTo" placeholder="e.g. John Doe" class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-transparent transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] outline-none">
            </div>
            <div class="mb-3">
                <label for="dueDate" class="block text-sm font-medium text-[#0d4f64]">Due Date &amp; Time</label>
                <input type="datetime-local" id="dueDate" class="w-full border rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-transparent transition-all duration-200 hover:border-[#176B87] border-[#B4D4FF] text-[#0d4f64] outline-none">
            </div>
            <div class="mb-4">
                <label for="proofUpload" class="block text-sm font-medium text-[#0d4f64]">Proof attachment (optional)</label>
                <input type="file" id="proofUpload" accept="image/*" class="w-full text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm transition-all duration-200 file:transition file:duration-200 file:hover:bg-[#176B87] file:hover:text-white text-[#86B6F6] bg-transparent">
            </div>
            <div class="flex gap-3">
                <button type="button" id="submitActionBtn" class="flex-1 text-white font-medium py-2 rounded-md transition-all duration-200 hover:scale-105 hover:shadow-md bg-[#176B87]">Assign Action</button>
                <button type="button" id="cancelModalBtn" class="flex-1 font-medium py-2 rounded-md transition-all duration-200 hover:bg-[#dce8f5] bg-[#EEF5FF] text-[#0d4f64]">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     TOAST NOTIFICATION CONTAINER
     ============================================================ -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-[100] space-y-3"></div>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
    (function() {
        'use strict';

        // ----- 1. LIVE CLOCK -----
        function updateClock() {
            const now = new Date();
            const str = now.toLocaleString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const el = document.getElementById('liveClock');
            if (el) el.textContent = str;
        }
        updateClock();
        setInterval(updateClock, 1000);

        // ----- 2. TOAST NOTIFICATION SYSTEM -----
        function showToast(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600'
            };
            toast.className = `${colors[type]} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 transform translate-y-full opacity-0 transition-all duration-300`;
            toast.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>${message}</span>
            `;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-y-full', 'opacity-0');
            }, 50);

            setTimeout(() => {
                toast.classList.add('translate-y-full', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // ----- 3. FILTER & SEARCH (including date range) -----
        const filterSelect = document.getElementById('severityFilter');
        const searchInput = document.getElementById('tableSearch');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const rows = document.querySelectorAll('.violation-row');

        function applyFilters() {
            const filterVal = filterSelect.value;
            const searchVal = searchInput.value.toLowerCase();
            const fromVal = dateFrom.value;
            const toVal = dateTo.value;

            rows.forEach(row => {
                const sev = row.getAttribute('data-severity');
                const loc = row.getAttribute('data-location');
                const rowDate = row.getAttribute('data-date');

                const matchesFilter = (filterVal === 'all' || sev === filterVal);
                const matchesSearch = loc.includes(searchVal);
                let matchesDate = true;
                if (fromVal && rowDate < fromVal) matchesDate = false;
                if (toVal && rowDate > toVal) matchesDate = false;

                if (matchesFilter && matchesSearch && matchesDate) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Filter action pills
            const pills = document.querySelectorAll('.action-pill');
            pills.forEach(pill => {
                const pillDate = pill.getAttribute('data-date');
                let matchesDate = true;
                if (fromVal && pillDate < fromVal) matchesDate = false;
                if (toVal && pillDate > toVal) matchesDate = false;
                pill.style.display = matchesDate ? '' : 'none';
            });
        }

        if (filterSelect) filterSelect.addEventListener('change', applyFilters);
        if (searchInput) searchInput.addEventListener('input', applyFilters);
        if (dateFrom) dateFrom.addEventListener('change', applyFilters);
        if (dateTo) dateTo.addEventListener('change', applyFilters);

        // ----- 4. MODAL LOGIC -----
        const modal = document.getElementById('actionModal');
        const openBtns = document.querySelectorAll('.assignActionBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');
        const cancelModalBtn = document.getElementById('cancelModalBtn');
        const modalViolationDesc = document.getElementById('modalViolationDesc');
        const modalViolationId = document.getElementById('modalViolationId');
        const assignedTo = document.getElementById('assignedTo');
        const dueDate = document.getElementById('dueDate');
        const submitBtn = document.getElementById('submitActionBtn');
        const proofUpload = document.getElementById('proofUpload');

        function openModal(violationId, location) {
            let desc = 'Violation #' + violationId;
            rows.forEach(row => {
                const btn = row.querySelector('.assignActionBtn');
                if (btn && btn.dataset.violationId == violationId) {
                    const descTd = row.querySelectorAll('td')[1];
                    if (descTd) desc = descTd.textContent.trim();
                }
            });
            modalViolationId.value = violationId;
            modalViolationDesc.textContent = location + ' – ' + desc;
            assignedTo.value = '';
            dueDate.value = '';
            proofUpload.value = '';
            modal.classList.remove('hidden');
            const modalContent = modal.querySelector('.bg-white');
            modalContent.style.transform = 'scale(1)';
        }

        function closeModal() {
            const modalContent = modal.querySelector('.bg-white');
            modalContent.style.transform = 'scale(0.95)';
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        openBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                const id = this.dataset.violationId;
                const loc = this.dataset.location || 'Unknown';
                openModal(id, loc);
            });
        });

        if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
        if (cancelModalBtn) cancelModalBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // ----- 5. SUBMIT ACTION -----
        if (submitBtn) {
            submitBtn.addEventListener('click', function() {
                const id = modalViolationId.value;
                const assign = assignedTo.value.trim();
                const due = dueDate.value;

                if (!assign || !due) {
                    showToast('Please fill in both "Assign to" and "Due Date".', 'error');
                    return;
                }

                const container = document.getElementById('actionsContainer');
                if (container) {
                    const dueFormatted = new Date(due).toISOString().split('T')[0];
                    const newPill = document.createElement('div');
                    newPill.className = 'action-pill border rounded-md px-3 py-1.5 text-xs flex items-center gap-2 transition-all duration-200 hover:scale-105 hover:shadow-sm bg-[#EEF5FF] border-[#B4D4FF]';
                    newPill.setAttribute('data-date', dueFormatted);
                    newPill.innerHTML = `
                        <span class="font-medium text-[#0d4f64]">NEW</span>
                        <span class="text-[#176B87]">${assign}</span>
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">OPEN</span>
                        <span class="text-[#86B6F6]">due ${new Date(due).toLocaleDateString()}</span>
                    `;
                    container.prepend(newPill);
                    applyFilters();
                }

                closeModal();
                showToast(`Corrective action assigned to ${assign}`, 'success');
            });
        }

        // ----- 6. EXPORT CSV (table export) -----
        const exportBtn = document.getElementById('exportCsvBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', function() {
                const visibleRows = document.querySelectorAll('.violation-row:not([style*="display: none"])');
                if (visibleRows.length === 0) {
                    showToast('No rows to export. Adjust your filters.', 'error');
                    return;
                }

                const headers = ['Location', 'Description', 'Date Detected', 'Severity', 'Status'];
                const rowsData = [];

                visibleRows.forEach(row => {
                    const cols = row.querySelectorAll('td');
                    const location = cols[0] ? cols[0].textContent.trim() : '';
                    const description = cols[1] ? cols[1].textContent.trim() : '';
                    const date = cols[2] ? cols[2].textContent.trim() : '';
                    const severity = cols[3] ? cols[3].textContent.trim() : '';
                    const status = cols[4] ? cols[4].textContent.trim() : '';
                    rowsData.push([location, description, date, severity, status]);
                });

                let csvContent = headers.join(',') + '\n';
                rowsData.forEach(row => {
                    const escaped = row.map(cell => {
                        if (typeof cell === 'string' && (cell.includes(',') || cell.includes('"'))) {
                            return '"' + cell.replace(/"/g, '""') + '"';
                        }
                        return cell;
                    });
                    csvContent += escaped.join(',') + '\n';
                });

                // UTF-8 BOM for Excel
                const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.href = url;
                link.setAttribute('download', 'violations_export.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);

                showToast('Violation data exported to CSV successfully!', 'success');
            });
        }

        // ----- 7. KEYBOARD SHORTCUT: ESC -----
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        console.log('Health Sanitation Dashboard initialized with clean Excel export.');
    })();
</script>

<?php include '../includes/footer.php'; ?>