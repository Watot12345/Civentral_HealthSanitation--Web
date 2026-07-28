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
                ]);
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

            $completionData = [
                'receipt_path' => 'receipts/' . $this->paymentModel->generateReceiptNumber() . '.pdf'
            ];

            $completed = $this->paymentModel->complete($id, $completionData);
            $updatedPayment = $completed[0] ?? $completed;

            $this->permitModel->updateById($payment['permit_id'], [
                'paid' => true,
                'payment_method' => $payment['method'],
            ]);

            return [
                'success' => true,
                'message' => 'Payment completed successfully',
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
}