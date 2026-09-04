<?php
// app/services/PermissionService.php

namespace App\Services;

use Throwable;
use Role;
use ActivityLog;
use Settings;

class PermissionService
{
    private static ?PermissionService $instance = null;
    private array $allSlugs = [
        'dashboard.view', 'dashboard.system_admin', 'dashboard.health_center', 'dashboard.sanitation', 'dashboard.immunization', 'dashboard.wastewater', 'dashboard.surveillance',
        'analytics.view', 'analytics.health_center', 'analytics.sanitation', 'analytics.immunization', 'analytics.wastewater', 'analytics.surveillance',
        'reports.view', 'reports.health_center', 'reports.sanitation', 'reports.immunization', 'reports.wastewater', 'reports.surveillance',
        'reports.generate', 'reports.export', 'reports.template.use', 'reports.template.create', 'reports.template.edit', 'reports.template.delete', 'reports.all_departments', 'reports.all_facilities', 'reports.analytics',
        'compliance.view', 'compliance.admin_only',
        'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
        'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
        'prescriptions.view', 'prescriptions.create',
        'permits.view', 'permits.create', 'permits.approve', 'inspections.view', 'inspections.conduct',
        'immunization.view', 'immunization.create', 'immunization.edit',
        'wastewater.view', 'wastewater.create', 'wastewater.edit', 'wastewater.manage',
        'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
        'users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage', 'settings.manage', 'logs.view'
    ];

    public static function getInstance(): PermissionService
    {
        if (self::$instance === null) {
            self::$instance = new PermissionService();
        }
        return self::$instance;
    }

    public static function normalizeRoleTitle(string $role): string
    {
        $role = trim($role);
        if (strcasecmp($role, 'System Admin') === 0 || strcasecmp($role, 'HSA') === 0) return 'System Administrator';
        if (strcasecmp($role, 'HCD') === 0 || strcasecmp($role, 'Health Center Director') === 0) return 'Health Center Director';
        if (strcasecmp($role, 'SD') === 0 || strcasecmp($role, 'Sanitation Director') === 0) return 'Sanitation Director';
        if (strcasecmp($role, 'Immunization Lead') === 0 || strcasecmp($role, 'IL') === 0) return 'Immunization Coordinator';
        if (strcasecmp($role, 'Wastewater Lead') === 0 || strcasecmp($role, 'WL') === 0) return 'Wastewater Officer';
        if (strcasecmp($role, 'Surveillance Lead') === 0 || strcasecmp($role, 'SL') === 0) return 'Surveillance Coordinator';
        return $role;
    }

