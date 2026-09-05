<?php
// api/privacy/deletion.php

require_once __DIR__ . '/../../Core/Env.php';
require_once __DIR__ . '/../../Core/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/DataDeletionRequest.php';
require_once __DIR__ . '/../../app/services/RateLimiterService.php';

Env::load();
header('Content-Type: application/json');

$limiter = new RateLimiterService();
$ipCheck = $limiter->check();
if (!$ipCheck['allowed']) {
    Response::error('Too many requests. Please try again later.', [], 429);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$model = new DataDeletionRequest();

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $action = $input['action'] ?? 'request';

    if ($action === 'request') {
        $userId = trim((string)($input['user_id'] ?? ''));
        $subjectType = trim((string)($input['subject_type'] ?? 'patient'));
        $reason = trim((string)($input['reason'] ?? ''));

        if (empty($userId)) {
            Response::error('user_id is required.', [], 400);
            exit;
        }

        $res = $model->createRequest($userId, $subjectType, $reason);
        Response::success($res, 'Deletion request submitted successfully.');
        exit;
    }

    if ($action === 'approve') {
        $requestId = (int)($input['request_id'] ?? 0);
        $adminUser = trim((string)($input['admin_user'] ?? 'admin'));

        if ($requestId <= 0) {
            Response::error('Invalid request_id.', [], 400);
            exit;
        }

        $ok = $model->approveRequest($requestId, $adminUser);
        if ($ok) {
            Response::success(['request_id' => $requestId], 'Deletion request approved.');
        } else {
            Response::error('Failed to approve deletion request.', [], 500);
        }
        exit;
    }

    if ($action === 'execute') {
        $executor = trim((string)($input['executor'] ?? 'admin_manual'));
        $count = $model->processApprovedRequests($executor);
        Response::success(['processed_count' => $count], "Executed {$count} approved deletion request(s).");
        exit;
    }
}

if ($method === 'GET') {
    $pending = $model->getPendingRequests();
    Response::success($pending, 'Pending deletion requests retrieved.');
    exit;
}

Response::error('Method not allowed', [], 405);
