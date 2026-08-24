<?php
// api/settings/storage-stats.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/repositories/BackupRepository.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $force = isset($_GET['refresh']) && $_GET['refresh'] === '1';

    $storageMetrics = $db->getStorageMetrics($force);
    $databaseMetrics = $db->getDatabaseMetrics($force);

    $backupRepo = new \App\Repositories\BackupRepository();
    $backupHistory = $backupRepo->getHistory(10);

    $backupDir = __DIR__ . '/../../storage/backups';
    $localFiles = [];
    $totalBackupBytes = 0;
    if (is_dir($backupDir)) {
        foreach (scandir($backupDir) as $f) {
            if ($f === '.' || $f === '..' || $f === '.gitignore') continue;
            $path = $backupDir . '/' . $f;
            if (is_file($path)) {
                $size = filesize($path);
                $totalBackupBytes += $size;
                $localFiles[] = [
                    'filename' => $f,
                    'size' => $size,
                    'size_formatted' => Database::formatBytes($size),
                    'created_at' => date('Y-m-d H:i:s', filemtime($path))
                ];
            }
        }
    }

    echo json_encode([
        'success' => true,
        'storage' => $storageMetrics,
        'database' => $databaseMetrics,
        'local_backups' => [
            'total_files' => count($localFiles),
            'total_bytes' => $totalBackupBytes,
            'total_formatted' => Database::formatBytes($totalBackupBytes),
            'history' => $backupHistory
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch storage statistics: ' . $e->getMessage()
    ]);
}
