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
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/Child.php';
require_once __DIR__ . '/../../includes/data-mask.php';
require_once __DIR__ . '/../../includes/toast.php';

// Constants
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 5;

// Shared nutrition badge color map.
// Defined ONCE here (previously redeclared on every loop iteration) and
// also echoed as JSON below so the JS side reuses the exact same map
// instead of keeping a second, hand-duplicated copy in <script>.
$nutritionColors = [
    'Normal'      => 'bg-emerald-100 text-emerald-700',
    'Moderate'    => 'bg-amber-100 text-amber-700',
    'Critical'    => 'bg-rose-100 text-rose-700',
    'Overweight'  => 'bg-blue-100 text-blue-700',
];

// Initialize model
$childModel = new Child();

// Get statistics from model
$stats = $childModel->getStats();

// Pagination logic
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : DEFAULT_PAGE;
$limit = DEFAULT_LIMIT;
$offset = ($page - 1) * $limit;

// Get paginated children from model
$children = $childModel->search([], $limit, $offset);
$totalChildren = $stats['total'];
$totalPages = max(1, ceil($totalChildren / $limit));

// Stats for display
$totalChildrenCount = $stats['total'];
$activeChildren = $stats['active'];
$criticalNutrition = $stats['critical_nutrition'];
$normalNutrition = $stats['normal_nutrition'];
$vaccineCompliant = $stats['vaccine_compliant'];

