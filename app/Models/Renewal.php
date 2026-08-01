<?php
// app/Models/Renewal.php

require_once __DIR__ . '/../../config/database.php';

class Renewal
{
    public const MAX_RENEWAL_FEE = '999999999999.00';

    public static function isValidRenewalFee(mixed $value): bool
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return false;
        }

        $fee = trim((string)$value);
        if (!preg_match('/^(\d{1,12})(?:\.(\d{1,2}))?$/', $fee, $matches)) {
            return false;
        }

        $whole = ltrim($matches[1], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[2] ?? '', 2, '0');

        return strlen($whole) < 12
            || $whole < '999999999999'
            || $fraction === '00';
    }

    private Database $db;
    private string $table = 'renewals';
    private string $historyTable = 'renewal_history';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        if (empty($options['order'])) {
            $options['order'] = 'date_applied.desc';
        }
        try {
            return $this->db->select($this->table, [], $options);
        } catch (Throwable $e) {
            error_log('Renewal Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Renewal Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (!isset($data['renewal_fee']) || !self::isValidRenewalFee($data['renewal_fee'])) {
            throw new InvalidArgumentException('Renewal fee must be a valid number.');
        }

        $renewalFee = (float)$data['renewal_fee'];
        $data['renewal_fee'] = $renewalFee;

        if (empty($data['renewal_id'])) {
            $data['renewal_id'] = $this->generateRenewalId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        if (empty($data['date_applied'])) {
            $data['date_applied'] = date('Y-m-d');
        }
        if (empty($data['documents'])) {
            $data['documents'] = '[]';
        }
        return $this->db->insert($this->table, $data);
    }

    public function update(string|int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function delete(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('Renewal Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function search(array $criteria = [], int $limit = 10, int $offset = 0): array
    {
        $filters = [];
        $options = [
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'date_applied.desc'
        ];

        if (!empty($criteria['status'])) {
            $filters['status'] = $criteria['status'];
        }

        if (!empty($criteria['permit_id'])) {
            $filters['permit_id'] = $criteria['permit_id'];
        }

        if (!empty($criteria['date_applied']) && is_array($criteria['date_applied'])) {
            $filters['date_applied'] = $criteria['date_applied'];
        }

        // For search, we use 'or' to search across multiple fields
        if (!empty($criteria['search'])) {
            $searchTerm = strtolower($criteria['search']);
            $options['or'] = "(applicant.ilike.%{$searchTerm}%,renewal_id.ilike.%{$searchTerm}%)";
        }

        try {
            return $this->db->select($this->table, $filters, $options);
        } catch (Throwable $e) {
            error_log('Renewal Model Error (search): ' . $e->getMessage());
            return [];
        }
    }

    public function count(array $filters = []): int
    {
        try {
            return $this->db->count($this->table, $filters);
        } catch (Throwable $e) {
            error_log('Renewal Model Error (count): ' . $e->getMessage());
            return 0;
        }
    }

    public function getStats(): array
    {
        try {
            $all = $this->all();
            $total = count($all);
            $pending = 0;
            $underReview = 0;
            $approved = 0;
            $rejected = 0;

            foreach ($all as $r) {
                switch ($r['status'] ?? '') {
                    case 'pending':
                        $pending++;
                        break;
                    case 'under_review':
                        $underReview++;
                        break;
                    case 'approved':
                        $approved++;
                        break;
                    case 'rejected':
                        $rejected++;
                        break;
                }
            }

            return [
                'total' => $total,
                'pending' => $pending,
                'under_review' => $underReview,
                'approved' => $approved,
                'rejected' => $rejected
            ];
        } catch (Throwable $e) {
            error_log('Renewal Model Error (getStats): ' . $e->getMessage());
            return [
                'total' => 0, 'pending' => 0, 'under_review' => 0,
                'approved' => 0, 'rejected' => 0
            ];
        }
    }

    public function getExpiringSoon(int $days = 30): array
    {
        try {
            $cutoff = date('Y-m-d', strtotime("+{$days} days"));
            $today = date('Y-m-d');
            // Get active permits that are expiring soon
            $permitsDb = Database::getInstance();
            return $permitsDb->select('permits', [
                'status' => 'active',
                'expiry_date' => [
                    'gte' => $today,
                    'lte' => $cutoff
                ]
            ], ['order' => 'expiry_date.asc']);
        } catch (Throwable $e) {
            error_log('Renewal Model Error (getExpiringSoon): ' . $e->getMessage());
            return [];
        }
    }

    public function getExpiredPermits(): array
    {
        try {
            return $this->db->select('permits', ['status' => 'expired']);
        } catch (Throwable $e) {
            error_log('Renewal Model Error (getExpiredPermits): ' . $e->getMessage());
            return [];
        }
    }

    public function getTotalRevenue(): float
    {
        try {
            $history = $this->db->select($this->historyTable);
            $total = 0;
            foreach ($history as $h) {
                $total += (float)($h['fee_paid'] ?? 0);
            }
            return $total;
        } catch (Throwable $e) {
            error_log('Renewal Model Error (getTotalRevenue): ' . $e->getMessage());
            return 0;
        }
    }

    // ============================================================
    // RENEWAL HISTORY METHODS
    // ============================================================
    public function getHistoryByPermitId(string $permitId): array
    {
        try {
            return $this->db->select($this->historyTable, [
                'permit_id' => $permitId
            ], ['order' => 'renewal_date.desc']);
        } catch (Throwable $e) {
            error_log('Renewal Model Error (getHistoryByPermitId): ' . $e->getMessage());
            return [];
        }
    }

    public function addHistory(array $data): array
    {
        return $this->db->insert($this->historyTable, $data);
    }

    public function getAllHistory(array $options = []): array
    {
        if (empty($options['order'])) {
            $options['order'] = 'renewal_date.desc';
        }
        try {
            return $this->db->select($this->historyTable, [], $options);
        } catch (Throwable $e) {
            error_log('Renewal Model Error (getAllHistory): ' . $e->getMessage());
            return [];
        }
    }

    public function generateRenewalId(): string
    {
        return 'REN-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}