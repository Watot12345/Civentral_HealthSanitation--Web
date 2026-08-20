<?php
// app/services/SurveillanceService.php
/**
 * Unified Health Surveillance Service
 * Standard-compliant (ISO/IEC 25010) epidemiological engine for disease surveillance.
 * Merges primary sources:
 *   1. surveillance_cases  (investigator-reported cases)      -> date: onset_date
 *   2. consultations       (Health Center clinical diagnoses) -> date: date
 *   3. immunizations       (overdue / VPD alerts)             -> date: next_due_date
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Barangay.php';

class SurveillanceService
{
    private Database $db;
    private static ?array $cachedPatients = null;
    private static ?array $cachedChildren = null;
    private static ?array $cachedCases = null;

    private const DISEASE_KEYWORDS = [
        'dengue'                => ['dengue', 'dhf', 'denv', 'breakbone', 'dengue fever', 'severe dengue', 'dengue with warning'],
        'influenza'             => ['influenza', 'flu', 'acute respiratory infection', 'ari', 'ili', 'pneumonia', 'bronchitis'],
        'leptospirosis'         => ['leptospirosis', 'lepto', 'weil', 'rat urine', 'flood contact fever'],
        'measles'               => ['measles', 'tigdas', 'rubeola', 'rubella', 'german measles'],
        'acute gastroenteritis' => ['gastroenteritis', 'age', 'acute watery diarrhea', 'severe diarrhea', 'cholera', 'typhoid', 'food poisoning', 'diarrhea'],
        'tuberculosis'          => ['tuberculosis', 'ptb', 'pulmonary tuberculosis', 'koch', 'tb active'],
        'covid-19'              => ['covid', 'covid-19', 'sars-cov-2', 'coronavirus'],
        'hypertension'          => ['hypertension', 'essential hypertension', 'htn', 'high blood pressure'],
        'diabetes'              => ['diabetes', 'type 2 diabetes', 'dm type 2', 't2dm', 'hyperglycemia']
    ];

    private const CLUSTER_MIN_CASES = 3;
    private const CLUSTER_WINDOW_DAYS = 14;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Extracts normalized numeric barangay or clean name string.
     */
    public function normalizeBarangay(?string $raw)
    {
        if (!$raw) return null;
        $raw = trim($raw);
        if (preg_match('/\b(\d{1,3})\b/', $raw, $m)) {
            return (int)$m[1];
        }
        return $raw;
    }

    /**
     * Maps diagnosis text and symptoms to standardized disease categories.
     */
    public function classifyDiagnosis(?string $diagnosis, ?string $symptoms = ''): ?string
    {
        $searchText = strtolower(trim(($diagnosis ?? '') . ' ' . ($symptoms ?? '')));
        if (empty($searchText)) return null;

        foreach (self::DISEASE_KEYWORDS as $disease => $keywords) {
            foreach ($keywords as $kw) {
                if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $searchText) || str_contains($searchText, $kw)) {
                    return ucwords($disease);
                }
            }
        }
        return null;
    }

    /**
     * Fetches unified surveillance cases from all 3 primary health sources.
     */
    public function getUnifiedCases(?string $dateFrom = null, ?string $dateTo = null, $barangay = null): array
    {
        $cases = array_merge(
            $this->fetchSurveillanceCases($dateFrom, $dateTo, $barangay),
            $this->fetchConsultationCases($dateFrom, $dateTo, $barangay),
            $this->fetchImmunizationAlerts($dateFrom, $dateTo, $barangay)
        );

        usort($cases, fn($a, $b) => strcmp($b['case_date'] ?? '', $a['case_date'] ?? ''));
        return $cases;
    }

    /**
     * 1. Investigator Reported Cases
     */
    private function fetchSurveillanceCases(?string $dateFrom, ?string $dateTo, $barangay): array
    {
        $filters = [];
        if ($dateFrom && $dateTo) {
            $filters['onset_date'] = ['gte' => $dateFrom, 'lte' => $dateTo];
        } elseif ($dateTo) {
            $filters['onset_date'] = 'lte.' . $dateTo;
        } elseif ($dateFrom) {
            $filters['onset_date'] = 'gte.' . $dateFrom;
        }

        try {
            $rows = $this->db->select('surveillance_cases', $filters, ['order' => 'onset_date.desc']);
        } catch (Throwable $e) {
            error_log("SurveillanceService fetchSurveillanceCases error: " . $e->getMessage());
            $rows = [];
        }

        $out = [];
        foreach ($rows as $row) {
            $caseDate = !empty($row['onset_date']) ? $row['onset_date'] : substr($row['created_at'] ?? date('Y-m-d'), 0, 10);
            if ($dateFrom && $caseDate < $dateFrom) continue;
            if ($dateTo && $caseDate > $dateTo) continue;

            $barangayNorm = $this->normalizeBarangay($row['barangay'] ?? '');
            if ($barangay !== null && $barangay !== '' && $barangayNorm != $this->normalizeBarangay($barangay)) continue;

            $out[] = [
                'id'           => 'sc_' . ($row['id'] ?? uniqid()),
                'case_code'    => $row['case_code'] ?? ('CS-' . ($row['id'] ?? '0')),
                'source'       => 'Investigator Report',
                'source_type'  => 'surveillance',
                'disease'      => ucwords(trim($row['disease'] ?? 'Unspecified')),
                'patient_name' => $row['patient_name'] ?? 'Anonymous Patient',
                'age'          => $row['age'] ?? 0,
                'gender'       => $row['gender'] ?? 'Unknown',
                'barangay'     => $barangayNorm,
                'raw_barangay' => $row['barangay'] ?? '',
                'address'      => $row['address'] ?? '',
                'symptoms'     => $row['symptoms'] ?? '',
                'case_date'    => $caseDate,
                'status'       => ucfirst($row['status'] ?? 'Suspected'),
                'severity'     => ucfirst($row['severity'] ?? 'Moderate')
            ];
        }
        return $out;
    }

    /**
     * 2. Health Center Doctor Consultations & Triage Diagnoses
     */
    private function fetchConsultationCases(?string $dateFrom, ?string $dateTo, $barangay): array
    {
        $filters = [];
        if ($dateFrom && $dateTo) {
            $filters['date'] = ['gte' => $dateFrom, 'lte' => $dateTo];
        } elseif ($dateTo) {
            $filters['date'] = 'lte.' . $dateTo;
        } elseif ($dateFrom) {
            $filters['date'] = 'gte.' . $dateFrom;
        }

        try {
            $consultations = $this->db->select('consultations', $filters, ['order' => 'date.desc', 'limit' => 300]);
        } catch (Throwable $e) {
            error_log("SurveillanceService fetchConsultationCases error: " . $e->getMessage());
            $consultations = [];
        }

        if (empty($consultations)) return [];

        // Fetch patients to resolve demographic addresses & barangay with static cache
        if (self::$cachedPatients === null) {
            self::$cachedPatients = [];
            try {
                $patients = $this->db->select('patients', [], ['limit' => 500]);
                foreach ($patients as $p) {
                    self::$cachedPatients[$p['id']] = $p;
                }
            } catch (Throwable $e) {}
        }
        $patientMap = self::$cachedPatients;

        $out = [];
        foreach ($consultations as $row) {
            $cDate = $row['date'] ?? date('Y-m-d');
            if ($dateFrom && $cDate < $dateFrom) continue;
            if ($dateTo && $cDate > $dateTo) continue;

            $disease = $this->classifyDiagnosis($row['diagnosis'] ?? '', $row['symptoms'] ?? '');
            if ($disease === null) continue; // Not a notifiable surveillance disease

            $patient = $patientMap[$row['patient_id'] ?? 0] ?? null;
            $rawBarangay = $patient['barangay'] ?? ($row['barangay'] ?? '');
            $barangayNorm = $this->normalizeBarangay($rawBarangay);
            if (!$barangayNorm) continue;

            if ($barangay !== null && $barangay !== '' && $barangayNorm != $this->normalizeBarangay($barangay)) continue;

            $pName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
            if (empty($pName)) $pName = 'Health Center Patient #' . ($row['patient_id'] ?? $row['id']);

            $out[] = [
                'id'           => 'cons_' . ($row['id'] ?? uniqid()),
                'case_code'    => $row['consultation_id'] ?? ('CNS-' . ($row['id'] ?? '0')),
                'source'       => 'Clinic Consultation',
                'source_type'  => 'consultation',
                'disease'      => $disease,
                'patient_name' => $pName,
                'age'          => $patient['age'] ?? 0,
                'gender'       => $patient['gender'] ?? 'Unknown',
                'barangay'     => $barangayNorm,
                'raw_barangay' => $rawBarangay,
                'address'      => $patient['address'] ?? '',
                'symptoms'     => $row['symptoms'] ?? ($row['diagnosis'] ?? ''),
                'case_date'    => $cDate,
                'status'       => 'Clinical Diagnosis',
                'severity'     => 'Moderate'
            ];
        }
        return $out;
    }

    /**
     * 3. Overdue Immunizations / Vaccine-Preventable Disease Defaulters
     */
    private function fetchImmunizationAlerts(?string $dateFrom, ?string $dateTo, $barangay): array
    {
        $filters = [
            'date_administered' => 'is.null',
            'next_due_date'     => 'lte.' . date('Y-m-d'),
        ];

        try {
            $overdue = $this->db->select('immunizations', $filters, ['limit' => 200]);
        } catch (Throwable $e) {
            $overdue = [];
        }

        if (empty($overdue)) return [];

        if (self::$cachedChildren === null) {
            self::$cachedChildren = [];
            try {
                $children = $this->db->select('children', [], ['limit' => 500]);
                foreach ($children as $ch) {
                    self::$cachedChildren[$ch['id']] = $ch;
                }
            } catch (Throwable $e) {}
        }
        $childMap = self::$cachedChildren;

        $out = [];
        foreach ($overdue as $row) {
            $child = $childMap[$row['child_id'] ?? 0] ?? null;
            $rawBarangay = $child['barangay'] ?? '';
            $barangayNorm = $this->normalizeBarangay($rawBarangay);
            if (!$barangayNorm) continue;

            if ($barangay !== null && $barangay !== '' && $barangayNorm != $this->normalizeBarangay($barangay)) continue;

            $alertDate = $row['next_due_date'] ?? date('Y-m-d');
            if ($dateFrom && $alertDate < $dateFrom) continue;
            if ($dateTo && $alertDate > $dateTo) continue;

            $chName = trim(($child['first_name'] ?? '') . ' ' . ($child['last_name'] ?? ''));
            if (empty($chName)) $chName = 'Pediatric Child #' . ($row['child_id'] ?? $row['id']);

            $vaccineName = trim($row['vaccine'] ?? 'Routine Vaccine');

            $out[] = [
                'id'           => 'imm_' . ($row['id'] ?? uniqid()),
                'case_code'    => 'VPD-' . ($row['id'] ?? '0'),
                'source'       => 'Immunization Alert',
                'source_type'  => 'immunization',
                'disease'      => 'VPD Risk: ' . $vaccineName,
                'patient_name' => $chName,
                'age'          => 1,
                'gender'       => $child['gender'] ?? 'Unknown',
                'barangay'     => $barangayNorm,
                'raw_barangay' => $rawBarangay,
                'address'      => $child['address'] ?? '',
                'symptoms'     => 'Overdue vaccination: ' . $vaccineName . ' (Defaulter)',
                'case_date'    => $alertDate,
                'status'       => 'Overdue Defaulter',
                'severity'     => 'High'
            ];
        }
        return $out;
    }

    /**
     * Outbreak cluster detection rewired to canonical AlertService (2-SD Statistical Engine).
     * Guarantees 100% data parity across Mapping & Clustering and Surveillance Dashboard.
     */
    public function detectClusters(array $cases = []): array
    {
        require_once __DIR__ . '/AlertService.php';
        $alertService = \App\Services\AlertService::getInstance($this->db);
        $alerts = $alertService->getActiveAlerts();

        $clusters = [];
        foreach ($alerts as $a) {
            $rawBrgy = $a['barangay'] ?? ($a['zone'] ?? '');
            $bNum = is_numeric($rawBrgy) ? (int)$rawBrgy : $this->normalizeBarangay($rawBrgy);
            $clusters[] = [
                'barangay'     => $bNum ?: $rawBrgy,
                'disease'      => $a['disease'],
                'case_count'   => (int)$a['cases'],
                'window_start' => substr($a['created_at'] ?? date('Y-m-d'), 0, 10),
                'window_end'   => date('Y-m-d'),
                'risk_level'   => (str_contains($a['plain_status'] ?? '', 'Outbreak') || ($a['severity'] ?? '') === 'Critical') ? 'High Outbreak' : 'Moderate',
                'alert_code'   => $a['alert_code'] ?? '',
                'threshold'    => $a['threshold'] ?? 3
            ];
        }

        return $clusters;
    }

    private function daysBetween(string $dateA, string $dateB): int
    {
        try {
            $a = new DateTime($dateA);
            $b = new DateTime($dateB);
            return abs($a->diff($b)->days);
        } catch (Throwable $e) {
            return 999;
        }
    }

    /**
     * Generates 6-month timeline labels & spread tracking metrics.
     */
    public function getTimelineData(array $cases): array
    {
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[] = date('M Y', strtotime("-{$i} months"));
        }

        $monthlyCounts = array_fill_keys($months, 0);
        $diseaseMonthly = [];

        foreach ($cases as $c) {
            $m = date('M Y', strtotime($c['case_date'] ?? date('Y-m-d')));
            if (isset($monthlyCounts[$m])) {
                $monthlyCounts[$m]++;
            }
            $d = $c['disease'] ?? 'Other';
            if (!isset($diseaseMonthly[$d])) {
                $diseaseMonthly[$d] = array_fill_keys($months, 0);
            }
            if (isset($diseaseMonthly[$d][$m])) {
                $diseaseMonthly[$d][$m]++;
            }
        }

        return [
            'months'          => $months,
            'total_trend'     => array_values($monthlyCounts),
            'disease_monthly' => $diseaseMonthly
        ];
    }

    /**
     * Calculates summary metrics for dashboard KPI cards.
     */
    public function getSummary(array $cases): array
    {
        $byDisease = [];
        $byBarangay = [];
        $bySource = [];

        foreach ($cases as $case) {
            $d = $case['disease'] ?? 'Unspecified';
            $b = $case['barangay'] ?? 'Unknown';
            $s = $case['source'] ?? 'General';

            $byDisease[$d] = ($byDisease[$d] ?? 0) + 1;
            $byBarangay[$b] = ($byBarangay[$b] ?? 0) + 1;
            $bySource[$s] = ($bySource[$s] ?? 0) + 1;
        }

        arsort($byDisease);
        arsort($byBarangay);

        return [
            'total_cases'  => count($cases),
            'by_disease'   => $byDisease,
            'by_barangay'  => $byBarangay,
            'by_source'    => $bySource,
            'top_disease'  => !empty($byDisease) ? array_key_first($byDisease) : 'None',
            'top_barangay' => !empty($byBarangay) ? array_key_first($byBarangay) : 'None',
        ];
    }
}
