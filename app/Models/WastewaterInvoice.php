<?php
// app/Models/WastewaterInvoice.php

require_once __DIR__ . '/../../config/database.php';

class WastewaterInvoice
{
    private Database $db;
    private string $table = 'wastewater_invoices';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        if (empty($options['order'])) {
            $options['order'] = 'created_at.desc';
        }
        try {
            return $this->db->select($this->table, [], $options);
        } catch (Throwable $e) {
            error_log('WastewaterInvoice Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('WastewaterInvoice Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['invoice_id'])) {
            $data['invoice_id'] = $this->generateInvoiceId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        if (empty($data['invoice_date'])) {
            $data['invoice_date'] = date('Y-m-d');
        }
        // Auto-compute total if not set
        if (!isset($data['total_amount'])) {
            $data['total_amount'] = ((float)($data['amount'] ?? 0)) + ((float)($data['tax'] ?? 0));
        }
        return $this->db->insert($this->table, $data);
    }

    public function updateById(string|int $id, array $data): array
    {
        $data['updated_at'] = date('c');
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('WastewaterInvoice Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function countByStatus(): array
    {
        try {
            $all = $this->db->select($this->table, [], ['select' => 'status,total_amount']);
            $counts = ['pending' => 0, 'paid' => 0, 'overdue' => 0, 'cancelled' => 0, 'refunded' => 0];
            $revenue = 0;
            $outstanding = 0;
            foreach ($all as $row) {
                $s = $row['status'] ?? 'pending';
                if (isset($counts[$s])) $counts[$s]++;
                if ($s === 'paid') $revenue += (float)($row['total_amount'] ?? 0);
                if (in_array($s, ['pending', 'overdue'])) $outstanding += (float)($row['total_amount'] ?? 0);
            }
            $counts['total'] = array_sum($counts);
            $counts['revenue']     = round($revenue, 2);
            $counts['outstanding'] = round($outstanding, 2);
            return $counts;
        } catch (Throwable $e) {
            error_log('WastewaterInvoice Model Error (countByStatus): ' . $e->getMessage());
            return ['total' => 0, 'pending' => 0, 'paid' => 0, 'overdue' => 0, 'cancelled' => 0, 'refunded' => 0, 'revenue' => 0, 'outstanding' => 0];
        }
    }

    public function findActiveByTankId(string $tankId, ?string $serviceType = null): ?array
    {
        try {
            $all = $this->db->select($this->table, ['tank_id' => $tankId]);
            foreach ($all as $inv) {
                $status = strtolower($inv['status'] ?? '');
                if (in_array($status, ['pending', 'overdue', 'partially_paid'])) {
                    if ($serviceType === null || strcasecmp($inv['service_type'] ?? '', $serviceType) === 0) {
                        return $inv;
                    }
                }
            }
            return null;
        } catch (Throwable $e) {
            error_log('WastewaterInvoice Model Error (findActiveByTankId): ' . $e->getMessage());
            return null;
        }
    }

    public function findActiveByServiceRequestId(string|int $serviceRequestId): ?array
    {
        try {
            $all = $this->db->select($this->table, ['service_request_id' => $serviceRequestId]);
            foreach ($all as $inv) {
                $status = strtolower($inv['status'] ?? '');
                if (in_array($status, ['pending', 'overdue', 'partially_paid'])) {
                    return $inv;
                }
            }
            return null;
        } catch (Throwable $e) {
            error_log('WastewaterInvoice Model Error (findActiveByServiceRequestId): ' . $e->getMessage());
            return null;
        }
    }

    public function findByServiceRequestId(string|int $serviceRequestId): array
    {
        try {
            return $this->db->select($this->table, ['service_request_id' => $serviceRequestId]);
        } catch (Throwable $e) {
            error_log('WastewaterInvoice Model Error (findByServiceRequestId): ' . $e->getMessage());
            return [];
        }
    }

    public function generateInvoiceId(): string
    {
        return 'INV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }

    /**
     * Retrieve the active municipal fee structure (from config/fee_structure.json if present, or defaults).
     */
    public static function getFeeStructure(): array
    {
        $feeConfigFile = __DIR__ . '/../../config/fee_structure.json';
        $defaultFees = [
            ['category' => 'Desludging (Residential)', 'base_fee' => 1200.00, 'per_unit' => null, 'description' => 'Standard residential desludging service'],
            ['category' => 'Desludging (Commercial)',  'base_fee' => 2000.00, 'per_unit' => null, 'description' => 'Commercial establishment desludging'],
            ['category' => 'Septic Tank Inspection',   'base_fee' => 800.00,  'per_unit' => null, 'description' => 'Complete septic tank inspection'],
            ['category' => 'Septic Tank Maintenance',  'base_fee' => 1500.00, 'per_unit' => null, 'description' => 'Regular maintenance service'],
            ['category' => 'Installation (New Tank)',  'base_fee' => 5000.00, 'per_unit' => null, 'description' => 'New septic tank installation'],
            ['category' => 'Emergency Service',        'base_fee' => 2500.00, 'per_unit' => null, 'description' => 'Emergency call-out service'],
            ['category' => 'Pipe Inspection',          'base_fee' => 600.00,  'per_unit' => null, 'description' => 'CCTV pipe inspection'],
            ['category' => 'Wastewater Treatment',     'base_fee' => 3000.00, 'per_unit' => null, 'description' => 'Wastewater treatment service'],
        ];

        if (file_exists($feeConfigFile)) {
            $loaded = json_decode(file_get_contents($feeConfigFile), true);
            if (is_array($loaded) && !empty($loaded)) {
                return $loaded;
            }
        }
        return $defaultFees;
    }

    /**
     * Resolve the municipal fee, 6% tax, and total amount for a given service type.
     */
    public static function getFeeForServiceType(string $serviceType, ?string $tankCategory = null): array
    {
        $fees = self::getFeeStructure();
        $st = strtolower(trim($serviceType));
        $tc = strtolower(trim($tankCategory ?? ''));

        // Commercial desludging check
        if (str_contains($st, 'desludging') && (str_contains($st, 'commercial') || str_contains($tc, 'commercial'))) {
            foreach ($fees as $fee) {
                if (strcasecmp($fee['category'] ?? '', 'Desludging (Commercial)') === 0) {
                    $base = (float)$fee['base_fee'];
                    return [
                        'base_fee' => $base,
                        'tax'      => round($base * 0.06, 2),
                        'total'    => round($base * 1.06, 2),
                        'category' => $fee['category']
                    ];
                }
            }
            return ['base_fee' => 2000.00, 'tax' => 120.00, 'total' => 2120.00, 'category' => 'Desludging (Commercial)'];
        }

        // Exact category match
        foreach ($fees as $fee) {
            if (strcasecmp($fee['category'] ?? '', $st) === 0) {
                $base = (float)$fee['base_fee'];
                return [
                    'base_fee' => $base,
                    'tax'      => round($base * 0.06, 2),
                    'total'    => round($base * 1.06, 2),
                    'category' => $fee['category']
                ];
            }
        }

        // Keyword partial match
        $keywordCategories = [
            'desludging'   => 'Desludging (Residential)',
            'inspection'   => 'Septic Tank Inspection',
            'maintenance'  => 'Septic Tank Maintenance',
            'installation' => 'Installation (New Tank)',
            'emergency'    => 'Emergency Service',
            'pipe'         => 'Pipe Inspection',
            'treatment'    => 'Wastewater Treatment',
        ];

        foreach ($keywordCategories as $key => $targetCat) {
            if (str_contains($st, $key)) {
                foreach ($fees as $fee) {
                    if (strcasecmp($fee['category'] ?? '', $targetCat) === 0) {
                        $base = (float)$fee['base_fee'];
                        return [
                            'base_fee' => $base,
                            'tax'      => round($base * 0.06, 2),
                            'total'    => round($base * 1.06, 2),
                            'category' => $fee['category']
                        ];
                    }
                }
            }
        }

        // Fallback default
        return [
            'base_fee' => 1200.00,
            'tax'      => 72.00,
            'total'    => 1272.00,
            'category' => 'Desludging (Residential)'
        ];
    }
}
