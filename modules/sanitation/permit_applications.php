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
// 1. PHP BACKEND - Include headers & dependencies
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
require_once __DIR__ . '/../../includes/data-mask.php';

$title = 'Permit Applications';
?>
<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Permit Applications</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage sanitation permit applications</p>
        </div>
        <div class="flex gap-3">
            <button onclick="ModalSystem.open('newPermitModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> New Application
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Loaded dynamically via API               -->
    <!-- ============================================================ -->
    <div id="statsContainer" class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
        <!-- Stats loaded via JavaScript -->
        <div class="col-span-full flex items-center justify-center py-8 text-slate-400">
            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading statistics...
        </div>
    </div>

    <!-- Revenue Card - Modern -->
    <div id="revenueCard" class="relative overflow-hidden bg-gradient-to-r from-brand-dark to-brand-medium rounded-2xl p-5 mb-6 text-white shadow-sm">
        <div class="absolute -top-12 -right-12 w-32 h-32 bg-white/10 rounded-full"></div>
        <div class="relative flex items-center justify-between">
            <div>
                <span class="text-sm font-medium opacity-80">💰 Total Revenue Collected</span>
                <p id="totalRevenue" class="text-2xl font-bold mt-1">₱0.00</p>
            </div>
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-coins text-2xl text-white/80"></i>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchPermit"
                       placeholder="Search by applicant, ID, or business type..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
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
                </select>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
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
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Address</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Fee</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date Applied</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="permitTableBody">
                    <tr id="loadingRow">
                        <td colspan="8" class="px-4 py-10 text-center text-slate-400">
                            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading permits...
                        </td>
                    </tr>
                </tbody>
            </table>
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
            <p id="paginationInfo" class="text-xs text-slate-500">Loading...</p>
            <div id="paginationButtons" class="flex gap-1">
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- NEW PERMIT APPLICATION MODAL                                 -->
<!-- ============================================================ -->
<div id="newPermitModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-pen text-brand-medium"></i>
                New Permit Application
            </h3>
            <button onclick="ModalSystem.close('newPermitModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="newPermitForm" class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Applicant Name</label>
                    <input type="text" id="permit_applicant" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label>
                    <input type="text" id="permit_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Business Type</label>
                <select id="permit_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Business Type</option>
                    <option value="Food Establishment">Food Establishment</option>
                    <option value="Market Vendor">Market Vendor</option>
                    <option value="Bakery">Bakery</option>
                    <option value="Recreational Facility">Recreational Facility</option>
                    <option value="Retail Store">Retail Store</option>
                    <option value="Pharmacy">Pharmacy</option>
                    <option value="Agricultural">Agricultural</option>
                    <option value="Office/Commercial">Office/Commercial</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="permit_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                    <input type="text" id="permit_contact" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                    <input type="email" id="permit_email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fee (₱)</label>
                <input type="number" id="permit_fee" required step="0.01" min="0" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Method</label>
                <select id="permit_payment" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Payment Method</option>
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Over-the-Counter">Over-the-Counter</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="permit_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('newPermitModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-file-pen mr-1.5"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW PERMIT MODAL                                            -->
<!-- ============================================================ -->
<div id="viewPermitModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Permit Application Details</h3>
            <button onclick="ModalSystem.close('viewPermitModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="permitDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- REVIEW PERMIT MODAL                                          -->
<!-- ============================================================ -->
<div id="reviewPermitModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-brand-medium"></i>
                Review Application
            </h3>
            <button onclick="ModalSystem.close('reviewPermitModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                <div>
                    <p id="reviewApplicant" class="font-semibold text-slate-800 text-sm">Loading...</p>
                    <p id="reviewPermitId" class="text-xs text-slate-400">Loading...</p>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Review Status</label>
                <select id="review_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="under_review">Under Review</option>
                    <option value="approved">Approve</option>
                    <option value="rejected">Reject</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Inspector</label>
                <select id="review_inspector" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Inspector</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Review Notes</label>
                <textarea id="review_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Inspection findings, recommendations..."></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 pb-6">
            <button type="button" onclick="ModalSystem.close('reviewPermitModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                Cancel
            </button>
            <button type="button" onclick="submitReview()"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                <i class="fa-solid fa-check mr-1.5"></i> Submit Review
            </button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT PERMIT MODAL                                            -->
<!-- ============================================================ -->
<div id="editPermitModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-brand-medium"></i>
                Edit Permit Application
            </h3>
            <button onclick="ModalSystem.close('editPermitModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editPermitForm" class="p-6 space-y-4">
            <input type="hidden" id="edit_permit_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Applicant Name</label>
                    <input type="text" id="edit_applicant" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label>
                    <input type="text" id="edit_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Business Type</label>
                <select id="edit_type" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Business Type</option>
                    <option value="Food Establishment">Food Establishment</option>
                    <option value="Market Vendor">Market Vendor</option>
                    <option value="Bakery">Bakery</option>
                    <option value="Recreational Facility">Recreational Facility</option>
                    <option value="Retail Store">Retail Store</option>
                    <option value="Pharmacy">Pharmacy</option>
                    <option value="Agricultural">Agricultural</option>
                    <option value="Office/Commercial">Office/Commercial</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="edit_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                    <input type="text" id="edit_contact" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                    <input type="email" id="edit_email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fee (₱)</label>
                <input type="number" id="edit_fee" required step="0.01" min="0" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Method</label>
                <select id="edit_payment" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Payment Method</option>
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Over-the-Counter">Over-the-Counter</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="edit_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('editPermitModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-floppy-disk mr-1.5"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- TOAST & MODAL SYSTEM                                         -->
<!-- ============================================================ -->
<?php include_once __DIR__ . '/../../includes/toast.php'; ?>
<script src="../../assets/js/modal-system.js"></script>

<!-- ============================================================ -->
<!-- JAVASCRIPT - API-Driven Application                         -->
<!-- ============================================================ -->
<script>
// ============================================================
// API CONFIGURATION
// ============================================================
const API_BASE = '../../api/permits.php';
let currentPage = 1;
const PAGE_LIMIT = 5;
let totalPages = 1;
let totalRecords = 0;
let permitsCache = {};

// ============================================================
// STATUS COLOR MAP
// ============================================================
const STATUS_COLORS = {
    pending: 'bg-amber-100 text-amber-700',
    under_review: 'bg-blue-100 text-blue-700',
    approved: 'bg-emerald-100 text-emerald-700',
    rejected: 'bg-rose-100 text-rose-700',
    expired: 'bg-slate-100 text-slate-500'
};

const STATUS_LABELS = {
    pending: 'Pending',
    under_review: 'Under Review',
    approved: 'Approved',
    rejected: 'Rejected',
    expired: 'Expired'
};

// ============================================================
// API HELPER
// ============================================================
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            ...options
        });
        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'API request failed');
        }
        return data;
    } catch (err) {
        console.error('API Error:', err);
        throw err;
    }
}

