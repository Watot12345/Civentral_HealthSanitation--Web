<?php
// app/services/SessionAuthService.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/MailService.php';

class SessionAuthService
{
    private MailService $mailService;

    public function __construct()
    {
        $this->mailService = new MailService();
    }

    /**
     * Resolves the device remembrance duration in seconds.
     * Defaults to 10 days (864,000s), but respects 'security.remember_device_days' setting or REMEMBER_DEVICE_DAYS env.
     */
    public static function getRememberDurationSeconds(): int
    {
        // 1. Check environment variable override (e.g. for fast-forward testing/simulation)
        $envDays = getenv('REMEMBER_DEVICE_DAYS');
        if ($envDays !== false && is_numeric($envDays)) {
            return (int)round(((float)$envDays) * 86400);
        }

        // 2. Check Settings if available
        if (class_exists('Settings')) {
            $settingDays = Settings::get('security.remember_device_days', null);
            if ($settingDays !== null && is_numeric($settingDays)) {
                return (int)round(((float)$settingDays) * 86400);
            }
        }

        // Default: 10 Days = 864,000 seconds
        return 10 * 86400;
    }

    /**
     * Generates a 6-digit OTP code with 3-minute TTL, stores it in DB, and emails it to the employee.
     */
    public function generateAndSendOtp(array $employee, bool $rememberMe = true): array
    {
        $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = gmdate('Y-m-d H:i:sP', time() + (3 * 60));
        $sessionToken = bin2hex(random_bytes(32));

        // Session expiration after OTP verification (configurable 10-day TTL).
        $rememberDuration = self::getRememberDurationSeconds();
        $finalExpiresAt = $rememberMe
            ? gmdate('Y-m-d H:i:sP', time() + $rememberDuration)
            : gmdate('Y-m-d H:i:sP', time() + 86400);

        $email = $employee['email'] ?? '';
        $name  = $employee['full_name'] ?? ($employee['username'] ?? 'Employee');
        $empId = (int)$employee['id'];

        // Save session/OTP record to database (using service key to guarantee RLS bypass)
        try {
            $db = Database::getInstance();
            $db->query('user_sessions', 'POST', [
                'employee_id'    => $empId,
                'session_token'  => $sessionToken,
                'otp_code'       => $otpCode,
                'otp_expires_at' => $otpExpiresAt,
                'remember_me'    => $rememberMe ? 1 : 0,
                'expires_at'     => $finalExpiresAt,
            ], [], [], true);
        } catch (\Throwable $e) {
            error_log('SessionAuthService DB error: ' . $e->getMessage());
        }

        // Send email via MailService (PHPMailer / SMTP) with 3-minute expiration note
        $sent = false;
        if (!empty($email)) {
            $sent = $this->mailService->sendOtpEmail($email, $name, $otpCode, 3);
        }

        return [
            'success'       => true,
            'sent'          => $sent,
            'session_token' => $sessionToken,
            'otp_code'      => $otpCode,
            'email'         => $email,
            'expires_at'    => $otpExpiresAt
        ];
    }

    /**
     * Verifies the 6-digit OTP code (Enforces max 5 attempts before locking code).
     */
    public function verifyOtp(string $sessionToken, string $enteredCode, ?bool $overrideRememberMe = null): array
    {
        try {
            $db = Database::getInstance();
            $sessions = $db->select('user_sessions', ['session_token' => $sessionToken], [], true);

            if (empty($sessions)) {
                return ['success' => false, 'error_type' => 'token_invalid', 'message' => 'Session expired. Please re-enter your credentials.'];
            }

            $session = $sessions[0];
            $rememberMe = ($overrideRememberMe !== null) ? (bool)$overrideRememberMe : (!empty($session['remember_me']));

            // 1. Check if token/code has expired or already been invalidated/locked
            if (strtotime($session['otp_expires_at']) < time() || empty($session['otp_code'])) {
                return [
                    'success' => false,
                    'error_type' => 'code_expired',
                    'message' => 'Verification code has expired (3-minute limit). Please click "Resend Code" for a new code.'
                ];
            }

            // 2. Track & Enforce Maximum 5 OTP Attempts (Brute Force Protection)
            $cacheDir = __DIR__ . '/../../storage/cache';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0755, true);
            }
            $attemptFile = $cacheDir . '/otp_attempts_' . md5($sessionToken) . '.json';
            $attemptData = ['attempts' => 0, 'created_at' => time()];
            if (file_exists($attemptFile)) {
                $raw = @file_get_contents($attemptFile);
                $attemptData = json_decode($raw, true) ?: $attemptData;
            }