$title = 'Child Records';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Child Records</h2>
            <p class="text-sm text-slate-500 mt-0.5">Manage child registration, demographics & health records</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('registerChildModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-child text-xs"></i> Register Child
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Children -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-child text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalChildrenCount; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Children</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">👶 All children</span>
                    <span class="text-[10px] text-slate-400"><?php echo $activeChildren; ?> active</span>
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
                        <p class="text-2xl font-black text-emerald-600"><?php echo $activeChildren; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Enrolled</span>
                    <span class="text-[10px] text-slate-400">Regular checkups</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Critical Nutrition -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $criticalNutrition; ?></p>
                        <p class="text-xs font-medium text-slate-500">Critical Nutrition</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
                    <span class="text-[10px] text-slate-400">Immediate intervention</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Normal Nutrition -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-heart-pulse text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $normalNutrition; ?></p>
                        <p class="text-xs font-medium text-slate-500">Normal Nutrition</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Healthy</span>
                    <span class="text-[10px] text-slate-400">On track</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Vaccine Compliant -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-syringe text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark"><?php echo $vaccineCompliant; ?></p>
                        <p class="text-xs font-medium text-slate-500">Vaccine Compliant</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-brand-light text-brand-dark rounded-full text-[10px] font-bold">💉 Protected</span>
                    <span class="text-[10px] text-slate-400">≥80% compliance</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Nutrition Alert -->
    <?php if ($criticalNutrition > 0 && $totalChildren > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo $criticalNutrition; ?></span> child(ren) with critical nutrition status require immediate attention
            </span>
        </div>
        <button onclick="document.getElementById('filterNutrition').value='Critical'; filterChildren();" 
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
                       id="searchChild"
                       placeholder="Search by name, ID, or mother's name..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterGender" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Genders</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
                <select id="filterNutrition" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Nutrition</option>
                    <option value="Normal">Normal</option>
                    <option value="Moderate">Moderate</option>
                    <option value="Critical">Critical</option>
                    <option value="Overweight">Overweight</option>
                </select>
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Quick Filter Chips -->
    <div class="flex flex-wrap gap-2 mb-4">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide mr-2">Quick Filters:</span>
        <button onclick="quickFilter('gender', 'Male')" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
            <i class="fa-solid fa-mars text-sky-500 mr-1"></i> Male
        </button>
        <button onclick="quickFilter('gender', 'Female')" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
            <i class="fa-solid fa-venus text-pink-500 mr-1"></i> Female
        </button>
        <button onclick="quickFilter('nutrition', 'Critical')" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 mr-1"></i> Critical
        </button>
        <button onclick="quickFilter('status', 'active')" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
            <i class="fa-solid fa-check-circle text-emerald-500 mr-1"></i> Active
        </button>
        <button onclick="quickFilter('status', 'inactive')" class="px-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
            <i class="fa-solid fa-circle-xmark text-slate-400 mr-1"></i> Inactive
        </button>
    </div>

    <!-- Children Table -->
    <div id="tableWrapper" class="<?php echo empty($children) ? 'hidden' : ''; ?>">
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Child ID</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Child Information</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Mother</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nutrition</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vaccine</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="childTableBody">
                    <?php foreach ($children as $child): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors child-row <?php echo $child['nutrition_status'] === 'Critical' ? 'bg-rose-50/50' : ''; ?>"
                        data-name="<?php echo htmlspecialchars(strtolower($child['first_name'] . ' ' . $child['last_name'])); ?>"
                        data-id="<?php echo htmlspecialchars($child['child_id']); ?>"
                        data-mother="<?php echo htmlspecialchars(strtolower($child['mother_name'])); ?>"
                        data-status="<?php echo htmlspecialchars($child['status']); ?>"
                        data-gender="<?php echo htmlspecialchars($child['gender']); ?>"
                        data-nutrition="<?php echo htmlspecialchars($child['nutrition_status']); ?>"
                        data-barangay="<?php echo htmlspecialchars(strtolower($child['barangay'])); ?>">
                        <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold maskable" data-real="<?php echo htmlspecialchars($child['child_id']); ?>"><?php echo htmlspecialchars($child['child_id']); ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                                    <?php echo strtoupper(substr($child['first_name'], 0, 1) . substr($child['last_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm maskable" data-real="<?php echo htmlspecialchars($child['first_name'] . ' ' . ($child['middle_name'] ?? '') . ' ' . $child['last_name']); ?>"><?php echo htmlspecialchars($child['first_name'] . ' ' . ($child['middle_name'] ?? '') . ' ' . $child['last_name']); ?></p>
                                    <p class="text-xs text-slate-400"><?php echo htmlspecialchars($child['age'] ?? '—'); ?> • <?php echo htmlspecialchars($child['barangay']); ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 maskable" data-real="<?php echo htmlspecialchars($child['mother_name']); ?>"><?php echo htmlspecialchars($child['mother_name']); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $nutritionColors[$child['nutrition_status']] ?? $nutritionColors['Normal']; ?>">
                                <?php echo htmlspecialchars($child['nutrition_status'] ?? 'Normal'); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-slate-200 rounded-full h-2 min-w-[60px]">
                                    <div class="h-2 rounded-full <?php echo ($child['vaccine_compliance'] ?? 0) >= 80 ? 'bg-emerald-500' : (($child['vaccine_compliance'] ?? 0) >= 50 ? 'bg-amber-500' : 'bg-rose-500'); ?>" 
                                         style="width: <?php echo (int)($child['vaccine_compliance'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $child['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?>">
                                <?php echo ucfirst($child['status'] ?? 'active'); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewChild(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="editChild(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                                <button onclick="viewVaccination(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Vaccination">
                                    <i class="fa-solid fa-syringe text-sm"></i>
                                </button>
                                <button onclick="viewHealthRecord(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Medical History">
                                    <i class="fa-solid fa-folder-medical text-sm"></i>
                                </button>
                                <button onclick="printChild(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Print">
                                    <i class="fa-solid fa-print text-sm"></i>
                                </button>
                                <button onclick="exportChild(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Export">
                                    <i class="fa-solid fa-download text-sm"></i>
                                </button>
                                <button onclick="archiveChild(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Archive">
                                    <i class="fa-solid fa-archive text-sm"></i>
                                </button>
                                <button onclick="deleteChild(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition" title="Delete">
                                    <i class="fa-solid fa-trash text-sm"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state for no search results -->
        <div id="emptySearchState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                <i class="fa-solid fa-magnifying-glass text-slate-400 text-2xl"></i>
            </div>
            <p class="text-base font-bold text-slate-700 mb-1">No matching child records found.</p>
            <p class="text-sm text-slate-500 mb-4">Try adjusting your search or filters.</p>
            <button onclick="resetFilters()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                <i class="fa-solid fa-rotate-right mr-1.5"></i> Clear Filters
            </button>
        </div>

        <!-- Pagination -->
        <div id="paginationWrapper" class="<?php echo empty($children) ? 'hidden' : ''; ?>">
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700"><?php echo $offset + 1; ?></span> to
                <span class="font-semibold text-slate-700"><?php echo min($offset + $limit, $totalChildren); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalChildren; ?></span> children
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

<!-- Empty state for no records at all -->
<div id="noRecordsState" class="<?php echo empty($children) ? 'flex' : 'hidden'; ?> flex-col items-center justify-center py-16 text-center">
    <div class="w-20 h-20 rounded-full bg-brand-light border-2 border-brand-border flex items-center justify-center mb-5">
        <i class="fa-solid fa-child text-brand-dark text-3xl"></i>
    </div>
    <h3 class="text-xl font-bold text-slate-900 mb-2">No Child Records Found</h3>
    <p class="text-sm text-slate-500 mb-6 max-w-md">There are currently no registered children. Click 'Register Child' to add the first child record.</p>
    <button onclick="openModal('registerChildModal')" class="px-6 py-2.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold shadow-sm">
        <i class="fa-solid fa-child mr-1.5"></i> Register Child
    </button>
</div>

<!-- ============================================================ -->
<!-- REGISTER CHILD MODAL                                         -->
<!-- ============================================================ -->
<div id="registerChildModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-child text-brand-medium"></i>
                Register Child
            </h3>
            <button onclick="closeModal('registerChildModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="registerChildForm" class="p-6 space-y-4" onsubmit="saveChildRegistration(event)">
            <!-- Child Information -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">👶 Child Information</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">First Name</label>
                        <input type="text" id="child_first_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Name</label>
                        <input type="text" id="child_last_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label>
                        <select id="child_gender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Date</label>
                        <input type="date" id="child_birth_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Weight (kg)</label>
                        <input type="number" id="child_birth_weight" step="0.1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Height (cm)</label>
                        <input type="number" id="child_birth_height" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Blood Type</label>
                        <select id="child_blood_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="">Select</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label>
                        <select id="child_barangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Barangay San Jose">Barangay San Jose</option>
                            <option value="Barangay Poblacion">Barangay Poblacion</option>
                            <option value="Barangay Riverside">Barangay Riverside</option>
                            <option value="Barangay San Roque">Barangay San Roque</option>
                            <option value="Barangay Sta. Cruz">Barangay Sta. Cruz</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                        <input type="text" id="child_address" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
            </div>

            <!-- Mother Information -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">👩 Mother Information</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Mother's Name</label>
                        <input type="text" id="child_mother_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                        <input type="text" id="child_mother_contact" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Occupation</label>
                        <input type="text" id="child_mother_occupation" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
            </div>

            <!-- Father Information -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">👨 Father Information</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Father's Name</label>
                        <input type="text" id="child_father_name" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                        <input type="text" id="child_father_contact" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Occupation</label>
                        <input type="text" id="child_father_occupation" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Family History</label>
                <textarea id="child_family_history" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Any family medical history..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Allergies</label>
                <input type="text" id="child_allergies" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="None">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('registerChildModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-child mr-1.5"></i> Register
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW CHILD MODAL                                             -->
<!-- ============================================================ -->
<div id="viewChildModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Child Record Details</h3>
            <button onclick="closeModal('viewChildModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="childDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EDIT CHILD MODAL                                             -->
<!-- ============================================================ -->
<div id="editChildModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pen text-brand-medium"></i>
                Edit Child Record
            </h3>
            <button onclick="closeModal('editChildModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="editChildForm" class="p-6 space-y-4" onsubmit="saveChildEdit(event)">
            <input type="hidden" id="edit_child_id">
            
            <!-- Child Information -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">👶 Child Information</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">First Name</label>
                        <input type="text" id="edit_first_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Middle Name</label>
                        <input type="text" id="edit_middle_name" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Last Name</label>
                        <input type="text" id="edit_last_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Gender</label>
                        <select id="edit_gender" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Date</label>
                        <input type="date" id="edit_birth_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Weight (kg)</label>
                        <input type="number" id="edit_birth_weight" step="0.1" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Height (cm)</label>
                        <input type="number" id="edit_birth_height" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Blood Type</label>
                        <select id="edit_blood_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="">Select</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Barangay</label>
                        <select id="edit_barangay" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Barangay San Jose">Barangay San Jose</option>
                            <option value="Barangay Poblacion">Barangay Poblacion</option>
                            <option value="Barangay Riverside">Barangay Riverside</option>
                            <option value="Barangay San Roque">Barangay San Roque</option>
                            <option value="Barangay Sta. Cruz">Barangay Sta. Cruz</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Address</label>
                        <input type="text" id="edit_address" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
            </div>

            <!-- Mother Information -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">👩 Mother Information</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Mother's Name</label>
                        <input type="text" id="edit_mother_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                        <input type="text" id="edit_mother_contact" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Occupation</label>
                        <input type="text" id="edit_mother_occupation" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
            </div>

            <!-- Father Information -->
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">👨 Father Information</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Father's Name</label>
                        <input type="text" id="edit_father_name" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Contact</label>
                        <input type="text" id="edit_father_contact" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Occupation</label>
                        <input type="text" id="edit_father_occupation" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Family History</label>
                <textarea id="edit_family_history" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Any family medical history..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Allergies</label>
                <input type="text" id="edit_allergies" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="None">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('editChildModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-save mr-1.5"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VACCINATION MODAL                                            -->
<!-- ============================================================ -->
<div id="vaccinationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Vaccination Records</h3>
            <button onclick="closeModal('vaccinationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="vaccinationContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- HEALTH RECORDS MODAL                                         -->
<!-- ============================================================ -->
<div id="healthRecordModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Health Records</h3>
            <button onclick="closeModal('healthRecordModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="healthRecordContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const API_BASE = '<?php echo site_url('api/immunization.php'); ?>';

    // Shared nutrition color map, sourced from the SAME PHP array used to
    // render the initial table (see $nutritionColors above) so there is
    // only ONE place that defines these colors.
    const NUTRITION_COLORS = <?php echo json_encode($nutritionColors); ?>;

    // ============================================================
    // SHARED HELPERS (previously re-declared inside viewChild,
    // printChild, and refreshChildList — now defined once and reused)
    // ============================================================
    const val = (v, fallback = 'Not Provided') =>
        (v === null || v === undefined || v === '' || (typeof v === 'number' && isNaN(v))) ? fallback : v;

    const capitalize = (s) => (s ? s.charAt(0).toUpperCase() + s.slice(1) : s);

    const initials = (first, last) => `${(first || '?').charAt(0)}${(last || '?').charAt(0)}`.toUpperCase();

    const getNutritionClass = (status) => NUTRITION_COLORS[status] || NUTRITION_COLORS.Normal;

    const vaccineBarColor = (pct) => {
        pct = pct || 0;
        return pct >= 80 ? 'bg-emerald-500' : pct >= 50 ? 'bg-amber-500' : 'bg-rose-500';
    };

    const complianceBar = (pct) => {
        pct = pct || 0;
        return `<div class="flex-1 bg-slate-200 rounded-full h-2 min-w-[60px]">
                    <div class="h-2 rounded-full ${vaccineBarColor(pct)}" style="width: ${pct}%"></div>
                </div>`;
    };

    const calculateAge = (birthDate) => {
        if (!birthDate) return 'Not Provided';
        const birth = new Date(birthDate);
        const today = new Date();
        const years = today.getFullYear() - birth.getFullYear();
        const months = today.getMonth() - birth.getMonth();
        const days = today.getDate() - birth.getDate();

        let age = '';
        if (years > 0) age += years + ' yr' + (years > 1 ? 's' : '');
        if (months > 0 || years > 0) age += (age ? ' ' : '') + months + ' mo' + (months > 1 ? 's' : '');
        if (days > 0 && years === 0) age += (age ? ' ' : '') + days + ' day' + (days > 1 ? 's' : '');
        return age || '0 days';
    };

    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Toggle a "hidden"/visible-mode pair on an element in one call
    // (replaces repeated classList.add('hidden') / remove('hidden') pairs).
    function setVisible(el, show, showClass = '') {
        if (!el) return;
        el.classList.toggle('hidden', !show);
        if (showClass) el.classList.toggle(showClass, show);
    }

    // ============================================================
    // MODAL FUNCTIONS - Using ModalSystem
    // ============================================================
    function openModal(id) {
        ModalSystem.open(id);
    }

    function closeModal(id) {
        ModalSystem.close(id);
    }

    // ============================================================
    // FETCH CHILDREN FROM API
    // ============================================================
    async function fetchChildren(page = 1, limit = 5) {
        try {
            const response = await fetch(`${API_BASE}?page=${page}&limit=${limit}`);
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch children');
            }
            return result.data || [];
        } catch (err) {
            console.error('Error fetching children:', err);
            toast.error(err.message || 'Failed to load children');
            return [];
        }
    }

    async function fetchChild(id) {
        try {
            const response = await fetch(`${API_BASE}?id=${id}`);
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch child');
            }
            return result.data;
        } catch (err) {
            console.error('Error fetching child:', err);
            toast.error(err.message || 'Failed to load child details');
            return null;
        }
    }

    // ============================================================
    // VIEW CHILD
    // ============================================================
    async function viewChild(id) {
        const content = document.getElementById('childDetailsContent');
        openModal('viewChildModal');
        content.innerHTML = `
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        `;

        const c = await fetchChild(id);
        if (!c) {
            content.innerHTML = `
                <div class="text-center py-10 text-rose-500">
                    <i class="fa-solid fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load child details</p>
                </div>
            `;
            return;
        }

        content.innerHTML = `
            <div class="space-y-4">
                <!-- Child Information -->
                <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                    <div class="w-16 h-16 rounded-full bg-brand-light border-2 border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0">
                        ${initials(c.first_name, c.last_name)}
                    </div>
                    <div>
                        <h4 class="text-lg font-bold text-slate-900">${escHtml(val(c.first_name))} ${escHtml(val(c.last_name))}</h4>
                        <p class="text-sm text-slate-500">${escHtml(val(c.child_id))} • ${calculateAge(c.birth_date)} • ${escHtml(val(c.barangay))}</p>
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${getNutritionClass(c.nutrition_status)}">
                            ${escHtml(val(c.nutrition_status, 'Normal'))}
                        </span>
                    </div>
                </div>

                <!-- Child Details Section -->
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h5 class="text-sm font-bold text-slate-700 mb-3">👶 Child Information</h5>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-slate-400">Child ID</p><p class="text-sm text-slate-800 font-mono maskable" data-real="${escHtml(val(c.child_id))}">${escHtml(val(c.child_id))}</p></div>
                        <div><p class="text-xs text-slate-400">Gender</p><p class="text-sm text-slate-800">${escHtml(val(c.gender))}</p></div>
                        <div><p class="text-xs text-slate-400">Birth Date</p><p class="text-sm text-slate-800">${c.birth_date ? new Date(c.birth_date).toLocaleDateString() : 'Not Provided'}</p></div>
                        <div><p class="text-xs text-slate-400">Birth Weight</p><p class="text-sm text-slate-800">${c.birth_weight ? c.birth_weight + ' kg' : 'Not Recorded'}</p></div>
                        <div><p class="text-xs text-slate-400">Birth Height</p><p class="text-sm text-slate-800">${c.birth_height ? c.birth_height + ' cm' : 'Not Recorded'}</p></div>
                        <div><p class="text-xs text-slate-400">Blood Type</p><p class="text-sm text-slate-800">${escHtml(val(c.blood_type, 'Unknown'))}</p></div>
                        <div class="sm:col-span-2"><p class="text-xs text-slate-400">Full Name</p><p class="text-sm text-slate-800 maskable" data-real="${escHtml(val(c.first_name) + ' ' + val(c.middle_name, '') + ' ' + val(c.last_name))}">${escHtml(val(c.first_name) + ' ' + val(c.middle_name, '') + ' ' + val(c.last_name))}</p></div>
                        <div class="sm:col-span-2"><p class="text-xs text-slate-400">Health Center</p><p class="text-sm text-slate-800">${escHtml(val(c.health_center))}</p></div>
                    </div>
                </div>

                <!-- Parents Section -->
                <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                    <h5 class="text-sm font-bold text-slate-700 mb-3">👨‍👩‍👧 Parents Information</h5>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-slate-400 font-semibold">Mother</p>
                            <p class="text-sm text-slate-800 maskable" data-real="${escHtml(val(c.mother_name))}">${escHtml(val(c.mother_name))}</p>
                            <p class="text-xs text-slate-400">${escHtml(val(c.mother_occupation, 'N/A'))} • <span class="maskable" data-real="${escHtml(val(c.mother_contact, 'N/A'))}">${escHtml(val(c.mother_contact, 'N/A'))}</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold">Father</p>
                            <p class="text-sm text-slate-800 maskable" data-real="${escHtml(val(c.father_name, 'Not Provided'))}">${escHtml(val(c.father_name, 'Not Provided'))}</p>
                            <p class="text-xs text-slate-400">${escHtml(val(c.father_occupation, 'N/A'))} • <span class="maskable" data-real="${escHtml(val(c.father_contact, 'N/A'))}">${escHtml(val(c.father_contact, 'N/A'))}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Medical Information -->
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h5 class="text-sm font-bold text-slate-700 mb-3">🏥 Medical Information</h5>
                    <div class="space-y-2">
                        <div><p class="text-xs text-slate-400">Family History</p><p class="text-sm text-slate-800 maskable" data-real="${escHtml(val(c.family_history, 'None Reported'))}">${escHtml(val(c.family_history, 'None Reported'))}</p></div>
                        <div><p class="text-xs text-slate-400">Allergies</p><p class="text-sm text-slate-800 maskable" data-real="${escHtml(val(c.allergies, 'None'))}">${escHtml(val(c.allergies, 'None'))}</p></div>
                        <div>
                            <p class="text-xs text-slate-400">Vaccine Compliance</p>
                            <div class="flex items-center gap-2 mt-1">
                                ${complianceBar(c.vaccine_compliance)}
                                <span class="text-sm font-bold">${c.vaccine_compliance || 0}%</span>
                            </div>
                        </div>
                        <div><p class="text-xs text-slate-400">Last Visit</p><p class="text-sm text-slate-800">${c.last_visit ? new Date(c.last_visit).toLocaleDateString() : 'Not Recorded'}</p></div>
                    </div>
                </div>

                <!-- Address -->
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h5 class="text-sm font-bold text-slate-700 mb-2">📍 Address</h5>
                    <p class="text-sm text-slate-800 maskable" data-real="${escHtml(val(c.address))}">${escHtml(val(c.address))}</p>
                    <p class="text-xs text-slate-500 mt-1">${escHtml(val(c.barangay))}</p>
                </div>

                <!-- Registration Details -->
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h5 class="text-sm font-bold text-slate-700 mb-2">📅 Registration Details</h5>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-slate-400">Registration Date</p><p class="text-sm text-slate-800">${c.registration_date ? new Date(c.registration_date).toLocaleDateString() : 'Not Recorded'}</p></div>
                        <div><p class="text-xs text-slate-400">Status</p><p class="text-sm text-slate-800">${capitalize(val(c.status, 'active'))}</p></div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('viewChildModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    <button onclick="closeModal('viewChildModal'); viewHealthRecord(${c.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold">
                        <i class="fa-solid fa-folder-medical mr-1.5"></i> Health Records
                    </button>
                </div>
            </div>
        `;
    }

    // ============================================================
    // EDIT CHILD
    // ============================================================
    const EDIT_FIELD_MAP = {
        first_name: 'edit_first_name', middle_name: 'edit_middle_name', last_name: 'edit_last_name',
        gender: 'edit_gender', birth_date: 'edit_birth_date', birth_weight: 'edit_birth_weight',
        birth_height: 'edit_birth_height', blood_type: 'edit_blood_type', barangay: 'edit_barangay',
        address: 'edit_address', mother_name: 'edit_mother_name', mother_contact: 'edit_mother_contact',
        mother_occupation: 'edit_mother_occupation', father_name: 'edit_father_name',
        father_contact: 'edit_father_contact', father_occupation: 'edit_father_occupation',
        family_history: 'edit_family_history', allergies: 'edit_allergies'
    };

    async function editChild(id) {
        openModal('editChildModal');
        document.getElementById('edit_child_id').value = id;

        // Show loading state
        const form = document.getElementById('editChildForm');
        const originalContent = form.innerHTML;
        form.innerHTML = `
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        `;

        const c = await fetchChild(id);
        if (!c) {
            form.innerHTML = originalContent;
            closeModal('editChildModal');
            toast.error('Failed to load child data');
            return;
        }

        // Restore form and populate fields (single loop instead of 17 repeated lines)
        form.innerHTML = originalContent;
        document.getElementById('edit_child_id').value = c.id;
        document.getElementById('edit_gender').value = c.gender || 'Male';
        for (const [key, elId] of Object.entries(EDIT_FIELD_MAP)) {
            if (key === 'gender') continue; // handled above (needs a default)
            const el = document.getElementById(elId);
            if (el) el.value = c[key] || '';
        }
    }

    // ============================================================
    // VIEW VACCINATION
    // ============================================================
    async function viewVaccination(id) {
        openModal('vaccinationModal');
        const content = document.getElementById('vaccinationContent');
        const c = await fetchChild(id);
        if (!c) {
            content.innerHTML = `
                <div class="text-center py-10 text-rose-500">
                    <i class="fa-solid fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load vaccination records</p>
                </div>
            `;
            return;
        }

        // In production, fetch vaccination records from API
        const vaccinations = [
            { vaccine: 'BCG', date: c.birth_date || '2024-01-15', status: 'completed', next_due: '' },
            { vaccine: 'Hepatitis B', date: c.birth_date || '2024-01-15', status: 'completed', next_due: '' },
            { vaccine: 'DPT', date: '2024-03-15', status: 'completed', next_due: '2024-06-15' },
            { vaccine: 'Polio', date: '2024-03-15', status: 'completed', next_due: '2024-06-15' },
            { vaccine: 'MMR', date: '2024-09-15', status: 'pending', next_due: '2024-09-15' },
        ];

        const vaccinationHtml = vaccinations.map(v => `
            <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full ${v.status === 'completed' ? 'bg-emerald-100 text-emerald-600' : 'bg-amber-100 text-amber-600'} flex items-center justify-center">
                        <i class="fa-solid fa-syringe text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${v.vaccine}</p>
                        <p class="text-xs text-slate-400">Given: ${new Date(v.date).toLocaleDateString()}</p>
                        ${v.next_due ? `<p class="text-xs text-slate-500">Next: ${new Date(v.next_due).toLocaleDateString()}</p>` : ''}
                    </div>
                </div>
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${v.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                    ${v.status === 'completed' ? 'Completed' : 'Pending'}
                </span>
            </div>
        `).join('');

        content.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                    <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                        ${initials(c.first_name, c.last_name)}
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(c.first_name || '')} ${escHtml(c.last_name || '')}</p>
                        <p class="text-xs text-slate-400">${escHtml(c.child_id || '')} • ${escHtml(c.age || '')}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    ${vaccinationHtml}
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('vaccinationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    <button class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-plus mr-1.5"></i> Add Vaccination
                    </button>
                </div>
            </div>
        `;
    }

    // ============================================================
    // PRINT CHILD
    // ============================================================
    async function printChild(id) {
        try {
            toast.info('Preparing print view...');
            const c = await fetchChild(id);
            if (!c) {
                toast.error('Failed to load child data');
                return;
            }

            // Create a new window for printing
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            if (!printWindow) {
                toast.error('Please allow popups to print');
                return;
            }
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Child Record - ${escHtml(val(c.child_id))}</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .header { text-align: center; border-bottom: 2px solid #0B4F4A; padding-bottom: 10px; margin-bottom: 20px; }
                        .section { margin-bottom: 20px; padding: 10px; background: #f8f8f8; border-radius: 5px; }
                        .section-title { font-weight: bold; color: #0B4F4A; margin-bottom: 10px; }
                        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                        .label { font-size: 12px; color: #666; }
                        .value { font-size: 14px; font-weight: 600; }
                        @media print { body { padding: 0; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Child Health Record</h1>
                        <p>${escHtml(val(c.child_id))} • Printed on ${new Date().toLocaleDateString()}</p>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Child Information</div>
                        <div class="grid">
                            <div><div class="label">Full Name</div><div class="value">${escHtml(val(c.first_name) + ' ' + val(c.middle_name, '') + ' ' + val(c.last_name))}</div></div>
                            <div><div class="label">Gender</div><div class="value">${escHtml(val(c.gender))}</div></div>
                            <div><div class="label">Birth Date</div><div class="value">${c.birth_date ? new Date(c.birth_date).toLocaleDateString() : 'Not Provided'}</div></div>
                            <div><div class="label">Age</div><div class="value">${calculateAge(c.birth_date)}</div></div>
                            <div><div class="label">Birth Weight</div><div class="value">${c.birth_weight ? c.birth_weight + ' kg' : 'Not Recorded'}</div></div>
                            <div><div class="label">Birth Height</div><div class="value">${c.birth_height ? c.birth_height + ' cm' : 'Not Recorded'}</div></div>
                            <div><div class="label">Blood Type</div><div class="value">${escHtml(val(c.blood_type, 'Unknown'))}</div></div>
                            <div><div class="label">Health Center</div><div class="value">${escHtml(val(c.health_center))}</div></div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Parents Information</div>
                        <div class="grid">
                            <div><div class="label">Mother's Name</div><div class="value">${escHtml(val(c.mother_name))}</div></div>
                            <div><div class="label">Mother's Contact</div><div class="value">${escHtml(val(c.mother_contact, 'N/A'))}</div></div>
                            <div><div class="label">Father's Name</div><div class="value">${escHtml(val(c.father_name, 'Not Provided'))}</div></div>
                            <div><div class="label">Father's Contact</div><div class="value">${escHtml(val(c.father_contact, 'N/A'))}</div></div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Medical Information</div>
                        <div class="grid">
                            <div><div class="label">Family History</div><div class="value">${escHtml(val(c.family_history, 'None Reported'))}</div></div>
                            <div><div class="label">Allergies</div><div class="value">${escHtml(val(c.allergies, 'None'))}</div></div>
                            <div><div class="label">Vaccine Compliance</div><div class="value">${c.vaccine_compliance || 0}%</div></div>
                            <div><div class="label">Last Visit</div><div class="value">${c.last_visit ? new Date(c.last_visit).toLocaleDateString() : 'Not Recorded'}</div></div>
                        </div>
                    </div>

                    <div class="section">
                        <div class="section-title">Address</div>
                        <div class="value">${escHtml(val(c.address))}</div>
                        <div class="value">${escHtml(val(c.barangay))}</div>
                    </div>

                    <script>window.onload = function() { window.print(); }<\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
            toast.success('Print view opened');
        } catch (err) {
            toast.error(err.message || 'Failed to prepare print view');
        }
    }

    // ============================================================
    // VIEW HEALTH RECORDS
    // ============================================================
    async function viewHealthRecord(id) {
        openModal('healthRecordModal');
        const content = document.getElementById('healthRecordContent');
        const c = await fetchChild(id);
        if (!c) {
            content.innerHTML = `
                <div class="text-center py-10 text-rose-500">
                    <i class="fa-solid fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load health records</p>
                </div>
            `;
            return;
        }

        // In production, fetch health records from API
        const healthRecords = [
            { date: c.last_visit || new Date().toISOString().split('T')[0], type: 'Checkup', doctor: 'Dr. Elena Santos', notes: 'Normal development', follow_up: '2026-08-10' },
        ];

        const recordsHtml = healthRecords.map(r => `
            <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200">
                <div>
                    <p class="font-semibold text-slate-800 text-sm">${r.type}</p>
                    <p class="text-xs text-slate-400">${new Date(r.date).toLocaleDateString()} • ${r.doctor}</p>
                    <p class="text-xs text-slate-600 mt-1">${r.notes}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Follow-up:</span>
                    <p class="text-xs font-semibold text-brand-dark">${new Date(r.follow_up).toLocaleDateString()}</p>
                </div>
            </div>
        `).join('');

        content.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                    <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                        ${initials(c.first_name, c.last_name)}
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(c.first_name || '')} ${escHtml(c.last_name || '')}</p>
                        <p class="text-xs text-slate-400">${escHtml(c.child_id || '')} • ${escHtml(c.age || '')}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    ${recordsHtml}
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('healthRecordModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    <button class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-plus mr-1.5"></i> Add Record
                    </button>
                </div>
            </div>
        `;
    }

    // ============================================================
    // SHARED: read a set of form fields into a plain object
    // (replaces two near-identical 15-line field-collection blocks
    // in saveChildEdit / saveChildRegistration)
    // ============================================================
    function readFormFields(prefix, keys) {
        const data = {};
        keys.forEach(key => {
            const el = document.getElementById(`${prefix}${key}`);
            data[key] = el ? (el.value || null) : null;
        });
        return data;
    }

    const CHILD_FORM_KEYS = [
        'first_name', 'last_name', 'gender', 'birth_date', 'birth_weight', 'birth_height',
        'blood_type', 'barangay', 'address', 'mother_name', 'mother_contact', 'mother_occupation',
        'father_name', 'father_contact', 'father_occupation', 'family_history', 'allergies'
    ];

    // Shared submit handler for both the register and edit forms — they
    // differ only in HTTP method, URL, and the success/cleanup step.
    async function submitChildForm(event, { url, method, formData, onSuccess, successMessage }) {
        event.preventDefault();
        const submitBtn = event.target.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const response = await fetch(url, {
                method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });

            let result;
            if (!response.ok) {
                let errorMessage = response.statusText || 'An unexpected server error occurred.';
                try {
                    result = await response.json();
                    errorMessage = result.message || errorMessage;
                } catch (_) { /* non-JSON error body, keep statusText */ }
                toast.error(errorMessage);
                return;
            }

            result = await response.json();
            if (!result.success) {
                toast.error(result.message || 'Request failed');
                return;
            }

            toast.success(successMessage);
            onSuccess();
            await refreshChildList();
        } catch (err) {
            toast.error(err.message || 'Network error. Please try again.');
        } finally {
            submitBtn.disabled = false;
        }
    }

    // ============================================================
    // SAVE CHILD EDIT
    // ============================================================
    async function saveChildEdit(event) {
        const id = document.getElementById('edit_child_id').value;
        const formData = readFormFields('edit_', CHILD_FORM_KEYS);
        formData.middle_name = document.getElementById('edit_middle_name').value || null;

        await submitChildForm(event, {
            url: `${API_BASE}?id=${id}`,
            method: 'PUT',
            formData,
            successMessage: 'Child record updated successfully.',
            onSuccess: () => closeModal('editChildModal')
        });
    }

    // ============================================================
    // SAVE CHILD REGISTRATION
    // ============================================================
    async function saveChildRegistration(event) {
        const formData = readFormFields('child_', CHILD_FORM_KEYS);
        formData.health_center = 'Health Center 1';

        await submitChildForm(event, {
            url: API_BASE,
            method: 'POST',
            formData,
            successMessage: 'Child registered successfully.',
            onSuccess: () => {
                closeModal('registerChildModal');
                event.target.reset();
            }
        });
    }

    // ============================================================
    // BUILD A CHILD ROW (shared by refreshChildList; single source
    // of truth for the JS-rendered <tr>, mirroring the PHP row above)
    // ============================================================
    function actionButtons(id) {
        const actions = [
            ['viewChild', 'fa-eye', 'text-brand-medium hover:bg-brand-light', 'View'],
            ['editChild', 'fa-pen', 'text-slate-500 hover:bg-slate-100 hover:text-slate-700', 'Edit'],
            ['viewVaccination', 'fa-syringe', 'text-emerald-600 hover:bg-emerald-50', 'Vaccination'],
            ['viewHealthRecord', 'fa-folder-medical', 'text-blue-600 hover:bg-blue-50', 'Medical History'],
            ['printChild', 'fa-print', 'text-slate-500 hover:bg-slate-100 hover:text-slate-700', 'Print'],
            ['exportChild', 'fa-download', 'text-slate-500 hover:bg-slate-100 hover:text-slate-700', 'Export'],
            ['archiveChild', 'fa-archive', 'text-amber-600 hover:bg-amber-50', 'Archive'],
            ['deleteChild', 'fa-trash', 'text-rose-500 hover:bg-rose-50', 'Delete'],
        ];
        return actions.map(([fn, icon, cls, title]) => `
            <button onclick="${fn}(${id})" class="p-1.5 ${cls} rounded-lg transition" title="${title}">
                <i class="fa-solid ${icon} text-sm"></i>
            </button>`).join('');
    }

    function buildChildRowHTML(child) {
        return `
            <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">${escHtml(child.child_id || '')}</td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                        ${initials(child.first_name, child.last_name)}
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(child.first_name || '')} ${escHtml(child.last_name || '')}</p>
                        <p class="text-xs text-slate-400">${escHtml(child.age || '—')} • ${escHtml(child.barangay || '')}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-xs text-slate-600 maskable" data-real="${escHtml(child.mother_name || '')}">${escHtml(child.mother_name || '')}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${getNutritionClass(child.nutrition_status)}">
                    ${escHtml(child.nutrition_status || 'Normal')}
                </span>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                    ${complianceBar(child.vaccine_compliance)}
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-semibold ${child.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                    ${capitalize(child.status || 'active')}
                </span>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center justify-center gap-1">
                    ${actionButtons(child.id)}
                </div>
            </td>
        `;
    }

    // ============================================================
    // REFRESH CHILD LIST FROM API
    // ============================================================
    async function refreshChildList() {
        try {
            const response = await fetch(`${API_BASE}?page=1&limit=5`);
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to refresh list');
            }

            const data = result.data;
            if (!Array.isArray(data)) {
                console.error('Unexpected data format', result);
                return;
            }

            const tableWrapper = document.getElementById('tableWrapper');
            const noRecordsState = document.getElementById('noRecordsState');
            const emptySearchState = document.getElementById('emptySearchState');

            setVisible(tableWrapper, data.length > 0);
            setVisible(noRecordsState, data.length === 0, 'flex');
            setVisible(emptySearchState, false, 'flex');

            if (data.length > 0) {
                const tbody = document.getElementById('childTableBody');
                tbody.innerHTML = '';

                data.forEach(child => {
                    const row = document.createElement('tr');
                    row.className = 'border-b border-slate-100 hover:bg-brand-light/40 transition-colors child-row ' +
                        (child.nutrition_status === 'Critical' ? 'bg-rose-50/50' : '');
                    Object.assign(row.dataset, {
                        name: (child.first_name + ' ' + child.last_name).toLowerCase(),
                        id: child.child_id || '',
                        mother: (child.mother_name || '').toLowerCase(),
                        status: child.status || 'active',
                        gender: child.gender || '',
                        nutrition: child.nutrition_status || '',
                        barangay: (child.barangay || '').toLowerCase(),
                    });
                    row.innerHTML = buildChildRowHTML(child);
                    tbody.appendChild(row);
                });
            }
        } catch (err) {
            console.error('Failed to refresh child list:', err);
            toast.error(err.message || 'Failed to refresh list');
        }
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchChild').addEventListener('input', filterChildren);
    document.getElementById('filterGender').addEventListener('change', filterChildren);
    document.getElementById('filterNutrition').addEventListener('change', filterChildren);
    document.getElementById('filterStatus').addEventListener('change', filterChildren);

    function filterChildren() {
        const search = document.getElementById('searchChild').value.toLowerCase();
        const gender = document.getElementById('filterGender').value;
        const nutrition = document.getElementById('filterNutrition').value;
        const status = document.getElementById('filterStatus').value;
        let visibleCount = 0;

        document.querySelectorAll('.child-row').forEach(row => {
            const d = row.dataset;
            const matchesSearch = !search ||
                [d.name, d.id.toLowerCase(), d.mother, d.barangay].some(field => field.includes(search));
            const isVisible = matchesSearch &&
                (!gender || d.gender === gender) &&
                (!nutrition || d.nutrition === nutrition) &&
                (!status || d.status === status);

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        const tableWrapper = document.getElementById('tableWrapper');
        const emptySearchState = document.getElementById('emptySearchState');
        const showEmpty = visibleCount === 0 && tableWrapper && !tableWrapper.classList.contains('hidden');
        setVisible(emptySearchState, showEmpty, 'flex');
    }

    function resetFilters() {
        document.getElementById('searchChild').value = '';
        document.getElementById('filterGender').value = '';
        document.getElementById('filterNutrition').value = '';
        document.getElementById('filterStatus').value = '';
        document.querySelectorAll('.child-row').forEach(row => row.style.display = '');
        setVisible(document.getElementById('emptySearchState'), false, 'flex');
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo $totalPages; ?>) return;
        window.location.href = '?page=' + page;
    }

    // ============================================================
    // ACTION MENU FUNCTIONS
    // ============================================================
    function toggleActionMenu(id) {
        const menu = document.getElementById('actionMenu-' + id);
        const isHidden = menu.classList.contains('hidden');
        closeAllActionMenus();
        if (isHidden) menu.classList.remove('hidden');
    }

    function closeAllActionMenus() {
        document.querySelectorAll('[id^="actionMenu-"]').forEach(menu => menu.classList.add('hidden'));
    }

    // Close menus when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative.inline-block')) {
            closeAllActionMenus();
        }
    });

    // ============================================================
    // QUICK FILTER FUNCTION
    // ============================================================
    function quickFilter(type, value) {
        const targetMap = { gender: 'filterGender', nutrition: 'filterNutrition', status: 'filterStatus' };
        const el = document.getElementById(targetMap[type]);
        if (el) el.value = value;
        filterChildren();
    }

    // ============================================================
    // EXPORT FUNCTION
    // ============================================================
    async function exportChild(id) {
        try {
            toast.info('Preparing export...');
            const response = await fetch(`${API_BASE}?id=${id}&export=pdf`);
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Export failed');
            }

            const blob = new Blob([JSON.stringify(result.data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `child_${id}_export.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            toast.success('Export downloaded successfully');
        } catch (err) {
            toast.error(err.message || 'Export failed');
        }
    }

    // ============================================================
    // ARCHIVE / DELETE (shared confirm + PATCH/DELETE flow)
    // ============================================================
    async function confirmAndSend(id, { confirmMsg, method, body, successMsg, failMsg }) {
        if (!confirm(confirmMsg)) return;
        try {
            const response = await fetch(`${API_BASE}?id=${id}`, {
                method,
                headers: body ? { 'Content-Type': 'application/json' } : undefined,
                body: body ? JSON.stringify(body) : undefined
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || failMsg);
            }

            toast.success(successMsg);
            await refreshChildList();
        } catch (err) {
            toast.error(err.message || failMsg);
        }
    }

    function archiveChild(id) {
        return confirmAndSend(id, {
            confirmMsg: 'Are you sure you want to archive this child record?',
            method: 'PATCH',
            body: { status: 'inactive' },
            successMsg: 'Child record archived successfully',
            failMsg: 'Archive failed'
        });
    }

    function deleteChild(id) {
        return confirmAndSend(id, {
            confirmMsg: 'Are you sure you want to delete this child record? This action cannot be undone.',
            method: 'DELETE',
            body: null,
            successMsg: 'Child record deleted successfully',
            failMsg: 'Delete failed'
        });
    }
</script>

<?php include_once '../../includes/footer.php'; ?>