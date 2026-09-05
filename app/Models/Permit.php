<?php
// app/Models/Permit.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

class Permit
{
    private Database $db;
    private string $table = 'permits';

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
            error_log('Permit Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
        } catch (Throwable $e) {
            error_log('Permit Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        // Let a DB trigger or PHP generate the permit_id if missing
        if (empty($data['permit_id'])) {
            $data['permit_id'] = $this->generatePermitId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        $res = $this->db->insert($this->table, $encryptedData);
        return is_array($res) ? EncryptionHelper::decryptModel($this->table, $res) : $res;
    }

    public function updateById(string|int $id, array $data): array
    {
        $encryptedData = EncryptionHelper::encryptModel($this->table, $data);
        $updated = $this->db->update($this->table, $encryptedData, ['id' => $id]);
        return is_array($updated) ? EncryptionHelper::decryptRows($this->table, $updated) : $updated;
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('Permit Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    private static ?Permit $instance = null;

    private static function getInstance(): Permit
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function __callStatic(string $name, array $arguments)
    {
        return self::getInstance()->$name(...$arguments);
    }

    public function findByPermitId(string $permitId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['permit_id' => $permitId]);
            return !empty($result) ? EncryptionHelper::decryptModel($this->table, $result[0]) : null;
        } catch (Throwable $e) {
            error_log('Permit Model Error (findByPermitId): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fast fallback ID generator — does NOT scan entire table.
     */
    public function generatePermitId(): string
    {
        return 'SP-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
