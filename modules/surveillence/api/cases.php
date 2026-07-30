<?php
// modules/surveillence/api/cases.php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../app/Middleware/AuthorizationMiddleware.php';
require_once __DIR__ . '/../../../app/Models/SurveillanceCase.php';
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
$caseModel = new SurveillanceCase();
$logModel = new ActivityLog();

try {
    switch ($action) {
        case 'create':
            $disease = trim($_POST['disease'] ?? '');
            $patientName = trim($_POST['patient_name'] ?? '');
            $age = (int) ($_POST['age'] ?? 0);
            $gender = trim($_POST['gender'] ?? 'Unknown');
            $address = trim($_POST['address'] ?? '');
            $barangay = trim($_POST['barangay'] ?? '');
            $contact = trim($_POST['contact_number'] ?? '');
            $symptoms = trim($_POST['symptoms'] ?? '');
            $onsetDate = trim($_POST['onset_date'] ?? date('Y-m-d'));
            $facility = trim($_POST['reporting_facility'] ?? 'City Health Office');
            $severity = trim($_POST['severity'] ?? 'Moderate');

            if (empty($disease) || empty($patientName) || empty($barangay)) {
                echo json_encode(['success' => false, 'message' => 'Disease, patient name, and barangay are required.']);
                exit;
            }

            $created = $caseModel->create([
                'disease' => $disease,
                'patient_name' => $patientName,
                'age' => $age,
                'gender' => $gender,
                'address' => $address,
                'barangay' => $barangay,
                'contact_number' => $contact,
                'symptoms' => $symptoms,
                'onset_date' => $onsetDate,
                'reporting_facility' => $facility,
                'status' => 'Suspected',
                'severity' => $severity,
                'reported_by' => $_SESSION['full_name'] ?? 'Surveillance Staff'
            ]);

            $logModel->log("Reported new case: {$patientName} ({$disease}) in {$barangay}", [
                'module' => 'Health Surveillance',
                'details' => "Case Code: " . ($created['case_code'] ?? 'New')
            ]);

            echo json_encode(['success' => true, 'message' => 'Case report submitted successfully!', 'data' => $created]);
            break;

        case 'investigate':
            $id = (int) ($_POST['id'] ?? 0);
            $notes = trim($_POST['investigation_notes'] ?? '');
            $investigator = trim($_POST['investigator_id'] ?? $_SESSION['full_name'] ?? 'Surveillance Officer');

            if (!$id || empty($notes)) {
                echo json_encode(['success' => false, 'message' => 'Case ID and investigation notes are required.']);
                exit;
            }

            $updated = $caseModel->updateById($id, [
                'investigation_notes' => $notes,
                'investigator_id' => $investigator,
                'status' => 'Investigating'
            ]);

            $logModel->log("Updated investigation notes for Case #{$id}", [
                'module' => 'Health Surveillance',
                'details' => "Notes: {$notes}"
            ]);

            echo json_encode(['success' => true, 'message' => 'Investigation notes saved!', 'data' => $updated]);
            break;

        case 'update_status':
            $id = (int) ($_POST['id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');

            if (!$id || !in_array($newStatus, ['Suspected', 'Investigating', 'Confirmed', 'Resolved', 'Dismissed'], true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid Case ID or status parameter.']);
                exit;
            }

            $updated = $caseModel->updateById($id, ['status' => $newStatus]);

            $logModel->log("Updated case status for Case #{$id} to {$newStatus}", [
                'module' => 'Health Surveillance',
                'details' => "Status changed to {$newStatus}"
            ]);

            echo json_encode(['success' => true, 'message' => "Case status updated to {$newStatus}.", 'data' => $updated]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
            break;
    }
} catch (Throwable $e) {
    error_log("Surveillance Cases API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error processing request: ' . $e->getMessage()]);
}
