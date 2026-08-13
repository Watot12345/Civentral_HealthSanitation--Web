<?php
// app/Models/VaccineInventory.php

require_once __DIR__ . '/../../config/database.php';

class VaccineInventory
{
    private Database $db;
    private string $table        = 'vaccine_inventory';
    private string $logTable     = 'inventory_log';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // -------------------------------------------------------
    // Status computation – single source of truth
    // -------------------------------------------------------

    /**
     * Compute stock status from quantity and minimum_stock.
     * critical  = qty > 0 but <= half of min
     * low_stock = qty > half of min but <= min
     * in_stock  = qty > min
     * out_of_stock = qty <= 0
     */
    public static function computeStatus(int $qty, int $min): string
    {
        if ($qty <= 0)          return 'out_of_stock';
        if ($qty <= ($min / 2)) return 'critical';
        if ($qty <= $min)       return 'low_stock';
        return 'in_stock';
    }

    // -------------------------------------------------------
    // CRUD
    // -------------------------------------------------------

    public function all(): array
    {
        try {
            return $this->db->select($this->table, [], ['order' => 'vaccine_name.asc']);
        } catch (Throwable $e) {
            error_log('VaccineInventory::all error: ' . $e->getMessage());
            return [];
        }
    }

    public function find(int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('VaccineInventory::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Insert a new inventory record.
     * Status field is never stored – always computed on read.
     */
    public function create(array $data): array
    {
        // Strip any client-sent status; compute on read
        unset($data['status']);
        return $this->db->insert($this->table, $data);
    }

    /**
     * Update an existing inventory record.
     */
    public function update(int $id, array $data): array
    {
        unset($data['status']);
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Adjust quantity by delta (+/-) or set absolute value, then log the change.
     *
     * @param int    $id             Inventory record ID
     * @param string $adjustmentType 'add' | 'remove' | 'set' | 'reorder'
     * @param int    $quantity       Units to add/remove/set
     * @param string $reason         Human-readable reason
     */
    public function adjust(int $id, string $adjustmentType, int $quantity, string $reason = ''): array
    {
        $existing = $this->find($id);
        if (!$existing) {
            throw new RuntimeException("Inventory record {$id} not found.");
        }

        $currentQty = (int)$existing['quantity'];

        switch ($adjustmentType) {
            case 'add':
            case 'reorder':
                $newQty = $currentQty + $quantity;
                break;
            case 'remove':
                $newQty = max(0, $currentQty - $quantity);
                break;
            case 'set':
                $newQty = max(0, $quantity);
                break;
            default:
                throw new InvalidArgumentException("Invalid adjustment type: {$adjustmentType}");
        }

        $updated = $this->db->update($this->table, ['quantity' => $newQty], ['id' => $id]);

        // Log the adjustment
        $this->logAdjustment([
            'inventory_id'    => $id,
            'adjustment_type' => $adjustmentType,
            'quantity'        => $quantity,
            'reason'          => trim($reason),
        ]);

        return $updated;
    }

    /**
     * Write a record to the inventory_log table.
     */
    public function logAdjustment(array $data): void
    {
        try {
            $this->db->insert($this->logTable, $data);
        } catch (Throwable $e) {
            error_log('VaccineInventory::logAdjustment error: ' . $e->getMessage());
        }
    }

    /**
     * Delete an inventory record by ID.
     */
    public function delete(int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('VaccineInventory::delete error: ' . $e->getMessage());
            return false;
        }
    }
}
