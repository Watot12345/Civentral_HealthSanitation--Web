<?php
// app/services/ClinicalSurveillanceService.php
// Pure Clinical Bridge connecting Health Services Consultations with Disease Surveillance
// Enforces Single Authorized Write Path, Clinician Confirmation Gate, and Patient Deduplication.

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../app/Models/SurveillanceCase.php';
require_once __DIR__ . '/../../app/services/AlertService.php';

use App\Services\AlertService;

class ClinicalSurveillanceService
{
    private Database $db;

    // Structured ICD-10 Surveillance Mapping (Primary Dictionary)
    public static array $icdMap = [
        'A90'   => ['disease' => 'Dengue', 'severity' => 'Moderate'],
        'A91'   => ['disease' => 'Dengue', 'severity' => 'Critical'],
        'A97'   => ['disease' => 'Dengue', 'severity' => 'Critical'],
        'A27'   => ['disease' => 'Leptospirosis', 'severity' => 'Critical'],
        'A27.0' => ['disease' => 'Leptospirosis', 'severity' => 'Critical'],
        'A15'   => ['disease' => 'Tuberculosis', 'severity' => 'Moderate'],
        'A16'   => ['disease' => 'Tuberculosis', 'severity' => 'Moderate'],
        'A17'   => ['disease' => 'Tuberculosis', 'severity' => 'High'],
        'J09'   => ['disease' => 'Influenza', 'severity' => 'Moderate'],
        'J10'   => ['disease' => 'Influenza', 'severity' => 'Moderate'],
        'J11'   => ['disease' => 'Influenza', 'severity' => 'Moderate'],
        'J12'   => ['disease' => 'Influenza', 'severity' => 'Moderate'],
        'J18'   => ['disease' => 'Influenza', 'severity' => 'Moderate'],
        'A09'   => ['disease' => 'Acute Gastroenteritis', 'severity' => 'Moderate'],
        'A00'   => ['disease' => 'Acute Gastroenteritis', 'severity' => 'Critical'],
        'A01'   => ['disease' => 'Acute Gastroenteritis', 'severity' => 'Critical'],
        'B05'   => ['disease' => 'Measles', 'severity' => 'Critical'],
        'B06'   => ['disease' => 'Measles', 'severity' => 'High'],
        'U07.1' => ['disease' => 'COVID-19', 'severity' => 'High'],
        'U07.2' => ['disease' => 'COVID-19', 'severity' => 'Moderate'],
        'I10'   => ['disease' => 'Hypertension', 'severity' => 'Low'],
        'E11'   => ['disease' => 'Diabetes Mellitus', 'severity' => 'Low']
    ];

