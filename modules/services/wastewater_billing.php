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
// 1. PHP BACKEND - Fetch Data
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('wastewater services');

// Sample Fee Structure
$feeStructure = [
    ['category' => 'Desludging (Residential)', 'base_fee' => 1200.00, 'per_unit' => null, 'description' => 'Standard residential desludging service'],
    ['category' => 'Desludging (Commercial)', 'base_fee' => 2000.00, 'per_unit' => null, 'description' => 'Commercial establishment desludging'],
    ['category' => 'Septic Tank Inspection', 'base_fee' => 800.00, 'per_unit' => null, 'description' => 'Complete septic tank inspection'],
    ['category' => 'Septic Tank Maintenance', 'base_fee' => 1500.00, 'per_unit' => null, 'description' => 'Regular maintenance service'],
    ['category' => 'Installation (New Tank)', 'base_fee' => 5000.00, 'per_unit' => null, 'description' => 'New septic tank installation'],
    ['category' => 'Emergency Service', 'base_fee' => 2500.00, 'per_unit' => null, 'description' => 'Emergency call-out service'],
    ['category' => 'Pipe Inspection', 'base_fee' => 600.00, 'per_unit' => null, 'description' => 'CCTV pipe inspection'],
    ['category' => 'Wastewater Treatment', 'base_fee' => 3000.00, 'per_unit' => null, 'description' => 'Wastewater treatment service'],
];

// Sample Invoices/Billing
$invoices = [
    [
        'id' => 1,
        'invoice_id' => 'INV-001',
        'client_name' => 'Pedro Garcia',
        'tank_id' => 'ST-001',
        'service_type' => 'Desludging (Residential)',
        'amount' => 1200.00,
        'tax' => 72.00,
        'total_amount' => 1272.00,
        'status' => 'paid',
        'payment_method' => 'GCash',
        'payment_reference' => 'GCH-20260720-001',
        'invoice_date' => '2026-07-20',
        'due_date' => '2026-08-04',
        'paid_at' => '2026-07-21 10:30:00',
        'notes' => 'Regular desludging service',
        'items' => [
            ['description' => 'Desludging Service', 'quantity' => 1, 'unit_price' => 1200.00, 'total' => 1200.00]
        ]
    ],
    [
        'id' => 2,
        'invoice_id' => 'INV-002',
        'client_name' => 'Carlos Lim',
        'tank_id' => 'ST-003',
        'service_type' => 'Emergency Service',
        'amount' => 2500.00,
        'tax' => 150.00,
        'total_amount' => 2650.00,
        'status' => 'pending',
        'payment_method' => null,
        'payment_reference' => null,
        'invoice_date' => '2026-07-18',
        'due_date' => '2026-08-01',
        'paid_at' => null,
        'notes' => 'Emergency maintenance - cracked tank',
        'items' => [
            ['description' => 'Emergency Service Call', 'quantity' => 1, 'unit_price' => 1000.00, 'total' => 1000.00],
            ['description' => 'Cracked Tank Repair', 'quantity' => 1, 'unit_price' => 1500.00, 'total' => 1500.00],
        ]
    ],
    [
        'id' => 3,
        'invoice_id' => 'INV-003',
        'client_name' => 'Elena Torres',
        'tank_id' => 'ST-004',
        'service_type' => 'Installation (New Tank)',
        'amount' => 5000.00,
        'tax' => 300.00,
        'total_amount' => 5300.00,
        'status' => 'paid',
        'payment_method' => 'Bank Transfer',
        'payment_reference' => 'BTR-20260715-001',
        'invoice_date' => '2026-07-15',
        'due_date' => '2026-07-29',
        'paid_at' => '2026-07-16 14:20:00',
        'notes' => 'New tank installation',
        'items' => [
            ['description' => 'New Septic Tank Installation', 'quantity' => 1, 'unit_price' => 5000.00, 'total' => 5000.00],
        ]
    ],
    [
        'id' => 4,
        'invoice_id' => 'INV-004',
        'client_name' => 'Rosa Mendoza',
        'tank_id' => 'ST-002',
        'service_type' => 'Septic Tank Maintenance',
        'amount' => 1500.00,
        'tax' => 90.00,
        'total_amount' => 1590.00,
        'status' => 'overdue',
        'payment_method' => null,
        'payment_reference' => null,
        'invoice_date' => '2026-07-10',
        'due_date' => '2026-07-24',
        'paid_at' => null,
        'notes' => 'Regular maintenance service',
        'items' => [
            ['description' => 'Maintenance Service', 'quantity' => 1, 'unit_price' => 1500.00, 'total' => 1500.00],
        ]
    ],
    [
        'id' => 5,
        'invoice_id' => 'INV-005',
        'client_name' => 'Ramon Garcia',
        'tank_id' => 'ST-005',
        'service_type' => 'Desludging (Residential)',
        'amount' => 1200.00,
        'tax' => 72.00,
        'total_amount' => 1272.00,
        'status' => 'paid',
        'payment_method' => 'Cash',
        'payment_reference' => 'CSH-20260722-001',
        'invoice_date' => '2026-07-22',
        'due_date' => '2026-08-05',
        'paid_at' => '2026-07-22 09:15:00',
        'notes' => 'Regular desludging',
        'items' => [
            ['description' => 'Desludging Service', 'quantity' => 1, 'unit_price' => 1200.00, 'total' => 1200.00],
        ]
    ],
    [
        'id' => 6,
        'invoice_id' => 'INV-006',
        'client_name' => 'Miguel Reyes',
        'tank_id' => 'ST-006',
        'service_type' => 'Wastewater Treatment',
        'amount' => 3000.00,
        'tax' => 180.00,
        'total_amount' => 3180.00,
        'status' => 'pending',
        'payment_method' => null,
        'payment_reference' => null,
        'invoice_date' => '2026-07-25',
        'due_date' => '2026-08-08',
        'paid_at' => null,
        'notes' => 'Wastewater treatment service',
        'items' => [
            ['description' => 'Wastewater Treatment', 'quantity' => 1, 'unit_price' => 3000.00, 'total' => 3000.00],
        ]
    ],
];

