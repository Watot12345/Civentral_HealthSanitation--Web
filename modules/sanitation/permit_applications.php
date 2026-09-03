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
requireDepartmentAccess('sanitation permits');
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
            <div class="flex gap-2 flex-wrap items-center">
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
                    <option value="Hotel/Lodging">Hotel/Lodging</option>
                </select>
                <button type="button" onclick="openSpecificDateModal()" id="specificDateBtn"
                        class="px-3.5 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-700 hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-calendar-days text-slate-400"></i>
                    <span id="specificDateLabel">Specific Date</span>
                    <span id="dateFilterBadge" class="hidden px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-brand-light text-brand-dark border border-brand-border">Active</span>
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
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center w-full">
            <div id="emptyIcon" class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3 mx-auto">
                <i class="fa-solid fa-file-circle-xmark text-slate-400"></i>
            </div>
            <p id="emptyTitle" class="text-sm font-semibold text-slate-600">No permits match your filters</p>
            <p id="emptySubtitle" class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button id="emptyResetBtn" onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
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
                    <option value="Hotel/Lodging">Hotel/Lodging</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="permit_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                    <input type="text" id="permit_contact" required minlength="12" maxlength="12" pattern="[0-9]{12}" inputmode="numeric" placeholder="639XXXXXXXXX" class="permit-contact w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                    <input type="email" id="permit_email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1 flex items-center justify-between">
                    <span>Fee (₱)</span>
                    <span class="text-[10px] text-brand-medium font-medium lowercase flex items-center gap-1">
                        <i class="fa-solid fa-scale-balanced"></i> from fee structure
                    </span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold">₱</span>
                    <input type="number" id="permit_fee" required step="0.01" min="0" readonly
                           placeholder="Select Business Type to calculate fee"
                           class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-800 font-bold focus:outline-none cursor-not-allowed">
                </div>
                <div id="permit_fee_breakdown" class="hidden mt-2 p-2.5 bg-brand-light/70 rounded-xl border border-brand-border/70 text-xs text-brand-dark flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5 text-brand-medium"></i>
                    <div>
                        <span class="font-semibold" id="permit_fee_category">Food Establishment</span>
                        <div class="text-[11px] text-slate-600 mt-0.5" id="permit_fee_math">Base Fee: ₱1,500.00 + Inspection Fee: ₱500.00 = <strong>Total: ₱2,000.00</strong></div>
                    </div>
                </div>
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
<!-- ASSIGN TO INSPECTOR MODAL                                    -->
<!-- ============================================================ -->
<div id="assignInspectorModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-user-check text-brand-medium"></i>
                <span id="assignModalTitle">Assign to Inspector</span>
            </h3>
            <button onclick="ModalSystem.close('assignInspectorModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="assignInspectorForm" class="p-6 space-y-4">
            <input type="hidden" id="assign_permit_id">
            
            <div class="p-3.5 bg-brand-light/50 rounded-xl border border-brand-border/70 flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg bg-white border border-brand-border flex items-center justify-center text-brand-dark flex-shrink-0">
                    <i class="fa-solid fa-building text-base"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <p id="assignApplicant" class="font-bold text-slate-900 text-sm truncate">Loading...</p>
                    <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-500 flex-wrap">
                        <span id="assignPermitCode" class="font-mono text-brand-dark font-semibold">Loading...</span>
                        <span>•</span>
                        <span id="assignBusinessType" class="text-slate-600">Business Type</span>
                    </div>
                    <p id="assignAddress" class="text-xs text-slate-500 mt-1 truncate"></p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                    Select Inspector <span class="text-rose-500">*</span>
                </label>
                <select id="assign_inspector_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Loading inspectors...</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                        Inspection Date <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" id="assign_inspection_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                        Scheduled Time
                    </label>
                    <input type="time" id="assign_inspection_time" value="09:00" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes / Instructions for Inspector</label>
                <textarea id="assign_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Special instructions, hazard notes, specific sanitation areas to inspect..."></textarea>
            </div>

            <div class="p-3 rounded-xl bg-blue-50/70 border border-blue-100 text-xs text-blue-800 flex items-start gap-2.5">
                <i class="fa-solid fa-circle-info mt-0.5 text-blue-500 flex-shrink-0"></i>
                <div>
                    <span>Assigning an inspector moves this permit to <strong class="text-blue-900">Under Review</strong> and automatically schedules the inspection in the <a href="inspections.php" target="_blank" class="underline font-semibold hover:text-blue-950">Inspections sub-feature</a>.</span>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="ModalSystem.close('assignInspectorModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitAssign"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-calendar-check text-xs"></i> <span>Assign & Schedule</span>
                </button>
            </div>
        </form>
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
                    <option value="Hotel/Lodging">Hotel/Lodging</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="edit_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                    <input type="text" id="edit_contact" required minlength="12" maxlength="12" pattern="[0-9]{12}" inputmode="numeric" placeholder="639XXXXXXXXX" class="permit-contact w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                    <input type="email" id="edit_email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1 flex items-center justify-between">
                    <span>Fee (₱)</span>
                    <span class="text-[10px] text-brand-medium font-medium lowercase flex items-center gap-1">
                        <i class="fa-solid fa-scale-balanced"></i> from fee structure
                    </span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm font-semibold">₱</span>
                    <input type="number" id="edit_fee" required step="0.01" min="0" readonly
                           placeholder="Select Business Type to calculate fee"
                           class="w-full pl-8 pr-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-800 font-bold focus:outline-none cursor-not-allowed">
                </div>
                <div id="edit_fee_breakdown" class="hidden mt-2 p-2.5 bg-brand-light/70 rounded-xl border border-brand-border/70 text-xs text-brand-dark flex items-start gap-2">
                    <i class="fa-solid fa-circle-info mt-0.5 text-brand-medium"></i>
                    <div>
                        <span class="font-semibold" id="edit_fee_category">Food Establishment</span>
                        <div class="text-[11px] text-slate-600 mt-0.5" id="edit_fee_math">Base Fee: ₱1,500.00 + Inspection Fee: ₱500.00 = <strong>Total: ₱2,000.00</strong></div>
                    </div>
                </div>
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
<!-- SPECIFIC DATE MODAL                                          -->
<!-- ============================================================ -->
<div id="specificDateModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-calendar-days text-brand-medium"></i>
                Specific Date
            </h3>
            <button onclick="ModalSystem.close('specificDateModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
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
            <button type="button" onclick="clearSpecificDateFilter()"
                    class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">
                Clear
            </button>
            <div class="flex gap-2">
                <button type="button" onclick="ModalSystem.close('specificDateModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="button" onclick="applySpecificDateFilter()"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    Apply Filter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- TOAST SYSTEM                                                 -->
