<?php
// app/services/SystemMetricsService.php

namespace App\Services;

require_once __DIR__ . '/../../config/database.php';

use Database;
use Throwable;

class SystemMetricsService
{
    private static ?SystemMetricsService $instance = null;
    private Database $db;

    private function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function getInstance(): SystemMetricsService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Measures Supabase PostgreSQL response latency and live record statistics with caching.
     */
    public function getMetrics(bool $forceRefresh = false): array
    {
        $cacheFile = __DIR__ . '/../../storage/cache/supabase_db_metrics.json';
        if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
            $cached = @json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['total_records'])) {
                return $cached;
            }
        }

        try {
            $startTime = microtime(true);
            
            // Ping latency test
            $testPing = $this->db->query('barangays', 'GET', null, [], ['select' => 'id', 'limit' => 1]);
            $latencyMs = max(1, (int)round((microtime(true) - $startTime) * 1000));

            $systemTables = [
                'employees', 'roles', 'permissions', 'role_permissions',
                'patients', 'appointments', 'consultations', 'assessment',
                'prescriptions', 'referrals', 'medical_records', 'triage_queue',
                'permits', 'inspections', 'permit_documents', 'payments',
                'renewals', 'renewal_history', 'children', 'immunizations',
                'immunization_assessments', 'service_providers', 'septic_tanks',
                'service_requests', 'maintenance_records', 'wastewater_invoices',
                'surveillance_cases', 'surveillance_index_cases', 'surveillance_alerts',
                'surveillance_intel_queue', 'surveillance_intel_log', 'barangays',
                'setting_categories', 'system_settings', 'feature_flags',
                'settings_versions', 'activity_logs', 'announcements'
            ];

            $multiConfig = [];
            foreach ($systemTables as $tbl) {
                $multiConfig[$tbl] = ['select' => 'id', 'limit' => 10000];
            }

            $tableResults = $this->db->multiSelect($multiConfig);
            $totalRecords = 0;
            $tableStats = [];

            foreach ($tableResults as $tbl => $rows) {
                $count = count($rows);
                $totalRecords += $count;
                $tableStats[$tbl] = $count;
            }

            $result = [
                'success' => true,
                'latency_ms' => $latencyMs,
                'status' => 'healthy',
                'total_records' => $totalRecords,
                'table_count' => count($systemTables),
                'active_tables_count' => count(array_filter($tableStats, fn($c) => $c > 0)),
                'tables' => $tableStats,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!is_dir(dirname($cacheFile))) {
                @mkdir(dirname($cacheFile), 0755, true);
            }
            @file_put_contents($cacheFile, json_encode($result));

            return $result;
        } catch (Throwable $e) {
            error_log("Supabase SystemMetricsService error: " . $e->getMessage());
            return [
                'success' => false,
                'latency_ms' => 0,
                'status' => 'unreachable',
                'error' => $e->getMessage(),
                'total_records' => 0,
                'table_count' => 0,
                'active_tables_count' => 0,
                'tables' => [],
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
    }
}
