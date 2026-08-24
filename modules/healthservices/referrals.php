<?php
// ============================================================
// 1. PHP BACKEND - Fetch ALL Data for Client-side Pagination
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('health center services');

require_once __DIR__ . '/../../app/Models/Referral.php';
require_once __DIR__ . '/../../app/Models/Patient.php';
require_once __DIR__ . '/../../app/Models/Employee.php';

$title = 'Referrals';

$referralModel = new Referral();
$patientModel  = new Patient();
$employeeModel = new Employee();

// ── Fetch all referrals ──────────────────────────────────────
$rawReferrals = [];
try {
    $rawReferrals = $referralModel->all(['order' => 'created_at.desc']);
} catch (Throwable $e) {
    error_log('Error fetching referrals: ' . $e->getMessage());
}

// ── Build lookup maps ────────────────────────────────────────
$patients  = [];
$patientsJsMap = [];
try {
    foreach ($patientModel->all() as $p) {
        $patients[$p['id']] = $p;
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
            'id' => (int)($p['id'] ?? 0),
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
} catch (Throwable $e) { error_log('Error fetching patients: ' . $e->getMessage()); }

$employees = [];
try {
    foreach ($employeeModel->all() as $e) {
        $employees[$e['id']] = $e;
    }
} catch (Throwable $e) { error_log('Error fetching employees: ' . $e->getMessage()); }

// Resolve logged in doctor / employee ID & Name based on role / session
$sessionUserId = $_SESSION['user_id'] ?? null;
$sessionEmployeeId = $_SESSION['employee_id'] ?? null;
$sessionFullName = trim($_SESSION['full_name'] ?? ($_SESSION['name'] ?? ($_SESSION['username'] ?? '')));

$loggedInDoctorId = null;
$loggedInDoctorName = null;

foreach ($employees as $e) {
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

// ── Enrich referrals ─────────────────────────────────────────
$allReferrals = [];
foreach ($rawReferrals as $r) {
    // Patient
    $pat = $patients[$r['patient_id']] ?? null;
    if ($pat) {
        $r['patient_name']   = trim(($pat['first_name'] ?? '') . ' ' . ($pat['last_name'] ?? ''));
        $r['patient_avatar'] = strtoupper(
            substr($pat['first_name'] ?? '', 0, 1) . substr($pat['last_name'] ?? '', 0, 1)
        );
    } else {
        $r['patient_name']   = 'Unknown';
        $r['patient_avatar'] = '??';
    }

    // From-doctor
    $fromDoc = $employees[$r['from_doctor_id']] ?? null;
    $r['from_doctor'] = $fromDoc ? ($fromDoc['full_name'] ?? 'Unknown') : 'Unknown';

    // To-doctor / specialist
    $toDoc = isset($r['to_doctor_id']) && $r['to_doctor_id']
        ? ($employees[$r['to_doctor_id']] ?? null)
        : null;
    $r['to_specialist'] = $toDoc ? ($toDoc['full_name'] ?? 'N/A') : ($r['to_hospital'] ?? 'N/A');
    $r['specialty']     = $toDoc ? ($toDoc['role_description'] ?: $toDoc['role'] ?: 'Specialist') : 'Hospital';

    // Map DB 'emergency' → frontend 'critical'
    if (($r['urgency'] ?? '') === 'emergency') {
        $r['urgency'] = 'critical';
    }

    // Formatted date
    if (!empty($r['created_at'])) {
        $r['date']           = date('Y-m-d', strtotime($r['created_at']));
        $r['date_formatted'] = date('M d, Y', strtotime($r['created_at']));
    }

    $allReferrals[] = $r;
}

// ── Stats ────────────────────────────────────────────────────
$totalPending   = count(array_filter($allReferrals, fn($r) => ($r['status'] ?? '') === 'pending'));
$totalAccepted  = count(array_filter($allReferrals, fn($r) => ($r['status'] ?? '') === 'accepted'));
$totalCompleted = count(array_filter($allReferrals, fn($r) => ($r['status'] ?? '') === 'completed'));
$totalCritical  = count(array_filter($allReferrals, fn($r) => ($r['urgency'] ?? '') === 'critical'));

$currentUserId = $_SESSION['user_id'] ?? 1;
?>

<!-- ============================================================ -->
<!-- 2. HTML LAYOUT                                               -->
<!-- ============================================================ -->
<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- ============================================================ -->
    <!-- PATIENT PROFILE MODAL IN REFERRALS                           -->
    <!-- ============================================================ -->
    <div id="referralPatientProfileModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-address-card text-brand-medium"></i> Patient Profile Overview
                </h3>
                <button onclick="ModalSystem.close('referralPatientProfileModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="referralPatientProfileContent" class="p-6">
                <div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading profile...</div>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Referrals</h2>
            <p class="text-sm text-slate-500 mt-0.5">Specialist &amp; hospital referrals with follow-up management</p>
        </div>
        <button onclick="ModalSystem.open('newReferralModal')"
                class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-arrow-right-arrow-left text-xs"></i> New Referral
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Total -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                    <i class="fa-solid fa-arrow-right-arrow-left text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900" id="kpiTotal"><?php echo count($allReferrals); ?></p>
                    <p class="text-xs font-medium text-slate-500">Total Referrals</p>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All referrals</span>
                <span class="text-[10px] text-slate-400" id="kpiAcceptedSub"><?php echo $totalAccepted; ?> accepted</span>
            </div>
        </div>
        <!-- Pending -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                    <i class="fa-solid fa-clock text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-amber-600" id="kpiPending"><?php echo $totalPending; ?></p>
                    <p class="text-xs font-medium text-slate-500">Pending</p>
                </div>
            </div>
            <div class="mt-3"><span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Needs review</span></div>
        </div>
        <!-- Accepted -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                    <i class="fa-solid fa-check-circle text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-emerald-600" id="kpiAccepted"><?php echo $totalAccepted; ?></p>
                    <p class="text-xs font-medium text-slate-500">Accepted</p>
                </div>
            </div>
            <div class="mt-3"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Confirmed</span></div>
        </div>
        <!-- Critical -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative flex items-center gap-3">
                <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-rose-600" id="kpiCritical"><?php echo $totalCritical; ?></p>
                    <p class="text-xs font-medium text-slate-500">Critical</p>
                </div>
            </div>
            <div class="mt-3"><span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Immediate</span></div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text" id="searchReferral"
                       placeholder="Search by patient, doctor, hospital, or referral ID..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="filterType" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="specialist">Specialist</option>
                    <option value="hospital">Hospital</option>
                </select>
                <select id="filterUrgency" class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 outline-none text-sm bg-white">
                    <option value="">All Urgency</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
                <label class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>From</span>
                    <input type="date" id="filterDateFrom" class="px-2.5 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                </label>
                <label class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>To</span>
                    <input type="date" id="filterDateTo" class="px-2.5 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                </label>
                <button onclick="resetFilters()" title="Reset"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 transition text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="referralTable">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">REF ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Referred To</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Urgency</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="referralTableBody">
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                            <i class="fa-solid fa-spinner fa-spin text-2xl mb-2 block"></i>
                            <p class="text-sm">Loading referrals...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-arrow-right-arrow-left text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600" id="emptyStateMsg">No referrals found</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700" id="showingStart">0</span>–<span class="font-semibold text-slate-700" id="showingEnd">0</span>
                of <span class="font-semibold text-slate-700" id="showingTotal">0</span> referrals
            </p>
            <div class="flex gap-1" id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- NEW REFERRAL MODAL                                           -->
<!-- ============================================================ -->
<div id="newReferralModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-arrow-right-arrow-left text-brand-medium"></i> New Referral
            </h3>
            <button onclick="ModalSystem.close('newReferralModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="newReferralForm" class="p-6 space-y-4" onsubmit="saveReferral(event)">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient *</label>
                    <select id="ref_patient" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">Select Patient</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Referring Doctor *</label>
                    <select id="ref_from_doctor" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">Select Doctor</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Referral Type *</label>
                <select id="ref_type" onchange="toggleReferralTypeFields()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="specialist">Specialist Referral</option>
                    <option value="hospital">Hospital Referral</option>
                </select>
            </div>
            <div id="ref_specialist_wrapper">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Refer To (Specialist) *</label>
                <select id="ref_to_doctor" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Specialist</option>
                </select>
            </div>
            <div id="ref_hospital_wrapper" class="hidden">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Hospital / Facility *</label>
                <input type="text" id="ref_hospital" placeholder="e.g. Caloocan City Medical Center, Philippine General Hospital"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Reason for Referral *</label>
                <textarea id="ref_reason" rows="2" required
                          class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"
                          placeholder="Explain why this referral is needed..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Diagnosis</label>
                <input type="text" id="ref_diagnosis"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"
                       placeholder="e.g. Hypertension, Suspected Carcinoma">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Urgency</label>
                    <select id="ref_urgency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Follow-up Date</label>
                    <input type="date" id="ref_follow_up"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Additional Notes</label>
                <textarea id="ref_notes" rows="1"
                          class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"
                          placeholder="Any additional information..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('newReferralModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-arrow-right-arrow-left mr-1.5"></i> Create Referral
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW REFERRAL MODAL                                          -->
<!-- ============================================================ -->
<div id="viewReferralModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Referral Details</h3>
            <button onclick="ModalSystem.close('viewReferralModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="referralDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT REFERRAL MODAL                                          -->
<!-- ============================================================ -->
<div id="editReferralModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Edit Referral</h3>
            <button onclick="ModalSystem.close('editReferralModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editReferralForm" class="p-6 space-y-4" onsubmit="saveEditedReferral(event)">
            <input type="hidden" id="edit_ref_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label>
                    <input type="text" id="edit_ref_patient" readonly
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 outline-none cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="edit_ref_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="completed">Completed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Urgency</label>
                <select id="edit_ref_urgency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Diagnosis</label>
                <input type="text" id="edit_ref_diagnosis"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Follow-up Date</label>
                <input type="date" id="edit_ref_follow_up"
                       class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="edit_ref_notes" rows="2"
                          class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Feedback / Outcome</label>
                <textarea id="edit_ref_feedback" rows="2"
                          class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"
                          placeholder="Update on referral outcome..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('editReferralModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
#emptyState.hidden { display: none !important; }
</style>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
const API_URL      = '<?php echo site_url('api/referrals.php'); ?>';
const CURRENT_USER = <?php echo (int)($loggedInDoctorId ?? $currentUserId); ?>;

// All data embedded from PHP — enriched server-side
let allReferrals      = <?php echo json_encode(array_values($allReferrals)); ?>;
let filteredReferrals = [...allReferrals];
let patients          = <?php echo json_encode(array_values($patients)); ?>;
let doctors           = <?php echo json_encode(array_values($employees)); ?>;

let currentPage     = 1;
const ITEMS_PER_PAGE = 10;

// Enrich referral object with localized patient, avatar, and doctor data
function enrichLocally(r) {
    if (!r) return {};
    
    // Patient Name & Avatar Initials
    if (!r.patient_name || r.patient_name === 'Unknown' || !r.patient_avatar) {
        const pat = (Array.isArray(patients) ? patients : []).find(p => p.id == r.patient_id || p.patient_id == r.patient_id);
        if (pat) {
            const fullName = `${pat.first_name || ''} ${pat.last_name || ''}`.trim();
            r.patient_name = r.patient_name || fullName || 'Unknown';
            const f = (pat.first_name || '').charAt(0).toUpperCase();
            const l = (pat.last_name || '').charAt(0).toUpperCase();
            r.patient_avatar = r.patient_avatar || (f + l) || '??';
            r.patient_code = r.patient_code || pat.patient_id || pat.id;
        } else {
            r.patient_name = r.patient_name || 'Unknown';
            r.patient_avatar = r.patient_avatar || '??';
        }
    }

    // Originating Doctor
    if (!r.from_doctor || r.from_doctor === 'Unknown' || r.from_doctor === 'N/A') {
        const doc = (Array.isArray(doctors) ? doctors : []).find(d => d.id == r.from_doctor_id);
        if (doc) {
            r.from_doctor = doc.full_name || `${doc.first_name || ''} ${doc.last_name || ''}`.trim() || 'N/A';
        }
    }

    // Target Specialist
    if (r.referral_type === 'specialist' || !r.referral_type) {
        if (!r.to_specialist || r.to_specialist === 'Unknown' || r.to_specialist === 'N/A') {
            const spec = (Array.isArray(doctors) ? doctors : []).find(d => d.id == r.to_doctor_id);
            if (spec) {
                r.to_specialist = spec.full_name || `${spec.first_name || ''} ${spec.last_name || ''}`.trim() || 'N/A';
            }
        }
    }

    return r;
}

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    allReferrals      = allReferrals.map(r => enrichLocally(r));
    filteredReferrals = [...allReferrals];
    renderTable();
    updateStats();
    populateFormSelects();
    toggleReferralTypeFields();

    // Default follow-up date = 14 days from today
    const fu = document.getElementById('ref_follow_up');
    if (fu) {
        const d = new Date();
        d.setDate(d.getDate() + 14);
        fu.value = d.toISOString().split('T')[0];
    }

    // Centralized Workflow: Auto-open & prefill New Referral modal if redirected from Consultation/Appointment
    const urlParams = new URLSearchParams(window.location.search);
    const targetPatientId = urlParams.get('patient_id') || urlParams.get('patient');
    const targetDoctorId = urlParams.get('doctor_id') || urlParams.get('from_doctor_id') || urlParams.get('employee_id');
    const autoOpenNew = urlParams.get('from_consultation') !== null || urlParams.get('action') === 'new' || urlParams.get('new') === 'true';

    if (autoOpenNew || targetPatientId) {
        setTimeout(() => {
            ModalSystem.open('newReferralModal');
            
            const patientSelect = document.getElementById('ref_patient');
            if (patientSelect && targetPatientId) {
                patientSelect.value = targetPatientId;
            }

            const doctorSelect = document.getElementById('ref_from_doctor');
            if (doctorSelect && targetDoctorId) {
                doctorSelect.value = targetDoctorId;
            }

            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.info('New referral form pre-filled for patient.', { title: '📋 Patient Referral' });
            }
        }, 350);
    }
});

