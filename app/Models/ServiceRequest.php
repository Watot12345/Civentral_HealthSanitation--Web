<?php
// app/Models/ServiceRequest.php

require_once __DIR__ . '/../../config/database.php';

class ServiceRequest
{
    private Database $db;
    private string $table = 'service_requests';

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
            error_log('ServiceRequest Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('ServiceRequest Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findActiveByTankId(string $tankId, ?string $date = null): ?array
    {
        try {
            $records = $this->db->select($this->table, ['tank_id' => $tankId]);
            foreach ($records as $r) {
                $st = strtolower($r['status'] ?? '');
                if ($st === 'pending' || $st === 'approved' || $st === 'in_progress' || $st === 'scheduled') {
                    if ($date) {
                        $rDate = substr((string)($r['preferred_date'] ?? $r['created_at'] ?? ''), 0, 10);
                        if ($rDate === substr($date, 0, 10)) {
                            return $r;
                        }
                    } else {
                        return $r;
                    }
                }
            }
            return null;
        } catch (Throwable $e) {
            error_log('ServiceRequest Model Error (findActiveByTankId): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['request_id'])) {
            $data['request_id'] = $this->generateRequestId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        if (empty($data['priority'])) {
            $data['priority'] = 'medium';
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
            error_log('ServiceRequest Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function countByStatus(): array
    {
        try {
            $all = $this->db->select($this->table, [], ['select' => 'status,priority']);
            $counts = ['pending' => 0, 'approved' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];
            $priority = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
            foreach ($all as $row) {
                $s = $row['status'] ?? 'pending';
                $p = $row['priority'] ?? 'medium';
                if (isset($counts[$s])) $counts[$s]++;
                if (isset($priority[$p])) $priority[$p]++;
            }
            $counts['total'] = array_sum($counts);
            $counts['priority'] = $priority;
            return $counts;
        } catch (Throwable $e) {
            error_log('ServiceRequest Model Error (countByStatus): ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'approved' => 0, 'in_progress' => 0, 'completed' => 0, 'cancelled' => 0];
        }
    }

    public function generateRequestId(): string
    {
        return 'SR-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
