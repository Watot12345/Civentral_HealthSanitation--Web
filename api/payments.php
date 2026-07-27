<?php
// api/payments.php

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Controllers/PaymentController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $controller = new PaymentController();
    $method = $_SERVER['REQUEST_METHOD'];

    $paymentId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'GET':
            if (isset($_GET['stats']) && $_GET['stats'] === 'true') {
                $controller->stats();
            } elseif (isset($_GET['fee_structure']) && $_GET['fee_structure'] === 'true') {
                $controller->feeStructure();
            } elseif ($paymentId) {
                $controller->show($paymentId);
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            if ($action === 'complete' && $paymentId) {
                $controller->complete($paymentId);
            } elseif ($action === 'fail' && $paymentId) {
                $controller->fail($paymentId);
            } elseif ($action === 'refund' && $paymentId) {
                $controller->refund($paymentId);
            } elseif ($action === 'update' && $paymentId) {
                $controller->update($paymentId);
            } elseif ($action === 'delete' && $paymentId) {
                $controller->destroy($paymentId);
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
        case 'PATCH':
            if ($paymentId) {
                if ($action === 'complete') {
                    $controller->complete($paymentId);
                } elseif ($action === 'fail') {
                    $controller->fail($paymentId);
                } elseif ($action === 'refund') {
                    $controller->refund($paymentId);
                } else {
                    $controller->update($paymentId);
                }
            } else {
                Response::error('Payment ID is required for update', 400);
            }
            break;

        case 'DELETE':
            if ($paymentId) {
                $controller->destroy($paymentId);
            } else {
                Response::error('Payment ID is required for deletion', 400);
            }
            break;

        default:
            Response::error('Method not allowed', 405);
    }
} catch (Exception $e) {
    error_log('API Error in payments.php: ' . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}