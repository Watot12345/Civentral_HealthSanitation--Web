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
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// ============================================================
// ACTIVITY LOGS & SYSTEM LOGS - Segregated Query
// ============================================================
require_once __DIR__ . '/../app/Models/ActivityLog.php';
$activityLogModel = new ActivityLog();

$isSystemAdmin = hasPermission(App\Constants\Permissions::ROLES_MANAGE) || getPermissionService()->isAdminRole($_SESSION['role'] ?? '') || getPermissionService()->isAdminRole($_SESSION['role_description'] ?? '');
$userDept      = getDepartmentResolver()->resolveDepartmentName();

if (!hasPermission(App\Constants\Permissions::LOGS_VIEW) && !$isSystemAdmin) {
    header('Location: ' . site_url('pages/dashboard.php'));
    exit;
}

$logOptions = ['limit' => 250, 'order' => 'created_at.desc'];
if (!$isSystemAdmin && !empty($userDept)) {
    $logOptions['department'] = $userDept;
}
$allLogs = $activityLogModel->all($logOptions);


// 1. SYSTEM AUDIT TRAIL — Administrative, Security & Authentication Logs (Admin System Logs)
$auditTrail = array_values(array_filter($allLogs, function($log) {
    $module = strtolower($log['module'] ?? '');
    $action = strtolower($log['action'] ?? '');
    return str_contains($module, 'user management') 
        || str_contains($module, 'authentication')
        || str_contains($module, 'system management')
        || str_contains($module, 'system')
        || str_contains($action, 'logged in')
        || str_contains($action, 'logged out')
        || str_contains($action, 'permission')
        || str_contains($action, 'user');
}));

// 2. OPERATIONAL MODULE ACTIVITY LOGS — Operations handling logs only (Health Services, Sanitation, Immunization, Wastewater, Surveillance)
$activityLogs = array_values(array_filter($allLogs, function($log) {
    $module = strtolower($log['module'] ?? '');
    return !str_contains($module, 'user management') 
        && !str_contains($module, 'authentication')
        && !str_contains($module, 'system management')
        && !str_contains($module, 'system');
}));

// ============================================================
// ERROR LOGS - Dynamic System Errors & Security Warnings
// ============================================================
$errorLogs = $activityLogModel->getErrorLogs();

// ============================================================
// STATISTICS
// ============================================================
$totalAudit      = count($auditTrail);
$totalActivities = count($activityLogs);
$totalErrors     = count($errorLogs);
$criticalErrors  = count(array_filter($errorLogs, function($e) { return $e['level'] == 'Critical'; }));
$openErrors      = count(array_filter($errorLogs, function($e) { return $e['status'] == 'Open'; }));

