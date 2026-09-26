<?php
date_default_timezone_set('Asia/Manila');
// api/permits.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../Core/BaseController.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/PermitRecordsController.php';

// Parse path segments after api/permitrecord(.php)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));

$subSegments = [];
$foundEndpoint = false;
foreach ($parts as $p) {
    if ($foundEndpoint) {
        $subSegments[] = $p;
    } elseif (str_contains($p, 'permitrecord') || str_contains($p, 'permits')) {
        $foundEndpoint = true;
    }
}

$id = null;
$action = $_GET['action'] ?? null;

if (!empty($subSegments)) {
    if (is_numeric($subSegments[0])) {
        $id = (int)$subSegments[0];
        $action = $subSegments[1] ?? $action;
    } elseif ($subSegments[0] === 'stats') {
        $action = 'stats';
    }
}

// Also support ?id=... and ?stats=true
if ($id === null && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
}
if ((isset($_GET['stats']) && $_GET['stats'] === 'true') || (isset($_GET['action']) && $_GET['action'] === 'stats')) {
    $action = 'stats';
}

$controller = new PermitRecordsController();

// Route the request
try {
    switch ($method) {
        case 'GET':
            if ($action === 'stats') {
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
            } elseif ($id && ($action === 'update')) {
                $controller->update($id);
            } elseif ($id && ($action === 'delete')) {
                $controller->destroy($id);
            } else {
                $controller->store();
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                if ($action === 'renew') {
                    $controller->renew($id);
                } else {
                    $controller->update($id);
                }
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