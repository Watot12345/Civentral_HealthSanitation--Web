<?php
// app/services/SettingsService.php

namespace App\Services;

require_once __DIR__ . '/../cache/CacheManager.php';
require_once __DIR__ . '/../encryption/EncryptionManager.php';
require_once __DIR__ . '/../validators/SettingsValidator.php';
require_once __DIR__ . '/../repositories/SettingsRepository.php';
require_once __DIR__ . '/../repositories/AuditRepository.php';
require_once __DIR__ . '/../repositories/FeatureRepository.php';
require_once __DIR__ . '/../exceptions/SettingsException.php';
require_once __DIR__ . '/../exceptions/ValidationException.php';

use Throwable;

class SettingsService
{
    private static ?SettingsService $instance = null;
    private static ?array $settingsMap = null;

    private \App\Cache\CacheManager $cache;
    private \App\Encryption\EncryptionManager $encryption;
    private \App\Validators\SettingsValidator $validator;
    private \App\Repositories\SettingsRepository $repository;
    private \App\Repositories\AuditRepository $auditRepository;
    private \App\Repositories\FeatureRepository $featureRepository;

    private array $sensitiveKeys = [
        'notifications.email.smtp_password',
        'notifications.sms.api_key',
        'notifications.sms.api_secret',
        'ai.gemini.api_key',
        'ai.openai.api_key',
        'api.jwt_secret',
        'api.oauth_secret',
        'database.password',
        'webhooks.secret',
    ];

