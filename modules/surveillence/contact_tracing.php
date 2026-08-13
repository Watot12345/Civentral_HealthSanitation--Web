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

require_once __DIR__ . '/../../app/Models/SurveillanceContact.php';
require_once __DIR__ . '/../../app/Models/SurveillanceCase.php';

try {
    $contactModel = new SurveillanceContact();
    $caseModel = new SurveillanceCase();

    $rawIndexCases = $caseModel->getIndexCases();
    $rawContacts = $contactModel->all();

    $indexCases = array_map(function($ic) {
        return [
            'id' => $ic['index_code'] ?? ('IC-' . $ic['id']),
            'db_id' => (int) $ic['id'],
            'name' => $ic['name'] ?? 'Unknown',
            'age' => (int) ($ic['age'] ?? 0),
            'gender' => $ic['gender'] ?? 'Unknown',
            'barangay' => $ic['barangay'] ?? '',
            'disease' => $ic['disease'] ?? 'Unknown',
            'date_confirmed' => $ic['date_confirmed'] ?? date('Y-m-d'),
            'status' => $ic['status'] ?? 'Isolated',
            'risk_level' => $ic['risk_level'] ?? 'High'
        ];
    }, $rawIndexCases);

    if (empty($indexCases)) {
        $indexCases = [
            ['id' => 'IC-001', 'db_id' => 1, 'name' => 'Juan Dela Cruz', 'age' => 45, 'gender' => 'Male', 'barangay' => 'San Jose', 'disease' => 'Dengue', 'date_confirmed' => '2026-07-20', 'status' => 'Isolated', 'risk_level' => 'High']
        ];
    }

    $contacts = array_map(function($c) {
        $symptomsRaw = $c['symptoms'] ?? '';
        $symptomsArr = is_array($symptomsRaw) ? $symptomsRaw : array_map('trim', explode(',', (string)$symptomsRaw));
        if (empty($symptomsArr) || (count($symptomsArr) === 1 && $symptomsArr[0] === '')) {
            $symptomsArr = [];
        }
        return [
            'id' => $c['contact_code'] ?? ('CT-' . $c['id']),
            'db_id' => (int) $c['id'],
            'index_case_id' => 'IC-00' . ($c['index_case_id'] ?? 1),
            'name' => $c['name'] ?? 'Anonymous Contact',
            'age' => (int) ($c['age'] ?? 0),
            'gender' => $c['gender'] ?? 'Unknown',
            'relationship' => $c['relationship'] ?? 'Relative',
            'address' => $c['address'] ?? '',
            'barangay' => $c['barangay'] ?? '',
            'exposure_type' => $c['exposure_type'] ?? 'Direct Contact',
            'exposure_date' => $c['exposure_date'] ?? date('Y-m-d'),
            'last_contact_date' => $c['last_contact_date'] ?? date('Y-m-d'),
            'symptoms' => $symptomsArr,
            'monitoring_status' => $c['monitoring_status'] ?? 'Under Monitoring',
            'quarantine_status' => $c['quarantine_status'] ?? 'Quarantined',
            'quarantine_start' => $c['quarantine_start'] ?? date('Y-m-d'),
            'quarantine_end' => $c['quarantine_end'] ?? date('Y-m-d', strtotime('+14 days')),
            'risk_level' => $c['risk_level'] ?? 'Medium'
        ];
    }, $rawContacts);

} catch (Throwable $e) {
    error_log("Contact tracing fetch error: " . $e->getMessage());
    $indexCases = [];
    $contacts = [];
}

// Statistics with divide-by-zero guards
$totalContacts = count($contacts);
$activeContacts = count(array_filter($contacts, function($c) { return strcasecmp($c['monitoring_status'], 'Cleared') !== 0; }));
$quarantined = count(array_filter($contacts, function($c) { return strcasecmp($c['quarantine_status'], 'Quarantined') === 0; }));
$highRiskContacts = count(array_filter($contacts, function($c) { return strcasecmp($c['risk_level'], 'High') === 0; }));
$quarantinedPercentage = $totalContacts > 0 ? round(($quarantined / $totalContacts) * 100) : 0;


