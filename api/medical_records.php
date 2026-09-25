<?php
// api/medical_records.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/MedicalRecordController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $controller = new MedicalRecordController();
    $method = $_SERVER['REQUEST_METHOD'];
    $id = $_GET['id'] ?? null;
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            $id ? $controller->show($id) : $controller->index();
            break;

        case 'POST':
            if ($action === 'update' && $id) {
                $controller->update($id);
            } elseif ($action === 'delete' && $id) {
                $controller->destroy($id);
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
            $id ? $controller->update($id) : Response::error('Medical record ID required for update', 400);
            break;

        case 'DELETE':
            $id ? $controller->destroy($id) : Response::error('Medical record ID required for deletion', 400);
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Throwable $e) {
    error_log('Medical Records API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}