    private array $defaultSeedValues = [
        'general.system_name' => 'Health & Sanitation Management System',
        'general.system_version' => '2.1.0',
        'general.timezone' => 'Asia/Manila',
        'general.date_format' => 'Y-m-d',
        'general.time_format' => 'H:i:s',
        'general.language' => 'English',
        'maintenance.mode' => false,

        'security.session_timeout' => 3600,
        'security.max_login_attempts' => 5,
        'security.password_expiry' => 90,
        'security.two_factor_auth' => false,
        'security.allowed_ips' => '192.168.1.*, 10.0.0.*',
        'security.ssl_enforced' => true,
        'security.audit_logging' => true,

        'performance.cache_enabled' => true,
        'performance.cache_duration' => 3600,
        'performance.log_retention_days' => 30,
        'performance.max_upload_size' => 50,

        'modules.health_center.enabled' => true,
        'modules.health_center.enable_online_appointments' => true,
        'modules.health_center.max_appointments_per_day' => 50,
        'modules.health_center.consultation_duration' => 30,
        'modules.health_center.enable_telemedicine' => true,
        'modules.health_center.require_vital_signs' => true,
        'modules.health_center.enable_prescriptions' => true,
        'modules.health_center.enable_referrals' => true,
        'modules.health_center.default_doctor' => 'Dr. Maria Reyes',

        'modules.sanitation.enabled' => true,
        'modules.sanitation.permit_validity_days' => 365,
        'modules.sanitation.auto_inspection_reminders' => true,
        'modules.sanitation.enable_online_applications' => true,
        'modules.sanitation.require_health_certificate' => true,
        'modules.sanitation.inspection_frequency' => 90,
        'modules.sanitation.enable_payment_gateway' => true,
        'modules.sanitation.allow_digital_submission' => true,
        'modules.sanitation.auto_renewal_reminder_days' => 30,

        'modules.immunization.enabled' => true,
        'modules.immunization.enable_vaccine_tracking' => true,
        'modules.immunization.enable_growth_monitoring' => true,
        'modules.immunization.vaccine_inventory_alert' => 50,
        'modules.immunization.enable_reminders' => true,
        'modules.immunization.reminder_days_prior' => 7,
        'modules.immunization.enable_import_export' => true,
        'modules.immunization.auto_generate_certificates' => true,
        'modules.immunization.enable_nutrition_assessment' => true,

        'modules.wastewater.enabled' => true,
        'modules.wastewater.enable_service_requests' => true,
        'modules.wastewater.auto_schedule_maintenance' => true,
        'modules.wastewater.maintenance_interval_days' => 180,
        'modules.wastewater.enable_billing' => true,
        'modules.wastewater.require_tank_inspection' => true,
        'modules.wastewater.allow_online_requests' => true,
        'modules.wastewater.enable_provider_management' => true,
        'modules.wastewater.auto_generate_reports' => true,

        'modules.surveillance.enabled' => true,
        'modules.surveillance.enable_real_time_monitoring' => true,
        'modules.surveillance.outbreak_threshold' => 10,
        'modules.surveillance.auto_alert_generation' => true,
        'modules.surveillance.enable_contact_tracing' => true,
        'modules.surveillance.enable_mapping' => true,
        'modules.surveillance.data_retention_days' => 365,
        'modules.surveillance.enable_pattern_recognition' => true,
        'modules.surveillance.auto_generate_reports' => true,

        'notifications.email.enabled' => true,
        'notifications.email.smtp_host' => 'smtp.gmail.com',
        'notifications.email.smtp_port' => 587,
        'notifications.email.smtp_encryption' => 'tls',
        'notifications.email.sender_email' => 'no-reply@caloocan.gov.ph',
        'notifications.email.sender_name' => 'Caloocan City Health Office',
        'notifications.email.test_email' => 'admin@caloocan.gov.ph',

        'notifications.sms.enabled' => false,
        'notifications.sms.api_provider' => 'Twilio',
        'notifications.sms.sender_id' => 'CALOOCAN',
        'notifications.sms.test_number' => '+639123456789',

        'notifications.in_app.enabled' => true,
        'notifications.in_app.enable_sound' => true,
        'notifications.in_app.enable_popups' => true,
        'notifications.in_app.enable_badge' => true,
        'notifications.in_app.alert_retention_days' => 30,
        'notifications.in_app.max_alerts_displayed' => 50,

        'notifications.alert_triggers.outbreak_detected' => true,
        'notifications.alert_triggers.threshold_exceeded' => true,
        'notifications.alert_triggers.system_error' => true,
        'notifications.alert_triggers.permit_expiring' => true,
        'notifications.alert_triggers.vaccine_low_stock' => true,
        'notifications.alert_triggers.patient_followup' => true,
        'notifications.alert_triggers.appointment_reminder' => true,
        'notifications.alert_triggers.emergency_response' => true,

        'backup.database.auto_backup_enabled' => true,
        'backup.database.backup_frequency' => 'daily',
        'backup.database.backup_time' => '02:00',
        'backup.database.retention_days' => 30,
        'backup.database.backup_location' => '/var/backups/hsms/',
        'backup.database.last_backup' => '2026-01-20 02:00:00',
        'backup.database.backup_size' => '245.6 MB',
        'backup.database.encrypt_backups' => true,

        'backup.files.auto_backup_enabled' => true,
        'backup.files.backup_frequency' => 'weekly',
        'backup.files.backup_time' => '03:00',
        'backup.files.retention_days' => 90,
        'backup.files.backup_location' => '/var/backups/hsms/files/',
        'backup.files.last_backup' => '2026-01-19 03:00:00',
        'backup.files.backup_size' => '1.2 GB',
        'backup.files.include_uploads' => true,

        'backup.recovery.enable_point_in_time_recovery' => true,
        'backup.recovery.enable_auto_restore' => false,
        'backup.recovery.restore_testing' => true,
        'backup.recovery.disaster_recovery_plan' => true,
        'backup.recovery.recovery_contact' => 'it-admin@caloocan.gov.ph',
        'backup.recovery.recovery_phone' => '+63 912 345 6789',
    ];

    public function __construct()
    {
        $this->cache = new \App\Cache\CacheManager();
        $this->encryption = new \App\Encryption\EncryptionManager();
        $this->validator = new \App\Validators\SettingsValidator();
        $this->repository = new \App\Repositories\SettingsRepository();
        $this->auditRepository = new \App\Repositories\AuditRepository();
        $this->featureRepository = new \App\Repositories\FeatureRepository();
    }

    public static function getInstance(): SettingsService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * High-performance single-query bulk map loader with instant default fallback
     */
    private function loadMap(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && self::$settingsMap !== null) {
            return self::$settingsMap;
        }

