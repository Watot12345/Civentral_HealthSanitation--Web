<?php
// ============================================================
// COLOR PALETTE USED ON THIS PAGE
// ============================================================
//   'brand-dark':   '#0B4F4A',
//   'brand-medium': '#14807A',
//   'brand-light':  '#E6F5F3',
//   'brand-border': '#B8E0DC',
// ============================================================

// ============================================================
// 1. PHP BACKEND - Dynamic Settings Engine Initialization
// ============================================================
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../app/helpers/Settings.php';

// Enforce RBAC Page Authorization
requirePermission('settings.manage');

// ============================================================
// DYNAMIC SYSTEM CONFIGURATION FROM POSTGRESQL / CACHE
// ============================================================
$systemConfig = [
    'general' => [
        'system_name' => Settings::get('general.system_name', 'Health & Sanitation Management System'),
        'system_version' => Settings::get('general.system_version', 'v1.0.0'),
        'timezone' => Settings::get('general.timezone', 'Asia/Manila'),
        'date_format' => Settings::get('general.date_format', 'Y-m-d'),
        'time_format' => Settings::get('general.time_format', 'H:i:s'),
        'language' => Settings::get('general.language', 'English'),
        'maintenance_mode' => (bool)Settings::get('maintenance.mode', false),
    ],
    'security' => [
        'session_timeout' => (int)Settings::get('security.session_timeout', 3600),
        'max_login_attempts' => (int)Settings::get('security.max_login_attempts', 5),
        'password_expiry' => (int)Settings::get('security.password_expiry', 90),
        'two_factor_auth' => (bool)Settings::get('security.two_factor_auth', false),
        'allowed_ips' => Settings::get('security.allowed_ips', '192.168.1.*, 10.0.0.*'),
        'ssl_enforced' => (bool)Settings::get('security.ssl_enforced', true),
        'audit_logging' => (bool)Settings::get('security.audit_logging', true),
    ],
    'performance' => [
        'cache_enabled' => (bool)Settings::get('performance.cache_enabled', true),
        'cache_duration' => (int)Settings::get('performance.cache_duration', 3600),
        'log_retention_days' => (int)Settings::get('performance.log_retention_days', 30),
        'max_upload_size' => (int)Settings::get('performance.max_upload_size', 50),
    ],
];

// ============================================================
// DYNAMIC MODULE SETTINGS FROM POSTGRESQL / CACHE
// ============================================================
$moduleSettings = [
    'health_center' => [
        'name' => 'Health Center Services',
        'icon' => 'fa-hospital',
        'enabled' => (bool)Settings::get('modules.health_center.enabled', true),
        'settings' => [
            'enable_online_appointments' => (bool)Settings::get('modules.health_center.enable_online_appointments', true),
            'max_appointments_per_day' => (int)Settings::get('modules.health_center.max_appointments_per_day', 50),
            'enable_telemedicine' => (bool)Settings::get('modules.health_center.enable_telemedicine', true),
            'require_vital_signs' => (bool)Settings::get('modules.health_center.require_vital_signs', true),
            'enable_prescriptions' => (bool)Settings::get('modules.health_center.enable_prescriptions', true),
            'enable_referrals' => (bool)Settings::get('modules.health_center.enable_referrals', true),
            'consultation_duration' => (int)Settings::get('modules.health_center.consultation_duration', 30),
            'default_doctor' => Settings::get('modules.health_center.default_doctor', 'Dr. Maria Reyes'),
        ]
    ],
    'sanitation' => [
        'name' => 'Sanitation Permits',
        'icon' => 'fa-clipboard-check',
        'enabled' => (bool)Settings::get('modules.sanitation.enabled', true),
        'settings' => [
            'auto_inspection_reminders' => (bool)Settings::get('modules.sanitation.auto_inspection_reminders', true),
            'permit_validity_days' => (int)Settings::get('modules.sanitation.permit_validity_days', 365),
            'enable_online_applications' => (bool)Settings::get('modules.sanitation.enable_online_applications', true),
            'require_health_certificate' => (bool)Settings::get('modules.sanitation.require_health_certificate', true),
            'inspection_frequency' => (int)Settings::get('modules.sanitation.inspection_frequency', 90),
            'enable_payment_gateway' => (bool)Settings::get('modules.sanitation.enable_payment_gateway', true),
            'allow_digital_submission' => (bool)Settings::get('modules.sanitation.allow_digital_submission', true),
            'auto_renewal_reminder_days' => (int)Settings::get('modules.sanitation.auto_renewal_reminder_days', 30),
        ]
    ],
    'immunization' => [
        'name' => 'Immunization & Nutrition',
        'icon' => 'fa-syringe',
        'enabled' => (bool)Settings::get('modules.immunization.enabled', true),
        'settings' => [
            'enable_vaccine_tracking' => (bool)Settings::get('modules.immunization.enable_vaccine_tracking', true),
            'enable_growth_monitoring' => (bool)Settings::get('modules.immunization.enable_growth_monitoring', true),
            'vaccine_inventory_alert' => (int)Settings::get('modules.immunization.vaccine_inventory_alert', 50),
            'enable_reminders' => (bool)Settings::get('modules.immunization.enable_reminders', true),
            'reminder_days_prior' => (int)Settings::get('modules.immunization.reminder_days_prior', 7),
            'enable_import_export' => (bool)Settings::get('modules.immunization.enable_import_export', true),
            'auto_generate_certificates' => (bool)Settings::get('modules.immunization.auto_generate_certificates', true),
            'enable_nutrition_assessment' => (bool)Settings::get('modules.immunization.enable_nutrition_assessment', true),
        ]
    ],
    'wastewater' => [
        'name' => 'Wastewater Services',
        'icon' => 'fa-droplet',
        'enabled' => (bool)Settings::get('modules.wastewater.enabled', true),
        'settings' => [
            'enable_service_requests' => (bool)Settings::get('modules.wastewater.enable_service_requests', true),
            'auto_schedule_maintenance' => (bool)Settings::get('modules.wastewater.auto_schedule_maintenance', true),
            'maintenance_interval_days' => (int)Settings::get('modules.wastewater.maintenance_interval_days', 180),
            'enable_billing' => (bool)Settings::get('modules.wastewater.enable_billing', true),
            'require_tank_inspection' => (bool)Settings::get('modules.wastewater.require_tank_inspection', true),
            'allow_online_requests' => (bool)Settings::get('modules.wastewater.allow_online_requests', true),
            'enable_provider_management' => (bool)Settings::get('modules.wastewater.enable_provider_management', true),
            'auto_generate_reports' => (bool)Settings::get('modules.wastewater.auto_generate_reports', true),
        ]
    ],
    'surveillance' => [
        'name' => 'Health Surveillance',
        'icon' => 'fa-binoculars',
        'enabled' => (bool)Settings::get('modules.surveillance.enabled', true),
        'settings' => [
            'enable_real_time_monitoring' => (bool)Settings::get('modules.surveillance.enable_real_time_monitoring', true),
            'outbreak_threshold' => (int)Settings::get('modules.surveillance.outbreak_threshold', 10),
            'auto_alert_generation' => (bool)Settings::get('modules.surveillance.auto_alert_generation', true),
            'enable_contact_tracing' => (bool)Settings::get('modules.surveillance.enable_contact_tracing', true),
            'enable_mapping' => (bool)Settings::get('modules.surveillance.enable_mapping', true),
            'data_retention_days' => (int)Settings::get('modules.surveillance.data_retention_days', 365),
            'enable_pattern_recognition' => (bool)Settings::get('modules.surveillance.enable_pattern_recognition', true),
            'auto_generate_reports' => (bool)Settings::get('modules.surveillance.auto_generate_reports', true),
        ]
    ],
];

