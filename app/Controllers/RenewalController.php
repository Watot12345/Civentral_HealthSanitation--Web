<?php
// app/Controllers/RenewalController.php

require_once __DIR__ . '/../../Core/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Renewal.php';
require_once __DIR__ . '/../Models/Permit.php';

class RenewalController
{
    private Renewal $renewalModel;
    private Permit $permitModel;

    private const DEFAULT_LIMIT = 5;
    private const MAX_LIMIT = 100;
    private const DEFAULT_PAGE = 1;
    private const VALID_STATUSES = ['pending', 'under_review', 'approved', 'rejected'];
    private const GRACE_PERIOD_DAYS = 30;
    private const LATE_FEE_PERCENTAGE = 25;
    private const INTEREST_RATE = 2;

    public function __construct(
        ?Renewal $renewalModel = null,
        ?Permit $permitModel = null
    ) {
        $this->renewalModel = $renewalModel ?? new Renewal();
        $this->permitModel = $permitModel ?? new Permit();
    }

    private function getQueryParam(string $key, mixed $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        if (is_array($value)) {
            return $default;
        }
        return (string)$value;
    }

    private function getQueryParamOrNull(string $key): ?string
    {
        $value = $_GET[$key] ?? null;
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }
        return (string)$value;
    }

    public function index(): void
    {
        $renewals = $this->renewalModel->all();
        Response::success('Renewals retrieved successfully', $renewals, 200, [
            'total' => count($renewals)
        ]);
    }

    public function paginated(): void
    {
        $page = max(1, (int)($this->getQueryParam('page', '1')));
        $limit = max(1, min(self::MAX_LIMIT, (int)($this->getQueryParam('limit', (string)self::DEFAULT_LIMIT))));
        $offset = ($page - 1) * $limit;

        $status = $this->getQueryParamOrNull('status');
        $search = $this->getQueryParamOrNull('q');
        $dateFrom = $this->getQueryParamOrNull('date_from');
        $dateTo = $this->getQueryParamOrNull('date_to');
        $dateFilter = [];
        if ($dateFrom !== null) {
            $dateFilter['gte'] = $dateFrom;
        }
        if ($dateTo !== null) {
            $dateFilter['lte'] = $dateTo;
        }

        $criteria = [
            'status' => $status,
            'search' => $search
        ];
        if (!empty($dateFilter)) {
            $criteria['date_applied'] = $dateFilter;
        }

        $renewals = $this->renewalModel->search($criteria, $limit, $offset);

        $countFilters = $status ? ['status' => $status] : [];
        if (!empty($dateFilter)) {
            $countFilters['date_applied'] = $dateFilter;
        }
        $total = $this->renewalModel->count($countFilters);
        $totalPages = max(1, ceil($total / $limit));

        Response::success('Renewals retrieved', $renewals, 200, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
            'offset' => $offset
        ]);
    }

    public function show(string $id): void
    {
        $renewal = $this->renewalModel->find($id);

        if (!$renewal) {
            Response::error('Renewal not found', 404);
        }

        Response::success('Renewal retrieved successfully', $renewal);
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $errors = $this->validate($data, ['permit_id', 'renewal_fee', 'payment_method']);

            if (!empty($errors)) {
                Response::error('Validation failed', 422, $errors);
            }

            // Get permit details to populate applicant and business_type
            $permit = $this->permitModel->find((int)$data['permit_id']);
            if (!$permit) {
                Response::error('Permit not found', 404);
            }

            $preparedData = $this->prepareDbData($data, $permit);
            $result = $this->renewalModel->create($preparedData);

            if (empty($result)) {
                Response::error('Failed to create renewal', 500);
            }

            Response::success('Renewal application submitted successfully', $result, 201);
        } catch (\Throwable $e) {
            error_log('Renewal store error: ' . $e->getMessage());
            Response::error('Failed to submit renewal: ' . $e->getMessage(), 500);
        }
    }

    public function update(string $id): void
    {
        $renewal = $this->renewalModel->find($id);

        if (!$renewal) {
            Response::error('Renewal not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data)) {
            Response::error('No data provided', 400);
        }

        $result = $this->renewalModel->update($id, $data);
        Response::success('Renewal updated successfully', $result);
    }

    public function approve(string $id): void
    {
        $renewal = $this->renewalModel->find($id);

        if (!$renewal) {
            Response::error('Renewal not found', 404);
        }

        $validityDays = class_exists('Settings') ? (int)Settings::get('modules.sanitation.permit_validity_days', 365) : 365;
        $newExpiry = date('Y-m-d', strtotime("+{$validityDays} days"));

        $updateData = [
            'status' => 'approved',
            'date_approved' => date('Y-m-d'),
            'new_expiry_date' => $newExpiry
        ];

        $result = $this->renewalModel->update($id, $updateData);

        if (empty($result)) {
            Response::error('Failed to approve renewal', 500);
        }

        // Get the permit's string ID (e.g., "SP-1040") for history tracking
        $permit = $this->permitModel->find((int)$renewal['permit_id']);
        $permitIdString = $permit ? ($permit['permit_id'] ?? (string)$renewal['permit_id']) : (string)$renewal['permit_id'];

        // Add to renewal history
        $this->renewalModel->addHistory([
            'permit_id' => $permitIdString,
            'applicant' => $renewal['applicant'],
            'renewal_date' => date('Y-m-d'),
            'fee_paid' => $renewal['renewal_fee'],
            'new_expiry' => $newExpiry,
            'status' => 'completed'
        ]);

        Response::success('Renewal approved successfully', $result);
    }

    public function reject(string $id): void
    {
        $renewal = $this->renewalModel->find($id);

        if (!$renewal) {
            Response::error('Renewal not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $reason = $data['rejection_reason'] ?? '';

        if (empty($reason)) {
            Response::error('Rejection reason is required', 422);
        }

        $result = $this->renewalModel->update($id, [
            'status' => 'rejected',
            'notes' => $reason
        ]);

        Response::success('Renewal rejected', $result);
    }

    public function destroy(string $id): void
    {
        $renewal = $this->renewalModel->find($id);

        if (!$renewal) {
            Response::error('Renewal not found', 404);
        }

        $success = $this->renewalModel->delete($id);

        if ($success) {
            Response::success('Renewal deleted successfully');
        } else {
            Response::error('Failed to delete renewal', 500);
        }
    }

    public function stats(): void
    {
        $stats = $this->renewalModel->getStats();
        $expiredPermits = count($this->renewalModel->getExpiredPermits());
        $totalRevenue = $this->renewalModel->getTotalRevenue();
        $expiringSoon = $this->renewalModel->getExpiringSoon(self::GRACE_PERIOD_DAYS);

        Response::success('Statistics retrieved successfully', [
            'total' => $stats['total'],
            'pending' => $stats['pending'] + $stats['under_review'],
            'approved' => $stats['approved'],
            'rejected' => $stats['rejected'],
            'expired_permits' => $expiredPermits,
            'revenue' => $totalRevenue,
            'expiring_soon_count' => count($expiringSoon),
            'grace_period_days' => self::GRACE_PERIOD_DAYS,
            'late_fee_percentage' => self::LATE_FEE_PERCENTAGE,
            'interest_rate' => self::INTEREST_RATE
        ]);
    }

    public function history(): void
    {
        $permitId = $this->getQueryParamOrNull('permit_id');

        if ($permitId) {
            $history = $this->renewalModel->getHistoryByPermitId($permitId);
        } else {
            $history = $this->renewalModel->getAllHistory();
        }

        Response::success('Renewal history retrieved', $history, 200, [
            'total' => count($history)
        ]);
    }

    public function expiringSoon(): void
    {
        $days = max(1, (int)($this->getQueryParam('days', (string)self::GRACE_PERIOD_DAYS)));
        $permits = $this->renewalModel->getExpiringSoon($days);

        Response::success('Expiring permits retrieved', $permits, 200, [
            'total' => count($permits),
            'grace_period_days' => self::GRACE_PERIOD_DAYS
        ]);
    }

    public function getPermits(): void
    {
        $permits = $this->permitModel->all(['order' => 'created_at.desc']);
        Response::success('Permits retrieved', $permits, 200, [
            'total' => count($permits)
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function prepareDbData(array $data, ?array $permit = null): array
    {
        $dbData = [];

        // Auto-generate payment reference for ALL payment methods
        $paymentMethod = $data['payment_method'] ?? '';
        $dbData['payment_reference'] = $this->generatePaymentReference($paymentMethod);

        $stringFields = ['renewal_id', 'payment_method', 'notes', 'documents'];
        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = $this->sanitizeString($data[$field]);
            }
        }

        $intFields = ['permit_id'];
        foreach ($intFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = $this->sanitizeInteger($data[$field]);
            }
        }

        $floatFields = ['renewal_fee'];
        foreach ($floatFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = (float)$data[$field];
            }
        }

        // Populate from permit if available
        if ($permit) {
            $dbData['permit_id'] = (int)$permit['id'];
            $dbData['applicant'] = $permit['applicant'] ?? '';
            $dbData['business_type'] = $permit['business_type'] ?? '';
            $dbData['current_fee'] = (float)($permit['fee'] ?? 0);

            if (!isset($dbData['renewal_fee']) || empty($data['renewal_fee'])) {
                $dbData['renewal_fee'] = (float)($permit['fee'] ?? 0);
            }
        }

        return $dbData;
    }

    /**
     * Auto-generate a unique payment reference for any renewal application,
     * regardless of payment method. Uses the same pattern as the Payments module.
     *
     * Prefix mapping:
     *   Cash              -> CSH
     *   GCash             -> GCH
     *   Bank Transfer     -> BTR
     *   Over-the-Counter  -> OTC
     *   PayMaya           -> PYM
     *   (default)         -> REN
     */
    private function generatePaymentReference(string $method): string
    {
        $prefixMap = [
            'cash'              => 'CSH',
            'gcash'             => 'GCH',
            'bank transfer'     => 'BTR',
            'over-the-counter'  => 'OTC',
            'over_the_counter'  => 'OTC',
            'paymaya'           => 'PYM',
        ];

        $key = strtolower(trim($method));
        $prefix = $prefixMap[$key] ?? 'REN';

        return $prefix . '-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    private function sanitizeString(mixed $value): string
    {
        return trim(strip_tags((string)$value));
    }

    private function sanitizeInteger(mixed $value): int
    {
        return (int)$value;
    }

    private function validate(array $data, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (empty($data[$field]) && $data[$field] !== '0')) {
                $errors[] = "{$field} is required";
            }
        }

        if (isset($data['status']) && !in_array((string)$data['status'], self::VALID_STATUSES)) {
            $errors[] = "Invalid status. Must be one of: " . implode(', ', self::VALID_STATUSES);
        }

        if (isset($data['renewal_fee'])) {
            if (!Renewal::isValidRenewalFee($data['renewal_fee'])) {
                $errors[] = 'renewal_fee must be a valid number';
            } else {
                $fee = (float)$data['renewal_fee'];
                if ($fee < 0 || $fee > (float)Renewal::MAX_RENEWAL_FEE) {
                    $errors[] = 'renewal_fee must not exceed 999999999999.00';
                }
            }
        }

        return $errors;
    }
}