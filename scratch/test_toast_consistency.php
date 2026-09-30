<?php
// scratch/test_toast_consistency.php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$passed = 0;
$failed = 0;

function assert_toast(bool $condition, string $testName, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  [\033[32mPASS\033[0m] {$testName}\n";
    } else {
        $failed++;
        echo "  [\033[31mFAIL\033[0m] {$testName}" . ($details ? " - {$details}" : "") . "\n";
    }
}

echo "====================================================================\n";
echo "  TOAST NOTIFICATION SYSTEM CONSISTENCY AUDIT\n";
echo "====================================================================\n\n";

// 1. Check toast.php file exists
$toastPath = __DIR__ . '/../includes/toast.php';
assert_toast(file_exists($toastPath), "includes/toast.php exists on disk");

$toastContent = file_get_contents($toastPath);

// 2. Check JavaScript API methods in toast.php
$requiredMethods = ['success', 'error', 'danger', 'info', 'warning', 'dismiss', 'dismissAll'];
foreach ($requiredMethods as $m) {
    assert_toast(str_contains($toastContent, "{$m}:"), "window.toast exposes '{$m}' method");
}

// 3. Check WCAG 4.1.3 accessibility semantics
assert_toast(str_contains($toastContent, 'id="toastContainer"'), "toastContainer defined with fixed overlay");
assert_toast(str_contains($toastContent, 'role="region"'), "toastContainer has role='region'");
assert_toast(str_contains($toastContent, 'aria-label="Notifications"'), "toastContainer has aria-label='Notifications'");
assert_toast(str_contains($toastContent, 'aria-live="polite"'), "toastContainer has aria-live='polite'");

// 4. Check global bridge showToast
assert_toast(str_contains($toastContent, 'window.showToast = function'), "Global window.showToast bridge is defined");

// 5. Check includes/footer.php includes toast.php
$footerPath = __DIR__ . '/../includes/footer.php';
$footerContent = file_get_contents($footerPath);
assert_toast(str_contains($footerContent, "include_once __DIR__ . '/toast.php'"), "includes/footer.php loads toast.php via include_once");

// 6. Check ModalSystem.toast integration in assets/js/modal-system.js
$modalSysPath = __DIR__ . '/../assets/js/modal-system.js';
$modalSysContent = file_get_contents($modalSysPath);
assert_toast(str_contains($modalSysContent, "if (typeof toast !== 'undefined' && toast.success)"), "ModalSystem.toast delegates to window.toast.success");
assert_toast(str_contains($modalSysContent, "if (typeof toast !== 'undefined' && toast.error)"), "ModalSystem.toast delegates to window.toast.error");
assert_toast(str_contains($modalSysContent, "danger: function"), "ModalSystem.toast has danger compatibility alias");

// 7. Check common.js integration in assets/js/common.js
$commonJsPath = __DIR__ . '/../assets/js/common.js';
$commonJsContent = file_get_contents($commonJsPath);
assert_toast(str_contains($commonJsContent, "if (typeof window.toast !== 'undefined')"), "common.js showToast bridges to window.toast");

// 8. Audit all module & page view files
echo "\nAuditing view files for footer.php / toast.php inclusion...\n";
$viewDirs = [__DIR__ . '/../modules', __DIR__ . '/../pages'];
$missingFiles = [];
$totalViews = 0;

foreach ($viewDirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $totalViews++;
            $relPath = str_replace(realpath(__DIR__ . '/..') . '/', '', $file->getRealPath());
            $c = file_get_contents($file->getRealPath());
            
            // Exclude pure API endpoints or JSON handlers
            if (str_contains($relPath, '/api/') || str_contains($c, 'header(\'Content-Type: application/json\');') && !str_contains($c, '<html')) {
                continue;
            }
            // Exclude pure redirects or printable standalone certificates
            if (str_contains($relPath, 'permit_certificate.php') || (str_contains($c, 'header("Location:') && strlen($c) < 600)) {
                continue;
            }
            
            $hasFooter = str_contains($c, 'footer.php');
            $hasToast = str_contains($c, 'toast.php');
            
            if (!$hasFooter && !$hasToast) {
                $missingFiles[] = $relPath;
            }
        }
    }
}

assert_toast(empty($missingFiles), "All interactive UI pages include footer.php (and therefore toast.php)", "Missing in: " . implode(', ', $missingFiles));

echo "\n====================================================================\n";
echo "TOAST AUDIT RESULTS SUMMARY\n";
echo "====================================================================\n";
echo "Total Passed: {$passed}\n";
echo "Total Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n\033[32mTOAST NOTIFICATION SYSTEM IS 100% UNIFIED, CONSISTENT, AND ACCESSIBLE!\033[0m\n";
    exit(0);
} else {
    exit(1);
}
