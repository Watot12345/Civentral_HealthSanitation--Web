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
// 1. PHP BACKEND - Initialize
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('sanitation permits');
require_once __DIR__ . '/../../includes/data-mask.php';
require_once __DIR__ . '/../../app/Models/Barangay.php';

$title = 'Permit Records';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$barangayModel = new Barangay();
$barangayOptions = $barangayModel->allForSurveillance();
?>

<!-- ============================================================ -->
<!-- 2. HTML + Tailwind CSS                                      -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Permit Records</h2>
            <p class="text-sm text-slate-500 mt-0.5">View all permit records, history, and status</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('exportPermitRecordsModal')"
                class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-download text-xs"></i> Export Records
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS                                           -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
        <!-- Card 1: Total Permits -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-file-lines text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900" id="statTotal">0</p>
                        <p class="text-xs font-medium text-slate-500">Total Permits</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-file-lines mr-1"></i>All permits</span>
                    <span class="text-[10px] text-slate-400"><span id="statRenewalsMini">0</span> renewals</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Active -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600" id="statActive">0</p>
                        <p class="text-xs font-medium text-slate-500">Active</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-circle-check mr-1"></i><span id="statActiveRate">0%</span></span>
                    <span class="text-[10px] text-slate-400">Compliance</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Pending -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600" id="statPending">0</p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-hourglass-half mr-1"></i>Awaiting</span>
                    <span class="text-[10px] text-slate-400">Initial review</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Under Review -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-clipboard-list text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-blue-600" id="statUnderReview">0</p>
                        <p class="text-xs font-medium text-slate-500">Under Review</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-magnifying-glass mr-1"></i>In progress</span>
                    <span class="text-[10px] text-slate-400">Being evaluated</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Expired -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-slate-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-slate-500 to-slate-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-slate-200">
                        <i class="fa-solid fa-calendar-xmark text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-600" id="statExpired">0</p>
                        <p class="text-xs font-medium text-slate-500">Expired</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold"><i class="fa-solid fa-calendar-xmark mr-1"></i>Overdue</span>
                    <span class="text-[10px] text-slate-400">Needs renewal</span>
                </div>
            </div>
        </div>

        <!-- Card 6: Rejected -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-circle-xmark text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600" id="statRejected">0</p>
                        <p class="text-xs font-medium text-slate-500">Rejected</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-circle-xmark mr-1"></i>Denied</span>
                    <span class="text-[10px] text-slate-400">Non-compliant</span>
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
                    id="searchPermitRecord"
                    placeholder="Search by permit ID, applicant, or business type..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="approved">Active</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="expired">Expired</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="filterType" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="Food Establishment">Food Establishment</option>
                    <option value="Market Vendor">Market Vendor</option>
                    <option value="Bakery">Bakery</option>
                    <option value="Recreational Facility">Recreational Facility</option>
                    <option value="Retail Store">Retail Store</option>
                    <option value="Pharmacy">Pharmacy</option>
                    <option value="Agricultural">Agricultural</option>
                    <option value="Office/Commercial">Office/Commercial</option>
                    <option value="Hotel/Lodging">Hotel/Lodging</option>
                </select>
                <button type="button" onclick="openBarangayFilterModal()" id="barangayFilterBtn"
                    class="px-3.5 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-700 hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-location-dot text-slate-400"></i>
                    <span id="barangayFilterLabel">Barangay</span>
                    <span id="barangayFilterBadge" class="hidden px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-brand-light text-brand-dark border border-brand-border">Active</span>
                </button>
                <button type="button" onclick="openSpecificDateModal()" id="specificDateBtn"
                    class="px-3.5 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-700 hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-calendar-days text-slate-400"></i>
                    <span id="specificDateLabel">Specific Date</span>
                    <span id="dateFilterBadge" class="hidden px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-brand-light text-brand-dark border border-brand-border">Active</span>
                </button>
            </div>
        </div>
        <!-- Quick Filter Buttons -->
        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-100">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Quick Filters:</span>
            <button onclick="quickFilter('all')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all active:bg-brand-dark active:text-white active:border-brand-dark">
                All
            </button>
            <button onclick="quickFilter('approved')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all" data-status="approved">
                <i class="fa-solid fa-circle-check mr-1"></i>Active
            </button>
            <button onclick="quickFilter('expired')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all" data-status="expired">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i>Expired
            </button>
            <button onclick="quickFilter('pending')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all" data-status="pending">
                <i class="fa-solid fa-hourglass-half mr-1"></i>Pending
            </button>
            <button onclick="quickFilter('under_review')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all" data-status="under_review">
                <i class="fa-solid fa-clipboard-list mr-1"></i>Under Review
            </button>
            <button onclick="quickFilter('rejected')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all" data-status="rejected">
                <i class="fa-solid fa-circle-xmark mr-1"></i>Rejected
            </button>
        </div>
    </div>

    <!-- Permits Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Permit ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Applicant</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Business Type</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Barangay</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Fee</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Requirements</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Expiry</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Renewals</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="permitRecordTableBody">
                    <!-- Loaded via API -->
                </tbody>
            </table>
        </div>

        <!-- Loading State -->
        <div id="loadingState" class="flex flex-col items-center justify-center py-14 text-center">
            <div class="w-10 h-10 border-4 border-brand-light border-t-brand-dark rounded-full animate-spin mb-3"></div>
            <p class="text-sm font-semibold text-slate-600">Loading permits...</p>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-file-circle-xmark text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No permits match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or filter choices</p>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500" id="paginationInfo">
                Loading...
            </p>
            <div class="flex gap-1" id="paginationButtons">
                <!-- Generated dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- SPECIFIC DATE FILTER MODAL -->
<div id="specificDateModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-brand-medium"></i> Specific Date
            </h3>
            <button onclick="closeModal('specificDateModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date Name</label>
                <select id="modalDateFieldName" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="created_at">Date Applied</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">From</label>
                <input type="date" id="modalFilterDateFrom" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">To</label>
                <input type="date" id="modalFilterDateTo" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div id="modalDateError" class="hidden p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-xs text-rose-600 flex items-center gap-1.5">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>The start date cannot be after the end date.</span>
            </div>
        </div>
        <div class="flex items-center justify-between gap-2 px-6 pb-6 pt-2 border-t border-slate-100">
            <button type="button" onclick="clearSpecificDateFilter()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">Clear</button>
            <div class="flex gap-2">
                <button type="button" onclick="closeModal('specificDateModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                <button type="button" onclick="applySpecificDateFilter()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">Apply Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- BARANGAY FILTER MODAL -->
<div id="barangayFilterModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-location-dot text-brand-medium"></i> Barangay Filter
            </h3>
            <button onclick="closeModal('barangayFilterModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Zone</label>
                <select id="modalFilterZone" onchange="populateModalBarangays()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Zone</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label>
                <select id="modalFilterBarangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" disabled>
                    <option value="">Select a zone first</option>
                </select>
            </div>
        </div>
        <div class="flex items-center justify-between gap-2 px-6 pb-6 pt-2 border-t border-slate-100">
            <button type="button" onclick="clearBarangayFilter()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">Clear</button>
            <div class="flex gap-2">
                <button type="button" onclick="closeModal('barangayFilterModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                <button type="button" onclick="applyBarangayFilter()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">Apply Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW PERMIT RECORD MODAL                                     -->
<!-- ============================================================ -->
<div id="viewPermitRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-brand-medium"></i> Permit Record Details & Workflow
            </h3>
            <button onclick="closeModal('viewPermitRecordModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="permitRecordDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading details...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT PERMIT RECORD MODAL                                     -->
