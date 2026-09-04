#!/usr/bin/env php
<?php
// bin/scheduler.php
// Civentral Health & Sanitation Management Information System
// Automated Scheduled Job Runner / Background Task Executor

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden: This script can only be executed via the PHP Command Line Interface (CLI).\n";
    exit(1);
}

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/SchedulerService.php';

// Parse command line arguments
$options = getopt('', ['job::', 'triggered-by::', 'silent', 'help']);

if (isset($options['help'])) {
    echo "===============================================================\n";
    echo " Civentral Health & Sanitation MIS - Background Job Executor   \n";
    echo "===============================================================\n";
    echo "Usage:\n";
    echo "  php bin/scheduler.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --job=<name>          Job to execute (all, permit_renewals, surveillance_thresholds, scheduled_reports, system_maintenance). Default: all\n";
    echo "  --triggered-by=<src>  Execution source label (e.g., cron, cli, github_action). Default: cli\n";
    echo "  --silent              Suppress verbose console output\n";
    echo "  --help                Display this help screen\n\n";
    echo "Crontab Example:\n";
    echo "  * * * * * php " . __FILE__ . " --triggered-by=cron >> /path/to/storage/logs/scheduler.log 2>&1\n";
    echo "===============================================================\n";
    exit(0);
}

$job = strtolower(trim($options['job'] ?? 'all'));
$triggeredBy = trim($options['triggered-by'] ?? 'cli');
$isSilent = isset($options['silent']);

if (!$isSilent) {
    echo "[" . date('Y-m-d H:i:s') . "] Starting Civentral Background Scheduler (Job: {$job}, Source: {$triggeredBy})...\n";
}

$service = new SchedulerService();

if ($job === 'all') {
    $result = $service->runAll($triggeredBy);
} else {
    $result = $service->runJob($job, $triggeredBy);
}

if (!$isSilent) {
    echo "[" . date('Y-m-d H:i:s') . "] Execution Completed: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
}

$isSuccess = ($result['success'] ?? false) || (($result['status'] ?? '') === 'success');
exit($isSuccess ? 0 : 1);
