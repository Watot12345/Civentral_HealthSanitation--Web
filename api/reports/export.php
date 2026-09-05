<?php
// api/reports/export.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/ExportService.php';
require_once __DIR__ . '/../../app/services/PermissionService.php';
require_once __DIR__ . '/../../app/Models/ActivityLog.php';

require_once __DIR__ . '/../../app/services/DepartmentResolver.php';

use App\Services\ExportService;
use App\Services\PermissionService;
use App\Services\DepartmentResolver;

try {
    $perm = PermissionService::getInstance();
    if (!$perm->hasPermission('reports.view') && !$perm->hasPermission('analytics.view')) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Forbidden: Permission denied for exporting reports.']);
        exit;
    }

    $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? '');
    $userRole     = trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? '');
    $isAdmin      = $perm->isAdminRole($userRoleDesc) || $perm->isAdminRole($userRole);

    $deptResolver = DepartmentResolver::getInstance();
    $assignedDept = $deptResolver->resolveDepartmentName();

    $format = strtolower($_GET['format'] ?? $_POST['format'] ?? 'pdf');
    $title  = trim($_GET['title'] ?? $_POST['title'] ?? 'Operational Report');
    $module = trim($_GET['module'] ?? $_POST['module'] ?? 'Reports');

    if (!$isAdmin) {
        // Enforce department scope: non-admins cannot export unified global reports
        if (stripos($module, 'unified') !== false || stripos($title, 'unified') !== false) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Forbidden: Unified global reports are restricted to Administrators.']);
            exit;
        }
        $module = $assignedDept;
    }

    $rawBody = file_get_contents('php://input');
    $payload = json_decode($rawBody, true) ?? $_POST;

    $headers = $payload['headers'] ?? ['Facility', 'Inspector', 'Date', 'Score', 'Status'];
    $rows    = $payload['rows'] ?? [];

    $formatLabel = match($format) {
        'excel', 'xlsx' => 'Excel Export',
        'csv'           => 'CSV Export',
        default         => 'PDF Export'
    };

    // Audit Logging
    try {
        $activityLog = new ActivityLog();
        $activityLog->log("Generated Report: {$title}", [
            'module'  => $module,
            'details' => "Exported {$title} ({$formatLabel}) with " . count($rows) . " record(s)",
            'status'  => 'Success'
        ]);
    } catch (\Throwable $logEx) {
        error_log('Export activity logging error: ' . $logEx->getMessage());
    }

    $timestamp = date('Y-m-d_His');
    $cleanSlug = preg_replace('/[^a-z0-9_-]/i', '_', strtolower($title));

    if ($format === 'excel' || $format === 'xlsx') {
        ExportService::toExcel(['headers' => $headers, 'rows' => $rows], $title, "{$cleanSlug}_{$timestamp}.xlsx");
    } elseif ($format === 'csv') {
        ExportService::toCsv(['headers' => $headers, 'rows' => $rows], "{$cleanSlug}_{$timestamp}.csv");
    } else {
        // PDF: prefer visual HTML (charts + table + AI summary) if client sent it
        $htmlContent = $payload['html'] ?? '';
        if (!empty($htmlContent) && is_string($htmlContent)) {
            ExportService::htmlToPdf($htmlContent, $title, "{$cleanSlug}_{$timestamp}.pdf");
        } else {
            ExportService::toPdf(['headers' => $headers, 'rows' => $rows], $title, "{$cleanSlug}_{$timestamp}.pdf");
        }
    }

} catch (\Throwable $e) {
    error_log('Export API Error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Failed to export report: ' . $e->getMessage()
    ]);
    exit;
}
