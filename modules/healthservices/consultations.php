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
require_once __DIR__ . '/../../app/Models/Consultation.php';
require_once __DIR__ . '/../../app/Controllers/ConsultationController.php';

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
$medicalStaff = []; // NEW: For filter dropdown
try {
    $rawEmployees = $employeeModel->all();
    foreach ($rawEmployees as $e) {
        $displayName = $e['full_name'] ?? '';
        if (empty($displayName)) {
            $displayName = $e['name'] ?? $e['username'] ?? "Employee #{$e['id']}";
        }
        $e['full_name'] = $displayName;
        $e['first_name'] = $displayName;
        $e['last_name'] = '';
        $dbEmployees[] = $e;
        
        // NEW: Filter only medical staff for doctor dropdown
        $role = strtolower($e['role_description'] ?? $e['role'] ?? '');
        if (in_array($role, ['doctor', 'medical practitioner', 'nurse', 'dentist', 'midwives', 'nutritionist', 'immunization coordinator', 'lab tech', 'health center director'])) {
            $medicalStaff[] = $e;
        }
    }
} catch (Throwable $e) {
    error_log('Error loading employees: ' . $e->getMessage());
    $dbEmployees = [];
    $medicalStaff = [];
}

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
$completedCount = count(array_filter($consultations, fn($c) => strtolower($c['status']) === 'completed'));
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
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Consultations</h2>
            <p class="text-sm text-slate-500 mt-0.5">View and manage all patient consultations and medical notes</p>
        </div>
        <div class="flex gap-3">
            <button onclick="ModalSystem.open('addConsultationModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> New Consultation
            </button>
        </div>
    </div>

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
                <button onclick="setDateFilter('year')" class="date-filter-btn px-3 py-1.5 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all"><i class="fa-solid fa-calendar-year mr-1"></i> This Year</button>
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
                <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">Click "New Consultation" above to create your first patient consultation record.</p>
                <button onclick="ModalSystem.open('addConsultationModal')" class="mt-4 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium text-sm font-semibold inline-flex items-center gap-2"><i class="fa-solid fa-plus text-xs"></i> New Consultation</button>
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
                                <!-- FIXED: Added maskable class -->
                                <p class="font-semibold text-slate-800 text-sm line-clamp-1 maskable" data-real="<?php echo htmlspecialchars($c['patient_name']); ?>" data-masked="<?php echo htmlspecialchars(maskName($c['patient_name'])); ?>"><?php echo htmlspecialchars(maskName($c['patient_name'])); ?></p>
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
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <a href="patients.php?patient=<?php echo $c['patient_id']; ?>&id=<?php echo $c['patient_id']; ?>" class="px-2.5 py-1 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="View Patient Profile"><i class="fa-solid fa-user mr-1"></i> Patient</a>
                    <div class="flex gap-1">
                        <button onclick="viewConsultation(<?php echo $c['id']; ?>)" class="px-2.5 py-1 text-xs font-semibold text-brand-medium hover:bg-brand-light rounded-lg transition"><i class="fa-solid fa-eye mr-1"></i> View</button>
                        <button onclick="editConsultation(<?php echo $c['id']; ?>)" class="px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition"><i class="fa-solid fa-pen mr-1"></i> Edit</button>
                        <button onclick="deleteConsultation(<?php echo $c['id']; ?>)" class="px-2.5 py-1 text-xs font-semibold text-rose-500 hover:bg-rose-50 rounded-lg transition"><i class="fa-solid fa-trash-can"></i></button>
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

