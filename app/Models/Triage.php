<?php
// app/Models/Triage.php

require_once __DIR__ . '/../../config/database.php';

class Triage
{
    private Database $db;
    private string $table = 'triage';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        if (empty($options['order'])) {
            $options['order'] = 'created_at.desc';
        }
        try {
            return $this->db->select($this->table, [], $options);
        } catch (Throwable $e) {
            error_log('Triage Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Triage Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByTriageId(string $triageId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['triage_id' => $triageId]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Triage Model Error (findByTriageId): ' . $e->getMessage());
            return null;
        }
    }

    public function getByPatientId(string|int $patientId): array
    {
        try {
            return $this->db->select($this->table, ['patient_id' => $patientId], ['order' => 'created_at.desc']);
        } catch (Throwable $e) {
            error_log('Triage Model Error (getByPatientId): ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): array
    {
        if (empty($data['triage_id'])) {
            $data['triage_id'] = $this->generateTriageId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        $res = $this->db->insert($this->table, $data);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $tid = $data['triage_id'] ?? '';
                $logger->log("Recorded Triage Assessment", [
                    'module'  => 'Health Center Services',
                    'details' => "Triage ID: {$tid} | Priority: " . ($data['priority_level'] ?? 'Normal'),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Triage::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return $res;
    }

    public function updateById(string|int $id, array $data): array
    {
        $updated = $this->db->update($this->table, $data, ['id' => $id]);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Triage Assessment", [
                    'module'  => 'Health Center Services',
                    'details' => "Updated triage record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Triage::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function updateStatus(string|int $id, string $status): array
    {
        $updated = $this->db->update($this->table, ['status' => $status], ['id' => $id]);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Triage Queue Status: {$status}", [
                    'module'  => 'Health Center Services',
                    'details' => "Triage #{$id} status changed to {$status}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Triage::updateStatus ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
                require_once __DIR__ . '/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Removed Triage Record", [
                        'module'  => 'Health Center Services',
                        'details' => "Removed triage record #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {
                    error_log('Triage::deleteById ActivityLog error: ' . $e->getMessage());
                }
            }
            return true;
        } catch (Throwable $e) {
            error_log('Triage Model Error (deleteById): ' . $e->getMessage());
            return false;
        }
    }

    public function generateTriageId(): string
    {
        try {
            $all = $this->all(['limit' => 1000]);
            $maxNum = 0;
            foreach ($all as $t) {
                if (!empty($t['triage_id']) && preg_match('/TRG-(\d+)/i', $t['triage_id'], $matches)) {
                    $num = (int)$matches[1];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
            $nextNum = $maxNum + 1;
            return 'TRG-' . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return 'TRG-' . date('YmdHis') . '-' . rand(100, 999);
        }
    }
}
