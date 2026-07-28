<?php
// api/roles.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/RoleController.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $controller = new RoleController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Get role ID from URL if exists (e.g. /api/roles.php/3)
    $roleId = null;
    if (count($parts) >= 3 && is_numeric($parts[2])) {
        $roleId = $parts[2];
    }
    if (!$roleId && isset($_GET['id'])) {
        $roleId = $_GET['id'];
    }

    switch ($method) {
        case 'GET':
            if ($roleId) {
                $controller->show($roleId);
            } else {
                $controller->index();
            }
            break;

        case 'PUT':
            if ($roleId) {
                $controller->update($roleId);
            } else {
                Response::error('Role ID required for update', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}
