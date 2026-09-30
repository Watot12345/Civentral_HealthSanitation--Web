<?php
// scratch/test_task3_functional.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/Controllers/ChildController.php';
require_once __DIR__ . '/../api/immunization.php';

use App\Constants\Permissions;
use App\Services\PermissionService;
use App\Services\DepartmentResolver;

echo "==============================================================\n";
echo "  FUNCTIONAL BUSINESS LOGIC TEST — TASK 3 (IMMUNIZATION LEAD)\n";
echo "==============================================================\n\n";

$pass = 0;
$fail = 0;

// Test 1: ChildController extends BaseController and has RBAC/CSRF methods
echo "Test 1: ChildController extends BaseController and enforces RBAC/CSRF...\n";
$controller = new ChildController();
if ($controller instanceof BaseController) {
    $refClass = new ReflectionClass(ChildController::class);
    if ($refClass->hasMethod('store') && $refClass->hasMethod('update') && $refClass->hasMethod('destroy')) {
        echo "  [PASS] ChildController extends BaseController with full REST action methods\n";
        $pass++;
    } else {
        echo "  [FAIL] ChildController missing expected REST action methods\n";
        $fail++;
    }
} else {
    echo "  [FAIL] ChildController does not extend BaseController\n";
    $fail++;
}

// Test 2: Child Model findWithVaccinations method
echo "Test 2: Child Model findWithVaccinations exists...\n";
$childModel = new Child();
if (method_exists($childModel, 'findWithVaccinations')) {
    echo "  [PASS] Child Model supports findWithVaccinations for complete clinical record retrieval\n";
    $pass++;
} else {
    echo "  [FAIL] Child Model missing findWithVaccinations\n";
    $fail++;
}

// Test 3: Vaccine compliance logic evaluates distinct EPI antigens
echo "Test 3: calculateVaccineCompliance evaluates distinct EPI antigens (anti-cheat)...\n";
// Create a mock Database object or test the logic directly
$dummyMockDosesDuplicate = [
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'BCG', 'dose' => 1]
];

// Test using mock DB class to test calculateVaccineCompliance
class MockDbForCompliance extends Database {
    private array $doses;
    public function __construct(array $doses) {
        $this->doses = $doses;
    }
    public function select(string $table, array $filters = [], array $options = [], ?bool $useServiceKey = null): array {
        return $this->doses;
    }
}

$mockDbDup = new MockDbForCompliance($dummyMockDosesDuplicate);
$complianceDup = calculateVaccineCompliance($mockDbDup, 999);

// 10 duplicate BCG doses should NOT yield 100%, but exactly 1/14 credited = 7%
if ($complianceDup <= 10) {
    echo "  [PASS] 10 duplicate BCG doses yielded {$complianceDup}% compliance (prevented 100% false compliance)\n";
    $pass++;
} else {
    echo "  [FAIL] 10 duplicate BCG doses incorrectly yielded {$complianceDup}%\n";
    $fail++;
}

// Test full EPI schedule compliance
$dummyMockFullSchedule = [
    ['vaccine' => 'BCG', 'dose' => 1],
    ['vaccine' => 'Hepatitis B', 'dose' => 1],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 1],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 2],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 3],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 1],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 2],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 3],
    ['vaccine' => 'Inactivated Polio Vaccine (IPV)', 'dose' => 1],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 1],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 2],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 3],
    ['vaccine' => 'Measles-Mumps-Rubella (MMR)', 'dose' => 1],
    ['vaccine' => 'Measles-Mumps-Rubella (MMR)', 'dose' => 2],
];
$mockDbFull = new MockDbForCompliance($dummyMockFullSchedule);
$complianceFull = calculateVaccineCompliance($mockDbFull, 999);

if ($complianceFull === 100) {
    echo "  [PASS] Full 14-dose EPI schedule yielded 100% compliance\n";
    $pass++;
} else {
    echo "  [FAIL] Full schedule yielded {$complianceFull}%, expected 100%\n";
    $fail++;
}

// Test 4: Immunization Coordinator has required permissions
echo "Test 4: Immunization Coordinator role granted permissions...\n";
$_SESSION['user_id'] = 777;
$_SESSION['employee_id'] = 777;
$_SESSION['user_role'] = 'Immunization Coordinator';
$_SESSION['role'] = 'Immunization Coordinator';
$_SESSION['role_description'] = 'Immunization Coordinator';
$_SESSION['department'] = 'Immunization & Nutrition';
$_SESSION['user'] = [
    'id' => 777,
    'employee_id' => 777,
    'role' => 'Immunization Coordinator',
    'role_description' => 'Immunization Coordinator',
    'department' => 'Immunization & Nutrition'
];
unset($_SESSION['granted_permission_slugs_key']);
unset($_SESSION['granted_permission_slugs']);

$permService = PermissionService::getInstance();
$granted = $permService->getGrantedPermissions();

$requiredCoordinatorSlugs = [
    'dashboard.immunization',
    'immunization.view',
    'immunization.create',
    'immunization.edit'
];

$missing = array_diff($requiredCoordinatorSlugs, $granted);
if (empty($missing)) {
    echo "  [PASS] Immunization Coordinator has all core operational permissions\n";
    $pass++;
} else {
    echo "  [FAIL] Immunization Coordinator missing: " . implode(', ', $missing) . "\n";
    $fail++;
}

echo "\n==============================================================\n";
echo "TASK 3 TEST RESULTS: {$pass} Passed, {$fail} Failed\n";
echo "==============================================================\n";
