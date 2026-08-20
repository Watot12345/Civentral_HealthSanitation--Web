<?php
// modules/surveillence/api/clinical_bridge.php
// REST API endpoint for the Clinical Surveillance Bridge & Barangay Signals

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../app/services/ClinicalSurveillanceService.php';

$action = trim($_GET['action'] ?? ($_POST['action'] ?? ''));
$service = new ClinicalSurveillanceService();

switch ($action) {
    case 'check_barangay_status':
        $barangay = trim($_GET['barangay'] ?? ($_POST['barangay'] ?? ''));
        if (empty($barangay)) {
            echo json_encode(['success' => false, 'message' => 'Barangay parameter is required.']);
            exit;
        }
        $signals = $service->getBarangayActiveSignals($barangay);
        echo json_encode(['success' => true, 'data' => $signals]);
        exit;

    case 'detect_disease':
        $diagnosis = trim($_POST['diagnosis'] ?? ($_GET['diagnosis'] ?? ''));
        $symptoms  = trim($_POST['symptoms'] ?? ($_GET['symptoms'] ?? ''));
        $icdCode   = trim($_POST['icd_code'] ?? ($_GET['icd_code'] ?? ''));
        $match = $service->detectDisease($diagnosis, $symptoms, [], $icdCode);
        echo json_encode([
            'success' => true,
            'matched' => $match !== null,
            'data'    => $match
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid or unspecified action.']);
        exit;
}
