<?php
// scratch/test_task5_functional.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/Models/SurveillanceCase.php';

use App\Constants\Permissions;
use App\Services\PermissionService;

echo "==================================================================\n";
echo "  FUNCTIONAL BUSINESS LOGIC TEST — TASK 5 (SURVEILLANCE LEAD)\n";
echo "==================================================================\n\n";

$pass = 0;
$fail = 0;

// Test 1: SurveillanceCase generateCaseCode method exists
echo "Test 1: SurveillanceCase monotonic sequential code generator exists...\n";
$caseModel = new SurveillanceCase();
if (method_exists($caseModel, 'generateCaseCode')) {
    echo "  [PASS] SurveillanceCase has generateCaseCode method\n";
    $pass++;
} else {
    echo "  [FAIL] SurveillanceCase missing generateCaseCode\n";
    $fail++;
}

// Test 2: generateCaseCode high-water mark logic prevents collisions
echo "Test 2: generateCaseCode follows high-water mark sequence...\n";
class MockCaseModelWithCases extends SurveillanceCase {
    public function all(array $options = []): array {
        return [
            ['case_code' => 'CS-2026-001'],
            ['case_code' => 'CS-2026-002'],
            ['case_code' => 'CS-2026-008'], // Gap: 3..7 deleted
        ];
    }
}
$mockModel = new MockCaseModelWithCases();
$nextCode = $mockModel->generateCaseCode('2026');
if ($nextCode === 'CS-2026-009') {
    echo "  [PASS] High-water mark correctly generated next sequential code: {$nextCode} (no collision with deleted records)\n";
    $pass++;
} else {
    echo "  [FAIL] Expected CS-2026-009, got {$nextCode}\n";
    $fail++;
}

// Test 3: case_reports.php has department guard before AJAX handler
echo "Test 3: case_reports.php enforces department guard prior to POST handler...\n";
$caseReportsContent = file_get_contents(__DIR__ . '/../modules/surveillence/case_reports.php');
$guardPos = strpos($caseReportsContent, "requireDepartmentAccess('health surveillance');");
$ajaxPos  = strpos($caseReportsContent, "if (\$_SERVER['REQUEST_METHOD'] === 'POST'");

if ($guardPos !== false && $ajaxPos !== false && $guardPos < $ajaxPos) {
    echo "  [PASS] requireDepartmentAccess executes BEFORE AJAX POST handler\n";
    $pass++;
} else {
    echo "  [FAIL] Department access check is not positioned before AJAX handler\n";
    $fail++;
}

// Test 4: Surveillance Coordinator / Lead role permissions
echo "Test 4: Surveillance Coordinator / Lead role granted permissions...\n";
$_SESSION['user_id'] = 555;
$_SESSION['employee_id'] = 555;
$_SESSION['user_role'] = 'Surveillance Coordinator';
$_SESSION['role'] = 'Surveillance Coordinator';
$_SESSION['role_description'] = 'Surveillance Coordinator';
$_SESSION['department'] = 'Health Surveillance';
$_SESSION['user'] = [
    'id' => 555,
    'employee_id' => 555,
    'role' => 'Surveillance Coordinator',
    'role_description' => 'Surveillance Coordinator',
    'department' => 'Health Surveillance'
];
unset($_SESSION['granted_permission_slugs_key']);
unset($_SESSION['granted_permission_slugs']);

$permService = PermissionService::getInstance();
$granted = $permService->getGrantedPermissions();

$requiredSurveillanceSlugs = [
    'dashboard.surveillance',
    'surveillance.view',
    'surveillance.create',
    'surveillance.edit',
    'surveillance.manage'
];

$missing = array_diff($requiredSurveillanceSlugs, $granted);
if (empty($missing)) {
    echo "  [PASS] Surveillance Coordinator has all core operational permissions\n";
    $pass++;
} else {
    echo "  [FAIL] Surveillance Coordinator missing: " . implode(', ', $missing) . "\n";
    $fail++;
}

echo "\n==================================================================\n";
echo "TASK 5 TEST RESULTS: {$pass} Passed, {$fail} Failed\n";
echo "==================================================================\n";
