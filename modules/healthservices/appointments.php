
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
requireDepartmentAccess('health center services');
include_once __DIR__ . '/../../includes/data-mask.php';

require_once __DIR__ . '/../../app/Models/Patient.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Appointment.php';
require_once __DIR__ . '/../../app/Models/Consultation.php'; // ADD THIS - Include Consultation Model
require_once __DIR__ . '/../../app/Controllers/AppointmentController.php';

// Fetch Patients
$patientModel = new Patient();
$dbPatients = [];
try {
    $dbPatients = $patientModel->all(['order' => 'first_name.asc']);
} catch (Throwable $e) {
    error_log('Error loading patients: ' . $e->getMessage());
}

// Fetch Employees/Doctors
$employeeModel = new Employee();
$dbEmployees = [];
try {
    $rawEmployees = $employeeModel->all();
    // Ensure each employee has a full_name
    foreach ($rawEmployees as $e) {
        $displayName = $e['full_name'] ?? '';
        if (empty($displayName)) {
            $displayName = $e['name'] ?? $e['username'] ?? "Employee #{$e['id']}";
        }
        $e['full_name'] = $displayName;
        $e['first_name'] = $displayName;
        $e['last_name'] = '';
        $dbEmployees[] = $e;
    }
} catch (Throwable $e) {
    error_log('Error loading employees: ' . $e->getMessage());
    $dbEmployees = [];
}

// Fetch Appointments
$appointmentModel = new Appointment();
$appointments = [];

try {
    $rawAppointments = $appointmentModel->all(['order' => 'appointment_date.desc,appointment_time.asc,created_at.desc']);

    $patientsMap = [];
    foreach ($dbPatients as $p) {
        if (isset($p['id'])) {
            $patientsMap[$p['id']] = $p;
        }
    }

    $medicalRoles = ['Health Center Director', 'Medical Practitioner', 'Health Center Staff', 'Immunization Lead', 'Nutrition Staff', 'Doctor', 'Nurse', 'Dentist', 'midwives', 'Nutritionist', 'Immunization Coordinator', 'Lab tech'];

    $employeesMap = [];
    foreach ($dbEmployees as $e) {
        if (isset($e['id'])) {
            $employeesMap[$e['id']] = $e;
        }
    }

    foreach ($rawAppointments as $a) {
        $patientId = $a['patient_id'] ?? null;
        $patient = $patientsMap[$patientId] ?? null;

        if ($patient) {
            $firstName = $patient['first_name'] ?? '';
            $lastName = $patient['last_name'] ?? '';
            $patientName = trim("$firstName $lastName");
            $patientCode = $patient['patient_id'] ?? "P-$patientId";

            $initials = '';
            if (!empty($firstName)) $initials .= strtoupper(substr($firstName, 0, 1));
            if (!empty($lastName)) $initials .= strtoupper(substr($lastName, 0, 1));
            $avatar = !empty($initials) ? $initials : 'PT';
        } else {
            $patientName = "Patient #{$patientId}";
            $patientCode = "P-{$patientId}";
            $avatar = "PT";
        }
            
        $employeeId = $a['employee_id'] ?? null;
        $employee = $employeesMap[$employeeId] ?? null;
        if ($employee) {
            $role = $employee['role_description'] ?? '';
            if (in_array($role, $medicalRoles)) {
                $doctorName = $employee['full_name'] ?? "Staff #{$employeeId}";
            } else {
                $doctorName = "Staff #{$employeeId}";
            }
        } else {
            $doctorName = "Staff #{$employeeId}";
        }

        $dateStr = $a['appointment_date'] ?? ($a['date'] ?? date('Y-m-d'));
        $timeStr = $a['appointment_time'] ?? ($a['time'] ?? '09:00:00');

        $appointments[] = [
            'id' => (int)($a['id'] ?? 0),
            'appointment_id' => $a['appointment_id'] ?? '',
            'patient_id' => (int)($a['patient_id'] ?? 0),
            'patient_name' => $patientName,
            'patient_code' => $patientCode,
            'patient_avatar' => $avatar,
            'employee_id' => (int)($a['employee_id'] ?? 1),
            'doctor_name' => $doctorName,
            'service_type' => $a['service_type'] ?? ($a['type'] ?? 'General Checkup'),
            'type' => $a['service_type'] ?? ($a['type'] ?? 'General Checkup'),
            'appointment_date' => $dateStr,
            'date' => $dateStr,
            'appointment_time' => !empty($timeStr) ? substr($timeStr, 0, 8) : '09:00:00',
            'time' => !empty($timeStr) ? date('h:i A', strtotime($timeStr)) : '09:00 AM',
            'status' => strtolower($a['status'] ?? 'pending'),
            'priority' => strtolower($a['priority'] ?? 'medium'),
            'notes' => $a['notes'] ?? '',
            'reminder_sent' => (bool)($a['reminder_sent'] ?? false),
            'created_at' => $a['created_at'] ?? ''
        ];
    }
} catch (Throwable $e) {
    error_log("Error building appointments list: " . $e->getMessage());
}

// ============================================================
// FETCH CONSULTATIONS FOR APPOINTMENT LINKING - ADD THIS SECTION
// ============================================================
$consultationModel = new Consultation();
$consultations = [];
$consultationMap = [];

try {
    $rawConsultations = $consultationModel->all(['order' => 'date.desc']);
    
    // Build a map of appointment_id -> consultation
    foreach ($rawConsultations as $c) {
        if (!empty($c['appointment_id'])) {
            $consultationMap[$c['appointment_id']] = $c['id'];
        }
    }
} catch (Throwable $e) {
    error_log('Error loading consultations: ' . $e->getMessage());
}

// Doctor list for UI filters and dropdowns
$doctorsList = $dbEmployees;

// ============================================================
// MERGE TRIAGE ASSIGNMENTS INTO APPOINTMENTS VIEW
// Triage patients assigned to a doctor show up here too
// ============================================================
require_once __DIR__ . '/../../app/Models/Triage.php';