$title = 'System Logs';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">System Logs</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-clock-rotate-left"></i> <?php echo $totalAudit + $totalActivities + $totalErrors; ?> Total Logs
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">Audit trail, activity logs, error logs &amp; log search</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="exportLogs()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-file-export text-xs"></i> Export Logs
            </button>
            <button onclick="refreshData()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- KPI CARDS - Logs Overview                                 -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Logs -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-list text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalAudit + $totalActivities + $totalErrors; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Logs</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">📊 All records</span>
                    <span class="text-[10px] text-slate-400">Last 30 days</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Critical Errors -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-red-200">
                        <i class="fa-solid fa-bug text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-red-600"><?php echo $criticalErrors; ?></p>
                        <p class="text-xs font-medium text-slate-500">Critical Errors</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">⚠️ Urgent</span>
                    <span class="text-[10px] text-slate-400"><?php echo $openErrors; ?> Open</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Open Issues -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $openErrors; ?></p>
                        <p class="text-xs font-medium text-slate-500">Open Issues</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">🔧 Needs Fix</span>
                    <span class="text-[10px] text-slate-400">Unresolved</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Audit Entries -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-purple-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-purple-200">
                        <i class="fa-solid fa-file-shield text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-purple-600"><?php echo $totalAudit; ?></p>
                        <p class="text-xs font-medium text-slate-500">Audit Entries</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-bold">🔍 Tracked</span>
                    <span class="text-[10px] text-slate-400">System changes</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- LOG SEARCH BAR                                             -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="p-4">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="logSearch" onkeyup="searchLogs()" placeholder="Search logs by user, action, module, or message..." class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <select id="logTypeFilter" onchange="filterLogs()" class="px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="all">All Log Types</option>
                        <option value="audit">Audit Trail</option>
                        <option value="activity">Activity Logs</option>
                        <option value="error">Error Logs</option>
                    </select>
                    <select id="logStatusFilter" onchange="filterLogs()" class="px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="all">All Status</option>
                        <option value="Success">Success</option>
                        <option value="Failed">Failed</option>
                        <option value="Resolved">Resolved</option>
                        <option value="Open">Open</option>
                    </select>
                    <button onclick="clearSearch()" class="px-3 py-2 bg-slate-100 text-slate-600 rounded-lg hover:bg-slate-200 transition text-sm font-semibold">
                        <i class="fa-solid fa-times"></i> Clear
                    </button>
                    <button onclick="openClearLogsModal()" class="px-3 py-2 bg-red-50 text-red-600 border border-red-200 rounded-lg hover:bg-red-100 transition text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can text-xs"></i> Clear Logs
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB NAVIGATION                                             -->
    <!-- ============================================================ -->
    <div class="flex gap-2 mb-6 border-b border-slate-200">
        <button onclick="switchTab('audit')" class="tab-btn active px-4 py-2.5 text-sm font-semibold border-b-2 border-brand-dark text-brand-dark transition" id="tab-audit">
            <i class="fa-solid fa-file-shield"></i> System &amp; Audit Logs
            <span class="ml-1.5 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px]"><?php echo $totalAudit; ?></span>
        </button>
        <button onclick="switchTab('activity')" class="tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition" id="tab-activity">
            <i class="fa-solid fa-list-check"></i> Operational Module Logs
            <span class="ml-1.5 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px]"><?php echo $totalActivities; ?></span>
        </button>
        <button onclick="switchTab('error')" class="tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition" id="tab-error">
            <i class="fa-solid fa-bug"></i> Error Logs
            <span class="ml-1.5 px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px]"><?php echo $totalErrors; ?></span>
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- TAB CONTENT: AUDIT TRAIL                                  -->
    <!-- ============================================================ -->
    <div id="auditContent" class="tab-content">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-shield text-brand-medium"></i>
                    System &amp; Audit Logs (Admin)
                    <span class="text-xs font-normal text-slate-400">(<?php echo $totalAudit; ?> entries)</span>
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">Admin logins, user management, security access &amp; role permission updates</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Module</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">IP Address</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody id="auditTableBody">
                        <?php if (empty($auditTrail)): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No administrative audit entries yet. Login, logout, and user management actions will appear here.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($auditTrail as $log): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition log-row" data-type="audit" data-status="<?php echo htmlspecialchars($log['status'] ?? 'Success', ENT_QUOTES); ?>">
                            <td class="px-4 py-3 font-medium text-slate-700 text-xs">#<?php echo (int)($log['id'] ?? 0); ?></td>
                            <td class="px-4 py-3 text-slate-500 text-xs"><?php echo $log['created_at'] ? date('M d, Y h:i A', strtotime($log['created_at'])) : '—'; ?></td>
                            <td class="px-4 py-3 font-medium text-slate-700"><?php echo htmlspecialchars($log['user_name'] ?? 'System', ENT_QUOTES); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 rounded-full text-xs font-semibold"><?php echo htmlspecialchars($log['role'] ?? 'System Administrator', ENT_QUOTES); ?></span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-sm"><?php echo htmlspecialchars($log['action'] ?? '', ENT_QUOTES); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium"><?php echo htmlspecialchars($log['module'] ?? 'System Management', ENT_QUOTES); ?></span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600"><?php echo htmlspecialchars($log['ip_address'] ?? '—', ENT_QUOTES); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 <?php echo ($log['status'] ?? 'Success') === 'Success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?> rounded-full text-xs font-semibold">
                                    <?php echo htmlspecialchars($log['status'] ?? 'Success', ENT_QUOTES); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- ============================================================ -->
    <!-- TAB CONTENT: OPERATIONAL ACTIVITY LOGS                      -->
    <!-- ============================================================ -->
    <div id="activityContent" class="tab-content hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-brand-medium"></i>
                    Operational Module Activity Logs
                    <span class="text-xs font-normal text-slate-400">(<?php echo $totalActivities; ?> entries)</span>
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">Handling logs across Health Services, Sanitation, Immunization, Wastewater &amp; Surveillance</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Module</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">IP &amp; Device</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody id="activityTableBody">
                        <?php foreach ($activityLogs as $log): 
                            $logRole = $log['role'] ?? 'Citizen';
                            $roleBadgeColor = $logRole === 'Citizen' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700';
                            if (strpos($logRole, 'Doctor') !== false || strpos($logRole, 'Nurse') !== false || strpos($logRole, 'Health') !== false) {
                                $roleBadgeColor = 'bg-blue-100 text-blue-700';
                            } elseif (strpos($logRole, 'Admin') !== false) {
                                $roleBadgeColor = 'bg-red-100 text-red-700';
                            } elseif (strpos($logRole, 'Inspector') !== false || strpos($logRole, 'Sanitation') !== false) {
                                $roleBadgeColor = 'bg-amber-100 text-amber-700';
                            }
                            $dateStr = !empty($log['created_at']) ? date('M d, Y h:i A', strtotime($log['created_at'])) : date('M d, Y h:i A');
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition log-row" data-type="activity" data-status="<?php echo $log['status']; ?>">
                            <td class="px-4 py-3 font-medium text-slate-700 text-xs">ACT-<?php echo sprintf('%03d', $log['id'] ?? 1); ?></td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                <span class="block text-sm font-semibold"><?php echo htmlspecialchars($log['user_name'] ?? 'System', ENT_QUOTES); ?></span>
                                <span class="block text-[10px] text-slate-400"><?php echo $dateStr; ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 <?php echo $roleBadgeColor; ?> rounded-full text-xs font-semibold">
                                    <?php echo htmlspecialchars($logRole, ENT_QUOTES); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 text-sm font-medium">
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($log['action'], ENT_QUOTES); ?></p>
                                <p class="text-[10px] text-slate-500 mt-0.5"><?php echo htmlspecialchars($log['details'] ?? '', ENT_QUOTES); ?></p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-medium"><?php echo htmlspecialchars($log['module'], ENT_QUOTES); ?></span>
                            </td>
                            <td class="px-4 py-3 text-xs">
                                <span class="font-mono font-semibold text-slate-700 block"><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES); ?></span>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><i class="fas fa-desktop text-[8px] mr-1"></i><?php echo htmlspecialchars($log['device'] ?? 'Desktop • Chrome 126 (Windows 11)', ENT_QUOTES); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 <?php echo $log['status'] == 'Success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?> rounded-full text-xs font-semibold">
                                    <?php echo htmlspecialchars($log['status'], ENT_QUOTES); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB CONTENT: ERROR LOGS                                  -->
    <!-- ============================================================ -->
    <div id="errorContent" class="tab-content hidden">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-bug text-brand-medium"></i>
                    Error Logs
                    <span class="text-xs font-normal text-slate-400">(<?php echo $totalErrors; ?> entries)</span>
                </h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400">System errors and warnings</span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Level</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Source</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Message</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="errorTableBody">
                        <?php if (empty($errorLogs)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 text-sm">No system error or security warning logs recorded.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($errorLogs as $log): 
                            $levelColors = [
                                'Critical' => 'bg-red-100 text-red-700',
                                'Error' => 'bg-amber-100 text-amber-700',
                                'Warning' => 'bg-yellow-100 text-yellow-700'
                            ];
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition log-row" data-type="error" data-status="<?php echo $log['status']; ?>">
                            <td class="px-4 py-3 font-medium text-slate-700 text-xs"><?php echo $log['id']; ?></td>
                            <td class="px-4 py-3 text-slate-500 text-xs"><?php echo date('M d, Y h:i A', strtotime($log['timestamp'])); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 <?php echo $levelColors[$log['level']] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-xs font-semibold">
                                    <?php echo $log['level']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-sm"><?php echo $log['source']; ?></td>
                            <td class="px-4 py-3 text-slate-500 text-xs max-w-[200px] truncate" title="<?php echo $log['message']; ?>"><?php echo $log['message']; ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 <?php echo $log['status'] == 'Resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'; ?> rounded-full text-xs font-semibold">
                                    <?php echo $log['status']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button onclick="viewErrorDetails('<?php echo $log['id']; ?>')" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition px-2 py-1 hover:bg-brand-light rounded">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button onclick="resolveError('<?php echo $log['id']; ?>')" class="text-emerald-600 hover:text-emerald-800 text-xs font-medium transition px-2 py-1 hover:bg-emerald-50 rounded">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- ERROR DETAILS MODAL                                        -->
<!-- ============================================================ -->
<div id="errorDetailsModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-bug text-red-500"></i>
                Error Details
            </h3>
            <button onclick="closeModal('errorDetailsModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6" id="errorDetailsContent">
            <!-- Dynamic content loaded via JavaScript -->
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<script>
    // PHP Data to JavaScript
    const ERROR_LOGS = <?php echo json_encode($errorLogs); ?>;

    // ============================================================
    // TAB SWITCHING
    // ============================================================
    function switchTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'border-brand-dark', 'text-brand-dark');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        
        const tabBtn = document.getElementById('tab-' + tab);
        tabBtn.classList.add('active', 'border-brand-dark', 'text-brand-dark');
        tabBtn.classList.remove('border-transparent', 'text-slate-500');
        
        // Show/hide content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        if (tab === 'audit') {
            document.getElementById('auditContent').classList.remove('hidden');
        } else if (tab === 'activity') {
            document.getElementById('activityContent').classList.remove('hidden');
        } else if (tab === 'error') {
            document.getElementById('errorContent').classList.remove('hidden');
        }
    }

    // ============================================================
    // SEARCH LOGS
    // ============================================================
    function searchLogs() {
        const searchTerm = document.getElementById('logSearch').value.toLowerCase();
        const rows = document.querySelectorAll('.log-row');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // ============================================================
    // FILTER LOGS
    // ============================================================
    function filterLogs() {
        const typeFilter = document.getElementById('logTypeFilter').value;
        const statusFilter = document.getElementById('logStatusFilter').value;
        const rows = document.querySelectorAll('.log-row');
        
        rows.forEach(row => {
            const type = row.dataset.type;
            const status = row.dataset.status;
            
            let show = true;
            if (typeFilter !== 'all' && type !== typeFilter) show = false;
            if (statusFilter !== 'all' && status !== statusFilter) show = false;
            
            // Also check if the row's tab is visible
            const parentContent = row.closest('.tab-content');
            if (parentContent && parentContent.classList.contains('hidden')) {
                show = false;
            }
            
            row.style.display = show ? 'table-row' : 'none';
        });
    }

    // ============================================================
    // CLEAR SEARCH
    // ============================================================
    function clearSearch() {
        document.getElementById('logSearch').value = '';
        document.getElementById('logTypeFilter').value = 'all';
        document.getElementById('logStatusFilter').value = 'all';
        
        document.querySelectorAll('.log-row').forEach(row => {
            row.style.display = 'table-row';
        });
    }

    // ============================================================
    // VIEW ERROR DETAILS
    // ============================================================
    function viewErrorDetails(errorId) {
        const error = ERROR_LOGS.find(e => e.id === errorId);
        const content = document.getElementById('errorDetailsContent');
        
        if (error) {
            content.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-slate-800">${error.id}</h4>
                            <p class="text-xs text-slate-500">${error.timestamp}</p>
                        </div>
                        <span class="px-2 py-1 ${error.level === 'Critical' ? 'bg-red-100 text-red-700' : error.level === 'Error' ? 'bg-amber-100 text-amber-700' : 'bg-yellow-100 text-yellow-700'} rounded-full text-xs font-semibold">
                            ${error.level}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-slate-500">Source</p>
                            <p class="font-medium text-slate-700">${error.source}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Status</p>
                            <span class="px-2 py-1 ${error.status === 'Resolved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'} rounded-full text-xs font-semibold">
                                ${error.status}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">File</p>
                            <p class="font-medium text-slate-700 text-sm">${error.file}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Line</p>
                            <p class="font-medium text-slate-700">${error.line}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-slate-500">Message</p>
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700 font-medium">${error.message}</p>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-xs text-slate-500">Stack Trace</p>
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                            <code class="text-xs text-slate-700 font-mono">${error.stack_trace}</code>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 pt-2">
                        <button onclick="closeModal('errorDetailsModal')" class="flex-1 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                            Close
                        </button>
                        <button onclick="resolveError('${error.id}')" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold">
                            <i class="fa-solid fa-check"></i> Resolve Issue
                        </button>
                    </div>
                </div>
            `;
        }
        
        openModal('errorDetailsModal');
    }

    // ============================================================
    // RESOLVE ERROR
    // ============================================================
    function resolveError(errorId) {
        if (confirm('Mark error ' + errorId + ' as resolved?')) {
            showToast('✅ Error ' + errorId + ' resolved!', 'success');
        }
    }

    // ============================================================
    // EXPORT LOGS
    // ============================================================
    function exportLogs() {
        showToast('📄 Exporting logs...', 'info');
        setTimeout(() => {
            showToast('✅ Logs exported successfully!', 'success');
        }, 1500);
    }

    // ============================================================
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('🔄 Refreshing logs...', 'info');
        setTimeout(() => {
            showToast('✅ Logs refreshed!', 'success');
        }, 1000);
    }

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
    // TOAST
    // ============================================================
    let toastTimer = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-rose-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        t.className = `fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ${colors[type] || colors.success}`;
        t.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.add('hidden'), 3000);
    }

    // ============================================================
    // CLEAR LOGS CONFIRMATION MODAL & API
    // ============================================================
    function openClearLogsModal() {
        const modal = document.getElementById('clearLogsModal');
        const card = document.getElementById('clearLogsModalCard');
        if (!modal || !card) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeClearLogsModal() {
        const modal = document.getElementById('clearLogsModal');
        const card = document.getElementById('clearLogsModalCard');
        if (!modal || !card) return;
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    function executeClearLogs() {
        closeClearLogsModal();

        const body = new URLSearchParams();
        body.append('action', 'clear_logs');

        fetch('user_management_api.php', {
            method: 'POST',
            body: body
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('🧹 ' + data.message, 'info');
                setTimeout(() => location.reload(), 600);
            } else {
                showToast('⚠️ ' + data.message, 'danger');
            }
        })
        .catch(err => {
            showToast('❌ Error clearing logs', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // ESC KEY TO CLOSE MODALS
    // ============================================================
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

<style>
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .tab-btn.active {
        border-bottom-width: 2px;
    }
    .tab-btn:not(.active):hover {
        border-bottom-color: #CBD5E1;
    }
    
    .log-row {
        transition: background-color 0.2s ease;
    }
</style>

<!-- CLEAR ACTIVITY LOGS CONFIRMATION MODAL -->
<div id="clearLogsModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-slate-900/50 backdrop-blur-sm p-4 transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6 text-center transform transition-all scale-95 opacity-0 duration-200" id="clearLogsModalCard">
        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-1">Clear Activity & System Logs?</h3>
        <p class="text-xs text-slate-500 mb-5 leading-relaxed">
            Are you sure you want to clear all activity and system logs? This action will permanently delete all activity log records directly from the database (<code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono text-[11px]">activity_logs</code> table) and cannot be undone.
        </p>
        <div class="flex items-center justify-center gap-3 pt-2 border-t border-slate-100">
            <button type="button" onclick="closeClearLogsModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition">
                Cancel
            </button>
            <button type="button" onclick="executeClearLogs()" class="px-4 py-2 text-xs font-semibold text-white bg-red-600 rounded-xl hover:bg-red-700 shadow-md shadow-red-500/20 transition flex items-center gap-1.5">
                <i class="fa-solid fa-trash-can text-[10px]"></i> Permanently Delete in DB
            </button>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>