<?php
if (ob_get_level() === 0) {
    ob_start();
}
// Load environment variables if available
require_once __DIR__ . '/../Core/Env.php';

// Try to get BASE_URL from env, otherwise detect dynamically
$baseUrl = Env::get('BASE_URL');

if ($baseUrl === null) {
    // Dynamic detection
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

    if (!empty($docRoot) && str_starts_with(strtolower($projectRoot), strtolower($docRoot))) {
        $baseUrl = substr($projectRoot, strlen($docRoot));
    } else {
        // Dynamic fallback: use current project folder name instead of hardcoded '/capstone'
        $baseUrl = '/' . basename($projectRoot);
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
        // Priority 1: Cloudflare CF-Connecting-IP
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // Priority 2: X-Forwarded-For (first valid public IP)
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

        // Priority 3: X-Real-IP
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = trim($_SERVER['HTTP_X_REAL_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // Priority 4: REMOTE_ADDR
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = trim($_SERVER['REMOTE_ADDR']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                // If local loopback (::1 or 127.0.0.1), resolve primary LAN IP if available
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

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }
}

// Helper to accurately detect client device / operating system & browser dynamically from User-Agent
if (!function_exists('getClientDevice')) {
    function getClientDevice(): string {
        $agent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (empty($agent)) {
            return '';
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

        if (!empty($deviceType) && !empty($browserOsStr)) {
            return "{$deviceType} • {$browserOsStr}";
        } elseif (!empty($browserOsStr)) {
            return $browserOsStr;
        } elseif (!empty($deviceType)) {
            return $deviceType;
        }

        return '';
    }
}

// ============================================================
// GLOBAL ROLE-BASED ACCESS CONTROL (RBAC) ENTERPRISE SYSTEM
// ============================================================

// Autoload core services & constants
require_once __DIR__ . '/../app/Constants/Permissions.php';
require_once __DIR__ . '/../app/services/PermissionService.php';
require_once __DIR__ . '/../app/services/DepartmentResolver.php';
require_once __DIR__ . '/../app/services/NavigationService.php';
require_once __DIR__ . '/../app/services/RememberMeService.php';
require_once __DIR__ . '/../app/Middleware/AuthorizationMiddleware.php';

use App\Services\PermissionService;
use App\Services\DepartmentResolver;
use App\Services\NavigationService;
use App\Services\RememberMeService;
use App\Middleware\AuthorizationMiddleware;

// Process auto-login if Keep Me Signed In cookie exists
RememberMeService::processAutoLogin();

if (!function_exists('getPermissionService')) {
    function getPermissionService(): PermissionService {
        return PermissionService::getInstance();
    }
}

if (!function_exists('getDepartmentResolver')) {
    function getDepartmentResolver(): DepartmentResolver {
        return DepartmentResolver::getInstance();
    }
}

if (!function_exists('getNavigationService')) {
    function getNavigationService(): NavigationService {
        return NavigationService::getInstance();
    }
}

if (!function_exists('getUserGrantedPermissions')) {
    function getUserGrantedPermissions(): array {
        return PermissionService::getInstance()->getGrantedPermissions();
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission(string $slug): bool {
        return PermissionService::getInstance()->hasPermission($slug);
    }
}

if (!function_exists('requirePermission')) {
    function requirePermission(string $slug): void {
        AuthorizationMiddleware::authorize($slug, 'URL Path Access');
    }
}

if (!function_exists('getCurrentUserDepartment')) {
    function getCurrentUserDepartment(): string {
        return DepartmentResolver::getInstance()->getCurrentUserDepartment();
    }
}

if (!function_exists('canAccessDepartment')) {
    function canAccessDepartment(string $department): bool {
        return DepartmentResolver::getInstance()->canAccessDepartment($department);
    }
}

if (!function_exists('requireDepartmentAccess')) {
    function requireDepartmentAccess(string $department): void {
        AuthorizationMiddleware::authorizeDepartment($department, 'Module Department Access');
    }
}



// ============================================================
// SYSTEM-WIDE SETTINGS ENGINE BOOTSTRAP INITIALIZATION
// ============================================================
if (file_exists(__DIR__ . '/../app/helpers/Settings.php')) {
    try {
        require_once __DIR__ . '/../app/helpers/Settings.php';

        // 1. Set System Timezone
        $appTimezone = Settings::get('general.timezone', 'Asia/Manila');
        if (!empty($appTimezone) && in_array($appTimezone, DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($appTimezone);
        }

        // 2. Enforce Maintenance Mode for non-admin users
        $maintenanceMode = Settings::get('maintenance.mode', false);
        if ($maintenanceMode) {
            $userRole = trim($_SESSION['role'] ?? $_SESSION['role_description'] ?? '');
            $isAdmin = (strcasecmp($userRole, 'System Administrator') === 0 || strcasecmp($userRole, 'System Admin') === 0 || strcasecmp($userRole, 'admin') === 0);
            $currentUri = $_SERVER['REQUEST_URI'] ?? '';

            if (!$isAdmin && !str_contains($currentUri, 'login.php') && !str_contains($currentUri, 'api/settings')) {
                if (!str_contains($currentUri, 'maintenance')) {
                    http_response_code(503);
                }
            }
        }

        // 3. Enforce Session Inactivity Timeout
        if (!empty($_SESSION['logged_in'])) {
            $sessionTimeout = (int)Settings::get('security.session_timeout', 3600);
            if ($sessionTimeout > 0 && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
                $expiredUserId = $_SESSION['user_id'] ?? null;
                $_SESSION = [];
                if (session_status() === PHP_SESSION_ACTIVE) {
                    @session_destroy();
                }
                setcookie('civentral_session', '', time() - 3600, '/');
                if ($expiredUserId) {
                    setcookie('civentral_session_' . $expiredUserId, '', time() - 3600, '/');
                }
                $currentUri = $_SERVER['REQUEST_URI'] ?? '';
                if (!str_contains($currentUri, 'login.php')) {
                    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Session expired due to inactivity. Please log in again.', 'session_expired' => true]);
                        exit;
                    }
                    if (!headers_sent()) {
                        header('Location: ' . site_url('login.php?session_expired=1'));
                        exit;
                    }
                }
            } else {
                $_SESSION['last_activity'] = time();
            }
        }
    } catch (Throwable $e) {
        error_log('Settings bootstrap error: ' . $e->getMessage());
    }
}

if (!function_exists('getSystemName')) {
    function getSystemName(): string {
        if (class_exists('Settings')) {
            return (string)Settings::get('general.system_name', 'Civentral');
        }
        return 'Civentral';
    }
}

if (!function_exists('formatSystemDate')) {
    function formatSystemDate(?string $datetime, bool $includeTime = false): string {
        if (empty($datetime)) {
            return '—';
        }
        $timestamp = is_numeric($datetime) ? (int)$datetime : strtotime($datetime);
        if (!$timestamp) {
            return $datetime;
        }
        $dateFormat = class_exists('Settings') ? Settings::get('general.date_format', 'Y-m-d') : 'Y-m-d';
        $timeFormat = class_exists('Settings') ? Settings::get('general.time_format', 'H:i:s') : 'H:i:s';
        
        return $includeTime ? date("{$dateFormat} {$timeFormat}", $timestamp) : date($dateFormat, $timestamp);
    }
}
?>