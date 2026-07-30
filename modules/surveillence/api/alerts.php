<?php
// modules/surveillence/api/alerts.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../app/Middleware/AuthorizationMiddleware.php';
require_once __DIR__ . '/../../../app/Models/SurveillanceAlert.php';
require_once __DIR__ . '/../../../app/Models/ActivityLog.php';

// Auth Guard
if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
    exit;
}

try {
    requireDepartmentAccess('health surveillance');
} catch (Throwable $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied: Department access restricted.']);
    exit;
}

// CSRF Guard
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!empty($_SESSION['csrf_token']) && !hash_equals($_SESSION['csrf_token'], $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security Error: Invalid CSRF Token.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$alertModel = new SurveillanceAlert();
$logModel = new ActivityLog();

try {
    switch ($action) {
        case 'update_status':
            $id = (int) ($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? 'Resolved');
            $responseActions = trim($_POST['response_actions'] ?? '');

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Alert ID is required.']);
                exit;
            }

            $updateData = ['status' => $status];
            if (!empty($responseActions)) {
                $updateData['response_actions'] = $responseActions;
            }

            $updated = $alertModel->updateById($id, $updateData);

            $logModel->log("Updated Alert #{$id} status to {$status}", [
                'module' => 'Health Surveillance',
                'details' => "Status: {$status}, Actions: " . ($responseActions ?: 'None logged')
            ]);

            echo json_encode(['success' => true, 'message' => "Alert status updated to {$status}.", 'data' => $updated]);
            break;

        case 'escalate':
            $id = (int) ($_POST['id'] ?? 0);
            $alert = $alertModel->find($id);
            if (!$alert) {
                echo json_encode(['success' => false, 'message' => 'Alert not found.']);
                exit;
            }

            $newLevel = ((int) ($alert['escalation_level'] ?? 1)) + 1;
            $updated = $alertModel->updateById($id, [
                'escalation_level' => $newLevel,
                'severity' => 'Critical'
            ]);

            $logModel->log("Escalated Alert #{$id} to Level {$newLevel}", [
                'module' => 'Health Surveillance',
                'details' => "Escalated to Level {$newLevel} (Critical)"
            ]);

            echo json_encode(['success' => true, 'message' => "Alert escalated to Level {$newLevel}.", 'data' => $updated]);
            break;

        case 'emergency_response':
            $id = (int) ($_POST['id'] ?? 0);
            $updated = $alertModel->updateById($id, [
                'status' => 'Emergency Response Active',
                'escalation_level' => 3,
                'severity' => 'Critical'
            ]);

            $logModel->log("Activated Emergency Response for Alert #{$id}", [
                'module' => 'Health Surveillance',
                'details' => 'Emergency Outbreak Response Command Activated'
            ]);

            echo json_encode(['success' => true, 'message' => 'Emergency response activated!', 'data' => $updated]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
            break;
    }
} catch (Throwable $e) {
    error_log("Surveillance Alerts API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error processing request: ' . $e->getMessage()]);
}
