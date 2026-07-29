<?php
// api/settings/cache-rebuild.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/helpers/Settings.php';

Settings::reload();
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Settings cache rebuilt and warmed successfully.']);
exit;
