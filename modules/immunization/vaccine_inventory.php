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
requireDepartmentAccess('immunization & nutrition');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/VaccineInventory.php';

// Base Vaccine Inventory Data
$vaccineInventory = [];

try {
    $db = Database::getInstance();
    $dbInventory = $db->select('vaccine_inventory', [], ['order' => 'vaccine_name.asc']);
    if (!empty($dbInventory) && is_array($dbInventory)) {
        foreach ($dbInventory as $v) {
            $rId = (int)$v['id'];
            $qty = (int)($v['quantity'] ?? 0);
            $min = (int)($v['minimum_stock'] ?? 20);
            $status = VaccineInventory::computeStatus($qty, $min);

            $vaccineInventory[] = [
                'id' => $rId,
                'vaccine_name' => $v['vaccine_name'] ?? 'Vaccine',
                'batch_number' => $v['batch_number'] ?? ('VAC-' . sprintf('%03d', $rId)),
                'quantity' => $qty,
                'minimum_stock' => $min,
                'received_date' => date('Y-m-d', strtotime($v['received_date'] ?? 'now')),
                'expiry_date' => date('Y-m-d', strtotime($v['expiry_date'] ?? '+1 year')),
                'temperature' => (float)($v['temperature'] ?? 4.0),
                'storage_location' => $v['storage_location'] ?? 'Refrigerator A1',
                'supplier' => $v['supplier'] ?? 'DOH Central',
                'status' => $status,
                'unit' => $v['unit'] ?? 'doses'
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Supabase vaccine_inventory query exception: ' . $e->getMessage());
}

// Real Supabase data loaded from surveillance_resources

// Stock Alert Thresholds
$criticalThreshold = 20;
$lowThreshold = 50;

// Stats
$totalVaccines = count($vaccineInventory);
$totalStock = array_sum(array_column($vaccineInventory, 'quantity'));
$totalLowStock = count(array_filter($vaccineInventory, fn($v) => $v['status'] === 'low_stock'));
$totalCritical = count(array_filter($vaccineInventory, fn($v) => $v['status'] === 'critical'));
$totalOutOfStock = count(array_filter($vaccineInventory, fn($v) => $v['status'] === 'out_of_stock'));

// Expiring vaccines (within 30 days)
$expiringSoon = array_filter($vaccineInventory, function($v) {
    $expiry = new DateTime($v['expiry_date']);
    $today = new DateTime();
    $daysLeft = $today->diff($expiry)->days;
    return $daysLeft <= 30 && $v['status'] !== 'out_of_stock';
});

$title = 'Vaccine Inventory';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Vaccine Inventory</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage stock, track expiry, monitor cold chain</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('addStockModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Add Stock
            </button>
            <button onclick="openModal('adjustStockModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-pen text-xs"></i> Adjust Stock
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Vaccines -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-syringe text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalVaccines; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Vaccines</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">💉 All types</span>
                    <span class="text-[10px] text-slate-400"><?php echo $totalStock; ?> units total</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Total Stock -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-boxes-stacked text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo number_format($totalStock); ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Stock</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">📦 Inventory</span>
                    <span class="text-[10px] text-slate-400">All vaccines combined</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Low Stock -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black <?php echo $totalLowStock > 0 ? 'text-amber-600' : 'text-slate-900'; ?>">
                            <?php echo $totalLowStock; ?>
                        </p>
                        <p class="text-xs font-medium text-slate-500">Low Stock</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 <?php echo $totalLowStock > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $totalLowStock > 0 ? '⚠️ Needs attention' : '✅ Stock adequate'; ?>
                    </span>
                    <span class="text-[10px] text-slate-400">Below minimum</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Critical -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black <?php echo $totalCritical > 0 ? 'text-rose-600' : 'text-slate-900'; ?>">
                            <?php echo $totalCritical; ?>
                        </p>
                        <p class="text-xs font-medium text-slate-500">Critical</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 <?php echo $totalCritical > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $totalCritical > 0 ? '🚨 Immediate action' : '✅ All good'; ?>
                    </span>
                    <span class="text-[10px] text-slate-400">Severely low</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Out of Stock -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-slate-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-slate-500 to-slate-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-slate-200">
                        <i class="fa-solid fa-ban text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black <?php echo $totalOutOfStock > 0 ? 'text-slate-500' : 'text-slate-900'; ?>">
                            <?php echo $totalOutOfStock; ?>
                        </p>
                        <p class="text-xs font-medium text-slate-500">Out of Stock</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 <?php echo $totalOutOfStock > 0 ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $totalOutOfStock > 0 ? '⚠️ Stock depleted' : '✅ In stock'; ?>
                    </span>
                    <span class="text-[10px] text-slate-400">Unavailable</span>
                </div>
            </div>
        </div>
    </div>


    <?php if (count($expiringSoon) > 0): ?>
    <div class="mb-4 space-y-2" id="alertContainer">
        <div class="flex items-center justify-between p-3 bg-amber-50 border border-amber-200 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-clock text-amber-500"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-700">⏰ <span class="font-bold"><?php echo count($expiringSoon); ?></span> Vaccine(s) Expiring Within 30 Days</p>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <?php foreach (array_slice($expiringSoon, 0, 3) as $item): ?>
                            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">
                                <?php echo $item['vaccine_name']; ?> (<?php 
                                    $expiry = new DateTime($item['expiry_date']);
                                    $today = new DateTime();
                                    echo $today->diff($expiry)->days . 'd';
                                ?>)
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($expiringSoon) > 3): ?>
                            <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-xs font-medium">
                                +<?php echo count($expiringSoon) - 3; ?> more
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="document.getElementById('filterStatus').value='expiring'; filterInventory();" 
                        class="px-3 py-1.5 text-xs font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition">
                    View All
                </button>
                <button onclick="document.getElementById('alertContainer').style.display='none'" 
                        class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-lg transition">
                    Dismiss
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- SEARCH & FILTER                                              -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchVaccine"
                       placeholder="Search by vaccine name, batch, or supplier..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="critical">Critical</option>
                    <option value="out_of_stock">Out of Stock</option>
                    <option value="expiring">Expiring Soon</option>
                    <option value="alert">All Alerts</option>
                </select>
                <select id="filterLocation" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Locations</option>
                    <option value="Freezer A1">Freezer A1</option>
                    <option value="Freezer A2">Freezer A2</option>
                    <option value="Freezer B1">Freezer B1</option>
                    <option value="Freezer C1">Freezer C1</option>
                    <option value="Refrigerator A1">Refrigerator A1</option>
                    <option value="Refrigerator A2">Refrigerator A2</option>
                    <option value="Refrigerator B1">Refrigerator B1</option>
                    <option value="Refrigerator B2">Refrigerator B2</option>
                    <option value="Refrigerator C1">Refrigerator C1</option>
                    <option value="Refrigerator C2">Refrigerator C2</option>
                </select>
                  <input type="date" id="filterDateFrom" aria-label="Expiry date from"
                      class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                  <input type="date" id="filterDateTo" aria-label="Expiry date to"
                      class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- INVENTORY TABLE                                              -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vaccine</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Batch #</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stock</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Expiry</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Temperature</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Location</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="inventoryTableBody">
                    <?php if (empty($vaccineInventory)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-400">
                            <i class="fa-solid fa-boxes-packing text-3xl block mb-2 opacity-30"></i>
                            No vaccine inventory records found in database
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($vaccineInventory as $item): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors inventory-row <?php echo $item['status'] === 'critical' || $item['status'] === 'out_of_stock' ? 'bg-rose-50/50' : ''; ?>"
                        data-name="<?php echo strtolower($item['vaccine_name']); ?>"
                        data-batch="<?php echo strtolower($item['batch_number']); ?>"
                        data-supplier="<?php echo strtolower($item['supplier']); ?>"
                        data-status="<?php echo $item['status']; ?>"
                        data-location="<?php echo $item['storage_location']; ?>"
                        data-expiry-date="<?php echo $item['expiry_date']; ?>"
                        data-id="<?php echo $item['id']; ?>">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $item['vaccine_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $item['unit']; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600"><?php echo $item['batch_number']; ?></td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-bold <?php echo $item['quantity'] <= $item['minimum_stock'] ? 'text-rose-600' : 'text-slate-800'; ?>">
                                <?php echo number_format($item['quantity']); ?>
                            </span>
                            <span class="block text-[10px] text-slate-400">Min: <?php echo number_format($item['minimum_stock']); ?></span>
                        </td>
                        <td class="px-4 py-3">
                            <?php 
                                $expiry = new DateTime($item['expiry_date']);
                                $today = new DateTime();
                                $daysLeft = $today->diff($expiry)->days;
                                $expiryClass = $daysLeft <= 30 ? 'text-rose-600 font-bold' : 'text-slate-500';
                            ?>
                            <span class="text-xs <?php echo $expiryClass; ?>">
                                <?php echo date('M d, Y', strtotime($item['expiry_date'])); ?>
                            </span>
                            <?php if ($daysLeft <= 30 && $item['status'] !== 'out_of_stock'): ?>
                                <span class="block text-[10px] text-rose-500"><?php echo $daysLeft; ?> days left</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <span class="text-xs font-medium <?php echo $item['temperature'] >= 2 && $item['temperature'] <= 8 ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                    <?php echo $item['temperature']; ?>°C
                                </span>
                                <?php if ($item['temperature'] >= 2 && $item['temperature'] <= 8): ?>
                                    <i class="fa-solid fa-check-circle text-emerald-500 text-xs"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-xs"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $item['storage_location']; ?></td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'in_stock' => 'bg-emerald-100 text-emerald-700',
                                    'low_stock' => 'bg-amber-100 text-amber-700',
                                    'critical' => 'bg-rose-100 text-rose-700',
                                    'out_of_stock' => 'bg-slate-100 text-slate-500'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$item['status']] ?? $statusColors['in_stock']; ?>">
                                <?php echo str_replace('_', ' ', ucfirst($item['status'])); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewVaccine(<?php echo $item['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View Details">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="openAdjustStockFor(<?php echo $item['id']; ?>)"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Adjust Stock">
                                    <i class="fa-solid fa-sliders text-sm"></i>
                                </button>
                                <button onclick="editVaccine(<?php echo $item['id']; ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-box-open text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No vaccines match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo count($vaccineInventory); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo count($vaccineInventory); ?></span> items
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
<!-- VIEW VACCINE MODAL                                           -->
<!-- ============================================================ -->
<div id="viewVaccineModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-syringe text-brand-medium"></i>
                Vaccine Details
            </h3>
            <button onclick="closeModal('viewVaccineModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="vaccineDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT VACCINE MODAL                                           -->
<!-- ============================================================ -->
<div id="editVaccineModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen text-brand-medium"></i>
                Edit Vaccine
            </h3>
            <button onclick="closeModal('editVaccineModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editVaccineForm" class="p-6 space-y-4" onsubmit="saveEditVaccine(event)">
            <input type="hidden" id="edit_vaccine_id">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Vaccine Name</label>
                <input type="text" id="edit_vaccine_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Batch Number (System Generated)</label>
                <input type="text" id="edit_batch_number" required readonly class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm font-mono bg-slate-100 text-slate-700 cursor-not-allowed font-semibold focus:outline-none select-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Quantity</label>
                <input type="number" id="edit_quantity" required min="0" max="99999999" step="1" inputmode="numeric" oninput="limitInventoryInteger(this)" title="Maximum 8 digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Minimum Stock</label>
                <input type="number" id="edit_minimum_stock" required min="1" max="99999999" step="1" inputmode="numeric" oninput="limitInventoryInteger(this)" title="Maximum 8 digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Expiry Date</label>
                <input type="date" id="edit_expiry_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Temperature (°C)</label>
                <input type="number" id="edit_temperature" min="-999" max="999" step="0.1" inputmode="decimal" oninput="limitTemperatureInput(this)" required title="Maximum 3 whole-number digits with decimals" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Storage Location</label>
                <select id="edit_storage_location" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Freezer A1">Freezer A1</option>
                    <option value="Freezer A2">Freezer A2</option>
                    <option value="Freezer B1">Freezer B1</option>
                    <option value="Freezer C1">Freezer C1</option>
                    <option value="Refrigerator A1">Refrigerator A1</option>
                    <option value="Refrigerator A2">Refrigerator A2</option>
                    <option value="Refrigerator B1">Refrigerator B1</option>
                    <option value="Refrigerator B2">Refrigerator B2</option>
                    <option value="Refrigerator C1">Refrigerator C1</option>
                    <option value="Refrigerator C2">Refrigerator C2</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Supplier</label>
                <input type="text" id="edit_supplier" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                <select id="edit_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="critical">Critical</option>
                    <option value="out_of_stock">Out of Stock</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('editVaccineModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD STOCK MODAL                                              -->
<!-- ============================================================ -->
<div id="addStockModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-medium"></i>
                Add Stock
            </h3>
            <button onclick="closeModal('addStockModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="addStockForm" class="p-6 space-y-4" onsubmit="saveAddStock(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Vaccine Name *</label>
                <input type="text" id="stock_vaccine" list="doh_vaccine_list" oninput="onVaccineNameChange()" placeholder="Select or type vaccine..." required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                <datalist id="doh_vaccine_list">
                    <option value="BCG Vaccine">
                    <option value="Hepatitis B Vaccine">
                    <option value="Pentavalent (DPT-HepB-Hib)">
                    <option value="Oral Polio Vaccine (OPV)">
                    <option value="Inactivated Polio Vaccine (IPV)">
                    <option value="Pneumococcal Conjugate Vaccine (PCV)">
                    <option value="Measles, Mumps, Rubella (MMR)">
                    <option value="Rotavirus Vaccine">
                    <option value="Influenza Vaccine">
                    <option value="Tetanus Toxoid (TT)">
                </datalist>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide">Batch Number (Auto-Generated) *</label>
                    <button type="button" onclick="generateBatchNumber()" class="text-[11px] font-semibold text-brand-medium hover:text-brand-dark flex items-center gap-1 transition">
                        <i class="fa-solid fa-arrows-rotate"></i> Regenerate
                    </button>
                </div>
                <input type="text" id="stock_batch" required readonly placeholder="e.g. BCG-2026-A4F2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm font-mono bg-slate-100 text-slate-700 cursor-not-allowed font-semibold focus:outline-none select-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Quantity *</label>
                <input type="number" id="stock_qty" required min="1" max="99999999" step="1" inputmode="numeric" oninput="limitInventoryInteger(this)" placeholder="e.g. 100" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-semibold">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Minimum Stock Alert Threshold *</label>
                <input type="number" id="stock_min" required min="1" max="99999999" value="20" step="1" inputmode="numeric" oninput="limitInventoryInteger(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-semibold">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Expiry Date *</label>
                <input type="date" id="stock_expiry" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Storage Temperature (°C) *</label>
                <input type="number" id="stock_temp" min="-999" max="999" step="0.1" value="4.0" inputmode="decimal" oninput="limitTemperatureInput(this)" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Storage Location *</label>
                <select id="stock_location" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Refrigerator A1">Refrigerator A1 (Main Cold Chain)</option>
                    <option value="Refrigerator A2">Refrigerator A2</option>
                    <option value="Refrigerator B1">Refrigerator B1</option>
                    <option value="Refrigerator B2">Refrigerator B2</option>
                    <option value="Freezer A1">Freezer A1 (-20°C for OPV/Live)</option>
                    <option value="Freezer A2">Freezer A2</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Supplier / Source *</label>
                <input type="text" id="stock_supplier" value="DOH Central Supply Office" placeholder="e.g. DOH Central Supply Office, Provincial Health Office" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('addStockModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Add Stock
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADJUST STOCK MODAL                                           -->
<!-- ============================================================ -->
<div id="adjustStockModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen text-brand-medium"></i>
                Adjust Stock
            </h3>
            <button onclick="closeModal('adjustStockModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="adjustStockForm" class="p-6 space-y-4" onsubmit="saveAdjustStock(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Vaccine *</label>
                <select id="adjust_vaccine" required onchange="updateAdjustPreview()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">-- Choose Vaccine to Adjust --</option>
                    <?php foreach ($vaccineInventory as $v): ?>
                        <option value="<?php echo $v['id']; ?>" data-qty="<?php echo $v['quantity']; ?>" data-min="<?php echo $v['minimum_stock']; ?>">
                            <?php echo htmlspecialchars($v['vaccine_name']); ?> (<?php echo number_format($v['quantity']); ?> in stock)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Dynamic Live Stock Preview Box -->
            <div id="adjust_stock_preview" class="hidden p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2 text-xs">
                <div class="grid grid-cols-2 gap-2 pb-2 border-b border-slate-200">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Batch Number</span>
                        <span id="prev_batch_number" class="font-mono font-bold text-slate-800">--</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Supplier</span>
                        <span id="prev_supplier" class="font-semibold text-slate-800 truncate block">DOH Central Supply Office</span>
                    </div>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>Current Stock:</span>
                    <span id="prev_current_stock" class="font-bold text-slate-900">0 doses</span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>Adjustment:</span>
                    <span id="prev_adjustment_calc" class="font-semibold text-brand-dark">+0 doses</span>
                </div>
                <div class="pt-1.5 border-t border-slate-200 flex items-center justify-between font-bold">
                    <span>Projected New Stock:</span>
                    <span id="prev_projected_stock" class="text-sm text-emerald-600">0 doses</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Adjustment Action *</label>
                <select id="adjust_type" required onchange="updateAdjustPreview()" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="add">➕ Add Stock (Received new units)</option>
                    <option value="remove">➖ Remove Stock (Doses administered / damaged / expired)</option>
                    <option value="set">🔄 Set Direct Quantity (Inventory reconciliation)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Quantity (Units) *</label>
                <input type="number" id="adjust_qty" required min="1" max="99999999" step="1" inputmode="numeric" oninput="limitInventoryInteger(this); updateAdjustPreview()" placeholder="Enter quantity" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none font-semibold">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Reason / Notes</label>
                <input type="text" id="adjust_reason" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. DOH Shipment Batch #2, Wastage, Physical Count">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('adjustStockModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" id="btnSubmitAdjust"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i> Apply Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const INVENTORY = <?php echo json_encode(array_column($vaccineInventory, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================
    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }
        if (id === 'addStockModal') {
            const batchInput = document.getElementById('stock_batch');
            if (batchInput && !batchInput.value.trim()) {
                generateBatchNumber();
            }
            const suppInput = document.getElementById('stock_supplier');
            if (suppInput && !suppInput.value.trim()) {
                suppInput.value = 'DOH Central Supply Office';
            }
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    }

    function limitInventoryInteger(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 8);
    }

    function limitTemperatureInput(input) {
        const raw = String(input.value || '');
        const sign = raw.startsWith('-') ? '-' : '';
        const parts = raw.replace(/^-/, '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 3);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = sign + whole + (parts.length > 1 ? '.' + fraction : '');
    }

    function isValidInventoryInteger(value, minimum = 0) {
        return /^\d{1,8}$/.test(String(value)) && Number(value) >= minimum && Number(value) <= 99999999;
    }

    function isValidTemperature(value) {
        return /^-?\d{1,3}(\.\d{1,2})?$/.test(String(value)) && Number(value) >= -999 && Number(value) <= 999;
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
    // VIEW VACCINE
    // ============================================================
    function viewVaccine(id) {
        openModal('viewVaccineModal');
        const v = INVENTORY[id];
        if (!v) {
            document.getElementById('vaccineDetailsContent').innerHTML = '<p class="text-sm text-rose-500 text-center py-10">Vaccine not found</p>';
            return;
        }

        setTimeout(() => {
            const statusColors = {
                in_stock: 'bg-emerald-100 text-emerald-700',
                low_stock: 'bg-amber-100 text-amber-700',
                critical: 'bg-rose-100 text-rose-700',
                out_of_stock: 'bg-slate-100 text-slate-500'
            };

            document.getElementById('vaccineDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0">
                            ${v.vaccine_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${v.vaccine_name}</h4>
                            <p class="text-sm text-slate-500">Batch: ${v.batch_number}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[v.status] || statusColors.in_stock}">
                                ${v.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-slate-400">Quantity</p><p class="text-sm font-bold text-slate-800">${v.quantity} ${v.unit}</p></div>
                        <div><p class="text-xs text-slate-400">Minimum Stock</p><p class="text-sm text-slate-800">${v.minimum_stock} ${v.unit}</p></div>
                        <div><p class="text-xs text-slate-400">Expiry Date</p><p class="text-sm text-slate-800">${new Date(v.expiry_date).toLocaleDateString()}</p></div>
                        <div><p class="text-xs text-slate-400">Temperature</p><p class="text-sm font-medium ${v.temperature >= 2 && v.temperature <= 8 ? 'text-emerald-600' : 'text-rose-600'}">${v.temperature}°C</p></div>
                        <div class="col-span-2"><p class="text-xs text-slate-400">Storage Location</p><p class="text-sm text-slate-800">${v.storage_location}</p></div>
                        <div class="col-span-2"><p class="text-xs text-slate-400">Supplier</p><p class="text-sm text-slate-800">${v.supplier}</p></div>
                        <div class="col-span-2"><p class="text-xs text-slate-400">Received Date</p><p class="text-sm text-slate-800">${new Date(v.received_date).toLocaleDateString()}</p></div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewVaccineModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <button onclick="closeModal('viewVaccineModal'); openAdjustStockFor('${v.id}')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5"><i class="fa-solid fa-sliders text-xs"></i> Adjust Stock</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // EDIT VACCINE
    // ============================================================
    function editVaccine(id) {
        const v = INVENTORY[id];
        if (!v) {
            showToast('Vaccine not found', 'danger');
            return;
        }

        document.getElementById('edit_vaccine_id').value = v.id;
        document.getElementById('edit_vaccine_name').value = v.vaccine_name;
        document.getElementById('edit_batch_number').value = v.batch_number;
        document.getElementById('edit_quantity').value = v.quantity;
        document.getElementById('edit_minimum_stock').value = v.minimum_stock;
        document.getElementById('edit_expiry_date').value = v.expiry_date;
        document.getElementById('edit_temperature').value = v.temperature;
        document.getElementById('edit_storage_location').value = v.storage_location;
        document.getElementById('edit_supplier').value = v.supplier || '';
        document.getElementById('edit_status').value = v.status;

        openModal('editVaccineModal');
    }

    async function saveEditVaccine(event) {
        event.preventDefault();
        const quantity = document.getElementById('edit_quantity').value;
        const minimumStock = document.getElementById('edit_minimum_stock').value;
        const temperature = document.getElementById('edit_temperature').value;
        if (!isValidInventoryInteger(quantity) || !isValidInventoryInteger(minimumStock, 1) || !isValidTemperature(temperature)) {
            showToast('Quantity and minimum stock allow up to 8 digits; temperature allows up to 3 digits with decimals.', 'warning');
            return;
        }
        const id = parseInt(document.getElementById('edit_vaccine_id').value);

        const payload = {
            vaccine_name: document.getElementById('edit_vaccine_name').value.trim(),
            batch_number: document.getElementById('edit_batch_number').value.trim(),
            quantity: parseInt(quantity),
            minimum_stock: parseInt(minimumStock),
            expiry_date: document.getElementById('edit_expiry_date').value,
            temperature: parseFloat(temperature),
            storage_location: document.getElementById('edit_storage_location').value,
            supplier: document.getElementById('edit_supplier').value.trim()
        };

        try {
            const res = await fetch(`<?php echo site_url('api/inventory.php'); ?>?id=${id}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeModal('editVaccineModal');
                showToast('Vaccine updated successfully!', 'success');
                const updated = data.data || payload;
                const item = INVENTORY[id];
                if (item) {
                    Object.assign(item, updated);
                    updateInventoryRow(item);
                }
            } else {
                showToast(data.message || 'Failed to update vaccine.', 'danger');
            }
        } catch (err) {
            console.error('Update vaccine error:', err);
            showToast('Error sending update to server.', 'danger');
        }
    }

    function updateInventoryRow(v) {
        const rows = document.querySelectorAll('.inventory-row');
        rows.forEach(row => {
            if (row.dataset.id == v.id) {
                const nameEl = row.querySelector('.font-semibold.text-slate-800.text-sm');
                if (nameEl) nameEl.textContent = v.vaccine_name;
                
                const batchEl = row.querySelector('.font-mono.text-xs.text-slate-600');
                if (batchEl) batchEl.textContent = v.batch_number;
                
                const stockSpan = row.querySelector('.text-sm.font-bold');
                if (stockSpan) {
                    stockSpan.textContent = v.quantity;
                    stockSpan.className = `text-sm font-bold ${v.quantity <= v.minimum_stock ? 'text-rose-600' : 'text-slate-800'}`;
                }
                const minEl = row.querySelector('.text-\\[10px\\].text-slate-400');
                if (minEl) minEl.textContent = 'Min: ' + v.minimum_stock;
                
                const expirySpan = row.querySelector('.px-4.py-3 .text-xs');
                if (expirySpan) {
                    const daysLeft = Math.round((new Date(v.expiry_date) - new Date()) / (1000 * 60 * 60 * 24));
                    expirySpan.textContent = new Date(v.expiry_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    expirySpan.className = `text-xs ${daysLeft <= 30 ? 'text-rose-600 font-bold' : 'text-slate-500'}`;
                }
                
                const tempSpan = row.querySelector('.text-xs.font-medium');
                if (tempSpan) {
                    tempSpan.textContent = v.temperature + '°C';
                    tempSpan.className = `text-xs font-medium ${v.temperature >= 2 && v.temperature <= 8 ? 'text-emerald-600' : 'text-rose-600'}`;
                }
                
                const locEl = row.querySelector('.text-slate-600.text-xs');
                if (locEl) locEl.textContent = v.storage_location;
                
                const statusColors = {
                    in_stock: 'bg-emerald-100 text-emerald-700',
                    low_stock: 'bg-amber-100 text-amber-700',
                    critical: 'bg-rose-100 text-rose-700',
                    out_of_stock: 'bg-slate-100 text-slate-500'
                };
                const statusBadge = row.querySelector('.px-2.py-1.rounded-full');
                if (statusBadge) {
                    statusBadge.className = `px-2 py-1 rounded-full text-xs font-semibold ${statusColors[v.status] || statusColors.in_stock}`;
                    statusBadge.textContent = (v.status || 'in_stock').replace('_', ' ').toUpperCase();
                }
                
                row.className = `border-b border-slate-100 hover:bg-brand-light/40 transition-colors inventory-row ${v.status === 'critical' || v.status === 'out_of_stock' ? 'bg-rose-50/50' : ''}`;
                row.dataset.status = v.status;
            }
        });
    }


    // ============================================================
    // ADJUST STOCK HELPERS
    // ============================================================
    function openAdjustStockFor(id) {
        const sel = document.getElementById('adjust_vaccine');
        if (sel) {
            sel.value = id;
            updateAdjustPreview();
        }
        openModal('adjustStockModal');
    }

    // ============================================================
    // BATCH NUMBER GENERATOR & ADD STOCK HELPERS
    // ============================================================
    function generateBatchNumber() {
        const vName = (document.getElementById('stock_vaccine').value || '').trim();
        let prefix = 'VAC';
        if (vName) {
            const clean = vName.replace(/[^A-Za-z]/g, '').toUpperCase();
            if (clean.includes('BCG')) prefix = 'BCG';
            else if (clean.includes('HEPB') || clean.includes('HEPATITIS')) prefix = 'HEPB';
            else if (clean.includes('PENTA')) prefix = 'PENTA';
            else if (clean.includes('OPV')) prefix = 'OPV';
            else if (clean.includes('IPV')) prefix = 'IPV';
            else if (clean.includes('PCV')) prefix = 'PCV';
            else if (clean.includes('MMR')) prefix = 'MMR';
            else if (clean.includes('ROTA')) prefix = 'ROTA';
            else if (clean.includes('FLU') || clean.includes('INFLUENZA')) prefix = 'FLU';
            else if (clean.includes('TETANUS') || clean.includes('TT')) prefix = 'TT';
            else prefix = clean.substring(0, 4) || 'VAC';
        }
        const yr = new Date().getFullYear();
        const rand = Math.random().toString(36).substring(2, 6).toUpperCase();
        const autoBatch = `${prefix}-${yr}-${rand}`;
        document.getElementById('stock_batch').value = autoBatch;
    }

    function onVaccineNameChange() {
        const currentBatch = (document.getElementById('stock_batch').value || '').trim();
        // If batch is empty or was auto-generated format, regenerate with new prefix
        if (!currentBatch || /^[A-Z]{2,6}-\d{4}-[A-Z0-9]{4}$/.test(currentBatch)) {
            generateBatchNumber();
        }
    }

    // ============================================================
    // ADJUST STOCK HELPERS
    // ============================================================
    function openAdjustStockFor(id) {
        const sel = document.getElementById('adjust_vaccine');
        if (sel) {
            sel.value = id;
            updateAdjustPreview();
        }
        openModal('adjustStockModal');
    }

    function updateAdjustPreview() {
        const sel = document.getElementById('adjust_vaccine');
        const previewBox = document.getElementById('adjust_stock_preview');
        if (!sel || !previewBox) return;

        const opt = sel.options[sel.selectedIndex];
        if (!sel.value || !opt) {
            previewBox.classList.add('hidden');
            return;
        }

        const v = INVENTORY[sel.value] || {};
        previewBox.classList.remove('hidden');

        const currQty = parseInt(opt.dataset.qty) || v.quantity || 0;
        const batchNum = v.batch_number || '--';
        const supplierName = v.supplier || 'DOH Central Supply Office';

        const type = document.getElementById('adjust_type').value;
        const inputQty = parseInt(document.getElementById('adjust_qty').value) || 0;

        let projected = currQty;
        let calcStr = '+0';

        if (type === 'add') {
            projected = currQty + inputQty;
            calcStr = `+${inputQty.toLocaleString()} doses`;
        } else if (type === 'remove') {
            projected = Math.max(0, currQty - inputQty);
            calcStr = `-${inputQty.toLocaleString()} doses`;
        } else if (type === 'set') {
            projected = Math.max(0, inputQty);
            calcStr = `Set to ${inputQty.toLocaleString()} doses`;
        }

        document.getElementById('prev_batch_number').textContent = batchNum;
        document.getElementById('prev_supplier').textContent = supplierName;
        document.getElementById('prev_current_stock').textContent = `${currQty.toLocaleString()} doses`;
        document.getElementById('prev_adjustment_calc').textContent = calcStr;

        const projEl = document.getElementById('prev_projected_stock');
        projEl.textContent = `${projected.toLocaleString()} doses`;
        projEl.className = projected <= 0 ? 'text-sm font-bold text-rose-600' : (projected <= (v.minimum_stock || 20) ? 'text-sm font-bold text-amber-600' : 'text-sm font-bold text-emerald-600');
    }

    // ============================================================
    // ADD STOCK
    // ============================================================
    async function saveAddStock(event) {
        event.preventDefault();
        const vaccineName = document.getElementById('stock_vaccine').value.trim();
        const batchNumber = document.getElementById('stock_batch').value.trim();
        const quantity = document.getElementById('stock_qty').value;
        const minStock = document.getElementById('stock_min').value;
        const expiry = document.getElementById('stock_expiry').value;
        const temp = document.getElementById('stock_temp').value;
        const location = document.getElementById('stock_location').value;
        const supplier = document.getElementById('stock_supplier').value.trim();

        if (!isValidInventoryInteger(quantity, 1) ||
            !isValidInventoryInteger(minStock, 1) ||
            !isValidTemperature(temp)) {
            showToast('Quantity and minimum stock must be valid numbers; temperature must be within reasonable bounds.', 'warning');
            return;
        }

        const payload = {
            vaccine_name: vaccineName,
            batch_number: batchNumber,
            quantity: Number(quantity),
            minimum_stock: Number(minStock),
            expiry_date: expiry,
            temperature: Number(temp),
            storage_location: location,
            supplier: supplier,
            unit: 'doses'
        };

        const submitBtn = event.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';
        }

        try {
            const res = await fetch('<?php echo site_url('api/inventory.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeModal('addStockModal');
                showToast('✅ Vaccine stock added successfully!', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'Failed to add stock.', 'danger');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-plus mr-1.5"></i> Add Stock';
                }
            }
        } catch (err) {
            console.error('Add stock error:', err);
            showToast('Error saving stock to server.', 'danger');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-plus mr-1.5"></i> Add Stock';
            }
        }
    }

    // ============================================================
    // ADJUST STOCK
    // ============================================================
    async function saveAdjustStock(event) {
        event.preventDefault();
        const id = document.getElementById('adjust_vaccine').value;
        const type = document.getElementById('adjust_type').value;
        const qty = document.getElementById('adjust_qty').value;
        const reason = document.getElementById('adjust_reason').value.trim();

        if (!id) {
            showToast('Please select a vaccine to adjust.', 'warning');
            return;
        }

        if (!isValidInventoryInteger(qty, 1)) {
            showToast('Adjustment quantity must be at least 1.', 'warning');
            return;
        }

        const payload = {
            id: Number(id),
            adjustment_type: type,
            quantity: Number(qty),
            reason: reason || 'Manual Stock Adjustment'
        };

        const submitBtn = document.getElementById('btnSubmitAdjust');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Adjusting...';
        }

        try {
            const res = await fetch('<?php echo site_url('api/inventory.php?action=adjust'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeModal('adjustStockModal');
                showToast('✅ Stock adjusted successfully!', 'success');
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(data.message || 'Failed to adjust stock.', 'danger');
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-check mr-1.5"></i> Apply Adjustment';
                }
            }
        } catch (err) {
            console.error('Adjust stock error:', err);
            showToast('Error sending stock adjustment to server.', 'danger');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-check mr-1.5"></i> Apply Adjustment';
            }
        }
    }

    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
    let toastTimer = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
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
    const searchInput = document.getElementById('searchVaccine');
    const filterStatus = document.getElementById('filterStatus');
    const filterLocation = document.getElementById('filterLocation');

    if (searchInput) searchInput.addEventListener('input', filterInventory);
    if (filterStatus) filterStatus.addEventListener('change', filterInventory);
    if (filterLocation) filterLocation.addEventListener('change', filterInventory);
    document.getElementById('filterDateFrom').addEventListener('change', filterInventory);
    document.getElementById('filterDateTo').addEventListener('change', filterInventory);

    function filterInventory() {
        const search = document.getElementById('searchVaccine').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const location = document.getElementById('filterLocation').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.inventory-row').forEach(row => {
            const name = row.dataset.name || '';
            const batch = row.dataset.batch || '';
            const supplier = row.dataset.supplier || '';
            const rowStatus = row.dataset.status || '';
            const rowLocation = row.dataset.location || '';
            const expiryDate = row.dataset.expiryDate || '';

            const matchesSearch = name.includes(search) || batch.includes(search) || supplier.includes(search);
            
            let matchesStatus = true;
            if (status === 'expiring') {
                const daysLeft = expiryDate ? (new Date(expiryDate + 'T00:00:00') - new Date()) / 86400000 : -1;
                matchesStatus = daysLeft >= 0 && daysLeft <= 30 && rowStatus !== 'out_of_stock';
            } else if (status === 'alert') {
                matchesStatus = rowStatus === 'low_stock' || rowStatus === 'critical' || rowStatus === 'out_of_stock';
            } else {
                matchesStatus = !status || rowStatus === status;
            }
            
            const matchesLocation = !location || rowLocation === location;
            const matchesDateFrom = !dateFrom || (expiryDate && expiryDate >= dateFrom);
            const matchesDateTo = !dateTo || (expiryDate && expiryDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesLocation && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        const emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'flex' : 'none';
        }
    }

    function resetFilters() {
        document.getElementById('searchVaccine').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterLocation').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.inventory-row').forEach(row => row.style.display = '');
        const emptyState = document.getElementById('emptyState');
        if (emptyState) emptyState.style.display = 'none';
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
    // SET DEFAULT DATE
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const expiryDate = document.getElementById('stock_expiry');
        if (expiryDate) {
            const date = new Date();
            date.setFullYear(date.getFullYear() + 2);
            expiryDate.value = date.toISOString().split('T')[0];
        }
        const tempInput = document.getElementById('stock_temp');
        if (tempInput) {
            tempInput.value = 2.5;
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>