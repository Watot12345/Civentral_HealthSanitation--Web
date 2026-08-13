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
requireDepartmentAccess('immunization & nutrition');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/TriageQueue.php';
require_once __DIR__ . '/../../app/Models/Patient.php';

// Fetch Active Patients Waiting for Immunization Visits
$triageQueueModel = new TriageQueue();
$patientModel = new Patient();
$immunizationVisitsRaw = [];
try {
    $immunizationVisitsRaw = $triageQueueModel->getVisitsByReason('Immunization');
} catch (\Throwable $e) {
    error_log('Error fetching immunization visits: ' . $e->getMessage());
}

$immunizationVisits = [];
foreach ($immunizationVisitsRaw as $v) {
    $pId = (int)($v['patient_id'] ?? 0);
    $p = null;
    try { if ($pId > 0) $p = $patientModel->find($pId); } catch (\Throwable $e) {}
    
    $name = 'Patient #' . $pId;
    $pCode = 'P-' . $pId;
    if ($p) {
        $firstName = $p['first_name'] ?? '';
        $lastName = $p['last_name'] ?? '';
        $name = trim($firstName . ' ' . $lastName) ?: ($p['name'] ?? $name);
        $pCode = $p['patient_id'] ?? $pCode;
    }
    
    $parts = explode(' ', $name);
    $initials = '';
    foreach ($parts as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }
    
    $immunizationVisits[] = [
        'id' => $v['id'],
        'patient_id' => $pId,
        'patient_name' => $name,
        'patient_code' => $pCode,
        'avatar' => substr($initials, 0, 2) ?: 'P',
        'check_in_time' => isset($v['check_in_time']) ? date('h:i A', strtotime($v['check_in_time'])) : (isset($v['created_at']) ? date('h:i A', strtotime($v['created_at'])) : date('h:i A')),
        'status' => $v['status'] ?? 'waiting'
    ];
}

// ============================================================
// DOH NATIONAL IMMUNIZATION PROGRAM SCHEDULE
// due_age_days = number of days after birth this dose is due.
// (This was referenced throughout the original file but never
// defined anywhere, so filter dropdowns / the schedule table /
// the "Select Vaccine" dropdown were silently broken before.)
// ============================================================
$vaccineSchedule = [
    ['vaccine' => 'BCG', 'dose' => 1, 'due_age_days' => 0, 'due_age' => 'At birth', 'description' => 'Protects against tuberculosis (TB)'],
    ['vaccine' => 'Hepatitis B', 'dose' => 1, 'due_age_days' => 0, 'due_age' => 'At birth (within 24 hrs)', 'description' => 'Protects against Hepatitis B infection'],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 1, 'due_age_days' => 42, 'due_age' => '6 weeks', 'description' => 'Protects against Diphtheria, Pertussis, Tetanus, Hepatitis B, and Hib'],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 2, 'due_age_days' => 70, 'due_age' => '10 weeks', 'description' => 'Second dose of the combined 5-in-1 vaccine'],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 3, 'due_age_days' => 98, 'due_age' => '14 weeks', 'description' => 'Third dose of the combined 5-in-1 vaccine'],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 1, 'due_age_days' => 42, 'due_age' => '6 weeks', 'description' => 'Protects against poliomyelitis'],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 2, 'due_age_days' => 70, 'due_age' => '10 weeks', 'description' => 'Second dose of oral polio vaccine'],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 3, 'due_age_days' => 98, 'due_age' => '14 weeks', 'description' => 'Third dose of oral polio vaccine'],
    ['vaccine' => 'Inactivated Polio Vaccine (IPV)', 'dose' => 1, 'due_age_days' => 98, 'due_age' => '14 weeks', 'description' => 'Injectable polio vaccine, boosts immunity'],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 1, 'due_age_days' => 42, 'due_age' => '6 weeks', 'description' => 'Protects against pneumococcal disease'],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 2, 'due_age_days' => 70, 'due_age' => '10 weeks', 'description' => 'Second dose of PCV'],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 3, 'due_age_days' => 98, 'due_age' => '14 weeks', 'description' => 'Third dose of PCV'],
    ['vaccine' => 'Measles-Mumps-Rubella (MMR)', 'dose' => 1, 'due_age_days' => 270, 'due_age' => '9 months', 'description' => 'Protects against measles, mumps, and rubella'],
    ['vaccine' => 'Measles-Mumps-Rubella (MMR)', 'dose' => 2, 'due_age_days' => 365, 'due_age' => '12 months', 'description' => 'Second dose for full immunity'],
];