    /**
     * Explicit 19-position department matrix with all fields written out.
     */
    public static function departmentMatrix(): array
    {
        return [
            'System Administrator' => [
                'position'           => 'System Administrator',
                'department_scope'   => null,
                'dashboard_slug'     => 'dashboard.system_admin',
                'analytics_slug'     => 'analytics.view',
                'reports_slug'       => 'reports.view',
                'compliance_allowed' => true,
                'modules'            => ['patients', 'consultations', 'triage', 'prescriptions', 'permits', 'inspections', 'immunization', 'nutrition', 'wastewater', 'surveillance', 'users', 'roles', 'settings', 'logs']
            ],
            'System Admin' => [
                'position'           => 'System Administrator',
                'department_scope'   => null,
                'dashboard_slug'     => 'dashboard.system_admin',
                'analytics_slug'     => 'analytics.view',
                'reports_slug'       => 'reports.view',
                'compliance_allowed' => true,
                'modules'            => ['patients', 'consultations', 'triage', 'prescriptions', 'permits', 'inspections', 'immunization', 'nutrition', 'wastewater', 'surveillance', 'users', 'roles', 'settings', 'logs']
            ],
            'HCD' => [
                'position'           => 'Health Center Director',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients', 'consultations', 'triage', 'prescriptions', 'users']
            ],
            'Health Center Director' => [
                'position'           => 'Health Center Director',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients', 'consultations', 'triage', 'prescriptions', 'users']
            ],
            'Doctor' => [
                'position'           => 'Doctor',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients', 'consultations', 'triage', 'prescriptions']
            ],
            'Nurse' => [
                'position'           => 'Nurse',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients', 'consultations', 'triage', 'prescriptions']
            ],
            'Dentist' => [
                'position'           => 'Dentist',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients', 'consultations', 'prescriptions']
            ],
            'Laboratory Technician' => [
                'position'           => 'Laboratory Technician',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients', 'consultations', 'prescriptions']
            ],
            'Medical Records Clerk' => [
                'position'           => 'Medical Records Clerk',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients']
            ],
            'Appointment Clerk' => [
                'position'           => 'Appointment Clerk',
                'department_scope'   => 'Health Center',
                'department_slug'    => 'health_center',
                'dashboard_slug'     => 'dashboard.health_center',
                'analytics_slug'     => 'analytics.health_center',
                'reports_slug'       => 'reports.health_center',
                'compliance_allowed' => false,
                'modules'            => ['patients', 'triage']
            ],
            'Sanitation Director' => [
                'position'           => 'Sanitation Director',
                'department_scope'   => 'Sanitation',
                'department_slug'    => 'sanitation',
                'dashboard_slug'     => 'dashboard.sanitation',
                'analytics_slug'     => 'analytics.sanitation',
                'reports_slug'       => 'reports.sanitation',
                'compliance_allowed' => true,
                'modules'            => ['permits', 'inspections', 'users']
            ],
            'Inspector' => [
                'position'           => 'Inspector',
                'department_scope'   => 'Sanitation',
                'department_slug'    => 'sanitation',
                'dashboard_slug'     => 'dashboard.sanitation',
                'analytics_slug'     => 'analytics.sanitation',
                'reports_slug'       => 'reports.sanitation',
                'compliance_allowed' => false,
                'modules'            => ['permits', 'inspections']
            ],
            'Permit Clerk' => [
                'position'           => 'Permit Clerk',
                'department_scope'   => 'Sanitation',
                'department_slug'    => 'sanitation',
                'dashboard_slug'     => 'dashboard.sanitation',
                'analytics_slug'     => 'analytics.sanitation',
                'reports_slug'       => 'reports.sanitation',
                'compliance_allowed' => false,
                'modules'            => ['permits', 'inspections']
            ],
            'Cashier' => [
                'position'           => 'Cashier',
                'department_scope'   => 'Sanitation',
                'department_slug'    => 'sanitation',
                'dashboard_slug'     => 'dashboard.sanitation',
                'analytics_slug'     => 'analytics.sanitation',
                'reports_slug'       => 'reports.sanitation',
                'compliance_allowed' => false,
                'modules'            => ['permits']
            ],
            'Immunization Coordinator' => [
                'position'           => 'Immunization Coordinator',
                'department_scope'   => 'Immunization',
                'department_slug'    => 'immunization',
                'dashboard_slug'     => 'dashboard.immunization',
                'analytics_slug'     => 'analytics.immunization',
                'reports_slug'       => 'reports.immunization',
                'compliance_allowed' => false,
                'modules'            => ['immunization', 'nutrition', 'users']
            ],
            'Midwife' => [
                'position'           => 'Midwife',
                'department_scope'   => 'Immunization',
                'department_slug'    => 'immunization',
                'dashboard_slug'     => 'dashboard.immunization',
                'analytics_slug'     => 'analytics.immunization',
                'reports_slug'       => 'reports.immunization',
                'compliance_allowed' => false,
                'modules'            => ['immunization', 'nutrition']
            ],
            'Nutritionist' => [
                'position'           => 'Nutritionist',
                'department_scope'   => 'Immunization',
                'department_slug'    => 'immunization',
                'dashboard_slug'     => 'dashboard.immunization',
                'analytics_slug'     => 'analytics.immunization',
                'reports_slug'       => 'reports.immunization',
                'compliance_allowed' => false,
                'modules'            => ['nutrition']
            ],
            'Nutrition Educator' => [
                'position'           => 'Nutrition Educator',
                'department_scope'   => 'Immunization',
                'department_slug'    => 'immunization',
                'dashboard_slug'     => 'dashboard.immunization',
                'analytics_slug'     => 'analytics.immunization',
                'reports_slug'       => 'reports.immunization',
                'compliance_allowed' => false,
                'modules'            => ['nutrition']
            ],
            'Wastewater Officer' => [
                'position'           => 'Wastewater Officer',
                'department_scope'   => 'Wastewater',
                'department_slug'    => 'wastewater',
                'dashboard_slug'     => 'dashboard.wastewater',
                'analytics_slug'     => 'analytics.wastewater',
                'reports_slug'       => 'reports.wastewater',
                'compliance_allowed' => false,
                'modules'            => ['wastewater', 'users']
            ],
            'Wastewater Lead' => [
                'position'           => 'Wastewater Officer',
                'department_scope'   => 'Wastewater',
                'department_slug'    => 'wastewater',
                'dashboard_slug'     => 'dashboard.wastewater',
                'analytics_slug'     => 'analytics.wastewater',
                'reports_slug'       => 'reports.wastewater',
                'compliance_allowed' => false,
                'modules'            => ['wastewater', 'users']
            ],
            'Surveillance Officer' => [
                'position'           => 'Surveillance Officer',
                'department_scope'   => 'Health Surveillance',
                'department_slug'    => 'surveillance',
                'dashboard_slug'     => 'dashboard.surveillance',
                'analytics_slug'     => 'analytics.surveillance',
                'reports_slug'       => 'reports.surveillance',
                'compliance_allowed' => false,
                'modules'            => ['surveillance']
            ],
            'Surveillance Coordinator' => [
                'position'           => 'Surveillance Coordinator',
                'department_scope'   => 'Health Surveillance',
                'department_slug'    => 'surveillance',
                'dashboard_slug'     => 'dashboard.surveillance',
                'analytics_slug'     => 'analytics.surveillance',
                'reports_slug'       => 'reports.surveillance',
                'compliance_allowed' => false,
                'modules'            => ['surveillance', 'users']
            ],
        ];
    }

