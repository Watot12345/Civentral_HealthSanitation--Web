<?php
// api/immunization.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Models/Child.php';
require_once __DIR__ . '/../app/Controllers/ChildController.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $childModel = new Child();
    $controller = new ChildController($childModel);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Find the position of this script in the URL path to handle base URLs correctly
    $scriptPos = array_search('immunization.php', $parts, true);
    
    $targetId = $childId ?? (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null);

    switch ($method) {
        case 'GET':
            if (isset($_GET['stats'])) {
                $controller->stats();
            } elseif ($targetId) {
                $controller->show($targetId);
            } elseif (isset($_GET['page'])) {
                $controller->paginated();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            $controller->store();
            break;

        case 'PUT':
        case 'PATCH':
            if (!$targetId) {
                Response::error('Child ID is required for update', 400);
            }
            $controller->update($targetId);
            break;

        case 'DELETE':
            if (!$targetId) {
                Response::error('Child ID is required for deletion', 400);
            }
            $controller->destroy($targetId);
            break;

        default:
            Response::error('Method not allowed', 405);
    }

} catch (\Throwable $e) {
    error_log('Immunization API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
    exit;
}