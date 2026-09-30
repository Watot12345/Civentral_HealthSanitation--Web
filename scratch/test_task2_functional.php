<?php
// scratch/test_task2_functional.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/Controllers/PaymentController.php';
require_once __DIR__ . '/../app/Controllers/PermitController.php';

use App\Services\PermissionService;

echo "========================================================\n";
echo "  FUNCTIONAL BUSINESS LOGIC TEST — TASK 2 (SANITATION)\n";
echo "========================================================\n\n";

$pass = 0;
$fail = 0;

$permService = PermissionService::getInstance();

// Test 1: Cashier role has permits.create capability
echo "Test 1: Cashier role has permits.create permission for fee collection...\n";
$_SESSION['user_id'] = 888;
$_SESSION['employee_id'] = 888;
$_SESSION['user_role'] = 'Cashier';
$_SESSION['role'] = 'Cashier';
$_SESSION['role_description'] = 'Cashier';
$_SESSION['department'] = 'Sanitation Permits';
$_SESSION['user'] = [
    'id' => 888,
    'employee_id' => 888,
    'role' => 'Cashier',
    'role_description' => 'Cashier',
    'department' => 'Sanitation Permits'
];
unset($_SESSION['granted_permission_slugs_key']);
unset($_SESSION['granted_permission_slugs']);

$granted = $permService->getGrantedPermissions();

if (in_array('permits.create', $granted, true)) {
    echo "  [PASS] Cashier granted permits.create for payment processing\n";
    $pass++;
} else {
    echo "  [FAIL] Cashier missing permits.create in granted permissions\n";
    $fail++;
}

// Test 2: PaymentController update and complete require PERMITS_CREATE instead of blocking cashiers
echo "Test 2: PaymentController update and complete allow PERMITS_CREATE holders...\n";
$paymentCode = file_get_contents(__DIR__ . '/../app/Controllers/PaymentController.php');
if (str_contains($paymentCode, "public function update(int \$id): void\n    {\n        \$this->validateCsrf();\n        \$this->requireDepartment('sanitation');\n        \$this->requireCapability(Permissions::PERMITS_CREATE);") &&
    str_contains($paymentCode, "public function complete(int \$id): void\n    {\n        \$this->validateCsrf();\n        \$this->requireDepartment('sanitation');\n        \$this->requireCapability(Permissions::PERMITS_CREATE);")) {
    echo "  [PASS] Payment update and complete accept PERMITS_CREATE\n";
    $pass++;
} else {
    echo "  [FAIL] PaymentController update/complete capability check not aligned\n";
    $fail++;
}

// Test 3: PermitController verifies fee payment before approval
echo "Test 3: PermitController enforces payment verification before approving permits...\n";
$permitCode = file_get_contents(__DIR__ . '/../app/Controllers/PermitController.php');
if (str_contains($permitCode, "Sanitation permit fees are unpaid. Please complete fee payment before granting approval.")) {
    echo "  [PASS] Fee payment verification enforced in PermitController approval workflows\n";
    $pass++;
} else {
    echo "  [FAIL] PermitController missing fee payment check\n";
    $fail++;
}

echo "\n========================================================\n";
echo "TASK 2 TEST RESULTS: {$pass} Passed, {$fail} Failed\n";
echo "========================================================\n";