    /**
     * Single source of truth helper returning department scope, admin flag, allowed modules, and compliance eligibility.
     */
    public function getUserScope(?int $employeeId = null): array
    {
        if ($employeeId === null) {
            if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
                @session_start();
            }
            $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? '');
            $userRole = trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? $_SESSION['user_role'] ?? '');
        } else {
            try {
                $db = \Database::getInstance();
                $rows = $db->select('employees', ['id' => 'eq.' . $employeeId], ['select' => 'role,role_description']);
                $userRoleDesc = trim($rows[0]['role_description'] ?? '');
                $userRole = trim($rows[0]['role'] ?? '');
            } catch (\Throwable $e) {
                $userRoleDesc = '';
                $userRole = '';
            }
        }

        $pos = self::normalizeRoleTitle(!empty($userRoleDesc) ? $userRoleDesc : $userRole);
        $isAdmin = $this->isAdminRole($userRoleDesc) || $this->isAdminRole($userRole) || strcasecmp($pos, 'System Administrator') === 0;

        $matrix = self::departmentMatrix();
        $matched = null;
        foreach ($matrix as $mPos => $row) {
            if (strcasecmp($mPos, $pos) === 0 || strcasecmp($mPos, $userRoleDesc) === 0 || strcasecmp($mPos, $userRole) === 0) {
                $matched = $row;
                break;
            }
        }

        if ($isAdmin) {
            return [
                'department'      => null,
                'department_slug' => null,
                'department_name' => null,
                'is_admin'        => true,
                'modules'         => $matched['modules'] ?? ['patients', 'consultations', 'triage', 'prescriptions', 'permits', 'inspections', 'immunization', 'nutrition', 'wastewater', 'surveillance', 'users', 'roles', 'settings', 'logs'],
                'compliance'      => true,
            ];
        }

        if ($matched) {
            $slug = $matched['department_slug'] ?? strtolower(str_replace([' ', '&'], ['_', 'and'], $matched['department_scope'] ?? ''));
            $name = $matched['department_name'] ?? $matched['department_scope'] ?? 'Health Center';
            return [
                'department'      => $slug,
                'department_slug' => $slug,
                'department_name' => $name,
                'is_admin'        => false,
                'modules'         => $matched['modules'],
                'compliance'      => (bool)$matched['compliance_allowed'],
            ];
        }

        return [
            'department'      => null,
            'department_slug' => null,
            'department_name' => null,
            'is_admin'        => false,
            'modules'         => [],
            'compliance'      => false,
        ];
    }

    /**
     * Get all granted permissions for the current user session with caching.
     */
    public function getGrantedPermissions(): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $userRoleDesc = trim($_SESSION['role_description'] ?? '');
        $userRole = trim($_SESSION['role'] ?? 'employee');
        $currentSessionRoleKey = $userRoleDesc . ':' . $userRole . ':v7';

        // Return session cache if populated for current active role
        if (isset($_SESSION['granted_permission_slugs_key']) 
            && $_SESSION['granted_permission_slugs_key'] === $currentSessionRoleKey 
            && isset($_SESSION['granted_permission_slugs']) 
            && is_array($_SESSION['granted_permission_slugs'])) {
            return $_SESSION['granted_permission_slugs'];
        }

        // Admin bypass
        if ($this->isAdminRole($userRoleDesc) || $this->isAdminRole($userRole)) {
            $_SESSION['granted_permission_slugs_key'] = $currentSessionRoleKey;
            $_SESSION['granted_permission_slugs'] = $this->allSlugs;
            return $this->allSlugs;
        }

        try {
            require_once __DIR__ . '/../Models/Role.php';
            require_once __DIR__ . '/../Models/ActivityLog.php';
            $roleModel = new Role();
            $roles = $roleModel->all();

            $normDesc = self::normalizeRoleTitle($userRoleDesc);
            $normRole = self::normalizeRoleTitle($userRole);

            $matchedRole = null;
            foreach ($roles as $r) {
                $rName = trim($r['name'] ?? '');
                $rNorm = self::normalizeRoleTitle($rName);
                if (($userRoleDesc !== '' && (strcasecmp($rName, $userRoleDesc) === 0 || strcasecmp($rNorm, $normDesc) === 0))
                    || strcasecmp($rName, $userRole) === 0 
                    || strcasecmp($rNorm, $normRole) === 0) {
                    $matchedRole = $r;
                    break;
                }
            }

            $grantedSlugs = [];
            if ($matchedRole && !empty($matchedRole['permissions'])) {
                foreach ($matchedRole['permissions'] as $p) {
                    if (!empty($p['granted']) && !empty($p['slug'])) {
                        $grantedSlugs[] = $p['slug'];
                    }
                }
            }
            
            if (empty($grantedSlugs)) {
                // Baseline matrix defaults if role not found in database or has no permissions configured
                $matrix = self::defaultRolePermissionMatrix();
                foreach ($matrix as $rName => $slugs) {
                    $rNorm = self::normalizeRoleTitle($rName);
                    if (($userRoleDesc !== '' && (strcasecmp(trim($rName), $userRoleDesc) === 0 || strcasecmp($rNorm, $normDesc) === 0))
                        || strcasecmp(trim($rName), $userRole) === 0
                        || strcasecmp($rNorm, $normRole) === 0) {
                        $grantedSlugs = $slugs;
                        break;
                    }
                }
            }

            // Department Heads RBAC Guarantee: They manage their own department staff inside User Management
            $isDeptHead = \ActivityLog::isDepartmentHeadRole($userRoleDesc) || \ActivityLog::isDepartmentHeadRole($userRole);
            if ($isDeptHead) {
                $headBase = ['users.view', 'users.create', 'users.edit', 'dashboard.view'];
                $grantedSlugs = array_merge($grantedSlugs, $headBase);

                // Add module dashboard slug
                $checkRoleStr = strtolower($userRoleDesc . ' ' . $userRole);
                if (str_contains($checkRoleStr, 'health center') || str_contains($checkRoleStr, 'medical')) {
                    $grantedSlugs[] = 'dashboard.health_center';
                } elseif (str_contains($checkRoleStr, 'sanitation')) {
                    $grantedSlugs[] = 'dashboard.sanitation';
                } elseif (str_contains($checkRoleStr, 'immunization') || str_contains($checkRoleStr, 'nutrition')) {
                    $grantedSlugs[] = 'dashboard.immunization';
                } elseif (str_contains($checkRoleStr, 'waste')) {
                    $grantedSlugs[] = 'dashboard.wastewater';
                } elseif (str_contains($checkRoleStr, 'surveillance')) {
                    $grantedSlugs[] = 'dashboard.surveillance';
                }
            }

            $grantedSlugs = array_values(array_unique($grantedSlugs));

            $_SESSION['granted_permission_slugs_key'] = $currentSessionRoleKey;
            $_SESSION['granted_permission_slugs'] = $grantedSlugs;
            return $grantedSlugs;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Official 19 System Roles default permission matrix mapping.
     */
    public static function defaultRolePermissionMatrix(): array
    {
        return [
            'System Administrator' => [
                'dashboard.view', 'dashboard.system_admin',
                'analytics.view', 'reports.view', 'compliance.view', 'compliance.admin_only',
                'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
                'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
                'prescriptions.view', 'prescriptions.create',
                'permits.view', 'permits.create', 'permits.approve', 'inspections.view', 'inspections.conduct',
                'immunization.view', 'immunization.create', 'immunization.edit',
                'wastewater.view', 'wastewater.create', 'wastewater.edit', 'wastewater.manage',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage', 'settings.manage', 'logs.view'
            ],
            'System Admin' => [
                'dashboard.view', 'dashboard.system_admin',
                'analytics.view', 'reports.view', 'compliance.view', 'compliance.admin_only',
                'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
                'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
                'prescriptions.view', 'prescriptions.create',
                'permits.view', 'permits.create', 'permits.approve', 'inspections.view', 'inspections.conduct',
                'immunization.view', 'immunization.create', 'immunization.edit',
                'wastewater.view', 'wastewater.create', 'wastewater.edit', 'wastewater.manage',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage', 'settings.manage', 'logs.view'
            ],
            'Health Center Director' => [
                'dashboard.view', 'dashboard.health_center',
                'analytics.view', 'analytics.health_center', 'reports.view', 'reports.health_center',
                'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
                'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
                'prescriptions.view', 'prescriptions.create',
                'users.view', 'users.create', 'users.edit'
            ],
            'Doctor' => [
                'dashboard.view', 'dashboard.health_center', 'reports.view', 'reports.health_center',
                'patients.view',
                'consultations.view', 'consultations.create', 'triage.view',
                'prescriptions.view', 'prescriptions.create'
            ],
            'Nurse' => [
                'dashboard.view', 'dashboard.health_center', 'reports.view', 'reports.health_center',
                'patients.view', 'patients.create', 'patients.edit',
                'triage.view', 'triage.create', 'consultations.view',
                'prescriptions.view'
            ],
            'Dentist' => [
                'dashboard.view', 'dashboard.health_center', 'reports.view', 'reports.health_center',
                'patients.view',
                'consultations.view', 'consultations.create',
                'prescriptions.view', 'prescriptions.create'
            ],
            'Laboratory Technician' => [
                'dashboard.view', 'dashboard.health_center',
                'patients.view', 'consultations.view', 'prescriptions.view'
            ],
            'Medical Records Clerk' => [
                'dashboard.view', 'dashboard.health_center', 'reports.view', 'reports.health_center',
                'patients.view', 'patients.create', 'patients.edit'
            ],
            'Appointment Clerk' => [
                'dashboard.view', 'dashboard.health_center',
                'patients.view', 'patients.create', 'triage.view'
            ],
            'Sanitation Director' => [
                'dashboard.view', 'dashboard.sanitation',
                'analytics.view', 'analytics.sanitation', 'reports.view', 'reports.sanitation', 'compliance.view',
                'permits.view', 'permits.create', 'permits.approve',
                'inspections.view', 'inspections.conduct',
                'wastewater.view', 'wastewater.create', 'wastewater.edit',
                'users.view', 'users.create', 'users.edit'
            ],
            'Inspector' => [
                'dashboard.view', 'dashboard.sanitation',
                'reports.view', 'reports.sanitation', 'permits.view', 'inspections.view', 'inspections.conduct', 'wastewater.view'
            ],
            'Permit Clerk' => [
                'dashboard.view', 'dashboard.sanitation',
                'reports.view', 'reports.sanitation', 'permits.view', 'permits.create', 'inspections.view'
            ],
            'Cashier' => [
                'dashboard.view', 'dashboard.sanitation', 'permits.view'
            ],
            'Immunization Coordinator' => [
                'dashboard.view', 'dashboard.immunization',
                'analytics.view', 'analytics.immunization', 'reports.view', 'reports.immunization',
                'immunization.view', 'immunization.create', 'immunization.edit', 'patients.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Midwife' => [
                'dashboard.view', 'dashboard.immunization', 'reports.view', 'reports.immunization',
                'immunization.view', 'immunization.create',
                'patients.view', 'patients.create', 'triage.create'
            ],
            'Nutritionist' => [
                'dashboard.view', 'dashboard.immunization',
                'analytics.view', 'analytics.immunization', 'reports.view', 'reports.immunization',
                'immunization.view', 'immunization.create', 'immunization.edit', 'patients.view'
            ],
            'Nutrition Educator' => [
                'dashboard.view', 'dashboard.immunization',
                'reports.view', 'reports.immunization', 'immunization.view', 'immunization.create'
            ],
            'Wastewater Officer' => [
                'dashboard.view', 'dashboard.wastewater',
                'analytics.view', 'analytics.wastewater', 'reports.view', 'reports.wastewater',
                'wastewater.view', 'wastewater.create', 'wastewater.edit', 'wastewater.manage',
                'inspections.view', 'inspections.conduct', 'permits.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Wastewater Lead' => [
                'dashboard.view', 'dashboard.wastewater',
                'analytics.view', 'analytics.wastewater', 'reports.view', 'reports.wastewater',
                'wastewater.view', 'wastewater.create', 'wastewater.edit', 'wastewater.manage',
                'inspections.view', 'inspections.conduct', 'permits.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Surveillance Officer' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'analytics.surveillance', 'reports.view', 'reports.surveillance',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'patients.view', 'consultations.view', 'inspections.view'
            ],
            'Surveillance Coordinator' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'analytics.surveillance', 'reports.view', 'reports.surveillance',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'patients.view', 'consultations.view', 'inspections.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Surveillance Lead' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'analytics.surveillance', 'reports.view', 'reports.surveillance',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'patients.view', 'consultations.view', 'inspections.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Epidemiologist' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'analytics.surveillance', 'reports.view', 'reports.surveillance',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'patients.view', 'consultations.view', 'inspections.view'
            ]
        ];
    }

    /**
     * Check if current user has a specific permission slug (evaluating feature flags if enabled).
     */
    public function hasPermission(string $slug): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $userRoleDesc = trim($_SESSION['role_description'] ?? '');
        $userRole = trim($_SESSION['role'] ?? '');
        if ($this->isAdminRole($userRoleDesc) || $this->isAdminRole($userRole)) {
            return true;
        }

        // Feature flag check integration if defined in Settings
        if (class_exists('Settings')) {
            try {
                $featureKey = "feature.{$slug}";
                $featureEnabled = Settings::get($featureKey, true);
                if ($featureEnabled === false) {
                    return false;
                }
            } catch (Throwable $e) {}
        }

        $granted = $this->getGrantedPermissions();
        if ($slug === 'view_ai_analytics' && (in_array('analytics.view', $granted, true) || in_array('view_ai_analytics', $granted, true))) {
            return true;
        }

        // Dynamic capability mappings for report permissions
        if ($slug === 'reports.generate' || $slug === 'reports.export' || $slug === 'reports.template.use') {
            return in_array('reports.view', $granted, true) || in_array($slug, $granted, true);
        }
        if ($slug === 'reports.all_departments' || $slug === 'reports.all_facilities') {
            return in_array($slug, $granted, true); // Admin already bypassed above; non-admin only if explicit
        }
        if ($slug === 'reports.analytics') {
            return in_array('analytics.view', $granted, true) || in_array('reports.analytics', $granted, true);
        }
        if ($slug === 'reports.template.create') {
            return $this->isHeadOrAdminRole($userRoleDesc) || $this->isHeadOrAdminRole($userRole) || in_array('reports.template.create', $granted, true);
        }
        if ($slug === 'reports.template.edit' || $slug === 'reports.template.delete') {
            return in_array($slug, $granted, true);
        }

        return in_array($slug, $granted, true);
    }

    /**
     * Check if user has ANY of the given permission slugs.
     */
    public function hasAnyPermission(array $slugs): bool
    {
        if (empty($slugs)) {
            return true;
        }
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has ALL of the given permission slugs.
     */
    public function hasAllPermissions(array $slugs): bool
    {
        if (empty($slugs)) {
            return true;
        }
        foreach ($slugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Invalidate session permission cache for current user or session.
     */
    public function invalidateCache(?int $userId = null): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        unset($_SESSION['granted_permission_slugs'], $_SESSION['granted_permission_slugs_key']);
    }

    /**
     * Audit log unauthorized access attempts.
     */
    public function logUnauthorizedAttempt(string $slug, string $context = ''): void
    {
        if (class_exists('ActivityLog')) {
            try {
                $logger = new ActivityLog();
                $logger->log('Unauthorized Access Attempt', [
                    'module'  => 'RBAC Security Guard',
                    'details' => "Attempted access to permission [{$slug}] " . ($context ? "in {$context}" : "via URL: " . ($_SERVER['REQUEST_URI'] ?? '')),
                    'status'  => 'Failed'
                ]);
            } catch (Throwable $e) {}
        }
    }

    /**
     * Helper to check if role is system admin.
     */
    public function isAdminRole(string $role): bool
    {
        $r = strtolower(trim($role));
        return $r === 'system administrator' 
            || $r === 'system admin' 
            || $r === 'admin'
            || $r === 'administrator'
            || $r === 'sysadmin'
            || $r === 'super admin'
            || $r === 'lgu admin'
            || str_contains($r, 'system admin')
            || str_contains($r, 'administrator');
    }

    /**
     * Helper to check if role is a Department Head, Director, Supervisor, or Admin.
     */
    public function isHeadOrAdminRole(string $role): bool
    {
        if ($this->isAdminRole($role)) {
            return true;
        }
        $r = strtolower(trim($role));
        return str_contains($r, 'director')
            || str_contains($r, 'head')
            || str_contains($r, 'supervisor')
            || str_contains($r, 'officer-in-charge')
            || str_contains($r, 'oic')
            || str_contains($r, 'chief')
            || str_contains($r, 'lead')
            || str_contains($r, 'manager');
    }
}