<!-- ============================================================ -->
<?php include_once __DIR__ . '/../../includes/toast.php'; ?>

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
let activeDateFrom = '';
let activeDateTo = '';

// ============================================================
// FEE STRUCTURE (Official Schedule & Helpers)
// ============================================================
const FEE_STRUCTURE = {
    'Food Establishment': { base_fee: 1500, inspection_fee: 500, total: 2000 },
    'Market Vendor': { base_fee: 800, inspection_fee: 300, total: 1100 },
    'Bakery': { base_fee: 1200, inspection_fee: 400, total: 1600 },
    'Recreational Facility': { base_fee: 2000, inspection_fee: 600, total: 2600 },
    'Retail Store': { base_fee: 1000, inspection_fee: 350, total: 1350 },
    'Pharmacy': { base_fee: 1800, inspection_fee: 500, total: 2300 },
    'Agricultural': { base_fee: 900, inspection_fee: 300, total: 1200 },
    'Office/Commercial': { base_fee: 2500, inspection_fee: 700, total: 3200 },
    'Hotel/Lodging': { base_fee: 3000, inspection_fee: 800, total: 3800 }
};

async function initFeeStructure() {
    try {
        const res = await fetch('../../api/payments.php?fee_structure=true');
        const json = await res.json();
        if (json && json.success && Array.isArray(json.data)) {
            json.data.forEach(item => {
                FEE_STRUCTURE[item.category] = {
                    base_fee: parseFloat(item.base_fee) || 0,
                    inspection_fee: parseFloat(item.inspection_fee) || 0,
                    total: parseFloat(item.total) || 0
                };
            });
        }
    } catch (e) {
        // Fallback to built-in fee schedule
    }
}

