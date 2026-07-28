<?php
// app/Controllers/EmployeeController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/ActivityLog.php';

class EmployeeController extends BaseController
{
    private Employee $employeeModel;
    private ActivityLog $activityLog;
    
    public function __construct()
    {
        $this->employeeModel = new Employee();
        $this->activityLog = new ActivityLog();
    }
    
    public function index(): void
    {
        $this->handle(function() {
            $employees = $this->employeeModel->all(['order' => 'id.asc']);
            
            return [
                'success' => true,
                'data' => $employees,
                'total' => count($employees)
            ];
        });
    }
    
    public function show(string $id): void
    {
        $this->handle(function() use ($id) {
            $employee = $this->employeeModel->find($id);
            
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Employee not found',
                    'code' => 404
                ];
            }
            
            return [
                'success' => true,
                'data' => $employee
            ];
        });
    }

    public function store(): void
    {
        $data = $this->input();

        $this->handle(function () use ($data) {
            // Validate required fields
            if (empty($data['full_name'])) {
                return ['success' => false, 'message' => 'Full name is required', 'code' => 400];
            }
            if (empty($data['username'])) {
                return ['success' => false, 'message' => 'Username is required', 'code' => 400];
            }
            if (empty($data['email'])) {
                return ['success' => false, 'message' => 'Email is required', 'code' => 400];
            }
            if (empty($data['password'])) {
                return ['success' => false, 'message' => 'Password is required', 'code' => 400];
            }
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters', 'code' => 400];
            }

            // Build database row
            $dbData = [
                'full_name'   => $data['full_name'],
                'employee_id' => $data['username'],  // employee_id = username
                'username'    => $data['username'],
                'email'       => $data['email'],
                'password'    => password_hash($data['password'], PASSWORD_DEFAULT),
                'department'  => $data['department'] ?? null,
                'status'      => $data['status'] ?? 'Active',
            ];

            // Map role_id to role name if provided
            if (!empty($data['role_id'])) {
                require_once __DIR__ . '/../Models/Role.php';
                $roleModel = new Role();
                $role = $roleModel->find((int) $data['role_id']);
                if ($role) {
                    $dbData['role'] = $role['name'];
                    $dbData['role_description'] = $role['description'] ?? '';
                }
            }

            $result = $this->employeeModel->create($dbData);

            // Log activity
            $this->activityLog->log('User Created', [
                'details' => "Created user: {$data['full_name']} ({$data['email']})",
                'module'  => 'User Management',
            ]);

            return [
                'success' => true,
                'message' => 'User registered successfully',
                'data'    => $result,
                'code'    => 201,
            ];
        });
    }

    public function update(string $id): void
    {
        $data = $this->input();

        $this->handle(function () use ($id, $data) {
            $existing = $this->employeeModel->find($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Employee not found', 'code' => 404];
            }

            $dbData = [];
            if (isset($data['full_name']))   $dbData['full_name']   = $data['full_name'];
            if (isset($data['username'])) {
                $dbData['username']    = $data['username'];
                $dbData['employee_id'] = $data['username'];
            }
            if (isset($data['email']))       $dbData['email']       = $data['email'];
            if (isset($data['department']))  $dbData['department']  = $data['department'];
            if (isset($data['status']))      $dbData['status']      = $data['status'];

            // Map role_id to role name
            if (!empty($data['role_id'])) {
                require_once __DIR__ . '/../Models/Role.php';
                $roleModel = new Role();
                $role = $roleModel->find((int) $data['role_id']);
                if ($role) {
                    $dbData['role'] = $role['name'];
                    $dbData['role_description'] = $role['description'] ?? '';
                }
            }

            if (!empty($dbData)) {
                $this->employeeModel->updateById($id, $dbData);
            }

            // Log activity
            $this->activityLog->log('User Updated', [
                'details' => "Updated user: {$existing['full_name']} (ID #{$id})",
                'module'  => 'User Management',
            ]);

            return [
                'success' => true,
                'message' => 'User updated successfully',
                'data'    => $this->employeeModel->find($id),
            ];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->employeeModel->find($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Employee not found', 'code' => 404];
            }

            $this->employeeModel->deleteById($id);

            // Log activity
            $this->activityLog->log('User Deleted', [
                'details' => "Deleted user: {$existing['full_name']} (ID #{$id})",
                'module'  => 'User Management',
            ]);

            return [
                'success' => true,
                'message' => 'User deleted successfully',
            ];
        });
    }

    public function toggleStatus(string $id): void
    {
        $this->handle(function () use ($id) {
            $result = $this->employeeModel->toggleStatus($id);
            if ($result === null) {
                return ['success' => false, 'message' => 'Employee not found', 'code' => 404];
            }

            // Log activity
            $this->activityLog->log('User Status Changed', [
                'details' => "Toggled status for user ID #{$id} to {$result['status']}",
                'module'  => 'User Management',
            ]);

            return [
                'success' => true,
                'message' => 'Status updated to ' . $result['status'],
                'data'    => $result,
            ];
        });
    }

    public function statistics(): void
    {
        $this->handle(function () {
            return [
                'success' => true,
                'data'    => $this->employeeModel->getStatistics(),
            ];
        });
    }
    
    public function search(): void
    {
        $query = $_GET['q'] ?? '';
        
        $this->handle(function() use ($query) {
            if (empty($query)) {
                return [
                    'success' => false,
                    'message' => 'Search query is required',
                    'code' => 400
                ];
            }
            
            $all = $this->employeeModel->all();
            $query = strtolower($query);
            
            $results = array_values(array_filter($all, function($e) use ($query) {
                return str_contains(strtolower($e['full_name'] ?? ''), $query) ||
                       str_contains(strtolower($e['username'] ?? ''), $query) ||
                       str_contains(strtolower($e['email'] ?? ''), $query);
            }));
            
            return [
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ];
        });
    }
}