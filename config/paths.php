<?php
// Load environment variables if available
require_once __DIR__ . '/../Core/Env.php';

// Try to get BASE_URL from env, otherwise detect dynamically
$baseUrl = Env::get('BASE_URL');

if ($baseUrl === null) {
    // Dynamic detection
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

    if (!empty($docRoot) && str_starts_with($projectRoot, $docRoot)) {
        $baseUrl = substr($projectRoot, strlen($docRoot));
    } else {
        // Fallback: if server document root is not matching, use default '/capstone'
        $baseUrl = '/capstone';
    }

    $baseUrl = '/' . trim($baseUrl, '/');
    if ($baseUrl === '/') {
        $baseUrl = '';
    }
}

define('BASE_URL', $baseUrl);

function site_url($path = '') {
    $clean = str_replace('../', '', $path);
    return rtrim(BASE_URL, '/') . '/' . ltrim($clean, '/');
}

// Helper to accurately detect client IP address in real-world environments
if (!function_exists('getClientIP')) {
    function getClientIP(): string {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}

// Helper to accurately detect client device / operating system & browser
if (!function_exists('getClientDevice')) {
    function getClientDevice(): string {
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($agent)) {
            return 'Desktop • Chrome (Linux)';
        }

        $os = 'Linux';
        if (preg_match('/linux/i', $agent)) $os = 'Linux';
        elseif (preg_match('/win/i', $agent)) $os = 'Windows 11';
        elseif (preg_match('/mac/i', $agent)) $os = 'macOS';
        elseif (preg_match('/iphone|ipad|ipod/i', $agent)) $os = 'iOS 17';
        elseif (preg_match('/android/i', $agent)) $os = 'Android 14';

        $browser = 'Chrome';
        if (preg_match('/chrome|crios/i', $agent) && !preg_match('/edg|opr|brave/i', $agent)) $browser = 'Chrome';
        elseif (preg_match('/firefox|fxios/i', $agent)) $browser = 'Firefox';
        elseif (preg_match('/safari/i', $agent) && !preg_match('/chrome/i', $agent)) $browser = 'Safari';
        elseif (preg_match('/edg/i', $agent)) $browser = 'Edge';

        $deviceType = preg_match('/mobile|android|iphone|ipad/i', $agent) ? 'Mobile' : 'Desktop';
        return "{$deviceType} • {$browser} ({$os})";
    }
}

// ============================================================
// GLOBAL ROLE-BASED ACCESS CONTROL (RBAC) PERMISSION SYSTEM
// ============================================================

if (!function_exists('getUserGrantedPermissions')) {
    function getUserGrantedPermissions(): array {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['granted_permission_slugs']) && is_array($_SESSION['granted_permission_slugs'])) {
            return $_SESSION['granted_permission_slugs'];
        }

        $userRole = trim($_SESSION['role'] ?? $_SESSION['role_description'] ?? 'employee');

        if (strcasecmp($userRole, 'System Administrator') === 0 || strcasecmp($userRole, 'System Admin') === 0 || strcasecmp($userRole, 'admin') === 0) {
            $allSlugs = [
                'dashboard.view', 'analytics.view', 'reports.view', 'compliance.view',
                'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
                'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
                'prescriptions.view', 'prescriptions.create',
                'permits.view', 'permits.create', 'permits.approve', 'inspections.view', 'inspections.conduct',
                'immunization.view', 'immunization.create', 'immunization.edit',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage', 'settings.manage', 'logs.view'
            ];
            $_SESSION['granted_permission_slugs'] = $allSlugs;
            return $allSlugs;
        }

        try {
            require_once __DIR__ . '/../app/Models/Role.php';
            $roleModel = new Role();
            $roles = $roleModel->all();

            $matchedRole = null;
            foreach ($roles as $r) {
                if (strcasecmp(trim($r['name']), $userRole) === 0) {
                    $matchedRole = $r;
                    break;
                }
            }

            $grantedSlugs = [];
            if ($matchedRole && !empty($matchedRole['permissions'])) {
                foreach ($matchedRole['permissions'] as $p) {
                    if (!empty($p['granted']) && !empty($p['slug'])) {
                        $grantedSlugs[] = $p['slug'];
                    }
                }
            }

            $_SESSION['granted_permission_slugs'] = $grantedSlugs;
            return $grantedSlugs;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission(string $slug): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userRole = trim($_SESSION['role'] ?? $_SESSION['role_description'] ?? '');
        if (strcasecmp($userRole, 'System Administrator') === 0 || strcasecmp($userRole, 'System Admin') === 0 || strcasecmp($userRole, 'admin') === 0) {
            return true;
        }
        $granted = getUserGrantedPermissions();
        return in_array($slug, $granted, true);
    }
}

if (!function_exists('requirePermission')) {
    function requirePermission(string $slug): void {
        if (!hasPermission($slug)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['flash_error'] = 'Access Denied: You do not have permission to view that page.';
            header('Location: ' . site_url('pages/dashboard.php'));
            exit;
        }
    }
}
?>