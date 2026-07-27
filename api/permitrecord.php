<?php
// api/permits.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../Core/BaseController.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/PermitRecordsController.php';

// Parse the request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path
$basePath = '/api/permits';
$path = substr($uri, strlen($basePath));
$path = trim($path, '/');

// Parse path segments
$segments = $path ? explode('/', $path) : [];
$id = isset($segments[0]) && is_numeric($segments[0]) ? (int)$segments[0] : null;
$action = $segments[1] ?? null;

$controller = new PermitController();

// Route the request
try {
    switch ($method) {
        case 'GET':
            if ($path === 'stats') {
                $controller->stats();
            } elseif ($id && $action === 'documents') {
                $controller->documents($id);
            } elseif ($id) {
                $controller->show($id);
            } else {
                $controller->index();
            }
            break;
            
        case 'POST':
            if ($id && $action === 'renew') {
                $controller->renew($id);
            } elseif ($id && $action === 'documents') {
                $controller->uploadDocument($id);
            } else {
                $controller->store();
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                $controller->update($id);
            } else {
                Response::error('Permit ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                $controller->destroy($id);
            } else {
                Response::error('Permit ID required', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    Response::error('Internal server error', 500);
}