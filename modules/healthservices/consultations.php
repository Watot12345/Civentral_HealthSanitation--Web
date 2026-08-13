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

// Role check — nurses can only do assessments, NOT create consultations
$_sessionRole = strtolower(trim($_SESSION['role_description'] ?? $_SESSION['role'] ?? ''));
$isNurse = str_contains($_sessionRole, 'nurse') || str_contains($_sessionRole, 'midwife');
$canCreateConsultation = !$isNurse;

require_once __DIR__ . '/../../app/Models/Patient.php';
require_once __DIR__ . '/../../app/Models/Employee.php';
require_once __DIR__ . '/../../app/Models/Consultation.php';
require_once __DIR__ . '/../../app/Models/Triage.php';
require_once __DIR__ . '/../../app/Controllers/ConsultationController.php';

$patientModel = new Patient();
$dbPatients = [];
$patientsJsMap = [];
try {
    $dbPatients = $patientModel->all(['order' => 'first_name.asc']);
    foreach ($dbPatients as $p) {
        if (!isset($p['id'])) continue;
        $age = 0;
        if (!empty($p['birth_date'])) {
            try {
                $dob = new DateTime($p['birth_date']);
                $now = new DateTime();
                $age = $now->diff($dob)->y;
            } catch (Throwable $ex) {}
        }
        $conditions = 'None';
        if (!empty($p['medical_history'])) {
            $history = is_string($p['medical_history']) 
                ? json_decode($p['medical_history'], true) 
                : $p['medical_history'];
            $conditions = $history['conditions'] ?? 'None';
        }
        $patientsJsMap[$p['id']] = [
            'id' => (int)$p['id'],
            'patient_id' => $p['patient_id'] ?? "P-{$p['id']}",
            'first_name' => $p['first_name'] ?? '',
            'last_name' => $p['last_name'] ?? '',
            'gender' => $p['gender'] ?? 'Unspecified',
            'age' => $age,
            'blood_type' => $p['blood_type'] ?? 'N/A',
            'contact' => $p['contact'] ?? 'N/A',
            'email' => $p['email'] ?? 'N/A',
            'address' => $p['address'] ?? 'N/A',
            'barangay' => $p['barangay'] ?? 'N/A',
            'emergency_contact' => $p['emergency_contact'] ?? 'N/A',
            'registration_date' => $p['registration_date'] ?? 'N/A',
            'status' => $p['status'] ?? 'active',
            'allergies' => $p['allergies'] ?? 'None',
            'conditions' => $conditions
        ];
    }
} catch (Throwable $e) {
    error_log('Error loading patients: ' . $e->getMessage());
}
// Fetch Employees/Doctors
$employeeModel = new Employee();
$dbEmployees = [];
$medicalStaff = []; // Attending Doctors / Physicians (filtered by role_description)
try {
    $rawEmployees = $employeeModel->all();
    foreach ($rawEmployees as $e) {
        $roleDesc = strtolower(trim($e['role_description'] ?? ''));
        $role = strtolower(trim($e['role'] ?? ''));
        
        $name = trim($e['full_name'] ?? (($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')));
        if (empty($name)) $name = $e['name'] ?? $e['username'] ?? ("Employee #{$e['id']}");
        $displayName = (str_starts_with($name, 'Dr.') || str_starts_with($name, 'Doctor')) ? $name : ('Dr. ' . $name);

        $e['full_name'] = $displayName;
        $dbEmployees[] = $e;

        // Attending Physicians, Doctors, Dentists, Directors & Admins (based on role_description)
        if ((str_contains($roleDesc, 'doctor') || str_contains($roleDesc, 'dentist') || str_contains($roleDesc, 'health center director') || str_contains($roleDesc, 'system administrator') || str_contains($roleDesc, 'physician')) && !str_contains($roleDesc, 'nurse') && !str_contains($roleDesc, 'technician') && !str_contains($roleDesc, 'sanitation')) {
            $medicalStaff[] = $e;
        }
    }
} catch (Throwable $e) {
    error_log('Error loading employees: ' . $e->getMessage());
    $dbEmployees = [];
    $medicalStaff = [];
}

// Resolve logged in doctor / employee ID
$sessionUserId = $_SESSION['user_id'] ?? null;
$sessionEmployeeId = $_SESSION['employee_id'] ?? null;
$sessionFullName = trim($_SESSION['full_name'] ?? ($_SESSION['name'] ?? ($_SESSION['username'] ?? '')));

$loggedInDoctorId = null;
$loggedInDoctorName = null;

foreach ($dbEmployees as $e) {
    $eId = (string)($e['id'] ?? '');
    $uId = (string)($e['user_id'] ?? '');
    $eName = trim($e['full_name'] ?? (($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')));
    $eUser = trim($e['username'] ?? '');

    if (
        ($sessionEmployeeId && (string)$sessionEmployeeId === $eId) ||
        ($sessionUserId && (string)$sessionUserId === $uId) ||
        (!empty($sessionFullName) && (stripos($eName, $sessionFullName) !== false || stripos($sessionFullName, $eName) !== false || stripos($sessionFullName, $eUser) !== false))
    ) {
        $loggedInDoctorId = (int)$e['id'];
        $loggedInDoctorName = $e['full_name'] ?? $eName;
        break;
    }
}

// Doctor Scoping — doctors only see consultations performed by themselves
$_sessionRoleDesc = strtolower(trim($_SESSION['role_description'] ?? $_SESSION['role'] ?? ''));
$isDoctorRole = (str_contains($_sessionRoleDesc, 'doctor') || str_contains($_sessionRoleDesc, 'physician') || str_contains($_sessionRoleDesc, 'dentist') || str_contains($_sessionRoleDesc, 'medical practitioner'));
$isAdminRole = (str_contains($_sessionRoleDesc, 'admin') || str_contains($_sessionRoleDesc, 'director') || str_contains($_sessionRoleDesc, 'system administrator'));
$isDoctorOnly = ($isDoctorRole && !$isAdminRole);

// Fetch real consultations from database
$consultationModel = new Consultation();
$consultations = [];

try {
    $rawConsultations = $consultationModel->all(['order' => 'date.desc,created_at.desc']);
    
    $patientsMap = [];
    foreach ($dbPatients as $p) {
        if (isset($p['id'])) {
            $patientsMap[$p['id']] = $p;
        }
    }
    
    $employeesMap = [];
    foreach ($dbEmployees as $e) {
        if (isset($e['id'])) {
            $employeesMap[$e['id']] = $e;
        }
    }

    foreach ($rawConsultations as $c) {
        $cEmpId = (int)($c['employee_id'] ?? 0);
        if ($isDoctorOnly && $loggedInDoctorId && $cEmpId > 0 && $cEmpId !== (int)$loggedInDoctorId) {
            continue;
        }

        $patientId = $c['patient_id'] ?? null;
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

        $employeeId = $c['employee_id'] ?? null;
        $employee = $employeesMap[$employeeId] ?? null;
        if ($employee) {
            $docFirst = $employee['first_name'] ?? '';
            $docLast = $employee['last_name'] ?? '';
            $docTitle = $employee['title'] ?? $employee['role'] ?? 'Dr.';
            $doctorName = trim("$docTitle $docFirst $docLast");
            if (empty(trim("$docFirst $docLast"))) {
                $doctorName = $employee['name'] ?? $employee['username'] ?? "Employee #{$employeeId}";
            }
        } else {
            // Default doctor fallback names based on ID or default
            $doctorNames = [
                1 => 'Dr. Elena Santos',
                2 => 'Dr. Miguel Reyes',
                3 => 'Dr. Ana Cruz'
            ];
            $doctorName = $doctorNames[$employeeId] ?? 'Dr. Elena Santos';
        }

        $vitalSigns = $c['vital_signs'] ?? null;
        if (is_string($vitalSigns)) {
            $decoded = json_decode($vitalSigns, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $vitalSigns = $decoded;
            }
        }

        $consultations[] = [
            'id' => (int)($c['id'] ?? 0),
            'consultation_id' => $c['consultation_id'] ?? '',
            'patient_id' => (int)($c['patient_id'] ?? 0),
            'patient_name' => $patientName,
            'patient_code' => $patientCode,
            'patient_avatar' => $avatar,
            'employee_id' => (int)($c['employee_id'] ?? 1),
            'doctor_name' => $doctorName,
            'appointment_id' => !empty($c['appointment_id']) ? (int)$c['appointment_id'] : null,
            'date' => $c['date'] ?? date('Y-m-d'),
            'time' => !empty($c['time']) ? substr($c['time'], 0, 8) : date('H:i:s'),
            'diagnosis' => $c['diagnosis'] ?? 'No diagnosis provided',
            'icd_code' => $c['icd_code'] ?? 'N/A',
            'symptoms' => $c['symptoms'] ?? '',
            'vital_signs' => $vitalSigns,
            'treatment_plan' => $c['treatment_plan'] ?? ($c['treatment'] ?? ''),
            'treatment' => $c['treatment_plan'] ?? ($c['treatment'] ?? ''),
            'notes' => $c['notes'] ?? '',
            'follow_up_date' => $c['follow_up_date'] ?? null,
            'follow_up' => $c['follow_up_date'] ?? null,
            'status' => $c['status'] ?? 'completed',
            'created_at' => $c['created_at'] ?? ''
        ];
    }
} catch (Throwable $e) {
    error_log("Error building consultations list: " . $e->getMessage());
}

// Fetch pending assessments sent from Check-in & Assessment intake
$triageModel = new Triage();
$pendingAssessments = [];
try {
    $rawTriage = $triageModel->all(['order' => 'created_at.desc']);
    foreach ($rawTriage as $t) {
        $st = strtolower($t['status'] ?? 'pending');
        if (in_array($st, ['sent_to_doctor', 'triaged', 'in_triage', 'waiting', 'pending'])) {
            $patientId = $t['patient_id'] ?? null;
            $patient = $patientsMap[$patientId] ?? null;
            
            if ($patient) {
                $firstName = $patient['first_name'] ?? '';
                $lastName = $patient['last_name'] ?? '';
                $patientName = trim("$firstName $lastName");
                $patientCode = $patient['patient_id'] ?? "P-$patientId";
                $dob = $patient['dob'] ?? null;
                $age = $dob ? (date('Y') - date('Y', strtotime($dob))) : ($t['age'] ?? 'N/A');
                $gender = $patient['gender'] ?? ($t['gender'] ?? 'N/A');
            } else {
                $patientName = "Patient #{$patientId}";
                $patientCode = "P-{$patientId}";
                $age = $t['age'] ?? 'N/A';
                $gender = $t['gender'] ?? 'N/A';
            }

            $vitals = $t['vital_signs'] ?? [];
            if (is_string($vitals)) {
                $decoded = json_decode($vitals, true);
                if (json_last_error() === JSON_ERROR_NONE) $vitals = $decoded;
            }

            $pendingAssessments[] = [
                'id' => (int)$t['id'],
                'triage_id' => $t['triage_id'] ?? ('TRG-' . $t['id']),
                'patient_id' => (int)$patientId,
                'patient_name' => $patientName,
                'patient_code' => $patientCode,
                'age' => $age,
                'gender' => $gender,
                'priority' => strtolower($t['priority'] ?? 'medium'),
                'chief_complaint' => $t['chief_complaint'] ?? 'No chief complaint recorded',
                'vitals' => [
                    'bp' => $vitals['blood_pressure'] ?? $vitals['bp'] ?? '120/80',
                    'temp' => $vitals['temperature'] ?? $vitals['temp'] ?? '36.5',
                    'hr' => $vitals['heart_rate'] ?? $vitals['hr'] ?? '75',
                    'weight' => $vitals['weight'] ?? '65',
                    'height' => $vitals['height'] ?? '165',
                    'spo2' => $vitals['oxygen_saturation'] ?? $vitals['spo2'] ?? '98',
                    'rr' => $vitals['respiratory_rate'] ?? $vitals['rr'] ?? '18'
                ],
                'notes' => $t['notes'] ?? $t['initial_assessment'] ?? '',
                'created_at' => $t['created_at'] ?? date('Y-m-d H:i:s')
            ];
        }
    }
} catch (Throwable $e) {
    error_log("Error building pending assessments: " . $e->getMessage());
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 9;
$offset = ($page - 1) * $limit;
$totalConsultations = count($consultations);
$totalPages = ceil($totalConsultations / $limit);
if ($totalPages < 1) $totalPages = 1;
$paginatedConsultations = array_slice($consultations, $offset, $limit);

$title = 'Consultations';

// Derived KPI stats
$servedStatuses = ['completed', 'consulted', 'follow_up', 'referred', 'in_consultation'];
$completedCount = count(array_filter($consultations, fn($c) => in_array(strtolower($c['status']), $servedStatuses)));
$referredCount = count(array_filter($consultations, fn($c) => strtolower($c['status']) === 'referred'));
$todayCount = count(array_filter($consultations, fn($c) => $c['date'] === date('Y-m-d')));
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->
<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-y-auto">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight"><?php echo $isDoctorOnly ? 'My Consultations' : 'Consultations'; ?></h2>
            <p class="text-sm text-slate-500 mt-0.5"><?php echo $isDoctorOnly ? 'View and manage medical consultations performed by you' : 'View and manage all patient consultations and medical notes'; ?></p>
        </div>
        <div class="flex gap-3">
            <?php if ($canCreateConsultation): ?>
            <button onclick="ModalSystem.open('addConsultationModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> New Consultation
            </button>
            <?php else: ?>
            <div class="flex items-center gap-2 px-4 py-2 bg-slate-100 text-slate-400 rounded-lg text-sm font-medium border border-slate-200" title="Only doctors and authorized staff can create consultations">
                <i class="fa-solid fa-lock text-xs"></i> View Only — Assessments are your module
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isDoctorOnly): ?>
    <div class="mb-5 px-4 py-2.5 bg-blue-50/80 border border-blue-200/80 rounded-xl flex items-center justify-between text-xs text-blue-900 font-medium shadow-xs">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-user-doctor text-blue-600 text-sm"></i>
            <span><strong>Doctor Consultation View:</strong> Showing medical consultations performed by <strong><?php echo htmlspecialchars($loggedInDoctorName ?: 'your doctor account'); ?></strong> only.</span>
        </div>
        <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[10px] font-extrabold">My Consultations Only</span>
    </div>
    <?php endif; ?>

    <!-- MODERN KPI CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Consultations -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-stethoscope text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalConsultations; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Consultations</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🩺 All records</span>
                    <span class="text-[10px] text-slate-400"><?php echo $completedCount; ?> completed</span>
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
                        <p class="text-2xl font-black text-emerald-600"><?php echo $completedCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Completed</span>
                    <span class="text-[10px] text-slate-400">Finished cases</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Referred -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-arrow-right-from-bracket text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $referredCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Referred Cases</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">↗️ Referred</span>
                    <span class="text-[10px] text-slate-400">Transferred care</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Today's Consultations -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-sky-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-sky-500 to-sky-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-sky-200">
                        <i class="fa-solid fa-calendar-day text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-sky-600"><?php echo $todayCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Today's Consultations</p>
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
        <div class="flex flex-col gap-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="searchConsultation" placeholder="Search by patient name, consultation ID, diagnosis, or ICD code..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
                </div>
                <div class="flex gap-2 flex-wrap">
                    <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white"><option value="">All Status</option><option value="in_progress">in progress</option><option value="completed">Completed</option><option value="referred">Referred</option></select>
                    <select id="filterDoctor" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
    <option value="">All Doctors</option>
    <?php foreach ($medicalStaff as $doc): 
        $docName = $doc['full_name'] ?? trim(($doc['first_name'] ?? '') . ' ' . ($doc['last_name'] ?? ''));
        if (empty($docName)) $docName = $doc['name'] ?? $doc['username'] ?? "Employee #{$doc['id']}";
    ?>
        <option value="<?php echo htmlspecialchars(strtolower($docName)); ?>"><?php echo htmlspecialchars($docName); ?></option>
    <?php endforeach; ?>
</select>
                    <button onclick="resetFilters()" title="Reset filters" class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm"><i class="fa-solid fa-rotate-right"></i></button>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Consultation Date:</span>
                <button onclick="setDateFilter('today')" class="date-filter-btn px-3 py-1.5 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all"><i class="fa-solid fa-calendar-day mr-1"></i> Today</button>
                <button onclick="setDateFilter('week')" class="date-filter-btn px-3 py-1.5 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all"><i class="fa-solid fa-calendar-week mr-1"></i> This Week</button>
                <button onclick="setDateFilter('month')" class="date-filter-btn px-3 py-1.5 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all"><i class="fa-solid fa-calendar mr-1"></i> This Month</button>   
                <button onclick="setDateFilter('all')" class="date-filter-btn px-3 py-1.5 text-xs rounded-lg border border-slate-200 text-slate-400 hover:bg-slate-100 transition-all"><i class="fa-solid fa-times mr-1"></i> All</button>
                <span id="activeDateFilter" class="text-xs text-brand-medium font-semibold hidden"><i class="fa-solid fa-filter mr-1"></i> <span id="activeDateFilterLabel">Today</span></span>
            </div>
        </div>
    </div>

    <!-- Consultations Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="consultationGrid">
        <?php if (empty($paginatedConsultations)): ?>
            <div class="col-span-full bg-white rounded-2xl p-12 text-center border border-slate-200 shadow-xs">
                <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center mx-auto mb-4 text-brand-medium text-2xl"><i class="fa-solid fa-stethoscope"></i></div>
                <h3 class="text-lg font-bold text-slate-800">No consultations recorded yet</h3>
                <?php if ($canCreateConsultation): ?>
                <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">Click "New Consultation" above to create your first patient consultation record.</p>
                <button onclick="ModalSystem.open('addConsultationModal')" class="mt-4 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium text-sm font-semibold inline-flex items-center gap-2"><i class="fa-solid fa-plus text-xs"></i> New Consultation</button>
                <?php else: ?>
                <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">Consultations are recorded by doctors. Your role allows you to perform <strong>patient assessments (triage)</strong> only.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($paginatedConsultations as $c): ?>
            <div class="consultation-card bg-white rounded-xl shadow-xs border border-slate-200 p-4 hover:shadow-md transition-all duration-200 flex flex-col justify-between"
                 data-patient="<?php echo htmlspecialchars(strtolower($c['patient_code'])); ?>"
                 data-doctor="<?php echo htmlspecialchars(strtolower($c['doctor_name'])); ?>"
                 data-diagnosis="<?php echo htmlspecialchars(strtolower($c['diagnosis'])); ?>"
                 data-icd="<?php echo htmlspecialchars(strtolower($c['icd_code'])); ?>"
                 data-status="<?php echo htmlspecialchars($c['status']); ?>"
                 data-date="<?php echo htmlspecialchars($c['date']); ?>">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0"><?php echo htmlspecialchars($c['patient_avatar']); ?></div>
                            <div>
                                <button type="button" onclick="openPatientProfile(<?php echo $c['patient_id']; ?>)" class="text-left group block">
                                    <p class="font-semibold text-slate-800 text-sm line-clamp-1 group-hover:text-brand-medium transition maskable" data-real="<?php echo htmlspecialchars($c['patient_name']); ?>" data-masked="<?php echo htmlspecialchars(maskName($c['patient_name'])); ?>"><?php echo htmlspecialchars(maskName($c['patient_name'])); ?></p>
                                </button>
                                <p class="text-xs text-slate-400 font-mono"><?php echo htmlspecialchars($c['consultation_id']); ?></p>
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo strtolower($c['status']) === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>"><?php echo htmlspecialchars(ucfirst($c['status'])); ?></span>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Doctor / Staff</span>
                            <span class="text-slate-800 font-medium"><?php echo htmlspecialchars($c['doctor_name']); ?></span>
                        </div>
                        <div class="flex justify-between"><span class="text-slate-500">Date & Time</span><span class="text-slate-800"><?php echo date('M d, Y', strtotime($c['date'])) . ' ' . date('h:i A', strtotime($c['time'])); ?></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">ICD-10 Code</span><span class="text-slate-800 font-mono font-bold"><?php echo htmlspecialchars($c['icd_code']); ?></span></div>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-100"><p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Diagnosis</p><p class="text-xs text-slate-800 font-semibold line-clamp-1 mt-0.5"><?php echo htmlspecialchars($c['diagnosis']); ?></p></div>
                    <div class="mt-2"><p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Treatment Plan</p><p class="text-xs text-slate-600 line-clamp-2 mt-0.5"><?php echo htmlspecialchars($c['treatment_plan'] ?: 'None recorded'); ?></p></div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <button onclick="viewConsultation(<?php echo $c['id']; ?>)" class="px-2 py-1 text-xs font-semibold text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details"><i class="fa-solid fa-eye mr-1"></i> View</button>
                        <button onclick="editConsultation(<?php echo $c['id']; ?>)" class="px-2 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition" title="Edit Record"><i class="fa-solid fa-pen mr-1"></i> Edit</button>
                    </div>
                    <div class="flex items-center gap-1 flex-wrap">
                        <button onclick="openPatientProfile(<?php echo $c['patient_id']; ?>)" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="View Patient Profile"><i class="fa-solid fa-address-card text-xs"></i></button>
                        <button onclick="issuePrescription(<?php echo $c['patient_id']; ?>, <?php echo $c['id']; ?>)" class="p-1.5 text-teal-600 hover:bg-teal-50 rounded-lg transition" title="Issue Prescription (Optional)"><i class="fa-solid fa-pills text-xs"></i></button>
                        <button onclick="createReferral(<?php echo $c['patient_id']; ?>, <?php echo $c['id']; ?>)" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Create Referral (Optional)"><i class="fa-solid fa-arrow-right-from-bracket text-xs"></i></button>
                        <button onclick="scheduleFollowUp(<?php echo $c['patient_id']; ?>, <?php echo $c['id']; ?>)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Schedule Follow-up"><i class="fa-solid fa-calendar-plus text-xs"></i></button>
                        <button onclick="openMedicalRecord(<?php echo $c['patient_id']; ?>)" class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Medical Record Archive"><i class="fa-solid fa-folder-open text-xs"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center"><div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3"><i class="fa-solid fa-stethoscope text-slate-400"></i></div><p class="text-sm font-semibold text-slate-600">No consultations match your search or filter</p><p class="text-xs text-slate-400 mt-1">Try clearing or adjusting your search criteria</p><button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear filters</button></div>

    <?php if ($totalConsultations > 0): ?>
    <div class="mt-6 px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-white rounded-xl shadow-xs border border-slate-200"><p class="text-xs text-slate-500">Showing <span class="font-semibold text-slate-700"><?php echo min($offset + 1, $totalConsultations); ?></span> to <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalConsultations); ?></span> of <span class="font-semibold text-slate-700"><?php echo $totalConsultations; ?></span> consultations</p><div class="flex gap-1"><button onclick="changePage(<?php echo $page - 1; ?>)" class="px-3 py-1.5 rounded-lg text-sm <?php echo $page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>><i class="fa-solid fa-chevron-left text-xs"></i></button><?php for ($i = 1; $i <= $totalPages; $i++): ?><button onclick="changePage(<?php echo $i; ?>)" class="px-3 py-1.5 rounded-lg text-sm font-medium <?php echo $i === $page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"><?php echo $i; ?></button><?php endfor; ?><button onclick="changePage(<?php echo $page + 1; ?>)" class="px-3 py-1.5 rounded-lg text-sm <?php echo $page >= $totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>><i class="fa-solid fa-chevron-right text-xs"></i></button></div></div>
    <?php endif; ?>
</div>

<!-- VIEW CONSULTATION MODAL -->
<div id="viewConsultationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10"><h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-notes-medical text-brand-medium"></i> Consultation Details</h3><button onclick="ModalSystem.close('viewConsultationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div><div id="consultationDetailsContent" class="p-6"><div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading consultation details...</div></div></div></div>

<!-- PATIENT PROFILE MODAL IN CONSULTATIONS -->
<div id="consultationPatientProfileModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-address-card text-brand-medium"></i> Patient Profile Overview
            </h3>
            <button onclick="ModalSystem.close('consultationPatientProfileModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="consultationPatientProfileContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading profile...</div>
        </div>
    </div>
</div>

<!-- ADD CONSULTATION MODAL -->
<?php if ($canCreateConsultation): ?>
<div id="addConsultationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10"><h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-stethoscope text-brand-medium"></i> Doctor Consultation Record</h3><button onclick="ModalSystem.close('addConsultationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<form id="addConsultationForm" class="p-6 space-y-4">
<input type="hidden" id="add_appointment_id" value="">
<input type="hidden" id="add_triage_id" value="">

<!-- RECORDED ASSESSMENT VITALS CARD (PRE-LOADED FROM CHECK-IN & ASSESSMENT) -->
<div id="assessmentVitalsCard" class="hidden p-4 bg-teal-50 border border-teal-200 rounded-xl">
    <div class="flex items-center justify-between mb-2">
        <h4 class="text-xs font-bold text-teal-900 uppercase tracking-wider flex items-center gap-1.5">
            <i class="fa-solid fa-heart-pulse text-teal-600"></i> Recorded Patient Check-in & Assessment Vitals
        </h4>
        <span id="assessment_time_badge" class="text-[10px] font-semibold text-teal-700 bg-teal-100 px-2 py-0.5 rounded-full">Pre-loaded Intake Vitals</span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs text-slate-700 mb-2">
        <div class="bg-white p-2 rounded-lg border border-teal-100"><span class="text-slate-400 block text-[10px]">Blood Pressure</span><strong id="vitals_bp_display" class="text-slate-900 font-mono text-xs">-</strong></div>
        <div class="bg-white p-2 rounded-lg border border-teal-100"><span class="text-slate-400 block text-[10px]">Temperature</span><strong id="vitals_temp_display" class="text-slate-900 text-xs">-</strong></div>
        <div class="bg-white p-2 rounded-lg border border-teal-100"><span class="text-slate-400 block text-[10px]">Heart Rate</span><strong id="vitals_hr_display" class="text-slate-900 text-xs">-</strong></div>
        <div class="bg-white p-2 rounded-lg border border-teal-100"><span class="text-slate-400 block text-[10px]">Weight / Height</span><strong id="vitals_weight_display" class="text-slate-900 text-xs">-</strong></div>
    </div>
    <div class="bg-white p-2.5 rounded-lg border border-teal-100 text-xs">
        <span class="text-slate-400 block text-[10px] font-bold uppercase">Chief Complaint</span>
        <p id="vitals_complaint_display" class="text-slate-800 font-medium text-xs mt-0.5">-</p>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient <span class="text-rose-500">*</span></label><select id="add_patient_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="">Select Patient</option><?php foreach ($dbPatients as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?> (<?php echo htmlspecialchars($p['patient_id'] ?? "P-{$p['id']}"); ?>)</option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Attending Doctor / Staff <span class="text-rose-500">*</span></label><select id="add_employee_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="">Select Doctor / Staff</option><?php foreach ($medicalStaff as $e): $displayName = $e['full_name'] ?? $e['name'] ?? "Employee #{$e['id']}"; $isSelected = ($loggedInDoctorId && (int)$e['id'] === (int)$loggedInDoctorId); ?><option value="<?php echo $e['id']; ?>" <?php echo $isSelected ? 'selected' : ''; ?>><?php echo htmlspecialchars($displayName); ?> (<?php echo htmlspecialchars($e['role_description'] ?? 'Doctor'); ?>)</option><?php endforeach; ?></select></div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date <span class="text-rose-500">*</span></label><input type="date" id="add_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time <span class="text-rose-500">*</span></label><input type="time" id="add_time" value="<?php echo date('H:i'); ?>" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Chief Complaints / Symptoms</label><input type="text" id="add_symptoms" placeholder="e.g., Fever, persistent cough, headache" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Diagnosis <span class="text-rose-500">*</span></label><input type="text" id="add_diagnosis" required placeholder="Primary diagnosis" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ICD-10 Code</label>
        <div class="flex gap-2">
            <input type="text" id="add_icd_code" placeholder="e.g., J06.9, I10" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-mono uppercase">
            <button type="button" onclick="suggestIcdCode()" class="px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shrink-0" title="Suggest ICD-10 code based on diagnosis">
                <i class="fa-solid fa-wand-magic-sparkles text-indigo-500"></i> AI Suggest
            </button>
        </div>
    </div>
    <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="add_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="completed">Completed</option><option value="in_progress">In Progress</option><option value="referred">Referred</option><option value="follow_up">Follow-up Needed</option></select></div>
</div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Treatment Plan & Clinical Notes</label><textarea id="add_treatment_plan" rows="2" placeholder="Medications prescribed, rest, lab tests ordered..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Follow-up Date</label><input type="date" id="add_follow_up_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Clinical Notes</label><input type="text" id="add_notes" placeholder="Additional doctor observations" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>

<!-- ACTION TRIGGERS FOR PRESCRIPTION & REFERRAL -->
<div class="pt-3 border-t border-slate-100 flex flex-wrap gap-4 text-xs font-semibold text-slate-700 bg-slate-50/70 p-3 rounded-xl">
    <label class="flex items-center gap-2 cursor-pointer hover:text-brand-dark transition">
        <input type="checkbox" id="add_create_prescription" class="w-4 h-4 text-brand-medium rounded border-slate-300 focus:ring-brand-medium">
        <span><i class="fa-solid fa-pills text-teal-600 mr-1"></i> Issue Prescription after saving</span>
    </label>
    <label class="flex items-center gap-2 cursor-pointer hover:text-brand-dark transition">
        <input type="checkbox" id="add_create_referral" class="w-4 h-4 text-brand-medium rounded border-slate-300 focus:ring-brand-medium">
        <span><i class="fa-solid fa-arrow-right-from-bracket text-amber-600 mr-1"></i> Create Referral Form after saving</span>
    </label>
</div>

<div class="flex justify-end gap-2 pt-3 border-t border-slate-100"><button type="button" onclick="ModalSystem.close('addConsultationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="submit" id="submitAddBtn" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5"><i class="fa-solid fa-check"></i> Save Consultation</button></div>
</form></div></div>
<?php endif; // $canCreateConsultation ?>

<!-- EDIT CONSULTATION MODAL -->
<div id="editConsultationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10"><h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-brand-medium"></i> Edit Consultation</h3><button onclick="ModalSystem.close('editConsultationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<!-- FIXED: Removed onsubmit -->
<form id="editConsultationForm" class="p-6 space-y-4">
<input type="hidden" id="edit_id"><input type="hidden" id="edit_appointment_id" value="">
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label><input type="text" id="edit_patient_name" readonly class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-700 outline-none font-semibold cursor-not-allowed"><input type="hidden" id="edit_patient_id"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Attending Doctor / Staff</label><select id="edit_employee_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="">Select Doctor / Staff</option><?php foreach ($medicalStaff as $e): $displayName = $e['full_name'] ?? $e['name'] ?? "Employee #{$e['id']}"; ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($displayName); ?> (<?php echo htmlspecialchars($e['role_description'] ?? 'Doctor'); ?>)</option><?php endforeach; ?></select></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label><input type="date" id="edit_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time</label><input type="time" id="edit_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Symptoms</label><input type="text" id="edit_symptoms" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Diagnosis</label><input type="text" id="edit_diagnosis" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ICD-10 Code</label><input type="text" id="edit_icd_code" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-mono uppercase"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="referred">Referred</option><option value="follow_up">Follow-up Needed</option></select></div></div>
<div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50"><label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2 flex items-center gap-1.5"><i class="fa-solid fa-heart-pulse text-rose-500"></i> Vital Signs</label><div class="grid grid-cols-2 sm:grid-cols-4 gap-3"><div><span class="text-[10px] text-slate-500">BP (mmHg)</span><input type="text" id="edit_bp" maxlength="7" pattern="[0-9]{2,3}/[0-9]{2,3}" inputmode="numeric" placeholder="120/80" class="vital-bp w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Heart Rate (bpm)</span><input type="text" id="edit_hr" maxlength="3" inputmode="numeric" placeholder="72" class="vital-number w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Temp (°C)</span><input type="text" id="edit_temp" maxlength="5" inputmode="decimal" placeholder="36.5" class="vital-decimal w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Weight (kg)</span><input type="text" id="edit_weight" maxlength="5" inputmode="decimal" placeholder="65" class="vital-decimal w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div></div></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Treatment Plan</label><textarea id="edit_treatment_plan" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Follow-up Date</label><input type="date" id="edit_follow_up_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Clinical Notes</label><input type="text" id="edit_notes" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="flex justify-end gap-2 pt-3 border-t border-slate-100"><button type="button" onclick="ModalSystem.close('editConsultationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="submit" id="submitEditBtn" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5"><i class="fa-solid fa-check"></i> Save Changes</button></div>
</form></div></div>
<!-- ============================================================ -->
<!-- 3. CSS STYLES                                                -->
<!-- ============================================================ -->
<style>
    .date-filter-btn.active {
        background-color: #14807A;
        border-color: #14807A;
        color: white;
    }
    .date-filter-btn.active:hover {
        background-color: #0B4F4A;
        border-color: #0B4F4A;
        color: white;
    }
</style>

<!-- ============================================================ -->
<!-- 4. JAVASCRIPT                                                -->
<!-- ============================================================ -->
<script>
    const CONSULTATIONS_DATA = <?php echo json_encode(array_column($consultations, null, 'id'), JSON_UNESCAPED_UNICODE); ?>;
    const PATIENTS_MAP = <?php echo json_encode($patientsJsMap, JSON_UNESCAPED_UNICODE); ?>;
    let activeDateFilter = 'all';
   
    // ============================================================
    // MASKING HELPERS
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

    // ============================================================
    // PATIENT PROFILE MODAL IN CONSULTATIONS
    // ============================================================
    function openPatientProfile(patientId) {
        ModalSystem.open('consultationPatientProfileModal');
        const content = document.getElementById('consultationPatientProfileContent');
        content.innerHTML = '<div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading profile...</div>';
        
        setTimeout(() => {
            const p = PATIENTS_MAP[patientId];
            if (!p) {
                content.innerHTML = '<p class="text-sm text-rose-500 text-center py-10">Patient profile details not found.</p>';
                return;
            }
            const fName = p.first_name || '';
            const lName = p.last_name || '';
            const initials = (((fName[0] || 'P')) + ((lName[0] || 'T'))).toUpperCase();
            const fullName = `${fName} ${lName}`.trim() || ('Patient #' + p.id);
            const statusBadge = p.status === 'active' 
                ? '<span class="inline-block px-2.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold mt-1">Active</span>' 
                : '<span class="inline-block px-2.5 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs font-semibold mt-1">Inactive</span>';
            
            content.innerHTML = `
                <div class="space-y-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">${initials}</div>
                            <div>
                                <h4 class="text-lg font-bold text-slate-900 maskable" data-real="${fullName}" data-masked="${maskPatientName(fullName)}">${maskPatientName(fullName)}</h4>
                                <p class="text-xs text-slate-500 font-mono">${p.patient_id} &bull; ${p.gender} &bull; ${p.age} yrs old</p>
                                ${statusBadge}
                            </div>
                        </div>
                        <a href="patients.php?patient=${p.id}&autoView=true" class="px-3.5 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5 shrink-0 shadow-xs">
                            <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View Patient Info
                        </a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Contact</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.contact}" data-masked="${maskPatientName(p.contact)}">${maskPatientName(p.contact)}</p></div>
                        <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Email</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.email}" data-masked="${maskPatientName(p.email)}">${maskPatientName(p.email)}</p></div>
                        <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Blood Type</p><p class="text-slate-800 font-bold text-rose-600 mt-0.5">${p.blood_type}</p></div>
                        <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Barangay</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.barangay}" data-masked="${maskPatientName(p.barangay)}">${maskPatientName(p.barangay)}</p></div>
                        <div class="md:col-span-2"><p class="text-slate-400 font-semibold uppercase text-[10px]">Address</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.address}" data-masked="${maskPatientName(p.address)}">${maskPatientName(p.address)}</p></div>
                        <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Emergency Contact</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.emergency_contact}" data-masked="${maskPatientName(p.emergency_contact)}">${maskPatientName(p.emergency_contact)}</p></div>
                        <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Registration Date</p><p class="text-slate-800 font-medium mt-0.5">${p.registration_date}</p></div>
                    </div>
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border text-xs">
                        <h5 class="font-bold text-slate-700 mb-2 flex items-center gap-1.5"><i class="fa-solid fa-notes-medical text-brand-medium"></i> Medical Information</h5>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Allergies</p><p class="text-slate-800 font-medium mt-0.5">${p.allergies}</p></div>
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Existing Conditions</p><p class="text-slate-800 font-medium mt-0.5">${p.conditions}</p></div>
                        </div>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                        <button type="button" onclick="ModalSystem.close('consultationPatientProfileModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-xs font-semibold">Close</button>
                        <a href="patients.php?patient=${p.id}&autoView=true" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-users text-xs"></i> Open Full Profile in Patients Page
                        </a>
                    </div>
                </div>
            `;
            if (typeof ModalSystem !== 'undefined' && ModalSystem.refreshMasking) {
                ModalSystem.refreshMasking('consultationPatientProfileModal');
            }
        }, 150);
    }

    // View Details Modal
    function viewConsultation(id) {
        ModalSystem.open('viewConsultationModal');
        const c = CONSULTATIONS_DATA[id];
        const content = document.getElementById('consultationDetailsContent');

        if (!c) {
            content.innerHTML = `<p class="text-center text-slate-500 py-6">Consultation details not found.</p>`;
            return;
        }

        let vitalsHtml = 'N/A';
        if (c.vital_signs) {
            if (typeof c.vital_signs === 'object') {
                const parts = [];
                if (c.vital_signs.bp) parts.push(`BP: <strong>${c.vital_signs.bp}</strong>`);
                if (c.vital_signs.hr) parts.push(`Heart Rate: <strong>${c.vital_signs.hr} bpm</strong>`);
                if (c.vital_signs.temp) parts.push(`Temp: <strong>${c.vital_signs.temp} °C</strong>`);
                if (c.vital_signs.weight) parts.push(`Weight: <strong>${c.vital_signs.weight} kg</strong>`);
                if (parts.length > 0) vitalsHtml = parts.join(' • ');
            } else {
                vitalsHtml = c.vital_signs;
            }
        }

        content.innerHTML = `
            <div class="space-y-5">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-lg flex-shrink-0">${c.patient_avatar || 'PT'}</div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 maskable" data-real="${c.patient_name}" data-masked="${maskPatientName(c.patient_name)}">${maskPatientName(c.patient_name)}</h4>
                        <p class="text-xs text-slate-500 font-mono maskable" data-real="${c.patient_code}" data-masked="${maskPatientCode(c.patient_code)}">${c.consultation_id} • ${maskPatientCode(c.patient_code)}</p>
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-semibold mt-1 ${c.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${c.status ? c.status.toUpperCase() : 'COMPLETED'}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                    <div><p class="text-slate-400 font-semibold uppercase">Attending Doctor / Staff</p><p class="text-slate-800 font-bold mt-0.5">${c.doctor_name}</p></div>
                    <div><p class="text-slate-400 font-semibold uppercase">Date & Time</p><p class="text-slate-800 font-semibold mt-0.5">${c.date} ${c.time}</p></div>
                    <div><p class="text-slate-400 font-semibold uppercase">ICD-10 Code</p><p class="text-slate-800 font-mono font-bold mt-0.5">${c.icd_code || 'N/A'}</p></div>
                    <div><p class="text-slate-400 font-semibold uppercase">Follow-up Date</p><p class="text-slate-800 font-semibold mt-0.5">${c.follow_up_date || c.follow_up || 'None scheduled'}</p></div>
                    <div class="col-span-2"><p class="text-slate-400 font-semibold uppercase">Vital Signs</p><p class="text-slate-800 mt-0.5">${vitalsHtml}</p></div>
                </div>
                ${c.symptoms ? `<div><h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Chief Complaints / Symptoms</h5><p class="text-sm text-slate-800 bg-slate-50 p-3 rounded-lg border border-slate-200">${c.symptoms}</p></div>` : ''}
                <div><h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Diagnosis</h5><p class="text-sm font-semibold text-slate-900 bg-emerald-50/60 p-3 rounded-lg border border-emerald-100">${c.diagnosis}</p></div>
                <div><h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Treatment Plan</h5><p class="text-sm text-slate-800 bg-brand-light/30 p-3 rounded-lg border border-brand-border">${c.treatment_plan || c.treatment || 'No treatment plan recorded.'}</p></div>
                ${c.notes ? `<div><h5 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Clinical Notes</h5><p class="text-sm text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-200">${c.notes}</p></div>` : ''}
                <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100">
                    <div class="flex gap-2 flex-wrap">
                        <button onclick="openPatientProfile(${c.patient_id})" class="px-3 py-1.5 bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 rounded-lg transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-address-card text-indigo-600"></i> Patient Profile
                        </button>
                        <button onclick="issuePrescription(${c.patient_id}, ${c.id})" class="px-3 py-1.5 bg-teal-50 border border-teal-200 text-teal-700 hover:bg-teal-100 rounded-lg transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-pills text-teal-600"></i> Issue Prescription
                        </button>
                        <button onclick="createReferral(${c.patient_id}, ${c.id})" class="px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 rounded-lg transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-right-from-bracket text-amber-600"></i> Create Referral
                        </button>
                        <button onclick="scheduleFollowUp(${c.patient_id}, ${c.id})" class="px-3 py-1.5 bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 rounded-lg transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-calendar-plus text-blue-600"></i> Schedule Follow-up
                        </button>
                    </div>
                    <div class="flex gap-2 flex-wrap">
                        <button onclick="openMedicalRecord(${c.patient_id})" class="px-3 py-1.5 bg-purple-50 border border-purple-200 text-purple-700 hover:bg-purple-100 rounded-lg transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-folder-open text-purple-600"></i> Medical Record Archive
                        </button>
                        <button onclick="ModalSystem.close('viewConsultationModal'); editConsultation(${c.id});" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-pen"></i> Edit Record
                        </button>
                    </div>
                </div>
            </div>`;
        
        setTimeout(() => { if (typeof ModalSystem !== 'undefined' && ModalSystem.refreshMasking) ModalSystem.refreshMasking('viewConsultationModal'); }, 100);
    }

    // ============================================================
    // WORKFLOW NAVIGATION & OUTCOME HELPERS
    // ============================================================
    function issuePrescription(patientId, consultationId) {
        window.location.href = `prescriptions.php?patient_id=${patientId}&consultation_id=${consultationId}&action=new`;
    }

    function createReferral(patientId, consultationId) {
        if (consultationId && typeof CONSULTATIONS_DATA !== 'undefined' && CONSULTATIONS_DATA[consultationId]) {
            const c = CONSULTATIONS_DATA[consultationId];
            if (c.status && String(c.status).toLowerCase() === 'completed') {
                const msg = 'Cannot create a referral for a completed consultation transaction.';
                if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                    ModalSystem.toast.warning(msg, { title: 'Transaction Finalized' });
                } else if (typeof toast !== 'undefined') {
                    toast.warning(msg);
                } else {
                    alert(msg);
                }
                return;
            }
        }
        window.location.href = `referrals.php?patient_id=${patientId}&consultation_id=${consultationId}&action=new`;
    }

    function scheduleFollowUp(patientId, consultationId) {
        window.location.href = `appointments.php?patient_id=${patientId}&consultation_id=${consultationId}&action=new`;
    }

    function openMedicalRecord(patientId) {
        window.location.href = `medical_records.php?patient_id=${patientId}`;
    }

    // ============================================================
    // ADD CONSULTATION
    // ============================================================
    function validateVitalSigns({ bp, hr, temp, weight }) {
        if (bp && !/^\d{2,3}\/\d{2,3}$/.test(bp)) return 'Blood pressure must use the format 120/80';
        if (bp) {
            const [systolic, diastolic] = bp.split('/').map(Number);
            if (systolic < 50 || systolic > 300 || diastolic < 30 || diastolic > 200) {
                return 'Blood pressure must be between 50/30 and 300/200 mmHg';
            }
        }
        if (hr && (!/^\d+$/.test(hr) || Number(hr) < 20 || Number(hr) > 250)) {
            return 'Heart rate must be between 20 and 250 bpm';
        }
        if (temp && (!/^\d+(\.\d{1,2})?$/.test(temp) || Number(temp) < 25 || Number(temp) > 45)) {
            return 'Temperature must be between 25 and 45 °C';
        }
        if (weight && (!/^\d+(\.\d{1,2})?$/.test(weight) || Number(weight) < 0.1 || Number(weight) > 500)) {
            return 'Weight must be between 0.1 and 500 kg';
        }
        return null;
    }

    document.addEventListener('input', event => {
        const input = event.target;
        if (input.matches('.vital-bp')) {
            input.value = input.value.replace(/[^\d/]/g, '').slice(0, 7);
        } else if (input.matches('.vital-number')) {
            input.value = input.value.replace(/\D/g, '').slice(0, 3);
        } else if (input.matches('.vital-decimal')) {
            const cleaned = input.value.replace(/[^\d.]/g, '');
            const parts = cleaned.split('.');
            input.value = parts.shift() + (parts.length ? '.' + parts.join('').slice(0, 2) : '');
        }
    });

    function startConsultationFromTriage(pa) {
        if (!pa) return;
        
        // Select patient
        const patientSelect = document.getElementById('add_patient_id');
        if (patientSelect && pa.patient_id) {
            for (let i = 0; i < patientSelect.options.length; i++) {
                if (String(patientSelect.options[i].value) === String(pa.patient_id)) {
                    patientSelect.selectedIndex = i;
                    break;
                }
            }
        }

        // Select doctor if assigned in triage
        const doctorSelect = document.getElementById('add_employee_id');
        if (doctorSelect && (pa.doctor_id || pa.doctor_assigned)) {
            let matched = false;
            if (pa.doctor_id) {
                const docId = String(pa.doctor_id);
                for (let i = 0; i < doctorSelect.options.length; i++) {
                    if (String(doctorSelect.options[i].value) === docId) {
                        doctorSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            if (!matched && pa.doctor_assigned) {
                for (let i = 0; i < doctorSelect.options.length; i++) {
                    if (doctorSelect.options[i].text.toLowerCase().includes(pa.doctor_assigned.toLowerCase())) {
                        doctorSelect.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            if (!matched && (pa.doctor_id || pa.doctor_assigned)) {
                const opt = document.createElement('option');
                opt.value = pa.doctor_id ? String(pa.doctor_id) : '';
                opt.textContent = pa.doctor_assigned ? (pa.doctor_assigned.startsWith('Dr.') ? pa.doctor_assigned : 'Dr. ' + pa.doctor_assigned) : `Doctor #${pa.doctor_id}`;
                opt.selected = true;
                doctorSelect.appendChild(opt);
                doctorSelect.value = opt.value;
            }
        }

        // Set hidden IDs & symptoms
        document.getElementById('add_triage_id').value = pa.id || '';
        document.getElementById('add_symptoms').value = pa.chief_complaint || '';

        // Vitals
        const bp = pa.vitals?.bp || '';
        const temp = pa.vitals?.temp || '';
        const hr = pa.vitals?.hr || '';
        const weight = pa.vitals?.weight || '';
        const height = pa.vitals?.height || '';

        // Vital sign fields removed from add form — skip setting them

        // Display Assessment Vitals Card
        const vitalsCard = document.getElementById('assessmentVitalsCard');
        if (vitalsCard) {
            document.getElementById('vitals_bp_display').textContent = bp || 'N/A';
            document.getElementById('vitals_temp_display').textContent = (temp && temp !== 'N/A') ? (temp + ' °C') : 'N/A';
            document.getElementById('vitals_hr_display').textContent = (hr && hr !== 'N/A') ? (hr + ' bpm') : 'N/A';
            document.getElementById('vitals_weight_display').textContent = (weight && weight !== 'N/A') ? (weight + ' kg / ' + (height !== 'N/A' ? height + ' cm' : '')) : 'N/A';
            document.getElementById('vitals_complaint_display').textContent = pa.chief_complaint || 'No complaint recorded';
            vitalsCard.classList.remove('hidden');
        }

        ModalSystem.open('addConsultationModal');
        if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
            ModalSystem.toast.info('Patient vitals and assessment pre-loaded for consultation', { title: '🩺 Patient Ready', duration: 3000 });
        }
    }

    function suggestIcdCode() {
        const symptoms = (document.getElementById('add_symptoms')?.value || '').toLowerCase();
        const diagnosis = (document.getElementById('add_diagnosis')?.value || '').toLowerCase();
        const text = symptoms + ' ' + diagnosis;
        
        let suggestedCode = 'J06.9';
        let suggestedLabel = 'Acute upper respiratory infection';

        if (text.includes('fever') || text.includes('cough') || text.includes('flu') || text.includes('sipon') || text.includes('trangkaso')) {
            suggestedCode = 'J06.9';
            suggestedLabel = 'Acute upper respiratory infection, unspecified';
        } else if (text.includes('diabetes') || text.includes('sugar') || text.includes('glucose')) {
            suggestedCode = 'E11.9';
            suggestedLabel = 'Type 2 diabetes mellitus without complications';
        } else if (text.includes('hypertension') || text.includes('bp') || text.includes('high blood') || text.includes('presyon')) {
            suggestedCode = 'I10';
            suggestedLabel = 'Essential (primary) hypertension';
        } else if (text.includes('diarrhea') || text.includes('stomach') || text.includes('lbm') || text.includes('tae') || text.includes('vomit')) {
            suggestedCode = 'A09';
            suggestedLabel = 'Infectious gastroenteritis and colitis, unspecified';
        } else if (text.includes('pneumonia') || text.includes('pulmonya') || text.includes('hina sa baga')) {
            suggestedCode = 'J18.9';
            suggestedLabel = 'Pneumonia, unspecified organism';
        } else if (text.includes('gastritis') || text.includes('acid') || text.includes('ulcer') || text.includes('sikmura')) {
            suggestedCode = 'K29.7';
            suggestedLabel = 'Gastritis, unspecified';
        }

        const icdInput = document.getElementById('add_icd_code');
        if (icdInput) {
            icdInput.value = suggestedCode;
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.success(`Suggested ICD-10: ${suggestedCode} (${suggestedLabel})`, { title: '🪄 AI ICD-10 Suggestion', duration: 4000 });
            }
        }
    }

    async function saveNewConsultation(event) {
        event.preventDefault();


        // Vital signs fields removed from add form — no validation needed

        const submitBtn = document.getElementById('submitAddBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...`;


        const appointmentId = document.getElementById('add_appointment_id')?.value || null;
        const triageId = document.getElementById('add_triage_id')?.value || null;
        const createPrescription = document.getElementById('add_create_prescription')?.checked || false;
        const createReferral = document.getElementById('add_create_referral')?.checked || false;

        const payload = {
            patient_id: parseInt(document.getElementById('add_patient_id').value),
            employee_id: parseInt(document.getElementById('add_employee_id').value),
            date: document.getElementById('add_date').value,
            time: document.getElementById('add_time').value,
            symptoms: document.getElementById('add_symptoms').value.trim(),
            diagnosis: document.getElementById('add_diagnosis').value.trim(),
            icd_code: document.getElementById('add_icd_code').value.trim(),
            status: document.getElementById('add_status').value,
            treatment_plan: document.getElementById('add_treatment_plan').value.trim(),
            notes: document.getElementById('add_notes').value.trim(),
            follow_up_date: document.getElementById('add_follow_up_date').value || null,
            appointment_id: appointmentId,
            triage_id: triageId
        };

        try {
            const res = await fetch('/capstone/api/consultations.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
            const data = await res.json();
            if (data.success) {
                ModalSystem.toast.success('Consultation created!');
                ModalSystem.close('addConsultationModal');
                
                const createdId = data.data?.id || data.data?.consultation_id || '';
                const patientId = payload.patient_id;

                if (createPrescription) {
                    setTimeout(() => {
                        window.location.href = `prescriptions.php?patient_id=${patientId}&consultation_id=${createdId}&from_consultation=true`;
                    }, 800);
                } else if (createReferral) {
                    setTimeout(() => {
                        window.location.href = `referrals.php?patient_id=${patientId}&consultation_id=${createdId}&from_consultation=true`;
                    }, 800);
                } else {
                    setTimeout(() => window.location.reload(), 1000);
                }
            } else {
                ModalSystem.toast.error(data.message || 'Failed');
            }
        } catch (err) { ModalSystem.toast.error('Network error'); console.error(err); }
        finally { submitBtn.disabled = false; submitBtn.innerHTML = `<i class="fa-solid fa-check"></i> Save Consultation`; }
    }

    // ============================================================
    // EDIT CONSULTATION
    // ============================================================
    function editConsultation(id) {
        const c = CONSULTATIONS_DATA[id];
        if (!c) { ModalSystem.toast.error('Consultation not found'); return; }

        if (c.status && String(c.status).toLowerCase() === 'completed') {
            const msg = 'This consultation transaction is completed and finalized. Edits are locked for completed records.';
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.warning(msg, { title: 'Transaction Finalized' });
            } else if (typeof toast !== 'undefined') {
                toast.warning(msg);
            } else {
                alert(msg);
            }
            return;
        }

        document.getElementById('edit_id').value = c.id;
        document.getElementById('edit_patient_id').value = c.patient_id;
        document.getElementById('edit_patient_name').value = c.patient_name + ' (' + c.patient_code + ')';
        document.getElementById('edit_appointment_id').value = c.appointment_id || '';
        
        const employeeSelect = document.getElementById('edit_employee_id');
        if (employeeSelect && c.employee_id) {
            const targetId = String(c.employee_id);
            let found = false;
            for (let i = 0; i < employeeSelect.options.length; i++) {
                if (String(employeeSelect.options[i].value) === targetId) { employeeSelect.selectedIndex = i; found = true; break; }
            }
            if (!found) {
                const doctorName = c.doctor_name || '';
                for (let i = 0; i < employeeSelect.options.length; i++) {
                    if (employeeSelect.options[i].text.toLowerCase().includes(doctorName.toLowerCase())) { employeeSelect.selectedIndex = i; break; }
                }
            }
        }
        
        document.getElementById('edit_date').value = c.date;
        document.getElementById('edit_time').value = c.time;
        document.getElementById('edit_symptoms').value = c.symptoms || '';
        document.getElementById('edit_diagnosis').value = c.diagnosis || '';
        document.getElementById('edit_icd_code').value = c.icd_code || '';
        document.getElementById('edit_status').value = c.status || 'completed';
        document.getElementById('edit_treatment_plan').value = c.treatment_plan || c.treatment || '';
        document.getElementById('edit_notes').value = c.notes || '';
        document.getElementById('edit_follow_up_date').value = c.follow_up_date || c.follow_up || '';

        if (c.vital_signs && typeof c.vital_signs === 'object') {
            document.getElementById('edit_bp').value = c.vital_signs.bp || '';
            document.getElementById('edit_hr').value = c.vital_signs.hr || '';
            document.getElementById('edit_temp').value = c.vital_signs.temp || '';
            document.getElementById('edit_weight').value = c.vital_signs.weight || '';
        } else {
            document.getElementById('edit_bp').value = '';
            document.getElementById('edit_hr').value = '';
            document.getElementById('edit_temp').value = '';
            document.getElementById('edit_weight').value = '';
        }

        ModalSystem.open('editConsultationModal');
    }

    // ============================================================
    // SAVE EDITED CONSULTATION
    // ============================================================
    async function saveEditedConsultation(event) {
        event.preventDefault();
        const id = document.getElementById('edit_id').value;

        const bp = document.getElementById('edit_bp').value.trim();
        const hr = document.getElementById('edit_hr').value.trim();
        const temp = document.getElementById('edit_temp').value.trim();
        const weight = document.getElementById('edit_weight').value.trim();

        const vitalError = validateVitalSigns({ bp, hr, temp, weight });
        if (vitalError) {
            ModalSystem.toast.error(vitalError);
            return;
        }

        const submitBtn = document.getElementById('submitEditBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...`;
        let vitalSigns = null;
        if (bp || hr || temp || weight) { vitalSigns = { bp, hr, temp, weight }; }

        const payload = {
            patient_id: parseInt(document.getElementById('edit_patient_id').value),
            employee_id: parseInt(document.getElementById('edit_employee_id').value),
            date: document.getElementById('edit_date').value,
            time: document.getElementById('edit_time').value,
            symptoms: document.getElementById('edit_symptoms').value.trim(),
            diagnosis: document.getElementById('edit_diagnosis').value.trim(),
            icd_code: document.getElementById('edit_icd_code').value.trim(),
            status: document.getElementById('edit_status').value,
            vital_signs: vitalSigns,
            treatment_plan: document.getElementById('edit_treatment_plan').value.trim(),
            notes: document.getElementById('edit_notes').value.trim(),
            follow_up_date: document.getElementById('edit_follow_up_date').value || null,
            appointment_id: document.getElementById('edit_appointment_id')?.value || null
        };

        try {
            const res = await fetch('/capstone/api/consultations.php?action=update&id=' + id, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
            const data = await res.json();
            if (data.success) { ModalSystem.toast.success('Consultation updated!'); ModalSystem.close('editConsultationModal'); setTimeout(() => window.location.reload(), 1000); }
            else { ModalSystem.toast.error(data.message || 'Failed'); }
        } catch (err) { ModalSystem.toast.error('Network error'); }
        finally { submitBtn.disabled = false; submitBtn.innerHTML = `<i class="fa-solid fa-check"></i> Save Changes`; }
    }

    // ============================================================
    // DELETE CONSULTATION
    // ============================================================
    async function deleteConsultation(id) {
        ModalSystem.confirm('This consultation record will be permanently removed.', async () => {
            try {
                const res = await fetch('/capstone/api/consultations.php?action=delete&id=' + id, { method:'POST' });
                const data = await res.json();
                if (data.success) { ModalSystem.toast.success('Consultation deleted!'); setTimeout(() => window.location.reload(), 800); }
                else { ModalSystem.toast.error(data.message || 'Failed'); }
            } catch (err) { ModalSystem.toast.error('Error deleting consultation'); }
        }, { title:'Delete Consultation', confirmText:'Delete', type:'danger' });
    }

    // ============================================================
    // FILTERING & SEARCHING
    // ============================================================
    document.getElementById('searchConsultation').addEventListener('input', filterConsultations);
    document.getElementById('filterStatus').addEventListener('change', filterConsultations);
    document.getElementById('filterDoctor').addEventListener('change', filterConsultations);

    function setDateFilter(range) {
        activeDateFilter = range;
        document.querySelectorAll('.date-filter-btn').forEach(btn => btn.classList.remove('active'));
        const indicator = document.getElementById('activeDateFilter');
        const label = document.getElementById('activeDateFilterLabel');
        if (range === 'all') { indicator.classList.add('hidden'); }
        else {
            document.querySelectorAll('.date-filter-btn').forEach(btn => {
                const t = btn.textContent.trim().toLowerCase();
                if ((range==='today'&&t.includes('today'))||(range==='week'&&t.includes('week'))||(range==='month'&&t.includes('month'))||(range==='year'&&t.includes('year'))) btn.classList.add('active');
            });
            label.textContent = {today:'Today',week:'This Week',month:'This Month',year:'This Year'}[range]||range;
            indicator.classList.remove('hidden');
        }
        filterConsultations();
    }

    function matchesDateFilter(d,r){if(!r||r==='all')return true;if(!d)return false;const dt=new Date(d+'T00:00:00'),td=new Date();td.setHours(0,0,0,0);const sw=new Date(td);sw.setDate(sw.getDate()-sw.getDay()+(sw.getDay()===0?-6:1));sw.setHours(0,0,0,0);const sm=new Date(td.getFullYear(),td.getMonth(),1),sy=new Date(td.getFullYear(),0,1);switch(r){case'today':return dt.getTime()===td.getTime();case'week':return dt>=sw&&dt<=td;case'month':return dt>=sm&&dt<=td;case'year':return dt>=sy&&dt<=td;default:return true;}}

 function filterConsultations() {
    const search = document.getElementById('searchConsultation').value.toLowerCase();
    const status = document.getElementById('filterStatus').value.toLowerCase();
    const doctor = document.getElementById('filterDoctor').value.toLowerCase();
    const dateRange = activeDateFilter;
    let visibleCount = 0;

    document.querySelectorAll('.consultation-card').forEach(card => {
        // Get BOTH real data (dataset) AND visible text
        const patientReal = card.dataset.patient || '';
        const patientCode = card.dataset.patientCode || '';
        const diagnosis = card.dataset.diagnosis || '';
        const icd = card.dataset.icd || '';
        
        // Get visible text from the card (masked version)
        const patientVisible = (card.querySelector('.maskable[data-real]')?.textContent || '').toLowerCase();
        const doctorVisible = (card.querySelector('span.maskable[data-real]')?.textContent || '').toLowerCase();
        const consultationId = (card.querySelector('.font-mono')?.textContent || '').toLowerCase();
        
        // Search against BOTH real and masked text
        const matchesSearch = 
            patientReal.includes(search) ||        // Real name from dataset
            patientVisible.includes(search) ||     // Masked name like "j**** g****"
            patientCode.includes(search) ||        // Patient code P-2024-001
            diagnosis.includes(search) || 
            icd.includes(search) ||
            consultationId.includes(search);       // CONS-0001
            
        const matchesStatus = !status || (card.dataset.status || '').toLowerCase() === status;
        const matchesDoctor = !doctor || (card.dataset.doctor || '').toLowerCase().includes(doctor);
        const matchesDate = matchesDateFilter(card.dataset.date, dateRange);

        const isVisible = matchesSearch && matchesStatus && matchesDoctor && matchesDate;
        card.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });

    document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
}
    function changePage(page){if(page<1||page><?php echo $totalPages; ?>)return;window.location.href='?page='+page;}

    const LOGGED_IN_DOCTOR_ID = <?php echo json_encode($loggedInDoctorId); ?>;
    const LOGGED_IN_DOCTOR_NAME = <?php echo json_encode($loggedInDoctorName); ?>;
    const IS_NURSE = <?php echo json_encode($isNurse); ?>;
    const CAN_CREATE_CONSULTATION = <?php echo json_encode($canCreateConsultation); ?>;
    const IS_ADMIN = <?php echo json_encode(str_contains(strtolower($_SESSION['role_description'] ?? $_SESSION['role'] ?? ''), 'admin') || str_contains(strtolower($_SESSION['role_description'] ?? $_SESSION['role'] ?? ''), 'director')); ?>;

    // Safety guard: if a nurse somehow triggers addConsultationModal, block it
    const _origModalOpen = (typeof ModalSystem !== 'undefined' && ModalSystem.open) ? ModalSystem.open.bind(ModalSystem) : null;
    if (!CAN_CREATE_CONSULTATION) {
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof ModalSystem !== 'undefined' && ModalSystem.open) {
                const _safeOpen = ModalSystem.open.bind(ModalSystem);
                ModalSystem.open = function(id) {
                    if (id === 'addConsultationModal') {
                        ModalSystem.toast.warning('Nurses can only perform patient assessments (triage). Consultations are for doctors only.', {title: '⚕️ Access Restricted', duration: 4000});
                        return;
                    }
                    _safeOpen(id);
                };
            }
        });
    }

    function lockDoctorSelect(selectId, doctorId, doctorName, isLocked = true) {
        const el = document.getElementById(selectId);
        if (!el) return;

        const targetId = doctorId || LOGGED_IN_DOCTOR_ID;
        const targetName = doctorName || LOGGED_IN_DOCTOR_NAME;

        if (targetId || targetName) {
            let matched = false;
            if (targetId) {
                const idStr = String(targetId);
                for (let i = 0; i < el.options.length; i++) {
                    if (String(el.options[i].value) === idStr) {
                        el.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            if (!matched && targetName) {
                for (let i = 0; i < el.options.length; i++) {
                    if (el.options[i].text.toLowerCase().includes(targetName.toLowerCase())) {
                        el.selectedIndex = i;
                        matched = true;
                        break;
                    }
                }
            }
            if (!matched && (targetId || targetName)) {
                const opt = document.createElement('option');
                opt.value = targetId ? String(targetId) : '';
                opt.textContent = targetName ? (targetName.startsWith('Dr.') ? targetName : 'Dr. ' + targetName) : `Doctor #${targetId}`;
                opt.selected = true;
                el.appendChild(opt);
                el.value = opt.value;
            }
        }

        if (isLocked) {
            el.classList.add('bg-slate-100', 'cursor-not-allowed', 'pointer-events-none');
            el.setAttribute('tabindex', '-1');
            
            let badge = el.parentElement.querySelector('.doctor-lock-badge');
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'doctor-lock-badge text-[10px] font-bold text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 mt-1 flex items-center gap-1';
                badge.innerHTML = '<i class="fa-solid fa-lock text-[9px]"></i> Assigned Attending Doctor (Locked)';
                el.parentElement.appendChild(badge);
            }
        } else {
            el.classList.remove('bg-slate-100', 'cursor-not-allowed', 'pointer-events-none');
            el.removeAttribute('tabindex');
            const badge = el.parentElement.querySelector('.doctor-lock-badge');
            if (badge) badge.remove();
        }
    }

    // ============================================================
    // APPOINTMENT & TRIAGE PRE-FILL
    // ============================================================
    document.addEventListener('DOMContentLoaded',function(){
        // Always enforce logged in doctor pre-selection by default
        if (LOGGED_IN_DOCTOR_ID || LOGGED_IN_DOCTOR_NAME) {
            lockDoctorSelect('add_employee_id', LOGGED_IN_DOCTOR_ID, LOGGED_IN_DOCTOR_NAME, true);
        }

        const p=new URLSearchParams(window.location.search);
        if(p.get('from_appointment')==='true' || p.get('from_triage')==='true' || p.get('employee_id') || p.get('doctor_name') || (p.get('action')==='new' && p.get('patient_id'))){
            const pid=p.get('patient_id'),aid=p.get('appointment_id'),eid=p.get('employee_id'),dt=p.get('date'),tm=p.get('time'),dn=p.get('doctor_name'),tid=p.get('triage_id');
            setTimeout(()=>{
                ModalSystem.open('addConsultationModal');
                const ps=document.getElementById('add_patient_id');
                if(ps&&pid){
                    let matched = false;
                    for(let o of ps.options){
                        if(String(o.value)===String(pid)){o.selected=true;matched=true;break;}
                    }
                    if(!matched){
                        const opt=document.createElement('option');
                        opt.value=String(pid);
                        opt.textContent=`Patient #${pid}`;
                        opt.selected=true;
                        ps.appendChild(opt);
                    }
                }
                
                // Pre-select and LOCK assigned doctor
                lockDoctorSelect('add_employee_id', eid || LOGGED_IN_DOCTOR_ID, dn || LOGGED_IN_DOCTOR_NAME, true);

                const di=document.getElementById('add_date');if(di&&dt)di.value=dt;
                const ti=document.getElementById('add_time');if(ti&&tm){const m=tm.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);if(m){let h=parseInt(m[1]);if(m[3].toUpperCase()==='PM'&&h!==12)h+=12;if(m[3].toUpperCase()==='AM'&&h===12)h=0;ti.value=`${h.toString().padStart(2,'0')}:${m[2]}`;}else if(tm.includes(':'))ti.value=tm.substring(0,5);else ti.value=new Date().toTimeString().slice(0,5);}
                const ss=document.getElementById('add_status');if(ss)ss.value='completed';
                const ni=document.getElementById('add_notes');if(ni&&aid)ni.value=`Consultation from appointment #${aid}`;
                const ai=document.getElementById('add_appointment_id');if(ai&&aid)ai.value=aid;
                const tri=document.getElementById('add_triage_id');if(tri&&tid)tri.value=tid;
                ModalSystem.toast.info('Patient and assigned doctor auto-selected and locked for security',{title:'📋 Auto-filled',duration:3000});
            },500);
            if(window.history&&window.history.replaceState){const pg=p.get('page')||'1';window.history.replaceState({},document.title,window.location.pathname+'?page='+pg);}
        }
    });

    // ============================================================
    // FORM VALIDATION
    // ============================================================
    function initConsultationValidation(){
        if(typeof ModalSystem==='undefined'||!ModalSystem.validateForm){setTimeout(initConsultationValidation,100);return;}
        ModalSystem.validateForm('addConsultationModal',{fields:{'add_patient_id':{label:'Patient'},'add_employee_id':{label:'Doctor / Staff'},'add_date':{label:'Date'},'add_time':{label:'Time'},'add_diagnosis':{label:'Diagnosis'}},onSubmit:saveNewConsultation});
        ModalSystem.validateForm('editConsultationModal',{fields:{'edit_employee_id':{label:'Doctor / Staff'},'edit_date':{label:'Date'},'edit_time':{label:'Time'},'edit_diagnosis':{label:'Diagnosis'}},onSubmit:saveEditedConsultation});
        console.log('✅ Consultation form validation initialized');
    }
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',initConsultationValidation);}else{initConsultationValidation();}
</script>
<?php include_once '../../includes/footer.php'; ?>