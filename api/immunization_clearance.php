<?php
// api/immunization_clearance.php
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Response.php';

if (PHP_SAPI !== 'cli') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    header('Content-Type: application/json');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

try {
    $patientId = $_GET['patient_id'] ?? $_GET['id'] ?? null;
    if (!$patientId || !is_numeric($patientId)) {
        Response::error('Patient ID is required', 400);
    }

    $db = Database::getInstance();
    $pId = (int)$patientId;

    // 1. Check pending immunization referrals from Doctor Consultations
    $referrals = [];
    try {
        $referrals = $db->select('immunization_referrals', [
            'patient_id' => $pId,
            'status' => 'pending'
        ], ['order' => 'created_at.desc', 'limit' => 1]);
    } catch (Throwable $e) {
        $referrals = [];
    }

    if (!empty($referrals) && is_array($referrals) && isset($referrals[0])) {
        $ref = $referrals[0];
        Response::success('Patient has active Doctor clearance for immunization.', [
            'cleared' => true,
            'status' => 'CLEARED',
            'type' => 'DOCTOR_REFERRAL',
            'referral_id' => $ref['id'],
            'patient_name' => $ref['patient_name'] ?? 'Patient',
            'vaccine_requested' => $ref['vaccine_requested'] ?? 'Vaccine',
            'referred_by' => $ref['referred_by'] ?? 'Doctor',
            'notes' => $ref['notes'] ?? '',
            'assessed_at' => $ref['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }

    // 2. Check pre-vaccination assessment records (triage / immunization_assessments)
    $assessments = [];
    try {
        $assessments = $db->select('immunization_assessments', [
            'patient_id' => $pId
        ], ['order' => 'created_at.desc', 'limit' => 1]);
    } catch (Throwable $e) {
        $assessments = [];
    }

    if (!empty($assessments) && is_array($assessments) && isset($assessments[0])) {
        $ass = $assessments[0];
        $result = strtoupper((string)($ass['assessment_result'] ?? 'ELIGIBLE'));
        if ($result === 'ELIGIBLE' || $result === 'CLEARED' || $result === 'FIT') {
            Response::success('Patient cleared by Health Center assessment.', [
                'cleared' => true,
                'status' => 'CLEARED',
                'type' => 'HEALTH_CENTER_ASSESSMENT',
                'assessment_id' => $ass['id'],
                'health_status' => $ass['health_status'] ?? 'Healthy',
                'assessed_by' => $ass['assessed_by'] ?? 'Health Center Staff',
                'vaccine_requested' => $ass['vaccine_due'] ?? 'Vaccine',
                'notes' => $ass['notes'] ?? '',
                'assessed_at' => $ass['created_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
    }

    // 3. Fallback: Check standard Triage queue
    $triage = [];
    try {
        $triage = $db->select('triage_queue', [
            'patient_id' => $pId
        ], ['order' => 'created_at.desc', 'limit' => 1]);
    } catch (Throwable $e) {
        $triage = [];
    }

    if (!empty($triage) && is_array($triage) && isset($triage[0])) {
        $t = $triage[0];
        $temp = (float)($t['temperature'] ?? $t['temp'] ?? 36.5);
        if ($temp < 38.0) { // No high fever
            Response::success('Patient cleared via Health Center Triage screening.', [
                'cleared' => true,
                'status' => 'CLEARED',
                'type' => 'TRIAGE_PRE_SCREENING',
                'triage_id' => $t['id'],
                'temperature' => $temp,
                'weight' => $t['weight'] ?? null,
                'assessed_at' => $t['created_at'] ?? date('Y-m-d H:i:s')
            ]);
        } else {
            Response::success('High temperature detected at Triage. Require Doctor consultation before vaccination.', [
                'cleared' => false,
                'status' => 'DEFERRED',
                'reason' => 'FEVER_DETECTED',
                'temperature' => $temp
            ]);
        }
    }

    // Default: Not assessed
    Response::success('Patient must complete Health Center assessment or Triage screening before receiving a vaccine.', [
        'cleared' => false,
        'status' => 'NOT_ASSESSED',
        'reason' => 'NO_ASSESSMENT_FOUND'
    ]);

} catch (Throwable $e) {
    error_log('Immunization Clearance API Error: ' . $e->getMessage());
    Response::error('Failed to verify patient clearance: ' . $e->getMessage(), 500);
}
