<?php
// modules/surveillence/api/contacts.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../app/Middleware/AuthorizationMiddleware.php';
require_once __DIR__ . '/../../../app/Models/SurveillanceContact.php';
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
$contactModel = new SurveillanceContact();
$logModel = new ActivityLog();

try {
    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? '');
            $age = (int) ($_POST['age'] ?? 0);
            $gender = trim($_POST['gender'] ?? 'Unknown');
            $relationship = trim($_POST['relationship'] ?? 'Relative');
            $barangay = trim($_POST['barangay'] ?? '');
            $exposureType = trim($_POST['exposure_type'] ?? 'Direct Contact');
            $lastContactDate = trim($_POST['last_contact_date'] ?? date('Y-m-d'));
            $indexCaseId = (int) ($_POST['index_case_id'] ?? 1);
            $riskLevel = trim($_POST['risk_level'] ?? 'Medium');

            if (empty($name) || empty($barangay)) {
                echo json_encode(['success' => false, 'message' => 'Contact name and barangay are required.']);
                exit;
            }

            $created = $contactModel->create([
                'index_case_id' => $indexCaseId,
                'name' => $name,
                'age' => $age,
                'gender' => $gender,
                'relationship' => $relationship,
                'barangay' => $barangay,
                'exposure_type' => $exposureType,
                'last_contact_date' => $lastContactDate,
                'monitoring_status' => 'Under Monitoring',
                'quarantine_status' => 'Quarantined',
                'quarantine_start' => date('Y-m-d'),
                'quarantine_end' => date('Y-m-d', strtotime('+14 days')),
                'risk_level' => $riskLevel
            ]);

            $logModel->log("Registered new contact: {$name} for Index Case #{$indexCaseId}", [
                'module' => 'Health Surveillance',
                'details' => "Risk Level: {$riskLevel}, Barangay: {$barangay}"
            ]);

            echo json_encode(['success' => true, 'message' => 'Contact registered successfully!', 'data' => $created]);
            break;

        case 'update_monitoring':
            $id = (int) ($_POST['id'] ?? 0);
            $monitoringStatus = trim($_POST['monitoring_status'] ?? 'Under Monitoring');
            $quarantineStatus = trim($_POST['quarantine_status'] ?? 'Quarantined');
            $symptoms = trim($_POST['symptoms'] ?? 'None');

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Contact ID is required.']);
                exit;
            }

            $updated = $contactModel->updateById($id, [
                'monitoring_status' => $monitoringStatus,
                'quarantine_status' => $quarantineStatus,
                'symptoms' => $symptoms
            ]);

            $logModel->log("Updated contact monitoring status for Contact #{$id}", [
                'module' => 'Health Surveillance',
                'details' => "Status: {$monitoringStatus}, Quarantine: {$quarantineStatus}"
            ]);

            echo json_encode(['success' => true, 'message' => 'Contact monitoring updated!', 'data' => $updated]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
            break;
    }
} catch (Throwable $e) {
    error_log("Surveillance Contacts API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error processing request: ' . $e->getMessage()]);
}
