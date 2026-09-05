<?php
// app/Models/Employee.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

class Employee
{
    private Database $db;
    private string $table = 'employees';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    private static ?array $cachedEmployees = null;
    private static ?int $cacheTime = null;

    public function all(array $options = []): array
    {
        if (empty($options) && self::$cachedEmployees !== null && self::$cacheTime !== null && (time() - self::$cacheTime < 120)) {
            return self::$cachedEmployees;
        }

        try {
            $employees = $this->db->select($this->table, [], $options);
            $normalized = $this->normalizeEmployees($employees);
            if (empty($options)) {
                self::$cachedEmployees = $normalized;
                self::$cacheTime = time();
            }
            return $normalized;
        } catch (Throwable $e) {
            // Fallback to users table if employees table is not directly accessible or missing
            try {
                $users = $this->db->select('users', [], $options);
                $normalized = $this->normalizeEmployees($users);
                if (empty($options)) {
                    self::$cachedEmployees = $normalized;
                    self::$cacheTime = time();
                }
                return $normalized;
            } catch (Throwable $e2) {
                return [];
            }
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            $employee = !empty($result) ? $result[0] : null;
            return $employee ? $this->normalizeEmployee($employee) : null;
        } catch (Throwable $e) {
            try {
                $result = $this->db->select('users', ['id' => $id]);
                $employee = !empty($result) ? $result[0] : null;
                return $employee ? $this->normalizeEmployee($employee) : null;
            } catch (Throwable $e2) {
                return null;
            }
        }
    }

    public function resolveRoleId(array $data): ?int
    {
        if (!empty($data['role_id'])) {
            return (int)$data['role_id'];
        }
        $targetRole = trim($data['role_description'] ?? $data['role'] ?? '');
        if (empty($targetRole)) return null;

        try {
            require_once __DIR__ . '/Role.php';
            $roleModel = new Role();
            foreach ($roleModel->all() as $r) {
                if (strcasecmp(trim($r['name']), $targetRole) === 0) {
                    return (int)$r['id'];
                }
            }
        } catch (Throwable $e) {}
        return null;
    }

    public function create(array $data): array
    {
        if (empty($data['role_id'])) {
            $data['role_id'] = $this->resolveRoleId($data);
        }
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        return $this->db->insert($this->table, $encryptedData, true);
    }

    public function updateById(string|int $id, array $data): array
    {
        if (empty($data['role_id']) && (!empty($data['role_description']) || !empty($data['role']))) {
            $data['role_id'] = $this->resolveRoleId($data);
        }
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        return $this->db->update($this->table, $encryptedData, ['id' => 'eq.' . $id], true);
    }

