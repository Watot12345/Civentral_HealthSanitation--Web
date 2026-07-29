<?php
// api/settings/feature-toggle.php

require_once __DIR__ . '/../../config/paths.php';
requirePermission('settings.manage');

require_once __DIR__ . '/../../app/repositories/FeatureRepository.php';
require_once __DIR__ . '/../../Core/Response.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true) ?? $_POST;

$key = $data['key'] ?? null;
$enabled = isset($data['enabled']) ? filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN) : true;

if (empty($key)) {
    Response::error('Feature key is required.', 400);
}

$featureRepo = new \App\Repositories\FeatureRepository();
$featureRepo->setFlag($key, $enabled);

Response::success("Feature flag [{$key}] set to " . ($enabled ? 'enabled' : 'disabled') . '.', [
    'key' => $key,
    'enabled' => $enabled
]);
