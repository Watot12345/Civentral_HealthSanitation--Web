<?php
// Test script to verify server-side FileUploadValidator::validate()
// behavior on fake/invalid files submitted to case_reports.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../app/helpers/FileUploadValidator.php';

// 1. Create a fake XLSX file (plain text content with .xlsx extension)
$fakeFile = __DIR__ . '/fake_test.xlsx';
file_put_contents($fakeFile, "This is plain text pretending to be an XLSX spreadsheet.\nColumn1,Column2\nVal1,Val2");

echo "======================================================\n";
echo "🧪 SERVER-SIDE FILE UPLOAD VALIDATION TEST\n";
echo "Testing file: " . basename($fakeFile) . "\n";
echo "Content length: " . filesize($fakeFile) . " bytes\n";
echo "======================================================\n\n";

// 2. Direct unit validation via FileUploadValidator
$result = FileUploadValidator::validate($fakeFile, 'fake_test.xlsx', ['csv', 'xlsx', 'json', 'xls']);

echo "1. DIRECT FILEUPLOADVALIDATOR RESULT:\n";
echo "   Valid: " . ($result['valid'] ? 'true' : 'false') . "\n";
echo "   Detected MIME: " . ($result['mime'] ?? 'N/A') . "\n";
echo "   Message: " . ($result['message'] ?? 'PASS') . "\n\n";

// 3. Simulate backend case_reports.php import endpoint submission
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'import_cases';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['csrf_token'] = 'test_token';
$_POST['csrf_token'] = 'test_token';

$_FILES['file'] = [
    'name'     => 'fake_test.xlsx',
    'type'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'tmp_name' => $fakeFile,
    'error'    => UPLOAD_ERR_OK,
    'size'     => filesize($fakeFile)
];

echo "2. SIMULATED BACKEND CASE_REPORTS.PHP RESPONSE:\n";
ob_start();
// Execute the endpoint logic block
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'import_cases') {
        if (isset($_FILES['file']['tmp_name'])) {
            $valRes = FileUploadValidator::validate($_FILES['file']['tmp_name'], $_FILES['file']['name'], ['csv', 'xlsx', 'json', 'xls', 'txt']);
            if (!$valRes['valid']) {
                echo json_encode(['success' => false, 'message' => $valRes['message']], JSON_PRETTY_PRINT);
            } else {
                echo json_encode(['success' => true, 'message' => 'Valid file'], JSON_PRETTY_PRINT);
            }
        }
    }
}
$endpointResponse = ob_get_clean();
echo $endpointResponse . "\n\n";

// Clean up test artifact
@unlink($fakeFile);
echo "======================================================\n";
