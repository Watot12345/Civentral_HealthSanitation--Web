<?php
// app/Models/MaintenanceRecord.php

require_once __DIR__ . '/../../config/database.php';

class MaintenanceRecord
{
    private Database $db;
    private string $table = 'maintenance_records';

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
            error_log('MaintenanceRecord Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('MaintenanceRecord Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['service_id'])) {
            $data['service_id'] = $this->generateServiceId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'scheduled';
        }
        return $this->db->insert($this->table, $data);
    }

    public function updateById(string|int $id, array $data): array
    {
        $data['updated_at'] = date('c');
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('MaintenanceRecord Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function countByStatus(): array
    {
        try {
            $all = $this->db->select($this->table, [], ['select' => 'status']);
            $counts = ['scheduled' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];
            foreach ($all as $row) {
                $s = $row['status'] ?? 'scheduled';
                if (isset($counts[$s])) $counts[$s]++;
            }
            $counts['total'] = array_sum($counts);
            return $counts;
        } catch (Throwable $e) {
            error_log('MaintenanceRecord Model Error (countByStatus): ' . $e->getMessage());
            return ['total' => 0, 'scheduled' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];
        }
    }

    public function generateServiceId(): string
    {
        return 'SRV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
