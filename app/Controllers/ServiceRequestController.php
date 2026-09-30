<?php
// app/Controllers/ServiceRequestController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/ServiceRequest.php';
require_once __DIR__ . '/../Constants/Permissions.php';

use App\Constants\Permissions;

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
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

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
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

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
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

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
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_VIEW);

            $req = $this->model->find($id);
            if (!$req) return ['success' => false, 'message' => 'Service request not found', 'code' => 404];
            return ['success' => true, 'data' => $req];
        });
    }

    public function store(): void
    {
        $this->handle(function () {
            $this->validateCsrf();
            $this->requireDepartment('wastewater services');
            $this->requireCapability(Permissions::WASTEWATER_CREATE);

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

            return [
                'success' => true,
                'action'  => 'create',
                'record'  => $req,
                'message' => 'Service request submitted successfully and connected records generated!',
                'data'    => $req,
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
            if (!$existing) return ['success' => false, 'message' => 'Service request not found', 'code' => 404];

            $d = $this->input();
            $allowed = ['tank_id','owner_name','address','barangay','service_type','preferred_date',
                        'preferred_time','assigned_to','provider_id','status','priority',
                        'notes','feedback','rating','completed_at'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (empty($data)) return ['success' => false, 'message' => 'No valid fields to update', 'code' => 422];

            $result = $this->model->updateById($id, $data);
            $record = $result[0] ?? $result;

            // ⚡ CASCADE: Sync linked MaintenanceRecord & SepticTank when status changes
            $newStatus = strtolower($data['status'] ?? '');
            $tankId    = $data['tank_id'] ?? ($existing['tank_id'] ?? null);

            if (($newStatus === 'completed' || $newStatus === 'cancelled') && !empty($tankId)) {
                try {
                    require_once __DIR__ . '/../Models/MaintenanceRecord.php';
                    $mModel = new MaintenanceRecord();

                    // Find the linked active maintenance record for this tank
                    $linkedMaint = $mModel->findActiveByTankId($tankId);

                    if ($linkedMaint && !empty($linkedMaint['id'])) {
                        if ($newStatus === 'completed') {
                            // Mark maintenance as completed and update septic tank status
                            $mModel->updateById($linkedMaint['id'], [
                                'status'         => 'completed',
                                'completed_date'  => date('Y-m-d'),
                                'completed_time'  => date('h:i A'),
                            ]);

                            // Update septic tank last_maintenance date and status
                            require_once __DIR__ . '/../Models/SepticTank.php';
                            $tankModel = new SepticTank();
                            $tank = $tankModel->findByTankId($tankId);
                            if ($tank && !empty($tank['id'])) {
                                $tankModel->updateById($tank['id'], [
                                    'last_maintenance' => date('Y-m-d'),
                                    'status'           => 'good'
                                ]);
                            }
                        } elseif ($newStatus === 'cancelled') {
                            // Cancel the linked maintenance record to unblock future scheduling
                            $mModel->updateById($linkedMaint['id'], [
                                'status' => 'cancelled',
                            ]);
                        }
                    }
                } catch (Throwable $e) {
                    error_log('ServiceRequest cascade sync error: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'action'  => 'update',
                'record'  => $record,
                'message' => 'Service request updated successfully.',
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
            if (!$existing) {
                return ['success' => false, 'message' => 'Service request not found', 'code' => 404];
            }

            // 1. Business Rule: Can only delete pending or cancelled service requests
            $currentStatus = strtolower(trim($existing['status'] ?? ''));
            if (!in_array($currentStatus, ['pending', 'cancelled'], true)) {
                return [
                    'success' => false,
                    'message' => "Cannot delete service request in '{$currentStatus}' status. Requests that are scheduled, in progress, or completed must be cancelled first or retained for auditing.",
                    'code' => 422
                ];
            }

            // 2. Financial Rule: Prevent deletion if billing invoices are attached
            require_once __DIR__ . '/../Models/WastewaterInvoice.php';
            $invoiceModel = new \WastewaterInvoice();
            $invoices = $invoiceModel->findByServiceRequestId($id);
            if (empty($invoices) && !empty($existing['request_id'])) {
                $invoices = $invoiceModel->findByServiceRequestId($existing['request_id']);
            }

            if (!empty($invoices)) {
                foreach ($invoices as $inv) {
                    $invStatus = strtolower($inv['status'] ?? '');
                    if (in_array($invStatus, ['pending', 'overdue', 'paid', 'partially_paid'], true)) {
                        return [
                            'success' => false,
                            'message' => "Cannot delete service request with attached billing invoice ({$inv['invoice_id']}, status: {$invStatus}). Please void or resolve billing invoices prior to deletion.",
                            'code' => 422
                        ];
                    }
                }
            }

            $this->model->deleteById($id);

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new \ActivityLog();
                    $logger->log("Deleted Wastewater Service Request: {$id}", [
                        'module'  => 'Wastewater Services',
                        'details' => "Status: {$currentStatus} | Type: " . ($existing['service_type'] ?? 'N/A'),
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {}
            }

            return [
                'success' => true,
                'action'  => 'delete',
                'id'      => $id,
                'record'  => ['id' => $id],
                'message' => 'Service request deleted successfully.'
            ];
        });
    }
}
