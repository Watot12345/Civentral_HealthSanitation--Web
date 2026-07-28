<?php
// app/Models/Employee.php

require_once __DIR__ . '/../../config/database.php';

class Employee
{
    private Database $db;
    private string $table = 'employees';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    public function all(array $options = []): array
    {
        try {
            $employees = $this->db->select($this->table, [], $options);
            return $this->normalizeEmployees($employees);
        } catch (Throwable $e) {
            // Fallback to users table if employees table is not directly accessible or missing
            try {
                $users = $this->db->select('users', [], $options);
                return $this->normalizeEmployees($users);
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
        $employee['first_name'] = $employee['full_name'];
        $employee['last_name'] = '';
        
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
        
        try {
            // Use PostgREST 'in' operator: id=in.(1,2,3)
            $idList = implode(',', array_map('intval', $ids));
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
