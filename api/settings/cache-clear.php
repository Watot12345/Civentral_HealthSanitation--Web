<?php
// api/settings/cache-clear.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/Controllers/SettingsController.php';

$controller = new SettingsController();
$controller->clearCache();