$patientsMapLocal = [];
foreach ($dbPatients as $p) {
    if (isset($p['id'])) $patientsMapLocal[$p['id']] = $p;
}
$employeesMapLocal = [];
foreach ($dbEmployees as $e) {
    if (isset($e['id'])) $employeesMapLocal[$e['id']] = $e;
}

try {
    $triageModel = new Triage();
    $rawTriage = $triageModel->all(['order' => 'created_at.desc']);

    // Get existing appointment IDs to avoid duplication
    $existingAppointmentIds = array_column($appointments, 'id');

    foreach ($rawTriage as $t) {
        // Only include triage records that have an assigned doctor
        if (empty($t['doctor_id']) && empty($t['doctor_assigned'])) continue;

        $tPatId = $t['patient_id'] ?? null;
        $tPat = $patientsMapLocal[$tPatId] ?? null;

        if ($tPat) {
            $fN = $tPat['first_name'] ?? '';
            $lN = $tPat['last_name'] ?? '';
            $tPatName = trim("$fN $lN");
            $tPatCode = $tPat['patient_id'] ?? "P-$tPatId";
            $tAvatar = '';
            if (!empty($fN)) $tAvatar .= strtoupper(substr($fN, 0, 1));
            if (!empty($lN)) $tAvatar .= strtoupper(substr($lN, 0, 1));
            if (empty($tAvatar)) $tAvatar = 'PT';
        } else {
            $tPatName = "Patient #{$tPatId}";
            $tPatCode = "P-{$tPatId}";
            $tAvatar = 'PT';
        }

        // Resolve doctor name
        $tDocId = (int)($t['doctor_id'] ?? 0);
        $tDocEmp = $tDocId > 0 ? ($employeesMapLocal[$tDocId] ?? null) : null;
        if ($tDocEmp) {
            $tDocName = $tDocEmp['full_name'] ?? ("Doctor #{$tDocId}");
        } elseif (!empty($t['doctor_assigned'])) {
            $tDocName = $t['doctor_assigned'];
        } else {
            $tDocName = 'Unassigned';
        }

        $tDate = isset($t['created_at']) ? date('Y-m-d', strtotime($t['created_at'])) : date('Y-m-d');
        $tTime = isset($t['created_at']) ? date('H:i:s', strtotime($t['created_at'])) : date('H:i:s');

        // Map triage status to appointment-like status
        $tStatus = match(strtolower($t['status'] ?? '')) {
            'waiting', 'in_triage' => 'pending',
            'completed', 'sent_to_doctor', 'triaged', 'consulted' => 'approved',
            'in_consultation' => 'approved',
            default => 'pending'
        };

        $tPriority = strtolower($t['priority'] ?? 'medium');

        $appointments[] = [
            'id'               => 'TRG-' . ($t['id'] ?? 0),
            'appointment_id'   => $t['triage_id'] ?? ('TRG-' . ($t['id'] ?? 0)),
            'patient_id'       => (int)($tPatId ?? 0),
            'patient_name'     => $tPatName,
            'patient_code'     => $tPatCode,
            'patient_avatar'   => $tAvatar,
            'employee_id'      => $tDocId,
            'doctor_name'      => $tDocName,
            'service_type'     => 'Triage Assessment',
            'type'             => 'Triage Assessment',
            'appointment_date' => $tDate,
            'date'             => $tDate,
            'appointment_time' => $tTime,
            'time'             => date('h:i A', strtotime($tTime)),
            'status'           => $tStatus,
            'priority'         => $tPriority,
            'notes'            => $t['notes'] ?? $t['symptoms'] ?? '',
            'reminder_sent'    => false,
            'created_at'       => $t['created_at'] ?? '',
            'source'           => 'triage'
        ];
    }
} catch (Throwable $e) {
    error_log('Error merging triage into appointments: ' . $e->getMessage());
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$totalAppointments = count($appointments);
$totalPages = ceil($totalAppointments / $limit);
if ($totalPages < 1) $totalPages = 1;
$paginatedAppointments = array_slice($appointments, $offset, $limit);

$title = 'Appointments';

// Derived stats
$totalApproved = count(array_filter($appointments, fn($a) => in_array($a['status'], ['approved', 'confirmed'])));
$totalPending  = count(array_filter($appointments, fn($a) => $a['status'] === 'pending'));
$totalCompleted = count(array_filter($appointments, fn($a) => $a['status'] === 'completed'));
$todayAppointments = count(array_filter($appointments, fn($a) => $a['date'] === date('Y-m-d')));
?>
<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->
<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-y-auto">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Appointment Scheduling</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage patient bookings, approvals, and medical service schedules</p>
        </div>
        <div class="flex gap-3">
            <button onclick="ModalSystem.open('addAppointmentModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-calendar-plus text-xs"></i> New Appointment
            </button>
        </div>
    </div>

    <!-- MODERN KPI CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Appointments -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalAppointments; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Bookings</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All records</span>
                    <span class="text-[10px] text-slate-400"><?php echo $totalApproved; ?> approved</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Approved / Confirmed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $totalApproved; ?></p>
                        <p class="text-xs font-medium text-slate-500">Approved</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Ready</span>
                    <span class="text-[10px] text-slate-400">Scheduled & confirmed</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Pending Approval -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $totalPending; ?></p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Action needed</span>
                    <span class="text-[10px] text-slate-400">Awaiting approval</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Today's Appointments -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-sky-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-sky-500 to-sky-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-sky-200">
                        <i class="fa-solid fa-calendar-day text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-sky-600"><?php echo $todayAppointments; ?></p>
                        <p class="text-xs font-medium text-slate-500">Today's Schedule</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-sky-100 text-sky-700 rounded-full text-[10px] font-bold">📅 Today</span>
                    <span class="text-[10px] text-slate-400"><?php echo date('F d, Y'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchAppointment"
                       placeholder="Search by patient name, appointment ID, service type, or doctor..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="no_show">No Show</option>
                </select>
                <select id="filterPriority" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Priorities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <select id="filterDate" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Dates</option>
                    <option value="today">Today</option>
                    <option value="tomorrow">Tomorrow</option>
                    <option value="week">This Week</option>
                </select>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Appointments Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden flex-1 flex flex-col justify-between">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Appt ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Doctor / Staff</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Service Type</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Priority</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="appointmentTableBody">
                    <?php if (empty($paginatedAppointments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-12 text-slate-400">
                                <i class="fa-solid fa-calendar-xmark text-3xl block mb-2"></i>
                                No appointment records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginatedAppointments as $a): 
                            $nameParts = explode(' ', $a['patient_name']);
                            $maskedName = '';
                            foreach ($nameParts as $part) {
                                if (!empty($part)) {
                                    $maskedName .= strtoupper(substr($part, 0, 1)) . str_repeat('*', max(0, strlen($part) - 1)) . ' ';
                                }
                            }
                            $maskedName = trim($maskedName);
                            $code = $a['patient_code'];
                            $maskedCode = substr($code, 0, 2) . str_repeat('*', max(0, strlen($code) - 2));
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors appointment-row"
                            data-patient="<?php echo htmlspecialchars(strtolower($a['patient_name'])); ?>"
                            data-doctor="<?php echo htmlspecialchars(strtolower($a['doctor_name'])); ?>"
                            data-service="<?php echo htmlspecialchars(strtolower($a['service_type'])); ?>"
                            data-status="<?php echo htmlspecialchars(strtolower($a['status'])); ?>"
                            data-priority="<?php echo htmlspecialchars(strtolower($a['priority'])); ?>"
                            data-date="<?php echo htmlspecialchars($a['date']); ?>">
                            
                            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-bold"><?php echo htmlspecialchars($a['appointment_id']); ?></td>
                            
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                                        <?php echo htmlspecialchars($a['patient_avatar']); ?>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-800 text-sm maskable" 
                                           data-masked="<?php echo htmlspecialchars($maskedName); ?>"
                                           data-real="<?php echo htmlspecialchars($a['patient_name']); ?>">
                                            <?php echo htmlspecialchars($a['patient_name']); ?>
                                        </p>
                                        <p class="text-xs text-slate-400 font-mono maskable" 
                                           data-masked="<?php echo htmlspecialchars($maskedCode); ?>"
                                           data-real="<?php echo htmlspecialchars($a['patient_code']); ?>">
                                            <?php echo htmlspecialchars($a['patient_code']); ?>
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800 text-xs"><?php echo htmlspecialchars($a['doctor_name']); ?></p>
                            </td>

                            <td class="px-4 py-3">
                                <p class="text-xs font-semibold text-slate-800"><?php echo date('M d, Y', strtotime($a['date'])); ?></p>
                                <p class="text-[10px] text-slate-400 font-medium"><?php echo htmlspecialchars($a['time']); ?></p>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-md text-xs font-medium">
                                    <?php echo htmlspecialchars($a['service_type']); ?>
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?php 
                                    echo $a['priority'] === 'critical' ? 'bg-rose-100 text-rose-700' :
                                        ($a['priority'] === 'high' ? 'bg-amber-100 text-amber-700' :
                                        ($a['priority'] === 'medium' ? 'bg-blue-100 text-blue-700' :
                                        'bg-slate-100 text-slate-600'));
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($a['priority'])); ?>
                                </span>
                            </td>

                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php 
                                    echo in_array($a['status'], ['approved', 'confirmed']) ? 'bg-emerald-100 text-emerald-700' : 
                                        ($a['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : 
                                        ($a['status'] === 'completed' ? 'bg-blue-100 text-blue-700' : 
                                        'bg-rose-100 text-rose-700')); 
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($a['status'])); ?>
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="viewAppointment('<?php echo htmlspecialchars((string)$a['id']); ?>')"
                                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </button>
                                    <?php if (in_array($a['status'], ['approved', 'confirmed', 'pending'])): ?>
                                        <button onclick="checkInScheduledPatient('<?php echo htmlspecialchars((string)($a['patient_id'] ?? $a['id'])); ?>')"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Check-in Scheduled Patient for Today's Visit">
                                            <i class="fa-solid fa-user-check text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (!in_array($a['status'], ['cancelled', 'completed'])): ?>
                                        <button onclick="changeAppointmentStatus('<?php echo htmlspecialchars((string)$a['id']); ?>', 'cancelled')"
                                                class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Cancel Appointment">
                                            <i class="fa-solid fa-ban text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($a['status'] === 'approved' || $a['status'] === 'confirmed'): ?>
                                        <button onclick="startConsultationFromAppointment('<?php echo htmlspecialchars((string)$a['id']); ?>')"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Start Consultation">
                                            <i class="fa-solid fa-stethoscope text-sm"></i>
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

        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-calendar-xmark text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No appointments match your filter criteria</p>
            <p class="text-xs text-slate-400 mt-1">Try resetting or broadening your search</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Reset filters</button>
        </div>

        <?php if ($totalAppointments > 0): ?>
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700"><?php echo min($offset + 1, $totalAppointments); ?></span> to
                <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalAppointments); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalAppointments; ?></span> appointments
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
        <?php endif; ?>
    </div>
</div>

<!-- VIEW APPOINTMENT MODAL -->
<div id="viewAppointmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-check text-brand-medium"></i> Appointment Information
            </h3>
            <button onclick="ModalSystem.close('viewAppointmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="appointmentDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading appointment details...
            </div>
        </div>
    </div>
</div>

<!-- ADD APPOINTMENT MODAL - FIXED -->
<div id="addAppointmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-brand-medium"></i> Schedule New Appointment
            </h3>
            <button onclick="ModalSystem.close('addAppointmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <!-- FIX: Removed onsubmit -->
        <form id="addAppointmentForm" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <input type="text" id="add_patient_search" placeholder="Search patient by name or ID..." autocomplete="off"
                           oninput="filterPatientList()" onfocus="showPatientDropdown()"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <input type="hidden" id="add_patient_id" value="">
                    <div id="add_patient_dropdown" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Doctor / Staff <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="add_doctor_search" placeholder="Search doctor or staff..." autocomplete="off"
                               oninput="filterDoctorList('add')" onfocus="showDoctorDropdown('add')"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <input type="hidden" id="add_employee_id" value="">
                        <div id="add_doctor_dropdown" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type <span class="text-rose-500">*</span></label>
                    <!-- FIX: Removed required attribute -->
                    <select id="add_service_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="General Checkup">General Checkup</option>
                        <option value="Cardiology Consultation">Cardiology Consultation</option>
                        <option value="Pediatric Checkup">Pediatric Checkup</option>
                        <option value="Prenatal Checkup">Prenatal Checkup</option>
                        <option value="Vaccination">Vaccination</option>
                        <option value="Follow-up Visit">Follow-up Visit</option>
                        <option value="Dental Services">Dental Services</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="add_appointment_date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time <span class="text-rose-500">*</span></label>
                    <input type="time" id="add_appointment_time" value="09:00" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority</label>
                    <select id="add_priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="add_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="pending">Pending Approval</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes / Instructions</label>
                <textarea id="add_notes" rows="3" placeholder="Reason for visit or special instructions..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('addAppointmentModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="submitAddBtn"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-calendar-check"></i> Book Appointment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT APPOINTMENT MODAL - FIXED -->
<div id="editAppointmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-brand-medium"></i> Edit Appointment
            </h3>
            <button onclick="ModalSystem.close('editAppointmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <!-- FIX: Removed onsubmit -->
        <form id="editAppointmentForm" class="p-6 space-y-4">
            <input type="hidden" id="edit_id">

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label>
                <div class="relative patient-field-wrapper">
                    <input type="text" id="edit_patient_name" readonly 
                           class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-700 outline-none font-semibold cursor-not-allowed"
                           data-real="">
                    <span id="edit_patient_masked_display" 
                          class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-slate-700 pointer-events-none"
                          style="display: none;"></span>
                    <input type="hidden" id="edit_patient_id">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Doctor / Staff</label>
                <div class="relative">
                    <input type="text" id="edit_doctor_search" placeholder="Search doctor or staff..." autocomplete="off"
                           oninput="filterDoctorList('edit')" onfocus="showDoctorDropdown('edit')"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <input type="hidden" id="edit_employee_id" value="">
                    <div id="edit_doctor_dropdown" class="hidden absolute z-20 mt-1 w-full bg-white border border-slate-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label>
                <input type="text" id="edit_service_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                    <input type="date" id="edit_appointment_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time</label>
                    <input type="time" id="edit_appointment_time" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Priority</label>
                    <select id="edit_priority" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="edit_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="no_show">No Show</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="edit_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('editAppointmentModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="submitEditBtn"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<!-- ============================================================ -->
<!-- 3. JAVASCRIPT                                                -->
<!-- ============================================================ -->
<script>
    const APPOINTMENTS_DATA = <?php echo json_encode(array_column($appointments, null, 'id'), JSON_UNESCAPED_UNICODE); ?>;
    const CONSULTATION_MAP = <?php echo json_encode($consultationMap); ?>;
    const MEDICAL_ROLES = ['Health Center Director', 'Medical Practitioner', 'Health Center Staff', 'Immunization Lead', 'Nutrition Staff', 'Doctor', 'Nurse', 'Dentist', 'midwives', 'Nutritionist', 'Immunization Coordinator', 'Lab tech'];

    async function checkInScheduledPatient(patientId) {
        const qNum = 'Q-' + String(Math.floor(1000 + Math.random() * 9000));
        try {
            await fetch('/capstone/api/triage-queue.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    patient_id: parseInt(patientId),
                    queue_number: qNum
                })
            });
            ModalSystem.toast.success('Scheduled patient checked in for today!');
            setTimeout(() => {
                window.location.href = `triage.php?patient_id=${patientId}&action=checkin`;
            }, 800);
        } catch (e) {
            window.location.href = `triage.php?patient_id=${patientId}&action=checkin`;
        }
    }
    const PATIENTS = <?php 
        echo json_encode(array_values(array_map(function($p) {
            return [
                'id' => $p['id'],
                'name' => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
                'patient_id' => $p['patient_id'] ?? ('P-' . $p['id'])
            ];
        }, $dbPatients)));
    ?>;

    const DOCTORS = <?php 
    $medicalStaff = array_filter($dbEmployees, function($e) {
        $primaryRole = $e['role'] ?? '';
        $roleDesc = $e['role_description'] ?? '';
        return in_array($primaryRole, ['Health Center Director', 'Medical Practitioner', 'Health Center Staff', 'Immunization Lead', 'Nutrition Staff'])
            || in_array($roleDesc, ['Health Center Director', 'Doctor', 'Nurse', 'Dentist', 'midwives', 'Nutritionist', 'Immunization Coordinator', 'Lab tech']);
    });
    echo json_encode(array_values(array_map(function($e) {
        return [
            'id' => $e['id'],
            'name' => $e['full_name'] ?? $e['name'] ?? $e['username'] ?? "Employee #{$e['id']}",
            'role' => $e['role_description'] ?: $e['role'] ?: '',
            'department' => $e['department'] ?? ''
        ];
    }, $medicalStaff)));
