<?php
// api/settings/maintenance.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/helpers/Settings.php';
require_once __DIR__ . '/../../Core/Response.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true) ?? $_POST;

$mode = isset($data['maintenance']) ? filter_var($data['maintenance'], FILTER_VALIDATE_BOOLEAN) : false;

Settings::set('maintenance.mode', $mode ? 'true' : 'false');

Response::success('Maintenance mode ' . ($mode ? 'activated' : 'deactivated') . '.', [
    'maintenance_mode' => $mode
]);
