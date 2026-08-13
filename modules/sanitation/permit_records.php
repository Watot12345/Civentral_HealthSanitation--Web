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

$title = 'Permit Records';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
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
                    <span class="text-[10px] text-slate-400"><span id="statActiveMini">0</span> active</span>
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
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-circle-check mr-1"></i>Valid</span>
                    <span class="text-[10px] text-slate-400">Currently active</span>
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

    <!-- ============================================================ -->
    <!-- MODERN SUMMARY CARDS                                        -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <!-- Revenue Card -->
        <div class="relative overflow-hidden bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-2xl p-5 shadow-sm">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-white/10 rounded-full"></div>
            <div class="relative flex items-center justify-between text-white">
                <div>
                    <p class="text-sm font-medium opacity-80"><i class="fa-solid fa-coins mr-1"></i>Total Revenue</p>
                    <p class="text-2xl font-bold mt-1" id="statRevenue">₱0.00</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-coins text-2xl text-white/80"></i>
                </div>
            </div>
        </div>

        <!-- Renewals Card -->
        <div class="relative overflow-hidden bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-5 shadow-sm">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-white/10 rounded-full"></div>
            <div class="relative flex items-center justify-between text-white">
                <div>
                    <p class="text-sm font-medium opacity-80"><i class="fa-solid fa-rotate mr-1"></i>Total Renewals</p>
                    <p class="text-2xl font-bold mt-1" id="statRenewals">0</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-rotate text-2xl text-white/80"></i>
                </div>
            </div>
        </div>

        <!-- Active Rate Card -->
        <div class="relative overflow-hidden bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl p-5 shadow-sm">
            <div class="absolute -top-12 -right-12 w-32 h-32 bg-white/10 rounded-full"></div>
            <div class="relative flex items-center justify-between text-white">
                <div>
                    <p class="text-sm font-medium opacity-80"><i class="fa-solid fa-chart-pie mr-1"></i>Active Rate</p>
                    <p class="text-2xl font-bold mt-1" id="statActiveRate">0%</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-chart-pie text-2xl text-white/80"></i>
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
                    <option value="active">Active</option>
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
                <select id="filterBarangay" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Barangays</option>
                    <option value="Barangay San Jose">San Jose</option>
                    <option value="Barangay Poblacion">Poblacion</option>
                    <option value="Barangay Riverside">Riverside</option>
                    <option value="Barangay San Roque">San Roque</option>
                    <option value="Barangay Sta. Cruz">Sta. Cruz</option>
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
        <!-- Quick Filter Buttons -->
        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-slate-100">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-1">Quick Filters:</span>
            <button onclick="quickFilter('all')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all active:bg-brand-dark active:text-white active:border-brand-dark">
                All
            </button>
            <button onclick="quickFilter('active')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all">
                <i class="fa-solid fa-circle-check mr-1"></i>Active
            </button>
            <button onclick="quickFilter('expired')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i>Expired
            </button>
            <button onclick="quickFilter('pending')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all">
                <i class="fa-solid fa-hourglass-half mr-1"></i>Pending
            </button>
            <button onclick="quickFilter('under_review')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all">
                <i class="fa-solid fa-clipboard-list mr-1"></i>Under Review
            </button>
            <button onclick="quickFilter('rejected')" class="quick-filter-btn px-3 py-1 text-xs rounded-lg border border-slate-200 text-slate-600 hover:bg-brand-light/40 hover:border-brand-medium transition-all">
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
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
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

