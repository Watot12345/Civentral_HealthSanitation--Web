<?php
// app/Controllers/SepticTankController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/SepticTank.php';
require_once __DIR__ . '/../Constants/Permissions.php';
require_once __DIR__ . '/../Policies/SepticTankPolicy.php';

use App\Constants\Permissions;
use App\Policies\SepticTankPolicy;

class SepticTankController extends BaseController
{
    private SepticTank $model;

    public function __construct()
    {
        $this->model = new SepticTank();
    }

    /** GET /api/septic-tanks  — list all */
    public function index(): void
    {
        $this->handle(function () {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $tanks = SepticTankPolicy::applyMaintenancePolicy(
                $this->model->all(['order' => 'created_at.desc']),
                new \DateTimeImmutable('now')
            );
            return ['success' => true, 'data' => $tanks, 'total' => count($tanks)];
        });
    }

    /** GET /api/septic-tanks?stats=true  — KPI counts */
    public function stats(): void
    {
        $this->handle(function () {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $tanks = SepticTankPolicy::applyMaintenancePolicy($this->model->all(), new \DateTimeImmutable('now'));
            return ['success' => true, 'data' => SepticTankPolicy::summarize($tanks)];
        });
    }

    /** GET /api/septic-tanks?guidelines=true — Siphoning policy & standards */
    public function guidelines(): void
    {
        $this->handle(function () {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            require_once __DIR__ . '/../Policies/SepticSiphoningPolicy.php';
            return [
                'success' => true,
                'data'    => \App\Policies\SepticSiphoningPolicy::getPolicyDetails()
            ];
        });
    }

    /** POST /api/septic-tanks?action=evaluate_siphoning — Evaluate household siphoning schedule & signs */
    public function evaluateSiphoning(): void
    {
        $this->handle(function () {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $d = $this->input();
            $occupants = max(1, (int)($d['occupants'] ?? 4));
            $lastMaintenance = !empty($d['last_maintenance']) ? trim((string)$d['last_maintenance']) : null;
            $signs = is_array($d['signs'] ?? null) ? $d['signs'] : [];

            require_once __DIR__ . '/../Policies/SepticSiphoningPolicy.php';
            $evaluation = \App\Policies\SepticSiphoningPolicy::evaluateSchedule(
                $occupants,
                $lastMaintenance,
                $signs,
                new \DateTimeImmutable('now')
            );

            return [
                'success' => true,
                'data'    => $evaluation
            ];
        });
    }

    /** GET /api/septic-tanks?page=N  — paginated + filtered */
    public function paginated(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = max(1, min(100, (int)($_GET['limit'] ?? 12)));
        $offset  = ($page - 1) * $limit;
        $search  = strtolower(trim($_GET['q'] ?? ''));
        $status  = trim($_GET['status'] ?? '');
        $type    = trim($_GET['type'] ?? '');
        $brgy    = trim($_GET['barangay'] ?? '');

        $this->handle(function () use ($page, $limit, $offset, $search, $status, $type, $brgy) {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $all = SepticTankPolicy::applyMaintenancePolicy(
                $this->model->all(['order' => 'created_at.desc']),
                new \DateTimeImmutable('now')
            );
            $filtered = array_values(array_filter($all, function ($t) use ($search, $status, $type, $brgy) {
                $matchSearch = !$search ||
                    str_contains(strtolower($t['owner_name'] ?? ''), $search) ||
                    str_contains(strtolower($t['tank_id'] ?? ''), $search) ||
                    str_contains(strtolower($t['address'] ?? ''), $search);
                $matchStatus = !$status || ($t['status'] ?? '') === $status;
                $matchType   = !$type   || ($t['type'] ?? '') === $type;
                $matchBrgy   = !$brgy   || ($t['barangay'] ?? '') === $brgy;
                return $matchSearch && $matchStatus && $matchType && $matchBrgy;
            }));
            $total = count($filtered);
            return [
                'success'     => true,
                'data'        => array_slice($filtered, $offset, $limit),
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => max(1, (int)ceil($total / $limit)),
            ];
        });
    }

