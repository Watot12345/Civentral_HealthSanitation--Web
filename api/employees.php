<?php
// api/employees.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/EmployeeController.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $controller = new EmployeeController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    // Get employee ID from URL if exists (e.g. /api/employees.php/123)
    $employeeId = null;
    if (count($parts) >= 3 && is_numeric($parts[2])) {
        $employeeId = $parts[2];
    }
    
    // Also support ?id=...
    if (!$employeeId && isset($_GET['id'])) {
        $employeeId = $_GET['id'];
    }

    // Check for sub-action (e.g. /api/employees.php/123/status)
    $subAction = null;
    if (count($parts) >= 4) {
        $subAction = $parts[3];
    }
    if (!$subAction && isset($_GET['action'])) {
        $subAction = $_GET['action'];
    }
    
    switch ($method) {
        case 'GET':
            if ($employeeId) {
                $controller->show($employeeId);
            } elseif (isset($_GET['q'])) {
                $controller->search();
            } elseif (isset($_GET['statistics']) || ($parts[2] ?? '') === 'statistics') {
                $controller->statistics();
            } else {
                $controller->index();
            }
            break;
            
        case 'POST':
            $controller->store();
            break;
            
        case 'PUT':
            if ($employeeId) {
                $controller->update($employeeId);
            } else {
                Response::error('Employee ID required for update', 400);
            }
            break;

        case 'PATCH':
            if ($employeeId && $subAction === 'status') {
                $controller->toggleStatus($employeeId);
            } elseif ($employeeId) {
                // Default PATCH = toggle status
                $controller->toggleStatus($employeeId);
            } else {
                Response::error('Employee ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($employeeId) {
                $controller->destroy($employeeId);
            } else {
                Response::error('Employee ID required for deletion', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}