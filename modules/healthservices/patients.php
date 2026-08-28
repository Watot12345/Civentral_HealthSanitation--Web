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
require_once __DIR__ . '/../../app/Models/Child.php';
require_once __DIR__ . '/../../app/Models/Appointment.php';
$patientModel = new Patient();
$childModel = new Child();
$appointmentModel = new Appointment();
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

// Permission Checks via RBAC System
$canCreatePatient = hasPermission('patients.create');
$canEditPatient   = hasPermission('patients.edit');
$isViewOnly       = !$canCreatePatient && !$canEditPatient;

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
            <p class="text-sm text-slate-500 mt-0.5"><?php echo $isViewOnly ? 'View patient records, clinical history, and vital signs' : 'Manage all patient records and information'; ?></p>
        </div>
        <div class="flex gap-3">
            <?php if ($canCreatePatient || $canEditPatient): ?>
            <div class="flex rounded-lg border border-slate-200 overflow-hidden">
                <?php if ($canCreatePatient): ?>
                <button onclick="ModalSystem.open('importModal')"
                        class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2 border-r border-slate-200">
                    <i class="fa-solid fa-file-import text-xs"></i> Import
                </button>
                <?php endif; ?>
                <button onclick="ModalSystem.open('exportModal'); prepExportModal();"
                        class="px-4 py-2 bg-white text-slate-700 hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-file-export text-xs"></i> Export
                </button>
            </div>
            <?php endif; ?>

            <?php if ($canCreatePatient): ?>
            <button onclick="ModalSystem.open('addPatientModal'); prepAddPatientModal();"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Add Patient
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isViewOnly): ?>
    <div class="mb-5 px-4 py-2.5 bg-blue-50/80 border border-blue-200/80 rounded-xl flex items-center justify-between text-xs text-blue-900 font-medium shadow-xs">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-eye text-blue-600 text-sm"></i>
            <span><strong>View Only Mode:</strong> Accessing patient clinical history and records. Demographic editing is managed by Records Staff & Nurses.</span>
        </div>
        <span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[10px] font-extrabold">Clinical View</span>
    </div>
    <?php endif; ?>

    <?php
    $criticalConditions = ['Heart Disease'];
    $criticalCount = count(array_filter($patients, fn($p) => in_array($p['conditions'], $criticalConditions)));
    
    // Dynamic real-time calculation of today's appointments
    $todaysAppointments = 0;
    try {
        $allAppointments = $appointmentModel->all();
        $todayStr = date('Y-m-d');
        $todaysAppointments = count(array_filter($allAppointments, function($a) use ($todayStr) {
            $status = strtolower($a['status'] ?? 'pending');
            if ($status === 'cancelled') return false;
            $aDate = !empty($a['appointment_date']) 
                ? substr($a['appointment_date'], 0, 10) 
                : (!empty($a['date']) ? substr($a['date'], 0, 10) : '');
            return $aDate === $todayStr;
        }));
    } catch (\Throwable $e) {
        error_log('Error counting today appointments in patients.php: ' . $e->getMessage());
        $todaysAppointments = 0;
    }
    $pendingLabResults  = 2;
    $followUpsDue       = 4;
    $ageGroups = [
        '0-5 yrs'  => 0,
        '6-17 yrs' => 0,
        '18-35 yrs'=> 0,
        '36-50 yrs'=> 0,
        '51-65 yrs'=> 0,
        '66+ yrs'  => 0
    ];

    foreach ($patients as $p) {
        $a = (int)$p['age'];
        if ($a <= 5) $ageGroups['0-5 yrs']++;
        elseif ($a <= 17) $ageGroups['6-17 yrs']++;
        elseif ($a <= 35) $ageGroups['18-35 yrs']++;
        elseif ($a <= 50) $ageGroups['36-50 yrs']++;
        elseif ($a <= 65) $ageGroups['51-65 yrs']++;
        else $ageGroups['66+ yrs']++;
    }

    // Include registered children under 5 from child records
    try {
        $dbChildren = $childModel->all();
        if (!empty($dbChildren) && is_array($dbChildren)) {
            $now = new DateTime();
            foreach ($dbChildren as $c) {
                $cAge = 0;
                if (!empty($c['birth_date'])) {
                    try {
                        $dob = new DateTime($c['birth_date']);
                        $cAge = $now->diff($dob)->y;
                    } catch (\Throwable $e) {
                        $cAge = 0;
                    }
                }
                if ($cAge <= 5) $ageGroups['0-5 yrs']++;
                elseif ($cAge <= 17) $ageGroups['6-17 yrs']++;
                elseif ($cAge <= 35) $ageGroups['18-35 yrs']++;
                elseif ($cAge <= 50) $ageGroups['36-50 yrs']++;
                elseif ($cAge <= 65) $ageGroups['51-65 yrs']++;
                else $ageGroups['66+ yrs']++;
            }
        }
    } catch (\Throwable $e) {
        error_log('Error loading children in age distribution: ' . $e->getMessage());
    }
    $caloocanZones = [
        'Zone 1'  => [1, 2, 3, 4],
        'Zone 7'  => [77, 78, 79, 80, 81],
        'Zone 8'  => [82, 83, 84, 85],
        'Zone 12' => [132, 133, 134, 135, 136, 137, 138, 139, 140],
        'Zone 13' => [141, 142, 143, 144, 145, 146, 147, 148, 149, 150],
        'Zone 14' => [151, 152, 153, 154, 155, 156, 157, 158, 159, 160],
        'Zone 15' => [161, 162, 163, 164]
    ];

    $zoneCounts = [
        'Zone 1'  => 0,
        'Zone 7'  => 0,
        'Zone 8'  => 0,
        'Zone 12' => 0,
        'Zone 13' => 0,
        'Zone 14' => 0,
        'Zone 15' => 0,
        'Other'   => 0
    ];

    $barangayCounts = [];
    foreach ($patients as $p) {
        $brgy = $p['barangay'] ?? '';
        $barangayCounts[$brgy] = ($barangayCounts[$brgy] ?? 0) + 1;

        // Match number from Barangay string
        preg_match('/\b(\d{1,3})\b/', $brgy, $matches);
        $brgyNum = isset($matches[1]) ? (int)$matches[1] : null;
        $assignedZone = 'Other';

        if ($brgyNum !== null) {
            foreach ($caloocanZones as $zName => $zBrgys) {
                if (in_array($brgyNum, $zBrgys, true)) {
                    $assignedZone = $zName;
                    break;
                }
            }
        }
        $zoneCounts[$assignedZone] = ($zoneCounts[$assignedZone] ?? 0) + 1;
    }
    // Remove 0-count zones and sort descending
    $zoneCounts = array_filter($zoneCounts, fn($c) => $c > 0);
    arsort($zoneCounts);
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
        <div onclick="window.location.href='appointments.php'" class="cursor-pointer relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group" title="View Today's Appointments">
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
        <div class="bg-white rounded-xl shadow-xs p-5 border border-slate-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-map-location-dot text-brand-medium"></i> Geographic Distribution (By Zone)
                </h3>
                <span class="text-[11px] font-bold text-brand-dark bg-brand-light border border-brand-border/60 px-2.5 py-0.5 rounded-full">
                    <?php echo count($zoneCounts); ?> Active Zones
                </span>
            </div>
            <div class="h-52">
                <canvas id="zonePieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <!-- Top Search Bar & Location/Status Dropdowns -->
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="searchPatient" oninput="filterPatients()" placeholder="Search by name, ID (e.g. P-0068), or barangay..." class="w-full pl-9 pr-9 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
                <button type="button" id="clearPatientSearch" onclick="clearPatientSearchInput()" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
            <div class="flex gap-2 flex-wrap items-center">
                <select id="filterZone" onchange="onFilterZoneChange()" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-xs font-semibold bg-white text-slate-700">
                    <option value="">All Zones</option>
                    <option value="Zone 1">Zone 1 (Brgy 1–4)</option>
                    <option value="Zone 7">Zone 7 (Brgy 77–81)</option>
                    <option value="Zone 8">Zone 8 (Brgy 82–85)</option>
                    <option value="Zone 12">Zone 12 (Brgy 132–140)</option>
                    <option value="Zone 13">Zone 13 (Brgy 141–150)</option>
                    <option value="Zone 14">Zone 14 (Brgy 151–160)</option>
                    <option value="Zone 15">Zone 15 (Brgy 161–164)</option>
                </select>
                <select id="filterBarangay" onchange="filterPatients()" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-xs font-semibold bg-white text-slate-700">
                    <option value="">All Barangays</option>
                </select>
                <select id="filterStatus" onchange="filterPatients()" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-xs font-semibold bg-white text-slate-700">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Quick Date Range Pills Under Search -->
        <div class="flex items-center justify-between flex-wrap gap-3 pt-3 mt-3 border-t border-slate-100">
            <div class="flex items-center gap-1.5 p-1 bg-slate-100/90 rounded-xl w-fit">
                <button type="button" onclick="setDateFilter('all')" id="dateFilterBtnAll"
                        class="date-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer bg-white text-slate-800 shadow-xs">
                    <i class="fa-solid fa-users mr-1"></i> All Patients (<?php echo count($patients); ?>)
                </button>
                <button type="button" onclick="setDateFilter('today')" id="dateFilterBtnToday"
                        class="date-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-calendar-day mr-1 text-amber-500"></i> Today's Visits
                </button>
                <button type="button" onclick="setDateFilter('week')" id="dateFilterBtnWeek"
                        class="date-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-calendar-week mr-1"></i> This Week
                </button>
                <button type="button" onclick="setDateFilter('month')" id="dateFilterBtnMonth"
                        class="date-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-calendar mr-1"></i> This Month
                </button>
                <button type="button" onclick="setDateFilter('custom')" id="dateFilterBtnCustom"
                        class="date-filter-btn px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer text-slate-500 hover:text-slate-800">
                    <i class="fa-solid fa-calendar-days mr-1 text-blue-500"></i> Specific Day / Range
                </button>
            </div>

            <!-- Custom Date Range (Only visible when 'Specific Day / Range' is clicked) -->
            <div id="customDateRangeContainer" class="hidden flex items-center gap-2 text-xs text-slate-500 font-medium bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                <span>From</span>
                <input type="date" id="filterDateFrom" onchange="filterPatients()" class="px-2 py-1 border border-slate-200 rounded-md text-xs bg-white focus:ring-1 focus:ring-brand-medium outline-none">
                <span>To</span>
                <input type="date" id="filterDateTo" onchange="filterPatients()" class="px-2 py-1 border border-slate-200 rounded-md text-xs bg-white focus:ring-1 focus:ring-brand-medium outline-none">
            </div>
        </div>
    </div>

    <!-- Patient Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200"><tr><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient ID</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Name</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Gender</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Age</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Blood Type</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Barangay</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th><th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Last Visit</th><th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th></tr></thead>
                <tbody id="patientTableBody">
                   <?php foreach ($patients as $patient): ?>
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
            <button onclick="viewPatient(<?php echo $patient['id']; ?>)" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details"><i class="fa-solid fa-eye text-sm"></i></button>
            <?php if ($canEditPatient): ?>
            <button onclick="editPatient(<?php echo $patient['id']; ?>)" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-lg transition" title="Edit Patient"><i class="fa-solid fa-pen-to-square text-sm"></i></button>
            <?php endif; ?>
            <button onclick="scheduleAppointment(<?php echo $patient['id']; ?>)" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Schedule Appointment"><i class="fa-solid fa-calendar-plus text-sm"></i></button>
            <button onclick="checkInPatient(<?php echo $patient['id']; ?>)" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Check-in / Queue"><i class="fa-solid fa-receipt text-sm"></i></button>
            <button onclick="openMedicalRecord(<?php echo $patient['id']; ?>)" class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Medical Record"><i class="fa-solid fa-folder-open text-sm"></i></button>
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

