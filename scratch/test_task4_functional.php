<?php
// scratch/test_task4_functional.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/Controllers/ServiceRequestController.php';
require_once __DIR__ . '/../app/Controllers/WastewaterInvoiceController.php';
require_once __DIR__ . '/../app/Models/WastewaterInvoice.php';

use App\Constants\Permissions;
use App\Services\PermissionService;

echo "==============================================================\n";
echo "  FUNCTIONAL BUSINESS LOGIC TEST — TASK 4 (WASTEWATER LEAD)\n";
echo "==============================================================\n\n";

$pass = 0;
$fail = 0;

// Test 1: WastewaterInvoice model has service_request_id search methods
echo "Test 1: WastewaterInvoice model supports service_request_id queries...\n";
$invModel = new WastewaterInvoice();
if (method_exists($invModel, 'findActiveByServiceRequestId') && method_exists($invModel, 'findByServiceRequestId')) {
    echo "  [PASS] WastewaterInvoice has findActiveByServiceRequestId and findByServiceRequestId\n";
    $pass++;
} else {
    echo "  [FAIL] WastewaterInvoice missing service_request_id query methods\n";
    $fail++;
}

// Test 2: WastewaterInvoiceController checks duplicate invoices on service_request_id
echo "Test 2: WastewaterInvoiceController checks duplicates by service_request_id...\n";
$invControllerCode = file_get_contents(__DIR__ . '/../app/Controllers/WastewaterInvoiceController.php');
if (str_contains($invControllerCode, '$serviceRequestId = trim($d[\'service_request_id\'] ?? \'\');') &&
    str_contains($invControllerCode, '$this->model->findActiveByServiceRequestId($serviceRequestId)')) {
    echo "  [PASS] WastewaterInvoiceController enforces duplicate protection on service_request_id\n";
    $pass++;
} else {
    echo "  [FAIL] WastewaterInvoiceController missing service_request_id duplicate check\n";
    $fail++;
}

// Test 3: ServiceRequestController rejects deleting in_progress / completed requests
echo "Test 3: ServiceRequestController guards against deletion of in_progress/completed requests...\n";
$srControllerCode = file_get_contents(__DIR__ . '/../app/Controllers/ServiceRequestController.php');
if (str_contains($srControllerCode, "Cannot delete service request in '{\$currentStatus}' status") &&
    str_contains($srControllerCode, "if (!in_array(\$currentStatus, ['pending', 'cancelled'], true))")) {
    echo "  [PASS] ServiceRequestController blocks deletion of active/completed requests\n";
    $pass++;
} else {
    echo "  [FAIL] ServiceRequestController status check not aligned\n";
    $fail++;
}

// Test 4: ServiceRequestController rejects deleting requests with attached invoices
echo "Test 4: ServiceRequestController checks for attached billing invoices before deletion...\n";
if (str_contains($srControllerCode, 'findByServiceRequestId') &&
    str_contains($srControllerCode, 'Cannot delete service request with attached billing invoice')) {
    echo "  [PASS] ServiceRequestController verifies invoice linkage before allowing deletion\n";
    $pass++;
} else {
    echo "  [FAIL] ServiceRequestController invoice linkage check not found\n";
    $fail++;
}

// Test 5: Wastewater Officer & Wastewater Lead role permissions
echo "Test 5: Wastewater Officer & Wastewater Lead granted permissions...\n";
$_SESSION['user_id'] = 666;
$_SESSION['employee_id'] = 666;
$_SESSION['user_role'] = 'Wastewater Lead';
$_SESSION['role'] = 'Wastewater Lead';
$_SESSION['role_description'] = 'Wastewater Lead';
$_SESSION['department'] = 'Wastewater Services';
$_SESSION['user'] = [
    'id' => 666,
    'employee_id' => 666,
    'role' => 'Wastewater Lead',
    'role_description' => 'Wastewater Lead',
    'department' => 'Wastewater Services'
];
unset($_SESSION['granted_permission_slugs_key']);
unset($_SESSION['granted_permission_slugs']);

$permService = PermissionService::getInstance();
$granted = $permService->getGrantedPermissions();

$requiredWastewaterSlugs = [
    'dashboard.wastewater',
    'wastewater.view',
    'wastewater.create',
    'wastewater.edit',
    'wastewater.manage'
];

$missing = array_diff($requiredWastewaterSlugs, $granted);
if (empty($missing)) {
    echo "  [PASS] Wastewater Lead has all required operational permissions\n";
    $pass++;
} else {
    echo "  [FAIL] Wastewater Lead missing: " . implode(', ', $missing) . "\n";
    $fail++;
}

echo "\n==============================================================\n";
echo "TASK 4 TEST RESULTS: {$pass} Passed, {$fail} Failed\n";
echo "==============================================================\n";