            // If already at or above 5 attempts, lock and invalidate the code
            if (($attemptData['attempts'] ?? 0) >= 5) {
                try {
                    $db->update('user_sessions', [
                        'otp_code' => null,
                        'otp_expires_at' => gmdate('Y-m-d H:i:sP', time() - 10)
                    ], ['session_token' => $sessionToken], true);
                } catch (\Throwable $ignored) {}

                return [
                    'success' => false,
                    'error_type' => 'too_many_attempts',
                    'locked' => true,
                    'message' => 'Maximum verification attempts exceeded (5/5). For your security, this code has been locked and invalidated. Please click "Resend Code" to receive a new code.'
                ];
            }

            // 3. Verify the Code
            if (trim($session['otp_code']) !== trim($enteredCode)) {
                $attemptData['attempts'] = ($attemptData['attempts'] ?? 0) + 1;
                @file_put_contents($attemptFile, json_encode($attemptData));

                $remaining = max(0, 5 - $attemptData['attempts']);

                if ($remaining === 0) {
                    try {
                        $db->update('user_sessions', [
                            'otp_code' => null,
                            'otp_expires_at' => gmdate('Y-m-d H:i:sP', time() - 10)
                        ], ['session_token' => $sessionToken], true);
                    } catch (\Throwable $ignored) {}

                    return [
                        'success' => false,
                        'error_type' => 'too_many_attempts',
                        'locked' => true,
                        'message' => 'Maximum verification attempts exceeded (5/5). For your security, this code has been locked. Click "Resend Code" to generate a new verification code.'
                    ];
                }

                return [
                    'success' => false,
                    'error_type' => 'wrong_code',
                    'remaining_attempts' => $remaining,
                    'message' => "Incorrect 6-digit verification code. ({$remaining} attempt" . ($remaining === 1 ? '' : 's') . " remaining before code is locked)"
                ];
            }

            // Code is valid! Clean up attempt tracker file
            if (file_exists($attemptFile)) {
                @unlink($attemptFile);
            }

            // Fetch employee record
            $empId = (int)$session['employee_id'];
            $employees = $db->select('employees', ['id' => $empId]);

            if (empty($employees)) {
                return ['success' => false, 'error_type' => 'user_not_found', 'message' => 'Employee account record not found in system.'];
            }

            $employee = $employees[0];

            // Enforce active employment status check before activating session
            $empStatus = strtolower(trim($employee['status'] ?? 'active'));
            if (!empty($employee['status']) && $empStatus !== 'active') {
                return [
                    'success' => false,
                    'error_type' => 'account_inactive',
                    'message' => "Access Denied: Account status is '" . ucfirst($employee['status']) . "'. Please contact System Administrator or HR."
                ];
            }

