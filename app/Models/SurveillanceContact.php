<?php
// app/Models/SurveillanceContact.php

require_once __DIR__ . '/../../config/database.php';

class SurveillanceContact
{
    private Database $db;
    private string $table = 'surveillance_contacts';

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
            error_log("SurveillanceContact DB query fallback: " . $e->getMessage());
            return [
                [
                    'id' => 1, 'contact_code' => 'CT-2026-001', 'index_case_id' => 1, 'name' => 'Anna Dela Cruz',
                    'age' => 32, 'gender' => 'Female', 'relationship' => 'Spouse', 'address' => '123 Mabini St',
                    'barangay' => 'San Jose', 'exposure_type' => 'Direct Household', 'exposure_date' => '2026-07-19',
                    'last_contact_date' => '2026-07-20', 'symptoms' => 'None', 'monitoring_status' => 'Under Monitoring',
                    'quarantine_status' => 'Quarantined', 'quarantine_start' => '2026-07-20', 'quarantine_end' => '2026-08-03',
                    'risk_level' => 'High'
                ]
            ];
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
        if (empty($data['contact_code'])) {
            $data['contact_code'] = 'CT-2026-' . str_pad((string) rand(100, 999), 3, '0', STR_PAD_LEFT);
        }
        try {
            $res = $this->db->insert($this->table, $data);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceContact insert fallback: " . $e->getMessage());
            $data['id'] = rand(10, 99);
            return $data;
        }
    }

    public function updateById($id, array $data): array
    {
        try {
            $res = $this->db->update($this->table, $data, ['id' => $id]);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceContact update fallback: " . $e->getMessage());
            $data['id'] = $id;
            return $data;
        }
    }
}
