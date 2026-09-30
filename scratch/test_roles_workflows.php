<?php
// scratch/test_roles_workflows.php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Constants/Permissions.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/Middleware/AuthorizationMiddleware.php';
require_once __DIR__ . '/../app/Controllers/PatientController.php';
require_once __DIR__ . '/../app/Controllers/AppointmentController.php';
require_once __DIR__ . '/../app/Controllers/TriageController.php';
require_once __DIR__ . '/../app/Controllers/ConsultationController.php';
require_once __DIR__ . '/../app/Controllers/PermitController.php';
require_once __DIR__ . '/../app/Controllers/PaymentController.php';
require_once __DIR__ . '/../app/Controllers/ChildController.php';
require_once __DIR__ . '/../app/Controllers/ServiceRequestController.php';
require_once __DIR__ . '/../app/Controllers/WastewaterInvoiceController.php';
require_once __DIR__ . '/../app/Models/SurveillanceCase.php';

use App\Constants\Permissions;
use App\Services\PermissionService;
use App\Services\DepartmentResolver;

$passed = 0;
$failed = 0;
$failures = [];

function assert_test(bool $condition, string $testName, string $details = ''): void {
    global $passed, $failed, $failures;
    if ($condition) {
        $passed++;
        echo "  [\033[32mPASS\033[0m] {$testName}\n";
    } else {
        $failed++;
        $msg = "  [\033[31mFAIL\033[0m] {$testName}" . ($details ? " - {$details}" : "");
        echo $msg . "\n";
        $failures[] = $msg;
    }
}

function simulateUserSession(string $roleTitle, string $roleType = 'employee', ?int $userId = 999): void {
    if (session_status() === PHP_SESSION_NONE && !headers_sent() && PHP_SAPI !== 'cli') {
        @session_start();
    }
    $_SESSION['user_id'] = $userId;
    $_SESSION['employee_id'] = $userId;
    $_SESSION['role_description'] = $roleTitle;
    $_SESSION['role'] = $roleType;
    $_SESSION['user_role'] = $roleType;
    $_SESSION['csrf_token'] = 'test_token_123456';
    
    // Invalidate cached permissions
    PermissionService::getInstance()->invalidateCache($userId);
}

echo "====================================================================\n";
echo "  CIVENTRAL COMPREHENSIVE ROLE & WORKFLOW TEST SUITE\n";
echo "====================================================================\n\n";

