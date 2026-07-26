<?php
// ============================================================
// QUEUE MANAGEMENT DISPLAY
// ============================================================
// Shows waiting patients only - for citizen viewing (public monitor)
// No admin bars, no back buttons, no header.php. Fully standalone.
// ============================================================

session_start();
require_once __DIR__ . '/../../app/Models/Triage.php';
require_once __DIR__ . '/../../app/Models/Patient.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civentral Health Queue</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>
<body class="bg-slate-950 text-slate-100 font-sans min-h-screen">
<?php

// Fetch all triage records
$triageModel = new Triage();
$patientModel = new Patient();
$rawPatients = [];

try {
    $rawPatients = $patientModel->all();
} catch (Throwable $e) {
    error_log('Queue page: Error fetching patients: ' . $e->getMessage());
}

$patientsMap = [];
foreach ($rawPatients as $p) {
    if (isset($p['id'])) $patientsMap[$p['id']] = $p;
}

$rawTriage = [];
try {
    $rawTriage = $triageModel->all(['order' => 'created_at.asc']);
} catch (Throwable $e) {
    error_log('Queue page: Error fetching triage: ' . $e->getMessage());
}

// ------------------------------------------------------------
// Service / Counter configuration
// Map whatever value is stored in the DB (service_type / department)
// to a display label + counter number. Add more entries here if
// your system has more service types.
// ------------------------------------------------------------
$serviceConfig = [
    'specialist' => [
        'label' => 'Specialist',
        'counter' => 'Counter 1',
        'icon' => 'fa-user-doctor',
        'color' => 'indigo',
    ],
    'internal_medicine' => [
        'label' => 'Internal Medicine',
        'counter' => 'Counter 2',
        'icon' => 'fa-stethoscope',
        'color' => 'teal',
    ],
    'pediatrics' => [
        'label' => 'Pediatrics',
        'counter' => 'Counter 3',
        'icon' => 'fa-child-reaching',
        'color' => 'purple',
    ],
    'general' => [
        'label' => 'General',
        'counter' => 'Counter 4',
        'icon' => 'fa-house-medical',
        'color' => 'slate',
    ],
];

