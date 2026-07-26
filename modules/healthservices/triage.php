<?php
// ============================================================
// COLOR PALETTE USED ON THIS PAGE
// ============================================================
//   'brand-dark':   '#0B4F4A',
//   'brand-medium': '#14807A',
//   'brand-light':  '#E6F5F3',
//   'brand-border': '#B8E0DC',
// ============================================================

// ============================================================
// 1. PHP BACKEND - Fetch Data
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

require_once __DIR__ . '/../../app/Models/Patient.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Triage.php';
require_once __DIR__ . '/../../app/Models/TriageQueue.php'; // NEW: Triage Queue model

// Fetch Patients
$patientModel = new Patient();
$rawPatients = [];
try {
    $rawPatients = $patientModel->all();
} catch (Throwable $e) {
    error_log('Error fetching patients for triage: ' . $e->getMessage());
}

// Fetch Employees/Nurses
$employeeModel = new Employee();
$rawEmployees = [];
try {
    $rawEmployees = $employeeModel->all();
} catch (Throwable $e) {
    error_log('Error fetching employees for triage: ' . $e->getMessage());
}

// Fetch Triage Queue
$triageModel = new Triage();
$rawTriage = [];
try {
    $rawTriage = $triageModel->all(['order' => 'created_at.desc']);
} catch (Throwable $e) {
    error_log('Error fetching triage queue: ' . $e->getMessage());
}

// NEW: Fetch Triage Queue (Check-in system)
$triageQueueModel = new TriageQueue();
$waitingCheckins = [];
try {
    $waitingCheckins = $triageQueueModel->all(['order' => 'created_at.asc']);
} catch (Throwable $e) {
    error_log('Error fetching triage queue check-ins: ' . $e->getMessage());
    $waitingCheckins = [];
}

// ============================================================
// Get IDs of patients already triaged TODAY to filter dropdown
// ============================================================
$triagedTodayIds = [];
$today = date('Y-m-d');
foreach ($rawTriage as $t) {
    if (isset($t['created_at']) && date('Y-m-d', strtotime($t['created_at'])) === $today) {
        $triagedTodayIds[] = $t['patient_id'];
    }
}

// Build patients array for Triage dropdown (only untriaged patients)
$patients = [];
foreach ($rawPatients as $p) {
    // Skip patients already in today's triage queue
    if (in_array($p['id'], $triagedTodayIds)) {
        continue;
    }
    
    $firstName = $p['first_name'] ?? '';
    $lastName = $p['last_name'] ?? '';
    $name = trim($firstName . ' ' . $lastName);
    if (empty($name)) {
        $name = $p['name'] ?? ('Patient #' . ($p['id'] ?? ''));
    }
    $patients[] = [
        'id' => $p['id'],
        'patient_id' => $p['patient_id'] ?? ('P-' . $p['id']),
        'name' => $name
    ];
}

// NEW: Build all patients array for Check-in dropdown (all patients)
$allPatientsForCheckin = [];
foreach ($rawPatients as $p) {
    $firstName = $p['first_name'] ?? '';
    $lastName = $p['last_name'] ?? '';
    $name = trim($firstName . ' ' . $lastName);
    if (empty($name)) {
        $name = $p['name'] ?? ('Patient #' . ($p['id'] ?? ''));
    }
    $allPatientsForCheckin[] = [
        'id' => $p['id'],
        'patient_id' => $p['patient_id'] ?? ('P-' . $p['id']),
        'name' => $name
    ];
}

$nurses = [];
foreach ($rawEmployees as $emp) {
    $empName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
    if (empty($empName)) $empName = $emp['name'] ?? ('Nurse #' . $emp['id']);
    $nurses[] = [
        'id' => $emp['id'],
        'name' => 'Nurse ' . $empName
    ];
}

$patientsMap = [];
foreach ($rawPatients as $p) {
    if (isset($p['id'])) $patientsMap[$p['id']] = $p;
}

$employeesMap = [];
foreach ($rawEmployees as $emp) {
    if (isset($emp['id'])) $employeesMap[$emp['id']] = $emp;
}