// ============================================================
// TOGGLE SPECIALIST VS HOSPITAL REFERRAL FIELDS
// ============================================================
function toggleReferralTypeFields() {
    const refType = document.getElementById('ref_type')?.value || 'specialist';
    const specWrapper = document.getElementById('ref_specialist_wrapper');
    const hospWrapper = document.getElementById('ref_hospital_wrapper');
    const toDoctorSelect = document.getElementById('ref_to_doctor');
    const hospitalInput = document.getElementById('ref_hospital');

    if (refType === 'specialist') {
        if (specWrapper) specWrapper.classList.remove('hidden');
        if (hospWrapper) hospWrapper.classList.add('hidden');
        if (toDoctorSelect) toDoctorSelect.required = true;
        if (hospitalInput) {
            hospitalInput.required = false;
            hospitalInput.value = '';
        }
    } else {
        if (specWrapper) specWrapper.classList.add('hidden');
        if (hospWrapper) hospWrapper.classList.remove('hidden');
        if (toDoctorSelect) {
            toDoctorSelect.required = false;
            toDoctorSelect.value = '';
        }
        if (hospitalInput) hospitalInput.required = true;
    }
}

// ============================================================
// POPULATE SELECTS IN NEW REFERRAL FORM
// ============================================================
function populateFormSelects() {
    // Patients
    const patSel = document.getElementById('ref_patient');
    if (patSel) {
        patSel.innerHTML = '<option value="">Select Patient</option>';
        patients.forEach(p => {
            const name = `${p.first_name || ''} ${p.last_name || ''}`.trim();
            const pid  = p.patient_id || p.id;
            const opt  = new Option(`${name} (${pid})`, p.id);
            patSel.appendChild(opt);
        });
    }
    // From-doctor
    const fromSel = document.getElementById('ref_from_doctor');
    if (fromSel) {
        fromSel.innerHTML = '<option value="">Select Doctor</option>';
        doctors.forEach(d => {
            fromSel.appendChild(new Option(d.full_name || `Employee #${d.id}`, d.id));
        });
        // Pre-select current user if found
        if (fromSel.querySelector(`option[value="${CURRENT_USER}"]`)) {
            fromSel.value = CURRENT_USER;
        }
    }
    // To-doctor (specialist) - Filter ONLY Doctors, Dentists & Health Center Directors
    const toSel = document.getElementById('ref_to_doctor');
    if (toSel) {
        toSel.innerHTML = '<option value="">Select Specialist</option>';
        doctors.forEach(d => {
            const roleDesc = (d.role_description || '').toLowerCase();
            const role = (d.role || '').toLowerCase();
            const isEligibleSpecialist = (
                roleDesc.includes('doctor') || 
                roleDesc.includes('dentist') || 
                roleDesc.includes('health center director') || 
                roleDesc.includes('physician') || 
                role.includes('doctor') || 
                role.includes('dentist') ||
                role.includes('health center director')
            ) && !roleDesc.includes('nurse') && !roleDesc.includes('clerk') && !roleDesc.includes('tech') && !roleDesc.includes('sanitation');

            if (isEligibleSpecialist) {
                const label = d.full_name || `Employee #${d.id}`;
                const spec  = d.role_description ? ` — ${d.role_description}` : '';
                toSel.appendChild(new Option(label + spec, d.id));
            }
        });
    }
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
}
function maskName(name) {
    if (!name) return '';
    return name.split(' ').map(p => p ? p[0].toUpperCase() + '*'.repeat(Math.max(0, p.length - 1)) : '').join(' ');
}
function maskId(id) {
    const s = String(id || '');
    return s.length <= 2 ? s : s.slice(0, 2) + '*'.repeat(s.length - 2);
}
function formatDate(d) {
    if (!d) return 'N/A';
    const dt = new Date(d);
    return isNaN(dt) ? d : dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
function statusClasses(s) {
    return ({ pending: 'bg-amber-100 text-amber-700', accepted: 'bg-emerald-100 text-emerald-700',
              completed: 'bg-blue-100 text-blue-700', rejected: 'bg-slate-100 text-slate-500' })[s] || 'bg-slate-100 text-slate-500';
}
function urgencyClasses(u) {
    return ({ critical: 'bg-rose-100 text-rose-700', high: 'bg-orange-100 text-orange-700',
              medium: 'bg-yellow-100 text-yellow-700', low: 'bg-green-100 text-green-700' })[u] || 'bg-slate-100 text-slate-500';
}
function urgencyIcon(u) {
    return ({ critical: '🔴', high: '🟠', medium: '🟡', low: '🟢' })[u] || '⚪';
}

// ============================================================
// TABLE RENDER
// ============================================================
function renderTable() {
    const tbody      = document.getElementById('referralTableBody');
    const emptyState = document.getElementById('emptyState');
    const tableEl    = document.getElementById('referralTable');
    const pagDiv     = document.querySelector('.px-4.py-3.border-t');

    const total      = filteredReferrals.length;
    const totalPages = Math.ceil(total / ITEMS_PER_PAGE) || 1;
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;

    const start    = (currentPage - 1) * ITEMS_PER_PAGE;
    const end      = Math.min(start + ITEMS_PER_PAGE, total);
    const pageData = filteredReferrals.slice(start, end);

    if (total === 0) {
        tbody.innerHTML = '';
        tableEl.classList.add('hidden');
        if (pagDiv) pagDiv.classList.add('hidden');
        emptyState.classList.remove('hidden');
        emptyState.style.display = 'flex';
        const search = document.getElementById('searchReferral').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        const hasFilter = search || document.getElementById('filterStatus').value
            || document.getElementById('filterType').value || document.getElementById('filterUrgency').value
            || dateFrom || dateTo;
        document.getElementById('emptyStateMsg').textContent = hasFilter
            ? 'No referrals match your filters' : 'No referrals found';
    } else {
        emptyState.classList.add('hidden');
        emptyState.style.display = 'none';
        tableEl.classList.remove('hidden');
        if (pagDiv) pagDiv.classList.remove('hidden');

        tbody.innerHTML = pageData.map(r => {
            const patName    = r.patient_name   || 'Unknown';
            const patAvatar  = r.patient_avatar || '??';
            const refId      = r.referral_id    || 'N/A';
            const toSpec     = r.to_specialist  || r.to_hospital || 'N/A';
            const fromDoc    = r.from_doctor    || 'N/A';

            return `
            <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors ${r.urgency === 'critical' ? 'bg-rose-50/30' : ''}">
                <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">
                    <span class="maskable" data-real="${escapeHtml(refId)}" data-masked="${maskId(refId)}">${escapeHtml(refId)}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                            <span class="maskable" data-real="${escapeHtml(patAvatar)}" data-masked="??">${escapeHtml(patAvatar)}</span>
                        </div>
                        <div>
                            <button type="button" onclick="openPatientProfile(${r.patient_id})" class="text-left group block">
                                <span class="font-semibold text-slate-800 text-sm group-hover:text-brand-medium transition maskable" data-real="${escapeHtml(patName)}" data-masked="${maskName(patName)}">${escapeHtml(patName)}</span>
                            </button>
                            <p class="text-xs text-slate-400">${escapeHtml(fromDoc)}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <p class="font-medium text-slate-800 text-xs">${escapeHtml(toSpec)}</p>
                    <p class="text-[10px] text-slate-400">${escapeHtml(r.specialty || '')}</p>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold ${r.referral_type === 'specialist' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700'}">
                        ${escapeHtml((r.referral_type || 'specialist').charAt(0).toUpperCase() + (r.referral_type || 'specialist').slice(1))}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${urgencyClasses(r.urgency)}">
                        ${urgencyIcon(r.urgency)} ${escapeHtml((r.urgency || 'medium').charAt(0).toUpperCase() + (r.urgency || 'medium').slice(1))}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusClasses(r.status)}">
                        ${escapeHtml((r.status || 'pending').charAt(0).toUpperCase() + (r.status || 'pending').slice(1))}
                    </span>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">${formatDate(r.date)}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="openPatientProfile(${r.patient_id})" title="View Patient Profile"
                                class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                            <i class="fa-solid fa-address-card text-sm"></i>
                        </button>
                        <button onclick="viewReferral(${r.id})" title="View"
                                class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition">
                            <i class="fa-solid fa-eye text-sm"></i>
                        </button>
                        ${r.status === 'pending' ? `
                        <button onclick="updateStatus(${r.id},'accepted')" title="Accept"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                            <i class="fa-solid fa-check text-sm"></i>
                        </button>
                        <button onclick="updateStatus(${r.id},'rejected')" title="Reject"
                                class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>` : ''}
                        ${r.status === 'accepted' ? `
                        <button onclick="updateStatus(${r.id},'completed')" title="Mark Completed"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition">
                            <i class="fa-solid fa-flag-checkered text-sm"></i>
                        </button>` : ''}
                        <button onclick="editReferral(${r.id})" title="Edit"
                                class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition">
                            <i class="fa-solid fa-pen text-sm"></i>
                        </button>
                        <button onclick="deleteReferral(${r.id})" title="Delete"
                                class="p-1.5 text-rose-400 hover:bg-rose-50 rounded-lg transition">
                            <i class="fa-solid fa-trash-can text-sm"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('');

        // Re-apply data masking to newly rendered rows
        setTimeout(() => {
            const tableContainer = document.querySelector('.bg-white.rounded-xl.shadow-xs.border');
            if (tableContainer && typeof ModalSystem !== 'undefined') {
                ModalSystem.applyMaskingToModal(tableContainer);
            }
        }, 50);
    }

    // Pagination counts
    document.getElementById('showingStart').textContent = total === 0 ? 0 : start + 1;
    document.getElementById('showingEnd').textContent   = end;
    document.getElementById('showingTotal').textContent = total;
    renderPagination(total, totalPages);
}

// ============================================================
// PAGINATION
// ============================================================
function changePage(page) {
    const totalPages = Math.ceil(filteredReferrals.length / ITEMS_PER_PAGE) || 1;
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderTable();
}

function renderPagination(total, totalPages) {
    const container = document.getElementById('paginationControls');
    if (!container) return;
    if (totalPages <= 1) { container.innerHTML = ''; return; }

    const base     = 'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors';
    const active   = `${base} bg-brand-dark text-white shadow-sm`;
    const inactive = `${base} bg-white border border-slate-200 text-slate-600 hover:bg-slate-50`;
    const disabled = `${base} bg-white border border-slate-200 text-slate-300 cursor-not-allowed`;

    let html = `<button onclick="changePage(${currentPage - 1})" class="${currentPage === 1 ? disabled : inactive}" ${currentPage === 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left text-[10px]"></i></button>`;

    const delta = 2;
    const rStart = Math.max(1, currentPage - delta);
    const rEnd   = Math.min(totalPages, currentPage + delta);

    if (rStart > 1) {
        html += `<button onclick="changePage(1)" class="${inactive}">1</button>`;
        if (rStart > 2) html += `<span class="px-1 py-1.5 text-xs text-slate-400">…</span>`;
    }
    for (let p = rStart; p <= rEnd; p++) {
        html += `<button onclick="changePage(${p})" class="${p === currentPage ? active : inactive}">${p}</button>`;
    }
    if (rEnd < totalPages) {
        if (rEnd < totalPages - 1) html += `<span class="px-1 py-1.5 text-xs text-slate-400">…</span>`;
        html += `<button onclick="changePage(${totalPages})" class="${inactive}">${totalPages}</button>`;
    }

    html += `<button onclick="changePage(${currentPage + 1})" class="${currentPage === totalPages ? disabled : inactive}" ${currentPage === totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right text-[10px]"></i></button>`;
    container.innerHTML = html;
}

// ============================================================
// STATS UPDATE
// ============================================================
function updateStats() {
    const total     = allReferrals.length;
    const accepted  = allReferrals.filter(r => r.status === 'accepted').length;
    const pending   = allReferrals.filter(r => r.status === 'pending').length;
    const critical  = allReferrals.filter(r => r.urgency === 'critical').length;

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('kpiTotal',      total);
    set('kpiAccepted',   accepted);
    set('kpiPending',    pending);
    set('kpiCritical',   critical);
    set('kpiAcceptedSub', accepted + ' accepted');
}
</script>

<script>
// ============================================================
// SEARCH & FILTER
// ============================================================
document.getElementById('searchReferral').addEventListener('input', filterReferrals);
document.getElementById('filterStatus').addEventListener('change', filterReferrals);
document.getElementById('filterType').addEventListener('change', filterReferrals);
document.getElementById('filterUrgency').addEventListener('change', filterReferrals);
document.getElementById('filterDateFrom').addEventListener('change', filterReferrals);
document.getElementById('filterDateTo').addEventListener('change', filterReferrals);

function filterReferrals() {
    const search  = document.getElementById('searchReferral').value.toLowerCase().trim();
    const status  = document.getElementById('filterStatus').value;
    const type    = document.getElementById('filterType').value;
    const urgency = document.getElementById('filterUrgency').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    if (dateFrom && dateTo && dateFrom > dateTo) {
        ModalSystem.toast.error('The start date cannot be after the end date');
        return;
    }

    filteredReferrals = allReferrals.filter(r => {
        const matchSearch = !search ||
            (r.patient_name   || '').toLowerCase().includes(search) ||
            (r.referral_id    || '').toLowerCase().includes(search) ||
            (r.from_doctor    || '').toLowerCase().includes(search) ||
            (r.to_specialist  || '').toLowerCase().includes(search) ||
            (r.to_hospital    || '').toLowerCase().includes(search) ||
            (r.specialty      || '').toLowerCase().includes(search) ||
            String(r.id       || '').includes(search);
        const matchStatus  = !status  || r.status        === status;
        const matchType    = !type    || r.referral_type === type;
        const matchUrgency = !urgency || r.urgency       === urgency;
        const referralDate = String(r.date || r.created_at || '').slice(0, 10);
        const matchDateFrom = !dateFrom || referralDate >= dateFrom;
        const matchDateTo = !dateTo || referralDate <= dateTo;
        return matchSearch && matchStatus && matchType && matchUrgency && matchDateFrom && matchDateTo;
    });

    currentPage = 1;
    renderTable();
}

function resetFilters() {
    document.getElementById('searchReferral').value = '';
    document.getElementById('filterStatus').value   = '';
    document.getElementById('filterType').value     = '';
    document.getElementById('filterUrgency').value  = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    filteredReferrals = [...allReferrals];
    currentPage = 1;
    renderTable();
}

// ============================================================
// VIEW REFERRAL — use local data immediately, then refresh
// ============================================================
async function viewReferral(id) {
    ModalSystem.open('viewReferralModal');
    const local = allReferrals.find(r => r.id === id);
    if (local) renderViewModal(local);

    try {
        const res  = await fetch(`${API_URL}/${id}`);
        const data = await res.json();
        if (data.success && data.data) {
            const merged = Object.assign({}, local || {}, data.data);
            if (local) {
                if (!data.data.patient_name  || data.data.patient_name  === 'Unknown') merged.patient_name  = local.patient_name;
                if (!data.data.patient_avatar|| data.data.patient_avatar === '??')     merged.patient_avatar = local.patient_avatar;
                if (!data.data.from_doctor   || data.data.from_doctor   === 'Unknown') merged.from_doctor   = local.from_doctor;
                if (!data.data.to_specialist || data.data.to_specialist  === 'N/A')    merged.to_specialist  = local.to_specialist;
                if (data.data.urgency === 'emergency') merged.urgency = 'critical';
            }
            renderViewModal(enrichLocally(merged));
        }
    } catch (e) {
        console.warn('Could not refresh from API:', e);
    }
}

function renderViewModal(r) {
    const patName   = r.patient_name   || 'Unknown';
    const patAvatar = r.patient_avatar || '??';
    const refId     = r.referral_id    || 'N/A';
    const toSpec    = r.to_specialist  || r.to_hospital || 'N/A';
    const fromDoc   = r.from_doctor    || 'N/A';

    document.getElementById('referralDetailsContent').innerHTML = `
        <div class="space-y-4">
            <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-lg flex-shrink-0">
                    <span class="maskable" data-real="${escapeHtml(patAvatar)}" data-masked="??">${escapeHtml(patAvatar)}</span>
                </div>
                <div>
                    <h4 class="text-lg font-bold text-slate-900">
                        <span class="maskable" data-real="${escapeHtml(patName)}" data-masked="${maskName(patName)}">${escapeHtml(patName)}</span>
                    </h4>
                    <p class="text-sm text-slate-500">
                        <span class="maskable font-mono" data-real="${escapeHtml(refId)}" data-masked="${maskId(refId)}">${escapeHtml(refId)}</span>
                        &nbsp;•&nbsp;${escapeHtml((r.referral_type || 'specialist'))} referral
                    </p>
                    <div class="flex gap-1 mt-1">
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${statusClasses(r.status)}">${escapeHtml((r.status || 'pending').toUpperCase())}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${urgencyClasses(r.urgency)}">${urgencyIcon(r.urgency)} ${escapeHtml((r.urgency || 'medium').toUpperCase())}</span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><p class="text-xs text-slate-400 font-semibold">Referring Doctor</p><p class="text-sm text-slate-800">${escapeHtml(fromDoc)}</p></div>
                <div><p class="text-xs text-slate-400 font-semibold">Referred To</p><p class="text-sm text-slate-800">${escapeHtml(toSpec)}</p></div>
                <div><p class="text-xs text-slate-400 font-semibold">Specialty</p><p class="text-sm text-slate-800">${escapeHtml(r.specialty || 'N/A')}</p></div>
                <div><p class="text-xs text-slate-400 font-semibold">Hospital</p><p class="text-sm text-slate-800">${escapeHtml(r.to_hospital || 'N/A')}</p></div>
                <div><p class="text-xs text-slate-400 font-semibold">Date</p><p class="text-sm text-slate-800">${formatDate(r.date)}</p></div>
                <div><p class="text-xs text-slate-400 font-semibold">Follow-up</p><p class="text-sm text-slate-800">${r.follow_up_date ? formatDate(r.follow_up_date) : 'Not scheduled'}</p></div>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h5 class="text-sm font-bold text-slate-700 mb-2">📋 Reason for Referral</h5>
                <p class="text-sm text-slate-800">${escapeHtml(r.reason || '')}</p>
            </div>
            ${r.diagnosis ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">🔬 Diagnosis</h5><p class="text-sm text-slate-800">${escapeHtml(r.diagnosis)}</p></div>` : ''}
            ${r.notes    ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">📝 Notes</h5><p class="text-sm text-slate-800">${escapeHtml(r.notes)}</p></div>` : ''}
            ${r.feedback ? `<div class="bg-emerald-50/40 rounded-xl p-4 border border-emerald-200"><h5 class="text-sm font-bold text-emerald-700 mb-2">✅ Feedback</h5><p class="text-sm text-slate-800">${escapeHtml(r.feedback)}</p></div>` : ''}
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                <button onclick="ModalSystem.close('viewReferralModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                ${r.status === 'pending'  ? `<button onclick="ModalSystem.close('viewReferralModal');updateStatus(${r.id},'accepted')" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i>Accept</button>` : ''}
                ${r.status === 'accepted' ? `<button onclick="ModalSystem.close('viewReferralModal');updateStatus(${r.id},'completed')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold"><i class="fa-solid fa-flag-checkered mr-1.5"></i>Complete</button>` : ''}
            </div>
        </div>`;

    ModalSystem.applyMaskingToModal('viewReferralModal');
}

// ============================================================
// UPDATE STATUS (accept / reject / complete)
// ============================================================
function updateStatus(id, status) {
    const r = allReferrals.find(r => r.id === id);
    if (!r) return;

    const cfg = {
        accepted:  { title: 'Accept Referral',   msg: 'Mark this referral as accepted?',   type: 'info',    btn: 'Accept'   },
        rejected:  { title: 'Reject Referral',    msg: 'Mark this referral as rejected?',   type: 'danger',  btn: 'Reject'   },
        completed: { title: 'Complete Referral',  msg: 'Mark this referral as completed?',  type: 'info',    btn: 'Complete' }
    };
    const c = cfg[status] || { title: 'Update', msg: 'Update this referral?', type: 'info', btn: 'Confirm' };

    ModalSystem.confirm(c.msg, async () => {
        try {
            const res  = await fetch(`${API_URL}/${id}?action=status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status })
            });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Failed');
            ModalSystem.toast.success(`Referral ${r.referral_id} marked as ${status}`);
            await loadInitialData();
        } catch (e) {
            ModalSystem.toast.error('Failed to update status: ' + e.message);
        }
    }, { title: c.title, confirmText: c.btn, type: c.type });
}

