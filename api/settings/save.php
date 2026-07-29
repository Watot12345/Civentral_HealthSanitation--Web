<?php
// api/settings/save.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/controllers/SettingsController.php';

$controller = new SettingsController();
$controller->save();
