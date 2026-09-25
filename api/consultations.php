<?php
// api/consultations.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/ConsultationController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// DEBUG
$rawInput = file_get_contents('php://input');
error_log('=== CONSULTATIONS API ===');
error_log('Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Input: ' . $rawInput);

try {
    $controller = new ConsultationController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    $consultationId = null;
    if (count($parts) >= 3 && is_numeric($parts[2])) {
        $consultationId = $parts[2];
    }
    if (!$consultationId && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $consultationId = $_GET['id'];
    }

    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            if ($consultationId) {
                $controller->show($consultationId);
            } elseif (isset($_GET['q'])) {
                $controller->search();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            if ($action === 'update' && $consultationId) {
                $controller->update($consultationId);
            } elseif ($action === 'delete' && $consultationId) {
                $controller->destroy($consultationId);
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
            if ($consultationId) {
                $controller->update($consultationId);
            } else {
                Response::error('Consultation ID required for update', 400);
            }
            break;

        case 'DELETE':
            if ($consultationId) {
                $controller->destroy($consultationId);
            } else {
                Response::error('Consultation ID required for deletion', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Throwable $e) {
    error_log('API ERROR: ' . $e->getMessage());
    error_log('TRACE: ' . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}