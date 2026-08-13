<?php
// logout.php
session_start();

// Log logout event BEFORE session is destroyed
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/app/Models/ActivityLog.php';
try {
    if (!empty($_SESSION['logged_in'])) {
        $logModel = new ActivityLog();
        $logModel->log("User logged out", [
            'user_id'   => $_SESSION['user_id']   ?? null,
            'user_name' => $_SESSION['full_name']  ?? 'Unknown',
            'role'      => $_SESSION['role_description'] ?? $_SESSION['role'] ?? 'Employee',
            'module'    => 'Authentication',
            'details'   => "Session ended for: " . ($_SESSION['employee_id'] ?? ''),
            'status'    => 'Success',
        ]);
    }
} catch (Throwable $ignored) {}

try {
    if (class_exists('App\Services\RememberMeService')) {
        \App\Services\RememberMeService::clearToken();
    }
} catch (Throwable $ignored) {}

// Clear PHP $_SESSION variables for security, but keep 12h/7d device trust token
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        // Reset data masking to hidden
        localStorage.setItem('data_masking_enabled', 'true');
        // Redirect to login with logout flag so user stays on login page
        window.location.href = '<?= site_url('login.php?logout=1'); ?>';
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>