<?php
// app/services/RememberMeService.php

namespace App\Services;

use Database;
use ActivityLog;
use Throwable;

class RememberMeService
{
    private static string $cookieName = 'civentral_remember';
    private static int $cookieLifetime = 864000; // 10 Days (10 * 24 * 60 * 60 = 864,000 seconds)
    private static string $secretKey = 'Civentral_LGU_Secure_Remember_HMAC_Secret_2026';

    /**
     * Create long-lived Remember Me token & set secure HTTP cookie.
     */
    public static function createToken(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (!headers_sent()) {
            @session_set_cookie_params([
                'lifetime' => self::$cookieLifetime,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        $userId     = (int)($user['id'] ?? 0);
        $employeeId = trim($user['employee_id'] ?? '');
        $pwdHash    = substr($user['password'] ?? '', 0, 16); // Hash slice

        if ($userId <= 0 || empty($employeeId)) {
            return;
        }

        $payload   = "{$userId}:{$employeeId}:{$pwdHash}";
        $signature = hash_hmac('sha256', $payload, self::$secretKey);
        $token     = base64_encode("{$payload}:{$signature}");

        // Populate $_COOKIE for CLI / test environment
        $_COOKIE[self::$cookieName] = $token;

        // Set HTTP cookie for 30 days
        if (!headers_sent()) {
            @setcookie(
                self::$cookieName,
                $token,
                [
                    'expires'  => time() + self::$cookieLifetime,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
                ]
            );
        }
    }

    /**
     * Attempt auto-login using Remember Me cookie if session is unauthenticated.
     */
    public static function processAutoLogin(): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // If already logged in, return true
        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            return true;
        }

        // Check if remember cookie exists
        if (empty($_COOKIE[self::$cookieName])) {
            return false;
        }

        try {
            $rawToken = base64_decode($_COOKIE[self::$cookieName], true);
            if (!$rawToken) {
                self::clearToken();
                return false;
            }

            $parts = explode(':', $rawToken);
            if (count($parts) !== 4) {
                self::clearToken();
                return false;
            }

            [$userId, $employeeId, $pwdHash, $signature] = $parts;
            $payload = "{$userId}:{$employeeId}:{$pwdHash}";
            $expectedSignature = hash_hmac('sha256', $payload, self::$secretKey);

            if (!hash_equals($expectedSignature, $signature)) {
                self::clearToken();
                return false;
            }

            // Fetch user record from Database
            require_once __DIR__ . '/../../config/database.php';
            $db = Database::getInstance();
            $result = $db->select('employees', ['id' => $userId]);

            if (empty($result) || !is_array($result)) {
                self::clearToken();
                return false;
            }

            $user = $result[0];

            // Verify password slice to ensure password hasn't been changed
            if (substr($user['password'] ?? '', 0, 16) !== $pwdHash) {
                self::clearToken();
                return false;
            }

            // Auto-authenticate session
            $_SESSION['user_id']          = $user['id'];
            $_SESSION['employee_id']      = $user['employee_id'];
            $_SESSION['full_name']        = $user['full_name'];
            $_SESSION['department']       = $user['department'] ?? '';
            $_SESSION['role']             = $user['role'] ?? 'employee';
            $_SESSION['role_description'] = $user['role_description'] ?? '';
            $_SESSION['logged_in']        = true;

            // Log auto-login event
            if (class_exists('ActivityLog')) {
                try {
                    $logger = new ActivityLog();
                    $logger->log('Remember Me Auto-Login', [
                        'user_id'   => $user['id'],
                        'user_name' => $user['full_name'],
                        'role'      => $user['role_description'] ?? $user['role'] ?? 'Employee',
                        'module'    => 'Authentication',
                        'details'   => "Auto-logged in via Keep Me Signed In cookie: {$user['employee_id']}",
                        'status'    => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            return true;

        } catch (Throwable $e) {
            self::clearToken();
            return false;
        }
    }

    /**
     * Clear Remember Me cookie & invalidate token on logout.
     */
    public static function clearToken(): void
    {
        if (isset($_COOKIE[self::$cookieName])) {
            if (!headers_sent()) {
                @setcookie(
                    self::$cookieName,
                    '',
                    [
                        'expires'  => time() - 3600,
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]
                );
            }
            unset($_COOKIE[self::$cookieName]);
        }
    }
}