?>;
    // ============================================================
    // DATA MASKING HELPERS
    // ============================================================
    function maskPatientName(name) {
        if (!name) return '';
        const parts = name.split(' ');
        return parts.map(p => p ? p.charAt(0).toUpperCase() + '*'.repeat(Math.max(0, p.length - 1)) : '').join(' ');
    }

    function maskPatientCode(code) {
        if (!code || code.length <= 2) return code || '';
        return code.substring(0, 2) + '*'.repeat(code.length - 2);
    }

    function getMaskedDisplay(name, code) {
        return maskPatientName(name) + ' (' + maskPatientCode(code) + ')';
    }

    function getRealDisplay(name, code) {
        return name + ' (' + code + ')';
    }

    // ============================================================
    // PATIENT DROPDOWN
    // ============================================================
    function filterPatientList() {
        const search = document.getElementById('add_patient_search').value.toLowerCase();
        const dropdown = document.getElementById('add_patient_dropdown');
        
        const filtered = PATIENTS.filter(p => 
            p.name.toLowerCase().includes(search) || p.patient_id.toLowerCase().includes(search)
        );
        
        dropdown.innerHTML = filtered.length === 0 
            ? '<div class="px-3 py-2 text-xs text-slate-400">No patients found</div>'
            : filtered.slice(0, 15).map(p => `
                <div onclick="selectPatient(${p.id}, '${p.name.replace(/'/g, "\\'")}', '${p.patient_id}')" 
                     class="px-3 py-2 hover:bg-brand-light/40 cursor-pointer flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-700 maskable" data-masked="${maskPatientName(p.name)}" data-real="${p.name}">${maskPatientName(p.name)}</span>
                    <span class="text-[10px] text-slate-400 font-mono maskable" data-masked="${maskPatientCode(p.patient_id)}" data-real="${p.patient_id}">${maskPatientCode(p.patient_id)}</span>
                </div>
            `).join('');
        
        dropdown.classList.remove('hidden');
    }

    function showPatientDropdown() { filterPatientList(); }

    function selectPatient(id, name, patientId) {
        document.getElementById('add_patient_id').value = id;
        const searchInput = document.getElementById('add_patient_search');
        searchInput.value = getMaskedDisplay(name, patientId);
        searchInput.dataset.real = getRealDisplay(name, patientId);
        searchInput.dataset.masked = getMaskedDisplay(name, patientId);
        searchInput.classList.add('maskable');
        document.getElementById('add_patient_dropdown').classList.add('hidden');
        searchInput.style.borderColor = '';
        searchInput.style.borderWidth = '';
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#add_patient_search')) {
            document.getElementById('add_patient_dropdown')?.classList.add('hidden');
        }
    });

    // ============================================================
    // DOCTOR DROPDOWN
    // ============================================================
    function filterDoctorList(prefix) {
        const search = document.getElementById(prefix + '_doctor_search').value.toLowerCase();
        const dropdown = document.getElementById(prefix + '_doctor_dropdown');
        
        const filtered = DOCTORS.filter(d => 
            d.name.toLowerCase().includes(search) || d.role.toLowerCase().includes(search)
        );
        
        dropdown.innerHTML = filtered.length === 0
            ? '<div class="px-3 py-2 text-xs text-slate-400">No medical staff found</div>'
            : filtered.map(d => `
                <div onclick="selectDoctor('${prefix}', ${d.id}, '${d.name.replace(/'/g, "\\'")}')" 
                     class="px-3 py-2 hover:bg-brand-light/40 cursor-pointer flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-700">${d.name}</span>
                    <span class="text-[10px] px-2 py-0.5 bg-slate-100 rounded-full text-slate-500">${d.role}</span>
                </div>
            `).join('');
        
        dropdown.classList.remove('hidden');
    }

    function showDoctorDropdown(prefix) { filterDoctorList(prefix); }

    function selectDoctor(prefix, id, name) {
        document.getElementById(prefix + '_employee_id').value = id;
        document.getElementById(prefix + '_doctor_search').value = name;
        document.getElementById(prefix + '_doctor_dropdown').classList.add('hidden');
        const searchField = document.getElementById(prefix + '_doctor_search');
        if (searchField) {
            searchField.style.borderColor = '';
            searchField.style.borderWidth = '';
        }
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#add_doctor_search')) document.getElementById('add_doctor_dropdown')?.classList.add('hidden');
        if (!e.target.closest('#edit_doctor_search')) document.getElementById('edit_doctor_dropdown')?.classList.add('hidden');
    });

    // ============================================================
    // VIEW APPOINTMENT
    // ============================================================
    function viewAppointment(id) {
        const a = APPOINTMENTS_DATA[id];
        const content = document.getElementById('appointmentDetailsContent');

        if (!a) {
            content.innerHTML = '<p class="text-center text-slate-500 py-6">Appointment details not found.</p>';
            ModalSystem.open('viewAppointmentModal');
            return;
        }

        const maskedName = maskPatientName(a.patient_name);
        const maskedCode = maskPatientCode(a.patient_code);
        const statusBadges = {
            pending: 'bg-amber-100 text-amber-700', approved: 'bg-emerald-100 text-emerald-700',
            completed: 'bg-blue-100 text-blue-700', cancelled: 'bg-rose-100 text-rose-700',
            no_show: 'bg-slate-100 text-slate-600'
        };

        const patientId = a.patient_id || 0;
        const patientIdForLink = patientId > 0 ? patientId : '';

        // Check if consultation exists using CONSULTATION_MAP
        const consultationId = CONSULTATION_MAP && CONSULTATION_MAP[a.id] ? CONSULTATION_MAP[a.id] : null;

        content.innerHTML = `
            <div class="space-y-5">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-lg flex-shrink-0">${a.patient_avatar || 'PT'}</div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 maskable" data-masked="${maskedName}" data-real="${a.patient_name}">${maskedName}</h4>
                        <p class="text-xs text-slate-500 font-mono">${a.appointment_id} • <span class="maskable" data-masked="${maskedCode}" data-real="${a.patient_code}">${maskedCode}</span></p>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusBadges[a.status] || 'bg-slate-100 text-slate-700'}">${a.status.toUpperCase()}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                    <div><p class="text-slate-400 font-semibold uppercase">Doctor / Staff</p><p class="text-slate-800 font-bold mt-0.5">${a.doctor_name}</p></div>
                    <div><p class="text-slate-400 font-semibold uppercase">Date & Time</p><p class="text-slate-800 font-semibold mt-0.5">${a.date} ${a.time}</p></div>
                    <div><p class="text-slate-400 font-semibold uppercase">Service Type</p><p class="text-slate-800 font-bold mt-0.5">${a.service_type}</p></div>
                    <div><p class="text-slate-400 font-semibold uppercase">Priority Level</p><p class="text-slate-800 font-bold uppercase mt-0.5">${a.priority}</p></div>
                    <div><p class="text-slate-400 font-semibold uppercase">Patient ID</p><p class="text-slate-800 font-mono font-bold mt-0.5 maskable" data-masked="${maskedCode}" data-real="${a.patient_code}">${maskedCode}</p></div>
                </div>
                ${a.notes ? `<div><h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Notes</h5><p class="text-sm text-slate-800 bg-slate-50 p-3 rounded-lg border border-slate-200">${a.notes}</p></div>` : ''}
                <div class="flex flex-wrap justify-end gap-2 pt-3 border-t border-slate-100">
                    ${patientIdForLink ? `
                        <a href="patients.php?patient=${patientIdForLink}&id=${patientIdForLink}" 
                           class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-xs font-semibold inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-user"></i> View Patient Profile
                        </a>
                    ` : `
                        <span class="px-4 py-2 bg-slate-200 text-slate-400 rounded-lg text-xs font-semibold inline-flex items-center gap-1.5 cursor-not-allowed">
                            <i class="fa-solid fa-user"></i> Patient ID Missing
                        </span>
                    `}
                    ${(a.status === 'approved' || a.status === 'confirmed') && !consultationId ? `
                        <button onclick="startConsultationFromAppointment(${a.id})" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-semibold inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-stethoscope"></i> Start Consultation
                        </button>
                    ` : ''}
                    ${consultationId ? `
                        <a href="consultations.php" 
                           class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-xs font-semibold inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-notes-medical"></i> View Consultation
                        </a>
                    ` : ''}
                    <button onclick="closeViewAndEdit(${a.id})" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-pen"></i> Edit
                    </button>
                </div>
            </div>`;

        ModalSystem.open('viewAppointmentModal', { applyMasking: true });
    }

    function closeViewAndEdit(id) {
        ModalSystem.close('viewAppointmentModal');
        setTimeout(() => editAppointment(id), 200);
    }

    // ============================================================
    // OPEN CONSULTATION WITH APPOINTMENT DATA - FIXED
    // ============================================================
    function openConsultationWithAppointmentData(a) {
        // Open the consultation modal
        ModalSystem.open('addConsultationModal');
        
        // Wait for modal to render
        setTimeout(() => {
            // Set patient
            const patientSelect = document.getElementById('add_patient_id');
            if (patientSelect && a.patient_id) {
                for (let option of patientSelect.options) {
                    if (String(option.value) === String(a.patient_id)) {
                        option.selected = true;
                        console.log('✅ Patient selected:', option.text);
                        break;
                    }
                }
            }
            
            // Set employee (doctor)
            const employeeSelect = document.getElementById('add_employee_id');
            if (employeeSelect && a.employee_id) {
                const targetId = String(a.employee_id);
                let found = false;
                for (let i = 0; i < employeeSelect.options.length; i++) {
                    if (String(employeeSelect.options[i].value) === targetId) {
                        employeeSelect.selectedIndex = i;
                        found = true;
                        console.log('✅ Doctor selected:', employeeSelect.options[i].text);
                        break;
                    }
                }
                if (!found) {
                    console.warn('⚠️ Doctor ID not found in dropdown:', a.employee_id);
                    // Try to find by name as fallback
                    const doctorName = a.doctor_name || '';
                    for (let i = 0; i < employeeSelect.options.length; i++) {
                        if (employeeSelect.options[i].text.toLowerCase().includes(doctorName.toLowerCase())) {
                            employeeSelect.selectedIndex = i;
                            console.log('✅ Doctor found by name:', employeeSelect.options[i].text);
                            break;
                        }
                    }
                }
            }
            
            // Set date
            const dateInput = document.getElementById('add_date');
            if (dateInput && a.date) {
                dateInput.value = a.date;
            }
            
            // Set time
            const timeInput = document.getElementById('add_time');
            if (timeInput && a.time) {
                const timeStr = a.time;
                const match = timeStr.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
                if (match) {
                    let hours = parseInt(match[1]);
                    const minutes = match[2];
                    const meridian = match[3].toUpperCase();
                    if (meridian === 'PM' && hours !== 12) hours += 12;
                    if (meridian === 'AM' && hours === 12) hours = 0;
                    timeInput.value = `${hours.toString().padStart(2, '0')}:${minutes}`;
                } else if (timeStr.includes(':')) {
                    timeInput.value = timeStr.substring(0, 5);
                } else {
                    timeInput.value = new Date().toTimeString().slice(0, 5);
                }
            }
            
            // Set status
            const statusSelect = document.getElementById('add_status');
            if (statusSelect) {
                statusSelect.value = 'in_progress';
            }
            
            // Add note
            const notesInput = document.getElementById('add_notes');
            if (notesInput && a.id) {
                notesInput.value = `Consultation from appointment #${a.appointment_id || a.id}`;
            }
            
            // Set hidden appointment_id
            const appointmentInput = document.getElementById('add_appointment_id');
            if (appointmentInput && a.id) {
                appointmentInput.value = a.id;
            }
            
            // Show confirmation
            ModalSystem.toast.info('Patient and doctor pre-filled from appointment', {
                title: '📋 Auto-filled',
                duration: 3000
            });
        }, 500);
    }

    // ============================================================
    // START CONSULTATION FROM APPOINTMENT - FIXED
    // ============================================================
    function startConsultationFromAppointment(appointmentId) {
        const a = APPOINTMENTS_DATA[appointmentId];
        if (!a) {
            ModalSystem.toast.error('Appointment not found');
            return;
        }
        
        // Check if consultation already exists using CONSULTATION_MAP
        if (CONSULTATION_MAP && CONSULTATION_MAP[appointmentId]) {
            ModalSystem.toast.info('Consultation already exists for this appointment', {
                title: 'Info',
                duration: 3000
            });
            window.location.href = 'consultations.php';
            return;
        }
        
        // Redirect with all parameters including doctor_name
        const params = new URLSearchParams({
            patient_id: a.patient_id,
            appointment_id: a.id,
            employee_id: a.employee_id,
            date: a.date,
            time: a.time,
            doctor_name: a.doctor_name,
            from_appointment: 'true'
        });
        
        window.location.href = `consultations.php?${params.toString()}`;
    }

    // ============================================================
    // FORM VALIDATION
    // ============================================================
    function initFormValidation() {
        if (typeof ModalSystem === 'undefined' || !ModalSystem.validateForm) {
            setTimeout(initFormValidation, 100);
            return;
        }

        // Add Appointment
        ModalSystem.validateForm('addAppointmentModal', {
            fields: {
                'add_patient_search': { type: 'search', hiddenField: 'add_patient_id', label: 'Patient' },
                'add_doctor_search': { type: 'search', hiddenField: 'add_employee_id', label: 'Doctor / Staff' },
                'add_service_type': { label: 'Service Type' },
                'add_appointment_date': { 
                    label: 'Date',
                    validator: function(value) {
                        if (!value) return true;
                        const d = new Date(value + 'T00:00:00');
                        const today = new Date(); today.setHours(0,0,0,0);
                        return d < today ? 'Date cannot be in the past' : true;
                    }
                },
                'add_appointment_time': { label: 'Time' }
            },
            submitButtonId: 'submitAddBtn',
            onSubmit: async function(event, helpers) {
                const submitBtn = document.getElementById('submitAddBtn');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                const payload = {
                    patient_id: parseInt(helpers.getFieldValue('add_patient_search', { type: 'search', hiddenField: 'add_patient_id' })),
                    employee_id: parseInt(helpers.getFieldValue('add_doctor_search', { type: 'search', hiddenField: 'add_employee_id' })),
                    service_type: helpers.getFieldValue('add_service_type', {}),
                    appointment_date: helpers.getFieldValue('add_appointment_date', {}),
                    appointment_time: helpers.getFieldValue('add_appointment_time', {}) + ':00',
                    priority: document.getElementById('add_priority').value,
                    status: document.getElementById('add_status').value,
                    notes: document.getElementById('add_notes').value.trim()
                };

                try {
                    const res = await fetch('../../api/appointments.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        ModalSystem.toast.success('Appointment scheduled!', { title: 'Success', duration: 3000 });
                        ModalSystem.close('addAppointmentModal');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        ModalSystem.toast.error(data.message || 'Failed to schedule appointment', { title: 'Error', duration: 6000 });
                    }
                } catch (err) {
                    console.error('Error saving appointment:', err);
                    ModalSystem.toast.error('Network or server error while saving appointment', { title: 'Error', duration: 6000 });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                }
            }
        });

        // Edit Appointment
        ModalSystem.validateForm('editAppointmentModal', {
            fields: {
                'edit_doctor_search': { type: 'search', hiddenField: 'edit_employee_id', label: 'Doctor / Staff' },
                'edit_service_type': { label: 'Service Type' },
                'edit_appointment_date': { label: 'Date' },
                'edit_appointment_time': { label: 'Time' }
            },
            submitButtonId: 'submitEditBtn',
            onSubmit: async function(event, helpers) {
                const id = document.getElementById('edit_id').value;
                const submitBtn = document.getElementById('submitEditBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';

                const payload = {
                    patient_id: parseInt(document.getElementById('edit_patient_id').value),
                    employee_id: parseInt(helpers.getFieldValue('edit_doctor_search', { type: 'search', hiddenField: 'edit_employee_id' })),
                    service_type: helpers.getFieldValue('edit_service_type', {}),
                    appointment_date: helpers.getFieldValue('edit_appointment_date', {}),
                    appointment_time: helpers.getFieldValue('edit_appointment_time', {}),
                    priority: document.getElementById('edit_priority').value,
                    status: document.getElementById('edit_status').value,
                    notes: document.getElementById('edit_notes').value.trim()
                };

                try {
                    const res = await fetch('../../api/appointments.php?action=update&id=' + id, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    
                    const data = await res.json();
                    
                    if (data.success) {
                        ModalSystem.toast.success('Appointment updated!');
                        ModalSystem.close('editAppointmentModal');
                        setTimeout(() => window.location.reload(), 800);
                    } else {
                        ModalSystem.toast.error(data.message || 'Failed to update', { title: 'Error' });
                    }
                } catch (err) {
                    ModalSystem.toast.error('Network error', { title: 'Error' });
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-check"></i> Save Changes';
                }
            }
        });

        console.log('✅ Form validation initialized');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormValidation);
    } else {
        initFormValidation();
    }

    // ============================================================
    // EDIT APPOINTMENT FUNCTION (fills form)
    // ============================================================
    function editAppointment(id) {
        const a = APPOINTMENTS_DATA[id];
        if (!a) { alert('Appointment not found'); return; }

        document.getElementById('edit_id').value = a.id;
        document.getElementById('edit_patient_id').value = a.patient_id;
        
        const editPatientInput = document.getElementById('edit_patient_name');
        const maskedSpan = document.getElementById('edit_patient_masked_display');
        const realDisplay = getRealDisplay(a.patient_name, a.patient_code);
        const maskedDisplay = getMaskedDisplay(a.patient_name, a.patient_code);
        
        editPatientInput.value = realDisplay;
        editPatientInput.dataset.real = realDisplay;
        maskedSpan.textContent = maskedDisplay;
        
        const shouldBeMasked = (localStorage.getItem('data_masking_enabled') || 'true') === 'true';
        if (shouldBeMasked) {
            editPatientInput.style.color = 'transparent';
            editPatientInput.style.background = '#f1f5f9';
            maskedSpan.style.display = 'block';
        } else {
            editPatientInput.style.color = '';
            maskedSpan.style.display = 'none';
        }
        
        document.getElementById('edit_service_type').value = a.service_type || '';
        document.getElementById('edit_appointment_date').value = a.date;
        document.getElementById('edit_appointment_time').value = a.appointment_time || '09:00';
        document.getElementById('edit_priority').value = a.priority || 'medium';
        document.getElementById('edit_status').value = a.status || 'pending';
        document.getElementById('edit_notes').value = a.notes || '';
        document.getElementById('edit_employee_id').value = a.employee_id || '';
        
        const doctor = DOCTORS.find(d => d.id == a.employee_id);
        document.getElementById('edit_doctor_search').value = doctor ? doctor.name : '';

        ModalSystem.open('editAppointmentModal', { applyMasking: true });
    }

    // ============================================================
    // CHANGE STATUS
    // ============================================================
    function changeAppointmentStatus(id, newStatus) {
        const configs = {
            approved: { title: 'Approve Appointment', msg: 'Approve this appointment?', type: 'info' },
            cancelled: { title: 'Cancel Appointment', msg: 'Cancel this appointment?', type: 'danger' }
        };
        const cfg = configs[newStatus] || { title: 'Update', msg: 'Update?', type: 'info' };

        ModalSystem.confirm(cfg.msg, async () => {
            try {
                const res = await fetch('/capstone/api/appointments.php?action=status&id=' + id, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: newStatus })
                });
                
                const data = await res.json();
                
                if (data.success) {
                    ModalSystem.toast.success('Appointment ' + newStatus + '!');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    ModalSystem.toast.error(data.message || 'Failed');
                }
            } catch (err) {
                console.error('Error:', err);
                ModalSystem.toast.error('Network error');
            }
        }, { title: cfg.title, confirmText: newStatus, type: cfg.type });
    }

    // ============================================================
    // DELETE
    // ============================================================
    async function deleteAppointment(id) {
        ModalSystem.confirm('Cancel this appointment?', async () => {
            try {
                const res = await fetch('/capstone/api/appointments.php?action=delete&id=' + id, {
                    method: 'POST',
                });
                const data = await res.json();
                if (data.success) {
                    ModalSystem.toast.success('Cancelled!');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    ModalSystem.toast.error(data.message || 'Failed');
                }
            } catch (err) {
                console.error('Error:', err);
                ModalSystem.toast.error('Network error');
            }
        }, { title: 'Cancel Appointment', confirmText: 'Cancel', type: 'danger' });
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const searchEl = document.getElementById('searchAppointment');
        const statusEl = document.getElementById('filterStatus');
        const priorityEl = document.getElementById('filterPriority');
        const dateEl = document.getElementById('filterDate');
        
        if (searchEl) searchEl.addEventListener('input', filterAppointments);
        if (statusEl) statusEl.addEventListener('change', filterAppointments);
        if (priorityEl) priorityEl.addEventListener('change', filterAppointments);
        if (dateEl) dateEl.addEventListener('change', filterAppointments);
    });

    function filterAppointments() {
        const search = document.getElementById('searchAppointment')?.value.toLowerCase() || '';
        const status = document.getElementById('filterStatus')?.value.toLowerCase() || '';
        const priority = document.getElementById('filterPriority')?.value.toLowerCase() || '';
        const dateFilter = document.getElementById('filterDate')?.value || '';
        let visibleCount = 0;

        const today = new Date(); today.setHours(0,0,0,0);
        const tomorrow = new Date(today); tomorrow.setDate(tomorrow.getDate()+1);
        const weekEnd = new Date(today); weekEnd.setDate(weekEnd.getDate()+7);

        document.querySelectorAll('.appointment-row').forEach(row => {
            const searchText = (row.dataset.patient + ' ' + row.dataset.doctor + ' ' + 
                               row.dataset.service + ' ' + row.textContent).toLowerCase();
            const rowDate = new Date(row.dataset.date + 'T00:00:00');
            
            let matchesDate = true;
            if (dateFilter === 'today') matchesDate = rowDate.getTime() === today.getTime();
            else if (dateFilter === 'tomorrow') matchesDate = rowDate.getTime() === tomorrow.getTime();
            else if (dateFilter === 'week') matchesDate = rowDate >= today && rowDate <= weekEnd;

            const visible = searchText.includes(search) && 
                           (!status || row.dataset.status === status) &&
                           (!priority || row.dataset.priority === priority) &&
                           matchesDate;
            
            row.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        const emptyState = document.getElementById('emptyState');
        if (emptyState) emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        ['searchAppointment', 'filterStatus', 'filterPriority', 'filterDate'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        document.querySelectorAll('.appointment-row').forEach(row => row.style.display = '');
        const emptyState = document.getElementById('emptyState');
        if (emptyState) emptyState.style.display = 'none';
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo $totalPages; ?>) return;
        window.location.href = '?page=' + page;
    }

    // ============================================================
    // REAL-TIME TRIAGE ASSIGNMENT POLLING
    // Every 30 seconds, check if new triage assignments have come
    // in and auto-refresh the table to show the doctor's new queue
    // ============================================================
    (function startTriagePolling() {
        let lastTriageCount = <?php
            $triageCountForPolling = 0;
            try {
                $pollModel = new Triage();
                $allTriage = $pollModel->all();
                $triageCountForPolling = count(array_filter($allTriage, fn($t) => !empty($t['doctor_id']) || !empty($t['doctor_assigned'])));
            } catch (Throwable $ex) {}
            echo $triageCountForPolling;
        ?>;

        async function checkTriageUpdates() {
            try {
                const res = await fetch('../../api/triage.php?action=count_assigned', { cache: 'no-store' });
                if (!res.ok) return;
                const data = await res.json();
                const newCount = data.count ?? data.total ?? null;
                if (newCount !== null && newCount !== lastTriageCount) {
                    lastTriageCount = newCount;
                    // Show a small toast then reload to reflect new assignments
                    if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                        ModalSystem.toast.info('New patient assignment received — refreshing your queue.', {
                            title: '🔄 Queue Updated',
                            duration: 2500
                        });
                    }
                    setTimeout(() => window.location.reload(), 2600);
                }
            } catch (e) {
                // Silent fail — polling is best-effort
            }
        }

        setInterval(checkTriageUpdates, 30000); // poll every 30 seconds
    })();
</script>
<?php include_once '../../includes/footer.php'; ?>