<!-- EDIT PATIENT MODAL -->
<div id="editPatientModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl"><h3 class="font-bold text-slate-900">Edit Patient</h3><button onclick="ModalSystem.close('editPatientModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<form id="editPatientForm" class="p-6 space-y-5"><input type="hidden" id="edit_id">
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">First Name</label><input type="text" id="edit_first_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Name</label><input type="text" id="edit_last_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label><input type="email" id="edit_email" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact Number</label><div class="flex rounded-lg border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-brand-medium/40 focus-within:border-brand-medium overflow-hidden"><span class="inline-flex items-center px-3 bg-slate-50 border-r border-slate-200 text-slate-700 font-bold text-xs select-none">🇵🇭 +63</span><input type="text" id="edit_contact" required maxlength="10" inputmode="numeric" placeholder="9XXXXXXXXX" class="w-full px-3 py-2 text-sm outline-none border-0 focus:ring-0 bg-transparent"></div></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label><select id="edit_gender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="Male">Male</option><option value="Female">Female</option></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Age</label><input type="number" id="edit_age" min="0" max="120" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Blood Type</label><select id="edit_blood_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><?php foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $bt): ?><option value="<?php echo $bt; ?>"><?php echo $bt; ?></option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>

<!-- HIERARCHICAL ZONE & BARANGAY SELECTION -->
<div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Zone</label>
        <select id="edit_zone" onchange="onZoneChange('edit_zone', 'edit_barangay')" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            <option value="">All Zones (Select Zone)</option>
            <option value="Zone 1">Zone 1 (Brgy 1 to 4)</option>
            <option value="Zone 7">Zone 7 (Brgy 77 to 81)</option>
            <option value="Zone 8">Zone 8 (Brgy 82 to 85)</option>
            <option value="Zone 12">Zone 12 (Brgy 132 to 140)</option>
            <option value="Zone 13">Zone 13 (Brgy 141 to 150)</option>
            <option value="Zone 14">Zone 14 (Brgy 151 to 160)</option>
            <option value="Zone 15">Zone 15 (Brgy 161 to 164)</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay <span class="text-rose-500">*</span></label>
        <select id="edit_barangay" onchange="onBarangayChange('edit_barangay', 'edit_zone')" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            <!-- Populated dynamically in ascending order -->
        </select>
    </div>
