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
        // FIXED: Use eq. format for Supabase
        $result = $this->db->select($this->table, ['id' => 'eq.' . $id]);
        return !empty($result) ? $result[0] : null;
    }

    public function findByAppointmentId(string $appointmentId): ?array
    {
        // FIXED: Use eq. format for Supabase
        $result = $this->db->select($this->table, ['appointment_id' => 'eq.' . $appointmentId]);
        return !empty($result) ? $result[0] : null;
    }

    public function create(array $data): array
    {
        if (empty($data['appointment_id'])) {
            $data['appointment_id'] = $this->generateAppointmentId();
        }
        $res = $this->db->insert($this->table, $data, true);
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
        return $res;
    }

    public function updateById(string|int $id, array $data): array
    {
        $updated = $this->db->update($this->table, $data, ['id' => 'eq.' . $id], true);
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
        return $updated;
    }

    public function updateStatus(string|int $id, string $status): array
    {
        $updated = $this->db->update($this->table, ['status' => $status], ['id' => 'eq.' . $id], true);
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/ActivityLog.php')) {
            require_once __DIR__ . '/ActivityLog.php';
            try {
                $logger = new ActivityLog();
                $logger->log("Updated Appointment Status to {$status}", [
                    'module'  => 'Health Center Services',
                    'details' => "Appointment #{$id} status changed to {$status}",
                    'status'  => 'Success'
                ]);
            } catch (Throwable $e) {
                error_log('Appointment::updateStatus ActivityLog error: ' . $e->getMessage());
            }
        }
        return $updated;
    }

    public function deleteById(string|int $id): bool
    {
        // FIXED: Use eq. format + service key
        $this->db->delete($this->table, ['id' => 'eq.' . $id], true);
        return true;
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