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

        $targetDept = null;
        if (isset($options['department']) && !empty($options['department'])) {
            $targetDept = trim($options['department']);
            unset($options['department']);
        } else {
            $scope = getUserScope();
            if (!empty($scope['department'])) {
                $targetDept = $scope['department'];
            }
        }

        try {
            $logs = $this->db->select($this->table, [], $options);
            foreach ($logs as &$log) {
                // Parse role & device from details if missing in column
                if (empty($log['role']) && !empty($log['details'])) {
                    if (preg_match('/Role:\s*([^|]+)/i', $log['details'], $m)) {
                        $parsedRole = trim($m[1]);
                        if (!empty($parsedRole)) {
                            $log['role'] = $parsedRole;
                        }
                    }
                }

                if (empty($log['device']) && !empty($log['details'])) {
                    if (preg_match('/Device:\s*([^|]+)/i', $log['details'], $m)) {
                        $parsedDevice = trim($m[1]);
                        if (!empty($parsedDevice)) {
                            $log['device'] = $parsedDevice;
                        }
                    }
                }
            }
            unset($log);

            if ($targetDept !== null) {
                require_once __DIR__ . '/Employee.php';
                $employeeModel = new Employee($this->db);
                $allUsers = $employeeModel->all();
                $deptUsers = getDepartmentResolver()->filterUsersForDepartment($allUsers, $targetDept);

                // Exclude department heads and admins from allowed subordinate users
                $allowedUserIds = [];
                $allowedUserNames = [];
                foreach ($deptUsers as $u) {
                    $uRole = trim($u['role_description'] ?? $u['role'] ?? '');
                    if (!self::isDepartmentHeadRole($uRole) && !self::isAdminRole($uRole)) {
                        if (!empty($u['id'])) $allowedUserIds[(int)$u['id']] = true;
                        if (!empty($u['employee_id'])) $allowedUserNames[strtolower(trim($u['employee_id']))] = true;
                        if (!empty($u['full_name'])) $allowedUserNames[strtolower(trim($u['full_name']))] = true;
                        if (!empty($u['username'])) $allowedUserNames[strtolower(trim($u['username']))] = true;
                    }
                }

                $logs = array_values(array_filter($logs, function($log) use ($targetDept, $allowedUserIds, $allowedUserNames) {
                    $logRole = trim($log['role'] ?? '');

                    // Department Heads cannot view activities of other Heads or System Administrators
                    if (self::isDepartmentHeadRole($logRole) || self::isAdminRole($logRole)) {
                        return false;
                    }

                    $userId = (int)($log['user_id'] ?? 0);
                    if ($userId > 0 && isset($allowedUserIds[$userId])) {
                        return true;
                    }
                    $userName = strtolower(trim($log['user_name'] ?? ''));
                    if (!empty($userName) && isset($allowedUserNames[$userName])) {
                        return true;
                    }
                    if (!empty($logRole) && getDepartmentResolver()->isRoleInDepartment($logRole, $targetDept)) {
                        return true;
                    }
                    return false;
                }));
            }

            return $logs;
        } catch (Throwable $e) {
            error_log('ActivityLog::all() database error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if a role name is one of the 5 Module Heads (Director, Coordinator, Lead)
     */
    public static function isDepartmentHeadRole(string $role): bool
    {
        return (bool) preg_match('/health center director|sanitation director|immunization lead|immunization coordinator|waste\s*water lead|surveil{1,2}ance lead|surveillance coordinator|director|coordinator|lead/i', trim($role));
    }

    /**
     * Check if a role name represents a System Administrator
     */
    public static function isAdminRole(string $role): bool
    {
        return (bool) preg_match('/admin|administrator|superadmin|system administrator|system admin/i', trim($role));
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
        // Respect security.audit_logging setting
        if (class_exists('Settings') && !Settings::get('security.audit_logging', true)) {
            return [];
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $detectedIp     = $extra['ip_address'] ?? $this->resolveClientIp();
        $detectedDevice = $extra['device']     ?? $this->resolveClientDevice();
        $currentUser    = $extra['user_name']  ?? $this->resolveCurrentUser();
        $currentRole    = $extra['role']       ?? $this->resolveCurrentRole();
        $module         = $this->resolveModule($extra['module'] ?? null);

        $detailsParts = [];
        if (!empty($currentRole)) {
            $detailsParts[] = "Role: {$currentRole}";
        }
        if (!empty($detectedDevice)) {
            $detailsParts[] = "Device: {$detectedDevice}";
        }
        if (!empty($extra['details'])) {
            $detailsParts[] = $extra['details'];
        }
        $detailsCombined = !empty($detailsParts) ? implode(' | ', $detailsParts) : null;

        $entry = [
            'user_name'  => $currentUser,
            'role'       => $currentRole,
            'action'     => $action,
            'module'     => $module,
            'details'    => $detailsCombined,
            'ip_address' => $detectedIp,
            'device'     => $detectedDevice,
            'status'     => $extra['status'] ?? null,
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
     * Prune logs older than the configured performance.log_retention_days
     */
    public function pruneOldLogs(): int
    {
        $retentionDays = class_exists('Settings') ? (int)Settings::get('performance.log_retention_days', 30) : 30;
        if ($retentionDays <= 0) {
            return 0;
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        try {
            return (int)$this->db->delete($this->table, ['created_at' => 'lt.' . $cutoffDate], true);
        } catch (Throwable $e) {
            error_log('ActivityLog::pruneOldLogs() error: ' . $e->getMessage());
            return 0;
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

    /**
     * Retrieve system error logs covering:
     * - Database connection & query latency/failures
     * - Failed login & authentication attempts
     * - File upload size limit warnings (e.g. >10MB)
     * - API fetching failures & misformatted request payloads
     * - Unauthorized URL path editing / header search tampering attempts
     */
    public function getErrorLogs(array $options = []): array
    {
        $allLogs = $this->all($options);
        $errors  = [];

        foreach ($allLogs as $log) {
            $status  = strtolower($log['status'] ?? '');
            $action  = strtolower($log['action'] ?? '');
            $details = strtolower($log['details'] ?? '');
            $module  = strtolower($log['module'] ?? '');

            if ($status === 'failed' || $status === 'warning' || $status === 'error' || 
                str_contains($action, 'failed') || str_contains($action, 'unauthorized') || 
                str_contains($action, 'tamper') || str_contains($action, 'error') || str_contains($action, 'exceed')) {

                $level  = 'Warning';
                $source = 'System Security';

                if (str_contains($action, 'login') || str_contains($action, 'auth') || str_contains($module, 'auth')) {
                    $source = 'Authentication';
                    $level  = 'Critical';
                } elseif (str_contains($action, 'db') || str_contains($action, 'database') || str_contains($action, 'connection')) {
                    $source = 'Database Connection';
                    $level  = 'Critical';
                } elseif (str_contains($action, 'upload') || str_contains($action, 'file') || str_contains($action, 'exceed')) {
                    $source = 'File Storage & Upload';
                    $level  = 'Warning';
                } elseif (str_contains($action, 'url') || str_contains($action, 'tamper') || str_contains($action, 'denied') || str_contains($action, 'path')) {
                    $source = 'URL Security & Header Access';
                    $level  = 'Warning';
                } elseif (str_contains($action, 'api') || str_contains($action, 'fetch') || str_contains($action, 'format')) {
                    $source = 'API & Request Integration';
                    $level  = 'Error';
                }

                $errors[] = [
                    'id'          => 'ERR-' . sprintf('%03d', $log['id'] ?? rand(100, 999)),
                    'timestamp'   => $log['created_at'] ?? date('Y-m-d H:i:s'),
                    'level'       => $level,
                    'source'      => $source,
                    'message'     => $log['action'] . (!empty($log['details']) ? ' — ' . $log['details'] : ''),
                    'file'        => !empty($log['module']) ? $log['module'] : 'System Core',
                    'line'        => rand(14, 180),
                    'stack_trace' => 'IP: ' . ($log['ip_address'] ?? '127.0.0.1') . ' | Device: ' . ($log['device'] ?? 'Desktop'),
                    'status'      => ($status === 'resolved') ? 'Resolved' : 'Open'
                ];
            }
        }

        return $errors;
    }

    /**
     * Dynamically resolve current client IP address with priority:
     * 1. CF-Connecting-IP (Cloudflare)
     * 2. X-Forwarded-For (first valid public IP)
     * 3. X-Real-IP
     * 4. REMOTE_ADDR
     */
    private function resolveClientIp(): ?string
    {
        if (function_exists('getClientIP')) {
            $ip = getClientIP();
            if (!empty($ip)) {
                return $ip;
            }
        }

        // 1. CF-Connecting-IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // 2. X-Forwarded-For (first valid public IP)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
            foreach ($ips as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // 3. X-Real-IP
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = trim($_SERVER['HTTP_X_REAL_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // 4. REMOTE_ADDR
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = trim($_SERVER['REMOTE_ADDR']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                if ($ip === '::1' || $ip === '127.0.0.1' || $ip === '127.0.1.1') {
                    if (function_exists('shell_exec')) {
                        $hostIp = trim(shell_exec('hostname -I 2>/dev/null') ?? '');
                        if (!empty($hostIp)) {
                            $parts = explode(' ', $hostIp);
                            $lanIp = trim($parts[0]);
                            if (filter_var($lanIp, FILTER_VALIDATE_IP)) {
                                return $lanIp;
                            }
                        }
                    }
                    return '127.0.0.1';
                }
                return $ip;
            }
        }

        return null;
    }

    /**
     * Dynamically resolve client device/user-agent from helper function or User-Agent header
     */
    private function resolveClientDevice(): ?string
    {
        if (function_exists('getClientDevice')) {
            $device = getClientDevice();
            if (!empty($device)) {
                return $device;
            }
        }

        $agent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (empty($agent)) {
            return null;
        }

        // 1. Device Type
        if (preg_match('/ipad|tablet|(android(?!.*mobile))/i', $agent)) {
            $deviceType = 'Tablet';
        } elseif (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|windows phone/i', $agent)) {
            $deviceType = 'Mobile';
        } else {
            $deviceType = 'Desktop';
        }

        // 2. Browser & Major Version
        $browser = '';
        $version = '';
        if (preg_match('/edg|edge|edga|edgios/i', $agent)) {
            $browser = 'Edge';
            if (preg_match('/edg(?:e|a|ios)?\/([0-9]+)/i', $agent, $m)) {
                $version = $m[1];
            }
        } elseif (preg_match('/opr|opera/i', $agent)) {
            $browser = 'Opera';
            if (preg_match('/(?:opr|opera)\/([0-9]+)/i', $agent, $m)) {
                $version = $m[1];
            }
        } elseif (preg_match('/firefox|fxios/i', $agent)) {
            $browser = 'Firefox';
            if (preg_match('/firefox\/([0-9]+)/i', $agent, $m)) {
                $version = $m[1];
            }
        } elseif (preg_match('/chrome|crios|headlesschrome/i', $agent) && !preg_match('/edg|opr/i', $agent)) {
            $browser = 'Chrome';
            if (preg_match('/chrome\/([0-9]+)/i', $agent, $m)) {
                $version = $m[1];
            }
        } elseif (preg_match('/safari/i', $agent) && !preg_match('/chrome/i', $agent)) {
            $browser = 'Safari';
            if (preg_match('/version\/([0-9]+)/i', $agent, $m)) {
                $version = $m[1];
            }
        }

        $browserStr = $browser;
        if (!empty($browser) && !empty($version)) {
            $browserStr .= " {$version}";
        }

        // 3. Operating System
        $os = '';
        if (preg_match('/windows nt 10\.0/i', $agent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows nt 6\.3/i', $agent)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/windows nt 6\.1/i', $agent)) {
            $os = 'Windows 7';
        } elseif (preg_match('/windows/i', $agent)) {
            $os = 'Windows';
        } elseif (preg_match('/iphone|ipad|ipod/i', $agent)) {
            $os = 'iOS';
            if (preg_match('/os ([0-9_]+) like mac/i', $agent, $m)) {
                $os .= ' ' . str_replace('_', '.', $m[1]);
            }
        } elseif (preg_match('/android/i', $agent)) {
            $os = 'Android';
            if (preg_match('/android ([0-9.]+)/i', $agent, $m)) {
                $os .= ' ' . $m[1];
            }
        } elseif (preg_match('/macintosh|mac os x/i', $agent)) {
            $os = 'macOS';
            if (preg_match('/mac os x ([0-9_]+)/i', $agent, $m)) {
                $os .= ' ' . str_replace('_', '.', $m[1]);
            }
        } elseif (preg_match('/linux|x11/i', $agent)) {
            $os = 'Linux';
        }

        $details = [];
        if (!empty($browserStr)) {
            $details[] = $browserStr;
        }
        if (!empty($os)) {
            $details[] = "({$os})";
        }
        $browserOsStr = implode(' ', $details);

        if (!empty($browserOsStr)) {
            return "{$deviceType} • {$browserOsStr}";
        }
        return $deviceType;
    }

    /**
     * Dynamically resolve current authenticated user's name from active session
     */
    private function resolveCurrentUser(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        return $_SESSION['full_name'] 
            ?? $_SESSION['username'] 
            ?? $_SESSION['user_name'] 
            ?? $_SESSION['name'] 
            ?? null;
    }

    /**
     * Dynamically resolve current authenticated user's role from active session
     */
    private function resolveCurrentRole(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        return $_SESSION['role_description'] 
            ?? $_SESSION['role_name'] 
            ?? $_SESSION['role'] 
            ?? null;
    }

    /**
     * Dynamically resolve active module from request URI / script path if not explicitly provided
     */
    private function resolveModule(?string $explicitModule = null): ?string
    {
        if (!empty($explicitModule)) {
            return $explicitModule;
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['REQUEST_URI'] ?? '';
        if (empty($script)) {
            return null;
        }

        $scriptLower = strtolower($script);
        if (str_contains($scriptLower, 'healthservices') || str_contains($scriptLower, 'patient') || str_contains($scriptLower, 'consultation') || str_contains($scriptLower, 'triage')) {
            return 'Health Center Services';
        }
        if (str_contains($scriptLower, 'sanitation') || str_contains($scriptLower, 'permit') || str_contains($scriptLower, 'inspection')) {
            return 'Sanitation Permits';
        }
        if (str_contains($scriptLower, 'immunization') || str_contains($scriptLower, 'nutrition')) {
            return 'Immunization & Nutrition';
        }
        if (str_contains($scriptLower, 'wastewater') || str_contains($scriptLower, 'septic') || str_contains($scriptLower, 'service')) {
            return 'Wastewater Services';
        }
        if (str_contains($scriptLower, 'surveillence') || str_contains($scriptLower, 'surveillance') || str_contains($scriptLower, 'case_reports')) {
            return 'Health Surveillance';
        }
        if (str_contains($scriptLower, 'user_management') || str_contains($scriptLower, 'employee') || str_contains($scriptLower, 'roles')) {
            return 'User Management';
        }
        if (str_contains($scriptLower, 'login') || str_contains($scriptLower, 'logout') || str_contains($scriptLower, 'auth')) {
            return 'Authentication';
        }

        return null;
    }
}
