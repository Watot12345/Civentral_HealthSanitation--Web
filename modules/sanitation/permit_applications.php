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
                    <option value="Water Refilling Station">Water Refilling Station</option>
                    <option value="Spa / Massage / Therapeutic Clinic">Spa / Massage / Therapeutic Clinic</option>
                    <option value="Medical / Dental Clinic / Hospital / Laboratory">Medical / Dental Clinic / Hospital / Laboratory</option>
                    <option value="Market Vendor / Supermarket">Market Vendor / Supermarket</option>
                    <option value="Hotel / Lodging / Condominium">Hotel / Lodging / Condominium</option>
                    <option value="Movie House">Movie House</option>
                    <option value="Funeral Parlor">Funeral Parlor</option>
                    <option value="Tiangge">Tiangge</option>
                    <option value="Department Store">Department Store</option>
                    <option value="Recreational Facility">Recreational Facility</option>
                    <option value="Pharmacy">Pharmacy</option>
                    <option value="Beauty Parlor / Salon / Barbershop">Beauty Parlor / Salon / Barbershop</option>
                    <option value="Facial / Skin Clinic">Facial / Skin Clinic</option>
                    <option value="Amusement Center">Amusement Center</option>
                    <option value="Construction Site">Construction Site</option>
                    <option value="Bank / Financial Institution">Bank / Financial Institution</option>
                    <option value="Industrial Establishment">Industrial Establishment</option>
                    <option value="Bakery">Bakery</option>
                    <option value="Retail Store">Retail Store</option>
                    <option value="Agricultural">Agricultural</option>
                    <option value="Office/Commercial">Office/Commercial</option>
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
                    <option value="Water Refilling Station">Water Refilling Station (WRS)</option>
                    <option value="Spa / Massage / Therapeutic Clinic">Spa / Massage / Therapeutic Clinic</option>
                    <option value="Medical / Dental Clinic / Hospital / Laboratory">Medical / Dental Clinic / Hospital / Laboratory</option>
                    <option value="Market Vendor / Supermarket">Market Vendor / Supermarket / Abattoir</option>
                    <option value="Hotel / Lodging / Condominium">Hotel / Lodging / Condominium</option>
                    <option value="Movie House">Movie House / Theater</option>
                    <option value="Funeral Parlor">Funeral Parlor</option>
                    <option value="Tiangge">Tiangge / Flea Market</option>
                    <option value="Department Store">Department Store / Mall</option>
                    <option value="Recreational Facility">Recreational Facility (Bowling, Pool)</option>
                    <option value="Pharmacy">Pharmacy / Drugstore</option>
                    <option value="Beauty Parlor / Salon / Barbershop">Beauty Parlor / Salon / Barbershop</option>
                    <option value="Facial / Skin Clinic">Facial / Skin Clinic</option>
                    <option value="Amusement Center">Amusement Center</option>
                    <option value="Construction Site">Construction Site</option>
                    <option value="Bank / Financial Institution">Bank / Financial Institution</option>
                    <option value="Industrial Establishment">Industrial Establishment</option>
                    <option value="Bakery">Bakery</option>
                    <option value="Retail Store">Retail Store</option>
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
                    <input type="text" id="permit_contact" required minlength="11" maxlength="12" pattern="^(09|639)[0-9]{9}$" inputmode="numeric" placeholder="09XXXXXXXXX or 639XXXXXXXXX" class="permit-contact w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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

                <!-- Dynamic Mandatory Category Requirements & Reference Record Container -->
                <div id="permit_requirements_container" class="hidden mt-3 p-3.5 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between mb-1.5 pb-1.5 border-b border-slate-200/60">
                        <span class="text-xs font-bold text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                            <i class="fa-solid fa-folder-open text-brand-medium"></i>
                            Mandatory Category Requirements & Document Records
                        </span>
                        <span id="permit_req_count" class="text-[10px] font-bold px-2 py-0.5 bg-brand-light text-brand-dark rounded-full border border-brand-border"></span>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-2">
                        Specify certificate ID/serial # or indicate physical photocopy on-file for easy tracking in Document Records:
                    </p>
                    <div id="permit_requirements_list" class="space-y-2 text-xs">
                        <!-- Rendered dynamically on business type select -->
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
<!-- PROCESS PAYMENT MODAL                                        -->
<!-- ============================================================ -->
<div id="processPaymentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-brand-medium"></i> Process Fee Payment & Record Revenue
            </h3>
            <button onclick="ModalSystem.close('processPaymentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="pay_permit_id">
            <div class="p-3.5 bg-brand-light/40 rounded-xl border border-brand-border space-y-1">
                <div class="flex items-center justify-between">
                    <span id="pay_permit_code" class="font-mono text-xs font-bold text-brand-dark">SAN-2026-001</span>
                    <span id="pay_business_type" class="text-xs font-semibold text-slate-600">Food Establishment</span>
                </div>
                <p id="pay_applicant" class="text-sm font-bold text-slate-800">Joshua Sierra</p>
                <div class="pt-2 border-t border-brand-border/60 flex items-center justify-between">
                    <span class="text-xs text-slate-500 font-semibold">Total Fee Due:</span>
                    <span id="pay_amount_display" class="text-lg font-black text-brand-dark">₱2,000.00</span>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Method</label>
                <select id="pay_method" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 outline-none font-semibold">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Over-the-Counter">Over-the-Counter</option>
                    <option value="Check">Check</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Official Receipt / Ref #</label>
                <input type="text" id="pay_reference" placeholder="e.g. OR-2026-88901 / GCash Ref" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm font-mono focus:ring-2 focus:ring-brand-medium/40 outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Notes</label>
                <textarea id="pay_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 outline-none" placeholder="Payment & fee collection notes..."></textarea>
            </div>
        </div>
        <div class="flex items-center justify-between gap-2 px-6 pb-6 pt-2 border-t border-slate-100">
            <button type="button" onclick="ModalSystem.close('processPaymentModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
            <button type="button" onclick="submitProcessPayment()" class="px-5 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
                <i class="fa-solid fa-circle-check"></i> Confirm Payment & Record Revenue
            </button>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- OFFICIAL RECEIPT MODAL & PRINTER INTEGRATION                -->
