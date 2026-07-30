<?php
// tests/Unit/RememberMeServiceTest.php

require_once __DIR__ . '/../../config/paths.php';

use App\Services\RememberMeService;

class RememberMeServiceTest
{
    public function runTests(): void
    {
        echo "Running RememberMeService Unit Tests...\n";

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $testUser = [
            'id'          => 1,
            'employee_id' => 'SYS--ADMIN-2011',
            'full_name'   => 'System Administrator',
            'department'  => 'Administration',
            'role'        => 'System Administrator',
            'password'    => '$2y$10$abcdefghijklmnopqrstuvwxyz1234567890'
        ];

        // Test 1: Token Creation
        RememberMeService::createToken($testUser);
        assert(isset($_COOKIE['civentral_remember']), "Remember cookie should be set after createToken");
        echo " ✓ Test 1 Passed: Remember Me cookie token generation\n";

        // Test 2: Auto-login Processing
        unset($_SESSION['logged_in'], $_SESSION['user_id']);
        $autoLoggedIn = RememberMeService::processAutoLogin();
        assert($autoLoggedIn === true, "Auto login should succeed with valid remember cookie");
        assert($_SESSION['user_id'] == 1, "Session user_id should be restored to 1");
        assert($_SESSION['employee_id'] === 'SYS--ADMIN-2011', "Session employee_id should be restored");
        echo " ✓ Test 2 Passed: Auto-login session restoration\n";

        // Test 3: Clear Token
        RememberMeService::clearToken();
        assert(!isset($_COOKIE['civentral_remember']), "Remember cookie should be unset after clearToken");
        echo " ✓ Test 3 Passed: Token clearing on logout\n";

        echo "All RememberMeService Unit Tests Passed Successfully!\n\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    (new RememberMeServiceTest())->runTests();
}
