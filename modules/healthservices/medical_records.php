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
// 1. PHP BACKEND - Aggregator & EHR Master Data Builder
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('health center services');

require_once '../../app/Models/Patient.php';
require_once '../../app/Models/Triage.php';
require_once '../../app/Models/Consultation.php';
require_once '../../app/Models/Prescription.php';
require_once '../../app/Models/Referral.php';
require_once '../../app/Models/Appointment.php';
require_once '../../app/Models/Employee.php';
require_once '../../app/Models/MedicalRecord.php';

$patientModel = new Patient();
$triageModel = new Triage();
$consultationModel = new Consultation();
$prescriptionModel = new Prescription();
$referralModel = new Referral();
$appointmentModel = new Appointment();
$employeeModel = new Employee();
$medicalRecordModel = new MedicalRecord();

$rawPatients = [];
$rawTriages = [];
$rawConsultations = [];
$rawPrescriptions = [];
$rawReferrals = [];
$rawAppointments = [];
$rawEmployees = [];
$rawLegacyRecords = [];

try {
    $rawPatients = $patientModel->all(['order' => 'first_name.asc,last_name.asc']);
} catch (Throwable $e) { error_log('EHR Patient Error: ' . $e->getMessage()); }

try {
    $rawTriages = $triageModel->all(['order' => 'created_at.desc']);
} catch (Throwable $e) { error_log('EHR Triage Error: ' . $e->getMessage()); }

try {
    $rawConsultations = $consultationModel->all(['order' => 'date.desc,created_at.desc']);
} catch (Throwable $e) { error_log('EHR Consultation Error: ' . $e->getMessage()); }

try {
    $rawPrescriptions = $prescriptionModel->all(['order' => 'date.desc,created_at.desc']);
} catch (Throwable $e) { error_log('EHR Prescription Error: ' . $e->getMessage()); }

try {
    $rawReferrals = $referralModel->all(['order' => 'date.desc,created_at.desc']);
} catch (Throwable $e) { error_log('EHR Referral Error: ' . $e->getMessage()); }

try {
    $rawAppointments = $appointmentModel->all(['order' => 'appointment_date.desc,created_at.desc']);
} catch (Throwable $e) { error_log('EHR Appointment Error: ' . $e->getMessage()); }

try {
    $rawEmployees = $employeeModel->all();
} catch (Throwable $e) { error_log('EHR Employee Error: ' . $e->getMessage()); }

try {
    $rawLegacyRecords = $medicalRecordModel->all(['order' => 'date.desc,created_at.desc']);
} catch (Throwable $e) { error_log('EHR Legacy Records Error: ' . $e->getMessage()); }

