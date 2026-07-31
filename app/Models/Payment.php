<?php
// app/Models/Payment.php

require_once __DIR__ . '/../../config/database.php';

class Payment
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all payments with optional filters
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $options = [
            'order' => 'created_at.desc',
            'offset' => ($page - 1) * $limit,
            'limit' => $limit
        ];

        $apiFilters = [];
        
        if (!empty($filters['status'])) {
            $apiFilters['status'] = $filters['status'];
        }
        if (!empty($filters['method'])) {
            $apiFilters['method'] = $filters['method'];
        }
        if (!empty($filters['permit_id'])) {
            $apiFilters['permit_id'] = $filters['permit_id'];
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $options['or'] = "(payment_id.ilike.*{$search}*,reference_number.ilike.*{$search}*)";
            
            // Also search by permit applicant name
            $options['select'] = '*,permits!inner(applicant)';
        }
        $dateFrom = $filters['date_from'] ?? '';
        $dateTo = $filters['date_to'] ?? '';

        $options['select'] = $options['select'] ?? '*,permits(permit_id,applicant,business_name)';

        if ($dateFrom || $dateTo) {
            // Fetch the filtered base set first because the query builder cannot
            // represent two bounds for the same column in one filter array.
            unset($options['offset'], $options['limit']);
            $allPayments = $this->db->select('payments', $apiFilters, $options);
            $payments = array_values(array_filter($allPayments, function (array $payment) use ($dateFrom, $dateTo): bool {
                $paymentDate = substr((string)($payment['created_at'] ?? ''), 0, 10);
                return (!$dateFrom || $paymentDate >= $dateFrom) && (!$dateTo || $paymentDate <= $dateTo);
            }));
            $total = count($payments);
            $payments = array_slice($payments, ($page - 1) * $limit, $limit);
        } else {
            $total = $this->db->count('payments', $apiFilters);
            $payments = $this->db->select('payments', $apiFilters, $options);
        }

        return [
            'payments' => $payments,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit),
            'limit' => $limit
        ];
    }

    /**
     * Get single payment by ID
     */
    public function getById(int $id): ?array
    {
        $filters = ['id' => $id];
        $options = ['select' => '*,permits(permit_id,applicant,business_name,business_type)'];
        $payments = $this->db->select('payments', $filters, $options);
        
        return !empty($payments) ? $payments[0] : null;
    }

    /**
     * Get payment by payment_id
     */
    public function getByPaymentId(string $paymentId): ?array
    {
        $filters = ['payment_id' => $paymentId];
        $options = ['select' => '*,permits(permit_id,applicant,business_name)'];
        $payments = $this->db->select('payments', $filters, $options);
        
        return !empty($payments) ? $payments[0] : null;
    }

    /**
     * Get payments for a specific permit
     */
    public function getByPermitId(int $permitId): array
    {
        $filters = ['permit_id' => $permitId];
        $options = ['order' => 'created_at.desc'];
        
        return $this->db->select('payments', $filters, $options);
    }

    /**
     * Create new payment
     *
     * Offline methods (cash, over_the_counter) have no external gateway,
     * so we auto-generate a reference number for them. Digital methods
     * (gcash, paymaya, bank_transfer) must arrive with a real reference
     * from the gateway - PaymentController::store() enforces that before
     * this is ever called.
     */
    public function create(array $data): array
    {
        $data['payment_id'] = $this->generatePaymentId();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        $method = $data['method'] ?? '';
        $isOfflineMethod = in_array($method, ['cash', 'over_the_counter'], true);

        if ($isOfflineMethod) {
            if (empty($data['reference_number'])) {
                $data['reference_number'] = $this->generateReferenceNumber($method);
            }
            $data['status'] = 'completed';
            $data['paid_at'] = date('Y-m-d H:i:s');
        } else {
            $data['status'] = $data['status'] ?? 'pending';
        }
        
        return $this->db->insert('payments', $data, true);
    }

    /**
     * Update payment
     */
    public function update(int $id, array $data): array
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update('payments', $data, ['id' => $id], true);
    }

    /**
     * Complete a payment
     */
    public function complete(int $id, array $completionData = []): array
    {
        $updateData = array_merge([
            'status' => 'completed',
            'paid_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], $completionData);
        
        return $this->db->update('payments', $updateData, ['id' => $id], true);
    }

    /**
     * Mark payment as failed
     */
    public function fail(int $id, string $reason = null): array
    {
        $updateData = [
            'status' => 'failed',
            'notes' => $reason,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('payments', $updateData, ['id' => $id], true);
    }

    /**
     * Refund a payment
     */
    public function refund(int $id, string $reason = null): array
    {
        $updateData = [
            'status' => 'refunded',
            'notes' => $reason ?? 'Payment refunded',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->update('payments', $updateData, ['id' => $id], true);
    }

    /**
     * Delete payment
     */
    public function delete(int $id): bool
    {
        $this->db->delete('payments', ['id' => $id], true);
        return true;
    }

    /**
     * Get payment statistics
     */
    public function getStats(array $filters = []): array
    {
        $stats = [
            'total' => $this->db->count('payments', $filters),
            'completed' => $this->db->count('payments', array_merge($filters, ['status' => 'completed'])),
            'pending' => $this->db->count('payments', array_merge($filters, ['status' => 'pending'])),
            'failed' => $this->db->count('payments', array_merge($filters, ['status' => 'failed'])),
            'refunded' => $this->db->count('payments', array_merge($filters, ['status' => 'refunded'])),
        ];

        // Get total revenue from completed payments
        $completedPayments = $this->db->select('payments', 
            array_merge($filters, ['status' => 'completed']), 
            ['select' => 'amount']
        );
        
        $stats['total_revenue'] = array_sum(array_column($completedPayments, 'amount'));

        // Get revenue by method
        $allCompleted = $this->db->select('payments', 
            ['status' => 'completed'], 
            ['select' => 'method,amount']
        );
        
        $revenueByMethod = [];
        foreach ($allCompleted as $payment) {
            $method = $payment['method'];
            if (!isset($revenueByMethod[$method])) {
                $revenueByMethod[$method] = 0;
            }
            $revenueByMethod[$method] += $payment['amount'];
        }
        $stats['revenue_by_method'] = $revenueByMethod;

        // Get pending permits count
        $stats['pending_permits'] = $this->db->count('payments', [
            'status' => 'pending'
        ]);

        return $stats;
    }

    /**
     * Get fee structure
     */
    public function getFeeStructure(): array
    {
        // This could be from a database table or configuration
        return [
            ['category' => 'Food Establishment', 'base_fee' => 1500.00, 'inspection_fee' => 500.00, 'total' => 2000.00],
            ['category' => 'Market Vendor', 'base_fee' => 800.00, 'inspection_fee' => 300.00, 'total' => 1100.00],
            ['category' => 'Bakery', 'base_fee' => 1200.00, 'inspection_fee' => 400.00, 'total' => 1600.00],
            ['category' => 'Recreational Facility', 'base_fee' => 2000.00, 'inspection_fee' => 600.00, 'total' => 2600.00],
            ['category' => 'Retail Store', 'base_fee' => 1000.00, 'inspection_fee' => 350.00, 'total' => 1350.00],
            ['category' => 'Pharmacy', 'base_fee' => 1800.00, 'inspection_fee' => 500.00, 'total' => 2300.00],
            ['category' => 'Agricultural', 'base_fee' => 900.00, 'inspection_fee' => 300.00, 'total' => 1200.00],
            ['category' => 'Office/Commercial', 'base_fee' => 2500.00, 'inspection_fee' => 700.00, 'total' => 3200.00],
            ['category' => 'Hotel/Lodging', 'base_fee' => 3000.00, 'inspection_fee' => 800.00, 'total' => 3800.00],
        ];
    }

    /**
     * Generate unique payment ID
     */
    private function generatePaymentId(): string
    {
        $count = $this->db->count('payments') + 1;
        return 'PAY-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a reference number for offline payments (cash / over-the-counter)
     * that have no external gateway to supply one. Uses uniqid() rather than
     * a running count to avoid unique-constraint collisions under concurrent
     * requests (a count-based scheme can race and produce duplicates).
     */
    private function generateReferenceNumber(string $method): string
    {
        $prefix = $method === 'cash' ? 'CSH' : 'OTC';
        return $prefix . '-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Generate receipt number
     */
    public function generateReceiptNumber(): string
    {
        $count = $this->db->count('payments', ['status' => 'completed']) + 1;
        return 'RCP-' . date('Ymd') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}