<?php
// logout.php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/app/Models/ActivityLog.php';

$isExpired = isset($_GET['session_expired']) || isset($_GET['expired']);

// Log logout event BEFORE session is destroyed
try {
    if (!empty($_SESSION['logged_in'])) {
        $logModel = new ActivityLog();
        $logModel->log($isExpired ? "Session expired due to inactivity" : "User logged out", [
            'user_id'   => $_SESSION['user_id']   ?? null,
            'user_name' => $_SESSION['full_name']  ?? 'Unknown',
            'role'      => $_SESSION['role_description'] ?? $_SESSION['role'] ?? 'Employee',
            'module'    => 'Authentication',
            'details'   => $isExpired ? ("Session timed out for: " . ($_SESSION['employee_id'] ?? '')) : ("Session ended for: " . ($_SESSION['employee_id'] ?? '')),
            'status'    => 'Success',
        ]);
    }
} catch (Throwable $ignored) {}

try {
    if (class_exists('App\Services\RememberMeService')) {
        \App\Services\RememberMeService::clearToken();
    }
} catch (Throwable $ignored) {}

// Clear PHP $_SESSION variables and cookies completely
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear active session cookie
setcookie('civentral_session', '', time() - 42000, '/');
if (session_status() === PHP_SESSION_ACTIVE) {
    @session_destroy();
}

$targetUrl = site_url($isExpired ? 'login.php?session_expired=1' : 'login.php?logout=1');

// Handle AJAX/JSON requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => $targetUrl]);
    exit;
}

if (!headers_sent()) {
    header('Location: ' . $targetUrl);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        try {
            localStorage.setItem('data_masking_enabled', 'true');
            localStorage.removeItem('civentral_last_activity');
            localStorage.removeItem('civentral_session_expired');
        } catch (e) {}
        window.location.href = '<?= $targetUrl; ?>';
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>