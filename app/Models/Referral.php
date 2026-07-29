<?php
// app/Models/Referral.php

require_once __DIR__ . '/../../config/database.php';

class Referral
{
    private Database $db;
    private string $table = 'referrals';

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
            error_log('Referral Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Referral Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['referral_id'])) {
            $data['referral_id'] = $this->generateReferralId();
        }
        $res = $this->db->insert($this->table, $data);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $rid = $data['referral_id'] ?? '';
                $logger->log("Created Patient Referral", [
                    'module'  => 'Health Center Services',
                    'details' => "Referral ID: {$rid} | Hospital: " . ($data['referred_to'] ?? 'Hospital'),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Referral::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return $res;
    }

    public function update(string|int $id, array $data): array
    {
        $updated = $this->db->update($this->table, $data, ['id' => $id]);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Patient Referral", [
                    'module'  => 'Health Center Services',
                    'details' => "Updated referral #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Referral::update ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function delete(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
                require_once __DIR__ . '/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Cancelled Patient Referral", [
                        'module'  => 'Health Center Services',
                        'details' => "Removed referral record #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {
                    error_log('Referral::delete ActivityLog error: ' . $e->getMessage());
                }
            }
            return true;
        } catch (Throwable $e) {
            error_log('Referral Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function generateReferralId(): string
    {
        // Use timestamp + random suffix — fast fallback if DB trigger not present
        return 'REF-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
?>