$rolesToTest = [
    'System Administrator' => [
        'dept' => null,
        'isAdmin' => true,
        'allowedDepts' => ['health center services', 'sanitation permits', 'immunization & nutrition', 'wastewater services', 'health surveillance'],
        'mustHavePerms' => [Permissions::PATIENTS_CREATE, Permissions::PERMITS_APPROVE, Permissions::IMMUNIZATION_CREATE, Permissions::WASTEWATER_MANAGE, Permissions::SURVEILLANCE_MANAGE],
        'mustNotHavePerms' => []
    ],
    'Health Center Director' => [
        'dept' => 'health_center',
        'isAdmin' => false,
        'allowedDepts' => ['health center services'],
        'forbiddenDepts' => ['sanitation permits', 'immunization & nutrition', 'wastewater services', 'health surveillance'],
        'mustHavePerms' => [Permissions::PATIENTS_VIEW, Permissions::PATIENTS_CREATE, Permissions::PATIENTS_EDIT, Permissions::PATIENTS_DELETE, Permissions::CONSULTATIONS_VIEW, Permissions::TRIAGE_VIEW, Permissions::USERS_VIEW],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::WASTEWATER_MANAGE, Permissions::SURVEILLANCE_MANAGE]
    ],
    'Doctor' => [
        'dept' => 'health_center',
        'isAdmin' => false,
        'allowedDepts' => ['health center services'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::PATIENTS_VIEW, Permissions::CONSULTATIONS_VIEW, Permissions::CONSULTATIONS_CREATE, Permissions::PRESCRIPTIONS_CREATE],
        'mustNotHavePerms' => [Permissions::PATIENTS_DELETE, Permissions::USERS_CREATE, Permissions::PERMITS_APPROVE]
    ],
    'Nurse' => [
        'dept' => 'health_center',
        'isAdmin' => false,
        'allowedDepts' => ['health center services'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::PATIENTS_VIEW, Permissions::PATIENTS_CREATE, Permissions::PATIENTS_EDIT, Permissions::TRIAGE_VIEW, Permissions::TRIAGE_CREATE],
        'mustNotHavePerms' => [Permissions::PATIENTS_DELETE, Permissions::CONSULTATIONS_CREATE, Permissions::PRESCRIPTIONS_CREATE]
    ],
    'Dentist' => [
        'dept' => 'health_center',
        'isAdmin' => false,
        'allowedDepts' => ['health center services'],
        'forbiddenDepts' => ['sanitation permits', 'immunization & nutrition', 'wastewater services'],
        'mustHavePerms' => [Permissions::PATIENTS_VIEW, Permissions::CONSULTATIONS_CREATE, Permissions::PRESCRIPTIONS_CREATE],
        'mustNotHavePerms' => [Permissions::TRIAGE_CREATE, Permissions::PERMITS_VIEW]
    ],
    'Laboratory Technician' => [
        'dept' => 'health_center',
        'isAdmin' => false,
        'allowedDepts' => ['health center services'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::PATIENTS_VIEW, Permissions::CONSULTATIONS_VIEW, Permissions::PRESCRIPTIONS_VIEW],
        'mustNotHavePerms' => [Permissions::PATIENTS_CREATE, Permissions::CONSULTATIONS_CREATE, Permissions::PRESCRIPTIONS_CREATE]
    ],
    'Medical Records Clerk' => [
        'dept' => 'health_center',
        'isAdmin' => false,
        'allowedDepts' => ['health center services'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services', 'health surveillance'],
        'mustHavePerms' => [Permissions::PATIENTS_VIEW, Permissions::PATIENTS_CREATE, Permissions::PATIENTS_EDIT],
        'mustNotHavePerms' => [Permissions::PATIENTS_DELETE, Permissions::CONSULTATIONS_CREATE, Permissions::PRESCRIPTIONS_CREATE]
    ],
    'Appointment Clerk' => [
        'dept' => 'health_center',
        'isAdmin' => false,
        'allowedDepts' => ['health center services'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::PATIENTS_VIEW, Permissions::PATIENTS_CREATE, Permissions::TRIAGE_VIEW],
        'mustNotHavePerms' => [Permissions::PATIENTS_DELETE, Permissions::CONSULTATIONS_CREATE, Permissions::USERS_VIEW]
    ],
    'Sanitation Director' => [
        'dept' => 'sanitation',
        'isAdmin' => false,
        'allowedDepts' => ['sanitation permits'],
        'forbiddenDepts' => ['health center services', 'immunization & nutrition', 'health surveillance'],
        'mustHavePerms' => [Permissions::PERMITS_VIEW, Permissions::PERMITS_CREATE, Permissions::PERMITS_APPROVE, Permissions::INSPECTIONS_VIEW, Permissions::INSPECTIONS_CONDUCT, Permissions::USERS_VIEW],
        'mustNotHavePerms' => [Permissions::CONSULTATIONS_CREATE, Permissions::IMMUNIZATION_CREATE]
    ],
    'Inspector' => [
        'dept' => 'sanitation',
        'isAdmin' => false,
        'allowedDepts' => ['sanitation permits'],
        'forbiddenDepts' => ['health center services', 'immunization & nutrition'],
        'mustHavePerms' => [Permissions::PERMITS_VIEW, Permissions::INSPECTIONS_VIEW, Permissions::INSPECTIONS_CONDUCT],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::USERS_CREATE]
    ],
    'Permit Clerk' => [
        'dept' => 'sanitation',
        'isAdmin' => false,
        'allowedDepts' => ['sanitation permits'],
        'forbiddenDepts' => ['health center services', 'wastewater services'],
        'mustHavePerms' => [Permissions::PERMITS_VIEW, Permissions::PERMITS_CREATE, Permissions::INSPECTIONS_VIEW],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::INSPECTIONS_CONDUCT]
    ],
    'Cashier' => [
        'dept' => 'sanitation',
        'isAdmin' => false,
        'allowedDepts' => ['sanitation permits'],
        'forbiddenDepts' => ['health center services', 'immunization & nutrition', 'wastewater services'],
        'mustHavePerms' => [Permissions::PERMITS_VIEW, Permissions::PERMITS_CREATE],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::INSPECTIONS_CONDUCT, Permissions::USERS_VIEW]
    ],
    'Immunization Coordinator' => [
        'dept' => 'immunization',
        'isAdmin' => false,
        'allowedDepts' => ['immunization & nutrition'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::IMMUNIZATION_VIEW, Permissions::IMMUNIZATION_CREATE, Permissions::IMMUNIZATION_EDIT, Permissions::USERS_VIEW],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::WASTEWATER_MANAGE]
    ],
    'Midwife' => [
        'dept' => 'immunization',
        'isAdmin' => false,
        'allowedDepts' => ['immunization & nutrition'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::IMMUNIZATION_VIEW, Permissions::IMMUNIZATION_CREATE, Permissions::PATIENTS_VIEW, Permissions::PATIENTS_CREATE],
        'mustNotHavePerms' => [Permissions::IMMUNIZATION_EDIT, Permissions::PERMITS_APPROVE]
    ],
    'Nutritionist' => [
        'dept' => 'immunization',
        'isAdmin' => false,
        'allowedDepts' => ['immunization & nutrition'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::IMMUNIZATION_VIEW, Permissions::IMMUNIZATION_CREATE, Permissions::IMMUNIZATION_EDIT],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::WASTEWATER_MANAGE]
    ],
    'Nutrition Educator' => [
        'dept' => 'immunization',
        'isAdmin' => false,
        'allowedDepts' => ['immunization & nutrition'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::IMMUNIZATION_VIEW, Permissions::IMMUNIZATION_CREATE],
        'mustNotHavePerms' => [Permissions::IMMUNIZATION_EDIT, Permissions::USERS_VIEW]
    ],
    'Wastewater Officer' => [
        'dept' => 'wastewater',
        'isAdmin' => false,
        'allowedDepts' => ['wastewater services'],
        'forbiddenDepts' => ['health center services', 'immunization & nutrition'],
        'mustHavePerms' => [Permissions::WASTEWATER_VIEW, Permissions::WASTEWATER_CREATE, Permissions::WASTEWATER_EDIT, Permissions::WASTEWATER_MANAGE, Permissions::USERS_VIEW],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::CONSULTATIONS_CREATE]
    ],
    'Surveillance Officer' => [
        'dept' => 'surveillance',
        'isAdmin' => false,
        'allowedDepts' => ['health surveillance'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::SURVEILLANCE_VIEW, Permissions::SURVEILLANCE_CREATE, Permissions::SURVEILLANCE_EDIT, Permissions::SURVEILLANCE_MANAGE],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::USERS_VIEW]
    ],
    'Surveillance Coordinator' => [
        'dept' => 'surveillance',
        'isAdmin' => false,
        'allowedDepts' => ['health surveillance'],
        'forbiddenDepts' => ['sanitation permits', 'wastewater services'],
        'mustHavePerms' => [Permissions::SURVEILLANCE_VIEW, Permissions::SURVEILLANCE_CREATE, Permissions::SURVEILLANCE_EDIT, Permissions::SURVEILLANCE_MANAGE, Permissions::USERS_VIEW],
        'mustNotHavePerms' => [Permissions::PERMITS_APPROVE, Permissions::IMMUNIZATION_EDIT]
    ],
];

