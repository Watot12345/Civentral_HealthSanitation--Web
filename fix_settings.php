<?php
require_once __DIR__ . '/Core/Env.php';
Env::load();
require_once __DIR__ . '/app/repositories/SettingsRepository.php';

$repo = new \App\Repositories\SettingsRepository();

// 1. Session Timeout: min:120
$repo->saveKey('security.session_timeout', 120, ['validation_rules' => 'required|integer|min:120|max:86400']);

// 2. Max Login Attempts: min:1, max:20, default 3
$repo->saveKey('security.max_login_attempts', 3);

// 3. Password Expiry: default 30
$repo->saveKey('security.password_expiry', 30);

// 4. Performance Cache Duration: min:60
$repo->saveKey('performance.cache_duration', 60, ['validation_rules' => 'required|integer|min:60|max:86400']);

// 5. Performance Max Upload Size: 10MB only
$repo->saveKey('performance.max_upload_size', 10, ['validation_rules' => 'required|integer|min:1|max:10']);

// Clear cache so it reloads
require_once __DIR__ . '/app/services/SettingsService.php';
\App\Services\SettingsService::getInstance()->clearCache();

echo "Done\n";
