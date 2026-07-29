-- Migration: 2026_07_29_create_enterprise_settings_tables.sql
-- Description: Enterprise Settings Module Schema for Health & Sanitation MIS

BEGIN;

-- 1. SETTING CATEGORIES
CREATE TABLE IF NOT EXISTS setting_categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fa-gear',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. SYSTEM SETTINGS
CREATE TABLE IF NOT EXISTS system_settings (
    id SERIAL PRIMARY KEY,
    category_id INT REFERENCES setting_categories(id) ON DELETE CASCADE,
    key VARCHAR(150) NOT NULL UNIQUE,
    value TEXT,
    data_type VARCHAR(50) NOT NULL DEFAULT 'string', -- string, integer, float, boolean, json, array, encrypted
    validation_rules TEXT,
    description TEXT,
    is_encrypted BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    default_value TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_system_settings_key ON system_settings(key);
CREATE INDEX IF NOT EXISTS idx_system_settings_category ON system_settings(category_id);

-- 3. FEATURE FLAGS
CREATE TABLE IF NOT EXISTS feature_flags (
    id SERIAL PRIMARY KEY,
    key VARCHAR(150) NOT NULL UNIQUE,
    flag_name VARCHAR(150) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_feature_flags_key ON feature_flags(key);

-- 4. SETTINGS VERSIONS
CREATE TABLE IF NOT EXISTS settings_versions (
    id SERIAL PRIMARY KEY,
    version_number INT NOT NULL,
    snapshot_json JSONB NOT NULL,
    changes_summary TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 5. BACKUP HISTORY
CREATE TABLE IF NOT EXISTS backup_history (
    id SERIAL PRIMARY KEY,
    backup_type VARCHAR(50) NOT NULL, -- database, files, full
    file_name VARCHAR(255) NOT NULL,
    file_size VARCHAR(50),
    status VARCHAR(50) NOT NULL DEFAULT 'completed', -- pending, running, completed, failed
    error_message TEXT,
    started_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP WITH TIME ZONE,
    created_by VARCHAR(100) DEFAULT 'System'
);

-- SEED DATA
INSERT INTO setting_categories (name, description, icon, display_order) VALUES
('general', 'General System Configuration', 'fa-sliders', 1),
('security', 'Security Policies and Authentication', 'fa-shield', 2),
('performance', 'Performance, Caching and Upload Limits', 'fa-gauge-high', 3),
('modules', 'Application Module Status and Configurations', 'fa-puzzle-piece', 4),
('notifications', 'Email, SMS and In-App Notifications', 'fa-bell', 5),
('backup', 'Database and File Backup Schedules', 'fa-database', 6),
('maintenance', 'System Maintenance Controls', 'fa-wrench', 7),
('ai', 'AI Integration Services (Gemini, OpenAI)', 'fa-brain', 8)
ON CONFLICT (name) DO NOTHING;

-- Seed System Settings
INSERT INTO system_settings (category_id, key, value, data_type, validation_rules, description, is_encrypted, is_editable, default_value) VALUES
((SELECT id FROM setting_categories WHERE name='general'), 'general.system_name', 'Health & Sanitation Management System', 'string', 'required|min:3|max:100', 'Official System Title', FALSE, TRUE, 'Health & Sanitation Management System'),
((SELECT id FROM setting_categories WHERE name='general'), 'general.system_version', '2.1.0', 'string', 'required', 'Current Software Build', FALSE, FALSE, '2.1.0'),
((SELECT id FROM setting_categories WHERE name='general'), 'general.timezone', 'Asia/Manila', 'timezone', 'required|timezone', 'Default Application Timezone', FALSE, TRUE, 'Asia/Manila'),
((SELECT id FROM setting_categories WHERE name='general'), 'general.date_format', 'Y-m-d', 'string', 'required', 'Standard Date Format', FALSE, TRUE, 'Y-m-d'),
((SELECT id FROM setting_categories WHERE name='general'), 'general.time_format', 'H:i:s', 'string', 'required', 'Standard Time Format', FALSE, TRUE, 'H:i:s'),
((SELECT id FROM setting_categories WHERE name='general'), 'general.language', 'English', 'enum:English,Filipino,Cebuano', 'required', 'Default UI Language', FALSE, TRUE, 'English'),

((SELECT id FROM setting_categories WHERE name='security'), 'security.session_timeout', '3600', 'integer', 'required|min:300|max:86400', 'User Session Lifespan in seconds', FALSE, TRUE, '3600'),
((SELECT id FROM setting_categories WHERE name='security'), 'security.max_login_attempts', '5', 'integer', 'required|min:1|max:20', 'Max failed login attempts before lockout', FALSE, TRUE, '5'),
((SELECT id FROM setting_categories WHERE name='security'), 'security.password_expiry', '90', 'integer', 'required|min:0|max:365', 'Days before password renewal required', FALSE, TRUE, '90'),
((SELECT id FROM setting_categories WHERE name='security'), 'security.two_factor_auth', 'false', 'boolean', 'boolean', 'Enforce 2FA for administrative accounts', FALSE, TRUE, 'false'),
((SELECT id FROM setting_categories WHERE name='security'), 'security.ssl_enforced', 'true', 'boolean', 'boolean', 'Require HTTPS connections', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='security'), 'security.audit_logging', 'true', 'boolean', 'boolean', 'Record detailed audit logs', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='security'), 'security.allowed_ips', '192.168.1.*, 10.0.0.*', 'string', 'nullable', 'Whitelisted IP patterns', FALSE, TRUE, '192.168.1.*, 10.0.0.*'),

((SELECT id FROM setting_categories WHERE name='performance'), 'performance.cache_enabled', 'true', 'boolean', 'boolean', 'Enable multi-tier settings caching', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='performance'), 'performance.cache_duration', '3600', 'integer', 'required|min:60|max:86400', 'Cache TTL in seconds', FALSE, TRUE, '3600'),
((SELECT id FROM setting_categories WHERE name='performance'), 'performance.log_retention_days', '30', 'integer', 'required|min:1|max:365', 'Days to store system logs', FALSE, TRUE, '30'),
((SELECT id FROM setting_categories WHERE name='performance'), 'performance.max_upload_size', '50', 'integer', 'required|min:1|max:500', 'Maximum file upload size in MB', FALSE, TRUE, '50'),

