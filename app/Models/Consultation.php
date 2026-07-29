<?php
// app/Models/Consultation.php

require_once __DIR__ . '/../../config/database.php';

class Consultation
{
    private Database $db;
    private string $table = 'consultations';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        return $this->db->select($this->table, [], $options);
    }

    public function find(string $id): ?array
    {
        $result = $this->db->select($this->table, ['id' => 'eq.' . $id]);
        return !empty($result) ? $result[0] : null;
    }

    public function create(array $data): array
    {
        $res = $this->db->insert($this->table, $data, true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $cid = $data['consultation_id'] ?? ($res['consultation_id'] ?? '');
                $logger->log("Recorded Consultation Entry", [
                    'module'  => 'Health Center Services',
                    'details' => "Consultation ID: {$cid} | Patient: " . ($data['patient_name'] ?? 'N/A'),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Consultation::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return $res;
    }

    public function updateById(string $id, array $data): array
    {
        $updated = $this->db->update($this->table, $data, ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Consultation Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Updated consultation #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Consultation::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function deleteById(string $id): bool
    {
        $this->db->delete($this->table, ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Deleted Consultation Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Removed consultation #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Consultation::deleteById ActivityLog error: ' . $e->getMessage());
            }
        }
        return true;
    }

    public function generateConsultationId(): string
    {
        try {
            $all = $this->all();
            $maxNum = 0;
            foreach ($all as $c) {
                if (!empty($c['consultation_id']) && preg_match('/CONS-(\d+)/i', $c['consultation_id'], $matches)) {
                    $num = (int)$matches[1];
                    if ($num > $maxNum) $maxNum = $num;
                }
            }
            return 'CONS-' . str_pad((string)($maxNum + 1), 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return 'CONS-' . date('YmdHis') . '-' . rand(100, 999);
        }
    }
}