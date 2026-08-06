<?php
// api/triage.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/TriageController.php';

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
    $controller = new TriageController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Extract ID if provided in URI or query param
    $id = null;
    if (count($parts) >= 3 && is_numeric(end($parts))) {
        $id = end($parts);
    }
    if (!$id && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];
    }

    $action = $_GET['action'] ?? null;

    switch ($method) {
        case 'GET':
            if ($action === 'queue-stats') {
                $controller->queueStats();
            } elseif ($action === 'call-next') {
                $controller->callNext();
            } elseif ($action === 'count_assigned') {
                // Returns count of triage records with a doctor assigned — used for real-time polling
                try {
                    $triageModel = new Triage();
                    $all = $triageModel->all();
                    $count = count(array_filter($all, fn($t) => !empty($t['doctor_id']) || !empty($t['doctor_assigned'])));
                    echo json_encode(['success' => true, 'count' => $count]);
                } catch (Throwable $e) {
                    echo json_encode(['success' => false, 'count' => 0]);
                }
            } elseif ($id) {
                $controller->show($id);
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            $controller->store();
            break;

        case 'PUT':
        case 'PATCH':
            if ($id) {
                if ($action === 'status' || isset($_GET['status'])) {
                    $controller->updateStatus($id);
                } else {
                    $controller->update($id);
                }
            } else {
                Response::error('Triage record ID required for update', 400);
            }
            break;

        case 'DELETE':
            if ($id) {
                $controller->destroy($id);
            } else {
                Response::error('Triage record ID required for deletion', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }

} catch (Exception $e) {
    error_log('Triage API Error: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}