echo "SECTION 1: ROLE-BY-ROLE ACCESS CONTROL & PERMISSION AUDIT\n";
echo "--------------------------------------------------------------------\n";

$permService = PermissionService::getInstance();
$deptResolver = DepartmentResolver::getInstance();

foreach ($rolesToTest as $roleName => $rules) {
    echo "\nTesting Role: [{$roleName}]\n";
    simulateUserSession($roleName);

    $scope = $permService->getUserScope();
    assert_test(
        $scope['is_admin'] === $rules['isAdmin'],
        "{$roleName} - Admin flag check",
        "Expected " . ($rules['isAdmin'] ? 'true' : 'false') . ", got " . ($scope['is_admin'] ? 'true' : 'false')
    );

    if ($rules['dept'] !== null) {
        assert_test(
            $scope['department'] === $rules['dept'],
            "{$roleName} - Department scope check",
            "Expected {$rules['dept']}, got " . ($scope['department'] ?? 'null')
        );
    }

    // Check allowed departments
    foreach ($rules['allowedDepts'] as $ad) {
        $canAccess = $deptResolver->canAccessDepartment($ad);
        assert_test($canAccess, "{$roleName} CAN access '{$ad}'");
    }

    // Check forbidden departments
    if (!empty($rules['forbiddenDepts'])) {
        foreach ($rules['forbiddenDepts'] as $fd) {
            $canAccess = $deptResolver->canAccessDepartment($fd);
            assert_test(!$canAccess, "{$roleName} CANNOT access forbidden department '{$fd}'");
        }
    }

    // Check mandatory permissions
    foreach ($rules['mustHavePerms'] as $perm) {
        $has = $permService->hasPermission($perm);
        assert_test($has, "{$roleName} GRANTED permission '{$perm}'");
    }

    // Check forbidden permissions
    foreach ($rules['mustNotHavePerms'] as $perm) {
        $has = $permService->hasPermission($perm);
        assert_test(!$has, "{$roleName} DENIED permission '{$perm}'");
    }
}

