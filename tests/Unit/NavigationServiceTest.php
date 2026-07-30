<?php
// tests/Unit/NavigationServiceTest.php

require_once __DIR__ . '/../../config/paths.php';

use App\Services\NavigationService;
use App\Constants\Permissions;

class NavigationServiceTest
{
    public function runTests(): void
    {
        echo "Running NavigationService Unit Tests...\n";

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $navService = NavigationService::getInstance();

        // Test 1: Admin dashboard title
        $_SESSION['role'] = 'System Administrator';
        $_SESSION['granted_permission_slugs'] = Permissions::all();
        assert($navService->getDashboardTitle() === 'System Overview', "Admin dashboard title should be System Overview");
        echo " ✓ Test 1 Passed: Admin dynamic title generation\n";

        // Test 2: Department dashboard title
        $_SESSION['role'] = 'Health Center Director';
        $_SESSION['department'] = 'Health Center Services';
        $_SESSION['granted_permission_slugs'] = [Permissions::DASHBOARD_VIEW, Permissions::PATIENTS_VIEW];
        assert($navService->getDashboardTitle() === 'Health Center Services Dashboard', "Department title should be Health Center Services Dashboard");
        echo " ✓ Test 2 Passed: Department dynamic title generation\n";

        // Test 3: Config-driven navigation filtering
        $filteredNav = $navService->getFilteredNavigation();
        assert(!empty($filteredNav), "Filtered navigation array should not be empty");
        echo " ✓ Test 3 Passed: Config-driven navigation filtering\n";

        echo "All NavigationService Unit Tests Passed Successfully!\n\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    (new NavigationServiceTest())->runTests();
}
