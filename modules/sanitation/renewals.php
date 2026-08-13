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
// 1. PHP BACKEND - With Dependency Injection
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('sanitation permits');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/Renewal.php';
require_once __DIR__ . '/../../app/Models/Permit.php';
require_once __DIR__ . '/../../includes/data-mask.php';
require_once __DIR__ . '/../../includes/toast.php';

// Constants
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 5;
const GRACE_PERIOD_DAYS = 30;
const LATE_FEE_PERCENTAGE = 25;
const INTEREST_RATE = 2;
const MAX_RENEWAL_FEE = '999999999999.00';

function normalizeRenewalDateFilter(mixed $value): ?string
{
    if (!is_string($value) || $value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

// Initialize models using your existing Database singleton
$renewalModel = new Renewal();
$permitModel = new Permit();

// Get statistics from model
$stats = $renewalModel->getStats();
$expiredPermits = count($renewalModel->getExpiredPermits());
$totalRenewalRevenue = $renewalModel->getTotalRevenue();

// Get expiring permits
$expiringSoon = $renewalModel->getExpiringSoon(GRACE_PERIOD_DAYS);
$expiringCount = count($expiringSoon);

// Pagination logic
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : DEFAULT_PAGE;
$limit = DEFAULT_LIMIT;
$offset = ($page - 1) * $limit;
$dateFrom = normalizeRenewalDateFilter($_GET['date_from'] ?? null);
$dateTo = normalizeRenewalDateFilter($_GET['date_to'] ?? null);
$renewalCriteria = [];
if ($dateFrom !== null || $dateTo !== null) {
    $renewalCriteria['date_applied'] = [];
    if ($dateFrom !== null) {
        $renewalCriteria['date_applied']['gte'] = $dateFrom;
    }
    if ($dateTo !== null) {
        $renewalCriteria['date_applied']['lte'] = $dateTo;
    }
}

// Get paginated renewals from model
$renewals = $renewalModel->search($renewalCriteria, $limit, $offset);
$totalRenewals = $stats['total'];
$totalPages = max(1, ceil($totalRenewals / $limit));

// Get permits for dropdown
$permits = $permitModel->all(['order' => 'created_at.desc']);

// Stats for display
$totalRenewalsCount = $stats['total'];
$pendingRenewals = $stats['pending'] + $stats['under_review'];
$approvedRenewals = $stats['approved'];
$rejectedRenewals = $stats['rejected'];

$title = 'Renewals';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Renewals</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage permit renewals, reminders & history</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('newRenewalModal')"
                class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-rotate text-xs"></i> New Renewal
            </button>
            <button onclick="sendAllReminders()"
                class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-bell text-xs"></i> Send Reminders
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Renewals -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-rotate text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalRenewalsCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Renewals</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🔄 All renewals</span>
                    <span class="text-[10px] text-slate-400"><?php echo $approvedRenewals; ?> approved</span>
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
                        <p class="text-2xl font-black text-amber-600"><?php echo $pendingRenewals; ?></p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400">Needs review</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Approved -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $approvedRenewals; ?></p>
                        <p class="text-xs font-medium text-slate-500">Approved</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Completed</span>
                    <span class="text-[10px] text-slate-400">Successfully renewed</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Expired Permits -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-calendar-xmark text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $expiredPermits; ?></p>
                        <p class="text-xs font-medium text-slate-500">Expired Permits</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Overdue</span>
                    <span class="text-[10px] text-slate-400">Immediate renewal</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Revenue -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-coins text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark">₱<?php echo number_format($totalRenewalRevenue, 0); ?></p>
                        <p class="text-xs font-medium text-slate-500">Revenue</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">💰 Collected</span>
                    <span class="text-[10px] text-slate-400">From renewals</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiring Soon Alert -->
    <?php if ($expiringCount > 0): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-clock text-amber-500 text-lg"></i>
                <span class="text-sm text-amber-700">
                    <span class="font-bold"><?php echo $expiringCount; ?></span> permit(s) expiring within <?php echo GRACE_PERIOD_DAYS; ?> days
                </span>
            </div>
            <button onclick="document.getElementById('quickFilter').value='expiring_soon'; filterRenewals();"
                class="text-xs font-semibold text-amber-700 hover:text-amber-900 underline">
                View expiring
            </button>
        </div>
    <?php endif; ?>

    <!-- Grace Period Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-4 flex items-center gap-3">
        <i class="fa-solid fa-info-circle text-blue-500 text-lg"></i>
        <div class="text-sm text-blue-700">
            <span class="font-bold">Grace Period:</span> <?php echo GRACE_PERIOD_DAYS; ?> days after expiry
            <span class="mx-2">•</span>
            <span class="font-bold">Late Fee:</span> <?php echo LATE_FEE_PERCENTAGE; ?>%
            <span class="mx-2">•</span>
            <span class="font-bold">Monthly Interest:</span> <?php echo INTEREST_RATE; ?>%
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                    id="searchRenewal"
                    placeholder="Search by permit ID, applicant, or renewal ID..."
                    class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="under_review">Under Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="quickFilter" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Renewals</option>
                    <option value="expiring_soon">Expiring Soon</option>
                    <option value="grace_period">In Grace Period</option>
                    <option value="completed">Completed</option>
                </select>
                <input type="date" id="filterDateFrom" value="<?php echo htmlspecialchars($dateFrom ?? ''); ?>"
                    aria-label="Date applied from"
                    class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <input type="date" id="filterDateTo" value="<?php echo htmlspecialchars($dateTo ?? ''); ?>"
                    aria-label="Date applied to"
                    class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                    class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Renewals Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Renewal ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Permit / Applicant</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Fee</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date Applied</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="renewalTableBody">
                    <?php foreach ($renewals as $renewal): ?>
                        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors renewal-row <?php echo $renewal['status'] === 'pending' ? 'bg-amber-50/30' : ''; ?>"
                            data-applicant="<?php echo htmlspecialchars(strtolower($renewal['applicant'] ?? '')); ?>"
                            data-status="<?php echo htmlspecialchars($renewal['status'] ?? 'pending'); ?>"
                            data-id="<?php echo htmlspecialchars($renewal['renewal_id'] ?? ''); ?>"
                            data-date-applied="<?php echo htmlspecialchars($renewal['date_applied'] ?? ''); ?>">
                            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo htmlspecialchars($renewal['renewal_id'] ?? ''); ?></td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm maskable"
                                        data-masked="<?php echo htmlspecialchars(maskName($renewal['applicant'] ?? '')); ?>"
                                        data-real="<?php echo htmlspecialchars($renewal['applicant'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($renewal['applicant'] ?? ''); ?>
                                    </p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($renewal['permit_id'] ?? ''); ?> • <?php echo htmlspecialchars($renewal['business_type'] ?? ''); ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-bold text-slate-800">₱<?php echo number_format((float)($renewal['renewal_fee'] ?? 0), 2); ?></span>
                                <span class="block text-[10px] text-slate-400">Prev: ₱<?php echo number_format((float)($renewal['current_fee'] ?? 0), 2); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <?php
                                $statusColors = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'under_review' => 'bg-blue-100 text-blue-700',
                                    'approved' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-rose-100 text-rose-700'
                                ];
                                $status = $renewal['status'] ?? 'pending';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$status] ?? $statusColors['pending']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($status)); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs"><?php echo date('M d, Y', strtotime($renewal['date_applied'] ?? 'now')); ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($renewal['payment_method'])): ?>
                                    <span class="text-xs text-slate-600"><?php echo htmlspecialchars($renewal['payment_method']); ?></span>
                                    <span class="block text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($renewal['payment_reference'] ?? ''); ?></span>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <button onclick="viewRenewal(<?php echo (int)($renewal['id'] ?? 0); ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                        <i class="fa-solid fa-eye text-sm"></i>
                                    </button>
                                    <?php if ($status === 'pending' || $status === 'under_review'): ?>
                                        <button onclick="updateRenewalStatus(<?php echo (int)($renewal['id'] ?? 0); ?>, 'approved')"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Approve">
                                            <i class="fa-solid fa-check text-sm"></i>
                                        </button>
                                        <button onclick="updateRenewalStatus(<?php echo (int)($renewal['id'] ?? 0); ?>, 'rejected')"
                                            class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Reject">
                                            <i class="fa-solid fa-times text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($status === 'approved'): ?>
                                        <button onclick="viewRenewalHistory(<?php echo (int)($renewal['id'] ?? 0); ?>)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="History">
                                            <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="sendReminder(<?php echo (int)($renewal['id'] ?? 0); ?>)"
                                        class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition" title="Send Reminder">
                                        <i class="fa-solid fa-bell text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state - shown when no renewals exist or no results match filters -->
        <div id="emptyState" class="flex-col items-center justify-center py-14 text-center <?php echo empty($renewals) ? 'flex' : 'hidden'; ?>">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <i class="fa-solid fa-rotate text-slate-300 text-2xl"></i>
            </div>
            <p class="text-base font-bold text-slate-700 mb-1">No Renewals Found</p>
            <p class="text-sm text-slate-500 mb-4">There are no renewal applications to display at this time.</p>
            <button onclick="openModal('newRenewalModal')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                <i class="fa-solid fa-plus mr-1.5"></i> Create Renewal
            </button>
        </div>

        <!-- Table wrapper - pagination is inside this container -->
        <div id="tableWrapper" class="<?php echo empty($renewals) ? 'hidden' : ''; ?>">
            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-slate-700"><?php echo $offset + 1; ?></span> to
                    <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalRenewals); ?></span> of
                    <span class="font-semibold text-slate-700"><?php echo $totalRenewals; ?></span> renewals
                </p>
                <div class="flex gap-1">
                    <button onclick="changePage(<?php echo $page - 1; ?>)"
                        class="px-3 py-1.5 rounded-lg text-sm <?php echo $page <= 1 ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"
                        <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-left text-xs"></i>
                    </button>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button onclick="changePage(<?php echo $i; ?>)"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium <?php echo $i === $page ? 'bg-brand-dark text-white' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    <button onclick="changePage(<?php echo $page + 1; ?>)"
                        class="px-3 py-1.5 rounded-lg text-sm <?php echo $page >= $totalPages ? 'bg-slate-100 text-slate-300 cursor-not-allowed' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-100'; ?>"
                        <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-chevron-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- NEW RENEWAL MODAL                                            -->
