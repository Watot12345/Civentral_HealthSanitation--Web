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
                'details' => "Username: {$username}, Role: {$role}",
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
                $response = ['success' => false, 'message' => 'You cannot delete your own logged-in account.'];
                break;
            }

            if (!$id) {
                $response = ['success' => false, 'message' => 'User ID is required.'];
                break;
            }

            // Get user name for log before deleting
            $user = $employeeModel->find($id);
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

            $result = $employeeModel->toggleStatus($id);
            $newStatus = $result['status'] ?? 'Unknown';

            $logModel->log("Toggled user status to {$newStatus} (ID: {$id})", [
                'module' => 'User Management',
            ]);

            $response = ['success' => true, 'message' => "Status changed to {$newStatus}.", 'data' => $result];
            break;

        // ==========================================================
        // SAVE PERMISSIONS — Sync role permissions
        // ==========================================================
        case 'save_permissions':
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $permissionIds = json_decode($_POST['permission_ids'] ?? '[]', true);

            if (!$roleId) {
                $response = ['success' => false, 'message' => 'Role ID is required.'];
                break;
            }

            $roleModel->syncPermissions($roleId, $permissionIds);
            unset($_SESSION['granted_permission_slugs']);

            // Resolve target role name for clear audit trail
            $targetRoleName = "Role ID #{$roleId}";
            $rolesList = $roleModel->all();
            foreach ($rolesList as $r) {
                if ((int)($r['id'] ?? 0) === $roleId) {
                    $targetRoleName = $r['name'];
                    break;
                }
            }

            $actorName = $_SESSION['full_name'] ?? 'System Administrator';
            $actorRole = $_SESSION['role_description'] ?? $_SESSION['role'] ?? 'System Administrator';

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

            // Group by module
            $grouped = [];
            foreach ($permissions as $perm) {
                $module = $perm['module'] ?? 'Other';
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
            $users = $employeeModel->all(['order' => 'created_at.desc']);
            $roles = $roleModel->all();
            $logs = $logModel->all(['limit' => 20, 'order' => 'created_at.desc']);

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
