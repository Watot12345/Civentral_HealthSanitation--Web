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
            'gcs_eye'            => isset($data['gcs_eye']) && $data['gcs_eye'] !== '' ? (int)$data['gcs_eye'] : null,
            'gcs_verbal'         => isset($data['gcs_verbal']) && $data['gcs_verbal'] !== '' ? (int)$data['gcs_verbal'] : null,
            'gcs_motor'          => isset($data['gcs_motor']) && $data['gcs_motor'] !== '' ? (int)$data['gcs_motor'] : null,
            'doctor_id'          => isset($data['doctor_id']) && $data['doctor_id'] !== '' ? (int)$data['doctor_id'] : null,
            'doctor_assigned'    => !empty($data['doctor_assigned']) ? trim($data['doctor_assigned']) : null,
            'symptoms'           => is_array($data['symptoms'] ?? null) ? implode(', ', $data['symptoms']) : ($data['symptoms'] ?? null),
            'priority'           => $data['priority'] ?? 'medium',
            'allergies'          => $data['allergies'] ?? null,
            'medications'        => $data['medications'] ?? null,
            'notes'              => $data['notes'] ?? $data['chief_complaint'] ?? null,
            'status'             => $data['status'] ?? 'pending'
        ];

        if (isset($assessmentData['gcs_eye']) || isset($assessmentData['gcs_verbal']) || isset($assessmentData['gcs_motor'])) {
            $assessmentData['gcs_total'] = ($assessmentData['gcs_eye'] ?? 0) + ($assessmentData['gcs_verbal'] ?? 0) + ($assessmentData['gcs_motor'] ?? 0);
        }

        $assessmentData = array_filter($assessmentData, fn($val) => $val !== null);

        $res = [];
        try {
            $res = $this->db->insert($this->table, $assessmentData);
            if (is_array($res) && isset($res[0]) && is_array($res[0])) {
                $res = $res[0];
            }
        } catch (Throwable $e) {
            error_log('Assessment Model create exception: ' . $e->getMessage());
            throw $e;
        }

        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $tid = $data['triage_id'] ?? ($res['triage_id'] ?? '');
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
        if (is_array($updated) && isset($updated[0]) && is_array($updated[0])) {
            $updated = $updated[0];
        }
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
        $filter = ['id' => 'eq.' . $id];
        if (is_string($id) && str_starts_with($id, 'TRG-')) {
            $num = substr($id, 4);
            if (is_numeric($num)) {
                $filter = ['id' => 'eq.' . (int)$num];
            } else {
                $filter = ['triage_id' => 'eq.' . $id];
            }
        } elseif (is_numeric($id)) {
            $filter = ['id' => 'eq.' . (int)$id];
        }
        $updated = $this->db->update($this->table, ['status' => $status], $filter);
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
            $all = $this->db->select($this->table, [], ['order' => 'id.desc', 'limit' => 50], true);
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
