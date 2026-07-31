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
        'dashboard.view', 'analytics.view', 'reports.view', 'compliance.view',
        'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
        'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
        'prescriptions.view', 'prescriptions.create',
        'permits.view', 'permits.create', 'permits.approve', 'inspections.view', 'inspections.conduct',
        'immunization.view', 'immunization.create', 'immunization.edit',
        'users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage', 'settings.manage', 'logs.view'
    ];

    public static function getInstance(): PermissionService
    {
        if (self::$instance === null) {
            self::$instance = new PermissionService();
        }
        return self::$instance;
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
        $currentSessionRoleKey = $userRoleDesc . ':' . $userRole . ':v4';

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
            $roleModel = new Role();
            $roles = $roleModel->all();

            $matchedRole = null;
            foreach ($roles as $r) {
                $rName = trim($r['name']);
                if (($userRoleDesc !== '' && strcasecmp($rName, $userRoleDesc) === 0) || strcasecmp($rName, $userRole) === 0) {
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

            // Always merge baseline matrix defaults for the role
            $matrixDefaults = [];
            $matrix = self::defaultRolePermissionMatrix();
            foreach ($matrix as $rName => $slugs) {
                if (($userRoleDesc !== '' && strcasecmp(trim($rName), $userRoleDesc) === 0) || strcasecmp(trim($rName), $userRole) === 0) {
                    $matrixDefaults = $slugs;
                    break;
                }
            }

            $grantedSlugs = array_values(array_unique(array_merge($grantedSlugs, $matrixDefaults)));

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
                'analytics.view', 'reports.view', 'compliance.view',
                'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
                'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
                'prescriptions.view', 'prescriptions.create',
                'permits.view', 'permits.create', 'permits.approve', 'inspections.view', 'inspections.conduct',
                'immunization.view', 'immunization.create', 'immunization.edit',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'roles.manage', 'settings.manage', 'logs.view'
            ],
            'Health Center Director' => [
                'dashboard.view', 'dashboard.health_center',
                'analytics.view', 'reports.view', 'compliance.view',
                'patients.view', 'patients.create', 'patients.edit', 'patients.delete',
                'consultations.view', 'consultations.create', 'triage.view', 'triage.create',
                'prescriptions.view', 'prescriptions.create',
                'users.view', 'users.create', 'users.edit'
            ],
            'Doctor' => [
                'dashboard.view', 'dashboard.health_center', 'reports.view',
                'patients.view', 'patients.create', 'patients.edit',
                'consultations.view', 'consultations.create', 'triage.view',
                'prescriptions.view', 'prescriptions.create'
            ],
            'Nurse' => [
                'dashboard.view', 'dashboard.health_center', 'reports.view',
                'patients.view', 'patients.create', 'patients.edit',
                'triage.view', 'triage.create', 'consultations.view',
                'prescriptions.view'
            ],
            'Dentist' => [
                'dashboard.view', 'dashboard.health_center', 'reports.view',
                'patients.view', 'patients.create', 'patients.edit',
                'consultations.view', 'consultations.create',
                'prescriptions.view', 'prescriptions.create'
            ],
            'Laboratory Technician' => [
                'dashboard.view', 'dashboard.health_center',
                'patients.view', 'consultations.view', 'prescriptions.view'
            ],
            'Medical Records Clerk' => [
                'dashboard.view', 'dashboard.health_center',
                'patients.view', 'patients.create', 'patients.edit'
            ],
            'Appointment Clerk' => [
                'dashboard.view', 'dashboard.health_center',
                'patients.view', 'patients.create', 'triage.view'
            ],
            'Sanitation Director' => [
                'dashboard.view', 'dashboard.sanitation',
                'analytics.view', 'reports.view', 'compliance.view',
                'permits.view', 'permits.create', 'permits.approve',
                'inspections.view', 'inspections.conduct',
                'users.view', 'users.create', 'users.edit'
            ],
            'Inspector' => [
                'dashboard.view', 'dashboard.sanitation',
                'reports.view', 'permits.view', 'inspections.view', 'inspections.conduct'
            ],
            'Permit Clerk' => [
                'dashboard.view', 'dashboard.sanitation',
                'permits.view', 'permits.create', 'inspections.view'
            ],
            'Cashier' => [
                'dashboard.view', 'dashboard.sanitation', 'permits.view'
            ],
            'Immunization Coordinator' => [
                'dashboard.view', 'dashboard.immunization',
                'analytics.view', 'reports.view',
                'immunization.view', 'immunization.create', 'immunization.edit', 'patients.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Midwife' => [
                'dashboard.view', 'dashboard.immunization', 'reports.view',
                'immunization.view', 'immunization.create',
                'patients.view', 'patients.create', 'triage.create'
            ],
            'Nutritionist' => [
                'dashboard.view', 'dashboard.immunization',
                'analytics.view', 'reports.view',
                'immunization.view', 'immunization.create', 'immunization.edit', 'patients.view'
            ],
            'Nutrition Educator' => [
                'dashboard.view', 'dashboard.immunization',
                'reports.view', 'immunization.view', 'immunization.create'
            ],
            'Wastewater Officer' => [
                'dashboard.view', 'dashboard.sanitation',
                'analytics.view', 'reports.view',
                'inspections.view', 'inspections.conduct', 'permits.view'
            ],
            'Surveillance Officer' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'reports.view', 'compliance.view',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'patients.view', 'consultations.view', 'inspections.view'
            ],
            'Surveillance Coordinator' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'reports.view', 'compliance.view',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'patients.view', 'consultations.view', 'inspections.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Surveillance Lead' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'reports.view', 'compliance.view',
                'surveillance.view', 'surveillance.create', 'surveillance.edit', 'surveillance.manage',
                'patients.view', 'consultations.view', 'inspections.view',
                'users.view', 'users.create', 'users.edit'
            ],
            'Epidemiologist' => [
                'dashboard.view', 'dashboard.surveillance',
                'analytics.view', 'reports.view', 'compliance.view',
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
        return strcasecmp($role, 'System Administrator') === 0 
            || strcasecmp($role, 'System Admin') === 0 
            || strcasecmp($role, 'admin') === 0;
    }
}
