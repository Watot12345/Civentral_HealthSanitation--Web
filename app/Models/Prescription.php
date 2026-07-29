<?php

require_once __DIR__ . '/../../config/database.php';

class Prescription
{
    private Database $db;
    private string $table = 'prescriptions';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        if (empty($options['order'])) {
            $options['order'] = 'created_at.desc';
        }
        return $this->db->select($this->table, [], $options);
    }

    public function find(string|int $id): ?array
    {
        $result = $this->db->select($this->table, ['id' => $id]);
        return !empty($result) ? $result[0] : null;
    }

    public function create(array $data): array
    {
        // The DB trigger generate_prescription_id_trigger auto-populates prescription_id on INSERT.
        // Only set it manually as a safety fallback if somehow not set after insert.
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        // Encode medications if it's an array
        if (isset($data['medications']) && is_array($data['medications'])) {
            $data['medications'] = json_encode($data['medications']);
        }
        // Remove prescription_id from data — let the DB trigger handle it
        unset($data['prescription_id']);
        
        $result = $this->db->insert($this->table, $data);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Issued Prescription", [
                    'module'  => 'Health Center Services',
                    'details' => "Prescription status: " . ($data['status'] ?? 'pending'),
                    'status'  => 'Success'
                ]);
            } catch (\Throwable $e) {
                error_log('Prescription::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($result) ? $result : [];
    }

    public function updateById(string|int $id, array $data): array
    {
        // Encode medications if it's an array
        if (isset($data['medications']) && is_array($data['medications'])) {
            $data['medications'] = json_encode($data['medications']);
        }
        $updated = $this->db->update($this->table, $data, ['id' => $id]);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $status = $data['status'] ?? 'updated';
                $logger->log("Updated Prescription: {$status}", [
                    'module'  => 'Health Center Services',
                    'details' => "Prescription #{$id} status set to {$status}",
                    'status'  => 'Success'
                ]);
            } catch (\Throwable $e) {
                error_log('Prescription::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
                require_once __DIR__ . '/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Cancelled Prescription", [
                        'module'  => 'Health Center Services',
                        'details' => "Removed prescription record #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {
                    error_log('Prescription::deleteById ActivityLog error: ' . $e->getMessage());
                }
            }
            return true;
        } catch (\Throwable $e) {
            error_log('Prescription Model Error (deleteById): ' . $e->getMessage());
            return false;
        }
    }

    public function generatePrescriptionId(): string
    {
        // The DB trigger generate_prescription_id_trigger handles this automatically on INSERT.
        // This PHP fallback is only used when the trigger is unavailable.
        // Use a timestamp + random suffix to avoid scanning the whole table.
        return 'RX-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }

    public function dispense(string $id, int $employeeId): array
    {
        $data = [
            'status' => 'dispensed',
            'dispensed_by' => $employeeId,
            'dispensed_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->update($this->table, $data, ['id' => $id]);
    }
    
   /**
 * Get total count of prescriptions with filters
 */
public function count(array $filters = []): int
{
    try {
        // Remove any limit/offset that might be in filters
        unset($filters['limit']);
        unset($filters['offset']);
        unset($filters['order']);
        
        $options = [];
        
        // If there are OR conditions, pass them
        if (isset($filters['or'])) {
            $options['or'] = $filters['or'];
            unset($filters['or']);
        }
        
        // Use the database's count method
        return $this->db->count($this->table, $filters, $options);
        
    } catch (\Exception $e) {
        error_log("Prescription count error: " . $e->getMessage());
        return 0;
    }
}
}