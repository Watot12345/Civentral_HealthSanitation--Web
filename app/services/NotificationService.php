<?php
// app/services/NotificationService.php

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/SurveillanceAlert.php';
require_once __DIR__ . '/DepartmentResolver.php';
require_once __DIR__ . '/PermissionService.php';

use App\Services\DepartmentResolver;
use App\Services\PermissionService;

class NotificationService
{
    private ?Database $db;

    public function __construct(?Database $db = null)
    {
        try {
            $this->db = $db ?? Database::getInstance();
        } catch (Throwable $e) {
            $this->db = null;
        }
    }

    /**
     * Fetch aggregated live system notifications with RBAC filtering for the active user
     */
    public function getNotifications(int $limit = 12): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // Fast In-Memory / Session Cache (45s TTL)
        $userKey = 'notifs_' . ($_SESSION['user_id'] ?? 'anon') . '_' . md5(($_SESSION['role'] ?? '') . ($_SESSION['department'] ?? ''));
        if (isset($_SESSION['cache_' . $userKey]) && isset($_SESSION['cache_time_' . $userKey])) {
            if (time() - (int)$_SESSION['cache_time_' . $userKey] < 45) {
                return (array)$_SESSION['cache_' . $userKey];
            }
        }

        $notifications = [];

        if (!$this->db) {
            return [];
        }

        // 1. Live Disease Surveillance & Outbreak Alerts (Health Cluster)
        try {
            $survModel = new SurveillanceAlert($this->db);
            $survAlerts = $survModel->all(['limit' => 5]);
            foreach (array_slice($survAlerts, 0, 4) as $idx => $sa) {
                $severity = strtolower($sa['severity'] ?? 'warning');
                $isCritical = $severity === 'critical' || $severity === 'emergency' || $severity === 'high';

                $item = [
                    'id' => 'notif-surv-' . ($sa['id'] ?? ($idx + 1)),
                    'category' => 'surveillance',
                    'title' => ($sa['disease'] ?? 'Disease') . ' Surveillance Alert',
                    'message' => $sa['message'] ?? ("Alert triggered in " . ($sa['barangay'] ?? 'Caloocan')),
                    'time' => 'Live Alert',
                    'url' => site_url('modules/surveillence/alerts.php'),
                    'icon' => 'fas fa-biohazard',
                    'icon_bg' => $isCritical ? 'bg-red-100' : 'bg-amber-100',
                    'icon_color' => $isCritical ? 'text-red-500' : 'text-amber-500',
                    'title_color' => $isCritical ? 'text-red-700' : 'text-amber-700',
                    'badge' => ucfirst($sa['severity'] ?? 'Alert'),
                    'badge_class' => $isCritical ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700',
                    'created_at' => date('Y-m-d H:i:s'),
                    'allowed_departments' => ['Health Surveillance', 'Health Center Services', 'Immunization & Nutrition', 'Administration'],
                    'required_permissions' => ['surveillance.view', 'dashboard.surveillance', 'dashboard.health_center', 'dashboard.immunization', 'patients.view']
                ];

                if ($this->isNotificationAllowed($item)) {
                    $notifications[] = $item;
                }
            }
        } catch (Throwable $e) {
            error_log("NotificationService Surv error: " . $e->getMessage());
        }