    // Syndromic Diagnosis Keywords (Fallback Dictionary)
    public static array $keywordMap = [
        'Dengue' => [
            'keywords' => ['dengue', 'denv', 'breakbone', 'dengue fever', 'ns1 positive', 'thrombocytopenia with fever'],
            'severity' => 'Moderate'
        ],
        'Leptospirosis' => [
            'keywords' => ['leptospirosis', 'lepto', 'weil', 'rat urine exposure', 'conjunctival suffusion with calf pain'],
            'severity' => 'Critical'
        ],
        'Tuberculosis' => [
            'keywords' => ['tuberculosis', 'ptb', 'tb dots', 'pulmonary tb', 'acid fast bacilli', 'hemoptysis with chronic cough'],
            'severity' => 'Moderate'
        ],
        'Influenza' => [
            'keywords' => ['influenza', 'flu', 'acute respiratory infection', 'ili', 'pneumonia', 'bronchitis', 'severe uri'],
            'severity' => 'Moderate'
        ],
        'Acute Gastroenteritis' => [
            'keywords' => ['gastroenteritis', 'age', 'acute watery diarrhea', 'cholera', 'typhoid', 'severe dehydration with diarrhea'],
            'severity' => 'Moderate'
        ],
        'Measles' => [
            'keywords' => ['measles', 'tigdas', 'rubeola', 'koplik spots', 'maculopapular rash with coryza'],
            'severity' => 'Critical'
        ],
        'COVID-19' => [
            'keywords' => ['covid', 'covid-19', 'sars-cov-2', 'corona virus disease'],
            'severity' => 'High'
        ],
        'Hypertension' => [
            'keywords' => ['hypertension', 'essential hypertension', 'htn', 'high blood pressure'],
            'severity' => 'Low'
        ],
        'Diabetes Mellitus' => [
            'keywords' => ['diabetes', 'type 2 diabetes', 'dm', 't2dm', 'hyperglycemia'],
            'severity' => 'Low'
        ]
    ];

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Detects if a diagnosis / symptoms / ICD-10 matches a reportable surveillance disease.
     */
    public function detectDisease($diagnosis = '', $symptoms = '', $vitals = [], string $icdCode = ''): ?array
    {
        $cleanIcd = strtoupper(trim($icdCode));
        if ($cleanIcd !== '') {
            foreach (self::$icdMap as $code => $info) {
                if ($cleanIcd === $code || str_starts_with($cleanIcd, $code . '.')) {
                    return [
                        'disease'    => $info['disease'],
                        'severity'   => $info['severity'],
                        'icd_code'   => $cleanIcd,
                        'match_type' => 'ICD-10 Code'
                    ];
                }
            }
        }

        $textToScan = strtolower($diagnosis . ' ' . (is_array($symptoms) ? implode(' ', $symptoms) : $symptoms));
        if (empty(trim($textToScan))) return null;

        foreach (self::$keywordMap as $disease => $config) {
            foreach ($config['keywords'] as $kw) {
                if (str_contains($textToScan, strtolower($kw))) {
                    return [
                        'disease'    => $disease,
                        'severity'   => $config['severity'],
                        'icd_code'   => null,
                        'match_type' => 'Clinical Keyword'
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Single authorized write path into surveillance_cases.
     * Enforces clinician confirmation ($confirmedByClinician must be true).
     * Deduplicates per patient within 14 days.
     */
    public function syncConsultation(array $consultation, ?array $patient = null, bool $confirmedByClinician = false): ?array
    {
        if (!$confirmedByClinician) {
            return null; // Zero silent auto-insertion
        }

        $diagnosis = $consultation['diagnosis'] ?? '';
        $symptoms  = $consultation['symptoms'] ?? '';
        $vitals    = $consultation['vital_signs'] ?? [];
        $icdCode   = $consultation['icd_code'] ?? '';

        $match = $this->detectDisease($diagnosis, $symptoms, $vitals, $icdCode);
        if (!$match) {
            return null;
        }

        // Resolve patient identity
        $patientId = $consultation['patient_id'] ?? 0;
        if (!$patient && !empty($patientId)) {
            try {
                $pRes = $this->db->select('patients', ['id' => 'eq.' . $patientId]);
                $patient = $pRes[0] ?? null;
            } catch (\Throwable $e) {}
        }

        $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
        if (empty($patientName)) {
            $patientName = $consultation['patient_name'] ?? 'Anonymous Patient';
        }

        $rawBarangay = trim($patient['barangay'] ?? ($consultation['barangay'] ?? 'Barangay 77'));
        $address     = trim($patient['address'] ?? ($consultation['address'] ?? $rawBarangay));
        $contact     = trim($patient['contact'] ?? ($consultation['contact_number'] ?? ''));
        $gender      = trim($patient['gender'] ?? ($consultation['gender'] ?? 'Unknown'));
        $age         = (int)($patient['age'] ?? ($consultation['age'] ?? 0));
        $consultDate = $consultation['date'] ?? date('Y-m-d');
        $consId      = $consultation['consultation_id'] ?? ($consultation['id'] ?? '');
        $facilityTag = 'Health Center Consultation (' . ($consId ?: 'HC') . ')';

        $caseModel = new SurveillanceCase($this->db);

        // Deduplication: Check if patient already has a case for this disease in the last 14 days
        $existingCase = null;
        try {
            $recentCases = $this->db->select('surveillance_cases', [
                'disease'      => 'eq.' . $match['disease'],
                'patient_name' => 'eq.' . $patientName
            ], ['order' => 'created_at.desc', 'limit' => 5]);

            foreach ($recentCases as $rc) {
                $caseDate = $rc['onset_date'] ?? substr($rc['created_at'] ?? '', 0, 10);
                $daysDiff = abs((strtotime($consultDate) - strtotime($caseDate)) / 86400);
                if ($daysDiff <= 14) {
                    $existingCase = $rc;
                    break;
                }
            }
        } catch (\Throwable $e) {}

        if ($existingCase && !empty($existingCase['id'])) {
            // Update existing case instead of duplicating
            $updateData = [
                'disease'            => $match['disease'],
                'patient_name'       => $patientName,
                'age'                => $age,
                'gender'             => $gender,
                'address'            => $address,
                'barangay'           => $rawBarangay,
                'contact_number'     => $contact,
                'symptoms'           => is_array($symptoms) ? implode(', ', $symptoms) : $symptoms,
                'onset_date'         => $consultDate,
                'reporting_facility' => $facilityTag,
                'severity'           => $match['severity'],
                'updated_at'         => date('Y-m-d H:i:s')
            ];

            try {
                $this->db->update('surveillance_cases', $updateData, ['id' => 'eq.' . $existingCase['id']]);
                AlertService::getInstance($this->db)->syncThresholdBreaches();
                return array_merge($existingCase, $updateData);
            } catch (\Throwable $e) {
                error_log("ClinicalSurveillanceService update error: " . $e->getMessage());
                return null;
            }
        }

        // Insert new confirmed surveillance case
        $caseCode = 'CS-' . date('Y') . '-' . str_pad((string)rand(100, 9999), 4, '0', STR_PAD_LEFT);
        $caseData = [
            'case_code'          => $caseCode,
            'disease'            => $match['disease'],
            'patient_name'       => $patientName,
            'age'                => $age,
            'gender'             => $gender,
            'address'            => $address,
            'barangay'           => $rawBarangay,
            'contact_number'     => $contact,
            'symptoms'           => is_array($symptoms) ? implode(', ', $symptoms) : $symptoms,
            'onset_date'         => $consultDate,
            'reporting_facility' => $facilityTag,
            'status'             => 'confirmed',
            'severity'           => $match['severity'],
            'reported_by'        => $consultation['doctor_name'] ?? 'Attending Physician',
            'created_at'         => date('Y-m-d H:i:s'),
            'updated_at'         => date('Y-m-d H:i:s')
        ];

        try {
            $created = $caseModel->create($caseData);
            AlertService::getInstance($this->db)->syncThresholdBreaches();
            return $created;
        } catch (\Throwable $e) {
            error_log("ClinicalSurveillanceService insert error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Resolves Caloocan administrative Zone for a given Barangay name.
     */
    public function resolveZoneForBarangay(string $barangayName): string
    {
        return AlertService::getInstance($this->db)->resolveZoneForBarangay($barangayName);
    }

    /**
     * Checks active outbreak alerts for patient's barangay/zone to power informational banner.
     */
    public function getBarangayActiveSignals(string $barangay): array
    {
        $cleanBrgy = trim($barangay);
        if (empty($cleanBrgy)) {
            return ['has_active' => false, 'count' => 0, 'alerts' => []];
        }

        $zone = $this->resolveZoneForBarangay($cleanBrgy);
        $activeAlerts = AlertService::getInstance($this->db)->getActiveAlerts();

        $matchingAlerts = [];
        foreach ($activeAlerts as $a) {
            if (strcasecmp($a['zone'], $zone) === 0 || str_contains(strtolower($a['barangay']), strtolower($cleanBrgy))) {
                $matchingAlerts[] = $a;
            }
        }

        return [
            'has_active' => count($matchingAlerts) > 0,
            'zone'       => $zone,
            'barangay'   => $cleanBrgy,
            'count'      => count($matchingAlerts),
            'alerts'     => $matchingAlerts
        ];
    }
}
