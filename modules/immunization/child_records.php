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
const DEFAULT_LIMIT = 5;

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
                        <input type="number" id="child_birth_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Height (cm)</label>
                        <input type="number" id="child_birth_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
                        <input type="number" id="edit_birth_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Birth Height (cm)</label>
                        <input type="number" id="edit_birth_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitMeasurementInput(this)" title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
            birth_height: 'edit_birth_height', blood_type: 'edit_blood_type', barangay: 'edit_barangay',
            address: 'edit_address', mother_name: 'edit_mother_name', mother_contact: 'edit_mother_contact',
            mother_occupation: 'edit_mother_occupation', father_name: 'edit_father_name',
            father_contact: 'edit_father_contact', father_occupation: 'edit_father_occupation',
            family_history: 'edit_family_history', allergies: 'edit_allergies'
        };
        Object.entries(fieldMap).forEach(([key, elId]) => {
            const el = document.getElementById(elId);
            if (el) el.value = c[key] ?? '';
        });

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
                        <p class="font-semibold text-slate-800 text-sm">${escHtml(v.name || v.vaccine_name || 'Vaccine')} &bull; Dose ${escHtml(val(v.dose, '1'))}</p>
                        <p class="text-xs text-slate-400">${v.date_administered ? new Date(v.date_administered).toLocaleDateString() : 'No date'} &bull; ${escHtml(val(v.administered_by))}</p>
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
        if (submitBtn) submitBtn.disabled = true;
        try {
            const response = await fetch(`${API_BASE}?id=${encodeURIComponent(childId)}&action=record`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
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
    // PRINT CHILD RECORD
    // ============================================================
    async function printChild(id) {
        const c = await fetchChild(id);
        if (!c) return;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head><title>Child Record - ${escHtml(c.child_id || '')}</title></head>
            <body style="font-family: sans-serif; padding: 24px;">
                <h2>${escHtml(c.first_name || '')} ${escHtml(c.last_name || '')}</h2>
                <p>ID: ${escHtml(c.child_id || '')}</p>
                <p>Gender: ${escHtml(c.gender || '')}</p>
                <p>Birth Date: ${escHtml(c.birth_date || '')}</p>
                <p>Barangay: ${escHtml(c.barangay || '')}</p>
                <p>Mother: ${escHtml(c.mother_name || '')}</p>
                <p>Father: ${escHtml(c.father_name || '')}</p>
                <p>Nutrition Status: ${escHtml(c.nutrition_status || '')}</p>
                <p>Vaccine Compliance: ${escHtml(String(c.vaccine_compliance ?? ''))}%</p>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
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
</script>

<?php include_once '../../includes/footer.php'; ?>