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
// 1. PHP BACKEND - Fetch Data
// ============================================================
require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Enforce RBAC Page Authorization
requirePermission('settings.manage');

// ============================================================
// SYSTEM CONFIGURATION
// ============================================================
$systemConfig = [
    'general' => [
        'system_name' => 'Health & Sanitation Management System',
        'system_version' => '2.1.0',
        'timezone' => 'Asia/Manila',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'language' => 'English',
        'maintenance_mode' => false,
    ],
    'security' => [
        'session_timeout' => 3600,
        'max_login_attempts' => 5,
        'password_expiry' => 90,
        'two_factor_auth' => false,
        'ip_whitelist' => ['192.168.1.1', '192.168.1.100', '10.0.0.1'],
        'allowed_ips' => '192.168.1.*, 10.0.0.*',
        'ssl_enforced' => true,
        'audit_logging' => true,
    ],
    'performance' => [
        'cache_enabled' => true,
        'cache_duration' => 3600,
        'log_retention_days' => 30,
        'backup_retention_days' => 90,
        'max_upload_size' => 50,
        'allowed_file_types' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'jpeg'],
    ],
];

// ============================================================
// MODULE SETTINGS
// ============================================================
$moduleSettings = [
    'health_center' => [
        'name' => 'Health Center Services',
        'icon' => 'fa-hospital',
        'enabled' => true,
        'settings' => [
            'enable_online_appointments' => true,
            'max_appointments_per_day' => 50,
            'enable_telemedicine' => true,
            'require_vital_signs' => true,
            'enable_prescriptions' => true,
            'enable_referrals' => true,
            'consultation_duration' => 30,
            'default_doctor' => 'Dr. Maria Reyes',
        ]
    ],
    'sanitation' => [
        'name' => 'Sanitation Permits',
        'icon' => 'fa-clipboard-check',
        'enabled' => true,
        'settings' => [
            'auto_inspection_reminders' => true,
            'permit_validity_days' => 365,
            'enable_online_applications' => true,
            'require_health_certificate' => true,
            'inspection_frequency' => 90,
            'enable_payment_gateway' => true,
            'allow_digital_submission' => true,
            'auto_renewal_reminder_days' => 30,
        ]
    ],
    'immunization' => [
        'name' => 'Immunization & Nutrition',
        'icon' => 'fa-syringe',
        'enabled' => true,
        'settings' => [
            'enable_vaccine_tracking' => true,
            'enable_growth_monitoring' => true,
            'vaccine_inventory_alert' => 50,
            'enable_reminders' => true,
            'reminder_days_prior' => 7,
            'enable_import_export' => true,
            'auto_generate_certificates' => true,
            'enable_nutrition_assessment' => true,
        ]
    ],
    'wastewater' => [
        'name' => 'Wastewater Services',
        'icon' => 'fa-droplet',
        'enabled' => true,
        'settings' => [
            'enable_service_requests' => true,
            'auto_schedule_maintenance' => true,
            'maintenance_interval_days' => 180,
            'enable_billing' => true,
            'require_tank_inspection' => true,
            'allow_online_requests' => true,
            'enable_provider_management' => true,
            'auto_generate_reports' => true,
        ]
    ],
    'surveillance' => [
        'name' => 'Health Surveillance',
        'icon' => 'fa-binoculars',
        'enabled' => true,
        'settings' => [
            'enable_real_time_monitoring' => true,
            'outbreak_threshold' => 10,
            'auto_alert_generation' => true,
            'enable_contact_tracing' => true,
            'enable_mapping' => true,
            'data_retention_days' => 365,
            'enable_pattern_recognition' => true,
            'auto_generate_reports' => true,
        ]
    ],
];

