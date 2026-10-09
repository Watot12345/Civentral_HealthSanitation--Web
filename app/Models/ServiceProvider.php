<?php
// app/Models/ServiceProvider.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

class ServiceProvider
{
    private Database $db;
    private string $table = 'service_providers';

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
            $rows = $this->db->select($this->table, [], $options);
            return EncryptionHelper::decryptRows($this->table, $rows);
        } catch (Throwable $e) {
            error_log('ServiceProvider Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => 'eq.' . $id]);
            return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
        } catch (Throwable $e) {
            error_log('ServiceProvider Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByProviderId(string $providerId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['provider_id' => 'eq.' . $providerId]);
            return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
        } catch (Throwable $e) {
            error_log('ServiceProvider Model Error (findByProviderId): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['provider_id'])) {
            $data['provider_id'] = $this->generateProviderId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        return $this->db->insert($this->table, $encryptedData);
    }

    public function updateById(string|int $id, array $data): array
    {
        $data['updated_at'] = date('c');
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        return $this->db->update($this->table, $encryptedData, ['id' => 'eq.' . $id]);
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => 'eq.' . $id]);
            return true;
        } catch (Throwable $e) {
            error_log('ServiceProvider Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function countByStatus(): array
    {
        try {
            $all = $this->db->select($this->table, [], ['select' => 'status']);
            $counts = ['active' => 0, 'inactive' => 0, 'suspended' => 0];
            foreach ($all as $row) {
                $s = $row['status'] ?? 'active';
                if (isset($counts[$s])) $counts[$s]++;
            }
            $counts['total'] = array_sum($counts);
            return $counts;
        } catch (Throwable $e) {
            error_log('ServiceProvider Model Error (countByStatus): ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'inactive' => 0, 'suspended' => 0];
        }
    }

    public function generateProviderId(): string
    {
        return 'PRV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
