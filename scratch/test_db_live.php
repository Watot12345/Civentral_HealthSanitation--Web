<?php
require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance();
    echo "=== LIVE DATABASE DIAGNOSTICS ===\n";
    
    $tables = ['roles', 'employees', 'patients', 'appointments', 'assessment', 'triage_queue', 'consultations', 'permits', 'payments', 'children', 'immunizations', 'septic_tanks', 'service_requests', 'wastewater_invoices', 'surveillance_cases'];
    
    foreach ($tables as $t) {
        try {
            $cnt = $db->count($t);
            $rows = $db->select($t, [], ['limit' => 2]);
            echo sprintf("  %-24s: %4d records (Live Supabase)\n", $t, $cnt);
        } catch (Throwable $te) {
            echo sprintf("  %-24s: ERROR: %s\n", $t, $te->getMessage());
        }
    }
} catch (Throwable $e) {
    echo "Connection Failure: " . $e->getMessage() . "\n";
}
