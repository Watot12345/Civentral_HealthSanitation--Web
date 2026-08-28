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

// AJAX API Endpoint Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF token validation failed.']);
        exit;
    }
    $action = $_POST['action'] ?? '';
    echo json_encode(['success' => true, 'action' => $action, 'message' => 'Septic tank action processed successfully.']);
    exit;
}

require_once __DIR__ . '/../../app/Models/SepticTank.php';
$septicTankModel = new SepticTank();

// Fetch live Septic Tanks from Supabase
$septicTanks = $septicTankModel->all();

// Stats
$counts = $septicTankModel->countByStatus();
$totalTanks = $counts['total'];
$goodStatus = $counts['good'];
$needsMaintenance = $counts['needs_maintenance'];
$criticalStatus = $counts['critical'];

$title = 'Septic Tank Registry';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Septic Tank Registry</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage septic tank registrations, details & maintenance history</p>
        </div>
        <div class="flex gap-3">
            <button onclick="exportTanksCSV()"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2"
                    title="Export to CSV" aria-label="Export septic tanks to CSV">
                <i class="fa-solid fa-file-csv text-xs"></i> Export
            </button>
            <button onclick="openModal('registerTankModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-plus text-xs"></i> Register Tank
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Tanks -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-water text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalTanks; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Tanks</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">🪣 All tanks</span>
                    <span class="text-[10px] text-slate-400"><?php echo $goodStatus; ?> in good condition</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Good Status -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $goodStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Good Status</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Healthy</span>
                    <span class="text-[10px] text-slate-400">No issues detected</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Needs Maintenance -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-tools text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $needsMaintenance; ?></p>
                        <p class="text-xs font-medium text-slate-500">Needs Maintenance</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">🔧 Attention</span>
                    <span class="text-[10px] text-slate-400">Action required</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Critical -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $criticalStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Critical</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
                    <span class="text-[10px] text-slate-400">Immediate response</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Alert -->
    <?php if ($criticalStatus > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo $criticalStatus; ?></span> septic tank(s) require immediate attention
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='critical'; filterTanks();" 
                class="text-xs font-semibold text-rose-700 hover:text-rose-900 underline">
            View critical
        </button>
    </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchTank"
                       placeholder="Search by tank ID, owner, or address..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="good">Good</option>
                    <option value="needs_maintenance">Needs Maintenance</option>
                    <option value="critical">Critical</option>
                </select>
                <select id="filterType" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Types</option>
                    <option value="Concrete">Concrete</option>
                    <option value="Plastic">Plastic</option>
                    <option value="Fiberglass">Fiberglass</option>
                </select>
                <select id="filterZone" onchange="onFilterZoneChange()" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Zones</option>
                    <option value="Zone 1">Zone 1 (Brgy 1–4)</option>
                    <option value="Zone 7">Zone 7 (Brgy 77–81)</option>
                    <option value="Zone 8">Zone 8 (Brgy 82–85)</option>
                    <option value="Zone 12">Zone 12 (Brgy 132–140)</option>
                    <option value="Zone 13">Zone 13 (Brgy 141–150)</option>
                    <option value="Zone 14">Zone 14 (Brgy 151–160)</option>
                    <option value="Zone 15">Zone 15 (Brgy 161–164)</option>
                </select>
                <select id="filterBarangay" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Barangays</option>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Maintenance date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Maintenance date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Tanks Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="tanksGrid">
        <?php foreach ($septicTanks as $tank): ?>
        <div class="tank-card bg-white rounded-xl shadow-xs border border-slate-200 p-4 hover:shadow-md transition-all duration-200 <?php echo $tank['status'] === 'critical' ? 'border-l-4 border-l-rose-500' : ($tank['status'] === 'needs_maintenance' ? 'border-l-4 border-l-amber-500' : 'border-l-4 border-l-emerald-500'); ?>"
             data-owner="<?php echo htmlspecialchars(strtolower($tank['owner_name']), ENT_QUOTES, 'UTF-8'); ?>"
             data-id="<?php echo htmlspecialchars($tank['tank_id'], ENT_QUOTES, 'UTF-8'); ?>"
             data-row-id="<?php echo (int)$tank['id']; ?>"
             data-status="<?php echo htmlspecialchars($tank['status'], ENT_QUOTES, 'UTF-8'); ?>"
             data-type="<?php echo htmlspecialchars($tank['type'], ENT_QUOTES, 'UTF-8'); ?>"
             data-barangay="<?php echo htmlspecialchars($tank['barangay'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             data-maintenance-date="<?php echo htmlspecialchars($tank['last_maintenance'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
             id="tank-card-<?php echo (int)$tank['id']; ?>">
            
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                        <?php echo strtoupper(substr($tank['owner_name'], 0, 2)); ?>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm"><?php echo $tank['owner_name']; ?></p>
                        <p class="text-xs text-slate-400"><?php echo $tank['tank_id']; ?></p>
                    </div>
                </div>
                <?php
                    $statusColors = [
                        'good' => 'bg-emerald-100 text-emerald-700',
                        'needs_maintenance' => 'bg-amber-100 text-amber-700',
                        'critical' => 'bg-rose-100 text-rose-700'
                    ];
                ?>
                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$tank['status']] ?? $statusColors['good']; ?>">
                    <?php echo str_replace('_', ' ', ucfirst($tank['status'])); ?>
                </span>
            </div>
            
            <!-- Details -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Address</span>
                    <span class="text-slate-800 text-xs text-right"><?php echo $tank['address']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Type</span>
                    <span class="text-slate-800 text-xs"><?php echo $tank['type']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Capacity</span>
                    <span class="text-slate-800 text-xs font-semibold"><?php echo $tank['capacity']; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Last Maintenance</span>
                    <span class="text-slate-800 text-xs"><?php echo !empty($tank['last_maintenance']) ? date('M d, Y', strtotime($tank['last_maintenance'])) : '<span class="text-slate-400 italic">None / Unscheduled</span>'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Barangay</span>
                    <span class="text-slate-800 text-xs"><?php echo str_replace('Barangay ', '', $tank['barangay']); ?></span>
                </div>
            </div>
            
            <!-- Location -->
            <div class="mt-2 pt-2 border-t border-slate-100 flex items-center gap-2 text-xs text-slate-400">
                <?php
                    $lat = (!empty($tank['latitude']) && is_numeric($tank['latitude'])) ? (float)$tank['latitude'] : 14.6538;
                    $lng = (!empty($tank['longitude']) && is_numeric($tank['longitude'])) ? (float)$tank['longitude'] : 120.9820;
                    $ownerJs = json_encode($tank['owner_name'] ?? 'Septic Tank');
                ?>
                <span><?php echo htmlspecialchars((string)($tank['latitude'] ?? '14.6538')); ?>, <?php echo htmlspecialchars((string)($tank['longitude'] ?? '120.9820')); ?></span>
                <button onclick="viewMap(<?php echo $lat; ?>, <?php echo $lng; ?>, <?php echo htmlspecialchars($ownerJs, ENT_QUOTES, 'UTF-8'); ?>)" 
                        class="ml-auto text-brand-medium hover:text-brand-dark transition">
                    <i class="fa-solid fa-map"></i> View Map
                </button>
            </div>
            
            <!-- Actions -->
            <div class="mt-3 pt-3 border-t border-slate-100 flex justify-end gap-2">
                <button onclick="viewTank(<?php echo $tank['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-brand-medium hover:bg-brand-light rounded-lg transition">
                    <i class="fa-solid fa-eye mr-1"></i> View
                </button>
                <button onclick="editTank(<?php echo $tank['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-lg transition">
                    <i class="fa-solid fa-pen mr-1"></i> Edit
                </button>
                <button onclick="viewHistory(<?php echo $tank['id']; ?>)"
                        class="px-3 py-1.5 text-xs font-semibold text-blue-600 hover:bg-blue-50 rounded-lg transition">
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i> History
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty state -->
    <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
            <i class="fa-solid fa-water text-slate-400"></i>
        </div>
        <p class="text-sm font-semibold text-slate-600">No tanks match your filters</p>
        <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
        <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
    </div>

    <!-- Pagination -->
    <div class="mt-4 px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-white rounded-xl shadow-xs">
        <p class="text-xs text-slate-500">
            Showing <span class="font-semibold text-slate-700">1</span> to
            <span class="font-semibold text-slate-700"><?php echo $totalTanks; ?></span> of
            <span class="font-semibold text-slate-700"><?php echo $totalTanks; ?></span> tanks
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

<!-- ============================================================ -->
<!-- REGISTER TANK MODAL                                          -->
<!-- ============================================================ -->
<div id="registerTankModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-plus text-brand-medium"></i>
                Register Septic Tank
            </h3>
            <button onclick="closeModal('registerTankModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="registerTankForm" class="p-6 space-y-4" onsubmit="saveTankRegistration(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label>
                <input type="text" id="tank_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                <input type="text" id="tank_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <!-- HIERARCHICAL ZONE & BARANGAY SELECTION -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Zone</label>
                    <select id="tank_zone" onchange="onZoneChange('tank_zone', 'tank_barangay')" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">All Zones (Select Zone)</option>
                        <option value="Zone 1">Zone 1 (Brgy 1 to 4)</option>
                        <option value="Zone 7">Zone 7 (Brgy 77 to 81)</option>
                        <option value="Zone 8">Zone 8 (Brgy 82 to 85)</option>
                        <option value="Zone 12">Zone 12 (Brgy 132 to 140)</option>
                        <option value="Zone 13">Zone 13 (Brgy 141 to 150)</option>
                        <option value="Zone 14">Zone 14 (Brgy 151 to 160)</option>
                        <option value="Zone 15">Zone 15 (Brgy 161 to 164)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay <span class="text-rose-500">*</span></label>
                    <select id="tank_barangay" onchange="onBarangayChange('tank_barangay', 'tank_zone')" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <!-- Populated dynamically in ascending order -->
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Capacity</label>
                    <select id="tank_capacity" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="500L">500L</option>
                        <option value="800L">800L</option>
                        <option value="1000L">1000L</option>
                        <option value="1200L" selected>1200L</option>
                        <option value="1500L">1500L</option>
                        <option value="2000L">2000L</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Type</label>
                    <select id="tank_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="Concrete">Concrete</option>
                        <option value="Plastic">Plastic</option>
                        <option value="Fiberglass">Fiberglass</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Installation Year</label>
                <input type="number" id="tank_year" min="2000" max="<?php echo date('Y'); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Maintenance Frequency (months)</label>
                    <select id="tank_frequency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="6">6 months</option>
                        <option value="12" selected>12 months</option>
                        <option value="18">18 months</option>
                        <option value="24">24 months</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="tank_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="good">Good</option>
                        <option value="needs_maintenance">Needs Maintenance</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                <textarea id="tank_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional notes..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('registerTankModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-plus mr-1.5"></i> Register
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT TANK MODAL                                              -->
<!-- ============================================================ -->
<div id="editTankModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Septic Tank</h3>
            <button type="button" onclick="closeModal('editTankModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveTankEdit(event)">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" id="edit_tank_id">
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Owner Name</label><input type="text" id="edit_tank_owner" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label><input type="text" id="edit_tank_address" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
            <!-- HIERARCHICAL ZONE & BARANGAY SELECTION -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Zone</label>
                    <select id="edit_tank_zone" onchange="onZoneChange('edit_tank_zone', 'edit_tank_barangay')" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">All Zones (Select Zone)</option>
                        <option value="Zone 1">Zone 1 (Brgy 1 to 4)</option>
                        <option value="Zone 7">Zone 7 (Brgy 77 to 81)</option>
                        <option value="Zone 8">Zone 8 (Brgy 82 to 85)</option>
                        <option value="Zone 12">Zone 12 (Brgy 132 to 140)</option>
                        <option value="Zone 13">Zone 13 (Brgy 141 to 150)</option>
                        <option value="Zone 14">Zone 14 (Brgy 151 to 160)</option>
                        <option value="Zone 15">Zone 15 (Brgy 161 to 164)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay <span class="text-rose-500">*</span></label>
                    <select id="edit_tank_barangay" onchange="onBarangayChange('edit_tank_barangay', 'edit_tank_zone')" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <!-- Populated dynamically in ascending order -->
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Capacity</label><select id="edit_tank_capacity" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option>500L</option><option>800L</option><option>1000L</option><option>1200L</option><option>1500L</option><option>2000L</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Type</label><select id="edit_tank_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option>Concrete</option><option>Plastic</option><option>Fiberglass</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Maintenance</label><input type="date" id="edit_tank_maintenance" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Frequency (months)</label><select id="edit_tank_frequency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="6">6 months</option><option value="12">12 months</option><option value="18">18 months</option><option value="24">24 months</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_tank_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="good">Good</option><option value="needs_maintenance">Needs Maintenance</option><option value="critical">Critical</option></select></div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label><textarea id="edit_tank_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editTankModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW TANK MODAL                                              -->
<!-- ============================================================ -->
<div id="viewTankModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Septic Tank Details</h3>
            <button onclick="closeModal('viewTankModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="tankDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- HISTORY MODAL                                                -->
<!-- ============================================================ -->
<div id="historyModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-brand-medium"></i>
                Tank History
            </h3>
            <button onclick="closeModal('historyModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="historyContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MAP MODAL                                                    -->
<!-- ============================================================ -->
<div id="mapModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-map text-brand-medium"></i>
                Location Map
            </h3>
            <button onclick="closeModal('mapModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="mapContent" class="p-6">
            <div class="mb-3 flex justify-between items-center text-xs text-slate-500">
                <span id="mapLocation" class="font-semibold text-slate-700">Location map</span>
                <span id="mapCoordinates" class="font-mono text-slate-400">Lat: 0, Lng: 0</span>
            </div>
            <div id="leafletMap" class="w-full h-80 rounded-xl border border-slate-200 shadow-inner z-10"></div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- Leaflet CSS & JS for Interactive Map -->
<link rel="stylesheet" href="<?= site_url('assets/css/leaflet.css'); ?>" />
<script src="<?= site_url('assets/js/leaflet.js'); ?>"></script>
<script src="<?= site_url('assets/js/common.js'); ?>"></script>
<script>
    const TANKS = <?php echo json_encode(array_column($septicTanks, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

    // Modal functions, toast, sanitizeHTML provided by common.js

    // ============================================================
    // VIEW TANK
    // ============================================================
    function viewTank(id) {
        openModal('viewTankModal');
        const t = TANKS[id];
        if (!t) return;

        setTimeout(() => {
            const statusColors = {
                good: 'bg-emerald-100 text-emerald-700',
                needs_maintenance: 'bg-amber-100 text-amber-700',
                critical: 'bg-rose-100 text-rose-700'
            };
            // Use sanitizeHTML() to prevent XSS
            const tOwner = sanitizeHTML(t.owner_name);
            const tTankId = sanitizeHTML(t.tank_id);
            const tType = sanitizeHTML(t.type);
            const tAddress = sanitizeHTML(t.address);
            const tBarangay = sanitizeHTML(t.barangay);
            const tCap = sanitizeHTML(t.capacity);
            const tNotes = sanitizeHTML(t.notes);
            const tStatus = sanitizeHTML(t.status);
            const currentYear = new Date().getFullYear();
            const tankAge = t.installation_year ? (currentYear - t.installation_year) + ' yrs old' : 'N/A';

            const historyHtml = (t.history || []).map(h => `
                <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${sanitizeHTML(h.type)}</p>
                        <p class="text-xs text-slate-500">${sanitizeHTML(h.notes)}</p>
                    </div>
                    <span class="text-xs text-slate-400">${new Date(h.date).toLocaleDateString()}</span>
                </div>
            `).join('');

            document.getElementById('tankDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${tOwner.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${tOwner}</h4>
                            <p class="text-sm text-slate-500">${tTankId} &bull; ${tType}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[t.status] || statusColors.good}">
                                ${tStatus.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Address</p><p class="text-sm text-slate-800">${tAddress}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Barangay</p><p class="text-sm text-slate-800">${tBarangay}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Capacity</p><p class="text-sm text-slate-800 font-bold">${tCap}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Type & Age</p><p class="text-sm text-slate-800">${tType} (${tankAge})</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Installed Year</p><p class="text-sm text-slate-800">${t.installation_year || 'N/A'}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Last Maintenance</p><p class="text-sm text-slate-800">${t.last_maintenance ? new Date(t.last_maintenance).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '<span class="text-slate-400 italic">None / Unscheduled</span>'}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Frequency</p><p class="text-sm text-slate-800">Every ${t.maintenance_frequency} months</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Location</p><p class="text-sm text-slate-800 font-mono text-xs">${t.latitude}, ${t.longitude}</p></div>
                    </div>
                    ${tNotes ? `<div class="bg-slate-50 rounded-xl p-4 border border-slate-200"><h5 class="text-sm font-bold text-slate-700 mb-2">Notes</h5><p class="text-sm text-slate-800">${tNotes}</p></div>` : ''}
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">🔄 Maintenance History</h5>
                        <div class="space-y-2">${historyHtml || '<p class="text-xs text-slate-400">No history available</p>'}</div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewTankModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        <button onclick="scheduleServiceForTank('${sanitizeAttr(t.tank_id)}')" class="px-4 py-2 bg-brand-medium text-white rounded-lg hover:bg-brand-dark transition text-sm font-semibold"><i class="fa-solid fa-calendar-plus mr-1.5"></i> Schedule Service</button>
                        <button onclick="closeModal('viewTankModal'); viewMap(${Number(t.latitude) || 14.6538}, ${Number(t.longitude) || 120.9820}, ${JSON.stringify(t.owner_name || 'Septic Tank')})" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold"><i class="fa-solid fa-map mr-1.5"></i> View Map</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    function scheduleServiceForTank(tankId) {
        window.location.href = `maintenance.php?tank_id=${encodeURIComponent(tankId)}`;
    }

    // ============================================================
    // VIEW HISTORY
    // ============================================================
    function viewHistory(id) {
        openModal('historyModal');
        const t = TANKS[id];
        if (!t) return;

        setTimeout(() => {
            const tOwner = sanitizeHTML(t.owner_name);
            const tTankId = sanitizeHTML(t.tank_id);
            const historyHtml = (t.history || []).map(h => `
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${sanitizeHTML(h.type)}</p>
                        <p class="text-xs text-slate-500">${sanitizeHTML(h.notes)}</p>
                    </div>
                    <span class="text-xs text-slate-400">${new Date(h.date).toLocaleDateString()}</span>
                </div>
            `).join('');

            document.getElementById('historyContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                        <div>
                            <p class="font-semibold text-slate-800 text-sm">${tOwner}</p>
                            <p class="text-xs text-slate-400">${tTankId}</p>
                        </div>
                        <span class="ml-auto text-xs text-slate-500">${(t.history || []).length} records</span>
                    </div>
                    <div class="space-y-2">${historyHtml || '<p class="text-xs text-slate-400">No history records.</p>'}</div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // CALOOCAN DISTRICT 1 ZONES & BARANGAYS CONFIGURATION
    // ============================================================
    const CALOOCAN_ZONES = {
        'Zone 1': [1, 2, 3, 4],
        'Zone 7': [77, 78, 79, 80, 81],
        'Zone 8': [82, 83, 84, 85],
        'Zone 12': [132, 133, 134, 135, 136, 137, 138, 139, 140],
        'Zone 13': [141, 142, 143, 144, 145, 146, 147, 148, 149, 150],
        'Zone 14': [151, 152, 153, 154, 155, 156, 157, 158, 159, 160],
        'Zone 15': [161, 162, 163, 164]
    };

    function getZoneForBarangay(barangayName) {
        if (!barangayName) return '';
        const match = String(barangayName).match(/\b(\d{1,3})\b/);
        if (!match) return '';
        const num = parseInt(match[1]);
        for (const [zone, brgys] of Object.entries(CALOOCAN_ZONES)) {
            if (brgys.includes(num)) return zone;
        }
        return '';
    }

    function populateBarangayDropdown(selectId, targetZone = '', selectedValue = '') {
        const select = document.getElementById(selectId);
        if (!select) return;
        
        select.innerHTML = '<option value="">' + (selectId.startsWith('filter') ? 'All Barangays' : 'Select Barangay') + '</option>';
        
        if (targetZone && CALOOCAN_ZONES[targetZone]) {
            const brgys = CALOOCAN_ZONES[targetZone];
            brgys.forEach(num => {
                const val = `Barangay ${num}`;
                const opt = document.createElement('option');
                opt.value = val;
                opt.textContent = `Barangay ${num}`;
                if (val === selectedValue) opt.selected = true;
                select.appendChild(opt);
            });
        } else {
            // Show all grouped by zone in ascending numerical order
            for (const [zone, brgys] of Object.entries(CALOOCAN_ZONES)) {
                const group = document.createElement('optgroup');
                group.label = `${zone} (Brgy ${brgys[0]}–${brgys[brgys.length-1]})`;
                brgys.forEach(num => {
                    const val = `Barangay ${num}`;
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = `Barangay ${num}`;
                    if (val === selectedValue) opt.selected = true;
                    group.appendChild(opt);
                });
                select.appendChild(group);
            }
        }
        if (selectedValue) {
            select.value = selectedValue;
        }
    }

    function onZoneChange(zoneSelectId, barangaySelectId) {
        const zoneSelect = document.getElementById(zoneSelectId);
        const zone = zoneSelect ? zoneSelect.value : '';
        populateBarangayDropdown(barangaySelectId, zone, '');
    }

    function onBarangayChange(barangaySelectId, zoneSelectId) {
        const barangaySelect = document.getElementById(barangaySelectId);
        const zoneSelect = document.getElementById(zoneSelectId);
        if (!barangaySelect || !zoneSelect) return;
        
        const zone = getZoneForBarangay(barangaySelect.value);
        if (zone && zoneSelect.value !== zone) {
            zoneSelect.value = zone;
        }
    }

    function onFilterZoneChange() {
        const filterZone = document.getElementById('filterZone')?.value || '';
        populateBarangayDropdown('filterBarangay', filterZone, '');
        filterTanks();
    }

    // Initialize all barangay dropdowns on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
        populateBarangayDropdown('filterBarangay');
        populateBarangayDropdown('tank_barangay');
        populateBarangayDropdown('edit_tank_barangay');
    });

    // ============================================================
    // VIEW MAP
    // ============================================================
    // Leaflet map instance
    let _leafletMapInstance = null;
    let _leafletMarker = null;

    function viewMap(lat, lng, owner) {
        const safeLat = (lat !== undefined && lat !== null && !isNaN(Number(lat))) ? Number(lat) : 14.6538;
        const safeLng = (lng !== undefined && lng !== null && !isNaN(Number(lng))) ? Number(lng) : 120.9820;
        const safeOwner = (owner || 'Septic Tank');

        document.getElementById('mapLocation').textContent = sanitizeHTML(safeOwner) + "'s Septic Tank Location";
        document.getElementById('mapCoordinates').textContent = 'Lat: ' + safeLat.toFixed(4) + ', Lng: ' + safeLng.toFixed(4);
        openModal('mapModal');

        setTimeout(() => {
            if (typeof L === 'undefined') return;
            const container = document.getElementById('leafletMap');
            if (!container) return;

            // Custom Vector HTML / SVG Pin - Completely avoids broken external PNG 404 images
            const customPinIcon = L.divIcon({
                className: 'custom-septic-marker',
                html: `<div style="width:36px; height:36px; background:linear-gradient(135deg, #0B4F4A, #14807A); border:3px solid #ffffff; border-radius:50%; box-shadow:0 4px 12px rgba(11,79,74,0.45); display:flex; align-items:center; justify-content:center; color:#ffffff; font-size:15px; transform:translate(-2px, -2px);"><i class="fa-solid fa-location-dot"></i></div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 18],
                popupAnchor: [0, -20]
            });

            if (!_leafletMapInstance) {
                _leafletMapInstance = L.map('leafletMap').setView([safeLat, safeLng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(_leafletMapInstance);
                _leafletMarker = L.marker([safeLat, safeLng], { icon: customPinIcon }).addTo(_leafletMapInstance);
            } else {
                _leafletMapInstance.setView([safeLat, safeLng], 15);
                if (_leafletMarker) {
                    _leafletMarker.setIcon(customPinIcon);
                    _leafletMarker.setLatLng([safeLat, safeLng]);
                } else {
                    _leafletMarker = L.marker([safeLat, safeLng], { icon: customPinIcon }).addTo(_leafletMapInstance);
                }
            }
            if (_leafletMarker) {
                _leafletMarker.bindPopup(`
                    <div style="font-family:inherit; padding:4px;">
                        <strong style="color:#0B4F4A; font-size:13px;">${sanitizeHTML(safeOwner)}</strong><br>
                        <span style="font-size:11px; color:#64748b;">Septic Tank Verified Location</span>
                    </div>
                `).openPopup();
            }
            _leafletMapInstance.invalidateSize();
        }, 200);
    }

    // ============================================================
    // EDIT TANK
    // ============================================================
    function editTank(id) {
        const tank = TANKS[id];
        if (!tank) return;
        document.getElementById('edit_tank_id').value = tank.id;
        document.getElementById('edit_tank_owner').value = tank.owner_name;
        document.getElementById('edit_tank_address').value = tank.address;
        
        const zone = getZoneForBarangay(tank.barangay);
        document.getElementById('edit_tank_zone').value = zone;
        populateBarangayDropdown('edit_tank_barangay', zone, tank.barangay);

        document.getElementById('edit_tank_capacity').value = tank.capacity;
        document.getElementById('edit_tank_type').value = tank.type;
        document.getElementById('edit_tank_maintenance').value = tank.last_maintenance;
        document.getElementById('edit_tank_frequency').value = tank.maintenance_frequency;
        document.getElementById('edit_tank_status').value = tank.status;
        document.getElementById('edit_tank_notes').value = tank.notes || '';
        openModal('editTankModal');
    }

    async function saveTankEdit(event) {
        event.preventDefault();
        try {
            const id = document.getElementById('edit_tank_id').value;
            const payload = {
                owner_name: document.getElementById('edit_tank_owner').value.trim(),
                address: document.getElementById('edit_tank_address').value.trim(),
                barangay: document.getElementById('edit_tank_barangay').value,
                capacity: document.getElementById('edit_tank_capacity').value,
                type: document.getElementById('edit_tank_type').value,
                last_maintenance: document.getElementById('edit_tank_maintenance').value,
                maintenance_frequency: Number(document.getElementById('edit_tank_frequency').value),
                status: document.getElementById('edit_tank_status').value,
                notes: document.getElementById('edit_tank_notes').value.trim()
            };

            const res = await fetch(`../../api/septic_tanks.php?id=${id}&action=update`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                closeModal('editTankModal');
                showToast('Septic tank updated successfully!', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to update tank', 'danger');
            }
        } catch (err) {
            console.error('saveTankEdit error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // REGISTER TANK
    // ============================================================
    async function saveTankRegistration(event) {
        event.preventDefault();
        try {
            const owner = document.getElementById('tank_owner').value.trim();
            const address = document.getElementById('tank_address').value.trim();
            const barangay = document.getElementById('tank_barangay').value;
            if (!owner || !address || !barangay) {
                showToast('Owner name, address, and barangay are required.', 'warning');
                return;
            }
            const payload = {
                owner_name: owner,
                address: address,
                barangay: barangay,
                capacity: document.getElementById('tank_capacity').value,
                type: document.getElementById('tank_type').value,
                installation_year: document.getElementById('tank_year')?.value ? Number(document.getElementById('tank_year').value) : null,
                maintenance_frequency: Number(document.getElementById('tank_frequency').value),
                status: document.getElementById('tank_status').value,
                notes: document.getElementById('tank_notes')?.value?.trim() || ''
            };

            const res = await fetch('../../api/septic_tanks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (json.success) {
                showToast('Septic tank registered successfully!', 'success');
                closeModal('registerTankModal');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message || 'Failed to register tank', 'danger');
            }
        } catch (err) {
            console.error('saveTankRegistration error:', err);
            showToast('An error occurred: ' + err.message, 'danger');
        }
    }

    // Export Septic Tanks to CSV
    function exportTanksCSV() {
        try {
            const headers = ['Tank ID', 'Owner Name', 'Address', 'Barangay', 'Capacity', 'Type', 'Installed Year', 'Last Maintenance', 'Status'];
            const rows = Object.values(TANKS).map(t => [
                t.tank_id, t.owner_name, t.address, t.barangay,
                t.capacity, t.type, t.installation_year, t.last_maintenance, t.status
            ]);
            const csvContent = [headers, ...rows].map(r =>
                r.map(v => '"' + String(v ?? '').replace(/"/g, '""') + '"').join(',')
            ).join('\n');
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'septic_tanks_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
            showToast('Exported to ' + a.download, 'success');
        } catch (err) {
            showToast('Export failed: ' + err.message, 'danger');
        }
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchTank').addEventListener('input', filterTanks);
    document.getElementById('filterStatus').addEventListener('change', filterTanks);
    document.getElementById('filterType').addEventListener('change', filterTanks);
    document.getElementById('filterBarangay').addEventListener('change', filterTanks);
    document.getElementById('filterDateFrom').addEventListener('change', filterTanks);
    document.getElementById('filterDateTo').addEventListener('change', filterTanks);

    function filterTanks() {
        const search = document.getElementById('searchTank').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const type = document.getElementById('filterType').value;
        const zone = document.getElementById('filterZone')?.value || '';
        const barangay = document.getElementById('filterBarangay').value;
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.tank-card').forEach(card => {
            const owner = card.dataset.owner;
            const id = card.dataset.id.toLowerCase();
            const cardStatus = card.dataset.status;
            const cardType = card.dataset.type;
            const cardBarangay = card.dataset.barangay;
            const maintenanceDate = card.dataset.maintenanceDate || '';

            const matchesSearch = owner.includes(search) || id.includes(search);
            const matchesStatus = !status || cardStatus === status;
            const matchesType = !type || cardType === type;
            const matchesZone = !zone || getZoneForBarangay(cardBarangay) === zone;
            const matchesBarangay = !barangay || cardBarangay === barangay || cardBarangay.includes(barangay);
            const matchesDateFrom = !dateFrom || (maintenanceDate && maintenanceDate >= dateFrom);
            const matchesDateTo = !dateTo || (maintenanceDate && maintenanceDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesType && matchesZone && matchesBarangay && matchesDateFrom && matchesDateTo;

            card.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchTank').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterType').value = '';
        if (document.getElementById('filterZone')) document.getElementById('filterZone').value = '';
        populateBarangayDropdown('filterBarangay');
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.tank-card').forEach(card => card.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    // ESC key and backdrop-click handled by common.js

    // ESC key and backdrop-click handled by common.js
</script>

<?php include_once '../../includes/footer.php'; ?>