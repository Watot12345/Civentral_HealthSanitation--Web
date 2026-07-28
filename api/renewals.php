<?php
// api/renewals.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Models/Renewal.php';
require_once __DIR__ . '/../app/Models/Permit.php';
require_once __DIR__ . '/../app/Controllers/RenewalController.php';

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
    $renewalModel = new Renewal();
    $permitModel = new Permit();

    $controller = new RenewalController($renewalModel, $permitModel);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Find the position of this script in the URL path to handle base URLs correctly
    $scriptPos = array_search('renewals.php', $parts, true);
    
    $renewalId = null;
    if ($scriptPos !== false && isset($parts[$scriptPos + 1]) && is_numeric($parts[$scriptPos + 1])) {
        $renewalId = $parts[$scriptPos + 1];
    }

    $action = ($scriptPos !== false && isset($parts[$scriptPos + 2])) ? $parts[$scriptPos + 2] : null;

    switch ($method) {
        case 'GET':
            if (isset($_GET['stats'])) {
                $controller->stats();
            } elseif (isset($_GET['history'])) {
                $controller->history();
            } elseif (isset($_GET['expiring'])) {
                $controller->expiringSoon();
            } elseif (isset($_GET['permits'])) {
                $controller->getPermits();
            } elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $controller->show((string)(int)$_GET['id']);
            } elseif ($renewalId) {
                $controller->show($renewalId);
            } elseif (isset($_GET['page'])) {
                $controller->paginated();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            $controller->store();
            break;

        case 'PATCH':
            if (!$renewalId) {
                Response::error('Renewal ID is required', 400);
            }
            if ($action === 'approve') {
                $controller->approve($renewalId);
            } elseif ($action === 'reject') {
                $controller->reject($renewalId);
            } else {
                $controller->update($renewalId);
            }
            break;

        case 'DELETE':
            if (!$renewalId) {
                Response::error('Renewal ID is required for deletion', 400);
            }
            $controller->destroy($renewalId);
            break;

        default:
            Response::error('Method not allowed', 405);
    }

} catch (\Throwable $e) {
    error_log('Renewals API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
    exit;
}