        if (!$forceRefresh) {
            $cached = $this->cache->get('all_settings_dictionary');
            if (is_array($cached) && !empty($cached)) {
                self::$settingsMap = $cached;
                return self::$settingsMap;
            }
        }

        $map = $this->defaultSeedValues;

        try {
            // 1 SINGLE HTTP DB query for ALL settings
            $allSettings = $this->repository->all();
            if (!empty($allSettings)) {
                foreach ($allSettings as $item) {
                    $key = $item['key'];
                    $val = $item['value'] ?? $item['default_value'];

                    if (!empty($item['is_encrypted']) || $this->isSensitiveKey($key)) {
                        $val = $this->encryption->decrypt((string)$val);
                    }

                    $map[$key] = $this->castValue($val, $key, $item['data_type'] ?? null);
                }
            }
        } catch (Throwable $e) {
            error_log('SettingsService::loadMap DB fetch fallback: ' . $e->getMessage());
        }

        self::$settingsMap = $map;
        $this->cache->set('all_settings_dictionary', $map, 86400);

        return self::$settingsMap;
    }

    /**
     * Get setting value by key with instant memory/cache lookup (< 1ms)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check Feature Flag lookup if prefixed
        if (str_starts_with($key, 'feature.')) {
            $flagKey = substr($key, 8);
            return $this->featureRepository->isEnabled($flagKey, (bool)$default);
        }

        $map = $this->loadMap();

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        return $default;
    }

    /**
     * Set a single setting key
     */
    public function set(string $key, mixed $value, array $meta = [], ?array $userContext = []): bool
    {
        $existing = $this->get($key, null);

        // Auto-encrypt sensitive values
        if ($this->isSensitiveKey($key) || !empty($meta['is_encrypted'])) {
            $meta['is_encrypted'] = true;
            $dbValue = $this->encryption->encrypt((string)$value);
        } else {
            $dbValue = $value;
        }

        // Save to DB
        $success = false;
        try {
            $success = $this->repository->saveKey($key, $dbValue, $meta);
        } catch (Throwable $e) {
            error_log("SettingsService::set error: " . $e->getMessage());
        }

        $this->auditRepository->logChange('Setting Updated', $key, $existing, $value, $userContext);
        
        // Always update local memory and cache dictionary instantly
        if (self::$settingsMap === null) {
            self::$settingsMap = $this->defaultSeedValues;
        }
        self::$settingsMap[$key] = $this->castValue($value, $key, $meta['data_type'] ?? null);
        $this->cache->set('all_settings_dictionary', self::$settingsMap, 86400);

        return true;
    }

    /**
     * Bulk update settings key-value payload
     */
    public function bulkUpdate(array $settings, ?array $userContext = []): bool
    {
        $allDbSettings = [];
        try {
            $allDbSettings = $this->repository->all();
        } catch (Throwable $e) {
            // Ignore if DB not migrated yet
        }

        $rulesMap = [];
        $existingMap = [];

        foreach ($allDbSettings as $item) {
            $existingMap[$item['key']] = $item;
            if (!empty($item['validation_rules'])) {
                $rulesMap[$item['key']] = $item['validation_rules'];
            }
        }

        // Validate payload
        $errors = [];
        foreach ($settings as $key => $val) {
            if (isset($rulesMap[$key])) {
                $fieldErrors = $this->validator->validateField($key, $val, $rulesMap[$key]);
                if (!empty($fieldErrors)) {
                    $errors[$key] = $fieldErrors;
                }
            }
        }

        if (!empty($errors)) {
            throw new \App\Exceptions\ValidationException($errors, 'Validation failed for one or more settings.');
        }

        // Process updates
        $snapshot = [];
        foreach ($settings as $key => $val) {
            $meta = [];
            if (isset($existingMap[$key])) {
                $meta = [
                    'data_type' => $existingMap[$key]['data_type'],
                    'is_encrypted' => $existingMap[$key]['is_encrypted'],
                    'is_editable' => $existingMap[$key]['is_editable'],
                    'validation_rules' => $existingMap[$key]['validation_rules'],
                ];
            }

            // Encrypt if sensitive
            $dbValue = $val;
            if ($this->isSensitiveKey($key) || !empty($meta['is_encrypted'])) {
                $meta['is_encrypted'] = true;
                $dbValue = $this->encryption->encrypt((string)$val);
            }

            try {
                $this->repository->saveKey($key, $dbValue, $meta);
            } catch (Throwable $e) {
                // Ignore DB error if table pending
            }

            $snapshot[$key] = $val;

            if (str_ends_with($key, '.enabled') || $key === 'maintenance.mode') {
                $flagKey = str_replace(['modules.', '.enabled'], '', $key);
                $this->featureRepository->setFlag($flagKey, filter_var($val, FILTER_VALIDATE_BOOLEAN));
            }
        }

        // Create Version Snapshot
        $createdBy = $userContext['username'] ?? $_SESSION['full_name'] ?? 'System Admin';
        try {
            $this->repository->createVersionSnapshot($snapshot, $createdBy, 'Bulk update of ' . count($settings) . ' settings.');
        } catch (Throwable $e) {
            // Ignore
        }

        // Re-warm single dictionary cache
        self::$settingsMap = array_merge($this->loadMap(), $snapshot);
        $this->cache->set('all_settings_dictionary', self::$settingsMap, 86400);

        return true;
    }

    /**
     * Delete setting key
     */
    public function delete(string $key): bool
    {
        try {
            $this->repository->deleteKey($key);
        } catch (Throwable $e) {
            // Ignore
        }
        $this->clearCache();
        $this->loadMap(true);
        return true;
    }

    /**
     * Clear all settings cache
     */
    public function clearCache(): bool
    {
        self::$settingsMap = null;
        $this->cache->delete('all_settings_dictionary');
        return $this->cache->clear();
    }

    /**
     * Reload settings into cache (Warmup)
     */
    public function reload(): bool
    {
        $this->clearCache();
        $this->loadMap(true);
        return true;
    }

    /**
     * Reset category or all settings to seed defaults
     */
    public function reset(?string $category = null): bool
    {
        foreach ($this->defaultSeedValues as $key => $defaultVal) {
            if ($category === null || str_starts_with($key, $category . '.')) {
                $this->set($key, $defaultVal);
            }
        }
        $this->clearCache();
        $this->loadMap(true);
        return true;
    }

    /**
     * Export all settings as JSON
     */
    public function export(): string
    {
        $map = $this->loadMap();
        $exportData = [];
        foreach ($map as $key => $val) {
            if ($this->isSensitiveKey($key)) {
                continue;
            }
            $exportData[$key] = $val;
        }
        return json_encode([
            'exported_at' => date('Y-m-d H:i:s'),
            'system_version' => $this->get('general.system_version', '2.1.0'),
            'settings' => $exportData,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Import settings from JSON string
     */
    public function import(string $jsonContent, ?array $userContext = []): bool
    {
        $data = json_decode($jsonContent, true);
        if (!is_array($data) || empty($data['settings']) || !is_array($data['settings'])) {
            throw new \App\Exceptions\SettingsException('Invalid settings export file structure.');
        }

        return $this->bulkUpdate($data['settings'], $userContext);
    }

    public function encrypt(string $value): string
    {
        return $this->encryption->encrypt($value);
    }

    public function decrypt(string $value): string
    {
        return $this->encryption->decrypt($value);
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array($key, $this->sensitiveKeys, true) || preg_match('/(password|secret|api_key)/i', $key);
    }

    private function castValue(mixed $value, string $key, ?string $dataType = null): mixed
    {
        if ($value === null) return null;

        if ($dataType === 'boolean' || is_bool($value) || $value === 'true' || $value === 'false') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        if ($dataType === 'integer' || (is_string($value) && ctype_digit($value))) {
            return (int)$value;
        }
        if ($dataType === 'float' || $dataType === 'numeric') {
            return (float)$value;
        }
        if ($dataType === 'json' || $dataType === 'array') {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                return $decoded ?? $value;
            }
        }
        return (string)$value;
    }
}