// Tailwind-safe color classes (declared explicitly so purge doesn't strip them)
$colorClasses = [
    'indigo' => ['header' => 'bg-indigo-600', 'soft' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-700', 'chip' => 'bg-indigo-100 text-indigo-700'],
    'teal'   => ['header' => 'bg-teal-600',   'soft' => 'bg-teal-50',   'border' => 'border-teal-200',   'text' => 'text-teal-700',   'chip' => 'bg-teal-100 text-teal-700'],
    'purple' => ['header' => 'bg-purple-600', 'soft' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-700', 'chip' => 'bg-purple-100 text-purple-700'],
    'slate'  => ['header' => 'bg-slate-600',  'soft' => 'bg-slate-50',  'border' => 'border-slate-200',  'text' => 'text-slate-700',  'chip' => 'bg-slate-100 text-slate-700'],
];

$now = time();

function buildDisplayEntry(array $t, array $patientsMap, array $serviceConfig, string $defaultServiceKey, bool $isCalling): array {
    $patientId = $t['patient_id'] ?? null;
    $patient = $patientsMap[$patientId] ?? null;

    $patientCode = $patient['patient_id'] ?? ('P-' . $patientId);
    $arrivalTime = isset($t['created_at']) ? strtotime($t['created_at']) : time();

    $rawService = $t['service_type'] ?? $t['department'] ?? $patient['service_type'] ?? $patient['department'] ?? $defaultServiceKey;
    $serviceKey = strtolower(str_replace(' ', '_', trim($rawService)));
    if (!isset($serviceConfig[$serviceKey])) {
        $serviceKey = $defaultServiceKey;
    }

    return [
        'id' => $t['id'],
        'patient_code' => $patientCode,
        'priority' => strtolower($t['priority'] ?? 'medium'),
        'service' => $serviceKey,
        'arrival_time' => $arrivalTime,
        'created_at' => $t['created_at'] ?? '',
        'is_calling' => $isCalling,
    ];
}

// Filter only waiting (pending) patients
$waitingPatients = [];
foreach ($rawTriage as $t) {
    $dbStatus = strtolower($t['status'] ?? 'pending');
    if ($dbStatus !== 'pending') continue; // Only show waiting patients
    $waitingPatients[] = buildDisplayEntry($t, $patientsMap, $serviceConfig, 'general', false);
}

// Sort: critical first, then high, medium, low, and within same priority by arrival time
$priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
usort($waitingPatients, function($a, $b) use ($priorityOrder) {
    $pa = $priorityOrder[$a['priority']] ?? 99;
    $pb = $priorityOrder[$b['priority']] ?? 99;
    if ($pa === $pb) {
        return $a['arrival_time'] - $b['arrival_time'];
    }
    return $pa - $pb;
});

// Group by service after sorting so each column keeps priority order
$groupedPatients = [];
foreach ($serviceConfig as $key => $cfg) {
    $groupedPatients[$key] = [];
}
foreach ($waitingPatients as $wp) {
    $groupedPatients[$wp['service']][] = $wp;
}

// ------------------------------------------------------------
// Wait time formatter - avoids showing absurd raw minute counts
// (e.g. "2410 min") by rolling over into hours once past 60 min.
// ------------------------------------------------------------
function formatWaitTime($minutes) {
    if ($minutes < 1) return 'Just now';
    if ($minutes < 60) return $minutes . ' min';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
}

$title = 'Queue Display';
$totalWaiting = count($waitingPatients);

// Build a list of active service calls for announcement logic.
$activeCalls = [];
foreach ($serviceConfig as $key => $cfg) {
    $queue = $groupedPatients[$key];
    if (!empty($queue)) {
        $activeCalls[] = [
            'service' => $key,
            'code' => $queue[0]['patient_code'],
            'counter' => $cfg['counter'],
            'label' => $cfg['label'],
        ];
    }
}
?>

<div class="min-h-screen bg-slate-950 text-slate-100">
    <style>
    .page-fade { opacity: 0; transform: translateY(12px); animation: pageFadeIn 0.9s ease-out forwards; }
    @keyframes pageFadeIn { to { opacity: 1; transform: translateY(0); } }

    .active-card { position: relative; overflow: hidden; border-radius: 2rem; border: 1px solid rgba(245,159,11,0.35); box-shadow: 0 36px 80px -50px rgba(245,158,11,0.85); animation: glowPulse 3s ease-in-out infinite; }
    .active-card::before { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at top, rgba(245,158,11,0.24), transparent 42%); pointer-events: none; mix-blend-mode: screen; }
    .active-card .active-inner { position: relative; z-index: 1; }
    .badge-pulse { animation: badgeBounce 1.8s ease-in-out infinite; }
    @keyframes badgeBounce { 0%,100% { transform: translateY(0) scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0); } 50% { transform: translateY(-2px) scale(1.02); box-shadow: 0 0 0 14px rgba(245,158,11,0.12); } }
    @keyframes glowPulse { 0%,100% { box-shadow: 0 36px 80px -50px rgba(245,158,11,0.75); } 50% { box-shadow: 0 40px 120px -50px rgba(245,158,11,0.95); } }

    .waiting-card { transition: transform 0.25s ease, border-color 0.25s ease, background-color 0.25s ease; }
    .waiting-card:hover { transform: translateY(-2px); }
    .counter-panel { min-height: 480px; }
    .section-header { text-transform: uppercase; letter-spacing: 0.24em; }
    #poweredToast { position: fixed; bottom: 1.5rem; right: 1.5rem; background: rgba(15,23,42,0.96); color: #fff; padding: 0.75rem 1.2rem; border-radius: 1rem; display: flex; align-items: center; gap: 0.75rem; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; box-shadow: 0 18px 45px rgba(0,0,0,0.25); transform: translateY(120%); opacity: 0; transition: transform 0.45s ease, opacity 0.45s ease; z-index: 60; }
    #poweredToast.show { transform: translateY(0); opacity: 1; }
    #poweredToast img { height: 18px; width: auto; }
    </style>

    <div id="queueWrapper" class="max-w-8xl mx-auto px-6 py-8 page-fade">
        <header class="mb-8 rounded-[2rem] border border-white/10 bg-slate-900/80 p-6 shadow-[0_30px_80px_-40px_rgba(0,0,0,0.6)] backdrop-blur-xl">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <img src="../../assets/images/logo.png" alt="Civentral Logo" class="h-16 w-auto object-contain">
                    <div>
                        <p class="text-sm uppercase tracking-[0.48em] text-amber-300/90">Civentral Health Center</p>
                        <h1 class="mt-2 text-4xl font-black tracking-tight text-white">Clinic Queue Monitor</h1>
                        <p class="mt-2 text-sm text-slate-400">A premium public queue display for patients and visitors.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-right">
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Current date/time</p>
                        <p id="clockTime" class="mt-3 text-2xl font-black text-white"></p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Total waiting</p>
                        <p class="mt-3 text-4xl font-black text-white"><?php echo $totalWaiting; ?></p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <p class="text-xs uppercase tracking-[0.4em] text-slate-400">Last refreshed</p>
                        <p id="refreshStamp" class="mt-3 text-2xl font-black text-white"><?php echo date('g:i A'); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <main class="grid grid-cols-1 xl:grid-cols-4 gap-6">
            <?php
            $priorityDots = ['critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];
            $priorityChip = [
                'critical' => 'bg-rose-100 text-rose-700',
                'high' => 'bg-orange-100 text-orange-700',
                'medium' => 'bg-yellow-100 text-yellow-700',
                'low' => 'bg-green-100 text-green-700',
            ];

            foreach ($serviceConfig as $key => $cfg):
                $colors = $colorClasses[$cfg['color']];
                $queue = $groupedPatients[$key];
                $active = $queue[0] ?? null;
                $waiting = array_slice($queue, 1);
            ?>
            <section class="counter-panel rounded-[2rem] border border-slate-800/60 bg-slate-950/90 p-5 shadow-2xl">
                <div class="mb-5 flex flex-col gap-3 rounded-[1.75rem] bg-slate-900/80 px-5 py-4 border border-white/10">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-slate-800/90 text-white">
                                <i class="fa-solid <?php echo $cfg['icon']; ?> text-lg"></i>
                            </div>
                            <div>
                                <p class="text-sm uppercase tracking-[0.32em] text-slate-400"><?php echo htmlspecialchars($cfg['label']); ?></p>
                                <p class="text-2xl font-black tracking-tight text-white"><?php echo htmlspecialchars($cfg['counter']); ?></p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs uppercase tracking-[0.32em] text-slate-400">Waiting</p>
                            <p class="mt-1 text-3xl font-black text-white"><?php echo count($queue); ?></p>
                        </div>
                    </div>
                </div>

                <?php if (!$queue): ?>
                    <div class="flex h-full min-h-[360px] flex-col items-center justify-center rounded-[2rem] border border-dashed border-slate-800 bg-slate-900/80 p-6 text-center text-slate-500">
                        <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-blue-500/10 text-3xl text-blue-300">
                            <i class="fa-solid fa-hand-holding-medical"></i>
                        </div>
                        <p class="text-xl font-semibold text-white">No patients waiting</p>
                        <p class="mt-3 text-sm leading-relaxed">This counter is ready for the next patient.</p>
                    </div>
                <?php else: ?>
                    <article class="active-card mb-6 overflow-hidden bg-gradient-to-br from-amber-50 via-white to-amber-100 p-6">
                        <div class="active-inner">
                            <div class="mb-4 flex justify-center">
                                <div class="badge-pulse inline-flex items-center gap-2 rounded-full bg-amber-500 px-4 py-2 text-xs font-bold uppercase tracking-[0.28em] text-white shadow-xl">
                                    <i class="fa-solid fa-bullhorn"></i>
                                    Now Calling
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm uppercase tracking-[0.3em] text-slate-700">Please proceed to</p>
                                <p class="mt-4 text-5xl font-black tracking-tight text-slate-950"><?php echo htmlspecialchars($active['patient_code']); ?></p>
                                <p class="mt-4 text-2xl font-semibold text-slate-900"><?php echo htmlspecialchars($cfg['counter']); ?></p>
                                <p class="mt-1 text-sm uppercase tracking-[0.24em] text-slate-500"><?php echo htmlspecialchars($cfg['label']); ?></p>
                            </div>
                        </div>
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-amber-200/40 to-transparent"></div>
                    </article>

                    <div class="rounded-[1.75rem] border border-slate-800/70 bg-slate-900/85 p-4">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs uppercase tracking-[0.28em] text-slate-400">Queue</p>
                                <p class="text-2xl font-black text-white">Next</p>
                            </div>
                            <span class="rounded-full bg-slate-800 px-3 py-1 text-xs uppercase tracking-[0.24em] text-slate-400"><?php echo count($waiting) + 1; ?> in line</span>
                        </div>

                        <?php if (empty($waiting)): ?>
                            <div class="rounded-[1.5rem] border border-slate-800 bg-slate-950/90 p-6 text-center text-slate-500">
                                <p class="font-semibold text-white">No other patients waiting</p>
                                <p class="mt-2 text-sm text-slate-500">The queue is clear after this call.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($waiting as $index => $wp):
                                    $position = $index + 2;
                                    $waitMinutes = round((time() - $wp['arrival_time']) / 60);
                                ?>
                                <div class="waiting-card rounded-[1.75rem] border border-slate-800/70 bg-slate-950/85 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs uppercase tracking-[0.28em] text-slate-500">#<?php echo $position; ?></p>
                                            <p class="mt-1 text-lg font-bold text-white"><?php echo htmlspecialchars($wp['patient_code']); ?></p>
                                        </div>
                                        <div class="flex flex-col items-end gap-2 text-right">
                                            <span class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.18em] <?php echo $priorityChip[$wp['priority']] ?? $priorityChip['medium']; ?>">
                                                <?php echo $priorityDots[$wp['priority']] ?? '🟡'; ?> <?php echo ucfirst($wp['priority']); ?>
                                            </span>
                                            <span class="text-xs text-slate-500 flex items-center gap-1"><i class="fa-regular fa-clock"></i><?php echo formatWaitTime($waitMinutes); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>
        </main>
    </div>
