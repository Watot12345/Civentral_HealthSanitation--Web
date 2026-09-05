<?php
// Session Seeder for Automated Integration Tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['logged_in']  = true;
$_SESSION['user_id']    = 1;
$_SESSION['username']   = 'admin';
$_SESSION['full_name']  = 'System Administrator';
$_SESSION['role']       = 'admin';
$_SESSION['csrf_token'] = 'valid_test_csrf_token_12345';

header('Content-Type: application/json');
echo json_encode([
    'session_id' => session_id(),
    'csrf_token' => $_SESSION['csrf_token']
]);