// ============================================================
// SAVE NEW REFERRAL
// ============================================================
async function saveReferral(event) {
    event.preventDefault();

    const patientId   = document.getElementById('ref_patient').value;
    const fromDoctorId= document.getElementById('ref_from_doctor').value;
    const refType     = document.getElementById('ref_type').value || 'specialist';
    const toDoctorId  = document.getElementById('ref_to_doctor').value;
    const hospital    = document.getElementById('ref_hospital').value.trim();
    const reason      = document.getElementById('ref_reason').value.trim();

    if (!patientId)    { ModalSystem.toast.warning('Please select a patient'); return; }
    if (!fromDoctorId) { ModalSystem.toast.warning('Please select a referring doctor'); return; }

    if (refType === 'specialist') {
        if (!toDoctorId) { ModalSystem.toast.warning('Please select a specialist doctor'); return; }
    } else if (refType === 'hospital') {
        if (!hospital) { ModalSystem.toast.warning('Please enter the hospital / facility name'); return; }
    }

    if (!reason) { ModalSystem.toast.warning('Please enter a reason for referral'); return; }

    const payload = {
        patient_id:     parseInt(patientId),
        from_doctor_id: parseInt(fromDoctorId),
        to_doctor_id:   (refType === 'specialist' && toDoctorId) ? parseInt(toDoctorId) : null,
        to_hospital:    (refType === 'hospital') ? hospital : null,
        reason,
        diagnosis:      document.getElementById('ref_diagnosis').value.trim()   || null,
        urgency:        document.getElementById('ref_urgency').value             || 'medium',
        referral_type:  refType,
        follow_up_date: document.getElementById('ref_follow_up').value          || null,
        notes:          document.getElementById('ref_notes').value.trim()       || null,
        status: 'pending'
    };

    try {
        const res    = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message || 'Failed');
        ModalSystem.toast.success('Referral created successfully!');
        ModalSystem.close('newReferralModal');
        document.getElementById('newReferralForm').reset();
        await loadInitialData();
    } catch (e) {
        ModalSystem.toast.error('Failed to create referral: ' + e.message);
    }
}