<!-- ADD CONSULTATION MODAL -->
<div id="addConsultationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10"><h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-stethoscope text-brand-medium"></i> New Consultation Record</h3><button onclick="ModalSystem.close('addConsultationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<!-- FIXED: Removed onsubmit -->
<form id="addConsultationForm" class="p-6 space-y-4">
<input type="hidden" id="add_appointment_id" value="">
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient <span class="text-rose-500">*</span></label><select id="add_patient_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="">Select Patient</option><?php foreach ($dbPatients as $p): ?><option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?> (<?php echo htmlspecialchars($p['patient_id'] ?? "P-{$p['id']}"); ?>)</option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Attending Doctor / Staff <span class="text-rose-500">*</span></label><select id="add_employee_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="">Select Doctor / Staff</option><?php if (!empty($dbEmployees)): ?><?php foreach ($dbEmployees as $e): $displayName = $e['full_name'] ?? ''; if (empty($displayName)) { $displayName = $e['name'] ?? $e['username'] ?? "Employee #{$e['id']}"; } ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($displayName); ?></option><?php endforeach; ?><?php else: ?><option value="1">Dr. Elena Santos</option><option value="2">Dr. Miguel Reyes</option><option value="3">Dr. Ana Cruz</option><?php endif; ?></select></div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date <span class="text-rose-500">*</span></label><input type="date" id="add_date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time <span class="text-rose-500">*</span></label><input type="time" id="add_time" value="<?php echo date('H:i'); ?>" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Chief Complaints / Symptoms</label><input type="text" id="add_symptoms" placeholder="e.g., Fever, persistent cough, headache" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Diagnosis <span class="text-rose-500">*</span></label><input type="text" id="add_diagnosis" required placeholder="Primary diagnosis" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ICD-10 Code</label><input type="text" id="add_icd_code" placeholder="e.g., J06.9, I10" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-mono uppercase"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="add_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="referred">Referred</option><option value="follow_up">Follow-up Needed</option></select></div></div>
<div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50"><label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2 flex items-center gap-1.5"><i class="fa-solid fa-heart-pulse text-rose-500"></i> Vital Signs (Optional)</label><div class="grid grid-cols-2 sm:grid-cols-4 gap-3"><div><span class="text-[10px] text-slate-500">BP (mmHg)</span><input type="text" id="add_bp" placeholder="120/80" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Heart Rate (bpm)</span><input type="text" id="add_hr" placeholder="72" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Temp (°C)</span><input type="text" id="add_temp" placeholder="36.5" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Weight (kg)</span><input type="text" id="add_weight" placeholder="65" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div></div></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Treatment Plan & Prescriptions</label><textarea id="add_treatment_plan" rows="2" placeholder="Medications prescribed, rest, lab tests ordered..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Follow-up Date</label><input type="date" id="add_follow_up_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Clinical Notes</label><input type="text" id="add_notes" placeholder="Additional observations" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="flex justify-end gap-2 pt-3 border-t border-slate-100"><button type="button" onclick="ModalSystem.close('addConsultationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="submit" id="submitAddBtn" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5"><i class="fa-solid fa-check"></i> Save Consultation</button></div>
</form></div></div>

<!-- EDIT CONSULTATION MODAL -->
<div id="editConsultationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10"><h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen-to-square text-brand-medium"></i> Edit Consultation</h3><button onclick="ModalSystem.close('editConsultationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<!-- FIXED: Removed onsubmit -->
<form id="editConsultationForm" class="p-6 space-y-4">
<input type="hidden" id="edit_id"><input type="hidden" id="edit_appointment_id" value="">
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label><input type="text" id="edit_patient_name" readonly class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm text-slate-700 outline-none font-semibold cursor-not-allowed"><input type="hidden" id="edit_patient_id"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Attending Doctor / Staff</label><select id="edit_employee_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="">Select Doctor / Staff</option><?php if (!empty($dbEmployees)): ?><?php foreach ($dbEmployees as $e): $fullName = trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')); $fullName = !empty($fullName) ? $fullName : ($e['name'] ?? ''); $displayText = !empty($fullName) ? $fullName : "Employee #{$e['id']}"; ?><option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($displayText); ?></option><?php endforeach; ?><?php else: ?><option value="1">Dr. Elena Santos</option><option value="2">Dr. Miguel Reyes</option><option value="3">Dr. Ana Cruz</option><?php endif; ?></select></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label><input type="date" id="edit_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time</label><input type="time" id="edit_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Symptoms</label><input type="text" id="edit_symptoms" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Diagnosis</label><input type="text" id="edit_diagnosis" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4"><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ICD-10 Code</label><input type="text" id="edit_icd_code" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-mono uppercase"></div><div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="in_progress">In Progress</option><option value="completed">Completed</option><option value="referred">Referred</option><option value="follow_up">Follow-up Needed</option></select></div></div>
<div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50"><label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-2 flex items-center gap-1.5"><i class="fa-solid fa-heart-pulse text-rose-500"></i> Vital Signs</label><div class="grid grid-cols-2 sm:grid-cols-4 gap-3"><div><span class="text-[10px] text-slate-500">BP (mmHg)</span><input type="text" id="edit_bp" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Heart Rate (bpm)</span><input type="text" id="edit_hr" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Temp (°C)</span><input type="text" id="edit_temp" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div><div><span class="text-[10px] text-slate-500">Weight (kg)</span><input type="text" id="edit_weight" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none"></div></div></div>
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
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <a href="patients.php?patient=${c.patient_id}&id=${c.patient_id}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-xs font-semibold inline-flex items-center gap-1.5"><i class="fa-solid fa-user"></i> View Patient Profile</a>
                    <button onclick="ModalSystem.close('viewConsultationModal'); editConsultation(${c.id});" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold inline-flex items-center gap-1.5"><i class="fa-solid fa-pen"></i> Edit Record</button>
                </div>
            </div>`;
        
        setTimeout(() => { if (typeof ModalSystem !== 'undefined' && ModalSystem.refreshMasking) ModalSystem.refreshMasking('viewConsultationModal'); }, 100);
    }

    // ============================================================
    // ADD CONSULTATION
    // ============================================================
    async function saveNewConsultation(event) {
        event.preventDefault();
        const submitBtn = document.getElementById('submitAddBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...`;

        const bp = document.getElementById('add_bp').value.trim();
        const hr = document.getElementById('add_hr').value.trim();
        const temp = document.getElementById('add_temp').value.trim();
        const weight = document.getElementById('add_weight').value.trim();

        let vitalSigns = null;
        if (bp || hr || temp || weight) { vitalSigns = { bp, hr, temp, weight }; }

        const appointmentId = document.getElementById('add_appointment_id')?.value || null;

        const payload = {
            patient_id: parseInt(document.getElementById('add_patient_id').value),
            employee_id: parseInt(document.getElementById('add_employee_id').value),
            date: document.getElementById('add_date').value,
            time: document.getElementById('add_time').value,
            symptoms: document.getElementById('add_symptoms').value.trim(),
            diagnosis: document.getElementById('add_diagnosis').value.trim(),
            icd_code: document.getElementById('add_icd_code').value.trim(),
            status: document.getElementById('add_status').value,
            vital_signs: vitalSigns,
            treatment_plan: document.getElementById('add_treatment_plan').value.trim(),
            notes: document.getElementById('add_notes').value.trim(),
            follow_up_date: document.getElementById('add_follow_up_date').value || null,
            appointment_id: appointmentId
        };

        try {
            const res = await fetch('/capstone/api/consultations.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
            const data = await res.json();
            if (data.success) { ModalSystem.toast.success('Consultation created!'); ModalSystem.close('addConsultationModal'); setTimeout(() => window.location.reload(), 1000); }
            else { ModalSystem.toast.error(data.message || 'Failed'); }
        } catch (err) { ModalSystem.toast.error('Network error'); console.error(err); }
        finally { submitBtn.disabled = false; submitBtn.innerHTML = `<i class="fa-solid fa-check"></i> Save Consultation`; }
    }

    // ============================================================
    // EDIT CONSULTATION
    // ============================================================
    function editConsultation(id) {
        const c = CONSULTATIONS_DATA[id];
        if (!c) { ModalSystem.toast.error('Consultation not found'); return; }

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
        const submitBtn = document.getElementById('submitEditBtn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...`;

        const bp = document.getElementById('edit_bp').value.trim();
        const hr = document.getElementById('edit_hr').value.trim();
        const temp = document.getElementById('edit_temp').value.trim();
        const weight = document.getElementById('edit_weight').value.trim();
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

    // ============================================================
    // APPOINTMENT PRE-FILL
    // ============================================================
    document.addEventListener('DOMContentLoaded',function(){
        const p=new URLSearchParams(window.location.search);
        if(p.get('from_appointment')==='true'){
            const pid=p.get('patient_id'),aid=p.get('appointment_id'),eid=p.get('employee_id'),dt=p.get('date'),tm=p.get('time'),dn=p.get('doctor_name');
            setTimeout(()=>{
                ModalSystem.open('addConsultationModal');
                const ps=document.getElementById('add_patient_id');if(ps&&pid){for(let o of ps.options){if(String(o.value)===String(pid)){o.selected=true;break;}}}
                const es=document.getElementById('add_employee_id');if(es&&eid){const t=String(eid);let f=false;for(let i=0;i<es.options.length;i++){if(String(es.options[i].value)===t){es.selectedIndex=i;f=true;break;}}if(!f&&dn){for(let i=0;i<es.options.length;i++){if(es.options[i].text.toLowerCase().includes(dn.toLowerCase())){es.selectedIndex=i;break;}}}}
                const di=document.getElementById('add_date');if(di&&dt)di.value=dt;
                const ti=document.getElementById('add_time');if(ti&&tm){const m=tm.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);if(m){let h=parseInt(m[1]);if(m[3].toUpperCase()==='PM'&&h!==12)h+=12;if(m[3].toUpperCase()==='AM'&&h===12)h=0;ti.value=`${h.toString().padStart(2,'0')}:${m[2]}`;}else if(tm.includes(':'))ti.value=tm.substring(0,5);else ti.value=new Date().toTimeString().slice(0,5);}
                const ss=document.getElementById('add_status');if(ss)ss.value='completed';
                const ni=document.getElementById('add_notes');if(ni&&aid)ni.value=`Consultation from appointment #${aid}`;
                const ai=document.getElementById('add_appointment_id');if(ai&&aid)ai.value=aid;
                ModalSystem.toast.info('Patient and doctor pre-filled from appointment',{title:'📋 Auto-filled',duration:3000});
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