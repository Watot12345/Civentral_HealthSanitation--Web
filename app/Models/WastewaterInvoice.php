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

    public function generateInvoiceId(): string
    {
        return 'INV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