// Build employee lookup maps
$employeesMap = [];
foreach ($rawEmployees as $e) {
    $name = trim($e['full_name'] ?? (($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')));
    if (empty($name)) $name = $e['name'] ?? $e['username'] ?? ("Employee #{$e['id']}");
    $displayName = (str_starts_with($name, 'Dr.') || str_starts_with($name, 'Doctor')) ? $name : ('Dr. ' . $name);
    $employeesMap[$e['id']] = $displayName;
}

// Master Aggregated Data Map per Patient
$ehrDataMap = [];
$patientsList = [];

foreach ($rawPatients as $p) {
    $pId = (int)$p['id'];
    $fName = trim($p['first_name'] ?? '');
    $lName = trim($p['last_name'] ?? '');
    $fullName = trim("$fName $lName") ?: "Patient #$pId";
    $patientCode = $p['patient_id'] ?? "P-$pId";
    $initials = strtoupper(substr($fName ?: 'P', 0, 1) . substr($lName ?: 'T', 0, 1));

    $age = 0;
    if (!empty($p['birth_date'])) {
        try {
            $dob = new DateTime($p['birth_date']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
        } catch (Throwable $ex) {}
    }

    $allergies = $p['allergies'] ?? 'None';
    $conditions = 'None';
    if (!empty($p['medical_history'])) {
        $history = is_string($p['medical_history']) 
            ? json_decode($p['medical_history'], true) 
            : $p['medical_history'];
        $conditions = $history['conditions'] ?? 'None';
    }

    $profile = [
        'id' => $pId,
        'patient_id' => $patientCode,
        'full_name' => $fullName,
        'first_name' => $fName,
        'last_name' => $lName,
        'initials' => $initials,
        'gender' => $p['gender'] ?? 'Unspecified',
        'age' => $age,
        'birth_date' => $p['birth_date'] ?? 'N/A',
        'blood_type' => $p['blood_type'] ?? 'N/A',
        'contact' => $p['contact'] ?? 'N/A',
        'email' => $p['email'] ?? 'N/A',
        'address' => $p['address'] ?? 'N/A',
        'barangay' => $p['barangay'] ?? 'N/A',
        'emergency_contact' => $p['emergency_contact'] ?? 'N/A',
        'registration_date' => $p['registration_date'] ?? 'N/A',
        'status' => $p['status'] ?? 'active',
        'allergies' => $allergies,
        'conditions' => $conditions
    ];

    // Filter sub-records for this patient
    $pTriages = array_values(array_filter($rawTriages, fn($t) => (int)($t['patient_id'] ?? 0) === $pId));
    $pConsultations = array_values(array_filter($rawConsultations, fn($c) => (int)($c['patient_id'] ?? 0) === $pId));
    $pPrescriptions = array_values(array_filter($rawPrescriptions, fn($pr) => (int)($pr['patient_id'] ?? 0) === $pId));
    $pReferrals = array_values(array_filter($rawReferrals, fn($r) => (int)($r['patient_id'] ?? 0) === $pId));
    $pAppointments = array_values(array_filter($rawAppointments, fn($a) => (int)($a['patient_id'] ?? 0) === $pId));
    $pLegacy = array_values(array_filter($rawLegacyRecords, fn($m) => (int)($m['patient_id'] ?? 0) === $pId));

    // Formatted timeline entries
    $timeline = [];

    // 1. Consultations to Timeline
    foreach ($pConsultations as $c) {
        $docName = $c['doctor_name'] ?? ($employeesMap[$c['employee_id'] ?? 0] ?? 'Attending Doctor');
        $dateStr = $c['date'] ?? (substr($c['created_at'] ?? '', 0, 10));
        $timeStr = $c['time'] ?? '';
        $timeline[] = [
            'type' => 'consultation',
            'timestamp' => strtotime("$dateStr $timeStr") ?: strtotime($c['created_at'] ?? 'now'),
            'date_display' => $dateStr . ($timeStr ? " • $timeStr" : ''),
            'title' => 'Doctor Consultation: ' . ($c['diagnosis'] ?? 'General Consultation'),
            'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'badge_label' => 'Consultation',
            'icon' => 'fa-stethoscope text-emerald-600',
            'provider' => $docName,
            'summary' => "Diagnosis: " . ($c['diagnosis'] ?? 'N/A') . ($c['icd_code'] ? " (ICD-10: {$c['icd_code']})" : ''),
            'data' => $c
        ];
    }

    // 2. Prescriptions to Timeline
    foreach ($pPrescriptions as $pr) {
        $docName = $pr['doctor_name'] ?? ($employeesMap[$pr['employee_id'] ?? 0] ?? 'Prescribing Physician');
        $dateStr = $pr['date'] ?? (substr($pr['created_at'] ?? '', 0, 10));
        $meds = is_string($pr['medications'] ?? null) ? json_decode($pr['medications'], true) : ($pr['medications'] ?? []);
        $medsCount = is_array($meds) ? count($meds) : 0;
        $statusLabel = ucfirst($pr['status'] ?? 'pending');

        $timeline[] = [
            'type' => 'prescription',
            'timestamp' => strtotime($pr['created_at'] ?? $dateStr) ?: time(),
            'date_display' => $dateStr,
            'title' => "Prescription Issued ($medsCount items)",
            'badge' => 'bg-teal-100 text-teal-800 border-teal-200',
            'badge_label' => "Rx ($statusLabel)",
            'icon' => 'fa-pills text-teal-600',
            'provider' => $docName,
            'summary' => "Prescription #{$pr['prescription_id']} • Status: $statusLabel",
            'data' => array_merge($pr, ['meds_parsed' => $meds])
        ];
    }

    // 3. Referrals to Timeline
    foreach ($pReferrals as $rf) {
        $fromDoc = $rf['from_doctor'] ?? ($employeesMap[$rf['from_doctor_id'] ?? 0] ?? 'Referring Doctor');
        $target = $rf['to_specialist'] ?? ($rf['to_hospital'] ?? 'Specialist Clinic');
        $dateStr = $rf['date'] ?? (substr($rf['created_at'] ?? '', 0, 10));

        $timeline[] = [
            'type' => 'referral',
            'timestamp' => strtotime($rf['created_at'] ?? $dateStr) ?: time(),
            'date_display' => $dateStr,
            'title' => "Patient Referral to $target",
            'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
            'badge_label' => 'Referral',
            'icon' => 'fa-arrow-right-from-bracket text-amber-600',
            'provider' => $fromDoc,
            'summary' => "Reason: " . ($rf['reason'] ?? 'Specialist Consultation') . " • Urgency: " . ucfirst($rf['urgency'] ?? 'Routine'),
            'data' => $rf
        ];
    }

    // 4. Triages to Timeline
    foreach ($pTriages as $tr) {
        $nurse = $employeesMap[$tr['nurse_id'] ?? 0] ?? 'Triage Nurse';
        $dateStr = substr($tr['created_at'] ?? '', 0, 10);
        $timeStr = substr($tr['created_at'] ?? '', 11, 5);
        $priority = ucfirst($tr['priority'] ?? 'Low');
        $bp = $tr['blood_pressure'] ?? 'N/A';
        $temp = $tr['temperature'] ? "{$tr['temperature']}°C" : 'N/A';
        $hr = $tr['heart_rate'] ? "{$tr['heart_rate']} bpm" : 'N/A';

        $timeline[] = [
            'type' => 'triage',
            'timestamp' => strtotime($tr['created_at'] ?? 'now') ?: time(),
            'date_display' => $dateStr . ($timeStr ? " • $timeStr" : ''),
            'title' => "Triage Vitals Check-in ($priority Priority)",
            'badge' => 'bg-purple-100 text-purple-800 border-purple-200',
            'badge_label' => 'Vitals & Triage',
            'icon' => 'fa-heart-pulse text-purple-600',
            'provider' => $nurse,
            'summary' => "BP: $bp | Temp: $temp | HR: $hr | Priority: $priority",
            'data' => $tr
        ];
    }

    // 5. Appointments to Timeline
    foreach ($pAppointments as $ap) {
        $doc = $ap['doctor_name'] ?? ($employeesMap[$ap['employee_id'] ?? 0] ?? 'Attending Staff');
        $dateStr = $ap['appointment_date'] ?? (substr($ap['created_at'] ?? '', 0, 10));
        $timeStr = $ap['appointment_time'] ?? '';
        $status = ucfirst($ap['status'] ?? 'Scheduled');

        $timeline[] = [
            'type' => 'appointment',
            'timestamp' => strtotime("$dateStr $timeStr") ?: strtotime($ap['created_at'] ?? 'now'),
            'date_display' => $dateStr . ($timeStr ? " • $timeStr" : ''),
            'title' => "Appointment: " . ($ap['service_type'] ?? 'General Checkup'),
            'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
            'badge_label' => "Appointment ($status)",
            'icon' => 'fa-calendar-check text-blue-600',
            'provider' => $doc,
            'summary' => "Service: " . ($ap['service_type'] ?? 'General') . " • Status: $status",
            'data' => $ap
        ];
    }

    // Sort timeline descending by timestamp
    usort($timeline, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

    $ehrDataMap[$pId] = [
        'profile' => $profile,
        'triages' => $pTriages,
        'consultations' => $pConsultations,
        'prescriptions' => $pPrescriptions,
        'referrals' => $pReferrals,
        'appointments' => $pAppointments,
        'legacy' => $pLegacy,
        'timeline' => $timeline,
        'stats' => [
            'total_visits' => count($pTriages) + count($pConsultations),
            'total_consultations' => count($pConsultations),
            'total_prescriptions' => count($pPrescriptions),
            'total_referrals' => count($pReferrals),
            'total_triages' => count($pTriages),
            'total_appointments' => count($pAppointments)
        ]
    ];

    $patientsList[] = [
        'id' => $pId,
        'patient_id' => $patientCode,
        'name' => $fullName,
        'initials' => $initials,
        'age' => $age,
        'gender' => $p['gender'] ?? 'Unspecified'
    ];
}

// Initial selected patient ID from URL or default to first
$requestedPatientId = (int)($_GET['patient_id'] ?? $_GET['patient'] ?? ($patientsList[0]['id'] ?? 0));
$title = 'Patient Electronic Health Record (EHR)';
?>

<!-- ============================================================ -->
<!-- 2. HTML + TAILWIND CSS INTERFACE                              -->
<!-- ============================================================ -->
<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-y-auto">

    <!-- Page Header & Patient Selector Bar -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-brand-light text-brand-dark border border-brand-border">
                    <i class="fa-solid fa-file-medical mr-1"></i> Centralized EHR Archive
                </span>
                <span class="text-xs text-slate-400">&bull; Comprehensive Medical History</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mt-1">Patient Electronic Health Record</h1>
            <p class="text-xs text-slate-500">Aggregated clinical visits, triage assessments, consultations, prescriptions, referrals & follow-ups</p>
        </div>

        <!-- Patient Search & Selector (Live Autocomplete Search Bar) -->
        <div class="flex items-center gap-3 flex-wrap">
            <div class="relative w-80">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs z-10"></i>
                <input type="text" id="ehrPatientSearch" 
                       placeholder="Search patient by name or ID (e.g. P-0070)..." 
                       autocomplete="off"
                       oninput="filterEhrPatients(false)" 
                       onfocus="handleEhrSearchFocus(this)"
                       class="w-full pl-9 pr-8 py-2 border border-slate-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none shadow-xs font-medium text-slate-800 transition">
                <button type="button" onclick="clearEhrPatientSearch()" id="clearEhrSearchBtn" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-xs">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
                
                <!-- Autocomplete Results Dropdown -->
                <div id="ehrPatientDropdown" class="hidden absolute z-30 mt-1 w-full bg-white border border-slate-200 rounded-xl shadow-xl max-h-72 overflow-y-auto divide-y divide-slate-100">
                    <!-- Dynamic filtered items injected via JS -->
                </div>
            </div>
            <button onclick="printEHRSummary()" class="px-3.5 py-2 bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-print"></i> Print EHR Summary
            </button>
        </div>
    </div>

    <!-- PATIENT HERO PROFILE CARD -->
    <div id="patientHeroCard" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-xs mb-6">
        <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading patient medical record...
        </div>
    </div>

    <!-- TABBED NAVIGATION HEADER -->
    <div class="flex items-center gap-2 border-b border-slate-200 overflow-x-auto pb-px mb-6 text-xs font-semibold">
        <button onclick="switchTab('timeline')" id="tab_btn_timeline" class="ehr-tab-btn px-4 py-2.5 border-b-2 border-brand-medium text-brand-medium flex items-center gap-2 transition">
            <i class="fa-solid fa-clock-rotate-left"></i> Full Clinical Timeline
        </button>
        <button onclick="switchTab('consultations')" id="tab_btn_consultations" class="ehr-tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition">
            <i class="fa-solid fa-stethoscope"></i> Consultations & Diagnoses
        </button>
        <button onclick="switchTab('prescriptions')" id="tab_btn_prescriptions" class="ehr-tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition">
            <i class="fa-solid fa-pills"></i> Prescriptions & Dispensed
        </button>
        <button onclick="switchTab('referrals')" id="tab_btn_referrals" class="ehr-tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition">
            <i class="fa-solid fa-arrow-right-from-bracket"></i> Referrals
        </button>
        <button onclick="switchTab('triages')" id="tab_btn_triages" class="ehr-tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition">
            <i class="fa-solid fa-heart-pulse"></i> Triage & Vitals
        </button>
        <button onclick="switchTab('appointments')" id="tab_btn_appointments" class="ehr-tab-btn px-4 py-2.5 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-2 transition">
            <i class="fa-solid fa-calendar-check"></i> Appointments & Follow-ups
        </button>
    </div>

    <!-- TAB CONTENTS -->
    <div id="tabContentArea" class="space-y-6">
        <!-- Content will be rendered dynamically via JS -->
    </div>

</div>

<!-- PRINTABLE MEDICAL RECORD SUMMARY MODAL -->
<div id="printEhrModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10 print:hidden">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-print text-brand-medium"></i> Printable Medical Record Summary
            </h3>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="px-3.5 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium text-xs font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-print"></i> Print Now
                </button>
                <button onclick="ModalSystem.close('printEhrModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
        <div id="printEhrContent" class="p-8 space-y-6">
            <!-- Printable Certificate Template -->
        </div>
    </div>
</div>

<!-- PRINT STYLESHEET -->
<style>
@media print {
    body * { visibility: hidden; }
    #printEhrContent, #printEhrContent * { visibility: visible; }
    #printEhrContent { position: absolute; left: 0; top: 0; width: 100%; padding: 0; margin: 0; }
    .print\:hidden { display: none !important; }
}
</style>

<!-- ============================================================ -->
<!-- 3. JAVASCRIPT MASTER EHR RENDERER                             -->
<!-- ============================================================ -->
<script>
const EHR_MASTER = <?php echo json_encode($ehrDataMap, JSON_UNESCAPED_UNICODE); ?>;
const PATIENTS_LIST = <?php echo json_encode($patientsList, JSON_UNESCAPED_UNICODE); ?>;
let currentPatientId = <?php echo $requestedPatientId; ?>;
let currentActiveTab = 'timeline';

document.addEventListener('DOMContentLoaded', function () {
    if (!EHR_MASTER[currentPatientId]) {
        const availableIds = Object.keys(EHR_MASTER);
        if (availableIds.length > 0) {
            currentPatientId = parseInt(availableIds[0]);
        }
    }
    const initialPatient = PATIENTS_LIST.find(p => p.id == currentPatientId);
    if (initialPatient) {
        const input = document.getElementById('ehrPatientSearch');
        if (input) {
            input.value = `${initialPatient.name} (${initialPatient.patient_id})`;
        }
    }
    renderCurrentPatient();
});

// ============================================================
// LIVE AUTOCOMPLETE SEARCH FOR PATIENTS BY NAME OR ID
// ============================================================
function handleEhrSearchFocus(input) {
    if (input) {
        input.select();
    }
    filterEhrPatients(true);
}

function filterEhrPatients(isFocusEvent = false) {
    const searchEl = document.getElementById('ehrPatientSearch');
    let query = searchEl ? searchEl.value.toLowerCase().trim() : '';
    const dropdown = document.getElementById('ehrPatientDropdown');
    const clearBtn = document.getElementById('clearEhrSearchBtn');

    // If focus event and query matches currently selected patient's full display string, treat as empty search to list all patients
    const selectedP = PATIENTS_LIST.find(p => p.id == currentPatientId);
    if (selectedP && isFocusEvent) {
        const selectedDisp = `${selectedP.name} (${selectedP.patient_id})`.toLowerCase();
        if (query === selectedDisp) {
            query = '';
        }
    }

    if (clearBtn) clearBtn.classList.toggle('hidden', searchEl ? searchEl.value.length === 0 : true);

    const filtered = (query.length === 0) ? PATIENTS_LIST : PATIENTS_LIST.filter(p => {
        const nameMatch = (p.name || '').toLowerCase().includes(query);
        const codeMatch = (p.patient_id || '').toLowerCase().includes(query);
        const idMatch = (p.id || '').toString().includes(query);
        return nameMatch || codeMatch || idMatch;
    });

    renderEhrPatientDropdown(filtered);
    if (dropdown) dropdown.classList.remove('hidden');
}

function showEhrPatientDropdown() {
    handleEhrSearchFocus(document.getElementById('ehrPatientSearch'));
}

function renderEhrPatientDropdown(patients) {
    const dropdown = document.getElementById('ehrPatientDropdown');
    if (!dropdown) return;

    if (patients.length === 0) {
        dropdown.innerHTML = `
            <div class="p-4 text-center text-xs text-slate-400">
                <i class="fa-solid fa-user-slash text-base mb-1 block"></i>
                No matching patients found
            </div>
        `;
        return;
    }

    let html = '';
    patients.forEach(p => {
        const pName = p.name || '';
        html += `
            <div onclick="selectEhrPatientFromSearch(${p.id})" class="px-3.5 py-2.5 hover:bg-brand-light/50 cursor-pointer transition flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-bold text-xs group-hover:bg-brand-medium group-hover:text-white transition">
                        ${p.initials}
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-brand-dark transition">${escapeHtml(pName)}</p>
                        <p class="text-[10px] text-slate-400 font-mono">${p.patient_id} &bull; ${p.gender} &bull; ${p.age} yrs</p>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-right text-[10px] text-slate-300 group-hover:text-brand-medium transition"></i>
            </div>
        `;
    });
    dropdown.innerHTML = html;
}

function selectEhrPatientFromSearch(patientId) {
    currentPatientId = parseInt(patientId);
    const p = PATIENTS_LIST.find(pt => pt.id == patientId);
    if (p) {
        const input = document.getElementById('ehrPatientSearch');
        if (input) {
            input.value = `${p.name} (${p.patient_id})`;
        }
    }
    const dropdown = document.getElementById('ehrPatientDropdown');
    if (dropdown) dropdown.classList.add('hidden');
    renderCurrentPatient();
}

function clearEhrPatientSearch() {
    const input = document.getElementById('ehrPatientSearch');
    if (input) {
        input.value = '';
        input.focus();
    }
    const clearBtn = document.getElementById('clearEhrSearchBtn');
    if (clearBtn) clearBtn.classList.add('hidden');
    filterEhrPatients(false);
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#ehrPatientSearch') && !e.target.closest('#ehrPatientDropdown')) {
        const dropdown = document.getElementById('ehrPatientDropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }
});

function switchTab(tabKey) {
    currentActiveTab = tabKey;
    document.querySelectorAll('.ehr-tab-btn').forEach(btn => {
        btn.classList.remove('border-brand-medium', 'text-brand-medium');
        btn.classList.add('border-transparent', 'text-slate-500');
    });
    const activeBtn = document.getElementById('tab_btn_' + tabKey);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-slate-500');
        activeBtn.classList.add('border-brand-medium', 'text-brand-medium');
    }
    renderTabContent();
}

function renderCurrentPatient() {
    const data = EHR_MASTER[currentPatientId];
    const heroCard = document.getElementById('patientHeroCard');

    if (!data) {
        heroCard.innerHTML = `
            <div class="py-12 text-center text-slate-400">
                <i class="fa-solid fa-folder-open text-3xl mb-2"></i>
                <p class="text-sm font-semibold">No medical records found for this patient.</p>
            </div>
        `;
        document.getElementById('tabContentArea').innerHTML = '';
        return;
    }

    const p = data.profile;
    const st = data.stats;

    // Render Hero Profile Card
    heroCard.innerHTML = `
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 pb-6 border-b border-slate-100">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0 shadow-xs">
                    ${p.initials}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-bold text-slate-900 maskable" data-real="${p.full_name}" data-masked="${maskName(p.full_name)}">${maskName(p.full_name)}</h2>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase">${p.status}</span>
                    </div>
                    <p class="text-xs text-slate-500 font-mono mt-0.5">
                        <span class="font-semibold text-slate-700">${p.patient_id}</span> &bull; ${p.gender} &bull; ${p.age} yrs old &bull; DOB: ${p.birth_date}
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="px-2 py-0.5 bg-rose-50 border border-rose-200 text-rose-700 rounded-md text-[10px] font-semibold">
                            <i class="fa-solid fa-droplet text-rose-500 mr-1"></i> Blood: <strong>${p.blood_type}</strong>
                        </span>
                        <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-800 rounded-md text-[10px] font-semibold">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 mr-1"></i> Allergies: <strong>${p.allergies}</strong>
                        </span>
                        <span class="px-2 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-md text-[10px] font-semibold">
                            <i class="fa-solid fa-notes-medical text-indigo-600 mr-1"></i> Conditions: <strong>${p.conditions}</strong>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Action Short-cuts -->
            <div class="flex items-center gap-2 flex-wrap shrink-0">
                <a href="consultations.php?patient_id=${p.id}&action=new" class="px-3 py-2 bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 rounded-xl transition text-xs font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-plus text-xs"></i> New Consultation
                </a>
                <a href="prescriptions.php?patient_id=${p.id}&action=new" class="px-3 py-2 bg-teal-50 border border-teal-200 text-teal-700 hover:bg-teal-100 rounded-xl transition text-xs font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-pills text-xs"></i> Issue Rx
                </a>
                <a href="referrals.php?patient_id=${p.id}&action=new" class="px-3 py-2 bg-amber-50 border border-amber-200 text-amber-700 hover:bg-amber-100 rounded-xl transition text-xs font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i> Create Referral
                </a>
                <a href="patients.php?patient=${p.id}&autoView=true" class="px-3 py-2 bg-brand-dark text-white rounded-xl hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-user-gear text-xs"></i> Full Demographic Profile
                </a>
            </div>
        </div>

        <!-- Contact & Demographic Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4 text-xs">
            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Contact Number</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.contact}" data-masked="${maskName(p.contact)}">${maskName(p.contact)}</p></div>
            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Barangay</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.barangay}" data-masked="${maskName(p.barangay)}">${maskName(p.barangay)}</p></div>
            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Emergency Contact</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.emergency_contact}" data-masked="${maskName(p.emergency_contact)}">${maskName(p.emergency_contact)}</p></div>
            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Registration Date</p><p class="text-slate-800 font-medium mt-0.5">${p.registration_date}</p></div>
        </div>

        <!-- Lifetime Counters Strip -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-5 mt-5 border-t border-slate-100 text-center">
            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200">
                <span class="text-lg font-bold text-slate-800 block">${st.total_visits}</span>
                <span class="text-[10px] font-semibold uppercase text-slate-400">Total Visits</span>
            </div>
            <div class="bg-emerald-50/60 p-2.5 rounded-xl border border-emerald-100">
                <span class="text-lg font-bold text-emerald-700 block">${st.total_consultations}</span>
                <span class="text-[10px] font-semibold uppercase text-emerald-600">Consultations</span>
            </div>
            <div class="bg-teal-50/60 p-2.5 rounded-xl border border-teal-100">
                <span class="text-lg font-bold text-teal-700 block">${st.total_prescriptions}</span>
                <span class="text-[10px] font-semibold uppercase text-teal-600">Prescriptions</span>
            </div>
            <div class="bg-amber-50/60 p-2.5 rounded-xl border border-amber-100">
                <span class="text-lg font-bold text-amber-700 block">${st.total_referrals}</span>
                <span class="text-[10px] font-semibold uppercase text-amber-600">Referrals</span>
            </div>
            <div class="bg-purple-50/60 p-2.5 rounded-xl border border-purple-100">
                <span class="text-lg font-bold text-purple-700 block">${st.total_triages}</span>
                <span class="text-[10px] font-semibold uppercase text-purple-600">Vitals & Triages</span>
            </div>
        </div>
    `;

    renderTabContent();
    if (typeof ModalSystem !== 'undefined' && ModalSystem.refreshMasking) {
        ModalSystem.refreshMasking('patientHeroCard');
    }
}

function renderTabContent() {
    const data = EHR_MASTER[currentPatientId];
    const container = document.getElementById('tabContentArea');
    if (!data) return;

    if (currentActiveTab === 'timeline') {
        renderTimelineView(data.timeline, container);
    } else if (currentActiveTab === 'consultations') {
        renderConsultationsView(data.consultations, container);
    } else if (currentActiveTab === 'prescriptions') {
        renderPrescriptionsView(data.prescriptions, container);
    } else if (currentActiveTab === 'referrals') {
        renderReferralsView(data.referrals, container);
    } else if (currentActiveTab === 'triages') {
        renderTriagesView(data.triages, container);
    } else if (currentActiveTab === 'appointments') {
        renderAppointmentsView(data.appointments, container);
    }
}

// 1. TIMELINE VIEW
function renderTimelineView(timeline, container) {
    if (timeline.length === 0) {
        container.innerHTML = `<div class="bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400"><i class="fa-solid fa-clock-rotate-left text-3xl mb-2"></i><p class="text-sm font-semibold">No timeline entries recorded yet for this patient.</p></div>`;
        return;
    }

    let html = `<div class="relative border-l-2 border-slate-200 ml-4 space-y-6">`;
    timeline.forEach(item => {
        html += `
            <div class="relative pl-6">
                <div class="absolute -left-[17px] top-1 w-8 h-8 rounded-full bg-white border-2 border-brand-medium flex items-center justify-center text-xs shadow-xs">
                    <i class="fa-solid ${item.icon}"></i>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs hover:shadow-md transition">
                    <div class="flex items-center justify-between flex-wrap gap-2 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${item.badge}">${item.badge_label}</span>
                            <h4 class="text-sm font-bold text-slate-800">${escapeHtml(item.title)}</h4>
                        </div>
                        <span class="text-xs text-slate-400 font-mono"><i class="fa-regular fa-clock mr-1"></i>${item.date_display}</span>
                    </div>
                    <div class="pt-2 text-xs text-slate-600 space-y-1">
                        <p class="text-slate-500 font-medium">Attending Staff: <strong class="text-slate-800">${escapeHtml(item.provider)}</strong></p>
                        <p class="text-slate-700 bg-slate-50 p-2.5 rounded-lg border border-slate-100 mt-1">${escapeHtml(item.summary)}</p>
                    </div>
                </div>
            </div>
        `;
    });
    html += `</div>`;
    container.innerHTML = html;
}

// 2. CONSULTATIONS VIEW
function renderConsultationsView(consultations, container) {
    if (consultations.length === 0) {
        container.innerHTML = `<div class="bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400"><i class="fa-solid fa-stethoscope text-3xl mb-2"></i><p class="text-sm font-semibold">No doctor consultations recorded yet.</p></div>`;
        return;
    }
    let html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;
    consultations.forEach(c => {
        html += `
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-mono font-bold text-brand-medium">${c.consultation_id}</span>
                            <h4 class="text-base font-bold text-slate-900 mt-0.5">${escapeHtml(c.diagnosis)}</h4>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold ${c.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">${c.status ? c.status.toUpperCase() : 'COMPLETED'}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 my-3 bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <div><span class="text-slate-400 uppercase text-[10px] block font-semibold">Doctor</span><strong>${escapeHtml(c.doctor_name || 'N/A')}</strong></div>
                        <div><span class="text-slate-400 uppercase text-[10px] block font-semibold">Date & Time</span><strong>${c.date} ${c.time || ''}</strong></div>
                        <div><span class="text-slate-400 uppercase text-[10px] block font-semibold">ICD-10 Code</span><strong class="font-mono text-indigo-600">${c.icd_code || 'N/A'}</strong></div>
                        <div><span class="text-slate-400 uppercase text-[10px] block font-semibold">Follow-up</span><strong>${c.follow_up_date || 'None'}</strong></div>
                    </div>
                    ${c.symptoms ? `<div class="mb-2"><span class="text-[10px] font-bold text-slate-400 uppercase">Symptoms:</span><p class="text-xs text-slate-700 bg-slate-50 p-2 rounded-md border border-slate-100">${escapeHtml(c.symptoms)}</p></div>` : ''}
                    <div><span class="text-[10px] font-bold text-slate-400 uppercase">Treatment Plan:</span><p class="text-xs text-slate-800 bg-brand-light/30 p-2 rounded-md border border-brand-border">${escapeHtml(c.treatment_plan || 'No treatment plan specified.')}</p></div>
                </div>
            </div>
        `;
    });
    html += `</div>`;
    container.innerHTML = html;
}

// 3. PRESCRIPTIONS VIEW
function renderPrescriptionsView(prescriptions, container) {
    if (prescriptions.length === 0) {
        container.innerHTML = `<div class="bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400"><i class="fa-solid fa-pills text-3xl mb-2"></i><p class="text-sm font-semibold">No prescriptions issued for this patient.</p></div>`;
        return;
    }
    let html = `<div class="space-y-4">`;
    prescriptions.forEach(p => {
        const meds = is_string(p.medications) ? json_decode(p.medications, true) : (p.medications || []);
        const statusBadge = p.status === 'dispensed' 
            ? '<span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">Dispensed</span>' 
            : '<span class="px-2.5 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold">Pending Dispensation</span>';

        let medsTable = `<div class="overflow-x-auto mt-3"><table class="w-full text-xs border border-slate-200 rounded-lg"><thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left">Medication</th><th class="px-3 py-2 text-left">Dosage</th><th class="px-3 py-2 text-left">Frequency</th><th class="px-3 py-2 text-left">Duration</th><th class="px-3 py-2 text-center">Qty</th></tr></thead><tbody>`;
        if (Array.isArray(meds) && meds.length > 0) {
            meds.forEach(m => {
                medsTable += `<tr class="border-t border-slate-100"><td class="px-3 py-2 font-bold text-slate-800">${escapeHtml(m.name || '')}</td><td class="px-3 py-2 text-slate-600">${escapeHtml(m.dosage || 'N/A')}</td><td class="px-3 py-2 text-slate-600">${escapeHtml(m.frequency || '')}</td><td class="px-3 py-2 text-slate-600">${escapeHtml(m.duration || '')}</td><td class="px-3 py-2 text-center font-semibold text-brand-medium">${m.quantity || 1}</td></tr>`;
            });
        } else {
            medsTable += `<tr><td colspan="5" class="px-3 py-4 text-center text-slate-400">No itemized medications recorded</td></tr>`;
        }
        medsTable += `</tbody></table></div>`;

        html += `
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-mono font-bold text-teal-600">Prescription #${p.prescription_id}</span>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Doctor: <strong class="text-slate-800">${escapeHtml(p.doctor_name || 'Attending Physician')}</strong> &bull; Date: ${p.date || p.created_at}</p>
                    </div>
                    ${statusBadge}
                </div>
                ${medsTable}
                ${p.notes ? `<p class="text-xs text-slate-500 italic mt-3 bg-slate-50 p-2 rounded-md">Notes: ${escapeHtml(p.notes)}</p>` : ''}
            </div>
        `;
    });
    html += `</div>`;
    container.innerHTML = html;
}

// 4. REFERRALS VIEW
function renderReferralsView(referrals, container) {
    if (referrals.length === 0) {
        container.innerHTML = `<div class="bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400"><i class="fa-solid fa-arrow-right-from-bracket text-3xl mb-2"></i><p class="text-sm font-semibold">No referrals recorded for this patient.</p></div>`;
        return;
    }
    let html = `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;
    referrals.forEach(r => {
        html += `
            <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-xs hover:shadow-md transition">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <span class="text-xs font-mono font-bold text-amber-600">${r.referral_id || ('REF-' + r.id)}</span>
                        <h4 class="text-sm font-bold text-slate-900 mt-0.5">Referred to: ${escapeHtml(r.to_specialist || r.to_hospital || 'Specialist Clinic')}</h4>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold ${r.urgency === 'critical' || r.urgency === 'emergency' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'}">${(r.urgency || 'routine').toUpperCase()}</span>
                </div>
                <div class="text-xs text-slate-600 my-3 space-y-1">
                    <p><span class="text-slate-400 font-semibold">Referring Doctor:</span> <strong>${escapeHtml(r.from_doctor || 'Doctor')}</strong></p>
                    <p><span class="text-slate-400 font-semibold">Reason:</span> ${escapeHtml(r.reason || 'N/A')}</p>
                    ${r.diagnosis ? `<p><span class="text-slate-400 font-semibold">Diagnosis:</span> ${escapeHtml(r.diagnosis)}</p>` : ''}
                    ${r.feedback ? `<p class="bg-amber-50 p-2 rounded-md border border-amber-100 mt-2 text-amber-900"><strong>Feedback/Outcome:</strong> ${escapeHtml(r.feedback)}</p>` : ''}
                </div>
            </div>
        `;
    });
    html += `</div>`;
    container.innerHTML = html;
}

// 5. TRIAGES VIEW
function renderTriagesView(triages, container) {
    if (triages.length === 0) {
        container.innerHTML = `<div class="bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400"><i class="fa-solid fa-heart-pulse text-3xl mb-2"></i><p class="text-sm font-semibold">No triage vital sign assessments recorded.</p></div>`;
        return;
    }
    let html = `<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs"><table class="w-full text-xs"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left">Date / Time</th><th class="px-4 py-3 text-left">Priority</th><th class="px-4 py-3 text-left">Blood Pressure</th><th class="px-4 py-3 text-left">Heart Rate</th><th class="px-4 py-3 text-left">Temperature</th><th class="px-4 py-3 text-left">SpO2</th><th class="px-4 py-3 text-left">Symptoms / Notes</th></tr></thead><tbody>`;
    triages.forEach(t => {
        html += `
            <tr class="border-b border-slate-100 hover:bg-slate-50/60">
                <td class="px-4 py-3 font-mono font-medium text-slate-700">${t.created_at ? t.created_at.substring(0, 16) : 'N/A'}</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${t.priority === 'high' || t.priority === 'emergency' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'}">${(t.priority || 'low').toUpperCase()}</span></td>
                <td class="px-4 py-3 font-bold text-slate-800">${t.blood_pressure || 'N/A'}</td>
                <td class="px-4 py-3 text-slate-700">${t.heart_rate ? t.heart_rate + ' bpm' : 'N/A'}</td>
                <td class="px-4 py-3 text-slate-700">${t.temperature ? t.temperature + ' °C' : 'N/A'}</td>
                <td class="px-4 py-3 text-slate-700">${t.oxygen_saturation ? t.oxygen_saturation + '%' : 'N/A'}</td>
                <td class="px-4 py-3 text-slate-600">${escapeHtml(t.symptoms || t.notes || 'N/A')}</td>
            </tr>
        `;
    });
    html += `</tbody></table></div>`;
    container.innerHTML = html;
}

// 6. APPOINTMENTS VIEW
function renderAppointmentsView(appointments, container) {
    if (appointments.length === 0) {
        container.innerHTML = `<div class="bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400"><i class="fa-solid fa-calendar-check text-3xl mb-2"></i><p class="text-sm font-semibold">No appointments scheduled or recorded.</p></div>`;
        return;
    }
    let html = `<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs"><table class="w-full text-xs"><thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left">Appt ID</th><th class="px-4 py-3 text-left">Service Type</th><th class="px-4 py-3 text-left">Date & Time</th><th class="px-4 py-3 text-left">Attending Doctor/Staff</th><th class="px-4 py-3 text-left">Status</th></tr></thead><tbody>`;
    appointments.forEach(a => {
        html += `
            <tr class="border-b border-slate-100 hover:bg-slate-50/60">
                <td class="px-4 py-3 font-mono font-bold text-blue-600">${a.appointment_id || ('APT-' + a.id)}</td>
                <td class="px-4 py-3 font-semibold text-slate-800">${escapeHtml(a.service_type || 'General')}</td>
                <td class="px-4 py-3 text-slate-600">${a.appointment_date || ''} ${a.appointment_time || ''}</td>
                <td class="px-4 py-3 text-slate-700">${escapeHtml(a.doctor_name || 'N/A')}</td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${a.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'}">${(a.status || 'scheduled').toUpperCase()}</span></td>
            </tr>
        `;
    });
    html += `</tbody></table></div>`;
    container.innerHTML = html;
}

// PRINT EHR SUMMARY GENERATOR
function printEHRSummary() {
    const data = EHR_MASTER[currentPatientId];
    if (!data) return;
    const p = data.profile;
    const content = document.getElementById('printEhrContent');

    let consultsList = '';
    data.consultations.forEach(c => {
        consultsList += `<div style="margin-bottom:10px; padding:8px; border:1px solid #e2e8f0; border-radius:6px;"><strong>Date: ${c.date}</strong> - Doctor: ${c.doctor_name || ''}<br/><strong>Diagnosis:</strong> ${c.diagnosis} (ICD-10: ${c.icd_code || 'N/A'})<br/><strong>Treatment Plan:</strong> ${c.treatment_plan || 'N/A'}</div>`;
    });

    content.innerHTML = `
        <div style="font-family: sans-serif; color: #1e293b;">
            <div style="text-align: center; border-bottom: 2px solid #0B4F4A; padding-bottom: 15px; margin-bottom: 20px;">
                <h2 style="margin:0; color:#0B4F4A; font-size:20px;">BARANGAY HEALTH CENTER</h2>
                <h3 style="margin:4px 0 0 0; color:#14807A; font-size:16px;">PATIENT ELECTRONIC HEALTH RECORD (EHR) SUMMARY</h3>
                <p style="margin:2px 0 0 0; font-size:11px; color:#64748b;">Official Medical History Document &bull; Generated ${new Date().toLocaleDateString()}</p>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px; font-size:12px; background:#f8fafc; padding:12px; border-radius:8px;">
                <div>
                    <p style="margin:2px 0;"><strong>Patient Name:</strong> ${p.full_name}</p>
                    <p style="margin:2px 0;"><strong>Patient Code:</strong> ${p.patient_id}</p>
                    <p style="margin:2px 0;"><strong>Age / Gender:</strong> ${p.age} yrs old / ${p.gender}</p>
                    <p style="margin:2px 0;"><strong>Blood Type:</strong> ${p.blood_type}</p>
                </div>
                <div>
                    <p style="margin:2px 0;"><strong>Contact:</strong> ${p.contact}</p>
                    <p style="margin:2px 0;"><strong>Barangay:</strong> ${p.barangay}</p>
                    <p style="margin:2px 0;"><strong>Known Allergies:</strong> ${p.allergies}</p>
                    <p style="margin:2px 0;"><strong>Existing Conditions:</strong> ${p.conditions}</p>
                </div>
            </div>

            <h4 style="color:#0B4F4A; border-bottom:1px solid #cbd5e1; padding-bottom:4px; margin-top:20px;">Medical Consultations & Diagnoses</h4>
            ${consultsList || '<p style="font-size:12px; color:#94a3b8;">No consultation entries recorded.</p>'}

            <div style="margin-top:40px; display:flex; justify-content:between; font-size:12px;">
                <div style="width:45%; border-top:1px solid #94a3b8; pt:6px; text-align:center;">
                    <p style="margin:0;"><strong>Attending Physician Signature</strong></p>
                    <p style="margin:2px 0; color:#64748b;">Licensed Medical Practitioner</p>
                </div>
                <div style="width:45%; border-top:1px solid #94a3b8; pt:6px; text-align:center;">
                    <p style="margin:0;"><strong>Barangay Health Center Seal</strong></p>
                    <p style="margin:2px 0; color:#64748b;">Health Services Office</p>
                </div>
            </div>
        </div>
    `;

    ModalSystem.open('printEhrModal');
}

function maskName(name) {
    if (!name) return '';
    if (typeof ModalSystem !== 'undefined' && typeof ModalSystem.maskName === 'function') {
        return ModalSystem.maskName(name);
    }
    return String(name).split(' ').map(p => p ? p[0].toUpperCase() + '*'.repeat(Math.max(0, p.length - 1)) : '').join(' ');
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}
function is_string(val) { return typeof val === 'string'; }
function json_decode(str) { try { return JSON.parse(str); } catch(e) { return null; } }
</script>

<?php include_once '../../includes/footer.php'; ?>