// ============================================================
// EDIT REFERRAL — use local data, no API round-trip to open
// ============================================================
function editReferral(id) {
    const r = allReferrals.find(r => r.id === id);
    if (!r) { ModalSystem.toast.error('Referral not found'); return; }

    document.getElementById('edit_ref_id').value        = r.id;
    document.getElementById('edit_ref_patient').value   = r.patient_name || 'Unknown';
    document.getElementById('edit_ref_status').value    = r.status       || 'pending';
    document.getElementById('edit_ref_urgency').value   = r.urgency      || 'medium';
    document.getElementById('edit_ref_diagnosis').value = r.diagnosis    || '';
    document.getElementById('edit_ref_follow_up').value = r.follow_up_date ? r.follow_up_date.substring(0, 10) : '';
    document.getElementById('edit_ref_notes').value     = r.notes        || '';
    document.getElementById('edit_ref_feedback').value  = r.feedback     || '';

    ModalSystem.open('editReferralModal');
}

async function saveEditedReferral(event) {
    event.preventDefault();
    const id = document.getElementById('edit_ref_id').value;

    const payload = {
        status:         document.getElementById('edit_ref_status').value,
        urgency:        document.getElementById('edit_ref_urgency').value,
        diagnosis:      document.getElementById('edit_ref_diagnosis').value.trim() || null,
        follow_up_date: document.getElementById('edit_ref_follow_up').value        || null,
        notes:          document.getElementById('edit_ref_notes').value.trim()     || null,
        feedback:       document.getElementById('edit_ref_feedback').value.trim()  || null
    };

    try {
        const res    = await fetch(`${API_URL}/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (!result.success) throw new Error(result.message || 'Failed');
        ModalSystem.toast.success('Referral updated successfully!');
        ModalSystem.close('editReferralModal');
        await loadInitialData();
    } catch (e) {
        ModalSystem.toast.error('Failed to update referral: ' + e.message);
    }
}

// ============================================================
// DELETE REFERRAL
// ============================================================
function deleteReferral(id) {
    const r = allReferrals.find(r => r.id === id);
    if (!r) return;

    ModalSystem.confirm(
        `Delete referral ${r.referral_id}? This cannot be undone.`,
        async () => {
            try {
                const res  = await fetch(`${API_URL}/${id}`, { method: 'DELETE' });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || 'Failed');
                ModalSystem.toast.success('Referral deleted successfully');
                await loadInitialData();
            } catch (e) {
                ModalSystem.toast.error('Failed to delete referral: ' + e.message);
            }
        },
        { title: 'Delete Referral', confirmText: 'Delete', type: 'danger' }
    );
}

// ============================================================
// LOAD FROM API & RE-ENRICH
// ============================================================
async function loadInitialData() {
    try {
        const res  = await fetch(`${API_URL}?limit=1000`);
        if (!res.ok) throw new Error('Network error');
        const data = await res.json();
        if (data.success) {
            allReferrals      = (data.data || []).map(r => enrichLocally(r));
            filteredReferrals = [...allReferrals];
            currentPage       = 1;
            updateStats();
            renderTable();
        } else {
            ModalSystem.toast.error(data.message || 'Failed to reload referrals');
        }
    } catch (e) {
        console.error('loadInitialData error:', e);
        ModalSystem.toast.error('Failed to reload referrals');
    }
}
</script>

<?php include_once '../../includes/footer.php'; ?>