            // Activate session in PHP $_SESSION
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }

            $functionalRole = $employee['role_description'] ?? $employee['role'] ?? 'Employee';

            $_SESSION['user_id']          = $employee['id'];
            $_SESSION['employee_id']      = $employee['employee_id'] ?? $employee['username'];
            $_SESSION['full_name']        = $employee['full_name'];
            $_SESSION['user_full_name']   = $employee['full_name'];
            $_SESSION['department']       = $employee['department'] ?? '';
            $_SESSION['user_department']  = $employee['department'] ?? '';
            $_SESSION['role']             = $functionalRole;
            $_SESSION['role_description'] = $functionalRole;
            $_SESSION['user_role']        = $functionalRole;
            $_SESSION['session_token']    = $sessionToken;
            
            // Set session expiration & cookies based on Remember this device toggle
            if ($rememberMe) {
                $cookieDuration = self::getRememberDurationSeconds();
                $newExpiresAt = gmdate('Y-m-d H:i:sP', time() + $cookieDuration);
                try {
                    $db->update('user_sessions', [
                        'remember_me' => 1,
                        'expires_at'  => $newExpiresAt
                    ], ['session_token' => $sessionToken], true);
                    $session['remember_me'] = 1;
                    $session['expires_at']  = $newExpiresAt;
                } catch (\Throwable $ignored) {}

                $_SESSION['session_expires']  = $session['expires_at'];
                $_SESSION['logged_in']        = true;

                // Set 10-day persistent device cookies
                setcookie('civentral_session', $sessionToken, time() + $cookieDuration, '/', '', false, true);
                setcookie('civentral_session_' . $employee['id'], $sessionToken, time() + $cookieDuration, '/', '', false, true);

                if (class_exists('App\Services\RememberMeService')) {
                    \App\Services\RememberMeService::createToken($employee);
                }
            } else {
                $newExpiresAt = gmdate('Y-m-d H:i:sP', time() + 86400);
                try {
                    $db->update('user_sessions', [
                        'remember_me' => 0,
                        'expires_at'  => $newExpiresAt
                    ], ['session_token' => $sessionToken], true);
                    $session['remember_me'] = 0;
                    $session['expires_at']  = $newExpiresAt;
                } catch (\Throwable $ignored) {}

                $_SESSION['session_expires']  = $session['expires_at'];
                $_SESSION['logged_in']        = true;

                // Session-only cookies (cleared on browser close)
                setcookie('civentral_session', $sessionToken, 0, '/', '', false, true);
                setcookie('civentral_session_' . $employee['id'], $sessionToken, 0, '/', '', false, true);
            }

            return [
                'success'  => true,
                'employee' => $employee,
                'user'     => [
                    'name'        => $employee['full_name'] ?? 'User',
                    'employee_id' => $employee['employee_id'] ?? ''
                ],
                'redirect' => site_url('pages/dashboard.php')
            ];

        } catch (\Throwable $e) {
            error_log('SessionAuthService verify error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Verification failed due to a system error.'];
        }
    }

    /**
     * Validates an active session token stored in civentral_session cookie
     * and restores the user's $_SESSION if valid.
     */
    public function validateActiveToken(string $sessionToken): bool
    {
        $sessionToken = trim($sessionToken);
        if (empty($sessionToken)) {
            return false;
        }

        try {
            $db = Database::getInstance();
            $sessions = $db->select('user_sessions', ['session_token' => $sessionToken], [], true);

            if (empty($sessions)) {
                $this->clearSessionCookie();
                return false;
            }

            $session = $sessions[0];

            // Validate expiration time
            if (!empty($session['expires_at']) && strtotime($session['expires_at']) <= time()) {
                $this->clearSessionCookie();
                return false;
            }

            $empId = (int)($session['employee_id'] ?? 0);
            if ($empId <= 0) {
                $this->clearSessionCookie();
                return false;
            }

            $employees = $db->select('employees', ['id' => $empId]);
            if (empty($employees)) {
                $this->clearSessionCookie();
                return false;
            }

            $employee = $employees[0];

            // Verify employee active status
            $empStatus = strtolower(trim($employee['status'] ?? 'active'));
            if (!empty($employee['status']) && $empStatus !== 'active') {
                $this->clearSessionCookie();
                return false;
            }

            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }

            $functionalRole = $employee['role_description'] ?? $employee['role'] ?? 'Employee';

            $_SESSION['user_id']          = $employee['id'];
            $_SESSION['employee_id']      = $employee['employee_id'] ?? $employee['username'];
            $_SESSION['full_name']        = $employee['full_name'];
            $_SESSION['user_full_name']   = $employee['full_name'];
            $_SESSION['department']       = $employee['department'] ?? '';
            $_SESSION['user_department']  = $employee['department'] ?? '';
            $_SESSION['role']             = $functionalRole;
            $_SESSION['role_description'] = $functionalRole;
            $_SESSION['user_role']        = $functionalRole;
            $_SESSION['session_token']    = $sessionToken;
            $_SESSION['session_expires']  = $session['expires_at'] ?? null;
            $_SESSION['logged_in']        = true;
            $_SESSION['last_activity']    = time();

            return true;
        } catch (\Throwable $e) {
            error_log('SessionAuthService validateActiveToken error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clears invalid or expired session cookie.
     */
    private function clearSessionCookie(): void
    {
        if (isset($_COOKIE['civentral_session'])) {
            unset($_COOKIE['civentral_session']);
            if (!headers_sent()) {
                setcookie('civentral_session', '', time() - 3600, '/', '', false, true);
            }
        }
    }
}

