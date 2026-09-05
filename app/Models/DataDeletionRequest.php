<?php
// app/Models/DataDeletionRequest.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ActivityLog.php';

class DataDeletionRequest
{
    private Database $db;
    private string $table = 'data_deletion_requests';
    private string $auditTable = 'deletion_audit_logs';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Submit a user deletion request
     */
    public function createRequest(string $userId, string $subjectType = 'patient', ?string $reason = null): array
    {
        $payload = [
            'user_id'        => trim($userId),
            'subject_type'   => strtolower(trim($subjectType)),
            'request_reason' => $reason ? trim($reason) : null,
            'status'         => 'pending',
            'requested_at'   => gmdate('Y-m-d H:i:sP'),
            'created_at'     => gmdate('Y-m-d H:i:sP')
        ];

        $res = $this->db->insert($this->table, $payload, true);
        return is_array($res) ? $res : $payload;
    }

    /**
     * Fetch pending deletion requests for admin review
     */
    public function getPendingRequests(): array
    {
        try {
            return $this->db->select($this->table, ['status' => 'eq.pending'], ['order' => 'id.asc']);
        } catch (\Throwable $e) {
            error_log('DataDeletionRequest::getPendingRequests error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Admin approve deletion request
     */
    public function approveRequest(int $requestId, string $adminUser): bool
    {
        try {
            $now = gmdate('Y-m-d H:i:sP');
            $this->db->update($this->table, [
                'status'      => 'approved',
                'reviewed_by' => $adminUser,
                'reviewed_at' => $now
            ], ['id' => 'eq.' . $requestId], true);

            return true;
        } catch (\Throwable $e) {
            error_log('DataDeletionRequest::approveRequest error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Admin reject deletion request
     */
    public function rejectRequest(int $requestId, string $adminUser, string $reason): bool
    {
        try {
            $now = gmdate('Y-m-d H:i:sP');
            $this->db->update($this->table, [
                'status'           => 'rejected',
                'reviewed_by'      => $adminUser,
                'reviewed_at'      => $now,
                'rejection_reason' => $reason
            ], ['id' => 'eq.' . $requestId], true);

            return true;
        } catch (\Throwable $e) {
            error_log('DataDeletionRequest::rejectRequest error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Automated execution job: Process all approved requests and audit output
     */
    public function processApprovedRequests(string $executor = 'system_scheduler'): int
    {
        $processed = 0;
        try {
            $approved = $this->db->select($this->table, ['status' => 'eq.approved']);
            if (empty($approved)) {
                return 0;
            }

            $logModel = new ActivityLog();

            foreach ($approved as $req) {
                $reqId = (int)$req['id'];
                $userId = $req['user_id'];
                $subjectType = $req['subject_type'];

                $deletedSummary = [
                    'anonymized_tables' => [],
                    'soft_deleted_tables' => []
                ];

                // Execute soft-delete / anonymization on target subject
                if ($subjectType === 'patient') {
                    $this->db->update('patients', ['status' => 'deleted', 'health_condition_notes' => null], ['patient_id' => 'eq.' . $userId]);
                    $deletedSummary['soft_deleted_tables'][] = 'patients';
                } elseif ($subjectType === 'permit_applicant') {
                    $this->db->update('permits', ['status' => 'cancelled'], ['permit_id' => 'eq.' . $userId]);
                    $deletedSummary['soft_deleted_tables'][] = 'permits';
                }

                $now = gmdate('Y-m-d H:i:sP');

                // Mark request as executed
                $this->db->update($this->table, [
                    'status'       => 'executed',
                    'executed_at'  => $now
                ], ['id' => 'eq.' . $reqId], true);

                // Write to deletion_audit_logs
                $this->db->insert($this->auditTable, [
                    'request_id'      => $reqId,
                    'user_id'         => $userId,
                    'subject_type'    => $subjectType,
                    'deleted_records' => json_encode($deletedSummary),
                    'executed_by'     => $executor,
                    'executed_at'     => $now,
                    'created_at'      => $now
                ], true);

                $logModel->log('Data Deletion Executed', [
                    'user_name' => $userId,
                    'role'      => 'System',
                    'module'    => 'Privacy',
                    'details'   => "Executed Right-to-be-Forgotten deletion request ID {$reqId} for {$subjectType} {$userId}",
                    'status'    => 'Success'
                ]);

                $processed++;
            }
        } catch (\Throwable $e) {
            error_log('DataDeletionRequest::processApprovedRequests error: ' . $e->getMessage());
        }

        return $processed;
    }
}
