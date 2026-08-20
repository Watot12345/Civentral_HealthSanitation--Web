<?php
// modules/surveillence/api/alerts.php
// Pure Surveillance REST endpoint for active alerts, stats, and 15-second heartbeat

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paths.php';
require_once __DIR__ . '/../../../app/services/AlertService.php';

use App\Services\AlertService;

$action = trim($_GET['action'] ?? ($_POST['action'] ?? 'get_alerts'));
$service = AlertService::getInstance();

switch ($action) {
    case 'heartbeat':
    case 'get_alerts':
        $lastSync = trim($_GET['last_sync'] ?? '');
        $alerts = $service->getActiveAlerts();
        $criticalCount = 0;
        foreach ($alerts as $a) {
            if ($a['plain_status'] === '🔴 Outbreak Alert' || $a['severity'] === 'Critical') {
                $criticalCount++;
            }
        }
        echo json_encode([
            'success'        => true,
            'timestamp'      => date('Y-m-d H:i:s'),
            'total_active'   => count($alerts),
            'critical_count' => $criticalCount,
            'alerts'         => $alerts
        ]);
        exit;

    case 'get_trends':
        $trendData = $service->get12WeekTrendData();
        echo json_encode([
            'success' => true,
            'data'    => $trendData
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Action not recognized.']);
        exit;
}
