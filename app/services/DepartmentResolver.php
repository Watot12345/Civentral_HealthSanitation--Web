<?php
// app/services/DepartmentResolver.php

namespace App\Services;

require_once __DIR__ . '/../Constants/Permissions.php';

use App\Constants\Permissions;

class DepartmentResolver
{
    private static ?DepartmentResolver $instance = null;

    public static function getInstance(): DepartmentResolver
    {
        if (self::$instance === null) {
            self::$instance = new DepartmentResolver();
        }
        return self::$instance;
    }

    /**
     * Resolve the primary department title dynamically for the logged-in user.
     */
    public function resolveDepartmentName(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        // 1. Return session department if explicitly populated
        $sessionDept = trim($_SESSION['department'] ?? $_SESSION['user']['department'] ?? '');
        if (!empty($sessionDept)) {
            return $sessionDept;
        }

        // 2. Map role_description to department via departmentRoleMap
        $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? $_SESSION['role'] ?? '');
        if (!empty($userRoleDesc)) {
            foreach (self::departmentRoleMap() as $dept => $roles) {
                foreach ($roles as $r) {
                    if (strcasecmp(trim($r), $userRoleDesc) === 0) {
                        return $dept;
                    }
                }
            }
        }

        $permService = PermissionService::getInstance();

        // 3. Resolve department based on granted permission clusters
        if ($permService->hasPermission(Permissions::ROLES_MANAGE) || $permService->hasPermission(Permissions::SETTINGS_MANAGE)) {
            return 'Administration';
        }

        if ($permService->hasAnyPermission([Permissions::PATIENTS_VIEW, Permissions::CONSULTATIONS_VIEW, Permissions::TRIAGE_VIEW, Permissions::PRESCRIPTIONS_VIEW])) {
            return 'Health Center Services';
        }

        if ($permService->hasAnyPermission([Permissions::PERMITS_VIEW, Permissions::INSPECTIONS_VIEW, Permissions::PERMITS_APPROVE])) {
            return 'Sanitation Permits';
        }

        if ($permService->hasAnyPermission([Permissions::IMMUNIZATION_VIEW, Permissions::IMMUNIZATION_CREATE])) {
            return 'Immunization & Nutrition';
        }

        if ($permService->hasAnyPermission([Permissions::WASTEWATER_VIEW, Permissions::WASTEWATER_CREATE, Permissions::WASTEWATER_EDIT, Permissions::WASTEWATER_MANAGE])) {
            return 'Wastewater Services';
        }

