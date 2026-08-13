<?php
// login.php - 2-Step OTP Employee Portal Authentication
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/Core/Env.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/paths.php';
require_once __DIR__ . '/app/Models/ActivityLog.php';
require_once __DIR__ . '/app/services/SessionAuthService.php';

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

            try {
                $db = Database::getInstance();
                $result = $db->select('employees', ['employee_id' => $employeeId]);
                $logModel = new ActivityLog();

                if (empty($result) || !is_array($result)) {
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

                $user = $result[0];

                if (!password_verify($password, $user['password'])) {
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

                $authService = new SessionAuthService();
                $cookieToken = $_COOKIE['civentral_session'] ?? '';

                // Check if device/employee has active 12h/7d verified session
                if ($authService->hasActiveVerifiedSession((int)$user['id'], $cookieToken)) {
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

                    $logModel->log("User logged in (12h Device Token active)", [
                        'user_id'   => $user['id'],
                        'user_name' => $user['full_name'],
                        'role'      => $user['role_description'] ?? $user['role'] ?? 'Employee',
                        'module'    => 'Authentication',
                        'details'   => "Re-authenticated via active 12h token: {$user['employee_id']}",
                        'status'    => 'Success',
                    ]);

                    echo json_encode([
                        'success'      => true,
                        'requires_otp' => false,
                        'redirect'     => 'pages/dashboard.php',
                        'user'         => [
                            'name'        => $user['full_name'],
                            'employee_id' => $user['employee_id']
                        ],
                        'message'      => 'Welcome back! Device session active.'
                    ]);
                    exit;
                }

                // Generate 6-digit OTP code & send email via SessionAuthService
                $otpResult   = $authService->generateAndSendOtp($user, $rememberMe);

                // Mask recipient email for security
                $rawEmail = $user['email'] ?? 'staff@health.gov.ph';
                $parts    = explode('@', $rawEmail);
                $maskedEmail = (strlen($parts[0]) > 2 ? substr($parts[0], 0, 2) . '***' : $parts[0]) . '@' . ($parts[1] ?? 'lgu.gov.ph');

                echo json_encode([
                    'success'       => true,
                    'requires_otp'  => true,
                    'session_token' => $otpResult['session_token'],
                    'masked_email'  => $maskedEmail,
                    'user'          => [
                        'name'        => $user['full_name'],
                        'employee_id' => $user['employee_id']
                    ],
                    'message'       => 'Credentials verified. 6-digit security code sent to ' . $maskedEmail
                ]);
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

            if (empty($sessionToken) || empty($otpCode)) {
                echo json_encode(['success' => false, 'message' => 'Security code and session token are required.']);
                exit;
            }

            try {
                $authService = new SessionAuthService();
                $verifyResult = $authService->verifyOtp($sessionToken, $otpCode);

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
                }

                echo json_encode($verifyResult);
                exit;

            } catch (\Exception $e) {
                error_log('OTP Verify error: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Verification failed.']);
                exit;
            }
        }
    }
}

// Redirect if already logged in or if active valid 12h/7d token cookie exists
if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: pages/dashboard.php');
    exit;
}

