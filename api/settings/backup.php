<?php
// api/settings/backup.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/controllers/BackupController.php';

$controller = new BackupController();
$controller->runBackup();
