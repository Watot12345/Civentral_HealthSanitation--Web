<?php
// api/settings/test-sms.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/Controllers/NotificationController.php';

$controller = new NotificationController();
$controller->testSms();
