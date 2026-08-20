<?php
// modules/surveillence/alerts.php - Forwarding to consolidated Outbreak Surveillance Dashboard
require_once __DIR__ . '/../../config/paths.php';

$target = site_url('modules/surveillence/outbreak_command.php');
if (!headers_sent()) {
    header("Location: {$target}", true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($target, ENT_QUOTES) ?>"></head>
<body><script>window.location.href = "<?= addslashes($target) ?>";</script></body>
</html>