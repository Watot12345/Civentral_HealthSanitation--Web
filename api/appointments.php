<?php
// api/appointments.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/AppointmentController.php';
require_once __DIR__ . '/../app/services/RateLimiterService.php';

// Rate Limiting Protection (60 req / min per IP)
$limiter = new RateLimiterService(60, 60);
$rateCheck = $limiter->check();
if (!$rateCheck['allowed']) {
    http_response_code(429);
    header('Retry-After: ' . $rateCheck['reset']);
    echo json_encode([
        'success' => false,
        'message' => 'Too many requests. Please retry in ' . $rateCheck['reset'] . ' seconds.',
        'retry_after' => $rateCheck['reset']
    ]);
    exit;
}

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
    $controller = new AppointmentController();
    $method = $_SERVER['REQUEST_METHOD'];
    
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Get appointment ID from URL if exists
    $appointmentId = null;
    if (count($parts) >= 3 && is_numeric($parts[2])) {
        $appointmentId = $parts[2];
    }

    // Also support ?id=...
    if (!$appointmentId && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $appointmentId = $_GET['id'];
    }

    $action = $_GET['action'] ?? '';
    $isStatusUpdate = ($action === 'status');

    switch ($method) {
        case 'GET':
            if ($appointmentId) {
                $controller->show($appointmentId);
            } elseif (isset($_GET['q'])) {
                $controller->search();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            // FIXED: Check action parameter for update/delete operations
            if ($action === 'status' && $appointmentId) {
                $controller->updateStatus($appointmentId);
            } elseif ($action === 'update' && $appointmentId) {
                $controller->update($appointmentId);
            } elseif ($action === 'delete' && $appointmentId) {
                $controller->destroy($appointmentId);
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
        case 'PATCH':
            if ($appointmentId) {
                if ($isStatusUpdate) {
                    $controller->updateStatus($appointmentId);
                } else {
                    $controller->update($appointmentId);
                }
            } else {
                Response::error('Appointment ID is required for update', 400);
            }
            break;

        case 'DELETE':
            if ($appointmentId) {
                $controller->destroy($appointmentId);
            } else {
                Response::error('Appointment ID is required for deletion', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('API Error in appointments.php: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}