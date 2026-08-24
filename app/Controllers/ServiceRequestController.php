<?php
// app/Controllers/ServiceRequestController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/ServiceRequest.php';

class ServiceRequestController extends BaseController
{
    private ServiceRequest $model;

    public function __construct()
    {
        $this->model = new ServiceRequest();
    }

    public function index(): void
    {
        $this->handle(function () {
            $requests = $this->model->all(['order' => 'created_at.desc']);
            return ['success' => true, 'data' => $requests, 'total' => count($requests)];
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
        $priority = trim($_GET['priority'] ?? '');

        $this->handle(function () use ($page, $limit, $offset, $search, $status, $type, $priority) {
            $all = $this->model->all(['order' => 'created_at.desc']);
            $filtered = array_values(array_filter($all, function ($r) use ($search, $status, $type, $priority) {
                $matchSearch = !$search ||
                    str_contains(strtolower($r['owner_name'] ?? ''), $search) ||
                    str_contains(strtolower($r['request_id'] ?? ''), $search) ||
                    str_contains(strtolower($r['tank_id'] ?? ''), $search);
                $matchStatus   = !$status   || ($r['status'] ?? '')       === $status;
                $matchType     = !$type     || ($r['service_type'] ?? '') === $type;
                $matchPriority = !$priority || ($r['priority'] ?? '')     === $priority;
                return $matchSearch && $matchStatus && $matchType && $matchPriority;
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
            $req = $this->model->find($id);
            if (!$req) return ['success' => false, 'message' => 'Service request not found', 'code' => 404];
            return ['success' => true, 'data' => $req];
        });
    }

    public function store(): void
    {
        $this->handle(function () {
            // Check if service requests are enabled
            $srvReqEnabled = class_exists('Settings') ? (bool)Settings::get('modules.wastewater.enable_service_requests', true) : true;
            if (!$srvReqEnabled) {
                return [
                    'success' => false,
                    'message' => 'Online wastewater service intake is temporarily disabled by the administrator.',
                    'code' => 403
                ];
            }

            $d = $this->input();
            if (empty($d['owner_name']))   return ['success' => false, 'message' => 'Owner name is required',   'code' => 422];
            if (empty($d['service_type'])) return ['success' => false, 'message' => 'Service type is required', 'code' => 422];

            $allowed = ['request_id','tank_id','owner_name','address','barangay','service_type',
                        'preferred_date','preferred_time','assigned_to','provider_id',
                        'status','priority','notes'];
            $data = array_intersect_key($d, array_flip($allowed));

            $result = $this->model->create($data);
            $req = $result[0] ?? $result;

            // ⚡ AUTOMATED WORKFLOW: Auto-create Maintenance Record & Invoice
            try {
                require_once __DIR__ . '/../Models/MaintenanceRecord.php';
                require_once __DIR__ . '/../Models/WastewaterInvoice.php';

                $mModel = new MaintenanceRecord();
                $mModel->create([
                    'service_id'     => 'SRV-' . date('ymd') . '-' . rand(100, 999),
                    'tank_id'        => $req['tank_id'] ?? ($d['tank_id'] ?? null),
                    'owner_name'     => $req['owner_name'] ?? $d['owner_name'],
                    'address'        => $req['address'] ?? ($d['address'] ?? ''),
                    'service_type'   => $req['service_type'] ?? $d['service_type'],
                    'scheduled_date' => $req['preferred_date'] ?? ($d['preferred_date'] ?? date('Y-m-d')),
                    'scheduled_time' => $req['preferred_time'] ?? ($d['preferred_time'] ?? '09:00 AM'),
                    'technician'     => $req['assigned_to'] ?? ($d['assigned_to'] ?? 'Unassigned'),
                    'provider_id'    => $req['provider_id'] ?? ($d['provider_id'] ?? null),
                    'status'         => 'scheduled',
                    'cost'           => 1500.00,
                    'notes'          => 'Auto-created from Service Request ' . ($req['request_id'] ?? '')
                ]);

                // Auto-generate invoice only if wastewater billing is enabled
                $billingEnabled = class_exists('Settings') ? (bool)Settings::get('modules.wastewater.enable_billing', true) : true;
                if ($billingEnabled) {
                    $iModel = new WastewaterInvoice();
                    $iModel->create([
                        'invoice_id'         => 'INV-' . date('ymd') . '-' . rand(100, 999),
                        'tank_id'            => $req['tank_id'] ?? ($d['tank_id'] ?? null),
                        'client_name'        => $req['owner_name'] ?? $d['owner_name'],
                        'service_type'       => ucfirst($req['service_type'] ?? ($d['service_type'] ?? 'desludging')) . ' Service',
                        'amount'             => 1500.00,
                        'tax'                => 180.00,
                        'total_amount'       => 1680.00,
                        'due_date'           => date('Y-m-d', strtotime('+14 days')),
                        'status'             => 'pending',
                        'provider_id'        => $req['provider_id'] ?? ($d['provider_id'] ?? null),
                        'service_request_id' => $req['request_id'] ?? null
                    ]);
                }
            } catch (Throwable $e) {
                error_log('Automated cascade error in ServiceRequestController: ' . $e->getMessage());
            }

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $srCode = $req['request_id'] ?? ($data['request_id'] ?? 'SR');
                    $st = ucfirst($req['service_type'] ?? ($data['service_type'] ?? 'Desludging'));
                    $owner = $req['owner_name'] ?? ($data['owner_name'] ?? 'Applicant');
                    $logger->log("Logged {$st} Service Request ({$srCode})", [
                        'module'  => 'Wastewater Services',
                        'details' => "Owner: {$owner} | Barangay: " . ($req['barangay'] ?? 'N/A'),
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            return ['success' => true, 'message' => 'Service request submitted successfully and connected records generated!', 'data' => $req];
        });
    }

    public function update(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Service request not found', 'code' => 404];

            $d = $this->input();
            $allowed = ['tank_id','owner_name','address','barangay','service_type','preferred_date',
                        'preferred_time','assigned_to','provider_id','status','priority',
                        'notes','feedback','rating','completed_at'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (empty($data)) return ['success' => false, 'message' => 'No valid fields to update', 'code' => 422];

            $result = $this->model->updateById($id, $data);
            return ['success' => true, 'message' => 'Service request updated successfully.', 'data' => $result[0] ?? $result];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Service request not found', 'code' => 404];
            $this->model->deleteById($id);
            return ['success' => true, 'message' => 'Service request deleted successfully.'];
        });
    }
}
