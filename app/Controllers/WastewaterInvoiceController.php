<?php
// app/Controllers/WastewaterInvoiceController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/WastewaterInvoice.php';

class WastewaterInvoiceController extends BaseController
{
    private WastewaterInvoice $model;

    public function __construct()
    {
        $this->model = new WastewaterInvoice();
    }

    public function index(): void
    {
        $this->handle(function () {
            $invoices = $this->model->all(['order' => 'created_at.desc']);
            return ['success' => true, 'data' => $invoices, 'total' => count($invoices)];
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
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = strtolower(trim($_GET['q'] ?? ''));
        $status = trim($_GET['status'] ?? '');
        $type   = trim($_GET['service_type'] ?? '');

        $this->handle(function () use ($page, $limit, $offset, $search, $status, $type) {
            $all = $this->model->all(['order' => 'created_at.desc']);
            $filtered = array_values(array_filter($all, function ($inv) use ($search, $status, $type) {
                $matchSearch = !$search ||
                    str_contains(strtolower($inv['client_name'] ?? ''), $search) ||
                    str_contains(strtolower($inv['invoice_id'] ?? ''), $search) ||
                    str_contains(strtolower($inv['tank_id'] ?? ''), $search);
                $matchStatus = !$status || ($inv['status'] ?? '') === $status;
                $matchType   = !$type   || ($inv['service_type'] ?? '') === $type;
                return $matchSearch && $matchStatus && $matchType;
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
            $inv = $this->model->find($id);
            if (!$inv) return ['success' => false, 'message' => 'Invoice not found', 'code' => 404];
            return ['success' => true, 'data' => $inv];
        });
    }

    public function store(): void
    {
        $this->handle(function () {
            $d = $this->input();
            if (empty($d['client_name']))  return ['success' => false, 'message' => 'Client name is required',   'code' => 422];
            if (empty($d['service_type'])) return ['success' => false, 'message' => 'Service type is required',  'code' => 422];
            if (!isset($d['amount']))      return ['success' => false, 'message' => 'Amount is required',         'code' => 422];

            // 🛡️ Duplicate Invoice Check for Active/Unpaid Invoices
            $tankId = trim($d['tank_id'] ?? '');
            $allowDuplicate = !empty($d['allow_duplicate']);
            if ($tankId && !$allowDuplicate) {
                $existingActive = $this->model->findActiveByTankId($tankId, $d['service_type']);
                if ($existingActive) {
                    return [
                        'success' => false,
                        'message' => "An active unpaid invoice ({$existingActive['invoice_id']}) already exists for Tank {$tankId} with status '{$existingActive['status']}'.",
                        'duplicate_invoice' => $existingActive,
                        'code' => 409
                    ];
                }
            }

            $allowed = ['invoice_id','client_name','tank_id','service_request_id','provider_id',
                        'service_type','amount','tax','total_amount','status','payment_method',
                        'payment_reference','invoice_date','due_date','paid_at','notes','items'];
            $data = array_intersect_key($d, array_flip($allowed));
            // Encode items array to JSON string if passed as array
            if (isset($data['items']) && is_array($data['items'])) {
                $data['items'] = json_encode($data['items']);
            }

            $result = $this->model->create($data);
            return ['success' => true, 'message' => 'Invoice created successfully.', 'data' => $result[0] ?? $result];
        });
    }

    public function update(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Invoice not found', 'code' => 404];

            $d = $this->input();
            $allowed = ['client_name','tank_id','service_request_id','provider_id','service_type',
                        'amount','tax','total_amount','status','payment_method','payment_reference',
                        'invoice_date','due_date','paid_at','notes','items'];
            $data = array_intersect_key($d, array_flip($allowed));
            if (empty($data)) return ['success' => false, 'message' => 'No valid fields to update', 'code' => 422];

            if (isset($data['items']) && is_array($data['items'])) {
                $data['items'] = json_encode($data['items']);
            }
            // Auto-recalculate total if amount/tax changed
            if (isset($data['amount']) || isset($data['tax'])) {
                $amt = (float)($data['amount'] ?? $existing['amount'] ?? 0);
                $tax = (float)($data['tax']    ?? $existing['tax']    ?? 0);
                $data['total_amount'] = $amt + $tax;
            }

            $result = $this->model->updateById($id, $data);
            return ['success' => true, 'message' => 'Invoice updated successfully.', 'data' => $result[0] ?? $result];
        });
    }

    public function markPaid(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Invoice not found', 'code' => 404];

            $d = $this->input();
            $method = $d['payment_method'] ?? 'Over-the-Counter';
            $ref    = trim($d['payment_reference'] ?? '');

            // ⚡ Auto-generate Official Receipt / OTC Reference for face-to-face over-the-counter payments
            if (empty($ref) && ($method === 'Over-the-Counter' || $method === 'Cash')) {
                $ref = 'OTC-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
            }

            $data = [
                'status'            => 'paid',
                'paid_at'           => date('c'),
                'payment_method'    => $method,
                'payment_reference' => $ref ?: null,
            ];
            $result = $this->model->updateById($id, $data);

            // Automatically complete any linked active service request or maintenance record upon payment
            try {
                if (!empty($existing['service_request_id'])) {
                    require_once __DIR__ . '/../Models/ServiceRequest.php';
                    $srModel = new ServiceRequest();
                    $srModel->updateById($existing['service_request_id'], ['status' => 'completed', 'completed_at' => date('c')]);
                }
                if (!empty($existing['tank_id'])) {
                    require_once __DIR__ . '/../Models/MaintenanceRecord.php';
                    $mrModel = new MaintenanceRecord();
                    $activeMaint = $mrModel->findActiveByTankId($existing['tank_id']);
                    if ($activeMaint && isset($activeMaint['id'])) {
                        $mrModel->updateById($activeMaint['id'], [
                            'status' => 'completed',
                            'completed_date' => date('Y-m-d'),
                            'completed_time' => date('H:i:s')
                        ]);
                    }
                }
            } catch (Throwable $e) {
                error_log('WastewaterInvoiceController markPaid sync error: ' . $e->getMessage());
            }

            return ['success' => true, 'message' => 'Invoice marked as paid.', 'data' => $result[0] ?? $result];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function () use ($id) {
            $existing = $this->model->find($id);
            if (!$existing) return ['success' => false, 'message' => 'Invoice not found', 'code' => 404];
            $this->model->deleteById($id);
            return ['success' => true, 'message' => 'Invoice deleted successfully.'];
        });
    }
}
