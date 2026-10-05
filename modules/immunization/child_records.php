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
requireDepartmentAccess('immunization & nutrition');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/Child.php';
require_once __DIR__ . '/../../includes/data-mask.php';
require_once __DIR__ . '/../../includes/toast.php';

// Constants
const DEFAULT_PAGE = 1;
const DEFAULT_LIMIT = 10;

function normalizeChildDateFilter(mixed $value): ?string
{
    if (!is_string($value) || $value === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : null;
}

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

// Fetch staff who can administer vaccines (Immunization, Nutrition, Health Center)
$immunizationStaff = [];
try {
    $db = Database::getInstance();
    $allEmployees = $db->select('employees', ['status' => 'Active'], ['limit' => 200, 'order' => 'department.asc,full_name.asc']);
    $staffDepts = ['immunization', 'nutrition', 'health center', 'health center services'];
    foreach ($allEmployees as $emp) {
        $dept = strtolower($emp['department'] ?? '');
        if (in_array($dept, $staffDepts)) {
            $immunizationStaff[] = [
                'name'       => $emp['full_name'] ?? 'Unknown',
                'role'       => $emp['role'] ?? '',
                'department' => $emp['department'] ?? ''
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Error fetching immunization staff: ' . $e->getMessage());
}

// Get statistics from model
$stats = $childModel->getStats();

// Pagination logic
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : DEFAULT_PAGE;
$limit = DEFAULT_LIMIT;
$offset = ($page - 1) * $limit;
$registrationDateFrom = normalizeChildDateFilter($_GET['registration_date_from'] ?? null);
$registrationDateTo = normalizeChildDateFilter($_GET['registration_date_to'] ?? null);
$childCriteria = [];
if ($registrationDateFrom !== null || $registrationDateTo !== null) {
    $childCriteria['registration_date'] = [];
    if ($registrationDateFrom !== null) {
        $childCriteria['registration_date']['gte'] = $registrationDateFrom;
    }
    if ($registrationDateTo !== null) {
        $childCriteria['registration_date']['lte'] = $registrationDateTo;
    }
}

// Get paginated children from model
$children = $childModel->search($childCriteria, $limit, $offset);
$totalChildren = empty($childCriteria) ? $stats['total'] : $childModel->count($childCriteria);
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
            <p class="text-sm text-slate-500 mt-0.5">Manage child registration, demographics &amp; health records</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('exportChildMasterlistModal')"
                    class="px-3.5 py-2 bg-white border border-emerald-300 text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-excel text-xs text-emerald-600"></i> Bulk Export Masterlist
            </button>
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
                <button type="button" onclick="openModal('childDateFilterModal')" title="Filter by registration date"
                        class="px-3 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition-colors text-sm">
                    <i class="fa-solid fa-calendar-days"></i>
                </button>
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Registration Date Filter Modal -->
    <div id="childDateFilterModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-days text-brand-medium"></i>
                    Registration Date Filter
                </h3>
                <button type="button" onclick="closeModal('childDateFilterModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label for="registrationDateFrom" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Registered From</label>
                    <input type="date" id="registrationDateFrom" value="<?php echo htmlspecialchars($registrationDateFrom ?? ''); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label for="registrationDateTo" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Registered To</label>
                    <input type="date" id="registrationDateTo" value="<?php echo htmlspecialchars($registrationDateTo ?? ''); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="clearChildDateFilter()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">Clear</button>
                    <button type="button" onclick="applyChildDateFilter()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">Apply Filter</button>
                </div>
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
                        data-barangay="<?php echo htmlspecialchars(strtolower($child['barangay'])); ?>"
                        data-registration-date="<?php echo htmlspecialchars($child['registration_date'] ?? ''); ?>">
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
                                <button onclick="archiveChild(<?php echo (int)($child['id'] ?? 0); ?>)"
                                        class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Archive">
                                    <i class="fa-solid fa-archive text-sm"></i>
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-slate-200/80">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white/95 backdrop-blur-md rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark flex-shrink-0">
                    <i class="fa-solid fa-child-reaching text-lg"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-tight">Register Child Record</h3>
                    <p class="text-xs text-slate-500">Enter demographic, residency, and guardian details</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('registerChildModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="registerChildForm" class="p-6 space-y-4" onsubmit="saveChildRegistration(event)">
            <!-- 1. Child Information -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-brand-light text-brand-dark inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-child text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Child Information</h4>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="child_first_name" required placeholder="e.g. Juan" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Last Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="child_last_name" required placeholder="e.g. Dela Cruz" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Gender <span class="text-rose-500">*</span></label>
                        <select id="child_gender" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Birth Date <span class="text-rose-500">*</span></label>
                        <input type="date" id="child_birth_date" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    
                    <!-- Measurements & Blood Type: Balanced 3-column subgrid across full width -->
                    <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Birth Weight (kg)</label>
                            <input type="number" id="child_birth_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" placeholder="e.g. 3.2" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Birth Height (cm)</label>
                            <input type="number" id="child_birth_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" placeholder="e.g. 50.0" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Blood Type</label>
                            <select id="child_blood_type" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                                <option value="">Select (Optional)</option>
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
                    </div>

                    <!-- Zone and Barangay side-by-side -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Zone</label>
                        <select id="child_zone" onchange="onZoneChange('child_zone', 'child_barangay')" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                            <option value="">All Zones (Select Zone)</option>
                            <option value="Zone 1">Zone 1 (Brgy 1 to 4)</option>
                            <option value="Zone 2">Zone 2 (Brgy 5 to 24)</option>
                            <option value="Zone 3">Zone 3 (Brgy 25 to 35)</option>
                            <option value="Zone 4">Zone 4 (Brgy 36 to 48)</option>
                            <option value="Zone 5">Zone 5 (Brgy 49 to 58)</option>
                            <option value="Zone 6">Zone 6 (Brgy 59 to 76)</option>
                            <option value="Zone 7">Zone 7 (Brgy 77 to 81)</option>
                            <option value="Zone 8">Zone 8 (Brgy 82 to 85)</option>
                            <option value="Zone 9">Zone 9 (Brgy 86 to 98)</option>
                            <option value="Zone 10">Zone 10 (Brgy 99 to 116)</option>
                            <option value="Zone 11">Zone 11 (Brgy 117 to 131)</option>
                            <option value="Zone 12">Zone 12 (Brgy 132 to 140)</option>
                            <option value="Zone 13">Zone 13 (Brgy 141 to 150)</option>
                            <option value="Zone 14">Zone 14 (Brgy 151 to 160)</option>
                            <option value="Zone 15">Zone 15 (Brgy 161 to 164)</option>
                            <option value="Zone 16">Zone 16 (Brgy 165 to 188)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Barangay <span class="text-rose-500">*</span></label>
                        <select id="child_barangay" onchange="onBarangayChange('child_barangay', 'child_zone')" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                            <option value="">Select Barangay</option>
                            <?php for ($b = 1; $b <= 188; $b++): ?>
                            <option value="Barangay <?= $b ?>">Barangay <?= $b ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <!-- Address full width -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Address <span class="text-rose-500">*</span></label>
                        <input type="text" id="child_address" required placeholder="House No., Street name, Subdivision / Village" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- 2. Mother Information -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-pink-100 text-pink-700 inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-person-dress text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Mother Information</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Mother's Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="child_mother_name" required placeholder="e.g. Maria Dela Cruz" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Number</label>
                        <input type="text" id="child_mother_contact" placeholder="e.g. 09171234567" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Occupation</label>
                        <input type="text" id="child_mother_occupation" placeholder="e.g. Teacher, Self-employed" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- 3. Father Information -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-700 inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-person text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Father Information</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Father's Full Name</label>
                        <input type="text" id="child_father_name" placeholder="e.g. Juan Dela Cruz Sr." class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Number</label>
                        <input type="text" id="child_father_contact" placeholder="e.g. 09181234567" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Occupation</label>
                        <input type="text" id="child_father_occupation" placeholder="e.g. Engineer, Driver" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- 4. Health & Medical Notes -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-rose-100 text-rose-700 inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-notes-medical text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Medical Notes & History</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Allergies</label>
                        <input type="text" id="child_allergies" placeholder="Known allergies (e.g. penicillin, dust) or None" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Family Medical History</label>
                        <input type="text" id="child_family_history" placeholder="e.g. Asthma, Hypertension, Diabetes, or None" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeModal('registerChildModal')"
                        class="px-4 py-2.5 bg-white border border-slate-200 text-slate-600 hover:text-slate-800 rounded-lg hover:bg-slate-50 transition text-sm font-semibold shadow-sm">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 bg-brand-dark hover:bg-brand-medium text-white rounded-lg transition text-sm font-semibold shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-child"></i> Register Child
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
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-slate-200/80">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white/95 backdrop-blur-md rounded-t-2xl z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark flex-shrink-0">
                    <i class="fa-solid fa-pen text-base"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-tight">Edit Child Record</h3>
                    <p class="text-xs text-slate-500">Update demographic, residency, and guardian details</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('editChildModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="editChildForm" class="p-6 space-y-4" onsubmit="saveChildEdit(event)">
            <input type="hidden" id="edit_child_id">
            
            <!-- 1. Child Information -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-brand-light text-brand-dark inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-child text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Child Information</h4>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">First Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="edit_first_name" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Middle Name</label>
                        <input type="text" id="edit_middle_name" placeholder="Optional" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Last Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="edit_last_name" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Gender <span class="text-rose-500">*</span></label>
                        <select id="edit_gender" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Birth Date <span class="text-rose-500">*</span></label>
                        <input type="date" id="edit_birth_date" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Blood Type</label>
                        <select id="edit_blood_type" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                            <option value="">Select (Optional)</option>
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
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Birth Weight (kg)</label>
                        <input type="number" id="edit_birth_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Birth Height (cm)</label>
                        <input type="number" id="edit_birth_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Zone</label>
                        <select id="edit_zone" onchange="onZoneChange('edit_zone', 'edit_barangay')" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                            <option value="">All Zones (Select Zone)</option>
                            <option value="Zone 1">Zone 1 (Brgy 1 to 4)</option>
                            <option value="Zone 2">Zone 2 (Brgy 5 to 24)</option>
                            <option value="Zone 3">Zone 3 (Brgy 25 to 35)</option>
                            <option value="Zone 4">Zone 4 (Brgy 36 to 48)</option>
                            <option value="Zone 5">Zone 5 (Brgy 49 to 58)</option>
                            <option value="Zone 6">Zone 6 (Brgy 59 to 76)</option>
                            <option value="Zone 7">Zone 7 (Brgy 77 to 81)</option>
                            <option value="Zone 8">Zone 8 (Brgy 82 to 85)</option>
                            <option value="Zone 9">Zone 9 (Brgy 86 to 98)</option>
                            <option value="Zone 10">Zone 10 (Brgy 99 to 116)</option>
                            <option value="Zone 11">Zone 11 (Brgy 117 to 131)</option>
                            <option value="Zone 12">Zone 12 (Brgy 132 to 140)</option>
                            <option value="Zone 13">Zone 13 (Brgy 141 to 150)</option>
                            <option value="Zone 14">Zone 14 (Brgy 151 to 160)</option>
                            <option value="Zone 15">Zone 15 (Brgy 161 to 164)</option>
                            <option value="Zone 16">Zone 16 (Brgy 165 to 188)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Barangay <span class="text-rose-500">*</span></label>
                        <select id="edit_barangay" onchange="onBarangayChange('edit_barangay', 'edit_zone')" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                            <option value="">Select Barangay</option>
                            <?php for ($b = 1; $b <= 188; $b++): ?>
                            <option value="Barangay <?= $b ?>">Barangay <?= $b ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Address <span class="text-rose-500">*</span></label>
                        <input type="text" id="edit_address" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- 2. Mother Information -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-pink-100 text-pink-700 inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-person-dress text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Mother Information</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Mother's Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" id="edit_mother_name" required class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Number</label>
                        <input type="text" id="edit_mother_contact" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Occupation</label>
                        <input type="text" id="edit_mother_occupation" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- 3. Father Information -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-700 inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-person text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Father Information</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Father's Full Name</label>
                        <input type="text" id="edit_father_name" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Contact Number</label>
                        <input type="text" id="edit_father_contact" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Occupation</label>
                        <input type="text" id="edit_father_occupation" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- 4. Health & Medical Notes -->
            <div class="bg-slate-50/80 rounded-xl p-4 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-center gap-2 pb-1 border-b border-slate-200/60">
                    <span class="w-5 h-5 rounded-md bg-rose-100 text-rose-700 inline-flex items-center justify-center text-xs font-bold">
                        <i class="fa-solid fa-notes-medical text-[11px]"></i>
                    </span>
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Medical Notes & History</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Allergies</label>
                        <input type="text" id="edit_allergies" placeholder="Known allergies or None" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Family Medical History</label>
                        <input type="text" id="edit_family_history" placeholder="Family medical history or None" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-800 focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeModal('editChildModal')"
                        class="px-4 py-2.5 bg-white border border-slate-200 text-slate-600 hover:text-slate-800 rounded-lg hover:bg-slate-50 transition text-sm font-semibold shadow-sm">
                    Cancel
                </button>
                <button type="submit"
                        class="px-5 py-2.5 bg-brand-dark hover:bg-brand-medium text-white rounded-lg transition text-sm font-semibold shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-check"></i> Save Changes
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
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
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
<!-- RECORD VACCINATION MODAL (uses ModalSystem)                   -->
<!-- ============================================================ -->
<div id="recordVaccinationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-brand-light flex items-center justify-center text-brand-dark">
                    <i class="fa-solid fa-syringe text-sm"></i>
                </div>
                <h3 class="font-bold text-slate-900">Record Vaccination</h3>
            </div>
            <button type="button" onclick="closeModal('recordVaccinationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="recordVaccinationForm" class="p-6 space-y-4" onsubmit="saveVaccinationRecord(event)">
            <input type="hidden" id="add_vacc_child_id" value="">
            
            <!-- Child Header Summary -->
            <div id="vaccChildBanner" class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                <div class="w-9 h-9 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0" id="vaccChildInitials">
                    --
                </div>
                <div>
                    <p class="font-semibold text-slate-800 text-sm" id="vaccChildName">Loading Child...</p>
                    <p class="text-xs text-slate-400" id="vaccChildSub">ID: --</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Vaccine Name <span class="text-rose-500">*</span></label>
                    <select id="add_vacc_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">Select Vaccine</option>
                        <option value="BCG">BCG (Tuberculosis)</option>
                        <option value="Hepatitis B">Hepatitis B</option>
                        <option value="Pentavalent (DPT-HepB-Hib)">Pentavalent (DPT-HepB-Hib)</option>
                        <option value="OPV (Oral Polio Vaccine)">OPV (Oral Polio)</option>
                        <option value="IPV (Inactivated Polio)">IPV (Inactivated Polio)</option>
                        <option value="PCV (Pneumococcal)">PCV (Pneumococcal)</option>
                        <option value="MMR (Measles, Mumps, Rubella)">MMR (Measles, Mumps, Rubella)</option>
                        <option value="Rotavirus">Rotavirus</option>
                        <option value="Influenza">Influenza</option>
                        <option value="HPV">HPV</option>
                    </select>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Dose Number <span class="text-rose-500">*</span></label>
                    <input type="number" id="add_vacc_dose" min="1" max="99" value="1" required oninput="limitDoseInput(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Date Administered <span class="text-rose-500">*</span></label>
                    <input type="date" id="add_vacc_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Next Due Date</label>
                    <input type="date" id="add_vacc_next_due" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Administered By</label>
                    <select id="add_vacc_by" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="">-- Select Staff --</option>
                        <?php
                        $staffGrouped = [];
                        foreach ($immunizationStaff as $s) {
                            $staffGrouped[$s['department']][] = $s;
                        }
                        foreach ($staffGrouped as $dept => $staffList): ?>
                        <optgroup label="<?php echo htmlspecialchars($dept); ?>">
                            <?php foreach ($staffList as $s): ?>
                            <option value="<?php echo htmlspecialchars($s['name']); ?>">
                                <?php echo htmlspecialchars($s['name']); ?> &mdash; <?php echo htmlspecialchars($s['role']); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Batch / Lot Number</label>
                    <input type="text" id="add_vacc_batch" placeholder="e.g. BCG-2026-01" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Health Center / Facility</label>
                <input type="text" id="add_vacc_facility" placeholder="Health Center Name" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Remarks / Notes</label>
                <textarea id="add_vacc_notes" rows="2" placeholder="Optional notes or reaction observations..." class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none resize-none"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeModal('recordVaccinationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-syringe text-xs"></i> Save Vaccination
                </button>
            </div>
        </form>
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

<!-- ARCHIVE CONFIRMATION MODAL -->
<div id="archiveChildModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                <i class="fa-solid fa-box-archive text-xl"></i>
            </div>
            <h3 class="font-bold text-slate-900 text-lg">Archive Child Record?</h3>
            <p class="text-sm text-slate-500 mt-2">The record will be marked inactive.</p>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="resolveConfirmation(false)" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                <button type="button" onclick="resolveConfirmation(true)" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-semibold">Archive</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- EXPORT CHILD MASTERLIST MODAL (BULK EXPORT)                  -->
<!-- ============================================================ -->
<div id="exportChildMasterlistModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-export text-emerald-600"></i> Export Child Masterlist
            </h3>
            <button onclick="closeModal('exportChildMasterlistModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Export Scope</label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="childExportScope" value="filtered" checked class="text-emerald-600 focus:ring-emerald-500">
                        <span>Active Filtered View</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-medium text-slate-700 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/50">
                        <input type="radio" name="childExportScope" value="all" class="text-emerald-600 focus:ring-emerald-500">
                        <span>Full Masterlist (All)</span>
                    </label>
                </div>
            </div>

            <p class="text-xs text-slate-500">Select export format for community health reports and Operation Timbang (OPT) Plus submissions:</p>

            <div class="space-y-2.5">
                <button type="button" onclick="exportChildMasterlistData('excel')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-emerald-50 hover:border-emerald-300 transition text-left group">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition">
                        <i class="fa-solid fa-file-excel"></i>
                    </div>
                    <div>
                        <strong class="block text-sm text-slate-800">Microsoft Excel (.xlsx / .xls)</strong>
                        <small class="text-xs text-slate-500">Formatted spreadsheet for municipal reporting</small>
                    </div>
                </button>

                <button type="button" onclick="exportChildMasterlistData('csv')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-blue-50 hover:border-blue-300 transition text-left group">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition">
                        <i class="fa-solid fa-file-csv"></i>
                    </div>
                    <div>
                        <strong class="block text-sm text-slate-800">CSV Spreadsheet (.csv)</strong>
                        <small class="text-xs text-slate-500">Standard comma-separated dataset</small>
                    </div>
                </button>

                <button type="button" onclick="exportChildMasterlistData('pdf')" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl border border-slate-200 hover:bg-rose-50 hover:border-rose-300 transition text-left group">
                    <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-lg shrink-0 group-hover:scale-105 transition">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div>
                        <strong class="block text-sm text-slate-800">Printable Report Document</strong>
                        <small class="text-xs text-slate-500">Formatted masterlist document for printing</small>
                    </div>
                </button>
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

    function limitMeasurementInput(input) {
        const value = input.value || '';
        const parts = value.split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 3);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    function limitDoseInput(input) {
        const value = (input.value || '').replace(/\D/g, '').slice(0, 2);
        input.value = value;
    }

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
        if (id === 'registerChildModal') {
            const zoneSelect = document.getElementById('child_zone');
            if (zoneSelect && !zoneSelect.value) {
                populateBarangayDropdown('child_barangay', '', '');
            }
        }
        ModalSystem.open(id);
    }

    function closeModal(id) {
        ModalSystem.close(id);
    }

    // ============================================================
    // CALOOCAN DISTRICT 1 ZONES & BARANGAYS CONFIGURATION
    // ============================================================
    const CALOOCAN_ZONES = {
        'Zone 1':  [1, 2, 3, 4],
        'Zone 2':  [5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24],
        'Zone 3':  [25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35],
        'Zone 4':  [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48],
        'Zone 5':  [49, 50, 51, 52, 53, 54, 55, 56, 57, 58],
        'Zone 6':  [59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76],
        'Zone 7':  [77, 78, 79, 80, 81],
        'Zone 8':  [82, 83, 84, 85],
        'Zone 9':  [86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98],
        'Zone 10': [99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116],
        'Zone 11': [117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131],
        'Zone 12': [132, 133, 134, 135, 136, 137, 138, 139, 140],
        'Zone 13': [141, 142, 143, 144, 145, 146, 147, 148, 149, 150],
        'Zone 14': [151, 152, 153, 154, 155, 156, 157, 158, 159, 160],
        'Zone 15': [161, 162, 163, 164],
        'Zone 16': [165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188]
    };

    function getZoneForBarangay(barangayName) {
        if (!barangayName) return '';
        const match = String(barangayName).match(/\b(\d{1,3})\b/);
        if (!match) return '';
        const num = parseInt(match[1], 10);
        for (const [zone, brgys] of Object.entries(CALOOCAN_ZONES)) {
            if (brgys.includes(num)) return zone;
        }
        return '';
    }

    function populateBarangayDropdown(selectId, targetZone = '', selectedValue = '') {
        const select = document.getElementById(selectId);
        if (!select) return;
        
        const defaultText = selectId.startsWith('filter') ? 'All Barangays' : 'Select Barangay';
        select.innerHTML = '<option value="">' + defaultText + '</option>';
        
        const brgysToRender = (targetZone && CALOOCAN_ZONES[targetZone])
            ? CALOOCAN_ZONES[targetZone]
            : Array.from({ length: 188 }, (_, i) => i + 1);
            
        brgysToRender.forEach(num => {
            const val = `Barangay ${num}`;
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = val;
            if (val === selectedValue) opt.selected = true;
            select.appendChild(opt);
        });
        
        if (selectedValue) {
            select.value = selectedValue;
        }
    }

    function onZoneChange(zoneSelectId, barangaySelectId) {
        const zoneSelect = document.getElementById(zoneSelectId);
        const zone = zoneSelect ? zoneSelect.value.trim() : '';
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

    // ============================================================
    // FETCH CHILDREN FROM API
    // ============================================================
    async function fetchChildren(page = 1, limit = 10) {
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
                        <p class="text-xs text-slate-500 mt-0.5">${escHtml(val(c.child_id))} &bull; ${escHtml(val(c.gender))} &bull; ${escHtml(calculateAge(c.birth_date))}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs font-semibold ${getNutritionClass(c.nutrition_status)}">
                            ${escHtml(val(c.nutrition_status, 'Normal'))}
                        </span>
                    </div>
                </div>

                <!-- Vitals -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Birth Date</p>
                        <p class="text-sm font-semibold text-slate-800">${escHtml(val(c.birth_date))}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Current Weight</p>
                        <p class="text-sm font-semibold text-slate-800">${c.birth_weight ? escHtml(c.birth_weight) + ' kg' : '<span class="text-slate-400 text-xs">Not recorded</span>'}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Current Height</p>
                        <p class="text-sm font-semibold text-slate-800">${c.birth_height ? escHtml(c.birth_height) + ' cm' : '<span class="text-slate-400 text-xs">Not recorded</span>'}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Blood Type</p>
                        <p class="text-sm font-semibold text-slate-800">${escHtml(val(c.blood_type))}</p>
                    </div>
                </div>

                <!-- Vaccine Compliance -->
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                    <div class="flex items-center justify-between mb-1">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">Vaccine Compliance</p>
                        <p class="text-xs font-semibold ${(c.vaccine_compliance ?? 0) >= 80 ? 'text-emerald-600' : (c.vaccine_compliance ?? 0) >= 50 ? 'text-amber-600' : 'text-rose-500'}">${escHtml(String(c.vaccine_compliance ?? 0))}%</p>
                    </div>
                    ${complianceBar(c.vaccine_compliance ?? 0)}
                    ${(c.vaccine_compliance ?? 0) === 0 ? '<p class="text-[10px] text-slate-400 mt-1"><i class="fa-solid fa-circle-info mr-1"></i>No vaccine doses recorded yet. Administer vaccines to update compliance.</p>' : ''}
                </div>

                <!-- Address -->
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Address</p>
                    <p class="text-sm text-slate-700">${escHtml(val(c.address))}, ${escHtml(val(c.barangay))}</p>
                </div>

                <!-- Mother / Father -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">👩 Mother</p>
                        <p class="text-sm font-semibold text-slate-800">${escHtml(val(c.mother_name))}</p>
                        <p class="text-xs text-slate-500">${escHtml(val(c.mother_contact))}</p>
                        <p class="text-xs text-slate-500">${escHtml(val(c.mother_occupation))}</p>
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">👨 Father</p>
                        <p class="text-sm font-semibold text-slate-800">${escHtml(val(c.father_name))}</p>
                        <p class="text-xs text-slate-500">${escHtml(val(c.father_contact))}</p>
                        <p class="text-xs text-slate-500">${escHtml(val(c.father_occupation))}</p>
                    </div>
                </div>

                <!-- Family History / Allergies -->
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Family History</p>
                    <p class="text-sm text-slate-700">${escHtml(val(c.family_history, 'None reported'))}</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-200">
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">Allergies</p>
                    <p class="text-sm text-slate-700">${escHtml(val(c.allergies, 'None'))}</p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('viewChildModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    <button onclick="closeModal('viewChildModal'); editChild(${Number(c.id) || 0})" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-pen mr-1.5"></i> Edit
                    </button>
                </div>
            </div>
        `;
    }

    // ============================================================
    // EDIT CHILD (opens edit modal and pre-fills form from API data)
    // ============================================================
    async function editChild(id) {
        const c = await fetchChild(id);
        if (!c) return;

        document.getElementById('edit_child_id').value = c.id ?? id;
        const fieldMap = {
            first_name: 'edit_first_name', middle_name: 'edit_middle_name', last_name: 'edit_last_name',
            gender: 'edit_gender', birth_date: 'edit_birth_date', birth_weight: 'edit_birth_weight',
            birth_height: 'edit_birth_height', blood_type: 'edit_blood_type',
            address: 'edit_address', mother_name: 'edit_mother_name', mother_contact: 'edit_mother_contact',
            mother_occupation: 'edit_mother_occupation', father_name: 'edit_father_name',
            father_contact: 'edit_father_contact', father_occupation: 'edit_father_occupation',
            family_history: 'edit_family_history', allergies: 'edit_allergies'
        };
        Object.entries(fieldMap).forEach(([key, elId]) => {
            const el = document.getElementById(elId);
            if (el) el.value = c[key] ?? '';
        });

        // Resolve and pre-select Zone and Barangay
        const brgy = c.barangay ?? '';
        const zone = getZoneForBarangay(brgy);
        const editZoneSelect = document.getElementById('edit_zone');
        if (editZoneSelect) editZoneSelect.value = zone;
        populateBarangayDropdown('edit_barangay', zone, brgy);

        openModal('editChildModal');
    }

    // ============================================================
    // VIEW VACCINATION RECORDS
    // ============================================================
    async function viewVaccination(id) {
        const content = document.getElementById('vaccinationContent');
        openModal('vaccinationModal');
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
                    <p>Failed to load vaccination records</p>
                </div>
            `;
            return;
        }

        const vaccinations = Array.isArray(c.vaccinations) ? c.vaccinations : [];
        const recordsHtml = vaccinations.length
            ? vaccinations.map(v => `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(v.vaccine || v.vaccine_name || v.name || 'Vaccine')} &bull; Dose ${escHtml(val(v.dose, '1'))}</p>
                        <p class="text-xs text-slate-400">${v.date_administered ? new Date(v.date_administered).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'}) : 'No date'} &bull; ${escHtml(val(v.administered_by, 'Health Worker'))}${v.batch_number ? ` &bull; <span class="font-mono text-slate-500">Lot: ${escHtml(v.batch_number)}</span>` : ''}</p>
                        ${v.notes ? `<p class="text-xs text-slate-600 mt-1">${escHtml(v.notes)}</p>` : ''}
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400">Next due:</span>
                        <p class="text-xs font-semibold text-brand-dark">${v.next_due_date ? new Date(v.next_due_date).toLocaleDateString() : '—'}</p>
                    </div>
                </div>
            `).join('')
            : `<p class="text-sm text-slate-500 text-center py-6">No vaccination records yet.</p>`;

        content.innerHTML = `
            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                    <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                        ${initials(c.first_name, c.last_name)}
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(c.first_name || '')} ${escHtml(c.last_name || '')}</p>
                        <p class="text-xs text-slate-400">${escHtml(c.child_id || '')} &bull; ${escHtml(c.age || calculateAge(c.birth_date))}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    ${recordsHtml}
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                    <button onclick="closeModal('vaccinationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    <button onclick="closeModal('vaccinationModal'); openRecordVaccination(${Number(c.id) || id})" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-plus mr-1.5"></i> Add Record
                    </button>
                </div>
            </div>
        `;
    }

    // ============================================================
    // OPEN "RECORD VACCINATION" FORM MODAL FOR A GIVEN CHILD
    // ============================================================
    async function openRecordVaccination(id) {
        document.getElementById('add_vacc_child_id').value = id;
        document.getElementById('vaccChildName').textContent = 'Loading Child...';
        document.getElementById('vaccChildSub').textContent = 'ID: --';
        document.getElementById('vaccChildInitials').textContent = '--';
        document.getElementById('recordVaccinationForm').reset();
        document.getElementById('add_vacc_child_id').value = id;
        openModal('recordVaccinationModal');

        const c = await fetchChild(id);
        if (!c) return;
        document.getElementById('vaccChildName').textContent = `${c.first_name || ''} ${c.last_name || ''}`.trim() || 'Unknown Child';
        document.getElementById('vaccChildSub').textContent = `ID: ${c.child_id || id}`;
        document.getElementById('vaccChildInitials').textContent = initials(c.first_name, c.last_name);
    }

    // ============================================================
    // SAVE A NEW VACCINATION RECORD
    // ============================================================
    async function saveVaccinationRecord(event) {
        event.preventDefault();
        const childId = document.getElementById('add_vacc_child_id')?.value || '';
        const vaccineName = document.getElementById('add_vacc_name')?.value || '';
        const payload = {
            child_id: childId,
            vaccine: vaccineName,
            name: vaccineName,
            dose: document.getElementById('add_vacc_dose')?.value || 1,
            date_administered: document.getElementById('add_vacc_date')?.value || new Date().toISOString().split('T')[0],
            next_due_date: document.getElementById('add_vacc_next_due')?.value || null,
            administered_by: document.getElementById('add_vacc_by')?.value || null,
            batch_number: document.getElementById('add_vacc_batch')?.value || null,
            health_center: document.getElementById('add_vacc_facility')?.value || 'Caloocan Main Health Center',
            facility: document.getElementById('add_vacc_facility')?.value || 'Caloocan Main Health Center',
            notes: document.getElementById('add_vacc_notes')?.value || null,
        };

        const submitBtn = event.target.querySelector('button[type="submit"]');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        try {
            const response = await fetch(`${API_BASE}?id=${encodeURIComponent(childId)}&action=record`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ ...payload, csrf_token: csrfToken })
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to save vaccination record');
            }
            toast.success('Vaccination recorded successfully.');
            closeModal('recordVaccinationModal');
            await refreshChildList();
        } catch (err) {
            toast.error(err.message || 'Failed to save vaccination record');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    // ============================================================
    // VIEW HEALTH & NUTRITION RECORDS
    // ============================================================
    async function viewHealthRecord(id) {
        const content = document.getElementById('healthRecordContent');
        openModal('healthRecordModal');
        content.innerHTML = `
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading health & nutrition records...
            </div>
        `;

        const [c, nutritionRes] = await Promise.all([
            fetchChild(id),
            fetch(`<?php echo site_url('api/nutrition.php'); ?>?child_id=${id}`).then(r => r.json()).catch(() => ({ data: [] }))
        ]);

        if (!c) {
            content.innerHTML = `
                <div class="text-center py-10 text-rose-500">
                    <i class="fa-solid fa-exclamation-circle text-2xl mb-2"></i>
                    <p>Failed to load child health records</p>
                </div>
            `;
            return;
        }

        const assessments = Array.isArray(nutritionRes.data) ? nutritionRes.data : [];
        const nutritionHtml = assessments.length
            ? assessments.map(a => `
                <div class="p-3 bg-white rounded-xl border border-slate-200 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${getNutritionClass(capitalize(a.nutrition_status || 'Normal'))}">
                                ${escHtml(capitalize(a.nutrition_status || 'Normal'))}
                            </span>
                            <span class="text-xs text-slate-400 font-medium">${a.assessment_date || 'Recent'}</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full font-medium ${a.risk_level === 'high' ? 'bg-rose-100 text-rose-700' : (a.risk_level === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700')}">
                            ${capitalize(a.risk_level || 'Low')} Risk
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs bg-slate-50 p-2 rounded-lg">
                        <div><span class="text-slate-400">Weight:</span> <strong class="text-slate-700">${Number(a.weight || 0).toFixed(1)} kg</strong></div>
                        <div><span class="text-slate-400">Height:</span> <strong class="text-slate-700">${Number(a.height || 0).toFixed(1)} cm</strong></div>
                        <div><span class="text-slate-400">BMI:</span> <strong class="text-slate-700">${Number(a.bmi || 0).toFixed(1)}</strong></div>
                    </div>
                    ${a.assessment_notes ? `<p class="text-xs text-slate-600 italic">“${escHtml(a.assessment_notes)}”</p>` : ''}
                    ${a.plan_of_action ? `<p class="text-xs text-brand-dark font-medium">📋 Plan: ${escHtml(a.plan_of_action)}</p>` : ''}
                </div>
            `).join('')
            : `<div class="text-center py-5 text-slate-400 text-xs bg-slate-50 rounded-xl border border-dashed border-slate-200">
                <p>No nutrition assessment records logged yet.</p>
               </div>`;

        const records = Array.isArray(c.health_records) ? c.health_records : [];
        const recordsHtml = records.length
            ? records.map(r => `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(r.type || 'Consultation')}</p>
                        <p class="text-xs text-slate-400">${r.date ? new Date(r.date).toLocaleDateString() : 'No date'} &bull; ${escHtml(val(r.doctor))}</p>
                        <p class="text-xs text-slate-600 mt-1">${escHtml(val(r.notes, ''))}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-400">Follow-up:</span>
                        <p class="text-xs font-semibold text-brand-dark">${r.follow_up ? new Date(r.follow_up).toLocaleDateString() : '—'}</p>
                    </div>
                </div>
            `).join('')
            : `<p class="text-xs text-slate-400 text-center py-3">No additional clinical consultation notes.</p>`;

        content.innerHTML = `
            <div class="space-y-4 max-h-[75vh] overflow-y-auto pr-1">
                <div class="flex items-center justify-between p-3 bg-brand-light/40 rounded-xl border border-brand-border">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-sm flex-shrink-0">
                            ${initials(c.first_name, c.last_name)}
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800 text-sm">${escHtml(c.first_name || '')} ${escHtml(c.last_name || '')}</p>
                            <p class="text-xs text-slate-400">${escHtml(c.child_id || '')} &bull; ${escHtml(c.age || calculateAge(c.birth_date))}</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold ${getNutritionClass(c.nutrition_status || 'Normal')}">
                        ${escHtml(c.nutrition_status || 'Normal')}
                    </span>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-apple-whole text-emerald-600"></i> Nutrition Assessment History
                        </h4>
                        <a href="<?php echo site_url('modules/immunization/nutrition_assessment.php'); ?>" class="text-xs text-brand-medium font-semibold hover:underline">
                            + Assess Nutrition
                        </a>
                    </div>
                    <div class="space-y-2">
                        ${nutritionHtml}
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-stethoscope text-blue-600"></i> Clinical Notes
                    </h4>
                    <div class="space-y-2">
                        ${recordsHtml}
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-200">
                    <button onclick="closeModal('healthRecordModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    <a href="<?php echo site_url('modules/immunization/nutrition_assessment.php'); ?>" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold inline-flex items-center gap-1.5">
                        <i class="fa-solid fa-plus text-xs"></i> New Nutrition Assessment
                    </a>
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
        const measurements = [
            ['birth_weight', 0.1, 999, 'kg'],
            ['birth_height', 20, 999, 'cm']
        ];
        for (const [field, minimum, maximum, unit] of measurements) {
            if (formData[field] !== null && formData[field] !== '' && (!/^\d{1,3}(\.\d{1,2})?$/.test(String(formData[field])) || Number(formData[field]) < minimum || Number(formData[field]) > maximum)) {
                toast.warning(`${field === 'birth_weight' ? 'Birth weight' : 'Birth height'} must be between ${minimum} and ${maximum} ${unit}.`);
                return;
            }
        }
        const submitBtn = event.target.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ ...formData, csrf_token: csrfToken })
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
                const zoneSelect = document.getElementById('child_zone');
                if (zoneSelect) zoneSelect.value = '';
                populateBarangayDropdown('child_barangay', '', '');
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
            ['archiveChild', 'fa-archive', 'text-amber-600 hover:bg-amber-50', 'Archive'],
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
            const response = await fetch(`${API_BASE}?page=1&limit=10`);
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
                        registrationDate: child.registration_date || '',
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
        const search = document.getElementById('searchChild').value.trim().toLowerCase();
        const gender = document.getElementById('filterGender').value.trim().toLowerCase();
        const nutrition = document.getElementById('filterNutrition').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value.trim().toLowerCase();
        const dateFrom = document.getElementById('registrationDateFrom').value;
        const dateTo = document.getElementById('registrationDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.child-row').forEach(row => {
            const d = row.dataset;
            const searchableFields = [d.name, d.id, d.mother, d.barangay]
                .map(value => String(value || '').toLowerCase());
            const matchesSearch = !search || searchableFields.some(field => field.includes(search));
            const isVisible = matchesSearch &&
                (!gender || String(d.gender || '').toLowerCase() === gender) &&
                (!nutrition || String(d.nutrition || '').toLowerCase() === nutrition) &&
                (!status || String(d.status || '').toLowerCase() === status) &&
                (!dateFrom || (d.registrationDate && d.registrationDate >= dateFrom)) &&
                (!dateTo || (d.registrationDate && d.registrationDate <= dateTo));

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        const tableWrapper = document.getElementById('tableWrapper');
        const emptySearchState = document.getElementById('emptySearchState');
        const showEmpty = visibleCount === 0 && tableWrapper && !tableWrapper.classList.contains('hidden');
        setVisible(emptySearchState, showEmpty, 'flex');
    }

    function resetFilters() {
        const url = new URL(window.location.href);
        if (url.searchParams.has('registration_date_from') || url.searchParams.has('registration_date_to')) {
            url.searchParams.delete('registration_date_from');
            url.searchParams.delete('registration_date_to');
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
            return;
        }
        document.getElementById('searchChild').value = '';
        document.getElementById('filterGender').value = '';
        document.getElementById('filterNutrition').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('registrationDateFrom').value = '';
        document.getElementById('registrationDateTo').value = '';
        document.querySelectorAll('.child-row').forEach(row => row.style.display = '');
        setVisible(document.getElementById('emptySearchState'), false, 'flex');
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo $totalPages; ?>) return;
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }

    function applyChildDateFilter() {
        const dateFrom = document.getElementById('registrationDateFrom').value;
        const dateTo = document.getElementById('registrationDateTo').value;

        if (dateFrom && dateTo && dateFrom > dateTo) {
            toast.warning('The start date must be before the end date.');
            return;
        }

        const url = new URL(window.location.href);
        if (dateFrom) {
            url.searchParams.set('registration_date_from', dateFrom);
        } else {
            url.searchParams.delete('registration_date_from');
        }
        if (dateTo) {
            url.searchParams.set('registration_date_to', dateTo);
        } else {
            url.searchParams.delete('registration_date_to');
        }
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    }

    function clearChildDateFilter() {
        document.getElementById('registrationDateFrom').value = '';
        document.getElementById('registrationDateTo').value = '';
        applyChildDateFilter();
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
        if (el) {
            el.value = el.value === value ? '' : value;
        }
        filterChildren();
    }

    // ============================================================
    // EXPORT FUNCTION
    // ============================================================
    async function exportChild(id) {
        try {
            toast.info('Preparing PDF export...');
            const response = await fetch(`${API_BASE}?id=${id}&export=pdf`);

            if (!response.ok) {
                let errMsg = 'Export failed';
                try {
                    const json = await response.json();
                    errMsg = json.message || errMsg;
                } catch (e) {}
                throw new Error(errMsg);
            }

            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `child_${id}_immunization.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            toast.success('Immunization card PDF downloaded successfully');
        } catch (err) {
            toast.error(err.message || 'Export failed');
        }
    }

    // ============================================================
    // BULK EXPORT CHILD MASTERLIST
    // ============================================================
    async function exportChildMasterlistData(format) {
        const scope = document.querySelector('input[name="childExportScope"]:checked')?.value || 'filtered';
        closeModal('exportChildMasterlistModal');

        if (scope === 'all') {
            const status = document.getElementById('filterStatus')?.value || '';
            const barangay = document.getElementById('filterBarangay')?.value || '';
            const url = `${API_BASE}?action=export_child_masterlist&format=${format}&status=${encodeURIComponent(status)}&barangay=${encodeURIComponent(barangay)}`;
            window.location.href = url;
            toast.info('Downloading full Child Masterlist export...');
            return;
        }

        // Filtered view from DOM
        const rows = Array.from(document.querySelectorAll('#childTableBody tr:not([id="emptyState"])'));
        const visibleRows = rows.filter(r => r.style.display !== 'none');

        if (!visibleRows || visibleRows.length === 0) {
            toast.warning('No child records visible to export.');
            return;
        }

        const headers = [
            'Child ID',
            'Full Name',
            'Gender / Age',
            'Barangay',
            'Parent Details',
            'Nutrition Status',
            'Vaccine Compliance',
            'Status'
        ];

        const dataRows = visibleRows.map(r => {
            const cells = r.querySelectorAll('td');
            if (cells.length < 7) return null;
            const id = cells[0]?.textContent.trim();
            const name = cells[1]?.querySelector('p.font-semibold')?.textContent.trim() || cells[1]?.textContent.trim();
            const genderAge = cells[2]?.textContent.trim();
            const barangay = cells[3]?.textContent.trim();
            const parents = cells[4]?.textContent.trim();
            const nutrition = cells[5]?.textContent.trim();
            const compliance = cells[6]?.textContent.trim();

            return [id, name, genderAge, barangay, parents, nutrition, compliance, 'Active'];
        }).filter(Boolean);

        const stamp = new Date().toISOString().slice(0, 10);
        const filename = `child_masterlist_filtered_${stamp}`;

        if (format === 'csv' || format === 'excel') {
            const escapeCsv = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
            const csv = [headers, ...dataRows].map(row => row.map(escapeCsv).join(',')).join('\n') + '\n';
            const mimeType = format === 'excel' ? 'application/vnd.ms-excel' : 'text/csv;charset=utf-8;';
            const ext = format === 'excel' ? 'xls' : 'csv';

            const blob = new Blob(['\uFEFF' + csv], { type: mimeType });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `${filename}.${ext}`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            toast.success(`Exported ${dataRows.length} child record(s) to ${ext.toUpperCase()} successfully!`);
        } else if (format === 'pdf') {
            const printWindow = window.open('', '_blank', 'width=950,height=750');
            if (!printWindow) {
                toast.warning('Please allow pop-ups to open the report document');
                return;
            }
            const esc = str => String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            const tableRowsHtml = dataRows.map(r => `<tr>${r.map(val => `<td style="padding:6px 8px; border:1px solid #cbd5e1; font-size:11px;">${esc(val)}</td>`).join('')}</tr>`).join('');
            const headerHtml = headers.map(h => `<th style="padding:8px; background:#0B4F4A; color:white; font-size:11px; text-align:left; border:1px solid #0B4F4A;">${esc(h)}</th>`).join('');

            printWindow.document.write(`
                <!doctype html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Child Health Masterlist - ${stamp}</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 24px; color: #1e293b; }
                        h2 { margin: 0 0 4px 0; color: #0B4F4A; }
                        p { margin: 0 0 16px 0; font-size: 12px; color: #64748b; }
                        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                        @media print { body { margin: 0; } }
                    </style>
                </head>
                <body>
                    <h2>Caloocan Health Center — Child Health &amp; Nutrition Masterlist</h2>
                    <p>Generated: ${new Date().toLocaleString()} | Filtered Records: ${dataRows.length}</p>
                    <table>
                        <thead><tr>${headerHtml}</tr></thead>
                        <tbody>${tableRowsHtml}</tbody>
                    </table>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
            }, 300);
            toast.info(`Opened printable masterlist with ${dataRows.length} records.`);
        }
    }

    // ============================================================
    // ARCHIVE / DELETE (shared confirm + PATCH/DELETE flow)
    // ============================================================
    let pendingConfirmation = null;

    function requestConfirmation(modalId) {
        return new Promise(resolve => {
            pendingConfirmation = resolve;
            openModal(modalId);
        });
    }

    function resolveConfirmation(confirmed) {
        const resolve = pendingConfirmation;
        pendingConfirmation = null;
        closeModal('archiveChildModal');
        if (resolve) resolve(confirmed);
    }

    async function confirmAndSend(id, { confirmMsg, method, body, successMsg, failMsg }) {
        const modalId = 'archiveChildModal';
        if (!await requestConfirmation(modalId)) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        try {
            const headers = {
                'X-CSRF-Token': csrfToken
            };
            if (body) {
                headers['Content-Type'] = 'application/json';
            }
            const reqBody = body ? { ...body, csrf_token: csrfToken } : { csrf_token: csrfToken };
            const response = await fetch(`${API_BASE}?id=${id}`, {
                method,
                headers: headers,
                body: JSON.stringify(reqBody)
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
</script>

<?php include_once '../../includes/footer.php'; ?>