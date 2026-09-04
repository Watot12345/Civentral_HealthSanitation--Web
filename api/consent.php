<?php
// api/consent.php

declare(strict_types=1);

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/ConsentController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Intake-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $controller = new ConsentController();
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Parse URI for endpoint routing (/api/consent or /api/consent/withdraw or action=withdraw)
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $action = $_GET['action'] ?? '';

    $isWithdraw = ($action === 'withdraw') || str_ends_with(rtrim($uriPath, '/'), '/withdraw');

    switch ($method) {
        case 'POST':
            if ($isWithdraw) {
                $controller->withdraw();
            } else {
                $controller->store();
            }
            break;

        case 'GET':
            $controller->index();
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (\Throwable $e) {
    error_log('API Error in api/consent.php: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}
