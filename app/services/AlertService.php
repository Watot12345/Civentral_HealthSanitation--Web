<?php
// app/services/AlertService.php
// Pure Public Health Surveillance & Early Warning Engine
// Single canonical source of truth for 2-SD anomaly thresholds and alert generation.

namespace App\Services;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paths.php';

class AlertService
{
    private static ?AlertService $instance = null;
    private \Database $db;

    public const MINIMUM_CASE_FLOOR = 3;
    public const SD_MULTIPLIER = 2.0;
    public const EPIDEMIOLOGICAL_WINDOW_WEEKS = 12;

    public static array $trackedDiseases = [
        'Dengue',
        'Influenza',
        'Leptospirosis',
        'Tuberculosis',
        'Measles',
        'Acute Gastroenteritis',
        'COVID-19',
        'Hypertension',
        'Diabetes Mellitus'
    ];

    public static array $district1Zones = [
        'Zone 1'  => ['start' => 1, 'end' => 4],
        'Zone 7'  => ['start' => 77, 'end' => 81],
        'Zone 8'  => ['start' => 82, 'end' => 85],
        'Zone 12' => ['start' => 132, 'end' => 140],
        'Zone 13' => ['start' => 141, 'end' => 150],
        'Zone 14' => ['start' => 151, 'end' => 160],
        'Zone 15' => ['start' => 161, 'end' => 164]
    ];

    private function __construct(?\Database $db = null)
    {
        $this->db = $db ?? \Database::getInstance();
    }