</div>

<div class="sm:col-span-2"><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label><input type="text" id="edit_address" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Allergies</label><input type="text" id="edit_allergies" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Conditions</label><input type="text" id="edit_conditions" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
</div>
<div class="flex justify-end gap-2 pt-2 border-t border-slate-100"><button type="button" onclick="ModalSystem.close('editPatientModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Save Changes</button></div>
</form></div></div>

<!-- ADD PATIENT MODAL -->
<div id="addPatientModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4"><div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"><div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl"><div><h3 class="font-bold text-slate-900">Add New Patient</h3><p class="text-xs text-slate-400 mt-0.5">Next ID: <span id="nextPatientIdPreview" class="font-mono font-semibold text-brand-dark"></span></p></div><button onclick="ModalSystem.close('addPatientModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button></div>
<form id="addPatientForm" class="p-6 space-y-5">
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">First Name</label><input type="text" id="add_first_name" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Name</label><input type="text" id="add_last_name" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label><input type="email" id="add_email" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact Number</label><div class="flex rounded-lg border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-brand-medium/40 focus-within:border-brand-medium overflow-hidden"><span class="inline-flex items-center px-3 bg-slate-50 border-r border-slate-200 text-slate-700 font-bold text-xs select-none">🇵🇭 +63</span><input type="text" id="add_contact" required maxlength="10" inputmode="numeric" placeholder="9XXXXXXXXX" class="maskable input-maskable w-full px-3 py-2 text-sm outline-none border-0 focus:ring-0 bg-transparent"></div></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label><select id="add_gender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="Male">Male</option><option value="Female">Female</option></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Age</label><input type="number" id="add_age" min="0" max="120" required class="maskable input-maskable w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Blood Type</label><select id="add_blood_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><?php foreach(['O+','O-','A+','A-','B+','B-','AB+','AB-'] as $bt): ?><option value="<?php echo $bt; ?>"><?php echo $bt; ?></option><?php endforeach; ?></select></div>
<div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="add_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>