    public function findByEmployeeId(string $employeeId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['employee_id' => $employeeId]);
            if (empty($result)) {
                $result = $this->db->select($this->table, ['username' => $employeeId]);
            }
            return !empty($result) ? $this->normalizeEmployee($result[0]) : null;
        } catch (Throwable $e) {
            return null;
        }
    }


    public function deleteById(string|int $id): bool
    {
        $this->db->delete($this->table, ['id' => 'eq.' . $id], true);
        return true;
    }

    /**
     * Auto-generate next Employee ID based on Department and Role prefix (e.g. HCD-0002, HMP-0005, SO-0004)
     */
    public function generateNextEmployeeId(string $role, string $department = ''): string
    {
        $deptRolePrefixes = [
            'Health Center Services_Health Center Director' => ['prefix' => 'HCD-', 'pad' => 4],
            'Health Center Services_Medical Practitioner'   => ['prefix' => 'HMP-', 'pad' => 4],
            'Health Center Services_Health Center Staff'     => ['prefix' => 'HCS-', 'pad' => 4],
            'Health Center_Health Center Director'          => ['prefix' => 'HCD-', 'pad' => 4],
            'Health Center_Medical Practitioner'            => ['prefix' => 'HMP-', 'pad' => 4],
            'Health Center_Health Center Staff'              => ['prefix' => 'HCS-', 'pad' => 4],

            'Sanitation Permits_Sanitation Director'        => ['prefix' => 'SD-',  'pad' => 4],
            'Sanitation Permits_Sanitation Officer'         => ['prefix' => 'SO-',  'pad' => 4],
            'Sanitation_Sanitation Director'                => ['prefix' => 'SD-',  'pad' => 4],
            'Sanitation_Sanitation Officer'                 => ['prefix' => 'SO-',  'pad' => 4],

            'Immunization & Nutrition_Immunization Lead'    => ['prefix' => 'IL-',  'pad' => 4],
            'Immunization & Nutrition_Nutrition Staff'      => ['prefix' => 'NS-',  'pad' => 4],
            'Immunization_Immunization Lead'                => ['prefix' => 'IL-',  'pad' => 4],
            'Nutrition_Nutrition Staff'                     => ['prefix' => 'NS-',  'pad' => 4],

            'Wastewater Services_Wastewater Lead'           => ['prefix' => 'WL-',  'pad' => 4],
            'Wastewater_Wastewater Lead'                    => ['prefix' => 'WL-',  'pad' => 4],

            'Health Surveillance_Surveillance Lead'         => ['prefix' => 'SL-',  'pad' => 4],
            'Administration_System Admin'                   => ['prefix' => 'HSA-ADMIN-', 'pad' => 2],
        ];

        $deptPrefixes = [
            'Health Center Services'   => ['prefix' => 'HCD-', 'pad' => 4],
            'Health Center'            => ['prefix' => 'HCD-', 'pad' => 4],
            'Sanitation Permits'       => ['prefix' => 'SD-',  'pad' => 4],
            'Sanitation'               => ['prefix' => 'SD-',  'pad' => 4],
            'Immunization & Nutrition' => ['prefix' => 'IL-',  'pad' => 4],
            'Immunization'             => ['prefix' => 'IL-',  'pad' => 4],
            'Nutrition'                => ['prefix' => 'NS-',  'pad' => 4],
            'Wastewater Services'      => ['prefix' => 'WL-',  'pad' => 4],
            'Wastewater'               => ['prefix' => 'WL-',  'pad' => 4],
            'Health Surveillance'      => ['prefix' => 'SL-',  'pad' => 4],
            'Administration'           => ['prefix' => 'HSA-ADMIN-', 'pad' => 2],
        ];

        $key = "{$department}_{$role}";
        $config = $deptRolePrefixes[$key] ?? ($deptPrefixes[$department] ?? ['prefix' => 'EMP-', 'pad' => 4]);
        $prefix = $config['prefix'];
        $pad = $config['pad'];

        $all = $this->all();
        $maxNum = 0;

        foreach ($all as $emp) {
            $empId = $emp['employee_id'] ?? $emp['username'] ?? '';
            if (str_starts_with($empId, $prefix)) {
                $numPart = substr($empId, strlen($prefix));
                if (is_numeric($numPart)) {
                    $num = (int) $numPart;
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
        }

        return $prefix . str_pad($maxNum + 1, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * Toggle status between Active / Inactive
     */
    public function toggleStatus(string|int $id): ?array
    {
        $employee = $this->find($id);
        if (!$employee) {
            return null;
        }

        $newStatus = ($employee['status'] ?? 'Active') === 'Active' ? 'Inactive' : 'Active';
        $result = $this->updateById($id, ['status' => $newStatus]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Get statistics for the KPI cards
     */
    public function getStatistics(): array
    {
        $all = $this->all();
        $total = count($all);
        $active = count(array_filter($all, fn($e) => ($e['status'] ?? 'Active') === 'Active'));
        $inactive = $total - $active;

        return [
            'total_users'       => $total,
            'active_users'      => $active,
            'inactive_users'    => $inactive,
            'active_percentage' => $total > 0 ? round(($active / $total) * 100) : 0,
        ];
    }

    /**
     * Normalize employee data to ensure consistent structure
     */
    private function normalizeEmployees(array $employees): array
    {
        return array_map([$this, 'normalizeEmployee'], $employees);
    }

    /**
     * Normalize a single employee record to ensure full_name and other fields exist
     */
    private function normalizeEmployee(array $employee): array
    {
        // Use full_name from database, or build from other fields
        if (empty($employee['full_name']) && !empty($employee['name'])) {
            $employee['full_name'] = $employee['name'];
        } elseif (empty($employee['full_name']) && !empty($employee['username'])) {
            $employee['full_name'] = $employee['username'];
        } elseif (empty($employee['full_name'])) {
            $employee['full_name'] = "Employee #{$employee['id']}";
        }

        // Ensure other fields exist for compatibility
        $employee = EncryptionHelper::decryptModel($this->table, $employee);
        $employee['first_name'] = $employee['full_name'];
        $employee['last_name'] = '';
        $employee['username']  = $employee['username'] ?? $employee['employee_id'] ?? '';
        $employee['email']     = $employee['email'] ?? '';
        $employee['status']    = $employee['status'] ?? 'Active';
        $employee['last_login'] = $employee['last_login'] ?? null;

        // Build initials
        $parts = explode(' ', $employee['full_name']);
        $initials = '';
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper($part[0]);
            }
        }
        $employee['initials'] = substr($initials, 0, 2);

        return $employee;
    }

    /**
     * Get full name for an employee by ID
     */
    public function getFullName(string|int $id): string
    {
        $employee = $this->find($id);
        if (!$employee) {
            return "Employee #{$id}";
        }
        return $employee['full_name'] ?? "Employee #{$id}";
    }

    /**
     * Find multiple employees by IDs (solves N+1 problem)
     */
    public function findMultiple(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        
        $idList = implode(',', array_map('intval', $ids));
        try {
            // Use PostgREST 'in' operator: id=in.(1,2,3)
            $results = $this->db->select($this->table, ["id=in.({$idList})"]);
            return $this->normalizeEmployees($results);
        } catch (Throwable $e) {
            error_log('Employee::findMultiple() Error: ' . $e->getMessage());
            // Fallback: try users table
            try {
                $results = $this->db->select('users', ["id=in.({$idList})"]);
                return $this->normalizeEmployees($results);
            } catch (Throwable $e2) {
                return [];
            }
        }
    }
}
