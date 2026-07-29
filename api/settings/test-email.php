<?php
// api/settings/test-email.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/controllers/NotificationController.php';

$controller = new NotificationController();
$controller->testEmail();
