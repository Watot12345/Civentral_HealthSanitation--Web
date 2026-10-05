<?php
// app/Models/ImmunizationReferral.php

require_once __DIR__ . '/../../config/database.php';

class ImmunizationReferral
{
    private Database $db;
    private string $table = 'immunization_referrals';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Get all referrals with optional filters.
     */
    public function all(array $filters = [], array $options = []): array
    {
        if (empty($options['order'])) {
            $options['order'] = 'created_at.desc';
        }
        try {
            return $this->db->select($this->table, $filters, $options);
        } catch (Throwable $e) {
            error_log('ImmunizationReferral::all error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch pending referrals for immunization queue.
     */
    public function getPending(): array
    {
        return $this->all(['status' => 'pending']);
    }

    /**
     * Find a referral by ID.
     */
    public function find(int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('ImmunizationReferral::find error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a new referral from consultation.
     */
    public function create(array $data): array
    {
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        return $this->db->insert($this->table, $data);
    }

    /**
     * Mark a referral as completed.
     */
    public function complete(int $id): array
    {
        return $this->db->update($this->table, [
            'status' => 'completed',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }

    /**
     * Cancel a referral.
     */
    public function cancel(int $id): array
    {
        return $this->db->update($this->table, [
            'status' => 'cancelled',
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $id]);
    }
}
