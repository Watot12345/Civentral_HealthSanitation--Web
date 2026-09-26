<?php
// app/Controllers/ServiceProviderController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/ServiceProvider.php';
require_once __DIR__ . '/../Constants/Permissions.php';

use App\Constants\Permissions;

class ServiceProviderController extends BaseController
{
    private ServiceProvider $model;

    public function __construct()
    {
        $this->model = new ServiceProvider();
    }

    public function index(): void
    {
        $this->handle(function () {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $providers = $this->model->all(['order' => 'created_at.desc']);
            return ['success' => true, 'data' => $providers, 'total' => count($providers)];
        });
    }

    public function stats(): void
    {
        $this->handle(function () {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            return ['success' => true, 'data' => $this->model->countByStatus()];
        });
    }

    public function paginated(): void
    {
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = strtolower(trim($_GET['q'] ?? ''));
        $status = trim($_GET['status'] ?? '');
        $spec   = trim($_GET['specialization'] ?? '');

        $this->handle(function () use ($page, $limit, $offset, $search, $status, $spec) {
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $all = $this->model->all(['order' => 'created_at.desc']);
            $filtered = array_values(array_filter($all, function ($p) use ($search, $status, $spec) {
                $matchSearch = !$search ||
                    str_contains(strtolower($p['name'] ?? ''), $search) ||
                    str_contains(strtolower($p['provider_id'] ?? ''), $search) ||
                    str_contains(strtolower($p['license_number'] ?? ''), $search);
                $matchStatus = !$status || ($p['status'] ?? '') === $status;
                $matchSpec   = !$spec   || ($p['specialization'] ?? '') === $spec;
                return $matchSearch && $matchStatus && $matchSpec;
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
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $provider = $this->model->find($id);
            if (!$provider) return ['success' => false, 'message' => 'Provider not found', 'code' => 404];
            return ['success' => true, 'data' => $provider];
        });
    }

    public function store(): void
    {
        $this->handle(function () {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_CREATE);

            $d = $this->input();
            if (empty($d['name']))           return ['success' => false, 'message' => 'Provider name is required', 'code' => 422];
            if (empty($d['specialization'])) return ['success' => false, 'message' => 'Specialization is required', 'code' => 422];

            $allowed = ['provider_id','name','contact','email','address','license_number',
                        'specialization','rating','status','equipment_count','completed_jobs',
                        'response_time','certification','joined_date','notes'];
            $data = array_intersect_key($d, array_flip($allowed));

            $result = $this->model->create($data);
            $record = $result[0] ?? $result;
            return [
                'success' => true,
                'action'  => 'create',
                'record'  => $record,
                'message' => 'Service provider added successfully.',
                'data'    => $record,
                'code'    => 201
            ];
        });
    }

    public function update(string $id): void
    {
        $this->handle(function () use ($id) {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_EDIT);

            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Provider not found', 'code' => 404];

            $d = $this->input();
            $allowed = ['name','contact','email','address','license_number','specialization',
                        'rating','status','equipment_count','completed_jobs','response_time',
                        'certification','joined_date','notes'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (empty($data)) return ['success' => false, 'message' => 'No valid fields to update', 'code' => 422];

            $result = $this->model->updateById($id, $data);
            $record = $result[0] ?? $result;
            return [
                'success' => true,
                'action'  => 'update',
                'record'  => $record,
                'message' => 'Provider updated successfully.',
                'data'    => $record
            ];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function () use ($id) {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_MANAGE);

            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Provider not found', 'code' => 404];
            $this->model->deleteById($id);
            return [
                'success' => true,
                'action'  => 'delete',
                'id'      => $id,
                'record'  => ['id' => $id],
                'message' => 'Provider deleted successfully.'
            ];
        });
    }
}
