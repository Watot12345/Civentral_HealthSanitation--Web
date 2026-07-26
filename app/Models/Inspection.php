<?php
// app/Models/Inspection.php

require_once __DIR__ . '/../../config/database.php';

class Inspection
{
    private Database $db;
    private string $table = 'inspections';

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
            error_log('Inspection Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Inspection Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['inspection_id'])) {
            $data['inspection_id'] = $this->generateInspectionId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'scheduled';
        }
        return $this->db->insert($this->table, $data);
    }

    public function updateById(string|int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('Inspection Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a unique inspection ID
     */
    public function generateInspectionId(): string
    {
        return 'INS-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
