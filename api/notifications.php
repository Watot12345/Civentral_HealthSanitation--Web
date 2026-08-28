<?php
// api/notifications.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/NotificationService.php';

try {
    $userId = (int)($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Please log in.']);
        exit;
    }

    $notificationService = new NotificationService();
    $action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $action === 'mark_read') {
        $raw = file_get_contents('php://input');
        $body = !empty($raw) ? json_decode($raw, true) : [];
        $notifIds = $body['notification_ids'] ?? ($_POST['notification_ids'] ?? ($_POST['notification_id'] ?? []));

        if (!is_array($notifIds)) {
            $notifIds = !empty($notifIds) ? [$notifIds] : [];
        }

        $notificationService->markAsRead($userId, $notifIds);

        echo json_encode([
            'success' => true,
            'message' => 'Notifications marked as read.',
            'count'   => count($notifIds)
        ]);
        exit;
    }

    // Default GET: Fetch live notifications with user-read status
    $notifications = $notificationService->getNotifications(15);
    echo json_encode([
        'success' => true,
        'data'    => $notifications
    ]);

} catch (\Throwable $e) {
    error_log('API Notifications Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process notification request',
        'error'   => $e->getMessage()
    ]);
}
