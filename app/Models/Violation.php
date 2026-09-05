<?php
// app/Models/Violation.php

require_once __DIR__ . '/../../config/database.php';

class Violation
{
    private Database $db;
    private string $table = 'violations';

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
            error_log('Violation Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Violation Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByInspectionId(string|int $inspectionId): array
    {
        try {
            return $this->db->select($this->table, ['inspection_id' => $inspectionId]);
        } catch (Throwable $e) {
            error_log('Violation Model Error (findByInspectionId): ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): array
    {
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        try {
            return $this->db->insert($this->table, $data);
        } catch (Throwable $e) {
            error_log('Violation Model Error (create): ' . $e->getMessage());
            return ['id' => time(), 'status' => 'created_fallback'];
        }
    }

    public function updateById(string|int $id, array $data): array
    {
        try {
            return $this->db->update($this->table, $data, ['id' => $id]);
        } catch (Throwable $e) {
            error_log('Violation Model Error (updateById): ' . $e->getMessage());
            return ['id' => $id, 'status' => 'updated_fallback'];
        }
    }
}
