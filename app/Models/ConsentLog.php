<?php
// app/Models/ConsentLog.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Patient.php';
require_once __DIR__ . '/Child.php';
require_once __DIR__ . '/Permit.php';
require_once __DIR__ . '/Employee.php';

class ConsentLog
{
    private Database $db;
    private string $table = 'consent_logs';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Verify existence of subject across corresponding core tables
     */
    public function subjectExists(string $subjectId, string $subjectType): bool
    {
        $subjectId = trim($subjectId);
        $subjectType = strtolower(trim($subjectType));

        try {
            switch ($subjectType) {
                case 'patient':
                    $patientModel = new Patient();
                    if (is_numeric($subjectId)) {
                        $p = $patientModel->find($subjectId);
                        if (!empty($p)) return true;
                    }
                    $p = $patientModel->findByPatientId($subjectId);
                    return !empty($p);

                case 'child':
                    $childModel = new Child();
                    if (is_numeric($subjectId)) {
                        $c = $childModel->find($subjectId);
                        if (!empty($c)) return true;
                    }
                    $c = $childModel->findByChildId($subjectId);
                    return !empty($c);

                case 'permit_applicant':
                case 'permit':
                    $permitModel = new Permit();
                    if (is_numeric($subjectId)) {
                        $pm = $permitModel->find($subjectId);
                        if (!empty($pm)) return true;
                    }
                    $pm = $permitModel->findByPermitId($subjectId);
                    return !empty($pm);

                case 'employee':
                    $empModel = new Employee();
                    if (is_numeric($subjectId)) {
                        $e = $empModel->find($subjectId);
                        if (!empty($e)) return true;
                    }
                    $e = $empModel->findByEmployeeId($subjectId);
                    return !empty($e);

                case 'citizen':
                    // Check patients table as fallback citizen registry
                    $patientModel = new Patient();
                    if (is_numeric($subjectId)) {
                        $p = $patientModel->find($subjectId);
                        if (!empty($p)) return true;
                    }
                    $p = $patientModel->findByPatientId($subjectId);
                    return !empty($p);

                default:
                    return false;
            }
        } catch (\Throwable $e) {
            error_log('ConsentLog::subjectExists verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Find active consent for a subject and consent type
     */
    public function findActiveConsent(string $subjectId, string $consentType): ?array
    {
        try {
            $results = $this->db->select($this->table, [
                'subject_id'   => 'eq.' . $subjectId,
                'consent_type' => 'eq.' . $consentType,
                'status'       => 'eq.active',
            ], [
                'limit' => 1,
                'order' => 'id.desc'
            ]);

            return !empty($results) ? $results[0] : null;
        } catch (\Throwable $e) {
            error_log('ConsentLog::findActiveConsent error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Record a new consent entry
     */
    public function create(array $data): array
    {
        $payload = [
            'subject_id'      => trim((string)($data['subject_id'] ?? '')),
            'subject_type'    => strtolower(trim((string)($data['subject_type'] ?? ''))),
            'consent_type'    => trim((string)($data['consent_type'] ?? '')),
            'consent_version' => trim((string)($data['consent_version'] ?? '1.0')),
            'ip_address'      => trim((string)($data['ip_address'] ?? '0.0.0.0')),
            'user_agent'      => trim((string)($data['user_agent'] ?? '')),
            'status'          => 'active',
            'consented_at'    => gmdate('Y-m-d H:i:sP'),
            'created_at'      => gmdate('Y-m-d H:i:sP'),
            'updated_at'      => gmdate('Y-m-d H:i:sP')
        ];

        $res = $this->db->insert($this->table, $payload, true);
        return is_array($res) ? $res : $payload;
    }

    /**
     * Withdraw an active consent record
     */
    public function withdraw(string $subjectId, string $consentType, ?string $reason = null): ?array
    {
        $active = $this->findActiveConsent($subjectId, $consentType);
        if (!$active) {
            return null;
        }

        $now = gmdate('Y-m-d H:i:sP');
        $updateData = [
            'status'            => 'withdrawn',
            'withdrawn_at'      => $now,
            'withdrawal_reason' => $reason,
            'updated_at'        => $now
        ];

        $res = $this->db->update(
            $this->table,
            $updateData,
            [
                'id'     => 'eq.' . $active['id'],
                'status' => 'eq.active'
            ],
            true
        );

        if (!empty($res)) {
            return is_array($res[0] ?? null) ? $res[0] : array_merge($active, $updateData);
        }

        return array_merge($active, $updateData);
    }

    /**
     * Get all consent history for a subject
     */
    public function getHistoryBySubject(string $subjectId): array
    {
        try {
            return $this->db->select($this->table, [
                'subject_id' => 'eq.' . $subjectId
            ], [
                'order' => 'created_at.desc'
            ]);
        } catch (\Throwable $e) {
            error_log('ConsentLog::getHistoryBySubject error: ' . $e->getMessage());
            return [];
        }
    }
}