        // 2. Pending Clinic Appointments & Consultations (Health Cluster)
        try {
            $apts = $this->db->select('appointments', ['status' => 'pending'], ['limit' => 3, 'order' => 'id.desc']);
            if (is_array($apts)) {
                foreach ($apts as $a) {
                    $item = [
                        'id' => 'notif-apt-' . $a['id'],
                        'category' => 'health_center',
                        'title' => 'Pending Appointment: ' . ($a['service_type'] ?? 'Consultation'),
                        'message' => ($a['notes'] ?? 'Scheduled patient visit awaiting confirmation') . ' (' . ($a['appointment_id'] ?? ('APT-' . $a['id'])) . ')',
                        'time' => $this->formatTimeAgo($a['created_at'] ?? 'now'),
                        'url' => site_url('modules/healthservices/appointments.php'),
                        'icon' => 'fas fa-calendar-check',
                        'icon_bg' => 'bg-blue-100',
                        'icon_color' => 'text-blue-500',
                        'title_color' => 'text-blue-700',
                        'badge' => 'Appointment',
                        'badge_class' => 'bg-blue-100 text-blue-700',
                        'created_at' => $a['created_at'] ?? date('Y-m-d H:i:s'),
                        'allowed_departments' => ['Health Center Services', 'Immunization & Nutrition', 'Health Surveillance', 'Administration'],
                        'required_permissions' => ['patients.view', 'consultations.view', 'triage.view', 'dashboard.health_center']
                    ];

                    if ($this->isNotificationAllowed($item)) {
                        $notifications[] = $item;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("NotificationService APT error: " . $e->getMessage());
        }

        // 3. Child Health & Immunization Records (Health Cluster)
        try {
            $children = $this->db->select('children', [], ['limit' => 2, 'order' => 'id.desc']);
            if (is_array($children)) {
                foreach ($children as $ch) {
                    $cName = trim(($ch['first_name'] ?? '') . ' ' . ($ch['last_name'] ?? ''));
                    $item = [
                        'id' => 'notif-child-' . ($ch['id'] ?? uniqid()),
                        'category' => 'immunization',
                        'title' => 'Child Health Record: ' . ($cName ?: 'Pediatric Patient'),
                        'message' => 'Vaccination & health record registered (' . ($ch['child_id'] ?? 'Child') . ')',
                        'time' => $this->formatTimeAgo($ch['created_at'] ?? 'now'),
                        'url' => site_url('modules/immunization/child_registry.php'),
                        'icon' => 'fas fa-syringe',
                        'icon_bg' => 'bg-emerald-100',
                        'icon_color' => 'text-emerald-600',
                        'title_color' => 'text-emerald-700',
                        'badge' => 'Immunization',
                        'badge_class' => 'bg-emerald-100 text-emerald-700',
                        'created_at' => $ch['created_at'] ?? date('Y-m-d H:i:s'),
                        'allowed_departments' => ['Immunization & Nutrition', 'Health Center Services', 'Health Surveillance', 'Administration'],
                        'required_permissions' => ['immunization.view', 'patients.view', 'dashboard.immunization']
                    ];

                    if ($this->isNotificationAllowed($item)) {
                        $notifications[] = $item;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("NotificationService Child error: " . $e->getMessage());
        }

        // 4. Pending Desludging & Sanitation Service Requests (Sanitation & Wastewater Cluster)
        try {
            $reqs = $this->db->select('service_requests', ['status' => 'pending'], ['limit' => 3, 'order' => 'id.desc']);
            if (is_array($reqs)) {
                foreach ($reqs as $r) {
                    $item = [
                        'id' => 'notif-sr-' . $r['id'],
                        'category' => 'wastewater',
                        'title' => ucfirst($r['service_type'] ?? 'Sanitation') . ' Request Pending',
                        'message' => ($r['owner_name'] ?? 'Applicant') . ' — ' . ($r['barangay'] ?? 'Location') . ' (' . ($r['request_id'] ?? ('SR-' . $r['id'])) . ')',
                        'time' => $this->formatTimeAgo($r['created_at'] ?? 'now'),
                        'url' => site_url('modules/services/service_requests.php'),
                        'icon' => 'fas fa-water',
                        'icon_bg' => 'bg-purple-100',
                        'icon_color' => 'text-purple-500',
                        'title_color' => 'text-purple-700',
                        'badge' => 'Pending',
                        'badge_class' => 'bg-purple-100 text-purple-700',
                        'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s'),
                        'allowed_departments' => ['Wastewater Services', 'Sanitation Permits', 'Administration'],
                        'required_permissions' => ['permits.view', 'inspections.view', 'wastewater.view']
                    ];

                    if ($this->isNotificationAllowed($item)) {
                        $notifications[] = $item;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("NotificationService SR error: " . $e->getMessage());
        }

        // 5. Sanitation Permits & Renewals (Sanitation & Wastewater Cluster)
        try {
            $permits = $this->db->select('permits', ['status' => 'pending'], ['limit' => 2, 'order' => 'id.desc']);
            if (is_array($permits)) {
                foreach ($permits as $p) {
                    $item = [
                        'id' => 'notif-perm-' . $p['id'],
                        'category' => 'sanitation',
                        'title' => 'Sanitation Permit Application',
                        'message' => ($p['business_name'] ?? 'Business') . ' — ' . ($p['business_type'] ?? 'Establishment'),
                        'time' => $this->formatTimeAgo($p['created_at'] ?? 'now'),
                        'url' => site_url('modules/sanitation/permit_applications.php'),
                        'icon' => 'fas fa-file-signature',
                        'icon_bg' => 'bg-rose-100',
                        'icon_color' => 'text-rose-500',
                        'title_color' => 'text-rose-700',
                        'badge' => 'Permit',
                        'badge_class' => 'bg-rose-100 text-rose-700',
                        'created_at' => $p['created_at'] ?? date('Y-m-d H:i:s'),
                        'allowed_departments' => ['Sanitation Permits', 'Wastewater Services', 'Administration'],
                        'required_permissions' => ['permits.view', 'permits.approve', 'inspections.view']
                    ];

                    if ($this->isNotificationAllowed($item)) {
                        $notifications[] = $item;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("NotificationService Permit error: " . $e->getMessage());
        }

        // 6. Recent System Logs / Activity (Admins only)
        try {
            $logs = $this->db->select('activity_logs', [], ['limit' => 2, 'order' => 'id.desc']);
            if (is_array($logs)) {
                foreach ($logs as $l) {
                    $item = [
                        'id' => 'notif-log-' . $l['id'],
                        'category' => 'audit',
                        'title' => ($l['module'] ?? 'System') . ': ' . ($l['action'] ?? 'Activity Recorded'),
                        'message' => ($l['user_name'] ?? 'System') . ' — ' . ($l['details'] ?? 'Operation executed'),
                        'time' => $this->formatTimeAgo($l['created_at'] ?? 'now'),
                        'url' => site_url('management/activity_log.php'),
                        'icon' => 'fas fa-shield-alt',
                        'icon_bg' => 'bg-slate-100',
                        'icon_color' => 'text-slate-600',
                        'title_color' => 'text-slate-700',
                        'badge' => 'Audit',
                        'badge_class' => 'bg-slate-100 text-slate-700',
                        'created_at' => $l['created_at'] ?? date('Y-m-d H:i:s'),
                        'allowed_departments' => ['Administration'],
                        'required_permissions' => ['logs.view', 'settings.manage', 'roles.manage']
                    ];

                    if ($this->isNotificationAllowed($item)) {
                        $notifications[] = $item;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("NotificationService Log error: " . $e->getMessage());
        }

        $userId = (int)($_SESSION['user_id'] ?? 0);
        $readIds = $this->getUserReadIds($userId);

        $result = array_slice($notifications, 0, $limit);
        foreach ($result as &$item) {
            $item['is_read'] = in_array($item['id'], $readIds, true);
        }
        unset($item);

        $_SESSION['cache_' . $userKey] = $result;
        $_SESSION['cache_time_' . $userKey] = time();

        return $result;
    }

    /**
     * Get list of notification IDs already read by this user from user_notification_reads table
     */
    public function getUserReadIds(int $userId): array
    {
        if ($userId <= 0 || !$this->db) {
            return [];
        }

        try {
            $reads = $this->db->select('user_notification_reads', ['user_id' => $userId]);
            if (is_array($reads) && !empty($reads)) {
                return array_values(array_unique(array_filter(array_column($reads, 'notification_id'))));
            }
        } catch (\Throwable $e) {
            // Table may not exist yet if migration hasn't run in Supabase
        }
        return [];
    }

    /**
     * Mark notification(s) as read for a user in the database
     */
    public function markAsRead(int $userId, array|string $notificationIds): bool
    {
        if ($userId <= 0 || !$this->db) {
            return false;
        }

        $ids = is_array($notificationIds) ? $notificationIds : [$notificationIds];
        foreach ($ids as $notifId) {
            $notifId = trim($notifId);
            if (empty($notifId)) continue;
            try {
                $this->db->query('user_notification_reads', 'POST', [
                    'user_id'         => $userId,
                    'notification_id' => $notifId,
                    'is_read'         => true,
                    'read_at'         => date('Y-m-d H:i:sP')
                ]);
            } catch (\Throwable $e) {
                // Ignore if duplicate key or table not created yet
            }
        }

        // Invalidate session notification cache
        $userKey = 'notifs_' . $userId . '_' . md5(($_SESSION['role'] ?? '') . ($_SESSION['department'] ?? ''));
        unset($_SESSION['cache_' . $userKey]);
        unset($_SESSION['cache_time_' . $userKey]);

        return true;
    }

    /**
     * RBAC Gate: Check if a notification is permitted for the logged-in user's role and department
     */
    private function isNotificationAllowed(array $notification): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $userRoleDesc = trim($_SESSION['role_description'] ?? '');
        $userRole = trim($_SESSION['role'] ?? 'employee');

        // 1. Full Admin Access: Administrators receive all alerts
        if ($this->isAdmin($userRoleDesc) || $this->isAdmin($userRole)) {
            return true;
        }

        // 2. Department-based RBAC match (user MUST belong to an allowed department)
        $deptResolver = DepartmentResolver::getInstance();
        $userDept = $deptResolver->resolveDepartmentName();
        $userDeptNorm = $deptResolver->normalizeDepartmentName($userDept);

        $allowedDepts = $notification['allowed_departments'] ?? [];
        $isDeptAllowed = false;
        if (!empty($allowedDepts)) {
            foreach ($allowedDepts as $dept) {
                if (strcasecmp($userDept, $dept) === 0 || strcasecmp($userDeptNorm, $deptResolver->normalizeDepartmentName($dept)) === 0) {
                    $isDeptAllowed = true;
                    break;
                }
            }
        }

        if (!$isDeptAllowed) {
            return false;
        }

        // 3. Permission-based RBAC match
        $requiredPerms = $notification['required_permissions'] ?? [];
        if (!empty($requiredPerms)) {
            $permService = PermissionService::getInstance();
            if (!$permService->hasAnyPermission($requiredPerms)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a role string represents an Administrator
     */
    private function isAdmin(string $role): bool
    {
        $r = strtolower(trim($role));
        return in_array($r, ['admin', 'administrator', 'system administrator', 'superadmin', 'system admin'], true);
    }

    /**
     * Helper to format readable relative time
     */
    private function formatTimeAgo(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return 'Just now';
        }

        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'Just now';
        } elseif ($diff < 3600) {
            $mins = max(1, round($diff / 60));
            return $mins . 'm ago';
        } elseif ($diff < 86400) {
            $hours = max(1, round($diff / 3600));
            return $hours . 'h ago';
        } elseif ($diff < 172800) {
            return 'Yesterday';
        } else {
            return date('M d', $timestamp);
        }
    }

    /**
     * Dispatch an email notification respecting the master notifications.email.enabled setting
     */
    public function dispatchEmailAlert(string $toEmail, string $recipientName, string $subject, string $message): bool
    {
        $emailEnabled = class_exists('Settings') ? (bool)\Settings::get('notifications.email.enabled', true) : true;
        if (!$emailEnabled) {
            error_log("NotificationService: Email alerts disabled in Settings. Skipped dispatch to {$toEmail}.");
            return false;
        }

        require_once __DIR__ . '/MailService.php';
        $mailer = new \MailService();
        return $mailer->sendNotificationEmail($toEmail, $recipientName, $subject, $message);
    }

    /**
     * Dispatch an SMS notification respecting the master notifications.sms.enabled setting
     */
    public function dispatchSmsAlert(string $phoneNumber, string $message): bool
    {
        $smsEnabled = class_exists('Settings') ? (bool)\Settings::get('notifications.sms.enabled', false) : false;
        if (!$smsEnabled) {
            error_log("NotificationService: SMS alerts disabled in Settings. Skipped dispatch to {$phoneNumber}.");
            return false;
        }

        $provider = class_exists('Settings') ? \Settings::get('notifications.sms.api_provider', 'Twilio') : 'Twilio';
        $logDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logEntry = date('Y-m-d H:i:s') . " | SMS to {$phoneNumber} via {$provider}: {$message}\n";
        @file_put_contents($logDir . '/sms_dispatch.log', $logEntry, FILE_APPEND);

        return true;
    }
}
