<?php
// ============================================================
// Redirect to Unified Nutrition Assessment & Growth Charts Module
// ============================================================
require_once __DIR__ . '/../../config/paths.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$childParam = isset($_GET['child_id']) ? '&child_id=' . urlencode($_GET['child_id']) : '';
$targetUrl = site_url('modules/immunization/nutrition_assessment.php?open_growth=1' . $childParam);

// If user is not logged in, redirect to login
if (empty($_SESSION['logged_in'])) {
    $loginUrl = site_url('login.php');
    if (!headers_sent()) {
        header('Location: ' . $loginUrl, true, 302);
    }
    echo '<script>window.location.replace(' . json_encode($loginUrl) . ');</script>';
    exit;
}

// Redirect to unified Nutrition & Growth page (Growth Tab)
if (!headers_sent()) {
    header('Location: ' . $targetUrl, true, 302);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($targetUrl, ENT_QUOTES) ?>">
    <title>Redirecting to Nutrition & Growth Monitoring...</title>
    <script>
        window.location.replace(<?= json_encode($targetUrl) ?>);
    </script>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f8fafc; color: #0B4F4A; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0;">
    <div style="text-align: center; background: white; padding: 2rem; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border: 1px solid #B8E0DC;">
        <h2 style="margin-top: 0; color: #0B4F4A;">Redirecting...</h2>
        <p style="color: #64748b; font-size: 0.9rem;">Taking you to the unified Nutrition & Growth Monitoring workspace.</p>
        <p><a href="<?= htmlspecialchars($targetUrl, ENT_QUOTES) ?>" style="color: #14807A; font-weight: bold; text-decoration: none;">Click here if you are not redirected automatically &rarr;</a></p>
    </div>
</body>
</html>
<?php
exit;