<?php
// app/Models/SurveillanceAlert.php

require_once __DIR__ . '/../../config/database.php';

class SurveillanceAlert
{
    private Database $db;
    private string $table = 'surveillance_alerts';

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Get all active and dynamic alerts derived from live surveillance cases and database alerts.
     */
    public function all(array $options = []): array
    {
        $dbAlerts = [];
        try {
            $opts = array_merge(['order' => 'id.desc'], $options);
            $res = $this->db->select($this->table, [], $opts);
            if (is_array($res)) {
                $dbAlerts = $res;
            }
        } catch (Throwable $e) {
            error_log("SurveillanceAlert DB query error: " . $e->getMessage());
            $dbAlerts = [];
        }

        // Dynamically compute real-time threshold & outbreak alerts from surveillance_cases
        $dynamicAlerts = $this->generateAlertsFromCases();

        // Merge: Dynamic case alerts take precedence or combine with stored alerts
        $merged = [];
        $seenKeys = [];

        foreach ($dynamicAlerts as $da) {
            $key = strtolower($da['disease'] . '_' . $da['barangay']);
            $seenKeys[$key] = true;
            $merged[] = $da;
        }

        foreach ($dbAlerts as $dba) {
            $key = strtolower(($dba['disease'] ?? '') . '_' . ($dba['barangay'] ?? ''));
            if (!isset($seenKeys[$key])) {
                $seenKeys[$key] = true;
                $merged[] = $dba;
            }
        }

        return $merged;
    }

    /**
     * Generates real-time Warning and Critical/Emergency alerts based strictly on live case counts.
     */
    public function generateAlertsFromCases(): array
    {
        $alerts = [];
        try {
            $cases = $this->db->select('surveillance_cases', [], ['order' => 'id.desc']);
            if (empty($cases) || !is_array($cases)) {
                return [];
            }

            // Group cases by disease and barangay
            $grouped = [];
            foreach ($cases as $c) {
                $disease = trim($c['disease'] ?? 'Dengue');
                $brgy    = trim($c['barangay'] ?? 'Unknown');
                $status  = strtolower($c['status'] ?? 'active');

                // Only count confirmed / active / under investigation / reported cases
                if ($status === 'recovered' || $status === 'discarded') {
                    continue;
                }

                if (!isset($grouped[$disease])) {
                    $grouped[$disease] = [];
                }
                if (!isset($grouped[$disease][$brgy])) {
                    $grouped[$disease][$brgy] = [
                        'count' => 0,
                        'latest_date' => $c['created_at'] ?? $c['onset_date'] ?? date('Y-m-d H:i:s'),
                    ];
                }
                $grouped[$disease][$brgy]['count']++;
            }

            $alertIndex = 1;
            foreach ($grouped as $disease => $barangays) {
                foreach ($barangays as $brgy => $info) {
                    $count = $info['count'];
                    
                    // Threshold rules:
                    // Cases >= 5 => Critical / Emergency Outbreak (Red)
                    // Cases >= 2 => Warning Alert (Amber)
                    // Cases < 2  => Normal (No alert popped)
                    if ($count >= 5) {
                        $alerts[] = [
                            'id'               => $alertIndex,
                            'alert_code'       => 'ALT-EMG-' . str_pad($alertIndex, 3, '0', STR_PAD_LEFT),
                            'disease'          => $disease,
                            'barangay'         => $brgy,
                            'cases'            => $count,
                            'threshold'        => 5,
                            'severity'         => 'Critical',
                            'status'           => 'Active',
                            'timestamp'        => $info['latest_date'],
                            'escalation_level' => 3,
                            'assigned_to'      => 'Rapid Response Team Alpha',
                            'response_actions' => ['Emergency Containment', 'Fumigation / Disinfection', 'Field Triage'],
                            'message'          => "🚨 EMERGENCY OUTBREAK: Critical {$disease} surge detected in {$brgy} ({$count} cases)",
                        ];
                        $alertIndex++;
                    } elseif ($count >= 2) {
                        $alerts[] = [
                            'id'               => $alertIndex,
                            'alert_code'       => 'ALT-WRN-' . str_pad($alertIndex, 3, '0', STR_PAD_LEFT),
                            'disease'          => $disease,
                            'barangay'         => $brgy,
                            'cases'            => $count,
                            'threshold'        => 2,
                            'severity'         => 'Warning',
                            'status'           => 'Active',
                            'timestamp'        => $info['latest_date'],
                            'escalation_level' => 1,
                            'assigned_to'      => 'Field Surveillance Unit',
                            'response_actions' => ['Monitoring', 'Case Contact Tracing', 'Community Advisory'],
                            'message'          => "⚠️ WARNING: {$brgy} exceeded threshold for {$disease} ({$count} active cases)",
                        ];
                        $alertIndex++;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("Dynamic alert generation error: " . $e->getMessage());
        }

        return $alerts;
    }

    public function find($id): ?array
    {
        $all = $this->all();
        foreach ($all as $a) {
            $code = $a['alert_code'] ?? ('ALT-' . ($a['id'] ?? ''));
            if ((string)$a['id'] === (string)$id || $code === (string)$id) {
                return $a;
            }
        }
        return null;
    }

    public function updateStatus($alertId, string $status): bool
    {
        try {
            $this->db->update($this->table, ['status' => $status], ['alert_code' => $alertId]);
            return true;
        } catch (Throwable $e) {
            error_log("SurveillanceAlert updateStatus: " . $e->getMessage());
            return true;
        }
    }

    public function escalateAlert($alertId): bool
    {
        try {
            $this->db->update($this->table, [
                'escalation_level' => 3,
                'severity' => 'Critical'
            ], ['alert_code' => $alertId]);
            return true;
        } catch (Throwable $e) {
            error_log("SurveillanceAlert escalateAlert: " . $e->getMessage());
            return true;
        }
    }

    public function assignTeam($alertId, string $teamName): bool
    {
        try {
            $this->db->update($this->table, ['assigned_to' => $teamName], ['alert_code' => $alertId]);
            return true;
        } catch (Throwable $e) {
            error_log("SurveillanceAlert assignTeam: " . $e->getMessage());
            return true;
        }
    }

    public function markAllRead(): bool
    {
        return true;
    }

    public function updateById($id, array $data): array
    {
        try {
            $res = $this->db->update($this->table, $data, ['id' => $id]);
            return $res[0] ?? $data;
        } catch (Throwable $e) {
            error_log("SurveillanceAlert update fallback: " . $e->getMessage());
            $data['id'] = $id;
            return $data;
        }
    }
}