// Stats
$totalInvoices = count($invoices);
$totalPaid = count(array_filter($invoices, fn($i) => $i['status'] === 'paid'));
$totalPending = count(array_filter($invoices, fn($i) => $i['status'] === 'pending'));
$totalOverdue = count(array_filter($invoices, fn($i) => $i['status'] === 'overdue'));
$totalRevenue = array_sum(array_filter(array_column($invoices, 'total_amount'), function($v, $k) use ($invoices) {
    return $invoices[$k]['status'] === 'paid';
}, ARRAY_FILTER_USE_BOTH));

$title = 'Wastewater Billing';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Wastewater Billing</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage fee structure, quotations, payments & invoices</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('quotationModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-invoice text-xs"></i> Generate Quotation
            </button>
            <button onclick="openModal('paymentModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-xs"></i> Process Payment
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match Case Reports design   -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Invoices -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-file-invoice text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalInvoices; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Invoices</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All invoices</span>
                    <span class="text-[10px] text-slate-400">Including <?php echo $totalPaid; ?> paid</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Paid -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $totalPaid; ?></p>
                        <p class="text-xs font-medium text-slate-500">Paid</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Completed</span>
                    <span class="text-[10px] text-slate-400">Payment confirmed</span>
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
                        <p class="text-2xl font-black text-amber-600"><?php echo $totalPending; ?></p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400">Payment processing</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Overdue -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $totalOverdue; ?></p>
                        <p class="text-xs font-medium text-slate-500">Overdue</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
                    <span class="text-[10px] text-slate-400">Immediate action</span>
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
                        <p class="text-2xl font-black text-brand-dark">₱<?php echo number_format($totalRevenue, 0); ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Revenue</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">💰 Collected</span>
                    <span class="text-[10px] text-slate-400">From <?php echo $totalPaid; ?> invoices</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Alert -->
    <?php if ($totalOverdue > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo $totalOverdue; ?></span> invoice(s) are overdue. Immediate payment required.
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='overdue'; filterInvoices();" 
                class="text-xs font-semibold text-rose-700 hover:text-rose-900 underline">
            View overdue
        </button>
    </div>
    <?php endif; ?>

    <!-- Fee Structure Section -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 p-4 mb-6">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-table-list text-brand-medium"></i> Fee Structure
            </h4>
            <button onclick="openModal('feeStructureModal')" class="text-xs font-semibold text-brand-medium hover:text-brand-dark transition">
                <i class="fa-solid fa-pen mr-1"></i> Edit Fees
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php foreach ($feeStructure as $fee): ?>
            <div class="bg-slate-50 rounded-lg p-3 border border-slate-200 hover:shadow-sm transition">
                <p class="font-semibold text-slate-800 text-sm"><?php echo $fee['category']; ?></p>
                <p class="text-xs text-slate-400"><?php echo $fee['description']; ?></p>
                <p class="text-lg font-bold text-brand-dark mt-1">₱<?php echo number_format($fee['base_fee'], 2); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchInvoice"
                       placeholder="Search by invoice ID, client, or tank..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="overdue">Overdue</option>
                </select>
                <select id="filterMethod" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Methods</option>
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cash">Cash</option>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Invoice date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Invoice date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Invoice ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Client</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Service</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="invoiceTableBody">
                    <?php foreach ($invoices as $invoice): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors invoice-row <?php echo $invoice['status'] === 'overdue' ? 'bg-rose-50/50' : ''; ?>"
                        data-client="<?php echo strtolower($invoice['client_name']); ?>"
                        data-id="<?php echo $invoice['invoice_id']; ?>"
                        data-status="<?php echo $invoice['status']; ?>"
                        data-method="<?php echo $invoice['payment_method'] ?? ''; ?>"
                        data-invoice-date="<?php echo $invoice['invoice_date']; ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold"><?php echo $invoice['invoice_id']; ?></td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $invoice['client_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $invoice['tank_id']; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $invoice['service_type']; ?></td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-bold text-slate-800">₱<?php echo number_format($invoice['total_amount'], 2); ?></span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                            <?php if ($invoice['status'] === 'overdue'): ?>
                                <span class="block text-[10px] text-rose-500">Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'overdue' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$invoice['status']] ?? $statusColors['pending']; ?>">
                                <?php echo ucfirst($invoice['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php if ($invoice['payment_method']): ?>
                                <span class="text-emerald-600"><?php echo $invoice['payment_method']; ?></span>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewInvoice(<?php echo $invoice['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <?php if ($invoice['status'] === 'pending' || $invoice['status'] === 'overdue'): ?>
                                    <button onclick="processPayment(<?php echo $invoice['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Pay">
                                        <i class="fa-solid fa-credit-card text-sm"></i>
                                    </button>
                                <?php endif; ?>
                                <button onclick="editInvoice(<?php echo $invoice['id']; ?>)"
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
                <i class="fa-solid fa-file-invoice text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No invoices match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo $totalInvoices; ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalInvoices; ?></span> invoices
            </p>
            <div class="flex gap-1">
                <button class="px-3 py-1.5 rounded-lg text-sm bg-slate-100 text-slate-300 cursor-not-allowed" disabled>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-brand-dark text-white">1</button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QUOTATION GENERATION MODAL                                   -->
<!-- ============================================================ -->
<div id="quotationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-brand-medium"></i>
                Generate Quotation
            </h3>
            <button onclick="closeModal('quotationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="quotationForm" class="p-6 space-y-4" onsubmit="saveQuotation(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Client Name</label>
                <input type="text" id="quote_client" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID</label>
                <input type="text" id="quote_tank" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label>
                <select id="quote_service" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <?php foreach ($feeStructure as $fee): ?>
                        <option value="<?php echo $fee['category']; ?>" data-fee="<?php echo $fee['base_fee']; ?>">
                            <?php echo $fee['category']; ?> - ₱<?php echo number_format($fee['base_fee'], 2); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Additional Items (Optional)</label>
                <textarea id="quote_items" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional items or notes..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="quote_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('quotationModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-file-pdf mr-1.5"></i> Generate Quotation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW INVOICE MODAL                                           -->
<!-- ============================================================ -->
<div id="viewInvoiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Invoice Details</h3>
            <button onclick="closeModal('viewInvoiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="invoiceDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- PAYMENT PROCESSING MODAL                                     -->
<!-- ============================================================ -->
<div id="editInvoiceModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Invoice</h3>
            <button type="button" onclick="closeModal('editInvoiceModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveInvoiceEdit(event)">
            <input type="hidden" id="edit_invoice_id">
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Client Name</label><input type="text" id="edit_invoice_client" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tank ID</label><input type="text" id="edit_invoice_tank" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Service Type</label><input type="text" id="edit_invoice_service" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Fee Amount</label><input type="number" id="edit_invoice_amount" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitFeeInput(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Tax</label><input type="number" id="edit_invoice_tax" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitFeeInput(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Due Date</label><input type="date" id="edit_invoice_due_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_invoice_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"><option value="paid">Paid</option><option value="pending">Pending</option><option value="overdue">Overdue</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_invoice_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editInvoiceModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<div id="paymentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-credit-card text-brand-medium"></i>
                Process Payment
            </h3>
            <button onclick="closeModal('paymentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="paymentForm" class="p-6 space-y-4" onsubmit="savePayment(event)">
            <input type="hidden" id="pay_invoice_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Invoice ID</label>
                <input type="text" id="pay_invoice_display" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 outline-none cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Amount</label>
                <input type="number" id="pay_amount" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 outline-none cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Payment Method</label>
                <select id="pay_method" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Cash">Cash</option>
                    <option value="Over-the-Counter">Over-the-Counter</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Reference Number</label>
                <input type="text" id="pay_reference" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Optional reference number">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('paymentModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Process Payment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- FEE STRUCTURE MODAL                                          -->
<!-- ============================================================ -->
<div id="feeStructureModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-table-list text-brand-medium"></i>
                Fee Structure Management
            </h3>
            <button onclick="closeModal('feeStructureModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <?php foreach ($feeStructure as $index => $fee): ?>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div class="flex-1">
                        <p class="font-semibold text-slate-800 text-sm"><?php echo $fee['category']; ?></p>
                        <p class="text-xs text-slate-400"><?php echo $fee['description']; ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" id="fee_<?php echo $index; ?>" value="<?php echo $fee['base_fee']; ?>" min="0" max="99999999999" step="0.01" inputmode="decimal" oninput="limitFeeInput(this)" title="Maximum 11 whole-number digits"
                               class="w-24 px-2 py-1 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <button onclick="updateFee(<?php echo $index; ?>)" class="text-xs text-brand-medium hover:text-brand-dark transition">
                            <i class="fa-solid fa-check"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                <button type="button" onclick="closeModal('feeStructureModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const INVOICES = <?php echo json_encode(array_column($invoices, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

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
    // VIEW INVOICE
    // ============================================================
    function viewInvoice(id) {
        openModal('viewInvoiceModal');
        const i = INVOICES[id];
        if (!i) return;

        setTimeout(() => {
            const statusColors = {
                paid: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                overdue: 'bg-rose-100 text-rose-700'
            };

            const itemsHtml = i.items.map(item => `
                <div class="flex justify-between items-center p-2 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="text-sm text-slate-800">${item.description}</p>
                        <p class="text-xs text-slate-400">${item.quantity} x ₱${Number(item.unit_price).toFixed(2)}</p>
                    </div>
                    <span class="font-semibold text-slate-800">₱${Number(item.total).toFixed(2)}</span>
                </div>
            `).join('');

            document.getElementById('invoiceDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${i.client_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${i.client_name}</h4>
                            <p class="text-sm text-slate-500">${i.invoice_id} • ${i.tank_id}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[i.status] || statusColors.pending}">
                                ${i.status.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Service</p><p class="text-sm text-slate-800">${i.service_type}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Invoice Date</p><p class="text-sm text-slate-800">${new Date(i.invoice_date).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Due Date</p><p class="text-sm text-slate-800">${new Date(i.due_date).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Payment Method</p><p class="text-sm text-slate-800">${i.payment_method || '—'}</p></div>
                        ${i.paid_at ? `<div><p class="text-xs text-slate-400 font-semibold">Paid At</p><p class="text-sm text-slate-800">${new Date(i.paid_at).toLocaleString()}</p></div>` : ''}
                        ${i.payment_reference ? `<div><p class="text-xs text-slate-400 font-semibold">Reference</p><p class="text-sm text-slate-800 font-mono">${i.payment_reference}</p></div>` : ''}
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">Items</h5>
                        <div class="space-y-2">${itemsHtml}</div>
                        <div class="mt-2 pt-2 border-t border-slate-200 flex justify-between">
                            <span class="font-semibold text-slate-700">Total</span>
                            <span class="font-bold text-brand-dark">₱${Number(i.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                    ${i.notes ? `<div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${i.notes}</p></div>` : ''}
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewInvoiceModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        ${i.status === 'pending' || i.status === 'overdue' ? `<button onclick="closeModal('viewInvoiceModal'); processPayment(${i.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-credit-card mr-1.5"></i> Pay Now</button>` : ''}
                        <button onclick="downloadInvoice('${i.invoice_id}')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-download mr-1.5"></i> Download</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // PROCESS PAYMENT
    // ============================================================
    function processPayment(id) {
        const i = INVOICES[id];
        if (!i) return;
        
        document.getElementById('pay_invoice_id').value = id;
        document.getElementById('pay_invoice_display').value = i.invoice_id;
        document.getElementById('pay_amount').value = i.total_amount;
        document.getElementById('pay_reference').value = '';
        
        openModal('paymentModal');
    }

    function savePayment(event) {
        event.preventDefault();
        const id = document.getElementById('pay_invoice_id').value;
        const i = INVOICES[id];
        if (!i) return;
        
        i.status = 'paid';
        i.payment_method = document.getElementById('pay_method').value;
        i.payment_reference = document.getElementById('pay_reference').value.trim() || 'PAY-' + Date.now();
        i.paid_at = new Date().toISOString().replace('T', ' ').slice(0, 19);
        
        updateInvoiceRow(i);
        closeModal('paymentModal');
        showToast('Payment for ' + i.invoice_id + ' processed successfully!', 'success');
    }

    function updateInvoiceRow(i) {
        const rows = document.querySelectorAll('.invoice-row');
        rows.forEach(row => {
            const client = row.querySelector('.font-semibold.text-slate-800.text-sm')?.textContent;
            if (client === i.client_name) {
                const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
                const statusColors = {
                    paid: 'bg-emerald-100 text-emerald-700',
                    pending: 'bg-amber-100 text-amber-700',
                    overdue: 'bg-rose-100 text-rose-700'
                };
                statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[i.status] || statusColors.pending}`;
                statusBadge.textContent = i.status.charAt(0).toUpperCase() + i.status.slice(1);
                
                const methodEl = row.querySelector('.text-slate-500.text-xs');
                if (methodEl) {
                    if (i.payment_method) {
                        methodEl.innerHTML = `<span class="text-emerald-600">${i.payment_method}</span>`;
                    } else {
                        methodEl.innerHTML = '<span class="text-slate-400">—</span>';
                    }
                }
            }
        });
    }

    // ============================================================
    // QUOTATION
    // ============================================================
    function saveQuotation(event) {
        event.preventDefault();
        showToast('Quotation generated successfully!', 'success');
        closeModal('quotationModal');
    }

    // ============================================================
    // FEE STRUCTURE
    // ============================================================
    function limitFeeInput(input) {
        const parts = String(input.value || '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 11);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    function isValidFee(value) {
        return /^\d{1,11}(\.\d{1,2})?$/.test(String(value)) && Number(value) >= 0 && Number(value) <= 99999999999.99;
    }

    function updateFee(index) {
        const value = document.getElementById('fee_' + index).value;
        if (!isValidFee(value)) {
            showToast('Fee must contain no more than 11 whole-number digits.', 'warning');
            return;
        }
        showToast('Fee updated to ₱' + parseFloat(value).toFixed(2), 'success');
    }

    // ============================================================
    // DOWNLOAD INVOICE
    // ============================================================
    function downloadInvoice(invoiceId) {
        const invoice = Object.values(INVOICES).find(item => item.invoice_id === invoiceId);
        if (!invoice) {
            showToast('Invoice not found', 'danger');
            return;
        }

        const printWindow = window.open('', '_blank', 'width=900,height=700');
        if (!printWindow) {
            showToast('Please allow popups to download the invoice', 'warning');
            return;
        }

        const formatMoney = value => 'PHP ' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const formatDate = value => value ? new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' }) : '—';
        const items = (invoice.items || []).map(item => `
            <tr><td>${item.description}</td><td class="number">${item.quantity}</td><td class="number">${formatMoney(item.unit_price)}</td><td class="number">${formatMoney(item.total)}</td></tr>
        `).join('');

        printWindow.document.write(`<!DOCTYPE html><html><head><title>${invoice.invoice_id} - Wastewater Billing</title><style>
            @page { margin: .75in; } * { box-sizing: border-box; }
            body { margin: 0; color: #000; font-family: "Times New Roman", Times, serif; font-size: 11pt; }
            .report-header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #000; }
            .report-header img { width: 120px; display: block; margin: 0 auto 10px; }
            h1 { margin: 0; font-size: 20pt; text-transform: uppercase; } h2 { margin: 5px 0 0; font-size: 14pt; font-weight: normal; }
            .meta { margin-top: 8px; font-size: 10pt; } .section { margin-bottom: 20px; page-break-inside: avoid; }
            .section-title { font-size: 12pt; font-weight: bold; text-transform: uppercase; border-bottom: 1px solid #777; padding-bottom: 6px; margin-bottom: 10px; }
            .details { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 24px; } .label { color: #444; font-size: 9pt; } .value { font-weight: 600; }
            table { width: 100%; border-collapse: collapse; } th, td { padding: 9px 7px; border-bottom: 1px solid #999; text-align: left; } th { border-top: 1px solid #000; font-size: 10pt; text-transform: uppercase; } .number { text-align: right; }
            .totals { margin-left: auto; width: 280px; margin-top: 15px; } .totals div { display: flex; justify-content: space-between; padding: 5px 0; } .grand-total { border-top: 2px solid #000; font-size: 13pt; font-weight: bold; }
            .notes { border: 1px solid #777; padding: 10px; min-height: 45px; } @media print { body { padding: 0; } }
        </style></head><body>
            <header class="report-header"><img src="<?php echo site_url('assets/images/logo.png'); ?>" alt="Logo"><h1>Health Sanitation Management Caloocan</h1><h2>Wastewater Billing Invoice</h2><div class="meta">${invoice.invoice_id} | Issued ${formatDate(invoice.invoice_date)}</div></header>
            <section class="section"><div class="section-title">Bill To</div><div class="details"><div><div class="label">Client</div><div class="value">${invoice.client_name}</div></div><div><div class="label">Tank ID</div><div class="value">${invoice.tank_id}</div></div><div><div class="label">Service</div><div class="value">${invoice.service_type}</div></div><div><div class="label">Due Date</div><div class="value">${formatDate(invoice.due_date)}</div></div></div></section>
            <section class="section"><div class="section-title">Charges</div><table><thead><tr><th>Description</th><th class="number">Qty</th><th class="number">Unit Price</th><th class="number">Total</th></tr></thead><tbody>${items || `<tr><td>${invoice.service_type}</td><td class="number">1</td><td class="number">${formatMoney(invoice.amount)}</td><td class="number">${formatMoney(invoice.amount)}</td></tr>`}</tbody></table><div class="totals"><div><span>Subtotal</span><span>${formatMoney(invoice.amount)}</span></div><div><span>Tax</span><span>${formatMoney(invoice.tax)}</span></div><div class="grand-total"><span>Total</span><span>${formatMoney(invoice.total_amount)}</span></div></div></section>
            <section class="section"><div class="section-title">Payment Status</div><div class="details"><div><div class="label">Status</div><div class="value">${invoice.status.toUpperCase()}</div></div><div><div class="label">Payment Method</div><div class="value">${invoice.payment_method || 'Unpaid'}</div></div></div></section>
            <section class="section"><div class="section-title">Notes</div><div class="notes">${invoice.notes || 'No additional notes.'}</div></section>
            <script>window.onload = function() { window.print(); }<\/script>
        </body></html>`);
        printWindow.document.close();
        showToast('Invoice print view opened', 'success');
    }

    // ============================================================
    // EDIT INVOICE
    // ============================================================
    function editInvoice(id) {
        const invoice = INVOICES[id];
        if (!invoice) return;
        document.getElementById('edit_invoice_id').value = invoice.id;
        document.getElementById('edit_invoice_client').value = invoice.client_name;
        document.getElementById('edit_invoice_tank').value = invoice.tank_id;
        document.getElementById('edit_invoice_service').value = invoice.service_type;
        document.getElementById('edit_invoice_amount').value = invoice.amount;
        document.getElementById('edit_invoice_tax').value = invoice.tax;
        document.getElementById('edit_invoice_due_date').value = invoice.due_date;
        document.getElementById('edit_invoice_status').value = invoice.status;
        document.getElementById('edit_invoice_notes').value = invoice.notes || '';
        openModal('editInvoiceModal');
    }

    function saveInvoiceEdit(event) {
        event.preventDefault();
        const amount = document.getElementById('edit_invoice_amount').value;
        const tax = document.getElementById('edit_invoice_tax').value;
        if (!isValidFee(amount) || !isValidFee(tax)) {
            showToast('Fee and tax must contain no more than 11 whole-number digits.', 'warning');
            return;
        }
        const id = document.getElementById('edit_invoice_id').value;
        const invoice = INVOICES[id];
        if (!invoice) return;
        invoice.client_name = document.getElementById('edit_invoice_client').value.trim();
        invoice.tank_id = document.getElementById('edit_invoice_tank').value.trim();
        invoice.service_type = document.getElementById('edit_invoice_service').value.trim();
        invoice.amount = Number(amount);
        invoice.tax = Number(tax);
        invoice.total_amount = invoice.amount + invoice.tax;
        invoice.due_date = document.getElementById('edit_invoice_due_date').value;
        invoice.status = document.getElementById('edit_invoice_status').value;
        invoice.notes = document.getElementById('edit_invoice_notes').value.trim();
        const row = document.querySelector(`.invoice-row[data-id="${invoice.invoice_id}"]`);
        if (row) {
            row.dataset.client = invoice.client_name.toLowerCase();
            row.dataset.status = invoice.status;
            const client = row.querySelector('.font-semibold.text-slate-800.text-sm');
            if (client) client.textContent = invoice.client_name;
            const amountCell = row.querySelector('.text-sm.font-bold.text-slate-800');
            if (amountCell) amountCell.textContent = '₱' + invoice.total_amount.toFixed(2);
        }
        closeModal('editInvoiceModal');
        showToast('Invoice updated successfully!', 'success');
        filterInvoices();
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
        toast.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('hidden');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchInvoice').addEventListener('input', filterInvoices);
    document.getElementById('filterStatus').addEventListener('change', filterInvoices);
    document.getElementById('filterMethod').addEventListener('change', filterInvoices);
    document.getElementById('filterDateFrom').addEventListener('change', filterInvoices);
    document.getElementById('filterDateTo').addEventListener('change', filterInvoices);

    function filterInvoices() {
        const search = document.getElementById('searchInvoice').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const method = document.getElementById('filterMethod').value.toLowerCase();
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.invoice-row').forEach(row => {
            const client = row.dataset.client;
            const id = row.dataset.id.toLowerCase();
            const rowStatus = row.dataset.status;
            const rowMethod = row.dataset.method.toLowerCase();
            const invoiceDate = row.dataset.invoiceDate || '';

            const matchesSearch = client.includes(search) || id.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesMethod = !method || rowMethod === method;
            const matchesDateFrom = !dateFrom || (invoiceDate && invoiceDate >= dateFrom);
            const matchesDateTo = !dateTo || (invoiceDate && invoiceDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesMethod && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchInvoice').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterMethod').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.invoice-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
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
</script>

<?php include_once '../../includes/footer.php'; ?>