<!-- ============================================================ -->
<!-- VIEW PERMIT RECORD MODAL                                     -->
<!-- ============================================================ -->
<div id="viewPermitRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Permit Record Details</h3>
            <button onclick="closeModal('viewPermitRecordModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="permitRecordDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
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
    // PERMIT API CLIENT
    // ============================================================
    const API_BASE = '../../api/permits.php';

    async function apiRequest(url, options = {}) {
        try {
            const response = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                },
                ...options,
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

    async function renewPermit(id, data) {
        return apiRequest(API_BASE + '?id=' + id + '&action=update', {
            method: 'POST',
            body: JSON.stringify({
                ...data,
                status: 'active'
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

    // ============================================================
    // LOAD PERMITS FROM API
    // ============================================================
    async function loadPermits(page = currentPage) {
        try {
            showLoading(true);

            const filters = {
                page: page,
                limit: currentLimit,
                status: document.getElementById('filterStatus').value,
                type: document.getElementById('filterType').value,
                barangay: document.getElementById('filterBarangay').value,
                search: document.getElementById('searchPermitRecord').value,
                dateFrom: document.getElementById('filterDateFrom').value,
                dateTo: document.getElementById('filterDateTo').value,
            };

            if (filters.dateFrom && filters.dateTo && filters.dateFrom > filters.dateTo) {
                showToast('The start date cannot be after the end date', 'warning');
                showLoading(false);
                return;
            }

            const result = await getPermits(filters);

            allPermits = {};
            result.data.forEach(p => {
                allPermits[p.id] = p;
            });

            totalPages = result.total_pages || 1;
            currentPage = page;

            renderPermitTable(result.data);
            updatePagination(result.page, result.total_pages, result.total);

            showLoading(false);
        } catch (error) {
            console.error('Failed to load permits:', error);
            showToast('Failed to load permits: ' + error.message, 'danger');
            showLoading(false);
        }
    }

    // ============================================================
    // LOAD STATISTICS FROM API
    // ============================================================
    async function loadStats() {
        try {
            const result = await getStats();
            const stats = result.data;

            document.getElementById('statTotal').textContent = stats.total || 0;
            document.getElementById('statActive').textContent = stats.active || 0;
            document.getElementById('statActiveMini').textContent = stats.active || 0;
            document.getElementById('statPending').textContent = stats.pending || 0;
            document.getElementById('statUnderReview').textContent = stats.under_review || 0;
            document.getElementById('statExpired').textContent = stats.expired || 0;
            document.getElementById('statRejected').textContent = stats.rejected || 0;

            // Revenue
            const revenue = parseFloat(stats.total_revenue) || 0;
            document.getElementById('statRevenue').textContent = '₱' + revenue.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            // Renewals
            document.getElementById('statRenewals').textContent = stats.total_renewals || 0;

            // Active Rate
            const activeRate = stats.total > 0 ? Math.round((stats.active / stats.total) * 100) : 0;
            document.getElementById('statActiveRate').textContent = activeRate + '%';

        } catch (error) {
            console.error('Failed to load stats:', error);
        }
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
            pending: 'bg-amber-100 text-amber-700',
            under_review: 'bg-blue-100 text-blue-700',
            expired: 'bg-slate-100 text-slate-500',
            rejected: 'bg-rose-100 text-rose-700'
        };

        tbody.innerHTML = permits.map(permit => `
        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors permit-record-row"
            data-applicant="${(permit.applicant || '').toLowerCase()}"
            data-type="${(permit.business_type || '').toLowerCase()}"
            data-status="${permit.status || ''}"
            data-barangay="${permit.barangay || ''}"
            data-id="${permit.permit_id || ''}">
            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${permit.permit_id || '—'}</td>
            <td class="px-4 py-3">
                <div>
                    <p class="font-semibold text-slate-800 text-sm">${permit.applicant || '—'}</p>
                    <p class="text-xs text-slate-400 maskable">${permit.owner_name || '—'}</p>
                </div>
            </td>
            <td class="px-4 py-3 text-slate-600 text-xs">${permit.business_type || '—'}</td>
            <td class="px-4 py-3 text-slate-600 text-xs maskable">${permit.barangay || '—'}</td>
            <td class="px-4 py-3">
                <span class="text-xs font-semibold text-slate-700">₱${parseFloat(permit.fee || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColors[permit.status] || statusColors.pending}">
                    ${(permit.status || 'pending').replace('_', ' ').toUpperCase()}
                </span>
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
                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>
                    ${permit.status === 'expired' ? `
                        <button onclick="renewPermit(${permit.id})"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Renew">
                            <i class="fa-solid fa-rotate text-sm"></i>
                        </button>
                    ` : ''}
                    ${permit.status === 'active' || permit.status === 'under_review' ? `
                        <button onclick="editPermitRecord(${permit.id})"
                                class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                            <i class="fa-solid fa-pen text-sm"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        </tr>
    `).join('');
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
    // VIEW PERMIT RECORD (via API)
    // ============================================================
    async function viewPermitRecord(id) {
        openModal('viewPermitRecordModal');

        try {
            const result = await getPermit(id);
            const p = result.data;

            if (!p) {
                document.getElementById('permitRecordDetailsContent').innerHTML = '<p class="text-center text-slate-500">Permit not found</p>';
                return;
            }

            const statusColors = {
                active: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                under_review: 'bg-blue-100 text-blue-700',
                expired: 'bg-slate-100 text-slate-500',
                rejected: 'bg-rose-100 text-rose-700'
            };

            const docsHtml = (p.documents || []).map(d => `
            <span class="px-2 py-1 bg-slate-100 rounded text-xs text-slate-600">${d.file_name || d}</span>
        `).join('') || '<span class="text-xs text-slate-400">No documents</span>';

            document.getElementById('permitRecordDetailsContent').innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                        ${(p.applicant || 'P').charAt(0)}
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">${p.applicant || '—'}</h4>
                        <p class="text-sm text-slate-500">${p.permit_id || '—'} • ${p.business_type || '—'}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[p.status] || statusColors.pending}">
                            ${(p.status || 'pending').replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="text-xs text-slate-400 font-semibold">Owner</p><p class="text-sm text-slate-800 maskable">${p.owner_name || '—'}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Contact</p><p class="text-sm text-slate-800 maskable">${p.contact || '—'}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Email</p><p class="text-sm text-slate-800 maskable">${p.email || '—'}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Barangay</p><p class="text-sm text-slate-800 maskable">${p.barangay || p.address || '—'}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Fee</p><p class="text-sm text-slate-800 font-bold">₱${parseFloat(p.fee || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Payment</p><p class="text-sm text-slate-800">${p.paid ? '<i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i>Paid' : '<i class="fa-solid fa-circle-xmark text-rose-600 mr-1"></i>Unpaid'} ${p.payment_method ? `(${p.payment_method})` : ''}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Date Applied</p><p class="text-sm text-slate-800">${p.created_at ? new Date(p.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Expiry Date</p><p class="text-sm text-slate-800">${p.expiry_date ? new Date(p.expiry_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '—'}</p></div>
                </div>
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h5 class="text-sm font-bold text-slate-700 mb-2"><i class="fa-solid fa-paperclip mr-1"></i>Documents</h5>
                    <div class="flex flex-wrap gap-2">${docsHtml}</div>
                </div>
                ${p.notes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">📝 Notes</h5><p class="text-sm text-slate-800">${p.notes}</p></div>` : ''}
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('viewPermitRecordModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    ${p.status === 'expired' ? `<button onclick="closeModal('viewPermitRecordModal'); renewPermit(${p.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-rotate mr-1.5"></i> Renew</button>` : ''}
                </div>
            </div>
        `;
        } catch (error) {
            document.getElementById('permitRecordDetailsContent').innerHTML =
                `<p class="text-center text-rose-500">Failed to load permit details: ${error.message}</p>`;
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
            await renewPermit(renewPermitId, data);
            closeModal('renewPermitModal');
            showToast('Permit renewed successfully!', 'success');
            loadPermits(currentPage);
            loadStats();
        } catch (error) {
            showToast('Failed to renew permit: ' + error.message, 'danger');
        }
    }

    // ============================================================
    // EDIT PERMIT RECORD
    // ============================================================
    function editPermitRecord(id) {
        showToast('Edit feature coming soon (Permit ID: ' + id + ')', 'info');
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
                if (btn.textContent.trim().toLowerCase().includes(status.replace('_', ' '))) {
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
                barangay: document.getElementById('filterBarangay').value,
                search: document.getElementById('searchPermitRecord').value,
                dateFrom: document.getElementById('filterDateFrom').value,
                dateTo: document.getElementById('filterDateTo').value
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
        <img src="${window.location.origin}/capstone/assets/images/logo.png" alt="Logo"><h1>Health Sanitation Management Caloocan</h1><h2>${escapeExportHtml(title)}</h2><p>Generated: ${new Date().toLocaleString()}</p>
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
    document.getElementById('filterBarangay').addEventListener('change', () => loadPermits(1));
    document.getElementById('filterDateFrom').addEventListener('change', () => loadPermits(1));
    document.getElementById('filterDateTo').addEventListener('change', () => loadPermits(1));

    function resetFilters() {
        document.getElementById('searchPermitRecord').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        document.getElementById('filterBarangay').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
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
    // INITIALIZE
    // ============================================================
    document.addEventListener('DOMContentLoaded', () => {
        loadStats();
        loadPermits(currentPage);
    });
</script>

<?php include_once '../../includes/footer.php'; ?>