<?php
// api/inspections.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/InspectionController.php';

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
    $controller = new InspectionController();
    $method = $_SERVER['REQUEST_METHOD'];
    
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Get inspection ID from URL if exists
    $inspectionId = null;
    if (count($parts) >= 3 && is_numeric($parts[2])) {
        $inspectionId = $parts[2];
    }

    // Also support ?id=...
    if (!$inspectionId && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $inspectionId = $_GET['id'];
    }

    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
                $controller->stats();
            } elseif ($inspectionId) {
                $controller->show($inspectionId);
            } elseif (isset($_GET['page'])) {
                $controller->paginated();
            } elseif (isset($_GET['q'])) {
                $controller->search();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            if ($action === 'conduct' && $inspectionId) {
                $controller->conduct($inspectionId);
            } elseif ($action === 'status' && $inspectionId) {
                $controller->updateStatus($inspectionId);
            } elseif ($action === 'update' && $inspectionId) {
                $controller->update($inspectionId);
            } elseif ($action === 'delete' && $inspectionId) {
                $controller->destroy($inspectionId);
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
        case 'PATCH':
            if ($inspectionId) {
                if ($action === 'conduct') {
                    $controller->conduct($inspectionId);
                } elseif ($action === 'status') {
                    $controller->updateStatus($inspectionId);
                } else {
                    $controller->update($inspectionId);
                }
            } else {
                Response::error('Inspection ID is required for update', 400);
            }
            break;

        case 'DELETE':
            if ($inspectionId) {
                $controller->destroy($inspectionId);
            } else {
                Response::error('Inspection ID is required for deletion', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('API Error in inspections.php: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}
