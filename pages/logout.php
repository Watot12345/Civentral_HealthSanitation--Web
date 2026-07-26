<?php
// logout.php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        // Reset data masking to hidden
        localStorage.setItem('data_masking_enabled', 'true');
        // Redirect to login
        window.location.href = 'login.php';
    </script>
</head>
<body>
    <p>Logging out...</p>
</body>
</html>