// ============================================================
// NOTIFICATION SETTINGS
// ============================================================
$notificationSettings = [
    'email' => [
        'enabled' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => 'notifications@caloocan.gov.ph',
        'sender_email' => 'no-reply@caloocan.gov.ph',
        'sender_name' => 'Caloocan City Health Office',
        'test_email' => 'admin@caloocan.gov.ph',
    ],
    'sms' => [
        'enabled' => false,
        'api_provider' => 'Twilio',
        'api_key' => '••••••••••••••••',
        'api_secret' => '••••••••••••••••',
        'sender_id' => 'CALOOCAN',
        'test_number' => '+639123456789',
    ],
    'in_app' => [
        'enabled' => true,
        'enable_sound' => true,
        'enable_popups' => true,
        'enable_badge' => true,
        'alert_retention_days' => 30,
        'max_alerts_displayed' => 50,
    ],
    'alert_triggers' => [
        'outbreak_detected' => true,
        'threshold_exceeded' => true,
        'system_error' => true,
        'permit_expiring' => true,
        'vaccine_low_stock' => true,
        'patient_followup' => true,
        'appointment_reminder' => true,
        'emergency_response' => true,
    ],
];

// ============================================================
// BACKUP & RECOVERY
// ============================================================
$backupSettings = [
    'database' => [
        'auto_backup_enabled' => true,
        'backup_frequency' => 'daily',
        'backup_time' => '02:00',
        'retention_days' => 30,
        'backup_location' => '/var/backups/hsms/',
        'last_backup' => '2024-01-20 02:00:00',
        'backup_size' => '245.6 MB',
        'encrypt_backups' => true,
    ],
    'files' => [
        'auto_backup_enabled' => true,
        'backup_frequency' => 'weekly',
        'backup_time' => '03:00',
        'retention_days' => 90,
        'backup_location' => '/var/backups/hsms/files/',
        'last_backup' => '2024-01-19 03:00:00',
        'backup_size' => '1.2 GB',
        'include_uploads' => true,
    ],
    'recovery' => [
        'enable_point_in_time_recovery' => true,
        'enable_auto_restore' => false,
        'restore_testing' => true,
        'disaster_recovery_plan' => true,
        'recovery_contact' => 'it-admin@caloocan.gov.ph',
        'recovery_phone' => '+63 912 345 6789',
    ],
];

// ============================================================
// STATISTICS
// ============================================================
$totalModules = count($moduleSettings);
$enabledModules = count(array_filter($moduleSettings, function($m) { return $m['enabled']; }));

