<?php
// Comprehensive Integration Test for All System File Upload Endpoints
// Verifies that finfo_file() byte inspection via FileUploadValidator rejects spoofed files

$baseUrl = 'http://127.0.0.1:8080';
$cookieFile = __DIR__ . '/cookie.txt';
$fakeFile = __DIR__ . '/fake_test.xlsx';

file_put_contents($fakeFile, "This is plain text pretending to be an XLSX file.\nColumn1,Column2\nVal1,Val2");

// 1. Establish authenticated session via session_helper
$ch = curl_init("{$baseUrl}/tests/session_helper.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$sessJson = curl_exec($ch);
curl_close($ch);

$sessData = json_decode($sessJson, true);
$csrfToken = $sessData['csrf_token'] ?? 'valid_test_csrf_token_12345';

echo "==========================================================\n";
echo "🛡️ ALL FILE UPLOAD ENDPOINTS — REAL HTTP cURL VERIFICATION\n";
echo "==========================================================\n\n";

function testEndpoint($name, $url, $postFields) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $body = substr($response, $headerSize);

    echo "[TEST] {$name}\n";
    echo "       URL: {$url}\n";
    echo "       HTTP Status Code: {$httpCode}\n";
    echo "       Response Body: " . trim($body) . "\n\n";
}

// ------------------------------------------------------------
// ENDPOINT 1: Case Reports Import
// ------------------------------------------------------------
$cFile1 = new CURLFile($fakeFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'fake_test.xlsx');
testEndpoint('1. Case Reports Import (modules/surveillence/case_reports.php)', "{$baseUrl}/modules/surveillence/case_reports.php", [
    'action'     => 'import_cases',
    'csrf_token' => $csrfToken,
    'file'       => $cFile1
]);

// ------------------------------------------------------------
// ENDPOINT 2: System Settings Import
// ------------------------------------------------------------
$cFile2 = new CURLFile($fakeFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'fake_test.xlsx');
testEndpoint('2. System Settings Import (api/settings/import.php)', "{$baseUrl}/api/settings/import.php", [
    'csrf_token' => $csrfToken,
    'file'       => $cFile2
]);

// ------------------------------------------------------------
// ENDPOINT 3: Sanitation Permit Document Upload
// ------------------------------------------------------------
$cFile3 = new CURLFile($fakeFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'fake_test.xlsx');
testEndpoint('3. Sanitation Permit Document Upload (api/permit_documents.php)', "{$baseUrl}/api/permit_documents.php", [
    'permit_id'     => 1,
    'document_type' => 'sanitary_permit',
    'uploaded_by'   => 1,
    'file'          => $cFile3
]);

// ------------------------------------------------------------
// ENDPOINT 4: Announcement Attachments Upload
// ------------------------------------------------------------
$cFile4 = new CURLFile($fakeFile, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'fake_test.xlsx');
testEndpoint('4. Announcement Attachment Upload (api/announcements.php)', "{$baseUrl}/api/announcements.php", [
    'title'    => 'Test Security Audit Announcement',
    'body'     => 'Testing upload validation for attachments.',
    'category' => 'General Announcement',
    'file'     => $cFile4
]);

echo "==========================================================\n";

// Cleanup
@unlink($fakeFile);
@unlink($cookieFile);