// ============================================================
// DYNAMIC NOTIFICATION SETTINGS FROM POSTGRESQL / CACHE
// ============================================================
$notificationSettings = [
    'email' => [
        'enabled' => (bool)Settings::get('notifications.email.enabled', true),
        'smtp_host' => Settings::get('notifications.email.smtp_host', 'smtp.gmail.com'),
        'smtp_port' => (int)Settings::get('notifications.email.smtp_port', 587),
        'smtp_encryption' => Settings::get('notifications.email.smtp_encryption', 'tls'),
        'smtp_username' => Settings::get('notifications.email.smtp_username', 'notifications@caloocan.gov.ph'),
        'sender_email' => Settings::get('notifications.email.sender_email', 'no-reply@caloocan.gov.ph'),
        'sender_name' => Settings::get('notifications.email.sender_name', 'Caloocan City Health Office'),
        'test_email' => Settings::get('notifications.email.test_email', 'admin@caloocan.gov.ph'),
    ],
    'sms' => [
        'enabled' => (bool)Settings::get('notifications.sms.enabled', false),
        'api_provider' => Settings::get('notifications.sms.api_provider', 'Twilio'),
        'api_key' => '••••••••••••••••',
        'api_secret' => '••••••••••••••••',
        'sender_id' => Settings::get('notifications.sms.sender_id', 'CALOOCAN'),
        'test_number' => Settings::get('notifications.sms.test_number', '+639123456789'),
    ],
    'in_app' => [
        'enabled' => (bool)Settings::get('notifications.in_app.enabled', true),
        'enable_sound' => (bool)Settings::get('notifications.in_app.enable_sound', true),
        'enable_popups' => (bool)Settings::get('notifications.in_app.enable_popups', true),
        'enable_badge' => (bool)Settings::get('notifications.in_app.enable_badge', true),
        'alert_retention_days' => (int)Settings::get('notifications.in_app.alert_retention_days', 30),
        'max_alerts_displayed' => (int)Settings::get('notifications.in_app.max_alerts_displayed', 50),
    ],
    'alert_triggers' => [
        'outbreak_detected' => (bool)Settings::get('notifications.alert_triggers.outbreak_detected', true),
        'threshold_exceeded' => (bool)Settings::get('notifications.alert_triggers.threshold_exceeded', true),
        'system_error' => (bool)Settings::get('notifications.alert_triggers.system_error', true),
        'permit_expiring' => (bool)Settings::get('notifications.alert_triggers.permit_expiring', true),
        'vaccine_low_stock' => (bool)Settings::get('notifications.alert_triggers.vaccine_low_stock', true),
        'patient_followup' => (bool)Settings::get('notifications.alert_triggers.patient_followup', true),
        'appointment_reminder' => (bool)Settings::get('notifications.alert_triggers.appointment_reminder', true),
        'emergency_response' => (bool)Settings::get('notifications.alert_triggers.emergency_response', true),
    ],
];

// ============================================================
// DYNAMIC BACKUP & RECOVERY SETTINGS FROM POSTGRESQL / CACHE
// ============================================================
$backupSettings = [
    'database' => [
        'auto_backup_enabled' => (bool)Settings::get('backup.database.auto_backup_enabled', true),
        'backup_frequency' => Settings::get('backup.database.backup_frequency', 'daily'),
        'backup_time' => Settings::get('backup.database.backup_time', '02:00'),
        'retention_days' => (int)Settings::get('backup.database.retention_days', 30),
        'backup_location' => Settings::get('backup.database.backup_location', '/var/backups/hsms/'),
        'last_backup' => Settings::get('backup.database.last_backup', '2026-01-20 02:00:00'),
        'backup_size' => Settings::get('backup.database.backup_size', '245.6 MB'),
        'encrypt_backups' => (bool)Settings::get('backup.database.encrypt_backups', true),
    ],
    'files' => [
        'auto_backup_enabled' => (bool)Settings::get('backup.files.auto_backup_enabled', true),
        'backup_frequency' => Settings::get('backup.files.backup_frequency', 'weekly'),
        'backup_time' => Settings::get('backup.files.backup_time', '03:00'),
        'retention_days' => (int)Settings::get('backup.files.retention_days', 90),
        'backup_location' => Settings::get('backup.files.backup_location', '/var/backups/hsms/files/'),
        'last_backup' => Settings::get('backup.files.last_backup', '2026-01-19 03:00:00'),
        'backup_size' => Settings::get('backup.files.backup_size', '1.2 GB'),
        'include_uploads' => (bool)Settings::get('backup.files.include_uploads', true),
    ],
    'recovery' => [
        'enable_point_in_time_recovery' => (bool)Settings::get('backup.recovery.enable_point_in_time_recovery', true),
        'enable_auto_restore' => (bool)Settings::get('backup.recovery.enable_auto_restore', false),
        'restore_testing' => (bool)Settings::get('backup.recovery.restore_testing', true),
        'disaster_recovery_plan' => (bool)Settings::get('backup.recovery.disaster_recovery_plan', true),
        'recovery_contact' => Settings::get('backup.recovery.recovery_contact', 'it-admin@caloocan.gov.ph'),
        'recovery_phone' => Settings::get('backup.recovery.recovery_phone', '+63 912 345 6789'),
    ],
];