<!-- ============================================================ -->
<div id="editPermitRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-brand-medium"></i> Edit Permit Record
            </h3>
            <button onclick="closeModal('editPermitRecordModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="edit_permit_db_id">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Permit ID</label>
                    <input type="text" id="edit_permit_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-100 font-mono font-bold text-slate-700 outline-none" readonly>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="edit_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none font-semibold">
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved / Active</option>
                        <option value="expired">Expired</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Applicant Name</label>
                <input type="text" id="edit_applicant" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label>
                    <input type="text" id="edit_owner_name" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Business Type</label>
                    <input type="text" id="edit_business_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label>
                    <input type="text" id="edit_barangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fee (₱)</label>
                    <input type="number" step="0.01" id="edit_fee" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Status</label>
                    <select id="edit_paid" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="1">Paid</option>
                        <option value="0">Unpaid</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Method</label>
                    <select id="edit_payment_method" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none">
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Check">Check</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Expiry Date</label>
                <input type="date" id="edit_expiry_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes & Workflow Audit</label>
                <textarea id="edit_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none" placeholder="Staff workflow notes..."></textarea>
            </div>
        </div>
        <div class="flex items-center justify-between gap-2 px-6 pb-6 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeModal('editPermitRecordModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
            <button type="button" onclick="savePermitRecordEdit()" class="px-5 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-save"></i> Save Changes
            </button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- RENEW PERMIT MODAL                                           -->
<!-- ============================================================ -->
<div id="renewPermitModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900">Renew Permit</h3>
            <button onclick="closeModal('renewPermitModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                <div>
                    <p id="renewPermitId" class="font-semibold text-slate-800 text-sm">—</p>
                    <p id="renewApplicant" class="text-xs text-slate-400">—</p>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Renewal Fee</label>
                <input type="number" id="renew_fee" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Method</label>
                <select id="renew_payment" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="renew_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Renewal notes..."></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6">
            <button type="button" onclick="closeModal('renewPermitModal')"
                class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                Cancel
            </button>
            <button type="button" onclick="confirmRenew()"
                class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                <i class="fa-solid fa-rotate mr-1.5"></i> Renew Permit
            </button>
        </div>
    </div>
</div>

<!-- EXPORT PERMIT RECORDS MODAL -->
<div id="exportPermitRecordsModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-export text-brand-medium"></i> Export Permit Records
            </h3>
            <button onclick="closeModal('exportPermitRecordsModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-3">
            <p class="text-xs text-slate-500">Export records using the active filters.</p>
            <button onclick="exportPermitRecords('pdf')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-rose-50 transition text-left">
                <i class="fa-solid fa-file-pdf text-rose-600 text-lg w-6 text-center"></i>
                <span><strong class="block text-sm text-slate-700">PDF</strong><small class="text-xs text-slate-400">Print-ready report</small></span>
            </button>
            <button onclick="exportPermitRecords('excel')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-emerald-50 transition text-left">
                <i class="fa-solid fa-file-excel text-emerald-600 text-lg w-6 text-center"></i>
                <span><strong class="block text-sm text-slate-700">Excel</strong><small class="text-xs text-slate-400">Spreadsheet format</small></span>
            </button>
            <button onclick="exportPermitRecords('docx')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-blue-50 transition text-left">
                <i class="fa-solid fa-file-word text-blue-600 text-lg w-6 text-center"></i>
                <span><strong class="block text-sm text-slate-700">DOCX</strong><small class="text-xs text-slate-400">Microsoft Word format</small></span>
            </button>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT - API Integration                                -->
