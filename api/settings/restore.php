<?php
// api/settings/restore.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/controllers/BackupController.php';

$controller = new BackupController();
$controller->restore();
