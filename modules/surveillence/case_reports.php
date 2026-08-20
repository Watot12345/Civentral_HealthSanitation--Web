<?php
// ============================================================
// COLOR PALETTE USED ON THIS PAGE
// ============================================================
//   'brand-dark':   '#0B4F4A',
//   'brand-medium': '#14807A',
//   'brand-light':  '#E6F5F3',
//   'brand-border': '#B8E0DC',
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================================
// AJAX API HANDLER FOR POST SUBMISSIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
        exit;
    }

    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];

    require_once __DIR__ . '/../../app/Models/SurveillanceCase.php';
    $caseModel = new SurveillanceCase();

    try {
        switch ($action) {
            case 'create':
            case 'report_case':
                $caseYear = date('Y');
                $existingCount = count($caseModel->all());
                $newCaseCode = 'CS-' . $caseYear . '-' . str_pad((string)($existingCount + 1), 3, '0', STR_PAD_LEFT);
                $newCase = [
                    'case_code'         => $newCaseCode,
                    'disease'           => trim($_POST['disease'] ?? ''),
                    'patient_name'      => trim($_POST['patient_name'] ?? ''),
                    'age'               => (int)($_POST['age'] ?? 0),
                    'gender'            => trim($_POST['gender'] ?? ''),
                    'address'           => trim($_POST['address'] ?? ''),
                    'barangay'          => trim($_POST['barangay'] ?? ''),
                    'contact_number'    => trim($_POST['contact_number'] ?? ''),
                    'symptoms'          => trim($_POST['symptoms'] ?? ''),
                    'onset_date'        => trim($_POST['onset_date'] ?? date('Y-m-d')),
                    'reporting_facility'=> trim($_POST['reporting_facility'] ?? ''),
                    'status'            => 'reported',
                    'severity'          => strtolower(trim($_POST['severity'] ?? 'moderate')),
                    'reported_by'       => $_SESSION['full_name'] ?? '',
                    'created_at'        => date('Y-m-d H:i:s')
                ];
                $created = $caseModel->create($newCase);
                $response = ['success' => true, 'message' => 'Case report submitted successfully!', 'data' => $created];
                break;

            case 'import_cases':
                $importedRows = [];
                if (!empty($_POST['rows_json'])) {
                    $importedRows = json_decode($_POST['rows_json'], true) ?: [];
                } elseif (!empty($_POST['csv_text'])) {
                    $lines = preg_split('/\r\n|\r|\n/', trim($_POST['csv_text']));
                    $headers = [];
                    foreach ($lines as $idx => $line) {
                        $cols = str_getcsv($line);
                        if ($idx === 0) {
                            $headers = array_map('strtolower', array_map('trim', $cols));
                            continue;
                        }
                        if (count($cols) < 2) continue;
                        $row = [];
                        foreach ($headers as $hIdx => $hKey) {
                            $row[$hKey] = trim($cols[$hIdx] ?? '');
                        }
                        $importedRows[] = $row;
                    }
                }

                if (empty($importedRows)) {
                    $response = ['success' => false, 'message' => 'No valid rows found to import.'];
                    break;
                }

                require_once __DIR__ . '/../../app/services/AlertService.php';
                $caseYear = date('Y');
                $existingCount = count($caseModel->all());
                $insertedCount = 0;

                foreach ($importedRows as $r) {
                    $patient = trim($r['patient_name'] ?? $r['patient'] ?? $r['name'] ?? '');
                    $disease = trim($r['disease'] ?? $r['illness'] ?? '');
                    if (empty($patient) || empty($disease)) continue;

                    $rawAge = trim((string)($r['age'] ?? '0'));
                    preg_match('/\d+/', $rawAge, $ageMatch);
                    $age = !empty($ageMatch) ? (int)$ageMatch[0] : 0;

                    $gender = trim($r['gender'] ?? $r['sex'] ?? 'Unknown');
                    if (stripos($gender, 'f') === 0) $gender = 'Female';
                    elseif (stripos($gender, 'm') === 0) $gender = 'Male';

                    $rawBrgy = trim((string)($r['barangay'] ?? $r['brgy'] ?? ''));
                    preg_match('/\d+/', $rawBrgy, $bMatch);
                    $barangay = !empty($bMatch) ? $bMatch[0] : $rawBrgy;

                    $rawDate = trim($r['onset_date'] ?? $r['report_date'] ?? $r['reported'] ?? $r['date'] ?? date('Y-m-d'));
                    $time = strtotime($rawDate);
                    $onsetDate = $time ? date('Y-m-d', $time) : date('Y-m-d');

                    $existingCount++;
                    $caseCode = 'CS-' . $caseYear . '-' . str_pad((string)$existingCount, 4, '0', STR_PAD_LEFT);

                    $caseData = [
                        'case_code'          => $caseCode,
                        'patient_name'       => $patient,
                        'disease'            => $disease,
                        'age'                => $age,
                        'gender'             => $gender,
                        'barangay'           => $barangay,
                        'address'            => trim($r['address'] ?? ("Barangay " . $barangay . ", Caloocan City")),
                        'contact_number'     => trim($r['contact_number'] ?? $r['contact'] ?? ''),
                        'symptoms'           => trim($r['symptoms'] ?? 'Reported via batch import'),
                        'onset_date'         => $onsetDate,
                        'reporting_facility' => trim($r['reporting_facility'] ?? 'District 1 Health Center'),
                        'status'             => 'reported',
                        'severity'           => 'moderate',
                        'reported_by'        => $_SESSION['full_name'] ?? 'System Import',
                        'created_at'         => date('Y-m-d H:i:s')
                    ];

                    $caseModel->create($caseData);
                    $insertedCount++;
                }

                // Trigger AlertService recalculation
                try {
                    \App\Services\AlertService::getInstance()->syncThresholdBreaches();
                } catch (\Throwable $e) {}

                $response = [
                    'success' => true,
                    'count'   => $insertedCount,
                    'message' => "Successfully imported {$insertedCount} case reports into the surveillance database!"
                ];
                break;

            case 'update':
            case 'update_case':
                $id = (int)($_POST['id'] ?? 0);
                $updateData = array_filter([
                    'disease'           => trim($_POST['disease'] ?? ''),
                    'patient_name'      => trim($_POST['patient_name'] ?? ''),
                    'age'               => (int)($_POST['age'] ?? 0),
                    'gender'            => trim($_POST['gender'] ?? ''),
                    'address'           => trim($_POST['address'] ?? ''),
                    'barangay'          => trim($_POST['barangay'] ?? ''),
                    'contact_number'    => trim($_POST['contact_number'] ?? ''),
                    'symptoms'          => trim($_POST['symptoms'] ?? ''),
                    'onset_date'        => trim($_POST['onset_date'] ?? ''),
                    'reporting_facility'=> trim($_POST['reporting_facility'] ?? ''),
                    'severity'          => strtolower(trim($_POST['severity'] ?? '')),
                ], fn($v) => $v !== '' && $v !== 0);
                $caseModel->updateById($id, $updateData);
                $response = ['success' => true, 'message' => 'Case #' . $id . ' updated successfully!'];
                break;

            case 'update_status':
            case 'confirm_case':
            case 'resolve_case':
                $id     = (int)($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? 'confirmed');
                $caseModel->updateById($id, ['status' => strtolower($status), 'updated_at' => date('Y-m-d H:i:s')]);
                $response = ['success' => true, 'message' => "Case #{$id} status updated to {$status}!"];
                break;

            case 'investigate':
            case 'investigate_case':
                $id = (int)($_POST['id'] ?? 0);
                $investigationData = array_filter([
                    'status'               => 'investigating',
                    'investigator_id'      => trim($_POST['investigator_id'] ?? ''),
                    'investigation_notes'  => trim($_POST['investigation_notes'] ?? ''),
                    'updated_at'           => date('Y-m-d H:i:s'),
                ], fn($v) => $v !== '');
                $caseModel->updateById($id, $investigationData);
                $response = ['success' => true, 'message' => "Investigation for case #{$id} submitted successfully!"];
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
                $caseModel->updateById($id, ['status' => 'archived', 'updated_at' => date('Y-m-d H:i:s')]);
                $response = ['success' => true, 'message' => "Case #{$id} archived successfully!"];
                break;

            default:
                $response = ['success' => true, 'message' => 'Action processed successfully!'];
                break;
        }
    } catch (Throwable $e) {
        $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
    }

    echo json_encode($response);
    exit;
}

