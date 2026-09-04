<?php
// app/Models/SchedulerLog.php

require_once __DIR__ . '/../../config/database.php';

class SchedulerLog
{
    private Database $db;
    private string $table = 'scheduler_logs';
    private string $fallbackFile;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->fallbackFile = __DIR__ . '/../../storage/logs/scheduler_logs.json';
        
        $storageDir = dirname($this->fallbackFile);
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
    }

    /**
     * Start a job execution log entry
     */
    public function start(string $jobName, string $triggeredBy = 'cron'): int|string
    {
        $data = [
            'job_name'     => $jobName,
            'status'       => 'running',
            'triggered_by' => $triggeredBy,
            'started_at'   => date('Y-m-d H:i:s'),
            'duration_ms'  => 0
        ];

        try {
            $inserted = $this->db->insert($this->table, $data, true);
            if (!empty($inserted) && isset($inserted[0]['id'])) {
                return (int)$inserted[0]['id'];
            }
        } catch (\Throwable $e) {
            error_log("SchedulerLog::start DB Error: " . $e->getMessage());
        }

        // Fallback local persistence
        return $this->writeFallbackLog($data);
    }

    /**
     * Complete a job execution log entry with final status and metrics
     */
    public function complete(int|string $id, string $status, ?string $output = null, ?string $errorMessage = null, int $durationMs = 0): bool
    {
        $data = [
            'status'        => $status,
            'output'        => $output,
            'error_message' => $errorMessage,
            'completed_at'  => date('Y-m-d H:i:s'),
            'duration_ms'   => $durationMs
        ];

        if (is_numeric($id) && (int)$id > 0) {
            try {
                $this->db->update($this->table, $data, ['id' => (int)$id], true);
                return true;
            } catch (\Throwable $e) {
                error_log("SchedulerLog::complete DB Error: " . $e->getMessage());
            }
        }

        return $this->updateFallbackLog((string)$id, $data);
    }

    /**
     * Record a complete run in one call
     */
    public function logRun(
        string $jobName,
        string $status,
        ?string $output = null,
        ?string $errorMessage = null,
        int $durationMs = 0,
        string $triggeredBy = 'cron',
        ?string $startedAt = null
    ): array {
        $started = $startedAt ?? date('Y-m-d H:i:s', time() - (int)($durationMs / 1000));
        $data = [
            'job_name'      => $jobName,
            'status'        => $status,
            'triggered_by'  => $triggeredBy,
            'output'        => $output,
            'error_message' => $errorMessage,
            'started_at'    => $started,
            'completed_at'  => date('Y-m-d H:i:s'),
            'duration_ms'   => $durationMs,
            'created_at'    => date('Y-m-d H:i:s')
        ];

        try {
            $result = $this->db->insert($this->table, $data, true);
            if (!empty($result) && is_array($result)) {
                $this->writeFallbackLog(array_merge($data, ['id' => $result[0]['id'] ?? time()]));
                return $result[0] ?? $data;
            }
        } catch (\Throwable $e) {
            error_log("SchedulerLog::logRun DB Error: " . $e->getMessage());
        }

        $id = $this->writeFallbackLog($data);
        $data['id'] = $id;
        return $data;
    }

    /**
     * Retrieve all scheduler logs with optional filtering
     */
    public function all(array $options = []): array
    {
        $limit = $options['limit'] ?? 100;
        $filters = [];
        if (!empty($options['status']) && $options['status'] !== 'all') {
            $filters['status'] = $options['status'];
        }
        if (!empty($options['job_name']) && $options['job_name'] !== 'all') {
            $filters['job_name'] = $options['job_name'];
        }

        $dbLogs = [];
        try {
            $dbLogs = $this->db->select($this->table, $filters, [
                'order' => 'created_at.desc',
                'limit' => $limit
            ]);
        } catch (\Throwable $e) {
            error_log("SchedulerLog::all DB Error: " . $e->getMessage());
        }

        $fallbackLogs = $this->getFallbackLogs();

        // Merge DB and fallback logs, deduplicating by ID or timestamp
        $combined = [];
        $seen = [];
        foreach (array_merge($dbLogs, $fallbackLogs) as $item) {
            $key = ($item['id'] ?? '') . '-' . ($item['job_name'] ?? '') . '-' . ($item['started_at'] ?? '');
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $combined[] = $item;
            }
        }

        usort($combined, function($a, $b) {
            $ta = strtotime($a['created_at'] ?? $a['started_at'] ?? 'now');
            $tb = strtotime($b['created_at'] ?? $b['started_at'] ?? 'now');
            return $tb <=> $ta;
        });

        if (count($combined) > $limit) {
            $combined = array_slice($combined, 0, $limit);
        }

        return $combined;
    }

    /**
     * Calculate summary statistics for scheduler dashboard
     */
    public function getStats(): array
    {
        $logs = $this->all(['limit' => 500]);
        $total = count($logs);
        $success = 0;
        $failed = 0;
        $lastRun = null;

        foreach ($logs as $log) {
            $st = strtolower($log['status'] ?? '');
            if ($st === 'success') {
                $success++;
            } elseif ($st === 'failed') {
                $failed++;
            }

            $createdAt = $log['created_at'] ?? $log['started_at'] ?? null;
            if ($createdAt && ($lastRun === null || strtotime($createdAt) > strtotime($lastRun))) {
                $lastRun = $createdAt;
            }
        }

        $successRate = $total > 0 ? round(($success / $total) * 100, 1) : 100;

        return [
            'total'        => $total,
            'success'      => $success,
            'failed'       => $failed,
            'success_rate' => $successRate,
            'last_run'     => $lastRun
        ];
    }

    private function getFallbackLogs(): array
    {
        if (file_exists($this->fallbackFile)) {
            $raw = @file_get_contents($this->fallbackFile);
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private function writeFallbackLog(array $data): string
    {
        $logs = $this->getFallbackLogs();
        $id = $data['id'] ?? ('log_' . bin2hex(random_bytes(6)));
        $data['id'] = $id;
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        array_unshift($logs, $data);
        if (count($logs) > 500) {
            $logs = array_slice($logs, 0, 500);
        }
        @file_put_contents($this->fallbackFile, json_encode($logs, JSON_PRETTY_PRINT));
        return (string)$id;
    }

    private function updateFallbackLog(string $id, array $updates): bool
    {
        $logs = $this->getFallbackLogs();
        $found = false;
        foreach ($logs as &$log) {
            if ((string)($log['id'] ?? '') === (string)$id) {
                foreach ($updates as $k => $v) {
                    $log[$k] = $v;
                }
                $found = true;
                break;
            }
        }
        unset($log);
        if ($found) {
            @file_put_contents($this->fallbackFile, json_encode($logs, JSON_PRETTY_PRINT));
        }
        return $found;
    }
}
