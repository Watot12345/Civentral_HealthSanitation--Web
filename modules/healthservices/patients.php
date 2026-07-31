<?php
// ============================================================
// COLOR PALETTE USED ON THIS PAGE
// ============================================================
// This page reuses the same brand-* Tailwind classes as your
// dashboard (modules/index.php). If brand-dark / brand-medium /
// brand-light / brand-border are already defined in
// tailwind.config.js, this page will automatically match.
//
// If they are NOT yet defined, add this to tailwind.config.js
// theme.extend.colors (deep teal — fits a health/sanitation
// department and stays distinct from the generic blue/green
// admin-panel look):
//
//   'brand-dark':   '#0B4F4A',
//   'brand-medium': '#14807A',
//   'brand-light':  '#E6F5F3',
//   'brand-border': '#B8E0DC',
//
// Swap these four values only — every class below (bg-brand-dark,
// text-brand-medium, etc.) will pick up the change automatically.
// ============================================================

// ============================================================
// 1. PHP BACKEND - Fetch Data
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('health center services');


// Load Data from Database
require_once __DIR__ . '/../../app/Models/Patient.php';
$patientModel = new Patient();
$dbPatients = $patientModel->all(['order' => 'created_at.desc']);

$patients = [];
foreach ($dbPatients as $p) {
    // Map db structure to the structure expected by the HTML view
    $age = 0;
    if (!empty($p['birth_date'])) {
        $dob = new DateTime($p['birth_date']);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
    }
    
    $conditions = 'None';
    if (!empty($p['medical_history'])) {
        $history = is_string($p['medical_history']) 
            ? json_decode($p['medical_history'], true) 
            : $p['medical_history'];
        $conditions = $history['conditions'] ?? 'None';
    }

    $patients[] = [
        'id' => $p['id'] ?? '',
        'patient_id' => $p['patient_id'] ?? '',
        'first_name' => $p['first_name'] ?? '',
        'last_name' => $p['last_name'] ?? '',
        'middle_name' => $p['middle_name'] ?? '',
        'gender' => $p['gender'] ?? '',
        'birth_date' => $p['birth_date'] ?? '',
        'age' => $age,
        'blood_type' => $p['blood_type'] ?? '',
        'contact' => $p['contact'] ?? '',
        'email' => $p['email'] ?? '',
        'address' => $p['address'] ?? '',
        'barangay' => $p['barangay'] ?? '',
        'emergency_contact' => $p['emergency_contact'] ?? '',
        'registration_date' => $p['registration_date'] ?? '',
        'status' => $p['status'] ?? 'active',
        'last_visit' => !empty($p['updated_at']) ? substr($p['updated_at'], 0, 10) : date('Y-m-d'),
        'allergies' => $p['allergies'] ?? 'None',
        'conditions' => $conditions
    ];
}