function updateFeeFromStructure(typeSelectId, feeInputId, breakdownContainerId, categorySpanId, mathDivId) {
    const typeSelect = document.getElementById(typeSelectId);
    const feeInput = document.getElementById(feeInputId);
    const breakdown = document.getElementById(breakdownContainerId);
    const catSpan = document.getElementById(categorySpanId);
    const mathDiv = document.getElementById(mathDivId);

    if (!typeSelect || !feeInput) return;

    const selectedType = typeSelect.value;
    const feeData = FEE_STRUCTURE[selectedType];

    if (feeData) {
        feeInput.value = feeData.total.toFixed(2);
        if (breakdown && catSpan && mathDiv) {
            catSpan.textContent = selectedType;
            mathDiv.innerHTML = `Base Fee: ₱${feeData.base_fee.toLocaleString('en-US', {minimumFractionDigits: 2})} + Inspection Fee: ₱${feeData.inspection_fee.toLocaleString('en-US', {minimumFractionDigits: 2})} = <strong>Total: ₱${feeData.total.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>`;
            breakdown.classList.remove('hidden');
        }
    } else {
        feeInput.value = '';
        if (breakdown) {
            breakdown.classList.add('hidden');
        }
    }
}

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
        const result = await apiRequest(API_BASE + '?action=summary');
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

        const totalRevEl = document.getElementById('totalRevenue');
        if (totalRevEl && stats.total_revenue !== undefined) {
            totalRevEl.textContent = '₱' + Number(stats.total_revenue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
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
    const search = document.getElementById('searchPermit').value.trim();
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;
    const dateFrom = activeDateFrom;
    const dateTo = activeDateTo;

    if (dateFrom && dateTo && dateFrom > dateTo) {
        ModalSystem.toast.error('The start date cannot be after the end date');
        return;
    }

    let url = API_BASE + '?page=' + page + '&limit=' + PAGE_LIMIT;
    if (search) url += '&q=' + encodeURIComponent(search);
    if (status) url += '&status=' + encodeURIComponent(status);
    if (type) url += '&type=' + encodeURIComponent(type);
    if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
    if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);

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
    const emptyIcon = document.getElementById('emptyIcon');
    const emptyTitle = document.getElementById('emptyTitle');
    const emptySubtitle = document.getElementById('emptySubtitle');
    const emptyResetBtn = document.getElementById('emptyResetBtn');
    const search = document.getElementById('searchPermit').value.trim();
    const status = document.getElementById('filterStatus').value;
    const type = document.getElementById('filterType').value;
    const dateFrom = activeDateFrom;
    const dateTo = activeDateTo;

    const hasActiveFilters = Boolean(search || status || type || dateFrom || dateTo);

    if (permits.length === 0) {
        tbody.innerHTML = '';
        emptyState.classList.remove('hidden');

        if (hasActiveFilters) {
            emptyIcon.innerHTML = '<i class="fa-solid fa-file-circle-xmark text-slate-400"></i>';
            emptyTitle.textContent = 'No permits match your filters';
            emptySubtitle.textContent = 'Try adjusting your search or clearing filters';
            emptyResetBtn.classList.remove('hidden');
        } else {
            emptyIcon.innerHTML = '<i class="fa-solid fa-inbox text-slate-400"></i>';
            emptyTitle.textContent = 'No applications found';
            emptySubtitle.textContent = 'There are no permit applications yet. Click "New Application" to add one.';
            emptyResetBtn.classList.add('hidden');
        }
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
                    <!-- View Details -->
                    <button onclick="viewPermit(${p.id})"
                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>

                    <!-- Assign / Reassign Inspector -->
                    ${p.status === 'pending' || p.status === 'under_review' ? `
                        <button onclick="openAssignInspectorModal(${p.id})"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                title="${p.status === 'under_review' ? 'Reassign Inspector' : 'Assign to Inspector'}">
                            <i class="fa-solid fa-user-check text-sm"></i>
                        </button>
                    ` : ''}

                    <!-- Edit Application -->
                    <button onclick="editPermit(${p.id})"
                            class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit Application">
                        <i class="fa-solid fa-pen text-sm"></i>
                    </button>

                    <!-- Cancel Application (Pending only) -->
                    ${p.status === 'pending' ? `
                        <button onclick="cancelPermit(${p.id})"
                                class="p-1.5 text-rose-400 hover:bg-rose-50 hover:text-rose-600 rounded-lg transition" title="Cancel Application">
                            <i class="fa-solid fa-ban text-sm"></i>
                        </button>
                    ` : ''}
                    ${p.status === 'rejected' ? `
                        <button onclick="reapplyPermit(${p.id})"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Re-apply">
                            <i class="fa-solid fa-file-circle-plus text-sm"></i>
                        </button>
                    ` : ''}
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

    html += `<button onclick="changePage(${currentPage - 1})"
        class="px-3 py-1.5 rounded-lg text-sm ${currentPage <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}"
        ${currentPage <= 1 ? 'disabled' : ''}>
        <i class="fa-solid fa-chevron-left text-xs"></i>
    </button>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button onclick="changePage(${i})"
            class="px-3 py-1.5 rounded-lg text-sm font-medium ${i === currentPage ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'}">
            ${i}
        </button>`;
    }

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

// Business Type to Fee Structure Listeners
document.getElementById('permit_type').addEventListener('change', function() {
    updateFeeFromStructure('permit_type', 'permit_fee', 'permit_fee_breakdown', 'permit_fee_category', 'permit_fee_math');
});

document.getElementById('edit_type').addEventListener('change', function() {
    updateFeeFromStructure('edit_type', 'edit_fee', 'edit_fee_breakdown', 'edit_fee_category', 'edit_fee_math');
});

// Specific Date Modal Functions
function openSpecificDateModal() {
    const errorEl = document.getElementById('modalDateError');
    if (errorEl) errorEl.classList.add('hidden');
    document.getElementById('modalFilterDateFrom').value = activeDateFrom;
    document.getElementById('modalFilterDateTo').value = activeDateTo;
    ModalSystem.open('specificDateModal');
}

function applySpecificDateFilter() {
    const fromVal = document.getElementById('modalFilterDateFrom').value;
    const toVal = document.getElementById('modalFilterDateTo').value;
    const errorEl = document.getElementById('modalDateError');

    if (fromVal && toVal && fromVal > toVal) {
        if (errorEl) errorEl.classList.remove('hidden');
        return;
    }
    if (errorEl) errorEl.classList.add('hidden');

    activeDateFrom = fromVal;
    activeDateTo = toVal;
    updateSpecificDateButtonState();
    ModalSystem.close('specificDateModal');
    loadPermits(1);
}

function clearSpecificDateFilter() {
    document.getElementById('modalFilterDateFrom').value = '';
    document.getElementById('modalFilterDateTo').value = '';
    activeDateFrom = '';
    activeDateTo = '';
    const errorEl = document.getElementById('modalDateError');
    if (errorEl) errorEl.classList.add('hidden');
    updateSpecificDateButtonState();
    ModalSystem.close('specificDateModal');
    loadPermits(1);
}

function updateSpecificDateButtonState() {
    const label = document.getElementById('specificDateLabel');
    const badge = document.getElementById('dateFilterBadge');
    const btn = document.getElementById('specificDateBtn');

    if (activeDateFrom || activeDateTo) {
        if (activeDateFrom && activeDateTo) {
            label.textContent = `${activeDateFrom} - ${activeDateTo}`;
        } else if (activeDateFrom) {
            label.textContent = `From ${activeDateFrom}`;
        } else {
            label.textContent = `Until ${activeDateTo}`;
        }
        if (badge) badge.classList.remove('hidden');
        if (btn) {
            btn.classList.add('border-brand-medium', 'text-brand-dark', 'bg-brand-light/30');
            btn.classList.remove('border-slate-200');
        }
    } else {
        label.textContent = 'Specific Date';
        if (badge) badge.classList.add('hidden');
        if (btn) {
            btn.classList.remove('border-brand-medium', 'text-brand-dark', 'bg-brand-light/30');
            btn.classList.add('border-slate-200');
        }
    }
}

document.addEventListener('input', event => {
    if (event.target.matches('.permit-contact')) {
        event.target.value = event.target.value.replace(/\D/g, '').slice(0, 12);
    }
});

function resetFilters() {
    document.getElementById('searchPermit').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterType').value = '';
    activeDateFrom = '';
    activeDateTo = '';
    document.getElementById('modalFilterDateFrom').value = '';
    document.getElementById('modalFilterDateTo').value = '';
    const errorEl = document.getElementById('modalDateError');
    if (errorEl) errorEl.classList.add('hidden');
    updateSpecificDateButtonState();
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

        const maskedApplicant = maskName(p.applicant);
        const maskedOwner = maskName(p.owner_name);
        const maskedContact = p.contact ? p.contact.slice(0, 4) + '*****' : '';

        // Rejection reason display
        let rejectionHtml = '';
        if (p.status === 'rejected' && p.rejection_reason) {
            rejectionHtml = `
                <div class="col-span-2 bg-rose-50 rounded-xl p-4 border border-rose-200">
                    <h5 class="text-sm font-bold text-rose-700 mb-2">Rejection Reason</h5>
                    <p class="text-sm text-rose-800">${escapeHtml(p.rejection_reason)}</p>
                </div>
            `;
        }

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
                    ${rejectionHtml}
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
                        <button onclick="ModalSystem.close('viewPermitModal'); openAssignInspectorModal(${p.id})" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-user-check text-xs"></i> Assign to Inspector
                        </button>
                    ` : ''}
                    ${p.status === 'under_review' ? `
                        <a href="inspections.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-clipboard-list text-xs"></i> View in Inspections
                        </a>
                    ` : ''}
                    ${p.status === 'rejected' ? `
                        <button onclick="ModalSystem.close('viewPermitModal'); reapplyPermit(${p.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold flex items-center gap-1.5">
                            <i class="fa-solid fa-file-circle-plus text-xs"></i> Re-apply
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
// ASSIGN TO INSPECTOR
// ============================================================
let assignPermitId = null;

async function openAssignInspectorModal(id) {
    assignPermitId = id;
    
    try {
        const result = await apiRequest(API_BASE + '?id=' + id);
        const p = result.data;
        permitsCache[p.id] = p;

        document.getElementById('assign_permit_id').value = p.id;
        document.getElementById('assignApplicant').textContent = p.applicant || 'N/A';
        document.getElementById('assignPermitCode').textContent = p.permit_id || 'N/A';
        document.getElementById('assignBusinessType').textContent = p.business_type || 'General';
        document.getElementById('assignAddress').textContent = p.address ? '📍 ' + p.address : '';
        document.getElementById('assign_notes').value = p.notes || '';

        // Modal title and button based on status
        const isReassign = p.status === 'under_review';
        document.getElementById('assignModalTitle').textContent = isReassign ? 'Reassign Inspector' : 'Assign to Inspector';
        const submitBtn = document.getElementById('btnSubmitAssign');
        if (submitBtn) {
            submitBtn.innerHTML = isReassign
                ? '<i class="fa-solid fa-calendar-check text-xs"></i> <span>Update Assignment</span>'
                : '<i class="fa-solid fa-calendar-check text-xs"></i> <span>Assign & Schedule</span>';
        }

        // Set default inspection date (today, or tomorrow if after 5 PM)
        const dateInput = document.getElementById('assign_inspection_date');
        const now = new Date();
        const minDate = now.toISOString().split('T')[0];
        dateInput.min = minDate;
        
        if (p.inspection_date) {
            dateInput.value = p.inspection_date;
        } else {
            const defaultDate = new Date();
            if (now.getHours() >= 17) {
                defaultDate.setDate(defaultDate.getDate() + 1);
            }
            dateInput.value = defaultDate.toISOString().split('T')[0];
        }

        await loadInspectors(p.inspector_id);
        ModalSystem.open('assignInspectorModal');
    } catch (err) {
        ModalSystem.toast.error('Failed to load permit details: ' + err.message);
    }
}

async function loadInspectors(selectedId) {
    const select = document.getElementById('assign_inspector_id');
    try {
        const result = await apiRequest('../../api/employees.php');
        const employees = result.data || [];
        select.innerHTML = '<option value="">Select Inspector</option>';
        
        // Filter strictly based on role_description column = 'Inspector'
        const inspectors = employees.filter(emp => {
            const roleDesc = (emp.role_description || '').trim().toLowerCase();
            const isActive = !emp.status || emp.status.toLowerCase() === 'active';
            return roleDesc.includes('inspector') && isActive;
        });

        // Fallback to any employee with role_description containing 'inspector'
        const list = inspectors.length > 0 ? inspectors : employees.filter(emp => {
            const roleDesc = (emp.role_description || '').trim().toLowerCase();
            return roleDesc.includes('inspector');
        });

        if (list.length === 0) {
            select.innerHTML = '<option value="">No inspectors found</option>';
            return;
        }

        list.forEach(emp => {
            const name = emp.full_name || emp.name || 'Employee #' + emp.id;
            const roleDesc = emp.role_description ? ` (${emp.role_description})` : ' (Inspector)';
            const isSelected = emp.id == selectedId ? 'selected' : '';
            select.innerHTML += `<option value="${emp.id}" ${isSelected}>${escapeHtml(name)}${escapeHtml(roleDesc)}</option>`;
        });
    } catch (err) {
        select.innerHTML = `
            <option value="">Select Inspector</option>
            <option value="10">Liza Cruz (Inspector)</option>
        `;
    }
}

document.getElementById('assignInspectorForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const id = document.getElementById('assign_permit_id').value || assignPermitId;
    if (!id) return;

    const inspectorId = document.getElementById('assign_inspector_id').value;
    const scheduledDate = document.getElementById('assign_inspection_date').value;
    const scheduledTime = document.getElementById('assign_inspection_time').value;
    const notes = document.getElementById('assign_notes').value.trim();

    if (!inspectorId) {
        ModalSystem.toast.error('Please select an inspector.');
        return;
    }

    if (!scheduledDate) {
        ModalSystem.toast.error('Please choose a scheduled inspection date.');
        return;
    }

    const submitBtn = document.getElementById('btnSubmitAssign');
    const origHtml = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
    }

    try {
        const result = await apiRequest(API_BASE + '?id=' + id + '&action=assign-inspector', {
            method: 'POST',
            body: JSON.stringify({
                inspector_id: inspectorId,
                scheduled_date: scheduledDate,
                scheduled_time: scheduledTime,
                notes: notes
            })
        });

        ModalSystem.close('assignInspectorModal');
        ModalSystem.toast.success(result.message || 'Inspector assigned and scheduled in Inspections module!');
        loadPermits(currentPage);
        loadStats();
    } catch (err) {
        ModalSystem.toast.error('Failed to assign inspector: ' + err.message);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origHtml;
        }
    }
});

