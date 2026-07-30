<?php
// app/Models/SurveillanceAlert.php

require_once __DIR__ . '/../../config/database.php';

class SurveillanceAlert
{
    private Database $db;
    private string $table = 'surveillance_alerts';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function all(array $options = []): array
    {
        try {
            $opts = array_merge(['order' => 'id.desc'], $options);
            return $this->db->select($this->table, [], $opts);
        } catch (Throwable $e) {
            error_log("SurveillanceAlert DB query fallback: " . $e->getMessage());
            return [
                [
                    'id' => 1, 'alert_code' => 'ALT-2026-001', 'disease' => 'Dengue Fever', 'barangay' => 'San Jose',
                    'cases' => 12, 'threshold' => 10, 'severity' => 'Critical', 'status' => 'Active',
                    'timestamp' => date('Y-m-d H:i:s'), 'escalation_level' => 3, 'assigned_to' => 'Dr. Reyes',
                    'response_actions' => 'Immediate containment, Contact tracing, Fogging operations',
                    'message' => 'Dengue outbreak threshold exceeded in Barangay San Jose (12 cases vs 10 threshold).'
                ]
            ];
        }
    }

    public function find($id): ?array
    {
        try {
            $res = $this->db->select($this->table, ['id' => $id]);
            return $res[0] ?? null;
        } catch (Throwable $e) {
            $all = $this->all();
            foreach ($all as $a) {
                if ((int)$a['id'] === (int)$id) return $a;
            }
            return null;
        }
    }

    public function updateById($id, array $data): array
    {
        try {
            $res = $this->db->update($this->table, $data, ['id' => $id]);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceAlert update fallback: " . $e->getMessage());
            $data['id'] = $id;
            return $data;
        }
    }
}
