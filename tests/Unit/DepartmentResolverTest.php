<?php
// tests/Unit/DepartmentResolverTest.php

require_once __DIR__ . '/../../config/paths.php';

use App\Services\DepartmentResolver;
use App\Constants\Permissions;

class DepartmentResolverTest
{
    public function runTests(): void
    {
        echo "Running DepartmentResolver Unit Tests...\n";

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $resolver = DepartmentResolver::getInstance();

        // Test 1: Explicit session department
        $_SESSION['department'] = 'Health Center Services';
        assert($resolver->resolveDepartmentName() === 'Health Center Services', "Should return explicit session department");
        assert($resolver->resolveDepartmentSlug() === 'health-center-services', "Should return correct slug");
        echo " ✓ Test 1 Passed: Explicit session department resolution\n";

        // Test 2: Dynamic fallback resolution from permissions
        $_SESSION['department'] = '';
        $_SESSION['role'] = 'Inspector';
        $_SESSION['granted_permission_slugs'] = [
            Permissions::DASHBOARD_VIEW,
            Permissions::PERMITS_VIEW,
            Permissions::INSPECTIONS_VIEW,
        ];

        assert($resolver->resolveDepartmentName() === 'Sanitation Permits', "Inspector permissions should resolve to Sanitation Permits");
        echo " ✓ Test 2 Passed: Dynamic permission-cluster department resolution\n";

        // Test 3: Department user and role filtering
        $sampleUsers = [
            ['id' => 1, 'full_name' => 'Dr. Maria', 'department' => 'Health Center Services', 'role' => 'Doctor'],
            ['id' => 2, 'full_name' => 'Inspector Bob', 'department' => 'Sanitation Permits', 'role' => 'Inspector'],
            ['id' => 3, 'full_name' => 'Nurse Joy', 'department' => 'Health Center Services', 'role' => 'Nurse'],
        ];
        $filteredUsers = $resolver->filterUsersForDepartment($sampleUsers, 'Health Center Services');
        assert(count($filteredUsers) === 2, "Should filter users down to Health Center Services only");
        assert($filteredUsers[0]['full_name'] === 'Dr. Maria');
        assert($filteredUsers[1]['full_name'] === 'Nurse Joy');

        $sampleRoles = [
            ['id' => 1, 'name' => 'Health Center Director'],
            ['id' => 2, 'name' => 'Sanitation Director'],
            ['id' => 3, 'name' => 'Nurse'],
        ];
        $filteredRoles = $resolver->filterRolesForDepartment($sampleRoles, 'Health Center Services');
        assert(count($filteredRoles) === 2, "Should filter position roles down to Health Center Services only");

        echo " ✓ Test 3 Passed: Department user & position role filtering\n";

        echo "All DepartmentResolver Unit Tests Passed Successfully!\n\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    (new DepartmentResolverTest())->runTests();
}