$title = 'Settings';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Settings</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-gear"></i> Configuration
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">System configuration, module settings, notifications & backup management</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="saveSettings()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-save text-xs"></i> Save All Settings
            </button>
            <button onclick="refreshData()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-sync-alt text-xs"></i> Refresh
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- KPI CARDS - Settings Overview                             -->
    <!-- ============================================================ -->
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
                        <p class="text-2xl font-black text-slate-900">v<?php echo $systemConfig['general']['system_version']; ?></p>
                        <p class="text-xs font-medium text-slate-500">System Version</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Up to date</span>
                    <span class="text-[10px] text-slate-400">Released 2024</span>
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

        <!-- Card 4: Backup Status -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-red-200">
                        <i class="fa-solid fa-database text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $backupSettings['database']['last_backup'] ? '✅' : '⚠️'; ?></p>
                        <p class="text-xs font-medium text-slate-500">Backup Status</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">
                        <?php echo date('M d, Y', strtotime($backupSettings['database']['last_backup'])); ?>
                    </span>
                    <span class="text-[10px] text-slate-400"><?php echo $backupSettings['database']['backup_size']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB NAVIGATION                                             -->
    <!-- ============================================================ -->
    <div class="flex gap-2 mb-6 border-b border-slate-200 overflow-x-auto">
        <button onclick="switchSettingTab('system')" class="setting-tab-btn active px-4 py-2.5 text-sm font-semibold border-b-2 border-brand-dark text-brand-dark transition whitespace-nowrap" id="tab-system">
            <i class="fa-solid fa-gear"></i> System Configuration
        </button>
        <button onclick="switchSettingTab('modules')" class="setting-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition whitespace-nowrap" id="tab-modules">
            <i class="fa-solid fa-puzzle-piece"></i> Module Settings
        </button>
        <button onclick="switchSettingTab('notifications')" class="setting-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition whitespace-nowrap" id="tab-notifications">
            <i class="fa-solid fa-bell"></i> Notification Settings
        </button>
        <button onclick="switchSettingTab('backup')" class="setting-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 transition whitespace-nowrap" id="tab-backup">
            <i class="fa-solid fa-database"></i> Backup & Recovery
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- TAB CONTENT: SYSTEM CONFIGURATION                          -->
    <!-- ============================================================ -->
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
                        <input type="text" value="<?php echo $systemConfig['general']['system_name']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">System Version</label>
                        <input type="text" value="<?php echo $systemConfig['general']['system_version']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 text-slate-500 outline-none" readonly>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Timezone</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Asia/Manila" selected>Asia/Manila (GMT+8)</option>
                            <option value="Asia/Singapore">Asia/Singapore (GMT+8)</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Language</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="English" selected>English</option>
                            <option value="Filipino">Filipino</option>
                            <option value="Cebuano">Cebuano</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Maintenance Mode</label>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="maintenance" value="false" <?php echo !$systemConfig['general']['maintenance_mode'] ? 'checked' : ''; ?> class="text-brand-dark focus:ring-brand-medium"> Off
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="maintenance" value="true" <?php echo $systemConfig['general']['maintenance_mode'] ? 'checked' : ''; ?> class="text-brand-dark focus:ring-brand-medium"> On
                            </label>
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
                            <input type="number" value="<?php echo $systemConfig['security']['session_timeout']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Max Login Attempts</label>
                            <input type="number" value="<?php echo $systemConfig['security']['max_login_attempts']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Allowed IPs</label>
                            <input type="text" value="<?php echo $systemConfig['security']['allowed_ips']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Security Options</label>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" <?php echo $systemConfig['security']['ssl_enforced'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                                    Enforce SSL
                                </label>
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" <?php echo $systemConfig['security']['audit_logging'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                                    Enable Audit Logging
                                </label>
                                <label class="flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" <?php echo $systemConfig['security']['two_factor_auth'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
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
                            <input type="number" value="<?php echo $systemConfig['performance']['cache_duration']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Log Retention (days)</label>
                            <input type="number" value="<?php echo $systemConfig['performance']['log_retention_days']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Max Upload Size (MB)</label>
                            <input type="number" value="<?php echo $systemConfig['performance']['max_upload_size']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" <?php echo $systemConfig['performance']['cache_enabled'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                                Enable Caching
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB CONTENT: MODULE SETTINGS                              -->
    <!-- ============================================================ -->
    <div id="modulesContent" class="setting-tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($moduleSettings as $key => $module): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between <?php echo $module['enabled'] ? 'bg-gradient-to-r from-brand-light/50 to-white' : 'bg-gradient-to-r from-slate-50/50 to-white'; ?>">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 <?php echo $module['enabled'] ? 'bg-brand-light' : 'bg-slate-100'; ?> rounded-lg flex items-center justify-center <?php echo $module['enabled'] ? 'text-brand-dark' : 'text-slate-400'; ?>">
                            <i class="fa-solid <?php echo $module['icon']; ?>"></i>
                        </div>
                        <h3 class="font-semibold text-slate-800"><?php echo $module['name']; ?></h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 <?php echo $module['enabled'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?> rounded-full text-[10px] font-bold">
                            <?php echo $module['enabled'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" <?php echo $module['enabled'] ? 'checked' : ''; ?>>
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
                                <input type="checkbox" class="sr-only peer" <?php echo $value ? 'checked' : ''; ?>>
                                <div class="w-8 h-4 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-brand-dark"></div>
                            </label>
                        <?php elseif (is_numeric($value)): ?>
                            <input type="number" value="<?php echo $value; ?>" class="w-20 px-2 py-1 border border-slate-200 rounded text-sm text-right focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <?php else: ?>
                            <span class="text-sm font-medium text-slate-700"><?php echo $value; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB CONTENT: NOTIFICATION SETTINGS                        -->
    <!-- ============================================================ -->
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
                            <input type="checkbox" class="sr-only peer" <?php echo $notificationSettings['email']['enabled'] ? 'checked' : ''; ?>>
                            <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                        </label>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">SMTP Host</label>
                        <input type="text" value="<?php echo $notificationSettings['email']['smtp_host']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">SMTP Port</label>
                        <input type="number" value="<?php echo $notificationSettings['email']['smtp_port']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Encryption</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="tls" <?php echo $notificationSettings['email']['smtp_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo $notificationSettings['email']['smtp_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="none" <?php echo $notificationSettings['email']['smtp_encryption'] == 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Sender Email</label>
                        <input type="email" value="<?php echo $notificationSettings['email']['sender_email']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Test Email</label>
                        <div class="flex gap-2">
                            <input type="email" value="<?php echo $notificationSettings['email']['test_email']; ?>" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <button onclick="sendTestEmail()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
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
                                <input type="checkbox" class="sr-only peer" <?php echo $notificationSettings['sms']['enabled'] ? 'checked' : ''; ?>>
                                <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                            </label>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">API Provider</label>
                            <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <option value="Twilio" <?php echo $notificationSettings['sms']['api_provider'] == 'Twilio' ? 'selected' : ''; ?>>Twilio</option>
                                <option value="MessageBird" <?php echo $notificationSettings['sms']['api_provider'] == 'MessageBird' ? 'selected' : ''; ?>>MessageBird</option>
                                <option value="Semaphore" <?php echo $notificationSettings['sms']['api_provider'] == 'Semaphore' ? 'selected' : ''; ?>>Semaphore</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Sender ID</label>
                            <input type="text" value="<?php echo $notificationSettings['sms']['sender_id']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Test Number</label>
                            <div class="flex gap-2">
                                <input type="tel" value="<?php echo $notificationSettings['sms']['test_number']; ?>" class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <button onclick="sendTestSms()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
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
                                <input type="checkbox" class="sr-only peer" <?php echo $notificationSettings['in_app']['enabled'] ? 'checked' : ''; ?>>
                                <div class="w-9 h-5 bg-slate-200 peer-focus:ring-2 peer-focus:ring-brand-medium/40 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-dark"></div>
                            </label>
                        </div>
                    </div>
                    <div class="p-4 space-y-3">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $notificationSettings['in_app']['enable_sound'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Sound Notifications
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $notificationSettings['in_app']['enable_popups'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Popup Notifications
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $notificationSettings['in_app']['enable_badge'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Badge Notifications
                        </label>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Alert Retention (days)</label>
                            <input type="number" value="<?php echo $notificationSettings['in_app']['alert_retention_days']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
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
                        <input type="checkbox" <?php echo $enabled ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                        <span class="text-sm text-slate-700"><?php echo ucwords(str_replace('_', ' ', $trigger)); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- TAB CONTENT: BACKUP & RECOVERY                           -->
    <!-- ============================================================ -->
    <div id="backupContent" class="setting-tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Database Backup -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 bg-gradient-to-r from-blue-50/50 to-white">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                            <i class="fa-solid fa-database text-blue-500"></i>
                            Database Backup
                        </h3>
                        <button onclick="runBackup('database')" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold">
                            <i class="fa-solid fa-play"></i> Run Now
                        </button>
                    </div>
                </div>
                <div class="p-4 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Auto Backup</label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $backupSettings['database']['auto_backup_enabled'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable Automatic Backups
                        </label>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Frequency</label>
                        <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="daily" <?php echo $backupSettings['database']['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo $backupSettings['database']['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo $backupSettings['database']['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Retention (days)</label>
                        <input type="number" value="<?php echo $backupSettings['database']['retention_days']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div class="bg-slate-50 rounded-lg p-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Last Backup</span>
                            <span class="font-medium text-slate-700"><?php echo date('M d, Y h:i A', strtotime($backupSettings['database']['last_backup'])); ?></span>
                        </div>
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-slate-500">Backup Size</span>
                            <span class="font-medium text-slate-700"><?php echo $backupSettings['database']['backup_size']; ?></span>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" <?php echo $backupSettings['database']['encrypt_backups'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
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
                                File Backup
                            </h3>
                            <button onclick="runBackup('files')" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold">
                                <i class="fa-solid fa-play"></i> Run Now
                            </button>
                        </div>
                    </div>
                    <div class="p-4 space-y-4">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $backupSettings['files']['auto_backup_enabled'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Enable File Backups
                        </label>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Frequency</label>
                            <select class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <option value="daily" <?php echo $backupSettings['files']['backup_frequency'] == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="weekly" <?php echo $backupSettings['files']['backup_frequency'] == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="monthly" <?php echo $backupSettings['files']['backup_frequency'] == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Retention (days)</label>
                            <input type="number" value="<?php echo $backupSettings['files']['retention_days']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div class="bg-slate-50 rounded-lg p-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Last Backup</span>
                                <span class="font-medium text-slate-700"><?php echo date('M d, Y h:i A', strtotime($backupSettings['files']['last_backup'])); ?></span>
                            </div>
                            <div class="flex justify-between text-sm mt-1">
                                <span class="text-slate-500">Backup Size</span>
                                <span class="font-medium text-slate-700"><?php echo $backupSettings['files']['backup_size']; ?></span>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $backupSettings['files']['include_uploads'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
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
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $backupSettings['recovery']['enable_point_in_time_recovery'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Point-in-Time Recovery
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $backupSettings['recovery']['enable_auto_restore'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Auto-Restore on Failure
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" <?php echo $backupSettings['recovery']['restore_testing'] ? 'checked' : ''; ?> class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium">
                            Regular Restore Testing
                        </label>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recovery Contact</label>
                            <input type="email" value="<?php echo $backupSettings['recovery']['recovery_contact']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Recovery Phone</label>
                            <input type="tel" value="<?php echo $backupSettings['recovery']['recovery_phone']; ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                    </div>
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
    // ============================================================
    // SETTINGS TAB SWITCHING
    // ============================================================
    function switchSettingTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.setting-tab-btn').forEach(btn => {
            btn.classList.remove('active', 'border-brand-dark', 'text-brand-dark');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        
        const tabBtn = document.getElementById('tab-' + tab);
        tabBtn.classList.add('active', 'border-brand-dark', 'text-brand-dark');
        tabBtn.classList.remove('border-transparent', 'text-slate-500');
        
        // Show/hide content
        document.querySelectorAll('.setting-tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        if (tab === 'system') {
            document.getElementById('systemContent').classList.remove('hidden');
        } else if (tab === 'modules') {
            document.getElementById('modulesContent').classList.remove('hidden');
        } else if (tab === 'notifications') {
            document.getElementById('notificationsContent').classList.remove('hidden');
        } else if (tab === 'backup') {
            document.getElementById('backupContent').classList.remove('hidden');
        }
    }

    // ============================================================
    // SAVE SETTINGS
    // ============================================================
    function saveSettings() {
        showToast('💾 Saving all settings...', 'info');
        setTimeout(() => {
            showToast('✅ All settings saved successfully!', 'success');
        }, 1500);
    }

    // ============================================================
    // SEND TEST EMAIL
    // ============================================================
    function sendTestEmail() {
        showToast('📧 Sending test email...', 'info');
        setTimeout(() => {
            showToast('✅ Test email sent successfully!', 'success');
        }, 1500);
    }

    // ============================================================
    // SEND TEST SMS
    // ============================================================
    function sendTestSms() {
        showToast('📱 Sending test SMS...', 'info');
        setTimeout(() => {
            showToast('✅ Test SMS sent successfully!', 'success');
        }, 1500);
    }

    // ============================================================
    // RUN BACKUP
    // ============================================================
    function runBackup(type) {
        const typeName = type === 'database' ? 'Database' : 'File';
        showToast('🔄 Running ' + typeName + ' backup...', 'info');
        setTimeout(() => {
            showToast('✅ ' + typeName + ' backup completed successfully!', 'success');
        }, 2000);
    }

    // ============================================================
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('🔄 Refreshing settings...', 'info');
        setTimeout(() => {
            showToast('✅ Settings refreshed!', 'success');
        }, 1000);
    }

    // ============================================================
    // TOAST
    // ============================================================
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
        t.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.add('hidden'), 3000);
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