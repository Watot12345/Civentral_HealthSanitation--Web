<?php
// api/patients.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/PatientController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $controller = new PatientController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    $patientId = null;
    foreach (array_reverse($parts) as $part) {
        if (is_numeric($part)) {
            $patientId = $part;
            break;
        }
    }
    if (!$patientId && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $patientId = $_GET['id'];
    }
    
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            if ($patientId) {
                $controller->show($patientId);
            } elseif (isset($_GET['q'])) {
                $controller->search();
            } else {
                $controller->index();
            }
            break;
            
        case 'POST':
            if ($action === 'import') {
                $controller->import();
            } elseif ($action === 'update' && $patientId) {
                $controller->update($patientId);
            } elseif ($action === 'delete' && $patientId) {
                $controller->destroy($patientId);
            } else {
                $controller->store();
            }
            break;
            
        case 'PUT':
            if ($patientId) {
                $controller->update($patientId);
            } else {
                Response::error('Patient ID required for update', 400);
            }
            break;
            
        case 'DELETE':
            if ($patientId) {
                $controller->destroy($patientId);
            } else {
                Response::error('Patient ID required for deletion', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}