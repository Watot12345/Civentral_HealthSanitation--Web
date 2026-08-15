<?php
// app/Models/SepticTank.php

require_once __DIR__ . '/../../config/database.php';

class SepticTank
{
    private Database $db;
    private string $table = 'septic_tanks';

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
            error_log('SepticTank Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('SepticTank Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByTankId(string $tankId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['tank_id' => $tankId]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('SepticTank Model Error (findByTankId): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['tank_id'])) {
            $data['tank_id'] = $this->generateTankId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'good';
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
            error_log('SepticTank Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function countByStatus(): array
    {
        try {
            $all = $this->db->select($this->table, [], ['select' => 'status']);
            $counts = ['good' => 0, 'needs_maintenance' => 0, 'critical' => 0, 'decommissioned' => 0];
            foreach ($all as $row) {
                $s = $row['status'] ?? 'good';
                if (isset($counts[$s])) $counts[$s]++;
            }
            $counts['total'] = array_sum($counts);
            return $counts;
        } catch (Throwable $e) {
            error_log('SepticTank Model Error (countByStatus): ' . $e->getMessage());
            return ['total' => 0, 'good' => 0, 'needs_maintenance' => 0, 'critical' => 0, 'decommissioned' => 0];
        }
    }

    public function generateTankId(): string
    {
        return 'ST-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
