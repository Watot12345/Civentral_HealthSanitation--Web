<?php
// api/settings/backup-download.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

$fileName = basename($_GET['file'] ?? '');

if (empty($fileName)) {
    http_response_code(400);
    die('Error: No backup filename specified.');
}

// Security: Prevent directory traversal
$backupDir = realpath(__DIR__ . '/../../storage/backups');
if (!$backupDir) {
    http_response_code(404);
    die('Error: Backup directory not found.');
}

$filePath = realpath($backupDir . '/' . $fileName);

// Validate path is strictly within storage/backups/
if (!$filePath || !str_starts_with($filePath, $backupDir) || !file_exists($filePath)) {
    http_response_code(404);
    die('Error: Requested backup file does not exist or has expired.');
}

$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentType = match ($extension) {
    'sql' => 'text/plain; charset=utf-8',
    'json' => 'application/json',
    'zip' => 'application/zip',
    default => 'application/octet-stream'
};

header('Content-Description: File Transfer');
header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// Clear output buffers and stream file cleanly
if (ob_get_level()) {
    ob_end_clean();
}
readfile($filePath);
exit;
