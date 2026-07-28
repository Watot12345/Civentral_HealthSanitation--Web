<?php
// app/Controllers/RoleController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Role.php';
require_once __DIR__ . '/../Models/ActivityLog.php';

class RoleController extends BaseController
{
    private Role $roleModel;
    private ActivityLog $activityLog;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->activityLog = new ActivityLog();
    }

    /**
     * GET /roles — list all roles
     */
    public function index(): void
    {
        $this->handle(function () {
            $roles = $this->roleModel->all(['order' => 'id.asc']);
            return [
                'success' => true,
                'data'    => $roles,
                'total'   => count($roles),
            ];
        });
    }

    /**
     * GET /roles/{id} — single role with permissions
     */
    public function show(string $id): void
    {
        $this->handle(function () use ($id) {
            $role = $this->roleModel->find((int) $id);
            if (!$role) {
                return ['success' => false, 'message' => 'Role not found', 'code' => 404];
            }
            return ['success' => true, 'data' => $role];
        });
    }

    /**
     * PUT /roles/{id} — update role meta and/or permissions
     */
    public function update(string $id): void
    {
        $data = $this->input();

        $this->handle(function () use ($id, $data) {
            $role = $this->roleModel->find((int) $id);
            if (!$role) {
                return ['success' => false, 'message' => 'Role not found', 'code' => 404];
            }

            // Update role fields (name, description, color)
            $updateFields = [];
            if (isset($data['name']))        $updateFields['name']        = $data['name'];
            if (isset($data['description'])) $updateFields['description'] = $data['description'];
            if (isset($data['color']))       $updateFields['color']       = $data['color'];

            if (!empty($updateFields)) {
                $this->roleModel->updateById((int) $id, $updateFields);
            }

            // Sync permissions if provided
            if (isset($data['permission_ids']) && is_array($data['permission_ids'])) {
                $this->roleModel->syncPermissions((int) $id, $data['permission_ids']);
            }

            // Log activity
            $this->activityLog->log('Role Updated', [
                'details' => "Updated role: {$role['name']} (ID #{$id})",
                'module'  => 'User Management',
            ]);

            return [
                'success' => true,
                'message' => 'Role updated successfully',
                'data'    => $this->roleModel->find((int) $id),
            ];
        });
    }
}
