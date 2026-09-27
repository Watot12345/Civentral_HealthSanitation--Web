<?php
require_once __DIR__ . '/../config/database.php';
try {
    $db = Database::getInstance();
    $rows = $db->select('activity_logs', [], ['limit' => 1]);
    echo "SELECT SUCCESS:\n";
    print_r($rows);

    $testData = [
        'user_id'    => 1,
        'user_name'  => 'Test User',
        'role'       => 'Admin',
        'module'     => 'Reports',
        'action'     => 'Generated Report: Test',
        'details'    => 'Test Details',
        'ip_address' => '127.0.0.1',
        'device'     => 'Desktop',
        'status'     => 'Success',
        'created_at' => date('Y-m-d H:i:s')
    ];
    $insertRes = $db->insert('activity_logs', $testData);
    echo "INSERT SUCCESS:\n";
    print_r($insertRes);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