<!-- HIERARCHICAL ZONE & BARANGAY SELECTION -->
<div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Zone</label>
        <select id="add_zone" onchange="onZoneChange('add_zone', 'add_barangay')" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            <option value="">All Zones (Select Zone)</option>
            <option value="Zone 1">Zone 1 (Brgy 1 to 4)</option>
            <option value="Zone 7">Zone 7 (Brgy 77 to 81)</option>
            <option value="Zone 8">Zone 8 (Brgy 82 to 85)</option>
            <option value="Zone 12">Zone 12 (Brgy 132 to 140)</option>
            <option value="Zone 13">Zone 13 (Brgy 141 to 150)</option>
            <option value="Zone 14">Zone 14 (Brgy 151 to 160)</option>
            <option value="Zone 15">Zone 15 (Brgy 161 to 164)</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay <span class="text-rose-500">*</span></label>
        <select id="add_barangay" onchange="onBarangayChange('add_barangay', 'add_zone')" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            <!-- Populated dynamically in ascending order -->
        </select>
    </div>
</div>

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

<!-- PATIENT CHECK-IN MODAL (PATIENTS.PHP) -->
<div id="checkInModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-check text-amber-500"></i> Patient Check-in
            </h3>
            <button onclick="ModalSystem.close('checkInModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="checkInForm" class="p-6 space-y-4" onsubmit="submitCheckinFromPatients(event)">
            <input type="hidden" id="checkin_patient_id" value="">
            
            <div class="bg-amber-50 rounded-xl p-3 border border-amber-200 text-xs text-amber-900 space-y-1">
                <p class="font-bold text-amber-900">Check-in Patient Confirmation</p>
                <p class="text-amber-800">Log this patient as arrived for today's health center visit.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Selected Patient</label>
                <div class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 font-bold text-slate-800" id="checkin_patient_name_display">
                    -
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Reason for Visit *</label>
                <select id="checkin_reason_for_visit" required class="w-full px-3 py-2.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-amber-500/40 focus:border-amber-500 outline-none font-semibold text-slate-800">
                    <option value="Medical Consultation">🩺 Medical Consultation (Doctor)</option>
                    <option value="Nutrition Assessment">🥗 Nutrition Assessment (Dietetics & Growth)</option>
                    <option value="Immunization">💉 Immunization / Vaccination</option>
                    <option value="Dental Consultation">🦷 Dental Consultation</option>
                    <option value="Prenatal Consultation">🤰 Prenatal / Maternal Care</option>
                    <option value="General Checkup">📋 General Checkup / Vitals</option>
                    <option value="Follow-up">🔄 Follow-up Visit</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Directs the patient to the corresponding clinic service queue</p>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('checkInModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition text-sm font-semibold">
                    <i class="fa-solid fa-check-circle mr-1.5"></i> Confirm Check-in
                </button>
            </div>
        </form>
    </div>
</div>

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
    const PALETTE = ['#0B4F4A', '#14807A', '#2EB8A0', '#5CCFBB', '#8FE3D6', '#D4A853', '#E07B54', '#6366F1', '#EC4899'];
    const ageLabels = <?php echo json_encode(array_keys($ageGroups)); ?>;
    const ageValues = <?php echo json_encode(array_values($ageGroups)); ?>;
    const zoneRaw   = <?php echo json_encode($zoneCounts); ?>;
    const zLabels   = Object.keys(zoneRaw);
    const zValues   = Object.values(zoneRaw);

    Chart.defaults.font.family = "'Inter', 'Segoe UI', system-ui, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#64748B';
    const TOOLTIP_STYLE = { backgroundColor:'#1E293B', titleFont:{weight:'600',size:12}, bodyFont:{size:11}, padding:10, cornerRadius:8, displayColors:true, boxPadding:4 };
    
    // 1. Age Group Line Chart
    const ageLineCtx = document.getElementById('ageLineChart').getContext('2d');
    new Chart(ageLineCtx, {
        type: 'line',
        data: {
            labels: ageLabels,
            datasets: [{
                label: 'Patients',
                data: ageValues,
                borderColor: BRAND.medium,
                backgroundColor: BRAND.medium + '20',
                borderWidth: 3,
                pointBackgroundColor: BRAND.dark,
                pointBorderColor: '#FFFFFF',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { ...TOOLTIP_STYLE }
            },
            scales: {
                x: { grid: { display: false }, border: { display: false } },
                y: { beginAtZero: true, grid: { color: '#F1F5F9' }, border: { display: false } }
            }
        }
    });

    // 2. Geographic Zone Doughnut Chart
    const zPieCtx = document.getElementById('zonePieChart').getContext('2d');
    const totalZonePatients = zValues.reduce((a, b) => a + b, 0);

    new Chart(zPieCtx, {
        type: 'doughnut',
        data: {
            labels: zLabels,
            datasets: [{
                data: zValues,
                backgroundColor: PALETTE.slice(0, zLabels.length),
                borderWidth: 2,
                borderColor: '#FFFFFF',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '55%',
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 12,
                        font: { size: 11, weight: '500' },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            if (data.labels.length && data.datasets.length) {
                                return data.labels.map((label, i) => {
                                    const val = data.datasets[0].data[i];
                                    const pct = totalZonePatients > 0 ? Math.round((val / totalZonePatients) * 100) : 0;
                                    return {
                                        text: `${label} (${val} • ${pct}%)`,
                                        fillStyle: data.datasets[0].backgroundColor[i],
                                        strokeStyle: '#ffffff',
                                        lineWidth: 1,
                                        hidden: isNaN(val) || (chart.getDatasetMeta(0).data[i] && chart.getDatasetMeta(0).data[i].hidden),
                                        index: i
                                    };
                                });
                            }
                            return [];
                        }
                    }
                },
                tooltip: {
                    ...TOOLTIP_STYLE,
                    callbacks: {
                        label: function(ctx) {
                            const val = ctx.parsed;
                            const pct = totalZonePatients > 0 ? ((val / totalZonePatients) * 100).toFixed(1) : 0;
                            return ` ${ctx.label}: ${val} patients (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
    })();

    // ============================================================
    // DATA & API
    // ============================================================
    const API_BASE = '<?php echo site_url('api'); ?>';
    const PATIENTS = <?php echo json_encode(array_column($patients, null, 'id'), JSON_PRETTY_PRINT); ?>;
    let pendingDeleteId = null;
    let selectedExportFormat = 'csv';

    // ============================================================
    // WORKFLOW SHORTCUT NAVIGATION HELPERS
    // ============================================================
    function scheduleAppointment(patientId) {
        window.location.href = `appointments.php?patient_id=${patientId}&action=new`;
    }

    function checkInPatient(patientId) {
        const patient = PATIENTS[patientId];
        document.getElementById('checkin_patient_id').value = patientId;
        
        let name = 'Patient #' + patientId;
        if (patient) {
            name = `${patient.first_name || ''} ${patient.last_name || ''}`.trim() || patient.name || name;
            const code = patient.patient_id || ('P-' + patientId);
            name = `${name} (${code})`;
        }
        document.getElementById('checkin_patient_id').value = patientId;
        document.getElementById('checkin_patient_name_display').textContent = name;
        document.getElementById('checkin_reason_for_visit').value = 'Medical Consultation';

        ModalSystem.open('checkInModal');
    }

    async function submitCheckinFromPatients(event) {
        event.preventDefault();
        const patientId = document.getElementById('checkin_patient_id').value;
        const reasonForVisit = document.getElementById('checkin_reason_for_visit').value;
        const submitBtn = event.target.querySelector('button[type="submit"]');

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Checking in...';
        }

        try {
            const response = await fetch(`${API_BASE}/triage-queue.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    patient_id: parseInt(patientId),
                    reason_for_visit: reasonForVisit
                })
            });
            const data = await response.json();
            if (data.success || response.ok) {
                ModalSystem.toast.success(`✅ Patient checked in successfully for ${reasonForVisit}!`);
                ModalSystem.close('checkInModal');
            } else {
                ModalSystem.toast.error(data.message || 'Failed to check in patient');
            }
        } catch (e) {
            ModalSystem.toast.error('Network error during check-in');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-check-circle mr-1.5"></i> Confirm Check-in';
            }
        }
    }

    function openMedicalRecord(patientId) {
        window.location.href = `medical_records.php?patient_id=${patientId}`;
    }

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
        
        let cleanContactForEdit = (p.contact || '').replace(/^\+?63/, '').replace(/^0/, '');
        const fields = {
            'edit_first_name': p.first_name || '',
            'edit_last_name': p.last_name || '',
            'edit_email': p.email || '',
            'edit_contact': cleanContactForEdit,
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
        
        const barangayVal = p.barangay || '';
        const zoneVal = getZoneForBarangay(barangayVal);
        const editZoneSelect = document.getElementById('edit_zone');
        if (editZoneSelect) editZoneSelect.value = zoneVal;
        populateBarangayDropdown('edit_barangay', zoneVal, barangayVal);

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
    let rawContact = (document.getElementById('add_contact')?.value || '').trim();
    const gender = document.getElementById('add_gender')?.value || '';
    const age = parseInt(document.getElementById('add_age')?.value) || 0;
    const bloodType = document.getElementById('add_blood_type')?.value || '';
    const status = document.getElementById('add_status')?.value || 'active';
    const barangay = document.getElementById('add_barangay')?.value || '';
    const address = document.getElementById('add_address')?.value || '';
    const emergencyContact = document.getElementById('add_emergency_contact')?.value || 'None';
    const allergies = document.getElementById('add_allergies')?.value || 'None';
    const conditions = document.getElementById('add_conditions')?.value || 'None';
    
    // Clean and validate contact number (10 digits starting with 9, e.g. 9358587433)
    let cleanContact = rawContact.replace(/\D/g, '');
    if (cleanContact.startsWith('63')) cleanContact = cleanContact.substring(2);
    if (cleanContact.startsWith('0')) cleanContact = cleanContact.substring(1);

    // Validate required fields
    if (!firstName || !lastName || !cleanContact) {
        ModalSystem.toast.error('Please fill in all required fields (First Name, Last Name, Contact)');
        return;
    }

    if (cleanContact.length !== 10 || !cleanContact.startsWith('9')) {
        ModalSystem.toast.error('Contact number must contain 10 digits starting with 9 (example: 9358587433)');
        return;
    }
    const contact = '+63' + cleanContact;
    
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
        } else if (data.is_duplicate || res.status === 409) {
            ModalSystem.close('addPatientModal');
            const searchVal = (firstName + ' ' + lastName).trim();
            const message = data.message || `⚠️ Patient record already exists! Please search and filter existing records instead of registering duplicates.`;
            
            ModalSystem.confirm(
                `${message}\n\nWould you like to search and filter for existing patient "${searchVal}" now?`,
                () => {
                    const searchInput = document.getElementById('searchPatients');
                    if (searchInput) {
                        searchInput.value = searchVal;
                        searchInput.dispatchEvent(new Event('input'));
                        searchInput.focus();
                    }
                },
                { title: '⚠️ Duplicate Patient Record', confirmText: 'Search & Filter Patient', type: 'warning' }
            );
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
        let rawContact = getRealVal('edit_contact') || '';
        let cleanContact = rawContact.replace(/\D/g, '');
        if (cleanContact.startsWith('63')) cleanContact = cleanContact.substring(2);
        if (cleanContact.startsWith('0')) cleanContact = cleanContact.substring(1);

        if (cleanContact.length !== 10 || !cleanContact.startsWith('9')) {
            ModalSystem.toast.error('Contact number must contain 10 digits starting with 9 (example: 9358587433)');
            return;
        }
        const contact = '+63' + cleanContact;
        const address = getRealVal('edit_address');
        const allergies = getRealVal('edit_allergies') || 'None';
        const conditions = getRealVal('edit_conditions') || 'None';
        const age = parseInt(document.getElementById('edit_age').value) || 0;
        
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
// SEARCH & FILTER - DATE PILLS & LIVE SEARCH
// ============================================================
let currentPatientDateFilter = 'all';

function setDateFilter(mode) {
    currentPatientDateFilter = mode;
    
    document.querySelectorAll('.date-filter-btn').forEach(btn => {
        btn.classList.remove('bg-white', 'text-slate-800', 'shadow-xs');
        btn.classList.add('text-slate-500', 'hover:text-slate-800');
    });

    const activeBtn = document.getElementById(
        mode === 'today' ? 'dateFilterBtnToday' :
        mode === 'week' ? 'dateFilterBtnWeek' :
        mode === 'month' ? 'dateFilterBtnMonth' :
        mode === 'custom' ? 'dateFilterBtnCustom' :
        mode === 'all' ? 'dateFilterBtnAll' : null
    );

    if (activeBtn) {
        activeBtn.classList.remove('text-slate-500', 'hover:text-slate-800');
        activeBtn.classList.add('bg-white', 'text-slate-800', 'shadow-xs');
    }

    const customContainer = document.getElementById('customDateRangeContainer');
    if (customContainer) {
        if (mode === 'custom') {
            customContainer.classList.remove('hidden');
        } else {
            customContainer.classList.add('hidden');
            const fromEl = document.getElementById('filterDateFrom');
            const toEl = document.getElementById('filterDateTo');
            if (fromEl) fromEl.value = '';
            if (toEl) toEl.value = '';
        }
    }

    filterPatients();
}

function clearPatientSearchInput() {
    const input = document.getElementById('searchPatient');
    if (input) {
        input.value = '';
        document.getElementById('clearPatientSearch')?.classList.add('hidden');
        filterPatients();
        input.focus();
    }
}

// ============================================================
// CALOOCAN DISTRICT 1 ZONES & BARANGAYS CONFIGURATION
// ============================================================
const CALOOCAN_ZONES = {
    'Zone 1': [1, 2, 3, 4],
    'Zone 7': [77, 78, 79, 80, 81],
    'Zone 8': [82, 83, 84, 85],
    'Zone 12': [132, 133, 134, 135, 136, 137, 138, 139, 140],
    'Zone 13': [141, 142, 143, 144, 145, 146, 147, 148, 149, 150],
    'Zone 14': [151, 152, 153, 154, 155, 156, 157, 158, 159, 160],
    'Zone 15': [161, 162, 163, 164]
};

function getZoneForBarangay(barangayName) {
    if (!barangayName) return '';
    const match = String(barangayName).match(/\b(\d{1,3})\b/);
    if (!match) return '';
    const num = parseInt(match[1]);
    for (const [zone, brgys] of Object.entries(CALOOCAN_ZONES)) {
        if (brgys.includes(num)) return zone;
    }
    return '';
}

function populateBarangayDropdown(selectId, targetZone = '', selectedValue = '') {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    select.innerHTML = '<option value="">' + (selectId.startsWith('filter') ? 'All Barangays' : 'Select Barangay') + '</option>';
    
    if (targetZone && CALOOCAN_ZONES[targetZone]) {
        const brgys = CALOOCAN_ZONES[targetZone];
        brgys.forEach(num => {
            const val = `Barangay ${num}`;
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = `Barangay ${num}`;
            if (val === selectedValue) opt.selected = true;
            select.appendChild(opt);
        });
    } else {
        // Show all grouped by zone in ascending numerical order
        for (const [zone, brgys] of Object.entries(CALOOCAN_ZONES)) {
            const group = document.createElement('optgroup');
            group.label = `${zone} (Brgy ${brgys[0]}–${brgys[brgys.length-1]})`;
            brgys.forEach(num => {
                const val = `Barangay ${num}`;
                const opt = document.createElement('option');
                opt.value = val;
                opt.textContent = `Barangay ${num}`;
                if (val === selectedValue) opt.selected = true;
                group.appendChild(opt);
            });
            select.appendChild(group);
        }
    }
    if (selectedValue) {
        select.value = selectedValue;
    }
}

function onZoneChange(zoneSelectId, barangaySelectId) {
    const zoneSelect = document.getElementById(zoneSelectId);
    const zone = zoneSelect ? zoneSelect.value : '';
    populateBarangayDropdown(barangaySelectId, zone, '');
}

function onBarangayChange(barangaySelectId, zoneSelectId) {
    const barangaySelect = document.getElementById(barangaySelectId);
    const zoneSelect = document.getElementById(zoneSelectId);
    if (!barangaySelect || !zoneSelect) return;
    
    const zone = getZoneForBarangay(barangaySelect.value);
    if (zone && zoneSelect.value !== zone) {
        zoneSelect.value = zone;
    }
}

function onFilterZoneChange() {
    const filterZone = document.getElementById('filterZone').value;
    populateBarangayDropdown('filterBarangay', filterZone, '');
    filterPatients();
}

function filterPatients() {
    const search = (document.getElementById('searchPatient')?.value || '').toLowerCase().trim();
    const clearBtn = document.getElementById('clearPatientSearch');
    if (clearBtn) {
        clearBtn.classList.toggle('hidden', search.length === 0);
    }
    
    const status = document.getElementById('filterStatus')?.value || '';
    const filterZone = document.getElementById('filterZone')?.value || '';
    const filterBrgy = document.getElementById('filterBarangay')?.value || '';
    const dateFrom = document.getElementById('filterDateFrom')?.value || '';
    const dateTo = document.getElementById('filterDateTo')?.value || '';

    const todayStr = new Date().toISOString().slice(0, 10);
    const now = new Date();
    const oneWeekAgo = new Date(now.getTime() - 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
    const currentMonthPrefix = todayStr.slice(0, 7);

    let visibleCount = 0;
    
    document.querySelectorAll('.patient-row').forEach(row => {
        // Get all searchable data from dataset
        const name = (row.dataset.name || '').toLowerCase();
        const patientId = (row.dataset.id || '').toLowerCase();
        const barangay = row.dataset.barangay || '';
        const rowStatus = row.dataset.status || '';
        const lastVisit = row.dataset.lastVisit || '';
        
        // Get full name from cell (useful for partial name matching)
        const nameCell = row.querySelector('.cell-name');
        const fullName = nameCell ? nameCell.textContent.toLowerCase() : '';
        
        // Get email from cell
        const emailCell = row.querySelector('.cell-email');
        const email = emailCell ? emailCell.textContent.toLowerCase() : '';
        
        // Get all text content for searching
        const allText = row.textContent.toLowerCase();
        
        // Check if search matches ANY field
        let matchesSearch = true;
        if (search) {
            matchesSearch = 
                name.includes(search) ||
                fullName.includes(search) ||
                patientId.includes(search) ||
                barangay.toLowerCase().includes(search) ||
                email.includes(search) ||
                allText.includes(search);
        }
        
        // Check status filter
        let matchesStatus = true;
        if (status) {
            matchesStatus = rowStatus === status;
        }

        // Check Zone filter
        let matchesZone = true;
        if (filterZone) {
            const rowZone = getZoneForBarangay(barangay);
            matchesZone = rowZone === filterZone;
        }

        // Check Barangay filter
        let matchesBarangay = true;
        if (filterBrgy) {
            matchesBarangay = barangay.trim().toLowerCase() === filterBrgy.trim().toLowerCase();
        }
        
        // Check date filter
        let matchesDate = true;
        if (currentPatientDateFilter === 'today') {
            matchesDate = (lastVisit === todayStr);
        } else if (currentPatientDateFilter === 'week') {
            matchesDate = (lastVisit >= oneWeekAgo && lastVisit <= todayStr);
        } else if (currentPatientDateFilter === 'month') {
            matchesDate = lastVisit.startsWith(currentMonthPrefix);
        } else if (currentPatientDateFilter === 'custom') {
            if (dateFrom && lastVisit < dateFrom) matchesDate = false;
            if (dateTo && lastVisit > dateTo) matchesDate = false;
        }
        
        // Check if row should be visible
        const isVisible = matchesSearch && matchesStatus && matchesZone && matchesBarangay && matchesDate;
        
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
    const searchInput = document.getElementById('searchPatient');
    if (searchInput) searchInput.value = '';
    document.getElementById('clearPatientSearch')?.classList.add('hidden');
    if (document.getElementById('filterStatus')) document.getElementById('filterStatus').value = '';
    if (document.getElementById('filterZone')) document.getElementById('filterZone').value = '';
    populateBarangayDropdown('filterBarangay', '', '');
    setDateFilter('all');
}

function prepAddPatientModal() {
    document.getElementById('addPatientForm').reset();
    document.querySelectorAll('#addPatientForm input.maskable').forEach(input => {
        input.dataset.real = '';
        input.dataset.masked = '';
        input.value = '';
    });
    const addZoneSelect = document.getElementById('add_zone');
    if (addZoneSelect) addZoneSelect.value = '';
    populateBarangayDropdown('add_barangay', '', '');

    const ids = Object.values(PATIENTS).map(p => p.id);
    const n = ids.length ? Math.max(...ids) + 1 : 1;
    document.getElementById('nextPatientIdPreview').textContent = 'P-' + String(1000 + n);
}

// Initial populate of dropdowns on page load
document.addEventListener('DOMContentLoaded', function() {
    populateBarangayDropdown('add_barangay', '', '');
    populateBarangayDropdown('edit_barangay', '', '');
    populateBarangayDropdown('filterBarangay', '', '');
});

    // ============================================================
    // FORM VALIDATION
    // ============================================================
    function initPatientValidation(){
        if(typeof ModalSystem==='undefined'||!ModalSystem.validateForm){ setTimeout(initPatientValidation,100); return; }
        const contactValidator = value => {
            const clean = (value || '').toString().replace(/\D/g, '').replace(/^\+?63/, '').replace(/^0/, '');
            return (clean.length === 10 && clean.startsWith('9')) || 'Contact number must contain 10 digits starting with 9 (e.g. 9358587433)';
        };
        ModalSystem.validateForm('addPatientModal',{ fields:{ 'add_first_name':{label:'First Name'}, 'add_last_name':{label:'Last Name'}, 'add_email':{label:'Email'}, 'add_contact':{label:'Contact', validator:contactValidator}, 'add_age':{label:'Age'}, 'add_address':{label:'Address'} }, onSubmit:saveNewPatient });
        ModalSystem.validateForm('editPatientModal',{ fields:{ 'edit_first_name':{label:'First Name'}, 'edit_last_name':{label:'Last Name'}, 'edit_email':{label:'Email'}, 'edit_contact':{label:'Contact', validator:contactValidator}, 'edit_age':{label:'Age'} }, onSubmit:saveEditedPatient });
        console.log('✅ Patient form validation initialized');
    }
    if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded',initPatientValidation); }else{ initPatientValidation(); }

    document.addEventListener('keydown',function(e){ if((e.ctrlKey||e.metaKey)&&e.key==='n'){ e.preventDefault(); ModalSystem.open('addPatientModal'); } if((e.ctrlKey||e.metaKey)&&e.key==='f'){ e.preventDefault(); document.getElementById('searchPatient').focus(); } });

    function changePage(page){ if(page<1||page><?php echo $totalPages; ?>)return; window.location.href='?page='+page; }

    // ============================================================
// UPDATE DATASET.REAL ON INPUT CHANGE & AUTO-CLEAN CONTACT INPUTS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    // Auto-clean mobile inputs to 10 digits without formatting errors
    ['add_contact', 'edit_contact'].forEach(fieldId => {
        const input = document.getElementById(fieldId);
        if (input) {
            input.addEventListener('input', function() {
                let val = this.value.replace(/\D/g, '');
                if (val.startsWith('63')) val = val.substring(2);
                if (val.startsWith('0')) val = val.substring(1);
                if (val.length > 10) val = val.substring(0, 10);
                this.value = val;
                this.dataset.real = val;
            });
        }
    });

    // Add input listeners to edit form fields
    const editFields = [
        'edit_first_name', 'edit_last_name', 'edit_email', 
        'edit_contact', 'edit_address', 'edit_allergies', 'edit_conditions'
    ];
    
    editFields.forEach(fieldId => {
        const input = document.getElementById(fieldId);
        if (input) {
            input.addEventListener('input', function() {
                const newValue = this.value;
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
// Attach event listeners for real-time filtering & initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchPatient');
    const statusFilter = document.getElementById('filterStatus');
    
    if (searchInput) {
        searchInput.addEventListener('input', filterPatients);
        searchInput.addEventListener('keyup', filterPatients);
    }
    if (statusFilter) {
        statusFilter.addEventListener('change', filterPatients);
    }

    // Default to filtering today's visits on load
    filterPatients();
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
        
        // Auto-open patient details modal if requested or navigated from consultations
        if (typeof viewPatient === 'function' && typeof PATIENTS !== 'undefined') {
            let matchedKey = null;
            for (const k in PATIENTS) {
                if (PATIENTS[k].id == patientId || PATIENTS[k].patient_id == patientId) {
                    matchedKey = k;
                    break;
                }
            }
            if (matchedKey) {
                setTimeout(() => {
                    viewPatient(matchedKey);
                }, 450);
            }
        }

        if (!found) {
            console.warn('⚠️ Patient not found on current page:', patientId);
        }
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>