<!-- ============================================================ -->
<div id="receiptModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
        <!-- Receipt Header -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center border border-emerald-500/30">
                    <i class="fa-solid fa-receipt text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm tracking-wide text-white">Official Electronic Receipt</h3>
                    <p class="text-[11px] text-slate-300" id="receipt_or_no">OR-2026-000000</p>
                </div>
            </div>
            <button onclick="ModalSystem.close('receiptModal')" class="w-8 h-8 rounded-lg hover:bg-white/10 flex items-center justify-center text-slate-300 hover:text-white transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Receipt Content Body -->
        <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto bg-slate-50/50" id="receiptModalBody">
            <div class="bg-white p-5 rounded-xl border border-slate-200/80 shadow-sm space-y-4 font-sans text-slate-800" id="printableReceiptCard">
                <!-- City LGU Seal / Title Header -->
                <div class="text-center pb-3 border-b border-dashed border-slate-300">
                    <p class="text-[10px] uppercase tracking-widest text-slate-400 font-bold">Republic of the Philippines</p>
                    <h4 class="font-black text-sm text-slate-900 uppercase tracking-tight">City Health & Sanitation Department</h4>
                    <p class="text-[11px] text-brand-dark font-semibold">Civentral Sanitation Management Information System</p>
                    <div class="inline-block px-2.5 py-0.5 mt-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-extrabold uppercase">
                        ✓ Official Receipt - Paid
                    </div>
                </div>

                <!-- Receipt Fields -->
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">O.R. Number:</span>
                        <span id="rcpt_or_num" class="font-mono font-bold text-slate-900">OR-2026-XXXXX</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Date & Time:</span>
                        <span id="rcpt_datetime" class="font-semibold text-slate-800">Oct 02, 2026, 09:48 PM</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Permit Reference:</span>
                        <span id="rcpt_permit_code" class="font-mono font-bold text-brand-dark">SP-261002-XXXX</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Payor / Applicant:</span>
                        <span id="rcpt_applicant" class="font-bold text-slate-900 text-right">Joshua Garcia</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Business Type:</span>
                        <span id="rcpt_business_type" class="font-semibold text-slate-800">Food Establishment</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-500">Payment Method:</span>
                        <span id="rcpt_payment_method" class="font-bold text-emerald-700">Cash</span>
                    </div>
                </div>

                <!-- Fee Line Items -->
                <div class="pt-2 border-t border-dashed border-slate-300">
                    <div class="flex justify-between text-xs py-1">
                        <span class="text-slate-600 font-medium">Sanitation Permit & Inspection Fee</span>
                        <span id="rcpt_fee_item" class="font-bold text-slate-900">₱1,500.00</span>
                    </div>
                </div>

                <!-- Total Paid -->
                <div class="p-3 bg-slate-900 text-white rounded-xl flex items-center justify-between mt-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Total Paid</span>
                    <span id="rcpt_total_paid" class="text-lg font-black text-emerald-400">₱1,500.00</span>
                </div>

                <!-- Cashier / Signature -->
                <div class="pt-4 text-center border-t border-dashed border-slate-300 space-y-1">
                    <p class="text-[10px] text-slate-400 uppercase font-semibold">Authorized Collecting Officer</p>
                    <p class="text-xs font-bold text-slate-800">City Treasury / Sanitation Cashier</p>
                    <p class="text-[9px] text-slate-400">System Generated Official Receipt</p>
                </div>
            </div>
        </div>

        <!-- Receipt Action Buttons -->
        <div class="p-4 bg-slate-100/80 border-t border-slate-200 flex items-center justify-between gap-2">
            <button onclick="ModalSystem.close('receiptModal')" class="px-3.5 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition text-xs font-semibold">
                Close
            </button>
            <div class="flex items-center gap-2">
                <button id="rcpt_btn_assign" onclick="triggerAssignFromReceipt()" class="hidden px-3.5 py-2 bg-brand-dark text-white rounded-xl hover:bg-brand-medium transition text-xs font-bold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-user-check"></i> Assign Inspector
                </button>
                <button onclick="downloadReceiptFile()" class="px-3.5 py-2 bg-slate-800 text-white rounded-xl hover:bg-slate-900 transition text-xs font-bold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-download"></i> 📥 Download Receipt
                </button>
                <button onclick="printCurrentReceipt()" class="px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition text-xs font-bold flex items-center gap-1.5 shadow-md hover:shadow-lg">
                    <i class="fa-solid fa-print"></i> 🖨️ Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- IN-PAGE PRINTABLE CONTAINER & COLOR PRINT STYLING           -->
