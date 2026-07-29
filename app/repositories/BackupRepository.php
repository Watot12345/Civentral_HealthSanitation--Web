<?php
// app/repositories/BackupRepository.php

namespace App\Repositories;

require_once __DIR__ . '/../../config/database.php';
use Database;
use Throwable;

class BackupRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get backup history logs
     */
    public function getHistory(int $limit = 20): array
    {
        try {
            return $this->db->select('backup_history', [], ['order' => 'started_at.desc', 'limit' => $limit]);
        } catch (Throwable $e) {
            error_log("BackupRepository::getHistory error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log backup run state
     */
    public function logBackup(
        string $type,
        string $fileName,
        string $fileSize,
        string $status = 'completed',
        ?string $errorMessage = null,
        ?string $createdBy = 'System'
    ): array {
        try {
            $data = [
                'backup_type' => $type,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'status' => $status,
                'error_message' => $errorMessage,
                'started_at' => date('Y-m-d H:i:s'),
                'completed_at' => $status === 'completed' ? date('Y-m-d H:i:s') : null,
                'created_by' => $createdBy,
            ];
            return $this->db->insert('backup_history', $data, true);
        } catch (Throwable $e) {
            error_log("BackupRepository::logBackup error: " . $e->getMessage());
            return [];
        }
    }
}