// ============================================================
// LOAD STATS
// ============================================================
async function loadStats() {
    try {
        const result = await apiRequest(API_BASE + '?stats=true');
        const stats = result.data;
        
        document.getElementById('statsContainer').innerHTML = `
            <!-- Card 1: Total Applications -->
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                            <i class="fa-solid fa-file-lines text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-900">${stats.total}</p>
                            <p class="text-xs font-medium text-slate-500">Total Applications</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All permits</span>
                        <span class="text-[10px] text-slate-400">${stats.approved} approved</span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Pending -->
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                            <i class="fa-solid fa-clock text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-amber-600">${stats.pending}</p>
                            <p class="text-xs font-medium text-slate-500">Pending</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                        <span class="text-[10px] text-slate-400">Initial review</span>
                    </div>
                </div>
            </div>

            <!-- Card 3: Under Review -->
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                            <i class="fa-solid fa-clipboard-list text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-blue-600">${stats.under_review}</p>
                            <p class="text-xs font-medium text-slate-500">Under Review</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🔍 In progress</span>
                        <span class="text-[10px] text-slate-400">Being evaluated</span>
                    </div>
                </div>
            </div>

            <!-- Card 4: Approved -->
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                            <i class="fa-solid fa-check-circle text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-emerald-600">${stats.approved}</p>
                            <p class="text-xs font-medium text-slate-500">Approved</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Granted</span>
                        <span class="text-[10px] text-slate-400">Permits issued</span>
                    </div>
                </div>
            </div>

            <!-- Card 5: Rejected -->
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                            <i class="fa-solid fa-circle-xmark text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-rose-600">${stats.rejected}</p>
                            <p class="text-xs font-medium text-slate-500">Rejected</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">❌ Denied</span>
                        <span class="text-[10px] text-slate-400">Non-compliant</span>
                    </div>
                </div>
            </div>

            <!-- Card 6: Expired -->
            <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
                <div class="absolute -top-12 -right-12 w-24 h-24 bg-slate-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
                <div class="relative">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 bg-gradient-to-br from-slate-500 to-slate-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-slate-200">
                            <i class="fa-solid fa-calendar-xmark text-lg"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-black text-slate-600">${stats.expired}</p>
                            <p class="text-xs font-medium text-slate-500">Expired</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold">📅 Overdue</span>
                        <span class="text-[10px] text-slate-400">Needs renewal</span>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('totalRevenue').textContent = '₱' + Number(stats.total_revenue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } catch (err) {
        document.getElementById('statsContainer').innerHTML = `
            <div class="col-span-full flex items-center justify-center py-8 text-rose-500">
                <i class="fa-solid fa-exclamation-circle mr-2"></i> Failed to load statistics
            </div>
        `;
    }
}

// ============================================================
// LOAD PERMITS
// ============================================================
async function loadPermits(page = 1) {
    currentPage = page;
    const search = document.getElementById('searchPermit').value;
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;

    let url = API_BASE + '?page=' + page + '&limit=' + PAGE_LIMIT;
    if (search) url += '&q=' + encodeURIComponent(search);
    if (status) url += '&status=' + encodeURIComponent(status);
    if (type) url += '&type=' + encodeURIComponent(type);

    try {
        const result = await apiRequest(url);
        const permits = result.data || [];
        totalPages = result.total_pages || 1;
        totalRecords = result.total || 0;

        // Cache permits by ID
        permits.forEach(p => { permitsCache[p.id] = p; });

        renderTable(permits);
        renderPagination();
    } catch (err) {
        document.getElementById('permitTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-rose-500">
                    <i class="fa-solid fa-exclamation-circle mr-2"></i> Failed to load permits: ${err.message}
                </td>
            </tr>
        `;
    }
}

