<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Employee;
use App\Models\Role;
use App\Repositories\ActivityLogRepository;
use App\Repositories\EmployeeRepository;
use App\Repositories\RoleRepository;
use App\Services\ActivityLogService;
use App\Services\EmployeeService;
use App\Services\RoleService;
use Core\Csrf;
use Core\Request;

/**
 * UserManagementPageController
 *
 * Not part of the REST API — this renders the server-side HTML page
 * (views/user_management.php). It gathers every value the view needs
 * up front so the view itself stays pure presentation: no SQL, no
 * business logic, no validation, per spec.
 */
final class UserManagementPageController
{
    public function render(): void
    {
        $employeeRepo = new EmployeeRepository();
        $roleRepo = new RoleRepository();
        $activityLogService = new ActivityLogService(new ActivityLogRepository());

        $employeeService = new EmployeeService($employeeRepo, $activityLogService);
        $roleService = new RoleService($roleRepo, $activityLogService);

        $employees = $employeeService->list();
        $roles = $roleService->list();
        $statistics = $employeeService->statistics();
        $recentActivity = $activityLogService->list(null, 1, 20);

        $title = 'User Management';
        $csrfToken = Csrf::token();

        // Everything the view touches is passed explicitly rather than
        // relying on ambient scope, so the view's data contract is explicit.
        $viewData = [
            'title'          => $title,
            'csrfToken'      => $csrfToken,
            'employees'      => array_map(fn (Employee $e) => $e->toArray(), $employees),
            'roles'          => array_map(fn (Role $r) => $r->toArray(), $roles),
            'statistics'     => $statistics,
            'activityLogs'   => array_map(fn ($log) => $log->toArray(), $recentActivity['data']),
            'activityTotal'  => $recentActivity['total'],
        ];

        extract($viewData, EXTR_SKIP);
        require __DIR__ . '/../../views/user_management.php';
    }
}