// ============================================================
// 1. PHP BACKEND - Include Header & Sidebar
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('health surveillance');

require_once __DIR__ . '/../../app/Models/SurveillanceCase.php';
require_once __DIR__ . '/../../app/services/ClinicalSurveillanceService.php';

try {
    // Fast query: only full-scan if explicitly requested via ?sync=1
    if (isset($_GET['sync'])) {
        $clinicalSvc = new ClinicalSurveillanceService();
        $clinicalSvc->scanAllClinicalSources();
    }

    $caseModel = new SurveillanceCase();
    $rawDbCases = $caseModel->all();

    $cases = array_map(function($c) {
        $symptomsRaw = $c['symptoms'] ?? '';
        $symptomsArr = is_array($symptomsRaw) ? $symptomsRaw : array_map('trim', explode(',', (string)$symptomsRaw));
        $symptomsArr = array_filter($symptomsArr, fn($s) => $s !== '');
        return [
            'id' => (int) ($c['id'] ?? 0),
            'case_id' => $c['case_code'] ?? ('CS-' . ($c['id'] ?? '000')),
            'disease' => $c['disease'] ?? 'Unknown',
            'patient_name' => $c['patient_name'] ?? 'Anonymous',
            'age' => (int) ($c['age'] ?? 0),
            'gender' => $c['gender'] ?? 'Unknown',
            'address' => $c['address'] ?? '',
            'barangay' => $c['barangay'] ?? '',
            'contact' => $c['contact_number'] ?? '',
            'symptoms' => $symptomsArr,
            'onset_date' => $c['onset_date'] ?? date('Y-m-d'),
            'reporting_facility' => $c['reporting_facility'] ?? 'Health Center',
            'status' => strtolower($c['status'] ?? 'suspected'),
            'severity' => strtolower($c['severity'] ?? 'moderate'),
            'reported_by' => $c['reported_by'] ?? 'Staff',
            'investigator_id' => $c['investigator_id'] ?? null,
            'investigation_notes' => $c['investigation_notes'] ?? '',
            'contact_tracing_done' => !empty($c['contact_tracing_done']),
            'outbreak_id' => $c['outbreak_id'] ?? null,
            'created_at' => $c['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $c['updated_at'] ?? date('Y-m-d H:i:s')
        ];
    }, $rawDbCases);
} catch (Throwable $e) {
    error_log("Case reports fetch error: " . $e->getMessage());
    $cases = [];
}


// Stats
$totalCases = count($cases);
$reportedCount = count(array_filter($cases, fn($c) => $c['status'] === 'reported'));
$investigatingCount = count(array_filter($cases, fn($c) => $c['status'] === 'investigating'));
$confirmedCount = count(array_filter($cases, fn($c) => $c['status'] === 'confirmed'));
$resolvedCount = count(array_filter($cases, fn($c) => $c['status'] === 'resolved'));
$criticalCount = count(array_filter($cases, fn($c) => $c['severity'] === 'critical'));
$highCount = count(array_filter($cases, fn($c) => $c['severity'] === 'high'));

// District 1 South Caloocan barangays (Brgy 1-4, 77-85, 132-164)
$d1Numbers = array_merge(range(1, 4), range(77, 85), range(132, 164));
$d1BarangayOptions = [];
foreach ($d1Numbers as $num) {
    $d1BarangayOptions[] = "Barangay {$num}";
}

$title = 'Case Reports';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Case Reports</h2>
            <p class="text-sm text-slate-500 mt-0.5">Report, manage, track and investigate disease cases</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="openModal('importCasesModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-import text-brand-dark"></i> Import Reports
            </button>
            <button onclick="openModal('reportCaseModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Report Case
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS                                            -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Cases -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-notes-medical text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalCases; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Cases</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All cases</span>
                    <span class="text-[10px] text-slate-400">Including <?php echo $resolvedCount; ?> resolved</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Reported -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-flag text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $reportedCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Reported</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">📢 New</span>
                    <span class="text-[10px] text-slate-400">Awaiting review</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Confirmed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $confirmedCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Confirmed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Verified</span>
                    <span class="text-[10px] text-slate-400">Laboratory confirmed</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Critical -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $criticalCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Critical</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
                    <span class="text-[10px] text-slate-400">Immediate attention</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Resolved -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-slate-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-slate-500 to-slate-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-slate-200">
                        <i class="fa-solid fa-flag-checkered text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-600"><?php echo $resolvedCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Resolved</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold">✅ Done</span>
                    <span class="text-[10px] text-slate-400">Case closed</span>
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
                       id="searchCase"
                       placeholder="Search by case ID, patient, or disease..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="reported">Reported</option>
                    <option value="investigating">Investigating</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="resolved">Resolved</option>
                </select>
                <select id="filterSeverity" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Severity</option>
                    <option value="low">Low</option>
                    <option value="moderate">Moderate</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
                <select id="filterBarangay" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Barangays (District 1)</option>
                    <?php foreach ($d1BarangayOptions as $bName): ?>
                    <option value="<?php echo htmlspecialchars($bName); ?>"><?php echo htmlspecialchars($bName); ?></option>
                    <?php endforeach; ?>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Reported date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Reported date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Cases Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Case ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Disease</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Barangay</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Severity</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Reported</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="caseTableBody">
                    <?php foreach ($cases as $case): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors case-row <?php echo $case['severity'] === 'critical' ? 'bg-rose-50/50' : ''; ?>"
                        data-patient="<?php echo strtolower($case['patient_name']); ?>"
                        data-disease="<?php echo strtolower($case['disease']); ?>"
                        data-status="<?php echo $case['status']; ?>"
                        data-severity="<?php echo $case['severity']; ?>"
                        data-barangay="<?php echo $case['barangay']; ?>"
                        data-db-id="<?php echo (int)$case['id']; ?>"
                        data-case-id="<?php echo htmlspecialchars(strtolower($case['case_id'])); ?>"
                        data-created-date="<?php echo htmlspecialchars(substr($case['created_at'], 0, 10)); ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo $case['case_id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $case['patient_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $case['age']; ?> yrs • <?php echo $case['gender']; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-medium text-slate-800 text-xs"><?php echo $case['disease']; ?></span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo str_replace('Barangay ', '', $case['barangay']); ?></td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'reported' => 'bg-blue-100 text-blue-700',
                                    'investigating' => 'bg-amber-100 text-amber-700',
                                    'confirmed' => 'bg-emerald-100 text-emerald-700',
                                    'resolved' => 'bg-slate-100 text-slate-500'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$case['status']] ?? $statusColors['reported']; ?>">
                                <?php echo ucfirst($case['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $severityColors = [
                                    'low' => 'bg-green-100 text-green-700',
                                    'moderate' => 'bg-yellow-100 text-yellow-700',
                                    'high' => 'bg-orange-100 text-orange-700',
                                    'critical' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $severityColors[$case['severity']] ?? $severityColors['moderate']; ?>">
                                <?php echo ucfirst($case['severity']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs"><?php echo date('M d, Y', strtotime($case['created_at'])); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewCase(<?php echo $case['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <?php if ($case['status'] === 'reported' || $case['status'] === 'investigating'): ?>
                                    <button onclick="investigateCase(<?php echo $case['id']; ?>)"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Investigate">
                                        <i class="fa-solid fa-magnifying-glass text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($case['status'] === 'reported' || $case['status'] === 'investigating'): ?>
                                    <button onclick="showConfirm('Confirm Case', 'Are you sure you want to confirm this case?', 'confirm', <?php echo $case['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Confirm">
                                        <i class="fa-solid fa-check text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($case['status'] === 'confirmed'): ?>
                                    <button onclick="showConfirm('Resolve Case', 'Are you sure you want to mark this case as resolved?', 'resolve', <?php echo $case['id']; ?>)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Resolve">
                                        <i class="fa-solid fa-flag-checkered text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <button onclick="openEditModal(<?php echo $case['id']; ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-file-medical text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No cases match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Dynamic Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p id="paginationInfo" class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo min(5, $totalCases); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalCases; ?></span> cases
            </p>
            <div id="paginationControls" class="flex gap-1">
                <!-- Populated dynamically via JS -->
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- REPORT CASE MODAL                                            -->
<!-- ============================================================ -->
<div id="reportCaseModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-medium"></i>
                Report New Case
            </h3>
            <button onclick="closeModal('reportCaseModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="reportCaseForm" class="p-6 space-y-4" onsubmit="saveCaseReport(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient Name</label>
                <input type="text" id="case_patient" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Age</label>
                    <input type="number" id="case_age" required min="0" max="99" step="1" inputmode="numeric" oninput="limitCaseAge(this)" title="Maximum 2 digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label>
                    <select id="case_gender" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">Select</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Disease</label>
                <input type="text" id="case_disease" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. Dengue Fever">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="case_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label>
                <select id="case_barangay" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Barangay (District 1)</option>
                    <?php foreach ($d1BarangayOptions as $bName): ?>
                    <option value="<?php echo htmlspecialchars($bName); ?>"><?php echo htmlspecialchars($bName); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                <input type="text" id="case_contact" inputmode="numeric" maxlength="11" oninput="limitCaseContact(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Onset Date</label>
                <input type="date" id="case_onset" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Symptoms</label>
                <div class="flex flex-wrap gap-2 mt-1" id="symptomCheckboxes">
                    <?php 
                        $symptoms = ['Fever', 'Headache', 'Cough', 'Chest pain', 'Shortness of breath', 'Nausea', 'Dizziness', 'Body aches', 'Fatigue', 'Palpitations', 'Blurred vision', 'Loss of appetite', 'Sore throat', 'Runny nose', 'Vomiting', 'Diarrhea', 'Joint pain', 'Skin rash', 'Red eyes', 'Muscle pain'];
                        foreach ($symptoms as $sym):
                    ?>
                    <label class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs hover:bg-brand-light/40 cursor-pointer transition has-[:checked]:border-brand-medium has-[:checked]:bg-brand-light/40">
                        <input type="checkbox" value="<?php echo $sym; ?>" class="symptom-checkbox accent-brand-dark"> <?php echo $sym; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Reporting Facility</label>
                <input type="text" id="case_facility" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Severity</label>
                <select id="case_severity" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="low">Low</option>
                    <option value="moderate" selected>Moderate</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('reportCaseModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Report Case
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT CASE MODAL                                              -->
<!-- ============================================================ -->
<div id="editCaseModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen text-brand-medium"></i>
                Edit Case
            </h3>
            <button onclick="closeModal('editCaseModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editCaseForm" class="p-6 space-y-4" onsubmit="updateCase(event)">
            <input type="hidden" id="edit_case_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient Name</label>
                <input type="text" id="edit_patient" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Age</label>
                    <input type="number" id="edit_age" required min="0" max="99" step="1" inputmode="numeric" oninput="limitCaseAge(this)" title="Maximum 2 digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label>
                    <select id="edit_gender" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Disease</label>
                <input type="text" id="edit_disease" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="edit_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label>
                <select id="edit_barangay" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <?php foreach ($d1BarangayOptions as $bName): ?>
                    <option value="<?php echo htmlspecialchars($bName); ?>"><?php echo htmlspecialchars($bName); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                <input type="text" id="edit_contact" inputmode="numeric" maxlength="11" oninput="limitCaseContact(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Onset Date</label>
                <input type="date" id="edit_onset" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                <select id="edit_status" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="reported">Reported</option>
                    <option value="investigating">Investigating</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Severity</label>
                <select id="edit_severity" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="low">Low</option>
                    <option value="moderate">Moderate</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('editCaseModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-save mr-1.5"></i> Update Case
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW CASE MODAL                                              -->
<!-- ============================================================ -->
<div id="viewCaseModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Case Details</h3>
            <button onclick="closeModal('viewCaseModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="caseDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- CONFIRM MODAL                                                -->
<!-- ============================================================ -->
<div id="confirmModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6 text-center">
            <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-900 mb-2" id="confirmTitle">Confirm Action</h3>
            <p class="text-sm text-slate-500 mb-6" id="confirmMessage">Are you sure you want to proceed?</p>
            <input type="hidden" id="confirmAction">
            <input type="hidden" id="confirmId">
            <div class="flex gap-3">
                <button onclick="closeModal('confirmModal')" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button onclick="executeConfirm()" class="flex-1 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold" id="confirmBtn">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- INVESTIGATE CASE MODAL                                       -->
<!-- ============================================================ -->
<div id="investigateModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass text-brand-medium"></i>
                Investigate Case
            </h3>
            <button onclick="closeModal('investigateModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="investigateForm" class="p-6 space-y-4" onsubmit="saveInvestigation(event)">
            <input type="hidden" id="investigate_case_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Investigator</label>
                <select id="investigate_investigator" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Dr. Miguel Reyes">Dr. Miguel Reyes</option>
                    <option value="Dr. Elena Santos">Dr. Elena Santos</option>
                    <option value="Dr. Ana Cruz">Dr. Ana Cruz</option>
                    <option value="Dr. Carlos Lim">Dr. Carlos Lim</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Investigation Notes</label>
                <textarea id="investigate_notes" rows="4" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Detailed investigation findings..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('investigateModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Submit Investigation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SHEETJS FOR EXCEL IMPORT/EXPORT -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- ============================================================ -->
<!-- BULK IMPORT CASE REPORTS MODAL (EXCEL & CSV)                -->
<!-- ============================================================ -->
<div id="importCasesModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                Bulk Import Case Reports (Excel & CSV)
            </h3>
            <button onclick="closeModal('importCasesModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        
        <div class="p-6 space-y-5">
            <!-- Instructions Banner & Template Download -->
            <div class="p-4 bg-emerald-50/60 border border-emerald-200 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                        <i class="fa-solid fa-table"></i> Required Columns in Spreadsheet:
                    </p>
                    <p class="text-[11px] text-slate-700 font-mono font-semibold">
                        Patient, Age, Gender, Disease, Barangay, Reported
                    </p>
                    <p class="text-[10px] text-slate-500">
                        *Case IDs, 2-SD anomaly calculations, and district zones are auto-generated.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="downloadSampleExcel()" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-bold hover:bg-emerald-700 transition shadow-2xs whitespace-nowrap flex items-center gap-1.5">
                        <i class="fa-solid fa-file-excel text-[11px]"></i> Template (.xlsx)
                    </button>
                    <button type="button" onclick="downloadSampleCsv()" class="px-2.5 py-1.5 bg-white border border-slate-300 text-slate-700 rounded-lg text-xs font-bold hover:bg-slate-50 transition shadow-2xs whitespace-nowrap flex items-center gap-1">
                        .csv
                    </button>
                </div>
            </div>

            <!-- Upload File (.xlsx, .xls, .csv) -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Choose Excel (.xlsx / .xls) or CSV File</label>
                <input type="file" id="spreadsheetFileInput" accept=".xlsx, .xls, .csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel, text/csv" onchange="handleSpreadsheetFileUpload(event)" class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50/50">
            </div>

            <div class="relative flex py-1 items-center">
                <div class="flex-grow border-t border-slate-200"></div>
                <span class="flex-shrink mx-3 text-slate-400 text-[10px] font-bold uppercase">OR PASTE DIRECTLY FROM EXCEL / TEXT</span>
                <div class="flex-grow border-t border-slate-200"></div>
            </div>

            <div>
                <textarea id="rawSpreadsheetText" rows="4" oninput="handleRawSpreadsheetInput(this.value)" placeholder="Patient	Age	Gender	Disease	Barangay	Reported&#10;Nestor Guinto	32	Female	Dengue	80	2026-08-02&#10;Maria Santos	28	Female	Influenza	77	2026-08-03" class="w-full p-3 border border-slate-200 rounded-xl text-xs font-mono focus:ring-2 focus:ring-emerald-500/40 focus:border-emerald-500 outline-none bg-slate-50/50"></textarea>
            </div>

            <!-- Real-time Preview Table -->
            <div id="importPreviewContainer" class="hidden space-y-2">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                        <i class="fa-solid fa-check-circle text-emerald-600"></i>
                        Parsed Rows Preview (<span id="parsedRowCount">0</span> cases ready)
                    </h4>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px]">Validated & Ready</span>
                </div>
                <div class="border border-slate-200 rounded-xl max-h-52 overflow-y-auto">
                    <table class="min-w-full text-[11px] text-left">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold sticky top-0">
                            <tr>
                                <th class="py-2 px-3">Patient</th>
                                <th class="py-2 px-3">Age / Gender</th>
                                <th class="py-2 px-3">Disease</th>
                                <th class="py-2 px-3">Barangay</th>
                                <th class="py-2 px-3">Reported Date</th>
                            </tr>
                        </thead>
                        <tbody id="importPreviewBody" class="divide-y divide-slate-100 font-medium text-slate-700">
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeModal('importCasesModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="button" id="btnSubmitImport" onclick="submitImportedCases()" disabled
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Confirm & Import to Database
                </button>
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
    const CASES = <?php echo json_encode(array_column($cases, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;
    let PARSED_IMPORT_ROWS = [];

    // ============================================================
    // EXCEL & CSV TEMPLATE DOWNLOAD & FILE PARSING
    // ============================================================
    function downloadSampleExcel() {
        const sampleData = [
            { "Patient": "Nestor Guinto", "Age": 32, "Gender": "Female", "Disease": "Dengue", "Barangay": "80", "Reported": "2026-08-02" },
            { "Patient": "Maria Santos", "Age": 28, "Gender": "Female", "Disease": "Influenza", "Barangay": "77", "Reported": "2026-08-03" },
            { "Patient": "Juan Dela Cruz", "Age": 45, "Gender": "Male", "Disease": "Leptospirosis", "Barangay": "82", "Reported": "2026-08-04" },
            { "Patient": "Elena Reyes", "Age": 19, "Gender": "Female", "Disease": "Measles", "Barangay": "132", "Reported": "2026-08-05" },
            { "Patient": "Carlos Mendoza", "Age": 54, "Gender": "Male", "Disease": "Gastroenteritis", "Barangay": "141", "Reported": "2026-08-06" }
        ];

        const worksheet = XLSX.utils.json_to_sheet(sampleData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Surveillance Cases");

        XLSX.writeFile(workbook, "surveillance_cases_template.xlsx");
    }

    function downloadSampleCsv() {
        const csvContent = "data:text/csv;charset=utf-8," 
            + "Patient,Age,Gender,Disease,Barangay,Reported\n"
            + "Nestor Guinto,32,Female,Dengue,80,2026-08-02\n"
            + "Maria Santos,28,Female,Influenza,77,2026-08-03\n"
            + "Juan Dela Cruz,45,Male,Leptospirosis,82,2026-08-04\n"
            + "Elena Reyes,19,Female,Measles,132,2026-08-05\n"
            + "Carlos Mendoza,54,Male,Gastroenteritis,141,2026-08-06";
        
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "surveillance_cases_template.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function handleSpreadsheetFileUpload(e) {
        const file = e.target.files[0];
        if (!file) return;

        const isExcel = file.name.endsWith('.xlsx') || file.name.endsWith('.xls');

        if (isExcel && typeof XLSX !== 'undefined') {
            const reader = new FileReader();
            reader.onload = function(evt) {
                const data = new Uint8Array(evt.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const jsonData = XLSX.utils.sheet_to_json(worksheet, { defval: '' });
                processJsonRows(jsonData);
            };
            reader.readAsArrayBuffer(file);
        } else {
            const reader = new FileReader();
            reader.onload = function(evt) {
                const text = evt.target.result;
                document.getElementById('rawSpreadsheetText').value = text;
                parseRawDelimitedText(text);
            };
            reader.readAsText(file);
        }
    }

    function handleRawSpreadsheetInput(val) {
        parseRawDelimitedText(val);
    }

    function processJsonRows(jsonData) {
        if (!jsonData || jsonData.length === 0) {
            document.getElementById('importPreviewContainer').classList.add('hidden');
            document.getElementById('btnSubmitImport').disabled = true;
            PARSED_IMPORT_ROWS = [];
            return;
        }

        const rows = [];
        jsonData.forEach(item => {
            const keys = Object.keys(item);
            let patient = '', age = 0, gender = 'Unknown', disease = '', barangay = '', reported = '';

            keys.forEach(k => {
                const lower = k.toLowerCase().trim();
                const val = String(item[k] || '').trim();
                if (lower.includes('patient') || lower.includes('name')) patient = val;
                else if (lower === 'age') age = parseInt(val.replace(/\D/g, '')) || 0;
                else if (lower.includes('gender') || lower.includes('sex')) gender = val;
                else if (lower.includes('disease') || lower.includes('illness') || lower.includes('diagnosis')) disease = val;
                else if (lower.includes('barangay') || lower.includes('brgy')) barangay = val.replace(/\D/g, '') || val;
                else if (lower.includes('report') || lower.includes('date') || lower.includes('onset')) reported = val;
            });

            if (patient && disease) {
                rows.push({
                    patient_name: patient,
                    age: age,
                    gender: gender || 'Unknown',
                    disease: disease,
                    barangay: barangay || '77',
                    onset_date: reported || new Date().toISOString().slice(0, 10)
                });
            }
        });

        renderPreviewTable(rows);
    }

    function parseRawDelimitedText(text) {
        if (!text || !text.trim()) {
            document.getElementById('importPreviewContainer').classList.add('hidden');
            document.getElementById('btnSubmitImport').disabled = true;
            PARSED_IMPORT_ROWS = [];
            return;
        }

        const lines = text.trim().split(/\r\n|\r|\n/);
        if (lines.length < 2) {
            document.getElementById('importPreviewContainer').classList.add('hidden');
            document.getElementById('btnSubmitImport').disabled = true;
            PARSED_IMPORT_ROWS = [];
            return;
        }

        // Auto-detect tab vs comma
        const isTab = lines[0].includes('\t');
        const delimiter = isTab ? '\t' : ',';

        const headers = lines[0].split(delimiter).map(h => h.trim().toLowerCase().replace(/['"]/g, ''));
        const rows = [];

        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;
            const cols = line.split(delimiter).map(c => c.trim().replace(/['"]/g, ''));
            if (cols.length < 2) continue;

            const row = {};
            headers.forEach((h, idx) => {
                row[h] = cols[idx] || '';
            });

            const patient = row.patient || row.patient_name || row.name || cols[0] || '';
            const age = (row.age || cols[1] || '').replace(/\D/g, '') || '0';
            const gender = row.gender || row.sex || cols[2] || 'Unknown';
            const disease = row.disease || row.illness || cols[3] || '';
            const barangay = (row.barangay || row.brgy || cols[4] || '').replace(/\D/g, '') || cols[4] || '';
            const reported = row.reported || row.report_date || row.onset_date || row.date || cols[5] || new Date().toISOString().slice(0, 10);

            if (patient && disease) {
                rows.push({
                    patient_name: patient,
                    age: parseInt(age) || 0,
                    gender: gender,
                    disease: disease,
                    barangay: barangay,
                    onset_date: reported
                });
            }
        }

        renderPreviewTable(rows);
    }

    function renderPreviewTable(rows) {
        PARSED_IMPORT_ROWS = rows;
        const countSpan = document.getElementById('parsedRowCount');
        const previewContainer = document.getElementById('importPreviewContainer');
        const previewBody = document.getElementById('importPreviewBody');
        const submitBtn = document.getElementById('btnSubmitImport');

        if (rows.length > 0) {
            countSpan.textContent = rows.length;
            previewBody.innerHTML = rows.map(r => `
                <tr class="hover:bg-slate-50">
                    <td class="py-1.5 px-3 font-bold text-slate-900">${escapeHtml(r.patient_name)}</td>
                    <td class="py-1.5 px-3">${r.age} yrs &bull; ${escapeHtml(r.gender)}</td>
                    <td class="py-1.5 px-3 font-semibold text-emerald-700">${escapeHtml(r.disease)}</td>
                    <td class="py-1.5 px-3">Brgy ${escapeHtml(r.barangay)}</td>
                    <td class="py-1.5 px-3 text-slate-500">${escapeHtml(r.onset_date)}</td>
                </tr>
            `).join('');

            previewContainer.classList.remove('hidden');
            submitBtn.disabled = false;
        } else {
            previewContainer.classList.add('hidden');
            submitBtn.disabled = true;
        }
    }

    function submitImportedCases() {
        if (!PARSED_IMPORT_ROWS || PARSED_IMPORT_ROWS.length === 0) {
            showToast('No valid case rows to import', 'warning');
            return;
        }

        const submitBtn = document.getElementById('btnSubmitImport');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Importing to Database...';

        postCaseApi('import_cases', {
            rows_json: JSON.stringify(PARSED_IMPORT_ROWS)
        }).then(res => {
            if (res.success) {
                closeModal('importCasesModal');
                showToast(res.message || 'Cases successfully imported to database!', 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showToast(res.message || 'Failed to import cases', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Confirm & Import to Database';
            }
        }).catch(err => {
            showToast('Error: ' + err.message, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Confirm & Import to Database';
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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
    // CONFIRM MODAL
    // ============================================================
    function showConfirm(title, message, action, id) {
        document.getElementById('confirmTitle').textContent = title;
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmAction').value = action;
        document.getElementById('confirmId').value = id;
        
        // Change button color based on action
        const btn = document.getElementById('confirmBtn');
        if (action === 'delete') {
            btn.className = 'flex-1 px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition text-sm font-semibold';
            btn.textContent = 'Delete';
        } else if (action === 'confirm') {
            btn.className = 'flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold';
            btn.textContent = 'Confirm';
        } else if (action === 'resolve') {
            btn.className = 'flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold';
            btn.textContent = 'Resolve';
        } else {
            btn.className = 'flex-1 px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold';
            btn.textContent = 'Confirm';
        }
        
        openModal('confirmModal');
    }

    function executeConfirm() {
        const action = document.getElementById('confirmAction').value;
        const id = parseInt(document.getElementById('confirmId').value);
        closeModal('confirmModal');
        
        if (action === 'confirm') {
            doConfirmCase(id);
        } else if (action === 'resolve') {
            doResolveCase(id);
        } else if (action === 'delete') {
            doDeleteCase(id);
        }
    }

    // ============================================================
    // VIEW CASE
    // ============================================================
    function viewCase(id) {
        openModal('viewCaseModal');
        const c = CASES[id];
        if (!c) return;

        setTimeout(() => {
            const statusColors = {
                reported: 'bg-blue-100 text-blue-700',
                investigating: 'bg-amber-100 text-amber-700',
                confirmed: 'bg-emerald-100 text-emerald-700',
                resolved: 'bg-slate-100 text-slate-500'
            };
            const severityColors = {
                low: 'bg-green-100 text-green-700',
                moderate: 'bg-yellow-100 text-yellow-700',
                high: 'bg-orange-100 text-orange-700',
                critical: 'bg-rose-100 text-rose-700'
            };
            const symptomsHtml = c.symptoms.map(s => `<span class="px-2 py-1 bg-slate-100 rounded text-xs">${s}</span>`).join('');

            document.getElementById('caseDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${c.patient_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${c.patient_name}</h4>
                            <p class="text-sm text-slate-500">${c.case_id} • ${c.disease}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[c.status] || statusColors.reported}">
                                ${c.status.toUpperCase()}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ml-1 ${severityColors[c.severity] || severityColors.moderate}">
                                ${c.severity.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Age</p><p class="text-sm text-slate-800">${c.age} yrs</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Gender</p><p class="text-sm text-slate-800">${c.gender}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Barangay</p><p class="text-sm text-slate-800">${c.barangay}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Contact</p><p class="text-sm text-slate-800">${c.contact || '—'}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Onset Date</p><p class="text-sm text-slate-800">${new Date(c.onset_date).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Reported By</p><p class="text-sm text-slate-800">${c.reported_by}</p></div>
                        <div class="col-span-2"><p class="text-xs text-slate-400 font-semibold">Address</p><p class="text-sm text-slate-800">${c.address}</p></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Symptoms</h5>
                        <div class="flex flex-wrap gap-2">${symptomsHtml}</div>
                    </div>
                    ${c.investigation_notes ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">🔍 Investigation Notes</h5><p class="text-sm text-slate-800">${c.investigation_notes}</p></div>` : ''}
                    ${c.contact_tracing_done ? `<div class="bg-emerald-50 rounded-xl p-4 border border-emerald-200"><h5 class="text-sm font-bold text-emerald-700 mb-2">✅ Contact Tracing</h5><p class="text-sm text-slate-800">Contact tracing has been completed for this case.</p></div>` : ''}
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewCaseModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        ${c.status === 'reported' || c.status === 'investigating' ? `<button onclick="closeModal('viewCaseModal'); investigateCase(${c.id})" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-semibold"><i class="fa-solid fa-magnifying-glass mr-1.5"></i> Investigate</button>` : ''}
                        ${c.status === 'reported' || c.status === 'investigating' ? `<button onclick="closeModal('viewCaseModal'); showConfirm('Confirm Case', 'Are you sure you want to confirm this case?', 'confirm', ${c.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Confirm</button>` : ''}
                        ${c.status === 'confirmed' ? `<button onclick="closeModal('viewCaseModal'); showConfirm('Resolve Case', 'Are you sure you want to mark this case as resolved?', 'resolve', ${c.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold"><i class="fa-solid fa-flag-checkered mr-1.5"></i> Resolve</button>` : ''}
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // OPEN EDIT MODAL
    // ============================================================
    function openEditModal(id) {
        const c = CASES[id];
        if (!c) return;
        
        document.getElementById('edit_case_id').value = id;
        document.getElementById('edit_patient').value = c.patient_name;
        document.getElementById('edit_age').value = c.age;
        document.getElementById('edit_gender').value = c.gender;
        document.getElementById('edit_disease').value = c.disease;
        document.getElementById('edit_address').value = c.address;
        document.getElementById('edit_barangay').value = c.barangay;
        document.getElementById('edit_contact').value = c.contact || '';
        document.getElementById('edit_onset').value = c.onset_date;
        document.getElementById('edit_status').value = c.status;
        document.getElementById('edit_severity').value = c.severity;
        
        openModal('editCaseModal');
    }

    function updateCase(event) {
        event.preventDefault();
        const id = parseInt(document.getElementById('edit_case_id').value);
        const c = CASES[id];
        if (!c) return;

        const age = document.getElementById('edit_age').value;
        const contact = document.getElementById('edit_contact').value;
        if (!/^\d{1,2}$/.test(age) || Number(age) > 99 || (contact && !/^\d{11}$/.test(contact))) {
            showToast('Age must be 2 digits maximum and contact must contain 11 digits.', 'warning');
            return;
        }
        
        c.patient_name = document.getElementById('edit_patient').value;
        c.age = parseInt(age);
        c.gender = document.getElementById('edit_gender').value;
        c.disease = document.getElementById('edit_disease').value;
        c.address = document.getElementById('edit_address').value;
        c.barangay = document.getElementById('edit_barangay').value;
        c.contact = contact;
        c.onset_date = document.getElementById('edit_onset').value;
        c.status = document.getElementById('edit_status').value;
        c.severity = document.getElementById('edit_severity').value;
        c.updated_at = new Date().toISOString().replace('T', ' ').slice(0, 19);

        postCaseApi('update', {
            id: c.db_id || c.id,
            disease: c.disease,
            patient_name: c.patient_name,
            age: c.age,
            gender: c.gender,
            address: c.address,
            barangay: c.barangay,
            contact_number: c.contact,
            onset_date: c.onset_date,
            status: c.status,
            severity: c.severity
        }).then(res => {
            if (!res.success) throw new Error(res.message || 'Failed to update case');
            updateCaseRow(c);
            closeModal('editCaseModal');
            showToast('Case #' + c.case_id + ' updated successfully!', 'success');
        }).catch(err => showToast(err.message, 'danger'));
    }

    // ============================================================
    // INVESTIGATE CASE
    // ============================================================
    function investigateCase(id) {
        const c = CASES[id];
        if (!c) return;
        
        document.getElementById('investigate_case_id').value = id;
        document.getElementById('investigate_investigator').value = c.investigator_id || 'Dr. Miguel Reyes';
        document.getElementById('investigate_notes').value = c.investigation_notes || '';
        
        openModal('investigateModal');
    }

    const fetchCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function postCaseApi(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', fetchCsrfToken());
        for (const key in data) {
            if (data[key] !== undefined && data[key] !== null) {
                formData.append(key, data[key]);
            }
        }
        return fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': fetchCsrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(res => res.json())
        .catch(err => {
            return fetch('api/cases.php', {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            }).then(res => res.json());
        });
    }

    function saveInvestigation(event) {
        event.preventDefault();
        const id = document.getElementById('investigate_case_id').value;
        const notes = document.getElementById('investigate_notes').value.trim();
        const investigator = document.getElementById('investigate_investigator').value.trim();
        const c = CASES[id];

        postCaseApi('investigate', { id: c?.db_id || id, investigation_notes: notes, investigator_id: investigator })
            .then(res => {
                if (res.success) {
                    if (c) {
                        c.status = 'investigating';
                        c.investigation_notes = notes;
                        updateCaseRow(c);
                    }
                    closeModal('investigateModal');
                    showToast(res.message || 'Investigation submitted successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(res.message || 'Error saving investigation', 'danger');
                }
            })
            .catch(err => showToast('Server request failed: ' + err.message, 'danger'));
    }

    // ============================================================
    // CONFIRM CASE (called from confirm modal)
    // ============================================================
    function doConfirmCase(id) {
        const c = CASES[id];
        postCaseApi('update_status', { id: c?.db_id || id, status: 'Confirmed' })
            .then(res => {
                if (res.success) {
                    if (c) { c.status = 'confirmed'; updateCaseRow(c); }
                    showToast(res.message || 'Case confirmed!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(res.message || 'Error confirming case', 'danger');
                }
            });
    }

    // ============================================================
    // RESOLVE CASE (called from confirm modal)
    // ============================================================
    function doResolveCase(id) {
        const c = CASES[id];
        postCaseApi('update_status', { id: c?.db_id || id, status: 'Resolved' })
            .then(res => {
                if (res.success) {
                    if (c) { c.status = 'resolved'; updateCaseRow(c); }
                    showToast(res.message || 'Case resolved!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast(res.message || 'Error resolving case', 'danger');
                }
            });
    }

    // ============================================================
    // DELETE CASE
    // ============================================================
    function doDeleteCase(id) {
        showToast('Case #' + (CASES[id]?.case_id || id) + ' archived.', 'info');
    }

    // ============================================================
    // UPDATE CASE ROW
    // ============================================================
    function updateCaseRow(c) {
        const rows = document.querySelectorAll('.case-row');
        rows.forEach(row => {
            if (row.dataset.dbId == c.id || row.dataset.patient === c.patient_name) {
                row.dataset.patient = c.patient_name.toLowerCase();
                row.dataset.status = c.status;
                row.dataset.severity = c.severity;
                row.dataset.barangay = c.barangay;
                const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
                if (statusBadge) {
                    const statusColors = {
                        reported: 'bg-blue-100 text-blue-700',
                        investigating: 'bg-amber-100 text-amber-700',
                        confirmed: 'bg-emerald-100 text-emerald-700',
                        resolved: 'bg-slate-100 text-slate-500'
                    };
                    statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[c.status] || statusColors.reported}`;
                    statusBadge.textContent = c.status.charAt(0).toUpperCase() + c.status.slice(1);
                }
            }
        });
    }

    // ============================================================
    // REPORT CASE
    // ============================================================
    function saveCaseReport(event) {
        event.preventDefault();
        const disease = document.getElementById('case_disease')?.value;
        const patientName = document.getElementById('case_patient')?.value;
        const age = document.getElementById('case_age')?.value;
        const gender = document.getElementById('case_gender')?.value;
        const barangay = document.getElementById('case_barangay')?.value;
        const address = document.getElementById('case_address')?.value;
        const contact = document.getElementById('case_contact')?.value;
        const onsetDate = document.getElementById('case_onset')?.value;
        const facility = document.getElementById('case_facility')?.value;

        if (!/^\d{1,2}$/.test(age) || Number(age) > 99 || (contact && !/^\d{11}$/.test(contact))) {
            showToast('Age must be 2 digits maximum and contact must contain 11 digits.', 'warning');
            return;
        }


        postCaseApi('create', {
            disease: disease,
            patient_name: patientName,
            age: age,
            gender: gender,
            barangay: barangay,
            address: address,
            contact_number: contact,
            onset_date: onsetDate,
            reporting_facility: facility
        }).then(res => {
            if (res.success) {
                showToast(res.message || 'Case reported successfully!', 'success');
                closeModal('reportCaseModal');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(res.message || 'Failed to submit case report', 'danger');
            }
        }).catch(err => showToast('Server connection error', 'danger'));
    }


    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
    function limitCaseAge(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 2);
    }

    function limitCaseContact(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 11);
    }

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
    // ============================================================
    // DYNAMIC PAGINATION & SEARCH/FILTER
    // ============================================================
    let currentPage = 1;
    const itemsPerPage = 5;

    document.getElementById('searchCase').addEventListener('input', () => { currentPage = 1; filterCases(); });
    document.getElementById('filterStatus').addEventListener('change', () => { currentPage = 1; filterCases(); });
    document.getElementById('filterSeverity').addEventListener('change', () => { currentPage = 1; filterCases(); });
    document.getElementById('filterBarangay').addEventListener('change', () => { currentPage = 1; filterCases(); });
    document.getElementById('filterDateFrom').addEventListener('change', () => { currentPage = 1; filterCases(); });
    document.getElementById('filterDateTo').addEventListener('change', () => { currentPage = 1; filterCases(); });

    function filterCases() {
        const search = document.getElementById('searchCase').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const severity = document.getElementById('filterSeverity').value;
        const barangay = document.getElementById('filterBarangay').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;

        const matchingRows = [];
        document.querySelectorAll('.case-row').forEach(row => {
            const patient = (row.dataset.patient || '').toLowerCase();
            const disease = (row.dataset.disease || '').toLowerCase();
            const rowStatus = row.dataset.status || '';
            const rowSeverity = row.dataset.severity || '';
            const rowBarangay = row.dataset.barangay || '';
            const caseId = (row.dataset.caseId || '').toLowerCase();
            const createdDate = row.dataset.createdDate || '';

            const matchesSearch = !search || [patient, disease, caseId].some(val => val.includes(search));
            const matchesStatus = !status || rowStatus === status;
            const matchesSeverity = !severity || rowSeverity === severity;
            const matchesBarangay = !barangay || rowBarangay === barangay;
            const matchesDateFrom = !dateFrom || (createdDate && createdDate >= dateFrom);
            const matchesDateTo = !dateTo || (createdDate && createdDate <= dateTo);

            if (matchesSearch && matchesStatus && matchesSeverity && matchesBarangay && matchesDateFrom && matchesDateTo) {
                matchingRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        const emptyEl = document.getElementById('emptyState');
        if (emptyEl) emptyEl.style.display = matchingRows.length === 0 ? 'flex' : 'none';

        renderPagination(matchingRows);
    }

    function renderPagination(matchingRows) {
        const totalMatching = matchingRows.length;
        const totalPages = Math.ceil(totalMatching / itemsPerPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = startIdx + itemsPerPage;

        matchingRows.forEach((row, index) => {
            if (index >= startIdx && index < endIdx) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const infoEl = document.getElementById('paginationInfo');
        if (infoEl) {
            const showingFrom = totalMatching === 0 ? 0 : startIdx + 1;
            const showingTo = Math.min(endIdx, totalMatching);
            infoEl.innerHTML = `Showing <span class="font-semibold text-slate-700">${showingFrom}</span> to <span class="font-semibold text-slate-700">${showingTo}</span> of <span class="font-semibold text-slate-700">${totalMatching}</span> cases`;
        }

        const controlsEl = document.getElementById('paginationControls');
        if (controlsEl) {
            let buttonsHtml = '';
            
            // Previous button
            buttonsHtml += `
                <button onclick="changePage(${currentPage - 1})" class="px-3 py-1.5 rounded-lg text-sm transition ${currentPage === 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-brand-dark'}" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
            `;
            
            // Window of at most 3 page numbers (e.g. 1-3, then 4-6, then 7-9)
            const maxVisible = 3;
            const blockIndex = Math.floor((currentPage - 1) / maxVisible);
            const startPage = blockIndex * maxVisible + 1;
            const endPage = Math.min(startPage + maxVisible - 1, totalPages);

            for (let p = startPage; p <= endPage; p++) {
                if (p === currentPage) {
                    buttonsHtml += `<button class="px-3 py-1.5 rounded-lg text-sm font-bold bg-brand-dark text-white shadow-sm">${p}</button>`;
                } else {
                    buttonsHtml += `<button onclick="changePage(${p})" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-brand-dark transition">${p}</button>`;
                }
            }

            // Next button
            buttonsHtml += `
                <button onclick="changePage(${currentPage + 1})" class="px-3 py-1.5 rounded-lg text-sm transition ${currentPage === totalPages || totalMatching === 0 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-brand-dark'}" ${currentPage === totalPages || totalMatching === 0 ? 'disabled' : ''}>
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            `;
            controlsEl.innerHTML = buttonsHtml;
        }
    }

    function changePage(page) {
        currentPage = page;
        filterCases();
    }

    function resetFilters() {
        document.getElementById('searchCase').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterSeverity').value = '';
        document.getElementById('filterBarangay').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        currentPage = 1;
        filterCases();
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
    // SET DEFAULT DATE & INITIALIZE PAGINATION
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const onsetInput = document.getElementById('case_onset');
        if (onsetInput) {
            const date = new Date();
            date.setDate(date.getDate() - 1);
            onsetInput.value = date.toISOString().split('T')[0];
        }
        filterCases();
    });
</script>

<?php include_once '../../includes/footer.php'; ?>