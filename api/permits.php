<?php
// api/permits.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/PermitController.php';

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
    $controller = new PermitController();
    $method = $_SERVER['REQUEST_METHOD'];
    
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Get permit ID from URL if exists
    $permitId = null;
    if (count($parts) >= 3 && is_numeric($parts[2])) {
        $permitId = $parts[2];
    }

    // Also support ?id=...
    if (!$permitId && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $permitId = $_GET['id'];
    }

    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
                $controller->stats();
            } elseif ($permitId) {
                $controller->show($permitId);
            } elseif (isset($_GET['q'])) {
                $controller->search();
            } elseif (isset($_GET['page'])) {
                $controller->paginated();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            if ($action === 'review' && $permitId) {
                $controller->review($permitId);
            } elseif ($action === 'status' && $permitId) {
                $controller->updateStatus($permitId);
            } elseif ($action === 'update' && $permitId) {
                $controller->update($permitId);
            } elseif ($action === 'delete' && $permitId) {
                $controller->destroy($permitId);
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
        case 'PATCH':
            if ($permitId) {
                if ($action === 'review') {
                    $controller->review($permitId);
                } elseif ($action === 'status') {
                    $controller->updateStatus($permitId);
                } else {
                    $controller->update($permitId);
                }
            } else {
                Response::error('Permit ID is required for update', 400);
            }
            break;

        case 'DELETE':
            if ($permitId) {
                $controller->destroy($permitId);
            } else {
                Response::error('Permit ID is required for deletion', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('API Error in permits.php: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}