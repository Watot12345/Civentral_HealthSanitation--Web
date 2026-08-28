<?php
// api/reports/log_export.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/PermissionService.php';

use App\Services\PermissionService;

try {
    $permService = PermissionService::getInstance();
    if (!$permService->hasPermission('reports.view') && !$permService->hasPermission('analytics.view')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden: Permission denied for logging reports.'
        ]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $reportName = trim($input['report_name'] ?? 'Compliance & Operational Report');
    $exportType = trim($input['export_type'] ?? ($input['format'] ?? 'Custom Report'));
    $department = trim($input['department'] ?? ($input['facility'] ?? 'All Core Departments'));
    $dateRange  = trim($input['date_range'] ?? '');

    $userId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0);
    $userName = trim($_SESSION['user']['name'] ?? ($_SESSION['name'] ?? ($_SESSION['full_name'] ?? 'System User')));
    $userRole = trim($_SESSION['role_description'] ?? ($_SESSION['user']['role_description'] ?? ($_SESSION['role'] ?? 'Staff Member')));

    if (empty($userName) || str_contains($userName, '@')) {
        $handle = explode('@', $_SESSION['user']['email'] ?? ($_SESSION['email'] ?? 'staff'))[0];
        $userName = ucwords(str_replace(['.', '_', '-'], ' ', $handle));
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    if (str_contains($ip, ',')) {
        $ip = trim(explode(',', $ip)[0]);
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desktop • Chrome (Web Client)';
    $device = 'Desktop • Web Browser';
    if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android') || str_contains($userAgent, 'iPhone')) {
        $device = 'Mobile Device';
    } elseif (str_contains($userAgent, 'Windows')) {
        $device = 'Desktop • Windows';
    } elseif (str_contains($userAgent, 'Macintosh')) {
        $device = 'Desktop • macOS';
    } elseif (str_contains($userAgent, 'Linux')) {
        $device = 'Desktop • Linux';
    }

    $details = "Generated {$reportName} ({$exportType}) for {$department}";
    if (!empty($dateRange)) {
        $details .= " [{$dateRange}]";
    }

    $db = Database::getInstance();
    $insertData = [
        'user_id'    => (int)$userId,
        'user_name'  => $userName,
        'role'       => $userRole,
        'module'     => 'Reports',
        'action'     => "Generated Report: {$reportName}",
        'details'    => $details,
        'ip_address' => $ip,
        'device'     => $device,
        'status'     => 'Success',
        'created_at' => date('Y-m-d H:i:s')
    ];

    $db->insert('activity_logs', $insertData);

    echo json_encode([
        'success' => true,
        'message' => 'Report generation activity logged successfully.',
        'log' => $insertData
    ]);

} catch (Throwable $e) {
    error_log('API Report Export Log Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to record report log',
        'error' => $e->getMessage()
    ]);
}
