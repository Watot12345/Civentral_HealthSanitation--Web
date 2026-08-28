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
            $providerId = trim($_GET['provider_id'] ?? '');
            $upcoming   = isset($_GET['upcoming']) && ($_GET['upcoming'] === '1' || $_GET['upcoming'] === 'true');
            $history    = isset($_GET['history']) && ($_GET['history'] === '1' || $_GET['history'] === 'true');
            
            $requests = $this->model->all(['order' => 'created_at.desc']);

            if ($providerId !== '') {
                $requests = array_values(array_filter($requests, function($r) use ($providerId) {
                    $pid = (string)($r['provider_id'] ?? '');
                    return $pid === $providerId || 
                           str_ends_with($pid, '-' . $providerId) || 
                           (is_numeric($providerId) && (int)$pid === (int)$providerId);
                }));
            }

            if ($upcoming) {
                $upcomingStatuses = ['pending', 'approved', 'in_progress', 'scheduled'];
                $requests = array_values(array_filter($requests, fn($r) => in_array(strtolower($r['status'] ?? 'pending'), $upcomingStatuses)));
            } elseif ($history) {
                $historyStatuses = ['completed', 'cancelled'];
                $requests = array_values(array_filter($requests, fn($r) => in_array(strtolower($r['status'] ?? ''), $historyStatuses)));
            }

            // Enrich with septic tank locations
            require_once __DIR__ . '/../Models/SepticTank.php';
            $tankModel = new SepticTank();
            $tanks = [];
            try {
                $tanksRaw = $tankModel->all();
                foreach ($tanksRaw as $t) {
                    if (!empty($t['tank_id'])) {
                        $tanks[$t['tank_id']] = $t;
                    }
                }
            } catch (Throwable $e) {}

            $baseLat = 14.6538;
            $baseLng = 120.9820;
            $idx = 0;

            foreach ($requests as &$req) {
                $tId = $req['tank_id'] ?? '';
                $t = $tanks[$tId] ?? null;
                $req['assignment_type'] = $req['service_type'] ?? 'maintenance';
                $req['scheduled_date']  = $req['preferred_date'] ?? date('Y-m-d');
                $req['scheduled_time']  = $req['preferred_time'] ?? '09:00 AM';
                
                // Coordinates
                $lat = !empty($t['latitude']) ? (float)$t['latitude'] : null;
                $lng = !empty($t['longitude']) ? (float)$t['longitude'] : null;
                if (!$lat || !$lng) {
                    $offsets = [
                        [0.0052, 0.0031], [-0.0041, 0.0062], [0.0035, -0.0048],
                        [-0.0060, -0.0035], [0.0080, 0.0010], [-0.0025, 0.0085]
                    ];
                    $pair = $offsets[$idx % count($offsets)];
                    $lat = $baseLat + $pair[0];
                    $lng = $baseLng + $pair[1];
                }
                $req['latitude'] = $lat;
                $req['longitude'] = $lng;
                $req['lat'] = $lat;
                $req['lng'] = $lng;
                if (empty($req['address']) && !empty($t['address'])) {
                    $req['address'] = $t['address'];
                }
                $idx++;
            }
            unset($req);

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

            // ⚡ DUPLICATE REQUEST PREVENTION: Block double requests for the same tank while an active request is open
            if (!empty($d['tank_id'])) {
                $active = $this->model->findActiveByTankId($d['tank_id'], $d['preferred_date'] ?? null);
                if ($active) {
                    $existingDate = substr((string)($active['preferred_date'] ?? $active['created_at'] ?? ''), 0, 10);
                    $existingReq = $active['request_id'] ?? 'Request';
                    return [
                        'success' => false,
                        'message' => "Duplicate request: Tank {$d['tank_id']} already has an active service request ({$existingReq}) for {$existingDate}.",
                        'code' => 409
                    ];
                }
            }

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
