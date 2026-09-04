<?php
// app/Models/Patient.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

class Patient
{
    private Database $db;
    private string $table = 'patients';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        try {
            $results = $this->db->select($this->table, [], $options);
            return EncryptionHelper::decryptRows($this->table, $results);
        } catch (\Throwable $e) {
            error_log("Patient::all error: " . $e->getMessage());
            return [];
        }
    }

    public function find(string $id): ?array
    {
        $result = $this->db->select($this->table, ['id' => 'eq.' . $id]);
        return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
    }

    public function findByPatientId(string $patientId): ?array
    {
        $result = $this->db->select($this->table, ['patient_id' => 'eq.' . $patientId]);
        return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
    }

    public function create(array $data): array
    {
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        $res = $this->db->insert($this->table, $encryptedData, true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                $pid = $data['patient_id'] ?? ($res['patient_id'] ?? '');
                $logger->log("Added Patient: {$name}", [
                    'module'  => 'Health Center Services',
                    'details' => "Patient ID: {$pid} | Barangay: " . ($data['barangay'] ?? 'N/A'),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Patient::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($res) ? EncryptionHelper::decryptModel($this->table, $res) : $res;
    }

    public function updateById(string $id, array $data): array
    {
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        $updated = $this->db->update($this->table, $encryptedData, ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                $logger->log("Updated Patient Record: " . ($name ?: "ID {$id}"), [
                    'module'  => 'Health Center Services',
                    'details' => "Updated patient record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Patient::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($updated) ? EncryptionHelper::decryptRows($this->table, $updated) : $updated;
    }

    public function deleteById(string $id): bool
    {
        $this->db->delete($this->table, ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Deleted Patient Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Removed patient record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Patient::deleteById ActivityLog error: ' . $e->getMessage());
            }
        }
        return true;
    }

    public function search(string $query): array
    {
        $all = $this->all();
        $query = strtolower($query);
        return array_values(array_filter($all, function($p) use ($query) {
            return str_contains(strtolower($p['first_name'] ?? ''), $query) ||
                   str_contains(strtolower($p['last_name'] ?? ''), $query) ||
                   str_contains(strtolower($p['patient_id'] ?? ''), $query) ||
                   str_contains(strtolower($p['barangay'] ?? ''), $query);
        }));
    }
}