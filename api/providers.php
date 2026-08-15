<?php
// api/providers.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/ServiceProviderController.php';

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
    $controller = new ServiceProviderController();
    $method = $_SERVER['REQUEST_METHOD'];

    $id = $_GET['id'] ?? null;
    if (!$id) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));
        if (count($parts) >= 3 && is_numeric($parts[2])) {
            $id = $parts[2];
        }
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch ($method) {
        case 'GET':
            if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
                $controller->stats();
            } elseif ($id) {
                $controller->show($id);
            } elseif (isset($_GET['page']) || isset($_GET['q']) || isset($_GET['status']) || isset($_GET['specialization'])) {
                $controller->paginated();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            if ($action === 'update' && $id) {
                $controller->update($id);
            } elseif ($action === 'delete' && $id) {
                $controller->destroy($id);
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
        case 'PATCH':
            if ($id) {
                $controller->update($id);
            } else {
                Response::error('Provider ID is required for update', 400);
            }
            break;

        case 'DELETE':
            if ($id) {
                $controller->destroy($id);
            } else {
                Response::error('Provider ID is required for deletion', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('API Error in providers.php: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}
