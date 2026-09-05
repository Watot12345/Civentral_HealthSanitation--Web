<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Constants/Permissions.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';

use App\Constants\Permissions;
use App\Services\PermissionService;
use App\Services\DepartmentResolver;

$permService = PermissionService::getInstance();
if (!$permService->hasPermission(Permissions::REPORTS_VIEW) && !$permService->hasPermission(Permissions::ANALYTICS_VIEW)) {
    header('Location: ' . site_url('pages/dashboard.php'));
    exit;
}

$userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? '');
$userRole     = trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? 'Staff Member');
$userName     = trim($_SESSION['user']['name'] ?? ($_SESSION['name'] ?? ($_SESSION['full_name'] ?? 'Staff Member')));
$userId       = (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));

$isAdmin    = $permService->isAdminRole($userRoleDesc) || $permService->isAdminRole($userRole);
$isDirector = !$isAdmin && $permService->isHeadOrAdminRole($userRoleDesc);
$isStaff    = !$isAdmin && !$isDirector;
$roleTier   = $isAdmin ? 'admin' : ($isDirector ? 'director' : 'staff');

$canAllDepts  = $permService->hasPermission('reports.all_departments');
$canAllFacs   = $permService->hasPermission('reports.all_facilities');
$canExport    = $permService->hasPermission('reports.export');
$canAnalytics = $permService->hasPermission('reports.analytics');
$canCreateTpl = $permService->hasPermission('reports.template.create');
$canEditTpl   = $permService->hasPermission('reports.template.edit');
$canDeleteTpl = $permService->hasPermission('reports.template.delete');
$canUseTpl    = $permService->hasPermission('reports.template.use');

$deptResolver = DepartmentResolver::getInstance();
$assignedDept = $deptResolver->resolveDepartmentName();
$assignedDeptSlug = $deptResolver->resolveDepartmentSlug();

// Assigned facility mapping
$assignedFacility = trim($_SESSION['facility'] ?? $_SESSION['user']['facility'] ?? '');
if (empty($assignedFacility)) {
    $assignedFacility = match($assignedDept) {
        'Health Center Services' => 'Central Health Center',
        'Sanitation Permits'    => 'South Sanitation Depot',
        'Immunization & Nutrition' => 'Central Health Center',
        'Wastewater Services'   => 'South Sanitation Depot',
        'Health Surveillance'   => 'Central Health Center',
        default => 'All Departments & Facilities'
    };
}

// Export Formats
$exportFormats = [
    'pdf' => 'PDF Document (.pdf)',
    'excel' => 'Excel Spreadsheet (.xlsx)',
    'word' => 'Word Document (.docx)',
    'csv' => 'CSV Data (.csv)'
];

