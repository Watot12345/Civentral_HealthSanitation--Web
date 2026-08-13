<?php
// api/growth.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Models/GrowthMeasurement.php';
require_once __DIR__ . '/../app/Controllers/GrowthController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $model      = new GrowthMeasurement();
    $controller = new GrowthController($model);

    $method   = $_SERVER['REQUEST_METHOD'];
    $targetId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    switch ($method) {
        case 'GET':
            $controller->index();
            break;

        case 'POST':
            $controller->store();
            break;

        case 'PUT':
        case 'PATCH':
            if (!$targetId) {
                Response::error('Measurement ID is required for update', 400);
            }
            $controller->update($targetId);
            break;

        case 'DELETE':
            if (!$targetId) {
                Response::error('Measurement ID is required for deletion', 400);
            }
            $controller->destroy($targetId);
            break;

        default:
            Response::error('Method not allowed', 405);
    }

} catch (\Throwable $e) {
    error_log('Growth API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
    exit;
}
