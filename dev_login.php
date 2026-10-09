<?php
// dev_login.php - Dev Role Switcher for UI & Flow Testing
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/services/PermissionService.php';
require_once __DIR__ . '/app/services/DepartmentResolver.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

$roleMap = [
    'Health Center Director' => [
        'id' => 2,
        'employee_id' => 'HCD-0001',
        'full_name' => 'Maria Santos',
        'role' => 'Health Center Director',
        'role_description' => 'Health Center Director',
        'department' => 'Health Center Services',
        'email' => 'maria@health.gov.ph',
    ],
    'Doctor' => [
        'id' => 3,
        'employee_id' => 'HMP-0001',
        'full_name' => 'Juan Dela Cruz',
        'role' => 'Medical Practitioner',
        'role_description' => 'Doctor',
        'department' => 'Health Center Services',
        'email' => 'doctor@health.gov.ph',
    ],
    'Sanitation Director' => [
        'id' => 9,
        'employee_id' => 'SD-0001',
        'full_name' => 'Pedro Garcia',
        'role' => 'Sanitation Director',
        'role_description' => 'Sanitation Director',
        'department' => 'Sanitation Permits',
        'email' => 'sdirector@health.gov.ph',
    ],
    'Cashier' => [
        'id' => 12,
        'employee_id' => 'SO-0003',
        'full_name' => 'Jenny Flores',
        'role' => 'Sanitation Officer',
        'role_description' => 'Cashier',
        'department' => 'Sanitation Permits',
        'email' => 'cashier@health.gov.ph',
    ],
    'Immunization Coordinator' => [
        'id' => 13,
        'employee_id' => 'IL-0001',
        'full_name' => 'Grace Mendoza',
        'role' => 'Immunization Lead',
        'role_description' => 'Immunization Coordinator',
        'department' => 'Immunization & Nutrition',
        'email' => 'immunization@health.gov.ph',
    ],
    'Wastewater Lead' => [
        'id' => 17,
        'employee_id' => 'WL-0001',
        'full_name' => 'Ramon Flores',
        'role' => 'Wastewater Lead',
        'role_description' => 'Wastewater Officer',
        'department' => 'Wastewater Services',
        'email' => 'wastewater@health.gov.ph',
    ],
    'Surveillance Coordinator' => [
        'id' => 19,
        'employee_id' => 'SL-0002',
        'full_name' => 'James Rivera',
        'role' => 'Surveillance Lead',
        'role_description' => 'Surveillance Coordinator',
        'department' => 'Health Surveillance',
        'email' => 'coordinator@health.gov.ph',
    ],
    'System Admin' => [
        'id' => 1,
        'employee_id' => 'HSA-ADMIN-01',
        'full_name' => 'Joshua Sierra',
        'role' => 'System Admin',
        'role_description' => 'System Administrator',
        'department' => 'Administration',
        'email' => 'admin@health.gov.ph',
    ],
];

$requestedRole = $_GET['role'] ?? 'Health Center Director';
$user = $roleMap[$requestedRole] ?? $roleMap['Health Center Director'];

$_SESSION['user_id']          = $user['id'];
$_SESSION['employee_id']      = $user['employee_id'];
$_SESSION['full_name']        = $user['full_name'];
$_SESSION['user_full_name']   = $user['full_name'];
$_SESSION['department']       = $user['department'];
$_SESSION['user_department']  = $user['department'];
$_SESSION['role']             = $user['role'];
$_SESSION['role_description'] = $user['role_description'];
$_SESSION['user_role']        = $user['role_description'];
$_SESSION['email']            = $user['email'];
$_SESSION['logged_in']        = true;
$_SESSION['last_activity']    = time();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

\App\Services\PermissionService::getInstance()->invalidateCache();

$redirect = $_GET['redirect'] ?? 'pages/dashboard.php';
$extraParams = $_GET;
unset($extraParams['role'], $extraParams['redirect']);
if (!empty($extraParams)) {
    $redirect .= (str_contains($redirect, '?') ? '&' : '?') . http_build_query($extraParams);
}
header('Location: ' . site_url($redirect));
exit;
