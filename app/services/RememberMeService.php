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

    public static function getLifetime(): int
    {
        if (class_exists('SessionAuthService')) {
            return \SessionAuthService::getRememberDurationSeconds();
        }
        return self::$cookieLifetime;
    }

    /**
     * Create long-lived Remember Me token & set secure HTTP cookie.
     */
    public static function createToken(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $lifetime = self::getLifetime();

        if (!headers_sent()) {
            @session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        $userId     = (int)($user['id'] ?? 0);
        $employeeId = trim($user['employee_id'] ?? '');
        $pwdHash    = substr($user['password'] ?? '', 0, 16); // Hash slice
        $expiresAt  = time() + $lifetime;

        if ($userId <= 0 || empty($employeeId)) {
            return;
        }

        $payload   = "{$userId}:{$employeeId}:{$pwdHash}:{$expiresAt}";
        $signature = hash_hmac('sha256', $payload, self::$secretKey);
        $token     = base64_encode("{$payload}:{$signature}");

        // Populate $_COOKIE for CLI / test environment
        $_COOKIE[self::$cookieName] = $token;

        // Set HTTP cookie
        if (!headers_sent()) {
            @setcookie(
                self::$cookieName,
                $token,
                [
                    'expires'  => $expiresAt,
                    'path'     => '/',
                    'httponly' => true,
                    'samesite' => 'Lax',
                    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
                ]
            );
        }
    }

    /**
     * Check if current browser has a valid, unexpired remember-device cookie for the given user.
     * Does NOT authenticate or modify $_SESSION. Used solely to determine if OTP can be skipped after password verification.
     */
    public static function isDeviceRememberedForUser(int $targetUserId): bool
    {
        if ($targetUserId <= 0 || empty($_COOKIE[self::$cookieName])) {
            return false;
        }

        try {
            $rawToken = base64_decode($_COOKIE[self::$cookieName], true);
            if (!$rawToken) {
                return false;
            }

            $parts = explode(':', $rawToken);
            if (count($parts) === 5) {
                [$userId, $employeeId, $pwdHash, $expiresAt, $signature] = $parts;
                $payload = "{$userId}:{$employeeId}:{$pwdHash}:{$expiresAt}";

                if ((int)$expiresAt <= time()) {
                    return false;
                }
            } elseif (count($parts) === 4) {
                [$userId, $employeeId, $pwdHash, $signature] = $parts;
                $payload = "{$userId}:{$employeeId}:{$pwdHash}";
            } else {
                return false;
            }

            if ((int)$userId !== $targetUserId) {
                return false;
            }

            $expectedSignature = hash_hmac('sha256', $payload, self::$secretKey);
            return hash_equals($expectedSignature, $signature);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Auto-login is disabled per security rules: credentials (password) are always required
     * on the employee portal / login page before starting an authenticated session.
     */
    public static function processAutoLogin(): bool
    {
        return false;
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
