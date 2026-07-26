<?php
// login.php - Combined PHP + HTML
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/Core/Env.php';
require_once __DIR__ . '/config/database.php';


// Handle AJAX login request - MUST come before ANY HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    $employeeId = $_POST['employee_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Only process JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        
        try {
            $db = Database::getInstance();
             $result = $db->select('employees', ['employee_id' => $employeeId]);
            if (empty($result) || !is_array($result)) {
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID or password.']);
                exit;
            }
            
            $user = $result[0];
            
            if (!password_verify($password, $user['password'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid employee ID or password.']);
                exit;
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['department'] = $user['department'] ?? '';
            $_SESSION['role'] = $user['role'] ?? 'employee';
            $_SESSION['role_description'] = $user['role_description'] ?? '';
            $_SESSION['logged_in'] = true;
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'name' => $user['full_name'],
                    'employee_id' => $user['employee_id'],
                    'department' => $user['department'] ?? '',
                    'role' => $user['role'] ?? 'employee',
                    'role_description' => $user['role_description'] ?? ''
                ]
            ]);
            exit;
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server error. Please contact IT support.']);
            exit;
        }
    }
}

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: pages/dashboard.php');
    exit;
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
                            <input type="checkbox" class="w-4 h-4 text-brand-medium border-gray-300 rounded focus:ring-brand-medium accent-brand-medium" />
                            <span class="text-xs text-gray-500">Keep me signed in</span>
                        </label>
                        <a href="#" class="text-xs font-semibold text-brand-medium hover:underline">Forgot password?</a>
                    </div>

                    <button type="submit" id="loginButton" class="w-full py-3 px-4 bg-brand-medium hover:bg-opacity-90 text-white font-medium rounded-lg text-sm transition shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-medium focus:ring-offset-2">
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

    <script>
    /* ============================================================
       PASSWORD TOGGLE
       ============================================================ */

    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');

        if (!passwordInput || !passwordIcon) return;

        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        passwordIcon.className = isHidden
            ? 'fa-solid fa-eye text-sm'
            : 'fa-solid fa-eye-slash text-sm';
    }

    /* ============================================================
       LOGIN HANDLER
       ============================================================ */

    async function handleLogin(event) {
        event.preventDefault();

        const employeeId = document.getElementById('employeeId').value.trim();
        const password = document.getElementById('password').value;
        const submitBtn = document.getElementById('loginButton');
        const btnText = document.getElementById('btnText');

        // Prevent multiple simultaneous submissions
        if (submitBtn.dataset.submitting === 'true') return;
        submitBtn.dataset.submitting = 'true';

        // ---- Validation ----
        if (!employeeId) {
            toast.error('Please enter your LGU Employee ID.', { title: 'Missing Field' });
            document.getElementById('employeeId').focus();
            submitBtn.dataset.submitting = 'false';
            return;
        }

        if (!password) {
            toast.error('Please enter your password.', { title: 'Missing Field' });
            document.getElementById('password').focus();
            submitBtn.dataset.submitting = 'false';
            return;
        }

        // ---- Loading state ----
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        btnText.innerHTML = '<span class="btn-loader"><span class="dot"></span><span class="dot"></span><span class="dot"></span></span>';

        // ---- AbortController for timeout ----
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 15000);

        try {
            const formData = new FormData();
            formData.append('employee_id', employeeId);
            formData.append('password', password);

            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error('Server returned ' + response.status + ' ' + response.statusText);
            }

            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                throw new Error('Invalid response from server. Please try again.');
            }

            if (data.success) {
                toast.success('Welcome back, ' + (data.user.name || 'User') + '!', {
                    title: 'Login Successful',
                    duration: 3000
                });

                submitBtn.classList.remove('bg-brand-medium');
                submitBtn.classList.add('bg-green-500');
                btnText.textContent = '✓  Success!';

                setTimeout(function() {
                    window.location.href = '/capstone/pages/dashboard.php';
                }, 1500);

            } else {
                toast.error(data.message || 'Login credentials are invalid.', {
                    title: 'Login Failed',
                    duration: 6000
                });

                submitBtn.classList.remove('bg-brand-medium');
                submitBtn.classList.add('bg-red-500');
                btnText.textContent = '✗  Failed';

                setTimeout(function() { resetButton(submitBtn, btnText); }, 1800);
            }

        } catch (error) {
            clearTimeout(timeoutId);

            if (error.name === 'AbortError') {
                toast.error('Request timed out. Please check your connection and try again.', {
                    title: 'Connection Timeout',
                    duration: 7000
                });
            } else {
                console.error('Login error:', error);
                toast.error(error.message || 'A network error occurred. Please try again.', {
                    title: 'Connection Error',
                    duration: 6000
                });
            }

            submitBtn.classList.remove('bg-brand-medium');
            submitBtn.classList.add('bg-red-500');
            btnText.textContent = '✗  Error';

            setTimeout(function() { resetButton(submitBtn, btnText); }, 1800);
        }
    }

    /**
     * Reset login button to its default appearance
     */
    function resetButton(btn, textEl) {
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed', 'bg-red-500', 'bg-green-500');
        btn.classList.add('bg-brand-medium');
        textEl.textContent = 'Sign in';
        btn.dataset.submitting = 'false';
    }

    /* ============================================================
       EVENT BINDING
       ============================================================ */

    document.addEventListener('DOMContentLoaded', function() {
        var loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', handleLogin);
        }

        var pwdField = document.getElementById('password');
        if (pwdField) {
            pwdField.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    var form = document.getElementById('loginForm');
                    if (form) form.requestSubmit();
                }
            });
        }

        var idField = document.getElementById('employeeId');
        if (idField) {
            idField.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('password').focus();
                }
            });
        }
    });
    </script>
</body>
</html>
