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

// Base Children Data
$children = [];

try {
    $db = Database::getInstance();
    $dbChildren = $db->query('children', 'GET');
    if (!empty($dbChildren) && is_array($dbChildren)) {
        foreach ($dbChildren as $c) {
            $cId = (int)$c['id'];
            $birthDate = $c['birth_date'] ?? date('Y-m-d');
            $birth = new DateTime($birthDate);
            $today = new DateTime();
            $diff = $today->diff($birth);
            $ageStr = $diff->y > 0 ? "{$diff->y} yrs {$diff->m} mos" : "{$diff->m} mos";

            $children[] = [
                'id' => $cId,
                'child_id' => $c['child_id'] ?? ('CH-' . sprintf('%03d', $cId)),
                'name' => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
                'gender' => !empty($c['gender']) ? ucfirst(strtolower($c['gender'])) : 'Female',
                'age' => $ageStr,
                'birth_date' => $birthDate
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Supabase children query exception: ' . $e->getMessage());
}

if (empty($children)) {
    $children = [
        ['id' => 1, 'child_id' => 'CH-001', 'name' => 'Sofia Garcia', 'gender' => 'Female', 'age' => '2 yrs 4 mos', 'birth_date' => '2024-03-15'],
        ['id' => 2, 'child_id' => 'CH-002', 'name' => 'Luis Mendoza', 'gender' => 'Male', 'age' => '1 yr 3 mos', 'birth_date' => '2025-04-20'],
        ['id' => 3, 'child_id' => 'CH-003', 'name' => 'Emma Lim', 'gender' => 'Female', 'age' => '3 yrs 1 mo', 'birth_date' => '2023-06-01']
    ];
}

// Real Nutrition Assessments from Database
$nutritionAssessments = [];
try {
    $db = Database::getInstance();
    $dbAssessments = $db->select('nutrition_assessments', [], ['order' => 'assessment_date.desc']);
    $childrenMap = array_column($children, null, 'id');

    if (!empty($dbAssessments) && is_array($dbAssessments)) {
        foreach ($dbAssessments as $a) {
            $cId = (int)($a['child_id'] ?? 0);
            $child = $childrenMap[$cId] ?? null;
            $cName = $child ? $child['name'] : ('Child #' . $cId);
            $cAge = $child ? $child['age'] : '—';
            $initials = 'CH';
            if ($child && !empty($child['name'])) {
                $parts = explode(' ', $child['name']);
                $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
            }

            $supplements = [];
            if (!empty($a['supplements'])) {
                $supplements = is_array($a['supplements']) ? $a['supplements'] : json_decode($a['supplements'], true);
            }
            if (!is_array($supplements)) $supplements = [];

            $nutritionAssessments[] = [
                'id' => (int)$a['id'],
                'child_id' => $cId,
                'child_name' => $cName,
                'child_avatar' => $initials,
                'date' => $a['assessment_date'],
                'age' => $cAge,
                'weight' => (float)$a['weight'],
                'height' => (float)$a['height'],
                'bmi' => (float)($a['bmi'] ?? 0),
                'weight_percentile' => (int)($a['weight_percentile'] ?? 50),
                'height_percentile' => (int)($a['height_percentile'] ?? 50),
                'nutrition_status' => strtolower($a['nutrition_status'] ?? 'normal'),
                'risk_level' => strtolower($a['risk_level'] ?? 'low'),
                'assessment_notes' => $a['assessment_notes'] ?? '',
                'plan_of_action' => $a['plan_of_action'] ?? '',
                'supplements' => $supplements,
                'next_assessment' => $a['next_assessment_date'] ?? null,
                'assessed_by' => $a['assessed_by'] ?? 'Staff',
                'status' => $a['status'] ?? 'active'
            ];
        }
    }
} catch (\Throwable $e) {
    error_log('Error querying nutrition_assessments: ' . $e->getMessage());
}

// Sample Supplement Inventory
$supplementInventory = [
    ['id' => 1, 'name' => 'Vitamin A', 'category' => 'Vitamin', 'stock' => 150, 'min_stock' => 50, 'unit' => 'capsules'],
    ['id' => 2, 'name' => 'Iron', 'category' => 'Mineral', 'stock' => 200, 'min_stock' => 60, 'unit' => 'tablets'],
    ['id' => 3, 'name' => 'Zinc', 'category' => 'Mineral', 'stock' => 120, 'min_stock' => 40, 'unit' => 'tablets'],
    ['id' => 4, 'name' => 'Vitamin D', 'category' => 'Vitamin', 'stock' => 180, 'min_stock' => 50, 'unit' => 'capsules'],
    ['id' => 5, 'name' => 'Multivitamin', 'category' => 'Vitamin', 'stock' => 90, 'min_stock' => 30, 'unit' => 'tablets'],
    ['id' => 6, 'name' => 'Calcium', 'category' => 'Mineral', 'stock' => 160, 'min_stock' => 40, 'unit' => 'tablets'],
    ['id' => 7, 'name' => 'Folic Acid', 'category' => 'Vitamin', 'stock' => 75, 'min_stock' => 25, 'unit' => 'tablets'],
];

// Stats
$totalAssessments = count($nutritionAssessments);
$normalStatus = count(array_filter($nutritionAssessments, fn($a) => $a['nutrition_status'] === 'normal'));
$moderateStatus = count(array_filter($nutritionAssessments, fn($a) => $a['nutrition_status'] === 'moderate'));
$criticalStatus = count(array_filter($nutritionAssessments, fn($a) => $a['nutrition_status'] === 'critical'));
$activePlans = count(array_filter($nutritionAssessments, fn($a) => $a['status'] === 'active'));

$title = 'Nutrition Assessment';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Nutrition Assessment</h2>
            <p class="text-sm text-slate-500 mt-0.5">Screen, detect malnutrition, plan interventions & track supplements</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('nutritionScreeningModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-clipboard-list text-xs"></i> New Screening
            </button>
            <button onclick="openModal('supplementTrackingModal')"
                    class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-pills text-xs"></i> Supplements
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <!-- Card 1: Total Assessments -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-clipboard-list text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalAssessments; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Assessments</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📋 All assessments</span>
                    <span class="text-[10px] text-slate-400"><?php echo $activePlans; ?> active plans</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Normal -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $normalStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Normal</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Healthy</span>
                    <span class="text-[10px] text-slate-400">On track</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Moderate -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $moderateStatus; ?></p>
                        <p class="text-xs font-medium text-slate-500">Moderate</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⚠️ Monitor</span>
                    <span class="text-[10px] text-slate-400">Needs attention</span>
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
                    <span class="text-[10px] text-slate-400">Immediate intervention</span>
                </div>
            </div>
        </div>

        <!-- Card 5: Active Plans -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-brand-light rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-light">
                        <i class="fa-solid fa-clipboard text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-brand-dark"><?php echo $activePlans; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active Plans</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-brand-light text-brand-dark rounded-full text-[10px] font-bold">📋 In progress</span>
                    <span class="text-[10px] text-slate-400">Currently monitored</span>
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
                <span class="font-bold"><?php echo $criticalStatus; ?></span> child(ren) require immediate nutrition intervention
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='critical'; filterAssessments();" 
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
                       id="searchNutrition"
                       placeholder="Search by child name or ID..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="normal">Normal</option>
                    <option value="moderate">Moderate</option>
                    <option value="critical">Critical</option>
                </select>
                <select id="filterRisk" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Risk</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
                <button type="button" onclick="openModal('assessmentDateFilterModal')" title="Filter by assessment date"
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

    <!-- Nutrition Assessments Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Child</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Weight (kg)</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Height (cm)</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Risk</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Next Assessment</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="nutritionTableBody">
                    <?php foreach ($nutritionAssessments as $assessment): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors nutrition-row <?php echo $assessment['nutrition_status'] === 'critical' ? 'bg-rose-50/50' : ''; ?>"
                        data-child="<?php echo strtolower($assessment['child_name']); ?>"
                        data-status="<?php echo $assessment['nutrition_status']; ?>"
                        data-risk="<?php echo $assessment['risk_level']; ?>"
                        data-date="<?php echo htmlspecialchars($assessment['date']); ?>"
                        data-id="<?php echo htmlspecialchars($assessment['child_id']); ?>">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                                    <?php echo $assessment['child_avatar']; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 text-sm"><?php echo $assessment['child_name']; ?></p>
                                    <p class="text-xs text-slate-400"><?php echo $assessment['age']; ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo date('M d, Y', strtotime($assessment['date'])); ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs font-medium"><?php echo $assessment['weight']; ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs"><?php echo $assessment['height']; ?></td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'normal' => 'bg-emerald-100 text-emerald-700',
                                    'moderate' => 'bg-amber-100 text-amber-700',
                                    'critical' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$assessment['nutrition_status']] ?? $statusColors['normal']; ?>">
                                <?php echo ucfirst($assessment['nutrition_status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <?php
                                $riskColors = [
                                    'low' => 'bg-emerald-100 text-emerald-700',
                                    'medium' => 'bg-amber-100 text-amber-700',
                                    'high' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $riskColors[$assessment['risk_level']] ?? $riskColors['low']; ?>">
                                <?php echo ucfirst($assessment['risk_level']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs">
                            <?php echo $assessment['next_assessment'] ? date('M d, Y', strtotime($assessment['next_assessment'])) : '—'; ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewAssessment(<?php echo $assessment['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <button onclick="editAssessment(<?php echo $assessment['id']; ?>)"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition" title="Edit">
                                    <i class="fa-solid fa-pen text-sm"></i>
                                </button>
                                <?php if ($assessment['nutrition_status'] === 'critical'): ?>
                                    <button onclick="emergencyIntervention(<?php echo $assessment['id']; ?>)"
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition" title="Emergency Intervention">
                                        <i class="fa-solid fa-truck-medical text-sm"></i>
                                    </button>
                                <?php endif; ?>
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
                <i class="fa-solid fa-apple-alt text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No assessments match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo $totalAssessments; ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalAssessments; ?></span> assessments
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
<!-- NUTRITION SCREENING MODAL                                    -->
<!-- ============================================================ -->
<div id="nutritionScreeningModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-brand-medium"></i>
                Nutrition Screening
            </h3>
            <button onclick="closeModal('nutritionScreeningModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="nutritionScreeningForm" class="p-6 space-y-4" onsubmit="saveNutritionScreening(event)">
            <div>

                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Child</label>
                <select id="screen_child" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Child</option>
                    <?php foreach ($children as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?> (<?php echo $c['child_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Date</label>
                <input type="date" id="screen_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Weight (kg)</label>
                    <input type="number" id="screen_weight" min="0.1" max="999" step="0.1" inputmode="decimal" oninput="limitNutritionMeasurement(this)" required title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Height (cm)</label>
                    <input type="number" id="screen_height" min="20" max="999" step="0.1" inputmode="decimal" oninput="limitNutritionMeasurement(this)" required title="Maximum 3 whole-number digits" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Nutrition Status</label>
                <select id="screen_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="normal">Normal</option>
                    <option value="moderate">Moderate</option>
                    <option value="critical">Critical</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Risk Level</label>
                <select id="screen_risk" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Notes</label>
                <textarea id="screen_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Observations and findings..."></textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Next Assessment Date</label>
                <input type="date" id="screen_next" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('nutritionScreeningModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-check mr-1.5"></i> Save Assessment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ASSESSMENT DATE FILTER MODAL -->
<div id="assessmentDateFilterModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-calendar-days text-brand-medium"></i> Assessment Date Filter</h3>
            <button type="button" onclick="closeModal('assessmentDateFilterModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <div><label for="assessmentDateFrom" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">From</label><input type="date" id="assessmentDateFrom" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
            <div><label for="assessmentDateTo" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">To</label><input type="date" id="assessmentDateTo" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="clearAssessmentDateFilter()" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">Clear</button>
                <button type="button" onclick="applyAssessmentDateFilter()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">Apply Filter</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT ASSESSMENT MODAL -->
<div id="editAssessmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2"><i class="fa-solid fa-pen text-brand-medium"></i> Edit Assessment</h3>
            <button type="button" onclick="closeModal('editAssessmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="saveEditedAssessment(event)">
            <input type="hidden" id="edit_assessment_id">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Date</label><input type="date" id="edit_assessment_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Weight (kg)</label><input type="number" id="edit_assessment_weight" min="0.1" max="999" step="0.1" required oninput="limitNutritionMeasurement(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Height (cm)</label><input type="number" id="edit_assessment_height" min="20" max="999" step="0.1" required oninput="limitNutritionMeasurement(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label><select id="edit_assessment_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="normal">Normal</option><option value="moderate">Moderate</option><option value="critical">Critical</option></select></div>
                <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Risk Level</label><select id="edit_assessment_risk" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option></select></div>
            </div>
            <div><label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Assessment Notes</label><textarea id="edit_assessment_notes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm"></textarea></div>
            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4"><button type="button" onclick="closeModal('editAssessmentModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="submit" class="px-4 py-2 bg-brand-dark text-white rounded-lg text-sm font-semibold">Save Changes</button></div>
        </form>
    </div>
</div>

<!-- EMERGENCY INTERVENTION MODAL -->
<div id="emergencyInterventionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-3"><div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center"><i class="fa-solid fa-truck-medical text-xl"></i></div><div><h3 class="font-bold text-slate-900">Emergency Intervention</h3><p id="emergencyAssessmentChild" class="text-sm text-slate-500"></p></div></div>
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mt-5 mb-1">Intervention Notes</label>
            <textarea id="emergencyInterventionNotes" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm" placeholder="Describe the immediate action...">Immediate nutrition intervention required.</textarea>
            <div class="flex justify-end gap-2 mt-5"><button type="button" onclick="closeModal('emergencyInterventionModal')" class="px-4 py-2 border border-slate-200 rounded-lg text-sm font-semibold">Cancel</button><button type="button" onclick="confirmEmergencyIntervention()" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-semibold">Initiate Intervention</button></div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW ASSESSMENT MODAL                                        -->
<!-- ============================================================ -->
<div id="viewAssessmentModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Nutrition Assessment Details</h3>
            <button onclick="closeModal('viewAssessmentModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="assessmentDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SUPPLEMENT TRACKING MODAL                                    -->
<!-- ============================================================ -->
<div id="supplementTrackingModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-pills text-brand-medium"></i>
                Supplement Tracking
            </h3>
            <button onclick="closeModal('supplementTrackingModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-4">
                <?php foreach ($supplementInventory as $supp): ?>
                <div class="bg-white rounded-xl shadow-xs p-3 border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-800 text-sm"><?php echo $supp['name']; ?></p>
                            <p class="text-xs text-slate-400"><?php echo $supp['category']; ?></p>
                        </div>
                        <span class="text-xs font-bold <?php echo $supp['stock'] <= $supp['min_stock'] ? 'text-rose-600' : 'text-slate-600'; ?>">
                            <?php echo $supp['stock']; ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <div class="w-full bg-slate-200 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full <?php echo $supp['stock'] <= $supp['min_stock'] ? 'bg-rose-500' : 'bg-emerald-500'; ?>" 
                                 style="width: <?php echo min(100, ($supp['stock'] / ($supp['min_stock'] * 2)) * 100); ?>%"></div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[10px] text-slate-400">Min: <?php echo $supp['min_stock']; ?></span>
                        <button onclick="adjustSupplementStock(<?php echo $supp['id']; ?>)" class="text-[10px] text-brand-medium hover:text-brand-dark">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <h4 class="text-sm font-bold text-slate-700 mb-2">📋 Supplement Distribution Summary</h4>
                <div class="space-y-2">
                    <?php 
                        $distributed = [];
                        foreach ($nutritionAssessments as $a) {
                            foreach ($a['supplements'] as $s) {
                                $distributed[$s] = ($distributed[$s] ?? 0) + 1;
                            }
                        }
                        arsort($distributed);
                    ?>
                    <?php foreach (array_slice($distributed, 0, 5) as $name => $count): ?>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-600"><?php echo $name; ?></span>
                        <span class="font-semibold text-brand-dark"><?php echo $count; ?> children</span>
                    </div>
                    <?php endforeach; ?>
                </div>
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
    const ASSESSMENTS = <?php echo json_encode(array_column($nutritionAssessments, null, 'id'), JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK); ?>;

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
    // VIEW ASSESSMENT
    // ============================================================
    function viewAssessment(id) {
        openModal('viewAssessmentModal');
        const a = ASSESSMENTS[id];
        if (!a) return;

        setTimeout(() => {
            const statusColors = {
                normal: 'bg-emerald-100 text-emerald-700',
                moderate: 'bg-amber-100 text-amber-700',
                critical: 'bg-rose-100 text-rose-700'
            };
            const riskColors = {
                low: 'bg-emerald-100 text-emerald-700',
                medium: 'bg-amber-100 text-amber-700',
                high: 'bg-rose-100 text-rose-700'
            };
            const supplementsHtml = a.supplements.map(s => `<span class="px-2 py-1 bg-brand-light/40 rounded text-xs border border-brand-border">${s}</span>`).join('');

            document.getElementById('assessmentDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">
                            ${a.child_avatar}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${a.child_name}</h4>
                            <p class="text-sm text-slate-500">${a.age} • Assessment Date: ${new Date(a.date).toLocaleDateString()}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[a.nutrition_status] || statusColors.normal}">
                                ${a.nutrition_status.toUpperCase()}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold ml-1 ${riskColors[a.risk_level] || riskColors.low}">
                                Risk: ${a.risk_level.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><p class="text-xs text-slate-400 font-semibold">Weight</p><p class="text-sm font-bold text-slate-800">${a.weight} kg</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Height</p><p class="text-sm font-bold text-slate-800">${a.height} cm</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">BMI</p><p class="text-sm font-bold text-slate-800">${a.bmi}</p></div>
                        <div><p class="text-xs text-slate-400 font-semibold">Percentiles</p><p class="text-sm font-bold text-slate-800">W: ${a.weight_percentile}% / H: ${a.height_percentile}%</p></div>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">📝 Assessment Notes</h5>
                        <p class="text-sm text-slate-800">${a.assessment_notes}</p>
                    </div>
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">📋 Nutrition Plan</h5>
                        <p class="text-sm text-slate-800">${a.plan_of_action}</p>
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">💊 Supplements</h5>
                        <div class="flex flex-wrap gap-2">${supplementsHtml}</div>
                        <p class="text-xs text-slate-400 mt-2">Next Assessment: ${a.next_assessment ? new Date(a.next_assessment).toLocaleDateString() : '—'}</p>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewAssessmentModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        ${a.nutrition_status === 'critical' ? `<button onclick="closeModal('viewAssessmentModal'); emergencyIntervention(${a.id})" class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition text-sm font-semibold"><i class="fa-solid fa-truck-medical mr-1.5"></i> Emergency Intervention</button>` : ''}
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // EDIT ASSESSMENT
    // ============================================================
    function editAssessment(id) {
        const a = ASSESSMENTS[id];
        if (!a) return;
        document.getElementById('edit_assessment_id').value = a.id;
        document.getElementById('edit_assessment_date').value = a.date;
        document.getElementById('edit_assessment_weight').value = a.weight;
        document.getElementById('edit_assessment_height').value = a.height;
        document.getElementById('edit_assessment_status').value = a.nutrition_status;
        document.getElementById('edit_assessment_risk').value = a.risk_level;
        document.getElementById('edit_assessment_notes').value = a.assessment_notes || '';
        openModal('editAssessmentModal');
    }

    async function saveEditedAssessment(event) {
        event.preventDefault();
        const id = document.getElementById('edit_assessment_id').value;
        const weight = document.getElementById('edit_assessment_weight').value;
        const height = document.getElementById('edit_assessment_height').value;
        const validMeasurement = (value, minimum) =>
            /^\d{1,3}(\.\d{1,2})?$/.test(value) && Number(value) >= minimum && Number(value) <= 999;
        if (!validMeasurement(weight, 0.1) || !validMeasurement(height, 20)) {
            showToast('Weight must be 0.1-999 kg and height must be 20-999 cm.', 'warning');
            return;
        }

        const payload = {
            date: document.getElementById('edit_assessment_date').value,
            weight: Number(weight),
            height: Number(height),
            nutrition_status: document.getElementById('edit_assessment_status').value,
            risk_level: document.getElementById('edit_assessment_risk').value,
            assessment_notes: document.getElementById('edit_assessment_notes').value.trim()
        };

        try {
            const res = await fetch(`/api/nutrition.php?id=${id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeModal('editAssessmentModal');
                showToast('Nutrition assessment updated successfully!', 'success');
                const a = ASSESSMENTS[id];
                if (a) {
                    a.date = payload.date;
                    a.weight = payload.weight;
                    a.height = payload.height;
                    a.nutrition_status = payload.nutrition_status;
                    a.risk_level = payload.risk_level;
                    a.assessment_notes = payload.assessment_notes;
                }
                filterAssessments();
            } else {
                showToast(data.message || 'Failed to update assessment.', 'danger');
            }
        } catch (err) {
            console.error('Update assessment error:', err);
            showToast('Error connecting to server.', 'danger');
        }
    }

    // ============================================================
    // EMERGENCY INTERVENTION
    // ============================================================
    function emergencyIntervention(id) {
        const a = ASSESSMENTS[id];
        if (!a) return;
        document.getElementById('emergencyInterventionModal').dataset.assessmentId = id;
        document.getElementById('emergencyAssessmentChild').textContent = a.child_name + ' requires immediate attention.';
        document.getElementById('emergencyInterventionNotes').value = a.plan_of_action || 'Immediate nutrition intervention required.';
        openModal('emergencyInterventionModal');
    }

    async function confirmEmergencyIntervention() {
        const modal = document.getElementById('emergencyInterventionModal');
        const id = modal.dataset.assessmentId;
        const notes = document.getElementById('emergencyInterventionNotes').value.trim();

        try {
            const res = await fetch(`/api/nutrition.php?id=${id}&action=emergency`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ plan_of_action: notes })
            });
            const data = await res.json();
            if (data.success) {
                closeModal('emergencyInterventionModal');
                showToast('Emergency intervention recorded!', 'success');
                const a = ASSESSMENTS[id];
                if (a) {
                    a.plan_of_action = notes;
                    a.nutrition_status = 'critical';
                    a.risk_level = 'high';
                }
                filterAssessments();
            } else {
                showToast(data.message || 'Failed to record intervention.', 'danger');
            }
        } catch (err) {
            console.error('Emergency intervention error:', err);
            showToast('Error connecting to server.', 'danger');
        }
    }

    // ============================================================
    // NUTRITION SCREENING
    // ============================================================
    async function saveNutritionScreening(event) {
        event.preventDefault();
        const childId = document.getElementById('screen_child').value;
        const weight = document.getElementById('screen_weight').value;
        const height = document.getElementById('screen_height').value;
        const date = document.getElementById('screen_date').value;
        const status = document.getElementById('screen_status').value;
        const risk = document.getElementById('screen_risk').value;
        const notes = document.getElementById('screen_notes').value;
        const nextDate = document.getElementById('screen_next').value;

        if (!childId) {
            showToast('Please select a child profile.', 'warning');
            return;
        }

        const validMeasurement = (value, minimum) =>
            /^\d{1,3}(\.\d{1,2})?$/.test(value) && Number(value) >= minimum && Number(value) <= 999;
        if (!validMeasurement(weight, 0.1) || !validMeasurement(height, 20)) {
            showToast('Weight must be 0.1-999 kg and height must be 20-999 cm.', 'warning');
            return;
        }

        const payload = {
            child_id: Number(childId),
            date: date || new Date().toISOString().split('T')[0],
            weight: Number(weight),
            height: Number(height),
            nutrition_status: status,
            risk_level: risk,
            assessment_notes: notes,
            next_assessment: nextDate,
            supplements: []
        };

        try {
            const res = await fetch('/api/nutrition.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closeModal('nutritionScreeningModal');
                showToast('Nutrition assessment saved successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to save assessment.', 'danger');
            }
        } catch (err) {
            console.error('Save assessment error:', err);
            showToast('Error saving assessment to server.', 'danger');
        }
    }

    function limitNutritionMeasurement(input) {
        const parts = String(input.value || '').split('.');
        const whole = parts[0].replace(/\D/g, '').slice(0, 3);
        const fraction = parts[1] ? parts[1].replace(/\D/g, '').slice(0, 2) : '';
        input.value = parts.length > 1 ? `${whole}.${fraction}` : whole;
    }

    // ============================================================
    // SUPPLEMENT TRACKING
    // ============================================================
    function adjustSupplementStock(id) {
        showToast('Adjust supplement stock for ID: ' + id, 'info');
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
    document.getElementById('searchNutrition').addEventListener('input', filterAssessments);
    document.getElementById('filterStatus').addEventListener('change', filterAssessments);
    document.getElementById('filterRisk').addEventListener('change', filterAssessments);

    function filterAssessments() {
        const search = document.getElementById('searchNutrition').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const risk = document.getElementById('filterRisk').value;
        const dateFrom = document.getElementById('assessmentDateFrom').value;
        const dateTo = document.getElementById('assessmentDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.nutrition-row').forEach(row => {
            const child = row.dataset.child;
            const rowStatus = row.dataset.status;
            const rowRisk = row.dataset.risk;
            const rowDate = row.dataset.date || '';
            const rowId = (row.dataset.id || '').toLowerCase();

            const matchesSearch = !search || child.includes(search) || rowId.includes(search);
            const matchesStatus = !status || rowStatus === status;
            const matchesRisk = !risk || rowRisk === risk;
            const matchesDateFrom = !dateFrom || (rowDate && rowDate >= dateFrom);
            const matchesDateTo = !dateTo || (rowDate && rowDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesRisk && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function resetFilters() {
        document.getElementById('searchNutrition').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterRisk').value = '';
        document.getElementById('assessmentDateFrom').value = '';
        document.getElementById('assessmentDateTo').value = '';
        document.querySelectorAll('.nutrition-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    function applyAssessmentDateFilter() {
        const dateFrom = document.getElementById('assessmentDateFrom').value;
        const dateTo = document.getElementById('assessmentDateTo').value;
        if (dateFrom && dateTo && dateFrom > dateTo) {
            showToast('The start date must be before the end date.', 'warning');
            return;
        }
        closeModal('assessmentDateFilterModal');
        filterAssessments();
    }

    function clearAssessmentDateFilter() {
        document.getElementById('assessmentDateFrom').value = '';
        document.getElementById('assessmentDateTo').value = '';
        closeModal('assessmentDateFilterModal');
        filterAssessments();
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
        const dateInput = document.getElementById('screen_date');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        const nextInput = document.getElementById('screen_next');
        if (nextInput) {
            const date = new Date();
            date.setMonth(date.getMonth() + 3);
            nextInput.value = date.toISOString().split('T')[0];
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>