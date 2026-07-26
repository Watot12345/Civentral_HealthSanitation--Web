<?php
// app/Models/Permit.php

require_once __DIR__ . '/../../config/database.php';

class Permit
{
    private Database $db;
    private string $table = 'permits';

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
            error_log('Permit Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Permit Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        // Let a DB trigger or PHP generate the permit_id if missing
        if (empty($data['permit_id'])) {
            $data['permit_id'] = $this->generatePermitId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
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
            error_log('Permit Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Fast fallback ID generator — does NOT scan entire table.
     */
    public function generatePermitId(): string
    {
        return 'SP-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