        return 'General Department';
    }

    /**
     * Resolve department slug for dynamic CSS/theme or route identifiers.
     */
    public function resolveDepartmentSlug(): string
    {
        $name = $this->resolveDepartmentName();
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }

    /**
     * Map departments to their corresponding position role titles.
     */
    public static function departmentRoleMap(): array
    {
        return [
            'Health Center Services' => [
                'Health Center Director', 'HCD', 'Doctor', 'Nurse', 'Dentist',
                'Laboratory Technician', 'Medical Records Clerk', 'Appointment Clerk',
                'Medical Practitioner', 'Health Center Staff'
            ],
            'Sanitation Permits' => [
                'Sanitation Director', 'SD', 'Inspector', 'Permit Clerk', 'Cashier',
                'Sanitation Officer'
            ],
            'Immunization & Nutrition' => [
                'Immunization Coordinator', 'IL', 'Midwife', 'Nutritionist', 'Nutrition Educator',
                'Immunization Lead', 'Nutrition Staff'
            ],
            'Wastewater Services' => [
                'Wastewater Officer', 'WL', 'Wastewater Lead'
            ],
            'Health Surveillance' => [
                'Surveillance Officer', 'SL', 'Surveillance Coordinator', 'Surveillance Lead', 'Surveillance Staff'
            ],
            'Administration' => [
                'System Administrator', 'System Admin', 'HSA'
            ]
        ];
    }

    /**
     * Normalize department name string to canonical identifier.
     */
    public function normalizeDepartmentName(string $d): string
    {
        $dLower = strtolower(trim($d));
        return match($dLower) {
            'health center', 'health center services' => 'health center services',
            'sanitation', 'sanitation permits' => 'sanitation permits',
            'immunization', 'nutrition', 'immunization & nutrition' => 'immunization & nutrition',
            'wastewater', 'wastewater services' => 'wastewater services',
            'surveillance', 'health surveillance' => 'health surveillance',
            'administration', 'admin' => 'administration',
            default => $dLower
        };
    }

    /**
     * Get primary role categories for a department (e.g. Medical Practitioner, Health Center Staff).
     */
    public function getPrimaryRoleCategoriesForDepartment(string $departmentName, bool $includeDirector = true): array
    {
        $norm = $this->normalizeDepartmentName($departmentName);
        
        $map = [
            'health center services'   => ['Medical Practitioner', 'Health Center Staff'],
            'sanitation permits'      => ['Sanitation Officer'],
            'immunization & nutrition' => ['Immunization Lead', 'Nutrition Staff'],
            'wastewater services'     => ['Wastewater Lead'],
            'health surveillance'     => ['Surveillance Lead', 'Surveillance Staff'],
            'administration'          => ['System Admin']
        ];

        $categories = $map[$norm] ?? ['Medical Practitioner', 'Health Center Staff', 'Sanitation Officer', 'Immunization Lead', 'Nutrition Staff', 'Wastewater Lead', 'Surveillance Lead'];

        if ($includeDirector) {
            $directorMap = [
                'health center services'   => 'Health Center Director',
                'sanitation permits'      => 'Sanitation Director',
                'immunization & nutrition' => 'Immunization Lead',
                'wastewater services'     => 'Wastewater Lead',
                'health surveillance'     => 'Surveillance Lead',
            ];
            if (isset($directorMap[$norm]) && !in_array($directorMap[$norm], $categories, true)) {
                array_unshift($categories, $directorMap[$norm]);
            }
        }

        return $categories;
    }

    /**
     * Check if a given role name belongs to a target department.
     */

    public function isRoleInDepartment(string $roleName, string $departmentName): bool
    {
        $map = self::departmentRoleMap();
        $normInput = $this->normalizeDepartmentName($departmentName);

        $targetRoles = [];
        foreach ($map as $deptKey => $rolesList) {
            if ($this->normalizeDepartmentName($deptKey) === $normInput) {
                $targetRoles = $rolesList;
                break;
            }
        }

        foreach ($targetRoles as $tr) {
            if (strcasecmp(trim($tr), trim($roleName)) === 0) {
                return true;
            }
        }
        return false;
    }


    /**
     * Filter user array to only include employees in the target department.
     */
    public function filterUsersForDepartment(array $users, string $departmentName): array
    {
        return array_values(array_filter($users, function($user) use ($departmentName) {
            $userDept = trim($user['department'] ?? '');
            $userRole = trim($user['role_description'] ?? $user['role'] ?? '');

            if (!empty($userDept) && strcasecmp($userDept, $departmentName) === 0) {
                return true;
            }
            return $this->isRoleInDepartment($userRole, $departmentName);
        }));
    }

    /**
     * Filter roles array to only include roles belonging to the target department.
     */
    public function filterRolesForDepartment(array $roles, string $departmentName): array
    {
        return array_values(array_filter($roles, function($role) use ($departmentName) {
            $roleName = trim($role['name'] ?? '');
            return $this->isRoleInDepartment($roleName, $departmentName);
        }));
    }

    /**
     * Get the current logged in user's department name.
     */
    public function getCurrentUserDepartment(): string
    {
        return $this->resolveDepartmentName();
    }

    /**
     * Check if the current logged-in user can access or manage data for the target department.
     * Admin Role: Full access to ALL departments.
     * Department Head / Staff: Full access WITHIN assigned department only.
     */
    public function canAccessDepartment(string $targetDepartment): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? '');
        $userRole     = trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? '');

        // 1. ADMIN ROLE: Full access to ALL departments
        if (PermissionService::getInstance()->isAdminRole($userRoleDesc) || PermissionService::getInstance()->isAdminRole($userRole)) {
            return true;
        }

        $currentDept = trim($this->getCurrentUserDepartment());
        $targetDept  = trim($targetDepartment);

        $normCurrent = $this->normalizeDepartmentName($currentDept);
        $normTarget  = $this->normalizeDepartmentName($targetDept);

        // 2. DEPARTMENT HEAD / STAFF: Full access WITHIN assigned department
        if (!empty($normCurrent) && $normCurrent === $normTarget) {
            return true;
        }

        // 3. Restrict access outside assigned department
        return false;
    }


}