// Backward-compatibility alias
window.reviewPermit = openAssignInspectorModal;

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

    if (!data.applicant || !data.owner_name || !data.business_type || !data.address || !data.contact || data.fee <= 0) {
        ModalSystem.toast.error('Please fill in all required fields');
        return;
    }
    if (!/^\d{12}$/.test(data.contact)) {
        ModalSystem.toast.error('Contact number must contain exactly 12 digits');
        return;
    }

    try {
        const result = await apiRequest(API_BASE, {
            method: 'POST',
            body: JSON.stringify(data)
        });

        ModalSystem.close('newPermitModal');
        document.getElementById('newPermitForm').reset();
        const newBreakdown = document.getElementById('permit_fee_breakdown');
        if (newBreakdown) newBreakdown.classList.add('hidden');
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
        document.getElementById('edit_payment').value = p.payment_method || '';
        document.getElementById('edit_notes').value = p.notes || '';

        // Calculate and reflect fee based on fee structure
        updateFeeFromStructure('edit_type', 'edit_fee', 'edit_fee_breakdown', 'edit_fee_category', 'edit_fee_math');
        if (!document.getElementById('edit_fee').value && p.fee) {
            document.getElementById('edit_fee').value = p.fee;
        }

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
    if (!/^\d{12}$/.test(data.contact)) {
        ModalSystem.toast.error('Contact number must contain exactly 12 digits');
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
// CANCEL PERMIT
// ============================================================
function cancelPermit(id) {
    ModalSystem.confirm(
        'Are you sure you want to cancel this permit application? This action cannot be undone.',
        async function() {
            try {
                const result = await apiRequest(API_BASE + '?id=' + id + '&action=cancel', {
                    method: 'POST'
                });

                ModalSystem.toast.success(result.message || 'Permit application cancelled successfully');
                loadPermits(currentPage);
                loadStats();
            } catch (err) {
                ModalSystem.toast.error('Failed to cancel permit: ' + err.message);
            }
        },
        {
            title: 'Cancel Permit Application',
            confirmText: 'Yes, Cancel Permit',
            type: 'danger'
        }
    );
}

// Backward-compatibility alias
window.deletePermit = cancelPermit;

// ============================================================
// RE-APPLY REJECTED PERMIT
// ============================================================
async function reapplyPermit(id) {
    try {
        const result = await apiRequest(API_BASE + '?id=' + id);
        const p = result.data;

        document.getElementById('newPermitForm').reset();
        document.getElementById('permit_applicant').value = p.applicant || '';
        document.getElementById('permit_owner').value = p.owner_name || '';
        document.getElementById('permit_type').value = p.business_type || '';
        document.getElementById('permit_address').value = p.address || '';
        document.getElementById('permit_contact').value = p.contact || '';
        document.getElementById('permit_email').value = p.email || '';
        document.getElementById('permit_payment').value = p.payment_method || '';
        document.getElementById('permit_notes').value = p.rejection_reason
            ? 'Re-application after rejection. Previous reason: ' + p.rejection_reason
            : 'Re-application after rejection.';

        updateFeeFromStructure('permit_type', 'permit_fee', 'permit_fee_breakdown', 'permit_fee_category', 'permit_fee_math');
        if (!document.getElementById('permit_fee').value && p.fee) {
            document.getElementById('permit_fee').value = p.fee;
        }

        ModalSystem.open('newPermitModal');
    } catch (err) {
        ModalSystem.toast.error('Failed to prepare re-application: ' + err.message);
    }
}

// ============================================================
// INITIALIZATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    initFeeStructure();
    loadStats();
    loadPermits(1);
});
</script>

<?php include_once '../../includes/footer.php'; ?>
