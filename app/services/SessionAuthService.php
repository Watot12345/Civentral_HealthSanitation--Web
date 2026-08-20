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
     * Generates a 6-digit OTP code, stores it in session/DB, and emails it to the employee.
     */
    public function generateAndSendOtp(array $employee, bool $rememberMe = false): array
    {
        $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpExpiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
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

        // Send email via MailService (PHPMailer / SMTP)
        // If developer testing option is ON (SHOW_VERIFICATION_CODE=true), skip slow external SMTP email
        $showDevCode = filter_var(Env::get('SHOW_VERIFICATION_CODE', Env::get('DEV_SHOW_OTP', false)), FILTER_VALIDATE_BOOLEAN);
        $skipEmail   = filter_var(Env::get('SKIP_EMAIL_IN_DEV', $showDevCode ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);

        $sent = false;
        if (!$skipEmail && !empty($email)) {
            $sent = $this->mailService->sendOtpEmail($email, $name, $otpCode, 5);
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
     * Verifies the 6-digit OTP code and activates the 12-hour (or 7-day) session.
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
            if (strtotime($session['otp_expires_at']) < time()) {
                return ['success' => false, 'error_type' => 'code_expired', 'message' => 'Security code has expired. Click "Resend Code" for a new 5-minute code.'];
            }

            if (trim($session['otp_code']) !== trim($enteredCode)) {
                return ['success' => false, 'error_type' => 'wrong_code', 'message' => 'Incorrect 6-digit verification code. Please check your email.'];
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
