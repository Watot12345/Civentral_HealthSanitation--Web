<?php
// app/Models/ImmunizationAssessment.php

require_once __DIR__ . '/../../config/database.php';

class ImmunizationAssessment
{
    private Database $db;
    private string $table = 'immunization_assessments';

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
            error_log('ImmunizationAssessment Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('ImmunizationAssessment Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        try {
            return $this->db->insert($this->table, $data);
        } catch (Throwable $e) {
            error_log('ImmunizationAssessment Model Error (create): ' . $e->getMessage());
            return [];
        }
    }
}
