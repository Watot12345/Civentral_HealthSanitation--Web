<?php
// Load environment variables if available
require_once __DIR__ . '/../Core/Env.php';

// Try to get BASE_URL from env, otherwise detect dynamically
$baseUrl = Env::get('BASE_URL');

if ($baseUrl === null) {
    // Dynamic detection
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

    if (!empty($docRoot) && str_starts_with($projectRoot, $docRoot)) {
        $baseUrl = substr($projectRoot, strlen($docRoot));
    } else {
        // Fallback: if server document root is not matching, use default '/capstone'
        $baseUrl = '/capstone';
    }

    $baseUrl = '/' . trim($baseUrl, '/');
    if ($baseUrl === '/') {
        $baseUrl = '';
    }
}

define('BASE_URL', $baseUrl);

function site_url($path = '') {
    $clean = str_replace('../', '', $path);
    return rtrim(BASE_URL, '/') . '/' . ltrim($clean, '/');
}
?>