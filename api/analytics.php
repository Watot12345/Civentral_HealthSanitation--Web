<?php
// api/analytics.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../app/services/RateLimiterService.php';
require_once __DIR__ . '/../app/services/AiAnalyticsService.php';

use App\Services\PermissionService;
use App\Services\DepartmentResolver;

try {
    // 1. IP Rate Limiting Check (Max 30 requests / minute)
    $limiter = new RateLimiterService(30, 60);
    $limitCheck = $limiter->check();

    header('X-RateLimit-Limit: ' . $limitCheck['limit']);
    header('X-RateLimit-Remaining: ' . $limitCheck['remaining']);
    header('X-RateLimit-Reset: ' . $limitCheck['reset']);

    if (!$limitCheck['allowed']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too Many Requests. Rate limit exceeded.',
            'retry_after_seconds' => $limitCheck['reset']
        ]);
        exit;
    }

    // 2. Resolve Scope Server-Side from Session (DO NOT accept client query params for scope)
    $permService = PermissionService::getInstance();
    if (!$permService->hasPermission('view_ai_analytics') && !$permService->hasPermission('analytics.view')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden: You do not have permission to view AI analytics.',
            'code' => 403
        ]);
        exit;
    }

    $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? '');
    $userRole     = trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? '');

    $isAdmin = $permService->isAdminRole($userRoleDesc) || $permService->isAdminRole($userRole);

    if ($isAdmin) {
        $scope = 'admin';
    } else {
        $deptResolver = DepartmentResolver::getInstance();
        $deptName = $deptResolver->resolveDepartmentName();
        $scope = match(strtolower($deptName)) {
            'health center', 'health center services' => 'health_center',
            'sanitation', 'sanitation permits' => 'sanitation',
            'immunization', 'nutrition', 'immunization & nutrition' => 'immunization',
            'wastewater', 'wastewater services' => 'wastewater',
            'surveillance', 'health surveillance' => 'surveillance',
            default => 'health_center'
        };
    }

    // 3. Query Analytics via AiAnalyticsService
    $range   = $_GET['range'] ?? '6m';
    $filter  = $_GET['filter'] ?? 'disease';
    $yoy     = isset($_GET['yoy']) && ($_GET['yoy'] === 'true' || $_GET['yoy'] === '1');
    $refresh = isset($_GET['refresh']) && ($_GET['refresh'] === '1' || $_GET['refresh'] === 'true');

    $analyticsService = new AiAnalyticsService();
    $data = $analyticsService->getAnalyticsData($range, $filter, $yoy, $refresh, $scope);

    if (isset($data['cache_status'])) {
        header('X-Cache-Status: ' . $data['cache_status']);
    }

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('API Analytics Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch analytics data',
        'error'   => $e->getMessage()
    ]);
}