</div>

<div id="poweredToast">
    <img src="../../assets/images/logo.png" alt="Civentral Logo">
    <span>Powered by Civentral</span>
</div>

<script type="application/json" id="activeCallsData">
<?= json_encode($activeCalls, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

<script>
const refreshInterval = 30000;
const activeCalls = JSON.parse(document.getElementById('activeCallsData').textContent || '[]');
const clockElement = document.getElementById('clockTime');
const refreshStamp = document.getElementById('refreshStamp');

function updateClock() {
    const now = new Date();
    clockElement.textContent = now.toLocaleString([], { weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
    refreshStamp.textContent = now.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
}

function announceActiveCalls() {
    if (!window.speechSynthesis) return;
    const storageKey = 'last_called_patient';
    let lastCalled = {};
    try {
        lastCalled = JSON.parse(localStorage.getItem(storageKey) || '{}');
    } catch (err) {
        lastCalled = {};
    }

    let updated = false;
    activeCalls.forEach(call => {
        const previousCode = lastCalled[call.service] || '';
        if (previousCode !== call.code) {
            const utter = new SpeechSynthesisUtterance(`Attention please. Patient ${call.code}. Please proceed to ${call.counter}. Thank you.`);
            utter.lang = "en-US";
            utter.rate = 0.9;
            utter.pitch = 1;
            utter.volume = 1;
            window.speechSynthesis.speak(utter);
            lastCalled[call.service] = call.code;
            updated = true;
        }
    });

    if (updated) {
        localStorage.setItem(storageKey, JSON.stringify(lastCalled));
    }
}

function showPoweredToast() {
    const toast = document.getElementById('poweredToast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 4000);
}

window.addEventListener('load', () => {
    updateClock();
    setInterval(updateClock, 1000);
    announceActiveCalls();
    showPoweredToast();
    setInterval(showPoweredToast, 60000);
    setTimeout(() => window.location.reload(), refreshInterval);
});
</script>
</body>
</html>