<!-- ============================================================ -->
<div id="newRenewalModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-rotate text-brand-medium"></i>
                New Renewal Application
            </h3>
            <button onclick="closeModal('newRenewalModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="newRenewalForm" class="p-6 space-y-4" onsubmit="saveRenewalApplication(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Permit to Renew</label>
                <select id="renew_permit" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Permit</option>
                    <?php foreach ($permits as $p): ?>
                        <option value="<?php echo (int)($p['id'] ?? 0); ?>" data-fee="<?php echo (float)($p['fee'] ?? 0); ?>">
                            <?php echo htmlspecialchars($p['permit_id'] ?? ''); ?> - <?php echo htmlspecialchars($p['applicant'] ?? ''); ?> (<?php echo htmlspecialchars($p['business_type'] ?? ''); ?>)
                            <?php if (($p['status'] ?? '') === 'expired'): ?> ⚠️ Expired<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Renewal Fee</label>
                <input type="number" id="renew_fee_amount" required min="0" max="<?php echo MAX_RENEWAL_FEE; ?>" step="0.01" inputmode="decimal"
                    title="Renewal fee cannot exceed <?php echo number_format(MAX_RENEWAL_FEE, 2); ?>"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Method</label>
                <select id="renew_payment_method" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Over-the-Counter">Over-the-Counter</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Reference (Auto-generated)</label>
                <input type="text" id="renew_payment_ref" readonly
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-500 cursor-not-allowed focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"
                    placeholder="Will be generated on submit">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="renew_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('newRenewalModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-rotate mr-1.5"></i> Submit Renewal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW RENEWAL MODAL                                           -->
<!-- ============================================================ -->
<div id="viewRenewalModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Renewal Details</h3>
            <button onclick="closeModal('viewRenewalModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="renewalDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- RENEWAL HISTORY MODAL                                        -->
<!-- ============================================================ -->
<div id="renewalHistoryModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Renewal History</h3>
            <button onclick="closeModal('renewalHistoryModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="renewalHistoryContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- REJECT RENEWAL MODAL                                         -->
<!-- ============================================================ -->
<div id="rejectRenewalModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                Reject Renewal
            </h3>
            <button onclick="closeModal('rejectRenewalModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="rejectRenewalForm" class="p-6 space-y-4">
            <div class="bg-rose-50 border border-rose-200 rounded-xl p-4">
                <p class="text-sm text-rose-700">
                    You are about to reject renewal <strong id="rejectRenewalId"></strong> for <strong id="rejectRenewalApplicant"></strong>.
                </p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Reason for Rejection <span class="text-rose-500">*</span></label>
                <textarea id="rejection_reason" rows="3" required
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-rose-500/40 focus:border-rose-500 outline-none"
                    placeholder="Please provide a reason for rejecting this renewal..."></textarea>
                <p class="text-xs text-slate-400 mt-1">This reason will be saved and visible to the applicant.</p>
            </div>
            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('rejectRenewalModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition text-sm font-semibold">
                    <i class="fa-solid fa-times mr-1.5"></i> Reject Renewal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const API_BASE = '<?php echo site_url('api/renewals.php'); ?>';

    // ============================================================
    // MODAL FUNCTIONS - Using ModalSystem
    // ============================================================
    function openModal(id) {
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.open(id);
        } else {
            // Fallback if ModalSystem not loaded
            var modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }
    }

    function closeModal(id) {
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.close(id);
        } else {
            // Fallback if ModalSystem not loaded
            var modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }
    }

    // ============================================================
    // AUTO-FILL FEE ON PERMIT SELECT
    // ============================================================
    document.getElementById('renew_permit').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const fee = selected.dataset.fee || 0;
        // Add 5% increase or keep same
        document.getElementById('renew_fee_amount').value = (parseFloat(fee) * 1.05).toFixed(2);
    });

    // ============================================================
    // FETCH RENEWAL DATA FROM API
    // ============================================================
    async function fetchRenewal(id) {
        try {
            const response = await fetch(API_BASE + '?id=' + id);
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch renewal');
            }
            return result.data;
        } catch (err) {
            console.error('Error fetching renewal:', err);
            return null;
        }
    }

    async function fetchRenewalHistory(permitId) {
        try {
            const response = await fetch(API_BASE + '?history=1&permit_id=' + encodeURIComponent(permitId));
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch history');
            }
            return result.data || [];
        } catch (err) {
            console.error('Error fetching history:', err);
            return [];
        }
    }

    // ============================================================
    // VIEW RENEWAL
    // ============================================================
    async function viewRenewal(id) {
        openModal('viewRenewalModal');
        document.getElementById('renewalDetailsContent').innerHTML = `
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        `;

        const r = await fetchRenewal(id);
        if (!r) {
            document.getElementById('renewalDetailsContent').innerHTML = `
                <div class="text-center py-10 text-rose-500">
                    <i class="fa-solid fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load renewal details</p>
                </div>
            `;
            return;
        }

        const statusColors = {
            pending: 'bg-amber-100 text-amber-700',
            under_review: 'bg-blue-100 text-blue-700',
            approved: 'bg-emerald-100 text-emerald-700',
            rejected: 'bg-rose-100 text-rose-700'
        };

        let docs = [];
        try {
            docs = typeof r.documents === 'string' ? JSON.parse(r.documents) : (r.documents || []);
        } catch (e) {
            docs = [];
        }

        const docsHtml = docs.length > 0 ?
            docs.map(d => `<span class="px-2 py-1 bg-slate-100 rounded text-xs text-slate-600">${escHtml(d)}</span>`).join('') :
            '<span class="text-xs text-slate-400">No documents</span>';

        // === REJECTION REASON BLOCK ADDED ===
        let rejectionHtml = '';
        if (r.status === 'rejected' && r.rejection_reason) {
            rejectionHtml = `
                <div class="bg-rose-50 rounded-xl p-4 border border-rose-200">
                    <h5 class="text-sm font-bold text-rose-700 mb-2">Rejection Reason</h5>
                    <p class="text-sm text-rose-800">${escHtml(r.rejection_reason)}</p>
                </div>
            `;
        }

        document.getElementById('renewalDetailsContent').innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0">
                        ${(r.applicant || '?').charAt(0)}
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">${escHtml(r.applicant || '')}</h4>
                        <p class="text-sm text-slate-500">${escHtml(r.renewal_id || '')} • ${escHtml(r.permit_id || '')}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[r.status] || statusColors.pending}">
                            ${(r.status || 'pending').replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><p class="text-xs text-slate-400 font-semibold">Business Type</p><p class="text-sm text-slate-800">${escHtml(r.business_type || '')}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Current Fee</p><p class="text-sm text-slate-800">₱${Number(r.current_fee || 0).toFixed(2)}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Renewal Fee</p><p class="text-sm font-bold text-brand-dark">₱${Number(r.renewal_fee || 0).toFixed(2)}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Date Applied</p><p class="text-sm text-slate-800">${r.date_applied ? new Date(r.date_applied).toLocaleDateString() : '—'}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Payment Method</p><p class="text-sm text-slate-800">${escHtml(r.payment_method || '—')}</p></div>
                    <div><p class="text-xs text-slate-400 font-semibold">Reference</p><p class="text-sm text-slate-800 font-mono">${escHtml(r.payment_reference || '—')}</p></div>
                    ${r.date_approved ? `<div><p class="text-xs text-slate-400 font-semibold">Date Approved</p><p class="text-sm text-slate-800">${new Date(r.date_approved).toLocaleDateString()}</p></div>` : ''}
                    ${r.new_expiry_date ? `<div><p class="text-xs text-slate-400 font-semibold">New Expiry</p><p class="text-sm font-bold text-emerald-600">${new Date(r.new_expiry_date).toLocaleDateString()}</p></div>` : ''}
                </div>
                ${rejectionHtml}
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h5 class="text-sm font-bold text-slate-700 mb-2">📎 Documents</h5>
                    <div class="flex flex-wrap gap-2">${docsHtml}</div>
                </div>
                ${r.notes ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${escHtml(r.notes)}</p></div>` : ''}
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('viewRenewalModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    ${r.status === 'pending' ? `<button onclick="closeModal('viewRenewalModal'); updateRenewalStatus(${r.id}, 'approved')" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Approve</button>` : ''}
                    <button onclick="sendReminder(${r.id})" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-semibold"><i class="fa-solid fa-bell mr-1.5"></i> Send Reminder</button>
                </div>
            </div>
        `;
    }

    // ============================================================
    // UPDATE RENEWAL STATUS (via API)
    // ============================================================
    async function updateRenewalStatus(id, status) {
        if (status === 'rejected') {
            openRejectModal(id);
            return;
        }

        // Use ModalSystem.confirm for approve
        if (typeof ModalSystem !== 'undefined') {
            ModalSystem.confirm('Are you sure you want to mark this renewal as APPROVED?', async function() {
                await submitStatusUpdate(id, 'approved');
            }, {
                title: 'Confirm Approval',
                type: 'info',
                confirmText: 'Yes, Approve',
                cancelText: 'Cancel'
            });
        } else {
            // Fallback
            if (!confirm('Mark this renewal as APPROVED?')) return;
            await submitStatusUpdate(id, 'approved');
        }
    }

    async function submitStatusUpdate(id, status) {
        try {
            // Map status to API action: 'approved' -> 'approve', 'rejected' -> 'reject'
            const action = status === 'approved' ? 'approve' : status;
            const response = await fetch(API_BASE + '/' + id + '/' + action, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to update status');
            }

            if (typeof toast !== 'undefined') {
                toast.success('Renewal ' + (result.data?.renewal_id || id) + ' ' + status + '!');
            }
            // Refresh the renewal list from the server
            await refreshRenewalList();
        } catch (err) {
            if (typeof toast !== 'undefined') {
                toast.error('Error: ' + err.message);
            }
        }
    }

    // ============================================================
    // REFRESH RENEWAL LIST FROM API
    // ============================================================
    async function refreshRenewalList() {
        try {
            const response = await fetch(API_BASE + '?page=1&limit=5');
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to refresh list');
            }

            const data = result.data;
            if (!Array.isArray(data)) {
                console.error('Unexpected data format', result);
                return;
            }

            // Rebuild table body
            const tbody = document.getElementById('renewalTableBody');
            tbody.innerHTML = '';

            data.forEach(renewal => {
                const unmasked = renewal.applicant || '';
                const masked = unmasked.split(' ').map(function(p) {
                    if (!p) return '';
                    return p.charAt(0) + '*'.repeat(Math.max(0, p.length - 1));
                }).join(' ');

                const row = document.createElement('tr');
                row.className = 'border-b border-slate-100 hover:bg-brand-light/40 transition-colors renewal-row ' + (renewal.status === 'pending' ? 'bg-amber-50/30' : '');
                row.dataset.applicant = unmasked.toLowerCase();
                row.dataset.status = renewal.status || 'pending';
                row.dataset.id = renewal.renewal_id || '';
                row.dataset.dateApplied = renewal.date_applied || '';
                row.innerHTML = `
                    <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${escHtml(renewal.renewal_id || '')}</td>
                    <td class="px-4 py-3">
                        <div>
                            <p class="font-semibold text-slate-800 text-sm maskable"
                               data-masked="${escHtml(masked)}"
                               data-real="${escHtml(unmasked)}">
                                ${escHtml(unmasked)}
                            </p>
                            <p class="text-xs text-slate-400">${escHtml(renewal.permit_id || '')} • ${escHtml(renewal.business_type || '')}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm font-bold text-slate-800">₱${Number(renewal.renewal_fee || 0).toFixed(2)}</span>
                        <span class="block text-[10px] text-slate-400">Prev: ₱${Number(renewal.current_fee || 0).toFixed(2)}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusClass(renewal.status)}">
                            ${(renewal.status || 'pending').replace('_', ' ').toUpperCase()}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs">${renewal.date_applied ? new Date(renewal.date_applied).toLocaleDateString() : '—'}</td>
                    <td class="px-4 py-3">
                        ${renewal.payment_method ? `<span class="text-xs text-slate-600">${escHtml(renewal.payment_method)}</span><span class="block text-[10px] text-slate-400 font-mono">${escHtml(renewal.payment_reference || '')}</span>` : '<span class="text-xs text-slate-400">—</span>'}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-center gap-1">
                            <button onclick="viewRenewal(${renewal.id})" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View"><i class="fa-solid fa-eye text-sm"></i></button>
                            ${(renewal.status === 'pending' || renewal.status === 'under_review') ? `<button onclick="updateRenewalStatus(${renewal.id}, 'approved')" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Approve"><i class="fa-solid fa-check text-sm"></i></button><button onclick="updateRenewalStatus(${renewal.id}, 'rejected')" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Reject"><i class="fa-solid fa-times text-sm"></i></button>` : ''}
                            ${renewal.status === 'approved' ? `<button onclick="viewRenewalHistory(${renewal.id})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="History"><i class="fa-solid fa-clock-rotate-left text-sm"></i></button>` : ''}
                            <button onclick="sendReminder(${renewal.id})" class="p-1.5 text-amber-500 hover:bg-amber-50 rounded-lg transition" title="Send Reminder"><i class="fa-solid fa-bell text-sm"></i></button>
                        </div>
                    </td>
                `;
                tbody.appendChild(row);
            });

            // Update empty state visibility
            const emptyState = document.getElementById('emptyState');
            const tableWrapper = document.getElementById('tableWrapper');
            if (data.length === 0) {
                emptyState.style.display = 'flex';
                if (tableWrapper) tableWrapper.style.display = 'none';
            } else {
                emptyState.style.display = 'none';
                if (tableWrapper) tableWrapper.style.display = '';
            }
        } catch (err) {
            console.error('Failed to refresh renewal list:', err);
            if (typeof toast !== 'undefined') {
                toast.error('Failed to refresh list: ' + err.message);
            }
        }
    }

    function getStatusClass(status) {
        const classes = {
            pending: 'bg-amber-100 text-amber-700',
            under_review: 'bg-blue-100 text-blue-700',
            approved: 'bg-emerald-100 text-emerald-700',
            rejected: 'bg-rose-100 text-rose-700'
        };
        return classes[status] || classes.pending;
    }

    // ============================================================
    // REJECT RENEWAL MODAL LOGIC
    // ============================================================
    let currentRejectId = null;

    async function openRejectModal(id) {
        currentRejectId = id;
        document.getElementById('rejectRenewalId').textContent = '#' + id;
        document.getElementById('rejection_reason').value = '';

        // Fetch renewal details to get applicant name
        const r = await fetchRenewal(id);
        if (r && r.applicant) {
            document.getElementById('rejectRenewalApplicant').textContent = r.applicant;
        } else {
            document.getElementById('rejectRenewalApplicant').textContent = 'Unknown';
        }

        openModal('rejectRenewalModal');
    }

    document.getElementById('rejectRenewalForm').addEventListener('submit', async function(event) {
        event.preventDefault();

        const reason = document.getElementById('rejection_reason').value.trim();
        if (!reason) {
            if (typeof toast !== 'undefined') {
                toast.warning('Please provide a reason for rejection');
            }
            return;
        }

        if (!currentRejectId) return;

        const submitBtn = event.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Rejecting...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(API_BASE + '/' + currentRejectId + '/reject', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    rejection_reason: reason
                })
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to reject renewal');
            }

            if (typeof toast !== 'undefined') {
                toast.success('Renewal rejected successfully');
            }
            closeModal('rejectRenewalModal');
            // Refresh the renewal list from the server
            await refreshRenewalList();
        } catch (err) {
            if (typeof toast !== 'undefined') {
                toast.error('Error: ' + err.message);
            }
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
            currentRejectId = null;
        }
    });

    // ============================================================
    // VIEW RENEWAL HISTORY (via API)
    // ============================================================
    async function viewRenewalHistory(id) {
        openModal('renewalHistoryModal');
        document.getElementById('renewalHistoryContent').innerHTML = `
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        `;

        const r = await fetchRenewal(id);
        if (!r) {
            document.getElementById('renewalHistoryContent').innerHTML = `
                <div class="text-center py-10 text-rose-500">
                    <i class="fa-solid fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load renewal details</p>
                </div>
            `;
            return;
        }

        const history = await fetchRenewalHistory(r.permit_id);

        const historyHtml = history.length > 0 ?
            history.map(h => `
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(h.permit_id || '')}</p>
                        <p class="text-xs text-slate-400">Renewed on ${h.renewal_date ? new Date(h.renewal_date).toLocaleDateString() : '—'}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-brand-dark">₱${Number(h.fee_paid || 0).toFixed(2)}</p>
                        <p class="text-xs text-slate-400">Expires: ${h.new_expiry ? new Date(h.new_expiry).toLocaleDateString() : '—'}</p>
                    </div>
                </div>
            `).join('') :
            '<p class="text-sm text-slate-400 text-center py-4">No renewal history found</p>';

        document.getElementById('renewalHistoryContent').innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(r.applicant || '')}</p>
                        <p class="text-xs text-slate-400">${escHtml(r.permit_id || '')}</p>
                    </div>
                    <span class="ml-auto text-xs font-semibold text-brand-dark">${history.length} renewal(s)</span>
                </div>
                <div class="space-y-2">${historyHtml}</div>
            </div>
        `;
    }

    // ============================================================
    // SAVE RENEWAL APPLICATION (via API)
    // ============================================================
    async function saveRenewalApplication(event) {
        event.preventDefault();

        const permitId = document.getElementById('renew_permit').value;
        const renewalFee = document.getElementById('renew_fee_amount').value;
        const paymentMethod = document.getElementById('renew_payment_method').value;
        const notes = document.getElementById('renew_notes').value;
        const renewalFeeValue = Number(renewalFee);

        if (!permitId) {
            if (typeof toast !== 'undefined') {
                toast.warning('Please select a permit');
            }
            return;
        }

        if (!/^\d{1,12}(\.\d{1,2})?$/.test(renewalFee.trim()) || !Number.isFinite(renewalFeeValue) || renewalFeeValue < 0 || renewalFeeValue > Number('<?php echo MAX_RENEWAL_FEE; ?>')) {
            if (typeof toast !== 'undefined') {
                toast.warning('Renewal fee must be between 0 and <?php echo number_format(MAX_RENEWAL_FEE, 2); ?>.');
            }
            document.getElementById('renew_fee_amount').focus();
            return;
        }

        const submitBtn = event.target.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Submitting...';
        submitBtn.disabled = true;

        try {
            const response = await fetch(API_BASE, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    permit_id: parseInt(permitId),
                    renewal_fee: renewalFeeValue,
                    payment_method: paymentMethod,
                    notes: notes || ''
                })
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to submit renewal');
            }

            if (typeof toast !== 'undefined') {
                toast.success('Renewal application submitted successfully!');
            }
            closeModal('newRenewalModal');
            event.target.reset();
            // Reload page to show new renewal
            setTimeout(() => window.location.reload(), 1000);
        } catch (err) {
            if (typeof toast !== 'undefined') {
                toast.error('Error: ' + err.message);
            }
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }

    // ============================================================
    // AUTO-REMINDERS
    // ============================================================
    function sendReminder(id) {
        if (typeof toast !== 'undefined') {
            toast.success('Reminder sent successfully!');
        }
    }

    function sendAllReminders() {
        if (typeof toast !== 'undefined') {
            toast.success('Sent reminders to all pending renewals!');
        }
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchRenewal').addEventListener('input', filterRenewals);
    document.getElementById('filterStatus').addEventListener('change', filterRenewals);
    document.getElementById('quickFilter').addEventListener('change', filterRenewals);
    document.getElementById('filterDateFrom').addEventListener('change', applyDateFilter);
    document.getElementById('filterDateTo').addEventListener('change', applyDateFilter);

    function filterRenewals() {
        const search = document.getElementById('searchRenewal').value.toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const quick = document.getElementById('quickFilter').value;
        let visibleCount = 0;

        document.querySelectorAll('.renewal-row').forEach(row => {
            const applicant = row.dataset.applicant;
            const rowStatus = row.dataset.status;
            const rowId = row.dataset.id.toLowerCase();
            const dateApplied = row.dataset.dateApplied;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;

            let matchesQuick = true;
            if (quick === 'expiring_soon') {
                matchesQuick = true; // Will be handled by server-side in real scenario
            } else if (quick === 'grace_period') {
                matchesQuick = rowStatus === 'pending' || rowStatus === 'under_review';
            } else if (quick === 'completed') {
                matchesQuick = rowStatus === 'approved';
            }

            const matchesSearch = applicant.includes(search) || rowId.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesDateFrom = !dateFrom || (dateApplied && dateApplied >= dateFrom);
            const matchesDateTo = !dateTo || (dateApplied && dateApplied <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesQuick && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        const tableWrapper = document.getElementById('tableWrapper');
        if (visibleCount === 0) {
            document.getElementById('emptyState').style.display = 'flex';
            if (tableWrapper) tableWrapper.style.display = 'none';
        } else {
            document.getElementById('emptyState').style.display = 'none';
            if (tableWrapper) tableWrapper.style.display = '';
        }
    }

    function resetFilters() {
        const url = new URL(window.location.href);
        if (url.searchParams.has('date_from') || url.searchParams.has('date_to')) {
            url.searchParams.delete('date_from');
            url.searchParams.delete('date_to');
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
            return;
        }
        document.getElementById('searchRenewal').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('quickFilter').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.renewal-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
        const tableWrapper = document.getElementById('tableWrapper');
        if (tableWrapper) tableWrapper.style.display = '';
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo $totalPages; ?>) return;
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }

    function applyDateFilter() {
        const url = new URL(window.location.href);
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;

        if (dateFrom) {
            url.searchParams.set('date_from', dateFrom);
        } else {
            url.searchParams.delete('date_from');
        }
        if (dateTo) {
            url.searchParams.set('date_to', dateTo);
        } else {
            url.searchParams.delete('date_to');
        }
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }


    // ============================================================
    // HTML ESCAPE HELPER
    // ============================================================
    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
</script>

<?php include_once '../../includes/footer.php'; ?>