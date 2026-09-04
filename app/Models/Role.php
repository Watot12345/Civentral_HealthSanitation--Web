<?php
// app/Models/Role.php

require_once __DIR__ . '/../../config/database.php';

class Role
{
    private Database $db;
    private string $table = 'roles';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public static function primaryRoleNames(): array
    {
        return [
            'System Administrator',
            'Health Center Director',
            'Doctor',
            'Nurse',
            'Dentist',
            'Laboratory Technician',
            'Medical Records Clerk',
            'Appointment Clerk',
            'Sanitation Director',
            'Inspector',
            'Permit Clerk',
            'Cashier',
            'Immunization Coordinator',
            'Midwife',
            'Nutritionist',
            'Nutrition Educator',
            'Wastewater Officer',
            'Surveillance Officer',
            'Surveillance Coordinator',
        ];
    }

    private static ?array $cachedRoles = null;
    private static ?int $cacheTime = null;

    public function all(array $options = [], ?array $existingUsers = null): array
    {
        if (empty($options) && $existingUsers === null && self::$cachedRoles !== null && self::$cacheTime !== null && (time() - self::$cacheTime < 120)) {
            return self::$cachedRoles;
        }

        $rawRoles = $this->db->select($this->table, [], $options);
        $primaryNames = self::primaryRoleNames();

        // Filter or build the exact 19 Roles list
        $roles = [];
        $seenNames = [];

        foreach ($rawRoles as $r) {
            $rName = trim($r['name'] ?? '');
            if (!empty($rName) && !isset($seenNames[$rName])) {
                $roles[] = $r;
                $seenNames[$rName] = true;
            }
        }

        // Color palette lookup
        $defaultColors = [
            'System Administrator'    => 'bg-red-100 text-red-700',
            'System Admin'             => 'bg-red-100 text-red-700',
            'Health Center Director'   => 'bg-blue-100 text-blue-700',
            'Doctor'                   => 'bg-cyan-100 text-cyan-700',
            'Nurse'                    => 'bg-cyan-100 text-cyan-700',
            'Dentist'                  => 'bg-cyan-100 text-cyan-700',
            'Laboratory Technician'    => 'bg-cyan-100 text-cyan-700',
            'Medical Records Clerk'    => 'bg-sky-100 text-sky-700',
            'Appointment Clerk'        => 'bg-sky-100 text-sky-700',
            'Sanitation Director'      => 'bg-amber-100 text-amber-700',
            'Inspector'                => 'bg-yellow-100 text-yellow-700',
            'Permit Clerk'             => 'bg-yellow-100 text-yellow-700',
            'Cashier'                  => 'bg-yellow-100 text-yellow-700',
            'Immunization Coordinator' => 'bg-emerald-100 text-emerald-700',
            'Midwife'                  => 'bg-emerald-100 text-emerald-700',
            'Nutritionist'             => 'bg-teal-100 text-teal-700',
            'Nutrition Educator'       => 'bg-teal-100 text-teal-700',
            'Wastewater Officer'       => 'bg-purple-100 text-purple-700',
            'Surveillance Officer'     => 'bg-indigo-100 text-indigo-700',
            'Surveillance Coordinator' => 'bg-indigo-100 text-indigo-700',
        ];

        $synthId = 100;
        foreach ($primaryNames as $pName) {
            if (!isset($seenNames[$pName])) {
                $roles[] = [
                    'id'          => $synthId++,
                    'name'        => $pName,
                    'slug'        => strtolower(str_replace(' ', '-', $pName)),
                    'description' => $pName,
                    'color'       => $defaultColors[$pName] ?? 'bg-slate-100 text-slate-700',
                    'user_count'  => 0,
                    'permissions' => [],
                ];
            }
        }

        // Fetch permissions and role_permissions for granted mapping
        try {
            $allPermissions = $this->db->select('permissions', [], ['order' => 'module.asc,id.asc']);
        } catch (Throwable $e) {
            $allPermissions = [];
        }

        try {
            $allRolePermissions = $this->db->select('role_permissions', [], ['select' => 'role_id,permission_id']);
        } catch (Throwable $e) {
            $allRolePermissions = [];
        }

        $grantedByRole = [];
        foreach ($allRolePermissions as $rp) {
            $rId = (int) ($rp['role_id'] ?? 0);
            $pId = (int) ($rp['permission_id'] ?? 0);
            if ($rId && $pId) {
                $grantedByRole[$rId][] = $pId;
            }
        }

        // User count per role (matches role_description or role)
        $userCountByRole = [];
        $usersToCount = $existingUsers !== null ? $existingUsers : [];
        if (empty($usersToCount)) {
            try {
                $usersToCount = $this->db->select('employees', []);
            } catch (Throwable $e) {
                $usersToCount = [];
            }
        }

        foreach ($usersToCount as $u) {
            $uRoleId = (int) ($u['role_id'] ?? 0);
            $uDesc   = trim($u['role_description'] ?? '');
            $uRole   = trim($u['role'] ?? '');

            foreach ($roles as $r) {
                $rId   = (int) $r['id'];
                $rName = trim($r['name'] ?? '');

                if (($uRoleId > 0 && $uRoleId === $rId) || ($rName !== '' && (strcasecmp($uDesc, $rName) === 0 || strcasecmp($uRole, $rName) === 0))) {
                    $userCountByRole[$rId] = ($userCountByRole[$rId] ?? 0) + 1;
                    break;
                }
            }
        }

        $defaultMatrix = class_exists('\App\Services\PermissionService') 
            ? \App\Services\PermissionService::defaultRolePermissionMatrix() 
            : [];

        // Merge in memory
        foreach ($roles as &$role) {
            $roleId = (int) $role['id'];
            $roleName = trim($role['name'] ?? '');
            $grantedIds = $grantedByRole[$roleId] ?? [];
            $hasDbCustom = !empty($grantedIds);

            $defaultSlugs = [];
            if (!$hasDbCustom && !empty($roleName)) {
                $normName = class_exists('\App\Services\PermissionService') ? \App\Services\PermissionService::normalizeRoleTitle($roleName) : $roleName;
                foreach ($defaultMatrix as $mName => $mSlugs) {
                    $mNorm = class_exists('\App\Services\PermissionService') ? \App\Services\PermissionService::normalizeRoleTitle($mName) : $mName;
                    if (strcasecmp(trim($mName), $roleName) === 0 || strcasecmp(trim($mNorm), $normName) === 0) {
                        $defaultSlugs = $mSlugs;
                        break;
                    }
                }
            }

            $perms = $allPermissions;
            foreach ($perms as &$perm) {
                if ($hasDbCustom) {
                    $perm['granted'] = in_array((int) $perm['id'], $grantedIds, true);
                } else {
                    $perm['granted'] = in_array($perm['slug'] ?? '', $defaultSlugs, true);
                }
            }

            $role['permissions'] = $perms;
            $role['user_count'] = $userCountByRole[$roleId] ?? 0;
        }

        if (empty($options) && $existingUsers === null) {
            self::$cachedRoles = $roles;
            self::$cacheTime = time();
        }

        return $roles;
    }

