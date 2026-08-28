<?php
// app/Models/TriageQueue.php

require_once __DIR__ . '/../../config/database.php';

class TriageQueue
{
    private Database $db;
    private string $table = 'triage_queue';

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
            error_log('TriageQueue Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('TriageQueue Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByQueueNumber(string $queueNumber): ?array
    {
        try {
            $result = $this->db->select($this->table, ['queue_number' => $queueNumber]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('TriageQueue Model Error (findByQueueNumber): ' . $e->getMessage());
            return null;
        }
    }

    public function getByPatientId(string|int $patientId): array
    {
        try {
            return $this->db->select($this->table, ['patient_id' => $patientId], ['order' => 'created_at.desc']);
        } catch (Throwable $e) {
            error_log('TriageQueue Model Error (getByPatientId): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get today's queue entries
     */
    public function getTodayQueue(): array
    {
        try {
            $all = $this->all(['order' => 'created_at.asc']);
            $today = date('Y-m-d');
            
            return array_values(array_filter($all, function($item) use ($today) {
                return isset($item['created_at']) && 
                       date('Y-m-d', strtotime($item['created_at'])) === $today;
            }));
        } catch (Throwable $e) {
            error_log('TriageQueue Model Error (getTodayQueue): ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get active visits filtered by reason_for_visit (defaults to today's active queue)
     *
     * @param string $reason
     * @param bool|string $dateOrTodayOnly true for today, string 'YYYY-MM-DD' for specific date, false for all dates
     * @return array
     */
    public function getVisitsByReason(string $reason, bool|string $dateOrTodayOnly = true): array
    {
        try {
            $all = $this->all(['order' => 'created_at.desc']);
            $targetDate = is_string($dateOrTodayOnly) ? $dateOrTodayOnly : ($dateOrTodayOnly ? date('Y-m-d') : null);
            return array_values(array_filter($all, function($item) use ($reason, $targetDate) {
                $status = strtolower($item['status'] ?? 'waiting');
                if ($status === 'cancelled') return false;
                
                if ($targetDate !== null) {
                    $itemDate = !empty($item['check_in_time']) 
                        ? date('Y-m-d', strtotime($item['check_in_time'])) 
                        : (!empty($item['created_at']) ? date('Y-m-d', strtotime($item['created_at'])) : '');
                    if ($itemDate !== $targetDate) return false;
                }
                
                $itemReason = $item['reason_for_visit'] ?? '';
                return strcasecmp(trim($itemReason), trim($reason)) === 0;
            }));
        } catch (Throwable $e) {
            error_log('TriageQueue Model Error (getVisitsByReason): ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data): array
    {
        if (empty($data['queue_number'])) {
            $data['queue_number'] = $this->generateQueueNumber();
        }
        if (empty($data['status'])) {
            $data['status'] = 'waiting';
        }
        if (empty($data['check_in_time'])) {
            $data['check_in_time'] = date('Y-m-d H:i:s');
        }
        return $this->db->insert($this->table, $data);
    }

    public function updateById(string|int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function updateStatus(string|int $id, string $status): array
    {
        $validStatuses = ['waiting', 'in_triage', 'completed'];
        if (!in_array($status, $validStatuses)) {
            $status = 'waiting';
        }
        return $this->db->update($this->table, ['status' => $status], ['id' => $id]);
    }

    public function deleteById(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('TriageQueue Model Error (deleteById): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a unique queue number
     */
    public function generateQueueNumber(): string
    {
        try {
            $todayQueue = $this->getTodayQueue();
            $count = count($todayQueue) + 1;
            return 'Q-' . date('Ymd') . '-' . str_pad((string)$count, 3, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            return 'Q-' . date('Ymd') . '-' . rand(100, 999);
        }
    }

    /**
     * Check if patient already has an active check-in today for the same reason for visit
     */
    public function isPatientCheckedInToday(int $patientId, ?string $reasonForVisit = null): bool
    {
        try {
            $todayQueue = $this->getTodayQueue();
            
            foreach ($todayQueue as $item) {
                if (
                    (int)($item['patient_id'] ?? 0) === $patientId && 
                    ($item['status'] ?? '') !== 'completed'
                ) {
                    // If a specific reason_for_visit is provided, only flag duplicate if reasons match
                    if ($reasonForVisit !== null && !empty($item['reason_for_visit'])) {
                        if (strcasecmp(trim($item['reason_for_visit']), trim($reasonForVisit)) === 0) {
                            return true;
                        }
                    } else if ($reasonForVisit === null) {
                        return true;
                    }
                }
            }
            
            return false;
        } catch (Throwable $e) {
            error_log('TriageQueue Model Error (isPatientCheckedInToday): ' . $e->getMessage());
            return false;
        }
    }
}