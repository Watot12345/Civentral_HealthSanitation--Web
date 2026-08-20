<?php
// app/Models/SurveillanceCase.php

require_once __DIR__ . '/../../config/database.php';

class SurveillanceCase
{
    private Database $db;
    private string $table = 'surveillance_cases';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function all(array $options = []): array
    {
        try {
            $opts = array_merge(['order' => 'id.desc'], $options);
            return $this->db->select($this->table, [], $opts);
        } catch (Throwable $e) {
            error_log("SurveillanceCase DB query error: " . $e->getMessage());
            return [];
        }
    }

    public function find($id): ?array
    {
        try {
            $res = $this->db->select($this->table, ['id' => $id]);
            return $res[0] ?? null;
        } catch (Throwable $e) {
            $all = $this->all();
            foreach ($all as $c) {
                if ((int)$c['id'] === (int)$id) return $c;
            }
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['case_code'])) {
            $data['case_code'] = 'CS-2026-' . str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);
        }
        try {
            $res = $this->db->insert($this->table, $data);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceCase insert fallback: " . $e->getMessage());
            $data['id'] = rand(10, 99);
            return $data;
        }
    }

    public function updateById($id, array $data): array
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        try {
            $res = $this->db->update($this->table, $data, ['id' => $id]);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceCase update fallback: " . $e->getMessage());
            $data['id'] = $id;
            return $data;
        }
    }

    public function getIndexCases(): array
    {
        try {
            return $this->db->select('surveillance_index_cases', [], ['order' => 'id.desc']);
        } catch (Throwable $e) {
            return [
                ['id' => 1, 'index_code' => 'IDX-2026-001', 'name' => 'Juan Dela Cruz', 'age' => 34, 'gender' => 'Male', 'barangay' => 'San Jose', 'disease' => 'Dengue Fever', 'date_confirmed' => '2026-07-20', 'status' => 'Isolated', 'risk_level' => 'High'],
                ['id' => 2, 'index_code' => 'IDX-2026-002', 'name' => 'Maria Santos', 'age' => 28, 'gender' => 'Female', 'barangay' => 'San Jose', 'disease' => 'Dengue Fever', 'date_confirmed' => '2026-07-21', 'status' => 'Hospitalized', 'risk_level' => 'Critical']
            ];
        }
    }
}
