<?php
// api/scheduler/run.php
// Civentral Health & Sanitation Management Information System
// Web API Endpoint for Scheduled Job Execution & Log Inspection

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Cron-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../Core/Env.php';
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/SchedulerService.php';
require_once __DIR__ . '/../../app/Models/SchedulerLog.php';

// Authentication Check:
// 1. Session Auth (System Admin or Officer with settings / log permissions)
// 2. CRON_SECRET token via Bearer header or query parameter
$isAdmin = false;
if (isset($_SESSION['user_id']) || isset($_SESSION['user']['id'])) {
    $role = $_SESSION['role'] ?? ($_SESSION['role_description'] ?? '');
    if (stripos($role, 'Admin') !== false || stripos($role, 'System') !== false || stripos($role, 'Officer') !== false || stripos($role, 'Director') !== false) {
        $isAdmin = true;
    }
}

$expectedSecret = Env::get('CRON_SECRET') ?: 'civentral_health_cron_secret_2026';
$providedSecret = $_GET['secret'] ?? ($_POST['secret'] ?? '');

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['HTTP_X_CRON_SECRET'] ?? '');
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $providedSecret = trim($matches[1]);
}

$isSecretValid = !empty($providedSecret) && hash_equals($expectedSecret, $providedSecret);

if (!$isAdmin && !$isSecretValid) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access Denied: Administrative session or valid CRON_SECRET required.'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$service = new SchedulerService();
$logModel = new SchedulerLog();

// ─── GET: Fetch stats and recent execution logs ────────────────────
if ($method === 'GET' && isset($_GET['stats'])) {
    $stats = $logModel->getStats();
    $recent = $logModel->all(['limit' => (int)($_GET['limit'] ?? 50)]);
    echo json_encode([
        'success' => true,
        'stats'   => $stats,
        'logs'    => $recent
    ]);
    exit;
}

// ─── POST / GET: Execute scheduled jobs ───────────────────────────
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true) ?? $_POST;
$jobKey = trim($_GET['job'] ?? ($input['job'] ?? 'all'));

$triggerSource = $isAdmin 
    ? ('manual:' . ($_SESSION['username'] ?? 'admin'))
    : 'cron_webhook';

if ($jobKey === 'all') {
    $result = $service->runAll($triggerSource);
} else {
    $result = $service->runJob($jobKey, $triggerSource);
}

$statusCode = ($result['success'] ?? false) || (($result['status'] ?? '') === 'success') ? 200 : 500;
http_response_code($statusCode);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