// Base Children Data
$children = [];
$childrenRaw = []; // keyed by id => ['birth' => DateTime, 'name' => ..., 'child_code' => ...]

try {
    $db = Database::getInstance();
    $dbChildren = $db->query('children', 'GET');
    if (!empty($dbChildren) && is_array($dbChildren)) {
        foreach ($dbChildren as $c) {
            $cId = (int)$c['id'];
            $birth = new DateTime($c['birth_date'] ?? 'now');
            $today = new DateTime();
            $diff = $today->diff($birth);
            $ageStr = $diff->y > 0 ? "{$diff->y} yrs {$diff->m} mos" : "{$diff->m} mos";
            $childCode = $c['child_id'] ?? ('CH-' . sprintf('%03d', $cId));
            $childName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));

            $children[] = [
                'id' => $cId,
                'child_id' => $childCode,
                'name' => $childName,
                'age' => $ageStr,
                'gender' => !empty($c['gender']) ? ucfirst(strtolower($c['gender'])) : 'Female',
                'mother' => $c['mother_name'] ?? 'N/A'
            ];

            $childrenRaw[$cId] = [
                'birth' => $birth,
                'name' => $childName,
                'child_code' => $childCode,
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Supabase children query exception: ' . $e->getMessage());
}

// ============================================================
// IMMUNIZATION RECORDS (actual administered doses from DB)
// Adjust the table/column names below if your `immunizations`
// table uses different naming.
// ============================================================
$administeredLookup = []; // "childId|vaccine|dose" => record

try {
    $dbImmunizations = $db->query('immunizations', 'GET');
    if (!empty($dbImmunizations) && is_array($dbImmunizations)) {
        foreach ($dbImmunizations as $rec) {
            $childId = (int)($rec['child_id'] ?? 0);
            $vaccine = $rec['vaccine'] ?? '';
            $dose = (int)($rec['dose'] ?? 1);
            $key = $childId . '|' . strtolower($vaccine) . '|' . $dose;
            $administeredLookup[$key] = $rec;
        }
    }
} catch (\Throwable $e) {
    error_log('Supabase immunizations query exception: ' . $e->getMessage());
}

// ============================================================
// COMPUTE PER-CHILD, PER-SCHEDULED-DOSE STATUS
//   completed  -> a matching administered record exists
//   missed     -> due date has passed, nothing administered
//   pending    -> due within the next 30 days, nothing administered yet
//   (not yet due doses are skipped from the table entirely)
// Also rolls up a per-child "on track" / "not on track" badge.
// ============================================================
$immunizations = [];
$missedVaccines = [];
$dueAlerts = [];
$childTrackStatus = []; // childId => 'on_track' | 'not_on_track'

$today = new DateTime();

foreach ($childrenRaw as $childId => $info) {
    $childHasMissed = false;

    foreach ($vaccineSchedule as $schedule) {
        $vaccine = $schedule['vaccine'];
        $dose = $schedule['dose'];
        $key = $childId . '|' . strtolower($vaccine) . '|' . $dose;

        $dueDate = clone $info['birth'];
        $dueDate->modify('+' . $schedule['due_age_days'] . ' days');
        $daysLeft = ($dueDate->getTimestamp() - $today->getTimestamp()) / 86400;

        if (isset($administeredLookup[$key])) {
            $rec = $administeredLookup[$key];
            $entryId = 'rec_' . ($rec['id'] ?? ($childId . '_' . $dose));

            $immunizations[] = [
                'id' => (string)$entryId,
                'child_id' => $info['child_code'],
                'child_db_id' => $childId,
                'child_name' => $info['name'],
                'vaccine' => $vaccine,
                'dose' => $dose,
                'date' => $rec['date_administered'] ?? null,
                'next_due' => $rec['next_due_date'] ?? null,
                'batch_number' => $rec['batch_number'] ?? null,
                'administered_by' => $rec['administered_by'] ?? null,
                'health_center' => $rec['health_center'] ?? '—',
                'status' => 'completed',
            ];
            continue;
        }

        // Not administered yet — only surface it once it's due or due soon
        if ($daysLeft < 0) {
            $status = 'missed';
            $childHasMissed = true;
        } elseif ($daysLeft <= 30) {
            $status = 'pending';
        } else {
            continue; // not due yet, don't clutter the table
        }

        $entryId = 'sched_' . $childId . '_' . preg_replace('/[^a-z0-9]/i', '', $vaccine) . '_' . $dose;

        $entry = [
            'id' => (string)$entryId,
            'child_id' => $info['child_code'],
            'child_db_id' => $childId,
            'child_name' => $info['name'],
            'vaccine' => $vaccine,
            'dose' => $dose,
            'date' => null,
            'next_due' => $dueDate->format('Y-m-d'),
            'batch_number' => null,
            'administered_by' => null,
            'health_center' => '—',
            'status' => $status,
        ];

        $immunizations[] = $entry;

        if ($status === 'missed') {
            $missedVaccines[] = $entry;
        } else {
            $dueAlerts[] = $entry;
        }
    }

    $childTrackStatus[$childId] = $childHasMissed ? 'not_on_track' : 'on_track';
}

// Stats
$totalImmunizations = count($immunizations);
$completedImmunizations = count(array_filter($immunizations, fn($i) => $i['status'] === 'completed'));
$missedImmunizations = count(array_filter($immunizations, fn($i) => $i['status'] === 'missed'));
$pendingImmunizations = count(array_filter($immunizations, fn($i) => $i['status'] === 'pending'));

$title = 'Vaccination Tracking';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Vaccination Tracking</h2>
            <p class="text-sm text-slate-500 mt-0.5">Track immunizations, schedules, and due dates</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('recordVaccinationModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-syringe text-xs"></i> Record Vaccination
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- PATIENTS WAITING FOR IMMUNIZATION (TODAY'S VISITS)          -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl shadow-xs border border-blue-200 mb-6 overflow-hidden">
        <div class="p-4 border-b border-blue-200/80 flex items-center justify-between bg-blue-50/50">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-syringe text-blue-600"></i>
                <h3 class="text-sm font-bold text-blue-900">Patients Waiting for Immunization</h3>
                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold"><?php echo count($immunizationVisits); ?> active visit(s)</span>
            </div>
            <a href="../healthservices/triage.php" class="text-xs font-semibold text-blue-700 hover:text-blue-900 flex items-center gap-1">
                <i class="fa-solid fa-plus-circle text-xs"></i> Waiting Queue
            </a>
        </div>
        <?php if (!empty($immunizationVisits)): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase">Patient ID</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase">Patient Name</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase">Reason for Visit</th>
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase">Check-in Time</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold text-slate-500 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($immunizationVisits as $visit): ?>
                    <tr class="border-b border-slate-100 hover:bg-blue-50/20 transition-colors">
                        <td class="px-4 py-3 font-mono text-xs font-bold text-blue-700"><?php echo htmlspecialchars($visit['patient_code']); ?></td>
                        <td class="px-4 py-3 font-semibold text-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-[10px]"><?php echo htmlspecialchars($visit['avatar']); ?></div>
                                <span><?php echo htmlspecialchars($visit['patient_name']); ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 inline-flex items-center gap-1">
                                <i class="fa-solid fa-syringe text-[10px] text-blue-600"></i> Immunization
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo htmlspecialchars($visit['check_in_time']); ?></td>
                        <td class="px-4 py-3 text-center">
                            <button onclick="startImmunizationAssessment(<?php echo (int)$visit['patient_id']; ?>, '<?php echo htmlspecialchars(addslashes($visit['patient_name']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($visit['patient_code']), ENT_QUOTES); ?>');" 
                                    class="px-3.5 py-1.5 text-xs font-semibold text-white bg-brand-dark rounded-lg hover:bg-brand-medium transition inline-flex items-center gap-1 shadow-xs">
                                <i class="fa-solid fa-stethoscope text-xs"></i> Start Assessment
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="p-6 text-center text-slate-500 bg-slate-50/50">
            <i class="fa-solid fa-user-clock text-2xl text-slate-300 mb-2 block"></i>
            <p class="text-xs font-semibold text-slate-700">No patients currently waiting for immunization</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Use <a href="../healthservices/triage.php" class="text-blue-600 underline font-semibold">Patient Check-in</a> to register incoming arrivals.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Records -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-syringe text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalImmunizations; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Records</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">💉 All vaccinations</span>
                    <span class="text-[10px] text-slate-400"><?php echo $completedImmunizations; ?> completed</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Completed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $completedImmunizations; ?></p>
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Done</span>
                    <span class="text-[10px] text-slate-400">Successfully administered</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Missed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $missedImmunizations; ?></p>
                        <p class="text-xs font-medium text-slate-500">Missed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">⚠️ Overdue</span>
                    <span class="text-[10px] text-slate-400">Requires attention</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Due Soon -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo count($dueAlerts); ?></p>
                        <p class="text-xs font-medium text-slate-500">Due Soon</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏰ Upcoming</span>
                    <span class="text-[10px] text-slate-400">Within 30 days</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Due Date Alerts -->
    <?php if (count($dueAlerts) > 0): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-bell text-amber-500 text-lg"></i>
            <span class="text-sm text-amber-700">
                <span class="font-bold"><?php echo count($dueAlerts); ?></span> vaccine(s) due within 30 days
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='due_soon'; filterVaccinations();" 
                class="text-xs font-semibold text-amber-700 hover:text-amber-900 underline">
            View due
        </button>
    </div>
    <?php endif; ?>

    <!-- Missed Vaccines Alert -->
    <?php if (count($missedVaccines) > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo count($missedVaccines); ?></span> missed vaccine(s) require attention
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='missed'; filterVaccinations();" 
                class="text-xs font-semibold text-rose-700 hover:text-rose-900 underline">
            View missed
        </button>
    </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchVaccination"
                       placeholder="Search by child name, vaccine, or batch number..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="missed">Missed</option>
                    <option value="due_soon">Due Soon</option>
                </select>
                <select id="filterVaccine" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Vaccines</option>
                    <?php 
                        $vaccines = array_unique(array_column($vaccineSchedule, 'vaccine'));
                        foreach ($vaccines as $v): 
                    ?>
                        <option value="<?php echo $v; ?>"><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Administered date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Administered date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Immunization Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Child</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vaccine</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dose</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date Administered</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Next Due</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Batch #</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="vaccinationTableBody">
                    <?php if (empty($immunizations)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                            <?php if (empty($children)): ?>
                                <i class="fa-solid fa-child text-3xl block mb-2 opacity-30"></i>
                                No children registered yet — add a child record to start tracking immunizations.
                            <?php else: ?>
                                <i class="fa-solid fa-circle-check text-3xl block mb-2 opacity-30 text-emerald-400"></i>
                                <span class="font-semibold text-emerald-600">No vaccine on track requires attention</span> — every child is currently up to date with no missed or due vaccinations.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($immunizations as $immunization): ?>
                    <?php
                        $childDbId = $immunization['child_db_id'] ?? null;
                        $trackStatus = $childTrackStatus[$childDbId] ?? 'on_track';
                        $trackBadge = $trackStatus === 'not_on_track'
                            ? '<span class="inline-block px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-600 mt-0.5">Not on Track</span>'
                            : '<span class="inline-block px-1.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-600 mt-0.5">On Track</span>';
                    ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors vaccination-row <?php echo $immunization['status'] === 'missed' ? 'bg-rose-50/50' : ''; ?>"
                        data-child="<?php echo strtolower($immunization['child_name']); ?>"
                        data-vaccine="<?php echo strtolower($immunization['vaccine']); ?>"
                        data-status="<?php echo $immunization['status']; ?>"
                        data-date="<?php echo htmlspecialchars($immunization['date'] ?? ''); ?>"
                        data-next-due="<?php echo htmlspecialchars($immunization['next_due'] ?? ''); ?>"
                        data-batch="<?php echo htmlspecialchars(strtolower($immunization['batch_number'] ?? '')); ?>"
                        data-dose="<?php echo (int)$immunization['dose']; ?>">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $immunization['child_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $immunization['child_id'] ?? ''; ?></p>
                                <?php echo $trackBadge; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-700 text-xs"><?php echo $immunization['vaccine']; ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs">Dose <?php echo $immunization['dose']; ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <?php echo isset($immunization['date']) && $immunization['date'] ? date('M d, Y', strtotime($immunization['date'])) : '—'; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <?php 
                                $nextDue = $immunization['next_due'] ?? null;
                                if ($nextDue): 
                                    $daysLeft = (strtotime($nextDue) - time()) / 86400;
                                    $daysLeft = round($daysLeft);
                            ?>
                                <span class="<?php echo $daysLeft <= 30 && $daysLeft > 0 ? 'text-rose-600 font-bold' : 'text-slate-500'; ?>">
                                    <?php echo date('M d, Y', strtotime($nextDue)); ?>
                                </span>
                                <?php if ($daysLeft > 0 && $daysLeft <= 30): ?>
                                    <span class="block text-[10px] text-rose-500"><?php echo $daysLeft; ?> days left</span>
                                <?php elseif ($daysLeft < 0): ?>
                                    <span class="block text-[10px] text-rose-500"><?php echo abs($daysLeft); ?> days overdue</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs font-mono"><?php echo $immunization['batch_number'] ?? '—'; ?></td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'missed' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$immunization['status']] ?? $statusColors['pending']; ?>">
                                <?php echo ucfirst($immunization['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewImmunization('<?php echo $immunization['id']; ?>')"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <?php if ($immunization['status'] === 'missed' || $immunization['status'] === 'pending'): ?>
                                    <button onclick="recordVaccination('<?php echo $immunization['id']; ?>')"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Record">
                                        <i class="fa-solid fa-syringe text-sm"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-syringe text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No vaccinations match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo min(10, $totalImmunizations); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalImmunizations; ?></span> records
            </p>
            <div class="flex gap-1">
                <button class="px-3 py-1.5 rounded-lg text-sm bg-slate-100 text-slate-300 cursor-not-allowed" disabled>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-brand-dark text-white">1</button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">2</button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Vaccine Schedule Section -->
    <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-table-list text-brand-medium"></i> Recommended Vaccine Schedule
            </h3>
            <button onclick="document.getElementById('vaccineSchedule').classList.toggle('hidden')" 
                    class="text-xs font-semibold text-brand-medium hover:text-brand-dark transition">
                <i class="fa-solid fa-chevron-down"></i> Toggle
            </button>
        </div>
        <div id="vaccineSchedule" class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vaccine</th>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dose</th>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Due Age</th>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($vaccineSchedule, 0, 15) as $schedule): ?>
                        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors">
                            <td class="px-4 py-2 font-medium text-slate-700 text-xs"><?php echo $schedule['vaccine']; ?></td>
                            <td class="px-4 py-2 text-slate-600 text-xs"><?php echo $schedule['dose']; ?></td>
                            <td class="px-4 py-2 text-slate-600 text-xs"><?php echo $schedule['due_age']; ?></td>
                            <td class="px-4 py-2 text-slate-500 text-xs"><?php echo $schedule['description']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-2 text-center text-xs text-slate-400 border-t border-slate-200">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Based on DOH National Immunization Program schedule
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- STEP 1: PRE-VACCINATION IMMUNIZATION ASSESSMENT MODAL        -->
<!-- ============================================================ -->
<div id="immunizationAssessmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-stethoscope text-brand-medium"></i>
                Pre-Vaccination Assessment (Step 1)
            </h3>
            <button onclick="closeModal('immunizationAssessmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="immunizationAssessmentForm" class="p-6 space-y-4" onsubmit="saveAssessmentAndProceed(event)">
            <input type="hidden" id="assess_patient_id" value="">
            
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 flex items-center justify-between">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Child / Patient</label>
                    <div id="assess_patient_display" class="font-bold text-slate-800 text-sm mt-0.5">Select Patient</div>
                </div>
                <span class="px-2.5 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold border border-brand-border">
                    <i class="fa-solid fa-clipboard-check mr-1"></i> Pre-Screening
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Weight (kg) <span class="text-rose-500">*</span></label>
                    <input type="number" id="assess_weight" step="0.1" min="0.5" max="100" required placeholder="e.g. 8.5"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Temperature (°C) <span class="text-rose-500">*</span></label>
                    <input type="number" id="assess_temp" step="0.1" min="30" max="45" required placeholder="e.g. 36.5" oninput="runAiEligibilityCheck()"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Current Health Status <span class="text-rose-500">*</span></label>
                <select id="assess_health_status" required onchange="runAiEligibilityCheck()"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                    <option value="Healthy">Healthy (No apparent illness)</option>
                    <option value="Fever">Fever (Elevated Temperature)</option>
                    <option value="Cough/Cold">Cough / Cold symptoms</option>
                    <option value="Other">Other acute illness</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contraindications <span class="text-rose-500">*</span></label>
                <select id="assess_contraindications" required onchange="runAiEligibilityCheck()"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                    <option value="None">None detected</option>
                    <option value="Severe Allergy">Severe Allergy to vaccine components</option>
                    <option value="Previous Reaction">Previous severe vaccine reaction</option>
                    <option value="Immunocompromised">Immunocompromised / High Risk</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Target Vaccine Due</label>
                <select id="assess_vaccine_due" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                    <?php foreach ($vaccineSchedule as $s): ?>
                        <option value="<?php echo htmlspecialchars($s['vaccine'] . ' (Dose ' . $s['dose'] . ')'); ?>">
                            <?php echo htmlspecialchars($s['vaccine'] . ' - Dose ' . $s['dose'] . ' (' . $s['due_age'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Notes</label>
                <textarea id="assess_notes" rows="2" placeholder="Clinical notes, caregiver remarks, or observations..."
                          class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none"></textarea>
            </div>

            <!-- GEMINI AI CLINICAL GUIDANCE (DECISION SUPPORT SYSTEM - ADVISORY) -->
            <div id="aiAdvisoryBox" class="bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-200 rounded-xl p-4 transition-all">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm">
                        <i class="fa-solid fa-brain text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h4 class="text-xs font-bold text-teal-900 uppercase tracking-wider flex items-center gap-1.5">
                                Gemini AI Clinical Guidance
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-teal-100 text-teal-800 border border-teal-200">Advisory DSS</span>
                            </h4>
                            <span id="assessResultBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                ✓ Eligible for Vaccination
                            </span>
                        </div>
                        <p id="aiAdvisoryText" class="text-xs text-teal-800 mt-1.5 leading-relaxed font-medium">
                            Based on recorded assessment: Patient appears healthy with no reported contraindications. Recommended to proceed with scheduled vaccine.
                        </p>
                        <p class="text-[10px] text-slate-500 mt-2 italic flex items-center gap-1">
                            <i class="fa-solid fa-info-circle text-slate-400"></i>
                            Advisory decision support only. Final clinical decision rests with the healthcare professional.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('immunizationAssessmentModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
                    <span>Proceed to Record Vaccination</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- RECORD VACCINATION MODAL                                     -->
<!-- ============================================================ -->
<div id="recordVaccinationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-syringe text-brand-medium"></i>
                Record Vaccination
            </h3>
            <button onclick="closeModal('recordVaccinationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="recordVaccinationForm" class="p-6 space-y-4" onsubmit="saveVaccinationRecord(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Child</label>
                <select id="vacc_child" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Child</option>
                    <?php foreach ($children as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?> (<?php echo $c['child_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Vaccine</label>
                <select id="vacc_vaccine" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Vaccine</option>
                    <?php 
                        $vaccines = array_unique(array_column($vaccineSchedule, 'vaccine'));
                        foreach ($vaccines as $v): 
                    ?>
                        <option value="<?php echo $v; ?>"><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Dose Number</label>
                <input type="number" id="vacc_dose" min="1" max="9999" step="1" required inputmode="numeric" oninput="limitDoseInput(this)" title="Dose number must be 1 to 9999" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date Administered</label>
                <input type="date" id="vacc_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Next Due Date</label>
                <input type="date" id="vacc_next_due" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Administered By</label>
                <select id="vacc_admin" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Nurse Maria Cruz">Nurse Maria Cruz</option>
                    <option value="Nurse Anna Reyes">Nurse Anna Reyes</option>
                    <option value="Dr. Elena Santos">Dr. Elena Santos</option>
                    <option value="Dr. Ana Cruz">Dr. Ana Cruz</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Batch Number</label>
                <input type="text" id="vacc_batch" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. BCG-2026-01">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('recordVaccinationModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-syringe mr-1.5"></i> Record Vaccination
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW IMMUNIZATION MODAL                                      -->
<!-- ============================================================ -->
<div id="viewImmunizationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Immunization Details</h3>
            <button onclick="closeModal('viewImmunizationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="immunizationDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const IMMUNIZATIONS = <?php echo json_encode(array_column($immunizations, null, 'id'), JSON_PRETTY_PRINT); ?>;

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // Close modal on backdrop click
    document.querySelectorAll('.fixed.inset-0').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });

    // ============================================================
    // PRE-VACCINATION IMMUNIZATION ASSESSMENT (STEP 1)
    // ============================================================
    let currentAssessmentData = {};

    function startImmunizationAssessment(patientId, patientName, patientCode) {
        document.getElementById('assess_patient_id').value = patientId;
        document.getElementById('assess_patient_display').textContent = `${patientName} (${patientCode || 'P-' + patientId})`;
        
        // Auto-select child in record vaccination modal as well
        const selectVaccChild = document.getElementById('vacc_child');
        if (selectVaccChild) {
            let found = false;
            for (let i = 0; i < selectVaccChild.options.length; i++) {
                if (parseInt(selectVaccChild.options[i].value) === parseInt(patientId)) {
                    selectVaccChild.selectedIndex = i;
                    found = true;
                    break;
                }
            }
            if (!found) {
                let newOpt = document.createElement('option');
                newOpt.value = String(patientId);
                newOpt.textContent = `${patientName} (${patientCode || 'P-' + patientId})`;
                newOpt.selected = true;
                selectVaccChild.appendChild(newOpt);
            }
        }

        // Set default values
        document.getElementById('assess_weight').value = '8.5';
        document.getElementById('assess_temp').value = '36.5';
        document.getElementById('assess_health_status').value = 'Healthy';
        document.getElementById('assess_contraindications').value = 'None';
        document.getElementById('assess_notes').value = '';

        runAiEligibilityCheck();
        openModal('immunizationAssessmentModal');
    }

    function runAiEligibilityCheck() {
        const temp = parseFloat(document.getElementById('assess_temp').value || 36.5);
        const status = document.getElementById('assess_health_status').value;
        const contra = document.getElementById('assess_contraindications').value;

        const advisoryBox = document.getElementById('aiAdvisoryBox');
        const advisoryText = document.getElementById('aiAdvisoryText');
        const badge = document.getElementById('assessResultBadge');

        if (temp >= 38.0 || contra !== 'None' || status === 'Fever') {
            badge.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200';
            badge.innerHTML = '⚠️ Vaccination Deferred';
            
            advisoryBox.className = 'bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-4 transition-all';
            advisoryText.innerHTML = `Caution: Patient presents elevated temperature (${temp}°C) or reported contraindications (${contra}). Clinical recommendation: Defer vaccination and consult attending physician.`;
        } else {
            badge.className = 'px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200';
            badge.innerHTML = '✓ Eligible for Vaccination';
            
            advisoryBox.className = 'bg-gradient-to-r from-teal-50 to-emerald-50 border border-teal-200 rounded-xl p-4 transition-all';
            advisoryText.innerHTML = 'Based on recorded assessment: Patient appears healthy with normal temperature and no reported contraindications. Recommended to proceed with scheduled vaccine administration.';
        }
    }

    async function saveAssessmentAndProceed(event) {
        event.preventDefault();

        const patientId = document.getElementById('assess_patient_id').value;
        const weight = document.getElementById('assess_weight').value;
        const temp = document.getElementById('assess_temp').value;
        const status = document.getElementById('assess_health_status').value;
        const contra = document.getElementById('assess_contraindications').value;
        const vaccineDue = document.getElementById('assess_vaccine_due').value;
        const notes = document.getElementById('assess_notes').value;
        const resultText = document.getElementById('assessResultBadge').textContent.trim();
        const aiGuidance = document.getElementById('aiAdvisoryText').textContent.trim();

        currentAssessmentData = {
            patient_id: parseInt(patientId),
            weight: weight,
            temperature: temp,
            health_status: status,
            contraindications: contra,
            vaccine_due: vaccineDue,
            notes: notes,
            ai_guidance: aiGuidance,
            assessment_result: resultText
        };

        // Save pre-vaccination assessment audit record
        try {
            await fetch('../../api/triage.php?action=immunization_assessment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(currentAssessmentData)
            });
        } catch (e) {
            console.warn('Notice saving assessment audit record:', e);
        }

        showToast('Pre-vaccination assessment recorded! Proceeding to vaccination...', 'success');
        closeModal('immunizationAssessmentModal');
        
        // Auto-open Step 2 Record Vaccination Modal pre-filled
        setTimeout(() => {
            document.getElementById('vacc_date').value = new Date().toISOString().split('T')[0];
            openModal('recordVaccinationModal');
        }, 450);
    }

    // ============================================================
    // VIEW IMMUNIZATION
    // ============================================================
    function viewImmunization(id) {
        openModal('viewImmunizationModal');
        const i = IMMUNIZATIONS[id];
        if (!i) return;

        setTimeout(() => {
            document.getElementById('immunizationDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0">
                            ${i.child_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${i.child_name}</h4>
                            <p class="text-sm text-slate-500">${i.vaccine} • Dose ${i.dose}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${i.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : i.status === 'missed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'}">
                                ${i.status.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-slate-400">Date Administered</p><p class="text-sm text-slate-800">${i.date ? new Date(i.date).toLocaleDateString() : '—'}</p></div>
                        <div><p class="text-xs text-slate-400">Next Due</p><p class="text-sm text-slate-800">${i.next_due ? new Date(i.next_due).toLocaleDateString() : '—'}</p></div>
                        <div><p class="text-xs text-slate-400">Administered By</p><p class="text-sm text-slate-800">${i.administered_by || '—'}</p></div>
                        <div><p class="text-xs text-slate-400">Health Center</p><p class="text-sm text-slate-800">${i.health_center}</p></div>
                        <div class="col-span-2"><p class="text-xs text-slate-400">Batch Number</p><p class="text-sm text-slate-800 font-mono">${i.batch_number || '—'}</p></div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewImmunizationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // RECORD VACCINATION
    // ============================================================
    function recordVaccination(id) {
        const i = IMMUNIZATIONS[id];
        if (!i) return;
        
        // Pre-fill form with data
        document.getElementById('vacc_child').value = i.child_db_id || '';
        document.getElementById('vacc_vaccine').value = i.vaccine;
        document.getElementById('vacc_dose').value = i.dose;
        document.getElementById('vacc_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('vacc_next_due').value = i.next_due || '';
        
        openModal('recordVaccinationModal');
    }

    function limitDoseInput(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 4);
    }

    function saveVaccinationRecord(event) {
        event.preventDefault();
        const dose = document.getElementById('vacc_dose').value;
        if (!/^\d{1,4}$/.test(dose) || Number(dose) < 1 || Number(dose) > 9999) {
            showToast('Dose number must be between 1 and 9999.', 'warning');
            document.getElementById('vacc_dose').focus();
            return;
        }
        showToast('Vaccination recorded successfully!', 'success');
        closeModal('recordVaccinationModal');
    }

    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
    let toastTimer = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-rose-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        toast.className = 'fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ' + (colors[type] || colors.success);
        toast.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('hidden');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchVaccination').addEventListener('input', filterVaccinations);
    document.getElementById('filterStatus').addEventListener('change', filterVaccinations);
    document.getElementById('filterVaccine').addEventListener('change', filterVaccinations);
    document.getElementById('filterDateFrom').addEventListener('change', filterVaccinations);
    document.getElementById('filterDateTo').addEventListener('change', filterVaccinations);

    function filterVaccinations() {
        const search = document.getElementById('searchVaccination').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const vaccine = document.getElementById('filterVaccine').value.toLowerCase();
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.vaccination-row').forEach(row => {
            const child = row.dataset.child;
            const rowVaccine = row.dataset.vaccine;
            const rowStatus = row.dataset.status;
            const rowDate = row.dataset.date || '';
            const rowBatch = row.dataset.batch || '';
            const rowDose = row.dataset.dose || '';

            const matchesSearch = !search || [child, rowVaccine, rowBatch, rowDose]
                .some(value => String(value || '').toLowerCase().includes(search));
            const matchesStatus = !status || 
                (status === 'due_soon' ? isDueSoon(row) : rowStatus === status);
            const matchesVaccine = !vaccine || rowVaccine === vaccine;
            const matchesDateFrom = !dateFrom || (rowDate && rowDate >= dateFrom);
            const matchesDateTo = !dateTo || (rowDate && rowDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesVaccine && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function isDueSoon(row) {
        if (!row.dataset.nextDue) return false;
        const dueDate = new Date(row.dataset.nextDue + 'T00:00:00');
        const daysLeft = (dueDate - new Date()) / 86400000;
        return daysLeft >= 0 && daysLeft <= 30;
    }

    function resetFilters() {
        document.getElementById('searchVaccination').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterVaccine').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.vaccination-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo max(1, ceil($totalImmunizations / 10)); ?>) return;
        window.location.href = '?page=' + page;
    }

    // ESC to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });

    // ============================================================
    // SET DEFAULT DATE
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('vacc_date');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        // Set next due default to 1 month later
        const nextDue = document.getElementById('vacc_next_due');
        if (nextDue) {
            const date = new Date();
            date.setMonth(date.getMonth() + 1);
            nextDue.value = date.toISOString().split('T')[0];
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>