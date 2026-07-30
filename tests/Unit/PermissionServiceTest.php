<?php
// tests/Unit/PermissionServiceTest.php

require_once __DIR__ . '/../../config/paths.php';

use App\Services\PermissionService;
use App\Constants\Permissions;

class PermissionServiceTest
{
    public function runTests(): void
    {
        echo "Running PermissionService Unit Tests...\n";

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // Test 1: Admin bypass
        $_SESSION['role'] = 'System Administrator';
        $_SESSION['granted_permission_slugs'] = null;
        $service = PermissionService::getInstance();

        assert($service->hasPermission(Permissions::DASHBOARD_VIEW) === true, "Admin should have DASHBOARD_VIEW");
        assert($service->hasPermission(Permissions::SETTINGS_MANAGE) === true, "Admin should have SETTINGS_MANAGE");
        echo " ✓ Test 1 Passed: Admin full authorization bypass\n";

        // Test 2: Standard role authorization
        $_SESSION['role'] = 'Doctor';
        $_SESSION['granted_permission_slugs'] = [
            Permissions::DASHBOARD_VIEW,
            Permissions::PATIENTS_VIEW,
            Permissions::PATIENTS_CREATE,
            Permissions::CONSULTATIONS_VIEW,
        ];

        assert($service->hasPermission(Permissions::PATIENTS_VIEW) === true, "Doctor should have PATIENTS_VIEW");
        assert($service->hasPermission(Permissions::SETTINGS_MANAGE) === false, "Doctor should NOT have SETTINGS_MANAGE");
        echo " ✓ Test 2 Passed: Standard role granted permissions evaluation\n";

        // Test 3: Cache Invalidation
        $service->invalidateCache();
        assert(!isset($_SESSION['granted_permission_slugs']), "Cache invalidation should clear session permission array");
        echo " ✓ Test 3 Passed: Automatic permission cache invalidation\n";

        echo "All PermissionService Unit Tests Passed Successfully!\n\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    (new PermissionServiceTest())->runTests();
}