// Available report types per role tier
$availableReportTypes = [];
if ($isAdmin) {
    $availableReportTypes = [
        'health_center' => 'Health Center Services & Consultations',
        'sanitation'    => 'Sanitation Inspections & Permits',
        'immunization'  => 'Immunization & Nutrition',
        'wastewater'    => 'Wastewater & Water Quality Analysis',
        'surveillance'  => 'Disease Surveillance & Outbreak Reports',
        'compliance'    => 'Overall Compliance Summary',
        'custom'        => 'Custom Report'
    ];
} elseif ($isDirector) {
    $deptType = match($assignedDept) {
        'Health Center Services'   => 'health_center',
        'Sanitation Permits'       => 'sanitation',
        'Immunization & Nutrition' => 'immunization',
        'Wastewater Services'     => 'wastewater',
        'Health Surveillance'     => 'surveillance',
        default                   => 'sanitation'
    };
    $deptLabels = [
        'health_center' => 'Health Center Services & Consultations',
        'sanitation'    => 'Sanitation Inspections & Permits',
        'immunization'  => 'Immunization & Nutrition',
        'wastewater'    => 'Wastewater & Water Quality Analysis',
        'surveillance'  => 'Disease Surveillance & Outbreak Reports'
    ];
    $availableReportTypes = [
        $deptType    => $deptLabels[$deptType] ?? 'Department Operational Report',
        'compliance' => $assignedDept . ' Compliance Summary'
    ];
} else {
    // Staff: only report type for their assigned department
    $deptType = match($assignedDept) {
        'Health Center Services'   => 'health_center',
        'Sanitation Permits'       => 'sanitation',
        'Immunization & Nutrition' => 'immunization',
        'Wastewater Services'     => 'wastewater',
        'Health Surveillance'     => 'surveillance',
        default                   => 'sanitation'
    };
    $deptLabels = [
        'health_center' => 'Health Center Services & Consultations',
        'sanitation'    => 'Sanitation Inspections & Permits',
        'immunization'  => 'Immunization & Nutrition',
        'wastewater'    => 'Wastewater & Water Quality Analysis',
        'surveillance'  => 'Disease Surveillance & Outbreak Reports'
    ];
    $availableReportTypes = [
        $deptType => $deptLabels[$deptType] ?? 'Assigned Work Report'
    ];
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<!-- ADD FONT AWESOME CDN (If not already in header.php) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<link rel="stylesheet" href="../assets/css/pages/custom_report.css" />
<main class="flex-1 bg-dash-bg h-screen m-5 rounded-2xl font-sans overflow-y-auto scrollbar-track-transparent">

    <!-- ─── TOP BAR: TITLE, SCOPE BADGE & NAVIGATION ─── -->
    <div class="mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-black text-slate-800 flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#176B87] to-[#86B6F6] text-white flex items-center justify-center text-lg shadow-md shadow-[#176B87]/20">
                        <i class="fa-solid fa-chart-pie"></i>
                    </span>
                    AI Generated Reports
                </h1>
                <p class="text-xs text-slate-500 mt-1">South Caloocan City Health &amp; Sanitation Management Information System</p>
            </div>
        </div>

    </div>

    <?php require __DIR__ . '/../includes/reports/dashboard.php'; ?>
    <?php require __DIR__ . '/../includes/reports/generate_form.php'; ?>
    
    <!-- Tabbed Report Management Container -->
    <div id="report-management-container" class="report-section mb-6">
        <div class="flex items-center gap-4 border-b border-[#B4D4FF]/30 mb-6 px-2">
            <button id="tab-btn-templates" onclick="switchReportTab('templates')" class="px-4 py-2 text-sm font-semibold text-[#176B87] border-b-2 border-[#176B87] transition">Saved Templates</button>
            <button id="tab-btn-scheduled" onclick="switchReportTab('scheduled')" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-[#176B87] border-b-2 border-transparent transition">Automated Schedules</button>
        </div>
        
        <div id="tab-content-templates">
            <?php require __DIR__ . '/../includes/reports/templates.php'; ?>
        </div>
        <div id="tab-content-scheduled" class="hidden">
            <?php require __DIR__ . '/../includes/reports/scheduled.php'; ?>
        </div>
    </div>

    <?php require __DIR__ . '/../includes/reports/recent_logs.php'; ?>
    <?php require __DIR__ . '/../includes/reports/modals.php'; ?>
    <!-- footer note -->
    <div id="reportFooter" class="mt-8 mb-4 text-center text-xs text-slate-400/70 border-t border-[#B4D4FF]/20 pt-6">
        Health Sanitation Management System · Enterprise Report Generator v2.5
    </div>

   <!-- bypass datamask for non data masking -->
<p class="kpi-number text-xl font-black text-slate-900 mt-1 leading-none"></p>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ─── CHART.JS ─── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- ─── jsPDF (real client-side PDF export) ─── -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>


<script>
    const CURRENT_USER = {
        id: <?= json_encode($userId) ?>,
        name: <?= json_encode($userName) ?>,
        role: <?= json_encode($userRole) ?>,
        role_description: <?= json_encode($userRoleDesc) ?>,
        tier: <?= json_encode($roleTier) ?>,
        department: <?= json_encode($assignedDept) ?>,
        department_slug: <?= json_encode($assignedDeptSlug) ?>,
        facility: <?= json_encode($assignedFacility) ?>,
        permissions: {
            view: <?= json_encode($permService->hasPermission('reports.view')) ?>,
            generate: <?= json_encode($permService->hasPermission('reports.generate')) ?>,
            export: <?= json_encode($canExport) ?>,
            template_use: <?= json_encode($canUseTpl) ?>,
            template_create: <?= json_encode($canCreateTpl) ?>,
            template_edit: <?= json_encode($canEditTpl) ?>,
            template_delete: <?= json_encode($canDeleteTpl) ?>,
            all_departments: <?= json_encode($canAllDepts) ?>,
            all_facilities: <?= json_encode($canAllFacs) ?>,
            analytics: <?= json_encode($canAnalytics) ?>
        }
    };
    
    const APP_CONFIG = {
        api_ai_summary: "<?= site_url('api/reports/ai-summary.php') ?>",
        api_log_export: "<?= site_url('api/reports/log_export.php') ?>",
        api_reports_data: "<?= site_url('api/reports/data.php') ?>",
        api_report_templates: "<?= site_url('api/report_templates.php') ?>",
        api_reports_schedule: "<?= site_url('api/reports/schedule.php') ?>",
        user_role_lower: <?= json_encode(strtolower($_SESSION['role_description'] ?? ($_SESSION['role'] ?? 'admin'))) ?>
    };
</script>
<script src="../assets/js/pages/custom_report.js"></script>

