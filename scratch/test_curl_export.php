<?php
// Test sending request to export.php via localhost HTTP
$url = 'http://localhost/Civentral_HealthSanitation--Web/api/reports/export.php?format=pdf&title=Test%20Report&module=Sanitation';

$sampleHtml = '<!doctype html><html><head><meta charset="UTF-8"><title>Test</title></head><body><h1>Test Report</h1><p>Executive Content</p></body></html>';

$payload = json_encode([
    'html' => $sampleHtml,
    'title' => 'Test Report'
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: PHPSESSID=' // without session or with session
]);
curl_setopt($ch, CURLOPT_HEADER, true);

$res = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Content-Type: " . $info['content_type'] . "\n";
echo "Size: " . strlen($res) . "\n";
echo "Head:\n" . substr($res, 0, 500) . "\n";
