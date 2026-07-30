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
requireDepartmentAccess('health surveillance');

// Index Cases (Patient Zero or confirmed cases)
$indexCases = [
    [
        'id' => 'IC-001',
        'name' => 'Juan Dela Cruz',
        'age' => 45,
        'gender' => 'Male',
        'barangay' => 'San Jose',
        'disease' => 'Dengue',
        'date_confirmed' => '2024-01-15',
        'status' => 'Active',
        'risk_level' => 'High'
    ],
    [
        'id' => 'IC-002',
        'name' => 'Maria Santos',
        'age' => 32,
        'gender' => 'Female',
        'barangay' => 'Poblacion',
        'disease' => 'Influenza',
        'date_confirmed' => '2024-01-16',
        'status' => 'Recovered',
        'risk_level' => 'Medium'
    ],
    [
        'id' => 'IC-003',
        'name' => 'Pedro Reyes',
        'age' => 58,
        'gender' => 'Male',
        'barangay' => 'Riverside',
        'disease' => 'Leptospirosis',
        'date_confirmed' => '2024-01-17',
        'status' => 'Active',
        'risk_level' => 'High'
    ],
];

// Contacts for each index case
$contacts = [
    [
        'id' => 'CT-001',
        'index_case_id' => 'IC-001',
        'name' => 'Ana Dela Cruz',
        'age' => 42,
        'gender' => 'Female',
        'relationship' => 'Spouse',
        'address' => '123 San Jose St.',
        'barangay' => 'San Jose',
        'exposure_type' => 'Household',
        'exposure_date' => '2024-01-12',
        'last_contact_date' => '2024-01-15',
        'symptoms' => ['Fever', 'Headache'],
        'monitoring_status' => 'Active',
        'quarantine_status' => 'Quarantined',
        'quarantine_start' => '2024-01-15',
        'quarantine_end' => '2024-01-29',
        'risk_level' => 'High'
    ],
    [
        'id' => 'CT-002',
        'index_case_id' => 'IC-001',
        'name' => 'Jose Dela Cruz',
        'age' => 18,
        'gender' => 'Male',
        'relationship' => 'Son',
        'address' => '123 San Jose St.',
        'barangay' => 'San Jose',
        'exposure_type' => 'Household',
        'exposure_date' => '2024-01-10',
        'last_contact_date' => '2024-01-14',
        'symptoms' => [],
        'monitoring_status' => 'Cleared',
        'quarantine_status' => 'Completed',
        'quarantine_start' => '2024-01-15',
        'quarantine_end' => '2024-01-29',
        'risk_level' => 'Medium'
    ],
    [
        'id' => 'CT-003',
        'index_case_id' => 'IC-001',
        'name' => 'Maria Cruz',
        'age' => 70,
        'gender' => 'Female',
        'relationship' => 'Mother',
        'address' => '123 San Jose St.',
        'barangay' => 'San Jose',
        'exposure_type' => 'Household',
        'exposure_date' => '2024-01-08',
        'last_contact_date' => '2024-01-14',
        'symptoms' => ['Fever', 'Body aches', 'Fatigue'],
        'monitoring_status' => 'Active',
        'quarantine_status' => 'Quarantined',
        'quarantine_start' => '2024-01-15',
        'quarantine_end' => '2024-01-29',
        'risk_level' => 'High'
    ],
    [
        'id' => 'CT-004',
        'index_case_id' => 'IC-002',
        'name' => 'Carlos Santos',
        'age' => 35,
        'gender' => 'Male',
        'relationship' => 'Spouse',
        'address' => '456 Poblacion St.',
        'barangay' => 'Poblacion',
        'exposure_type' => 'Household',
        'exposure_date' => '2024-01-14',
        'last_contact_date' => '2024-01-16',
        'symptoms' => ['Cough', 'Sore throat'],
        'monitoring_status' => 'Active',
        'quarantine_status' => 'Quarantined',
        'quarantine_start' => '2024-01-16',
        'quarantine_end' => '2024-01-30',
        'risk_level' => 'Medium'
    ],
    [
        'id' => 'CT-005',
        'index_case_id' => 'IC-002',
        'name' => 'Lina Santos',
        'age' => 8,
        'gender' => 'Female',
        'relationship' => 'Daughter',
        'address' => '456 Poblacion St.',
        'barangay' => 'Poblacion',
        'exposure_type' => 'Household',
        'exposure_date' => '2024-01-13',
        'last_contact_date' => '2024-01-16',
        'symptoms' => [],
        'monitoring_status' => 'Monitoring',
        'quarantine_status' => 'Quarantined',
        'quarantine_start' => '2024-01-16',
        'quarantine_end' => '2024-01-30',
        'risk_level' => 'Low'
    ],
    [
        'id' => 'CT-006',
        'index_case_id' => 'IC-003',
        'name' => 'Ben Reyes',
        'age' => 60,
        'gender' => 'Male',
        'relationship' => 'Spouse',
        'address' => '789 Riverside St.',
        'barangay' => 'Riverside',
        'exposure_type' => 'Household',
        'exposure_date' => '2024-01-10',
        'last_contact_date' => '2024-01-17',
        'symptoms' => ['Fever', 'Muscle pain', 'Jaundice'],
        'monitoring_status' => 'Active',
        'quarantine_status' => 'Quarantined',
        'quarantine_start' => '2024-01-17',
        'quarantine_end' => '2024-01-31',
        'risk_level' => 'High'
    ],
];

