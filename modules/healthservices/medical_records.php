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
require_once '../../app/Models/Patient.php';
require_once '../../app/Models/Employee.php';
require_once '../../app/Models/MedicalRecord.php';

$healthCenterTests = [
    'Complete Blood Count (CBC)',
    'Urinalysis',
    'Fecalysis',
    'Fasting Blood Sugar (FBS)',
    'Random Blood Sugar (RBS)',
    'HbA1c',
    'Lipid Profile',
    'Blood Typing',
    'Pregnancy Test',
    'Dengue NS1 / IgM / IgG',
    'Tuberculosis Sputum Test',
    'Chest X-Ray',
    'X-Ray',
    'ECG',
    'Blood Pressure Reading',
    'Prenatal Ultrasound',
    'Wound Dressing Record',
    'Minor Procedure Record',
    'Dental Checkup',
    'Medical Certificate'
];

function medicalRecordInitials(string $name): string {
    $letters = '';
    foreach (preg_split('/\s+/', trim($name)) as $part) {
        if ($part !== '') $letters .= strtoupper($part[0]);
    }
    return substr($letters ?: 'P', 0, 2);
}

function decodeMedicalRecordMeta($value): array {
    if (is_array($value)) return $value;
    if (!$value) return [];
    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function mapMedicalRecordForPage(array $record, array $patientsById, Employee $employeeModel): array {
    $patient = $patientsById[$record['patient_id']] ?? null;
    $patientName = $patient ? trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? '')) : 'Patient #' . ($record['patient_id'] ?? '');
    $meta = decodeMedicalRecordMeta($record['attachments'] ?? null);
    $doctor = $meta['doctor'] ?? '';
    if (!$doctor && !empty($record['created_by'])) {
        $doctor = $employeeModel->getFullName($record['created_by']);
    }

    return [
        'id' => $record['id'],
        'patient_id' => $record['patient_id'],
        'patient_name' => $patientName,
        'patient_code' => $patient['patient_id'] ?? '',
        'patient_avatar' => medicalRecordInitials($patientName),
        'record_type' => $record['record_type'],
        'title' => $meta['title'] ?? (ucfirst($record['record_type']) . ' Record'),
        'description' => $record['description'],
        'date' => $record['date'],
        'doctor' => $doctor,
        'status' => $meta['status'] ?? 'completed',
        'shared_with' => $meta['shared_with'] ?? [],
        'attachments' => $meta['files'] ?? [],
        'created_by' => $record['created_by'] ?? null,
    ];
}

$patients = [];
$medicalRecords = [];
$employees = [];
$doctorEmployees = [];
$recordsClerkEmployees = [];
$pageError = '';

