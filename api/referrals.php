<?php
// api/referrals.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/ReferralController.php';

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
    error_log('Referrals API: Starting request - Method: ' . $_SERVER['REQUEST_METHOD'] . ', URI: ' . $_SERVER['REQUEST_URI']);
    
    $controller = new ReferralController();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));
    
    // Get referral ID — scan parts in reverse for a numeric segment
    // Works for both /api/referrals.php/42 and /capstone/api/referrals.php/42
    $id = null;
    foreach (array_reverse($parts) as $part) {
        if (is_numeric($part)) {
            $id = $part;
            break;
        }
    }
    if (!$id && isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];
    }
    
    $action = $_GET['action'] ?? null;
    
    error_log('Referrals API: Routing - Method: ' . $method . ', ID: ' . ($id ?? 'none') . ', Action: ' . ($action ?? 'none'));
    
    switch ($method) {
        case 'GET':
            if ($id) {
                error_log('Referrals API: Calling show(' . $id . ')');
                $controller->show($id);
            } else {
                error_log('Referrals API: Calling index()');
                $controller->index();
            }
            break;
            
        case 'POST':
            error_log('Referrals API: Calling store()');
            $controller->store();
            break;
            
        case 'PUT':
        case 'PATCH':
            if ($id) {
                if ($action === 'status' || isset($_GET['status'])) {
                    error_log('Referrals API: Calling updateStatus(' . $id . ')');
                    $controller->updateStatus($id);
                } else {
                    error_log('Referrals API: Calling update(' . $id . ')');
                    $controller->update($id);
                }
            } else {
                Response::error('Referral ID required for update', 400);
            }
            break;
            
        case 'DELETE':
            if ($id) {
                error_log('Referrals API: Calling destroy(' . $id . ')');
                $controller->destroy($id);
            } else {
                Response::error('Referral ID required for deletion', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
    error_log('Referrals API: Request completed successfully');
    
} catch (Exception $e) {
    error_log('Referrals API Error: ' . $e->getMessage());
    error_log('Referrals API Error Trace: ' . $e->getTraceAsString());
    error_log('Referrals API Error File: ' . $e->getFile() . ' Line: ' . $e->getLine());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    error_log('Referrals API Throwable Error: ' . $e->getMessage());
    error_log('Referrals API Throwable Trace: ' . $e->getTraceAsString());
    Response::error('Internal server error', 500);
}
?>