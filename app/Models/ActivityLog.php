<?php
// app/Models/ActivityLog.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paths.php';

class ActivityLog
{
    private Database $db;
    private string $table = 'activity_logs';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retrieve all activity logs directly from the database table
     */
    public function all(array $options = []): array
    {
        if (!isset($options['order'])) {
            $options['order'] = 'created_at.desc';
        }

        try {
            $logs = $this->db->select($this->table, [], $options);
            foreach ($logs as &$log) {
                // Parse role & device from details if missing
                if (empty($log['role'])) {
                    if (preg_match('/Role:\s*([^|]+)/i', $log['details'] ?? '', $m)) {
                        $log['role'] = trim($m[1]);
                    } else {
                        $log['role'] = 'System Administrator';
                    }
                }
                if (empty($log['device'])) {
                    if (preg_match('/Device:\s*([^|]+)/i', $log['details'] ?? '', $m)) {
                        $log['device'] = trim($m[1]);
                    } else {
                        $log['device'] = 'Desktop • Chrome (Linux)';
                    }
                }
            }
            unset($log);
            return $logs;
        } catch (Throwable $e) {
            error_log('ActivityLog::all() database error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new activity log record in the database
     */
    public function create(array $data): array
    {
        return $this->db->insert($this->table, $data, true);
    }

    /**
     * Log an activity entry directly to the database with dynamic role, IP, and device detection
     */
    public function log(string $action, array $extra = []): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $detectedIp = function_exists('getClientIP') ? getClientIP() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $detectedDevice = function_exists('getClientDevice') ? getClientDevice() : 'Desktop • Chrome (Linux)';
        $currentRole = $_SESSION['role_description'] ?? $_SESSION['role'] ?? 'System Administrator';
        $currentUser = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'System Administrator';

        $user   = $extra['user_name'] ?? $currentUser;
        $role   = $extra['role'] ?? $currentRole;
        $device = $extra['device'] ?? $detectedDevice;

        $detailsCombined = "Role: {$role} | Device: {$device}";
        if (!empty($extra['details'])) {
            $detailsCombined .= " | " . $extra['details'];
        }

        $entry = [
            'user_name'  => $user,
            'role'       => $role,
            'action'     => $action,
            'module'     => $extra['module'] ?? 'System Management',
            'details'    => $detailsCombined,
            'ip_address' => $extra['ip_address'] ?? $detectedIp,
            'device'     => $device,
            'status'     => $extra['status'] ?? 'Success',
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($extra['user_id']) || !empty($_SESSION['user_id'])) {
            $entry['user_id'] = $extra['user_id'] ?? $_SESSION['user_id'];
        }

        try {
            return $this->create($entry);
        } catch (Throwable $e) {
            error_log('ActivityLog::log() insert error: ' . $e->getMessage());
            return $entry;
        }
    }

    /**
     * Delete all activity logs from the database
     */
    public function clearAll(): void
    {
        try {
            $this->db->delete($this->table, ['id' => 'gt.0'], true);
        } catch (Throwable $e) {
            error_log('ActivityLog::clearAll() error: ' . $e->getMessage());
        }
    }

    /**
     * Count total activity logs in database
     */
    public function count(): int
    {
        try {
            return $this->db->count($this->table);
        } catch (Throwable $e) {
            return 0;
        }
    }
}
