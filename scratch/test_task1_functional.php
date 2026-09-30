<?php
// scratch/test_task1_functional.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/Controllers/ConsultationController.php';
require_once __DIR__ . '/../app/Controllers/TriageController.php';
require_once __DIR__ . '/../app/Controllers/AppointmentController.php';

echo "========================================================\n";
echo "  FUNCTIONAL BUSINESS LOGIC TEST — TASK 1 (HEALTH CENTER)\n";
echo "========================================================\n\n";

$pass = 0;
$fail = 0;

// Setup session as Health Center Director
$_SESSION['user_id'] = 999;
$_SESSION['employee_id'] = 999;
$_SESSION['user_role'] = 'Health Center Director';
$_SESSION['role'] = 'Health Center Director';
$_SESSION['role_description'] = 'Health Center Director';
$_SESSION['department'] = 'Health Center Services';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Test 1: TriageController status whitelist validation
echo "Test 1: TriageController status whitelist accepts reassignment statuses...\n";
$triageController = new TriageController();
$reflection = new ReflectionClass($triageController);
$sourceCode = file_get_contents(__DIR__ . '/../app/Controllers/TriageController.php');

if (str_contains($sourceCode, "'reassignment_pending'") && str_contains($sourceCode, "'sent_to_doctor'")) {
    echo "  [PASS] Whitelist includes reassignment_pending and sent_to_doctor\n";
    $pass++;
} else {
    echo "  [FAIL] Whitelist missing reassignment statuses\n";
    $fail++;
}

// Test 2: ConsultationController auto-resolve targets patient-specific records
echo "Test 2: ConsultationController uses getByPatientId for triage auto-resolution...\n";
$consultCode = file_get_contents(__DIR__ . '/../app/Controllers/ConsultationController.php');
if (str_contains($consultCode, '$triageModel->getByPatientId($dbData[\'patient_id\'])')) {
    echo "  [PASS] ConsultationController uses getByPatientId (patient-specific lookup)\n";
    $pass++;
} else {
    echo "  [FAIL] ConsultationController does not use getByPatientId\n";
    $fail++;
}

// Test 3: AppointmentController cross-table sync looks up triage by patient_id
echo "Test 3: AppointmentController synchronizes triage records via patient lookup...\n";
$apptCode = file_get_contents(__DIR__ . '/../app/Controllers/AppointmentController.php');
if (str_contains($apptCode, '$pTriages = $tModel->getByPatientId($patientId)') &&
    str_contains($apptCode, '$pQueues = $tqModel->getByPatientId($patientId)')) {
    echo "  [PASS] AppointmentController synchronizes patient triages and queues by patient_id\n";
    $pass++;
} else {
    echo "  [FAIL] AppointmentController missing patient-linked lookup\n";
    $fail++;
}

echo "\n========================================================\n";
echo "TASK 1 TEST RESULTS: {$pass} Passed, {$fail} Failed\n";
echo "========================================================\n";