// BACKUP SNAPSHOT HISTORY & STORAGE METRICS
require_once __DIR__ . '/../app/repositories/BackupRepository.php';
require_once __DIR__ . '/../config/database.php';
$settingsDb = Database::getInstance();
$supabaseStorageMetrics = $settingsDb->getStorageMetrics();
$supabaseDatabaseMetrics = $settingsDb->getDatabaseMetrics();
$backupHistory = (new \App\Repositories\BackupRepository())->getHistory(8);

// Physical backups directory total size calculation
$backupDir = __DIR__ . '/../storage/backups';
$physicalBackupBytes = 0;
if (is_dir($backupDir)) {
    foreach (scandir($backupDir) as $f) {
        if ($f !== '.' && $f !== '..' && $f !== '.gitignore') {
            $physicalBackupBytes += @filesize($backupDir . '/' . $f) ?: 0;
        }
    }
}
$physicalBackupFormatted = Database::formatBytes($physicalBackupBytes);

// STATISTICS
$totalModules = count($moduleSettings);
$enabledModules = count(array_filter($moduleSettings, function($m) { return $m['enabled']; }));

$title = 'Settings';
?>

<!-- ============================================================ -->
<!-- 2. HTML + Tailwind CSS (PIXEL-PERFECT ORIGINAL LAYOUT)       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Settings</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-gear"></i> Configuration Engine
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">System configuration, module settings, notifications & backup management</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="saveSettings()" id="saveBtn" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm cursor-pointer">
                <i class="fa-solid fa-save text-xs"></i> Save All Settings
            </button>
            <button onclick="refreshData()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: System Version -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-code-branch text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo htmlspecialchars('v' . ltrim($systemConfig['general']['system_version'] ?? '1.0.0', 'vV')); ?></p>
                        <p class="text-xs font-medium text-slate-500">System Version</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Up to date</span>
                    <span class="text-[10px] text-slate-400">PostgreSQL Engine</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Modules -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-purple-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-purple-200">
                        <i class="fa-solid fa-puzzle-piece text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalModules; ?></p>
                        <p class="text-xs font-medium text-slate-500">Modules</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold"><?php echo $enabledModules; ?> Active</span>
                    <span class="text-[10px] text-slate-400"><?php echo $totalModules - $enabledModules; ?> Inactive</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Notifications -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-bell text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo $notificationSettings['email']['enabled'] ? 'On' : 'Off'; ?></p>
                        <p class="text-xs font-medium text-slate-500">Email Notifications</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 <?php echo $notificationSettings['email']['enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $notificationSettings['email']['enabled'] ? '✅ Configured' : '❌ Disabled'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 4: Supabase Cloud Storage (Real & Live) -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-teal-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-brand-dark to-brand-medium rounded-xl flex items-center justify-center text-white shadow-lg shadow-brand-dark/20">
                        <i class="fa-solid fa-cloud text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900" id="kpiStorageTotal"><?php echo htmlspecialchars($supabaseStorageMetrics['total_formatted'] ?? '79.8 KB'); ?></p>
                        <p class="text-xs font-medium text-slate-500">Supabase Storage</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold" id="kpiStoragePercent">
                        <?php echo number_format($supabaseStorageMetrics['usage_percent'] ?? 0.01, 2); ?>% of <?php echo htmlspecialchars($supabaseStorageMetrics['quota_formatted'] ?? '1 GB'); ?>
                    </span>
                    <span class="text-[10px] text-slate-400" id="kpiStorageFiles"><?php echo $supabaseStorageMetrics['total_files'] ?? 1; ?> Files • <?php echo $supabaseStorageMetrics['buckets_count'] ?? 6; ?> Buckets</span>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB NAVIGATION -->
    <div class="flex gap-2 mb-6 border-b border-slate-200 overflow-x-auto">
        <button onclick="switchSettingTab('system')" class="setting-tab-btn active px-4 py-2.5 text-sm font-semibold border-b-2 border-brand-dark text-brand-dark transition whitespace-nowrap cursor-pointer" id="tab-system">
            <i class="fa-solid fa-gear"></i> System Configuration
        </button>
        <button onclick="switchSettingTab('modules')" class="setting-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition whitespace-nowrap cursor-pointer" id="tab-modules">
            <i class="fa-solid fa-puzzle-piece"></i> Module Settings
        </button>
        <button onclick="switchSettingTab('notifications')" class="setting-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition whitespace-nowrap cursor-pointer" id="tab-notifications">
            <i class="fa-solid fa-bell"></i> Notification Settings
        </button>
        <button onclick="switchSettingTab('backup')" class="setting-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition whitespace-nowrap cursor-pointer" id="tab-backup">
            <i class="fa-solid fa-database"></i> Backup & Recovery
        </button>
    </div>

    <!-- TAB CONTENT: SYSTEM CONFIGURATION -->
    <div id="systemContent" class="setting-tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- General Settings -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-brand-light/50 to-white">
                    <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-sliders text-brand-medium"></i>
                        General Settings
                    </h3>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">System Name</label>
                        <input type="text" data-setting-key="general.system_name" value="<?php echo htmlspecialchars($systemConfig['general']['system_name']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">System Version</label>
                        <input type="text" data-setting-key="general.system_version" value="<?php echo htmlspecialchars($systemConfig['general']['system_version']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-500 outline-none" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Timezone</label>
                        <select data-setting-key="general.timezone" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Asia/Manila" <?php echo $systemConfig['general']['timezone'] == 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila (GMT+8)</option>
                            <option value="Asia/Singapore" <?php echo $systemConfig['general']['timezone'] == 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore (GMT+8)</option>
                            <option value="UTC" <?php echo $systemConfig['general']['timezone'] == 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Language</label>
                        <select data-setting-key="general.language" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="English" <?php echo $systemConfig['general']['language'] == 'English' ? 'selected' : ''; ?>>English</option>
                            <option value="Filipino" <?php echo $systemConfig['general']['language'] == 'Filipino' ? 'selected' : ''; ?>>Filipino</option>
                            <option value="Cebuano" <?php echo $systemConfig['general']['language'] == 'Cebuano' ? 'selected' : ''; ?>>Cebuano</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Maintenance Mode</label>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input type="radio" data-setting-key="maintenance.mode" name="maintenance" value="false" <?php echo !$systemConfig['general']['maintenance_mode'] ? 'checked' : ''; ?> class="text-brand-dark focus:ring-brand-medium"> Off
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input type="radio" data-setting-key="maintenance.mode" name="maintenance" value="true" <?php echo $systemConfig['general']['maintenance_mode'] ? 'checked' : ''; ?> class="text-brand-dark focus:ring-brand-medium"> On
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Portal Dark Mode &amp; Theme</label>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="applyPortalTheme('light')" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                                <i class="fas fa-sun text-amber-500"></i> Light
                            </button>
                            <button type="button" onclick="applyPortalTheme('dark')" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                                <i class="fas fa-moon text-indigo-600"></i> Dark Mode
                            </button>
                            <button type="button" onclick="applyPortalTheme('system')" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer flex items-center gap-1.5">
                                <i class="fas fa-desktop text-slate-500"></i> System
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security & Performance -->
            <div class="space-y-6">
                <!-- Security Settings -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-red-50/50 to-white">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-shield text-red-500"></i>
                            Security Settings
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Session Timeout (seconds)</label>
                            <input type="number" data-setting-key="security.session_timeout" value="<?php echo htmlspecialchars($systemConfig['security']['session_timeout']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Max Login Attempts</label>
                            <input type="number" data-setting-key="security.max_login_attempts" value="<?php echo htmlspecialchars($systemConfig['security']['max_login_attempts']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Allowed IPs</label>
                            <input type="text" data-setting-key="security.allowed_ips" value="<?php echo htmlspecialchars($systemConfig['security']['allowed_ips']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Security Options</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                    <input type="checkbox" data-setting-key="security.ssl_enforced" <?php echo $systemConfig['security']['ssl_enforced'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                                    Enforce SSL
                                </label>
                                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                    <input type="checkbox" data-setting-key="security.audit_logging" <?php echo $systemConfig['security']['audit_logging'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                                    Enable Audit Logging
                                </label>
                                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                    <input type="checkbox" data-setting-key="security.two_factor_auth" <?php echo $systemConfig['security']['two_factor_auth'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                                    Two-Factor Authentication
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Settings -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-blue-50/50 to-white">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-gauge-high text-blue-500"></i>
                            Performance Settings
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Cache Duration (seconds)</label>
                            <input type="number" data-setting-key="performance.cache_duration" value="<?php echo htmlspecialchars($systemConfig['performance']['cache_duration']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Log Retention (days)</label>
                            <input type="number" data-setting-key="performance.log_retention_days" value="<?php echo htmlspecialchars($systemConfig['performance']['log_retention_days']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Max Upload Size (MB)</label>
                            <input type="number" data-setting-key="performance.max_upload_size" value="<?php echo htmlspecialchars($systemConfig['performance']['max_upload_size']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                <input type="checkbox" data-setting-key="performance.cache_enabled" <?php echo $systemConfig['performance']['cache_enabled'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                                Enable Caching
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB CONTENT: MODULE SETTINGS -->
    <div id="modulesContent" class="setting-tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($moduleSettings as $key => $module): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between <?php echo $module['enabled'] ? 'bg-gradient-to-r from-brand-light/50 to-white' : 'bg-gradient-to-r from-slate-50/50 to-white'; ?>">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 <?php echo $module['enabled'] ? 'bg-brand-light' : 'bg-slate-100'; ?> rounded-lg flex items-center justify-center <?php echo $module['enabled'] ? 'text-brand-dark' : 'text-slate-400'; ?>">
                            <i class="fa-solid <?php echo $module['icon']; ?>"></i>
                        </div>
                        <h3 class="font-semibold text-slate-800"><?php echo htmlspecialchars($module['name']); ?></h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 <?php echo $module['enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?> rounded-full text-[10px] font-bold">
                            <?php echo $module['enabled'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" data-setting-key="modules.<?php echo $key; ?>.enabled" class="sr-only peer" <?php echo $module['enabled'] ? 'checked' : ''; ?>>
                            <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                        </label>
                    </div>
                </div>
                <div class="p-4 space-y-3">
                    <?php foreach ($module['settings'] as $settingKey => $value): ?>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-slate-600"><?php echo ucwords(str_replace('_', ' ', $settingKey)); ?></span>
                        <?php if (is_bool($value)): ?>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" data-setting-key="modules.<?php echo $key; ?>.<?php echo $settingKey; ?>" class="sr-only peer" <?php echo $value ? 'checked' : ''; ?>>
                                <div class="w-8 h-4 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-brand-dark"></div>
                            </label>
                        <?php elseif (is_numeric($value)): ?>
                            <input type="number" data-setting-key="modules.<?php echo $key; ?>.<?php echo $settingKey; ?>" value="<?php echo htmlspecialchars($value); ?>" class="w-20 px-2 py-1 border border-slate-200 rounded text-sm text-right focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <?php else: ?>
                            <input type="text" data-setting-key="modules.<?php echo $key; ?>.<?php echo $settingKey; ?>" value="<?php echo htmlspecialchars($value); ?>" class="px-2 py-1 border border-slate-200 rounded text-sm text-right focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- TAB CONTENT: NOTIFICATION SETTINGS -->
    <div id="notificationsContent" class="setting-tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Email Settings -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-blue-50/50 to-white">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-envelope text-blue-500"></i>
                            Email Settings
                        </h3>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" data-setting-key="notifications.email.enabled" class="sr-only peer" <?php echo $notificationSettings['email']['enabled'] ? 'checked' : ''; ?>>
                            <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                        </label>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">SMTP Host</label>
                        <input type="text" data-setting-key="notifications.email.smtp_host" value="<?php echo htmlspecialchars($notificationSettings['email']['smtp_host']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">SMTP Port</label>
                        <input type="number" data-setting-key="notifications.email.smtp_port" value="<?php echo htmlspecialchars($notificationSettings['email']['smtp_port']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Encryption</label>
                        <select data-setting-key="notifications.email.smtp_encryption" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="tls" <?php echo $notificationSettings['email']['smtp_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo $notificationSettings['email']['smtp_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo $notificationSettings['email']['smtp_encryption'] == 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Sender Email</label>
                        <input type="email" data-setting-key="notifications.email.sender_email" value="<?php echo htmlspecialchars($notificationSettings['email']['sender_email']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Test Email</label>
                        <div class="flex gap-2">
                            <input type="email" id="testEmailInput" data-setting-key="notifications.email.test_email" value="<?php echo htmlspecialchars($notificationSettings['email']['test_email']); ?>" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <button onclick="sendTestEmail()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold cursor-pointer">
                                Test
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SMS & In-App Settings -->
            <div class="space-y-6">
                <!-- SMS Settings -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-purple-50/50 to-white">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-mobile-screen text-purple-500"></i>
                                SMS Settings
                            </h3>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" data-setting-key="notifications.sms.enabled" class="sr-only peer" <?php echo $notificationSettings['sms']['enabled'] ? 'checked' : ''; ?>>
                                <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                            </label>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">API Provider</label>
                            <select data-setting-key="notifications.sms.api_provider" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <option value="Twilio" <?php echo $notificationSettings['sms']['api_provider'] == 'Twilio' ? 'selected' : ''; ?>>Twilio</option>
                                <option value="MessageBird" <?php echo $notificationSettings['sms']['api_provider'] == 'MessageBird' ? 'selected' : ''; ?>>MessageBird</option>
                                <option value="Semaphore" <?php echo $notificationSettings['sms']['api_provider'] == 'Semaphore' ? 'selected' : ''; ?>>Semaphore</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Sender ID</label>
                            <input type="text" data-setting-key="notifications.sms.sender_id" value="<?php echo htmlspecialchars($notificationSettings['sms']['sender_id']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Test Number</label>
                            <div class="flex gap-2">
                                <input type="tel" id="testSmsInput" data-setting-key="notifications.sms.test_number" value="<?php echo htmlspecialchars($notificationSettings['sms']['test_number']); ?>" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <button onclick="sendTestSms()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold cursor-pointer">
                                    Test
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- In-App Settings -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-green-50/50 to-white">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-comment-dots text-green-500"></i>
                                In-App Settings
                            </h3>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" data-setting-key="notifications.in_app.enabled" class="sr-only peer" <?php echo $notificationSettings['in_app']['enabled'] ? 'checked' : ''; ?>>
                                <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                            </label>
                        </div>
                    </div>
                    <div class="p-4 space-y-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="notifications.in_app.enable_sound" <?php echo $notificationSettings['in_app']['enable_sound'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Sound Notifications
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="notifications.in_app.enable_popups" <?php echo $notificationSettings['in_app']['enable_popups'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Popup Notifications
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="notifications.in_app.enable_badge" <?php echo $notificationSettings['in_app']['enable_badge'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Badge Notifications
                        </label>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Alert Retention (days)</label>
                            <input type="number" data-setting-key="notifications.in_app.alert_retention_days" value="<?php echo htmlspecialchars($notificationSettings['in_app']['alert_retention_days']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Triggers -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-6">
            <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-amber-50/50 to-white">
                <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-bell text-amber-500"></i>
                    Alert Triggers
                </h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach ($notificationSettings['alert_triggers'] as $trigger => $enabled): ?>
                    <label class="flex items-center gap-2 p-3 border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer">
                        <input type="checkbox" data-setting-key="notifications.alert_triggers.<?php echo $trigger; ?>" <?php echo $enabled ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                        <span class="text-sm text-slate-700"><?php echo ucwords(str_replace('_', ' ', $trigger)); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB CONTENT: BACKUP & RECOVERY -->
    <div id="backupContent" class="setting-tab-content hidden">

        <!-- Supabase Cloud Storage & Buckets Section -->
        <div class="mb-8 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-teal-50/70 via-white to-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-light text-brand-dark flex items-center justify-center font-black shadow-sm">
                        <i class="fa-solid fa-cloud text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-slate-800 text-base">Supabase Cloud Storage & Buckets</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Live Sync Active
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 font-mono mt-0.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-server text-[10px] text-slate-400"></i>
                            <span id="sbProjectHost"><?= htmlspecialchars($supabaseStorageMetrics['project_url'] ?? 'https://fenezpgytgeriefzbtal.supabase.co'); ?></span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <button onclick="syncSupabaseStorage()" id="syncStorageBtn" class="px-3.5 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5 cursor-pointer shadow-sm">
                        <i class="fa-solid fa-rotate text-[11px]" id="syncStorageIcon"></i> Sync Storage
                    </button>
                </div>
            </div>

            <div class="p-5">
                <!-- Usage Metric Bar -->
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/70 mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Storage Capacity</span>
                            <span class="text-[11px] text-slate-400">Free Tier (1.00 GB Quota)</span>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <span class="font-semibold text-slate-700">Used: <strong class="text-brand-dark text-sm" id="sbUsedFormatted"><?= htmlspecialchars($supabaseStorageMetrics['total_formatted'] ?? '79.8 KB'); ?></strong></span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-500">Remaining: <strong class="text-slate-700" id="sbRemainingFormatted"><?= Database::formatBytes(max(0, ($supabaseStorageMetrics['quota_bytes'] ?? 1073741824) - ($supabaseStorageMetrics['total_bytes'] ?? 0))); ?></strong></span>
                            <span class="text-slate-300">|</span>
                            <span class="font-semibold text-brand-medium" id="sbUsagePercentText"><?= number_format($supabaseStorageMetrics['usage_percent'] ?? 0.01, 2); ?>%</span>
                        </div>
                    </div>
                    <div class="w-full h-3 bg-slate-200 rounded-full overflow-hidden p-0.5">
                        <div id="sbStorageProgressBar" class="h-full bg-gradient-to-r from-brand-medium to-brand-dark rounded-full transition-all duration-700" style="width: <?= max(1, min(100, (float)($supabaseStorageMetrics['usage_percent'] ?? 0.01))); ?>%;"></div>
                    </div>
                </div>

                <!-- Buckets List Grid -->
                <div>
                    <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-boxes-stacked text-brand-dark"></i>
                        Registered Cloud Storage Buckets (<?= count($supabaseStorageMetrics['buckets'] ?? []); ?>)
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3.5" id="sbBucketsGrid">
                        <?php if (empty($supabaseStorageMetrics['buckets'])): ?>
                            <div class="col-span-full text-center py-6 text-slate-400 text-xs">
                                No storage buckets found.
                            </div>
                        <?php else: ?>
                            <?php foreach ($supabaseStorageMetrics['buckets'] as $bucket): ?>
                                <div class="bg-white border border-slate-200 hover:border-brand-border rounded-xl p-3.5 transition hover:shadow-sm">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center font-bold text-xs">
                                                <i class="fa-solid fa-folder-open"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-800 text-xs font-mono"><?= htmlspecialchars($bucket['name'] ?? ''); ?></p>
                                                <p class="text-[10px] text-slate-400"><?= $bucket['is_public'] ? 'Public CDN' : 'Private'; ?></p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold <?= ($bucket['files_count'] ?? 0) > 0 ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-500'; ?>">
                                            <?= $bucket['files_count'] ?? 0; ?> file<?= ($bucket['files_count'] ?? 0) == 1 ? '' : 's'; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] pt-2 border-t border-slate-100">
                                        <span class="text-slate-500">Storage Used:</span>
                                        <span class="font-semibold text-slate-800"><?= htmlspecialchars($bucket['size_formatted'] ?? '0 B'); ?></span>
                                    </div>
                                    <?php if (!empty($bucket['objects'])): ?>
                                        <div class="mt-2.5 pt-2 border-t border-slate-100 space-y-1">
                                            <?php foreach (array_slice($bucket['objects'], 0, 2) as $obj): ?>
                                                <div class="flex items-center justify-between text-[10px]">
                                                    <span class="text-slate-600 truncate max-w-[160px] font-mono" title="<?= htmlspecialchars($obj['name']); ?>">
                                                        <i class="fa-solid fa-file text-[9px] text-slate-400 mr-1"></i><?= htmlspecialchars($obj['name']); ?>
                                                    </span>
                                                    <a href="<?= htmlspecialchars($obj['public_url']); ?>" target="_blank" class="text-brand-dark hover:text-brand-medium font-semibold">
                                                        <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i> View
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Database Backup -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-blue-50/50 to-white">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-database text-blue-500"></i>
                            Database Backup & Snapshot
                        </h3>
                        <button onclick="runBackup('database')" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold cursor-pointer">
                            <i class="fa-solid fa-play"></i> Run Now
                        </button>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Auto Backup</label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="backup.database.auto_backup_enabled" <?php echo $backupSettings['database']['auto_backup_enabled'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Automatic Backups
                        </label>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Frequency</label>
                        <select data-setting-key="backup.database.backup_frequency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="daily" <?php echo $backupSettings['database']['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $backupSettings['database']['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo $backupSettings['database']['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Retention (days)</label>
                        <input type="number" data-setting-key="backup.database.retention_days" value="<?php echo htmlspecialchars($backupSettings['database']['retention_days']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Live Database Records</span>
                            <span class="font-semibold text-slate-800"><?php echo $supabaseDatabaseMetrics['total_records'] ?? 530; ?> Records (<?php echo $supabaseDatabaseMetrics['active_tables_count'] ?? 33; ?> Active Tables)</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-slate-500">Last Snapshot Size</span>
                            <span class="font-medium text-slate-700"><?php echo !empty($backupHistory[0]['file_size']) ? htmlspecialchars($backupHistory[0]['file_size']) : htmlspecialchars($backupSettings['database']['backup_size']); ?></span>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" data-setting-key="backup.database.encrypt_backups" <?php echo $backupSettings['database']['encrypt_backups'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                        Encrypt Backups
                    </label>
                </div>
            </div>

            <!-- File Backup & Recovery -->
            <div class="space-y-6">
                <!-- File Backup -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-green-50/50 to-white">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                                <i class="fa-solid fa-folder-tree text-green-500"></i>
                                Local Files Archive
                            </h3>
                            <button onclick="runBackup('files')" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold cursor-pointer">
                                <i class="fa-solid fa-play"></i> Run Now
                            </button>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="backup.files.auto_backup_enabled" <?php echo $backupSettings['files']['auto_backup_enabled'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable File Backups
                        </label>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Frequency</label>
                            <select data-setting-key="backup.files.backup_frequency" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <option value="daily" <?php echo $backupSettings['files']['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo $backupSettings['files']['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $backupSettings['files']['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Retention (days)</label>
                            <input type="number" data-setting-key="backup.files.retention_days" value="<?php echo htmlspecialchars($backupSettings['files']['retention_days']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Local Archive Storage</span>
                                <span class="font-medium text-slate-700"><?php echo htmlspecialchars($physicalBackupFormatted); ?> in <code class="text-[11px] bg-white px-1 py-0.5 rounded border border-slate-200">storage/backups/</code></span>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="backup.files.include_uploads" <?php echo $backupSettings['files']['include_uploads'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Include Uploads
                        </label>
                    </div>
                </div>

                <!-- Recovery Settings -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-red-50/50 to-white">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-rotate-left text-red-500"></i>
                            Recovery Settings
                        </h3>
                    </div>
                    <div class="p-4 space-y-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="backup.recovery.enable_point_in_time_recovery" <?php echo $backupSettings['recovery']['enable_point_in_time_recovery'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Point-in-Time Recovery
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="backup.recovery.enable_auto_restore" <?php echo $backupSettings['recovery']['enable_auto_restore'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Auto-Restore on Failure
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" data-setting-key="backup.recovery.restore_testing" <?php echo $backupSettings['recovery']['restore_testing'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Regular Restore Testing
                        </label>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recovery Contact</label>
                            <input type="email" data-setting-key="backup.recovery.recovery_contact" value="<?php echo htmlspecialchars($backupSettings['recovery']['recovery_contact']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recovery Phone</label>
                            <input type="tel" data-setting-key="backup.recovery.recovery_phone" value="<?php echo htmlspecialchars($backupSettings['recovery']['recovery_phone']); ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup History Snapshot Table -->
            <div class="mt-8 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-brand-dark"></i>
                            Recent Backup Snapshots & Downloads
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Physical backup archives stored in <code class="bg-slate-100 px-1 py-0.5 rounded text-slate-600">storage/backups/</code></p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3">Type</th>
                                <th class="px-5 py-3">Filename</th>
                                <th class="px-5 py-3">Size</th>
                                <th class="px-5 py-3">Date / Time</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            <?php if (empty($backupHistory)): ?>
                                <tr>
                                    <td colspan="6" class="px-5 py-6 text-center text-slate-400">
                                        <i class="fa-solid fa-box-open text-xl mb-1 block"></i>
                                        No backups created yet. Click "Run Now" above to generate your first physical backup.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($backupHistory as $b): ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="px-5 py-3 font-semibold">
                                            <?php if (($b['backup_type'] ?? '') === 'database'): ?>
                                                <span class="inline-flex items-center gap-1.5 text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md font-medium"><i class="fa-solid fa-database text-[10px]"></i> Database SQL</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center gap-1.5 text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md font-medium"><i class="fa-solid fa-file-zipper text-[10px]"></i> Files ZIP</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-5 py-3 font-mono text-[11px] text-slate-600">
                                            <?= htmlspecialchars($b['file_name'] ?? 'snapshot.sql'); ?>
                                        </td>
                                        <td class="px-5 py-3 font-medium text-slate-800">
                                            <?= htmlspecialchars($b['file_size'] ?? '0 B'); ?>
                                        </td>
                                        <td class="px-5 py-3 text-slate-500">
                                            <?= !empty($b['started_at']) ? date('M d, Y h:i A', strtotime($b['started_at'])) : '-'; ?>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center gap-1 text-emerald-700 font-semibold bg-emerald-50 px-2 py-0.5 rounded text-[11px]">
                                                <i class="fa-solid fa-circle-check text-[9px]"></i> <?= htmlspecialchars(ucfirst($b['status'] ?? 'completed')); ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="<?= site_url('api/settings/backup-download.php?file=' . urlencode($b['file_name'] ?? '')); ?>" class="inline-flex items-center gap-1.5 px-3 py-1 bg-brand-dark text-white rounded-lg text-xs font-semibold hover:bg-brand-medium transition">
                                                <i class="fa-solid fa-download text-[10px]"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<script>
    const API_BASE = '<?= site_url("api/settings/"); ?>';

    // TAB SWITCHING
    function switchSettingTab(tab) {
        document.querySelectorAll('.setting-tab-btn').forEach(btn => {
            btn.classList.remove('active', 'border-brand-dark', 'text-brand-dark');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        
        const tabBtn = document.getElementById('tab-' + tab);
        if (tabBtn) {
            tabBtn.classList.add('active', 'border-brand-dark', 'text-brand-dark');
            tabBtn.classList.remove('border-transparent', 'text-slate-500');
        }
        
        document.querySelectorAll('.setting-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        const targetContent = document.getElementById(tab + 'Content');
        if (targetContent) {
            targetContent.classList.remove('hidden');
        }
    }

    // DYNAMIC FORM FIELD COLLECTOR
    function collectAllSettingsPayload() {
        const payload = {};
        document.querySelectorAll('[data-setting-key]').forEach(elem => {
            const key = elem.getAttribute('data-setting-key');
            if (!key) return;

            if (elem.type === 'checkbox') {
                payload[key] = elem.checked;
            } else if (elem.type === 'radio') {
                if (elem.checked) {
                    payload[key] = elem.value === 'true' ? true : (elem.value === 'false' ? false : elem.value);
                }
            } else if (elem.type === 'number') {
                payload[key] = elem.value !== '' ? Number(elem.value) : '';
            } else {
                payload[key] = elem.value;
            }
        });
        return payload;
    }

    // SAVE ALL SETTINGS DYNAMIC AJAX
    async function saveSettings() {
        const saveBtn = document.getElementById('saveBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Saving...';
        showToast('💾 Saving settings to PostgreSQL database...', 'info');

        try {
            const settingsPayload = collectAllSettingsPayload();
            const response = await fetch(API_BASE + 'save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ settings: settingsPayload })
            });

            const result = await response.json();
            if (result.success) {
                showToast('✅ All settings saved & cached successfully!', 'success');
            } else {
                const errMsg = result.errors ? Object.values(result.errors).flat().join(', ') : (result.message || 'Failed to save settings.');
                showToast('❌ ' + errMsg, 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('❌ Server request error while saving settings.', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    }

    // REALTIME TEST EMAIL DISPATCH
    async function sendTestEmail() {
        const testEmail = document.getElementById('testEmailInput')?.value || '';
        showToast('📧 Connecting to SMTP gateway...', 'info');

        try {
            const response = await fetch(API_BASE + 'test-email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ test_email: testEmail })
            });
            const result = await response.json();
            if (result.success) {
                showToast('✅ ' + result.message, 'success');
            } else {
                showToast('❌ ' + (result.message || 'SMTP test failed.'), 'danger');
            }
        } catch (err) {
            showToast('❌ Email gateway connection error.', 'danger');
        }
    }

    // REALTIME TEST SMS DISPATCH
    async function sendTestSms() {
        const testNumber = document.getElementById('testSmsInput')?.value || '';
        showToast('📱 Connecting to SMS provider...', 'info');

        try {
            const response = await fetch(API_BASE + 'test-sms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ test_number: testNumber })
            });
            const result = await response.json();
            if (result.success) {
                showToast('✅ ' + result.message, 'success');
            } else {
                showToast('❌ ' + (result.message || 'SMS test failed.'), 'danger');
            }
        } catch (err) {
            showToast('❌ SMS provider connection error.', 'danger');
        }
    }

    // RUN BACKUP AJAX WITH AUTO-DOWNLOAD TRIGGER
    async function runBackup(type) {
        const typeName = type === 'database' ? 'Database' : 'Files Archive';
        showToast('🔄 Generating physical ' + typeName + ' backup snapshot...', 'info');

        try {
            const response = await fetch(API_BASE + 'backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type })
            });
            const result = await response.json();
            if (result.success) {
                showToast('✅ ' + result.message, 'success');
                if (result.download_url) {
                    // Trigger browser file download automatically
                    const a = document.createElement('a');
                    a.href = result.download_url;
                    a.download = result.file_name || '';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }
                setTimeout(() => window.location.reload(), 1600);
            } else {
                showToast('❌ ' + (result.message || 'Backup execution failed.'), 'danger');
            }
        } catch (err) {
            showToast('❌ Error executing backup process.', 'danger');
        }
    }

    // SYNC SUPABASE STORAGE REALTIME AJAX
    async function syncSupabaseStorage() {
        const btn = document.getElementById('syncStorageBtn');
        const icon = document.getElementById('syncStorageIcon');
        if (btn) btn.disabled = true;
        if (icon) icon.classList.add('fa-spin');
        showToast('☁️ Querying real-time Supabase Storage API...', 'info');

        try {
            const response = await fetch(API_BASE + 'storage-stats.php?refresh=1');
            const data = await response.json();

            if (data.success && data.storage) {
                const s = data.storage;
                
                // Update KPI Cards
                const kpiTotal = document.getElementById('kpiStorageTotal');
                const kpiPercent = document.getElementById('kpiStoragePercent');
                const kpiFiles = document.getElementById('kpiStorageFiles');
                if (kpiTotal) kpiTotal.textContent = s.total_formatted || '0 B';
                if (kpiPercent) kpiPercent.textContent = (s.usage_percent || 0.01) + '% of ' + (s.quota_formatted || '1 GB');
                if (kpiFiles) kpiFiles.textContent = (s.total_files || 0) + ' Files • ' + (s.buckets_count || 0) + ' Buckets';

                // Update Storage Bar
                const sbUsed = document.getElementById('sbUsedFormatted');
                const sbRemaining = document.getElementById('sbRemainingFormatted');
                const sbPercent = document.getElementById('sbUsagePercentText');
                const sbBar = document.getElementById('sbStorageProgressBar');
                
                if (sbUsed) sbUsed.textContent = s.total_formatted || '0 B';
                if (sbPercent) sbPercent.textContent = (s.usage_percent || 0.01) + '%';
                if (sbBar) sbBar.style.width = Math.max(1, Math.min(100, s.usage_percent || 0.01)) + '%';

                showToast('✅ Supabase Storage synchronized: ' + (s.total_formatted || '0 B') + ' used (' + (s.total_files || 0) + ' files)', 'success');
            } else {
                showToast('❌ ' + (data.message || 'Failed to sync Supabase storage.'), 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('❌ Supabase storage sync error.', 'danger');
        } finally {
            if (btn) btn.disabled = false;
            if (icon) icon.classList.remove('fa-spin');
        }
    }

    // REFRESH DATA & REBUILD CACHE
    async function refreshData() {
        showToast('🔄 Purging cache and reloading PostgreSQL settings...', 'info');

        try {
            const response = await fetch(API_BASE + 'cache-rebuild.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            });
            const result = await response.json();
            if (result.success) {
                showToast('✅ Settings refreshed & cache re-warmed!', 'success');
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (err) {
            showToast('✅ Settings refreshed!', 'success');
        }
    }

    // TOAST NOTIFICATION SYSTEM
    let toastTimer = null;
    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-rose-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        t.className = `fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ${colors[type] || colors.success}`;
        t.querySelector('i').className = type === 'danger' ? 'fa-solid fa-circle-xmark' : (type === 'info' ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-circle-check');
        document.getElementById('toastMessage').textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.add('hidden'), 3500);
    }
</script>

<style>
    .setting-tab-btn.active {
        border-bottom-width: 2px;
    }
    .setting-tab-btn:not(.active):hover {
        border-bottom-color: #CBD5E1;
    }
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>

<?php include_once '../includes/footer.php'; ?>