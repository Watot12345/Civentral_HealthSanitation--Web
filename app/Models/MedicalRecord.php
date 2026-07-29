<?php
// app/Models/MedicalRecord.php

require_once __DIR__ . '/../../config/database.php';

class MedicalRecord
{
    private Database $db;
    private string $table = 'medical_records';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        return $this->db->select($this->table, [], $options);
    }

    public function find(string|int $id): ?array
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
                $logger->log("Added Medical Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Diagnosis: " . ($data['diagnosis'] ?? 'General Checkup'),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('MedicalRecord::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return $res;
    }

    public function updateById(string|int $id, array $data): array
    {
        $updated = $this->db->update($this->table, $data, ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Medical Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Updated medical record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('MedicalRecord::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function deleteById(string|int $id): bool
    {
        $this->db->delete($this->table, ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Deleted Medical Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Removed medical record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('MedicalRecord::deleteById ActivityLog error: ' . $e->getMessage());
            }
        }
        return true;
    }
}
