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

// These two model requires assume this view lives two levels under the
// project root (same depth as your other module views). Adjust the path
// if your Views folder sits somewhere else.
require_once __DIR__ . '/../../app/Models/Permit.php';
require_once __DIR__ . '/../../app/Models/Employee.php';

$title = 'Inspections';

// Permits for the "Schedule Inspection" dropdown. Only permits that are
// actually approved/active make sense to inspect — adjust the filter key
// below to match whatever status field/value your Permit model uses.
$permits = [];
try {
    $permitModel = new Permit();
    $permits = $permitModel->all();
} catch (\Throwable $e) {
    error_log('Inspections view: failed to load permits - ' . $e->getMessage());
}

// Employees for the Inspector dropdown/filter. TODO: if your employees
// table has a role/department column that distinguishes inspectors,
// filter for it here (e.g. array_filter by $e['department'] === 'Sanitation')
// instead of listing every employee.
$inspectors = [];
try {
    $employeeModel = new Employee();
    $inspectors = $employeeModel->all();
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

    <!-- ============================================================ -->
    <!-- KPI CARDS - values populated via loadStats() -->
    <!-- ============================================================ -->
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
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Inspector</label>
                <select id="insp_inspector" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
<!-- CONDUCT INSPECTION MODAL                                     -->
<!-- ============================================================ -->
<div id="conductInspectionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check text-brand-medium"></i>
                Conduct Inspection
            </h3>
            <button onclick="closeModal('conductInspectionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="conductInspectionForm" class="p-6 space-y-4" onsubmit="saveConductedInspection(event)">
            <input type="hidden" id="conduct_inspection_id">
            <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                <div>
                    <p id="conductApplicant" class="font-semibold text-slate-800 text-sm maskable">—</p>
                    <p id="conductPermit" class="text-xs text-slate-400">—</p>
                    <p id="conductAddress" class="text-xs text-slate-400 maskable">—</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Findings</label>
                <div class="space-y-3" id="findingsContainer"></div>
                <button type="button" onclick="addFinding()" class="mt-2 text-xs font-semibold text-brand-medium hover:text-brand-dark transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Finding
                </button>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Overall Status</label>
                <select id="conduct_overall" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="compliant">Compliant</option>
                    <option value="partially_compliant">Partially Compliant</option>
                    <option value="non_compliant">Non-Compliant</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recommendations</label>
                <textarea id="conduct_recommendations" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Follow-up Date (if needed)</label>
                <input type="date" id="conduct_follow_up" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                <p class="text-[11px] text-slate-400 mt-1">Requires the <code>follow_up_date</code> column migration — see notes.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="conduct_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('conductInspectionModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="conductSubmitBtn"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Submit Report
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW INSPECTION MODAL                                        -->
<!-- ============================================================ -->
<div id="viewInspectionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Inspection Details</h3>
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
<!-- JAVASCRIPT - everything below talks to api/inspections.php  -->
<!-- ============================================================ -->
<script>
    // Adjust this to the actual path of your API endpoint relative to this page.
    const API_URL = '../../api/inspections.php';
    const PAGE_LIMIT = 5;

    let currentPage = 1;
    let lastTotalPages = 1;
    let conductInspectionId = null;
    let searchDebounceTimer = null;

    // ============================================================
    // MODAL FUNCTIONS - delegate to ModalSystem (handles masking,
    // backdrop click, and Escape globally already)
    // ============================================================
    function openModal(id) { ModalSystem.open(id); }
    function closeModal(id) { ModalSystem.close(id); }

    // ============================================================
    // STATS (KPI cards)
    // ============================================================
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

    // ============================================================
    // TABLE LOADING (server-side pagination + filtering)
    // ============================================================
    function buildQueryParams(page) {
        const params = new URLSearchParams({ page, limit: PAGE_LIMIT });
        const q = document.getElementById('searchInspection').value.trim();
        const status = document.getElementById('filterStatus').value;
        const result = document.getElementById('filterResult').value;
        const inspector = document.getElementById('filterInspector').value;
        if (q) params.set('q', q);
        if (status) params.set('status', status);
        if (result) params.set('result', result);
        if (inspector) params.set('inspector', inspector);
        return params;
    }

    function hasActiveFilters() {
        return !!(
            document.getElementById('searchInspection').value.trim() ||
            document.getElementById('filterStatus').value ||
            document.getElementById('filterResult').value ||
            document.getElementById('filterInspector').value
        );
    }

    async function loadInspections(page = 1) {
        currentPage = page;
        const tbody = document.getElementById('inspectionTableBody');
        tbody.innerHTML = `<tr><td colspan="7" class="px-4 py-10 text-center text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading inspections...</td></tr>`;

        try {
            const params = buildQueryParams(page);
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

    // ============================================================
    // SEARCH & FILTER -> re-query the API (server-side, not client-side)
    // ============================================================
    document.getElementById('searchInspection').addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => loadInspections(1), 350);
    });
    ['filterStatus', 'filterResult', 'filterInspector'].forEach(id => {
        document.getElementById(id).addEventListener('change', () => loadInspections(1));
    });

    function resetFilters() {
        document.getElementById('searchInspection').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterResult').value = '';
        document.getElementById('filterInspector').value = '';
        loadInspections(1);
    }

    // ============================================================
    // FINDINGS MANAGEMENT (conduct modal)
    // ============================================================
    function findingRowHtml(category = '', status = 'compliant', notes = '') {
        return `
            <div class="finding-item p-3 bg-slate-50 rounded-lg border border-slate-200">
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Category</label>
                        <input type="text" class="finding-category w-full px-2 py-1 border border-slate-200 rounded text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. Sanitation" value="${escapeHtml(category)}">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Status</label>
                        <select class="finding-status w-full px-2 py-1 border border-slate-200 rounded text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="compliant" ${status === 'compliant' ? 'selected' : ''}>Compliant</option>
                            <option value="partially_compliant" ${status === 'partially_compliant' ? 'selected' : ''}>Partially Compliant</option>
                            <option value="non_compliant" ${status === 'non_compliant' ? 'selected' : ''}>Non-Compliant</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-slate-500 mb-1">Notes</label>
                        <input type="text" class="finding-notes w-full px-2 py-1 border border-slate-200 rounded text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Details..." value="${escapeHtml(notes)}">
                    </div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="mt-1 text-xs text-rose-500 hover:text-rose-700 transition">
                    <i class="fa-solid fa-trash-can mr-1"></i> Remove
                </button>
            </div>`;
    }

    function addFinding() {
        document.getElementById('findingsContainer').insertAdjacentHTML('beforeend', findingRowHtml());
    }

    // ============================================================
    // VIEW INSPECTION
    // ============================================================
    async function viewInspection(id) {
        openModal('viewInspectionModal');
        const content = document.getElementById('inspectionDetailsContent');
        content.innerHTML = `<div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...</div>`;

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
            content.innerHTML = `<p class="text-sm text-rose-500 text-center py-6">Failed to load inspection</p>`;
        }
    }

    function renderInspectionDetails(i) {
        const findingsHtml = (i.findings && i.findings.length > 0) ? i.findings.map(f => `
            <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-slate-200">
                <div>
                    <p class="font-semibold text-slate-800 text-sm">${escapeHtml(f.category)}</p>
                    <p class="text-xs text-slate-500">${escapeHtml(f.notes || 'No notes')}</p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${resultColors[f.status] || resultColors.partially_compliant}">
                    ${f.status.replace('_', ' ').toUpperCase()}
                </span>
            </div>
        `).join('') : '<p class="text-xs text-slate-400">No findings recorded</p>';

        const needsFollowUp = i.status === 'completed' &&
            (i.overall_status === 'non_compliant' || i.overall_status === 'partially_compliant');

        document.getElementById('inspectionDetailsContent').innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                        ${escapeHtml((i.applicant || '?').charAt(0))}
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 maskable">${escapeHtml(i.applicant)}</h4>
                        <p class="text-sm text-slate-500">${escapeHtml(i.inspection_id)} • ${escapeHtml(i.permit_number)}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[i.status] || statusColors.scheduled}">
                            ${i.status.toUpperCase()}
                        </span>
                        ${i.overall_status ? `<span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ml-1 ${resultColors[i.overall_status] || resultColors.partially_compliant}">${i.overall_status.replace('_', ' ').toUpperCase()}</span>` : ''}
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="text-xs text-slate-400 font-semibold">Inspector</p><p class="text-sm text-slate-800">${escapeHtml(i.inspector_name)}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Business Type</p><p class="text-sm text-slate-800">${escapeHtml(i.business_type)}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Scheduled Date</p><p class="text-sm text-slate-800">${formatDate(i.scheduled_date)} at ${escapeHtml(i.scheduled_time || '')}</p></div>
                    ${i.conducted_date ? `<div><p class="text-xs text-slate-400 font-semibold">Conducted Date</p><p class="text-sm text-slate-800">${formatDateTime(i.conducted_date)}</p></div>` : ''}
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h5 class="text-sm font-bold text-slate-700 mb-2">📋 Findings</h5>
                    <div class="space-y-2">${findingsHtml}</div>
                </div>
                ${i.recommendations ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">Recommendations</h5><p class="text-sm text-slate-800">${escapeHtml(i.recommendations)}</p></div>` : ''}
                ${i.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${escapeHtml(i.notes)}</p></div>` : ''}
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('viewInspectionModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    ${i.status === 'scheduled' ? `<button onclick="closeModal('viewInspectionModal'); conductInspection(${i.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-clipboard-check mr-1.5"></i> Conduct</button>` : ''}
                    ${needsFollowUp ? `<button onclick="closeModal('viewInspectionModal'); scheduleFollowUp(${i.id})" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-semibold"><i class="fa-solid fa-arrow-rotate-right mr-1.5"></i> Follow-up</button>` : ''}
                </div>
            </div>
        `;
    }

    // ============================================================
    // SCHEDULE INSPECTION -> POST create
    // ============================================================
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

    // ============================================================
    // CONDUCT INSPECTION -> POST ?id=X&action=conduct
    // ============================================================
    async function conductInspection(id) {
        conductInspectionId = id;
        openModal('conductInspectionModal');

        document.getElementById('conductApplicant').textContent = 'Loading…';
        document.getElementById('conductPermit').textContent = '';
        document.getElementById('conductAddress').textContent = '';
        document.getElementById('findingsContainer').innerHTML = findingRowHtml('Sanitation');
        document.getElementById('conduct_overall').value = 'partially_compliant';
        document.getElementById('conduct_recommendations').value = '';
        document.getElementById('conduct_follow_up').value = '';
        document.getElementById('conduct_notes').value = '';

        try {
            const res = await fetch(`${API_URL}?id=${id}`);
            const json = await res.json();
            if (!json.success) {
                showToast(json.message || 'Inspection not found', 'danger');
                closeModal('conductInspectionModal');
                return;
            }
            const i = json.data;
            document.getElementById('conduct_inspection_id').value = i.id;
            document.getElementById('conductApplicant').textContent = i.applicant;
            document.getElementById('conductPermit').textContent = i.permit_number;
            document.getElementById('conductAddress').textContent = i.address || '';
            // Clear stale masking cache from placeholder text, then reapply on real data
            ['conductApplicant', 'conductAddress'].forEach(elId => {
                const el = document.getElementById(elId);
                el.removeAttribute('data-real');
                el.removeAttribute('data-masked');
            });
            ModalSystem.refreshMasking('conductInspectionModal');
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

        const findings = [];
        document.querySelectorAll('.finding-item').forEach(item => {
            const category = item.querySelector('.finding-category')?.value || '';
            const status = item.querySelector('.finding-status')?.value || 'compliant';
            const notes = item.querySelector('.finding-notes')?.value || '';
            if (category) findings.push({ category, status, notes });
        });

        const payload = {
            findings,
            overall_status: document.getElementById('conduct_overall').value,
            recommendations: document.getElementById('conduct_recommendations').value,
            follow_up_date: document.getElementById('conduct_follow_up').value || null,
            notes: document.getElementById('conduct_notes').value
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
            showToast(json.message || 'Inspection completed successfully!', 'success');
            closeModal('conductInspectionModal');
            loadStats();
            loadInspections(currentPage);
        } catch (e) {
            showToast('Network error submitting report', 'danger');
        } finally {
            btn.disabled = false;
        }
    }

    // ============================================================
    // EDIT INSPECTION -> PUT/PATCH ?id=X (controller's update())
    // ============================================================
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

            // Fields were set programmatically (no input/change event fired).
            // ModalSystem.validateForm resets the form ~100ms after the modal
            // opens, so re-validate slightly after that to unlock Save.
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

    // ============================================================
    // FOLLOW-UP (placeholder - wire up once that flow exists)
    // ============================================================
    function scheduleFollowUp(id) {
        showToast('Follow-up scheduling UI coming soon (inspection #' + id + ')', 'info');
    }

    // ============================================================
    // TOAST NOTIFICATIONS - proxies to the shared ModalSystem.toast
    // (kept as showToast() so existing call sites don't need to change)
    // ============================================================
    function showToast(message, type = 'success') {
        const map = { danger: 'error', success: 'success', info: 'info', warning: 'warning' };
        const fn = map[type] || 'success';
        ModalSystem.toast[fn](message);
    }

    // ============================================================
    // HELPERS
    // ============================================================
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

    // ============================================================
    // INIT
    // ============================================================
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