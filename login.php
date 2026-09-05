<?php
// login.php - 2-Step OTP Employee Portal Authentication
require_once __DIR__ . '/Core/Env.php';
Env::load();

$appDebug = filter_var(Env::get('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
$appEnv   = Env::get('APP_ENV', 'production');

error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/app/helpers/EncryptionHelper.php';
require_once __DIR__ . '/app/Models/ActivityLog.php';
require_once __DIR__ . '/app/services/SessionAuthService.php';
require_once __DIR__ . '/app/services/RateLimiterService.php';
require_once __DIR__ . '/app/Models/ConsentLog.php';


// Handle POST Requests (Credential Check & OTP Verification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');

        $action = $_POST['action'] ?? 'login';

        // ----------------------------------------------------
        // ACTION 1: INITIAL CREDENTIAL CHECK -> TRIGGER OTP
        // ----------------------------------------------------
        if ($action === 'login') {
            $employeeId = trim($_POST['employee_id'] ?? '');
            $password   = $_POST['password'] ?? '';
            $rememberMe = !empty($_POST['remember_me']) && ($_POST['remember_me'] === 'true' || $_POST['remember_me'] === '1' || $_POST['remember_me'] === 'on');
            $agreeTerms = !empty($_POST['agree_terms']) && ($_POST['agree_terms'] === '1' || $_POST['agree_terms'] === 'true' || $_POST['agree_terms'] === true);

            if (!$agreeTerms) {
                echo json_encode(['success' => false, 'message' => 'You must review and agree to the Terms & Staff Privacy Policy to sign in.']);
                exit;
            }

            // --- RATE LIMITING & BRUTE-FORCE PROTECTION ---
            $limiter = new RateLimiterService();
            $clientIp = $limiter->getClientIp();
            
            // Enforce persistent lockout surviving session/cookie clearing
            $maxLoginAttempts = class_exists('Settings') ? (int)Settings::get('security.max_login_attempts', 5) : 5;
            $lockoutWindow = 900; // 15-minute window
            $accountLockKey = 'login_account_' . hash('sha256', $clientIp . '_' . strtolower($employeeId));
            $ipLockKey = 'login_ip_' . hash('sha256', $clientIp);

            // 1. IP-wide brute-force threshold (25 attempts / 15 mins)
            $ipStatus = $limiter->inspect($ipLockKey, 25, $lockoutWindow);
            if (!$ipStatus['allowed']) {
                $mins = max(1, ceil($ipStatus['reset'] / 60));
                echo json_encode([
                    'success' => false, 
                    'message' => "Too many failed attempts from your network. Security lockout active for {$mins} more minute(s)."
                ]);
                exit;
            }

            // 2. Targeted Account + IP Lockout
            $accountStatus = $limiter->inspect($accountLockKey, $maxLoginAttempts, $lockoutWindow);
            if (!$accountStatus['allowed']) {
                $mins = max(1, ceil($accountStatus['reset'] / 60));
                echo json_encode([
                    'success' => false, 
                    'message' => "Too many failed attempts. Security lockout active for {$mins} more minute(s)."
                ]);
                exit;
            }

            try {
                $db = Database::getInstance();
                $result = $db->select('employees', ['employee_id' => $employeeId]);
                $logModel = new ActivityLog();

                if (empty($result) || !is_array($result)) {
                    $limiter->hit($accountLockKey, $maxLoginAttempts, $lockoutWindow);
                    $limiter->hit($ipLockKey, 25, $lockoutWindow);
                    
                    $logModel->log("Failed login attempt", [
                        'user_name' => $employeeId ?: 'Unknown',
                        'role'      => 'Unknown',
                        'module'    => 'Authentication',
                        'details'   => "Employee ID not found: {$employeeId}",
                        'status'    => 'Failed',
                    ]);
                    echo json_encode(['success' => false, 'message' => 'Invalid employee ID or password.']);
                    exit;
                }

                $user = EncryptionHelper::decryptModel('employees', $result[0]);

                // BUG-003: Enforce account status check before credential evaluation
                $userStatus = strtolower(trim($user['status'] ?? 'active'));
                if (!empty($user['status']) && $userStatus !== 'active') {
                    $limiter->hit($accountLockKey, $maxLoginAttempts, $lockoutWindow);
                    $limiter->hit($ipLockKey, 25, $lockoutWindow);

                    $logModel->log("Failed login attempt (Inactive Account)", [
                        'user_name' => $user['full_name'] ?? $employeeId,
                        'role'      => $user['role_description'] ?? $user['role'] ?? 'Unknown',
                        'module'    => 'Authentication',
                        'details'   => "Login attempt for inactive/suspended account: {$employeeId} (Status: {$user['status']})",
                        'status'    => 'Failed',
                    ]);
                    echo json_encode([
                        'success' => false, 
                        'message' => "Access Denied: Account status is '" . ucfirst($user['status']) . "'. Please contact System Administrator or HR."
                    ]);
                    exit;
                }

                if (!password_verify($password, $user['password'])) {
                    $limiter->hit($accountLockKey, $maxLoginAttempts, $lockoutWindow);
                    $limiter->hit($ipLockKey, 25, $lockoutWindow);

                    $logModel->log("Failed login attempt", [
                        'user_name' => $user['full_name'] ?? $employeeId,
                        'role'      => $user['role_description'] ?? $user['role'] ?? 'Unknown',
                        'module'    => 'Authentication',
                        'details'   => "Wrong password for employee ID: {$employeeId}",
                        'status'    => 'Failed',
                    ]);
                    echo json_encode(['success' => false, 'message' => 'Invalid employee ID or password.']);
                    exit;
                }

                // Successful authentication: Reset failed attempt counter
                $limiter->clear($accountLockKey);
                $rateKey = 'login_rate_' . md5($clientIp . '_' . $employeeId);
                unset($_SESSION[$rateKey]);

                $authService = new SessionAuthService();
                $twoFactorEnforced = class_exists('Settings') ? (bool)Settings::get('security.two_factor_auth', false) : false;
                // Remembered Device Logic:
                // If this device already has an active verified session/cookie and 2FA is not forced on every login, bypass OTP
                $userCookieToken = $_COOKIE['civentral_session_' . $user['id']] ?? ($_COOKIE['civentral_session'] ?? '');
                $deviceRemembered = false;
                if (!empty($userCookieToken)) {
                    $deviceRemembered = $authService->validateActiveToken($userCookieToken);
                    if ($deviceRemembered && isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user['id']) {
                        $deviceRemembered = false;
                    }
                }
                
                $requireOtp = $twoFactorEnforced || !$deviceRemembered;

                // Direct login if device is already verified
                if (!$requireOtp) {
                    $functionalRole               = $user['role_description'] ?? $user['role'] ?? 'Employee';
                    $_SESSION['user_id']          = $user['id'];
                    $_SESSION['employee_id']      = $user['employee_id'];
                    $_SESSION['full_name']        = $user['full_name'];
                    $_SESSION['user_full_name']   = $user['full_name'];
                    $_SESSION['department']       = $user['department'] ?? '';
                    $_SESSION['user_department']  = $user['department'] ?? '';
                    $_SESSION['role']             = $functionalRole;
                    $_SESSION['role_description'] = $functionalRole;
                    $_SESSION['user_role']        = $functionalRole;
                    $_SESSION['logged_in']        = true;
                    $_SESSION['last_activity']    = time();

                    // Refresh/set active session cookie (10 days for remembered device)
                    $cookieDuration = class_exists('SessionAuthService') ? SessionAuthService::getRememberDurationSeconds() : 10 * 86400;
                    $sessionToken = !empty($userCookieToken) ? $userCookieToken : bin2hex(random_bytes(32));
                    setcookie('civentral_session', $sessionToken, time() + $cookieDuration, '/', '', false, true);
                    setcookie('civentral_session_' . $user['id'], $sessionToken, time() + $cookieDuration, '/', '', false, true);

                    if (class_exists('App\Services\RememberMeService')) {
                        \App\Services\RememberMeService::createToken($user);
                    }

                    $logModel->log("User logged in (Verified Device)", [
                        'user_id'   => $user['id'],
                        'user_name' => $user['full_name'],
                        'role'      => $user['role_description'] ?? $user['role'] ?? 'Employee',
                        'module'    => 'Authentication',
                        'details'   => "Direct login on verified device: {$user['employee_id']}",
                        'status'    => 'Success',
                    ]);

                    // Update last_login
                    try {
                        $db->update('employees', ['last_login' => date('Y-m-d H:i:sP')], ['id' => $user['id']], true);
                    } catch (\Throwable $ignored) {}

                    // Record Staff Terms & Privacy Policy Consent Log
                    try {
                        $consentModel = new ConsentLog();
                        $empSubjectId = $user['employee_id'] ?? ('EMP-' . $user['id']);
                        $existingConsent = $consentModel->findActiveConsent($empSubjectId, 'staff_privacy_policy_dpa');
                        if (!$existingConsent) {
                            $consentModel->create([
                                'subject_id'      => $empSubjectId,
                                'subject_type'    => 'employee',
                                'consent_type'    => 'staff_privacy_policy_dpa',
                                'consent_version' => '1.0',
                                'ip_address'      => $clientIp,
                                'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? 'Civentral-Staff-Portal'
                            ]);
                        }
                    } catch (\Throwable $consentErr) {
                        error_log('Staff consent logging error: ' . $consentErr->getMessage());
                    }


                    // Check password expiry
                    $passwordExpiryDays = class_exists('Settings') ? (int)Settings::get('security.password_expiry', 90) : 90;
                    $passwordExpired = false;
                    $lastPasswordUpdate = $user['password_updated_at'] ?? ($user['updated_at'] ?? null);
                    if ($passwordExpiryDays > 0 && !empty($lastPasswordUpdate)) {
                        $daysOld = (time() - strtotime($lastPasswordUpdate)) / 86400;
                        if ($daysOld > $passwordExpiryDays) {
                            $passwordExpired = true;
                            $_SESSION['password_expired_notice'] = "Your password is more than {$passwordExpiryDays} days old. Please consider updating it in account settings.";
                        }
                    }

                    echo json_encode([
                        'success'          => true,
                        'requires_otp'     => false,
                        'password_expired' => $passwordExpired,
                        'redirect'         => site_url('pages/dashboard.php'),
                        'user'             => [
                            'name'        => $user['full_name'],
                            'employee_id' => $user['employee_id']
                        ],
                        'message'          => $passwordExpired ? "Welcome back! Note: Your password has expired (> {$passwordExpiryDays} days old)." : 'Welcome back! Login successful.'
                    ]);
                    exit;
                }

                // Generate 6-digit OTP code & send email via SessionAuthService
                $otpResult   = $authService->generateAndSendOtp($user, $rememberMe);

                // Mask recipient email for security
                $rawEmail = $user['email'] ?? 'staff@health.gov.ph';
                $parts    = explode('@', $rawEmail);
                $maskedEmail = (strlen($parts[0]) > 2 ? substr($parts[0], 0, 2) . '***' : $parts[0]) . '@' . ($parts[1] ?? 'lgu.gov.ph');

                $responsePayload = [
                    'success'       => true,
                    'requires_otp'  => true,
                    'session_token' => $otpResult['session_token'],
                    'masked_email'  => $maskedEmail,
                    'user'          => [
                        'name'        => $user['full_name'],
                        'employee_id' => $user['employee_id']
                    ],
                    'message'       => 'Credentials verified. 6-digit security code sent to ' . $maskedEmail
                ];

                echo json_encode($responsePayload);
                exit;

            } catch (\Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Server error. Please contact IT support.']);
                exit;
            }
        }

        // ----------------------------------------------------
        // ACTION 2: VERIFY 6-DIGIT OTP CODE -> ACTIVATE SESSION
        // ----------------------------------------------------
        if ($action === 'verify_otp') {
            $sessionToken = trim($_POST['session_token'] ?? '');
            $otpCode      = trim($_POST['otp_code'] ?? '');
            $rememberMe   = isset($_POST['remember_me']) 
                ? (!empty($_POST['remember_me']) && ($_POST['remember_me'] === 'true' || $_POST['remember_me'] === '1' || $_POST['remember_me'] === 'on' || $_POST['remember_me'] === true))
                : true;

            if (empty($sessionToken) || empty($otpCode)) {
                echo json_encode(['success' => false, 'message' => 'Security code and session token are required.']);
                exit;
            }

            try {
                $authService = new SessionAuthService();
                $verifyResult = $authService->verifyOtp($sessionToken, $otpCode, $rememberMe);

                if ($verifyResult['success']) {
                    $user = $verifyResult['employee'];
                    $_SESSION['logged_in'] = true;

                    // Log successful login
                    $logModel = new ActivityLog();
                    $logModel->log("User logged in (OTP verified)", [
                        'user_id'   => $user['id'],
                        'user_name' => $user['full_name'],
                        'role'      => $user['role_description'] ?? $user['role'] ?? 'Employee',
                        'module'    => 'Authentication',
                        'details'   => "Logged in via OTP: {$user['employee_id']} ({$user['full_name']})",
                        'status'    => 'Success',
                    ]);

                    // Update last_login
                    try {
                        $db = Database::getInstance();
                        $db->update('employees', ['last_login' => date('Y-m-d H:i:sP')], ['id' => $user['id']], true);
                    } catch (\Throwable $ignored) {}

                    // Record Staff Terms & Privacy Policy Consent Log
                    try {
                        $consentModel = new ConsentLog();
                        $empSubjectId = $user['employee_id'] ?? ('EMP-' . $user['id']);
                        $existingConsent = $consentModel->findActiveConsent($empSubjectId, 'staff_privacy_policy_dpa');
                        if (!$existingConsent) {
                            $limiter = new RateLimiterService();
                            $otpClientIp = $limiter->getClientIp();
                            $consentModel->create([
                                'subject_id'      => $empSubjectId,
                                'subject_type'    => 'employee',
                                'consent_type'    => 'staff_privacy_policy_dpa',
                                'consent_version' => '1.0',
                                'ip_address'      => $otpClientIp,
                                'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? 'Civentral-Staff-Portal'
                            ]);
                        }
                    } catch (\Throwable $consentErr) {
                        error_log('Staff consent logging error (OTP): ' . $consentErr->getMessage());
                    }

                }

                echo json_encode($verifyResult);
                exit;

            } catch (\Exception $e) {
                error_log('OTP Verify error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Verification failed.']);
                exit;
            }
        }

        // ----------------------------------------------------
        // ACTION 3: STEP 1 - VERIFY EMPLOYEE ID & ACTIVE STATUS
        // ----------------------------------------------------
        if ($action === 'forgot_verify_id') {
            $employeeId = trim($_POST['employee_id'] ?? '');

            if (empty($employeeId)) {
                echo json_encode(['success' => false, 'message' => 'Please enter your Employee ID.']);
                exit;
            }

            try {
                $db = Database::getInstance();
                $employees = $db->select('employees', ['employee_id' => $employeeId]);
                if (empty($employees)) {
                    $employees = $db->select('employees', ['username' => $employeeId]);
                }

                if (empty($employees)) {
                    echo json_encode(['success' => false, 'message' => 'Employee ID not found in system records.']);
                    exit;
                }

                $user = $employees[0];
                $status = strtolower(trim($user['status'] ?? 'active'));

                // Check if employee is active vs resigned / inactive / terminated
                if (!empty($user['status']) && $status !== 'active') {
                    echo json_encode([
                        'success' => false,
                        'message' => "Access Denied: Account status is '" . ucfirst($user['status']) . "' (Resigned/Inactive). Password reset is not permitted for departed personnel. Please contact HR."
                    ]);
                    exit;
                }

                $lookupToken = bin2hex(random_bytes(16));
                $_SESSION['forgot_lookup_' . $lookupToken] = (int)$user['id'];

                // Mask email for user preview
                $rawEmail = $user['email'] ?? 'staff@caloocan.gov.ph';
                $parts = explode('@', $rawEmail);
                $maskedEmail = (strlen($parts[0]) > 2 ? substr($parts[0], 0, 2) . '***' : $parts[0]) . '@' . ($parts[1] ?? 'lgu.gov.ph');

                // Return ONLY Name and Department (and masked email for confirmation)
                echo json_encode([
                    'success'      => true,
                    'lookup_token' => $lookupToken,
                    'full_name'    => $user['full_name'] ?? 'Government Employee',
                    'department'   => $user['department'] ?? 'LGU Operations',
                    'masked_email' => $maskedEmail,
                    'message'      => 'Employee verified successfully.'
                ]);
                exit;

            } catch (\Exception $e) {
                error_log('Forgot Verify ID error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Server error. Please contact IT support.']);
                exit;
            }
        }

        // ----------------------------------------------------
        // ACTION 4: STEP 2 - CONFIRM & SEND OTP CODE TO GMAIL
        // ----------------------------------------------------
        if ($action === 'forgot_send_code') {
            $lookupToken = trim($_POST['lookup_token'] ?? '');

            if (empty($lookupToken) || empty($_SESSION['forgot_lookup_' . $lookupToken])) {
                echo json_encode(['success' => false, 'message' => 'Identity confirmation expired. Please re-enter your Employee ID.']);
                exit;
            }

            $empId = (int)$_SESSION['forgot_lookup_' . $lookupToken];

            try {
                $db = Database::getInstance();
                $employees = $db->select('employees', ['id' => $empId]);

                if (empty($employees)) {
                    echo json_encode(['success' => false, 'message' => 'Employee account record not found.']);
                    exit;
                }

                $user = $employees[0];
                $resetToken = bin2hex(random_bytes(32));
                $otpCode = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otpExpiresAt = date('Y-m-d H:i:s', strtotime('+3 minutes'));

                // Store in user_sessions
                $db->query('user_sessions', 'POST', [
                    'employee_id'    => $empId,
                    'session_token'  => 'reset_' . $resetToken,
                    'otp_code'       => $otpCode,
                    'otp_expires_at' => $otpExpiresAt,
                    'remember_me'    => 0,
                    'expires_at'     => $otpExpiresAt,
                ]);

                // Send email to registered Gmail / Email address
                require_once __DIR__ . '/app/services/MailService.php';
                $mailService = new MailService();
                $email = $user['email'] ?? '';
                $name = $user['full_name'] ?? ($user['username'] ?? 'Employee');

                if (!empty($email)) {
                    $mailService->sendOtpEmail($email, $name, $otpCode, 3);
                }

                $parts = explode('@', $email ?: 'staff@health.gov.ph');
                $maskedEmail = (strlen($parts[0]) > 2 ? substr($parts[0], 0, 2) . '***' : $parts[0]) . '@' . ($parts[1] ?? 'lgu.gov.ph');

                $payload = [
                    'success'      => true,
                    'reset_token'  => $resetToken,
                    'masked_email' => $maskedEmail,
                    'message'      => "Verification code sent to {$maskedEmail}."
                ];

                echo json_encode($payload);
                exit;

            } catch (\Exception $e) {
                error_log('Forgot Send Code error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to dispatch code. Please try again.']);
                exit;
            }
        }

        // ----------------------------------------------------
        // ACTION 5: STEP 3 - VERIFY 6-DIGIT CODE FIRST
        // ----------------------------------------------------
        if ($action === 'forgot_verify_code') {
            $resetToken = trim($_POST['reset_token'] ?? '');
            $otpCode    = trim($_POST['otp_code'] ?? '');

            if (empty($resetToken) || empty($otpCode)) {
                echo json_encode(['success' => false, 'message' => 'Please enter the 6-digit verification code.']);
                exit;
            }

            try {
                $db = Database::getInstance();
                $sessions = $db->select('user_sessions', ['session_token' => 'reset_' . $resetToken]);

                if (empty($sessions)) {
                    echo json_encode(['success' => false, 'message' => 'Verification session expired. Please request a new code.']);
                    exit;
                }

                $session = $sessions[0];
                if (strtotime($session['otp_expires_at']) < time() || empty($session['otp_code'])) {
                    echo json_encode(['success' => false, 'message' => 'Verification code has expired (3-minute limit). Please request a new code.']);
                    exit;
                }

                // Brute force tracking for password reset (Max 5 attempts)
                $cacheDir = __DIR__ . '/storage/cache';
                if (!is_dir($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }
                $attemptFile = $cacheDir . '/reset_attempts_' . md5($resetToken) . '.json';
                $attemptData = ['attempts' => 0, 'created_at' => time()];
                if (file_exists($attemptFile)) {
                    $raw = @file_get_contents($attemptFile);
                    $attemptData = json_decode($raw, true) ?: $attemptData;
                }

                if (($attemptData['attempts'] ?? 0) >= 5) {
                    try {
                        $db->update('user_sessions', [
                            'otp_code' => null,
                            'otp_expires_at' => date('Y-m-d H:i:s', time() - 10)
                        ], ['id' => $session['id']], true);
                    } catch (\Throwable $ignored) {}

                    echo json_encode([
                        'success' => false,
                        'locked' => true,
                        'message' => 'Maximum verification attempts exceeded (5/5). This code is locked. Please request a new code.'
                    ]);
                    exit;
                }

                if (trim($session['otp_code']) !== trim($otpCode)) {
                    $attemptData['attempts'] = ($attemptData['attempts'] ?? 0) + 1;
                    @file_put_contents($attemptFile, json_encode($attemptData));
                    $rem = max(0, 5 - $attemptData['attempts']);

                    if ($rem === 0) {
                        try {
                            $db->update('user_sessions', [
                                'otp_code' => null,
                                'otp_expires_at' => date('Y-m-d H:i:s', time() - 10)
                            ], ['id' => $session['id']], true);
                        } catch (\Throwable $ignored) {}

                        echo json_encode([
                            'success' => false,
                            'locked' => true,
                            'message' => 'Maximum verification attempts exceeded (5/5). Code locked. Please request a new code.'
                        ]);
                        exit;
                    }

                    echo json_encode([
                        'success' => false,
                        'message' => "Incorrect 6-digit verification code. ({$rem} attempt" . ($rem === 1 ? '' : 's') . " remaining before code is locked)"
                    ]);
                    exit;
                }

                if (file_exists($attemptFile)) {
                    @unlink($attemptFile);
                }

                // Code is correct! Generate verified token for setting password
                $verifiedToken = bin2hex(random_bytes(32));
                $db->update('user_sessions', [
                    'session_token'  => 'verified_reset_' . $verifiedToken,
                    'otp_expires_at' => date('Y-m-d H:i:s', strtotime('+15 minutes'))
                ], ['id' => $session['id']], true);

                echo json_encode([
                    'success'        => true,
                    'verified_token' => $verifiedToken,
                    'message'        => 'Security code verified! Please set your new password.'
                ]);
                exit;

            } catch (\Exception $e) {
                error_log('Forgot verify code error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to verify security code. Please try again.']);
                exit;
            }
        }

        // ----------------------------------------------------
        // ACTION 6: STEP 4 - SET NEW PASSWORD (AFTER VERIFICATION)
        // ----------------------------------------------------
        if ($action === 'reset_password') {
            $verifiedToken   = trim($_POST['verified_token'] ?? '');
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($verifiedToken) || empty($newPassword)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required.']);
                exit;
            }

            if (strlen($newPassword) < 6) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long.']);
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match. Please re-type your new password.']);
                exit;
            }

            try {
                $db = Database::getInstance();
                $sessions = $db->select('user_sessions', ['session_token' => 'verified_reset_' . $verifiedToken]);

                if (empty($sessions)) {
                    echo json_encode(['success' => false, 'message' => 'Password reset session expired or unverified. Please restart recovery.']);
                    exit;
                }

                $session = $sessions[0];
                if (strtotime($session['otp_expires_at']) < time()) {
                    echo json_encode(['success' => false, 'message' => 'Session expired. Please restart recovery.']);
                    exit;
                }

                $empId = (int)$session['employee_id'];
                $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                $db->update('employees', ['password' => $hashedPassword, 'updated_at' => date('Y-m-d H:i:sP')], ['id' => $empId], true);

                // Clean up reset session
                try {
                    $db->delete('user_sessions', ['id' => $session['id']]);
                } catch (\Throwable $ignored) {}

                $logModel = new ActivityLog();
                $employees = $db->select('employees', ['id' => $empId]);
                $empRecord = $employees[0] ?? [];

                $logModel->log("Password Reset Completed", [
                    'user_id'   => $empId,
                    'user_name' => $empRecord['full_name'] ?? 'Employee',
                    'role'      => $empRecord['role_description'] ?? $empRecord['role'] ?? 'Employee',
                    'module'    => 'Authentication',
                    'details'   => "Password successfully reset for employee ID: " . ($empRecord['employee_id'] ?? ''),
                    'status'    => 'Success',
                ]);

                echo json_encode([
                    'success'     => true,
                    'employee_id' => $empRecord['employee_id'] ?? '',
                    'message'     => 'Password reset successful! You can now sign in with your new password.'
                ]);
                exit;

            } catch (\Exception $e) {
                error_log('Reset password error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Failed to reset password. Please try again.']);
                exit;
            }
        }
    }
}

