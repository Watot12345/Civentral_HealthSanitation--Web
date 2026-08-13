<?php
// app/Models/GrowthMeasurement.php

require_once __DIR__ . '/../../config/database.php';

class GrowthMeasurement
{
    private Database $db;
    private string $table = 'growth_measurements';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetch all measurements for a given child, ordered by date ascending.
     */
    public function allForChild(int $childId): array
    {
        try {
            return $this->db->select($this->table, ['child_id' => $childId], [
                'order' => 'measurement_date.asc'
            ]);
        } catch (Throwable $e) {
            error_log('GrowthMeasurement::allForChild error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a single measurement by primary key.
     */
    public function find(int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('GrowthMeasurement::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Insert a new measurement. Returns the inserted record array.
     */
    public function create(array $data): array
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update an existing measurement by ID.
     */
    public function update(int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Delete a measurement by ID.
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('GrowthMeasurement::delete error: ' . $e->getMessage());
            return false;
        }
    }
}
