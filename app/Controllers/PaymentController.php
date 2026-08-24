<?php
// app/Controllers/PaymentController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Payment.php';
require_once __DIR__ . '/../Models/Permit.php';

class PaymentController extends BaseController
{
    private Payment $paymentModel;
    private Permit $permitModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->permitModel = new Permit();
    }

    /**
     * GET /api/payments.php  (list, paginated)
     */
    public function index(): void
    {
        $this->handle(function () {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);

            $filters = [
                'status' => $_GET['status'] ?? null,
                'method' => $_GET['method'] ?? null,
                'permit_id' => $_GET['permit_id'] ?? null,
                'search' => $_GET['search'] ?? null,
            ];
            $filters = array_filter($filters);

            $result = $this->paymentModel->getAll($filters, $page, $limit);

            return [
                'success' => true,
                'data' => $result['payments'],
                'page' => $result['page'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages'],
                'limit' => $result['limit']
            ];
        });
    }

    /**
     * GET /api/payments.php?stats=true
     */
    public function stats(): void
    {
        $this->handle(function () {
            return [
                'success' => true,
                'data' => $this->paymentModel->getStats()
            ];
        });
    }

    /**
     * GET /api/payments.php?fee_structure=true
     */
    public function feeStructure(): void
    {
        $this->handle(function () {
            return [
                'success' => true,
                'data' => $this->paymentModel->getFeeStructure()
            ];
        });
    }

    /**
     * GET /api/payments.php?id=X
     */
    public function show(int $id): void
    {
        $this->handle(function () use ($id) {
            $payment = $this->paymentModel->getById($id);

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'data' => $payment
            ];
        });
    }

    /**
     * POST /api/payments.php  (create)
     */
    public function store(): void
    {
        $this->handle(function () {
            $data = $this->input();

            $required = ['permit_id', 'amount', 'method'];
            $errors = [];
            foreach ($required as $field) {
                if (empty($data[$field]) && $data[$field] !== 0 && $data[$field] !== '0') {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }

            if (!is_numeric($data['amount'] ?? null) || (float)$data['amount'] < 0.01 || (float)$data['amount'] > 1000000) {
                $errors['amount'] = 'Amount must be between 0.01 and 1,000,000.00';
            }

            if (!empty($data['permit_id'])) {
                $permit = $this->permitModel->find($data['permit_id']);
                if (!$permit) {
                    $errors['permit_id'] = 'Permit not found';
                }
            }

            $validMethods = ['cash', 'gcash', 'paymaya', 'bank_transfer', 'over_the_counter'];
            if (!empty($data['method']) && !in_array($data['method'], $validMethods)) {
                $errors['method'] = 'Invalid payment method';
            }

            // Digital methods have a real gateway reference - it must be
            // supplied, not invented. Cash/OTC have no gateway, so the
            // model auto-generates one instead (see Payment::create()).
            $digitalMethods = ['gcash', 'paymaya', 'bank_transfer'];
            if (in_array($data['method'] ?? '', $digitalMethods, true) && empty($data['reference_number'])) {
                $errors['reference_number'] = 'Reference number is required for ' . str_replace('_', ' ', $data['method']) . ' payments';
            }

            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'data' => $errors,
                    'code' => 422
                ];
            }

            if (empty($data['paid_by'])) {
                $data['paid_by'] = $_SESSION['email'] ?? 'System';
            }

            $payment = $this->paymentModel->create($data);
            $createdPayment = $payment[0] ?? $payment;

            if (($createdPayment['status'] ?? null) === 'completed') {
                $this->permitModel->updateById($data['permit_id'], [
                    'paid' => true,
                    'payment_method' => $data['method'],
                    'status' => 'approved',
                ]);
                $this->autoGeneratePermitDocument((int)$data['permit_id'], $createdPayment['receipt_number'] ?? null);
            }

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'data' => $createdPayment,
                'code' => 201
            ];
        });
    }

    /**
     * PUT/PATCH /api/payments.php?id=X
     */
    public function update(int $id): void
    {
        $this->handle(function () use ($id) {
            $payment = $this->paymentModel->getById($id);
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                    'code' => 404
                ];
            }

            $data = $this->input();
            $updated = $this->paymentModel->update($id, $data);

            return [
                'success' => true,
                'message' => 'Payment updated successfully',
                'data' => $updated[0] ?? $updated
            ];
        });
    }

    /**
     * POST /api/payments.php?id=X&action=complete
     */
    public function complete(int $id): void
    {
        $this->handle(function () use ($id) {
            $payment = $this->paymentModel->getById($id);
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                    'code' => 404
                ];
            }

            if ($payment['status'] !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Only pending payments can be completed',
                    'code' => 400
                ];
            }

            $data = $this->input();
            $completionData = [
                'status' => 'completed',
                'paid_at' => date('Y-m-d H:i:s'),
                'payment_reference' => $data['reference_number'] ?? $payment['reference_number'] ?? null,
                'receipt_path' => 'receipts/' . $this->paymentModel->generateReceiptNumber() . '.pdf'
            ];

            $completed = $this->paymentModel->complete($id, $completionData);
            $updatedPayment = $completed[0] ?? $completed;

            $this->permitModel->updateById($payment['permit_id'], [
                'paid' => true,
                'payment_method' => $payment['method'],
                'status' => 'approved',
            ]);

            // Auto-generate Official Sanitation Permit document with unique QR Code
            $this->autoGeneratePermitDocument((int)$payment['permit_id'], $updatedPayment['receipt_number'] ?? null);

            return [
                'success' => true,
                'message' => 'Payment verified & official Sanitation Permit with QR Code generated successfully!',
                'data' => $updatedPayment
            ];
        });
    }

    /**
     * POST /api/payments.php?id=X&action=fail
     */
    public function fail(int $id): void
    {
        $this->handle(function () use ($id) {
            $payment = $this->paymentModel->getById($id);
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                    'code' => 404
                ];
            }

            $data = $this->input();
            $reason = $data['reason'] ?? 'Payment failed';
            $failed = $this->paymentModel->fail($id, $reason);

            return [
                'success' => true,
                'message' => 'Payment marked as failed',
                'data' => $failed[0] ?? $failed
            ];
        });
    }

    /**
     * POST /api/payments.php?id=X&action=refund
     */
    public function refund(int $id): void
    {
        $this->handle(function () use ($id) {
            $payment = $this->paymentModel->getById($id);
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                    'code' => 404
                ];
            }

            if ($payment['status'] !== 'completed') {
                return [
                    'success' => false,
                    'message' => 'Only completed payments can be refunded',
                    'code' => 400
                ];
            }

            $data = $this->input();
            $reason = $data['reason'] ?? 'Payment refunded';
            $refunded = $this->paymentModel->refund($id, $reason);

            return [
                'success' => true,
                'message' => 'Payment refunded successfully',
                'data' => $refunded[0] ?? $refunded
            ];
        });
    }

    /**
     * DELETE /api/payments.php?id=X
     */
    public function destroy(int $id): void
    {
        $this->handle(function () use ($id) {
            $payment = $this->paymentModel->getById($id);
            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                    'code' => 404
                ];
            }

            $this->paymentModel->delete($id);

            return [
                'success' => true,
                'message' => 'Payment deleted successfully'
            ];
        });
    }

    /**
     * Helper to auto-generate verified Sanitation Permit document with unique QR Code
     */
    private function autoGeneratePermitDocument(int $permitId, ?string $receiptNumber = null): void
    {
        try {
            require_once __DIR__ . '/../Models/PermitDocument.php';
            $docModel = new PermitDocument(Database::getInstance());
            $permit = $this->permitModel->find($permitId);
            if ($permit) {
                $permitCode = $permit['permit_id'] ?? ('SP-' . date('Y') . '-' . str_pad((string)$permit['id'], 3, '0', STR_PAD_LEFT));
                $applicantName = $permit['applicant'] ?? ($permit['business_name'] ?? 'Authorized Business Owner');
                $qrCode = 'QR-SAN-' . date('Y') . '-' . str_pad((string)$permit['id'], 4, '0', STR_PAD_LEFT);
                
                if (!$docModel->exists((int)$permit['id'], 'sanitary_permit', 'Sanitation_Permit_' . $permitCode . '.pdf')) {
                    $docModel->create([
                        'permit_id'     => (int)$permit['id'],
                        'applicant'     => $applicantName,
                        'document_type' => 'sanitary_permit',
                        'file_name'     => 'Sanitation_Permit_' . $permitCode . '.pdf',
                        'file_path'     => 'permits/sanitary_permit_' . $permitCode . '.pdf',
                        'file_size'     => 148500,
                        'file_type'     => 'pdf',
                        'mime_type'     => 'application/pdf',
                        'status'        => 'verified',
                        'verified'      => true,
                        'qr_code'       => $qrCode,
                        'expiry_date'   => date('Y-m-d', strtotime('+' . (class_exists('Settings') ? (int)Settings::get('modules.sanitation.permit_validity_days', 365) : 365) . ' days')),
                        'notes'         => 'Official Sanitation Permit with QR Code generated upon verified payment (OR #' . ($receiptNumber ?? 'N/A') . ')'
                    ]);
                }
            }
        } catch (\Throwable $e) {
            error_log('PermitDocument auto-generation notice: ' . $e->getMessage());
        }
    }
}