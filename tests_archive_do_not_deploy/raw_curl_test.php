<?php
// Script to run real HTTP curl request against case_reports.php on local web server

$baseUrl = 'http://127.0.0.1:8080';
$cookieFile = __DIR__ . '/cookie.txt';

// Step 1: Create fake XLSX file
$fakeFile = __DIR__ . '/fake_test.xlsx';
file_put_contents($fakeFile, "This is plain text pretending to be an XLSX spreadsheet.\nColumn1,Column2\nVal1,Val2");

// Step 1: Initialize authenticated session via session_helper.php
$ch = curl_init("{$baseUrl}/tests/session_helper.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$sessJson = curl_exec($ch);
curl_close($ch);

$sessData = json_decode($sessJson, true);
$csrfToken = $sessData['csrf_token'] ?? 'valid_test_csrf_token_12345';
echo "DEBUG: Session authenticated! Session CSRF Token: '{$csrfToken}'\n";

// Step 3: Send REAL multipart/form-data POST request with curl
$ch = curl_init("{$baseUrl}/modules/surveillence/case_reports.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_POST, true);

$cFile = new CURLFile($fakeFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'fake_test.xlsx');

$postData = [
    'action'     => 'import_cases',
    'csrf_token' => $csrfToken,
    'file'       => $cFile
];

curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "======================================================\n";
echo "🌐 REAL HTTP CURL REQUEST TO CASE_REPORTS.PHP\n";
echo "Target Endpoint: {$baseUrl}/modules/surveillence/case_reports.php\n";
echo "HTTP Status Code: {$httpCode}\n";
echo "======================================================\n\n";
echo "RESPONSE HEADERS:\n";
echo trim($headers) . "\n\n";
echo "RESPONSE BODY:\n";
echo trim($body) . "\n\n";
echo "======================================================\n";

// Cleanup
@unlink($fakeFile);
@unlink($cookieFile);
