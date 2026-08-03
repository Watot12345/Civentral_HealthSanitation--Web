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
require_once __DIR__ . '/../app/services/RateLimiterService.php';
require_once __DIR__ . '/../app/services/AiAnalyticsService.php';

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

    // 2. Query Analytics via AiAnalyticsService (wrapped in 5-min server cache)
    $range  = $_GET['range'] ?? '6m';
    $filter = $_GET['filter'] ?? 'disease';
    $yoy    = isset($_GET['yoy']) && ($_GET['yoy'] === 'true' || $_GET['yoy'] === '1');
    $refresh = true; // Always bypass cache for instant live Supabase calculations

    $analyticsService = new AiAnalyticsService();
    $data = $analyticsService->getAnalyticsData($range, $filter, $yoy, $refresh);

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
        'error' => $e->getMessage()
    ]);
}
