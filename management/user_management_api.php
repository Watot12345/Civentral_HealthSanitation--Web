<?php
// management/user_management_api.php
// ============================================================
// Lightweight AJAX action handler for User Management CRUD.
// Called via fetch() from the front-end JS. Returns JSON.
// ============================================================

header('Content-Type: application/json');

// Bootstrap
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Models/Role.php';
require_once __DIR__ . '/../app/Models/ActivityLog.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => 'Invalid request.'];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $db = Database::getInstance();
    $employeeModel = new Employee($db);
    $roleModel = new Role();
    $logModel = new ActivityLog();

    $isSystemAdmin = getPermissionService()->isAdminRole($_SESSION['role'] ?? '') 
        || getPermissionService()->isAdminRole($_SESSION['role_description'] ?? '') 
        || hasPermission(\App\Constants\Permissions::ROLES_MANAGE);
    $userDept = getDepartmentResolver()->resolveDepartmentName();


    switch ($action) {

        // ==========================================================
        // CREATE — Register a new user
        // ==========================================================
        case 'create':
            $fullName        = trim($_POST['full_name'] ?? '');
            $username        = trim($_POST['username'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $password        = $_POST['password'] ?? '';
            $role            = trim($_POST['role'] ?? 'Health Center Staff');
            $department      = trim($_POST['department'] ?? '');
            $roleDescription = trim($_POST['role_description'] ?? '');
            $status          = trim($_POST['status'] ?? 'Active');

            if (!$fullName || !$password) {
                $response = ['success' => false, 'message' => 'Full name and password are required.'];
                break;
            }

            // Departmental Scoping Guard: Non-admin department heads can only create users in their department
            if (!$isSystemAdmin && !empty($userDept)) {
                $submittedDept = trim($_POST['department'] ?? '');
                if (!empty($submittedDept) && strcasecmp($submittedDept, $userDept) !== 0) {
                    $response = ['success' => false, 'message' => "Access Denied: You cannot create users for another department ({$submittedDept}). Your department is {$userDept}."];
                    break;
                }
                $department = $userDept;
                $targetRoleName = $roleDescription ?: $role;
                if (!getDepartmentResolver()->isRoleInDepartment($targetRoleName, $userDept)) {
                    $response = ['success' => false, 'message' => "Access Denied: You can only register position roles within your department ({$userDept})."];
                    break;
                }
            }


            if (empty($username)) {
                $username = $employeeModel->generateNextEmployeeId($role, $department);
            }

            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Resolve role_id foreign key
            $allRoles = $roleModel->all();
            $matchedRoleId = null;
            foreach ($allRoles as $r) {
                if (strcasecmp($r['name'], $roleDescription) === 0 || strcasecmp($r['name'], $role) === 0) {
                    $matchedRoleId = (int) $r['id'];
                    break;
                }
            }

            $data = [
                'employee_id'      => $username,
                'full_name'        => $fullName,
                'username'         => $username,
                'email'            => $email,
                'password'         => $hashed,
                'role'             => $role,
                'department'       => $department,
                'role_description' => $roleDescription,
                'role_id'          => $matchedRoleId,
                'status'           => $status,
            ];

            $result = $employeeModel->create($data);

            // Log activity
            $logModel->log("Created user: {$fullName}", [
                'module'  => 'User Management',
                'details' => "Username: {$username}, Role: {$role}, Dept: {$department}",
            ]);

            $response = ['success' => true, 'message' => 'User registered successfully!', 'data' => $result];
            break;

        // ==========================================================
        // UPDATE — Edit an existing user
        // ==========================================================
        case 'update':
            $id              = (int) ($_POST['user_id'] ?? 0);
            $fullName        = trim($_POST['full_name'] ?? '');
            $username        = trim($_POST['username'] ?? '');
            $email           = trim($_POST['email'] ?? '');
            $role            = trim($_POST['role'] ?? '');
            $department      = trim($_POST['department'] ?? '');
            $roleDescription = trim($_POST['role_description'] ?? '');
            $status          = trim($_POST['status'] ?? '');

            $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
            if ($id && $currentUserId && $id === $currentUserId) {
                $response = ['success' => false, 'message' => 'You cannot edit your own account from User Management.'];
                break;
            }

            if (!$id || !$fullName || !$username) {
                $response = ['success' => false, 'message' => 'User ID, full name, and username are required.'];
                break;
            }

            // Departmental Scoping Guard: Non-admin department heads can only edit users within their department
            if (!$isSystemAdmin && !empty($userDept)) {
                $submittedDept = trim($_POST['department'] ?? '');
                if (!empty($submittedDept) && !canAccessDepartment($submittedDept)) {
                    $response = ['success' => false, 'message' => "Access Denied: You cannot reassign users to a different department ({$submittedDept}). Your department is {$userDept}."];
                    break;
                }

                $targetUser = $employeeModel->find($id);
                if (!empty($targetUser)) {
                    $targetDept = trim($targetUser['department'] ?? '');
                    $targetRole = trim($targetUser['role_description'] ?? $targetUser['role'] ?? '');
                    if (!getDepartmentResolver()->isRoleInDepartment($targetRole, $userDept) && !canAccessDepartment($targetDept)) {
                        $response = ['success' => false, 'message' => "Access Denied: You can only edit users within your department ({$userDept})."];
                        break;
                    }
                }
                $department = $userDept;
            }


            $data = [
                'full_name'        => $fullName,
                'username'         => $username,
                'email'            => $email,
                'role'             => $role,
                'department'       => $department,
                'role_description' => $roleDescription,
                'status'           => $status,
            ];

            // Update password only if provided
            $password = $_POST['password'] ?? '';
            if (!empty($password)) {
                $data['password'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $result = $employeeModel->updateById($id, $data);

            $logModel->log("Updated user: {$fullName} (ID: {$id})", [
                'module'  => 'User Management',
                'details' => "Role: {$role}, Status: {$status}",
            ]);

            $response = ['success' => true, 'message' => 'User updated successfully!', 'data' => $result];
            break;

        // ==========================================================
        // DELETE — Remove a user
        // ==========================================================
        case 'delete':
            $id = (int) ($_POST['user_id'] ?? 0);
            $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
            if ($id && $currentUserId && $id === $currentUserId) {
                $response = ['success' => false, 'message' => 'You cannot delete your own account.'];
                break;
            }

            if (!$isSystemAdmin) {
                $response = ['success' => false, 'message' => 'Access Denied: Only System Administrators are authorized to permanently delete employee accounts. Department Heads may set status to Inactive or Suspended instead.'];
                break;
            }

            $user = $employeeModel->find($id);
            if (empty($user)) {
                $response = ['success' => false, 'message' => 'User not found.'];
                break;
            }

            $userName = $user['full_name'] ?? "ID {$id}";
            $employeeModel->deleteById($id);

            $logModel->log("Deleted user: {$userName} (ID: {$id})", [
                'module' => 'User Management',
            ]);

            $response = ['success' => true, 'message' => "User '{$userName}' deleted."];
            break;

        // ==========================================================
        // TOGGLE STATUS — Active ↔ Inactive
        // ==========================================================
        case 'toggle_status':
            $id = (int) ($_POST['user_id'] ?? 0);
            $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
            if ($id && $currentUserId && $id === $currentUserId) {
                $response = ['success' => false, 'message' => 'You cannot disable your own logged-in account.'];
                break;
            }

            if (!$id) {
                $response = ['success' => false, 'message' => 'User ID is required.'];
                break;
            }

            $user = $employeeModel->find($id);
            if (!$isSystemAdmin && !empty($userDept) && !empty($user)) {
                $targetDept = trim($user['department'] ?? '');
                $targetRole = trim($user['role_description'] ?? $user['role'] ?? '');
                if (!getDepartmentResolver()->isRoleInDepartment($targetRole, $userDept) && strcasecmp($targetDept, $userDept) !== 0) {
                    $response = ['success' => false, 'message' => "Access Denied: You can only modify status for users within your department ({$userDept})."];
                    break;
                }
            }

            $currentStatus = $user['status'] ?? 'Active';
            $newStatus = ($currentStatus === 'Active') ? 'Inactive' : 'Active';

            $employeeModel->updateById($id, ['status' => $newStatus]);

            $userName = $user['full_name'] ?? "ID {$id}";
            $logModel->log("Changed user status: {$userName} to {$newStatus}", [
                'module'  => 'User Management',
                'details' => "Status toggled from {$currentStatus} to {$newStatus}"
            ]);

            $response = ['success' => true, 'message' => "Status for {$userName} updated to {$newStatus}.", 'new_status' => $newStatus];
            break;

        // ==========================================================
        // SET STATUS — Active, Inactive, Suspended
        // ==========================================================
        case 'set_status':
            $id = (int) ($_POST['user_id'] ?? 0);
            $newStatus = trim($_POST['new_status'] ?? '');
            $allowed = ['Active', 'Inactive', 'Suspended'];

            if (!$id || !in_array($newStatus, $allowed, true)) {
                $response = ['success' => false, 'message' => 'Invalid status value. Allowed: Active, Inactive, Suspended.'];
                break;
            }

            $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
            if ($id && $currentUserId && $id === $currentUserId) {
                $response = ['success' => false, 'message' => 'You cannot change your own logged-in account status.'];
                break;
            }

            $user = $employeeModel->find($id);
            if (!$user) {
                $response = ['success' => false, 'message' => 'User not found.'];
                break;
            }

            if (!$isSystemAdmin && !empty($userDept)) {
                $targetDept = trim($user['department'] ?? '');
                $targetRole = trim($user['role_description'] ?? $user['role'] ?? '');
                if (!getDepartmentResolver()->isRoleInDepartment($targetRole, $userDept) && strcasecmp($targetDept, $userDept) !== 0) {
                    $response = ['success' => false, 'message' => "Access Denied: You can only modify status for users within your department ({$userDept})."];
                    break;
                }
            }

            $employeeModel->updateById($id, ['status' => $newStatus]);

            $userName = $user['full_name'] ?? "ID {$id}";
            $logModel->log("Set user status: {$userName} to {$newStatus}", [
                'module'  => 'User Management',
                'details' => "Status changed to {$newStatus}"
            ]);

            $response = ['success' => true, 'message' => "Status for {$userName} updated to {$newStatus}.", 'new_status' => $newStatus];
            break;

        // ==========================================================
        // SAVE PERMISSIONS — Sync role permissions
        // ==========================================================
        case 'save_permissions':
        case 'update_role_permissions':
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $permissionIds = [];
            if (!empty($_POST['permission_ids'])) {
                $permissionIds = json_decode($_POST['permission_ids'], true) ?: [];
            } elseif (!empty($_POST['permissions'])) {
                $permissionIds = is_array($_POST['permissions']) ? $_POST['permissions'] : (json_decode($_POST['permissions'], true) ?: []);
            }

            if (!$roleId) {
                $response = ['success' => false, 'message' => 'Role ID is required.'];
                break;
            }

            // Departmental Scoping & Escalation Protection Guard
            if (!$isSystemAdmin && !empty($userDept)) {
                $rolesList = $roleModel->all();
                $targetRoleObj = null;
                foreach ($rolesList as $r) {
                    if ((int)($r['id'] ?? 0) === $roleId) {
                        $targetRoleObj = $r;
                        break;
                    }
                }

                if ($targetRoleObj) {
                    $targetRoleName = trim($targetRoleObj['name']);
                    $actorRole = trim($_SESSION['role_description'] ?? $_SESSION['role'] ?? '');

                    // 1. Department Boundary Check
                    if (!getDepartmentResolver()->isRoleInDepartment($targetRoleName, $userDept)) {
                        $response = ['success' => false, 'message' => "Access Denied: You can only modify permission matrices for position roles within your department ({$userDept})."];
                        break;
                    }

                    // 2. Self-Role / Director-Role Privilege Edit Restriction
                    if (strcasecmp($targetRoleName, $actorRole) === 0 || preg_match('/director|coordinator|lead/i', $targetRoleName)) {
                        $response = ['success' => false, 'message' => "Access Denied: Department Heads cannot edit permissions for Director/Lead roles (including their own). Only subordinate roles may be modified."];
                        break;
                    }
                }

                // 3. Strip Administrative Escalation Slugs from non-admin grant attempts
                $forbiddenSlugs = [
                    \App\Constants\Permissions::ROLES_MANAGE,
                    \App\Constants\Permissions::USERS_DELETE,
                    \App\Constants\Permissions::SETTINGS_MANAGE,
                    \App\Constants\Permissions::LOGS_VIEW,
                    \App\Constants\Permissions::SYSTEM_ADMIN_DASHBOARD
                ];
                $allDbPerms = $roleModel->getPermissionsForRole($roleId);
                $forbiddenIds = [];
                foreach ($allDbPerms as $p) {
                    if (in_array($p['slug'] ?? '', $forbiddenSlugs, true)) {
                        $forbiddenIds[] = (int) $p['id'];
                    }
                }
                $permissionIds = array_values(array_diff(array_map('intval', $permissionIds), $forbiddenIds));
            }


            $roleModel->syncPermissions($roleId, $permissionIds);

            // Invalidate cache
            if (class_exists('App\Services\PermissionService')) {
                \App\Services\PermissionService::getInstance()->invalidateCache();
            }

            // Resolve target role name for clear audit trail
            $targetRoleName = "Role ID #{$roleId}";
            $rolesList = $roleModel->all();
            foreach ($rolesList as $r) {
                if ((int)($r['id'] ?? 0) === $roleId) {
                    $targetRoleName = $r['name'];
                    break;
                }
            }

            $actorName = $_SESSION['full_name'] ?? 'Department Director';
            $actorRole = $_SESSION['role_description'] ?? $_SESSION['role'] ?? 'Department Director';

            $logModel->log("Updated permissions for role: {$targetRoleName}", [
                'user_name' => $actorName,
                'role'      => $actorRole,
                'module'    => 'User Management',
                'details'   => "Updated permission matrix for role '{$targetRoleName}' (ID: {$roleId})"
            ]);

            $response = ['success' => true, 'message' => 'Permissions saved!'];
            break;

        // ==========================================================
        // GET ROLE PERMISSIONS — Fetch permissions for a role (AJAX)
        // ==========================================================
        case 'get_role_permissions':
            $roleId = (int) ($_GET['role_id'] ?? $_POST['role_id'] ?? 0);
            if (!$roleId) {
                $response = ['success' => false, 'message' => 'Role ID is required.'];
                break;
            }

            $permissions = $roleModel->getPermissionsForRole($roleId);

            // Group by module & filter sections for non-admin Department Heads
            $grouped = [];
            $resolver = getDepartmentResolver();

            foreach ($permissions as $perm) {
                $module = $perm['module'] ?? 'Other';

                if (!$isSystemAdmin && !empty($userDept)) {
                    $modLower = strtolower(trim($module));
                    $isMainControls = ($modLower === 'main controls');
                    $isSystemManagement = ($modLower === 'system management');
                    $isOwnDept = ($resolver->normalizeDepartmentName($module) === $resolver->normalizeDepartmentName($userDept));

                    if (!$isMainControls && !$isSystemManagement && !$isOwnDept) {
                        continue;
                    }
                }

                if (!isset($grouped[$module])) {
                    $grouped[$module] = [];
                }
                $grouped[$module][] = $perm;
            }

            $response = ['success' => true, 'data' => $grouped];
            break;


        // ==========================================================
        // GET ALL DATA — Return users, roles, and logs for live refresh
        // ==========================================================
        case 'get_all_data':
            $allUsers = $employeeModel->all(['order' => 'created_at.desc']);
            $allRoles = $roleModel->all();
            $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['role'] ?? '');
            $userRole     = trim($_SESSION['role'] ?? '');
            $isDeptHead   = (bool) preg_match('/director|coordinator|lead|health center director|sanitation director|immunization coordinator|surveillance coordinator|wastewater lead/i', $userRoleDesc . ' ' . $userRole);

            if ($isSystemAdmin) {
                $logs = $logModel->all(['limit' => 20, 'order' => 'created_at.desc']);
            } elseif ($isDeptHead && !empty($userDept)) {
                $logs = $logModel->all(['limit' => 20, 'order' => 'created_at.desc', 'department' => $userDept]);
            } else {
                $logs = [];
            }

            if (!$isSystemAdmin && !empty($userDept)) {
                $users = getDepartmentResolver()->filterUsersForDepartment($allUsers, $userDept);
                $roles = getDepartmentResolver()->filterRolesForDepartment($allRoles, $userDept);
            } else {
                $users = $allUsers;
                $roles = $allRoles;
            }

            $response = [
                'success' => true,
                'data' => [
                    'users' => $users,
                    'roles' => $roles,
                    'logs'  => $logs,
                ]
            ];
            break;

        // ==========================================================
        // CLEAR LOGS — Delete all activity logs
        // ==========================================================
        case 'clear_logs':
            if (!$isSystemAdmin) {
                $response = ['success' => false, 'message' => 'Access Denied: Only System Administrators can clear activity logs.'];
                break;
            }
            $logModel->clearAll();
            $response = ['success' => true, 'message' => 'Activity logs cleared.'];
            break;

        default:
            $response = ['success' => false, 'message' => "Unknown action: {$action}"];
            break;
    }

} catch (Throwable $e) {
    error_log('user_management_api error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
exit;
