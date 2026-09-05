<?php
// api/compliance_actions.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Models/Violation.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $violationModel = new Violation();

    if ($method === 'GET') {
        $violations = $violationModel->all();
        Response::success('Violations retrieved successfully', $violations);
        exit;
    }

    if ($method !== 'POST') {
        Response::error('Method not allowed', 405);
        exit;
    }

    $action       = $_POST['action'] ?? 'assign_action';
    $violationId  = $_POST['violation_id'] ?? $_POST['inspection_id'] ?? null;
    $assignedTo   = trim($_POST['assigned_to'] ?? '');
    $dueDate      = $_POST['due_date'] ?? null;
    $notes        = trim($_POST['notes'] ?? $_POST['corrective_action'] ?? '');

    if (!$violationId) {
        Response::error('Violation ID or Inspection ID is required', 400);
        exit;
    }

    if (empty($assignedTo) || empty($dueDate)) {
        Response::error('Assigned to and Due Date are required fields', 422);
        exit;
    }

    $proofUrl = null;
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['proof'];
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        if (!in_array($mime, $allowedMimes, true)) {
            Response::error('Invalid proof document MIME type. Only JPG, PNG, WEBP, and PDF files are permitted.', 415);
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $uploadDir = __DIR__ . '/../assets/uploads/compliance_proofs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFilename = 'proof_v' . preg_replace('/[^a-zA-Z0-9]/', '', $violationId) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath  = $uploadDir . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            Response::error('Failed to save uploaded proof file.', 500);
            exit;
        }

        $proofUrl = '/assets/uploads/compliance_proofs/' . $newFilename;
    }

    // Connect & Update / Create in Database violations table
    $updateData = [
        'status'            => 'in_progress',
        'corrective_action' => !empty($notes) ? "Assigned to {$assignedTo}: {$notes}" : "Assigned to {$assignedTo} (Due: {$dueDate})",
        'corrected_date'    => $dueDate
    ];
    if ($proofUrl) {
        $updateData['proof_document'] = $proofUrl;
    }

    $existing = $violationModel->find($violationId);
    if ($existing) {
        $result = $violationModel->updateById($violationId, $updateData);
    } else {
        // Look up by inspection_id
        $byInsp = $violationModel->findByInspectionId($violationId);
        if (!empty($byInsp)) {
            $result = $violationModel->updateById($byInsp[0]['id'], $updateData);
        } else {
            // Create violation record linked to inspection / permit
            $insertData = array_merge([
                'permit_id'      => 1,
                'inspection_id'  => (int)$violationId,
                'violation_type' => 'Sanitation Non-Compliance',
                'description'    => 'Corrective action assigned for flagged sanitation finding',
                'severity'       => 'medium'
            ], $updateData);
            $result = $violationModel->create($insertData);
        }
    }

    Response::success('Corrective action and proof document recorded successfully', [
        'violation_id' => $violationId,
        'assigned_to'  => $assignedTo,
        'due_date'     => $dueDate,
        'status'       => 'in_progress',
        'proof_url'    => $proofUrl
    ], 200);

} catch (Exception $e) {
    error_log('Compliance Actions API Error: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}
