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
// 1. PHP BACKEND - Fetch Data from Supabase
// ============================================================
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Enforce RBAC Page Authorization
requirePermission('users.view');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Models/Role.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';

// Get current logged-in user identification
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentEmployeeId = $_SESSION['employee_id'] ?? 'SYS--ADMIN-2011';

// Initialize models
$db = Database::getInstance();
$employeeModel = new Employee($db);
$roleModel = new Role();
$logModel = new ActivityLog();

$isSystemAdmin = hasPermission(App\Constants\Permissions::ROLES_MANAGE) || getPermissionService()->isAdminRole($_SESSION['role'] ?? '');
$userDept = getDepartmentResolver()->resolveDepartmentName();

try {
    $allUsers = $employeeModel->all(['order' => 'created_at.desc']);

    // Departmental Scoping: Non-admin department heads only see users under their department
    if (!$isSystemAdmin && !empty($userDept)) {
        $users = getDepartmentResolver()->filterUsersForDepartment($allUsers, $userDept);
    } else {
        $users = $allUsers;
    }

    // Sort employees by Organizational Hierarchy
    usort($users, function($a, $b) {
        $getRoleRank = function($user) {
            $role = $user['role_description'] ?? $user['role'] ?? '';
            if ($role === 'System Admin' || $role === 'System Administrator') return 1;

            $deptHeads = [
                'Health Center Director',
                'Sanitation Director',
                'Immunization Lead',
                'Wastewater Lead',
                'Surveillance Lead'
            ];
            if (in_array($role, $deptHeads, true)) return 2;
            return 3;
        };

        $rankA = $getRoleRank($a);
        $rankB = $getRoleRank($b);
        if ($rankA !== $rankB) return $rankA <=> $rankB;

        $deptA = strtolower($a['department'] ?? '');
        $deptB = strtolower($b['department'] ?? '');
        if ($deptA !== $deptB) return strcmp($deptA, $deptB);

        $posA = strtolower($a['role_description'] ?? '');
        $posB = strtolower($b['role_description'] ?? '');
        if ($posA !== $posB) return strcmp($posA, $posB);

        $nameA = strtolower($a['full_name'] ?? '');
        $nameB = strtolower($b['full_name'] ?? '');
        return strcmp($nameA, $nameB);
    });
} catch (Throwable $e) {
    error_log('User Management — users fetch error: ' . $e->getMessage());
    $users = [];
}

// --- Fetch Roles (with permissions & user_count attached) -----------------
try {
    $allRoles = $roleModel->all(['order' => 'id.asc'], $users);

    // Departmental Scoping: Non-admin department heads only see roles under their department
    if (!$isSystemAdmin && !empty($userDept)) {
        $roles = getDepartmentResolver()->filterRolesForDepartment($allRoles, $userDept);
    } else {
        $roles = $allRoles;
    }
} catch (Throwable $e) {
    error_log('User Management — roles fetch error: ' . $e->getMessage());
    $roles = [];
}

// Build name→color lookup for the 10 Primary Roles
$roleColorMap = [
    'System Admin'           => 'bg-red-100 text-red-700',
    'Health Center Director' => 'bg-blue-100 text-blue-700',
    'Medical Practitioner'   => 'bg-cyan-100 text-cyan-700',
    'Health Center Staff'     => 'bg-sky-100 text-sky-700',
    'Sanitation Director'    => 'bg-amber-100 text-amber-700',
    'Sanitation Officer'     => 'bg-yellow-100 text-yellow-700',
    'Immunization Lead'      => 'bg-emerald-100 text-emerald-700',
    'Nutrition Staff'        => 'bg-teal-100 text-teal-700',
    'Wastewater Lead'        => 'bg-purple-100 text-purple-700',
    'Surveillance Lead'      => 'bg-indigo-100 text-indigo-700',
    'Surveillance Staff'     => 'bg-indigo-100 text-indigo-700',
];
foreach ($roles as $r) {
    if (!empty($r['name']) && !empty($r['color'])) {
        $roleColorMap[$r['name']] = $r['color'];
    }
}

// Build list of all distinct role options for the role filter dropdown
$filterRoleOptions = [];
foreach (array_keys($roleColorMap) as $roleName) {
    $filterRoleOptions[$roleName] = $roleName;
}
foreach ($roles as $r) {
    if (!empty($r['name'])) {
        $filterRoleOptions[$r['name']] = $r['name'];
    }
}
foreach ($users as $u) {
    if (!empty($u['role'])) {
        $filterRoleOptions[$u['role']] = $u['role'];
    }
    if (!empty($u['role_description'])) {
        $filterRoleOptions[$u['role_description']] = $u['role_description'];
    }
}
ksort($filterRoleOptions);

// --- Fetch Activity Logs (User Management actions only, excluding login/logout) ---
try {
    $logOptions = ['limit' => 100, 'order' => 'created_at.desc'];
    if (!$isSystemAdmin && !empty($userDept)) {
        $logOptions['department'] = $userDept;
    }
    $allUserLogs  = $logModel->all($logOptions);

    $activityLogs = array_values(array_filter($allUserLogs, function($log) {
        $module = strtolower($log['module'] ?? '');
        $action = strtolower($log['action'] ?? '');
        return (str_contains($module, 'user management') || str_contains($action, 'user') || str_contains($action, 'employee') || str_contains($action, 'permission') || str_contains($action, 'role') || str_contains($action, 'status'))
            && !str_contains($action, 'logged in') 
            && !str_contains($action, 'logged out');
    }));
    $activityLogs = array_slice($activityLogs, 0, 20);
} catch (Throwable $e) {
    error_log('User Management — logs fetch error: ' . $e->getMessage());
    $activityLogs = [];
}

// --- Statistics -----------------------------------------------------------
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => ($u['status'] ?? 'Active') === 'Active'));
$inactiveUsers = count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'Inactive'));
$suspendedUsers = count(array_filter($users, fn($u) => ($u['status'] ?? '') === 'Suspended'));

