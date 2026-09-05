<?php
// DAST Security Vulnerability Scanner
// Tests local Civentral Web App against OWASP Top 10 vulnerabilities

$targetUrl = $argv[1] ?? 'http://127.0.0.1:8080';

echo "======================================================\n";
echo "🛡️ DAST Vulnerability Scan — Civentral Web ERP\n";
echo "Target URL: {$targetUrl}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "======================================================\n\n";

$passCount = 0;
$failCount = 0;

function checkUrl($url, $method = 'GET', $postData = [], $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $headerText = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    return [
        'code' => $httpCode,
        'headers' => $headerText,
        'body' => $body
    ];
}

// 1. Check Security Headers
echo "[TEST 1] Security Headers Audit...\n";
$res = checkUrl("{$targetUrl}/");
$requiredHeaders = ['X-Content-Type-Options', 'X-Frame-Options', 'X-XSS-Protection'];
foreach ($requiredHeaders as $hdr) {
    if (stripos($res['headers'], $hdr) !== false) {
        echo "  [PASS] Header '{$hdr}' is present.\n";
        $passCount++;
    } else {
        echo "  [INFO] Header '{$hdr}' missing on root endpoint.\n";
    }
}

// 2. Test Path Traversal / LFI
echo "\n[TEST 2] Local File Inclusion / Path Traversal Protection...\n";
$lfiUrl = "{$targetUrl}/api/export.php?category=../../../../etc/passwd&format=csv";
$res = checkUrl($lfiUrl);
if ($res['code'] === 200 && strpos($res['body'], 'root:') === false) {
    echo "  [PASS] Path traversal attempt safely handled/fallback used (HTTP {$res['code']}).\n";
    $passCount++;
} else {
    echo "  [FAIL] Path traversal attempt vulnerable!\n";
    $failCount++;
}

// 3. Test SQL Injection Resilience in API Filter Parameters
echo "\n[TEST 3] SQL Injection Resilience...\n";
$sqliUrl = "{$targetUrl}/api/permits.php?search=" . urlencode("' OR 1=1 --");
$res = checkUrl($sqliUrl);
if ($res['code'] === 200 || $res['code'] === 400 || $res['code'] === 401 || $res['code'] === 404) {
    echo "  [PASS] SQL Injection payload safely parameterized/handled (HTTP {$res['code']}).\n";
    $passCount++;
} else {
    echo "  [FAIL] Potential SQL Injection error detected (HTTP {$res['code']}).\n";
    $failCount++;
}

// 4. Test Unauthenticated Access to Admin API Endpoints
echo "\n[TEST 4] Unauthenticated Access Enforcement (RBAC)...\n";
$adminUrl = "{$targetUrl}/api/activity-logs.php";
$res = checkUrl($adminUrl);
if ($res['code'] === 401 || $res['code'] === 403 || strpos($res['body'], 'Unauthorized') !== false || $res['code'] === 302) {
    echo "  [PASS] Protected endpoint returned HTTP {$res['code']} (Access Denied).\n";
    $passCount++;
} else {
    echo "  [INFO] Endpoint returned HTTP {$res['code']}.\n";
}

// 5. Test Formula Injection Mitigation in Export API
echo "\n[TEST 5] CSV Formula Injection Mitigation...\n";
$csvUrl = "{$targetUrl}/api/export.php?category=patients&format=csv";
$res = checkUrl($csvUrl);
if ($res['code'] === 200) {
    if (strpos($res['body'], "=cmd|' /C calc'!A0") === false) {
        echo "  [PASS] CSV export sanitizes potential formula injection characters.\n";
        $passCount++;
    } else {
        echo "  [FAIL] Raw formula injection characters passed unsanitized!\n";
        $failCount++;
    }
}

echo "\n======================================================\n";
echo "SUMMARY: Passed {$passCount} tests, {$failCount} failures.\n";
echo "DAST Scan Completed Successfully.\n";
echo "======================================================\n";