// ============================================================
// RENDER TABLE
// ============================================================
function renderTable(permits) {
    const tbody = document.getElementById('permitTableBody');
    const emptyState = document.getElementById('emptyState');

    if (permits.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');
        return;
    }

    emptyState.classList.add('hidden');

    tbody.innerHTML = permits.map(p => {
        const statusColor = STATUS_COLORS[p.status] || STATUS_COLORS.pending;
        const statusLabel = STATUS_LABELS[p.status] || p.status;
        const dateApplied = p.created_at ? new Date(p.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
        const paidBadge = p.paid
            ? '<span class="ml-1 text-[10px] text-emerald-600">✓ Paid</span>'
            : '<span class="ml-1 text-[10px] text-rose-500">Unpaid</span>';

        // Data masking: apply maskable class to applicant and owner name (client/citizen data)
        const maskedApplicant = maskName(p.applicant);
        const maskedOwner = maskName(p.owner_name);

        return `
        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors permit-row"
            data-id="${p.id}">
            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${p.permit_id}</td>
            <td class="px-4 py-3">
                <div>
                    <p class="font-semibold text-slate-800 text-sm maskable" data-real="${escapeHtml(p.applicant)}" data-masked="${escapeHtml(maskedApplicant)}">${p.applicant}</p>
                    <p class="text-xs text-slate-400 maskable" data-real="${escapeHtml(p.owner_name)}" data-masked="${escapeHtml(maskedOwner)}">${p.owner_name}</p>
                </div>
            </td>
            <td class="px-4 py-3 text-slate-600 text-xs">${p.business_type}</td>
            <td class="px-4 py-3 text-slate-600 text-xs">${p.address}</td>
            <td class="px-4 py-3">
                <span class="text-xs font-semibold text-slate-700">₱${Number(p.fee).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                ${paidBadge}
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColor}">
                    ${statusLabel}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs">${dateApplied}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="viewPermit(${p.id})"
                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>
                    ${p.status === 'pending' || p.status === 'under_review' ? `
                        <button onclick="reviewPermit(${p.id})"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Review">
                            <i class="fa-solid fa-clipboard-list text-sm"></i>
                        </button>
                        <button onclick="quickStatusUpdate(${p.id}, 'approved')"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Approve">
                            <i class="fa-solid fa-check text-sm"></i>
                        </button>
                        <button onclick="quickStatusUpdate(${p.id}, 'rejected')"
                                class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Reject">
                            <i class="fa-solid fa-times text-sm"></i>
                        </button>
                    ` : ''}
                    <button onclick="editPermit(${p.id})"
                            class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                        <i class="fa-solid fa-pen text-sm"></i>
                    </button>
                    <button onclick="deletePermit(${p.id})"
                            class="p-1.5 text-rose-400 hover:bg-rose-50 hover:text-rose-600 rounded-lg transition" title="Delete">
                        <i class="fa-solid fa-trash-can text-sm"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ============================================================
// RENDER PAGINATION
// ============================================================
function renderPagination() {
    const start = ((currentPage - 1) * PAGE_LIMIT) + 1;
    const end = Math.min(currentPage * PAGE_LIMIT, totalRecords);

    document.getElementById('paginationInfo').textContent =
        totalRecords > 0
            ? `Showing ${start} to ${end} of ${totalRecords} applications`
            : 'No applications found';

    const container = document.getElementById('paginationButtons');
    let html = '';

    // Previous button
    html += `<button onclick="changePage(${currentPage - 1})"
        class="px-3 py-1.5 rounded-lg text-sm ${currentPage <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}"
        ${currentPage <= 1 ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-left text-xs"></i>
    </button>`;

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="changePage(${i})"
            class="px-3 py-1.5 rounded-lg text-sm font-medium ${i === currentPage ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}">
            ${i}
        </button>`;
    }

    // Next button
    html += `<button onclick="changePage(${currentPage + 1})"
        class="px-3 py-1.5 rounded-lg text-sm ${currentPage >= totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}"
        ${currentPage >= totalPages ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-right text-xs"></i>
    </button>`;

    container.innerHTML = html;
}

// ============================================================
// CHANGE PAGE
// ============================================================
function changePage(page) {
    if (page < 1 || page > totalPages) return;
    loadPermits(page);
}

// ============================================================
// SEARCH & FILTER
// ============================================================
document.getElementById('searchPermit').addEventListener('input', debounce(() => loadPermits(1), 300));
document.getElementById('filterStatus').addEventListener('change', () => loadPermits(1));
document.getElementById('filterType').addEventListener('change', () => loadPermits(1));

function resetFilters() {
    document.getElementById('searchPermit').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterType').value = '';
    loadPermits(1);
}

// ============================================================
// DEBOUNCE UTILITY
// ============================================================
function debounce(fn, delay) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ============================================================
// HTML ESCAPE
// ============================================================
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ============================================================
// DATA MASKING HELPER
// ============================================================
function maskName(name) {
    if (!name) return '';
    return name.split(' ').map(function(p) {
        if (!p) return '';
        return p.charAt(0).toUpperCase() + '*'.repeat(Math.max(0, p.length - 1));
    }).join(' ');
}

// ============================================================
// VIEW PERMIT
// ============================================================
async function viewPermit(id) {
    ModalSystem.open('viewPermitModal');
    document.getElementById('permitDetailsContent').innerHTML = `
        <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
            <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
        </div>
    `;

    try {
        const result = await apiRequest(API_BASE + '?id=' + id);
        const p = result.data;
        permitsCache[p.id] = p;

        const statusColor = STATUS_COLORS[p.status] || STATUS_COLORS.pending;
        const statusLabel = STATUS_LABELS[p.status] || p.status;
        const dateApplied = p.created_at ? new Date(p.created_at).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'N/A';
        const dateApproved = p.approved_date ? new Date(p.approved_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'Not yet';
        const expiryDate = p.expiry_date ? new Date(p.expiry_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : 'N/A';

        // Data masking for client/citizen fields
        const maskedApplicant = maskName(p.applicant);
        const maskedOwner = maskName(p.owner_name);
        const maskedContact = p.contact ? p.contact.slice(0, 4) + '*****' : '';

        document.getElementById('permitDetailsContent').innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                        ${p.applicant ? p.applicant.charAt(0).toUpperCase() : '?'}
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 maskable" data-real="${escapeHtml(p.applicant)}" data-masked="${escapeHtml(maskedApplicant)}">${p.applicant}</h4>
                        <p class="text-sm text-slate-500">${p.permit_id} • ${p.business_type}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColor}">
                            ${statusLabel.toUpperCase()}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Owner</p>
                        <p class="text-sm text-slate-800 maskable" data-real="${escapeHtml(p.owner_name)}" data-masked="${escapeHtml(maskedOwner)}">${p.owner_name}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Contact</p>
                        <p class="text-sm text-slate-800 maskable" data-real="${escapeHtml(p.contact)}" data-masked="${escapeHtml(maskedContact)}">${p.contact}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Email</p>
                        <p class="text-sm text-slate-800">${p.email || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Address</p>
                        <p class="text-sm text-slate-800">${p.address}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Fee</p>
                        <p class="text-sm text-slate-800 font-bold">₱${Number(p.fee).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Payment</p>
                        <p class="text-sm text-slate-800">${p.paid ? 'Paid via ' + (p.payment_method || 'N/A') : 'Unpaid'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Date Applied</p>
                        <p class="text-sm text-slate-800">${dateApplied}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Date Approved</p>
                        <p class="text-sm text-slate-800">${dateApproved}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Expiry Date</p>
                        <p class="text-sm text-slate-800">${expiryDate}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Inspector</p>
                        <p class="text-sm text-slate-800">${p.inspector_id ? 'Inspector #' + p.inspector_id : 'Not assigned'}</p>
                    </div>
                </div>
                ${p.notes ? `
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5>
                        <p class="text-sm text-slate-800">${escapeHtml(p.notes)}</p>
                    </div>
                ` : ''}
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="ModalSystem.close('viewPermitModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    ${p.status === 'pending' ? `
                        <button onclick="ModalSystem.close('viewPermitModal'); reviewPermit(${p.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold">
                            <i class="fa-solid fa-clipboard-list mr-1.5"></i> Review
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    } catch (err) {
        document.getElementById('permitDetailsContent').innerHTML = `
            <div class="flex items-center justify-center py-10 text-rose-500 text-sm">
                <i class="fa-solid fa-exclamation-circle mr-2"></i> ${err.message}
            </div>
        `;
    }
}

// ============================================================
// REVIEW PERMIT
// ============================================================
let reviewPermitId = null;

async function reviewPermit(id) {
    reviewPermitId = id;
    
    // Load permit data
    try {
        const result = await apiRequest(API_BASE + '?id=' + id);
        const p = result.data;
        permitsCache[p.id] = p;

        document.getElementById('reviewApplicant').textContent = p.applicant;
        document.getElementById('reviewPermitId').textContent = p.permit_id;
        document.getElementById('review_status').value = p.status === 'pending' ? 'under_review' : p.status;
        document.getElementById('review_notes').value = p.notes || '';
        
        // Load employees for inspector dropdown
        await loadInspectors(p.inspector_id);
        
        ModalSystem.open('reviewPermitModal');
    } catch (err) {
        ModalSystem.toast.error('Failed to load permit: ' + err.message);
    }
}

async function loadInspectors(selectedId) {
    try {
        const result = await apiRequest('../../api/employees.php');
        const employees = result.data || [];
        const select = document.getElementById('review_inspector');
        select.innerHTML = '<option value="">Select Inspector</option>';
        
        employees.forEach(emp => {
            const name = emp.full_name || emp.name || 'Employee #' + emp.id;
            const isSelected = emp.id == selectedId ? 'selected' : '';
            select.innerHTML += `<option value="${emp.id}" ${isSelected}>${escapeHtml(name)}</option>`;
        });
    } catch (err) {
        // Fallback if employees API not available
        const select = document.getElementById('review_inspector');
        select.innerHTML = `
            <option value="">Select Inspector</option>
            <option value="1">Inspector 1</option>
            <option value="2">Inspector 2</option>
            <option value="3">Inspector 3</option>
        `;
    }
}

async function submitReview() {
    const id = reviewPermitId;
    if (!id) return;

    const status = document.getElementById('review_status').value;
    const inspectorId = document.getElementById('review_inspector').value;
    const notes = document.getElementById('review_notes').value;

    try {
        const result = await apiRequest(API_BASE + '?id=' + id + '&action=review', {
            method: 'POST',
            body: JSON.stringify({
                status: status,
                inspector_id: inspectorId || null,
                notes: notes
            })
        });

        ModalSystem.close('reviewPermitModal');
        ModalSystem.toast.success(result.message || 'Permit reviewed successfully!');
        loadPermits(currentPage);
        loadStats();
    } catch (err) {
        ModalSystem.toast.error('Failed to review permit: ' + err.message);
    }
}

// ============================================================
// QUICK STATUS UPDATE
// ============================================================
function quickStatusUpdate(id, status) {
    const label = status.charAt(0).toUpperCase() + status.slice(1);
    ModalSystem.confirm(
        'Mark this permit as ' + label + '?',
        async function() {
            try {
                const result = await apiRequest(API_BASE + '?id=' + id + '&action=status', {
                    method: 'POST',
                    body: JSON.stringify({ status: status })
                });

                ModalSystem.toast.success(result.message || 'Permit marked as ' + label);
                loadPermits(currentPage);
                loadStats();
            } catch (err) {
                ModalSystem.toast.error('Failed to update status: ' + err.message);
            }
        },
        {
            title: 'Update Status',
            confirmText: 'Yes, ' + label,
            type: status === 'approved' ? 'info' : 'warning'
        }
    );
}

// ============================================================
// SAVE PERMIT (New Application)
// ============================================================
document.getElementById('newPermitForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const data = {
        applicant: document.getElementById('permit_applicant').value.trim(),
        owner_name: document.getElementById('permit_owner').value.trim(),
        business_type: document.getElementById('permit_type').value,
        address: document.getElementById('permit_address').value.trim(),
        contact: document.getElementById('permit_contact').value.trim(),
        email: document.getElementById('permit_email').value.trim(),
        fee: parseFloat(document.getElementById('permit_fee').value) || 0,
        payment_method: document.getElementById('permit_payment').value || null,
        notes: document.getElementById('permit_notes').value.trim() || null
    };

    // Validate
    if (!data.applicant || !data.owner_name || !data.business_type || !data.address || !data.contact || data.fee <= 0) {
        ModalSystem.toast.error('Please fill in all required fields');
        return;
    }

    try {
        const result = await apiRequest(API_BASE, {
            method: 'POST',
            body: JSON.stringify(data)
        });

        ModalSystem.close('newPermitModal');
        document.getElementById('newPermitForm').reset();
        ModalSystem.toast.success(result.message || 'Permit application submitted successfully!');
        loadPermits(1);
        loadStats();
    } catch (err) {
        ModalSystem.toast.error('Failed to submit application: ' + err.message);
    }
});

// ============================================================
// EDIT PERMIT
// ============================================================
async function editPermit(id) {
    try {
        const result = await apiRequest(API_BASE + '?id=' + id);
        const p = result.data;
        permitsCache[p.id] = p;

        document.getElementById('edit_permit_id').value = p.id;
        document.getElementById('edit_applicant').value = p.applicant;
        document.getElementById('edit_owner').value = p.owner_name;
        document.getElementById('edit_type').value = p.business_type;
        document.getElementById('edit_address').value = p.address;
        document.getElementById('edit_contact').value = p.contact;
        document.getElementById('edit_email').value = p.email || '';
        document.getElementById('edit_fee').value = p.fee;
        document.getElementById('edit_payment').value = p.payment_method || '';
        document.getElementById('edit_notes').value = p.notes || '';

        ModalSystem.open('editPermitModal');
    } catch (err) {
        ModalSystem.toast.error('Failed to load permit: ' + err.message);
    }
}

document.getElementById('editPermitForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const id = document.getElementById('edit_permit_id').value;
    const data = {
        applicant: document.getElementById('edit_applicant').value.trim(),
        owner_name: document.getElementById('edit_owner').value.trim(),
        business_type: document.getElementById('edit_type').value,
        address: document.getElementById('edit_address').value.trim(),
        contact: document.getElementById('edit_contact').value.trim(),
        email: document.getElementById('edit_email').value.trim(),
        fee: parseFloat(document.getElementById('edit_fee').value) || 0,
        payment_method: document.getElementById('edit_payment').value || null,
        notes: document.getElementById('edit_notes').value.trim() || null
    };

    if (!data.applicant || !data.owner_name || !data.business_type || !data.address || !data.contact || data.fee <= 0) {
        ModalSystem.toast.error('Please fill in all required fields');
        return;
    }

    try {
        const result = await apiRequest(API_BASE + '?id=' + id + '&action=update', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        ModalSystem.close('editPermitModal');
        ModalSystem.toast.success(result.message || 'Permit updated successfully!');
        loadPermits(currentPage);
        loadStats();
    } catch (err) {
        ModalSystem.toast.error('Failed to update permit: ' + err.message);
    }
});

// ============================================================
// DELETE PERMIT
// ============================================================
function deletePermit(id) {
    ModalSystem.confirm(
        'Are you sure you want to delete this permit? This action cannot be undone.',
        async function() {
            try {
                const result = await apiRequest(API_BASE + '?id=' + id + '&action=delete', {
                    method: 'POST'
                });

                ModalSystem.toast.success(result.message || 'Permit deleted successfully');
                loadPermits(currentPage);
                loadStats();
            } catch (err) {
                ModalSystem.toast.error('Failed to delete permit: ' + err.message);
            }
        },
        {
            title: 'Delete Permit',
            confirmText: 'Delete',
            type: 'danger'
        }
    );
}

// ============================================================
// INITIALIZATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadPermits(1);
});
</script>

<?php include_once '../../includes/footer.php'; ?>