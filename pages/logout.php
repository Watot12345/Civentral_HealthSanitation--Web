<?php
// logout.php
session_start();
session_destroy();
require_once __DIR__ . '/../config/paths.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        // Reset data masking to hidden
        localStorage.setItem('data_masking_enabled', 'true');
        // Redirect to login
        window.location.href = site_url('login.php');
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>