$title = 'Contact Tracing';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header (screen only) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 screen-only">
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

    <!-- KPI CARDS (screen only) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 screen-only">
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
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-search mr-1"></i>Traced</span>
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
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-chart-line mr-1"></i>Under Observation</span>
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
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-house-circle-exclamation mr-1"></i>Isolation</span>
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
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-bolt mr-1"></i>Priority</span>
                    <span class="text-[10px] text-slate-400">Immediate action</span>
                </div>
            </div>
        </div>
    </div>

    <!-- INDEX CASES TABLE (screen only) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 screen-only">
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
                                <span class="text-xs text-slate-400 block"><?php echo sprintf("%02d", $case['age']); ?> yrs, <?php echo $case['gender']; ?></span>
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

    <!-- CONTACTS TABLE (screen only) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6 screen-only">
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
                                <span class="text-xs text-slate-400 block"><?php echo sprintf("%02d", $contact['age']); ?> yrs, <?php echo $contact['gender']; ?></span>
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
                                <button onclick="openEditMonitoringModal('<?php echo $contact['id']; ?>')" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition">
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

    <!-- QUARANTINE MANAGEMENT OVERVIEW (screen only) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden screen-only">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-house-chimney-medical text-brand-medium"></i>
                Quarantine Management
                <span class="text-xs font-normal text-slate-400">(<?php echo $quarantined; ?> currently quarantined)</span>
            </h3>
            <button onclick="openModal('reportModal')" class="px-3 py-1.5 bg-brand-light text-brand-dark rounded-lg hover:bg-brand-dark hover:text-white transition text-xs font-semibold flex items-center gap-1.5">
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
<!--  REPORT CONTENT – Visible only when printing / exporting    -->
<!-- ============================================================ -->
<div id="reportContent" class="report-content" style="display:none;">
    <div class="report-wrapper" style="max-width:1100px; margin:0 auto; padding:40px 28px; font-family:'Times New Roman',serif;">

        <!-- ── FORMAL HEADER ── -->
        <div style="text-align:center; margin-bottom:28px;">
            <img src="<?= site_url('assets/images/logo.png') ?>" alt="Logo" width="64" height="64" style="width:64px; height:64px; margin:0 auto 12px; display:block; object-fit:contain;">
            <h1 style="font-size:17px; font-weight:900; color:#1a1a1a; letter-spacing:1.5px;
                       text-transform:uppercase; margin:0 0 6px; font-family:'Times New Roman',serif;">
                Health Sanitation Management Caloocan
            </h1>
            <p style="font-size:14px; font-weight:700; color:#14807A; margin:0 0 14px;
                      font-family:'Times New Roman',serif; letter-spacing:0.5px;">
                Contact Tracing
            </p>
            <hr style="border:none; border-top:1.5px solid #1a1a1a; margin:0 0 18px;">
            <p style="font-size:12px; color:#555; margin:0; font-family:'Times New Roman',serif;">
                Generated on: <?php echo date('F d, Y h:i:s A'); ?>
            </p>
        </div>

        <!-- Summary -->
        <div style="margin-bottom:25px; font-size:14px; font-family:'Times New Roman',serif;">
            <p style="margin:4px 0;"><strong>Total Contacts:</strong> <?php echo $totalContacts; ?></p>
            <p style="margin:4px 0;"><strong>Active Monitoring:</strong> <?php echo $activeContacts; ?></p>
            <p style="margin:4px 0;"><strong>Quarantined:</strong> <?php echo $quarantined; ?></p>
            <p style="margin:4px 0;"><strong>High Risk Contacts:</strong> <?php echo $highRiskContacts; ?></p>
        </div>

        <!-- Index Cases Table -->
        <h3 style="font-size:14pt; font-weight:700; color:#0B4F4A; margin:28px 0 8px;
                   border-bottom:1px solid #aaa; padding-bottom:4px; font-family:'Times New Roman',serif;">
            Index Cases
        </h3>
        <table style="width:100%; border-collapse:collapse; font-size:9pt; margin-bottom:28px; font-family:'Times New Roman',serif;">
            <thead>
                <tr style="background:#0B4F4A; color:white;">
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">ID</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Patient</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Disease</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Barangay</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Date Confirmed</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Status</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Risk</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($indexCases as $i => $case): ?>
                <tr style="background: <?php echo $i % 2 === 1 ? '#f5fafa' : '#ffffff'; ?>;">
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $case['id']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $case['name']; ?> (<?php echo sprintf("%02d", $case['age']); ?> yrs, <?php echo $case['gender']; ?>)</td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $case['disease']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $case['barangay']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo date('M d, Y', strtotime($case['date_confirmed'])); ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $case['status']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $case['risk_level']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Contacts Table -->
        <h3 style="font-size:14pt; font-weight:700; color:#0B4F4A; margin:28px 0 8px;
                   border-bottom:1px solid #aaa; padding-bottom:4px; font-family:'Times New Roman',serif;">
            Contacts
        </h3>
        <table style="width:100%; border-collapse:collapse; font-size:9pt; margin-bottom:28px; font-family:'Times New Roman',serif;">
            <thead>
                <tr style="background:#0B4F4A; color:white;">
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">ID</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Name</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Age</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Gender</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Index Case</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Relationship</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Exposure</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Exposure Date</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Barangay</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Symptoms</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Risk</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Monitoring</th>
                    <th style="padding:7px 9px; text-align:left; border:1px solid #0B4F4A;">Quarantine</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $i => $c): ?>
                <tr style="background: <?php echo $i % 2 === 1 ? '#f5fafa' : '#ffffff'; ?>;">
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['id']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['name']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo sprintf("%02d", $c['age']); ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['gender']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['index_case_id']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['relationship']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['exposure_type']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo date('M d, Y', strtotime($c['exposure_date'])); ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['barangay']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo count($c['symptoms']) > 0 ? implode(', ', $c['symptoms']) : 'None'; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['risk_level']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['monitoring_status']; ?></td>
                    <td style="padding:5px 9px; border:1px solid #cccccc;"><?php echo $c['quarantine_status']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="text-align:center; font-size:9pt; color:#888; margin-top:28px;
                    border-top:1px solid #ccc; padding-top:12px; font-family:'Times New Roman',serif;">
            This is a computer-generated report. For official use only.
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODALS                                                       -->
<!-- ============================================================ -->
<!-- Add Contact Modal -->
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
                            <input type="number" min="0" max="99" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Age" required oninput="this.value = this.value.slice(0, 2)">
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

