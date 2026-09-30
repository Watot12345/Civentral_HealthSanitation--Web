<?php
// scratch/test_live_supabase_dynamic.php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Constants/Permissions.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/Models/Permit.php';
require_once __DIR__ . '/../app/Models/Patient.php';
require_once __DIR__ . '/../app/Models/SurveillanceCase.php';
require_once __DIR__ . '/../app/Models/Child.php';
require_once __DIR__ . '/../app/Models/Role.php';
require_once __DIR__ . '/../app/Models/Employee.php';

use App\Constants\Permissions;
use App\Services\PermissionService;
use App\Services\DepartmentResolver;

$passed = 0;
$failed = 0;

function assert_live(bool $cond, string $name, string $extra = ''): void {
    global $passed, $failed;
    if ($cond) {
        $passed++;
        echo "  [\033[32mPASS\033[0m] {$name}\n";
    } else {
        $failed++;
        echo "  [\033[31mFAIL\033[0m] {$name}" . ($extra ? " - {$extra}" : "") . "\n";
    }
}

echo "====================================================================\n";
echo "  LIVE SUPABASE DYNAMIC DATA VERIFICATION TEST\n";
echo "====================================================================\n\n";

$db = Database::getInstance();

// 1. DYNAMIC ROLES IN DATABASE
echo "1. VERIFYING DYNAMIC ROLES FROM SUPABASE...\n";
$roleModel = new Role();
$liveRoles = $roleModel->all();
assert_live(!empty($liveRoles), "Fetched live roles from Supabase", "Count: " . count($liveRoles));
echo "   -> Found " . count($liveRoles) . " live roles in Supabase database:\n";
foreach ($liveRoles as $r) {
    echo "      * Role #{$r['id']}: " . ($r['name'] ?? 'Unnamed') . "\n";
}

// 2. DYNAMIC EMPLOYEES IN DATABASE
echo "\n2. VERIFYING DYNAMIC EMPLOYEES FROM SUPABASE...\n";
$empModel = new Employee();
$liveEmployees = $empModel->all();
assert_live(!empty($liveEmployees), "Fetched live employees from Supabase", "Count: " . count($liveEmployees));
echo "   -> Found " . count($liveEmployees) . " live employees in Supabase.\n";

// Test PermissionService dynamic resolution using REAL LIVE employee IDs from database!
$permService = PermissionService::getInstance();
foreach (array_slice($liveEmployees, 0, 5) as $emp) {
    $empId = (int)$emp['id'];
    $roleName = $emp['role_description'] ?? ($emp['role'] ?? 'Unknown');
    $scope = $permService->getUserScope($empId);
    assert_live(
        is_array($scope) && isset($scope['is_admin']),
        "Resolved live scope for Employee #{$empId} ({$roleName}) -> Dept: " . ($scope['department'] ?? 'null') . ", Admin: " . ($scope['is_admin'] ? 'YES' : 'NO')
    );
}

// 3. DYNAMIC PATIENTS IN DATABASE
echo "\n3. VERIFYING DYNAMIC PATIENTS FROM SUPABASE...\n";
$patientModel = new Patient();
$livePatients = $patientModel->all();
assert_live(!empty($livePatients), "Fetched live patients from Supabase", "Count: " . count($livePatients));
echo "   -> Found " . count($livePatients) . " live patients in Supabase database:\n";
foreach ($livePatients as $pat) {
    $pName = trim(($pat['first_name'] ?? '') . ' ' . ($pat['last_name'] ?? ''));
    $pCode = $pat['patient_id'] ?? ('#' . $pat['id']);
    echo "      * Patient {$pCode}: {$pName}\n";
}

// 4. DYNAMIC SANITATION PERMITS IN DATABASE
echo "\n4. VERIFYING DYNAMIC SANITATION PERMITS FROM SUPABASE...\n";
$permitModel = new Permit();
$livePermits = $permitModel->all();
assert_live(!empty($livePermits), "Fetched live permits from Supabase", "Count: " . count($livePermits));
echo "   -> Found " . count($livePermits) . " live permits in Supabase database:\n";
foreach ($livePermits as $pmt) {
    $pCode = $pmt['permit_id'] ?? ('#' . $pmt['id']);
    $applicant = $pmt['applicant'] ?? ($pmt['business_name'] ?? 'Establishment');
    $status = $pmt['status'] ?? 'pending';
    echo "      * Permit {$pCode}: {$applicant} [Status: {$status}, Fee: ₱" . number_format((float)($pmt['fee'] ?? 0), 2) . "]\n";
}

// 5. DYNAMIC SURVEILLANCE CASES IN DATABASE
echo "\n5. VERIFYING DYNAMIC SURVEILLANCE CASES & SEQUENCING...\n";
$caseModel = new SurveillanceCase();
$liveCases = $caseModel->all();
assert_live(!empty($liveCases), "Fetched live surveillance cases from Supabase", "Count: " . count($liveCases));
echo "   -> Found " . count($liveCases) . " live surveillance cases in Supabase database.\n";

// Test monotonic case code generation against the REAL 23 cases in database!
$nextCode = $caseModel->generateCaseCode();
echo "   -> Next calculated sequential case code based on real live data: {$nextCode}\n";
assert_live(preg_match('/^CS-\d{4}-\d{3}$/', $nextCode) === 1, "Next case code matches CS-YYYY-XXX format: {$nextCode}");

// Verify that nextCode is strictly greater than all existing codes in database
$prefix = "CS-" . date('Y') . "-";
$maxExisting = 0;
foreach ($liveCases as $c) {
    $cCode = $c['case_code'] ?? '';
    if (str_starts_with($cCode, $prefix)) {
        $num = (int)substr($cCode, strlen($prefix));
        if ($num > $maxExisting) $maxExisting = $num;
    }
}
$nextNum = (int)substr($nextCode, strlen($prefix));
assert_live($nextNum === $maxExisting + 1, "Next code number ({$nextNum}) is exactly max existing ({$maxExisting}) + 1 without collision!");

// 6. DYNAMIC CHILDREN IN DATABASE
echo "\n6. VERIFYING DYNAMIC CHILDREN FROM SUPABASE...\n";
$childModel = new Child();
$liveChildren = $childModel->all();
assert_live(!empty($liveChildren), "Fetched live children records from Supabase", "Count: " . count($liveChildren));
if (!empty($liveChildren)) {
    $firstChild = $liveChildren[0];
    $childWithVacc = $childModel->findWithVaccinations((int)$firstChild['id']);
    assert_live(is_array($childWithVacc) && isset($childWithVacc['vaccinations']), "findWithVaccinations dynamically pulled live record and vaccinations array from Supabase for Child #{$firstChild['id']}");
}

echo "\n====================================================================\n";
echo "LIVE DATABASE VERIFICATION SUMMARY\n";
echo "====================================================================\n";
echo "Total Live DB Tests Passed: {$passed}\n";
echo "Total Live DB Tests Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n\033[32mALL TESTS ARE 100% CONNECTED AND VALIDATED AGAINST YOUR LIVE SUPABASE DATABASE!\033[0m\n";
    exit(0);
} else {
    exit(1);
}
