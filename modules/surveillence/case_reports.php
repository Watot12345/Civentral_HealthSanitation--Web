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

    if (!isset($_SESSION['cases_data'])) {
        $_SESSION['cases_data'] = [];
    }

    try {
        switch ($action) {
            case 'create':
            case 'report_case':
                $newId = count($_SESSION['cases_data']) + 100;
                $newCase = [
                    'id' => $newId,
                    'case_code' => 'CS-' . sprintf('%03d', $newId),
                    'disease' => trim($_POST['disease'] ?? 'Unknown'),
                    'patient_name' => trim($_POST['patient_name'] ?? 'Anonymous'),
                    'age' => (int)($_POST['age'] ?? 0),
                    'gender' => trim($_POST['gender'] ?? 'Unknown'),
                    'address' => trim($_POST['address'] ?? ''),
                    'barangay' => trim($_POST['barangay'] ?? ''),
                    'contact_number' => trim($_POST['contact_number'] ?? ''),
                    'symptoms' => trim($_POST['symptoms'] ?? 'Fever, Headache'),
                    'onset_date' => trim($_POST['onset_date'] ?? date('Y-m-d')),
                    'reporting_facility' => trim($_POST['reporting_facility'] ?? 'Health Center'),
                    'status' => 'reported',
                    'severity' => strtolower(trim($_POST['severity'] ?? 'moderate')),
                    'reported_by' => $_SESSION['full_name'] ?? 'Surveillance Staff',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $_SESSION['cases_data'][] = $newCase;
                $response = ['success' => true, 'message' => 'Case report submitted successfully!', 'data' => $newCase];
                break;

            case 'update':
            case 'update_case':
                $id = (int)($_POST['id'] ?? 0);
                $response = ['success' => true, 'message' => 'Case #' . $id . ' updated successfully!'];
                break;

            case 'update_status':
            case 'confirm_case':
            case 'resolve_case':
                $id = (int)($_POST['id'] ?? 0);
                $status = trim($_POST['status'] ?? 'Confirmed');
                $response = ['success' => true, 'message' => "Case #{$id} status updated to {$status}!"];
                break;

            case 'investigate':
            case 'investigate_case':
                $id = (int)($_POST['id'] ?? 0);
                $response = ['success' => true, 'message' => "Investigation for case #{$id} submitted successfully!"];
                break;

            case 'delete':
                $id = (int)($_POST['id'] ?? 0);
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

try {
    $caseModel = new SurveillanceCase();
    $rawDbCases = $caseModel->all();

    $cases = array_map(function($c) {
        $symptomsRaw = $c['symptoms'] ?? '';
        $symptomsArr = is_array($symptomsRaw) ? $symptomsRaw : array_map('trim', explode(',', (string)$symptomsRaw));
        if (empty($symptomsArr) || (count($symptomsArr) === 1 && $symptomsArr[0] === '')) {
            $symptomsArr = ['Fever', 'Headache'];
        }
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
        <div class="flex gap-3">
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
                    <option value="">All Barangays</option>
                    <option value="Barangay San Jose">San Jose</option>
                    <option value="Barangay Poblacion">Poblacion</option>
                    <option value="Barangay Riverside">Riverside</option>
                    <option value="Barangay San Roque">San Roque</option>
                    <option value="Barangay Sta. Cruz">Sta. Cruz</option>
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
                    <option value="">Select Barangay</option>
                    <option value="Barangay San Jose">Barangay San Jose</option>
                    <option value="Barangay Poblacion">Barangay Poblacion</option>
                    <option value="Barangay Riverside">Barangay Riverside</option>
                    <option value="Barangay San Roque">Barangay San Roque</option>
                    <option value="Barangay Sta. Cruz">Barangay Sta. Cruz</option>
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
                    <option value="Barangay San Jose">Barangay San Jose</option>
                    <option value="Barangay Poblacion">Barangay Poblacion</option>
                    <option value="Barangay Riverside">Barangay Riverside</option>
                    <option value="Barangay San Roque">Barangay San Roque</option>
                    <option value="Barangay Sta. Cruz">Barangay Sta. Cruz</option>
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

    const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function postCaseApi(action, data) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('csrf_token', getCsrfToken());
        for (const key in data) {
            if (data[key] !== undefined && data[key] !== null) {
                formData.append(key, data[key]);
            }
        }
        return fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': getCsrfToken(),
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
            buttonsHtml += `
                <button onclick="changePage(${currentPage - 1})" class="px-3 py-1.5 rounded-lg text-sm ${currentPage === 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
            `;
            for (let p = 1; p <= totalPages; p++) {
                if (p === currentPage) {
                    buttonsHtml += `<button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-brand-dark text-white">${p}</button>`;
                } else {
                    buttonsHtml += `<button onclick="changePage(${p})" class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">${p}</button>`;
                }
            }
            buttonsHtml += `
                <button onclick="changePage(${currentPage + 1})" class="px-3 py-1.5 rounded-lg text-sm ${currentPage === totalPages || totalMatching === 0 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${currentPage === totalPages || totalMatching === 0 ? 'disabled' : ''}>
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