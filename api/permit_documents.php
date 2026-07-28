<?php
// api/permit_documents.php

// Remove output buffering - it's causing issues
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Models/PermitDocument.php';
require_once __DIR__ . '/../app/Models/Employee.php';
require_once __DIR__ . '/../app/Controllers/PermitDocumentController.php';

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
    $db = Database::getInstance();
    
    $documentModel = new PermitDocument($db);
    $employeeModel = new Employee($db);
    
    $controller = new PermitDocumentController($documentModel, $employeeModel);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    $documentId = null;
    if (count($parts) >= 3 && is_numeric($parts[2])) {
        $documentId = $parts[2];
    }
    
    $action = count($parts) >= 4 ? $parts[3] : null;
    
    switch ($method) {
        case 'GET':
            if (isset($_GET['stats'])) {
                $controller->stats();
            } elseif (isset($_GET['by_permit']) && is_numeric($_GET['by_permit'])) {
                $controller->getByPermit((int)$_GET['by_permit']);
            } elseif (isset($_GET['expiring'])) {
                $controller->getExpiringSoon();
            } elseif ($documentId) {
                $controller->show($documentId);
            } elseif (isset($_GET['page'])) {
                $controller->paginated();
            } else {
                $controller->index();
            }
            break;
            
        case 'POST':
            $controller->store();
            break;
            
        case 'PUT':
            if (!$documentId) {
                Response::error('Document ID is required for update', 400);
            }
            $controller->update($documentId);
            break;
            
        case 'PATCH':
            if (!$documentId) {
                Response::error('Document ID is required', 400);
            }
            if ($action === 'verify') {
                $controller->verify($documentId);
            } else {
                Response::error('Invalid action', 400);
            }
            break;
            
        case 'DELETE':
            if (!$documentId) {
                Response::error('Document ID is required for deletion', 400);
            }
            $controller->destroy($documentId);
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (\Throwable $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
    exit;
}
