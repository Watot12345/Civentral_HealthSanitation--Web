<?php
// api/prescriptions.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/PrescriptionController.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    // Skipping auth for now since AuthMiddleware is not fully implemented
    
    $controller = new PrescriptionController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    // Get prescription ID from URL — scan all path parts for a trailing numeric segment
    // e.g. /capstone/api/prescriptions.php/42  →  parts = [capstone, api, prescriptions.php, 42]
    // e.g. /api/prescriptions.php/42           →  parts = [api, prescriptions.php, 42]
    $prescriptionId = null;
    foreach (array_reverse($parts) as $part) {
        if (is_numeric($part)) {
            $prescriptionId = $part;
            break;
        }
    }
    
    // Also support ?id=... query param as fallback
    if (!$prescriptionId && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $prescriptionId = $_GET['id'];
    }
    
    switch ($method) {
        case 'GET':
            if ($prescriptionId) {
                $controller->show($prescriptionId);
            } elseif (isset($_GET['q'])) {
                $controller->search();
            } else {
                $controller->index();
            }
            break;
            
        case 'POST':
            // Check if this is a dispense action
            if (isset($_POST['action']) && $_POST['action'] === 'dispense') {
                $controller->dispense();
            } else {
                $controller->store();
            }
            break;
            
        case 'PUT':
            if ($prescriptionId) {
                $controller->update($prescriptionId);
            } else {
                Response::error('Prescription ID required for update', 400);
            }
            break;
            
        case 'DELETE':
            if ($prescriptionId) {
                $controller->destroy($prescriptionId);
            } else {
                Response::error('Prescription ID required for deletion', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}