((SELECT id FROM setting_categories WHERE name='modules'), 'modules.health_center.enabled', 'true', 'boolean', 'boolean', 'Enable Health Center Services module', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.health_center.enable_online_appointments', 'true', 'boolean', 'boolean', 'Allow citizen online booking', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.health_center.max_appointments_per_day', '50', 'integer', 'min:1|max:1000', 'Daily appointment slot limit', FALSE, TRUE, '50'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.health_center.consultation_duration', '30', 'integer', 'min:5|max:120', 'Default appointment duration in minutes', FALSE, TRUE, '30'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.sanitation.enabled', 'true', 'boolean', 'boolean', 'Enable Sanitation Permits module', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.sanitation.permit_validity_days', '365', 'integer', 'min:30|max:1095', 'Permit validity duration', FALSE, TRUE, '365'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.immunization.enabled', 'true', 'boolean', 'boolean', 'Enable Immunization & Nutrition module', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.wastewater.enabled', 'true', 'boolean', 'boolean', 'Enable Wastewater Services module', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='modules'), 'modules.surveillance.enabled', 'true', 'boolean', 'boolean', 'Enable Health Surveillance module', FALSE, TRUE, 'true'),

((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.email.enabled', 'true', 'boolean', 'boolean', 'Master toggle for email dispatches', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.email.smtp_host', 'smtp.gmail.com', 'string', 'required', 'SMTP Server Hostname', FALSE, TRUE, 'smtp.gmail.com'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.email.smtp_port', '587', 'integer', 'required|min:1|max:65535', 'SMTP Port', FALSE, TRUE, '587'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.email.smtp_encryption', 'tls', 'enum:tls,ssl,none', 'required', 'SMTP Transport Encryption', FALSE, TRUE, 'tls'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.email.sender_email', 'no-reply@caloocan.gov.ph', 'email', 'required|email', 'Sender From Address', FALSE, TRUE, 'no-reply@caloocan.gov.ph'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.email.sender_name', 'Caloocan City Health Office', 'string', 'required', 'Sender Display Name', FALSE, TRUE, 'Caloocan City Health Office'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.email.test_email', 'admin@caloocan.gov.ph', 'email', 'required|email', 'Default test recipient', FALSE, TRUE, 'admin@caloocan.gov.ph'),

((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.sms.enabled', 'false', 'boolean', 'boolean', 'Master toggle for SMS dispatches', FALSE, TRUE, 'false'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.sms.api_provider', 'Twilio', 'enum:Twilio,MessageBird,Semaphore', 'required', 'SMS Gateway Provider', FALSE, TRUE, 'Twilio'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.sms.sender_id', 'CALOOCAN', 'string', 'required', 'SMS Sender ID', FALSE, TRUE, 'CALOOCAN'),
((SELECT id FROM setting_categories WHERE name='notifications'), 'notifications.sms.test_number', '+639123456789', 'string', 'required', 'Default SMS test recipient', FALSE, TRUE, '+639123456789'),

((SELECT id FROM setting_categories WHERE name='backup'), 'backup.database.auto_backup_enabled', 'true', 'boolean', 'boolean', 'Enable automated database backups', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='backup'), 'backup.database.backup_frequency', 'daily', 'enum:daily,weekly,monthly', 'required', 'Database backup interval', FALSE, TRUE, 'daily'),
((SELECT id FROM setting_categories WHERE name='backup'), 'backup.database.retention_days', '30', 'integer', 'required|min:1|max:365', 'Backup file retention threshold in days', FALSE, TRUE, '30'),
((SELECT id FROM setting_categories WHERE name='backup'), 'backup.database.encrypt_backups', 'true', 'boolean', 'boolean', 'Encrypt output backup archives', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='backup'), 'backup.files.auto_backup_enabled', 'true', 'boolean', 'boolean', 'Enable file asset backups', FALSE, TRUE, 'true'),
((SELECT id FROM setting_categories WHERE name='backup'), 'backup.files.backup_frequency', 'weekly', 'enum:daily,weekly,monthly', 'required', 'File backup interval', FALSE, TRUE, 'weekly'),
((SELECT id FROM setting_categories WHERE name='backup'), 'backup.files.retention_days', '90', 'integer', 'required|min:1|max:365', 'File backup retention in days', FALSE, TRUE, '90'),
((SELECT id FROM setting_categories WHERE name='backup'), 'backup.files.include_uploads', 'true', 'boolean', 'boolean', 'Include uploaded document assets', FALSE, TRUE, 'true'),

((SELECT id FROM setting_categories WHERE name='maintenance'), 'maintenance.mode', 'false', 'boolean', 'boolean', 'System Maintenance Mode', FALSE, TRUE, 'false')
ON CONFLICT (key) DO NOTHING;

-- Seed Feature Flags
INSERT INTO feature_flags (key, flag_name, enabled, description) VALUES
('maintenance.mode', 'Maintenance Mode', FALSE, 'Lock application for public access during updates'),
('dashboard.realtime', 'Realtime Dashboard', TRUE, 'Enable WebSocket/Polling live counters on administrative dashboard'),
('sync.offline', 'Offline Sync Capabilities', TRUE, 'Enable service worker caching and offline queue'),
('ai.analytics', 'AI Health Analytics', TRUE, 'Enable predictive health epidemic AI models'),
('ai.gemini', 'Google Gemini Integration', TRUE, 'Enable Gemini AI automated clinical summary engine'),
('notifications.email', 'Email Notification Engine', TRUE, 'Dispatch automated system emails'),
('notifications.sms', 'SMS Gateway Engine', FALSE, 'Dispatch automated SMS alerts')
ON CONFLICT (key) DO NOTHING;

COMMIT;