<!-- ============================================================ -->
<script>
    // ============================================================
    // OFFICIAL SANITATION REQUIREMENTS MATRIX
    // ============================================================
    const SANITATION_REQUIREMENTS_MATRIX = {
        'Food Establishment': [
            'Certificate of Water Potability (Drinking Water & Ice)',
            'Contract for Abatement of Insect & Vermin (Accredited Pest Control)',
            'Health Certificates for Food & Non-Food Handlers',
            'Meat Handlers Permit (if serving meat)'
        ],
        'Water Refilling Station': [
            'Certificate of Water Potability (HPC, Physical-Chemical, Microbiological)',
            'Heterotrophic Plate Count (HPC) Test Result',
            'Physical-Chemical Analysis of H2O (not more than 6 mos)',
            'Microbiological Exam of H2O',
            'Payment of Delinquency Receipt (for delinquent WRS)'
        ],
        'Spa / Massage / Therapeutic Clinic': [
            'Certificate of Water Potability (a, b, c)',
            'Photocopy of DOH & TESDA License for Masseur / Masseuse',
            'Certificate of Training to Conduct Massage',
            'Certificate of DOH Accreditation for Training Institution',
            'Health Certificate of Registered Masseur and Attendants',
            'Pest Control Contract from Accredited Operator'
        ],
        'Medical / Dental Clinic / Hospital / Laboratory': [
            'DOH License to Operate',
            'Certificate of Proficiency (Drug Testing, HIV-AIDS Accredited)',
            'Contract for Collection & Disposal of Hazardous Waste & Sharps',
            'Certificate of Water Potability (a, b, c)',
            'Copy of Employees PRC Licenses',
            'Pest Control Contract from Accredited Operator'
        ],
        'Market Vendor / Supermarket': [
            'Certificate of Accreditation from NMIC',
            'Certificate of Training from NMIC',
            'Certificate of Water Potability (a, b, c)',
            'Health Certificates / Meat Handlers Permit / Butchers Permit',
            'Contract for Insect & Vermin Abatement from Accredited Operator'
        ],
        'Hotel / Lodging / Condominium': [
            'Certificate of Water Potability (a, b, c)',
            'Contract for Insect & Vermin Prevention & Control',
            'Water Quality Monitoring (pH, HPC, Microbio) for Swimming Pools',
            'Certified Lifeguards Training Certificates'
        ],
        'Movie House': [
            'Contract for Pest Control from Accredited Operator',
            'Certificate of Water Potability (a, b, c)',
            'Health Certificates of Ushers, Ticket Attendants, Utilities'
        ],
        'Funeral Parlor': [
            'DOH Registered Mortician / Embalmer License',
            'Certificate of Water Potability (a, b, c)',
            'Contract for Pest Control from Accredited Operator',
            'DENR/DOH Certificate of Approval on Wastewater & Hazardous Waste Disposal'
        ],
        'Tiangge': [
            'Certificate of Water Potability (a, b, c)',
            'Contract for Pest Control',
            'Contract for Solid Waste Collection & Disposal',
            'Health Certificate for Food / Non-Food Handlers',
            'Access to Toilet Facilities & Prescribed Refuse Bins'
        ],
        'Department Store': [
            'Certificate of Water Potability (a, b, c)',
            'List of Employees for Health Certificate',
            'Contract for Pest Control from Accredited Operator',
            'Contract for Solid Waste Collection & Disposal'
        ],
        'Recreational Facility': [
            'Certificate of Water Potability (a, b, c)',
            'Contract for Pest Control',
            'Contract for Solid Waste Collection & Disposal',
            'Health Certificates for Employees'
        ],
        'Pharmacy': [
            'Registered Licensed Pharmacist PRC License',
            'Health Certificates for Employees (Tellers, Attendants, Security, Cashiers)',
            'Pest Control Contract from Accredited Operator'
        ],
        'Beauty Parlor / Salon / Barbershop': [
            'Sterilizing Device for Manicure / Pedicure Equipment',
            'Certificate of Water Potability (a, b, c)',
            'Pest Control Contract from Accredited Operator',
            'Health Certificate for Employees'
        ],
        'Facial / Skin Clinic': [
            'Dermatologist PRC License / Accreditation',
            'Certificate of Training for Aestheticians',
            'Health Certificate for Employees',
            'Certificate of Water Potability (a, b, c)',
            'Pest Control Contract from Accredited Operator',
            'Contract for Disposal of Sharps, Needles & Hazardous Waste'
        ],
        'Amusement Center': [
            'Noise Level Monitoring Device Certification',
            'Contract for Pest Control',
            'Health Certificate for Employees'
        ],
        'Construction Site': [
            'Zoning & Engineering Building Permit',
            'Certificate of Water Potability (a, b, c)',
            'Contract for Pest Control from Accredited Operator',
            'Temporary Sanitary Permit for Food Providers',
            'Environmental Compliance Certificate (ECC) & DENR Waste Water Disposal Cert',
            'On-site Medical Facility (Clinic, Nurse/Doctor, Transport Vehicle & Affiliate Hospital)',
            'Personal Protective Equipment (PPE) & Toilet Facilities Provision'
        ],
        'Bank / Financial Institution': [
            'Certificate of Water Potability (a, b, c)',
            'Pest Control Contract from Accredited Operator',
            'Health Certificate for Employees (Tellers, Managers, Security)'
        ],
        'Industrial Establishment': [
            'Certificate of Water Potability (a, b, c)',
            'PPE Provision (Noise, Dust, Pollutants, Gaseous Materials, Helmets)',
            'Environmental Safety & Waste Management Permits'
        ]
    };

    // ============================================================
    // PERMIT API CLIENT
    // ============================================================
    const API_BASE = '../../api/permitrecord.php';
    const BARANGAYS = <?php echo json_encode($barangayOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    let activeDateFrom = '';
    let activeDateTo = '';
    let activeBarangay = '';
    let activeZone = '';

    async function apiRequest(url, options = {}) {
        const csrfToken = window.CrudAjax ? window.CrudAjax.getCsrfToken() : '';
        const headers = {
            'Content-Type': 'application/json',
            ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
            ...(options.headers || {})
        };
        try {
            const response = await fetch(url, {
                ...options,
                headers
            });
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || 'API request failed');
            }
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    async function getPermits(filters = {}) {
        let url = API_BASE + '?page=' + (filters.page || 1) + '&limit=' + (filters.limit || 5);
        if (filters.status) url += '&status=' + encodeURIComponent(filters.status);
        if (filters.type) url += '&type=' + encodeURIComponent(filters.type);
        if (filters.search) url += '&q=' + encodeURIComponent(filters.search);
        if (filters.barangay) url += '&barangay=' + encodeURIComponent(filters.barangay);
        if (filters.dateFrom) url += '&date_from=' + encodeURIComponent(filters.dateFrom);
        if (filters.dateTo) url += '&date_to=' + encodeURIComponent(filters.dateTo);
        return apiRequest(url);
    }

    async function getPermit(id) {
        return apiRequest(API_BASE + '?id=' + id);
    }

    async function apiRenewPermit(id, data) {
        return apiRequest(API_BASE + '?id=' + id + '&action=update', {
            method: 'POST',
            body: JSON.stringify({
                ...data,
                status: 'approved'
            }),
        });
    }

    async function getStats() {
        return apiRequest(API_BASE + '?stats=true');
    }

    // ============================================================
    // GLOBAL STATE
    // ============================================================
    let currentPage = <?php echo $page; ?>;
    let currentLimit = <?php echo $limit; ?>;
    let totalPages = 1;
    let allPermits = {};
    let isLoadingPermits = false;

    // ============================================================
    // LOAD PERMITS FROM API (with silent refresh support)
    // ============================================================
    async function loadPermits(page = currentPage, isSilent = false) {
        if (isLoadingPermits && isSilent) return;
        isLoadingPermits = true;

        try {
            if (!isSilent) {
                showLoading(true);
            }

            const filters = {
                page: page,
                limit: currentLimit,
                status: document.getElementById('filterStatus') ? document.getElementById('filterStatus').value : '',
                type: document.getElementById('filterType') ? document.getElementById('filterType').value : '',
                barangay: activeBarangay,
                search: document.getElementById('searchPermitRecord') ? document.getElementById('searchPermitRecord').value : '',
                dateFrom: activeDateFrom,
                dateTo: activeDateTo,
            };

            if (filters.dateFrom && filters.dateTo && filters.dateFrom > filters.dateTo) {
                showToast('The start date cannot be after the end date', 'warning');
                return;
            }

            const result = await getPermits(filters);

            allPermits = {};
            if (result && Array.isArray(result.data)) {
                result.data.forEach(p => {
                    allPermits[p.id] = p;
                });
                totalPages = result.total_pages || 1;
                currentPage = page;

                renderPermitTable(result.data);
                updatePagination(result.page || page, result.total_pages || 1, result.total || 0);
            } else {
                renderPermitTable([]);
            }
        } catch (error) {
            console.error('Failed to load permits:', error);
            showToast('Failed to load permits: ' + error.message, 'danger');
        } finally {
            showLoading(false);
            isLoadingPermits = false;
        }
    }

    // ============================================================
    // LOAD STATISTICS FROM API
    // ============================================================
    async function loadStats() {
        try {
            const result = await getStats();
            const stats = result.data || {};

            const total = Number(stats.total) || 0;
            const active = Number(stats.active ?? stats.approved) || 0;

            const setEl = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            };

            setEl('statTotal', total);
            setEl('statActive', active);
            setEl('statActiveMini', active);
            setEl('statPending', stats.pending || 0);
            setEl('statUnderReview', stats.under_review || 0);
            setEl('statExpired', stats.expired || 0);
            setEl('statRejected', stats.rejected || 0);

            // Renewals
            const renewals = stats.total_renewals || 0;
            setEl('statRenewals', renewals);
            setEl('statRenewalsMini', renewals);

            // Active / Compliance Rate
            const activeRate = total > 0 ? Math.round((active / total) * 100) : 0;
            setEl('statActiveRate', activeRate + '%');

            // Revenue (Safely guarded if present)
            const revenue = parseFloat(stats.total_revenue) || 0;
            setEl('statRevenue', '₱' + revenue.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));

        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    // ============================================================
    // RENDER PERMIT TABLE
    // ============================================================
    // HELPER & WORKFLOW FUNCTIONS
    // ============================================================
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    async function apiUpdatePermit(id, data) {
        return apiRequest(API_BASE + '?id=' + id + '&action=update', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    async function quickUpdatePermitStatus(id, newStatus) {
        try {
            const updateData = { status: newStatus };
            if (newStatus === 'approved') {
                updateData.paid = 1;
            }
            await apiUpdatePermit(id, updateData);
            showToast('Permit status updated to ' + newStatus.replace('_', ' ').toUpperCase(), 'success');
            closeModal('viewPermitRecordModal');
            closeModal('editPermitRecordModal');
            loadPermits(currentPage);
            loadStats();
            if (typeof window.broadcastSanitationChange === 'function') {
                window.broadcastSanitationChange('permits', { action: 'status_updated', id: id, status: newStatus });
            }
        } catch (error) {
            showToast('Failed to update permit status: ' + error.message, 'danger');
        }
    }

    // Parse requirement checklist stored in notes or requirements_data
    function parsePermitRequirementsData(permit) {
        const reqs = SANITATION_REQUIREMENTS_MATRIX[permit.business_type] || SANITATION_REQUIREMENTS_MATRIX['Office/Commercial'] || [];
        let reqState = [];

        if (permit.requirements_data) {
            try {
                reqState = typeof permit.requirements_data === 'string' ? JSON.parse(permit.requirements_data) : permit.requirements_data;
            } catch (e) { reqState = []; }
        }

        if (!Array.isArray(reqState) || reqState.length === 0) {
            // Default initialization
            const defaultChecked = (permit.status === 'approved' || permit.status === 'active');
            reqState = reqs.map((r, idx) => ({
                title: r,
                checked: defaultChecked,
                ref_id: `${permit.permit_id || 'SAN'}-DOC-${idx + 1}`,
                sub_type: defaultChecked ? '📄 Physical Copy / On-File' : '⏳ To Follow'
            }));
        }
        return reqState;
    }

    // ============================================================
    // RENDER PERMIT TABLE
    // ============================================================
    function renderPermitTable(permits) {
        const tbody = document.getElementById('permitRecordTableBody');

        if (permits.length === 0) {
            tbody.innerHTML = '';
            document.getElementById('emptyState').classList.remove('hidden');
            document.getElementById('emptyState').classList.add('flex');
            return;
        }

        document.getElementById('emptyState').classList.add('hidden');
        document.getElementById('emptyState').classList.remove('flex');

        const statusColors = {
            active: 'bg-emerald-100 text-emerald-700',
            approved: 'bg-emerald-100 text-emerald-700',
            pending: 'bg-amber-100 text-amber-700',
            under_review: 'bg-blue-100 text-blue-700',
            expired: 'bg-slate-100 text-slate-500',
            rejected: 'bg-rose-100 text-rose-700'
        };

        tbody.innerHTML = permits.map(permit => {
            const reqs = SANITATION_REQUIREMENTS_MATRIX[permit.business_type] || SANITATION_REQUIREMENTS_MATRIX['Office/Commercial'] || [];
            const reqState = parsePermitRequirementsData(permit);
            const metCount = reqState.filter(r => r.checked).length;
            const totalReqs = reqs.length || 1;
            const pct = Math.round((metCount / totalReqs) * 100);

            return `
            <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors permit-record-row"
                data-applicant="${(permit.applicant || '').toLowerCase()}"
                data-type="${(permit.business_type || '').toLowerCase()}"
                data-status="${permit.status || ''}"
                data-barangay="${permit.barangay || ''}"
                data-id="${permit.permit_id || ''}">
                <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${permit.permit_id || '—'}</td>
                <td class="px-4 py-3">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escapeHtml(permit.applicant || '—')}</p>
                        <p class="text-xs text-slate-400 maskable">${escapeHtml(permit.owner_name || '—')}</p>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">${escapeHtml(permit.business_type || '—')}</td>
                <td class="px-4 py-3 text-slate-600 text-xs maskable">${escapeHtml(permit.barangay || '—')}</td>
                <td class="px-4 py-3">
                    <span class="text-xs font-semibold text-slate-700">₱${parseFloat(permit.fee || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                </td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColors[permit.status] || statusColors.pending}">
                        ${(permit.status || 'pending').replace('_', ' ').toUpperCase()}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <button onclick="viewPermitRecord(${permit.id})" class="px-2 py-0.5 rounded-full text-[11px] font-bold ${metCount === totalReqs ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : (metCount > 0 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-rose-100 text-rose-800 border border-rose-200')} hover:scale-105 transition flex items-center gap-1 w-max">
                        <i class="fa-solid ${metCount === totalReqs ? 'fa-circle-check text-emerald-600' : 'fa-hourglass-half text-amber-600'}"></i>
                        ${metCount}/${totalReqs} Met (${pct}%)
                    </button>
                </td>
                <td class="px-4 py-3 text-slate-500 text-xs">
                    ${permit.expiry_date ? new Date(permit.expiry_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '—'}
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="text-xs font-semibold text-brand-dark">${permit.renewal_count || 0}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="viewPermitRecord(${permit.id})"
                                class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details & Checklist">
                            <i class="fa-solid fa-eye text-sm"></i>
                        </button>
                        ${permit.status === 'pending' ? `
                            <button onclick="quickUpdatePermitStatus(${permit.id}, 'under_review')"
                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Move to Under Review / Inspection">
                                <i class="fa-solid fa-clipboard-check text-sm"></i>
                            </button>
                        ` : ''}
                        ${permit.status === 'under_review' ? `
                            <button onclick="quickUpdatePermitStatus(${permit.id}, 'approved')"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Approve & Issue Sanitation Permit">
                                <i class="fa-solid fa-circle-check text-sm"></i>
                            </button>
                        ` : ''}
                        ${permit.status === 'approved' || permit.status === 'active' ? `
                            <a href="permit_certificate.php?permit_id=${permit.id}" target="_blank"
                               class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Print Official Sanitation Permit Certificate">
                                <i class="fa-solid fa-certificate text-sm"></i>
                            </a>
                        ` : ''}
                        ${permit.status === 'expired' ? `
                            <button onclick="renewPermit(${permit.id})"
                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Renew Permit">
                                <i class="fa-solid fa-rotate text-sm"></i>
                            </button>
                        ` : ''}
                        <button onclick="editPermitRecord(${permit.id})"
                                class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit Record">
                            <i class="fa-solid fa-pen text-sm"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
    }

    // ============================================================
    // UPDATE PAGINATION
    // ============================================================
    function updatePagination(page, totalPagesCount, total) {
        const info = document.getElementById('paginationInfo');
        const buttons = document.getElementById('paginationButtons');

        const start = (page - 1) * currentLimit + 1;
        const end = Math.min(page * currentLimit, total);

        info.textContent = `Showing ${start} to ${end} of ${total} permits`;

        let html = '';

        // Previous button
        html += `<button onclick="changePage(${page - 1})"
                class="px-3 py-1.5 rounded-lg text-sm ${page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}"
                ${page <= 1 ? 'disabled' : ''}>
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </button>`;

        // Page numbers
        for (let i = 1; i <= totalPagesCount; i++) {
            html += `<button onclick="changePage(${i})"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium ${i === page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}">
                    ${i}
                </button>`;
        }

        // Next button
        html += `<button onclick="changePage(${page + 1})"
                class="px-3 py-1.5 rounded-lg text-sm ${page >= totalPagesCount ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}"
                ${page >= totalPagesCount ? 'disabled' : ''}>
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>`;

        buttons.innerHTML = html;
    }

    // ============================================================
    // SHOW/HIDE LOADING
    // ============================================================
    function showLoading(show) {
        const loading = document.getElementById('loadingState');
        const empty = document.getElementById('emptyState');

        if (show) {
            loading.classList.remove('hidden');
            loading.classList.add('flex');
            empty.classList.add('hidden');
            empty.classList.remove('flex');
        } else {
            loading.classList.add('hidden');
            loading.classList.remove('flex');
        }
    }

    // ============================================================
    // MODAL FUNCTIONS (using ModalSystem)
    // ============================================================
    function openModal(id) {
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.open(id);
        } else {
            document.getElementById(id).classList.remove('hidden');
            document.getElementById(id).classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
    }

    function closeModal(id) {
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.close(id);
        } else {
            document.getElementById(id).classList.add('hidden');
            document.getElementById(id).classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    }

    // ============================================================
    // VIEW PERMIT RECORD & INTERACTIVE WORKFLOW MATRIX
    // ============================================================
    let activeModalPermit = null;

    async function viewPermitRecord(id) {
        openModal('viewPermitRecordModal');

        try {
            const [result, docsRes, payRes] = await Promise.all([
                getPermit(id),
                fetch(`../../api/permit_documents.php?by_permit=${id}`).then(r => r.json()).catch(() => ({ data: [] })),
                fetch(`../../api/payments.php?permit_id=${id}`).then(r => r.json()).catch(() => ({ data: [] }))
            ]);

            const p = result.data;
            const docs = (docsRes && docsRes.data) || [];
            const payments = (payRes && payRes.data) || [];
            activeModalPermit = p;

            if (!p) {
                document.getElementById('permitRecordDetailsContent').innerHTML = '<p class="text-center text-slate-500">Permit record not found</p>';
                return;
            }

            const statusColors = {
                active: 'bg-emerald-100 text-emerald-700',
                approved: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                under_review: 'bg-blue-100 text-blue-700',
                expired: 'bg-slate-100 text-slate-500',
                rejected: 'bg-rose-100 text-rose-700'
            };

            // Payment Calculations & Metadata
            const latestPay = payments.length > 0 ? payments[0] : null;
            const isPaid = p.paid || (latestPay && (latestPay.status === 'paid' || latestPay.status === 'completed'));
            const paymentMethod = (latestPay && latestPay.method) || p.payment_method || 'Cash';
            const orNumber = (latestPay && (latestPay.reference_number || latestPay.payment_id)) || p.payment_reference || 'OR-PENDING';
            const paymentAmount = latestPay ? parseFloat(latestPay.amount) : parseFloat(p.fee || 0);
            const paymentDate = (latestPay && (latestPay.paid_at || latestPay.created_at)) 
                ? new Date(latestPay.paid_at || latestPay.created_at).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) 
                : (isPaid ? 'Verified' : 'Pending');

            // QR & Certificate Metadata
            const qrCodeVal = p.qr_code || ('QR-SAN-' + (p.permit_id || id));

            const categoryReqs = SANITATION_REQUIREMENTS_MATRIX[p.business_type] || SANITATION_REQUIREMENTS_MATRIX['Office/Commercial'] || [];
            const reqState = parsePermitRequirementsData(p);
            const checkedCount = reqState.filter(r => r.checked).length;
            const totalCount = categoryReqs.length || 1;
            const progressPct = Math.round((checkedCount / totalCount) * 100);

            // Requirements Table HTML
            let reqsTableHtml = '';
            if (categoryReqs.length > 0) {
                reqsTableHtml = `
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                                    <i class="fa-solid fa-folder-check text-brand-medium"></i>
                                    Compliance Requirements Checklist
                                </h5>
                                <p class="text-[11px] text-slate-500 mt-0.5" id="modal_req_summary_text">
                                    <span id="modal_req_checked_count">${checkedCount}</span> of ${totalCount} Requirements Met (${progressPct}%)
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="markAllModalReqs(true)" class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg hover:bg-emerald-100 transition text-[11px] font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-check-double"></i> Mark All Met
                                </button>
                                <button type="button" onclick="saveModalRequirementsChecklist(${p.id})" class="px-2.5 py-1 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-[11px] font-bold flex items-center gap-1">
                                    <i class="fa-solid fa-save"></i> Save Checklist
                                </button>
                            </div>
                        </div>
                        <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                            <div id="modal_req_progress_bar" class="bg-brand-dark h-full transition-all duration-300" style="width: ${progressPct}%"></div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-slate-100/80 text-slate-500 font-bold uppercase text-[10px]">
                                    <tr>
                                        <th class="px-2.5 py-1.5">Status</th>
                                        <th class="px-2.5 py-1.5">Requirement Item</th>
                                        <th class="px-2.5 py-1.5">Submission Type</th>
                                        <th class="px-2.5 py-1.5">Document / Cert Ref ID</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200" id="modal_req_tbody">
                                    ${categoryReqs.map((r, idx) => {
                                        const isChecked = reqState[idx] ? reqState[idx].checked : (p.status === 'approved' || p.status === 'active');
                                        const refId = (reqState[idx] && reqState[idx].ref_id) ? reqState[idx].ref_id : `${p.permit_id || 'SAN'}-DOC-${idx + 1}`;
                                        const subType = (reqState[idx] && reqState[idx].sub_type) ? reqState[idx].sub_type : '📄 Physical Copy / On-File';
                                        return `
                                            <tr>
                                                <td class="px-2.5 py-2">
                                                    <input type="checkbox" id="modal_req_chk_${idx}" data-req-title="${escapeHtml(r)}" ${isChecked ? 'checked' : ''} onchange="updateModalReqProgress()" class="w-4 h-4 text-brand-dark rounded focus:ring-brand-medium accent-brand-dark cursor-pointer">
                                                </td>
                                                <td class="px-2.5 py-2 font-medium text-slate-800" id="modal_req_title_${idx}">${escapeHtml(r)}</td>
                                                <td class="px-2.5 py-2">
                                                    <select id="modal_req_type_${idx}" class="text-[11px] px-2 py-1 border border-slate-200 rounded-md bg-white font-medium text-slate-700 outline-none">
                                                        <option value="📄 Physical Copy / On-File" ${subType.includes('Physical') ? 'selected' : ''}>📄 Physical Copy</option>
                                                        <option value="💻 Digital Upload" ${subType.includes('Digital') ? 'selected' : ''}>💻 Digital Upload</option>
                                                        <option value="⏳ To Follow" ${subType.includes('Follow') ? 'selected' : ''}>⏳ To Follow</option>
                                                    </select>
                                                </td>
                                                <td class="px-2.5 py-2 font-mono text-slate-700">
                                                    <input type="text" id="modal_req_ref_${idx}" value="${escapeHtml(refId)}" class="w-full px-2 py-1 border border-slate-200 rounded-md text-xs font-mono font-semibold outline-none focus:border-brand-medium">
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            document.getElementById('permitRecordDetailsContent').innerHTML = `
            <div class="space-y-4">
                <!-- Workflow Lifecycle Pipeline -->
                <div class="bg-gradient-to-r from-brand-dark to-brand-medium text-white rounded-xl p-3.5 shadow-xs flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-lg bg-white/20 flex items-center justify-center font-bold text-sm">
                            <i class="fa-solid fa-diagram-project"></i>
                        </span>
                        <div>
                            <p class="text-[10px] uppercase font-bold tracking-wider opacity-80">Permit Workflow Stage</p>
                            <p class="text-xs font-black capitalize">${(p.status || 'pending').replace('_', ' ')}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-[10px] font-bold">
                        <span class="px-2 py-0.5 rounded-full ${p.status === 'pending' ? 'bg-amber-400 text-slate-900 font-extrabold' : 'bg-white/10 opacity-70'}">1. Applied</span>
                        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
                        <span class="px-2 py-0.5 rounded-full ${p.status === 'under_review' ? 'bg-blue-400 text-slate-900 font-extrabold' : 'bg-white/10 opacity-70'}">2. Inspection</span>
                        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
                        <span class="px-2 py-0.5 rounded-full ${(p.status === 'approved' || p.status === 'active') ? 'bg-emerald-400 text-slate-900 font-extrabold' : 'bg-white/10 opacity-70'}">3. Permit Active</span>
                    </div>
                </div>

                <!-- Establishment Banner -->
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                        ${(p.applicant || 'P').charAt(0)}
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">${escapeHtml(p.applicant || '—')}</h4>
                        <p class="text-sm text-slate-500">${escapeHtml(p.permit_id || '—')} • ${escapeHtml(p.business_type || '—')}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[p.status] || statusColors.pending}">
                            ${(p.status || 'pending').replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>

                <!-- Establishment Details -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <div><p class="text-[10px] text-slate-400 font-bold uppercase">Owner</p><p class="text-xs font-semibold text-slate-800 maskable">${escapeHtml(p.owner_name || '—')}</p></div>
                    <div><p class="text-[10px] text-slate-400 font-bold uppercase">Contact</p><p class="text-xs font-semibold text-slate-800 maskable">${escapeHtml(p.contact || '—')}</p></div>
                    <div><p class="text-[10px] text-slate-400 font-bold uppercase">Barangay</p><p class="text-xs font-semibold text-slate-800 maskable">${escapeHtml(p.barangay || p.address || '—')}</p></div>
                    <div><p class="text-[10px] text-slate-400 font-bold uppercase">Validity</p><p class="text-xs font-semibold text-slate-800">${p.expiry_date ? escapeHtml(p.expiry_date) : '1 Year from Approval'}</p></div>
                </div>

                <!-- INTEGRATED 1: OFFICIAL PAYMENT & RECEIPT CARD -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-2xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                            <i class="fa-solid fa-receipt text-emerald-600"></i>
                            Official Fee Payment & Receipt
                        </h5>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold ${isPaid ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'}">
                            <i class="fa-solid ${isPaid ? 'fa-circle-check text-emerald-600' : 'fa-clock text-amber-600'} mr-1"></i>
                            ${isPaid ? 'PAID & VERIFIED' : 'UNPAID / FEE PENDING'}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Amount</span>
                            <span class="font-extrabold text-slate-900">₱${paymentAmount.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Method</span>
                            <span class="font-semibold text-slate-800 capitalize">${escapeHtml(paymentMethod.replace('_', ' '))}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Official Receipt / Ref</span>
                            <span class="font-mono font-bold text-brand-dark">${escapeHtml(orNumber)}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-400 font-bold uppercase block">Payment Date</span>
                            <span class="font-medium text-slate-700">${escapeHtml(paymentDate)}</span>
                        </div>
                    </div>
                </div>

                <!-- INTEGRATED 2: OFFICIAL SANITARY PERMIT & QR CODE CARD -->
                <div class="bg-gradient-to-br from-brand-dark/5 via-slate-50 to-brand-medium/10 rounded-xl p-4 border border-brand-border/60 shadow-2xs space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-white border border-brand-border flex items-center justify-center text-brand-dark shadow-xs flex-shrink-0">
                                <i class="fa-solid fa-qrcode text-2xl"></i>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-900 uppercase tracking-wide flex items-center gap-1.5">
                                    Official Sanitary Permit & QR
                                    <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-brand-light text-brand-dark border border-brand-border">Verified</span>
                                </h5>
                                <p class="text-xs font-mono font-bold text-brand-dark mt-0.5">${escapeHtml(qrCodeVal)}</p>
                                <p class="text-[10px] text-slate-500">Valid until: ${p.expiry_date ? new Date(p.expiry_date).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '1 Year from Issuance'}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="permit_certificate.php?permit_id=${p.id}" target="_blank"
                               class="px-3.5 py-2 bg-brand-dark hover:bg-brand-medium text-white rounded-lg transition font-bold text-xs flex items-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-certificate"></i> Print Official Permit
                            </a>
                            <a href="../../api/permit_documents.php?qr=${encodeURIComponent(qrCodeVal)}" target="_blank"
                               class="px-3 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 rounded-lg transition font-semibold text-xs flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved text-brand-medium"></i> Verify QR
                            </a>
                        </div>
                    </div>
                </div>

                <!-- INTEGRATED 3: ATTACHED COMPLIANCE DOCUMENTS VAULT -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-2xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                            <i class="fa-solid fa-folder-open text-blue-600"></i>
                            Attached Files & Compliance Records (${docs.length})
                        </h5>
                        <button type="button" onclick="triggerRecordDocUpload(${p.id})" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition text-[11px] font-bold flex items-center gap-1">
                            <i class="fa-solid fa-upload"></i> Attach File
                        </button>
                    </div>
                    ${docs.length === 0 ? `
                        <div class="py-4 text-center text-xs text-slate-400 bg-slate-50 rounded-lg border border-dashed border-slate-200">
                            <i class="fa-solid fa-file-circle-question text-lg mb-1 block opacity-40"></i>
                            No additional document files uploaded yet for this permit.
                        </div>
                    ` : `
                        <div class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                            ${docs.map(d => `
                                <div class="py-2 flex items-center justify-between gap-2 text-xs">
                                    <div class="flex items-center gap-2 truncate">
                                        <i class="fa-solid ${d.file_type === 'pdf' ? 'fa-file-pdf text-red-500' : 'fa-file text-slate-400'} text-base"></i>
                                        <div class="truncate">
                                            <p class="font-medium text-slate-800 truncate">${escapeHtml(d.file_name || d.document_type || 'Document')}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">${escapeHtml(d.document_id || '')} • ${(d.file_size ? (d.file_size / 1024).toFixed(1) + ' KB' : 'File')} • ${d.uploaded_at ? new Date(d.uploaded_at).toLocaleDateString('en-PH') : ''}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span class="px-2 py-0.5 text-[9px] font-bold rounded-full ${d.verified ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}">
                                            ${d.verified ? 'VERIFIED' : 'ON FILE'}
                                        </span>
                                        ${d.file_path ? `
                                            <a href="../../uploads/${escapeHtml(d.file_path)}" target="_blank" class="p-1.5 text-brand-dark hover:bg-brand-light rounded transition" title="View Document">
                                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            </a>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `}
                </div>

                ${reqsTableHtml}

                ${p.notes ? `<div class="bg-slate-50 rounded-xl p-3.5 border border-slate-200"><h5 class="text-xs font-bold text-slate-700 mb-1">📝 Notes & Audit Summary</h5><p class="text-xs text-slate-800 whitespace-pre-wrap">${escapeHtml(p.notes)}</p></div>` : ''}

                <!-- Workflow Actions Footer -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-200">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-500">Quick Status:</span>
                        <select onchange="quickUpdatePermitStatus(${p.id}, this.value)" class="text-xs px-2.5 py-1.5 border border-slate-300 rounded-lg bg-white font-bold text-slate-800 outline-none">
                            <option value="pending" ${p.status === 'pending' ? 'selected' : ''}>Pending</option>
                            <option value="under_review" ${p.status === 'under_review' ? 'selected' : ''}>Under Review</option>
                            <option value="approved" ${(p.status === 'approved' || p.status === 'active') ? 'selected' : ''}>Approved / Active</option>
                            <option value="expired" ${p.status === 'expired' ? 'selected' : ''}>Expired</option>
                            <option value="rejected" ${p.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button onclick="closeModal('viewPermitRecordModal')" class="px-3.5 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-xs font-semibold">Close</button>
                        ${p.status === 'pending' ? `
                            <button onclick="quickUpdatePermitStatus(${p.id}, 'under_review')" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-xs font-bold flex items-center gap-1.5">
                                <i class="fa-solid fa-clipboard-check"></i> Move to Under Review / Inspection
                            </button>
                        ` : ''}
                        ${p.status === 'under_review' ? `
                            <a href="inspections.php" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition text-xs font-bold flex items-center gap-1.5">
                                <i class="fa-solid fa-clipboard-list"></i> View in Inspections
                            </a>
                            <button onclick="quickUpdatePermitStatus(${p.id}, 'approved')" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition text-xs font-bold flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check"></i> Pass & Issue Permit
                            </button>
                        ` : ''}
                        ${(p.status === 'approved' || p.status === 'active') ? `
                            <a href="permit_certificate.php?permit_id=${p.id}" target="_blank" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition text-xs font-bold flex items-center gap-1.5"><i class="fa-solid fa-certificate"></i> Print Official Permit</a>
                        ` : ''}
                        ${p.status === 'expired' ? `
                            <button onclick="closeModal('viewPermitRecordModal'); renewPermit(${p.id})" class="px-3.5 py-1.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-xs font-bold flex items-center gap-1.5"><i class="fa-solid fa-rotate"></i> Renew Permit</button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        } catch (error) {
            document.getElementById('permitRecordDetailsContent').innerHTML =
                `<p class="text-center text-rose-500">Failed to load permit details: ${escapeHtml(error.message)}</p>`;
        }
    }

    // Modal Document Upload Helper
    function triggerRecordDocUpload(permitId) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.pdf,.jpg,.jpeg,.png';
        input.onchange = async () => {
            if (!input.files || input.files.length === 0) return;
            const file = input.files[0];
            const fd = new FormData();
            fd.append('file', file);
            fd.append('permit_id', permitId);
            fd.append('document_type', 'sanitary_permit');
            fd.append('status', 'verified');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fd.append('csrf_token', csrfToken);
            try {
                if (typeof toast !== 'undefined') toast.info('Uploading document attachment...', { title: 'Uploading' });
                const resp = await fetch('../../api/permit_documents.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                    body: fd
                });
                const json = await resp.json();
                if (json && json.success) {
                    if (typeof toast !== 'undefined') toast.success('Document uploaded successfully!', { title: 'Success' });
                    viewPermitRecord(permitId);
                } else {
                    throw new Error((json && json.message) || 'Upload failed');
                }
            } catch (err) {
                if (typeof toast !== 'undefined') toast.error(err.message, { title: 'Upload Failed' });
                else alert('Upload failed: ' + err.message);
            }
        };
        input.click();
    }

    // Modal Requirements Interactivity
    function updateModalReqProgress() {
        const tbody = document.getElementById('modal_req_tbody');
        if (!tbody) return;
        const checkboxes = tbody.querySelectorAll('input[type="checkbox"]');
        let checked = 0;
        checkboxes.forEach(c => { if (c.checked) checked++; });
        const total = checkboxes.length || 1;
        const pct = Math.round((checked / total) * 100);

        const countEl = document.getElementById('modal_req_checked_count');
        const summaryText = document.getElementById('modal_req_summary_text');
        const barEl = document.getElementById('modal_req_progress_bar');

        if (countEl) countEl.textContent = checked;
        if (summaryText) summaryText.innerHTML = `<span id="modal_req_checked_count">${checked}</span> of ${total} Requirements Met (${pct}%)`;
        if (barEl) barEl.style.width = pct + '%';
    }

    function markAllModalReqs(met = true) {
        const tbody = document.getElementById('modal_req_tbody');
        if (!tbody) return;
        tbody.querySelectorAll('input[type="checkbox"]').forEach(c => { c.checked = met; });
        updateModalReqProgress();
    }

    async function saveModalRequirementsChecklist(permitId) {
        const tbody = document.getElementById('modal_req_tbody');
        if (!tbody || !permitId) return;

        const reqs = [];
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, idx) => {
            const chk = row.querySelector(`#modal_req_chk_${idx}`);
            const typeSel = row.querySelector(`#modal_req_type_${idx}`);
            const refInp = row.querySelector(`#modal_req_ref_${idx}`);
            if (chk) {
                reqs.push({
                    title: chk.dataset.reqTitle || '',
                    checked: chk.checked,
                    sub_type: typeSel ? typeSel.value : '📄 Physical Copy',
                    ref_id: refInp ? refInp.value : ''
                });
            }
        });

        try {
            await apiUpdatePermit(permitId, {
                requirements_data: JSON.stringify(reqs)
            });
            showToast('Requirements checklist saved successfully!', 'success');
            loadPermits(currentPage);
        } catch (error) {
            showToast('Failed to save requirements checklist: ' + error.message, 'danger');
        }
    }

    // ============================================================
    // EDIT PERMIT RECORD
    // ============================================================
    function editPermitRecord(id) {
        const p = allPermits[id];
        if (!p) {
            showToast('Permit record not found', 'danger');
            return;
        }

        document.getElementById('edit_permit_db_id').value = p.id;
        document.getElementById('edit_permit_id').value = p.permit_id || '';
        document.getElementById('edit_applicant').value = p.applicant || '';
        document.getElementById('edit_owner_name').value = p.owner_name || '';
        document.getElementById('edit_business_type').value = p.business_type || '';
        document.getElementById('edit_barangay').value = p.barangay || p.address || '';
        document.getElementById('edit_fee').value = p.fee || 0;
        document.getElementById('edit_paid').value = p.paid ? '1' : '0';
        document.getElementById('edit_payment_method').value = p.payment_method || 'Cash';
        document.getElementById('edit_status').value = p.status || 'pending';
        document.getElementById('edit_expiry_date').value = p.expiry_date || '';
        document.getElementById('edit_notes').value = p.notes || '';

        openModal('editPermitRecordModal');
    }

    async function savePermitRecordEdit() {
        const id = document.getElementById('edit_permit_db_id').value;
        if (!id) return;

        const data = {
            applicant: document.getElementById('edit_applicant').value,
            owner_name: document.getElementById('edit_owner_name').value,
            business_type: document.getElementById('edit_business_type').value,
            barangay: document.getElementById('edit_barangay').value,
            fee: parseFloat(document.getElementById('edit_fee').value) || 0,
            paid: document.getElementById('edit_paid').value === '1' ? 1 : 0,
            payment_method: document.getElementById('edit_payment_method').value,
            status: document.getElementById('edit_status').value,
            expiry_date: document.getElementById('edit_expiry_date').value,
            notes: document.getElementById('edit_notes').value
        };

        try {
            await apiUpdatePermit(id, data);
            closeModal('editPermitRecordModal');
            showToast('Permit record updated successfully!', 'success');
            loadPermits(currentPage);
            loadStats();
            if (typeof window.broadcastSanitationChange === 'function') {
                window.broadcastSanitationChange('permits', { action: 'updated', id: id });
            }
        } catch (error) {
            showToast('Failed to save permit edits: ' + error.message, 'danger');
        }
    }

    // ============================================================
    // RENEW PERMIT (via API)
    // ============================================================
    let renewPermitId = null;

    function renewPermit(id) {
        const p = allPermits[id];
        if (!p) return;

        renewPermitId = id;
        document.getElementById('renewPermitId').textContent = p.permit_id;
        document.getElementById('renewApplicant').textContent = p.applicant;
        document.getElementById('renew_fee').value = p.fee || 0;

        openModal('renewPermitModal');
    }

    async function confirmRenew() {
        if (!renewPermitId) return;

        const data = {
            fee: parseFloat(document.getElementById('renew_fee').value) || 0,
            payment_method: document.getElementById('renew_payment').value,
            notes: document.getElementById('renew_notes').value
        };

        try {
            await apiRenewPermit(renewPermitId, data);
            closeModal('renewPermitModal');
            showToast('Permit renewed successfully!', 'success');
            loadPermits(currentPage);
            loadStats();
            if (typeof window.broadcastSanitationChange === 'function') {
                window.broadcastSanitationChange('permits', { action: 'renewed', id: renewPermitId });
            }
        } catch (error) {
            showToast('Failed to renew permit: ' + error.message, 'danger');
        }
    }

    // ============================================================
    // QUICK FILTER
    // ============================================================
    function quickFilter(status) {
        document.getElementById('filterStatus').value = status === 'all' ? '' : status;
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.classList.remove('bg-brand-dark', 'text-white', 'border-brand-dark');
        });
        if (status !== 'all') {
            document.querySelectorAll('.quick-filter-btn').forEach(btn => {
                if (btn.dataset.status === status) {
                    btn.classList.add('bg-brand-dark', 'text-white', 'border-brand-dark');
                }
            });
        }
        loadPermits(1);
    }

    // ============================================================
    // EXPORT PERMIT RECORDS - uses the same API filters, but requests all matches
    // ============================================================
    async function exportPermitRecords(format = 'excel') {
        try {
            const filters = {
                page: 1,
                limit: 100,
                status: document.getElementById('filterStatus').value,
                type: document.getElementById('filterType').value,
                barangay: activeBarangay,
                search: document.getElementById('searchPermitRecord').value,
                dateFrom: activeDateFrom,
                dateTo: activeDateTo
            };
            if (filters.dateFrom && filters.dateTo && filters.dateFrom > filters.dateTo) {
                showToast('The start date cannot be after the end date', 'warning');
                return;
            }

            const result = await getPermits(filters);
            const records = result.data || [];
            if (!records.length) {
                showToast('No records to export', 'warning');
                return;
            }

            const headers = ['Permit ID', 'Applicant', 'Business Type', 'Barangay', 'Status', 'Fee', 'Expiry Date', 'Renewals'];
            const escapeCsv = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
            const rows = records.map(permit => [
                permit.permit_id,
                permit.applicant,
                permit.business_type,
                permit.barangay,
                permit.status,
                permit.fee,
                permit.expiry_date,
                permit.renewal_count || 0
            ]);
            const stamp = new Date().toISOString().slice(0, 10);
            const reportTitle = 'Permit Records Report';

            if (format === 'pdf') {
                const printWindow = window.open('', '_blank', 'width=900,height=700');
                if (!printWindow) throw new Error('Please allow pop-ups to export PDF');
                const tableRows = rows.map(row => `<tr>${row.map(value => `<td>${escapeExportHtml(value)}</td>`).join('')}</tr>`).join('');
                printWindow.document.write(getPermitExportDocument(reportTitle, headers, tableRows));
                printWindow.document.close();
                printWindow.onload = () => setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 250);
            } else if (format === 'docx') {
                const tableRows = rows.map(row => `<tr>${row.map(value => `<td>${escapeExportHtml(value)}</td>`).join('')}</tr>`).join('');
                downloadExportFile(getPermitExportDocument(reportTitle, headers, tableRows), `permit_records_${stamp}.doc`, 'application/msword');
            } else {
                const csv = [headers, ...rows].map(row => row.map(escapeCsv).join(',')).join('\n') + '\n';
                downloadExportFile('\uFEFF' + csv, `permit_records_${stamp}.xls`, 'application/vnd.ms-excel');
            }

            closeModal('exportPermitRecordsModal');
            showToast('Exported ' + records.length + ' filtered records successfully!', 'success');
        } catch (error) {
            showToast('Failed to export records: ' + error.message, 'danger');
        }
    }

    function escapeExportHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [character]));
    }

    function getPermitExportDocument(title, headers, tableRows) {
        return `<!doctype html><html><head><meta charset="UTF-8"><title>${escapeExportHtml(title)}</title><style>
        @page { margin: 0.75in; }
        body { font-family: Arial, sans-serif; color: #1e293b; font-size: 12px; margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 14px; margin-bottom: 24px; }
        .header img { width: 90px; display: block; margin: 0 auto 8px; }
        h1 { margin: 0; font: bold 18pt 'Times New Roman', serif; text-transform: uppercase; color: #000; }
        h2 { color: #176B87; font-size: 14pt; margin: 6px 0; }
        p { color: #64748b; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #176B87; color: #fff; padding: 7px; text-align: left; }
        td { border: 1px solid #cbd5e1; padding: 7px; }
    </style></head><body><div class="header">
        <img src="<?= site_url('assets/images/logo.png') ?>" alt="Logo"><h1>Health Sanitation Management Caloocan</h1><h2>${escapeExportHtml(title)}</h2><p>Generated: ${new Date().toLocaleString()}</p>
    </div><table><thead><tr>${headers.map(header => `<th>${escapeExportHtml(header)}</th>`).join('')}</tr></thead><tbody>${tableRows}</tbody></table></body></html>`;
    }

    function downloadExportFile(content, filename, mimeType) {
        const url = URL.createObjectURL(new Blob([content], {
            type: mimeType
        }));
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
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
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('hidden');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    let searchTimeout;
    document.getElementById('searchPermitRecord').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => loadPermits(1), 300);
    });

    document.getElementById('filterStatus').addEventListener('change', () => loadPermits(1));
    document.getElementById('filterType').addEventListener('change', () => loadPermits(1));

    function openSpecificDateModal() {
        document.getElementById('modalFilterDateFrom').value = activeDateFrom;
        document.getElementById('modalFilterDateTo').value = activeDateTo;
        document.getElementById('modalDateError').classList.add('hidden');
        openModal('specificDateModal');
    }

    function applySpecificDateFilter() {
        const fromVal = document.getElementById('modalFilterDateFrom').value;
        const toVal = document.getElementById('modalFilterDateTo').value;
        const errorEl = document.getElementById('modalDateError');

        if (fromVal && toVal && fromVal > toVal) {
            errorEl.classList.remove('hidden');
            return;
        }

        errorEl.classList.add('hidden');
        activeDateFrom = fromVal;
        activeDateTo = toVal;
        updateSpecificDateButtonState();
        closeModal('specificDateModal');
        loadPermits(1);
    }

    function clearSpecificDateFilter() {
        activeDateFrom = '';
        activeDateTo = '';
        document.getElementById('modalFilterDateFrom').value = '';
        document.getElementById('modalFilterDateTo').value = '';
        document.getElementById('modalDateError').classList.add('hidden');
        updateSpecificDateButtonState();
        closeModal('specificDateModal');
        loadPermits(1);
    }

    function updateSpecificDateButtonState() {
        const label = document.getElementById('specificDateLabel');
        const badge = document.getElementById('dateFilterBadge');
        const btn = document.getElementById('specificDateBtn');

        if (activeDateFrom || activeDateTo) {
            label.textContent = activeDateFrom && activeDateTo ? `${activeDateFrom} - ${activeDateTo}` : (activeDateFrom ? `From ${activeDateFrom}` : `Until ${activeDateTo}`);
            badge.classList.remove('hidden');
            btn.classList.add('border-brand-medium', 'text-brand-dark', 'bg-brand-light/30');
            btn.classList.remove('border-slate-200');
        } else {
            label.textContent = 'Specific Date';
            badge.classList.add('hidden');
            btn.classList.remove('border-brand-medium', 'text-brand-dark', 'bg-brand-light/30');
            btn.classList.add('border-slate-200');
        }
    }

    function setupBarangayModal() {
        const zoneSelect = document.getElementById('modalFilterZone');
        const zones = [...new Set(BARANGAYS.map(item => item.zone).filter(Boolean))].sort((a, b) => {
            const aNum = parseInt(String(a).replace(/\D/g, ''), 10) || 0;
            const bNum = parseInt(String(b).replace(/\D/g, ''), 10) || 0;
            return aNum - bNum;
        });

        zoneSelect.innerHTML = '<option value="">Select Zone</option>' + zones.map(zone => `<option value="${escapeExportHtml(zone)}">${escapeExportHtml(zone)}</option>`).join('');
    }

    function openBarangayFilterModal() {
        document.getElementById('modalFilterZone').value = activeZone;
        populateModalBarangays(activeBarangay);
        openModal('barangayFilterModal');
    }

    function populateModalBarangays(selectedBarangay = '') {
        const zone = document.getElementById('modalFilterZone').value;
        const barangaySelect = document.getElementById('modalFilterBarangay');

        if (!zone) {
            barangaySelect.innerHTML = '<option value="">Select a zone first</option>';
            barangaySelect.disabled = true;
            return;
        }

        const barangays = BARANGAYS.filter(item => item.zone === zone);
        barangaySelect.disabled = false;
        barangaySelect.innerHTML = '<option value="">All barangays in this zone</option>' + barangays.map(item => {
            const selected = item.name === selectedBarangay ? 'selected' : '';
            return `<option value="${escapeExportHtml(item.name)}" ${selected}>${escapeExportHtml(item.name)}</option>`;
        }).join('');
    }

    function applyBarangayFilter() {
        const selectedZone = document.getElementById('modalFilterZone').value;
        const selectedBarangay = document.getElementById('modalFilterBarangay').value;

        if (selectedZone && !selectedBarangay) {
            showToast('Please select a barangay after choosing a zone.', 'warning');
            return;
        }

        activeZone = selectedZone;
        activeBarangay = selectedBarangay;
        updateBarangayButtonState();
        closeModal('barangayFilterModal');
        loadPermits(1);
    }

    function clearBarangayFilter() {
        activeZone = '';
        activeBarangay = '';
        document.getElementById('modalFilterZone').value = '';
        populateModalBarangays();
        updateBarangayButtonState();
        closeModal('barangayFilterModal');
        loadPermits(1);
    }

    function updateBarangayButtonState() {
        const label = document.getElementById('barangayFilterLabel');
        const badge = document.getElementById('barangayFilterBadge');
        const btn = document.getElementById('barangayFilterBtn');

        if (activeBarangay || activeZone) {
            label.textContent = activeBarangay || activeZone;
            badge.classList.remove('hidden');
            btn.classList.add('border-brand-medium', 'text-brand-dark', 'bg-brand-light/30');
            btn.classList.remove('border-slate-200');
        } else {
            label.textContent = 'Barangay';
            badge.classList.add('hidden');
            btn.classList.remove('border-brand-medium', 'text-brand-dark', 'bg-brand-light/30');
            btn.classList.add('border-slate-200');
        }
    }

    function resetFilters() {
        document.getElementById('searchPermitRecord').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        activeBarangay = '';
        activeZone = '';
        activeDateFrom = '';
        activeDateTo = '';
        document.getElementById('modalFilterZone').value = '';
        populateModalBarangays();
        document.getElementById('modalFilterDateFrom').value = '';
        document.getElementById('modalFilterDateTo').value = '';
        document.getElementById('modalDateError').classList.add('hidden');
        updateBarangayButtonState();
        updateSpecificDateButtonState();
        document.querySelectorAll('.quick-filter-btn').forEach(btn => {
            btn.classList.remove('bg-brand-dark', 'text-white', 'border-brand-dark');
        });
        loadPermits(1);
    }

    function changePage(page) {
        if (page < 1 || page > totalPages) return;
        loadPermits(page);
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
    // REALTIME SYNCHRONIZATION (SUPABASE CDC & BROADCAST HUB)
    // ============================================================
    function isRecordModalOpen() {
        const viewModal = document.getElementById('viewPermitRecordModal');
        const renewModal = document.getElementById('renewPermitModal');
        const exportModal = document.getElementById('exportPermitRecordsModal');
        const barangayModal = document.getElementById('barangayFilterModal');
        return (viewModal && !viewModal.classList.contains('hidden')) ||
               (renewModal && !renewModal.classList.contains('hidden')) ||
               (exportModal && !exportModal.classList.contains('hidden')) ||
               (barangayModal && !barangayModal.classList.contains('hidden'));
    }

    function setupRealtimePermitSync() {
        let syncTimer = null;
        let lastFetchTime = Date.now();
        const debouncedRefresh = () => {
            clearTimeout(syncTimer);
            syncTimer = setTimeout(() => {
                if (window._lastLocalRecordAction && (Date.now() - window._lastLocalRecordAction < 1500)) {
                    return;
                }
                loadStats();
                if (!isRecordModalOpen()) {
                    loadPermits(currentPage, true);
                    lastFetchTime = Date.now();
                }
            }, 350);
        };

        const onRealtimeUpdate = (e) => {
            debouncedRefresh();
        };

        window.addEventListener('sanitationPermitsUpdated', onRealtimeUpdate);
        window.addEventListener('realtimeUpdate', (e) => {
            if (!e.detail || !e.detail.module || e.detail.module === 'permits' || e.detail.module === 'inspections') {
                onRealtimeUpdate(e);
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden && !isRecordModalOpen() && (Date.now() - lastFetchTime > 45000)) {
                loadStats();
                loadPermits(currentPage, true);
                lastFetchTime = Date.now();
            }
        });

        // 60s background heartbeat sync (throttled)
        setInterval(() => {
            if (!document.hidden && !isRecordModalOpen() && (Date.now() - lastFetchTime > 45000)) {
                loadStats();
                loadPermits(currentPage, true);
                lastFetchTime = Date.now();
            }
        }, 60000);
    }

    // ============================================================
    // INITIALIZE
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        setupBarangayModal();
        loadStats();
        loadPermits(currentPage);
        setupRealtimePermitSync();
    });
</script>

<?php include_once '../../includes/footer.php'; ?>
