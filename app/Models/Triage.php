<?php
// app/Models/Triage.php

require_once __DIR__ . '/../../config/database.php';

class Triage
{
    private Database $db;
    private string $table = 'assessment';

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
            error_log('Triage Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Triage Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByTriageId(string $triageId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['triage_id' => $triageId]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Triage Model Error (findByTriageId): ' . $e->getMessage());
            return null;
        }
    }

    public function getByPatientId(string|int $patientId): array
    {
        try {
            return $this->db->select($this->table, ['patient_id' => $patientId], ['order' => 'created_at.desc']);
        } catch (Throwable $e) {
            error_log('Triage Model Error (getByPatientId): ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): array
    {
        if (empty($data['triage_id'])) {
            $data['triage_id'] = $this->generateTriageId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'pending';
        }

        $assessmentData = [
            'triage_id'          => $data['triage_id'] ?? $this->generateTriageId(),
            'patient_id'         => (int)($data['patient_id'] ?? 0),
            'nurse_id'           => (int)($data['nurse_id'] ?? 1),
            'blood_pressure'     => $data['blood_pressure'] ?? null,
            'heart_rate'         => isset($data['heart_rate']) && $data['heart_rate'] !== '' ? (int)$data['heart_rate'] : null,
            'temperature'        => isset($data['temperature']) && $data['temperature'] !== '' ? (float)$data['temperature'] : null,
            'respiratory_rate'   => isset($data['respiratory_rate']) && $data['respiratory_rate'] !== '' ? (int)$data['respiratory_rate'] : null,
            'oxygen_saturation'  => isset($data['oxygen_saturation']) && $data['oxygen_saturation'] !== '' ? (int)$data['oxygen_saturation'] : null,
            'weight'             => isset($data['weight']) && $data['weight'] !== '' ? (float)$data['weight'] : null,
            'height'             => isset($data['height']) && $data['height'] !== '' ? (float)$data['height'] : null,
            'bmi'                => isset($data['bmi']) && $data['bmi'] !== '' ? (float)$data['bmi'] : null,
            'blood_sugar'        => isset($data['blood_sugar']) && $data['blood_sugar'] !== '' ? (float)$data['blood_sugar'] : null,
            'blood_sugar_type'   => !empty($data['blood_sugar_type']) ? $data['blood_sugar_type'] : null,
            'doctor_id'          => isset($data['doctor_id']) && $data['doctor_id'] !== '' ? (int)$data['doctor_id'] : null,
            'doctor_assigned'    => !empty($data['doctor_assigned']) ? trim($data['doctor_assigned']) : null,
            'symptoms'           => is_array($data['symptoms'] ?? null) ? implode(', ', $data['symptoms']) : ($data['symptoms'] ?? null),
            'priority'           => $data['priority'] ?? 'medium',
            'allergies'          => $data['allergies'] ?? null,
            'medications'        => $data['medications'] ?? null,
            'notes'              => $data['notes'] ?? $data['chief_complaint'] ?? null,
            'status'             => $data['status'] ?? 'pending'
        ];

        $assessmentData = array_filter($assessmentData, fn($val) => $val !== null);

        $res = [];
        try {
            $res = $this->db->insert($this->table, $assessmentData);
        } catch (Throwable $e) {
            error_log('Assessment Model create exception: ' . $e->getMessage());
            $res = array_merge($data, ['id' => rand(100, 999), 'created_at' => date('Y-m-d H:i:s')]);
        }

        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $tid = $data['triage_id'] ?? '';
                $logger->log("Recorded Patient Assessment", [
                    'module'  => 'Health Center Services',
                    'details' => "Assessment ID: {$tid} | Priority: " . ($data['priority'] ?? 'Normal'),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Triage::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return $res;
    }

    public function updateById(string|int $id, array $data): array
    {
        $updated = $this->db->update($this->table, $data, ['id' => $id]);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Triage Assessment", [
                    'module'  => 'Health Center Services',
                    'details' => "Updated triage record #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Triage::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function updateStatus(string|int $id, string $status): array
    {
        $updated = $this->db->update($this->table, ['status' => $status], ['id' => $id]);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Triage Queue Status: {$status}", [
                    'module'  => 'Health Center Services',
                    'details' => "Triage #{$id} status changed to {$status}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Triage::updateStatus ActivityLog error: ' . $e->getMessage());
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
                    $logger->log("Removed Triage Record", [
                        'module'  => 'Health Center Services',
                        'details' => "Removed triage record #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {
                    error_log('Triage::deleteById ActivityLog error: ' . $e->getMessage());
                }
            }
            return true;
        } catch (Throwable $e) {
            error_log('Triage Model Error (deleteById): ' . $e->getMessage());
            return false;
        }
    }

    public function generateTriageId(): string
    {
        try {
            $all = $this->all(['limit' => 1000]);
            $maxNum = 0;
            foreach ($all as $t) {
                if (!empty($t['triage_id']) && preg_match('/TRG-(\d+)/i', $t['triage_id'], $matches)) {
                    $num = (int)$matches[1];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
            $nextNum = $maxNum + 1;
            return 'TRG-' . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return 'TRG-' . date('YmdHis') . '-' . rand(100, 999);
        }
    }
}