try {
    $patientModel = new Patient();
    $employeeModel = new Employee();
    $recordModel = new MedicalRecord();

    $rawPatients = $patientModel->all(['order' => 'last_name.asc']);
    foreach ($rawPatients as $patient) {
        $patients[] = [
            'id' => $patient['id'],
            'patient_id' => $patient['patient_id'] ?? ('P-' . $patient['id']),
            'name' => trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''))
        ];
    }

    $doctorEmployees = array_values(array_filter($employees, function($employee) {
        $role = $employee['role'] ?? '';
        $desc = strtolower($employee['role_description'] ?? '');
        $dept = strtolower(trim($employee['department'] ?? ''));
        return (in_array($role, ['Medical Practitioner', 'Health Center Director']) || str_contains($desc, 'doctor') || str_contains($desc, 'physician'))
            && ($dept === '' || str_contains($dept, 'health') || str_contains($dept, 'medical'));
    }));
    $recordsClerkEmployees = array_values(array_filter($employees, function($employee) {
        $role = $employee['role'] ?? '';
        $desc = strtolower($employee['role_description'] ?? '');
        $dept = strtolower(trim($employee['department'] ?? ''));
        return (in_array($role, ['Health Center Staff', 'Health Center Director']) || str_contains($desc, 'clerk') || str_contains($desc, 'records'))
            && ($dept === '' || str_contains($dept, 'health') || str_contains($dept, 'medical'));
    }));
    $patientsById = array_column($rawPatients, null, 'id');
    $rawRecords = $recordModel->all(['order' => 'date.desc,created_at.desc']);
    $medicalRecords = array_map(fn($record) => mapMedicalRecordForPage($record, $patientsById, $employeeModel), $rawRecords);
} catch (Throwable $e) {
    $pageError = $e->getMessage();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;
$totalRecords = count($medicalRecords);
$totalPages = ceil($totalRecords / $limit);
$paginatedRecords = array_slice($medicalRecords, $offset, $limit);

$title = 'Medical Records';

// Stats
$totalConsultations = count(array_filter($medicalRecords, fn($r) => $r['record_type'] === 'consultation'));
$totalLabResults = count(array_filter($medicalRecords, fn($r) => $r['record_type'] === 'lab'));
$totalImaging = count(array_filter($medicalRecords, fn($r) => $r['record_type'] === 'imaging'));
$totalShared = count(array_filter($medicalRecords, fn($r) => count($r['shared_with']) > 0));
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Medical Records</h2>
            <p class="text-sm text-slate-500 mt-0.5">Electronic Health Record (EHR) - Documentation & Reporting</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openAddRecordModal()"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Add Record
            </button>
        </div>
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
                    <i class="fa-solid fa-notes-medical text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900"><?php echo $totalRecords; ?></p>
                    <p class="text-xs font-medium text-slate-500">Total Records</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All records</span>
                <span class="text-[10px] text-slate-400"><?php echo $totalConsultations; ?> consultations</span>
            </div>
        </div>
    </div>

    <!-- Card 2: Consultations -->
    <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                    <i class="fa-solid fa-stethoscope text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-emerald-600"><?php echo $totalConsultations; ?></p>
                    <p class="text-xs font-medium text-slate-500">Consultations</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">🩺 Patient visits</span>
                <span class="text-[10px] text-slate-400">Clinical encounters</span>
            </div>
        </div>
    </div>

    <!-- Card 3: Lab Results -->
    <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-violet-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-violet-200">
                    <i class="fa-solid fa-flask text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-violet-600"><?php echo $totalLabResults; ?></p>
                    <p class="text-xs font-medium text-slate-500">Lab Results</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-[10px] font-bold">🧪 Tests</span>
                <span class="text-[10px] text-slate-400">Diagnostic results</span>
            </div>
        </div>
    </div>

    <!-- Card 4: Shared Records -->
    <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
        <div class="absolute -top-12 -right-12 w-24 h-24 bg-sky-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
        <div class="relative">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-sky-500 to-sky-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-sky-200">
                    <i class="fa-solid fa-share-nodes text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-sky-600"><?php echo $totalShared; ?></p>
                    <p class="text-xs font-medium text-slate-500">Shared Records</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-sky-100 text-sky-700 rounded-full text-[10px] font-bold">📤 Shared</span>
                <span class="text-[10px] text-slate-400">Collaborative care</span>
            </div>
        </div>
    </div>
</div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchRecord"
                       placeholder="Search by patient ID, patient name, test title, or record type..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterTitle" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Lab / Test Titles</option>
                    <?php foreach ($healthCenterTests as $testTitle): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($testTitle)); ?>"><?php echo htmlspecialchars($testTitle); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterType" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="consultation">Consultation</option>
                    <option value="lab">Lab Result</option>
                    <option value="imaging">Imaging</option>
                    <option value="procedure">Procedure</option>
                    <option value="other">Other</option>
                </select>
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                </select>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Records Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="recordsGrid">
        <?php foreach ($paginatedRecords as $record): ?>
        <div class="record-card bg-white rounded-xl shadow-xs border border-slate-200 p-4 hover:shadow-md transition-all duration-200"
             data-patient="<?php echo strtolower($record['patient_name']); ?>"
             data-patient-id="<?php echo strtolower($record['patient_code'] ?: ('patient-' . $record['patient_id'])); ?>"
             data-type="<?php echo $record['record_type']; ?>"
             data-title="<?php echo strtolower($record['title']); ?>"
             data-status="<?php echo $record['status']; ?>">
            
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                        <?php echo $record['patient_avatar']; ?>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm maskable" data-real="<?php echo htmlspecialchars($record['patient_name']); ?>" data-masked="<?php echo htmlspecialchars(maskName($record['patient_name'])); ?>"><?php echo htmlspecialchars(maskName($record['patient_name'])); ?></p>
                        <p class="text-xs text-slate-400">
                            <span class="font-semibold text-slate-500"><?php echo htmlspecialchars($record['patient_code'] ?: ('Patient #' . $record['patient_id'])); ?></span>
                            <span class="mx-1">-</span><?php echo htmlspecialchars(ucfirst($record['record_type'])); ?>
                        </p>
                    </div>
                </div>
                <?php
                    $typeIcons = [
                        'consultation' => 'fa-stethoscope text-emerald-600',
                        'lab' => 'fa-flask text-violet-600',
                        'imaging' => 'fa-image text-sky-600',
                        'procedure' => 'fa-syringe text-amber-600',
                        'other' => 'fa-file text-slate-400'
                    ];
                    $icon = $typeIcons[$record['record_type']] ?? $typeIcons['other'];
                ?>
                <span class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center">
                    <i class="fa-solid <?php echo $icon; ?>"></i>
                </span>
            </div>
            
            <!-- Title & Description -->
            <div>
                <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($record['title']); ?></p>
                <p class="text-xs text-slate-500 line-clamp-2 mt-1 maskable" data-real="<?php echo htmlspecialchars($record['description']); ?>" data-masked="Protected clinical note"><?php echo htmlspecialchars('Protected clinical note'); ?></p>
            </div>
            
            <!-- Details -->
            <div class="mt-3 flex items-center justify-between text-xs">
                <span class="text-slate-500"><?php echo date('M d, Y', strtotime($record['date'])); ?></span>
                <span class="text-slate-500"><?php echo htmlspecialchars($record['doctor']); ?></span>
            </div>
            
            <!-- Status & Shared -->
            <div class="mt-2 flex items-center justify-between">
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $record['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?>">
                    <?php echo ucfirst($record['status']); ?>
                </span>
                <?php if (count($record['shared_with']) > 0): ?>
                    <span class="text-xs text-slate-400" title="Shared with: <?php echo implode(', ', $record['shared_with']); ?>">
                        <i class="fa-solid fa-share-nodes text-sky-500 mr-1"></i>
                        <?php echo count($record['shared_with']); ?> shared
                    </span>
                <?php endif; ?>
                <?php if (count($record['attachments']) > 0): ?>
                    <span class="text-xs text-slate-400">
                        <i class="fa-solid fa-paperclip mr-1"></i>
                        <?php echo count($record['attachments']); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Actions -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button onclick="viewRecord(<?php echo $record['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-brand-medium hover:bg-brand-light rounded-lg transition">
                    <i class="fa-solid fa-eye mr-1"></i> View
                </button>
                <button onclick="editRecord(<?php echo $record['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-lg transition">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
                <button onclick="shareRecord(<?php echo $record['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-sky-600 hover:bg-sky-50 rounded-lg transition">
                    <i class="fa-solid fa-share-nodes mr-1"></i> Share
                </button>
                <button onclick="deleteRecord(<?php echo $record['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg transition">
                    <i class="fa-solid fa-trash mr-1"></i> Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="fa-solid fa-notes-medical text-slate-400"></i>
        </div>
        <p class="text-sm font-semibold text-slate-600">No records match your filters</p>
        <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
        <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
    </div>

    <!-- Pagination -->
    <div class="mt-4 px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-white rounded-xl shadow-xs border border-slate-200">
        <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700"><?php echo $totalRecords ? $offset + 1 : 0; ?></span> to
            <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalRecords); ?></span> of
            <span class="font-semibold text-slate-700"><?php echo $totalRecords; ?></span> records
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

