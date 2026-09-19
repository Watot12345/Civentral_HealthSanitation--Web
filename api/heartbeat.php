<?php
require_once __DIR__ . '/../config/paths.php';

// The session is validated by paths.php on inclusion.
// If the session was expired on the backend, paths.php returns a 401 and exits.

// Update last activity since this heartbeat explicitly represents user activity
$_SESSION['last_activity'] = time();

header('Content-Type: application/json');
echo json_encode(['success' => true]);
