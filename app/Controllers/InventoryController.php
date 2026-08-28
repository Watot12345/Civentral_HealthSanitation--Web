<?php
// app/Controllers/InventoryController.php

require_once __DIR__ . '/../../Core/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/VaccineInventory.php';

class InventoryController
{
    private VaccineInventory $model;

    private const VALID_ADJUSTMENTS = ['add', 'remove', 'set', 'reorder'];

    public function __construct(?VaccineInventory $model = null)
    {
        $this->model = $model ?? new VaccineInventory();
    }

    // -------------------------------------------------------
    // GET /api/inventory.php
    // -------------------------------------------------------
    public function index(): void
    {
        $items = $this->model->all();

        // Compute status server-side before returning
        foreach ($items as &$item) {
            $item['status'] = VaccineInventory::computeStatus(
                (int)$item['quantity'],
                (int)$item['minimum_stock']
            );
        }
        unset($item);

        Response::success('Vaccine inventory retrieved', $items, 200, ['total' => count($items)]);
    }

    // -------------------------------------------------------
    // POST /api/inventory.php              → add new stock
    // POST /api/inventory.php?action=adjust → adjust quantity
    // POST /api/inventory.php?action=reorder → log reorder
    // -------------------------------------------------------
    public function store(): void
    {
        $action = $_GET['action'] ?? '';

        if ($action === 'adjust' || $action === 'reorder') {
            $this->adjust($action);
            return;
        }

        // --- Add new stock entry ---
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $errors = $this->validate($data, ['vaccine_name', 'batch_number', 'quantity', 'expiry_date']);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $record = $this->prepareData($data);

        try {
            $result = $this->model->create($record);
            $inserted = is_array($result) && !empty($result[0]) ? $result[0] : $result;
            // Inject computed status before returning
            if (isset($inserted['quantity'])) {
                $inserted['status'] = VaccineInventory::computeStatus(
                    (int)$inserted['quantity'],
                    (int)($inserted['minimum_stock'] ?? 20)
                );
            }
            Response::success('Stock added successfully', $inserted, 201);
        } catch (Throwable $e) {
            error_log('InventoryController::store error: ' . $e->getMessage());
            Response::error('Failed to add stock: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // PUT /api/inventory.php?id=X
    // -------------------------------------------------------
    public function update(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('Inventory record not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($data)) {
            Response::error('No data provided', 400);
        }

        $errors = $this->validatePartial($data);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $record = $this->prepareData($data, true);

        try {
            $result = $this->model->update($id, $record);
            $updated = is_array($result) && !empty($result[0]) ? $result[0] : array_merge($existing, $record);
            $updated['status'] = VaccineInventory::computeStatus(
                (int)($updated['quantity']      ?? $existing['quantity']),
                (int)($updated['minimum_stock'] ?? $existing['minimum_stock'])
            );
            Response::success('Inventory updated successfully', $updated);
        } catch (Throwable $e) {
            error_log('InventoryController::update error: ' . $e->getMessage());
            Response::error('Failed to update inventory: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // DELETE /api/inventory.php?id=X
    // -------------------------------------------------------
    public function destroy(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('Inventory record not found', 404);
        }

        if ($this->model->delete($id)) {
            Response::success('Inventory record deleted successfully');
        } else {
            Response::error('Failed to delete inventory record', 500);
        }
    }

    // -------------------------------------------------------
    // Internal – adjust / reorder stock
    // -------------------------------------------------------
    private function adjust(string $defaultType): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $id = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : null;
        if (!$id) {
            Response::error('Inventory ID is required for adjustment', 400);
        }

        $type     = $data['adjustment_type'] ?? $defaultType;
        $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
        $reason   = trim(strip_tags($data['reason'] ?? ''));

        if (!in_array($type, self::VALID_ADJUSTMENTS)) {
            Response::error('Invalid adjustment type. Must be: ' . implode(', ', self::VALID_ADJUSTMENTS), 422);
        }
        if ($quantity < 1) {
            Response::error('Quantity must be at least 1', 422);
        }

        try {
            $result   = $this->model->adjust($id, $type, $quantity, $reason);
            $existing = $this->model->find($id);
            $status   = $existing
                ? VaccineInventory::computeStatus((int)$existing['quantity'], (int)$existing['minimum_stock'])
                : 'in_stock';

            Response::success('Stock adjusted successfully', array_merge(
                is_array($result) && !empty($result[0]) ? $result[0] : [],
                ['status' => $status]
            ));
        } catch (Throwable $e) {
            error_log('InventoryController::adjust error: ' . $e->getMessage());
            Response::error('Failed to adjust stock: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // Data preparation
    // -------------------------------------------------------
    private function prepareData(array $data, bool $isUpdate = false): array
    {
        $record = [];

        if (isset($data['vaccine_name'])) {
            $record['vaccine_name'] = trim(strip_tags($data['vaccine_name']));
        }
        if (isset($data['batch_number']) && !empty(trim($data['batch_number']))) {
            $record['batch_number'] = trim(strip_tags($data['batch_number']));
        } elseif (!$isUpdate) {
            $vClean = isset($record['vaccine_name']) ? strtoupper(preg_replace('/[^A-Z]/', '', $record['vaccine_name'])) : 'VAC';
            $prefix = substr($vClean, 0, 4) ?: 'VAC';
            $record['batch_number'] = $prefix . '-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));
        }

        if (isset($data['quantity']))        $record['quantity']         = max(0, (int)$data['quantity']);
        if (isset($data['minimum_stock']))   $record['minimum_stock']    = max(0, (int)$data['minimum_stock']);
        if (isset($data['expiry_date']))     $record['expiry_date']      = $this->sanitizeDate($data['expiry_date']);
        if (isset($data['temperature']))     $record['temperature']      = (float)$data['temperature'];
        if (isset($data['storage_location']))$record['storage_location'] = !empty(trim($data['storage_location'])) ? trim(strip_tags($data['storage_location'])) : 'Refrigerator A1';
        
        $supplier = isset($data['supplier']) ? trim(strip_tags($data['supplier'])) : '';
        $record['supplier'] = !empty($supplier) ? $supplier : 'DOH Central Supply Office';

        if (isset($data['unit']))            $record['unit']             = !empty(trim($data['unit'])) ? trim(strip_tags($data['unit'])) : 'doses';
        if (isset($data['received_date']))   $record['received_date']    = $this->sanitizeDate($data['received_date'] ?? 'now');

        return $record;
    }

    // -------------------------------------------------------
    // Validation helpers
    // -------------------------------------------------------
    private function validate(array $data, array $required): array
    {
        $errors = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (string)$data[$field] === '') {
                $errors[] = "{$field} is required";
            }
        }
        return array_merge($errors, $this->validatePartial($data));
    }

    private function validatePartial(array $data): array
    {
        $errors = [];
        if (isset($data['quantity']) && ((int)$data['quantity'] < 0 || (int)$data['quantity'] > 99999999)) {
            $errors[] = 'Quantity must be between 0 and 99,999,999';
        }
        if (isset($data['minimum_stock']) && ((int)$data['minimum_stock'] < 1 || (int)$data['minimum_stock'] > 99999999)) {
            $errors[] = 'Minimum stock must be between 1 and 99,999,999';
        }
        if (isset($data['temperature']) && ((float)$data['temperature'] < -999 || (float)$data['temperature'] > 999)) {
            $errors[] = 'Temperature must be between -999 and 999 °C';
        }
        return $errors;
    }

    private function sanitizeDate(mixed $value): string
    {
        $ts = strtotime((string)$value);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
