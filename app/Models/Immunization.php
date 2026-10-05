<?php
// app/Models/Immunization.php

require_once __DIR__ . '/../../config/database.php';

class Immunization
{
    private Database $db;
    private string $table = 'immunizations';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Fetch all immunizations with optional filters & sorting.
     */
    public function all(array $filters = [], array $options = []): array
    {
        // Support callers passing options directly in first parameter
        if (isset($filters['order']) && empty($options['order'])) {
            $options['order'] = $filters['order'];
            unset($filters['order']);
        }
        if (isset($filters['limit']) && empty($options['limit'])) {
            $options['limit'] = $filters['limit'];
            unset($filters['limit']);
        }
        if (empty($options['order'])) {
            $options['order'] = 'date_administered.desc';
        }
        try {
            return $this->db->select($this->table, $filters, $options);
        } catch (Throwable $e) {
            error_log('Immunization::all error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch all immunizations for a specific child.
     */
    public function allForChild(int $childId): array
    {
        return $this->all(['child_id' => $childId], ['order' => 'date_administered.asc']);
    }

    /**
     * Fetch all immunizations for a specific adult or senior patient.
     */
    public function allForPatient(int $patientId): array
    {
        return $this->all(['patient_id' => $patientId], ['order' => 'date_administered.asc']);
    }

    /**
     * Find a single immunization record by ID.
     */
    public function find(int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Immunization::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Insert a new immunization record (supports child_id or patient_id).
     */
    public function create(array $data): array
    {
        // Default patient_type if not set
        if (empty($data['patient_type'])) {
            $data['patient_type'] = !empty($data['patient_id']) ? 'adult' : 'child';
        }
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update an immunization record.
     */
    public function update(int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Delete an immunization record.
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('Immunization::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count immunizations matching filters.
     */
    public function count(array $filters = []): int
    {
        try {
            return $this->db->count($this->table, $filters);
        } catch (Throwable $e) {
            error_log('Immunization::count error: ' . $e->getMessage());
            return 0;
        }
    }
}
