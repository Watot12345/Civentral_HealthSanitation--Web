<?php
// app/Models/Assessment.php

require_once __DIR__ . '/../../config/database.php';

class Assessment
{
    private Database $db;
    private string $table = 'assessment'; // adjust table name as needed

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
            error_log('Assessment Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Assessment Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    // Additional CRUD methods can be implemented as needed
}