    public static function getInstance(?\Database $db = null): AlertService
    {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    /**
     * Get active alerts from the canonical surveillance_alerts table.
     * Read-only presentation with plain-language status badges.
     */
    public function getActiveAlerts(array $filters = []): array
    {
        try {
            $options = ['order' => 'cases.desc,created_at.desc'];
            if (!empty($filters['limit'])) {
                $options['limit'] = (int)$filters['limit'];
            }

            $rawAlerts = $this->db->select('surveillance_alerts', [], $options);

            $formatted = [];
            foreach ($rawAlerts as $a) {
                $status = trim($a['status'] ?? 'Active');
                if (empty($filters['include_resolved']) && strcasecmp($status, 'Resolved') === 0) {
                    continue;
                }

                $cases = (int)($a['cases'] ?? ($a['case_count'] ?? 0));
                $threshold = (int)($a['threshold'] ?? 3);
                $severity = trim($a['severity'] ?? 'Warning');

                // Plain-language classification with dynamic outbreak threshold setting
                $plainStatus = '🟢 Normal';
                $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $dotColor = 'bg-emerald-500';

                $outbreakThreshold = class_exists('Settings') ? (int)\Settings::get('modules.surveillance.outbreak_threshold', 10) : 10;
                $effectiveThreshold = $threshold > 0 ? min($threshold, $outbreakThreshold) : $outbreakThreshold;

                if (($cases >= $effectiveThreshold || $cases >= $outbreakThreshold) && $cases >= self::MINIMUM_CASE_FLOOR) {
                    $plainStatus = '🔴 Outbreak Alert';
                    $badgeClass = 'bg-red-50 text-red-700 border-red-200';
                    $dotColor = 'bg-red-500 animate-pulse';
                } elseif ($cases >= 2 || strcasecmp($severity, 'Warning') === 0) {
                    $plainStatus = '🟡 Watch';
                    $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                    $dotColor = 'bg-amber-500';
                }

                $formatted[] = [
                    'id'               => $a['id'] ?? 0,
                    'alert_code'       => $a['alert_code'] ?? ('ALT-' . ($a['id'] ?? rand(100, 999))),
                    'disease'          => $a['disease'] ?? 'Unknown Condition',
                    'zone'             => $this->resolveZoneForBarangay($a['barangay'] ?? 'Zone 1'),
                    'barangay'         => $a['barangay'] ?? 'Caloocan District 1',
                    'cases'            => $cases,
                    'threshold'        => $threshold,
                    'variance'         => max(0, $cases - $threshold),
                    'severity'         => $severity,
                    'status'           => $status,
                    'plain_status'     => $plainStatus,
                    'badge_class'      => $badgeClass,
                    'dot_color'        => $dotColor,
                    'escalation_level' => (int)($a['escalation_level'] ?? 1),
                    'created_at'       => $a['created_at'] ?? date('Y-m-d H:i:s'),
                    'updated_at'       => $a['updated_at'] ?? ($a['created_at'] ?? date('Y-m-d H:i:s'))
                ];
            }

            return $formatted;
        } catch (\Throwable $e) {
            error_log("AlertService::getActiveAlerts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * The ONLY threshold anomaly calculation engine in the system.
     * Evaluates current cases against 12-week historical moving average (2-SD threshold).
     * Synchronously triggered on case creation/update.
     */
    public function syncThresholdBreaches(): array
    {
        $currentEpiWeek = (int)date('W');
        $currentYear = (int)date('Y');
        $breaches = [];

        try {
            $cases = $this->db->select('surveillance_cases', [], ['order' => 'onset_date.desc,created_at.desc', 'limit' => 2000]);
        } catch (\Throwable $e) {
            error_log("AlertService::syncThresholdBreaches case fetch error: " . $e->getMessage());
            return [];
        }

        // Aggregate cases by disease + zone + epi_week
        $grid = [];
        foreach ($cases as $c) {
            $disease = trim($c['disease'] ?? '');
            if (empty($disease)) continue;

            $brgy = trim($c['barangay'] ?? ($c['address'] ?? ''));
            $zone = $this->resolveZoneForBarangay($brgy);

            $dateStr = $c['onset_date'] ?? ($c['created_at'] ?? date('Y-m-d'));
            $timestamp = strtotime($dateStr) ?: time();
            $week = (int)date('W', $timestamp);
            $year = (int)date('Y', $timestamp);

            if ($year === $currentYear || ($year === $currentYear - 1 && $week > 40)) {
                $grid[$disease][$zone][$week] = ($grid[$disease][$zone][$week] ?? 0) + 1;
            }
        }

        // Fetch existing alerts to update idempotently
        try {
            $existingAlerts = $this->db->select('surveillance_alerts', [], ['limit' => 500]);
        } catch (\Throwable $e) {
            $existingAlerts = [];
        }

        $alertLookup = [];
        foreach ($existingAlerts as $ea) {
            $eaDisease = strtolower(trim($ea['disease'] ?? ''));
            $eaZone = strtolower(trim($this->resolveZoneForBarangay($ea['barangay'] ?? '')));
            $alertLookup[$eaDisease . '|' . $eaZone] = $ea;
        }

        foreach ($grid as $disease => $zoneData) {
            foreach ($zoneData as $zone => $weekCounts) {
                $currentCount = $weekCounts[$currentEpiWeek] ?? 0;

                // 12-week moving window statistics
                $windowCounts = [];
                for ($w = 1; $w <= self::EPIDEMIOLOGICAL_WINDOW_WEEKS; $w++) {
                    $targetWeek = $currentEpiWeek - $w;
                    if ($targetWeek <= 0) $targetWeek += 52;
                    $windowCounts[] = $weekCounts[$targetWeek] ?? 0;
                }

                $n = count($windowCounts);
                $mean = array_sum($windowCounts) / max(1, $n);
                $variance = 0.0;
                foreach ($windowCounts as $val) {
                    $variance += pow($val - $mean, 2);
                }
                $sd = ($n > 1) ? sqrt($variance / ($n - 1)) : 0.0;

                $dynamicThreshold = max(self::MINIMUM_CASE_FLOOR, (int)round($mean + (self::SD_MULTIPLIER * $sd)));
                $watchThreshold = max(2, (int)round($mean + (1.0 * $sd)));

                $isOutbreak = ($currentCount >= self::MINIMUM_CASE_FLOOR) && ($currentCount >= $dynamicThreshold);
                $isWatch = (!$isOutbreak) && ($currentCount >= $watchThreshold);

                $lookupKey = strtolower($disease) . '|' . strtolower($zone);
                $existing = $alertLookup[$lookupKey] ?? null;

                if ($isOutbreak || $isWatch) {
                    $severity = $isOutbreak ? 'Critical' : 'Warning';
                    $effectiveThreshold = $isOutbreak ? $dynamicThreshold : $watchThreshold;

                    if ($existing) {
                        // Upsert: update existing alert
                        $this->db->update('surveillance_alerts', [
                            'cases'      => $currentCount,
                            'threshold'  => $effectiveThreshold,
                            'severity'   => $severity,
                            'status'     => 'Active',
                            'timestamp'  => date('Y-m-d H:i:s')
                        ], ['id' => (int)$existing['id']], true);

                        $breaches[] = array_merge($existing, [
                            'cases' => $currentCount,
                            'threshold' => $effectiveThreshold,
                            'severity' => $severity
                        ]);
                    } else {
                        // Insert new alert
                        $prefix = $isOutbreak ? 'ALT' : 'WCH';
                        $alertCode = $prefix . '-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $disease), 0, 3)) . '-' . date('yW') . '-' . rand(10, 99);
                        $newAlert = [
                            'alert_code'       => $alertCode,
                            'disease'          => $disease,
                            'barangay'         => $zone,
                            'cases'            => $currentCount,
                            'threshold'        => $effectiveThreshold,
                            'severity'         => $severity,
                            'status'           => 'Active',
                            'escalation_level' => $isOutbreak ? 2 : 1,
                            'timestamp'        => date('Y-m-d H:i:s'),
                            'created_at'       => date('Y-m-d H:i:s')
                        ];
                        try {
                            $this->db->query('surveillance_alerts', 'POST', $newAlert);
                            $breaches[] = $newAlert;
                        } catch (\Throwable $e) {
                            error_log("Alert insert failed: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $breaches;
    }

    /**
     * Resolves Caloocan administrative Zone for a given Barangay name.
     */
    public function resolveZoneForBarangay(string $barangayName): string
    {
        if (empty($barangayName)) return 'Zone 1';

        if (preg_match('/Zone\s*(\d+)/i', $barangayName, $m)) {
            return 'Zone ' . $m[1];
        }

        if (preg_match('/\b(\d{1,3})\b/', $barangayName, $m)) {
            $bNum = (int)$m[1];
            if ($bNum >= 1 && $bNum <= 4) return 'Zone 1';
            if ($bNum >= 77 && $bNum <= 81) return 'Zone 7';
            if ($bNum >= 82 && $bNum <= 85) return 'Zone 8';
            if ($bNum >= 132 && $bNum <= 140) return 'Zone 12';
            if ($bNum >= 141 && $bNum <= 150) return 'Zone 13';
            if ($bNum >= 151 && $bNum <= 160) return 'Zone 14';
            if ($bNum >= 161 && $bNum <= 164) return 'Zone 15';
        }

        return 'Zone 7';
    }

    /**
     * Generates 12-week longitudinal epidemic curves for Chart.js
     */
    public function get12WeekTrendData(): array
    {
        $currentWeek = (int)date('W');
        $weeks = [];
        for ($i = 11; $i >= 0; $i--) {
            $w = $currentWeek - $i;
            if ($w <= 0) $w += 52;
            $weeks[] = "Wk " . $w;
        }

        $tracked = ['Dengue', 'Leptospirosis', 'Influenza'];
        $series = [];

        try {
            $cases = $this->db->select('surveillance_cases', [], ['order' => 'onset_date.desc', 'limit' => 1500]);
            $matrix = [];
            foreach ($cases as $c) {
                $d = trim($c['disease'] ?? '');
                $dateStr = $c['onset_date'] ?? ($c['created_at'] ?? '');
                $ts = strtotime($dateStr) ?: time();
                $wk = (int)date('W', $ts);
                $wkLabel = "Wk " . $wk;
                if (in_array($wkLabel, $weeks)) {
                    $matrix[$d][$wkLabel] = ($matrix[$d][$wkLabel] ?? 0) + 1;
                }
            }

            foreach ($tracked as $d) {
                $dataPoints = [];
                foreach ($weeks as $wkLabel) {
                    $dataPoints[] = $matrix[$d][$wkLabel] ?? 0;
                }
                $series[$d] = $dataPoints;
            }
        } catch (\Throwable $e) {
            foreach ($tracked as $d) {
                $series[$d] = array_fill(0, 12, 0);
            }
        }

        return [
            'labels' => $weeks,
            'series' => $series
        ];
    }
}