<!-- Contact Details Modal -->
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
            <!-- Dynamic content -->
        </div>
    </div>
</div>

<!-- Edit Monitoring Modal -->
<div id="editMonitoringModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-brand-medium"></i>
                Update Monitoring
            </h3>
            <button onclick="closeModal('editMonitoringModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6" id="editMonitoringContent">
            <!-- Dynamic content -->
        </div>
    </div>
</div>

<!-- View Contacts Modal -->
<div id="viewContactsModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-users text-brand-medium"></i>
                Contacts for <span id="viewContactsIndexId" class="text-brand-dark">IC-001</span>
            </h3>
            <button onclick="closeModal('viewContactsModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6" id="viewContactsContent">
            <!-- Dynamic content -->
        </div>
    </div>
</div>

<!-- Report Options Modal -->
<div id="reportModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-export text-brand-medium"></i>
                Generate Report
            </h3>
            <button onclick="closeModal('reportModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <p class="text-sm text-slate-500">Choose the output format for the report.</p>
            <button onclick="generateReport('pdf')" class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-sm font-semibold text-slate-700">
                <i class="fa-solid fa-file-pdf text-red-500 text-lg"></i> PDF (Print)
            </button>
            <button onclick="generateReport('docx')" class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-sm font-semibold text-slate-700">
                <i class="fa-solid fa-file-word text-blue-600 text-lg"></i> DOCX (Word)
            </button>
            <button onclick="generateReport('excel')" class="w-full flex items-center gap-3 px-4 py-3 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition text-sm font-semibold text-slate-700">
                <i class="fa-solid fa-file-excel text-green-600 text-lg"></i> Excel (CSV)
            </button>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 flex justify-end">
            <button onclick="closeModal('reportModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                Cancel
            </button>
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
    // VIEW CONTACTS (by Index Case)
    // ============================================================
    function viewContacts(indexId) {
        const indexCases = <?php echo json_encode($indexCases); ?>;
        const indexCase = indexCases.find(ic => ic.id === indexId);
        if (!indexCase) {
            showToast('Index case not found', 'danger');
            return;
        }
        const allContacts = <?php echo json_encode($contacts); ?>;
        const relatedContacts = allContacts.filter(c => c.index_case_id === indexId);
        document.getElementById('viewContactsIndexId').textContent = indexId;
        let html = `
            <div class="mb-4">
                <p><strong>Patient:</strong> ${indexCase.name}</p>
                <p><strong>Disease:</strong> ${indexCase.disease}</p>
                <p><strong>Barangay:</strong> ${indexCase.barangay}</p>
            </div>
            <hr class="my-4">
            <h4 class="font-semibold text-slate-700 mb-3">Contacts (${relatedContacts.length})</h4>
        `;
        if (relatedContacts.length === 0) {
            html += `<p class="text-sm text-slate-500">No contacts recorded for this index case.</p>`;
        } else {
            html += `<div class="overflow-x-auto"><table class="w-full text-sm border border-slate-200 rounded-lg">
                <thead class="bg-slate-50"><tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">ID</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Name</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Age</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Risk</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Quarantine</th>
                </tr></thead><tbody>`;
            relatedContacts.forEach(c => {
                html += `<tr class="border-t border-slate-100">
                    <td class="px-3 py-2">${c.id}</td>
                    <td class="px-3 py-2">${c.name}</td>
                    <td class="px-3 py-2">${String(c.age).padStart(2, '0')}</td>
                    <td class="px-3 py-2"><span class="px-2 py-0.5 ${c.risk_level === 'High' ? 'bg-rose-100 text-rose-700' : (c.risk_level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')} rounded-full text-xs">${c.risk_level}</span></td>
                    <td class="px-3 py-2">${c.quarantine_status}</td>
                </tr>`;
            });
            html += `</tbody></table></div>`;
        }
        document.getElementById('viewContactsContent').innerHTML = html;
        openModal('viewContactsModal');
    }

    // ============================================================
    // OPEN EDIT MONITORING MODAL
    // ============================================================
    function openEditMonitoringModal(contactId) {
        const allContacts = <?php echo json_encode($contacts); ?>;
        const contact = allContacts.find(c => c.id === contactId);
        if (!contact) {
            showToast('Contact not found', 'danger');
            return;
        }
        const content = document.getElementById('editMonitoringContent');
        content.innerHTML = `
            <form onsubmit="updateMonitoringSubmit(event, '${contact.id}')">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                        <p class="text-sm font-medium text-slate-700">${contact.name} (${contact.id})</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Monitoring Status</label>
                        <select id="edit_monitoring_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Active" ${contact.monitoring_status === 'Active' ? 'selected' : ''}>Active</option>
                            <option value="Monitoring" ${contact.monitoring_status === 'Monitoring' ? 'selected' : ''}>Monitoring</option>
                            <option value="Cleared" ${contact.monitoring_status === 'Cleared' ? 'selected' : ''}>Cleared</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Quarantine Status</label>
                        <select id="edit_quarantine_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Quarantined" ${contact.quarantine_status === 'Quarantined' ? 'selected' : ''}>Quarantined</option>
                            <option value="Completed" ${contact.quarantine_status === 'Completed' ? 'selected' : ''}>Completed</option>
                            <option value="Released" ${contact.quarantine_status === 'Released' ? 'selected' : ''}>Released</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Risk Level</label>
                        <select id="edit_risk_level" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="High" ${contact.risk_level === 'High' ? 'selected' : ''}>High</option>
                            <option value="Medium" ${contact.risk_level === 'Medium' ? 'selected' : ''}>Medium</option>
                            <option value="Low" ${contact.risk_level === 'Low' ? 'selected' : ''}>Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Quarantine End Date</label>
                        <input type="date" id="edit_quarantine_end" value="${contact.quarantine_end}" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeModal('editMonitoringModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-save mr-1.5"></i> Update
                    </button>
                </div>
            </form>
        `;
        openModal('editMonitoringModal');
    }

    // ============================================================
    // UPDATE MONITORING SUBMIT
    // ============================================================
    function updateMonitoringSubmit(e, contactId) {
        e.preventDefault();
        showToast(`✅ Monitoring updated for ${contactId}`, 'success');
        closeModal('editMonitoringModal');
        setTimeout(() => window.location.reload(), 1000);
    }

    // ============================================================
    // VIEW CONTACT DETAILS
    // ============================================================
    function viewContactDetails(contactId) {
        const content = document.getElementById('contactDetailsContent');
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
                            <p class="text-xs text-slate-500">${String(contact.age).padStart(2, '0')} yrs, ${contact.gender}</p>
                            <p class="text-xs text-slate-500">${contact.id}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-slate-500">Relationship</p><p class="text-sm font-medium text-slate-700">${contact.relationship}</p></div>
                        <div><p class="text-xs text-slate-500">Index Case</p><p class="text-sm font-medium text-slate-700">${contact.index_case_id}</p></div>
                        <div><p class="text-xs text-slate-500">Exposure Type</p><p class="text-sm font-medium text-slate-700">${contact.exposure_type}</p></div>
                        <div><p class="text-xs text-slate-500">Exposure Date</p><p class="text-sm font-medium text-slate-700">${new Date(contact.exposure_date).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-500">Barangay</p><p class="text-sm font-medium text-slate-700">${contact.barangay}</p></div>
                        <div><p class="text-xs text-slate-500">Risk Level</p><span class="px-2 py-1 ${contact.risk_level === 'High' ? 'bg-rose-100 text-rose-700' : (contact.risk_level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')} rounded-full text-xs font-semibold">${contact.risk_level}</span></div>
                    </div>
                    <div><p class="text-xs text-slate-500 mb-1">Symptoms</p>${contact.symptoms.length > 0 ? contact.symptoms.map(s => `<span class="px-2 py-1 bg-red-50 text-red-600 rounded-full text-xs inline-block mr-1">${s}</span>`).join('') : '<span class="text-sm text-slate-400">No symptoms reported</span>'}</div>
                    <div class="border-t border-slate-100 pt-3">
                        <p class="text-xs text-slate-500 mb-1">Quarantine Status</p>
                        <div class="flex justify-between items-center">
                            <span class="px-2 py-1 ${contact.quarantine_status === 'Quarantined' ? 'bg-amber-100 text-amber-700' : (contact.quarantine_status === 'Completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700')} rounded-full text-xs font-semibold">${contact.quarantine_status}</span>
                            <span class="text-xs text-slate-500">${new Date(contact.quarantine_start).toLocaleDateString()} - ${new Date(contact.quarantine_end).toLocaleDateString()}</span>
                        </div>
                    </div>
                    <div><p class="text-xs text-slate-500 mb-1">Monitoring Status</p><span class="px-2 py-1 ${contact.monitoring_status === 'Active' ? 'bg-amber-100 text-amber-700' : (contact.monitoring_status === 'Monitoring' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700')} rounded-full text-xs font-semibold">${contact.monitoring_status}</span></div>
                    <div class="flex gap-2 pt-2">
                        <button onclick="closeModal('contactDetailsModal')" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <button onclick="openEditMonitoringModal('${contact.id}')" class="flex-1 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-pen"></i> Update Status</button>
                    </div>
                </div>
            `;
        }
        openModal('contactDetailsModal');
    }

    // ============================================================
    // GENERATE REPORT
    // ============================================================
    function generateReport(format) {
        closeModal('reportModal');
        if (format === 'pdf') {
            const reportHTML = buildReportHTML();
            const printWindow = window.open('', '_blank', 'width=1000,height=800');
            printWindow.document.open();
            printWindow.document.write(reportHTML);
            printWindow.document.close();

            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 250);
            };
            showToast('PDF report opened in a new tab. Choose "Save as PDF" in the print dialog.', 'info');
        } else if (format === 'docx') {
            const reportHTML = buildReportHTML();
            const blob = new Blob([reportHTML], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Contact_Tracing_Report.doc';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('DOCX report downloaded!', 'success');
        } else if (format === 'excel') {
            const contacts = <?php echo json_encode($contacts); ?>;
            let csv = "Contact ID,Name,Age,Gender,Index Case,Relationship,Exposure Type,Exposure Date,Barangay,Symptoms,Risk Level,Monitoring Status,Quarantine Status\n";
            contacts.forEach(c => {
                const symptoms = c.symptoms.join('; ') || 'None';
                csv += `${c.id},"${c.name}",${String(c.age).padStart(2, '0')},${c.gender},${c.index_case_id},"${c.relationship}","${c.exposure_type}",${c.exposure_date},"${c.barangay}","${symptoms}",${c.risk_level},${c.monitoring_status},${c.quarantine_status}\n`;
            });
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'Contact_Tracing_Report.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('CSV (Excel) report downloaded!', 'success');
        }
    }

    // ============================================================
    // BUILD REPORT HTML for DOCX
    // ============================================================
    function buildReportHTML() {
        const indexCases = <?php echo json_encode($indexCases); ?>;
        const contacts = <?php echo json_encode($contacts); ?>;
        const totalContacts = contacts.length;
        const activeContacts = contacts.filter(c => c.monitoring_status !== 'Cleared').length;
        const quarantined = contacts.filter(c => c.quarantine_status === 'Quarantined').length;
        const highRiskContacts = contacts.filter(c => c.risk_level === 'High').length;

        let indexRows = indexCases.map((c, i) => `
            <tr style="background:${i % 2 === 1 ? '#f5fafa' : '#ffffff'};">
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.id}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.name} (${String(c.age).padStart(2, '0')} yrs, ${c.gender})</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.disease}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.barangay}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${new Date(c.date_confirmed).toLocaleDateString()}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.status}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.risk_level}</td>
            </tr>
        `).join('');

        let contactRows = contacts.map((c, i) => `
            <tr style="background:${i % 2 === 1 ? '#f5fafa' : '#ffffff'};">
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.id}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.name}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${String(c.age).padStart(2, '0')}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.gender}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.index_case_id}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.relationship}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.exposure_type}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${new Date(c.exposure_date).toLocaleDateString()}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.barangay}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.symptoms.join('; ') || 'None'}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.risk_level}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.monitoring_status}</td>
                <td style="padding:5px 9px; border:1px solid #cccccc;">${c.quarantine_status}</td>
            </tr>
        `).join('');

        return `
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <title>Contact Tracing Report</title>
            <!--[if gte mso 9]>
            <xml>
                <w:WordDocument>
                    <w:View>Print</w:View>
                    <w:Zoom>90</w:Zoom>
                    <w:DoNotOptimizeForBrowser/>
                </w:WordDocument>
            </xml>
            <![endif]-->
            <style>
                /* Landscape page, sized to fit the 13-column Contacts table */
                @page WordSection1 {
                    size: 297mm 210mm;
                    mso-page-orientation: landscape;
                    margin: 15mm 12mm;
                }
                div.WordSection1 { page: WordSection1; }
                body { font-family: 'Times New Roman', serif; margin: 0; background: #fff; }
                .report-wrapper { max-width: 1100px; margin: 0 auto; }
                .header { text-align: center; margin-bottom: 28px; }
                .logo-img {
                    width: 64px; height: 64px; margin: 0 auto 12px;
                    display: block; object-fit: contain;
                }
                h1 {
                    font-size: 17px; font-weight: 900; color: #1a1a1a;
                    letter-spacing: 1.5px; text-transform: uppercase;
                    margin: 0 0 6px; font-family: 'Times New Roman', serif;
                }
                .report-subtitle {
                    font-size: 14px; font-weight: 700; color: #14807A;
                    margin: 0 0 14px; font-family: 'Times New Roman', serif;
                    letter-spacing: 0.5px;
                }
                .header-divider {
                    border: none; border-top: 1.5px solid #1a1a1a; margin: 0 0 18px;
                }
                .generated-on { font-size: 12px; color: #555; margin: 0; }
                .summary { margin-bottom: 25px; font-size: 14px; }
                .summary p { margin: 4px 0; }
                h3 {
                    font-size: 14pt; font-weight: 700; color: #0B4F4A;
                    margin: 28px 0 8px; border-bottom: 1px solid #aaa;
                    padding-bottom: 4px; font-family: 'Times New Roman', serif;
                }
                table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 28px; }
                th {
                    background-color: #0B4F4A; color: #ffffff;
                    padding: 7px 9px; text-align: left;
                    border: 1px solid #0B4F4A; font-size: 8.5pt; letter-spacing: 0.3px;
                }
                td { padding: 5px 9px; border: 1px solid #cccccc; vertical-align: top; }
                .footer {
                    text-align: center; font-size: 9pt; color: #888;
                    margin-top: 28px; border-top: 1px solid #ccc; padding-top: 12px;
                }
            </style>
        </head>
        <body>
        <div class="WordSection1">
        <div class="report-wrapper">
            <div class="header">
                <img class="logo-img" src="<?= site_url('assets/images/logo.png') ?>" alt="Logo" width="64" height="64" style="width:64px; height:64px;">
                <h1>Health Sanitation Management Caloocan</h1>
                <p class="report-subtitle">Contact Tracing</p>
                <hr class="header-divider">
                <p class="generated-on">Generated on: ${new Date().toLocaleString()}</p>
            </div>

            <div class="summary">
                <p><strong>Total Contacts:</strong> ${totalContacts}</p>
                <p><strong>Active Monitoring:</strong> ${activeContacts}</p>
                <p><strong>Quarantined:</strong> ${quarantined}</p>
                <p><strong>High Risk Contacts:</strong> ${highRiskContacts}</p>
            </div>

            <h3>Index Cases</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Patient</th><th>Disease</th><th>Barangay</th>
                        <th>Date Confirmed</th><th>Status</th><th>Risk</th>
                    </tr>
                </thead>
                <tbody>${indexRows}</tbody>
            </table>

            <h3>Contacts</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Age</th><th>Gender</th>
                        <th>Index Case</th><th>Relationship</th><th>Exposure</th>
                        <th>Exposure Date</th><th>Barangay</th><th>Symptoms</th>
                        <th>Risk</th><th>Monitoring</th><th>Quarantine</th>
                    </tr>
                </thead>
                <tbody>${contactRows}</tbody>
            </table>

            <div class="footer">This is a computer-generated report. For official use only.</div>
        </div>
        </div>
        </body>
        </html>
        `;
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
        setTimeout(() => window.location.reload(), 500);
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

    /* Screen: hide report content */
    .report-content {
        display: none !important;
    }

    /* ── PRINT / PDF ── */
    @media print {
        /* Page setup: landscape so the wide Contacts table (13 cols) fits without clipping */
        @page {
            size: landscape;
            margin: 12mm;
        }

        /* Force background colours to render in PDF */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }

        /* Collapse the known layout containers completely (not just hide them
           visually) so their height doesn't still reserve blank space/pages
           in the printout. Matches sidebar/header by tag, common class
           patterns, and id, in case the literal ".sidebar" class isn't the
           one actually used in the markup. */
        header, nav, aside,
        .sidebar, [class*="sidebar" i], [id*="sidebar" i],
        .flex-1, .no-print, .screen-only,
        #addContactModal, #contactDetailsModal, #editMonitoringModal,
        #viewContactsModal, #reportModal, #toast {
            display: none !important;
        }

        /* Bulletproof hide: hide EVERYTHING else too. This guarantees any
           remaining screen chrome disappears no matter what class/id names
           it actually uses, instead of relying on us guessing every
           selector correctly. */
        body * {
            visibility: hidden !important;
        }

        /* Then reveal only the report and everything inside it */
        .report-content,
        .report-content * {
            visibility: visible !important;
        }

        html, body {
            background: white !important;
            margin: 0 !important;
            padding: 0 !important;
            height: auto !important;
            overflow: visible !important;
            font-family: 'Times New Roman', serif;
        }

        /* Pull the report out of the app's flex/overflow-hidden layout so it
           is not clipped and renders as its own normal, top-to-bottom document */
        .report-content {
            display: block !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        .report-wrapper {
            max-width: 100%;
            margin: 0 auto;
            padding: 0;
            font-family: 'Times New Roman', serif;
        }

        /* Summary */
        .report-wrapper p {
            font-size: 13pt;
            margin: 4px 0;
        }

        /* Section headings — kept attached to the table that follows so a
           heading is never stranded alone at the bottom of a page */
        .report-wrapper h3 {
            font-size: 14pt;
            font-weight: 700;
            color: #0B4F4A;
            margin: 28px 0 8px;
            border-bottom: 1px solid #aaa;
            padding-bottom: 4px;
            page-break-after: avoid;
            break-after: avoid;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 24px;
            page-break-inside: auto;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        thead {
            display: table-header-group;
        }

        th {
            background-color: #0B4F4A !important;
            color: #ffffff !important;
            padding: 7px 9px;
            text-align: left;
            border: 1px solid #0B4F4A;
            font-size: 8.5pt;
            letter-spacing: 0.3px;
        }

        td {
            padding: 5px 9px;
            border: 1px solid #cccccc;
            vertical-align: top;
        }

        tr:nth-child(even) td {
            background-color: #f5fafa !important;
        }

        .footer {
            text-align: center;
            font-size: 9pt;
            color: #888;
            margin-top: 28px;
            border-top: 1px solid #ccc;
            padding-top: 12px;
        }
    }
</style>

<?php include_once '../../includes/footer.php'; ?>