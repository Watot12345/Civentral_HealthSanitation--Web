<?php
$url  = 'https://github.com/phpstan/phpstan/releases/latest/download/phpstan.phar';
$dest = __DIR__ . '/../phpstan.phar';
echo "Downloading phpstan.phar from GitHub...\n";
$ctx   = stream_context_create(['http' => ['timeout' => 90, 'follow_location' => 1]]);
$data  = @file_get_contents($url, false, $ctx);
if ($data === false) {
    echo "FAILED: file_get_contents returned false\n";
    exit(1);
}
file_put_contents($dest, $data);
echo "Downloaded: " . number_format(strlen($data)) . " bytes -> phpstan.phar\n";
// Verify it's a valid PHAR
try {
    $p = new Phar($dest);
    echo "PHAR valid: yes\n";
} catch (Exception $e) {
    echo "PHAR validation warning: " . $e->getMessage() . "\n";
}