$title = 'User Management';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">User Management</h2>
                <span id="kpiHeaderUserBadge" class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-users-cog"></i> <?php echo $totalUsers; ?> Users
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">User registration, role assignment, permission management & activity monitoring</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="openModal('addUserModal')" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-user-plus text-xs"></i> Add New User
            </button>
            <button onclick="refreshData()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <?php if (!$isSystemAdmin): ?>
    <!-- Departmental Scope Banner -->
    <div class="mb-6 bg-blue-50/80 border border-blue-200/80 rounded-2xl p-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-building-user"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider">Department Scope Active</h4>
                <p class="text-xs text-blue-700 mt-0.5">Filtered to employees and position roles within <strong class="font-semibold"><?= htmlspecialchars($userDept) ?></strong>.</p>
            </div>
        </div>
        <span class="px-3 py-1 bg-blue-200/60 text-blue-800 text-[11px] font-bold rounded-full">
            <?= htmlspecialchars($userDept) ?>
        </span>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- KPI CARDS - User Overview                                 -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Users -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <div>
                        <p id="kpiTotalUsers" class="text-2xl font-black text-slate-900"><?php echo $totalUsers; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Users</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span id="kpiSubActive" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold"><?php echo $activeUsers; ?> Active</span>
                    <span id="kpiSubInactive" class="text-[10px] text-slate-400"><?php echo $inactiveUsers; ?> Inactive</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Active Users -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-user-check text-lg"></i>
                    </div>
                    <div>
                        <p id="kpiActiveUsers" class="text-2xl font-black text-emerald-600"><?php echo $activeUsers; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active Users</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Online</span>
                    <span id="kpiActivePercent" class="text-[10px] text-slate-400"><?php echo round(($activeUsers / ($totalUsers ?: 1)) * 100); ?>% of total</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Roles -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-purple-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-purple-200">
                        <i class="fa-solid fa-layer-group text-lg"></i>
                    </div>
                    <div>
                        <p id="kpiTotalRoles" class="text-2xl font-black text-purple-600"><?php echo count($roles); ?></p>
                        <p class="text-xs font-medium text-slate-500">Roles</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-[10px] font-bold">🔑 Defined</span>
                    <span class="text-[10px] text-slate-400">With permissions</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Recent Activity -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p id="kpiTotalActivities" class="text-2xl font-black text-amber-600"><?php echo count($activityLogs); ?></p>
                        <p class="text-xs font-medium text-slate-500">Activities</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">📊 Today</span>
                    <span class="text-[10px] text-slate-400">Last 24 hours</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- USER REGISTRATION - Users Table                           -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-brand-medium"></i>
                User Registration
                <span class="text-xs font-normal text-slate-400">(<?php echo $totalUsers; ?> registered)</span>
            </h3>
            <div class="flex items-center gap-3">
                <div class="relative min-w-[210px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="userSearchInput" onkeyup="filterUsers()" placeholder="Search ID, name, email..." class="pl-8 pr-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none w-full shadow-2xs transition">
                </div>
                <select id="roleFilter" onchange="filterUsers()" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="all">All Roles</option>
                    <?php foreach ($filterRoleOptions as $roleOpt): ?>
                    <option value="<?php echo htmlspecialchars($roleOpt, ENT_QUOTES); ?>"><?php echo htmlspecialchars($roleOpt, ENT_QUOTES); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="statusFilter" onchange="filterUsers()" class="px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="all">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                    <option value="Suspended">Suspended</option>
                </select>
                <button onclick="openModal('addUserModal')" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i> Add User
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Employee ID</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Department</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Position</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Last Login</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400 text-sm">No users registered yet.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($users as $user): 
                        $isCurrentUser = ($currentUserId > 0 && (int)($user['id'] ?? 0) === $currentUserId) ||
                                         (!empty($currentEmployeeId) && ($user['employee_id'] ?? '') === $currentEmployeeId) ||
                                         (!empty($currentEmployeeId) && ($user['username'] ?? '') === $currentEmployeeId);
                    ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition user-row"
                        data-id="<?php echo (int) $user['id']; ?>"
                        data-employeeid="<?php echo htmlspecialchars($user['employee_id'] ?? '', ENT_QUOTES); ?>"
                        data-role="<?php echo htmlspecialchars($user['role'] ?? '', ENT_QUOTES); ?>"
                        data-status="<?php echo htmlspecialchars($user['status'] ?? 'Active', ENT_QUOTES); ?>"
                        data-fullname="<?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES); ?>"
                        data-username="<?php echo htmlspecialchars($user['username'] ?? '', ENT_QUOTES); ?>"
                        data-email="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?>"
                        data-department="<?php echo htmlspecialchars($user['department'] ?? '', ENT_QUOTES); ?>"
                        data-roledescription="<?php echo htmlspecialchars($user['role_description'] ?? '', ENT_QUOTES); ?>">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-brand-light flex items-center justify-center text-brand-dark font-bold text-xs">
                                    <?php echo htmlspecialchars($user['initials'] ?? strtoupper(substr($user['full_name'] ?? '?', 0, 1)), ENT_QUOTES); ?>
                                </div>
                                <div>
                                    <span class="font-medium text-slate-800"><?php echo htmlspecialchars($user['full_name'] ?? '', ENT_QUOTES); ?></span>
                                    <span class="text-xs text-slate-400 block"><?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES); ?></span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-sm font-semibold font-mono text-xs"><?php echo htmlspecialchars($user['employee_id'] ?? $user['username'] ?? '—', ENT_QUOTES); ?></td>
                        <td class="px-4 py-3 text-slate-600 text-sm font-medium"><?php echo htmlspecialchars($user['department'] ?? '—', ENT_QUOTES); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 <?php echo $roleColorMap[$user['role'] ?? ''] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-xs font-semibold">
                                <?php echo htmlspecialchars($user['role'] ?? 'Unassigned', ENT_QUOTES); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-sm font-medium"><?php echo htmlspecialchars($user['role_description'] ?? '—', ENT_QUOTES); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 <?php echo ($user['status'] ?? 'Active') === 'Active' ? 'bg-emerald-100 text-emerald-700' : (($user['status'] ?? '') === 'Inactive' ? 'bg-slate-100 text-slate-700' : 'bg-red-100 text-red-700'); ?> rounded-full text-xs font-semibold">
                                <?php echo htmlspecialchars($user['status'] ?? 'Active', ENT_QUOTES); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs"><?php echo $user['last_login'] ? date('M d, Y h:i A', strtotime($user['last_login'])) : 'Never'; ?></td>
                        <td class="px-4 py-3">
                            <?php if ($isCurrentUser): ?>
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-500 rounded-lg text-xs font-medium border border-slate-200 inline-flex items-center gap-1.5" title="You cannot edit or delete your logged-in account">
                                <i class="fa-solid fa-user-shield text-brand-medium"></i> You (Current User)
                            </span>
                            <?php else: ?>
                            <div class="flex gap-1">
                                <button onclick="editUser(<?php echo (int) $user['id']; ?>)" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition px-2 py-1 hover:bg-brand-light rounded" title="Edit User">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button onclick="managePermissions(<?php echo (int) $user['id']; ?>)" class="text-purple-600 hover:text-purple-800 text-xs font-medium transition px-2 py-1 hover:bg-purple-50 rounded" title="Permissions">
                                    <i class="fa-solid fa-key"></i>
                                </button>
                                <button onclick="toggleUserStatus(<?php echo (int) $user['id']; ?>)" class="text-amber-600 hover:text-amber-800 text-xs font-medium transition px-2 py-1 hover:bg-amber-50 rounded" title="Toggle Status">
                                    <i class="fa-solid <?php echo ($user['status'] ?? 'Active') === 'Active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                </button>
                                <?php if ($isSystemAdmin): ?>
                                <button onclick="deleteUser(<?php echo (int) $user['id']; ?>)" class="text-red-500 hover:text-red-700 text-xs font-medium transition px-2 py-1 hover:bg-red-50 rounded" title="Delete User">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                                <?php endif; ?>
                            </div>

                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ROLE ASSIGNMENT & PERMISSION MANAGEMENT                   -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Role Assignment -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-tag text-brand-medium"></i>
                    Role Assignment
                </h3>
                <span class="text-xs font-semibold px-2.5 py-0.5 bg-brand-light text-brand-dark rounded-full"><?php echo count($roles); ?> Roles</span>
            </div>
            <div class="p-4 max-h-[400px] overflow-y-auto">
                <div class="space-y-3">
                    <?php $roleNum = 1; foreach ($roles as $role): ?>
                    <div class="role-item-card flex items-center justify-between p-3 border border-slate-200 rounded-lg hover:shadow-md transition" data-rolename="<?php echo htmlspecialchars($role['name'], ENT_QUOTES); ?>">
                        <div class="flex items-center gap-3">
                            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[11px] shrink-0">#<?php echo $roleNum++; ?></span>
                            <span class="w-2.5 h-2.5 rounded-full <?php echo htmlspecialchars(explode(' ', $role['color'] ?? 'bg-slate-500')[0], ENT_QUOTES); ?> shrink-0"></span>
                            <div>
                                <p class="font-medium text-slate-800 text-sm"><?php echo htmlspecialchars($role['name'], ENT_QUOTES); ?></p>
                                <p class="text-xs text-slate-500"><span class="role-user-count"><?php echo (int) ($role['user_count'] ?? 0); ?> users</span> • <?php echo count(array_filter($role['permissions'] ?? [], fn($p) => $p['granted'] ?? false)); ?> permissions</p>
                            </div>
                        </div>
                        <button onclick="editRole(<?php echo (int) $role['id']; ?>)" class="px-3 py-1 bg-brand-light text-brand-dark rounded-lg hover:bg-brand-dark hover:text-white transition text-xs font-semibold shrink-0">
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Permission Management -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-2">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-key text-brand-medium"></i>
                    Permission Management
                </h3>
                <select id="permissionRoleSelect" onchange="loadRolePermissions(this.value)" class="px-2 py-1 text-xs border border-slate-200 rounded-lg bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo (int) $role['id']; ?>"><?php echo htmlspecialchars($role['name'], ENT_QUOTES); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="p-4 max-h-[400px] overflow-y-auto">
                <div class="space-y-4" id="permissionGrid">
                    <p class="text-xs text-slate-400 text-center py-6">Loading permissions…</p>
                </div>
                <div class="flex justify-end pt-3 mt-1 border-t border-slate-100">
                    <button onclick="savePermissions()" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold">
                        <i class="fa-solid fa-save mr-1"></i> Save Permissions
                    </button>
               </div>
        </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- USER ACTIVITY LOG                                          -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-list-check text-brand-medium"></i>
                User Activity Logs
                <span class="text-xs font-normal text-slate-400">(<span id="activityCount"><?php echo count($activityLogs); ?></span> activities)</span>
            </h3>
            <div class="flex items-center gap-2">
                <button onclick="filterActivity('all')" class="filter-btn-activity active px-3 py-1 text-xs font-semibold rounded-full bg-brand-dark text-white hover:bg-brand-medium transition" id="act-all">All</button>
                <button onclick="filterActivity('Success')" class="filter-btn-activity px-3 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition" id="act-success">Success</button>
                <button onclick="filterActivity('Failed')" class="filter-btn-activity px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 hover:bg-red-200 transition" id="act-failed">Failed</button>
                <button onclick="openClearLogsModal()" class="text-xs text-red-500 hover:text-red-700 transition font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-trash-can text-[10px]"></i> Clear logs
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Action</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">IP &amp; Device</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody id="activityTableBody">
                    <?php if (empty($activityLogs)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-sm">No user management or permission activity recorded yet.</td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($activityLogs as $log): 
                        $logRole = $log['role'] ?? $log['role_name'] ?? 'Citizen';
                        $roleBadgeColor = $roleColorMap[$logRole] ?? ($logRole === 'Citizen' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700');
                        $deviceStr = $log['device'] ?? 'Desktop • Chrome 126 (Windows 11)';
                    ?>
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition activity-row" data-status="<?php echo htmlspecialchars($log['status'] ?? 'Success', ENT_QUOTES); ?>">
                        <td class="px-4 py-3 font-medium text-slate-700"><?php echo htmlspecialchars($log['user_name'] ?? 'System', ENT_QUOTES); ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 <?php echo $roleBadgeColor; ?> rounded-full text-xs font-semibold">
                                <?php echo htmlspecialchars($logRole, ENT_QUOTES); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-sm font-medium"><?php echo htmlspecialchars($log['action'] ?? '', ENT_QUOTES); ?></td>
                        <td class="px-4 py-3 text-slate-500 text-xs"><?php echo $log['created_at'] ? date('M d, Y h:i A', strtotime($log['created_at'])) : '—'; ?></td>
                        <td class="px-4 py-3 text-xs">
                            <span class="font-mono font-semibold text-slate-700 block"><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1', ENT_QUOTES); ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5"><i class="fas fa-desktop text-[8px] mr-1"></i><?php echo htmlspecialchars($deviceStr, ENT_QUOTES); ?></span>
                        </td>
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
<!-- ADD USER MODAL                                             -->
<!-- ============================================================ -->
<div id="addUserModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2" id="userModalTitle">
                <i class="fa-solid fa-user-plus text-brand-medium"></i>
                Register New User
            </h3>
            <button onclick="closeModal('addUserModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="userForm" onsubmit="submitUserForm(event)">
                <input type="hidden" id="userId" value="">
                <div class="space-y-4">
                    <div id="userFormError" class="hidden text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2"></div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Full Name</label>
                        <input type="text" id="fullName" name="full_name" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Enter full name" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1 flex items-center justify-between">
                            <span>Employee ID</span>
                            <span class="text-[10px] text-brand-medium font-normal bg-brand-medium/10 px-1.5 py-0.5 rounded"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i>Auto-Generated</span>
                        </label>
                        <input type="text" id="username" name="username" readonly class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-100 text-slate-700 font-medium cursor-not-allowed outline-none select-none" placeholder="Auto-generated upon selection" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Email</label>
                        <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Enter email address">
                    </div>
                    <div id="passwordField">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Password</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Create password">
                        <p class="text-[10px] text-slate-400 mt-1">Min. 8 characters, one uppercase letter, one number.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Department</label>
                        <?php if (!$isSystemAdmin && !empty($userDept)): ?>
                        <input type="hidden" name="department" value="<?php echo htmlspecialchars($userDept, ENT_QUOTES); ?>">
                        <select id="department" disabled class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-100 text-slate-700 font-medium cursor-not-allowed outline-none select-none">
                            <option value="<?php echo htmlspecialchars($userDept, ENT_QUOTES); ?>" selected><?php echo htmlspecialchars($userDept, ENT_QUOTES); ?></option>
                        </select>
                        <?php else: ?>
                        <select id="department" name="department" onchange="onDepartmentChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" required>
                            <option value="">Select Department</option>
                            <option value="Health Center Services">Health Center Services</option>
                            <option value="Sanitation Permits">Sanitation Permits</option>
                            <option value="Immunization & Nutrition">Immunization & Nutrition</option>
                            <option value="Wastewater Services">Wastewater Services</option>
                            <option value="Health Surveillance">Health Surveillance</option>
                        </select>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Primary Role</label>
                        <select id="roleId" name="role" onchange="onRoleChange(this.value)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" required>
                            <option value="">Select Primary Role</option>
                            <?php 
                            $primaryCategories = getDepartmentResolver()->getPrimaryRoleCategoriesForDepartment($userDept, $isSystemAdmin);
                            foreach ($primaryCategories as $catRole): 
                                if ($catRole === 'System Admin') continue;
                            ?>
                            <option value="<?php echo htmlspecialchars($catRole, ENT_QUOTES); ?>"><?php echo htmlspecialchars($catRole, ENT_QUOTES); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Position</label>
                        <select id="roleDescription" name="role_description" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="">Select Position</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Suspended">Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeModal('addUserModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" id="userFormSubmit" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-save mr-1.5"></i> Register User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    // ============================================================
    // FILTER USERS (Search + Role + Status)
    // ============================================================
    function filterUsers() {
        const searchQuery = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();
        const roleFilter = (document.getElementById('roleFilter')?.value || '').toLowerCase().trim();
        const statusFilter = (document.getElementById('statusFilter')?.value || '').trim();
        
        const rows = document.querySelectorAll('.user-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const employeeId = (row.dataset.employeeid || '').toLowerCase().trim();
            const fullName = (row.dataset.fullname || '').toLowerCase().trim();
            const username = (row.dataset.username || '').toLowerCase().trim();
            const email = (row.dataset.email || '').toLowerCase().trim();
            const department = (row.dataset.department || '').toLowerCase().trim();
            const role = (row.dataset.role || '').toLowerCase().trim();
            const roleDesc = (row.dataset.roledescription || '').toLowerCase().trim();
            const status = (row.dataset.status || '').trim();
            
            let show = true;

            // 1. Live Search Filter (matches Employee ID, Name, Username, Email, Department)
            if (searchQuery !== '') {
                const matchEmpId = employeeId.includes(searchQuery);
                const matchName  = fullName.includes(searchQuery);
                const matchUser  = username.includes(searchQuery);
                const matchEmail = email.includes(searchQuery);
                const matchDept  = department.includes(searchQuery);

                if (!matchEmpId && !matchName && !matchUser && !matchEmail && !matchDept) {
                    show = false;
                }
            }

            // 2. Role Filter
            if (roleFilter !== 'all') {
                const matchRole = role === roleFilter || role.includes(roleFilter);
                const matchDesc = roleDesc === roleFilter || roleDesc.includes(roleFilter);
                if (!matchRole && !matchDesc) {
                    show = false;
                }
            }

            // 3. Status Filter
            if (statusFilter !== 'all' && status !== statusFilter) {
                show = false;
            }
            
            row.style.display = show ? 'table-row' : 'none';
            if (show) visibleCount++;
        });

        // Update count display badge
        const registeredCountEl = document.getElementById('registeredCount');
        if (registeredCountEl) {
            registeredCountEl.textContent = visibleCount;
        }
    }

    // ============================================================
    // FILTER ACTIVITY
    // ============================================================
    function filterActivity(status) {
        document.querySelectorAll('.filter-btn-activity').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-dark', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700');
        });
        
        if (status === 'all') {
            document.getElementById('act-all').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Success') {
            document.getElementById('act-success').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Failed') {
            document.getElementById('act-failed').classList.add('active', 'bg-brand-dark', 'text-white');
        }
        
        const rows = document.querySelectorAll('.activity-row');
        rows.forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Cascading Dropdown Mappings for Department -> Primary Role -> Position (Head to Lower Level)
    const PRIMARY_ROLES = [
        'Health Center Director',
        'Medical Practitioner',
        'Health Center Staff',
        'Sanitation Director',
        'Sanitation Officer',
        'Immunization Lead',
        'Nutrition Staff',
        'Wastewater Lead',
        'Surveillance Lead',
        'Surveillance Staff'
    ];

    const DEPT_TO_ROLES = {
        'Health Center Services': ['Health Center Director', 'Medical Practitioner', 'Health Center Staff'],
        'Health Center': ['Health Center Director', 'Medical Practitioner', 'Health Center Staff'],

        'Sanitation Permits': ['Sanitation Director', 'Sanitation Officer'],
        'Sanitation': ['Sanitation Director', 'Sanitation Officer'],

        'Immunization & Nutrition': ['Immunization Lead', 'Nutrition Staff'],
        'Immunization': ['Immunization Lead'],
        'Nutrition': ['Nutrition Staff'],

        'Wastewater Services': ['Wastewater Lead'],
        'Wastewater': ['Wastewater Lead'],

        'Health Surveillance': ['Surveillance Lead', 'Surveillance Staff'],
        'Administration': ['System Admin']
    };

    const ROLE_TO_DESCRIPTIONS = {
        'System Admin': ['System Administrator'],
        'Health Center Director': ['Health Center Director'],
        'Medical Practitioner': ['Doctor', 'Nurse', 'Dentist', 'Laboratory Technician'],
        'Health Center Staff': ['Medical Records Clerk', 'Appointment Clerk'],
        'Sanitation Director': ['Sanitation Director', 'Sanitation Officer'],
        'Sanitation Officer': ['Inspector', 'Permit Clerk', 'Cashier'],
        'Immunization Lead': ['Immunization Coordinator', 'Midwife'],
        'Nutrition Staff': ['Nutritionist', 'Nutrition Educator'],
        'Wastewater Lead': ['Wastewater Officer'],
        'Surveillance Lead': ['Surveillance Officer', 'Surveillance Coordinator']
    };

    function updateRoleCardCountersJS() {
        const rows = document.querySelectorAll('.user-row');
        const counts = {};

        rows.forEach(row => {
            const role = (row.dataset.role || '').trim().toLowerCase();
            const desc = (row.dataset.roledescription || '').trim().toLowerCase();

            if (role) counts[role] = (counts[role] || 0) + 1;
            if (desc) counts[desc] = (counts[desc] || 0) + 1;
        });

        const roleCards = document.querySelectorAll('.role-item-card');
        roleCards.forEach(card => {
            const rName = (card.dataset.rolename || '').trim().toLowerCase();
            const countSpan = card.querySelector('.role-user-count');
            if (countSpan && rName) {
                const count = counts[rName] || 0;
                countSpan.textContent = `${count} users`;
            }
        });
    }

    const CURRENT_USER_DEPT = <?php echo json_encode($userDept); ?>;
    const IS_SYSTEM_ADMIN   = <?php echo json_encode($isSystemAdmin); ?>;

    function onDepartmentChange(dept, targetRole = '') {
        if (!IS_SYSTEM_ADMIN && CURRENT_USER_DEPT) {
            dept = CURRENT_USER_DEPT;
        }
        const roleSelect = document.getElementById('roleId');
        if (!roleSelect) return;
        roleSelect.innerHTML = '<option value="">Select Primary Role</option>';
        
        let roles = dept && DEPT_TO_ROLES[dept] ? DEPT_TO_ROLES[dept] : PRIMARY_ROLES;
        roles.forEach(r => {
            if (r === 'System Admin') return; // Cannot select Admin
            if (!IS_SYSTEM_ADMIN && (r.includes('Director') || r.includes('System Admin'))) {
                return; // Non-admin Department Heads cannot register Director roles
            }
            const opt = document.createElement('option');
            opt.value = r;
            opt.textContent = r;
            if (r === targetRole) opt.selected = true;
            roleSelect.appendChild(opt);
        });
        
        if (targetRole) {
            onRoleChange(targetRole);
        } else {
            onRoleChange(roleSelect.value);
        }
    }


    function generateNextEmployeeIdJS(dept, role) {
        const deptRolePrefixes = {
            'Health Center Services_Health Center Director': { prefix: 'HCD-', pad: 4 },
            'Health Center Services_Medical Practitioner':   { prefix: 'HMP-', pad: 4 },
            'Health Center Services_Health Center Staff':     { prefix: 'HCS-', pad: 4 },
            'Health Center_Health Center Director':          { prefix: 'HCD-', pad: 4 },
            'Health Center_Medical Practitioner':            { prefix: 'HMP-', pad: 4 },
            'Health Center_Health Center Staff':              { prefix: 'HCS-', pad: 4 },

            'Sanitation Permits_Sanitation Director':        { prefix: 'SD-',  pad: 4 },
            'Sanitation Permits_Sanitation Officer':         { prefix: 'SO-',  pad: 4 },
            'Sanitation_Sanitation Director':                { prefix: 'SD-',  pad: 4 },
            'Sanitation_Sanitation Officer':                 { prefix: 'SO-',  pad: 4 },

            'Immunization & Nutrition_Immunization Lead':    { prefix: 'IL-',  pad: 4 },
            'Immunization & Nutrition_Nutrition Staff':      { prefix: 'NS-',  pad: 4 },
            'Immunization_Immunization Lead':                { prefix: 'IL-',  pad: 4 },
            'Nutrition_Nutrition Staff':                     { prefix: 'NS-',  pad: 4 },

            'Wastewater Services_Wastewater Lead':           { prefix: 'WL-',  pad: 4 },
            'Wastewater_Wastewater Lead':                    { prefix: 'WL-',  pad: 4 },

            'Health Surveillance_Surveillance Lead':         { prefix: 'SL-',  pad: 4 },
            'Administration_System Admin':                   { prefix: 'HSA-ADMIN-', pad: 2 }
        };

        const deptPrefixes = {
            'Health Center Services':   { prefix: 'HCD-', pad: 4 },
            'Health Center':            { prefix: 'HCD-', pad: 4 },
            'Sanitation Permits':       { prefix: 'SD-',  pad: 4 },
            'Sanitation':               { prefix: 'SD-',  pad: 4 },
            'Immunization & Nutrition': { prefix: 'IL-',  pad: 4 },
            'Immunization':             { prefix: 'IL-',  pad: 4 },
            'Nutrition':                { prefix: 'NS-',  pad: 4 },
            'Wastewater Services':      { prefix: 'WL-',  pad: 4 },
            'Wastewater':               { prefix: 'WL-',  pad: 4 },
            'Health Surveillance':      { prefix: 'SL-',  pad: 4 },
            'Administration':           { prefix: 'HSA-ADMIN-', pad: 2 }
        };

        const key = `${dept}_${role}`;
        const config = deptRolePrefixes[key] || deptPrefixes[dept] || { prefix: 'EMP-', pad: 4 };
        const prefix = config.prefix;
        const pad = config.pad;

        const rows = document.querySelectorAll('.user-row');
        let maxNum = 0;

        rows.forEach(row => {
            const empId = row.dataset.employeeid || row.dataset.username || '';
            if (empId.startsWith(prefix)) {
                const numPart = empId.substring(prefix.length);
                const num = parseInt(numPart, 10);
                if (!isNaN(num) && num > maxNum) {
                    maxNum = num;
                }
            }
        });

        const nextNum = (maxNum + 1).toString().padStart(pad, '0');
        return prefix + nextNum;
    }

    function onRoleChange(role, targetDesc = '') {
        const descSelect = document.getElementById('roleDescription');
        if (descSelect) {
            descSelect.innerHTML = '<option value="">Select Position</option>';
            let descs = role && ROLE_TO_DESCRIPTIONS[role] ? ROLE_TO_DESCRIPTIONS[role] : [];
            descs.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                if (d === targetDesc) opt.selected = true;
                descSelect.appendChild(opt);
            });
        }

        // Auto-generate Employee ID when adding a new user
        const userId = document.getElementById('userId')?.value;
        const dept = document.getElementById('department')?.value || '';
        const usernameInput = document.getElementById('username');
        if (!userId && usernameInput && (dept || role)) {
            usernameInput.value = generateNextEmployeeIdJS(dept, role);
        }
    }

    // ============================================================
    // OPEN ADD USER MODAL (RESET FORM)
    // ============================================================
    function openAddUserModal() {
        const form = document.getElementById('userForm');
        if (form) form.reset();
        document.getElementById('userId').value = '';
        const initialDept = (!IS_SYSTEM_ADMIN && CURRENT_USER_DEPT) ? CURRENT_USER_DEPT : 'Health Center Services';
        const deptSelect = document.getElementById('department');
        if (deptSelect) deptSelect.value = initialDept;

        onDepartmentChange(initialDept);
        document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-plus text-brand-medium"></i> Register New User';
        document.getElementById('userFormSubmit').innerHTML = '<i class="fa-solid fa-save mr-1.5"></i> Register User';
        const err = document.getElementById('userFormError');
        if (err) err.classList.add('hidden');
        
        const initialRole = (DEPT_TO_ROLES[initialDept] && DEPT_TO_ROLES[initialDept][0]) ? DEPT_TO_ROLES[initialDept][0] : 'Health Center Director';
        document.getElementById('username').value = generateNextEmployeeIdJS(initialDept, initialRole);
        
        openModal('addUserModal');
    }


    // Override openModal for addUserModal to ensure clean state
    const _origOpenModal = openModal;
    openModal = function(id) {
        if (id === 'addUserModal' && !document.getElementById('userId').value) {
            document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-plus text-brand-medium"></i> Register New User';
            document.getElementById('userFormSubmit').innerHTML = '<i class="fa-solid fa-save mr-1.5"></i> Register User';
        }
        _origOpenModal(id);
    };

    // ============================================================
    // REAL-TIME REACTIVE DOM UPDATES
    // ============================================================
    function updateKPISummariesJS() {
        const rows = document.querySelectorAll('.user-row');
        const total = rows.length;
        let active = 0;
        let inactive = 0;

        rows.forEach(r => {
            const st = (r.dataset.status || 'Active').trim();
            if (st === 'Active') active++;
            else if (st === 'Inactive') inactive++;
        });

        const totalEl = document.getElementById('kpiTotalUsers');
        if (totalEl) totalEl.textContent = total;

        const activeEl = document.getElementById('kpiActiveUsers');
        if (activeEl) activeEl.textContent = active;

        const subActive = document.getElementById('kpiSubActive');
        if (subActive) subActive.textContent = `${active} Active`;

        const subInactive = document.getElementById('kpiSubInactive');
        if (subInactive) subInactive.textContent = `${inactive} Inactive`;

        const activePct = document.getElementById('kpiActivePercent');
        if (activePct) activePct.textContent = `${total > 0 ? Math.round((active / total) * 100) : 0}% of total`;

        const headerBadge = document.getElementById('kpiHeaderUserBadge');
        if (headerBadge) headerBadge.innerHTML = `<i class="fa-solid fa-users-cog"></i> ${total} Users`;

        const regCount = document.getElementById('registeredCount');
        if (regCount) regCount.textContent = total;
    }

    function updateRoleCardCountersJS() {
        const counts = {};
        document.querySelectorAll('.user-row').forEach(row => {
            const rName = row.dataset.role || '';
            const rDesc = row.dataset.roledescription || '';
            if (rName) counts[rName] = (counts[rName] || 0) + 1;
            if (rDesc && rDesc !== rName) counts[rDesc] = (counts[rDesc] || 0) + 1;
        });

        document.querySelectorAll('.role-item-card').forEach(card => {
            const rName = card.dataset.rolename || '';
            const countSpan = card.querySelector('.role-user-count');
            if (countSpan) {
                const count = counts[rName] || 0;
                countSpan.textContent = `${count} users`;
            }
        });
    }

    const CURRENT_USER_NAME = <?php echo json_encode($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System Administrator'); ?>;
    const CURRENT_USER_ROLE = <?php echo json_encode($_SESSION['role_description'] ?? $_SESSION['role'] ?? 'System Administrator'); ?>;
    const CURRENT_CLIENT_IP = <?php echo json_encode(function_exists('getClientIP') ? getClientIP() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')); ?>;

    function getJSClientDevice() {
        const ua = navigator.userAgent;
        let os = "Linux";
        if (ua.indexOf("Win") !== -1) os = "Windows 11";
        else if (ua.indexOf("Mac") !== -1) os = "macOS";
        else if (ua.indexOf("Linux") !== -1 || ua.indexOf("X11") !== -1) os = "Linux";
        else if (ua.indexOf("Android") !== -1) os = "Android 14";
        else if (ua.indexOf("iPhone") !== -1 || ua.indexOf("iPad") !== -1) os = "iOS 17";

        let browser = "Chrome";
        if (ua.indexOf("Firefox") !== -1) browser = "Firefox";
        else if (ua.indexOf("Chrome") !== -1 && ua.indexOf("Edg") === -1) browser = "Chrome";
        else if (ua.indexOf("Safari") !== -1 && ua.indexOf("Chrome") === -1) browser = "Safari";
        else if (ua.indexOf("Edg") !== -1) browser = "Edge";

        const isMobile = /Mobi|Android|iPhone/i.test(ua);
        return `${isMobile ? 'Mobile' : 'Desktop'} • ${browser} (${os})`;
    }

    function addActivityLogJS(actionText, status = 'Success', role = CURRENT_USER_ROLE, userName = CURRENT_USER_NAME) {
        const tbody = document.getElementById('activityTableBody');
        if (!tbody) return;

        const emptyTd = tbody.querySelector('tr td[colspan]');
        if (emptyTd) emptyTd.closest('tr').remove();

        const dateStr = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        const deviceStr = getJSClientDevice();
        const tr = document.createElement('tr');
        tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition activity-row';
        tr.dataset.status = status;
        tr.innerHTML = `
            <td class="px-4 py-3 font-medium text-slate-700">${userName}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">
                    ${role}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-600 text-sm font-medium">${actionText}</td>
            <td class="px-4 py-3 text-slate-500 text-xs">${dateStr}</td>
            <td class="px-4 py-3 text-xs">
                <span class="font-mono font-semibold text-slate-700 block">${CURRENT_CLIENT_IP}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5"><i class="fas fa-desktop text-[8px] mr-1"></i>${deviceStr}</span>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 ${status === 'Success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'} rounded-full text-xs font-semibold">
                    ${status}
                </span>
            </td>
        `;
        tbody.insertBefore(tr, tbody.firstChild);

        const actTotal = document.getElementById('kpiTotalActivities');
        if (actTotal) {
            actTotal.textContent = tbody.querySelectorAll('tr.activity-row').length;
        }
    }

    // ============================================================
    // SUBMIT USER FORM (CREATE / UPDATE via API) - ZERO RELOAD
    // ============================================================
    function submitUserForm(e) {
        e.preventDefault();
        const userId = document.getElementById('userId').value;
        const action = userId ? 'update' : 'create';

        const formData = new FormData(document.getElementById('userForm'));
        formData.append('action', action);
        if (userId) {
            formData.append('user_id', userId);
        }

        const submitBtn = document.getElementById('userFormSubmit');
        const origText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';

        fetch('user_management_api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;

            if (data.success) {
                const u = data.data || {};
                const id = userId || u.id || Date.now();
                const fullName = formData.get('full_name');
                const username = formData.get('username');
                const email = formData.get('email');
                const dept = formData.get('department');
                const role = formData.get('role');
                const desc = formData.get('role_description');
                const status = formData.get('status') || 'Active';
                const initials = fullName ? fullName.split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase() : '?';

                const tbody = document.getElementById('usersTableBody');
                const emptyTd = tbody?.querySelector('tr td[colspan]');
                if (emptyTd) emptyTd.closest('tr').remove();

                triggerTableSkeletonRefresh(() => {
                    if (action === 'create') {
                        const tr = document.createElement('tr');
                        tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition user-row';
                        tr.dataset.id = id;
                        tr.dataset.employeeid = username;
                        tr.dataset.role = role;
                        tr.dataset.status = status;
                        tr.dataset.fullname = fullName;
                        tr.dataset.username = username;
                        tr.dataset.email = email;
                        tr.dataset.department = dept;
                        tr.dataset.roledescription = desc;

                        tr.innerHTML = `
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-brand-light flex items-center justify-center text-brand-dark font-bold text-xs">
                                        ${initials}
                                    </div>
                                    <div>
                                        <span class="font-medium text-slate-800">${fullName}</span>
                                        <span class="text-xs text-slate-400 block">${email}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-sm font-semibold font-mono text-xs">${username}</td>
                            <td class="px-4 py-3 text-slate-600 text-sm font-medium">${dept}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-semibold">
                                    ${role}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-sm font-medium">${desc || '—'}</td>
                            <td class="px-4 py-3">
                                <span class="status-badge px-2 py-1 ${status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'} rounded-full text-xs font-semibold">
                                    ${status}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">Never</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <button onclick="editUser(${id})" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition px-2 py-1 hover:bg-brand-light rounded" title="Edit User">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button onclick="managePermissions(${id})" class="text-purple-600 hover:text-purple-800 text-xs font-medium transition px-2 py-1 hover:bg-purple-50 rounded" title="Permissions">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                    <button onclick="toggleUserStatus(${id})" class="text-amber-600 hover:text-amber-800 text-xs font-medium transition px-2 py-1 hover:bg-amber-50 rounded toggle-btn" title="Toggle Status">
                                        <i class="fa-solid ${status === 'Active' ? 'fa-pause' : 'fa-play'}"></i>
                                    </button>
                                    <button onclick="deleteUser(${id})" class="text-red-500 hover:text-red-700 text-xs font-medium transition px-2 py-1 hover:bg-red-50 rounded" title="Delete User">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.insertBefore(tr, tbody.firstChild);
                        showToast(`User '${fullName}' registered successfully!`, 'success', 'Realtime Sync');
                        addActivityLogJS(`Registered user: ${fullName}`);
                    } else {
                        const row = document.querySelector(`.user-row[data-id="${id}"]`);
                        if (row) {
                            row.dataset.fullname = fullName;
                            row.dataset.username = username;
                            row.dataset.email = email;
                            row.dataset.department = dept;
                            row.dataset.role = role;
                            row.dataset.roledescription = desc;
                            row.dataset.status = status;

                            row.children[0].querySelector('span.font-medium').textContent = fullName;
                            row.children[0].querySelector('span.text-xs').textContent = email;
                            row.children[0].querySelector('div.w-8').textContent = initials;
                            row.children[1].textContent = username;
                            row.children[2].textContent = dept;
                            row.children[3].querySelector('span').textContent = role;
                            row.children[4].textContent = desc || '—';
                            const statusBadge = row.children[5].querySelector('span');
                            statusBadge.textContent = status;
                            statusBadge.className = `status-badge px-2 py-1 ${status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'} rounded-full text-xs font-semibold`;
                        }
                        showToast(`User '${fullName}' updated successfully!`, 'success', 'Realtime Sync');
                        addActivityLogJS(`Updated user: ${fullName}`);
                    }

                    closeModal('addUserModal');
                    updateKPISummariesJS();
                    updateRoleCardCountersJS();
                });
            } else {
                const errDiv = document.getElementById('userFormError');
                if (errDiv) {
                    errDiv.textContent = data.message;
                    errDiv.classList.remove('hidden');
                }
                showToast(data.message, 'danger', 'Error');
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origText;
            showToast('Error connecting to server', 'danger', 'Error');
            console.error(err);
        });
    }

    // ============================================================
    // EDIT USER (POPULATE FORM)
    // ============================================================
    function editUser(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        if (!row) {
            showToast('User data not found', 'danger');
            return;
        }

        document.getElementById('userId').value = userId;
        document.getElementById('fullName').value = row.dataset.fullname || '';
        document.getElementById('username').value = row.dataset.username || '';
        document.getElementById('email').value = row.dataset.email || '';
        document.getElementById('status').value = row.dataset.status || 'Active';
        document.getElementById('password').value = '';

        const dept = row.dataset.department || '';
        const role = row.dataset.role || '';
        const desc = row.dataset.roledescription || '';

        document.getElementById('department').value = dept;
        onDepartmentChange(dept, role);
        if (desc) {
            onRoleChange(role, desc);
        }

        document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-pen text-brand-medium"></i> Edit User';
        document.getElementById('userFormSubmit').innerHTML = '<i class="fa-solid fa-save mr-1.5"></i> Update User';

        const err = document.getElementById('userFormError');
        if (err) err.classList.add('hidden');

        openModal('addUserModal');
    }

    // ============================================================
    // MANAGE PERMISSIONS (LOAD FOR ROLE)
    // ============================================================
    function managePermissions(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        const userRole = row ? row.dataset.role : '';
        const roleSelect = document.getElementById('permissionRoleSelect');
        
        if (roleSelect && userRole) {
            for (let opt of roleSelect.options) {
                if (opt.text.toLowerCase() === userRole.toLowerCase()) {
                    roleSelect.value = opt.value;
                    loadRolePermissions(opt.value);
                    break;
                }
            }
        }
        showToast('🔑 Select permissions for role: ' + (userRole || 'User'), 'info');
        document.getElementById('permissionGrid').scrollIntoView({ behavior: 'smooth' });
    }

    // ============================================================
    // DYNAMIC ROLE PERMISSIONS (LOAD & SAVE)
    // ============================================================
    function showToast(message, type = 'info', title = '') {
        if (typeof toast !== 'undefined') {
            if (type === 'danger' || type === 'error') {
                toast.error(message, { title: title || 'Error' });
            } else if (type === 'success') {
                toast.success(message, { title: title || 'Success' });
            } else if (type === 'warning') {
                toast.warning(message, { title: title || 'Warning' });
            } else {
                toast.info(message, { title: title || 'Notification' });
            }
            return;
        }
        if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
            if (type === 'danger' || type === 'error') {
                ModalSystem.toast.error(message, { title: title || 'Error' });
            } else if (type === 'success') {
                ModalSystem.toast.success(message, { title: title || 'Success' });
            } else if (type === 'warning') {
                ModalSystem.toast.warning(message, { title: title || 'Warning' });
            } else {
                ModalSystem.toast.info(message, { title: title || 'Notification' });
            }
        }
    }

    function openModal(id) {
        if (typeof ModalSystem !== 'undefined' && ModalSystem.open) {
            ModalSystem.open(id);
        } else {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                el.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }
    }

    function closeModal(id) {
        if (typeof ModalSystem !== 'undefined' && ModalSystem.close) {
            ModalSystem.close(id);
        } else {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('hidden');
                el.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        }
    }

    // ============================================================
    // SKELETON LOADER HELPERS & SHIMMER REFRESH TRIGGERS
    // ============================================================
    function triggerTableSkeletonRefresh(callback) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) {
            if (typeof callback === 'function') callback();
            return;
        }
        tbody.classList.add('animate-pulse', 'opacity-50');
        setTimeout(() => {
            if (typeof callback === 'function') callback();
            tbody.classList.remove('animate-pulse', 'opacity-50');
        }, 250);
    }

    function triggerPermissionSkeletonRefresh(callback) {
        const grid = document.getElementById('permissionGrid');
        if (!grid) {
            if (typeof callback === 'function') callback();
            return;
        }
        grid.classList.add('animate-pulse', 'opacity-50');
        setTimeout(() => {
            if (typeof callback === 'function') callback();
            grid.classList.remove('animate-pulse', 'opacity-50');
        }, 250);
    }

    function renderPermissionSkeletonJS() {
        return `
            <div class="space-y-4 animate-pulse">
                <div class="border border-slate-100 rounded-lg p-3 space-y-3 bg-slate-50/50">
                    <div class="h-4 bg-slate-200 rounded w-1/3"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="h-3 bg-slate-200/70 rounded w-4/5"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-5/6"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-2/3"></div>
                    </div>
                </div>
                <div class="border border-slate-100 rounded-lg p-3 space-y-3 bg-slate-50/50">
                    <div class="h-4 bg-slate-200 rounded w-1/4"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="h-3 bg-slate-200/70 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-4/5"></div>
                    </div>
                </div>
                <div class="border border-slate-100 rounded-lg p-3 space-y-3 bg-slate-50/50">
                    <div class="h-4 bg-slate-200 rounded w-2/5"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="h-3 bg-slate-200/70 rounded w-2/3"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderTableSkeletonJS() {
        let html = '';
        for (let i = 0; i < 4; i++) {
            html += `
                <tr class="animate-pulse border-b border-slate-100">
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-3/4 mb-1"></div><div class="h-3 bg-slate-100 rounded w-1/2"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-2/3"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/2"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/3"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/2"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/4"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/3"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/4"></div></td>
                </tr>
            `;
        }
        return html;
    }

    function loadRolePermissions(roleId) {
        const grid = document.getElementById('permissionGrid');
        grid.innerHTML = renderPermissionSkeletonJS();

        fetch(`user_management_api.php?action=get_role_permissions&role_id=${roleId}`)
        .then(res => res.json())
        .then(res => {
            if (!res.success || !res.data) {
                grid.innerHTML = '<p class="text-xs text-slate-400 text-center py-6">No permissions found for this role.</p>';
                return;
            }

            let html = '';
            for (const [module, perms] of Object.entries(res.data)) {
                html += `
                    <div class="border border-slate-200 rounded-lg p-3">
                        <h4 class="font-semibold text-slate-700 text-sm flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-shield-halved text-brand-medium"></i>
                            ${module}
                        </h4>
                        <div class="grid grid-cols-2 gap-2">
                `;
                perms.forEach(p => {
                    const checked = p.granted ? 'checked' : '';
                    const permName = p.label || p.name || p.slug || 'Permission';
                    const slug = (p.slug || '').toLowerCase();
                    
                    const isAdminOnlySlug = ['roles.manage', 'settings.manage', 'users.delete', 'logs.view', 'dashboard.system_admin'].includes(slug);
                    const isDisabled = (!IS_SYSTEM_ADMIN && isAdminOnlySlug);
                    
                    const disabledAttr = isDisabled ? 'disabled' : '';
                    const labelClass = isDisabled ? 'text-slate-400 opacity-60 cursor-not-allowed' : 'text-slate-600 cursor-pointer';
                    const lockBadge = isDisabled ? '<i class="fa-solid fa-lock text-[10px] text-amber-500 ml-0.5" title="Requires System Administrator Privileges"></i>' : '';

                    html += `
                        <label class="flex items-center gap-2 text-xs ${labelClass}" title="${isDisabled ? 'Requires System Administrator Privileges' : ''}">
                            <input type="checkbox" value="${p.id}" ${checked} ${disabledAttr} class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium ${isDisabled ? 'bg-slate-100 cursor-not-allowed' : ''}">
                            <span>${permName}</span>
                            ${lockBadge}
                        </label>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            }
            grid.innerHTML = html || '<p class="text-xs text-slate-400 text-center py-6">No permissions defined.</p>';
        })
        .catch(err => {
            grid.innerHTML = '<p class="text-xs text-rose-500 text-center py-6">Failed to load permissions.</p>';
            showToast('Failed to load permissions', 'danger', 'Permission Error');
            console.error(err);
        });
    }

    function savePermissions() {
        const roleSelect = document.getElementById('permissionRoleSelect');
        const roleId = roleSelect ? roleSelect.value : 0;
        const roleName = roleSelect ? roleSelect.options[roleSelect.selectedIndex].text : '';
        const checkboxes = document.querySelectorAll('#permissionGrid input[type="checkbox"]:checked:not(:disabled)');

        const permIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        const saveBtn = document.querySelector('button[onclick="savePermissions()"]');
        const origBtnText = saveBtn ? saveBtn.innerHTML : '<i class="fa-solid fa-save mr-1"></i> Save Permissions';

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
        }

        const body = new URLSearchParams();
        body.append('action', 'save_permissions');
        body.append('role_id', roleId);
        body.append('permission_ids', JSON.stringify(permIds));

        fetch('user_management_api.php', {
            method: 'POST',
            body: body
        })
        .then(res => res.json())
        .then(data => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = origBtnText;
            }

            if (data.success) {
                showToast(`Permissions saved for ${roleName || 'role'}!`, 'success', 'Permission Management');

                // Real-time update permission count badge on target role card
                const roleCards = document.querySelectorAll('.role-item-card');
                roleCards.forEach(card => {
                    if (card.dataset.rolename && card.dataset.rolename.toLowerCase() === roleName.toLowerCase()) {
                        const permSpan = card.querySelector('.role-perm-count') || card.querySelector('p span');
                        if (permSpan) {
                            const userCountText = card.querySelector('.role-user-count') ? card.querySelector('.role-user-count').textContent : '';
                            permSpan.parentElement.innerHTML = `<span class="role-user-count">${userCountText}</span> • <span class="role-perm-count">${permIds.length} permissions</span>`;
                        }
                    }
                });

                addActivityLogJS(`Updated permissions for role: ${roleName}`);
            } else {
                showToast(data.message || 'Failed to save permissions.', 'danger', 'Save Failed');
            }
        })
        .catch(err => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = origBtnText;
            }
            showToast('Error saving permissions', 'danger', 'Save Error');
            console.error(err);
        });
    }

    // Load permissions for initial role on page load
    document.addEventListener('DOMContentLoaded', () => {
        const roleSelect = document.getElementById('permissionRoleSelect');
        if (roleSelect && roleSelect.value) {
            loadRolePermissions(roleSelect.value);
        }
    });

    // ============================================================
    // TOGGLE USER STATUS via API (REALTIME DOM UPDATE)
    // ============================================================
    function toggleUserStatus(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        const userName = row ? (row.dataset.fullname || `ID ${userId}`) : `ID ${userId}`;

        const performToggle = () => {
            const body = new URLSearchParams();
            body.append('action', 'toggle_status');
            body.append('user_id', userId);

            fetch('user_management_api.php', {
                method: 'POST',
                body: body
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (row) {
                        const currentStatus = (row.dataset.status || 'Active').trim();
                        const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
                        row.dataset.status = newStatus;

                        const statusBadge = row.children[5].querySelector('span');
                        if (statusBadge) {
                            statusBadge.textContent = newStatus;
                            statusBadge.className = `status-badge px-2 py-1 ${newStatus === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'} rounded-full text-xs font-semibold`;
                        }

                        const toggleBtn = row.querySelector('.toggle-btn i');
                        if (toggleBtn) {
                            toggleBtn.className = `fa-solid ${newStatus === 'Active' ? 'fa-pause' : 'fa-play'}`;
                        }
                    }

                    updateKPISummariesJS();
                    addActivityLogJS(`Toggled status for: ${userName}`);
                    showToast(data.message, 'success', 'Status Updated (Realtime)');
                } else {
                    showToast(data.message, 'danger', 'Update Failed');
                }
            })
            .catch(err => {
                showToast('Error toggling status', 'danger', 'Error');
                console.error(err);
            });
        };

        if (typeof ModalSystem !== 'undefined' && ModalSystem.confirm) {
            ModalSystem.confirm(
                `Are you sure you want to change status for user '${userName}'?`,
                performToggle,
                { title: 'Change User Status', confirmText: 'Change Status', type: 'warning' }
            );
        } else if (confirm(`Are you sure you want to change status for user '${userName}'?`)) {
            performToggle();
        }
    }

    // ============================================================
    // DELETE USER via API (REALTIME DOM UPDATE)
    // ============================================================
    function deleteUser(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        const userName = row ? (row.dataset.fullname || `ID ${userId}`) : `ID ${userId}`;

        const performDelete = () => {
            const body = new URLSearchParams();
            body.append('action', 'delete');
            body.append('user_id', userId);

            fetch('user_management_api.php', {
                method: 'POST',
                body: body
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            row.remove();
                            updateKPISummariesJS();
                            updateRoleCardCountersJS();
                        }, 300);
                    } else {
                        updateKPISummariesJS();
                        updateRoleCardCountersJS();
                    }
                    addActivityLogJS(`Deleted user: ${userName}`);
                    showToast(data.message, 'success', 'User Deleted (Realtime)');
                } else {
                    showToast(data.message, 'danger', 'Delete Failed');
                }
            })
            .catch(err => {
                showToast('Error deleting user', 'danger', 'Error');
                console.error(err);
            });
        };

        if (typeof ModalSystem !== 'undefined' && ModalSystem.confirm) {
            ModalSystem.confirm(
                `Are you sure you want to delete user '${userName}'? This action cannot be undone.`,
                performDelete,
                { title: 'Delete User Confirmation', confirmText: 'Delete User', type: 'danger' }
            );
        } else if (confirm(`Are you sure you want to delete user '${userName}'? This action cannot be undone.`)) {
            performDelete();
        }
    }

    // ============================================================
    // EDIT ROLE
    // ============================================================
    function editRole(roleId) {
        const roleSelect = document.getElementById('permissionRoleSelect');
        if (roleSelect) {
            roleSelect.value = roleId;
            loadRolePermissions(roleId);
            showToast('✏️ Loaded permissions for role ID: ' + roleId, 'info');
            document.getElementById('permissionGrid').scrollIntoView({ behavior: 'smooth' });
        }
    }

    // ============================================================
    // CLEAR LOGS CONFIRMATION MODAL
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

    function clearLogs() {
        openClearLogsModal();
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
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('🔄 Refreshing data...', 'info');
        setTimeout(() => {
            location.reload();
        }, 500);
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
    
    .filter-btn-activity.active {
        background: #0B4F4A !important;
        color: white !important;
    }
    .filter-btn-activity:not(.active):hover {
        opacity: 0.8;
    }
    
<!-- CLEAR ACTIVITY LOGS CONFIRMATION MODAL -->
<div id="clearLogsModal" class="fixed inset-0 z-50 items-center justify-center hidden bg-slate-900/50 backdrop-blur-sm p-4 transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6 text-center transform transition-all scale-95 opacity-0 duration-200" id="clearLogsModalCard">
        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-1">Clear Activity Logs?</h3>
        <p class="text-xs text-slate-500 mb-5 leading-relaxed">
            Are you sure you want to clear all activity logs? This action will permanently delete all activity log records directly from the database (<code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono text-[11px]">activity_logs</code> table) and cannot be undone.
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