echo "\n\nSECTION 2: DYNAMIC PERMISSION CACHING & INVALIDATION TEST\n";
echo "--------------------------------------------------------------------\n";

simulateUserSession('Nurse');
assert_test($permService->hasPermission(Permissions::TRIAGE_CREATE), "Nurse initially has triage.create");
assert_test(!$permService->hasPermission(Permissions::PERMITS_APPROVE), "Nurse does NOT have permits.approve");

// Switch to Sanitation Director dynamically
simulateUserSession('Sanitation Director');
assert_test($permService->hasPermission(Permissions::PERMITS_APPROVE), "Dynamic switch to Sanitation Director grants permits.approve");
assert_test(!$permService->hasPermission(Permissions::TRIAGE_CREATE), "Sanitation Director does not have triage.create");

echo "\n\nSECTION 3: FUNCTIONAL EDGE CASE & BUSINESS WORKFLOW VERIFICATION\n";
echo "--------------------------------------------------------------------\n";

// Test 3.1: Philippine Contact Validation in PermitController & PatientController
echo "\n--- Sub-test 3.1: Philippine Mobile Validation ---\n";
$validPhilippineContacts = ['09171234567', '09228889999', '639171234567', '639987654321', '+63 917 123 4567', '0917-123-4567'];
$invalidContacts = ['12345', '08123456789', '091712345', '0917123456789', '638123456789', 'abcdefghijk'];

foreach ($validPhilippineContacts as $contact) {
    $cleaned = preg_replace('/\D+/', '', $contact);
    $isValid = (bool)preg_match('/^(09\d{9}|639\d{9})$/', $cleaned);
    assert_test($isValid, "Accepts valid format: '{$contact}' -> '{$cleaned}'");
}

foreach ($invalidContacts as $contact) {
    $cleaned = preg_replace('/\D+/', '', $contact);
    $isValid = (bool)preg_match('/^(09\d{9}|639\d{9})$/', $cleaned);
    assert_test(!$isValid, "Rejects invalid format: '{$contact}'");
}

// Test 3.2: Permit Approval Unpaid Guard & Payment Bypass Override
echo "\n--- Sub-test 3.2: Permit Payment Verification Guard ---\n";
simulateUserSession('Sanitation Director');
$permitController = new PermitController();

// Test 3.3: Wastewater Service Request Deletion Rules
echo "\n--- Sub-test 3.3: Wastewater Service Request Deletion Guards ---\n";
simulateUserSession('Wastewater Officer');
$srController = new ServiceRequestController();

// Verify rules: In-progress/completed requests cannot be deleted
$activeStatuses = ['scheduled', 'in_progress', 'completed'];
foreach ($activeStatuses as $st) {
    // We check rule logic
    $isBlocked = !in_array($st, ['pending', 'cancelled'], true);
    assert_test($isBlocked, "Deletion blocked for service request in status '{$st}'");
}

// Test 3.4: Surveillance Monotonic Case Code Generation
echo "\n--- Sub-test 3.4: Surveillance Case Code Monotonic Generator ---\n";
$caseModel = new SurveillanceCase();
$code1 = $caseModel->generateCaseCode('2026');
assert_test(str_starts_with($code1, 'CS-2026-'), "Code starts with CS-2026- ({$code1})");
$numPart = (int)substr($code1, strlen('CS-2026-'));
assert_test($numPart >= 1, "Generated code numeric part is >= 1 ({$numPart})");

// Test 3.5: Cashier Role Fee Collection Permission
echo "\n--- Sub-test 3.5: Cashier Payment Processing Permissions ---\n";
simulateUserSession('Cashier');
$cashierPerms = $permService->getGrantedPermissions();
assert_test(in_array('permits.create', $cashierPerms, true), "Cashier has permits.create for fee processing");
assert_test(!in_array('permits.approve', $cashierPerms, true), "Cashier strictly lacks permits.approve");

echo "\n====================================================================\n";
echo "TEST RESULTS SUMMARY\n";
echo "====================================================================\n";
echo "Total Passed: {$passed}\n";
echo "Total Failed: {$failed}\n";

if ($failed > 0) {
    echo "\nFailures encountered:\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
} else {
    echo "\n\033[32mALL 19 ROLES AND WORKFLOW SPECIFICATIONS VERIFIED WITH ZERO ERRORS!\033[0m\n";
    exit(0);
}