    public function find(int $id): ?array
    {
        $result = $this->db->select($this->table, ['id' => 'eq.' . $id]);
        if (empty($result)) {
            return null;
        }

        $role = $result[0];
        $role['permissions'] = $this->getPermissionsForRole($id);
        $role['user_count'] = $this->countUsersForRole($role['name']);
        return $role;
    }

    public function updateById(int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => 'eq.' . $id], true);
    }

    public static function standardPermissionsCatalog(): array
    {
        return [
            ['id' => 1,  'module' => 'Main Controls', 'slug' => 'dashboard.view',   'label' => 'Dashboard / System Overview'],
            ['id' => 2,  'module' => 'Main Controls', 'slug' => 'analytics.view',   'label' => 'Analytics'],
            ['id' => 3,  'module' => 'Main Controls', 'slug' => 'reports.view',     'label' => 'Reports'],
            ['id' => 4,  'module' => 'Main Controls', 'slug' => 'compliance.view',  'label' => 'Compliance & Violations'],

            ['id' => 5,  'module' => 'Health Center Services', 'slug' => 'patients.view',        'label' => 'View Patients'],
            ['id' => 6,  'module' => 'Health Center Services', 'slug' => 'patients.create',      'label' => 'Create Patients'],
            ['id' => 7,  'module' => 'Health Center Services', 'slug' => 'patients.edit',        'label' => 'Edit Patients'],
            ['id' => 8,  'module' => 'Health Center Services', 'slug' => 'patients.delete',      'label' => 'Delete Patients'],
            ['id' => 9,  'module' => 'Health Center Services', 'slug' => 'consultations.view',   'label' => 'View Consultations'],
            ['id' => 10, 'module' => 'Health Center Services', 'slug' => 'consultations.create', 'label' => 'Create Consultations'],
            ['id' => 11, 'module' => 'Health Center Services', 'slug' => 'triage.view',          'label' => 'View Triage'],
            ['id' => 12, 'module' => 'Health Center Services', 'slug' => 'triage.create',        'label' => 'Create Triage'],
            ['id' => 13, 'module' => 'Health Center Services', 'slug' => 'prescriptions.view',   'label' => 'View Prescriptions'],
            ['id' => 14, 'module' => 'Health Center Services', 'slug' => 'prescriptions.create', 'label' => 'Create Prescriptions'],

            ['id' => 15, 'module' => 'Sanitation Permits', 'slug' => 'permits.view',         'label' => 'View Permits'],
            ['id' => 16, 'module' => 'Sanitation Permits', 'slug' => 'permits.create',       'label' => 'Create Permits'],
            ['id' => 17, 'module' => 'Sanitation Permits', 'slug' => 'permits.approve',      'label' => 'Approve Permits'],
            ['id' => 18, 'module' => 'Sanitation Permits', 'slug' => 'inspections.view',     'label' => 'View Inspections'],
            ['id' => 19, 'module' => 'Sanitation Permits', 'slug' => 'inspections.conduct',  'label' => 'Conduct Inspections'],

            ['id' => 20, 'module' => 'Immunization & Nutrition', 'slug' => 'immunization.view',   'label' => 'View Records'],
            ['id' => 21, 'module' => 'Immunization & Nutrition', 'slug' => 'immunization.create', 'label' => 'Create Records'],
            ['id' => 22, 'module' => 'Immunization & Nutrition', 'slug' => 'immunization.edit',   'label' => 'Edit Records'],

            ['id' => 34, 'module' => 'Wastewater Services', 'slug' => 'wastewater.view',   'label' => 'View Wastewater & Septic Records'],
            ['id' => 35, 'module' => 'Wastewater Services', 'slug' => 'wastewater.create', 'label' => 'Create Service Request / Tank'],
            ['id' => 36, 'module' => 'Wastewater Services', 'slug' => 'wastewater.edit',   'label' => 'Edit Maintenance & Status'],
            ['id' => 37, 'module' => 'Wastewater Services', 'slug' => 'wastewater.manage', 'label' => 'Manage Desludging & Haulers'],

            ['id' => 30, 'module' => 'Health Surveillance', 'slug' => 'surveillance.view',   'label' => 'View Disease Surveillance'],
            ['id' => 31, 'module' => 'Health Surveillance', 'slug' => 'surveillance.create', 'label' => 'Create Case & Outbreak Alert'],
            ['id' => 32, 'module' => 'Health Surveillance', 'slug' => 'surveillance.edit',   'label' => 'Edit Case & Investigation'],
            ['id' => 33, 'module' => 'Health Surveillance', 'slug' => 'surveillance.manage', 'label' => 'Manage Surveillance & Tracing'],

            ['id' => 23, 'module' => 'System Management', 'slug' => 'users.view',       'label' => 'View Users'],
            ['id' => 24, 'module' => 'System Management', 'slug' => 'users.create',     'label' => 'Create Users'],
            ['id' => 25, 'module' => 'System Management', 'slug' => 'users.edit',       'label' => 'Edit Users'],
            ['id' => 26, 'module' => 'System Management', 'slug' => 'users.delete',     'label' => 'Delete Users'],
            ['id' => 27, 'module' => 'System Management', 'slug' => 'roles.manage',     'label' => 'Manage Roles'],
            ['id' => 28, 'module' => 'System Management', 'slug' => 'settings.manage',  'label' => 'Manage Settings'],
            ['id' => 29, 'module' => 'System Management', 'slug' => 'logs.view',        'label' => 'View Logs'],
        ];
    }

    /**
     * Ensure all 29 standard permissions exist in public.permissions DB table
     */
    public function ensurePermissionsExistInDB(): array
    {
        try {
            $existing = $this->db->select('permissions', [], ['order' => 'id.asc']);
        } catch (Throwable $e) {
            $existing = [];
        }

        $existingBySlug = [];
        foreach ($existing as $p) {
            if (!empty($p['slug'])) {
                $existingBySlug[$p['slug']] = $p;
            }
        }

        $catalog = self::standardPermissionsCatalog();
        $finalMap = []; // slug/int_id => real_db_id

        foreach ($catalog as $cat) {
            $slug = $cat['slug'];
            $catId = (int) $cat['id'];

            if (isset($existingBySlug[$slug])) {
                $realId = (int) $existingBySlug[$slug]['id'];
                $finalMap[$slug] = $realId;
                $finalMap[$catId] = $realId;
            } else {
                try {
                    $inserted = $this->db->insert('permissions', [
                        'id'     => $catId,
                        'module' => $cat['module'],
                        'slug'   => $cat['slug'],
                        'label'  => $cat['label'],
                    ], true);
                    $newId = !empty($inserted) ? (int) $inserted[0]['id'] : $catId;
                    $finalMap[$slug] = $newId;
                    $finalMap[$catId] = $newId;
                } catch (Throwable $e2) {
                    $finalMap[$slug] = $catId;
                    $finalMap[$catId] = $catId;
                }
            }
        }

        return $finalMap;
    }

    /**
     * Get all permissions with a granted flag for a specific role
     */
    public function getPermissionsForRole(int $roleId): array
    {
        $this->ensurePermissionsExistInDB();

        try {
            $allPermissions = $this->db->select('permissions', [], ['order' => 'id.asc']);
        } catch (Throwable $e) {
            $allPermissions = self::standardPermissionsCatalog();
        }

        if (empty($allPermissions)) {
            $allPermissions = self::standardPermissionsCatalog();
        }

        // Sort so Main Controls is first
        usort($allPermissions, function ($a, $b) {
            if (($a['module'] ?? '') === 'Main Controls' && ($b['module'] ?? '') !== 'Main Controls') return -1;
            if (($a['module'] ?? '') !== 'Main Controls' && ($b['module'] ?? '') === 'Main Controls') return 1;
            return strcmp($a['module'] ?? '', $b['module'] ?? '');
        });

        // Get granted permission IDs for this role
        $hasDbCustom = false;
        try {
            $granted = $this->db->select('role_permissions', ['role_id' => 'eq.' . $roleId], ['select' => 'permission_id']);
            if (is_array($granted)) {
                $grantedIds = array_map('intval', array_column($granted, 'permission_id'));
                $hasDbCustom = !empty($grantedIds);
            } else {
                $grantedIds = [];
            }
        } catch (Throwable $e) {
            $grantedIds = [];
        }

        // Get role name for matrix fallback
        $roleName = '';
        try {
            $roleRecord = $this->db->select($this->table, ['id' => 'eq.' . $roleId], ['select' => 'name']);
            if (!empty($roleRecord)) {
                $roleName = trim($roleRecord[0]['name'] ?? '');
            }
        } catch (Throwable $e) {}

        $isSystemAdminRole = ($roleId === 1 || strcasecmp($roleName, 'System Administrator') === 0 || strcasecmp($roleName, 'System Admin') === 0);

        if (empty($roleName) && !$isSystemAdminRole) {
            error_log("Role ID {$roleId} not found in roles table — permission matrix skipped.");
        }

        $defaultMatrix = class_exists('\App\Services\PermissionService') 
            ? \App\Services\PermissionService::defaultRolePermissionMatrix() 
            : [];

        $defaultSlugs = [];
        if (!$hasDbCustom && !empty($roleName)) {
            $normRoleName = class_exists('\App\Services\PermissionService') 
                ? \App\Services\PermissionService::normalizeRoleTitle($roleName) 
                : $roleName;
            foreach ($defaultMatrix as $mName => $mSlugs) {
                $normMName = class_exists('\App\Services\PermissionService') 
                    ? \App\Services\PermissionService::normalizeRoleTitle($mName) 
                    : $mName;
                if (strcasecmp(trim($mName), $roleName) === 0 || strcasecmp(trim($normMName), $normRoleName) === 0) {
                    $defaultSlugs = $mSlugs;
                    break;
                }
            }
        }

        // Merge granted flag
        foreach ($allPermissions as &$perm) {
            $permId = (int) ($perm['id'] ?? 0);
            $slug = $perm['slug'] ?? '';
            if ($isSystemAdminRole) {
                $perm['granted'] = true;
            } elseif ($hasDbCustom) {
                $perm['granted'] = in_array($permId, $grantedIds, true);
            } else {
                $perm['granted'] = in_array($slug, $defaultSlugs, true);
            }
        }

        return $allPermissions;
    }

    /**
     * Replace all permissions for a role
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        // 1. Ensure permissions physically exist in DB table first
        $idMap = $this->ensurePermissionsExistInDB();

        // 2. Delete existing role permissions
        try {
            $this->db->delete('role_permissions', ['role_id' => 'eq.' . $roleId], true);
        } catch (Throwable $e) {
            // Table might be empty — that's fine
        }

        // 3. Insert mapped permission IDs
        foreach ($permissionIds as $permId) {
            $targetId = $idMap[(int) $permId] ?? (int) $permId;
            try {
                $this->db->insert('role_permissions', [
                    'role_id'       => $roleId,
                    'permission_id' => $targetId,
                ], true);
            } catch (Throwable $e) {
                // Ignore duplicates
            }
        }
    }

    /**
     * Count employees assigned to a role name
     */
    private function countUsersForRole(string $roleName): int
    {
        try {
            $results = $this->db->select('employees', ['role' => 'eq.' . $roleName], ['select' => 'id']);
            return count($results);
        } catch (Throwable $e) {
            return 0;
        }
    }
}
