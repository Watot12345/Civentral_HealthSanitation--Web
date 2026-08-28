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
     * Generates a 6-digit OTP code with 3-minute TTL, stores it in DB, and emails it to the employee.
     */
    public function generateAndSendOtp(array $employee, bool $rememberMe = false): array
    {
        $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = date('Y-m-d H:i:s', strtotime('+3 minutes'));
        $sessionToken = bin2hex(random_bytes(32));

        // Calculate final session expiration post-OTP verification
        $shiftHours = $rememberMe ? '+7 days' : '+12 hours';
        $finalExpiresAt = date('Y-m-d H:i:s', strtotime($shiftHours));

        $email = $employee['email'] ?? '';
        $name  = $employee['full_name'] ?? ($employee['username'] ?? 'Employee');
        $empId = (int)$employee['id'];

        // Save session/OTP record to database
        try {
            $db = Database::getInstance();
            $db->query('user_sessions', 'POST', [
                'employee_id'    => $empId,
                'session_token'  => $sessionToken,
                'otp_code'       => $otpCode,
                'otp_expires_at' => $otpExpiresAt,
                'remember_me'    => $rememberMe ? 1 : 0,
                'expires_at'     => $finalExpiresAt,
            ]);
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
    public function verifyOtp(string $sessionToken, string $enteredCode): array
    {
        try {
            $db = Database::getInstance();
            $sessions = $db->select('user_sessions', ['session_token' => $sessionToken]);

            if (empty($sessions)) {
                return ['success' => false, 'error_type' => 'token_invalid', 'message' => 'Session expired. Please re-enter your credentials.'];
            }

            $session = $sessions[0];

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
                        'otp_expires_at' => date('Y-m-d H:i:s', time() - 10)
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
                            'otp_expires_at' => date('Y-m-d H:i:s', time() - 10)
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
            $_SESSION['session_expires']  = $session['expires_at'];
            $_SESSION['logged_in']        = true;

            // Set browser cookie (both global active session & account-specific device trust token)
            $cookieDuration = !empty($session['remember_me']) ? (7 * 86400) : (12 * 3600);
            setcookie('civentral_session', $sessionToken, time() + $cookieDuration, '/', '', false, true);
            setcookie('civentral_session_' . $employee['id'], $sessionToken, time() + $cookieDuration, '/', '', false, true);

            if (!empty($session['remember_me']) && class_exists('App\Services\RememberMeService')) {
                \App\Services\RememberMeService::createToken($employee);
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
     * Auto-validates an active 12-hour / 7-day session token from browser cookie.
     * Returns true if valid and activates session without requiring a new OTP code.
     */
    public function validateActiveToken(string $sessionToken): bool
    {
        if (empty($sessionToken)) return false;

        try {
            $db = Database::getInstance();
            $sessions = $db->select('user_sessions', ['session_token' => $sessionToken]);

            if (empty($sessions)) return false;

            $session = $sessions[0];
            if (strtotime($session['expires_at']) <= time()) {
                return false; // Token has expired
            }

            // Fetch employee
            $empId = (int)$session['employee_id'];
            $employees = $db->select('employees', ['id' => $empId]);

            if (empty($employees)) return false;

            $employee = $employees[0];

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
            $_SESSION['session_expires']  = $session['expires_at'];
            $_SESSION['logged_in']        = true;

            return true;

        } catch (\Throwable $e) {
            error_log('SessionAuthService validateActiveToken error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Checks if this specific device/cookie has an active verified 12h/7d session token.
     * Strictly requires matching cookieToken and valid expires_at timestamp.
     */
    public function hasActiveVerifiedSession(int $employeeId, string $cookieToken = ''): bool
    {
        if (empty($cookieToken)) {
            return false; // No device cookie token -> Always require OTP code!
        }

        try {
            $db = Database::getInstance();
            $sessions = $db->select('user_sessions', [
                'session_token' => $cookieToken,
                'employee_id'   => $employeeId
            ]);

            if (empty($sessions)) {
                return false; // Token not found -> Require OTP code!
            }

            $session = $sessions[0];
            $expiresTimestamp = strtotime($session['expires_at']);

            // Strictly check if 12-hour / 7-day token is still active
            if ($expiresTimestamp > time()) {
                return true; // Token still valid -> Skip OTP code!
            }

            // Expiration timestamp reached -> Token expired -> Require OTP code!
            return false;

        } catch (\Throwable $e) {
            error_log('SessionAuthService hasActiveVerifiedSession error: ' . $e->getMessage());
            return false;
        }
    }
}
