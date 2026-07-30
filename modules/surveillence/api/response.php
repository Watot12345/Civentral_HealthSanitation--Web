<?php
// modules/surveillence/api/response.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../app/Middleware/AuthorizationMiddleware.php';
require_once __DIR__ . '/../../../app/Models/SurveillanceResponse.php';
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
$responseModel = new SurveillanceResponse();
$logModel = new ActivityLog();

try {
    switch ($action) {
        case 'deploy_team':
            $id = (int) ($_POST['id'] ?? 0);
            $location = trim($_POST['deployed_to'] ?? 'Barangay San Jose');
            $status = trim($_POST['status'] ?? 'Deployed');

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Team ID is required.']);
                exit;
            }

            $updated = $responseModel->updateTeam($id, [
                'status' => $status,
                'deployed_to' => $location,
                'last_deployment' => date('Y-m-d H:i:s')
            ]);

            $logModel->log("Deployed response team #{$id} to {$location}", [
                'module' => 'Health Surveillance',
                'details' => "Status: {$status}, Deployed Location: {$location}"
            ]);

            echo json_encode(['success' => true, 'message' => "Team deployed to {$location}!", 'data' => $updated]);
            break;

        case 'allocate_resource':
            $id = (int) ($_POST['id'] ?? 0);
            $qty = (int) ($_POST['quantity'] ?? 0);

            if (!$id || $qty <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid Resource ID and quantity required.']);
                exit;
            }

            $resources = $responseModel->getResources();
            $targetResource = null;
            foreach ($resources as $r) {
                if ((int) $r['id'] === $id) {
                    $targetResource = $r;
                    break;
                }
            }

            if (!$targetResource) {
                echo json_encode(['success' => false, 'message' => 'Resource not found.']);
                exit;
            }

            $currentQty = (int) ($targetResource['quantity'] ?? 0);
            $newQty = max(0, $currentQty - $qty);
            $newStatus = ($newQty <= (int) ($targetResource['threshold'] ?? 10)) ? 'Low Stock' : 'Sufficient';

            $updated = $responseModel->updateResource($id, [
                'quantity' => $newQty,
                'status' => $newStatus
            ]);

            $logModel->log("Allocated {$qty} units of {$targetResource['name']}", [
                'module' => 'Health Surveillance',
                'details' => "Remaining Quantity: {$newQty} ({$newStatus})"
            ]);

            echo json_encode(['success' => true, 'message' => "Successfully allocated {$qty} units!", 'data' => $updated]);
            break;

        case 'update_intervention':
            $id = (int) ($_POST['id'] ?? 0);
            $progress = (int) ($_POST['progress'] ?? 0);
            $status = trim($_POST['status'] ?? 'In Progress');
            $activities = trim($_POST['activities'] ?? '');

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Intervention ID is required.']);
                exit;
            }

            $updateData = [
                'progress' => min(100, max(0, $progress)),
                'status' => $status
            ];
            if (!empty($activities)) {
                $updateData['activities'] = $activities;
            }

            $updated = $responseModel->updateIntervention($id, $updateData);

            $logModel->log("Updated intervention #{$id} progress to {$progress}%", [
                'module' => 'Health Surveillance',
                'details' => "Status: {$status}, Progress: {$progress}%"
            ]);

            echo json_encode(['success' => true, 'message' => 'Intervention status updated!', 'data' => $updated]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
            break;
    }
} catch (Throwable $e) {
    error_log("Surveillance Response API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error processing request: ' . $e->getMessage()]);
}
