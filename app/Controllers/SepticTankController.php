<?php
// app/Controllers/SepticTankController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/SepticTank.php';

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
            $tanks = $this->model->all(['order' => 'created_at.desc']);
            return ['success' => true, 'data' => $tanks, 'total' => count($tanks)];
        });
    }

    /** GET /api/septic-tanks?stats=true  — KPI counts */
    public function stats(): void
    {
        $this->handle(function () {
            return ['success' => true, 'data' => $this->model->countByStatus()];
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
            $all = $this->model->all(['order' => 'created_at.desc']);
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
            $tank = $this->model->find($id);
            if (!$tank) return ['success' => false, 'message' => 'Septic tank not found', 'code' => 404];
            return ['success' => true, 'data' => $tank];
        });
    }

    /** POST /api/septic-tanks  — create */
    public function store(): void
    {
        $this->handle(function () {
            $d = $this->input();
            if (empty($d['owner_name'])) return ['success' => false, 'message' => 'Owner name is required', 'code' => 422];
            if (empty($d['address']))    return ['success' => false, 'message' => 'Address is required',    'code' => 422];

            $allowed = ['tank_id','owner_name','address','barangay','latitude','longitude',
                        'capacity','type','installation_year','last_maintenance',
                        'maintenance_frequency','status','notes'];
            $data = array_intersect_key($d, array_flip($allowed));

            $result = $this->model->create($data);
            return ['success' => true, 'message' => 'Septic tank registered successfully.', 'data' => $result[0] ?? $result];
        });
    }

    /** PATCH /api/septic-tanks?id=N  — update */
    public function update(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Septic tank not found', 'code' => 404];

            $d = $this->input();
            $allowed = ['owner_name','address','barangay','latitude','longitude',
                        'capacity','type','installation_year','last_maintenance',
                        'maintenance_frequency','status','notes'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (empty($data)) return ['success' => false, 'message' => 'No valid fields to update', 'code' => 422];

            $result = $this->model->updateById($id, $data);
            return ['success' => true, 'message' => 'Septic tank updated successfully.', 'data' => $result[0] ?? $result];
        });
    }

    /** DELETE /api/septic-tanks?id=N */
    public function destroy(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Septic tank not found', 'code' => 404];
            $this->model->deleteById($id);
            return ['success' => true, 'message' => 'Septic tank deleted successfully.'];
        });
    }
}