if (!empty($_COOKIE['civentral_session']) && empty($_GET['logout']) && empty($_GET['switch_account']) && empty($_GET['logged_out'])) {
    $authService = new SessionAuthService();
    if ($authService->validateActiveToken($_COOKIE['civentral_session'])) {
        header('Location: pages/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Civentral · Employee Portal</title>
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
    </style>
    <?php include 'includes/toast.php'; ?>
</head>
<body class="bg-white min-h-screen font-sans antialiased selection:bg-brand-medium selection:text-white">

    <div class="min-h-screen flex flex-col md:flex-row relative">
        <div class="hidden md:block md:w-1/2 lg:w-3/5 bg-[url(assets/images/building-bg.jpg)] bg-cover bg-left bg-no-repeat mix-blend-multiply relative">
            <div class="absolute inset-0 bg-gradient-to-r from-transparent to-white"></div>
        </div>

        <div class="flex-1 flex flex-col justify-between p-8 sm:p-12 md:p-16 lg:p-24 bg-white z-10">
            <div></div>

            <div class="w-full max-w-md mx-auto space-y-6 my-auto relative">
                <div class="w-full space-y-6">
                    <div class="flex flex-col items-center justify-center text-center pb-4 w-full">
                        <img src="assets/images/logo.png" alt="Civentral Graphic" class="h-24 w-auto object-contain mb-3">
                        <span class="text-4xl font-black text-brand-medium tracking-[0.25em] uppercase font-sans">
                            Civentral
                        </span>
                    </div>
                    <div class="flex flex-col items-center md:items-start text-center md:text-left space-y-2">
                        <span class="text-xs font-bold tracking-widest text-gray-400 uppercase">Employee Access</span>
                        <h1 class="text-3xl font-extrabold text-gray-600 tracking-tight">Sign in to your office</h1>
                        <p class="text-xs text-gray-500">Enter your LGU-issued credentials to continue.</p>
                    </div>
                </div>

                <form id="loginForm" class="space-y-4 pt-2" autocomplete="on">
                    <div class="space-y-1.5">
                        <label for="employeeId" class="text-xs font-semibold text-gray-500">LGU Employee ID</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-4 text-gray-400">
                                <i class="fa-solid fa-user-tie text-sm"></i>
                            </span>
                            <input
                                type="text"
                                id="employeeId"
                                name="employee_id"
                                placeholder="ID 1111-ADMIN-2011"
                                required
                                autocomplete="username"
                                class="input-field w-full pl-11 pr-4 py-3 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium transition"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="password" class="text-xs font-semibold text-gray-500">Password</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-4 text-gray-400">
                                <i class="fa-solid fa-key text-sm"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                                class="input-field w-full pl-11 pr-11 py-3 bg-white border border-gray-300 rounded-lg text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-brand-medium focus:ring-1 focus:ring-brand-medium transition"
                            />
                            <button
                                type="button"
                                onclick="togglePasswordVisibility()"
                                class="absolute right-4 text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer"
                                tabindex="-1"
                                aria-label="Toggle password visibility"
                            >
                                <i id="passwordIcon" class="fa-solid fa-eye-slash text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center space-x-2 cursor-pointer select-none">
                            <input type="checkbox" id="rememberMe" name="remember_me" class="w-4 h-4 text-brand-medium border-gray-300 rounded focus:ring-brand-medium accent-brand-medium" />
                            <span class="text-xs text-gray-500">Keep me signed in</span>
                        </label>
                        <a href="#" class="text-xs font-semibold text-brand-medium hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit" id="loginButton" class="w-full py-3 px-4 bg-brand-medium hover:bg-opacity-90 text-white font-medium rounded-lg text-sm transition shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-medium focus:ring-offset-2 cursor-pointer">
                        <span id="btnText">Sign in</span>
                    </button>
                </form>

                <div class="relative flex py-2 items-center">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <span class="flex-shrink mx-4 text-xs font-semibold text-gray-400 tracking-wider">OR</span>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>

                <a href="index.php" class="inline-block text-center w-full py-3 px-4 bg-white hover:bg-brand-medium hover:text-white text-brand-medium font-medium border border-brand-medium rounded-lg text-sm transition focus:outline-none">
                    Back to Home
                </a>
            </div>

            <div class="text-center pt-8">
                <p class="text-[10px] md:text-xs font-bold text-gray-400 tracking-wider uppercase max-w-sm mx-auto leading-relaxed">
                    DEPT ACCESS ONLY · UNAUTHORIZED USE IS LOGGED & PROSECUTABLE UNDER RA 8792
                </p>
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

                    <!-- Inline Error Banner -->
                    <div id="otpErrorBanner" class="hidden p-3 rounded-xl bg-red-50 border border-red-200 text-xs font-semibold text-red-600 text-center flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-circle-exclamation text-red-500 text-sm"></i>
                        <span id="otpErrorMessage">Incorrect 6-digit code. Please try again.</span>
                    </div>

                    <div class="flex items-center justify-between text-xs text-gray-500 pt-1">
                        <span>Code expires in <strong id="otpTimer" class="text-gray-800">5:00</strong></span>
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

    <script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');
        if (!passwordInput || !passwordIcon) return;
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        passwordIcon.className = isHidden ? 'fa-solid fa-eye text-sm' : 'fa-solid fa-eye-slash text-sm';
    }

    let timerInterval = null;

    function startOtpTimer(durationSeconds = 300) {
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

    async function handleLogin(event) {
        event.preventDefault();
        const employeeId = document.getElementById('employeeId').value.trim();
        const password = document.getElementById('password').value;
        const submitBtn = document.getElementById('loginButton');
        const btnText = document.getElementById('btnText');

        if (!employeeId || !password) {
            toast.error('Please enter both Employee ID and Password.', { title: 'Missing Information' });
            return;
        }

        submitBtn.disabled = true;
        btnText.innerHTML = '<span class="btn-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span> Authenticating...';

        try {
            const rememberMe = document.getElementById('rememberMe')?.checked || false;
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('employee_id', employeeId);
            formData.append('password', password);
            formData.append('remember_me', rememberMe);

            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (data.requires_otp) {
                    document.getElementById('otpSessionToken').value = data.session_token;
                    document.getElementById('otpMaskedEmail').textContent = data.masked_email;
                    document.getElementById('otpModal').classList.remove('hidden');
                    document.getElementById('otpModal').classList.add('flex');
                    document.getElementById('otpCodeInput').focus();

                    startOtpTimer(300);

                    toast.info('Security code sent to ' + data.masked_email, { title: 'Check Your Email' });
                    resetButton(submitBtn, btnText);
                } else {
                    // Active 12-hour session active! Skip OTP code!
                    const userName = data.user?.name || 'Employee';
                    toast.success('Welcome back, ' + userName + '!', { title: 'Device Verified' });
                    submitBtn.classList.remove('bg-brand-medium');
                    submitBtn.classList.add('bg-green-500');
                    btnText.textContent = '✓ Verified!';
                    setTimeout(() => {
                        window.location.href = data.redirect || 'pages/dashboard.php';
                    }, 1000);
                }
            } else {
                toast.error(data.message || 'Invalid Employee ID or Password.', { title: 'Login Failed' });
                resetButton(submitBtn, btnText);
            }
        } catch (err) {
            console.error('Login error:', err);
            toast.error('Network error. Please try again.', { title: 'Connection Issue' });
            resetButton(submitBtn, btnText);
        }
    }

    async function handleVerifyOtp(event) {
        event.preventDefault();
        const sessionToken = document.getElementById('otpSessionToken').value;
        const otpCode = document.getElementById('otpCodeInput').value.trim();
        const otpBtn = document.getElementById('otpSubmitBtn');
        const otpBtnText = document.getElementById('otpBtnText');
        const otpInput = document.getElementById('otpCodeInput');
        const errorBanner = document.getElementById('otpErrorBanner');
        const errorMsg = document.getElementById('otpErrorMessage');
        const modalCard = document.querySelector('#otpModal .bg-white');

        // Reset previous error state
        if (errorBanner) errorBanner.classList.add('hidden');
        if (otpInput) {
            otpInput.classList.remove('border-red-500', 'bg-red-50', 'text-red-700');
            otpInput.classList.add('border-brand-border', 'bg-gray-50', 'text-brand-dark');
        }

        if (otpCode.length !== 6) {
            toast.warning('Please enter the full 6-digit code.', { title: 'Invalid Code' });
            if (otpInput) otpInput.focus();
            return;
        }

        otpBtn.disabled = true;
        otpBtnText.innerHTML = '<span class="btn-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span> Authenticating Code...';

        try {
            const formData = new FormData();
            formData.append('action', 'verify_otp');
            formData.append('session_token', sessionToken);
            formData.append('otp_code', otpCode);

            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                const userName = (data.user && data.user.name) ? data.user.name : (data.employee && data.employee.full_name ? data.employee.full_name : 'User');
                toast.success('Welcome back, ' + userName + '!', { title: 'Identity Verified' });
                otpBtnText.textContent = '✓  Verified!';
                setTimeout(() => {
                    window.location.href = data.redirect || 'pages/dashboard.php';
                }, 1000);
            } else {
                const errMsg = data.message || 'Incorrect 6-digit verification code.';
                toast.error(errMsg, { title: 'Verification Failed' });

                // Show inline error message banner
                if (errorBanner && errorMsg) {
                    errorMsg.textContent = errMsg;
                    errorBanner.classList.remove('hidden');
                }

                // Highlight input field in red
                if (otpInput) {
                    otpInput.classList.remove('border-brand-border', 'bg-gray-50', 'text-brand-dark');
                    otpInput.classList.add('border-red-500', 'bg-red-50', 'text-red-700');
                    otpInput.focus();
                    otpInput.select();
                }

                // Trigger shake animation on modal
                if (modalCard) {
                    modalCard.classList.remove('shake-error');
                    void modalCard.offsetWidth;
                    modalCard.classList.add('shake-error');
                }

                otpBtn.disabled = false;
                otpBtnText.textContent = 'Verify & Access Portal';
            }
        } catch (err) {
            console.error('OTP error:', err);
            const sysErrMsg = 'Database or connection error. Please try again.';
            toast.error(sysErrMsg, { title: 'System Error' });

            if (errorBanner && errorMsg) {
                errorMsg.textContent = sysErrMsg;
                errorBanner.classList.remove('hidden');
            }

            otpBtn.disabled = false;
            otpBtnText.textContent = 'Verify & Access Portal';
        }
    }

    function resendOtp() {
        toast.info('Requesting a new security code...', { title: 'Resending Code' });
        const form = document.getElementById('loginForm');
        if (form) form.requestSubmit();
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
    });
    </script>
</body>
</html>
