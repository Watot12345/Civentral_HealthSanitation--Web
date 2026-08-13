<?php
// app/Models/NutritionAssessment.php

require_once __DIR__ . '/../../config/database.php';

class NutritionAssessment
{
    private Database $db;
    private string $table = 'nutrition_assessments';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Fetch all assessments, with optional filters. Joins child name from children table
     * by fetching children separately and merging (PostgREST join via select param).
     */
    public function all(array $filters = [], array $options = []): array
    {
        if (empty($options['order'])) {
            $options['order'] = 'assessment_date.desc';
        }
        try {
            return $this->db->select($this->table, $filters, $options);
        } catch (Throwable $e) {
            error_log('NutritionAssessment::all error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch all assessments for a specific child.
     */
    public function allForChild(int $childId): array
    {
        return $this->all(['child_id' => $childId]);
    }

    /**
     * Find a single assessment by primary key.
     */
    public function find(int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('NutritionAssessment::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Insert a new assessment. Returns the inserted record array.
     */
    public function create(array $data): array
    {
        // Compute BMI if not provided
        if (!isset($data['bmi']) && isset($data['weight']) && isset($data['height']) && (float)$data['height'] > 0) {
            $heightM = (float)$data['height'] / 100;
            $data['bmi'] = round((float)$data['weight'] / ($heightM * $heightM), 2);
        }
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update an existing assessment by ID.
     */
    public function update(int $id, array $data): array
    {
        // Recompute BMI on update if weight/height changed
        if ((isset($data['weight']) || isset($data['height']))) {
            $existing = $this->find($id);
            $weight = (float)($data['weight'] ?? ($existing['weight'] ?? 0));
            $height = (float)($data['height'] ?? ($existing['height'] ?? 0));
            if ($height > 0) {
                $heightM = $height / 100;
                $data['bmi'] = round($weight / ($heightM * $heightM), 2);
            }
        }
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Delete an assessment by ID.
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('NutritionAssessment::delete error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync the latest nutrition_status back to the children table.
     * Called after create/update so KPI cards in child_records.php stay current.
     */
    public function syncChildNutritionStatus(int $childId, string $status): void
    {
        $validStatuses = ['Normal', 'Moderate', 'Critical'];
        $mapped = [
            'normal'   => 'Normal',
            'moderate' => 'Moderate',
            'critical' => 'Critical',
        ];
        $dbStatus = $mapped[strtolower($status)] ?? 'Normal';
        if (!in_array($dbStatus, $validStatuses)) return;

        try {
            $this->db->update('children', ['nutrition_status' => $dbStatus], ['id' => $childId]);
        } catch (Throwable $e) {
            error_log('NutritionAssessment::syncChildNutritionStatus error: ' . $e->getMessage());
        }
    }

    /**
     * Count records matching optional filters.
     */
    public function count(array $filters = []): int
    {
        try {
            return $this->db->count($this->table, $filters);
        } catch (Throwable $e) {
            error_log('NutritionAssessment::count error: ' . $e->getMessage());
            return 0;
        }
    }
}
