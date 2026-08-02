<?php
// app/Models/Child.php

require_once __DIR__ . '/../../config/database.php';

class Child
{
    private Database $db;
    private string $table = 'children';

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
            error_log('Child Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Child Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByChildId(string $childId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['child_id' => $childId]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Child Model Error (findByChildId): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['child_id'])) {
            $data['child_id'] = $this->generateChildId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }
        if (empty($data['registration_date'])) {
            $data['registration_date'] = date('Y-m-d');
        }
        if (empty($data['vaccine_compliance'])) {
            $data['vaccine_compliance'] = 0;
        }
        return $this->db->insert($this->table, $data);
    }

    public function update(string|int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function delete(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('Child Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function search(array $criteria = [], int $limit = 10, int $offset = 0): array
    {
        $filters = [];
        $options = [
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'created_at.desc'
        ];

        if (!empty($criteria['status'])) {
            $filters['status'] = $criteria['status'];
        }

        if (!empty($criteria['gender'])) {
            $filters['gender'] = $criteria['gender'];
        }

        if (!empty($criteria['nutrition_status'])) {
            $filters['nutrition_status'] = $criteria['nutrition_status'];
        }

        if (!empty($criteria['barangay'])) {
            $filters['barangay'] = $criteria['barangay'];
        }

        if (!empty($criteria['registration_date']) && is_array($criteria['registration_date'])) {
            $filters['registration_date'] = $criteria['registration_date'];
        }

        if (!empty($criteria['search'])) {
            $searchTerm = strtolower($criteria['search']);
            $options['or'] = "(first_name.ilike.%{$searchTerm}%,last_name.ilike.%{$searchTerm}%,child_id.ilike.%{$searchTerm}%,mother_name.ilike.%{$searchTerm}%)";
        }

        try {
            return $this->db->select($this->table, $filters, $options);
        } catch (Throwable $e) {
            error_log('Child Model Error (search): ' . $e->getMessage());
            return [];
        }
    }

    public function count(array $filters = []): int
    {
        try {
            return $this->db->count($this->table, $filters);
        } catch (Throwable $e) {
            error_log('Child Model Error (count): ' . $e->getMessage());
            return 0;
        }
    }

    public function getStats(): array
    {
        try {
            $all = $this->all();
            $total = count($all);
            $active = 0;
            $critical = 0;
            $normal = 0;
            $vaccineCompliant = 0;

            foreach ($all as $child) {
                $status = $child['status'] ?? '';
                $nutrition = $child['nutrition_status'] ?? '';
                $compliance = (int)($child['vaccine_compliance'] ?? 0);

                if ($status === 'active') {
                    $active++;
                }

                if ($nutrition === 'Critical') {
                    $critical++;
                }

                if ($nutrition === 'Normal') {
                    $normal++;
                }

                if ($compliance >= 80) {
                    $vaccineCompliant++;
                }
            }

            return [
                'total' => $total,
                'active' => $active,
                'critical_nutrition' => $critical,
                'normal_nutrition' => $normal,
                'vaccine_compliant' => $vaccineCompliant
            ];
        } catch (Throwable $e) {
            error_log('Child Model Error (getStats): ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'critical_nutrition' => 0,
                'normal_nutrition' => 0,
                'vaccine_compliant' => 0
            ];
        }
    }

    public function generateChildId(): string
    {
        $count = $this->db->count($this->table) + 1;
        return 'CH-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}