<!-- ============================================================ -->
<!-- VIEW RECORD MODAL                                            -->
<!-- ============================================================ -->
<div id="viewRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Medical Record Details</h3>
            <button onclick="ModalSystem.close('viewRecordModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="recordDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD RECORD MODAL                                             -->
<!-- ============================================================ -->
<div id="addRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Add Medical Record</h3>
            <button onclick="ModalSystem.close('addRecordModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addRecordForm" class="p-6 space-y-4" onsubmit="saveNewRecord(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label>
                <select id="add_patient" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Patient</option>
                    <?php foreach ($patients as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?> (<?php echo $p['patient_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Record Type</label>
                <select id="add_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="consultation">Consultation</option>
                    <option value="lab">Lab Result</option>
                    <option value="imaging">Imaging</option>
                    <option value="procedure">Procedure</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Title</label>
                <select id="add_title" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Lab / Test Title</option>
                    <?php foreach ($healthCenterTests as $testTitle): ?>
                        <option value="<?php echo htmlspecialchars($testTitle); ?>"><?php echo htmlspecialchars($testTitle); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Description</label>
                <textarea id="add_description" rows="3" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                <input type="date" id="add_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Doctor</label>
                <select id="add_doctor" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctorEmployees as $employee): ?>
                        <option value="<?php echo htmlspecialchars($employee['full_name']); ?>"><?php echo htmlspecialchars($employee['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($doctorEmployees)): ?>
                    <p class="text-[10px] text-rose-500 mt-1">No Health Center employee with Doctor role description found.</p>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recorded By</label>
                <select id="add_created_by" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Medical Records Clerk</option>
                    <?php foreach ($recordsClerkEmployees as $employee): ?>
                        <option value="<?php echo htmlspecialchars($employee['id']); ?>"><?php echo htmlspecialchars($employee['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($recordsClerkEmployees)): ?>
                    <p class="text-[10px] text-rose-500 mt-1">No Health Center employee with Med Records Clerk role description found.</p>
                <?php endif; ?>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                <select id="add_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Share With (Optional)</label>
                <select id="add_shared" multiple class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none min-h-24">
                    <?php foreach ($doctorEmployees as $employee): ?>
                        <option value="<?php echo htmlspecialchars($employee['full_name']); ?>"><?php echo htmlspecialchars($employee['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[10px] text-slate-400 mt-1">Hold Ctrl or Cmd to select more than one doctor</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Attachments</label>
                <input type="file" id="add_attachments" multiple class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                <p class="text-[10px] text-slate-400 mt-1">Supported: PDF, JPG, PNG, GIF, WebP (Max 5MB each)</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('addRecordModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Add Record
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- SHARE RECORD MODAL                                           -->
<!-- ============================================================ -->
<div id="shareRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900">Share Medical Record</h3>
            <button onclick="ModalSystem.close('shareRecordModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                    MS
                </div>
                <div>
                    <p id="sharePatientName" class="font-semibold text-slate-800 text-sm">Maria Santos</p>
                    <p id="shareRecordTitle" class="text-xs text-slate-400">Hypertension Follow-up</p>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Share With</label>
                <select id="shareWithInput" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Doctor</option>
                    <?php foreach ($doctorEmployees as $employee): ?>
                        <option value="<?php echo htmlspecialchars($employee['full_name']); ?>"><?php echo htmlspecialchars($employee['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button onclick="addShareDoctor()" class="mt-2 text-xs font-semibold text-brand-medium hover:text-brand-dark transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Doctor
                </button>
            </div>
            <div id="shareDoctorsList" class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-brand-light/40 rounded-full text-xs text-slate-600 border border-brand-border">Dr. Elena Santos</span>
                <span class="px-2 py-1 bg-brand-light/40 rounded-full text-xs text-slate-600 border border-brand-border">Dr. Miguel Reyes</span>
            </div>
            <p class="text-[10px] text-slate-400 mt-2">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Shared doctors will have view-only access to this record.
            </p>
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6">
            <button type="button" onclick="ModalSystem.close('shareRecordModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                Cancel
            </button>
            <button type="button" onclick="confirmShareRecord()"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                <i class="fa-solid fa-share-nodes mr-1.5"></i> Share Record
            </button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<style>
    .record-card {
        transition: all 0.2s ease;
    }
    .record-card:hover {
        transform: translateY(-2px);
    }
</style>

<script>
    const RECORDS = <?php echo json_encode(array_column($medicalRecords, null, 'id'), JSON_PRETTY_PRINT); ?>;
    const API_URL = '../../api/medical_records.php';
    const PAGE_ERROR = <?php echo json_encode($pageError); ?>;
    let editingRecordId = null;

    function notify(type, message, title = '') {
        if (window.toast && typeof window.toast[type] === 'function') {
            window.toast[type](message, { title });
            return;
        }
        if (window.ModalSystem && ModalSystem.toast && typeof ModalSystem.toast[type] === 'function') {
            ModalSystem.toast[type](message);
        }
    }

    async function sendRecordRequest(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Request failed');
        }
        return result;
    }

    function getAttachmentNames(inputId) {
        const input = document.getElementById(inputId);
        return input && input.files ? Array.from(input.files).map(file => file.name) : [];
    }

    function selectedValues(selectId) {
        const select = document.getElementById(selectId);
        return select ? Array.from(select.selectedOptions).map(option => option.value).filter(Boolean) : [];
    }

    function setSelectedValues(selectId, values) {
        const selected = Array.isArray(values) ? values : [];
        const select = document.getElementById(selectId);
        if (!select) return;
        Array.from(select.options).forEach(option => {
            option.selected = selected.includes(option.value);
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function maskedText(real, masked) {
        return `data-real="${escapeHtml(real)}" data-masked="${escapeHtml(masked)}"`;
    }

    function maskOptionLabel(label) {
        return String(label || '').split(' ').map(part => {
            if (!part) return '';
            if (part.startsWith('(') || part.startsWith('P-')) return part;
            return part.charAt(0) + '*'.repeat(Math.max(0, part.length - 1));
        }).join(' ');
    }

    function syncMedicalRecordSelectMasking() {
        const shouldMask = typeof window.isDataMasked === 'function' ? window.isDataMasked() : true;
        ['add_patient'].forEach(selectId => {
            const select = document.getElementById(selectId);
            if (!select) return;
            Array.from(select.options).forEach(option => {
                if (!option.value) return;
                if (!option.dataset.realText) {
                    option.dataset.realText = option.textContent;
                    option.dataset.maskedText = maskOptionLabel(option.textContent);
                }
                option.textContent = shouldMask ? option.dataset.maskedText : option.dataset.realText;
            });
        });
    }

    function getRecordFormData(prefix) {
        const selectedAttachments = getAttachmentNames(prefix + '_attachments');
        const existingRecord = editingRecordId ? RECORDS[editingRecordId] : null;

        return {
            patient_id: document.getElementById(prefix + '_patient').value,
            record_type: document.getElementById(prefix + '_type').value,
            title: document.getElementById(prefix + '_title').value.trim(),
            description: document.getElementById(prefix + '_description').value.trim(),
            date: document.getElementById(prefix + '_date').value,
            doctor: document.getElementById(prefix + '_doctor').value,
            status: document.getElementById(prefix + '_status').value,
            shared_with: selectedValues(prefix + '_shared'),
            attachments: selectedAttachments.length ? selectedAttachments : (existingRecord ? (existingRecord.attachments || []) : []),
            created_by: document.getElementById(prefix + '_created_by').value
        };
    }

    function openAddRecordModal() {
        editingRecordId = null;
        document.getElementById('addRecordForm').reset();
        document.querySelector('#addRecordModal h3').textContent = 'Add Medical Record';
        document.querySelector('#addRecordForm button[type="submit"]').innerHTML = '<i class="fa-solid fa-plus mr-1.5"></i> Add Record';
        document.getElementById('add_date').value = new Date().toISOString().slice(0, 10);
        ModalSystem.open('addRecordModal');
        setTimeout(() => {
            if (window.ModalSystem) ModalSystem.refreshMasking('addRecordModal');
            syncMedicalRecordSelectMasking();
        }, 200);
    }

    function reloadAfterToast() {
        setTimeout(() => window.location.reload(), 700);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (PAGE_ERROR) {
            notify('error', 'Could not load medical records: ' + PAGE_ERROR, 'Backend Error');
        }
        syncMedicalRecordSelectMasking();
        if (typeof window.toggleDataMask === 'function' && !window.medicalRecordsMaskBridgeLoaded) {
            const originalToggleDataMask = window.toggleDataMask;
            window.toggleDataMask = function() {
                originalToggleDataMask();
                setTimeout(syncMedicalRecordSelectMasking, 50);
            };
            window.medicalRecordsMaskBridgeLoaded = true;
        }
        document.getElementById('addRecordModal').addEventListener('click', () => {
            if (window.ModalSystem) ModalSystem.refreshMasking('addRecordModal');
            syncMedicalRecordSelectMasking();
        });
    });
    // ============================================================
    // VIEW RECORD
    // ============================================================
    function viewRecord(id) {
        ModalSystem.open('viewRecordModal');
        const r = RECORDS[id];
        if (!r) return;

        setTimeout(() => {
            const typeIcons = {
                consultation: 'fa-stethoscope text-emerald-600',
                lab: 'fa-flask text-violet-600',
                imaging: 'fa-image text-sky-600',
                procedure: 'fa-syringe text-amber-600',
                other: 'fa-file text-slate-400'
            };
            const icon = typeIcons[r.record_type] || typeIcons.other;

            const sharedHtml = r.shared_with && r.shared_with.length > 0 
                ? r.shared_with.map(d => `<span class="px-2 py-1 bg-brand-light/40 rounded-full text-xs text-slate-600 border border-brand-border">${escapeHtml(d)}</span>`).join('')
                : '<span class="text-xs text-slate-400">Not shared with anyone</span>';

            const attachmentsHtml = r.attachments && r.attachments.length > 0
                ? r.attachments.map(a => `
                    <div class="flex items-center gap-2 p-2 bg-slate-50 rounded-lg border border-slate-200">
                        <i class="fa-solid fa-paperclip text-slate-400"></i>
                        <span class="text-xs text-slate-600">${escapeHtml(a)}</span>
                        <button class="ml-auto text-xs text-brand-medium hover:text-brand-dark transition">
                            <i class="fa-solid fa-download"></i>
                        </button>
                    </div>
                `).join('')
                : '<span class="text-xs text-slate-400">No attachments</span>';

            document.getElementById('recordDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-lg flex-shrink-0">
                            ${escapeHtml(r.patient_avatar)}
                        </div>
                        <div>
                            <h4 ${maskedText(r.patient_name, 'P********')} class="maskable text-lg font-bold text-slate-900">${escapeHtml(r.patient_name)}</h4>
                            <p class="text-sm text-slate-500">${escapeHtml(r.patient_code || ('Patient #' + r.patient_id))} - ${escapeHtml(r.title)}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${r.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                                ${escapeHtml(r.status.toUpperCase())}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Patient ID</p><p class="text-sm text-slate-800">${escapeHtml(r.patient_code || ('Patient #' + r.patient_id))}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Type</p><p class="text-sm text-slate-800 capitalize">${escapeHtml(r.record_type)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Date</p><p class="text-sm text-slate-800">${new Date(r.date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Doctor</p><p class="text-sm text-slate-800">${escapeHtml(r.doctor)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Status</p><p class="text-sm text-slate-800 capitalize">${escapeHtml(r.status)}</p></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Description</h5>
                        <p ${maskedText(r.description, 'Protected clinical note')} class="maskable text-sm text-slate-800">${escapeHtml(r.description)}</p>
                    </div>
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">
                            <i class="fa-solid fa-share-nodes mr-1"></i> Shared With
                        </h5>
                        <div class="flex flex-wrap gap-2">${sharedHtml}</div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">
                            <i class="fa-solid fa-paperclip mr-1"></i> Attachments
                        </h5>
                        <div class="space-y-2">${attachmentsHtml}</div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="ModalSystem.close('viewRecordModal')" 
                                class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                            Close
                        </button>
                        <button onclick="ModalSystem.close('viewRecordModal'); editRecord(${r.id})"
                                class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                            <i class="fa-solid fa-pen mr-1.5"></i> Edit Record
                        </button>
                    </div>
                </div>
            `;
            if (window.ModalSystem) ModalSystem.refreshMasking('viewRecordModal');
        }, 300);
    }

    // ============================================================
    // EDIT RECORD
    // ============================================================
    function editRecord(id) {
        const r = RECORDS[id];
        if (!r) return;

        editingRecordId = id;
        document.querySelector('#addRecordModal h3').textContent = 'Edit Medical Record';
        document.querySelector('#addRecordForm button[type="submit"]').innerHTML = '<i class="fa-solid fa-floppy-disk mr-1.5"></i> Save Changes';
        document.getElementById('add_patient').value = r.patient_id;
        document.getElementById('add_type').value = r.record_type;
        document.getElementById('add_title').value = r.title;
        document.getElementById('add_description').value = r.description;
        document.getElementById('add_date').value = r.date;
        document.getElementById('add_doctor').value = r.doctor || '';
        document.getElementById('add_status').value = r.status || 'completed';
        setSelectedValues('add_shared', r.shared_with || []);
        document.getElementById('add_created_by').value = r.created_by || '';
        ModalSystem.open('addRecordModal');
        setTimeout(() => {
            if (window.ModalSystem) ModalSystem.refreshMasking('addRecordModal');
            syncMedicalRecordSelectMasking();
        }, 200);
    }

    // ============================================================
    // SHARE RECORD
    // ============================================================
    let shareRecordId = null;

    function shareRecord(id) {
        shareRecordId = id;
        const r = RECORDS[id];
        if (!r) return;

        const sharePatientName = document.getElementById('sharePatientName');
        sharePatientName.textContent = r.patient_name;
        sharePatientName.dataset.real = r.patient_name;
        sharePatientName.dataset.masked = 'P********';
        sharePatientName.classList.add('maskable');
        document.getElementById('shareRecordTitle').textContent = r.title;
        document.getElementById('shareWithInput').value = '';

        // Load existing shared doctors
        const list = document.getElementById('shareDoctorsList');
        if (r.shared_with && r.shared_with.length > 0) {
            list.innerHTML = r.shared_with.map(d => `
                <span data-doctor="${escapeHtml(d)}" class="px-2 py-1 bg-brand-light/40 rounded-full text-xs text-slate-600 border border-brand-border">
                    <span>${escapeHtml(d)}</span>
                    <button onclick="this.parentElement.remove()" class="ml-1 text-slate-400 hover:text-rose-500">x</button>
                </span>
            `).join('');
        } else {
            list.innerHTML = '<span class="text-xs text-slate-400">No doctors added yet</span>';
        }

        ModalSystem.open('shareRecordModal');
        setTimeout(() => {
            if (window.ModalSystem) ModalSystem.refreshMasking('shareRecordModal');
            syncMedicalRecordSelectMasking();
        }, 200);
    }

    function addShareDoctor() {
        const input = document.getElementById('shareWithInput');
        const name = input.value;
        if (!name) return;

        const list = document.getElementById('shareDoctorsList');
        if (Array.from(list.querySelectorAll('[data-doctor]')).some(item => item.dataset.doctor === name)) {
            notify('warning', 'Doctor already added');
            return;
        }
        if (list.textContent.includes('No doctors added yet')) {
            list.innerHTML = '';
        }

        const span = document.createElement('span');
        span.className = 'px-2 py-1 bg-brand-light/40 rounded-full text-xs text-slate-600 border border-brand-border';
        span.dataset.doctor = name;
        span.innerHTML = `<span>${escapeHtml(name)}</span> <button onclick="this.parentElement.remove()" class="ml-1 text-slate-400 hover:text-rose-500">x</button>`;
        list.appendChild(span);
        input.value = '';
        if (window.ModalSystem) ModalSystem.refreshMasking('shareRecordModal');
    }

    function removeShareDoctor(name) {
        const list = document.getElementById('shareDoctorsList');
        const items = list.querySelectorAll('span');
        items.forEach(item => {
            if (item.dataset.doctor === name || item.textContent.includes(name)) {
                item.remove();
            }
        });
    }

    function confirmShareRecord() {
        const list = document.getElementById('shareDoctorsList');
        const doctors = [];
        list.querySelectorAll('[data-doctor]').forEach(item => {
            const text = item.dataset.doctor || item.textContent.replace('x', '').trim();
            if (text && !text.includes('No doctors')) {
                doctors.push(text);
            }
        });

        if (doctors.length === 0) {
            notify('warning', 'Please add at least one doctor to share with');
            return;
        }

        const r = RECORDS[shareRecordId];
        if (r) {
            sendRecordRequest(API_URL + '?action=update&id=' + shareRecordId, {
                patient_id: r.patient_id,
                record_type: r.record_type,
                title: r.title,
                description: r.description,
                date: r.date,
                doctor: r.doctor,
                status: r.status,
                shared_with: doctors,
                attachments: r.attachments || [],
                created_by: r.created_by
            }).then(() => {
                notify('success', 'Record shared with ' + doctors.length + ' doctor(s)');
                ModalSystem.close('shareRecordModal');
                reloadAfterToast();
            }).catch(error => notify('error', error.message, 'Share Failed'));
        }
    }

    // ============================================================
    // ADD RECORD
    // ============================================================
    async function saveNewRecord(event) {
        event.preventDefault();
        const data = getRecordFormData('add');
        const url = editingRecordId ? API_URL + '?action=update&id=' + editingRecordId : API_URL;

        try {
            await sendRecordRequest(url, data);
            notify('success', editingRecordId ? 'Medical record updated successfully' : 'Medical record added successfully');
            ModalSystem.close('addRecordModal');
            reloadAfterToast();
        } catch (error) {
            notify('error', error.message, editingRecordId ? 'Update Failed' : 'Save Failed');
        }
    }

    async function deleteRecord(id) {
        const r = RECORDS[id];
        if (!r || !confirm('Delete this medical record?')) return;

        try {
            await sendRecordRequest(API_URL + '?action=delete&id=' + id, {});
            notify('success', 'Medical record deleted successfully');
            reloadAfterToast();
        } catch (error) {
            notify('error', error.message, 'Delete Failed');
        }
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchRecord').addEventListener('input', filterRecords);
    document.getElementById('filterTitle').addEventListener('change', filterRecords);
    document.getElementById('filterType').addEventListener('change', filterRecords);
    document.getElementById('filterStatus').addEventListener('change', filterRecords);

    function filterRecords() {
        const search = document.getElementById('searchRecord').value.toLowerCase();
        const title = document.getElementById('filterTitle').value.toLowerCase();
        const type = document.getElementById('filterType').value.toLowerCase();
        const status = document.getElementById('filterStatus').value.toLowerCase();
        let visibleCount = 0;

        document.querySelectorAll('.record-card').forEach(card => {
            const cardPatient = card.dataset.patient;
            const cardPatientId = card.dataset.patientId;
            const cardTitle = card.dataset.title;
            const cardType = card.dataset.type;
            const cardStatus = card.dataset.status;

            const matchesSearch = cardPatient.includes(search) || cardPatientId.includes(search) || cardTitle.includes(search) || cardType.includes(search);
            const matchesTitle = !title || cardTitle === title;
            const matchesType = !type || cardType === type;
            const matchesStatus = !status || cardStatus === status;
            const isVisible = matchesSearch && matchesTitle && matchesType && matchesStatus;

            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchRecord').value = '';
        document.getElementById('filterTitle').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterStatus').value = '';
        document.querySelectorAll('.record-card').forEach(card => card.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo $totalPages; ?>) return;
        window.location.href = '?page=' + page;
    }
</script>

<?php include_once '../../includes/footer.php'; ?>