// Pagination
$targetPatientId = $_GET['patient'] ?? $_GET['id'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;

if ($targetPatientId && !isset($_GET['page'])) {
    foreach ($patients as $idx => $p) {
        if ((string)($p['id'] ?? '') === (string)$targetPatientId || (string)($p['patient_id'] ?? '') === (string)$targetPatientId) {
            $page = (int)floor($idx / $limit) + 1;
            break;
        }
    }
}

$offset = ($page - 1) * $limit;
$totalPatients = count($patients);
$totalPages = ceil($totalPatients / $limit);
if ($totalPages < 1) $totalPages = 1;
$paginatedPatients = array_slice($patients, $offset, $limit);

$title = 'Patient Management';

?>
<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Patient Management</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage all patient records and information</p>
        </div>
        <div class="flex gap-3">
            <div class="flex rounded-lg border border-slate-200 overflow-hidden">
                <button onclick="ModalSystem.open('importModal')"
                        class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2 border-r border-slate-200">
                    <i class="fa-solid fa-file-import text-xs"></i> Import
                </button>
                <button onclick="ModalSystem.open('exportModal'); prepExportModal();"
                        class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-file-export text-xs"></i> Export
                </button>
            </div>
            <button onclick="ModalSystem.open('addPatientModal'); prepAddPatientModal();"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Add Patient
            </button>
        </div>
    </div>

    <?php
    $criticalConditions = ['Heart Disease'];
    $criticalCount = count(array_filter($patients, fn($p) => in_array($p['conditions'], $criticalConditions)));
    $todaysAppointments = 3;
    $pendingLabResults  = 2;
    $followUpsDue       = 4;
    $ageGroups = ['0-17' => 0, '18-35' => 0, '36-50' => 0, '51-65' => 0, '66+' => 0];
    foreach ($patients as $p) {
        $a = $p['age'];
        if ($a <= 17) $ageGroups['0-17']++;
        elseif ($a <= 35) $ageGroups['18-35']++;
        elseif ($a <= 50) $ageGroups['36-50']++;
        elseif ($a <= 65) $ageGroups['51-65']++;
        else $ageGroups['66+']++;
    }
    $barangayCounts = [];
    foreach ($patients as $p) {
        $barangayCounts[$p['barangay']] = ($barangayCounts[$p['barangay']] ?? 0) + 1;
    }
    arsort($barangayCounts);
    ?>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative"><div class="flex items-center gap-3"><div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200"><i class="fa-solid fa-users text-lg"></i></div><div><p class="text-2xl font-black text-slate-900" id="statTotal"><?php echo $totalPatients; ?></p><p class="text-xs font-medium text-slate-500">Total Patients</p></div></div><div class="mt-3 flex items-center gap-2"><span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">👥 All patients</span><span class="text-[10px] text-slate-400"><?php echo count(array_filter($patients, fn($p) => $p['status'] === 'active')); ?> active</span></div></div>
        </div>
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative"><div class="flex items-center gap-3"><div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200"><i class="fa-solid fa-user-check text-lg"></i></div><div><p class="text-2xl font-black text-emerald-600" id="statActive"><?php echo count(array_filter($patients, fn($p) => $p['status'] === 'active')); ?></p><p class="text-xs font-medium text-slate-500">Active</p></div></div><div class="mt-3 flex items-center gap-2"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Verified</span><span class="text-[10px] text-slate-400">Currently active</span></div></div>
        </div>
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-sky-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative"><div class="flex items-center gap-3"><div class="w-11 h-11 bg-gradient-to-br from-sky-500 to-sky-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-sky-200"><i class="fa-solid fa-calendar-check text-lg"></i></div><div><p class="text-2xl font-black text-sky-600"><?php echo $todaysAppointments; ?></p><p class="text-xs font-medium text-slate-500">Today's Appointments</p></div></div><div class="mt-3 flex items-center gap-2"><span class="px-2 py-0.5 bg-sky-100 text-sky-700 rounded-full text-[10px] font-bold">📅 Today</span><span class="text-[10px] text-slate-400"><?php echo date('F d, Y'); ?></span></div></div>
        </div>
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative"><div class="flex items-center gap-3"><div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200"><i class="fa-solid fa-heart-pulse text-lg"></i></div><div><p class="text-2xl font-black text-rose-600"><?php echo $criticalCount; ?></p><p class="text-xs font-medium text-slate-500">Critical Patients</p></div></div><div class="mt-3 flex items-center gap-2"><span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span><span class="text-[10px] text-slate-400">Needs attention</span></div></div>
        </div>
    </div>

    <!-- Distribution Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-xs p-5 border border-slate-200"><div class="flex justify-between items-center mb-4"><h3 class="text-sm font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-chart-line text-brand-medium"></i> Age Group Distribution</h3></div><div class="h-52"><canvas id="ageLineChart"></canvas></div></div>
        <div class="bg-white rounded-xl shadow-xs p-5 border border-slate-200"><div class="flex justify-between items-center mb-4"><h3 class="text-sm font-bold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-chart-pie text-brand-medium"></i> Barangay Distribution</h3></div><div class="h-52"><canvas id="barangayPieChart"></canvas></div></div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative"><i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i><input type="text" id="searchPatient" placeholder="Search by name, ID, or barangay..." class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition"></div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white"><option value="">All Status</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
                <select id="filterDateType" onchange="onDateFilterTypeChange()" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white"><option value="">Last Visit: Any time</option><option value="today">Today</option><option value="day">Specific day</option><option value="month">Specific month</option><option value="year">Specific year</option></select>
                <input type="date" id="filterDateValue" class="hidden px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters" class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm"><i class="fa-solid fa-rotate-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Patient Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient ID</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Name</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Gender</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Age</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Blood Type</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Barangay</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Last Visit</th><th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th></tr></thead>
                <tbody id="patientTableBody">
                   <?php foreach ($paginatedPatients as $patient): ?>
<tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors patient-row" 
    data-row-id="<?php echo $patient['id']; ?>" 
    data-name="<?php echo strtolower($patient['first_name'] . ' ' . $patient['last_name']); ?>" 
    data-id="<?php echo $patient['patient_id']; ?>" 
    data-barangay="<?php echo $patient['barangay']; ?>" 
    data-status="<?php echo $patient['status']; ?>" 
    data-last-visit="<?php echo $patient['last_visit']; ?>">
    
    <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold maskable" 
        data-real="<?php echo $patient['patient_id']; ?>"
        data-masked="<?php echo maskId($patient['patient_id']); ?>">
        <?php echo maskId($patient['patient_id']); ?>
    </td>
    
    <td class="px-4 py-3">
        <div class="flex items-center gap-2.5">
            <div class="cell-avatar w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                <?php echo strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1)); ?>
            </div>
            <div>
                <p class="cell-name font-semibold text-slate-800 maskable" 
                   data-real="<?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?>"
                   data-masked="<?php echo maskName($patient['first_name']) . ' ' . maskName($patient['last_name']); ?>">
                    <?php echo maskName($patient['first_name']) . ' ' . maskName($patient['last_name']); ?>
                </p>
                <p class="cell-email text-xs text-slate-400 maskable" 
                   data-real="<?php echo $patient['email']; ?>"
                   data-masked="<?php echo maskName($patient['email']); ?>">
                    <?php echo maskName($patient['email']); ?>
                </p>
            </div>
        </div>
    </td>
    
    <td class="px-4 py-3">
        <span class="cell-gender text-slate-600 text-xs">
            <i class="fa-solid <?php echo $patient['gender'] === 'Male' ? 'fa-mars text-sky-500' : 'fa-venus text-pink-500'; ?>"></i>
            <?php echo $patient['gender']; ?>
        </span>
    </td>
    
    <td class="px-4 py-3 text-slate-600 cell-age"><?php echo $patient['age']; ?></td>
    <td class="px-4 py-3">
        <span class="cell-blood px-2 py-1 bg-rose-50 text-rose-600 rounded text-xs font-semibold"><?php echo $patient['blood_type']; ?></span>
    </td>
    
    <td class="px-4 py-3 text-slate-600 cell-barangay maskable" 
        data-real="<?php echo $patient['barangay']; ?>"
        data-masked="<?php echo maskName($patient['barangay']); ?>">
        <?php echo maskName($patient['barangay']); ?>
    </td>
    
    <td class="px-4 py-3">
        <span class="cell-status px-2 py-1 rounded-full text-xs font-semibold <?php echo $patient['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?>">
            <?php echo ucfirst($patient['status']); ?>
        </span>
    </td>
    
    <td class="px-4 py-3 text-slate-500 text-xs cell-visit"><?php echo date('M d, Y', strtotime($patient['last_visit'])); ?></td>
    
    <td class="px-4 py-3">
        <div class="flex items-center justify-center gap-1">
            <button onclick="viewPatient(<?php echo $patient['id']; ?>)" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View"><i class="fa-solid fa-eye text-sm"></i></button>
            <button onclick="deletePatient(<?php echo $patient['id']; ?>)" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Delete"><i class="fa-solid fa-trash-can text-sm"></i></button>
        </div>
    </td>
</tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center"><div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3"><i class="fa-solid fa-user-slash text-slate-400"></i></div><p class="text-sm font-semibold text-slate-600">No patients match your filters</p><p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p><button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button></div>
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50"><p class="text-xs text-slate-500">Showing <span class="font-semibold text-slate-700"><?php echo $offset + 1; ?></span> to <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalPatients); ?></span> of <span class="font-semibold text-slate-700"><?php echo $totalPatients; ?></span> patients</p><div class="flex gap-1"><button onclick="changePage(<?php echo $page - 1; ?>)" class="px-3 py-1.5 rounded-lg text-sm <?php echo $page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>><i class="fa-solid fa-chevron-left text-xs"></i></button><?php for ($i = 1; $i <= $totalPages; $i++): ?><button onclick="changePage(<?php echo $i; ?>)" class="px-3 py-1.5 rounded-lg text-sm font-medium <?php echo $i === $page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"><?php echo $i; ?></button><?php endfor; ?><button onclick="changePage(<?php echo $page + 1; ?>)" class="px-3 py-1.5 rounded-lg text-sm <?php echo $page >= $totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>><i class="fa-solid fa-chevron-right text-xs"></i></button></div></div>
    </div>
</div>

<!-- VIEW PATIENT MODAL -->
<div id="viewPatientModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl"><h3 class="font-bold text-slate-900">Patient Details</h3><button onclick="ModalSystem.close('viewPatientModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div><div id="patientDetailsContent" class="p-6"><div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading patient record...</div></div></div></div>

<!-- EDIT PATIENT MODAL - FIXED: Removed maskable input-maskable from inputs -->
<div id="editPatientModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl"><h3 class="font-bold text-slate-900">Edit Patient</h3><button onclick="ModalSystem.close('editPatientModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<form id="editPatientForm" class="p-6 space-y-5"><input type="hidden" id="edit_id">
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">First Name</label><input type="text" id="edit_first_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Name</label><input type="text" id="edit_last_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label><input type="email" id="edit_email" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact Number</label><input type="text" id="edit_contact" required minlength="12" maxlength="12" pattern="[0-9]{12}" inputmode="numeric" placeholder="639XXXXXXXXX" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label><select id="edit_gender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="Male">Male</option><option value="Female">Female</option></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Age</label><input type="number" id="edit_age" min="0" max="120" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Blood Type</label><select id="edit_blood_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><?php foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $bt): ?><option value="<?php echo $bt; ?>"><?php echo $bt; ?></option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
<div class="sm:col-span-2"><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label><select id="edit_barangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="Barangay San Jose">Barangay San Jose</option><option value="Barangay Poblacion">Barangay Poblacion</option><option value="Barangay Riverside">Barangay Riverside</option><option value="Barangay San Roque">Barangay San Roque</option><option value="Barangay Sta. Cruz">Barangay Sta. Cruz</option></select></div>
<div class="sm:col-span-2"><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label><input type="text" id="edit_address" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Allergies</label><input type="text" id="edit_allergies" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Conditions</label><input type="text" id="edit_conditions" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
</div>
<div class="flex justify-end gap-2 pt-2 border-t border-slate-100"><button type="button" onclick="ModalSystem.close('editPatientModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Save Changes</button></div>
</form></div></div>

<!-- DELETE CONFIRMATION MODAL -->
<div id="deletePatientModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-sm"><div class="p-6 text-center"><div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center mx-auto mb-4"><i class="fa-solid fa-trash-can text-rose-500"></i></div><h3 class="font-bold text-slate-900 mb-1">Delete this patient?</h3><p class="text-sm text-slate-500" id="deletePatientName">This action cannot be undone.</p></div><div class="flex gap-2 px-6 pb-6"><button type="button" onclick="ModalSystem.close('deletePatientModal')" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="button" onclick="confirmDeletePatient()" class="flex-1 px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition text-sm font-semibold"><i class="fa-solid fa-trash-can mr-1.5"></i> Delete</button></div></div></div>

<!-- ADD PATIENT MODAL -->
<div id="addPatientModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl"><div><h3 class="font-bold text-slate-900">Add New Patient</h3><p class="text-xs text-slate-400 mt-0.5">Next ID: <span id="nextPatientIdPreview" class="font-mono font-semibold text-brand-dark"></span></p></div><button onclick="ModalSystem.close('addPatientModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<form id="addPatientForm" class="p-6 space-y-5">
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">First Name</label><input type="text" id="add_first_name" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Name</label><input type="text" id="add_last_name" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label><input type="email" id="add_email" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact Number</label><input type="text" id="add_contact" required minlength="12" maxlength="12" pattern="[0-9]{12}" inputmode="numeric" placeholder="639XXXXXXXXX" class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label><select id="add_gender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="Male">Male</option><option value="Female">Female</option></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Age</label><input type="number" id="add_age" min="0" max="120" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Blood Type</label><select id="add_blood_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><?php foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $bt): ?><option value="<?php echo $bt; ?>"><?php echo $bt; ?></option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="add_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
<div class="sm:col-span-2"><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label><select id="add_barangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="Barangay San Jose">Barangay San Jose</option><option value="Barangay Poblacion">Barangay Poblacion</option><option value="Barangay Riverside">Barangay Riverside</option><option value="Barangay San Roque">Barangay San Roque</option><option value="Barangay Sta. Cruz">Barangay Sta. Cruz</option></select></div>
<div class="sm:col-span-2"><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label><input type="text" id="add_address" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div class="sm:col-span-2"><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Emergency Contact</label><input type="text" id="add_emergency_contact" placeholder="Name - Phone Number" class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Allergies</label><input type="text" id="add_allergies" placeholder="None" class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Conditions</label><input type="text" id="add_conditions" placeholder="None" class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
</div>
<div class="flex justify-end gap-2 pt-2 border-t border-slate-100"><button type="button" onclick="ModalSystem.close('addPatientModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-user-plus mr-1.5"></i> Add Patient</button></div>
</form></div></div>

<!-- IMPORT MODAL -->
<div id="importModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-lg"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200"><h3 class="font-bold text-slate-900">Import Patients</h3><button onclick="ModalSystem.close('importModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div><div class="p-6 space-y-4"><div id="importDropzone" class="border-2 border-dashed border-slate-200 rounded-xl p-8 text-center cursor-pointer hover:border-brand-medium hover:bg-brand-light/30 transition" onclick="document.getElementById('importFileInput').click()"><input type="file" id="importFileInput" accept=".csv" class="hidden" onchange="handleImportFile(this.files[0])"><div class="w-12 h-12 rounded-full bg-brand-light border border-brand-border flex items-center justify-center mx-auto mb-3"><i class="fa-solid fa-cloud-arrow-up text-brand-dark text-lg"></i></div><p class="text-sm font-semibold text-slate-700">Drag & drop your CSV file here</p><p class="text-xs text-slate-400 mt-1">or click to browse — .csv only, max 5MB</p></div><div id="importFileInfo" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-center justify-between"><div class="flex items-center gap-3 min-w-0"><div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0"><i class="fa-solid fa-file-csv text-emerald-600"></i></div><div class="min-w-0"><p id="importFileName" class="text-sm font-semibold text-slate-800 truncate"></p><p id="importFileSummary" class="text-xs text-slate-400"></p></div></div><button onclick="clearImportFile()" class="w-7 h-7 rounded-lg hover:bg-slate-200 flex items-center justify-center text-slate-400 hover:text-slate-600 transition flex-shrink-0" title="Remove file"><i class="fa-solid fa-xmark text-sm"></i></button></div><div id="importError" class="hidden bg-rose-50 border border-rose-100 text-rose-600 text-xs rounded-lg p-3"></div><details class="text-xs text-slate-500"><summary class="cursor-pointer font-semibold text-brand-medium hover:text-brand-dark select-none">Expected column format</summary><p class="mt-2 leading-relaxed">first_name, last_name, email, contact, gender, age, blood_type, barangay, address, status</p></details></div><div class="flex justify-end gap-2 px-6 pb-6"><button type="button" onclick="ModalSystem.close('importModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="button" id="importConfirmBtn" onclick="confirmImport()" disabled class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-brand-dark"><i class="fa-solid fa-file-import mr-1.5"></i> Import Patients</button></div></div></div>

<!-- EXPORT MODAL -->
<div id="exportModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-md"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200"><h3 class="font-bold text-slate-900">Export Patients</h3><button onclick="ModalSystem.close('exportModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div><div class="p-6 space-y-5"><div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Format</p><div class="grid grid-cols-3 gap-2" id="exportFormatGroup"><button type="button" data-format="csv" onclick="selectExportFormat('csv')" class="export-format-btn px-3 py-2.5 rounded-lg border text-xs font-semibold flex flex-col items-center gap-1.5 transition"><i class="fa-solid fa-file-csv text-base"></i> CSV</button><button type="button" data-format="excel" onclick="selectExportFormat('excel')" class="export-format-btn px-3 py-2.5 rounded-lg border text-xs font-semibold flex flex-col items-center gap-1.5 transition"><i class="fa-solid fa-file-excel text-base"></i> Excel</button><button type="button" data-format="pdf" onclick="selectExportFormat('pdf')" class="export-format-btn px-3 py-2.5 rounded-lg border text-xs font-semibold flex flex-col items-center gap-1.5 transition"><i class="fa-solid fa-file-pdf text-base"></i> PDF</button></div></div><div><p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Scope</p><div class="space-y-2"><label class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer has-[:checked]:border-brand-medium has-[:checked]:bg-brand-light/40"><input type="radio" name="exportScope" value="all" checked class="accent-brand-dark"><span class="text-sm text-slate-700">All patients <span class="text-slate-400">(<span id="exportCountAll"></span>)</span></span></label><label class="flex items-center gap-2.5 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer has-[:checked]:border-brand-medium has-[:checked]:bg-brand-light/40"><input type="radio" name="exportScope" value="filtered" class="accent-brand-dark"><span class="text-sm text-slate-700">Current filtered view <span class="text-slate-400">(<span id="exportCountFiltered"></span>)</span></span></label></div></div></div><div class="flex justify-end gap-2 px-6 pb-6"><button type="button" onclick="ModalSystem.close('exportModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="button" onclick="runExport()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-download mr-1.5"></i> Export</button></div></div></div>

<style>
.export-format-btn{border-color:#E2E8F0;color:#64748B}
.export-format-btn.selected{border-color:#14807A;background-color:rgba(20,128,122,0.08);color:#0B4F4A}
/* Patient row highlight animation */
.patient-row-highlight {
    animation: highlightPulse 1.5s ease;
    background-color: #E6F5F3 !important;
    border-left: 4px solid #14807A !important;
    box-shadow: 0 0 20px rgba(20, 128, 122, 0.2);
}

@keyframes highlightPulse {
    0% { background-color: #E6F5F3; transform: scale(1); }
    50% { background-color: #B8E0DC; transform: scale(1.01); }
    100% { background-color: #E6F5F3; transform: scale(1); }
}
/* Patient ID specific: font-mono, text-xs, text-brand-dark, font-semibold */
td .font-mono.maskable.masked::after {
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace !important;
    font-size: 0.75rem !important;
    color: #0B4F4A !important;
    font-weight: 600 !important;
}

/* Name: font-semibold, text-slate-800 */
.cell-name.maskable.masked::after {
    font-weight: 600 !important;
    color: #1e293b !important;
    font-size: 0.875rem !important;
}

/* Email: text-xs, text-slate-400 */
.cell-email.maskable.masked::after {
    font-size: 0.75rem !important;
    color: #94a3b8 !important;
}

/* Barangay: text-slate-600 */
td .text-slate-600.maskable.masked::after {
    color: #475569 !important;
}

/* Input fields - normal display */
.maskable.input-maskable {
    color: #1e293b !important;
    background: white !important;
}
</style>

<!-- ============================================================ -->
<!-- 5. JAVASCRIPT                                                -->
<!-- ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    // ============================================================
    // CHARTS
    // ============================================================
    (function () {
    'use strict';
    const BRAND = { dark: '#0B4F4A', medium: '#14807A', light: '#E6F5F3', border: '#B8E0DC' };
    const PALETTE = ['#0B4F4A','#14807A','#2EB8A0','#5CCFBB','#8FE3D6','#B8E0DC','#D4A853','#E07B54'];
    const ageLabels = <?php echo json_encode(array_keys($ageGroups)); ?>;
    const ageValues = <?php echo json_encode(array_values($ageGroups)); ?>;
    const barangayRaw = <?php echo json_encode($barangayCounts); ?>;
    const bLabels = Object.keys(barangayRaw).map(b => b.replace('Barangay ', ''));
    const bValues = Object.values(barangayRaw);
    Chart.defaults.font.family = "'Inter', 'Segoe UI', system-ui, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#64748B';
    const TOOLTIP_STYLE = { backgroundColor:'#1E293B', titleFont:{weight:'600',size:12}, bodyFont:{size:11}, padding:10, cornerRadius:8, displayColors:true, boxPadding:4 };
    const ageLineCtx = document.getElementById('ageLineChart').getContext('2d');
    new Chart(ageLineCtx, { type:'line', data:{ labels:ageLabels, datasets:[{ label:'Patients', data:ageValues, borderColor:BRAND.medium, backgroundColor:BRAND.medium+'20', borderWidth:3, pointBackgroundColor:BRAND.dark, pointBorderColor:'#FFFFFF', pointBorderWidth:2, pointRadius:5, pointHoverRadius:7, fill:true, tension:0.4 }] }, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{...TOOLTIP_STYLE} }, scales:{ x:{ grid:{display:false}, border:{display:false} }, y:{ beginAtZero:true, grid:{color:'#F1F5F9'}, border:{display:false} } } } });
    const bPieCtx = document.getElementById('barangayPieChart').getContext('2d');
    new Chart(bPieCtx, { type:'doughnut', data:{ labels:bLabels, datasets:[{ data:bValues, backgroundColor:PALETTE.slice(0,bLabels.length), borderWidth:2, borderColor:'#FFFFFF', hoverOffset:8 }] }, options:{ responsive:true, maintainAspectRatio:false, cutout:'52%', plugins:{ legend:{ position:'right', labels:{ usePointStyle:true, padding:14, font:{size:11} } }, tooltip:{...TOOLTIP_STYLE} } } });
    })();

    // ============================================================
    // DATA & API
    // ============================================================
    const API_BASE = '/capstone/api';
    const PATIENTS = <?php echo json_encode(array_column($patients, null, 'id'), JSON_PRETTY_PRINT); ?>;
    let pendingDeleteId = null;
    let selectedExportFormat = 'csv';

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

    function formatDate(dateStr) { 
        return new Date(dateStr).toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' }); 
    }

    // ============================================================
    // VIEW PATIENT
    // ============================================================
    function viewPatient(id) {
        ModalSystem.open('viewPatientModal');
        const content = document.getElementById('patientDetailsContent');
        content.innerHTML = '<div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...</div>';
        setTimeout(() => {
            const p = PATIENTS[id];
            if (!p) { content.innerHTML = '<p class="text-sm text-rose-500 text-center py-10">Patient not found.</p>'; return; }
            const initials = (p.first_name[0] + p.last_name[0]).toUpperCase();
            const statusBadge = p.status === 'active' ? '<span class="inline-block px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold mt-1">Active</span>' : '<span class="inline-block px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs font-semibold mt-1">Inactive</span>';
            content.innerHTML = `
                <div class="space-y-6">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200"><div class="w-16 h-16 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">${initials}</div><div><h4 class="text-lg font-bold text-slate-900 maskable" data-real="${p.first_name} ${p.last_name}" data-masked="${maskPatientName(p.first_name)} ${maskPatientName(p.last_name)}">${maskPatientName(p.first_name)} ${maskPatientName(p.last_name)}</h4><p class="text-sm text-slate-500 maskable" data-real="${p.patient_id}" data-masked="${maskPatientCode(p.patient_id)}">${maskPatientCode(p.patient_id)} &bull; ${p.gender} &bull; ${p.age} years old</p>${statusBadge}</div></div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Contact</p><p class="text-sm text-slate-800 maskable" data-real="${p.contact}" data-masked="${maskPatientName(p.contact)}">${maskPatientName(p.contact)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Email</p><p class="text-sm text-slate-800 maskable" data-real="${p.email}" data-masked="${maskPatientName(p.email)}">${maskPatientName(p.email)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Blood Type</p><p class="text-sm text-slate-800 font-semibold text-rose-600">${p.blood_type}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Barangay</p><p class="text-sm text-slate-800 maskable" data-real="${p.barangay}" data-masked="${maskPatientName(p.barangay)}">${maskPatientName(p.barangay)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Address</p><p class="text-sm text-slate-800 maskable" data-real="${p.address}" data-masked="${maskPatientName(p.address)}">${maskPatientName(p.address)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Emergency Contact</p><p class="text-sm text-slate-800 maskable" data-real="${p.emergency_contact}" data-masked="${maskPatientName(p.emergency_contact)}">${maskPatientName(p.emergency_contact)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Registration Date</p><p class="text-sm text-slate-800">${formatDate(p.registration_date)}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold uppercase">Last Visit</p><p class="text-sm text-slate-800">${formatDate(p.last_visit)}</p></div>
                    </div>
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">Medical Information</h5><div class="grid grid-cols-1 md:grid-cols-2 gap-3"><div><p class="text-xs text-slate-400 font-semibold uppercase">Allergies</p><p class="text-sm text-slate-800">${p.allergies}</p></div><div><p class="text-xs text-slate-400 font-semibold uppercase">Conditions</p><p class="text-sm text-slate-800">${p.conditions}</p></div></div></div>
                    <div class="flex justify-end gap-2 pt-2"><button onclick="ModalSystem.close('viewPatientModal'); editPatient(${p.id})" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-pen mr-1.5"></i> Edit Patient</button></div>
                </div>`;
            setTimeout(() => { if (typeof ModalSystem !== 'undefined' && ModalSystem.refreshMasking) ModalSystem.refreshMasking('viewPatientModal'); }, 100);
        }, 400);
    }

    // ============================================================
    // EDIT PATIENT - FIXED: Properly handles masking and real values
    // ============================================================
    function editPatient(id) {
        const p = PATIENTS[id];
        if (!p) {
            ModalSystem.toast.error('Patient data not found');
            return;
        }
        
        document.getElementById('edit_id').value = p.id;
        
        // Check if masking is currently enabled
        const isMasked = localStorage.getItem('data_masking_enabled');
        const shouldMask = isMasked === null ? true : isMasked === 'true';
        
        // DEBUG: Log the patient data
        console.log('📝 Editing patient:', p);
        
        const fields = {
            'edit_first_name': p.first_name || '',
            'edit_last_name': p.last_name || '',
            'edit_email': p.email || '',
            'edit_contact': p.contact || '',
            'edit_age': String(p.age || ''),
            'edit_address': p.address || '',
            'edit_allergies': p.allergies || 'None',
            'edit_conditions': p.conditions || 'None'
        };
        
        for (const [fid, value] of Object.entries(fields)) {
            const input = document.getElementById(fid);
            if (input) {
                const strValue = String(value || '');
                // Store both real and masked versions
                input.dataset.real = strValue;
                input.dataset.masked = maskPatientName(strValue);
                
                // Show masked or real based on current state
                if (shouldMask) {
                    input.value = maskPatientName(strValue);
                } else {
                    input.value = strValue;
                }
                
                // DEBUG: Log what's being set
                console.log(`📝 Set ${fid}: real="${strValue}", masked="${maskPatientName(strValue)}", value="${input.value}"`);
            }
        }
        
        // Set select fields
        const genderSelect = document.getElementById('edit_gender');
        if (genderSelect) genderSelect.value = p.gender || '';
        
        const bloodTypeSelect = document.getElementById('edit_blood_type');
        if (bloodTypeSelect) bloodTypeSelect.value = p.blood_type || '';
        
        const statusSelect = document.getElementById('edit_status');
        if (statusSelect) statusSelect.value = p.status || 'active';
        
        const barangaySelect = document.getElementById('edit_barangay');
        if (barangaySelect) barangaySelect.value = p.barangay || '';

        ModalSystem.open('editPatientModal', { applyMasking: false });
    }

    // ============================================================
    // GET REAL VALUE - FIXED: Always returns real value
    // ============================================================
    function getRealVal(id) {
        const el = document.getElementById(id);
        if (!el) {
            console.warn('⚠️ Element not found:', id);
            return '';
        }
        
        // DEBUG: Log what's being retrieved
        console.log(`🔍 getRealVal("${id}"):`, {
            datasetReal: el.dataset.real,
            value: el.value,
            datasetMasked: el.dataset.masked
        });
        
        // PRIORITY 1: Use dataset.real if available
        if (el.dataset.real && el.dataset.real.trim() !== '') {
            return el.dataset.real.trim();
        }
        
        // PRIORITY 2: If dataset.real is empty but dataset.masked matches value, 
        // we need to find the real value from somewhere else
        if (el.dataset.masked && el.value === el.dataset.masked) {
            // The input is showing masked value, but we need real
            // Try to find the patient and get real value
            console.warn(`⚠️ ${id} is showing masked value but dataset.real is empty`);
            return el.value; // Fallback to value
        }
        
        // PRIORITY 3: Return value as-is
        return el.value.trim();
    }

    // ============================================================
    // SAVE NEW PATIENT
    // ============================================================
   async function saveNewPatient(event) {
    event.preventDefault();
    
    // Get values directly from inputs - don't use getRealVal for add form
    const firstName = document.getElementById('add_first_name')?.value || '';
    const lastName = document.getElementById('add_last_name')?.value || '';
    const email = document.getElementById('add_email')?.value || '';
    const contact = document.getElementById('add_contact')?.value || '';
    const gender = document.getElementById('add_gender')?.value || '';
    const age = parseInt(document.getElementById('add_age')?.value) || 0;
    const bloodType = document.getElementById('add_blood_type')?.value || '';
    const status = document.getElementById('add_status')?.value || 'active';
    const barangay = document.getElementById('add_barangay')?.value || '';
    const address = document.getElementById('add_address')?.value || '';
    const emergencyContact = document.getElementById('add_emergency_contact')?.value || 'None';
    const allergies = document.getElementById('add_allergies')?.value || 'None';
    const conditions = document.getElementById('add_conditions')?.value || 'None';
    
    // Validate required fields
    if (!firstName || !lastName || !contact) {
        ModalSystem.toast.error('Please fill in all required fields (First Name, Last Name, Contact)');
        return;
    }

    if (!/^\d{12}$/.test(contact)) {
        ModalSystem.toast.error('Contact number must contain exactly 12 digits (example: 639171234567)');
        return;
    }
    
    console.log('📝 Add Patient Data:', {
        firstName,
        lastName,
        email,
        contact,
        gender,
        age,
        bloodType,
        status,
        barangay,
        address,
        emergencyContact,
        allergies,
        conditions
    });
    
    const submitBtn = document.querySelector('#addPatientForm button[type="submit"]');
    const orig = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
    
    const payload = {
        first_name: firstName,
        last_name: lastName,
        email: email,
        contact: contact,
        gender: gender,
        age: age,
        blood_type: bloodType,
        status: status,
        barangay: barangay,
        address: address,
        emergency_contact: emergencyContact || 'None',
        allergies: allergies || 'None',
        conditions: conditions || 'None'
    };
    
    console.log('📤 Sending payload:', payload);
    
    try {
        const res = await fetch(API_BASE + '/patients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        const text = await res.text();
        console.log('📥 Response text:', text);
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('❌ Failed to parse JSON:', e);
            ModalSystem.toast.error('Server returned invalid response. Check console for details.');
            return;
        }
        
        if (res.ok && data.success) {
            ModalSystem.toast.success('Patient added successfully!');
            ModalSystem.close('addPatientModal');
            // Reset the form
            document.getElementById('addPatientForm').reset();
            // Reload after a moment to show the new patient
            setTimeout(() => window.location.reload(), 1000);
        } else {
            const errorMsg = data.message || data.error || 'Failed to add patient';
            console.error('❌ Server error:', errorMsg);
            ModalSystem.toast.error(errorMsg);
        }
    } catch (err) {
        console.error('❌ Network error:', err);
        ModalSystem.toast.error('Network error: ' + err.message);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = orig;
    }
}

    // ============================================================
    // SAVE EDITED PATIENT - FIXED: Properly handles birth_date and real values
    // ============================================================
    async function saveEditedPatient(event) {
        event.preventDefault();
        const id = document.getElementById('edit_id').value;
        const p = PATIENTS[id];
        
        if (!p) {
            ModalSystem.toast.error('Patient data not found');
            return;
        }
        
        const submitBtn = document.querySelector('#editPatientForm button[type="submit"]');
        const orig = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
        
        // Get real values - use getRealVal which checks dataset.real first
        const firstName = getRealVal('edit_first_name');
        const lastName = getRealVal('edit_last_name');
        const email = getRealVal('edit_email');
        const contact = getRealVal('edit_contact');
        const address = getRealVal('edit_address');
        const allergies = getRealVal('edit_allergies') || 'None';
        const conditions = getRealVal('edit_conditions') || 'None';
        const age = parseInt(document.getElementById('edit_age').value) || 0;

        if (!/^\d{12}$/.test(contact)) {
            ModalSystem.toast.error('Contact number must contain exactly 12 digits (example: 639171234567)');
            return;
        }
        
        // DEBUG: Log the real values being retrieved
        console.log('📝 Real values retrieved:', {
            firstName,
            lastName,
            email,
            contact,
            address,
            allergies,
            conditions,
            age
        });
        
        // Use existing birth_date from database, or calculate from age
        let birthDate = p.birth_date || '';
        
        // If birth_date is empty but we have age, calculate it
        if (!birthDate && age > 0) {
            const now = new Date();
            const birthYear = now.getFullYear() - age;
            birthDate = birthYear + '-01-01';
            console.log('📅 Calculated birth_date from age:', birthDate);
        }
        
        const payload = {
            first_name: firstName,
            last_name: lastName,
            email: email,
            contact: contact,
            gender: document.getElementById('edit_gender').value,
            birth_date: birthDate,
            blood_type: document.getElementById('edit_blood_type').value,
            status: document.getElementById('edit_status').value,
            barangay: document.getElementById('edit_barangay').value,
            address: address,
            allergies: allergies,
            conditions: conditions
        };

        console.log('📤 Sending payload:', payload);

        try {
            const res = await fetch(API_BASE + '/patients.php?id=' + id, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const text = await res.text();
            console.log('📥 Response text:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('❌ Failed to parse JSON:', e);
                ModalSystem.toast.error('Invalid response from server');
                return;
            }
            
            if (res.ok && data.success) {
                ModalSystem.toast.success('Patient updated successfully!');
                ModalSystem.close('editPatientModal');
                setTimeout(() => window.location.reload(), 800);
            } else {
                ModalSystem.toast.error(data.message || 'Failed to update patient');
            }
        } catch (err) {
            console.error('❌ Network error:', err);
            ModalSystem.toast.error('Network error: ' + err.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = orig;
        }
    }

    // ============================================================
    // DELETE
    // ============================================================
    function deletePatient(id) {
        const p = PATIENTS[id]; if (!p) return;
        pendingDeleteId = id;
        document.getElementById('deletePatientName').textContent = `${p.first_name} ${p.last_name} (${p.patient_id}) will be permanently removed.`;
        ModalSystem.open('deletePatientModal');
    }
    async function confirmDeletePatient() {
        if (!pendingDeleteId) return;
        try {
            const res = await fetch(API_BASE + '/patients.php?action=delete&id=' + pendingDeleteId, { method:'POST' });
            const data = await res.json();
            if (data.success) { ModalSystem.toast.success('Patient deleted!'); ModalSystem.close('deletePatientModal'); setTimeout(() => window.location.reload(), 1000); }
            else { ModalSystem.toast.error(data.message || 'Failed'); }
        } catch (err) { ModalSystem.toast.success('Patient deleted!'); ModalSystem.close('deletePatientModal'); setTimeout(() => window.location.reload(), 1000); }
        pendingDeleteId = null;
    }

    // ============================================================
    // IMPORT / EXPORT
    // ============================================================
    let pendingImportRows = null;
    function handleImportFile(file) {
        const errorBox = document.getElementById('importError'); errorBox.classList.add('hidden');
        if (!file||!file.name.toLowerCase().endsWith('.csv')) { errorBox.textContent='Please choose a .csv file.'; errorBox.classList.remove('hidden'); return; }
        const reader = new FileReader();
        reader.onload = e => {
            try {
                const rows = e.target.result.trim().split(/\r?\n/).filter(l=>l.trim().length).slice(1).map(l=>{const c=l.split(',').map(x=>x.trim()); return {first_name:c[0]||'',last_name:c[1]||'',email:c[2]||'',contact:c[3]||'',gender:c[4]||'',age:c[5]||'',blood_type:c[6]||'',barangay:c[7]||'',address:c[8]||'',status:c[9]||'active'}; });
                if(!rows.length)throw new Error('No data rows found.');
                pendingImportRows=rows; document.getElementById('importFileName').textContent=file.name;
                document.getElementById('importFileSummary').textContent=rows.length+' patient(s) ready';
                document.getElementById('importFileInfo').classList.remove('hidden'); document.getElementById('importConfirmBtn').disabled=false;
            } catch(err){ pendingImportRows=null; document.getElementById('importConfirmBtn').disabled=true; errorBox.textContent='Error: '+err.message; errorBox.classList.remove('hidden'); }
        };
        reader.readAsText(file);
    }
    function clearImportFile(){ pendingImportRows=null; document.getElementById('importFileInput').value=''; document.getElementById('importFileInfo').classList.add('hidden'); document.getElementById('importError').classList.add('hidden'); document.getElementById('importConfirmBtn').disabled=true; }
    function confirmImport(){ if(!pendingImportRows?.length)return; ModalSystem.close('importModal'); clearImportFile(); ModalSystem.toast.success(pendingImportRows.length+' patient(s) imported.'); setTimeout(()=>window.location.reload(),1000); }
    function prepExportModal(){ document.getElementById('exportCountAll').textContent=Object.keys(PATIENTS).length; document.getElementById('exportCountFiltered').textContent=document.querySelectorAll('.patient-row:not([style*="display: none"])').length; selectExportFormat('csv'); }
    function selectExportFormat(f){ selectedExportFormat=f; document.querySelectorAll('.export-format-btn').forEach(b=>b.classList.toggle('selected',b.dataset.format===f)); }
    function runExport(){ const rows=Object.values(PATIENTS); const h=['patient_id','first_name','last_name','gender','age','blood_type','barangay','status','contact','email']; const csv=[h.join(',')]; rows.forEach(p=>csv.push(h.map(k=>`"${String(p[k]??'').replace(/"/g,'""')}"`).join(','))); const blob=new Blob([csv.join('\n')],{type:'text/csv;charset=utf-8;'}); const url=URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download='patients_export.csv'; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url); ModalSystem.close('exportModal'); ModalSystem.toast.success(rows.length+' patient(s) exported.'); }

    // ============================================================
// SEARCH & FILTER - IMPROVED
// ============================================================
function onDateFilterTypeChange() {
    const t = document.getElementById('filterDateType').value;
    const v = document.getElementById('filterDateValue');
    if (t === 'day') {
        v.type = 'date';
        v.classList.remove('hidden');
    } else if (t === 'month') {
        v.type = 'month';
        v.classList.remove('hidden');
    } else if (t === 'year') {
        v.type = 'number';
        v.min = 2000;
        v.max = 2100;
        v.classList.remove('hidden');
    } else {
        v.classList.add('hidden');
    }
    v.value = '';
    filterPatients();
}

function matchesDateFilter(lastVisit, filterType, filterValue) {
    if (!filterType || !lastVisit) return true;
    
    const d = new Date(lastVisit + 'T00:00:00');
    const td = new Date();
    td.setHours(0, 0, 0, 0);
    
    if (filterType === 'today') {
        return d.getTime() === td.getTime();
    }
    if (filterType === 'day') {
        return !filterValue || lastVisit === filterValue;
    }
    if (filterType === 'month') {
        return !filterValue || lastVisit.slice(0, 7) === filterValue;
    }
    if (filterType === 'year') {
        return !filterValue || lastVisit.slice(0, 4) === String(filterValue);
    }
    return true;
}

function filterPatients() {
    const search = document.getElementById('searchPatient').value.toLowerCase().trim();
    const status = document.getElementById('filterStatus').value;
    const dt = document.getElementById('filterDateType').value;
    const dv = document.getElementById('filterDateValue').value;
    let visibleCount = 0;
    
    document.querySelectorAll('.patient-row').forEach(row => {
        // Get all searchable data from dataset
        const name = (row.dataset.name || '').toLowerCase();
        const patientId = (row.dataset.id || '').toLowerCase();
        const barangay = (row.dataset.barangay || '').toLowerCase();
        const rowStatus = row.dataset.status || '';
        const lastVisit = row.dataset.lastVisit || '';
        
        // Get full name from cell (useful for partial name matching)
        const nameCell = row.querySelector('.cell-name');
        const fullName = nameCell ? nameCell.textContent.toLowerCase() : '';
        
        // Get email from cell
        const emailCell = row.querySelector('.cell-email');
        const email = emailCell ? emailCell.textContent.toLowerCase() : '';
        
        // Get all text content for searching (catches everything)
        const allText = row.textContent.toLowerCase();
        
        // Check if search matches ANY field
        let matchesSearch = true;
        if (search) {
            matchesSearch = 
                name.includes(search) ||
                fullName.includes(search) ||
                patientId.includes(search) ||
                barangay.includes(search) ||
                email.includes(search) ||
                allText.includes(search);
        }
        
        // Check status filter
        let matchesStatus = true;
        if (status) {
            matchesStatus = rowStatus === status;
        }
        
        // Check date filter
        let matchesDate = matchesDateFilter(lastVisit, dt, dv);
        
        // Check if row should be visible
        const isVisible = matchesSearch && matchesStatus && matchesDate;
        
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });
    
    // Show empty state if no results
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
    }
}

function resetFilters() {
    document.getElementById('searchPatient').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterDateType').value = '';
    document.getElementById('filterDateValue').value = '';
    document.getElementById('filterDateValue').classList.add('hidden');
    document.querySelectorAll('.patient-row').forEach(row => {
        row.style.display = '';
    });
    document.getElementById('emptyState').style.display = 'none';
}

function prepAddPatientModal() {
    document.getElementById('addPatientForm').reset();
    document.querySelectorAll('#addPatientForm input.maskable').forEach(input => {
        input.dataset.real = '';
        input.dataset.masked = '';
        input.value = '';
    });
    const ids = Object.values(PATIENTS).map(p => p.id);
    const n = ids.length ? Math.max(...ids) + 1 : 1;
    document.getElementById('nextPatientIdPreview').textContent = 'P-' + String(1000 + n);
}

    // ============================================================
    // FORM VALIDATION
    // ============================================================
    function initPatientValidation(){
        if(typeof ModalSystem==='undefined'||!ModalSystem.validateForm){ setTimeout(initPatientValidation,100); return; }
        const contactValidator = value => /^\d{12}$/.test(value) || 'Contact number must contain exactly 12 digits';
        ModalSystem.validateForm('addPatientModal',{ fields:{ 'add_first_name':{label:'First Name'}, 'add_last_name':{label:'Last Name'}, 'add_email':{label:'Email'}, 'add_contact':{label:'Contact', validator:contactValidator}, 'add_age':{label:'Age'}, 'add_address':{label:'Address'} }, onSubmit:saveNewPatient });
        ModalSystem.validateForm('editPatientModal',{ fields:{ 'edit_first_name':{label:'First Name'}, 'edit_last_name':{label:'Last Name'}, 'edit_email':{label:'Email'}, 'edit_contact':{label:'Contact', validator:contactValidator}, 'edit_age':{label:'Age'} }, onSubmit:saveEditedPatient });
        console.log('✅ Patient form validation initialized');
    }
    if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded',initPatientValidation); }else{ initPatientValidation(); }

    document.addEventListener('keydown',function(e){ if((e.ctrlKey||e.metaKey)&&e.key==='n'){ e.preventDefault(); ModalSystem.open('addPatientModal'); } if((e.ctrlKey||e.metaKey)&&e.key==='f'){ e.preventDefault(); document.getElementById('searchPatient').focus(); } });

    function changePage(page){ if(page<1||page><?php echo $totalPages; ?>)return; window.location.href='?page='+page; }

    // ============================================================
// UPDATE DATASET.REAL ON INPUT CHANGE
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Add input listeners to edit form fields
    const editFields = [
        'edit_first_name', 'edit_last_name', 'edit_email', 
        'edit_contact', 'edit_address', 'edit_allergies', 'edit_conditions'
    ];
    
    editFields.forEach(fieldId => {
        const input = document.getElementById(fieldId);
        if (input) {
            input.addEventListener('input', function() {
                // When user types, update dataset.real with the new value
                const newValue = this.value;
                // Only update if it's not masked (or we want to store the real value)
                // Since we're editing, the user is typing the real value
                if (newValue) {
                    this.dataset.real = newValue;
                }
            });
            
            // Also update on blur (when focus leaves the field)
            input.addEventListener('blur', function() {
                const newValue = this.value;
                if (newValue) {
                    this.dataset.real = newValue;
                }
            });
        }
    });
});
// Attach event listeners for real-time filtering
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchPatient');
    const statusFilter = document.getElementById('filterStatus');
    const dateTypeFilter = document.getElementById('filterDateType');
    const dateValueFilter = document.getElementById('filterDateValue');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterPatients);
        searchInput.addEventListener('keyup', filterPatients);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterPatients);
    }
    if (dateTypeFilter) {
        dateTypeFilter.addEventListener('change', function() {
            onDateFilterTypeChange();
            filterPatients();
        });
    }
    if (dateValueFilter) {
        dateValueFilter.addEventListener('change', filterPatients);
        dateValueFilter.addEventListener('input', filterPatients);
    }
});
// ============================================================
// HIGHLIGHT PATIENT FROM URL PARAMETER
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Get patient ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const patientId = urlParams.get('patient') || urlParams.get('id');
    
    if (patientId) {
        console.log('🔍 Looking for patient:', patientId);
        
        // Find the patient row with matching ID
        let found = false;
        document.querySelectorAll('.patient-row').forEach(row => {
            const rowId = row.dataset.rowId || '';
            const patientCode = row.dataset.id || '';
            
            if (rowId == patientId || patientCode == patientId) {
                found = true;
                console.log('✅ Found patient:', patientId);
                
                // Add highlight class
                row.classList.add('patient-row-highlight');
                
                // Scroll to the row
                setTimeout(() => {
                    row.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 300);
                
                // Also highlight the avatar
                const avatar = row.querySelector('.cell-avatar');
                if (avatar) {
                    avatar.style.boxShadow = '0 0 0 3px #14807A';
                    setTimeout(() => {
                        avatar.style.boxShadow = '';
                    }, 5000);
                }
                
                // Remove highlight after 5 seconds
                setTimeout(() => {
                    row.classList.remove('patient-row-highlight');
                }, 5000);
            }
        });
        
        if (!found) {
            console.warn('⚠️ Patient not found on current page:', patientId);
        }
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>