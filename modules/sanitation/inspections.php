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
// 1. PHP BACKEND - live data only (no static arrays)
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('sanitation permits');

require_once __DIR__ . '/../../app/Models/Permit.php';
require_once __DIR__ . '/../../app/Models/Employee.php';

$title = 'Inspections';

$permits = [];
try {
    $permitModel = new Permit();
    $permits = $permitModel->all();
} catch (\Throwable $e) {
    error_log('Inspections view: failed to load permits - ' . $e->getMessage());
}

$inspectors = [];
try {
    $employeeModel = new Employee();
    $allEmps = $employeeModel->all();
    $inspectors = array_values(array_filter($allEmps, function($e) {
        $primaryRole = $e['role'] ?? '';
        $roleDesc = strtolower($e['role_description'] ?? '');
        $dept = strtolower(trim($e['department'] ?? ''));
        return (in_array($primaryRole, ['Sanitation Officer', 'Sanitation Director', 'Wastewater Lead']) || str_contains($roleDesc, 'inspector') || str_contains($roleDesc, 'officer'))
            && ($dept === '' || str_contains($dept, 'sanitation') || str_contains($dept, 'permits'));
    }));
    if (empty($inspectors)) {
        $inspectors = $allEmps;
    }
} catch (\Throwable $e) {
    error_log('Inspections view: failed to load employees - ' . $e->getMessage());
}
?>
<!-- ============================================================ -->
<!-- 2. HTML + Tailwind CSS                                      -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Inspections</h2>
            <p class="text-sm text-slate-500 mt-0.5">Schedule, conduct, and manage inspections</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('scheduleInspectionModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-calendar-plus text-xs"></i> Schedule Inspection
            </button>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Inspections -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-clipboard-list text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900" id="statTotal">—</p>
                        <p class="text-xs font-medium text-slate-500">Total Inspections</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All inspections</span>
                    <span class="text-[10px] text-slate-400"><span id="statTotalCompletedInline">—</span> completed</span>
                </div>
            </div>
        </div>
        <!-- Card 2: Scheduled -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-blue-600" id="statScheduled">—</p>
                        <p class="text-xs font-medium text-slate-500">Scheduled</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📅 Upcoming</span>
                    <span class="text-[10px] text-slate-400">Awaiting conduct</span>
                </div>
            </div>
        </div>
        <!-- Card 3: Completed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600" id="statCompleted">—</p>
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Done</span>
                    <span class="text-[10px] text-slate-400">Successfully finished</span>
                </div>
            </div>
        </div>
        <!-- Card 4: Follow-ups -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-arrow-rotate-right text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600" id="statFollowUps">—</p>
                        <p class="text-xs font-medium text-slate-500">Follow-ups</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">🔄 Pending</span>
                    <span class="text-[10px] text-slate-400">Needs re-inspection</span>
                </div>
            </div>
        </div>
        <!-- Card 5: Non-Compliant -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600" id="statNonCompliant">—</p>
                        <p class="text-xs font-medium text-slate-500">Non-Compliant</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Violations</span>
                    <span class="text-[10px] text-slate-400">Immediate action</span>
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
                       id="searchInspection"
                       placeholder="Search by applicant, permit ID, or inspector..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <select id="filterResult" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Results</option>
                    <option value="compliant">Compliant</option>
                    <option value="partially_compliant">Partially Compliant</option>
                    <option value="non_compliant">Non-Compliant</option>
                </select>
                <select id="filterInspector" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Inspectors</option>
                    <?php foreach ($inspectors as $i): ?>
                        <option value="<?php echo htmlspecialchars($i['full_name'] ?? ('Inspector #' . $i['id'])); ?>">
                            <?php echo htmlspecialchars($i['full_name'] ?? ('Inspector #' . $i['id'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>From</span>
                    <input type="date" id="filterDateFrom" class="px-2.5 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                </label>
                <label class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>To</span>
                    <input type="date" id="filterDateTo" class="px-2.5 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                </label>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Inspections Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Inspection ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Permit/Applicant</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Inspector</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Scheduled Date</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Result</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="inspectionTableBody">
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-slate-400 text-sm">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading inspections...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-clipboard-list text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600" id="emptyStateTitle">No inspections match your filters</p>
            <p class="text-xs text-slate-400 mt-1" id="emptyStateSubtitle">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" id="emptyStateClearBtn" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500" id="paginationSummary">Loading…</p>
            <div class="flex gap-1" id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SCHEDULE INSPECTION MODAL                                    -->
<!-- ============================================================ -->
<div id="scheduleInspectionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-brand-medium"></i>
                Schedule Inspection
            </h3>
            <button onclick="closeModal('scheduleInspectionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="scheduleInspectionForm" class="p-6 space-y-4" onsubmit="saveScheduledInspection(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Permit/Applicant</label>
                <select id="insp_permit" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Permit</option>
                    <?php foreach ($permits as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>">
                            <?php echo htmlspecialchars(($p['permit_id'] ?? '') . ' - ' . ($p['applicant'] ?? '') . ' (' . ($p['business_type'] ?? '') . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Inspector <span class="text-rose-500">*</span></label>
                <select id="insp_inspector" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Inspector</option>
                    <?php foreach ($inspectors as $i): ?>
                        <option value="<?php echo (int)$i['id']; ?>">
                            <?php echo htmlspecialchars($i['full_name'] ?? ('Inspector #' . $i['id'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Inspector must be assigned before scheduling.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                    <input type="date" id="insp_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time</label>
                    <input type="time" id="insp_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="insp_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('scheduleInspectionModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="scheduleSubmitBtn"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-calendar-plus mr-1.5"></i> Schedule
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ============================================================ -->
<!-- CONDUCT INSPECTION MODAL – Standard Sanitation Criteria & Review -->
<!-- ============================================================ -->
<div id="conductInspectionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[92vh] flex flex-col overflow-hidden">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark">
                    <i class="fa-solid fa-clipboard-check text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 leading-tight">Sanitation Inspection &amp; Criteria Review</h3>
                    <p class="text-xs text-slate-500">Evaluate sanitation standards before approval or rejection</p>
                </div>
            </div>
            <button onclick="closeModal('conductInspectionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Establishment Banner -->
        <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div>
                    <p id="conductApplicant" class="font-bold text-slate-800 text-sm maskable">—</p>
                    <p class="text-slate-500"><span id="conductPermit" class="font-mono text-brand-dark font-semibold">—</span> • <span id="conductAddress" class="maskable">—</span></p>
                </div>
            </div>
            <!-- Live Score Pill -->
            <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-xs">
                <span class="text-[11px] font-semibold text-slate-500">Compliance Score:</span>
                <span id="conductScoreBadge" class="px-2 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700">100% (8/8)</span>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="px-6 pt-3 border-b border-slate-200 flex gap-4 bg-white">
            <button type="button" onclick="switchConductTab('criteria')" id="tabBtnCriteria" class="pb-2.5 text-xs font-bold border-b-2 border-brand-dark text-brand-dark flex items-center gap-2 transition">
                <i class="fa-solid fa-list-check"></i> 1. Sanitation Criteria Checklist
            </button>
            <button type="button" onclick="switchConductTab('review')" id="tabBtnReview" class="pb-2.5 text-xs font-semibold border-b-2 border-transparent text-slate-400 hover:text-slate-600 flex items-center gap-2 transition">
                <i class="fa-solid fa-clipboard-user"></i> 2. Review &amp; Verdict Sign-Off
            </button>
        </div>

        <!-- Form Body (Scrollable) -->
        <form id="conductInspectionForm" class="flex-1 overflow-y-auto p-6 space-y-5" onsubmit="saveConductedInspection(event)">
            <input type="hidden" id="conduct_inspection_id">

            <!-- TAB 1: CRITERIA CHECKLIST -->
            <div id="conductTabCriteria" class="space-y-4">
                <!-- Checklist Actions & Status Summary -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3.5 bg-brand-light/30 rounded-xl border border-brand-border">
                    <div>
                        <p class="text-xs font-bold text-slate-800">Standard Municipal Sanitation Criteria</p>
                        <p class="text-[11px] text-slate-500">Assess each criterion against local health and sanitation regulations.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="markAllCriteria('compliant')" class="px-2.5 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-check-double text-[10px]"></i> Mark All Passed
                        </button>
                        <button type="button" onclick="addCustomCriterionPrompt()" class="px-2.5 py-1.5 bg-white text-slate-700 border border-slate-200 rounded-lg hover:bg-slate-50 transition text-xs font-semibold flex items-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-plus text-[10px]"></i> Add Custom Criterion
                        </button>
                    </div>
                </div>

                <!-- Criteria Container -->
                <div id="criteriaListContainer" class="space-y-3">
                    <!-- Criteria cards inserted dynamically -->
                </div>

                <div class="flex justify-end pt-3 border-t border-slate-100">
                    <button type="button" onclick="switchConductTab('review')" class="px-5 py-2.5 bg-brand-dark text-white rounded-xl hover:bg-brand-medium transition text-xs font-bold flex items-center gap-2 shadow-sm">
                        Proceed to Review &amp; Verdict <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>

            <!-- TAB 2: REVIEW & VERDICT -->
            <div id="conductTabReview" class="hidden space-y-5">
                <!-- Scorecard Summary Preview -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900">Criteria Review Summary</h4>
                            <p class="text-xs text-slate-500">Live evaluation score based on checklist criteria</p>
                        </div>
                        <div class="text-right">
                            <span id="reviewScorePercentage" class="text-lg font-black text-emerald-700">100%</span>
                            <p id="reviewScoreCounts" class="text-[10px] text-slate-500">8 Passed • 0 Conditional • 0 Failed</p>
                        </div>
                    </div>

                    <!-- Mini Criteria Breakdown Table -->
                    <div class="overflow-x-auto max-h-44 overflow-y-auto rounded-lg border border-slate-200 bg-white">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left">Criterion</th>
                                    <th class="px-3 py-2 text-center w-32">Assessment</th>
                                    <th class="px-3 py-2 text-left">Inspector Observation</th>
                                </tr>
                            </thead>
                            <tbody id="reviewCriteriaTableBody" class="divide-y divide-slate-100">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Final Verdict Selection -->
                <div class="space-y-4 bg-white p-4 rounded-2xl border border-slate-200">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">
                            Overall Inspection Verdict <span class="text-rose-500">*</span>
                        </label>
                        <select id="conduct_overall" required onchange="handleVerdictChange()" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm font-semibold bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="compliant">Compliant (Passed — Recommend for Sanitary Clearance)</option>
                            <option value="partially_compliant">Partially Compliant (Conditional — Requires Follow-up Re-Inspection)</option>
                            <option value="non_compliant">Non-Compliant (Failed — Immediate Remediation / Rejection)</option>
                        </select>
                    </div>

                    <!-- APPROVED BANNER (Shown when compliant) -->
                    <div id="verdictApprovedBanner" class="p-3.5 bg-emerald-50 rounded-xl border border-emerald-200 flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-emerald-500 text-white flex items-center justify-center flex-shrink-0 text-xs mt-0.5">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-emerald-900">Recommended for Sanitary Permit Approval</p>
                            <p class="text-[11px] text-emerald-700 mt-0.5">The establishment meets municipal sanitation code standards. The sanitary permit certificate can be issued upon supervisor endorsement.</p>
                        </div>
                    </div>

                    <!-- CONDITIONAL / FOLLOW-UP SECTION (Shown when partially_compliant) -->
                    <div id="verdictConditionalSection" class="hidden p-4 bg-amber-50/70 rounded-xl border border-amber-200 space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center flex-shrink-0 text-xs mt-0.5">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-amber-900">Conditional Approval — Follow-up Re-Inspection Required</p>
                                <p class="text-[11px] text-amber-700 mt-0.5">Minor deficiencies identified. Schedule a grace period re-inspection date to verify corrective actions.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-amber-900 mb-1">Follow-up Inspection Date <span class="text-rose-500">*</span></label>
                                <input type="date" id="conduct_follow_up" class="w-full px-3 py-2 border border-amber-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-amber-500/40 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- REJECTION CRITERIA SECTION (Shown when non_compliant) -->
                    <div id="verdictRejectionSection" class="hidden p-4 bg-rose-50/80 rounded-xl border border-rose-200 space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-rose-500 text-white flex items-center justify-center flex-shrink-0 text-xs mt-0.5">
                                <i class="fa-solid fa-ban"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-rose-900">Inspection Failure / Rejection of Sanitary Permit</p>
                                <p class="text-[11px] text-rose-700 mt-0.5">Critical sanitation hazards detected. Specify the primary rejection criteria and mandatory corrective orders.</p>
                            </div>
                        </div>

                        <div class="space-y-3 pt-2">
                            <div>
                                <label class="block text-xs font-semibold text-rose-900 mb-1">Primary Rejection Criteria / Violation Category <span class="text-rose-500">*</span></label>
                                <select id="conduct_rejection_criteria" onchange="toggleConductCustomRejection()" class="w-full px-3 py-2 border border-rose-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-rose-500/40 outline-none font-medium text-slate-800">
                                    <option value="">Select primary rejection reason...</option>
                                    <option value="Critical Vector &amp; Pest Infestation">Critical Vector &amp; Pest Infestation (Rodent/Insect contamination)</option>
                                    <option value="Contaminated or Unsafe Water Supply">Contaminated or Unsafe Water Supply / Failed Potability Test</option>
                                    <option value="Hazardous Sewage &amp; Wastewater Discharge Failure">Hazardous Sewage &amp; Wastewater Discharge Failure / Non-functional Grease Trap</option>
                                    <option value="Severe Food Temperature &amp; Cross-Contamination Hazards">Severe Food Temperature &amp; Cross-Contamination Hazards</option>
                                    <option value="Absence of Mandatory Sanitary Health Certificates">Absence of Mandatory Sanitary / Health Certificates for Handlers</option>
                                    <option value="Major Structural &amp; Environmental Hazard">Major Structural &amp; Environmental Sanitation Hazard</option>
                                    <option value="Failure to Meet Minimum Municipal Standards (&lt;50% Compliance)">Failure to Meet Minimum Municipal Standards (&lt;50% Compliance)</option>
                                    <option value="Other">Other Specific Sanitation Code Violation</option>
                                </select>
                            </div>
                            <div id="conductCustomRejectionContainer" class="hidden">
                                <label class="block text-xs font-semibold text-rose-900 mb-1">Custom Rejection Reason Details <span class="text-rose-500">*</span></label>
                                <textarea id="conduct_custom_rejection" rows="2" class="w-full px-3 py-2 border border-rose-200 rounded-lg text-sm focus:ring-2 focus:ring-rose-500/40 outline-none bg-white" placeholder="Describe the specific violation causing inspection failure..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations / Corrective Orders -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                            Recommendations &amp; Corrective Actions Order
                        </label>
                        <textarea id="conduct_recommendations" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Specify instructions, corrective actions, or remediation requirements..."></textarea>
                    </div>

                    <!-- Inspector Official Sign-Off Notes -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1">
                            Inspector Sign-Off Notes &amp; General Remarks
                        </label>
                        <textarea id="conduct_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional observations, establishment representative present, etc."></textarea>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <button type="button" onclick="switchConductTab('criteria')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition text-xs font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-arrow-left text-xs"></i> Back to Criteria
                    </button>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeModal('conductInspectionModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition text-xs font-semibold">
                            Cancel
                        </button>
                        <button type="submit" id="conductSubmitBtn" class="px-5 py-2.5 bg-brand-dark text-white rounded-xl hover:bg-brand-medium transition text-xs font-bold flex items-center gap-2 shadow-sm">
                            <i class="fa-solid fa-check-circle text-sm"></i> Submit Inspection Report
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW INSPECTION MODAL                                        -->
<!-- ============================================================ -->
<div id="viewInspectionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[92vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-shield text-brand-medium"></i>
                Inspection Report &amp; Criteria Review
            </h3>
            <button onclick="closeModal('viewInspectionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="inspectionDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT INSPECTION MODAL                                        -->
<!-- ============================================================ -->
<div id="editInspectionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen text-brand-medium"></i>
                Edit Inspection
            </h3>
            <button onclick="closeModal('editInspectionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editInspectionForm" class="p-6 space-y-4">
            <input type="hidden" id="edit_inspection_id">
            <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                <div>
                    <p id="editApplicant" class="font-semibold text-slate-800 text-sm maskable">—</p>
                    <p id="editPermit" class="text-xs text-slate-400">—</p>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Inspector</label>
                <select id="edit_inspector" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Inspector</option>
                    <?php foreach ($inspectors as $i): ?>
                        <option value="<?php echo (int)$i['id']; ?>">
                            <?php echo htmlspecialchars($i['full_name'] ?? ('Inspector #' . $i['id'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                    <input type="date" id="edit_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Time</label>
                    <input type="time" id="edit_time" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                <select id="edit_status" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="scheduled">Scheduled</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="edit_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('editInspectionModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="editSubmitBtn"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
const API_URL = '../../api/inspections.php';
const PAGE_LIMIT = 5;

let currentPage = 1;
let lastTotalPages = 1;
let conductInspectionId = null;
let searchDebounceTimer = null;

function openModal(id) { ModalSystem.open(id); }
function closeModal(id) { ModalSystem.close(id); }

async function loadStats() {
    try {
        const res = await fetch(`${API_URL}?stats=true`);
        const json = await res.json();
        if (!json.success) return;
        const d = json.data;
        document.getElementById('statTotal').textContent = d.total ?? 0;
        document.getElementById('statTotalCompletedInline').textContent = d.completed ?? 0;
        document.getElementById('statScheduled').textContent = d.scheduled ?? 0;
        document.getElementById('statCompleted').textContent = d.completed ?? 0;
        document.getElementById('statFollowUps').textContent = d.follow_ups ?? 0;
        document.getElementById('statNonCompliant').textContent = d.non_compliant ?? 0;
    } catch (e) {
        console.error('Failed to load stats', e);
    }
}

function buildQueryParams(page) {
    const params = new URLSearchParams({ page, limit: PAGE_LIMIT });
    const q = document.getElementById('searchInspection').value.trim();
    const status = document.getElementById('filterStatus').value;
    const result = document.getElementById('filterResult').value;
    const inspector = document.getElementById('filterInspector').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;
    if (dateFrom && dateTo && dateFrom > dateTo) {
        showToast('The start date cannot be after the end date', 'danger');
        return null;
    }
    if (q) params.set('q', q);
    if (status) params.set('status', status);
    if (result) params.set('result', result);
    if (inspector) params.set('inspector', inspector);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);
    return params;
}

function hasActiveFilters() {
    return !!(document.getElementById('searchInspection').value.trim() ||
        document.getElementById('filterStatus').value ||
        document.getElementById('filterResult').value ||
        document.getElementById('filterInspector').value ||
        document.getElementById('filterDateFrom').value ||
        document.getElementById('filterDateTo').value);
}

async function loadInspections(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('inspectionTableBody');
    tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-10 text-center text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading inspections...</td></tr>`;

    try {
        const params = buildQueryParams(page);
        if (!params) return;
        const res = await fetch(`${API_URL}?${params.toString()}`);
        const json = await res.json();

        if (!json.success) {
            showToast(json.message || 'Failed to load inspections', 'danger');
            tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-10 text-center text-rose-500 text-sm">Failed to load inspections</td></tr>`;
            return;
        }

        renderTable(json.data, hasActiveFilters());
        lastTotalPages = json.total_pages || 1;
        renderPagination(json.page || page, lastTotalPages, json.total || 0, json.limit || PAGE_LIMIT);
    } catch (e) {
        console.error(e);
        showToast('Network error loading inspections', 'danger');
    }
}

const statusColors = {
    scheduled: 'bg-blue-100 text-blue-700',
    completed: 'bg-emerald-100 text-emerald-700',
    cancelled: 'bg-slate-100 text-slate-500'
};
const resultColors = {
    compliant: 'bg-emerald-100 text-emerald-700',
    partially_compliant: 'bg-amber-100 text-amber-700',
    non_compliant: 'bg-rose-100 text-rose-700'
};

function renderTable(rows, filtersActive = false) {
    const tbody = document.getElementById('inspectionTableBody');
    const emptyState = document.getElementById('emptyState');
    const emptyTitle = document.getElementById('emptyStateTitle');
    const emptySubtitle = document.getElementById('emptyStateSubtitle');
    const emptyClearBtn = document.getElementById('emptyStateClearBtn');

    if (!rows || rows.length === 0) {
        tbody.innerHTML = '';
        if (filtersActive) {
            emptyTitle.textContent = 'No inspections match your filters';
            emptySubtitle.textContent = 'Try adjusting your search or clearing filters';
            emptyClearBtn.style.display = 'inline-block';
        } else {
            emptyTitle.textContent = 'No inspections yet';
            emptySubtitle.textContent = 'Schedule your first inspection to get started';
            emptyClearBtn.style.display = 'none';
        }
        emptyState.style.display = 'flex';
        return;
    }
    emptyState.style.display = 'none';

    tbody.innerHTML = rows.map(i => {
        const needsFollowUp = i.status === 'completed' &&
            (i.overall_status === 'non_compliant' || i.overall_status === 'partially_compliant');

        return `
        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors">
            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${escapeHtml(i.inspection_id)}</td>
            <td class="px-4 py-3">
                <div>
                    <p class="font-semibold text-slate-800 text-sm maskable">${escapeHtml(i.applicant)}</p>
                    <p class="text-xs text-slate-400">${escapeHtml(i.permit_number)} • ${escapeHtml(i.business_type)}</p>
                </div>
            </td>
            <td class="px-4 py-3 text-slate-600 text-xs">${escapeHtml(i.inspector_name)}</td>
            <td class="px-4 py-3 text-slate-600 text-xs">
                ${formatDate(i.scheduled_date)}
                <br><span class="text-[10px] text-slate-400">${escapeHtml(i.scheduled_time || '')}</span>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColors[i.status] || statusColors.scheduled}">
                    ${capitalize(i.status)}
                </span>
            </td>
            <td class="px-4 py-3">
                ${i.overall_status ? `
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${resultColors[i.overall_status] || resultColors.partially_compliant}">
                        ${i.overall_status.replace('_', ' ').toUpperCase()}
                    </span>
                ` : '<span class="text-xs text-slate-400">—</span>'}
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="viewInspection(${i.id})" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>
                    ${i.status === 'scheduled' ? `
                    <button onclick="conductInspection(${i.id})" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Conduct">
                        <i class="fa-solid fa-clipboard-check text-sm"></i>
                    </button>` : ''}
                    ${needsFollowUp ? `
                    <button onclick="scheduleFollowUp(${i.id})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Follow-up">
                        <i class="fa-solid fa-arrow-rotate-right text-sm"></i>
                    </button>` : ''}
                    <button onclick="editInspection(${i.id})" class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                        <i class="fa-solid fa-pen text-sm"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function renderPagination(page, totalPages, total, limit) {
    const summary = document.getElementById('paginationSummary');
    const controls = document.getElementById('paginationControls');

    if (total === 0) {
        summary.textContent = 'No inspections found';
        controls.innerHTML = '';
        return;
    }

    const start = (page - 1) * limit + 1;
    const end = Math.min(page * limit, total);
    summary.innerHTML = `Showing <span class="font-semibold text-slate-700">${start}</span> to <span class="font-semibold text-slate-700">${end}</span> of <span class="font-semibold text-slate-700">${total}</span> inspections`;

    let html = `<button onclick="changePage(${page - 1})" class="px-3 py-1.5 rounded-lg text-sm ${page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${page <= 1 ? 'disabled' : ''}><i class="fa-solid fa-chevron-left text-xs"></i></button>`;
    for (let p = 1; p <= totalPages; p++) {
        html += `<button onclick="changePage(${p})" class="px-3 py-1.5 rounded-lg text-sm font-medium ${p === page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}">${p}</button>`;
    }
    html += `<button onclick="changePage(${page + 1})" class="px-3 py-1.5 rounded-lg text-sm ${page >= totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}" ${page >= totalPages ? 'disabled' : ''}><i class="fa-solid fa-chevron-right text-xs"></i></button>`;
    controls.innerHTML = html;
}

function changePage(page) {
    if (page < 1 || page > lastTotalPages) return;
    loadInspections(page);
}

document.getElementById('searchInspection').addEventListener('input', () => {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => loadInspections(1), 350);
});
['filterStatus', 'filterResult', 'filterInspector'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => loadInspections(1));
});
['filterDateFrom', 'filterDateTo'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => loadInspections(1));
});

function resetFilters() {
    document.getElementById('searchInspection').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterResult').value = '';
    document.getElementById('filterInspector').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    loadInspections(1);
}

// ============================================================
// SANITATION INSPECTION CRITERIA DEFINITIONS & SYSTEM
// ============================================================
const STANDARD_CRITERIA = [
    {
        id: 'water_supply',
        category: 'Water Supply & Potability',
        icon: 'fa-droplet',
        color: 'text-sky-600 bg-sky-50',
        description: 'Potable water source, storage cleanliness, backflow prevention, & potability compliance.',
        status: 'compliant',
        notes: ''
    },
    {
        id: 'food_safety',
        category: 'Food Safety & Handling',
        icon: 'fa-utensils',
        color: 'text-emerald-600 bg-emerald-50',
        description: 'Temperature control, raw/cooked separation, sanitary food preparation & safe storage.',
        status: 'compliant',
        notes: ''
    },
    {
        id: 'wastewater',
        category: 'Wastewater & Drainage System',
        icon: 'fa-faucet-drip',
        color: 'text-teal-600 bg-teal-50',
        description: 'Functional grease traps, unobstructed drainage flow, proper sewer/septic line discharge.',
        status: 'compliant',
        notes: ''
    },
    {
        id: 'solid_waste',
        category: 'Solid Waste Segregation & Disposal',
        icon: 'fa-trash-arrow-up',
        color: 'text-indigo-600 bg-indigo-50',
        description: 'Covered color-coded bins, segregation (bio/non-bio), regular garbage disposal & hygiene.',
        status: 'compliant',
        notes: ''
    },
    {
        id: 'pest_control',
        category: 'Vector & Vermin Control',
        icon: 'fa-shield-halved',
        color: 'text-amber-600 bg-amber-50',
        description: 'Vermin-proofing, absence of rodent/insect infestation, active certified pest control plan.',
        status: 'compliant',
        notes: ''
    },
    {
        id: 'sanitary_facilities',
        category: 'Sanitary Facilities & Handwashing',
        icon: 'fa-sink',
        color: 'text-cyan-600 bg-cyan-50',
        description: 'Clean restrooms, running water, soap, paper towels/sanitizers, & handwashing signage.',
        status: 'compliant',
        notes: ''
    },
    {
        id: 'personnel_health',
        category: 'Personnel Hygiene & Health Cards',
        icon: 'fa-id-card-clip',
        color: 'text-violet-600 bg-violet-50',
        description: 'Valid Sanitation/Health Cards for food handlers/staff, clean uniforms, hairnets, & gloves.',
        status: 'compliant',
        notes: ''
    },
    {
        id: 'premises_ventilation',
        category: 'Premises Cleanliness & Ventilation',
        icon: 'fa-wind',
        color: 'text-blue-600 bg-blue-50',
        description: 'Clean floors, walls, and ceilings; adequate illumination & functional exhaust airflow.',
        status: 'compliant',
        notes: ''
    }
];

let currentCriteria = [];
let conductInspectionData = null;

function initCriteriaState(existingFindings = null) {
    if (existingFindings && Array.isArray(existingFindings) && existingFindings.length > 0) {
        currentCriteria = existingFindings.map((f, idx) => {
            const std = STANDARD_CRITERIA.find(s => s.category.toLowerCase() === (f.category || '').toLowerCase()) || {};
            return {
                id: f.id || std.id || `custom_${idx}_${Date.now()}`,
                category: f.category || 'General Sanitation',
                icon: f.icon || std.icon || 'fa-clipboard-check',
                color: f.color || std.color || 'text-slate-600 bg-slate-100',
                description: f.description || std.description || 'Custom inspection observation',
                status: f.status || 'compliant',
                notes: f.notes || '',
                isCustom: !!f.isCustom
            };
        });
    } else {
        currentCriteria = JSON.parse(JSON.stringify(STANDARD_CRITERIA));
    }
    renderCriteriaList();
    updateCriteriaScore();
}

function renderCriteriaList() {
    const container = document.getElementById('criteriaListContainer');
    if (!container) return;

    container.innerHTML = currentCriteria.map((c, index) => {
        return `
        <div class="p-3.5 bg-white rounded-xl border border-slate-200 hover:border-slate-300 transition space-y-2.5" id="criterionCard_${index}">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="flex items-start gap-2.5">
                    <div class="w-8 h-8 rounded-lg ${c.color || 'text-slate-600 bg-slate-100'} flex items-center justify-center flex-shrink-0 text-xs mt-0.5">
                        <i class="fa-solid ${c.icon || 'fa-check'}"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-800">${escapeHtml(c.category)}</span>
                            ${c.isCustom ? `<span class="px-1.5 py-0.5 bg-slate-100 text-slate-500 rounded text-[9px] font-semibold">Custom</span>` : ''}
                        </div>
                        <p class="text-[11px] text-slate-400 leading-tight mt-0.5">${escapeHtml(c.description || '')}</p>
                    </div>
                </div>

                <!-- 3-State Status Pills -->
                <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 self-start sm:self-center flex-shrink-0">
                    <label class="cursor-pointer text-[11px] font-semibold px-2.5 py-1 rounded-lg transition flex items-center gap-1 ${c.status === 'compliant' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/60'}">
                        <input type="radio" name="criterion_status_${index}" value="compliant" ${c.status === 'compliant' ? 'checked' : ''} onchange="setCriterionStatus(${index}, 'compliant')" class="hidden">
                        <i class="fa-solid fa-check text-[10px]"></i> Pass
                    </label>
                    <label class="cursor-pointer text-[11px] font-semibold px-2.5 py-1 rounded-lg transition flex items-center gap-1 ${c.status === 'partially_compliant' ? 'bg-amber-500 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/60'}">
                        <input type="radio" name="criterion_status_${index}" value="partially_compliant" ${c.status === 'partially_compliant' ? 'checked' : ''} onchange="setCriterionStatus(${index}, 'partially_compliant')" class="hidden">
                        <i class="fa-solid fa-circle-exclamation text-[10px]"></i> Conditional
                    </label>
                    <label class="cursor-pointer text-[11px] font-semibold px-2.5 py-1 rounded-lg transition flex items-center gap-1 ${c.status === 'non_compliant' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/60'}">
                        <input type="radio" name="criterion_status_${index}" value="non_compliant" ${c.status === 'non_compliant' ? 'checked' : ''} onchange="setCriterionStatus(${index}, 'non_compliant')" class="hidden">
                        <i class="fa-solid fa-xmark text-[10px]"></i> Fail
                    </label>
                </div>
            </div>

            <!-- Notes & Remarks Field -->
            <div class="flex items-center gap-2 pt-1 border-t border-slate-100">
                <input type="text"
                       id="criterion_note_${index}"
                       placeholder="Inspector remarks / specific observations for this criterion..."
                       value="${escapeHtml(c.notes || '')}"
                       oninput="setCriterionNotes(${index}, this.value)"
                       class="w-full px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs bg-slate-50/50 focus:bg-white focus:ring-2 focus:ring-brand-medium/30 focus:border-brand-medium outline-none">
                ${c.isCustom ? `
                    <button type="button" onclick="removeCriterion(${index})" class="text-rose-500 hover:text-rose-700 p-1 text-xs" title="Remove Custom Criterion">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                ` : ''}
            </div>
        </div>`;
    }).join('');
}

function setCriterionStatus(index, status) {
    if (currentCriteria[index]) {
        currentCriteria[index].status = status;
        renderCriteriaList();
        updateCriteriaScore();
    }
}

function setCriterionNotes(index, notes) {
    if (currentCriteria[index]) {
        currentCriteria[index].notes = notes;
    }
}

function markAllCriteria(status) {
    currentCriteria.forEach(c => c.status = status);
    renderCriteriaList();
    updateCriteriaScore();
    showToast(`All criteria marked as ${status === 'compliant' ? 'Compliant' : status}`, 'info');
}

function addCriterion(category, description = '') {
    if (!category.trim()) return;
    currentCriteria.push({
        id: 'custom_' + Date.now(),
        category: category.trim(),
        icon: 'fa-plus-circle',
        color: 'text-indigo-600 bg-indigo-50',
        description: description.trim() || 'Establishment-specific criteria',
        status: 'compliant',
        notes: '',
        isCustom: true
    });
    renderCriteriaList();
    updateCriteriaScore();
}

function addCustomCriterionPrompt() {
    const name = prompt('Enter custom inspection criterion name (e.g. Swimming Pool Hygiene, Hazardous Chemical Storage):');
    if (name && name.trim()) {
        addCriterion(name);
    }
}

function removeCriterion(index) {
    if (currentCriteria[index] && currentCriteria[index].isCustom) {
        currentCriteria.splice(index, 1);
        renderCriteriaList();
        updateCriteriaScore();
    }
}

function updateCriteriaScore() {
    const total = currentCriteria.length || 1;
    let compliant = 0;
    let partial = 0;
    let nonCompliant = 0;

    currentCriteria.forEach(c => {
        if (c.status === 'compliant') compliant++;
        else if (c.status === 'partially_compliant') partial++;
        else if (c.status === 'non_compliant') nonCompliant++;
    });

    const scorePct = Math.round(((compliant + (partial * 0.5)) / total) * 100);

    // Update Header Pill
    const badge = document.getElementById('conductScoreBadge');
    if (badge) {
        badge.textContent = `${scorePct}% (${compliant}/${total})`;
        if (nonCompliant > 0 || scorePct < 60) {
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-black bg-rose-100 text-rose-700';
        } else if (partial > 0 || scorePct < 85) {
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-700';
        } else {
            badge.className = 'px-2 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700';
        }
    }

    // Update Review Tab Scorecard
    const reviewPct = document.getElementById('reviewScorePercentage');
    const reviewCounts = document.getElementById('reviewScoreCounts');
    if (reviewPct) {
        reviewPct.textContent = `${scorePct}%`;
        if (nonCompliant > 0 || scorePct < 60) {
            reviewPct.className = 'text-lg font-black text-rose-700';
        } else if (partial > 0 || scorePct < 85) {
            reviewPct.className = 'text-lg font-black text-amber-600';
        } else {
            reviewPct.className = 'text-lg font-black text-emerald-700';
        }
    }
    if (reviewCounts) {
        reviewCounts.textContent = `${compliant} Passed • ${partial} Conditional • ${nonCompliant} Failed`;
    }

    // Auto-suggest verdict
    const overallSelect = document.getElementById('conduct_overall');
    if (overallSelect) {
        if (nonCompliant > 0) {
            overallSelect.value = 'non_compliant';
        } else if (partial > 0) {
            overallSelect.value = 'partially_compliant';
        } else {
            overallSelect.value = 'compliant';
        }
        handleVerdictChange();
    }
}

function handleVerdictChange() {
    const verdict = document.getElementById('conduct_overall')?.value || 'compliant';
    const approvedBanner = document.getElementById('verdictApprovedBanner');
    const conditionalSection = document.getElementById('verdictConditionalSection');
    const rejectionSection = document.getElementById('verdictRejectionSection');
    const followUpInput = document.getElementById('conduct_follow_up');
    const rejectionSelect = document.getElementById('conduct_rejection_criteria');

    if (verdict === 'compliant') {
        approvedBanner?.classList.remove('hidden');
        conditionalSection?.classList.add('hidden');
        rejectionSection?.classList.add('hidden');
        if (followUpInput) followUpInput.required = false;
        if (rejectionSelect) rejectionSelect.required = false;
    } else if (verdict === 'partially_compliant') {
        approvedBanner?.classList.add('hidden');
        conditionalSection?.classList.remove('hidden');
        rejectionSection?.classList.add('hidden');
        if (followUpInput) {
            followUpInput.required = true;
            if (!followUpInput.value) {
                const d = new Date();
                d.setDate(d.getDate() + 7);
                followUpInput.value = d.toISOString().split('T')[0];
            }
        }
        if (rejectionSelect) rejectionSelect.required = false;
    } else { // non_compliant
        approvedBanner?.classList.add('hidden');
        conditionalSection?.classList.add('hidden');
        rejectionSection?.classList.remove('hidden');
        if (followUpInput) followUpInput.required = false;
        if (rejectionSelect) rejectionSelect.required = true;
    }
}

function toggleConductCustomRejection() {
    const select = document.getElementById('conduct_rejection_criteria');
    const customContainer = document.getElementById('conductCustomRejectionContainer');
    const customInput = document.getElementById('conduct_custom_rejection');
    if (select && customContainer) {
        if (select.value === 'Other') {
            customContainer.classList.remove('hidden');
            if (customInput) customInput.required = true;
        } else {
            customContainer.classList.add('hidden');
            if (customInput) customInput.required = false;
        }
    }
}

function switchConductTab(tab) {
    const tabCriteria = document.getElementById('conductTabCriteria');
    const tabReview = document.getElementById('conductTabReview');
    const tabBtnCriteria = document.getElementById('tabBtnCriteria');
    const tabBtnReview = document.getElementById('tabBtnReview');

    if (tab === 'review') {
        tabCriteria?.classList.add('hidden');
        tabReview?.classList.remove('hidden');

        tabBtnCriteria?.classList.remove('border-brand-dark', 'text-brand-dark', 'font-bold');
        tabBtnCriteria?.classList.add('border-transparent', 'text-slate-400', 'font-semibold');

        tabBtnReview?.classList.remove('border-transparent', 'text-slate-400', 'font-semibold');
        tabBtnReview?.classList.add('border-brand-dark', 'text-brand-dark', 'font-bold');

        renderReviewCriteriaSummary();
    } else {
        tabCriteria?.classList.remove('hidden');
        tabReview?.classList.add('hidden');

        tabBtnCriteria?.classList.add('border-brand-dark', 'text-brand-dark', 'font-bold');
        tabBtnCriteria?.classList.remove('border-transparent', 'text-slate-400', 'font-semibold');

        tabBtnReview?.classList.add('border-transparent', 'text-slate-400', 'font-semibold');
        tabBtnReview?.classList.remove('border-brand-dark', 'text-brand-dark', 'font-bold');
    }
}

function renderReviewCriteriaSummary() {
    const tbody = document.getElementById('reviewCriteriaTableBody');
    if (!tbody) return;

    tbody.innerHTML = currentCriteria.map(c => {
        const badgeMap = {
            compliant: '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Passed</span>',
            partially_compliant: '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">Conditional</span>',
            non_compliant: '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">Failed</span>'
        };
        return `
        <tr class="hover:bg-slate-50 transition">
            <td class="px-3 py-2 font-medium text-slate-800 flex items-center gap-1.5">
                <i class="fa-solid ${c.icon || 'fa-check'} text-[10px] text-slate-400"></i>
                ${escapeHtml(c.category)}
            </td>
            <td class="px-3 py-2 text-center">${badgeMap[c.status] || badgeMap.compliant}</td>
            <td class="px-3 py-2 text-slate-500 text-[11px]">${escapeHtml(c.notes || '—')}</td>
        </tr>`;
    }).join('');
}

async function viewInspection(id) {
    openModal('viewInspectionModal');
    const content = document.getElementById('inspectionDetailsContent');
    content.innerHTML = `<div class="flex items-center justify-center py-12 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading inspection details...</div>`;

    try {
        const res = await fetch(`${API_URL}?id=${id}`);
        const json = await res.json();
        if (!json.success) {
            content.innerHTML = `<p class="text-sm text-rose-500 text-center py-6">${escapeHtml(json.message || 'Inspection not found')}</p>`;
            return;
        }
        renderInspectionDetails(json.data);
        ModalSystem.refreshMasking('viewInspectionModal');
    } catch (e) {
        content.innerHTML = `<p class="text-sm text-rose-500 text-center py-6">Failed to load inspection details</p>`;
    }
}

function renderInspectionDetails(i) {
    // Decode findings
    const findings = Array.isArray(i.findings) ? i.findings : [];
    const totalCriteria = findings.length || 0;
    let compliantCount = 0;
    let partialCount = 0;
    let nonCompliantCount = 0;

    findings.forEach(f => {
        if (f.status === 'compliant') compliantCount++;
        else if (f.status === 'partially_compliant') partialCount++;
        else if (f.status === 'non_compliant') nonCompliantCount++;
    });

    const scorePct = totalCriteria > 0 ? Math.round(((compliantCount + (partialCount * 0.5)) / totalCriteria) * 100) : 100;

    // Build findings rows
    const findingsRowsHtml = findings.length > 0 ? findings.map(f => {
        const badgeClass = resultColors[f.status] || resultColors.partially_compliant;
        const iconClass = f.status === 'compliant' ? 'fa-check text-emerald-500' : (f.status === 'partially_compliant' ? 'fa-triangle-exclamation text-amber-500' : 'fa-xmark text-rose-500');
        const statusLabel = f.status === 'compliant' ? 'PASSED' : (f.status === 'partially_compliant' ? 'CONDITIONAL' : 'FAILED');

        return `
        <div class="p-3 bg-white rounded-xl border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div class="flex items-start gap-2.5">
                <div class="w-6 h-6 rounded-md bg-slate-100 flex items-center justify-center flex-shrink-0 text-xs mt-0.5">
                    <i class="fa-solid ${iconClass}"></i>
                </div>
                <div>
                    <p class="font-bold text-slate-800 text-xs">${escapeHtml(f.category)}</p>
                    <p class="text-[11px] text-slate-500 mt-0.5">${escapeHtml(f.notes || 'No remarks noted')}</p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold ${badgeClass} self-start sm:self-center flex-shrink-0">
                ${statusLabel}
            </span>
        </div>`;
    }).join('') : '<p class="text-xs text-slate-400 py-2">No individual criteria findings recorded</p>';

    // Outcome Banner
    let outcomeBannerHtml = '';
    if (i.status === 'completed') {
        if (i.overall_status === 'compliant') {
            outcomeBannerHtml = `
            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 text-sm">
                    <i class="fa-solid fa-certificate"></i>
                </div>
                <div>
                    <h5 class="text-xs font-bold text-emerald-950 uppercase tracking-wide">Inspection Result: Approved / Compliant</h5>
                    <p class="text-xs text-emerald-800 mt-0.5">The establishment satisfied municipal sanitation standards with a compliance rating of <strong>${scorePct}%</strong>.</p>
                </div>
            </div>`;
        } else if (i.overall_status === 'partially_compliant') {
            outcomeBannerHtml = `
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 text-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h5 class="text-xs font-bold text-amber-950 uppercase tracking-wide">Inspection Result: Conditionally Passed (Follow-Up Required)</h5>
                    <p class="text-xs text-amber-800 mt-0.5">Minor deficiencies noted (${scorePct}% compliance score). Re-inspection scheduled for <strong>${formatDate(i.follow_up_date)}</strong>.</p>
                </div>
            </div>`;
        } else {
            outcomeBannerHtml = `
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-rose-600 text-white flex items-center justify-center flex-shrink-0 text-sm">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div>
                    <h5 class="text-xs font-bold text-rose-950 uppercase tracking-wide">Inspection Result: Failed / Non-Compliant</h5>
                    <p class="text-xs text-rose-800 mt-0.5">Critical sanitation hazards identified (${scorePct}% compliance score). Establishment must complete mandatory corrective remediation before re-application.</p>
                </div>
            </div>`;
        }
    }

    const needsFollowUp = i.status === 'completed' &&
        (i.overall_status === 'non_compliant' || i.overall_status === 'partially_compliant');

    document.getElementById('inspectionDetailsContent').innerHTML = `
        <div class="space-y-5">
            <!-- Header Profile -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-black text-lg flex-shrink-0">
                        ${escapeHtml((i.applicant || '?').charAt(0))}
                    </div>
                    <div>
                        <h4 class="text-base font-bold text-slate-900 maskable">${escapeHtml(i.applicant)}</h4>
                        <p class="text-xs text-slate-500 font-medium">${escapeHtml(i.inspection_id)} • <span class="font-mono text-brand-dark">${escapeHtml(i.permit_number)}</span> • ${escapeHtml(i.business_type)}</p>
                        <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${statusColors[i.status] || statusColors.scheduled}">
                                ${i.status.toUpperCase()}
                            </span>
                            ${i.overall_status ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${resultColors[i.overall_status] || resultColors.partially_compliant}">${i.overall_status.replace('_', ' ').toUpperCase()}</span>` : ''}
                            ${i.status === 'completed' ? `<span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-slate-900 text-white">Score: ${scorePct}%</span>` : ''}
                        </div>
                    </div>
                </div>

                ${i.status === 'completed' ? `
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-right">
                    <p class="text-[10px] text-slate-400 font-semibold uppercase">Criteria Scorecard</p>
                    <p class="text-sm font-black text-slate-800">${compliantCount} Pass / ${partialCount} Cond / ${nonCompliantCount} Fail</p>
                </div>` : ''}
            </div>

            <!-- Outcome Status Alert Banner -->
            ${outcomeBannerHtml}

            <!-- Inspection Key Metadata -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                <div><p class="text-[10px] text-slate-400 font-semibold uppercase">Inspector</p><p class="font-bold text-slate-800 mt-0.5">${escapeHtml(i.inspector_name)}</p></div>
                <div><p class="text-[10px] text-slate-400 font-semibold uppercase">Scheduled Date</p><p class="font-semibold text-slate-800 mt-0.5">${formatDate(i.scheduled_date)} ${escapeHtml(i.scheduled_time || '')}</p></div>
                <div><p class="text-[10px] text-slate-400 font-semibold uppercase">Conducted Date</p><p class="font-semibold text-slate-800 mt-0.5">${formatDateTime(i.conducted_date)}</p></div>
                <div><p class="text-[10px] text-slate-400 font-semibold uppercase">Follow-up Date</p><p class="font-semibold ${i.follow_up_date ? 'text-amber-700 font-bold' : 'text-slate-800'} mt-0.5">${formatDate(i.follow_up_date)}</p></div>
            </div>

            <!-- Criteria Assessment Checklist Breakdown -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check text-brand-medium"></i> Sanitation Criteria Checklist (${totalCriteria} Evaluated)
                    </h5>
                </div>
                <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                    ${findingsRowsHtml}
                </div>
            </div>

            <!-- Recommendations & Notes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                ${i.recommendations ? `
                <div class="p-3.5 bg-brand-light/30 rounded-xl border border-brand-border space-y-1">
                    <h5 class="text-xs font-bold text-brand-dark uppercase tracking-wide flex items-center gap-1.5">
                        <i class="fa-solid fa-clipboard-list"></i> Corrective Orders &amp; Recommendations
                    </h5>
                    <p class="text-xs text-slate-800 whitespace-pre-line">${escapeHtml(i.recommendations)}</p>
                </div>` : ''}

                ${i.notes ? `
                <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                        <i class="fa-solid fa-note-sticky"></i> Inspector Sign-Off Remarks
                    </h5>
                    <p class="text-xs text-slate-800 whitespace-pre-line">${escapeHtml(i.notes)}</p>
                </div>` : ''}
            </div>

            <!-- Modal Footer Actions -->
            <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                <button onclick="closeModal('viewInspectionModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition text-xs font-semibold">Close</button>
                ${i.status === 'scheduled' ? `<button onclick="closeModal('viewInspectionModal'); conductInspection(${i.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition text-xs font-bold flex items-center gap-1.5 shadow-sm"><i class="fa-solid fa-clipboard-check"></i> Conduct Inspection</button>` : ''}
                ${needsFollowUp ? `<button onclick="closeModal('viewInspectionModal'); scheduleFollowUp(${i.id}, ${i.permit_id || 0})" class="px-4 py-2 bg-amber-600 text-white rounded-xl hover:bg-amber-700 transition text-xs font-bold flex items-center gap-1.5 shadow-sm"><i class="fa-solid fa-calendar-plus"></i> Schedule Follow-up</button>` : ''}
            </div>
        </div>
    `;
}

async function saveScheduledInspection(event) {
    event.preventDefault();
    const btn = document.getElementById('scheduleSubmitBtn');
    btn.disabled = true;

    const payload = {
        permit_id: document.getElementById('insp_permit').value,
        inspector_id: document.getElementById('insp_inspector').value,
        scheduled_date: document.getElementById('insp_date').value,
        scheduled_time: document.getElementById('insp_time').value,
        notes: document.getElementById('insp_notes').value
    };

    if (!payload.inspector_id) {
        showToast('Please select an inspector.', 'danger');
        btn.disabled = false;
        return;
    }

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) {
            showToast(json.message || 'Failed to schedule inspection', 'danger');
            return;
        }
        showToast('Inspection scheduled successfully!', 'success');
        closeModal('scheduleInspectionModal');
        document.getElementById('scheduleInspectionForm').reset();
        loadStats();
        loadInspections(1);
    } catch (e) {
        showToast('Network error scheduling inspection', 'danger');
    } finally {
        btn.disabled = false;
    }
}

async function conductInspection(id) {
    conductInspectionId = id;
    switchConductTab('criteria');
    openModal('conductInspectionModal');

    document.getElementById('conductApplicant').textContent = 'Loading…';
    document.getElementById('conductPermit').textContent = '';
    document.getElementById('conductAddress').textContent = '';
    document.getElementById('conduct_overall').value = 'compliant';
    document.getElementById('conduct_recommendations').value = '';
    document.getElementById('conduct_follow_up').value = '';
    document.getElementById('conduct_notes').value = '';
    if (document.getElementById('conduct_rejection_criteria')) {
        document.getElementById('conduct_rejection_criteria').value = '';
    }
    if (document.getElementById('conduct_custom_rejection')) {
        document.getElementById('conduct_custom_rejection').value = '';
    }
    toggleConductCustomRejection();

    try {
        const res = await fetch(`${API_URL}?id=${id}`);
        const json = await res.json();
        if (!json.success) {
            showToast(json.message || 'Inspection not found', 'danger');
            closeModal('conductInspectionModal');
            return;
        }
        const i = json.data;
        conductInspectionData = i;
        document.getElementById('conduct_inspection_id').value = i.id;
        document.getElementById('conductApplicant').textContent = i.applicant || 'Unknown Applicant';
        document.getElementById('conductPermit').textContent = i.permit_number || '';
        document.getElementById('conductAddress').textContent = (i.address || i.business_type || '');

        ['conductApplicant', 'conductAddress'].forEach(elId => {
            const el = document.getElementById(elId);
            if (el) {
                el.removeAttribute('data-real');
                el.removeAttribute('data-masked');
            }
        });
        ModalSystem.refreshMasking('conductInspectionModal');

        // Initialize criteria checklist state
        initCriteriaState(i.findings);
    } catch (e) {
        showToast('Failed to load inspection details', 'danger');
    }
}

async function saveConductedInspection(event) {
    event.preventDefault();
    const id = conductInspectionId;
    if (!id) return;
    const btn = document.getElementById('conductSubmitBtn');
    btn.disabled = true;

    const overallStatus = document.getElementById('conduct_overall').value;
    let recommendations = document.getElementById('conduct_recommendations').value.trim();
    let followUpDate = document.getElementById('conduct_follow_up').value || null;

    // If non-compliant, validate and attach rejection reason
    if (overallStatus === 'non_compliant') {
        const rejectionSelect = document.getElementById('conduct_rejection_criteria');
        const customRejection = document.getElementById('conduct_custom_rejection')?.value.trim() || '';
        let primaryReason = rejectionSelect ? rejectionSelect.value : '';

        if (!primaryReason) {
            showToast('Please select the primary rejection criteria.', 'warning');
            btn.disabled = false;
            switchConductTab('review');
            rejectionSelect?.focus();
            return;
        }

        if (primaryReason === 'Other') {
            if (!customRejection) {
                showToast('Please specify the custom rejection reason details.', 'warning');
                btn.disabled = false;
                switchConductTab('review');
                document.getElementById('conduct_custom_rejection')?.focus();
                return;
            }
            primaryReason = customRejection;
        }

        const rejectionNote = `[REJECTION CRITERIA: ${primaryReason}]`;
        if (!recommendations.includes('[REJECTION CRITERIA:')) {
            recommendations = recommendations ? `${rejectionNote} ${recommendations}` : rejectionNote;
        }
    }

    if (overallStatus === 'partially_compliant' && !followUpDate) {
        showToast('Please provide a follow-up re-inspection date.', 'warning');
        btn.disabled = false;
        switchConductTab('review');
        document.getElementById('conduct_follow_up')?.focus();
        return;
    }

    const payload = {
        findings: currentCriteria,
        overall_status: overallStatus,
        recommendations: recommendations,
        follow_up_date: followUpDate,
        notes: document.getElementById('conduct_notes').value.trim()
    };

    try {
        const res = await fetch(`${API_URL}?id=${id}&action=conduct`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) {
            showToast(json.message || 'Failed to submit inspection report', 'danger');
            return;
        }
        const outcomeMsg = overallStatus === 'compliant' ? 'Inspection Passed & Approved!' : (overallStatus === 'partially_compliant' ? 'Conditional Inspection Report saved!' : 'Inspection Non-Compliance / Rejection Report submitted.');
        showToast(outcomeMsg, 'success');
        closeModal('conductInspectionModal');
        loadStats();
        loadInspections(currentPage);
    } catch (e) {
        showToast('Network error submitting report', 'danger');
    } finally {
        btn.disabled = false;
    }
}

function scheduleFollowUp(inspectionId, permitId) {
    openModal('scheduleInspectionModal');
    if (permitId && document.getElementById('insp_permit')) {
        document.getElementById('insp_permit').value = permitId;
    }
    const dateInput = document.getElementById('insp_date');
    if (dateInput) {
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        dateInput.value = nextWeek.toISOString().split('T')[0];
    }
    const notesInput = document.getElementById('insp_notes');
    if (notesInput) {
        notesInput.value = `Follow-up re-inspection for previous inspection #${inspectionId}`;
    }
}

let editInspectionId = null;
let editFormValidation = null;

async function editInspection(id) {
    editInspectionId = id;
    ModalSystem.open('editInspectionModal');

    document.getElementById('editApplicant').textContent = 'Loading…';
    document.getElementById('editPermit').textContent = '';
    ['editApplicant'].forEach(elId => {
        const el = document.getElementById(elId);
        el.removeAttribute('data-real');
        el.removeAttribute('data-masked');
    });

    try {
        const res = await fetch(`${API_URL}?id=${id}`);
        const json = await res.json();
        if (!json.success) {
            showToast(json.message || 'Inspection not found', 'danger');
            ModalSystem.close('editInspectionModal');
            return;
        }
        const i = json.data;
        document.getElementById('edit_inspection_id').value = i.id;
        document.getElementById('editApplicant').textContent = i.applicant;
        document.getElementById('editPermit').textContent = i.permit_number + ' • ' + i.business_type;
        document.getElementById('edit_inspector').value = i.inspector_id || '';
        document.getElementById('edit_date').value = i.scheduled_date || '';
        document.getElementById('edit_time').value = (i.scheduled_time || '').slice(0, 5);
        document.getElementById('edit_status').value = i.status || 'scheduled';
        document.getElementById('edit_notes').value = i.notes || '';

        document.getElementById('editApplicant').removeAttribute('data-real');
        document.getElementById('editApplicant').removeAttribute('data-masked');
        ModalSystem.refreshMasking('editInspectionModal');

        setTimeout(() => { if (editFormValidation) editFormValidation.isValid(); }, 150);
    } catch (e) {
        showToast('Failed to load inspection details', 'danger');
        ModalSystem.close('editInspectionModal');
    }
}

async function saveEditedInspection(event, helpers) {
    const id = editInspectionId;
    if (!id) return;
    const btn = document.getElementById('editSubmitBtn');
    btn.disabled = true;

    const payload = {
        inspector_id: document.getElementById('edit_inspector').value,
        scheduled_date: document.getElementById('edit_date').value,
        scheduled_time: document.getElementById('edit_time').value,
        status: document.getElementById('edit_status').value,
        notes: document.getElementById('edit_notes').value
    };

    try {
        const res = await fetch(`${API_URL}?id=${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) {
            showToast(json.message || 'Failed to update inspection', 'danger');
            return;
        }
        showToast('Inspection updated successfully!', 'success');
        ModalSystem.close('editInspectionModal');
        loadStats();
        loadInspections(currentPage);
    } catch (e) {
        showToast('Network error updating inspection', 'danger');
    } finally {
        btn.disabled = false;
    }
}

function scheduleFollowUp(id) {
    showToast('Follow-up scheduling UI coming soon (inspection #' + id + ')', 'info');
}

function showToast(message, type = 'success') {
    const map = { danger: 'error', success: 'success', info: 'info', warning: 'warning' };
    const fn = map[type] || 'success';
    ModalSystem.toast[fn](message);
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
}
function formatDateTime(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('insp_date');
    if (dateInput) {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        dateInput.value = tomorrow.toISOString().split('T')[0];
    }
    loadStats();
    loadInspections(1);

    editFormValidation = ModalSystem.validateForm('editInspectionModal', {
        fields: {
            'edit_inspector': { label: 'Inspector' },
            'edit_date': { label: 'Date' },
            'edit_time': { label: 'Time' },
            'edit_status': { label: 'Status' }
        },
        submitButtonId: 'editSubmitBtn',
        onSubmit: saveEditedInspection
    });
});
</script>

<?php include_once '../../includes/footer.php'; ?>