$triageQueue = [];
$qNum = 1;
foreach ($rawTriage as $t) {
    $pId = $t['patient_id'] ?? null;
    $patient = $patientsMap[$pId] ?? null;
    if ($patient) {
        $pName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
        if (empty($pName)) $pName = $patient['name'] ?? ('Patient #' . $pId);
        $gender = $patient['gender'] ?? 'Unspecified';
        $age = 'N/A';
        if (isset($patient['birth_date'])) {
            try {
                $dob = new DateTime($patient['birth_date']);
                $now = new DateTime();
                $age = $now->diff($dob)->y;
            } catch (Throwable $ex) {}
        }
    } else {
        $pName = 'Patient #' . ($pId ?? 'N/A');
        $gender = 'Unspecified';
        $age = 'N/A';
    }

    $nId = $t['nurse_id'] ?? null;
    $nurse = $employeesMap[$nId] ?? null;
    $nurseName = $nurse ? trim(($nurse['first_name'] ?? '') . ' ' . ($nurse['last_name'] ?? '')) : '';
    if (empty($nurseName)) $nurseName = 'Nurse Maria Cruz';
    else $nurseName = 'Nurse ' . $nurseName;

    $parts = explode(' ', $pName);
    $initials = '';
    foreach ($parts as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }
    $avatar = substr($initials, 0, 2) ?: 'P';

    $symStr = $t['symptoms'] ?? '';
    $symArr = is_string($symStr) ? array_filter(array_map('trim', explode(',', $symStr))) : (array)$symStr;

    $dbStatus = strtolower($t['status'] ?? 'pending');
    $status = match($dbStatus) {
        'pending' => 'waiting',
        'triaged' => 'in_triage',
        'consulted' => 'sent_to_doctor',
        'cancelled' => 'cancelled',
        default => 'in_triage'
    };

    $triageQueue[] = [
        'id' => $t['id'],
        'triage_id' => $t['triage_id'] ?? ('TRG-' . $t['id']),
        'patient_id' => $pId,
        'patient_name' => $pName,
        'patient_avatar' => $avatar,
        'age' => $age,
        'gender' => $gender,
        'queue_number' => $qNum++,
        'bmi' => isset($t['bmi']) ? $t['bmi'] : (isset($t['weight'], $t['height']) && $t['height'] > 0 ? round($t['weight'] / (($t['height']/100) * ($t['height']/100)), 1) : null),
        'queue_status' => ($status === 'waiting' || $status === 'in_triage') ? 'waiting' : 'completed',
        'arrival_time' => isset($t['created_at']) ? date('h:i A', strtotime($t['created_at'])) : date('h:i A'),
        'wait_time' => '15 mins',
        'vital_signs' => [
            'blood_pressure' => $t['blood_pressure'] ?? '120/80',
            'heart_rate' => $t['heart_rate'] ?? 75,
            'temperature' => $t['temperature'] ?? 36.5,
            'respiratory_rate' => $t['respiratory_rate'] ?? 18,
            'oxygen_saturation' => $t['oxygen_saturation'] ?? 98,
            'weight' => $t['weight'] ?? 65.0,
            'height' => $t['height'] ?? 165.0,
            'blood_sugar' => $t['blood_sugar'] ?? 'N/A',
            'gcs_eye' => $t['gcs_eye'] ?? 0,
            'gcs_verbal' => $t['gcs_verbal'] ?? 0,
            'gcs_motor' => $t['gcs_motor'] ?? 0,
            'gcs_total' => ($t['gcs_eye'] ?? 0) + ($t['gcs_verbal'] ?? 0) + ($t['gcs_motor'] ?? 0),
            'bmi' => isset($t['bmi']) ? $t['bmi'] : (isset($t['weight'], $t['height']) && $t['height'] > 0 ? round($t['weight'] / (($t['height']/100) * ($t['height']/100)), 1) : null)
        ],
        'priority' => strtolower($t['priority'] ?? 'medium'),
        'symptoms' => array_values($symArr),
        'chief_complaint' => $t['notes'] ?? 'General Checkup',
        'nurse_assigned' => $nurseName,
        'status' => $status
    ];
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;
$totalTriage = count($triageQueue);
$totalPages = max(1, ceil($totalTriage / $limit));
$paginatedTriage = array_slice($triageQueue, $offset, $limit);

$title = 'Triage';

// Stats
$totalWaiting = count(array_filter($triageQueue, fn($t) => $t['queue_status'] === 'waiting'));
$totalCritical = count(array_filter($triageQueue, fn($t) => $t['priority'] === 'critical'));
$totalCompleted = count(array_filter($triageQueue, fn($t) => $t['status'] === 'completed' || $t['status'] === 'sent_to_doctor'));
$avgWaitTime = rand(15, 45);

// NEW: Generate next queue number for check-in
$todayCheckins = array_filter($waitingCheckins, function($c) use ($today) {
    return isset($c['created_at']) && date('Y-m-d', strtotime($c['created_at'])) === $today;
});
$nextQueueNumber = 'Q-' . date('Ymd') . '-' . str_pad(count($todayCheckins) + 1, 3, '0', STR_PAD_LEFT);
?>
<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->
<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Triage</h2>
            <p class="text-sm text-slate-500 mt-0.5">Vital signs recording, priority classification & queue management</p>
        </div>
        <div class="flex gap-3">
            <!-- NEW: Check-in button -->
            <button onclick="ModalSystem.open('checkInModal')"
                    class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-clipboard-check text-xs"></i> Check-in
            </button>
            <a href="queue_management.php"
                    class="px-4 py-2 bg-white border border-amber-300 text-amber-700 rounded-lg hover:bg-amber-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-list-ol text-xs"></i> Queue Display
            </a>
            <button onclick="ModalSystem.open('symptomCheckerModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-stethoscope text-xs"></i> Symptom Checker
            </button>
            <button onclick="ModalSystem.open('addTriageModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Add Patient
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
<!-- MODERN KPI CARDS - Updated to match design               -->
<!-- ============================================================ -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <!-- Card 1: Waiting -->
    <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                    <i class="fa-solid fa-people-group text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-amber-600" id="statWaiting"><?php echo $totalWaiting; ?></p>
                    <p class="text-xs font-medium text-slate-500">Waiting</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Queue</span>
                <span class="text-[10px] text-slate-400">Avg <?php echo $avgWaitTime; ?> min wait</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Critical -->
    <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                    <i class="fa-solid fa-heart-pulse text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-rose-600"><?php echo $totalCritical; ?></p>
                    <p class="text-xs font-medium text-slate-500">Critical</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
                <span class="text-[10px] text-slate-400">Immediate attention</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Completed -->
    <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                    <i class="fa-solid fa-check-circle text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-emerald-600"><?php echo $totalCompleted; ?></p>
                    <p class="text-xs font-medium text-slate-500">Completed</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Done</span>
                <span class="text-[10px] text-slate-400">Sent to doctor</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Today's Patients -->
    <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-sky-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-sky-500 to-sky-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-sky-200">
                    <i class="fa-solid fa-clock text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-sky-600"><?php echo $totalTriage; ?></p>
                    <p class="text-xs font-medium text-slate-500">Today's Patients</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-sky-100 text-sky-700 rounded-full text-[10px] font-bold">📅 Today</span>
                <span class="text-[10px] text-slate-400"><?php echo date('F d, Y'); ?></span>
            </div>
        </div>
    </div>
</div>

    <!-- Quick Queue Status -->
    <div class="bg-gradient-to-r from-amber-50 to-white rounded-xl shadow-xs p-3 border border-amber-200 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-4">
                <span class="text-xs font-semibold text-amber-700"><i class="fa-solid fa-queue mr-1"></i> Current Queue:</span>
                <?php 
                    $priorities = ['critical' => '🔴', 'high' => '🟠', 'medium' => '🟡', 'low' => '🟢'];
                    foreach ($priorities as $key => $icon):
                        $count = count(array_filter($triageQueue, fn($t) => $t['priority'] === $key && $t['queue_status'] === 'waiting'));
                        if ($count > 0):
                ?>
                    <span class="text-xs text-slate-600"><?php echo $icon; ?> <?php echo ucfirst($key); ?>: <?php echo $count; ?></span>
                <?php endif; endforeach; ?>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400">Next: <span class="font-semibold text-brand-dark">#<?php echo $triageQueue[0]['queue_number'] ?? 'N/A'; ?></span></span>
                <button onclick="callNextPatient()" class="px-3 py-1 text-xs font-semibold text-white bg-brand-dark rounded-lg hover:bg-brand-medium transition">
                    <i class="fa-solid fa-bullhorn mr-1"></i> Call Next
                </button>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchTriage"
                       placeholder="Search by patient name or ID..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterPriority" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Priorities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="waiting">Waiting</option>
                    <option value="in_triage">In Triage</option>
                    <option value="completed">Completed</option>
                    <option value="sent_to_doctor">Sent to Doctor</option>
                </select>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Triage Queue Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Age/Gender</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vital Signs</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Priority</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Wait Time</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="triageTableBody">
                    <?php foreach ($paginatedTriage as $triage): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors triage-row <?php echo $triage['priority'] === 'critical' ? 'bg-rose-50/50' : ''; ?>"
                        data-patient="<?php echo strtolower($triage['patient_name']); ?>"
                        data-priority="<?php echo $triage['priority']; ?>"
                        data-status="<?php echo $triage['status']; ?>">
                        <td class="px-4 py-3 font-mono text-xs font-bold <?php echo $triage['priority'] === 'critical' ? 'text-rose-600' : 'text-slate-400'; ?>">
                            #<?php echo $triage['queue_number']; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                                    <?php echo $triage['patient_avatar']; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm"><?php echo $triage['patient_name']; ?></p>
                                    <p class="text-xs text-slate-400"><?php echo $triage['chief_complaint']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <?php echo $triage['age']; ?> yrs<br>
                            <?php echo $triage['gender']; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-slate-400">BP:</span>
                                    <span class="font-medium text-slate-700"><?php echo $triage['vital_signs']['blood_pressure']; ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-slate-400">HR:</span>
                                    <span class="font-medium text-slate-700"><?php echo $triage['vital_signs']['heart_rate']; ?></span>
                                    <span class="text-slate-400 ml-1">Temp:</span>
                                    <span class="font-medium text-slate-700"><?php echo $triage['vital_signs']['temperature']; ?>°C</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-slate-400">O2:</span>
                                    <span class="font-medium text-slate-700"><?php echo $triage['vital_signs']['oxygen_saturation']; ?>%</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $priorityColors = [
                                    'critical' => 'bg-rose-100 text-rose-700 border-rose-200',
                                    'high' => 'bg-orange-100 text-orange-700 border-orange-200',
                                    'medium' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'low' => 'bg-green-100 text-green-700 border-green-200'
                                ];
                                $priorityIcons = [
                                    'critical' => '🔴',
                                    'high' => '🟠',
                                    'medium' => '🟡',
                                    'low' => '🟢'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold border <?php echo $priorityColors[$triage['priority']] ?? $priorityColors['medium']; ?>">
                                <?php echo $priorityIcons[$triage['priority']] ?? ''; ?> <?php echo ucfirst($triage['priority']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php 
                                echo $triage['status'] === 'in_triage' ? 'bg-brand-light text-brand-dark border border-brand-border' : 
                                    ($triage['status'] === 'waiting' ? 'bg-amber-100 text-amber-700' : 
                                    ($triage['status'] === 'sent_to_doctor' ? 'bg-emerald-100 text-emerald-700' : 
                                    'bg-slate-100 text-slate-500')); 
                            ?>">
                                <?php echo str_replace('_', ' ', ucfirst($triage['status'])); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <?php echo $triage['wait_time']; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewTriage(<?php echo $triage['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="editTriage(<?php echo $triage['id']; ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                                <?php if ($triage['status'] === 'in_triage' || $triage['status'] === 'waiting'): ?>
                                    <button onclick="completeTriage(<?php echo $triage['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete & Send to Doctor">
                                        <i class="fa-solid fa-check text-sm"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-user-slash text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No patients in triage queue</p>
            <p class="text-xs text-slate-400 mt-1">All patients have been attended to</p>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700"><?php echo $offset + 1; ?></span> to
                <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalTriage); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalTriage; ?></span> patients
            </p>
            <div class="flex gap-1">
                <button onclick="changePage(<?php echo $page - 1; ?>)"
                        class="px-3 py-1.5 rounded-lg text-sm <?php echo $page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"
                        <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <button onclick="changePage(<?php echo $i; ?>)"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium <?php echo $i === $page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                <button onclick="changePage(<?php echo $page + 1; ?>)"
                        class="px-3 py-1.5 rounded-lg text-sm <?php echo $page >= $totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"
                        <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- PATIENT CHECK-IN MODAL (NEW)                                  -->
<!-- ============================================================ -->
<div id="checkInModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Patient Check-in</h3>
            <button onclick="ModalSystem.close('checkInModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="checkInForm" class="p-6 space-y-4" onsubmit="checkInPatient(event)">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-info-circle text-amber-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Check-in Process</p>
                        <p class="text-xs text-amber-600 mt-1">
                            1. Select patient<br>
                            2. Queue number auto-assigned<br>
                            3. Patient waits for triage nurse
                        </p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label>
                <select id="checkin_patient" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Patient</option>
                    <?php foreach ($allPatientsForCheckin as $p): ?>
                        <option value="<?php echo $p['id']; ?>">
                            <?php echo htmlspecialchars($p['name']); ?> 
                            (<?php echo $p['patient_id']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Queue Number</label>
                <input type="text" id="checkin_queue" readonly 
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-700 font-mono font-bold"
                       value="<?php echo $nextQueueNumber; ?>" 
                       placeholder="Auto-generated">
                <p class="text-xs text-slate-400 mt-1">Queue number is automatically assigned</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('checkInModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition text-sm font-semibold">
                    <i class="fa-solid fa-check-circle mr-1.5"></i> Check In
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD TRIAGE MODAL                                             -->
<!-- ============================================================ -->
<div id="addTriageModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Add Triage Patient</h3>
            <button onclick="ModalSystem.close('addTriageModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addTriageForm" class="p-6 space-y-4" onsubmit="saveTriage(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label>
                <select id="triage_patient" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?> (<?php echo $p['patient_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($patients)): ?>
                    <p class="text-xs text-amber-600 mt-1">
                        <i class="fa-solid fa-info-circle"></i> No patients waiting. Please check-in a patient first.
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- Vital Signs -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">Vital Signs</h4>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">BP (Systolic/Diastolic)</label>
                        <input type="text" id="triage_bp" placeholder="120/80" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Heart Rate (bpm)</label>
                        <input type="number" id="triage_hr" placeholder="72" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Temperature (°C)</label>
                        <input type="text" id="triage_temp" placeholder="36.5" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">O2 Saturation (%)</label>
                        <input type="number" id="triage_o2" placeholder="98" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Respiratory Rate</label>
                        <input type="number" id="triage_rr" placeholder="18" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Weight (kg)</label>
                        <input type="text" id="triage_weight" placeholder="65.0" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Height (cm)</label>
                        <input type="text" id="triage_height" placeholder="165" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Blood Sugar</label>
                        <input type="text" id="triage_blood_sugar" placeholder="100" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Blood Sugar Type</label>
                        <select id="triage_blood_sugar_type" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="">Select type</option>
                            <option value="fasting">Fasting</option>
                            <option value="random">Random</option>
                            <option value="post_prandial">Post Prandial</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">GCS Eye</label>
                        <input type="number" id="triage_gcs_eye" min="1" max="4" placeholder="1-4" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">GCS Verbal</label>
                        <input type="number" id="triage_gcs_verbal" min="1" max="5" placeholder="1-5" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">GCS Motor</label>
                        <input type="number" id="triage_gcs_motor" min="1" max="6" placeholder="1-6" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
            </div>

            <!-- Symptoms -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Symptoms</label>
                <div class="flex flex-wrap gap-2" id="symptomCheckboxes">
                    <?php 
                        $symptoms = ['Headache', 'Fever', 'Cough', 'Chest pain', 'Shortness of breath', 'Nausea', 'Dizziness', 'Body aches', 'Fatigue', 'Palpitations', 'Blurred vision', 'Loss of appetite'];
                        foreach ($symptoms as $symptom):
                    ?>
                    <label class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs hover:bg-brand-light/40 cursor-pointer transition has-[:checked]:border-brand-medium has-[:checked]:bg-brand-light/40">
                        <input type="checkbox" value="<?php echo $symptom; ?>" class="accent-brand-dark"> <?php echo $symptom; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Chief Complaint</label>
                <textarea id="triage_complaint" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Describe the main reason for visit..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority</label>
                <select id="triage_priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="low">🟢 Low</option>
                    <option value="medium" selected>🟡 Medium</option>
                    <option value="high">🟠 High</option>
                    <option value="critical">🔴 Critical</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Nurse Assigned</label>
                <select id="triage_nurse" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <?php foreach ($nurses as $n): ?>
                        <option value="<?php echo $n['id']; ?>"><?php echo htmlspecialchars($n['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('addTriageModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Add to Queue
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const TRIAGE_DATA = <?php echo json_encode(array_column($triageQueue, null, 'id'), JSON_PRETTY_PRINT); ?>;
    let selectedSymptoms = [];

    // ============================================================
    // PATIENT CHECK-IN (NEW)
    // ============================================================
    async function checkInPatient(event) {
        event.preventDefault();
        
        const patientId = document.getElementById('checkin_patient').value;
        const queueNumber = document.getElementById('checkin_queue').value;
        
        if (!patientId) {
            ModalSystem.toast.warning('Please select a patient');
            return;
        }
        
        try {
            const res = await fetch('../../api/triage-queue.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    patient_id: parseInt(patientId),
                    queue_number: queueNumber
                })
            });
            
            const data = await res.json();
            
            if (data.success) {
                ModalSystem.toast.success(`Patient checked in! Queue #: ${data.queue_number}`);
                setTimeout(() => {
                    ModalSystem.close('checkInModal');
                    window.location.reload();
                }, 1000);
            } else {
                ModalSystem.toast.error(data.message || 'Check-in failed');
            }
        } catch (err) {
            ModalSystem.toast.error('Network error during check-in');
        }
    }

    // ============================================================
    // VIEW TRIAGE
    // ============================================================
    function viewTriage(id) {
        ModalSystem.open('viewTriageModal');
        const t = TRIAGE_DATA[id];
        if (!t) return;

        setTimeout(() => {
            const priorityColors = {
                critical: 'bg-rose-100 text-rose-700 border-rose-200',
                high: 'bg-orange-100 text-orange-700 border-orange-200',
                medium: 'bg-yellow-100 text-yellow-700 border-yellow-200',
                low: 'bg-green-100 text-green-700 border-green-200'
            };
            const statusColors = {
                waiting: 'bg-amber-100 text-amber-700',
                in_triage: 'bg-brand-light text-brand-dark border border-brand-border',
                completed: 'bg-slate-100 text-slate-500',
                sent_to_doctor: 'bg-emerald-100 text-emerald-700'
            };

            const symptomsHtml = t.symptoms.map(s => `<span class="px-2 py-1 bg-slate-100 rounded-full text-xs">${s}</span>`).join('');

            document.getElementById('triageDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-lg flex-shrink-0">
                            ${t.patient_avatar}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${t.patient_name}</h4>
                            <p class="text-sm text-slate-500">Queue #${t.queue_number} • Arrived: ${t.arrival_time}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 border ${priorityColors[t.priority] || priorityColors.medium}">
                                ${t.priority.toUpperCase()} PRIORITY
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Age</p><p class="text-sm text-slate-800">${t.age} yrs</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Gender</p><p class="text-sm text-slate-800">${t.gender}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Wait Time</p><p class="text-sm text-slate-800">${t.wait_time}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Status</p><p class="text-sm"><span class="px-2 py-0.5 rounded-full text-xs font-semibold ${statusColors[t.status] || statusColors.waiting}">${t.status.replace('_', ' ').toUpperCase()}</span></p></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Vital Signs</h5>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div><p class="text-[10px] text-slate-400">BP</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.blood_pressure}</p></div>
                            <div><p class="text-[10px] text-slate-400">Heart Rate</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.heart_rate} bpm</p></div>
                            <div><p class="text-[10px] text-slate-400">Temperature</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.temperature}°C</p></div>
                            <div><p class="text-[10px] text-slate-400">O2 Saturation</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.oxygen_saturation}%</p></div>
                            <div><p class="text-[10px] text-slate-400">Respiratory Rate</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.respiratory_rate}</p></div>
                            <div><p class="text-[10px] text-slate-400">Weight</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.weight} kg</p></div>
                            <div><p class="text-[10px] text-slate-400">Height</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.height} cm</p></div>
                            <div><p class="text-[10px] text-slate-400">Blood Sugar</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.blood_sugar} mg/dL</p></div>
                            <div><p class="text-[10px] text-slate-400">GCS</p><p class="text-sm font-semibold text-slate-800">Eye ${t.vital_signs.gcs_eye}, Verbal ${t.vital_signs.gcs_verbal}, Motor ${t.vital_signs.gcs_motor} (Total ${t.vital_signs.gcs_total})</p></div>
                            <div><p class="text-[10px] text-slate-400">BMI</p><p class="text-sm font-semibold text-slate-800">${t.vital_signs.bmi}</p></div>
                        </div>
                    </div>
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Symptoms</h5>
                        <div class="flex flex-wrap gap-2">${symptomsHtml}</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Chief Complaint</h5>
                        <p class="text-sm text-slate-800">${t.chief_complaint}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="ModalSystem.close('viewTriageModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <button onclick="ModalSystem.close('viewTriageModal'); editTriage(${t.id})" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-pen mr-1.5"></i> Edit</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // EDIT TRIAGE
    // ============================================================
    function editTriage(id) {
        const t = TRIAGE_DATA[id];
        if (!t) {
            ModalSystem.toast.error('Triage record not found');
            return;
        }

        // Fill the edit form with current data
        document.getElementById('edit_triage_id').value = id;
        document.getElementById('edit_patient_name').textContent = t.patient_name + ' (#' + t.triage_id + ')';
        document.getElementById('edit_triage_bp').value = t.vital_signs.blood_pressure;
        document.getElementById('edit_triage_hr').value = t.vital_signs.heart_rate;
        document.getElementById('edit_triage_temp').value = t.vital_signs.temperature;
        document.getElementById('edit_triage_o2').value = t.vital_signs.oxygen_saturation;
        document.getElementById('edit_triage_rr').value = t.vital_signs.respiratory_rate;
        document.getElementById('edit_triage_weight').value = t.vital_signs.weight;
        document.getElementById('edit_triage_height').value = t.vital_signs.height;
        document.getElementById('edit_triage_blood_sugar').value = t.vital_signs.blood_sugar !== 'N/A' ? t.vital_signs.blood_sugar : '';
        document.getElementById('edit_triage_gcs_eye').value = t.vital_signs.gcs_eye || '';
        document.getElementById('edit_triage_gcs_verbal').value = t.vital_signs.gcs_verbal || '';
        document.getElementById('edit_triage_gcs_motor').value = t.vital_signs.gcs_motor || '';
        document.getElementById('edit_triage_complaint').value = t.chief_complaint;
        document.getElementById('edit_triage_priority').value = t.priority;
        document.getElementById('edit_triage_status').value = t.status === 'waiting' ? 'pending' : t.status === 'in_triage' ? 'triaged' : t.status === 'sent_to_doctor' ? 'consulted' : 'cancelled';

        // Set nurse
        const nurseSelect = document.getElementById('edit_triage_nurse');
        for (let opt of nurseSelect.options) {
            if (opt.text.includes(t.nurse_assigned.replace('Nurse ', ''))) {
                opt.selected = true;
                break;
            }
        }

        // Check symptoms
        document.querySelectorAll('#editSymptomCheckboxes input[type="checkbox"]').forEach(cb => {
            cb.checked = t.symptoms.includes(cb.value);
        });

        ModalSystem.open('editTriageModal');
    }

    // ============================================================
    // UPDATE TRIAGE (Save Edit)
    // ============================================================
    async function updateTriage(event) {
        event.preventDefault();

        const id = document.getElementById('edit_triage_id').value;
        if (!id) return;

        const selectedSymptoms = Array.from(document.querySelectorAll('#editSymptomCheckboxes input[type="checkbox"]:checked')).map(cb => cb.value);

        const payload = {
            blood_pressure: document.getElementById('edit_triage_bp').value,
            heart_rate: document.getElementById('edit_triage_hr').value ? parseInt(document.getElementById('edit_triage_hr').value) : null,
            temperature: document.getElementById('edit_triage_temp').value ? parseFloat(document.getElementById('edit_triage_temp').value) : null,
            respiratory_rate: document.getElementById('edit_triage_rr').value ? parseInt(document.getElementById('edit_triage_rr').value) : null,
            oxygen_saturation: document.getElementById('edit_triage_o2').value ? parseInt(document.getElementById('edit_triage_o2').value) : null,
            weight: document.getElementById('edit_triage_weight').value ? parseFloat(document.getElementById('edit_triage_weight').value) : null,
            height: document.getElementById('edit_triage_height').value ? parseFloat(document.getElementById('edit_triage_height').value) : null,
            blood_sugar: document.getElementById('edit_triage_blood_sugar').value ? parseFloat(document.getElementById('edit_triage_blood_sugar').value) : null,
            blood_sugar_type: document.getElementById('edit_triage_blood_sugar_type').value || null,
            gcs_eye: document.getElementById('edit_triage_gcs_eye').value ? parseInt(document.getElementById('edit_triage_gcs_eye').value) : null,
            gcs_verbal: document.getElementById('edit_triage_gcs_verbal').value ? parseInt(document.getElementById('edit_triage_gcs_verbal').value) : null,
            gcs_motor: document.getElementById('edit_triage_gcs_motor').value ? parseInt(document.getElementById('edit_triage_gcs_motor').value) : null,
            symptoms: selectedSymptoms.join(', '),
            notes: document.getElementById('edit_triage_complaint').value,
            priority: document.getElementById('edit_triage_priority').value,
            status: document.getElementById('edit_triage_status').value,
            nurse_id: parseInt(document.getElementById('edit_triage_nurse').value) || 1
        };

        try {
            const res = await fetch('../../api/triage.php?id=' + id, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                ModalSystem.toast.success('Triage record updated successfully!');
                ModalSystem.close('editTriageModal');
                setTimeout(() => window.location.reload(), 800);
            } else {
                ModalSystem.toast.error(data.message || 'Failed to update triage record');
            }
        } catch (err) {
            ModalSystem.toast.error('Network error while updating triage');
        }
    }

    // ============================================================
    // COMPLETE TRIAGE
    // ============================================================
   async function completeTriage(id) {
    ModalSystem.confirm(
        'This will mark the patient as complete and send them to the doctor.',
        async () => {
            try {
                const res = await fetch('../../api/triage.php?id=' + id + '&action=status', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: 'consulted' })
                });
                const data = await res.json();
                if (data.success) {
                    ModalSystem.toast.success('Patient sent to doctor successfully!');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    ModalSystem.toast.error(data.message || 'Failed to update triage status');
                }
            } catch (err) {
                ModalSystem.toast.error('Error updating status');
            }
        },
        { title: 'Complete Triage', confirmText: 'Send to Doctor', type: 'info' }
    );
}

    // ============================================================
    // ADD TRIAGE
    // ============================================================
    async function saveTriage(event) {
        event.preventDefault();

        const patientId = document.getElementById('triage_patient').value;
        const bp = document.getElementById('triage_bp').value;
        const hr = document.getElementById('triage_hr').value;
        const temp = document.getElementById('triage_temp').value;
        const o2 = document.getElementById('triage_o2').value;
        const rr = document.getElementById('triage_rr').value;
        const weight = document.getElementById('triage_weight').value;
        const height = document.getElementById('triage_height').value;
        const bloodSugar = document.getElementById('triage_blood_sugar').value;
        const bloodSugarType = document.getElementById('triage_blood_sugar_type').value;
        const gcsEye = document.getElementById('triage_gcs_eye').value;
        const gcsVerbal = document.getElementById('triage_gcs_verbal').value;
        const gcsMotor = document.getElementById('triage_gcs_motor').value;

        const selectedSymptoms = Array.from(document.querySelectorAll('#symptomCheckboxes input[type="checkbox"]:checked')).map(cb => cb.value);
        const complaint = document.getElementById('triage_complaint').value;
        const priority = document.getElementById('triage_priority').value;
        const nurseId = document.getElementById('triage_nurse').value;

        if (!patientId) {
            ModalSystem.toast.warning('Please select a patient');
            return;
        }

        const payload = {
            patient_id: parseInt(patientId),
            nurse_id: parseInt(nurseId) || 1,
            blood_pressure: bp,
            heart_rate: hr ? parseInt(hr) : null,
            temperature: temp ? parseFloat(temp) : null,
            respiratory_rate: rr ? parseInt(rr) : null,
            oxygen_saturation: o2 ? parseInt(o2) : null,
            weight: weight ? parseFloat(weight) : null,
            height: height ? parseFloat(height) : null,
            blood_sugar: bloodSugar ? parseFloat(bloodSugar) : null,
            blood_sugar_type: bloodSugarType || null,
            gcs_eye: gcsEye ? parseInt(gcsEye) : null,
            gcs_verbal: gcsVerbal ? parseInt(gcsVerbal) : null,
            gcs_motor: gcsMotor ? parseInt(gcsMotor) : null,
            symptoms: selectedSymptoms.join(', '),
            notes: complaint,
            priority: priority,
            status: 'pending'
        };

        try {
            const res = await fetch('../../api/triage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                ModalSystem.toast.success('Patient added to triage queue successfully!');
                ModalSystem.close('addTriageModal');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                ModalSystem.toast.error(data.message || 'Failed to save triage entry');
            }
        } catch (err) {
            ModalSystem.toast.error('Network error while saving triage');
        }
    }

    // ============================================================
    // CALL NEXT PATIENT
    // ============================================================
    function callNextPatient() {
        ModalSystem.toast.info('📢 Calling next patient: #' + (<?php echo $triageQueue[0]['queue_number'] ?? '1'; ?>));
    }

    // ============================================================
    // SYMPTOM CHECKER
    // ============================================================
    function toggleSymptom(btn) {
        btn.classList.toggle('border-brand-medium');
        btn.classList.toggle('bg-brand-light/40');
        const symptom = btn.textContent.trim();
        
        if (btn.classList.contains('border-brand-medium')) {
            if (!selectedSymptoms.includes(symptom)) {
                selectedSymptoms.push(symptom);
            }
        } else {
            selectedSymptoms = selectedSymptoms.filter(s => s !== symptom);
        }
        
        updateSymptomDisplay();
    }

    function updateSymptomDisplay() {
        const display = document.getElementById('selectedSymptomsDisplay');
        if (selectedSymptoms.length === 0) {
            display.innerHTML = '<span class="text-xs text-slate-400">No symptoms selected</span>';
            document.getElementById('symptomResult').classList.add('hidden');
        } else {
            display.innerHTML = selectedSymptoms.map(s => 
                `<span class="px-2 py-1 bg-brand-light border border-brand-border rounded-full text-xs flex items-center gap-1">
                    ${s}
                    <button onclick="removeSymptom('${s}')" class="text-slate-400 hover:text-rose-500">✕</button>
                </span>`
            ).join('');
        }
    }

    function removeSymptom(symptom) {
        selectedSymptoms = selectedSymptoms.filter(s => s !== symptom);
        document.querySelectorAll('#symptomCheckerList button').forEach(btn => {
            if (btn.textContent.trim() === symptom) {
                btn.classList.remove('border-brand-medium', 'bg-brand-light/40');
            }
        });
        updateSymptomDisplay();
    }

    function analyzeSymptoms() {
        if (selectedSymptoms.length === 0) {
            ModalSystem.toast.warning('Please select at least one symptom');
            return;
        }
        
        const result = document.getElementById('symptomResult');
        result.classList.remove('hidden');
        result.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Show different results based on symptoms
        if (selectedSymptoms.some(s => ['Chest pain', 'Shortness of breath'].includes(s))) {
            document.querySelector('#symptomResult .bg-amber-50').innerHTML = `
                <h4 class="text-sm font-bold text-rose-700 mb-2">⚠️ URGENT - Possible Cardiac Issues</h4>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-rose-200">
                        <span class="text-rose-500">•</span>
                        <span class="text-sm text-slate-700">Cardiac Event / Heart Attack</span>
                        <span class="ml-auto text-xs text-rose-600 font-semibold">85% match</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-rose-200">
                        <span class="text-rose-500">•</span>
                        <span class="text-sm text-slate-700">Angina</span>
                        <span class="ml-auto text-xs text-rose-600 font-semibold">70% match</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-rose-200">
                        <span class="text-rose-500">•</span>
                        <span class="text-sm text-slate-700">Pulmonary Embolism</span>
                        <span class="ml-auto text-xs text-rose-600 font-semibold">55% match</span>
                    </div>
                </div>
                <p class="text-xs text-rose-600 mt-3 font-bold">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    URGENT: Seek immediate medical attention!
                </p>
            `;
        } else if (selectedSymptoms.some(s => ['Fever', 'Cough', 'Body aches'].includes(s))) {
            document.querySelector('#symptomResult .bg-amber-50').innerHTML = `
                <h4 class="text-sm font-bold text-amber-700 mb-2">⚠️ Possible Respiratory Infection</h4>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-amber-100">
                        <span class="text-amber-500">•</span>
                        <span class="text-sm text-slate-700">Influenza (Flu)</span>
                        <span class="ml-auto text-xs text-amber-600 font-semibold">75% match</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-amber-100">
                        <span class="text-amber-500">•</span>
                        <span class="text-sm text-slate-700">COVID-19</span>
                        <span class="ml-auto text-xs text-amber-600 font-semibold">60% match</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-amber-100">
                        <span class="text-amber-500">•</span>
                        <span class="text-sm text-slate-700">Pneumonia</span>
                        <span class="ml-auto text-xs text-amber-600 font-semibold">45% match</span>
                    </div>
                </div>
                <p class="text-xs text-amber-600 mt-3">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Please consult a doctor for proper diagnosis.
                </p>
            `;
        } else {
            document.querySelector('#symptomResult .bg-amber-50').innerHTML = `
                <h4 class="text-sm font-bold text-slate-700 mb-2">📋 Possible Conditions</h4>
                <div class="space-y-2">
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-500">•</span>
                        <span class="text-sm text-slate-700">Stress / Anxiety</span>
                        <span class="ml-auto text-xs text-slate-500 font-semibold">50% match</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-500">•</span>
                        <span class="text-sm text-slate-700">Migraine</span>
                        <span class="ml-auto text-xs text-slate-500 font-semibold">40% match</span>
                    </div>
                    <div class="flex items-center gap-2 p-2 bg-white rounded-lg border border-slate-200">
                        <span class="text-slate-500">•</span>
                        <span class="text-sm text-slate-700">Allergies</span>
                        <span class="ml-auto text-xs text-slate-500 font-semibold">35% match</span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-3">
                    <i class="fa-solid fa-info-circle mr-1"></i>
                    Please consult a doctor for proper diagnosis.
                </p>
            `;
        }
    }

    function resetSymptomChecker() {
        selectedSymptoms = [];
        document.querySelectorAll('#symptomCheckerList button').forEach(btn => {
            btn.classList.remove('border-brand-medium', 'bg-brand-light/40');
        });
        updateSymptomDisplay();
        document.getElementById('symptomResult').classList.add('hidden');
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchTriage').addEventListener('input', filterTriage);
    document.getElementById('filterPriority').addEventListener('change', filterTriage);
    document.getElementById('filterStatus').addEventListener('change', filterTriage);

    function filterTriage() {
        const search = document.getElementById('searchTriage').value.toLowerCase();
        const priority = document.getElementById('filterPriority').value;
        const status = document.getElementById('filterStatus').value;
        let visibleCount = 0;

        document.querySelectorAll('.triage-row').forEach(row => {
            const patient = row.dataset.patient;
            const rowPriority = row.dataset.priority;
            const rowStatus = row.dataset.status;

            const matchesSearch = patient.includes(search);
            const matchesPriority = !priority || rowPriority === priority;
            const matchesStatus = !status || rowStatus === status;
            const isVisible = matchesSearch && matchesPriority && matchesStatus;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchTriage').value = '';
        document.getElementById('filterPriority').value = '';
        document.getElementById('filterStatus').value = '';
        document.querySelectorAll('.triage-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }
    
    let currentTriagePage = <?php echo $page; ?>;
    const TRIAGE_LIMIT = 5;
    let isTriageLoading = false;

    async function changePage(page) {
        if (page < 1 || isTriageLoading) return;

        isTriageLoading = true;
        showTriageLoading();

        try {
            const res = await fetch(`../../api/triage.php?page=${page}&limit=${TRIAGE_LIMIT}`);
            const data = await res.json();

            if (!data.success) {
                ModalSystem.toast.error(data.message || 'Failed to load triage queue');
                return;
            }

            const safePage = data.page ?? page;
            const safeLimit = data.limit ?? TRIAGE_LIMIT;
            const safeTotal = data.total ?? (data.data ? data.data.length : 0);
            const safeTotalPages = data.total_pages ?? Math.max(1, Math.ceil(safeTotal / safeLimit));

            currentTriagePage = safePage;
            renderTriageTable(data.data, (safePage - 1) * safeLimit);
            renderTriagePagination(safePage, safeTotalPages);
            updateShowingText(safePage, safeLimit, safeTotal);

            data.data.forEach(t => { TRIAGE_DATA[t.id] = normalizeTriageRecord(t); });

        } catch (err) {
            console.error(err);
            ModalSystem.toast.error('Network error while loading triage queue');
        } finally {
            isTriageLoading = false;
            hideTriageLoading();
        }
    }

    function showTriageLoading() {
        const tbody = document.getElementById('triageTableBody');
        if (tbody) {
            tbody.style.opacity = '0.4';
            tbody.style.pointerEvents = 'none';
        }
        document.querySelectorAll('.px-4.py-3.border-t.border-slate-200 .flex.gap-1 button').forEach(btn => {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        });

        let spinner = document.getElementById('triageLoadingSpinner');
        if (!spinner) {
            spinner = document.createElement('div');
            spinner.id = 'triageLoadingSpinner';
            spinner.className = 'flex items-center justify-center py-2 text-xs text-slate-400';
            spinner.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...';
            tbody.parentElement.parentElement.insertBefore(spinner, tbody.parentElement.nextSibling);
        }
        spinner.style.display = 'flex';
    }

    function hideTriageLoading() {
        const tbody = document.getElementById('triageTableBody');
        if (tbody) {
            tbody.style.opacity = '1';
            tbody.style.pointerEvents = 'auto';
        }
        const spinner = document.getElementById('triageLoadingSpinner');
        if (spinner) spinner.style.display = 'none';
    }

    function normalizeTriageRecord(t, index, offset) {
        const dbStatus = (t.status || 'pending').toLowerCase();
        const statusMap = { pending: 'waiting', triaged: 'in_triage', consulted: 'sent_to_doctor', cancelled: 'cancelled' };
        const status = statusMap[dbStatus] || 'in_triage';

        const weight = parseFloat(t.weight) || 65.0;
        const height = parseFloat(t.height) || 165.0;
        const bmi = (t.weight && t.height && height > 0)
            ? Math.round((weight / ((height / 100) ** 2)) * 10) / 10
            : null;

        return {
            id: t.id,
            triage_id: t.triage_id || ('TRG-' + t.id),
            patient_name: t.patient_name || 'Unknown',
            patient_avatar: t.patient_avatar || 'P',
            age: t.age ?? 'N/A',
            gender: t.gender || 'Unspecified',
            queue_number: (offset ?? 0) + (index ?? 0) + 1,
            vital_signs: {
                blood_pressure: t.blood_pressure || '120/80',
                heart_rate: t.heart_rate ?? 75,
                temperature: t.temperature ?? 36.5,
                respiratory_rate: t.respiratory_rate ?? 18,
                oxygen_saturation: t.oxygen_saturation ?? 98,
                weight: weight,
                height: height,
                blood_sugar: t.blood_sugar ?? 'N/A',
                gcs_eye: t.gcs_eye ?? 0,
                gcs_verbal: t.gcs_verbal ?? 0,
                gcs_motor: t.gcs_motor ?? 0,
                gcs_total: (t.gcs_eye ?? 0) + (t.gcs_verbal ?? 0) + (t.gcs_motor ?? 0),
                bmi: bmi
            },
            priority: (t.priority || 'medium').toLowerCase(),
            symptoms: t.symptoms_list || [],
            chief_complaint: t.chief_complaint || t.notes || 'General Checkup',
            nurse_assigned: t.nurse_name || 'Nurse Maria Cruz',
            status: status,
            arrival_time: t.created_at ? new Date(t.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '',
            wait_time: '15 mins'
        };
    }

    function renderTriageTable(rawList, offset) {
        const tbody = document.getElementById('triageTableBody');
        const priorityColors = {
            critical: 'bg-rose-100 text-rose-700 border-rose-200',
            high: 'bg-orange-100 text-orange-700 border-orange-200',
            medium: 'bg-yellow-100 text-yellow-700 border-yellow-200',
            low: 'bg-green-100 text-green-700 border-green-200'
        };
        const priorityIcons = { critical: '🔴', high: '🟠', medium: '🟡', low: '🟢' };
        const statusClasses = {
            in_triage: 'bg-brand-light text-brand-dark border border-brand-border',
            waiting: 'bg-amber-100 text-amber-700',
            sent_to_doctor: 'bg-emerald-100 text-emerald-700',
            completed: 'bg-slate-100 text-slate-500'
        };

        if (rawList.length === 0) {
            tbody.innerHTML = '';
            document.getElementById('emptyState').style.display = 'flex';
            return;
        }
        document.getElementById('emptyState').style.display = 'none';

        tbody.innerHTML = rawList.map((raw, i) => {
            const t = normalizeTriageRecord(raw, i, offset);
            return `
            <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors triage-row ${t.priority === 'critical' ? 'bg-rose-50/50' : ''}"
                data-patient="${t.patient_name.toLowerCase()}" data-priority="${t.priority}" data-status="${t.status}">
                <td class="px-4 py-3 font-mono text-xs font-bold ${t.priority === 'critical' ? 'text-rose-600' : 'text-slate-400'}">#${t.queue_number}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">${t.patient_avatar}</div>
                        <div><p class="font-semibold text-slate-800 text-sm">${t.patient_name}</p><p class="text-xs text-slate-400">${t.chief_complaint}</p></div>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">${t.age} yrs<br>${t.gender}</td>
                <td class="px-4 py-3">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2 text-xs"><span class="text-slate-400">BP:</span><span class="font-medium text-slate-700">${t.vital_signs.blood_pressure}</span></div>
                        <div class="flex items-center gap-2 text-xs"><span class="text-slate-400">HR:</span><span class="font-medium text-slate-700">${t.vital_signs.heart_rate}</span><span class="text-slate-400 ml-1">Temp:</span><span class="font-medium text-slate-700">${t.vital_signs.temperature}°C</span></div>
                        <div class="flex items-center gap-2 text-xs"><span class="text-slate-400">O2:</span><span class="font-medium text-slate-700">${t.vital_signs.oxygen_saturation}%</span></div>
                    </div>
                </td>
                <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-semibold border ${priorityColors[t.priority] || priorityColors.medium}">${priorityIcons[t.priority] || ''} ${t.priority.charAt(0).toUpperCase() + t.priority.slice(1)}</span></td>
                <td class="px-4 py-3"><span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClasses[t.status] || statusClasses.waiting}">${t.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}</span></td>
                <td class="px-4 py-3 text-slate-600 text-xs">${t.wait_time}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="viewTriage(${t.id})" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View"><i class="fa-solid fa-eye text-sm"></i></button>
                        <button onclick="editTriage(${t.id})" class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit"><i class="fa-solid fa-pen text-sm"></i></button>
                        ${(t.status === 'in_triage' || t.status === 'waiting') ? `<button onclick="completeTriage(${t.id})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Complete & Send to Doctor"><i class="fa-solid fa-check text-sm"></i></button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
    }

    function renderTriagePagination(page, totalPages) {
        const container = document.querySelector('.px-4.py-3.border-t.border-slate-200 .flex.gap-1');
        if (!container) return;
        let html = `<button onclick="changePage(${page - 1})" class="px-3 py-1.5 rounded-lg text-sm ${page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${page <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left text-xs"></i></button>`;
        for (let i = 1; i <= totalPages; i++) {
            html += `<button onclick="changePage(${i})" class="px-3 py-1.5 rounded-lg text-sm font-medium ${i === page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}">${i}</button>`;
        }
        html += `<button onclick="changePage(${page + 1})" class="px-3 py-1.5 rounded-lg text-sm ${page >= totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${page >= totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right text-xs"></i></button>`;
        container.innerHTML = html;
    }

    function updateShowingText(page, limit, total) {
        const startEl = document.querySelector('.px-4.py-3.border-t.border-slate-200 p.text-xs span:nth-child(1)');
        const endEl = document.querySelector('.px-4.py-3.border-t.border-slate-200 p.text-xs span:nth-child(2)');
        const totalEl = document.querySelector('.px-4.py-3.border-t.border-slate-200 p.text-xs span:nth-child(3)');
        const offset = (page - 1) * limit;
        if (startEl) startEl.textContent = total === 0 ? 0 : offset + 1;
        if (endEl) endEl.textContent = Math.min(offset + limit, total);
        if (totalEl) totalEl.textContent = total;
    }
</script>

<?php include_once '../../includes/footer.php'; ?>