// Redirect if already logged in or if active valid 12h/7d token cookie exists
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: ' . site_url('pages/dashboard.php'));
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civentral</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
    @theme {
        --color-brand-light: #EEF5FF;
        --color-brand-border: #B4D4FF;
        --color-brand-medium: #86B6F6;
        --color-brand-dark: #176B87;
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    /* Button loading animation */
    .btn-loader {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .btn-loader .dot {
        width: 5px;
        height: 5px;
        background: white;
        border-radius: 50%;
        animation: btnBounce 1.4s ease-in-out infinite;
    }

    .btn-loader .dot:nth-child(2) { animation-delay: 0.16s; }
    .btn-loader .dot:nth-child(3) { animation-delay: 0.32s; }

    @keyframes btnBounce {
        0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }

    @keyframes shakeError {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-6px); }
        40%, 80% { transform: translateX(6px); }
    }
    .shake-error { animation: shakeError 0.4s ease-in-out; }

    .input-field:focus {
        box-shadow: 0 0 0 3px rgba(134, 182, 246, 0.2);
    }

    /* Simple Toggle Switch */
    .toggle-switch-input {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        pointer-events: none;
    }
    .toggle-switch-track {
        display: inline-block;
        width: 38px;
        height: 22px;
        background-color: #176B87;
        border-radius: 9999px;
        position: relative;
        cursor: pointer;
        transition: background-color 0.2s ease;
        flex-shrink: 0;
    }
    .toggle-switch-thumb {
        position: absolute;
        top: 3px;
        left: 3px;
        width: 16px;
        height: 16px;
        background-color: #ffffff;
        border-radius: 9999px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
        transform: translateX(16px);
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .toggle-switch-input:not(:checked) + .toggle-switch-track {
        background-color: #cbd5e1;
    }
    .toggle-switch-input:not(:checked) + .toggle-switch-track .toggle-switch-thumb {
        transform: translateX(0px);
    }
    </style>
</head>
<body class="bg-white min-h-screen font-sans antialiased selection:bg-brand-medium selection:text-white">

    <div class="min-h-screen flex flex-col md:flex-row relative">
        <div class="hidden md:block md:w-1/2 lg:w-3/5 bg-[url(assets/images/building-bg.jpg)] bg-cover bg-left bg-no-repeat mix-blend-multiply relative">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white"></div>
        </div>

        <div class="flex-1 flex flex-col justify-start p-4 sm:p-6 md:p-8 lg:p-10 bg-white z-10 overflow-y-auto min-h-screen">
            <div class="w-full max-w-md mx-auto space-y-3 relative">
                <div class="w-full space-y-2">
                    <div class="flex flex-col items-center justify-center text-center pb-1 w-full">
                        <img src="assets/images/logo.png" alt="Portal Graphic" class="h-14 md:h-16 w-auto object-contain mb-1">
                        <span class="text-2xl md:text-3xl font-black text-brand-medium tracking-[0.2em] uppercase font-sans">
                            CIVENTRAL
                        </span>
                    </div>
                    <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-0.5">
                        <span class="text-[11px] font-bold tracking-widest text-gray-400 uppercase">Employee Access</span>
                        <h1 class="text-xl md:text-2xl font-extrabold text-gray-600 tracking-tight">Sign in to your office</h1>
                        <p class="text-xs text-gray-500">Enter your LGU-issued credentials to continue.</p>
                    </div>
                </div>

                <?php if (isset($_GET['session_expired'])): ?>
                    <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-xs font-semibold text-amber-800 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm shrink-0"></i>
                        <span>Your session has expired due to inactivity. Please sign in again.</span>
                    </div>
                <?php endif; ?>

                <form id="loginForm" class="space-y-3 pt-1" autocomplete="on">
                    <!-- Inline Error Banner for Login Failed -->
                    <div id="loginErrorBanner" class="hidden p-2.5 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 text-center items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                        <span id="loginErrorMessage">Invalid Employee ID or Password.</span>
                    </div>

                    <div class="space-y-1">
                        <label for="employeeId" class="text-xs font-semibold text-gray-500">LGU Employee ID</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-gray-400">
                                <i class="fa-solid fa-user-tie text-xs"></i>
                            </span>
                            <input
                                type="text"
                                id="employeeId"
                                name="employee_id"
                                placeholder="e.g. HSA-ADMIN-01, HCD-0001, SD-0001"
                                required
                                autocomplete="username"
                                class="input-field w-full pl-10 pr-4 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium transition"
                            />
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label for="password" class="text-xs font-semibold text-gray-500">Password</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-gray-400">
                                <i class="fa-solid fa-key text-xs"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                class="input-field w-full pl-10 pr-10 py-2 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium transition"
                            />
                            <button
                                type="button"
                                onclick="togglePasswordVisibility()"
                                class="absolute right-3.5 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
                                tabindex="-1"
                                aria-label="Toggle password visibility"
                            >
                                <i id="passwordIcon" class="fa-solid fa-eye-slash text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-0.5">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" id="rememberMe" name="remember_me" checked class="w-3.5 h-3.5 text-brand-medium border-gray-300 rounded focus:ring-brand-medium accent-brand-medium cursor-pointer" />
                            <span class="text-xs text-gray-500">Keep me signed in</span>
                        </label>
                        <a href="javascript:void(0)" onclick="openForgotPasswordModal()" class="text-xs font-semibold text-brand-medium hover:underline cursor-pointer">Forgot password?</a>
                    </div>

                    <!-- Staff DPA Terms & Privacy Policy Agreement -->
                    <div class="pt-1.5 pb-0.5 border-t border-gray-100">
                        <div class="flex items-start space-x-2">
                            <input
                                type="checkbox"
                                id="agreeTermsCheckbox"
                                name="agree_terms"
                                required
                                onclick="handleTermsCheckboxClick(event)"
                                class="w-3.5 h-3.5 mt-0.5 text-brand-dark border-gray-300 rounded focus:ring-brand-medium accent-brand-medium cursor-pointer"
                            />
                            <label for="agreeTermsCheckbox" class="text-[11px] text-gray-600 select-none leading-relaxed">
                                I agree to the 
                                <button type="button" onclick="openPrivacyPolicyModal()" class="text-brand-dark font-bold hover:underline cursor-pointer inline-flex items-center gap-1">
                                    <span>Terms &amp; Staff Privacy Policy</span>
                                    <i class="fa-solid fa-up-right-from-square text-[9px]"></i>
                                </button>
                                under RA 10173 (DPA).
                            </label>
                        </div>
                        <p id="policyCheckError" class="hidden text-[11px] font-semibold text-red-600 mt-1 flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>You must review and agree to the Terms &amp; Privacy Policy to proceed.</span>
                        </p>
                    </div>

                    <button type="submit" id="loginButton" class="w-full py-2.5 px-4 bg-brand-medium hover:bg-opacity-90 text-white font-medium rounded-lg text-sm transition shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-medium focus:ring-offset-2 cursor-pointer">
                        <span id="btnText">Sign in</span>
                    </button>
                </form>

                <div class="relative flex py-1 items-center">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <span class="flex-shrink mx-3 text-[10px] font-semibold text-gray-400 tracking-wider">OR</span>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>

                <a href="index.php" class="inline-block text-center w-full py-2.5 px-4 bg-white hover:bg-brand-medium hover:text-white text-brand-medium font-medium border border-brand-medium rounded-lg text-sm transition focus:outline-none">
                    Back to Home
                </a>

                <div class="text-center pt-2 pb-2">
                    <p class="text-[9px] md:text-[10px] font-bold text-gray-400 tracking-wider uppercase max-w-xs mx-auto leading-relaxed">
                        DEPT ACCESS ONLY · UNAUTHORIZED USE IS LOGGED & PROSECUTABLE UNDER RA 8792
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2-STEP OTP SECURITY CODE VERIFICATION MODAL                  -->
    <!-- ============================================================ -->
    <div id="otpModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-brand-border overflow-hidden transform transition-all">
            <div class="bg-brand-medium p-6 text-center text-white relative">
                <div class="h-12 w-12 rounded-full bg-white/20 border border-white/40 flex items-center justify-center mx-auto mb-2 text-white text-xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-lg font-extrabold tracking-tight">Security Verification</h3>
                <p class="text-xs text-white/90 mt-1">2-Step Employee Portal Authentication</p>
            </div>

            <div class="p-6 space-y-5">
                <div class="text-center space-y-1">
                    <p class="text-xs text-gray-500">A 6-digit security code has been sent to:</p>
                    <p class="text-sm font-bold text-gray-800" id="otpMaskedEmail">s***@health.gov.ph</p>
                </div>

                <form id="otpForm" class="space-y-4">
                    <input type="hidden" id="otpSessionToken" name="session_token" value="" />

                    <!-- Security Code Entry Form -->
                    <div id="devOtpBox" class="hidden p-3 rounded-xl bg-amber-50 border border-amber-300 text-xs text-amber-900 items-center justify-between gap-2 shadow-xs">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-amber-200 text-amber-900 font-mono font-bold text-[10px] uppercase tracking-wider flex items-center gap-1">
                                <i class="fa-solid fa-flask"></i> DEV CODE
                            </span>
                            <span><strong id="devOtpCodeValue" class="font-mono text-base font-black tracking-widest text-amber-950">------</strong></span>
                        </div>
                        <button type="button" onclick="autofillDevOtp()" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[10px] font-bold transition shadow-xs cursor-pointer flex items-center gap-1">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-fill
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider text-center mb-2">Enter 6-Digit Code</label>
                        <input
                            type="text"
                            id="otpCodeInput"
                            name="otp_code"
                            maxlength="6"
                            placeholder="000000"
                            required
                            autocomplete="one-time-code"
                            class="w-full py-3 text-center text-2xl font-black tracking-[0.5em] font-mono border-2 border-brand-border rounded-xl focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/20 outline-none text-brand-dark bg-gray-50"
                        />
                    </div>

                    <!-- Remember This Device Toggle (Default Toggled ON) -->
                    <div class="flex items-center pt-1 pb-1">
                        <label class="flex items-center space-x-2.5 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                id="otpRememberDevice"
                                name="remember_device"
                                checked
                                class="toggle-switch-input"
                            />
                            <span class="toggle-switch-track">
                                <span class="toggle-switch-thumb"></span>
                            </span>
                            <span class="text-xs font-semibold text-gray-700">Remember this device</span>
                        </label>
                    </div>

                    <!-- Inline Error Banner -->
                    <div id="otpErrorBanner" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 text-center flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                        <span id="otpErrorMessage">Incorrect 6-digit code. Please try again.</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500 pt-1">
                        <span>Code expires in <strong id="otpTimer" class="text-gray-800">3:00</strong></span>
                        <button type="button" onclick="resendOtp()" class="text-brand-medium font-bold hover:underline cursor-pointer">Resend Code</button>
                    </div>

                    <button
                        type="submit"
                        id="otpSubmitBtn"
                        class="w-full py-3 bg-brand-medium hover:bg-brand-dark text-white font-bold rounded-xl text-sm transition shadow-md focus:outline-none cursor-pointer"
                    >
                        <span id="otpBtnText">Verify & Access Portal</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- FORGOT PASSWORD & ACCOUNT RECOVERY MODAL                     -->
    <!-- ============================================================ -->
    <div id="forgotPasswordModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-brand-border overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-brand-dark to-brand-medium p-5 text-center text-white relative">
                <button type="button" onclick="closeForgotPasswordModal()" class="absolute top-3.5 right-3.5 text-white/80 hover:text-white text-lg cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <div class="h-11 w-11 rounded-full bg-white/20 border border-white/40 flex items-center justify-center mx-auto mb-1.5 text-white text-lg">
                    <i class="fa-solid fa-key"></i>
                </div>
                <h3 class="text-base font-extrabold tracking-tight">Account Recovery</h3>
                <p id="forgotModalStepLabel" class="text-xs text-white/90 mt-0.5">Step 1 of 4: Enter Employee ID</p>
            </div>

            <!-- STEP 1: Identification & Active Status Check -->
            <div id="forgotStep1" class="p-6 space-y-4">
                <p class="text-xs text-gray-600 leading-relaxed">
                    Please enter your <strong>Employee ID</strong>. The system will verify your active employment status before initiating recovery.
                </p>

                <form id="forgotStep1Form" class="space-y-4" onsubmit="handleForgotVerifyId(event)">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Employee ID</label>
                        <input
                            type="text"
                            id="forgotIdentifier"
                            placeholder="e.g., HSA-ADMIN-01, HCD-0001, SD-0001"
                            required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-medium/20 focus:border-brand-medium transition"
                        />
                    </div>

                    <!-- Step 1 Error Banner -->
                    <div id="forgotStep1ErrorBanner" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 text-center flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                        <span id="forgotStep1ErrorMessage">Employee ID not found.</span>
                    </div>

                    <button
                        type="submit"
                        id="forgotVerifyBtn"
                        class="w-full py-3 bg-brand-medium hover:bg-brand-dark text-white font-bold rounded-xl text-sm transition shadow-md focus:outline-none cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span id="forgotVerifyBtnText">Verify Employee ID</span>
                    </button>

                    <div class="text-center pt-1">
                        <button type="button" onclick="closeForgotPasswordModal()" class="text-xs text-gray-500 hover:text-brand-dark font-medium">
                            Cancel and return to sign in
                        </button>
                    </div>
                </form>
            </div>

            <!-- STEP 2: Identity Confirmation Card (Name & Department Only) -->
            <div id="forgotStep2" class="hidden p-6 space-y-4">
                <input type="hidden" id="forgotLookupTokenHidden" value="" />

                <div class="p-4 rounded-xl bg-brand-light border border-brand-border space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-brand-medium text-white flex items-center justify-center flex-shrink-0 font-bold text-sm">
                            <i class="fa-solid fa-user-check"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-brand-dark uppercase tracking-wider">Employee Found</p>
                            <p class="text-sm font-extrabold text-gray-900" id="confirmFullName">---</p>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-brand-border/60 text-xs space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Department:</span>
                            <span class="font-bold text-gray-800" id="confirmDepartment">---</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Send Code To:</span>
                            <span class="font-mono font-bold text-brand-dark" id="confirmMaskedEmail">---</span>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-600 text-center leading-relaxed">
                    Is this your account? Click below to send a 6-digit verification code to your registered email address.
                </p>

                <!-- Step 2 Error Banner -->
                <div id="forgotStep2ErrorBanner" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 text-center flex items-center justify-center gap-1.5 transition-all">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                    <span id="forgotStep2ErrorMessage">Failed to dispatch code.</span>
                </div>

                <div class="space-y-2 pt-1">
                    <button
                        type="button"
                        id="confirmSendCodeBtn"
                        onclick="handleForgotSendCode()"
                        class="w-full py-3 bg-brand-medium hover:bg-brand-dark text-white font-bold rounded-xl text-sm transition shadow-md focus:outline-none cursor-pointer flex items-center justify-center gap-2"
                    >
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                        <span id="confirmSendCodeBtnText">Yes, Send Verification Code</span>
                    </button>

                    <button
                        type="button"
                        onclick="goBackToStep1()"
                        class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-xs transition cursor-pointer"
                    >
                        Not me / Enter different ID
                    </button>
                </div>
            </div>

            <!-- STEP 3: Enter & Verify 6-Digit Code FIRST -->
            <div id="forgotStep3" class="hidden p-6 space-y-4">
                <input type="hidden" id="resetTokenHidden" value="" />

                <div class="text-center space-y-1">
                    <p class="text-xs text-gray-500">A 6-digit security code has been sent to:</p>
                    <p class="text-sm font-bold text-gray-800" id="resetMaskedEmail">u***@caloocan.gov.ph</p>
                </div>

                <form id="verifyCodeForm" class="space-y-4" onsubmit="handleForgotVerifyCode(event)">
                    <!-- Dev Mode Code Display -->
                    <div id="forgotDevOtpBox" class="hidden p-3 rounded-xl bg-amber-50 border border-amber-300 text-xs text-amber-900 items-center justify-between gap-2 shadow-xs">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded bg-amber-200 text-amber-900 font-mono font-bold text-[10px] uppercase tracking-wider flex items-center gap-1">
                                <i class="fa-solid fa-flask"></i> DEV CODE
                            </span>
                            <span><strong id="forgotDevOtpCodeValue" class="font-mono text-base font-black tracking-widest text-amber-950">------</strong></span>
                        </div>
                        <button type="button" onclick="autofillForgotDevOtp()" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[10px] font-bold transition shadow-xs cursor-pointer flex items-center gap-1">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-fill
                        </button>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1 text-center">Enter 6-Digit Code</label>
                        <input
                            type="text"
                            id="forgotOtpInput"
                            maxlength="6"
                            placeholder="000000"
                            required
                            class="w-full py-2.5 text-center text-xl font-black tracking-[0.4em] font-mono border-2 border-brand-border rounded-xl focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/20 outline-none text-brand-dark bg-gray-50"
                        />
                    </div>

                    <!-- Step 3 Error Banner -->
                    <div id="forgotStep3ErrorBanner" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 text-center flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                        <span id="forgotStep3ErrorMessage">Incorrect security code.</span>
                    </div>

                    <button
                        type="submit"
                        id="verifyCodeBtn"
                        class="w-full py-3 bg-brand-medium hover:bg-brand-dark text-white font-bold rounded-xl text-sm transition shadow-md focus:outline-none cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span id="verifyCodeBtnText">Verify Security Code</span>
                    </button>

                    <div class="flex items-center justify-between text-xs text-gray-500 pt-1">
                        <button type="button" onclick="handleForgotSendCode()" class="text-brand-medium hover:underline font-semibold cursor-pointer">Resend Code</button>
                        <button type="button" onclick="goBackToStep1()" class="hover:text-gray-800">Change Employee ID</button>
                    </div>
                </form>
            </div>

            <!-- STEP 4: Set New Password & Confirm -->
            <div id="forgotStep4" class="hidden p-6 space-y-4">
                <div class="text-center space-y-1">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-semibold border border-green-200">
                        <i class="fa-solid fa-circle-check text-xs"></i> Code Verified Successfully
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Please enter and confirm your new account password.</p>
                </div>

                <form id="resetForm" class="space-y-4" onsubmit="handleResetPassword(event)">
                    <input type="hidden" id="verifiedTokenHidden" value="" />

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">New Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                id="forgotNewPass"
                                placeholder="At least 6 characters"
                                required
                                minlength="6"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/20 transition pr-10"
                            />
                            <button
                                type="button"
                                onclick="toggleForgotPass('forgotNewPass', 'forgotNewPassIcon')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
                            >
                                <i id="forgotNewPassIcon" class="fa-solid fa-eye-slash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1">Confirm New Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                id="forgotConfirmPass"
                                placeholder="Re-type new password"
                                required
                                minlength="6"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:border-brand-medium focus:ring-2 focus:ring-brand-medium/20 transition pr-10"
                            />
                            <button
                                type="button"
                                onclick="toggleForgotPass('forgotConfirmPass', 'forgotConfirmPassIcon')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
                            >
                                <i id="forgotConfirmPassIcon" class="fa-solid fa-eye-slash text-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Step 4 Error Banner -->
                    <div id="resetErrorBanner" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 text-center flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                        <span id="resetErrorMessage">Passwords do not match.</span>
                    </div>

                    <button
                        type="submit"
                        id="resetSubmitBtn"
                        class="w-full py-3 bg-brand-medium hover:bg-brand-dark text-white font-bold rounded-xl text-sm transition shadow-md focus:outline-none cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span id="resetBtnText">Save New Password &amp; Sign In</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- STAFF PRIVACY POLICY & TERMS MODAL (SCROLL TO AGREE)         -->
    <!-- ============================================================ -->
    <div id="privacyPolicyModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-brand-border overflow-hidden flex flex-col max-h-[90vh] animate-scale-up">
            <!-- Modal Header -->
            <div class="bg-brand-dark p-5 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-brand-medium text-lg border border-white/20">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-white">Staff Privacy Policy &amp; Terms of Access</h3>
                        <p class="text-xs text-brand-light/70">Republic Act No. 10173 (Philippine Data Privacy Act of 2012)</p>
                    </div>
                </div>
                <button
                    type="button"
                    onclick="closePrivacyPolicyModal()"
                    class="text-white/70 hover:text-white hover:bg-white/10 w-8 h-8 rounded-lg flex items-center justify-center transition cursor-pointer"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Scroll Notice Banner -->
            <div id="scrollNoticeBanner" class="bg-amber-50 px-5 py-2.5 border-b border-amber-200 text-xs text-amber-900 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-arrow-down-long text-amber-600 animate-bounce"></i>
                    <span id="scrollNoticeText" class="font-semibold">Please read and scroll to the end of the policy to enable the Agree button.</span>
                </div>
            </div>


            <!-- Scrollable Policy Content Container -->
            <div id="policyScrollContainer" onscroll="handlePolicyScroll()" class="p-6 overflow-y-auto space-y-4 text-xs leading-relaxed text-slate-700 select-none">
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                    <p class="font-bold text-slate-900 uppercase text-[11px] tracking-wide">Civentral Portal — Employee Data Privacy &amp; Terms of Access</p>
                    <p class="text-slate-500 text-[11px]">Guidelines for handling citizen, patient, and permit information under Republic Act No. 10173.</p>
                </div>

                <!-- ITEM 1: PERSONAL DATA PROTECTION -->
                <div class="p-4 rounded-xl border border-slate-200 bg-white space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-blue-100 text-blue-800 flex items-center justify-center font-black text-xs">1</span>
                        <h4 class="font-bold text-slate-900 text-sm">Personal Data Protection</h4>
                    </div>
                    <p class="text-slate-600">
                        <strong>Personal information complies with the Data Privacy Act.</strong>
                    </p>
                    <ul class="list-disc pl-5 space-y-1 text-slate-600">
                        <li>All citizen names, contact numbers, residential addresses, and medical records are strictly confidential.</li>
                        <li>Do not share, screenshot, copy, or distribute citizen information outside official duties.</li>
                        <li>Keep confidential data masked on screen when assisting in public areas or shared workspaces.</li>
                    </ul>
                </div>

                <!-- ITEM 2: CONSENT MANAGEMENT -->
                <div class="p-4 rounded-xl border border-slate-200 bg-white space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center font-black text-xs">2</span>
                        <h4 class="font-bold text-slate-900 text-sm">Consent Management</h4>
                    </div>
                    <p class="text-slate-600">
                        <strong>User consent is properly collected and managed.</strong>
                    </p>
                    <ul class="list-disc pl-5 space-y-1 text-slate-600">
                        <li>Citizens must willingly agree to data processing before registration or medical intake.</li>
                        <li>Never check privacy consent boxes on behalf of a citizen without their explicit permission.</li>
                        <li>Citizens have the right to withdraw their consent at any time through official request channels.</li>
                    </ul>
                </div>

                <!-- ITEM 3: RIGHT TO DELETE DATA -->
                <div class="p-4 rounded-xl border border-slate-200 bg-white space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-800 flex items-center justify-center font-black text-xs">3</span>
                        <h4 class="font-bold text-slate-900 text-sm">Right to Delete Data</h4>
                    </div>
                    <p class="text-slate-600">
                        <strong>Users can request deletion of personal information.</strong>
                    </p>
                    <ul class="list-disc pl-5 space-y-1 text-slate-600">
                        <li>Citizens have the right to request erasure or anonymization of their personal information.</li>
                        <li>Staff must route all citizen deletion requests to authorized administrators for compliance review.</li>
                        <li>Clinical history will be handled in accordance with Department of Health retention policies.</li>
                    </ul>
                </div>

                <!-- EMPLOYEE ACKNOWLEDGMENT -->
                <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-950 text-xs flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm mt-0.5"></i>
                    <div>
                        <p class="font-bold">Employee Acknowledgment</p>
                        <p class="text-emerald-900 mt-0.5">By clicking <strong>"I Have Read &amp; Agree"</strong>, you confirm your responsibility to protect citizen data and follow these privacy policies.</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3">
                <button
                    type="button"
                    onclick="closePrivacyPolicyModal()"
                    class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-200/70 rounded-xl transition cursor-pointer"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    id="agreePolicyModalBtn"
                    disabled
                    onclick="acceptPrivacyPolicyFromModal()"
                    class="px-5 py-2.5 bg-brand-dark hover:bg-brand-medium text-white text-xs font-bold rounded-xl transition shadow-sm cursor-not-allowed opacity-40 flex items-center gap-2"
                >
                    <i class="fa-solid fa-check"></i>
                    <span id="agreePolicyModalBtnText">Scroll to Bottom to Agree</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');
        if (!passwordInput || !passwordIcon) return;
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        passwordIcon.className = isHidden ? 'fa-solid fa-eye text-sm' : 'fa-solid fa-eye-slash text-sm';
    }

    function toggleForgotPass(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!input || !icon) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.className = isHidden ? 'fa-solid fa-eye text-xs' : 'fa-solid fa-eye-slash text-xs';
    }

    function openForgotPasswordModal() {
        const modal = document.getElementById('forgotPasswordModal');
        goBackToStep1();

        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        const empVal = document.getElementById('employeeId')?.value.trim();
        const identifierInput = document.getElementById('forgotIdentifier');
        if (identifierInput) {
            if (empVal) identifierInput.value = empVal;
            setTimeout(() => identifierInput.focus(), 150);
        }
    }

    function closeForgotPasswordModal() {
        const modal = document.getElementById('forgotPasswordModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }

    function goBackToStep1() {
        document.getElementById('forgotStep1')?.classList.remove('hidden');
        document.getElementById('forgotStep2')?.classList.add('hidden');
        document.getElementById('forgotStep3')?.classList.add('hidden');
        document.getElementById('forgotStep4')?.classList.add('hidden');

        document.getElementById('forgotStep1ErrorBanner')?.classList.add('hidden');
        document.getElementById('forgotStep2ErrorBanner')?.classList.add('hidden');
        document.getElementById('forgotStep3ErrorBanner')?.classList.add('hidden');
        document.getElementById('resetErrorBanner')?.classList.add('hidden');

        const stepLabel = document.getElementById('forgotModalStepLabel');
        if (stepLabel) stepLabel.textContent = 'Step 1 of 4: Enter Employee ID';
    }

    // STEP 1: Verify Employee ID & check if active
    async function handleForgotVerifyId(event) {
        event.preventDefault();
        const employeeId = document.getElementById('forgotIdentifier')?.value.trim();
        const submitBtn = document.getElementById('forgotVerifyBtn');
        const btnText = document.getElementById('forgotVerifyBtnText');
        const errorBanner = document.getElementById('forgotStep1ErrorBanner');
        const errorMsg = document.getElementById('forgotStep1ErrorMessage');

        if (!employeeId) {
            toast.error('Please enter your Employee ID.', { title: 'Missing Information' });
            return;
        }

        submitBtn.disabled = true;
        btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking Records...';
        if (errorBanner) errorBanner.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('action', 'forgot_verify_id');
            formData.append('employee_id', employeeId);

            const response = await fetch('login.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Populate confirmation card in Step 2
                document.getElementById('forgotLookupTokenHidden').value = data.lookup_token;
                document.getElementById('confirmFullName').textContent = data.full_name;
                document.getElementById('confirmDepartment').textContent = data.department;
                document.getElementById('confirmMaskedEmail').textContent = data.masked_email;
                document.getElementById('resetMaskedEmail').textContent = data.masked_email;

                // Switch to Step 2
                document.getElementById('forgotStep1')?.classList.add('hidden');
                document.getElementById('forgotStep2')?.classList.remove('hidden');
                document.getElementById('forgotStep3')?.classList.add('hidden');
                document.getElementById('forgotStep4')?.classList.add('hidden');

                const stepLabel = document.getElementById('forgotModalStepLabel');
                if (stepLabel) stepLabel.textContent = 'Step 2 of 4: Confirm Identity';

                toast.info('Employee record verified. Please confirm your identity.', { title: 'Employee Found' });

            } else {
                if (errorBanner && errorMsg) {
                    errorMsg.textContent = data.message || 'Employee ID not found.';
                    errorBanner.classList.remove('hidden');
                }
                toast.error(data.message || 'Unable to locate active employee.', { title: 'Verification Failed' });
            }
        } catch (error) {
            toast.error('Connection error. Please try again.', { title: 'Server Error' });
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Verify Employee ID';
        }
    }

    // STEP 2: Confirm Identity & Send 6-Digit Code to Gmail
    async function handleForgotSendCode() {
        const lookupToken = document.getElementById('forgotLookupTokenHidden')?.value.trim();
        const submitBtn = document.getElementById('confirmSendCodeBtn');
        const btnText = document.getElementById('confirmSendCodeBtnText');
        const errorBanner = document.getElementById('forgotStep2ErrorBanner');
        const errorMsg = document.getElementById('forgotStep2ErrorMessage');

        if (!lookupToken) {
            toast.error('Session expired. Please re-enter your Employee ID.', { title: 'Session Expired' });
            goBackToStep1();
            return;
        }

        submitBtn.disabled = true;
        btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending Code to Email...';
        if (errorBanner) errorBanner.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('action', 'forgot_send_code');
            formData.append('lookup_token', lookupToken);

            const response = await fetch('login.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message || 'Verification code sent to your email!', { title: 'Code Dispatched' });

                // Switch to Step 3 (Verify Code First)
                document.getElementById('forgotStep1')?.classList.add('hidden');
                document.getElementById('forgotStep2')?.classList.add('hidden');
                document.getElementById('forgotStep3')?.classList.remove('hidden');
                document.getElementById('forgotStep4')?.classList.add('hidden');
                document.getElementById('resetTokenHidden').value = data.reset_token;

                const stepLabel = document.getElementById('forgotModalStepLabel');
                if (stepLabel) stepLabel.textContent = 'Step 3 of 4: Verify 6-Digit Code';

                // Handle Dev Mode code display
                if (data.dev_otp_code) {
                    const devBox = document.getElementById('forgotDevOtpBox');
                    const devVal = document.getElementById('forgotDevOtpCodeValue');
                    if (devBox && devVal) {
                        devVal.textContent = data.dev_otp_code;
                        devBox.classList.remove('hidden');
                        devBox.classList.add('flex');
                    }
                }

                setTimeout(() => document.getElementById('forgotOtpInput')?.focus(), 200);

            } else {
                if (errorBanner && errorMsg) {
                    errorMsg.textContent = data.message || 'Failed to dispatch verification code.';
                    errorBanner.classList.remove('hidden');
                }
                toast.error(data.message || 'Failed to dispatch code.', { title: 'Error' });
            }
        } catch (error) {
            toast.error('Connection error. Please try again.', { title: 'Server Error' });
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Yes, Send Verification Code';
        }
    }

    // STEP 3: Verify 6-Digit Code First
    async function handleForgotVerifyCode(event) {
        event.preventDefault();
        const resetToken = document.getElementById('resetTokenHidden')?.value.trim();
        const otpCode = document.getElementById('forgotOtpInput')?.value.trim();
        const submitBtn = document.getElementById('verifyCodeBtn');
        const btnText = document.getElementById('verifyCodeBtnText');
        const errorBanner = document.getElementById('forgotStep3ErrorBanner');
        const errorMsg = document.getElementById('forgotStep3ErrorMessage');

        if (!otpCode || otpCode.length !== 6) {
            toast.error('Please enter the full 6-digit security code.', { title: 'Incomplete Code' });
            return;
        }

        submitBtn.disabled = true;
        btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Code...';
        if (errorBanner) errorBanner.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('action', 'forgot_verify_code');
            formData.append('reset_token', resetToken);
            formData.append('otp_code', otpCode);

            const response = await fetch('login.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                toast.success('Security code verified! You can now set your new password.', { title: 'Code Verified' });

                // Switch to Step 4 (Set New Password)
                document.getElementById('forgotStep1')?.classList.add('hidden');
                document.getElementById('forgotStep2')?.classList.add('hidden');
                document.getElementById('forgotStep3')?.classList.add('hidden');
                document.getElementById('forgotStep4')?.classList.remove('hidden');
                document.getElementById('verifiedTokenHidden').value = data.verified_token;

                const stepLabel = document.getElementById('forgotModalStepLabel');
                if (stepLabel) stepLabel.textContent = 'Step 4 of 4: Set New Password';

                setTimeout(() => document.getElementById('forgotNewPass')?.focus(), 200);

            } else {
                if (errorBanner && errorMsg) {
                    errorMsg.textContent = data.message || 'Incorrect security code. Please try again.';
                    errorBanner.classList.remove('hidden');
                }
                toast.error(data.message || 'Invalid security code.', { title: 'Verification Failed' });
                document.getElementById('forgotOtpInput').value = '';
                document.getElementById('forgotOtpInput').focus();
            }
        } catch (error) {
            toast.error('Connection error. Please try again.', { title: 'Server Error' });
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Verify Security Code';
        }
    }

    // STEP 4: Set New Password & Confirm
    async function handleResetPassword(event) {
        event.preventDefault();
        const verifiedToken = document.getElementById('verifiedTokenHidden')?.value.trim();
        const newPassword = document.getElementById('forgotNewPass')?.value;
        const confirmPassword = document.getElementById('forgotConfirmPass')?.value;
        const submitBtn = document.getElementById('resetSubmitBtn');
        const btnText = document.getElementById('resetBtnText');
        const errorBanner = document.getElementById('resetErrorBanner');
        const errorMsg = document.getElementById('resetErrorMessage');

        if (!newPassword || !confirmPassword) {
            toast.error('Please fill in both password fields.', { title: 'Missing Information' });
            return;
        }

        if (newPassword.length < 6) {
            if (errorBanner && errorMsg) {
                errorMsg.textContent = 'New password must be at least 6 characters long.';
                errorBanner.classList.remove('hidden');
            }
            toast.error('Password too short. Must be at least 6 characters.', { title: 'Validation Error' });
            return;
        }

        if (newPassword !== confirmPassword) {
            if (errorBanner && errorMsg) {
                errorMsg.textContent = 'Passwords do not match. Please re-type.';
                errorBanner.classList.remove('hidden');
            }
            toast.error('Passwords do not match.', { title: 'Validation Error' });
            return;
        }

        submitBtn.disabled = true;
        btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Updating Password...';
        if (errorBanner) errorBanner.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('verified_token', verifiedToken);
            formData.append('new_password', newPassword);
            formData.append('confirm_password', confirmPassword);

            const response = await fetch('login.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                toast.success('Password updated successfully! You can now log in.', { title: 'Password Reset' });
                closeForgotPasswordModal();

                // Fill employee ID on login form and focus password
                if (data.employee_id) {
                    const empInput = document.getElementById('employeeId');
                    if (empInput) empInput.value = data.employee_id;
                }
                const passInput = document.getElementById('password');
                if (passInput) {
                    passInput.value = '';
                    passInput.focus();
                }

            } else {
                if (errorBanner && errorMsg) {
                    errorMsg.textContent = data.message || 'Failed to reset password.';
                    errorBanner.classList.remove('hidden');
                }
                toast.error(data.message || 'Reset failed.', { title: 'Error' });
            }
        } catch (error) {
            toast.error('Connection error. Please try again.', { title: 'Server Error' });
        } finally {
            submitBtn.disabled = false;
            btnText.textContent = 'Save New Password & Sign In';
        }
    }

    function autofillForgotDevOtp() {
        const code = document.getElementById('forgotDevOtpCodeValue')?.textContent?.trim();
        const input = document.getElementById('forgotOtpInput');
        if (code && input && code !== '------') {
            input.value = code;
            input.focus();
            toast.info('Reset code auto-filled!', { title: 'Dev Mode' });
        }
    }

    let timerInterval = null;

    function startOtpTimer(durationSeconds = 180) {
        clearInterval(timerInterval);
        let timer = durationSeconds;
        const timerEl = document.getElementById('otpTimer');

        timerInterval = setInterval(() => {
            const minutes = Math.floor(timer / 60);
            const seconds = timer % 60;
            if (timerEl) {
                timerEl.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            }
            if (--timer < 0) {
                clearInterval(timerInterval);
                if (timerEl) timerEl.textContent = 'Expired';
            }
        }, 1000);
    }

    // ============================================================
    // STAFF PRIVACY POLICY & TERMS MODAL LOGIC (SCROLL TO AGREE)
    // ============================================================
    let hasReachedPolicyBottom = false;

    function handleTermsCheckboxClick(event) {
        const checkbox = document.getElementById('agreeTermsCheckbox');
        if (!checkbox) return;

        // If not agreed yet, prevent direct checking and show the policy modal
        if (!hasReachedPolicyBottom) {
            checkbox.checked = false;
            openPrivacyPolicyModal();
        } else {
            // Already read and agreed; allow normal toggle
            const policyError = document.getElementById('policyCheckError');
            if (checkbox.checked && policyError) {
                policyError.classList.add('hidden');
            }
        }
    }

    function openPrivacyPolicyModal() {
        const modal = document.getElementById('privacyPolicyModal');
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        const container = document.getElementById('policyScrollContainer');
        const agreeBtn = document.getElementById('agreePolicyModalBtn');
        const btnText = document.getElementById('agreePolicyModalBtnText');

        // Check if content already fits or previously scrolled to end
        if (container) {
            const isScrollable = container.scrollHeight > container.clientHeight + 10;
            if (!isScrollable || hasReachedPolicyBottom) {
                unlockPolicyAgreeButton();
            } else {
                if (agreeBtn) {
                    agreeBtn.disabled = true;
                    agreeBtn.classList.add('opacity-40', 'cursor-not-allowed');
                }
                if (btnText) btnText.textContent = 'Scroll to End to Agree';
            }
        }
    }

    function closePrivacyPolicyModal() {
        const modal = document.getElementById('privacyPolicyModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function unlockPolicyAgreeButton() {
        hasReachedPolicyBottom = true;
        const agreeBtn = document.getElementById('agreePolicyModalBtn');
        const btnText = document.getElementById('agreePolicyModalBtnText');
        const banner = document.getElementById('scrollNoticeBanner');

        if (agreeBtn) {
            agreeBtn.disabled = false;
            agreeBtn.classList.remove('opacity-40', 'cursor-not-allowed');
            agreeBtn.classList.add('hover:bg-emerald-600', 'bg-emerald-700');
        }
        if (btnText) {
            btnText.textContent = 'I Have Read & Agree to Terms';
        }
        if (banner) {
            banner.className = 'bg-emerald-50 px-5 py-2.5 border-b border-emerald-200 text-xs text-emerald-900 flex items-center justify-between';
            banner.innerHTML = '<div class="flex items-center gap-2"><i class="fa-solid fa-circle-check text-emerald-600"></i><span class="font-semibold">Review complete. You may now click Agree.</span></div>';
        }
    }

    function handlePolicyScroll() {
        if (hasReachedPolicyBottom) return;

        const container = document.getElementById('policyScrollContainer');
        if (!container) return;

        const scrollTop = container.scrollTop;
        // Threshold: within 25px of the end of the modal content
        if (scrollTop + container.clientHeight >= container.scrollHeight - 25) {
            unlockPolicyAgreeButton();
        }
    }

    function acceptPrivacyPolicyFromModal() {
        if (!hasReachedPolicyBottom) return;

        const checkbox = document.getElementById('agreeTermsCheckbox');
        if (checkbox) {
            checkbox.checked = true;
            const errorMsg = document.getElementById('policyCheckError');
            if (errorMsg) errorMsg.classList.add('hidden');
        }

        closePrivacyPolicyModal();
    }



    async function handleLogin(event) {
        event.preventDefault();
        const employeeId = document.getElementById('employeeId').value.trim();
        const password = document.getElementById('password').value;
        const submitBtn = document.getElementById('loginButton');
        const btnText = document.getElementById('btnText');
        const agreeCheckbox = document.getElementById('agreeTermsCheckbox');
        const policyError = document.getElementById('policyCheckError');

        if (!employeeId || !password) {
            toast.error('Please enter both Employee ID and Password.', { title: 'Missing Information' });
            return;
        }

        // Validate DPA Terms & Policy Checkbox
        if (!agreeCheckbox || !agreeCheckbox.checked) {
            if (policyError) policyError.classList.remove('hidden');
            toast.error('Please read and agree to the Terms & Staff Privacy Policy before signing in.', { title: 'DPA Consent Required' });
            // Shake checkbox container
            agreeCheckbox?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            agreeCheckbox?.focus();
            return;
        } else {
            if (policyError) policyError.classList.add('hidden');
        }

        submitBtn.disabled = true;
        btnText.innerHTML = '<span class="btn-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span> Authenticating...';

        // Clear previous error state
        const errorBanner = document.getElementById('loginErrorBanner');
        if (errorBanner) errorBanner.classList.add('hidden');
        document.getElementById('employeeId')?.classList.remove('!border-red-500', '!bg-red-50');
        document.getElementById('password')?.classList.remove('!border-red-500', '!bg-red-50');

        try {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('employee_id', employeeId);
            formData.append('password', password);
            const rememberMe = document.getElementById('rememberMe')?.checked || false;
            formData.append('remember_me', rememberMe);
            formData.append('agree_terms', agreeCheckbox ? (agreeCheckbox.checked ? '1' : '0') : '0');


            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (data.requires_otp) {
                    // Open 2-Step OTP Modal
                    document.getElementById('otpMaskedEmail').textContent = data.masked_email;
                    document.getElementById('otpSessionToken').value = data.session_token;

                    // Developer testing option: display code badge if enabled in .env
                    const devOtpBox = document.getElementById('devOtpBox');
                    const devOtpVal = document.getElementById('devOtpCodeValue');
                    if (data.dev_otp_code) {
                        devOtpVal.textContent = data.dev_otp_code;
                        devOtpBox.classList.remove('hidden');
                        devOtpBox.classList.add('flex');
                    } else {
                        devOtpBox.classList.add('hidden');
                        devOtpBox.classList.remove('flex');
                    }

                    // Auto-check Remember this device in OTP container when OTP is sent
                    const otpRemember = document.getElementById('otpRememberDevice');
                    if (otpRemember) {
                        otpRemember.checked = true;
                    }

                    document.getElementById('otpModal').classList.remove('hidden');
                    document.getElementById('otpModal').classList.add('flex');
                    startOtpTimer(180); // 3 minutes

                    toast.success(data.message, { title: 'OTP Code Sent' });
                    setTimeout(() => document.getElementById('otpCodeInput').focus(), 200);
                } else {
                    // Direct login (Dev bypass or valid 12h/7d session)
                    toast.success(data.message || 'Login successful! Redirecting...', { title: 'Access Granted' });
                    setTimeout(() => {
                        window.location.href = data.redirect || 'pages/dashboard.php';
                    }, 100);
                }
            } else {
                // Show inline error banner and shake input
                const errorMsg = document.getElementById('loginErrorMessage');
                if (errorBanner && errorMsg) {
                    errorMsg.textContent = data.message || 'Invalid employee ID or password.';
                    errorBanner.classList.remove('hidden');
                }
                
                // Highlight input fields with red borders
                const empInput = document.getElementById('employeeId');
                const passInput = document.getElementById('password');
                if (empInput) empInput.classList.add('!border-red-500', '!bg-red-50');
                if (passInput) passInput.classList.add('!border-red-500', '!bg-red-50');

                // Shake form container
                const formCard = document.getElementById('loginForm');
                if (formCard) {
                    formCard.classList.remove('animate-shake');
                    void formCard.offsetWidth; // Trigger reflow
                    formCard.classList.add('animate-shake');
                }

                toast.error(data.message || 'Invalid employee ID or password.', { title: 'Authentication Failed' });
                resetButton(submitBtn, btnText);
            }
        } catch (error) {
            console.error('Login Error:', error);
            const errorBanner = document.getElementById('loginErrorBanner');
            const errorMsg = document.getElementById('loginErrorMessage');
            if (errorBanner && errorMsg) {
                errorMsg.textContent = 'Server communication error. Please try again.';
                errorBanner.classList.remove('hidden');
            }
            toast.error('Unable to connect to authentication service.', { title: 'Network Error' });
            resetButton(submitBtn, btnText);
        }
    }

    async function handleVerifyOtp(event) {
        event.preventDefault();
        const sessionToken = document.getElementById('otpSessionToken').value;
        const otpCode = document.getElementById('otpCodeInput').value.trim();
        const rememberMe = document.getElementById('otpRememberDevice') ? document.getElementById('otpRememberDevice').checked : true;
        const otpBtn = document.getElementById('otpSubmitBtn');
        const otpBtnText = document.getElementById('otpBtnText');

        if (!otpCode || otpCode.length !== 6) {
            toast.error('Please enter the full 6-digit security code.', { title: 'Incomplete Code' });
            return;
        }

        otpBtn.disabled = true;
        otpBtnText.innerHTML = '<span class="btn-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span> Verifying Code...';

        try {
            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('session_token', sessionToken);
            formData.append('otp_code', otpCode);
            formData.append('remember_me', rememberMe);

            const response = await fetch('login.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                clearInterval(timerInterval);
                toast.success('Identity verified! Redirecting to Dashboard...', { title: 'Verification Success' });
                setTimeout(() => {
                    window.location.href = data.redirect || 'pages/dashboard.php';
                }, 100);
            } else {
                const otpBanner = document.getElementById('otpErrorBanner');
                const otpMsg = document.getElementById('otpErrorMessage');
                if (otpBanner && otpMsg) {
                    otpMsg.textContent = data.message || 'Incorrect security code. Please try again.';
                    otpBanner.classList.remove('hidden');
                }
                toast.error(data.message || 'Invalid security code. Please try again.', { title: data.locked ? 'Code Locked' : 'Verification Failed' });
                document.getElementById('otpCodeInput').value = '';
                
                if (data.locked) {
                    document.getElementById('otpCodeInput').disabled = true;
                    document.getElementById('otpSubmitBtn').disabled = true;
                    document.getElementById('otpSubmitBtn').classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    document.getElementById('otpCodeInput').focus();
                }
            }
        } catch (error) {
            console.error('OTP Error:', error);
            toast.error('Verification error occurred. Please try again.', { title: 'System Error' });
        } finally {
            otpBtn.disabled = false;
            otpBtnText.textContent = 'Verify & Access Portal';
        }
    }

    function resetButton(btn, textEl) {
        btn.disabled = false;
        textEl.textContent = 'Sign in';
    }

    document.addEventListener('DOMContentLoaded', () => {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) loginForm.addEventListener('submit', handleLogin);

        const otpForm = document.getElementById('otpForm');
        if (otpForm) otpForm.addEventListener('submit', handleVerifyOtp);

        const step1Form = document.getElementById('forgotStep1Form');
        if (step1Form) step1Form.addEventListener('submit', handleForgotVerifyId);

        const verifyCodeForm = document.getElementById('verifyCodeForm');
        if (verifyCodeForm) verifyCodeForm.addEventListener('submit', handleForgotVerifyCode);

        const resetForm = document.getElementById('resetForm');
        if (resetForm) resetForm.addEventListener('submit', handleResetPassword);

        // Clear error banners and red borders when user types
        const clearLoginErrors = () => {
            const errorBanner = document.getElementById('loginErrorBanner');
            if (errorBanner) errorBanner.classList.add('hidden');
            document.getElementById('employeeId')?.classList.remove('!border-red-500', '!bg-red-50');
            document.getElementById('password')?.classList.remove('!border-red-500', '!bg-red-50');
        };

        document.getElementById('employeeId')?.addEventListener('input', clearLoginErrors);
        document.getElementById('password')?.addEventListener('input', clearLoginErrors);
    });
    </script>
    <?php include 'includes/toast.php'; ?>
</body>
</html>
