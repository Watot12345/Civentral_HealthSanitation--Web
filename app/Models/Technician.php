<?php
// app/Models/Technician.php

require_once __DIR__ . '/../../config/database.php';

class Technician
{
    private Database $db;
    private string $table = 'technicians';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Return all technicians ordered by name.
     */
    public function all(): array
    {
        try {
            return $this->db->select($this->table, [], ['order' => 'name.asc']);
        } catch (Throwable $e) {
            error_log('Technician::all() error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Return only available technicians for the dispatch dropdown.
     */
    public function available(): array
    {
        try {
            return $this->db->select($this->table, ['status' => 'available'], ['order' => 'name.asc']);
        } catch (Throwable $e) {
            error_log('Technician::available() error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a single technician by ID.
     */
    public function find(int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Technician::find() error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update a technician's status and optional current assignment.
     *
     * @param int         $id         Technician row ID
     * @param string      $status     One of: available | on_site | en_route | off_duty
     * @param string|null $assignment Tank ID currently assigned, or null to clear
     */
    public function updateStatus(int $id, string $status, ?string $assignment = null): bool
    {
        $allowed = ['available', 'on_site', 'en_route', 'off_duty'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        try {
            $this->db->update($this->table, [
                'status'             => $status,
                'current_assignment' => $assignment,
                'updated_at'         => date('c')
            ], ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('Technician::updateStatus() error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count technicians grouped by status (for dashboard KPIs).
     *
     * @return array{available:int, on_site:int, en_route:int, off_duty:int, total:int}
     */
    public function countByStatus(): array
    {
        $counts = ['available' => 0, 'on_site' => 0, 'en_route' => 0, 'off_duty' => 0, 'total' => 0];
        try {
            foreach ($this->all() as $t) {
                $s = $t['status'] ?? 'available';
                if (isset($counts[$s])) {
                    $counts[$s]++;
                }
                $counts['total']++;
            }
        } catch (Throwable $e) {
            error_log('Technician::countByStatus() error: ' . $e->getMessage());
        }
        return $counts;
    }
}