// Statistics
$totalContacts = count($contacts);
$activeContacts = count(array_filter($contacts, function($c) { return $c['monitoring_status'] == 'Active'; }));
$quarantined = count(array_filter($contacts, function($c) { return $c['quarantine_status'] == 'Quarantined'; }));
$highRiskContacts = count(array_filter($contacts, function($c) { return $c['risk_level'] == 'High'; }));

$title = 'Contact Tracing';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Contact Tracing</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-location-dot"></i> Caloocan City
                </span>
                <?php if ($activeContacts > 0): ?>
                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold flex items-center gap-1 animate-pulse">
                    <i class="fa-solid fa-circle text-[6px]"></i> <?php echo $activeContacts; ?> Active Traces
                </span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">Contact identification, exposure assessment, monitoring & quarantine management</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="openModal('addContactModal')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Add Contact
            </button>
            <button onclick="refreshData()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- KPI CARDS - Contact Tracing Overview                       -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Contacts -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalContacts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Contacts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🔍 Traced</span>
                    <span class="text-[10px] text-slate-400">From <?php echo count($indexCases); ?> index cases</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Active Monitoring -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-eye text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $activeContacts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active Monitoring</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">📊 Under Observation</span>
                    <span class="text-[10px] text-slate-400">Daily checks</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Quarantined -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-red-200">
                        <i class="fa-solid fa-house-chimney-medical text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-red-600"><?php echo $quarantined; ?></p>
                        <p class="text-xs font-medium text-slate-500">Quarantined</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">🏠 Isolation</span>
                    <span class="text-[10px] text-slate-400">14-day protocol</span>
                </div>
            </div>
        </div>

        <!-- Card 4: High Risk -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $highRiskContacts; ?></p>
                        <p class="text-xs font-medium text-slate-500">High Risk Contacts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">⚠️ Priority</span>
                    <span class="text-[10px] text-slate-400">Immediate action</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- INDEX CASES TABLE                                           -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-doctor text-brand-medium"></i>
                Index Cases
                <span class="text-xs font-normal text-slate-400">(<?php echo count($indexCases); ?> cases)</span>
            </h3>
            <div class="flex items-center gap-2">
                <button onclick="filterIndexCases('all')" class="filter-btn-index active px-3 py-1 text-xs font-semibold rounded-full bg-brand-dark text-white hover:bg-brand-medium transition" id="index-all">All</button>
                <button onclick="filterIndexCases('Active')" class="filter-btn-index px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 hover:bg-red-200 transition" id="index-active">Active</button>
                <button onclick="filterIndexCases('Recovered')" class="filter-btn-index px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition" id="index-recovered">Recovered</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Patient</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Disease</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Barangay</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Date Confirmed</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Risk</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="indexTableBody">
                    <?php foreach ($indexCases as $case): ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition index-row" data-status="<?php echo $case['status']; ?>">
                        <td class="px-4 py-3 font-medium text-slate-700"><?php echo $case['id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <span class="font-medium text-slate-800"><?php echo $case['name']; ?></span>
                                <span class="text-xs text-slate-400 block"><?php echo $case['age']; ?> yrs, <?php echo $case['gender']; ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium"><?php echo $case['disease']; ?></span>
                        </td>
                        <td class="px-4 py-3 text-slate-600"><?php echo $case['barangay']; ?></td>
                        <td class="px-4 py-3 text-slate-600"><?php echo date('M d, Y', strtotime($case['date_confirmed'])); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 <?php echo $case['status'] == 'Active' ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-xs font-semibold">
                                <?php echo $case['status']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 <?php echo $case['risk_level'] == 'High' ? 'bg-rose-100 text-rose-700' : ($case['risk_level'] == 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'); ?> rounded-full text-xs font-semibold">
                                <?php echo $case['risk_level']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button onclick="viewContacts('<?php echo $case['id']; ?>')" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition">
                                <i class="fa-solid fa-eye"></i> View Contacts
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- CONTACTS TABLE                                              -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-address-book text-brand-medium"></i>
                Contact List
                <span class="text-xs font-normal text-slate-400">(<?php echo $totalContacts; ?> contacts)</span>
            </h3>
            <div class="flex items-center gap-3">
                <select id="riskFilter" onchange="filterContacts()" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="all">All Risk Levels</option>
                    <option value="High">High Risk</option>
                    <option value="Medium">Medium Risk</option>
                    <option value="Low">Low Risk</option>
                </select>
                <select id="statusFilter" onchange="filterContacts()" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="all">All Status</option>
                    <option value="Active">Active Monitoring</option>
                    <option value="Monitoring">Monitoring</option>
                    <option value="Cleared">Cleared</option>
                </select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Contact</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Index Case</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Exposure</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Symptoms</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Quarantine</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Risk</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="contactsTableBody">
                    <?php foreach ($contacts as $contact): ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition contact-row" data-risk="<?php echo $contact['risk_level']; ?>" data-status="<?php echo $contact['monitoring_status']; ?>">
                        <td class="px-4 py-3 font-medium text-slate-700"><?php echo $contact['id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <span class="font-medium text-slate-800"><?php echo $contact['name']; ?></span>
                                <span class="text-xs text-slate-400 block"><?php echo $contact['age']; ?> yrs, <?php echo $contact['gender']; ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $contact['index_case_id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <span class="text-xs font-medium text-slate-700"><?php echo $contact['exposure_type']; ?></span>
                                <span class="text-xs text-slate-400 block"><?php echo date('M d', strtotime($contact['exposure_date'])); ?></span>
                                <span class="text-xs text-slate-400 block"><?php echo $contact['relationship']; ?></span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <?php if (count($contact['symptoms']) > 0): ?>
                                <div class="flex flex-wrap gap-1">
                                    <?php foreach ($contact['symptoms'] as $symptom): ?>
                                    <span class="px-2 py-0.5 bg-red-50 text-red-600 rounded-full text-[10px]"><?php echo $symptom; ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">No symptoms</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <span class="px-2 py-1 <?php echo $contact['quarantine_status'] == 'Quarantined' ? 'bg-amber-100 text-amber-700' : ($contact['quarantine_status'] == 'Completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'); ?> rounded-full text-[10px] font-semibold">
                                    <?php echo $contact['quarantine_status']; ?>
                                </span>
                                <span class="text-[10px] text-slate-400 block">
                                    <?php echo date('M d', strtotime($contact['quarantine_start'])); ?> - <?php echo date('M d', strtotime($contact['quarantine_end'])); ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 <?php echo $contact['risk_level'] == 'High' ? 'bg-rose-100 text-rose-700' : ($contact['risk_level'] == 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'); ?> rounded-full text-xs font-semibold">
                                <?php echo $contact['risk_level']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                <button onclick="updateMonitoring('<?php echo $contact['id']; ?>')" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="viewContactDetails('<?php echo $contact['id']; ?>')" class="text-blue-500 hover:text-blue-700 text-xs font-medium transition">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- QUARANTINE MANAGEMENT OVERVIEW                             -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-house-chimney-medical text-brand-medium"></i>
                Quarantine Management
                <span class="text-xs font-normal text-slate-400">(<?php echo $quarantined; ?> currently quarantined)</span>
            </h3>
            <button onclick="generateQuarantineReport()" class="px-3 py-1.5 bg-brand-light text-brand-dark rounded-lg hover:bg-brand-dark hover:text-white transition text-xs font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-file-pdf"></i> Generate Report
            </button>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Quarantine Progress -->
                <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-slate-500">Active Quarantine</span>
                        <span class="text-sm font-bold text-amber-600"><?php echo $quarantined; ?></span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-amber-500 h-2 rounded-full" style="width: <?php echo ($quarantined / $totalContacts) * 100; ?>%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1"><?php echo round(($quarantined / $totalContacts) * 100); ?>% of contacts</p>
                </div>
                
                <!-- Completed Quarantine -->
                <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-slate-500">Completed</span>
                        <span class="text-sm font-bold text-emerald-600">
                            <?php echo count(array_filter($contacts, function($c) { return $c['quarantine_status'] == 'Completed'; })); ?>
                        </span>
                    </div>
                    <div class="w-full bg-slate-200 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full" style="width: <?php echo (count(array_filter($contacts, function($c) { return $c['quarantine_status'] == 'Completed'; })) / $totalContacts) * 100; ?>%"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Successfully completed quarantine</p>
                </div>
                
                <!-- Quarantine Facilities -->
                <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-slate-500">Facilities</span>
                        <span class="text-sm font-bold text-brand-dark">3</span>
                    </div>
                    <div class="space-y-1">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-600">Caloocan City Hospital</span>
                            <span class="text-brand-dark font-medium">12</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-600">Barangay Health Center</span>
                            <span class="text-brand-dark font-medium">8</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-600">Isolation Facility</span>
                            <span class="text-brand-dark font-medium">5</span>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Releases -->
                <div class="border border-slate-200 rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-medium text-slate-500">Upcoming Releases</span>
                        <span class="text-sm font-bold text-blue-600">
                            <?php echo count(array_filter($contacts, function($c) { 
                                return $c['quarantine_status'] == 'Quarantined' && strtotime($c['quarantine_end']) < strtotime('+3 days'); 
                            })); ?>
                        </span>
                    </div>
                    <div class="space-y-1">
                        <?php 
                        $upcoming = array_filter($contacts, function($c) { 
                            return $c['quarantine_status'] == 'Quarantined' && strtotime($c['quarantine_end']) < strtotime('+3 days'); 
                        });
                        foreach (array_slice($upcoming, 0, 3) as $c): 
                        ?>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-600"><?php echo $c['name']; ?></span>
                            <span class="text-blue-600"><?php echo date('M d', strtotime($c['quarantine_end'])); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD CONTACT MODAL                                           -->
<!-- ============================================================ -->
<div id="addContactModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-brand-medium"></i>
                Add New Contact
            </h3>
            <button onclick="closeModal('addContactModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form onsubmit="saveContact(event)">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Index Case ID</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="IC-001">IC-001 - Juan Dela Cruz</option>
                            <option value="IC-002">IC-002 - Maria Santos</option>
                            <option value="IC-003">IC-003 - Pedro Reyes</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Full Name</label>
                            <input type="text" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Enter name" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Age</label>
                            <input type="number" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Age" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Relationship</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option>Spouse</option>
                            <option>Child</option>
                            <option>Parent</option>
                            <option>Sibling</option>
                            <option>Other Relative</option>
                            <option>Neighbor</option>
                            <option>Colleague</option>
                            <option>Friend</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address / Barangay</label>
                        <input type="text" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Enter address" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Exposure Type</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option>Household</option>
                            <option>Close Contact</option>
                            <option>Workplace</option>
                            <option>Social Gathering</option>
                            <option>Healthcare</option>
                            <option>Community</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Symptoms</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Fever
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Cough
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Headache
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Body aches
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Sore throat
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Fatigue
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Risk Level</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="High">High Risk</option>
                            <option value="Medium">Medium Risk</option>
                            <option value="Low">Low Risk</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeModal('addContactModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-save mr-1.5"></i> Save Contact
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- CONTACT DETAILS MODAL                                       -->
<!-- ============================================================ -->
<div id="contactDetailsModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user text-brand-medium"></i>
                Contact Details
            </h3>
            <button onclick="closeModal('contactDetailsModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6" id="contactDetailsContent">
            <!-- Dynamic content loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<script>
    // ============================================================
    // FILTER INDEX CASES
    // ============================================================
    function filterIndexCases(status) {
        document.querySelectorAll('.filter-btn-index').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-dark', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700');
        });
        
        if (status === 'all') {
            document.getElementById('index-all').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Active') {
            document.getElementById('index-active').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Recovered') {
            document.getElementById('index-recovered').classList.add('active', 'bg-brand-dark', 'text-white');
        }
        
        const rows = document.querySelectorAll('.index-row');
        rows.forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // ============================================================
    // FILTER CONTACTS
    // ============================================================
    function filterContacts() {
        const riskFilter = document.getElementById('riskFilter').value;
        const statusFilter = document.getElementById('statusFilter').value;
        
        const rows = document.querySelectorAll('.contact-row');
        rows.forEach(row => {
            const risk = row.dataset.risk;
            const status = row.dataset.status;
            
            let show = true;
            if (riskFilter !== 'all' && risk !== riskFilter) show = false;
            if (statusFilter !== 'all' && status !== statusFilter) show = false;
            
            row.style.display = show ? 'table-row' : 'none';
        });
    }

    // ============================================================
    // VIEW CONTACTS
    // ============================================================
    function viewContacts(indexId) {
        showToast('🔍 Showing contacts for ' + indexId, 'info');
        // In production, this would filter the contacts table
    }

    // ============================================================
    // UPDATE MONITORING
    // ============================================================
    function updateMonitoring(contactId) {
        showToast('📊 Updating monitoring for ' + contactId, 'info');
        // In production, this would open a monitoring update modal
    }

    // ============================================================
    // VIEW CONTACT DETAILS
    // ============================================================
    function viewContactDetails(contactId) {
        const content = document.getElementById('contactDetailsContent');
        // Find contact data
        const contact = <?php echo json_encode($contacts); ?>.find(c => c.id === contactId);
        
        if (contact) {
            content.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-3 bg-slate-50 rounded-lg">
                        <div class="w-16 h-16 bg-brand-light rounded-full flex items-center justify-center text-brand-dark text-2xl font-bold">
                            ${contact.name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="font-bold text-slate-800">${contact.name}</h4>
                            <p class="text-xs text-slate-500">${contact.age} yrs, ${contact.gender}</p>
                            <p class="text-xs text-slate-500">${contact.id}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-slate-500">Relationship</p>
                            <p class="text-sm font-medium text-slate-700">${contact.relationship}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Index Case</p>
                            <p class="text-sm font-medium text-slate-700">${contact.index_case_id}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Exposure Type</p>
                            <p class="text-sm font-medium text-slate-700">${contact.exposure_type}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Exposure Date</p>
                            <p class="text-sm font-medium text-slate-700">${new Date(contact.exposure_date).toLocaleDateString()}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Barangay</p>
                            <p class="text-sm font-medium text-slate-700">${contact.barangay}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Risk Level</p>
                            <span class="px-2 py-1 ${contact.risk_level === 'High' ? 'bg-rose-100 text-rose-700' : (contact.risk_level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')} rounded-full text-xs font-semibold">
                                ${contact.risk_level}
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Symptoms</p>
                        ${contact.symptoms.length > 0 ? 
                            contact.symptoms.map(s => `<span class="px-2 py-1 bg-red-50 text-red-600 rounded-full text-xs inline-block mr-1">${s}</span>`).join('') :
                            '<span class="text-sm text-slate-400">No symptoms reported</span>'
                        }
                    </div>
                    
                    <div class="border-t border-slate-100 pt-3">
                        <p class="text-xs text-slate-500 mb-1">Quarantine Status</p>
                        <div class="flex justify-between items-center">
                            <span class="px-2 py-1 ${contact.quarantine_status === 'Quarantined' ? 'bg-amber-100 text-amber-700' : (contact.quarantine_status === 'Completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700')} rounded-full text-xs font-semibold">
                                ${contact.quarantine_status}
                            </span>
                            <span class="text-xs text-slate-500">
                                ${new Date(contact.quarantine_start).toLocaleDateString()} - ${new Date(contact.quarantine_end).toLocaleDateString()}
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Monitoring Status</p>
                        <span class="px-2 py-1 ${contact.monitoring_status === 'Active' ? 'bg-amber-100 text-amber-700' : (contact.monitoring_status === 'Monitoring' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700')} rounded-full text-xs font-semibold">
                            ${contact.monitoring_status}
                        </span>
                    </div>
                    
                    <div class="flex gap-2 pt-2">
                        <button onclick="closeModal('contactDetailsModal')" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                            Close
                        </button>
                        <button onclick="updateMonitoring('${contact.id}')" class="flex-1 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                            <i class="fa-solid fa-pen"></i> Update Status
                        </button>
                    </div>
                </div>
            `;
        }
        
        openModal('contactDetailsModal');
    }

    // ============================================================
    // QUARANTINE REPORT
    // ============================================================
    function generateQuarantineReport() {
        showToast('📄 Generating quarantine report...', 'info');
        setTimeout(() => {
            showToast('✅ Quarantine report generated!', 'success');
        }, 1500);
    }

    // ============================================================
    // SAVE CONTACT
    // ============================================================
    function saveContact(e) {
        e.preventDefault();
        showToast('✅ Contact added successfully!', 'success');
        closeModal('addContactModal');
    }

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
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('🔄 Refreshing data...', 'info');
        setTimeout(() => {
            showToast('✅ Data refreshed!', 'success');
        }, 1000);
    }

    // ============================================================
    // TOAST
    // ============================================================
    let toastTimer = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-rose-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        t.className = `fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ${colors[type] || colors.success}`;
        t.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.add('hidden'), 3000);
    }

    // ============================================================
    // ESC KEY TO CLOSE MODALS
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });
</script>

<style>
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .filter-btn-index.active {
        background: #0B4F4A !important;
        color: white !important;
    }
    .filter-btn-index:not(.active):hover {
        opacity: 0.8;
    }
    
    .contact-row, .index-row {
        transition: background-color 0.2s ease;
    }
</style>

<?php include_once '../../includes/footer.php'; ?>