<!-- ============================================================ -->
<style id="sanitationPrintStyle">
@media print {
    @page {
        size: portrait;
        margin: 10mm;
    }
    body > *:not(#sanitationReceiptPrintContainer) {
        display: none !important;
    }
    #sanitationReceiptPrintContainer {
        display: flex !important;
        justify-content: center !important;
        align-items: flex-start !important;
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        padding: 20px 0 !important;
        margin: 0 !important;
        background: #ffffff !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}
</style>

<div id="sanitationReceiptPrintContainer" class="hidden">
    <div style="font-family: system-ui, -apple-system, sans-serif; max-width: 420px; margin: 0 auto; padding: 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; color: #1e293b; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
        <div style="text-align: center; padding-bottom: 16px; border-bottom: 2px dashed #cbd5e1; margin-bottom: 16px;">
            <p style="font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; font-weight: 700; margin: 0;">Republic of the Philippines</p>
            <h3 style="font-size: 15px; font-weight: 900; color: #0f172a; text-transform: uppercase; margin: 4px 0 2px 0;">City Health & Sanitation Department</h3>
            <p style="font-size: 11px; color: #0284c7; font-weight: 700; margin: 0;">Civentral Sanitation Management Information System</p>
            <div style="display: inline-block; padding: 4px 12px; margin-top: 10px; background-color: #ecfdf5 !important; color: #047857 !important; border: 1px solid #a7f3d0; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase;">
                ✓ Official Receipt - Paid
            </div>
        </div>

        <div style="margin-bottom: 16px; font-size: 12px;">
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">O.R. Number:</span>
                <span style="font-family: monospace; font-weight: 700; color: #0f172a;" id="print_or_num">OR-2026-XXXXX</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Date & Time:</span>
                <span style="font-weight: 600; color: #334155;" id="print_datetime">Oct 02, 2026, 09:48 PM</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Permit Reference:</span>
                <span style="font-family: monospace; font-weight: 700; color: #0284c7;" id="print_permit_code">SP-261002-XXXX</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Payor / Applicant:</span>
                <span style="font-weight: 700; color: #0f172a;" id="print_applicant">Joshua Garcia</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Business Type:</span>
                <span style="font-weight: 600; color: #334155;" id="print_business_type">Food Establishment</span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9;">
                <span style="color: #64748b;">Payment Method:</span>
                <span style="font-weight: 700; color: #047857;" id="print_payment_method">Cash</span>
            </div>
        </div>

        <div style="padding-top: 10px; border-top: 2px dashed #cbd5e1; margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 12px; padding: 4px 0;">
                <span style="color: #475569; font-weight: 500;">Sanitation Permit & Inspection Fee</span>
                <span style="font-weight: 700; color: #0f172a;" id="print_fee_item">₱1,500.00</span>
            </div>
        </div>

        <div style="padding: 14px 18px; background-color: #0f172a !important; color: #ffffff !important; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
            <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1;">Total Paid</span>
            <span style="font-size: 18px; font-weight: 900; color: #34d399 !important;" id="print_total_paid">₱1,500.00</span>
        </div>

        <div style="margin-top: 20px; padding-top: 16px; text-align: center; border-top: 2px dashed #cbd5e1;">
            <div style="font-family: monospace; font-size: 16px; letter-spacing: 4px; margin-bottom: 4px; color: #334155;">||| | |||| | ||||| |||</div>
            <p style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 600; margin: 0 0 12px 0;">Authorized Collecting Officer</p>
            <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0;">City Treasury / Sanitation Cashier</p>
            <p style="font-size: 9px; color: #94a3b8; margin-top: 6px;">This document serves as an Official Electronic Receipt for Sanitation Permit fees.</p>
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
                    <option value="Water Refilling Station">Water Refilling Station (WRS)</option>
                    <option value="Spa / Massage / Therapeutic Clinic">Spa / Massage / Therapeutic Clinic</option>
                    <option value="Medical / Dental Clinic / Hospital / Laboratory">Medical / Dental Clinic / Hospital / Laboratory</option>
                    <option value="Market Vendor / Supermarket">Market Vendor / Supermarket / Abattoir</option>
                    <option value="Hotel / Lodging / Condominium">Hotel / Lodging / Condominium</option>
                    <option value="Movie House">Movie House / Theater</option>
                    <option value="Funeral Parlor">Funeral Parlor</option>
                    <option value="Tiangge">Tiangge / Flea Market</option>
                    <option value="Department Store">Department Store / Mall</option>
                    <option value="Recreational Facility">Recreational Facility (Bowling, Pool)</option>
                    <option value="Pharmacy">Pharmacy / Drugstore</option>
                    <option value="Beauty Parlor / Salon / Barbershop">Beauty Parlor / Salon / Barbershop</option>
                    <option value="Facial / Skin Clinic">Facial / Skin Clinic</option>
                    <option value="Amusement Center">Amusement Center</option>
                    <option value="Construction Site">Construction Site</option>
                    <option value="Bank / Financial Institution">Bank / Financial Institution</option>
                    <option value="Industrial Establishment">Industrial Establishment</option>
                    <option value="Bakery">Bakery</option>
                    <option value="Retail Store">Retail Store</option>
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
                    <input type="text" id="edit_contact" required minlength="11" maxlength="12" pattern="^(09|639)[0-9]{9}$" inputmode="numeric" placeholder="09XXXXXXXXX or 639XXXXXXXXX" class="permit-contact w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
// OFFICIAL SANITATION REQUIREMENTS MATRIX (By Business Category)
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

const FEE_STRUCTURE = {
    'Food Establishment': { base_fee: 1500, inspection_fee: 500, total: 2000 },
    'Water Refilling Station': { base_fee: 1600, inspection_fee: 500, total: 2100 },
    'Spa / Massage / Therapeutic Clinic': { base_fee: 1800, inspection_fee: 500, total: 2300 },
    'Medical / Dental Clinic / Hospital / Laboratory': { base_fee: 2500, inspection_fee: 700, total: 3200 },
    'Market Vendor / Supermarket': { base_fee: 1200, inspection_fee: 400, total: 1600 },
    'Hotel / Lodging / Condominium': { base_fee: 3000, inspection_fee: 800, total: 3800 },
    'Movie House': { base_fee: 2000, inspection_fee: 500, total: 2500 },
    'Funeral Parlor': { base_fee: 2200, inspection_fee: 600, total: 2800 },
    'Tiangge': { base_fee: 700, inspection_fee: 300, total: 1000 },
    'Department Store': { base_fee: 2500, inspection_fee: 600, total: 3100 },
    'Recreational Facility': { base_fee: 2000, inspection_fee: 600, total: 2600 },
    'Pharmacy': { base_fee: 1800, inspection_fee: 500, total: 2300 },
    'Beauty Parlor / Salon / Barbershop': { base_fee: 1000, inspection_fee: 400, total: 1400 },
    'Facial / Skin Clinic': { base_fee: 1800, inspection_fee: 500, total: 2300 },
    'Amusement Center': { base_fee: 1500, inspection_fee: 500, total: 2000 },
    'Construction Site': { base_fee: 3500, inspection_fee: 1000, total: 4500 },
    'Bank / Financial Institution': { base_fee: 2000, inspection_fee: 500, total: 2500 },
    'Industrial Establishment': { base_fee: 4000, inspection_fee: 1000, total: 5000 },
    'Market Vendor': { base_fee: 800, inspection_fee: 300, total: 1100 },
    'Bakery': { base_fee: 1200, inspection_fee: 400, total: 1600 },
    'Retail Store': { base_fee: 1000, inspection_fee: 350, total: 1350 },
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
    const feeData = FEE_STRUCTURE[selectedType] || FEE_STRUCTURE['Office/Commercial'] || { base_fee: 1500, inspection_fee: 500, total: 2000 };

    if (selectedType) {
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

    renderRequirementsChecklist(selectedType);
}

function renderRequirementsChecklist(businessType) {
    const reqContainer = document.getElementById('permit_requirements_container');
    const reqList = document.getElementById('permit_requirements_list');
    const reqCount = document.getElementById('permit_req_count');

    if (!reqContainer || !reqList) return;

    if (!businessType) {
        reqContainer.classList.add('hidden');
        return;
    }

    const requirements = SANITATION_REQUIREMENTS_MATRIX[businessType] || SANITATION_REQUIREMENTS_MATRIX['Office/Commercial'] || [
        'Certificate of Water Potability (a, b, c)',
        'Contract for Insect & Vermin Control from Accredited Operator',
        'Health Certificates for Employees'
    ];

    reqList.innerHTML = requirements.map((req, idx) => `
        <div id="req_card_${idx}" class="p-2.5 bg-white rounded-lg border border-slate-200 shadow-2xs space-y-1.5 transition-all">
            <div class="flex items-center justify-between font-semibold text-slate-800 text-xs">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox"
                           id="req_doc_check_${idx}"
                           class="req-doc-checkbox w-4 h-4 rounded text-brand-dark focus:ring-brand-medium border-slate-300 accent-brand-dark cursor-pointer"
                           checked
                           onchange="toggleReqItemState(${idx})">
                    <span id="req_doc_title_${idx}" class="text-slate-800">${escapeHtml(req)}</span>
                </label>
                <span id="req_status_badge_${idx}" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">☑ Submitted</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-0.5">
                <div>
                    <input type="text"
                           name="req_doc_id_${idx}"
                           id="req_doc_id_${idx}"
                           data-req-title="${escapeHtml(req)}"
                           placeholder="Cert / Serial Ref ID (e.g. WTR-2026-9012)"
                           class="req-doc-ref-input w-full px-2.5 py-1.5 border border-slate-200 rounded-md text-xs focus:ring-1 focus:ring-brand-medium outline-none font-mono">
                </div>
                <div>
                    <select name="req_doc_type_${idx}"
                            id="req_doc_type_${idx}"
                            onchange="toggleDropZone(${idx})"
                            class="req-doc-type-select w-full px-2.5 py-1.5 border border-slate-200 rounded-md text-xs bg-slate-50 focus:ring-1 focus:ring-brand-medium outline-none">
                        <option value="Physical Copy (Photocopy On-File)">📄 Physical Copy (Photocopy On-File)</option>
                        <option value="Digital Upload">💻 Digital Upload (Drag & Drop File)</option>
                        <option value="To Follow / Pending">⏳ To Follow / Pending</option>
                    </select>
                </div>
            </div>

            <!-- Drag & Drop File Upload Box (Triggered on Digital Upload) -->
            <div id="dropzone_container_${idx}" class="hidden mt-2 p-3 border-2 border-dashed border-brand-medium/40 rounded-xl bg-brand-light/20 hover:bg-brand-light/50 transition text-center cursor-pointer relative group">
                <input type="file"
                       id="req_doc_file_${idx}"
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                       accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                       onchange="handleFileDropSelect(${idx}, this.files)">
                <div id="dropzone_prompt_${idx}" class="space-y-1">
                    <i class="fa-solid fa-cloud-arrow-up text-brand-medium text-lg group-hover:scale-110 transition-transform"></i>
                    <p class="text-xs font-semibold text-slate-700">Drag & Drop digital file here or <span class="text-brand-dark underline font-bold">Browse File</span></p>
                    <p class="text-[10px] text-slate-400">Supports PDF, PNG, JPG, DOCX (Max 10MB)</p>
                </div>
                <div id="dropzone_file_preview_${idx}" class="hidden flex items-center justify-between bg-white p-2 rounded-lg border border-brand-border text-xs text-brand-dark font-semibold">
                    <span class="flex items-center gap-1.5 truncate max-w-[220px]" id="dropzone_file_name_${idx}">
                        <i class="fa-solid fa-file-check text-brand-medium"></i> document.pdf
                    </span>
                    <button type="button" onclick="clearDropzoneFile(${idx}, event)" class="text-rose-500 hover:text-rose-700 text-xs px-1.5 py-0.5 rounded hover:bg-rose-50 z-20">
                        <i class="fa-solid fa-xmark"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    `).join('');

    if (reqCount) {
        reqCount.textContent = `${requirements.length} Required Documents`;
    }

    reqContainer.classList.remove('hidden');
}

function toggleReqItemState(idx) {
    const chk = document.getElementById(`req_doc_check_${idx}`);
    const card = document.getElementById(`req_card_${idx}`);
    const badge = document.getElementById(`req_status_badge_${idx}`);
    const title = document.getElementById(`req_doc_title_${idx}`);
    const typeSelect = document.getElementById(`req_doc_type_${idx}`);
    const dropzone = document.getElementById(`dropzone_container_${idx}`);

    if (!chk || !card) return;

    if (chk.checked) {
        card.classList.remove('opacity-60', 'bg-slate-50');
        card.classList.add('bg-white');
        if (title) title.classList.remove('line-through', 'text-slate-400');
        if (badge) {
            badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700';
            badge.textContent = '☑ Submitted';
        }
        if (typeSelect && typeSelect.value === 'To Follow / Pending') {
            typeSelect.value = 'Physical Copy (Photocopy On-File)';
        }
    } else {
        card.classList.add('opacity-60', 'bg-slate-50');
        card.classList.remove('bg-white');
        if (title) title.classList.add('line-through', 'text-slate-400');
        if (badge) {
            badge.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700';
            badge.textContent = '☐ Pending / To Follow';
        }
        if (typeSelect) {
            typeSelect.value = 'To Follow / Pending';
        }
        if (dropzone) {
            dropzone.classList.add('hidden');
        }
    }
}

function toggleDropZone(idx) {
    const typeSelect = document.getElementById(`req_doc_type_${idx}`);
    const dropzone = document.getElementById(`dropzone_container_${idx}`);
    const chk = document.getElementById(`req_doc_check_${idx}`);
    if (!typeSelect || !dropzone) return;

    if (typeSelect.value === 'Digital Upload') {
        dropzone.classList.remove('hidden');
        if (chk && !chk.checked) {
            chk.checked = true;
            toggleReqItemState(idx);
        }
    } else {
        dropzone.classList.add('hidden');
        if (typeSelect.value === 'To Follow / Pending' && chk && chk.checked) {
            chk.checked = false;
            toggleReqItemState(idx);
        }
    }
}

function handleFileDropSelect(idx, files) {
    if (!files || !files.length) return;
    const file = files[0];
    const prompt = document.getElementById(`dropzone_prompt_${idx}`);
    const preview = document.getElementById(`dropzone_file_preview_${idx}`);
    const fileNameSpan = document.getElementById(`dropzone_file_name_${idx}`);
    const refInput = document.getElementById(`req_doc_id_${idx}`);

    if (prompt && preview && fileNameSpan) {
        prompt.classList.add('hidden');
        preview.classList.remove('hidden');
        preview.classList.add('flex');
        fileNameSpan.innerHTML = `<i class="fa-solid fa-file-circle-check text-brand-medium mr-1"></i> ${escapeHtml(file.name)}`;
    }

    if (refInput && !refInput.value.trim()) {
        refInput.value = 'DIGITAL: ' + file.name;
    }
}

function clearDropzoneFile(idx, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    const fileInput = document.getElementById(`req_doc_file_${idx}`);
    const prompt = document.getElementById(`dropzone_prompt_${idx}`);
    const preview = document.getElementById(`dropzone_file_preview_${idx}`);
    const refInput = document.getElementById(`req_doc_id_${idx}`);

    if (fileInput) fileInput.value = '';
    if (prompt && preview) {
        prompt.classList.remove('hidden');
        preview.classList.add('hidden');
        preview.classList.remove('flex');
    }
    if (refInput && refInput.value.startsWith('DIGITAL: ')) {
        refInput.value = '';
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
    const csrfToken = window.CrudAjax ? window.CrudAjax.getCsrfToken() : '';
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
        ...(options.headers || {})
    };
    try {
        const response = await fetch(url, {
            ...options,
            headers
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
    window._lastPermitFetchTime = Date.now();
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
            ? '<span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold text-emerald-600 bg-emerald-50 border border-emerald-200/60">✓ Paid</span>'
            : '<span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-200/60">✕ Unpaid</span>';

        const maskedApplicant = maskName(p.applicant);
        const maskedOwner = maskName(p.owner_name);

        return `
        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors permit-row"
            data-id="${p.id}">
            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${escapeHtml(p.permit_id || '')}</td>
            <td class="px-4 py-3">
                <div>
                    <p class="font-semibold text-slate-800 text-sm maskable" data-real="${escapeHtml(p.applicant || '')}" data-masked="${escapeHtml(maskedApplicant)}">${escapeHtml(p.applicant || '')}</p>
                    <p class="text-xs text-slate-400 maskable" data-real="${escapeHtml(p.owner_name || '')}" data-masked="${escapeHtml(maskedOwner)}">${escapeHtml(p.owner_name || '')}</p>
                </div>
            </td>
            <td class="px-4 py-3 text-slate-600 text-xs">${escapeHtml(p.business_type || '')}</td>
            <td class="px-4 py-3 text-slate-600 text-xs">${escapeHtml(p.address || '')}</td>
            <td class="px-4 py-3">
                <span class="text-xs font-semibold text-slate-700">₱${Number(p.fee).toLocaleString('en-US', { minimumFractionDigits: 2 })}</span>
                ${paidBadge}
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${statusColor}">
                    ${statusLabel}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-500 text-xs">${escapeHtml(dateApplied)}</td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    <!-- View Details -->
                    <button onclick="viewPermit(${p.id})"
                            class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details">
                        <i class="fa-solid fa-eye text-sm"></i>
                    </button>

                    <!-- Process Payment / Pay Fee (if unpaid) OR View Receipt (if paid) -->
                    ${!p.paid ? `
                        <button onclick="openProcessPaymentModal(${p.id})"
                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition font-bold" title="Pay Fee / Process Payment">
                            <i class="fa-solid fa-credit-card text-sm"></i>
                        </button>
                    ` : `
                        <button onclick="openReceiptModal(${p.id}, null, false)"
                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="View / Print Official Receipt">
                            <i class="fa-solid fa-receipt text-sm"></i>
                        </button>
                    `}

                    <!-- Assign / Reassign Inspector (Only shown once payment is completed) -->
                    ${p.paid && (p.status === 'pending' || p.status === 'under_review') ? `
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
                        <h4 class="text-lg font-bold text-slate-900 maskable" data-real="${escapeHtml(p.applicant || '')}" data-masked="${escapeHtml(maskedApplicant)}">${escapeHtml(p.applicant || '')}</h4>
                        <p class="text-sm text-slate-500">${escapeHtml(p.permit_id || '')} • ${escapeHtml(p.business_type || '')}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColor}">
                            ${statusLabel.toUpperCase()}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Owner</p>
                        <p class="text-sm text-slate-800 maskable" data-real="${escapeHtml(p.owner_name || '')}" data-masked="${escapeHtml(maskedOwner)}">${escapeHtml(p.owner_name || '')}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Contact</p>
                        <p class="text-sm text-slate-800 maskable" data-real="${escapeHtml(p.contact || '')}" data-masked="${escapeHtml(maskedContact)}">${escapeHtml(p.contact || '')}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Email</p>
                        <p class="text-sm text-slate-800">${escapeHtml(p.email || 'N/A')}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Address</p>
                        <p class="text-sm text-slate-800">${escapeHtml(p.address || '')}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Fee</p>
                        <p class="text-sm text-slate-800 font-bold">₱${Number(p.fee).toLocaleString('en-US', { minimumFractionDigits: 2 })}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Payment</p>
                        <p class="mt-0.5">${p.paid ? `<span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200/60">✓ Paid via ${escapeHtml(p.payment_method || 'N/A')}</span>` : `<span class="px-2 py-0.5 rounded text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200/60">✕ Unpaid</span>`}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Date Applied</p>
                        <p class="text-sm text-slate-800">${escapeHtml(dateApplied)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Date Approved</p>
                        <p class="text-sm text-slate-800">${escapeHtml(dateApproved)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Expiry Date</p>
                        <p class="text-sm text-slate-800">${escapeHtml(expiryDate)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-semibold">Inspector</p>
                        <p class="text-sm text-slate-800">${escapeHtml(p.inspector_id ? 'Inspector #' + p.inspector_id : 'Not assigned')}</p>
                    </div>
                    ${rejectionHtml}
                </div>
                ${(() => {
                    const categoryReqs = SANITATION_REQUIREMENTS_MATRIX[p.business_type] || SANITATION_REQUIREMENTS_MATRIX['Office/Commercial'] || [];
                    if (!categoryReqs.length) return '';
                    return `
                        <div class="col-span-2 bg-slate-50 rounded-xl p-4 border border-slate-200 mt-2 space-y-2">
                            <div class="flex items-center justify-between">
                                <h5 class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                                    <i class="fa-solid fa-folder-open text-brand-medium"></i>
                                    Document Requirements & Physical Copy Tracking
                                </h5>
                                <a href="documents.php?q=${encodeURIComponent(p.applicant || '')}" class="text-[11px] font-bold text-brand-medium hover:text-brand-dark flex items-center gap-1">
                                    <i class="fa-solid fa-search"></i> Find in Documents
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-slate-100/80 text-slate-500 font-bold uppercase text-[10px]">
                                        <tr>
                                            <th class="px-2.5 py-1.5">Requirement Item</th>
                                            <th class="px-2.5 py-1.5">Submission Mode</th>
                                            <th class="px-2.5 py-1.5">Document / Cert Ref ID</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        ${categoryReqs.map((r, idx) => `
                                            <tr>
                                                <td class="px-2.5 py-2 font-medium text-slate-800">${escapeHtml(r)}</td>
                                                <td class="px-2.5 py-2 text-slate-600">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-brand-light/70 text-brand-dark border border-brand-border/60">
                                                        📄 Physical / Recorded On-File
                                                    </span>
                                                </td>
                                                <td class="px-2.5 py-2 font-mono text-slate-700 font-bold">
                                                    <a href="documents.php?q=${encodeURIComponent(r)}" class="hover:underline text-brand-dark">
                                                        ${escapeHtml(p.permit_id || 'SAN')}-DOC-${idx + 1}
                                                    </a>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                })()}
                ${p.notes ? `
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5>
                        <p class="text-sm text-slate-800">${escapeHtml(p.notes)}</p>
                    </div>
                ` : ''}
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="ModalSystem.close('viewPermitModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    ${!p.paid ? `
                        <button onclick="ModalSystem.close('viewPermitModal'); openProcessPaymentModal(${p.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-credit-card text-xs"></i> Process Fee Payment
                        </button>
                    ` : `
                        <button onclick="ModalSystem.close('viewPermitModal'); openReceiptModal(${p.id}, null, false)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-receipt text-xs"></i> 🖨️ View / Print Official Receipt
                        </button>
                    `}
                    ${p.paid && p.status === 'pending' ? `
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
// PROCESS PAYMENT & FEE COLLECTION
// ============================================================
let autoAssignAfterPayment = false;

function openProcessPaymentModal(permitId, autoAssign = false) {
    const p = permitsCache[permitId];
    if (!p) return;

    autoAssignAfterPayment = autoAssign;
    document.getElementById('pay_permit_id').value = p.id;
    document.getElementById('pay_permit_code').textContent = p.permit_id || 'SAN';
    document.getElementById('pay_applicant').textContent = p.applicant || 'N/A';
    document.getElementById('pay_business_type').textContent = p.business_type || 'General';
    const fee = parseFloat(p.fee || 0);
    document.getElementById('pay_amount_display').textContent = '₱' + fee.toLocaleString('en-US', { minimumFractionDigits: 2 });
    document.getElementById('pay_reference').value = `OR-${new Date().getFullYear()}-${Math.floor(10000 + Math.random() * 90000)}`;

    ModalSystem.open('processPaymentModal');
}

async function submitProcessPayment() {
    const permitId = document.getElementById('pay_permit_id').value;
    if (!permitId) return;

    const p = permitsCache[permitId];
    const amount = p ? parseFloat(p.fee || 0) : 1000;
    const method = document.getElementById('pay_method').value;
    const ref = document.getElementById('pay_reference').value || ('OR-' + new Date().getFullYear() + '-' + Math.floor(100000 + Math.random() * 900000));
    const notes = document.getElementById('pay_notes').value;

    try {
        // Record payment in Payments API
        let paymentRecorded = false;
        try {
            await apiRequest('../../api/payments.php', {
                method: 'POST',
                body: JSON.stringify({
                    permit_id: parseInt(permitId, 10),
                    amount: amount > 0 ? amount : 1000,
                    method: method.toLowerCase().replace(/[^a-z]/g, '_'),
                    reference_number: ref,
                    notes: notes || 'Sanitation permit fee payment'
                })
            });
            paymentRecorded = true;
        } catch (e) {
            console.warn('Payment API notice:', e);
        }

        // Only fallback to direct permit update if payments API did not handle it
        if (!paymentRecorded) {
            await apiRequest(API_BASE + '?id=' + permitId + '&action=update', {
                method: 'POST',
                body: JSON.stringify({
                    paid: 1,
                    payment_method: method,
                    reference_number: ref
                })
            });
        }

        // Update local cache
        if (permitsCache[permitId]) {
            permitsCache[permitId].paid = 1;
            permitsCache[permitId].payment_method = method;
            permitsCache[permitId].reference_number = ref;
        }

        ModalSystem.close('processPaymentModal');
        ModalSystem.toast.success('Payment recorded successfully! Generating Official Receipt...');
        window._lastLocalPermitAction = Date.now();
        loadStats();
        loadPermits(currentPage);

        if (typeof window.broadcastSanitationChange === 'function') {
            window.broadcastSanitationChange('permits', { action: 'paid', id: permitId });
        }

        // Open official receipt modal and trigger printer integration
        setTimeout(() => {
            openReceiptModal(permitId, ref, true);
        }, 250);
    } catch (err) {
        ModalSystem.toast.error('Failed to process payment: ' + err.message);
    }
}

// ============================================================
// OFFICIAL RECEIPT & PRINTER INTEGRATION
// ============================================================
let currentReceiptPermitId = null;
let currentReceiptData = null;

function openReceiptModal(permitId, customOR = null, autoPrint = true) {
    const p = permitsCache[permitId];
    if (!p) return;

    currentReceiptPermitId = permitId;
    const orNum = customOR || p.reference_number || `OR-${new Date().getFullYear()}-${Math.floor(100000 + Math.random() * 900000)}`;
    const nowStr = new Date().toLocaleString('en-US', { dateStyle: 'medium', timeStyle: 'short' });
    const feeStr = '₱' + Number(p.fee || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
    const methodStr = p.payment_method || 'Cash';

    currentReceiptData = {
        orNum,
        nowStr,
        permitCode: p.permit_id || 'SAN',
        applicant: p.applicant || 'N/A',
        businessType: p.business_type || 'General',
        feeStr,
        methodStr
    };

    document.getElementById('receipt_or_no').textContent = orNum;
    document.getElementById('rcpt_or_num').textContent = orNum;
    document.getElementById('rcpt_datetime').textContent = nowStr;
    document.getElementById('rcpt_permit_code').textContent = p.permit_id || 'SAN';
    document.getElementById('rcpt_applicant').textContent = p.applicant || 'N/A';
    document.getElementById('rcpt_business_type').textContent = p.business_type || 'General';
    document.getElementById('rcpt_payment_method').textContent = methodStr;
    document.getElementById('rcpt_fee_item').textContent = feeStr;
    document.getElementById('rcpt_total_paid').textContent = feeStr;

    const assignBtn = document.getElementById('rcpt_btn_assign');
    if (autoAssignAfterPayment) {
        assignBtn.classList.remove('hidden');
    } else {
        assignBtn.classList.add('hidden');
    }

    ModalSystem.open('receiptModal');

    if (autoPrint) {
        setTimeout(() => {
            printCurrentReceipt();
        }, 400);
    }
}

function triggerAssignFromReceipt() {
    ModalSystem.close('receiptModal');
    if (currentReceiptPermitId) {
        openAssignInspectorModal(currentReceiptPermitId);
    }
}

function printCurrentReceipt() {
    if (!currentReceiptData) return;
    const d = currentReceiptData;

    // Populate in-page printable container
    document.getElementById('print_or_num').textContent = d.orNum;
    document.getElementById('print_datetime').textContent = d.nowStr;
    document.getElementById('print_permit_code').textContent = d.permitCode;
    document.getElementById('print_applicant').textContent = d.applicant;
    document.getElementById('print_business_type').textContent = d.businessType;
    document.getElementById('print_payment_method').textContent = d.methodStr;
    document.getElementById('print_fee_item').textContent = d.feeStr;
    document.getElementById('print_total_paid').textContent = d.feeStr;

    // Try popup window first with exact UI/UX colors, fallback to in-page window.print() if popup is blocked
    try {
        const printWindow = window.open('', '_blank', 'width=480,height=700');
        if (!printWindow) {
            window.print();
            return;
        }

        const htmlContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Official Receipt - ${d.orNum}</title>
                <style>
                    @page { size: portrait; margin: 10mm; }
                    * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; color-adjust: exact !important; box-sizing: border-box; }
                    html, body {
                        width: 100%;
                        margin: 0;
                        padding: 0;
                        background: #ffffff;
                        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                        display: flex;
                        justify-content: center;
                        align-items: flex-start;
                    }
                    .receipt-wrapper {
                        width: 100%;
                        max-width: 440px;
                        margin: 10px auto;
                        padding: 24px;
                        background: #ffffff;
                        border: 1px solid #e2e8f0;
                        border-radius: 16px;
                        color: #1e293b;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                    }
                    .header { text-align: center; padding-bottom: 16px; border-bottom: 2px dashed #cbd5e1; margin-bottom: 16px; }
                    .badge { display: inline-block; padding: 4px 12px; margin-top: 8px; background-color: #ecfdf5 !important; color: #047857 !important; border: 1px solid #a7f3d0; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
                    .line-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
                    .total-box { padding: 14px 18px; background-color: #0f172a !important; color: #ffffff !important; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-top: 14px; }
                    .total-price { font-size: 18px; font-weight: 900; color: #34d399 !important; }
                    .footer { margin-top: 20px; padding-top: 16px; text-align: center; border-top: 2px dashed #cbd5e1; }
                    .barcode { font-family: monospace; font-size: 16px; letter-spacing: 4px; margin-bottom: 4px; color: #334155; }
                </style>
            </head>
            <body>
                <div class="receipt-wrapper">
                    <div class="header">
                        <p style="font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; font-weight: 700; margin: 0;">Republic of the Philippines</p>
                        <h3 style="font-size: 15px; font-weight: 900; color: #0f172a; text-transform: uppercase; margin: 4px 0 2px 0;">City Health & Sanitation Department</h3>
                        <p style="font-size: 11px; color: #0284c7; font-weight: 700; margin: 0;">Civentral Sanitation MIS - Official Receipt</p>
                        <div class="badge">✓ Official Receipt - Paid</div>
                    </div>

                    <div class="line-item"><span style="color: #64748b;">O.R. Number:</span><span style="font-family: monospace; font-weight: 700; color: #0f172a;">${d.orNum}</span></div>
                    <div class="line-item"><span style="color: #64748b;">Date & Time:</span><span style="font-weight: 600; color: #334155;">${d.nowStr}</span></div>
                    <div class="line-item"><span style="color: #64748b;">Permit Reference:</span><span style="font-family: monospace; font-weight: 700; color: #0284c7;">${d.permitCode}</span></div>
                    <div class="line-item"><span style="color: #64748b;">Payor / Applicant:</span><span style="font-weight: 700; color: #0f172a;">${d.applicant}</span></div>
                    <div class="line-item"><span style="color: #64748b;">Business Type:</span><span style="font-weight: 600; color: #334155;">${d.businessType}</span></div>
                    <div class="line-item"><span style="color: #64748b;">Payment Method:</span><span style="font-weight: 700; color: #047857;">${d.methodStr}</span></div>

                    <div style="padding-top: 10px; border-top: 2px dashed #cbd5e1; margin-top: 8px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; padding: 4px 0;">
                            <span style="color: #475569; font-weight: 500;">Sanitation Permit & Inspection Fee</span>
                            <span style="font-weight: 700; color: #0f172a;">${d.feeStr}</span>
                        </div>
                    </div>

                    <div class="total-box">
                        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1;">Total Paid</span>
                        <span class="total-price">${d.feeStr}</span>
                    </div>

                    <div class="footer">
                        <div class="barcode">||| | |||| | ||||| |||</div>
                        <p style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 600; margin: 0 0 12px 0;">Authorized Collecting Officer</p>
                        <p style="font-size: 12px; font-weight: 700; color: #1e293b; margin: 0;">City Treasury / Sanitation Cashier</p>
                        <p style="font-size: 9px; color: #94a3b8; margin-top: 6px;">This document serves as an Official Electronic Receipt for Sanitation Permit fees.</p>
                    </div>
                </div>

                <script>
                    window.onload = function() {
                        window.print();
                    };
                <\/script>
            </body>
            </html>
        `;

        printWindow.document.open();
        printWindow.document.write(htmlContent);
        printWindow.document.close();
    } catch (err) {
        window.print();
    }
}

function downloadReceiptFile() {
    if (!currentReceiptData) return;
    const d = currentReceiptData;

    const htmlContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Official Receipt - ${d.orNum}</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 440px; margin: 20px auto; padding: 24px; border: 1px solid #cbd5e1; border-radius: 16px; color: #1e293b; background: #ffffff; }
        .header { text-align: center; border-bottom: 2px dashed #cbd5e1; padding-bottom: 14px; margin-bottom: 14px; }
        .badge { display: inline-block; padding: 4px 12px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .line-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
        .total-box { padding: 14px 18px; background: #0f172a; color: #ffffff; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-top: 14px; }
        .total-price { font-size: 18px; font-weight: 900; color: #34d399; }
        .footer { margin-top: 20px; padding-top: 14px; text-align: center; border-top: 2px dashed #cbd5e1; font-size: 10px; color: #94a3b8; }
        .barcode { font-family: monospace; font-size: 16px; letter-spacing: 4px; color: #334155; margin-bottom: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <p style="font-size: 10px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; font-weight: 700; margin: 0;">REPUBLIC OF THE PHILIPPINES</p>
        <h3 style="font-size: 15px; font-weight: 900; color: #0f172a; text-transform: uppercase; margin: 4px 0 2px 0;">CITY HEALTH & SANITATION DEPT</h3>
        <p style="font-size: 11px; color: #0284c7; font-weight: 700; margin: 0;">Civentral Sanitation MIS - Official Receipt</p>
        <div class="badge">✓ Official Receipt - Paid</div>
    </div>
    <div class="line-item"><span style="color: #64748b;">O.R. Number:</span><strong style="font-family: monospace;">${d.orNum}</strong></div>
    <div class="line-item"><span style="color: #64748b;">Date & Time:</span><span>${d.nowStr}</span></div>
    <div class="line-item"><span style="color: #64748b;">Permit Reference:</span><strong style="color: #0284c7; font-family: monospace;">${d.permitCode}</strong></div>
    <div class="line-item"><span style="color: #64748b;">Payor / Applicant:</span><strong>${d.applicant}</strong></div>
    <div class="line-item"><span style="color: #64748b;">Business Type:</span><span>${d.businessType}</span></div>
    <div class="line-item"><span style="color: #64748b;">Payment Method:</span><strong style="color: #047857;">${d.methodStr}</strong></div>
    <div style="padding-top: 10px; border-top: 2px dashed #cbd5e1; margin-top: 8px;">
        <div class="line-item" style="border: none;"><span style="color: #475569;">Sanitation Permit & Inspection Fee</span><strong>${d.feeStr}</strong></div>
    </div>
    <div class="total">
        <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #cbd5e1;">TOTAL PAID</span>
        <span class="total-price">${d.feeStr}</span>
    </div>
    <div class="footer">
        <div class="barcode">||| | |||| | ||||| |||</div>
        <p style="font-weight: 700; color: #1e293b; margin: 4px 0 0 0;">Authorized Collecting Officer - City Treasury Cashier</p>
        <p style="margin-top: 4px;">This document serves as an Official Electronic Receipt for Sanitation Permit fees.</p>
    </div>
</body>
</html>`;

    const blob = new Blob([htmlContent], { type: 'text/html;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Official_Receipt_${d.orNum}.html`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    ModalSystem.toast.success(`Receipt downloaded to your Downloads folder: Official_Receipt_${d.orNum}.html`);
}

// ============================================================
// ASSIGN TO INSPECTOR (with Payment Verification)
// ============================================================
let assignPermitId = null;

function extractCleanInspectorNotes(notesStr) {
    if (!notesStr) return '';
    if (notesStr.includes('📋 [DOCUMENT REQUIREMENTS RECORD]')) {
        const parts = notesStr.split('📋 [DOCUMENT REQUIREMENTS RECORD]');
        let afterPart = parts[1] || '';
        const cleanLines = afterPart.split('\n').filter(l => {
            const trimmed = l.trim();
            return trimmed.length > 0 && !trimmed.startsWith('•') && !trimmed.startsWith('[SUBMITTED]') && !trimmed.startsWith('[PENDING]');
        });
        return cleanLines.join('\n').trim();
    }
    return notesStr.trim();
}

let cachedInspectorsList = null;
let isFetchingInspectors = false;

async function fetchInspectorsList() {
    if (cachedInspectorsList && cachedInspectorsList.length > 0) return cachedInspectorsList;

    try {
        const stored = sessionStorage.getItem('sanitation_inspectors_list');
        if (stored) {
            const parsed = JSON.parse(stored);
            if (Array.isArray(parsed) && parsed.length > 0) {
                cachedInspectorsList = parsed;
                return cachedInspectorsList;
            }
        }
    } catch (e) {}

    if (isFetchingInspectors) return [];
    isFetchingInspectors = true;
    try {
        const result = await apiRequest('../../api/employees.php');
        const employees = result.data || [];
        const inspectors = employees.filter(emp => {
            const roleDesc = (emp.role_description || '').trim().toLowerCase();
            const isActive = !emp.status || emp.status.toLowerCase() === 'active';
            return roleDesc.includes('inspector') && isActive;
        });
        cachedInspectorsList = inspectors.length > 0 ? inspectors : employees.filter(emp => {
            const roleDesc = (emp.role_description || '').trim().toLowerCase();
            return roleDesc.includes('inspector');
        });
        try {
            sessionStorage.setItem('sanitation_inspectors_list', JSON.stringify(cachedInspectorsList));
        } catch (e) {}
    } catch (err) {
        console.warn('Failed to fetch inspectors, using fallback:', err);
        cachedInspectorsList = [{ id: 10, full_name: 'Liza Cruz', role_description: 'Inspector' }];
    } finally {
        isFetchingInspectors = false;
    }
    return cachedInspectorsList;
}

function renderInspectorDropdown(selectedId) {
    const select = document.getElementById('assign_inspector_id');
    if (!select) return;

    if (!cachedInspectorsList) {
        select.innerHTML = '<option value="">⏳ Loading inspectors...</option>';
        return;
    }

    if (cachedInspectorsList.length === 0) {
        select.innerHTML = '<option value="">No inspectors found</option>';
        return;
    }

    let html = '<option value="">Select Inspector</option>';
    cachedInspectorsList.forEach(emp => {
        const name = emp.full_name || emp.name || 'Employee #' + emp.id;
        const roleDesc = emp.role_description ? ` (${emp.role_description})` : ' (Inspector)';
        const isSelected = emp.id == selectedId ? 'selected' : '';
        html += `<option value="${emp.id}" ${isSelected}>${escapeHtml(name)}${escapeHtml(roleDesc)}</option>`;
    });
    select.innerHTML = html;
}

async function openAssignInspectorModal(id, bypassPaymentCheck = false) {
    assignPermitId = id;
    
    // 1. Fast retrieval from memory cache if available, else fetch
    let p = permitsCache[id];
    if (!p) {
        try {
            const result = await apiRequest(API_BASE + '?id=' + id);
            p = result.data;
            permitsCache[p.id] = p;
        } catch (err) {
            ModalSystem.toast.error('Failed to load permit details: ' + err.message);
            return;
        }
    }

    // 2. Unpaid check
    if (!p.paid) {
        const feeStr = '₱' + Number(p.fee || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });
        ModalSystem.confirm(
            `Permit #${p.permit_id} for ${p.applicant} is currently UNPAID (Fee: ${feeStr}). Sanitation workflow requires completing fee payment first before assigning an inspector.`,
            function() {
                openProcessPaymentModal(p.id, true);
            },
            {
                title: '💳 Payment Required Before Inspector Assignment',
                confirmText: '💳 Process Payment Now',
                cancelText: 'Cancel',
                type: 'warning'
            }
        );
        return;
    }

    // 3. Immediately populate permit fields
    document.getElementById('assign_permit_id').value = p.id;
    document.getElementById('assignApplicant').textContent = p.applicant || 'N/A';
    document.getElementById('assignPermitCode').textContent = p.permit_id || 'N/A';
    document.getElementById('assignBusinessType').textContent = p.business_type || 'General';
    document.getElementById('assignAddress').textContent = p.address ? '📍 ' + p.address : '';
    document.getElementById('assign_notes').value = extractCleanInspectorNotes(p.notes);

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

    // 4. Render inspector dropdown from cache immediately (0ms)
    renderInspectorDropdown(p.inspector_id);

    // 5. Open modal INSTANTLY
    ModalSystem.open('assignInspectorModal');

    // 6. If inspectors not cached yet, fetch asynchronously in background and populate
    if (!cachedInspectorsList) {
        fetchInspectorsList().then(() => {
            renderInspectorDropdown(p.inspector_id);
        });
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
        window._lastLocalPermitAction = Date.now();
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

    const docRefInputs = document.querySelectorAll('.req-doc-ref-input');
    const docRecords = [];
    docRefInputs.forEach((input, idx) => {
        const title = input.getAttribute('data-req-title') || '';
        const val = input.value.trim();
        const chk = document.getElementById(`req_doc_check_${idx}`);
        const isChecked = chk ? chk.checked : true;
        const typeSelect = document.getElementById(`req_doc_type_${idx}`);
        const submissionType = typeSelect ? typeSelect.value : (isChecked ? 'Physical Copy (Photocopy On-File)' : 'To Follow / Pending');
        const fileInput = document.getElementById(`req_doc_file_${idx}`);
        const uploadedFile = fileInput && fileInput.files && fileInput.files.length ? fileInput.files[0].name : null;

        if (title) {
            docRecords.push({
                title: title,
                is_submitted: isChecked,
                ref_id: val || (uploadedFile ? 'DIGITAL: ' + uploadedFile : (isChecked ? 'ON-FILE' : 'NONE')),
                submission_type: uploadedFile ? `Digital Upload (${uploadedFile})` : submissionType
            });
        }
    });

    let finalNotes = data.notes || '';
    if (docRecords.length > 0) {
        const reqSummary = '📋 [DOCUMENT REQUIREMENTS RECORD]\n' + docRecords.map(r => `• [${r.is_submitted ? 'SUBMITTED' : 'PENDING'}] ${r.title}: Ref# ${r.ref_id} (${r.submission_type})`).join('\n');
        finalNotes = finalNotes ? `${reqSummary}\n\n${finalNotes}` : reqSummary;
    }
    data.notes = finalNotes;

    if (!data.applicant || !data.owner_name || !data.business_type || !data.address || !data.contact || data.fee <= 0) {
        ModalSystem.toast.error('Please fill in all required fields');
        return;
    }
    if (!/^(09\d{9}|639\d{9})$/.test(data.contact)) {
        ModalSystem.toast.error('Contact number must be a valid Philippine mobile number (e.g. 09171234567 or 639171234567)');
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
        window._lastLocalPermitAction = Date.now();
        loadPermits(1);
        loadStats();
        if (typeof window.broadcastSanitationChange === 'function') {
            window.broadcastSanitationChange('permits', { action: 'created', id: result.data?.id });
        }
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
    if (!/^(09\d{9}|639\d{9})$/.test(data.contact)) {
        ModalSystem.toast.error('Contact number must be a valid Philippine mobile number (e.g. 09171234567 or 639171234567)');
        return;
    }

    try {
        const result = await apiRequest(API_BASE + '?id=' + id + '&action=update', {
            method: 'POST',
            body: JSON.stringify(data)
        });

        ModalSystem.close('editPermitModal');
        ModalSystem.toast.success(result.message || 'Permit updated successfully!');
        window._lastLocalPermitAction = Date.now();
        loadPermits(currentPage);
        loadStats();
        if (typeof window.broadcastSanitationChange === 'function') {
            window.broadcastSanitationChange('permits', { action: 'updated', id: id });
        }
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
                window._lastLocalPermitAction = Date.now();
                loadPermits(currentPage);
                loadStats();
                if (typeof window.broadcastSanitationChange === 'function') {
                    window.broadcastSanitationChange('permits', { action: 'cancelled', id: id });
                }
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
// REALTIME SYNCHRONIZATION (SUPABASE CDC & BROADCAST HUB)
// ============================================================
function isPermitAppModalOpen() {
    return ['newPermitModal', 'editPermitModal', 'viewPermitModal', 'assignInspectorModal'].some(id => {
        const el = document.getElementById(id);
        return el && !el.classList.contains('hidden');
    });
}

function setupRealtimePermitAppSync() {
    // Debounced and deduplicated refresh to prevent storm of parallel requests
    const debouncedRefresh = debounce(() => {
        // If this tab performed an action within the last 1500ms, skip duplicate broadcast reaction
        if (window._lastLocalPermitAction && (Date.now() - window._lastLocalPermitAction < 1500)) {
            return;
        }
        loadStats();
        if (!isPermitAppModalOpen()) {
            loadPermits(currentPage);
        }
    }, 350);

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
        // Only refresh if tab is visible, modal is closed, and cached data is older than 45 seconds
        if (!document.hidden && !isPermitAppModalOpen()) {
            const lastFetch = window._lastPermitFetchTime || 0;
            if (Date.now() - lastFetch > 45000) {
                loadStats();
                loadPermits(currentPage);
            }
        }
    });

    // 60s background heartbeat sync (throttled to avoid unnecessary CPU/network wakeups)
    setInterval(() => {
        if (!document.hidden && !isPermitAppModalOpen()) {
            const lastFetch = window._lastPermitFetchTime || 0;
            if (Date.now() - lastFetch > 45000) {
                loadStats();
                loadPermits(currentPage);
            }
        }
    }, 60000);
}

// ============================================================
// INITIALIZATION
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadPermits(1);
    setupRealtimePermitAppSync();

    // Defer non-critical inspector prefetching until after table renders
    if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
            fetchInspectorsList();
        });
    } else {
        setTimeout(fetchInspectorsList, 1200);
    }
});
</script>

<?php include_once '../../includes/footer.php'; ?>
