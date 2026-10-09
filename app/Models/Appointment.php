<?php
// app/Models/Appointment.php

require_once __DIR__ . '/../../config/database.php';

class Appointment
{
    private Database $db;
    private string $table = 'appointments';

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
        } catch (\Throwable $e) {
            error_log("Appointment::all error: " . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        if (!is_numeric($id)) {
            return $this->findByAppointmentId((string)$id);
        }
        $result = $this->db->select($this->table, ['id' => 'eq.' . $id]);
        return !empty($result) ? $result[0] : null;
    }

    public function findByAppointmentId(string $appointmentId): ?array
    {
        // FIXED: Use eq. format for Supabase
        $result = $this->db->select($this->table, ['appointment_id' => 'eq.' . $appointmentId]);
        return !empty($result) ? $result[0] : null;
    }

    public function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string)$status));
        if ($status === 'scheduled' || $status === 'confirmed') {
            return 'approved';
        }
        if (in_array($status, ['reassignment_pending', 'sent_to_other_doctor', 'not_available_pending', 'not_available_reassigned'], true)) {
            return 'pending';
        }
        $validDbStatuses = ['pending', 'approved', 'completed', 'cancelled', 'no_show'];
        return in_array($status, $validDbStatuses, true) ? $status : 'pending';
    }

    public function create(array $data): array
    {
        if (empty($data['appointment_id'])) {
            $data['appointment_id'] = $this->generateAppointmentId();
        }
        if (isset($data['status'])) {
            $data['status'] = $this->normalizeStatus($data['status']);
        } else {
            $data['status'] = 'pending';
        }
        $res = $this->db->insert($this->table, $data, true);
        if (is_array($res) && isset($res[0]) && is_array($res[0])) {
            $res = $res[0];
        }
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $type = $data['type'] ?? $data['service'] ?? 'Consultation';
                $aid = $data['appointment_id'] ?? '';
                $logger->log("Booked Appointment: {$type}", [
                    'module'  => 'Health Center Services',
                    'details' => "Appointment ID: {$aid} | Date: " . ($data['appointment_date'] ?? date('Y-m-d')),
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Appointment::create ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($res) ? $res : $res;
    }

    public function update(array $data, array $where = []): array
    {
        if (isset($data['status'])) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }
        if (isset($where['id'])) {
            $id = str_replace('eq.', '', (string)$where['id']);
            return $this->updateById($id, $data);
        }
        $updated = $this->db->update($this->table, $data, $where);
        return is_array($updated) ? $updated : [];
    }

    public function updateById(string|int $id, array $data): array
    {
        if (isset($data['status'])) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }
        $updated = $this->db->update($this->table, $data, ['id' => 'eq.' . $id], true);
        if (is_array($updated) && isset($updated[0]) && is_array($updated[0])) {
            $updated = $updated[0];
        }
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Appointment Record", [
                    'module'  => 'Health Center Services',
                    'details' => "Updated appointment #{$id}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Appointment::updateById ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($updated) ? $updated : $updated;
    }

    public function getByPatientId(string|int $patientId): array
    {
        try {
            return $this->db->select($this->table, ['patient_id' => 'eq.' . $patientId], ['order' => 'created_at.desc']);
        } catch (\Throwable $e) {
            error_log("Appointment::getByPatientId error: " . $e->getMessage());
            return [];
        }
    }

    public function updateStatus(string|int $id, string $status): array
    {
        $numericId = $id;
        if (!is_numeric($id)) {
            $found = $this->findByAppointmentId((string)$id);
            if (!empty($found['id'])) {
                $numericId = $found['id'];
            }
        }
        $dbStatus = $this->normalizeStatus($status);
        $updated = $this->db->update($this->table, ['status' => $dbStatus], ['id' => 'eq.' . $numericId], true);
        if (is_array($updated) && isset($updated[0]) && is_array($updated[0])) {
            $updated = $updated[0];
        }
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Appointment Status to {$dbStatus}", [
                    'module'  => 'Health Center Services',
                    'details' => "Appointment #{$id} status changed to {$dbStatus}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Appointment::updateStatus ActivityLog error: ' . $e->getMessage());
            }
        }
        return is_array($updated) ? $updated : $updated;
    }

    public function deleteById(string|int $id): bool
    {
        // FIXED: Use eq. format + service key
        $this->db->delete($this->table, ['id' => 'eq.' . $id], true);
        return true;
    }

    /**
     * Count appointments for a specific date (used for daily quota enforcement).
     */
    public function countByDate(string $date): int
    {
        try {
            return $this->db->count($this->table, ['appointment_date' => 'eq.' . $date]);
        } catch (\Throwable $e) {
            error_log("Appointment::countByDate error: " . $e->getMessage());
            return 0;
        }
    }

    public function generateAppointmentId(): string
    {
        try {
            $all = $this->all();
            $maxNum = 0;
            foreach ($all as $a) {
                if (!empty($a['appointment_id']) && preg_match('/APT-(\d+)/i', $a['appointment_id'], $matches)) {
                    $num = (int)$matches[1];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }
            $nextNum = $maxNum + 1;
            return 'APT-' . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return 'APT-' . date('YmdHis') . '-' . rand(100, 999);
        }
    }
}