    /** GET /api/septic-tanks?id=N */
    public function show(string $id): void
    {
        $this->handle(function () use ($id) {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $tank = $this->model->find($id);
            if (!$tank) return ['success' => false, 'message' => 'Septic tank not found', 'code' => 404];
            $tank = SepticTankPolicy::applyMaintenancePolicy([$tank], new \DateTimeImmutable('now'))[0];
            return ['success' => true, 'data' => $tank];
        });
    }

    /** POST /api/septic-tanks  — create */
    public function store(): void
    {
        $this->handle(function () {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_CREATE);

            $d = $this->input();
            $validation = SepticTankPolicy::validate($d, false, new \DateTimeImmutable('now'));
            if ($validation->denied()) return $validation->toResponse();

            $allowed = ['tank_id','owner_name','address','barangay','latitude','longitude',
                        'capacity','type','installation_year','last_maintenance',
                        'maintenance_frequency','status','notes'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (!empty($data['type'])) {
                $data['type'] = SepticTankPolicy::canonicalType((string)$data['type']);
            }

            $result = $this->model->create($data);
            $record = $result[0] ?? $result;
            return [
                'success' => true,
                'action'  => 'create',
                'record'  => $record,
                'message' => 'Septic tank registered successfully.',
                'data'    => $record,
                'code'    => 201
            ];
        });
    }

    /** PATCH /api/septic-tanks?id=N  — update */
    public function update(string $id): void
    {
        $this->handle(function () use ($id) {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_EDIT);

            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Septic tank not found', 'code' => 404];

            $d = $this->input();
            $allowed = ['owner_name','address','barangay','latitude','longitude',
                        'capacity','type','installation_year','last_maintenance',
                        'maintenance_frequency','status','notes'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (empty($data)) return ['success' => false, 'message' => 'No valid fields to update', 'code' => 422];

            $validation = SepticTankPolicy::validate($data, true, new \DateTimeImmutable('now'));
            if ($validation->denied()) return $validation->toResponse();
            if (!empty($data['type'])) {
                $data['type'] = SepticTankPolicy::canonicalType((string)$data['type']);
            }

            $result = $this->model->updateById($id, $data);
            $record = $result[0] ?? $result;
            return [
                'success' => true,
                'action'  => 'update',
                'record'  => $record,
                'message' => 'Septic tank updated successfully.',
                'data'    => $record
            ];
        });
    }

    /** POST /api/septic-tanks?action=complete_siphoning&id=N — Mark siphoning complete & loop back to START */
    public function completeSiphoning(string $id): void
    {
        $this->handle(function () use ($id) {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_EDIT);

            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Septic tank not found', 'code' => 404];

            $d = $this->input();
            $completedDate = !empty($d['completed_date']) ? substr(trim((string)$d['completed_date']), 0, 10) : date('Y-m-d');
            $occupants = isset($d['occupants']) ? (int)$d['occupants'] : null;

            $updateData = [
                'last_maintenance' => $completedDate,
                'status'           => 'good',
            ];
            if ($occupants !== null && $occupants > 0) {
                require_once __DIR__ . '/../Policies/SepticSiphoningPolicy.php';
                $updateData['maintenance_frequency'] = \App\Policies\SepticSiphoningPolicy::recommendedFrequencyMonths($occupants);
            }

            $result = $this->model->updateById($id, $updateData);
            $record = $result[0] ?? $result;

            return [
                'success' => true,
                'action'  => 'complete_siphoning',
                'record'  => $record,
                'message' => 'Siphoning recorded as COMPLETED. Next due date recalculated and status reset to OK (Loop back to START).',
                'data'    => $record
            ];
        });
    }

    /** DELETE /api/septic-tanks?id=N */
    public function destroy(string $id): void
    {
        $this->handle(function () use ($id) {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_MANAGE);

            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Septic tank not found', 'code' => 404];
            $this->model->deleteById($id);
            return [
                'success' => true,
                'action'  => 'delete',
                'id'      => $id,
                'record'  => ['id' => $id],
                'message' => 'Septic tank deleted successfully.'
            ];
        });
    }
}
