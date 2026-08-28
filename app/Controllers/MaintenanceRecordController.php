<?php
// app/Controllers/MaintenanceRecordController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/MaintenanceRecord.php';

class MaintenanceRecordController extends BaseController
{
    private MaintenanceRecord $model;

    public function __construct()
    {
        $this->model = new MaintenanceRecord();
    }

    public function index(): void
    {
        $this->handle(function () {
            $records = $this->model->all(['order' => 'created_at.desc']);
            return ['success' => true, 'data' => $records, 'total' => count($records)];
        });
    }

    public function stats(): void
    {
        $this->handle(function () {
            return ['success' => true, 'data' => $this->model->countByStatus()];
        });
    }

    public function paginated(): void
    {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset  = ($page - 1) * $limit;
        $search  = strtolower(trim($_GET['q'] ?? ''));
        $status  = trim($_GET['status'] ?? '');
        $type    = trim($_GET['service_type'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo  = trim($_GET['date_to'] ?? '');

        $this->handle(function () use ($page, $limit, $offset, $search, $status, $type, $dateFrom, $dateTo) {
            $all = $this->model->all(['order' => 'scheduled_date.desc']);
            $filtered = array_values(array_filter($all, function ($r) use ($search, $status, $type, $dateFrom, $dateTo) {
                $matchSearch = !$search ||
                    str_contains(strtolower($r['owner_name'] ?? ''), $search) ||
                    str_contains(strtolower($r['service_id'] ?? ''), $search) ||
                    str_contains(strtolower($r['tank_id'] ?? ''), $search) ||
                    str_contains(strtolower($r['technician'] ?? ''), $search);
                $matchStatus = !$status || ($r['status'] ?? '') === $status;
                $matchType   = !$type   || ($r['service_type'] ?? '') === $type;
                $date        = substr((string)($r['scheduled_date'] ?? ''), 0, 10);
                $matchFrom   = !$dateFrom || $date >= $dateFrom;
                $matchTo     = !$dateTo   || $date <= $dateTo;
                return $matchSearch && $matchStatus && $matchType && $matchFrom && $matchTo;
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

    public function show(string $id): void
    {
        $this->handle(function () use ($id) {
            $record = $this->model->find($id);
            if (!$record) return ['success' => false, 'message' => 'Maintenance record not found', 'code' => 404];
            return ['success' => true, 'data' => $record];
        });
    }

    public function store(): void
    {
        $this->handle(function () {
            $d = $this->input();
            if (empty($d['owner_name']))   return ['success' => false, 'message' => 'Owner name is required',   'code' => 422];
            if (empty($d['service_type'])) return ['success' => false, 'message' => 'Service type is required', 'code' => 422];

            // ⚡ DUPLICATE SCHEDULE PREVENTION: Block double-scheduling active services for the same tank
            if (!empty($d['tank_id'])) {
                $active = $this->model->findActiveByTankId($d['tank_id'], $d['scheduled_date'] ?? null);
                if ($active) {
                    $existingDate = substr((string)($active['scheduled_date'] ?? ''), 0, 10);
                    $existingSvc = $active['service_id'] ?? 'Service';
                    return [
                        'success' => false,
                        'message' => "Duplicate schedule: Tank {$d['tank_id']} already has an active service ({$existingSvc}) on {$existingDate}.",
                        'code' => 409
                    ];
                }
            }

            $allowed = ['service_id','tank_id','owner_name','address','service_type','scheduled_date',
                        'scheduled_time','technician','provider_id','status','completed_date',
                        'completed_time','findings','recommendations','notes','cost','rating'];
            $data = array_intersect_key($d, array_flip($allowed));

            $result = $this->model->create($data);
            return ['success' => true, 'message' => 'Maintenance record created successfully.', 'data' => $result[0] ?? $result];
        });
    }

    public function update(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Record not found', 'code' => 404];

            $d = $this->input();
            $allowed = ['tank_id','owner_name','address','service_type','scheduled_date',
                        'scheduled_time','technician','provider_id','status','completed_date',
                        'completed_time','findings','recommendations','notes','cost','rating'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (empty($data)) return ['success' => false, 'message' => 'No valid fields to update', 'code' => 422];

            $result = $this->model->updateById($id, $data);

            // ⚡ AUTOMATED WORKFLOW: When completed, update Septic Tank status & last_maintenance date in Supabase
            $newStatus = $data['status'] ?? ($existing['status'] ?? '');
            $tankId    = $data['tank_id'] ?? ($existing['tank_id'] ?? null);

            if ($newStatus === 'completed' && !empty($tankId)) {
                try {
                    require_once __DIR__ . '/../Models/SepticTank.php';
                    $tankModel = new SepticTank();
                    $tank = $tankModel->findByTankId($tankId);
                    if ($tank && !empty($tank['id'])) {
                        $tankModel->updateById($tank['id'], [
                            'last_maintenance' => date('Y-m-d'),
                            'status'           => 'good'
                        ]);
                    }
                } catch (Throwable $e) {
                    error_log('Automated SepticTank update error: ' . $e->getMessage());
                }
            }

            return ['success' => true, 'message' => 'Maintenance record updated successfully.', 'data' => $result[0] ?? $result];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Record not found', 'code' => 404];
            $this->model->deleteById($id);
            return ['success' => true, 'message' => 'Maintenance record deleted successfully.'];
        });
    }
}
