<?php
// database/seeders/ProfessionalDataSeeder.php
/**
 * Civentral LGU - Professional Enterprise Data Seeder
 * 
 * Strict INSERT-ONLY operation.
 * Preserves all existing records; skips duplicates matching unique business keys.
 * Accurately models Philippine Local Government Unit (LGU) Health, Sanitation,
 * Wastewater Management, and Epidemiological Surveillance datasets.
 */

namespace Database\Seeders;

require_once __DIR__ . '/../../Core/Env.php';
\Env::load();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../app/helpers/EncryptionHelper.php';

use Database;
use EncryptionHelper;
use Throwable;

class ProfessionalDataSeeder
{
    private Database $db;
    private array $summary = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Safe Insert-Only Helper
     * Checks if a record with the same unique key already exists.
     * If not found, applies column encryption where required and inserts.
     */
    private function insertIfMissing(string $table, string $uniqueKey, array $data, ?string $encryptModel = null): ?array
    {
        if (!isset($this->summary[$table])) {
            $this->summary[$table] = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];
        }

        try {
            // Check for existing record
            $existing = $this->db->select($table, [$uniqueKey => $data[$uniqueKey]], ['limit' => 1], true);
            if (!empty($existing)) {
                $this->summary[$table]['skipped']++;
                return $existing[0];
            }

            // Encrypt sensitive fields if configured for this table
            $payload = $data;
            if ($encryptModel !== null && class_exists('EncryptionHelper')) {
                $payload = EncryptionHelper::encryptModel($encryptModel, $payload);
            }

            // Insert via Database API (service key bypasses RLS safely)
            $result = $this->db->query($table, 'POST', $payload, [], ['ignore_duplicates' => true], true);
            $this->summary[$table]['inserted']++;
            
            return !empty($result) && is_array($result) ? $result[0] : null;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '409') || str_contains($msg, 'duplicate key') || str_contains($msg, 'unique constraint')) {
                $this->summary[$table]['skipped']++;
                return null;
            }
            $this->summary[$table]['failed']++;
            echo "   ⚠️ Warning inserting into {$table} [{$data[$uniqueKey]}]: " . $msg . "\n";
            return null;
        }
    }

    /**
     * Run all seeders in relational dependency order
     */
    public function run(): array
    {
        echo "====================================================================\n";
        echo "🚀 CIVENTRAL LGU - PROFESSIONAL DATA SEEDER (INSERT-ONLY)\n";
        echo "====================================================================\n\n";

        $patientMap = $this->seedPatients();
        $this->seedAppointmentsAndTriage($patientMap);
        $childMap = $this->seedChildrenAndImmunizations();
        $this->seedSanitationPermitsAndInspections();
        $this->seedWastewaterAndSeptage();
        $this->seedDiseaseSurveillance();
        $this->seedAnnouncements();

        echo "\n====================================================================\n";
        echo "📊 SEEDING EXECUTION SUMMARY (INSERT-ONLY):\n";
        echo "====================================================================\n";
        foreach ($this->summary as $table => $stats) {
            printf(" • %-25s : %3d inserted | %3d skipped | %3d failed\n", 
                $table, $stats['inserted'], $stats['skipped'], $stats['failed']);
        }
        echo "====================================================================\n";
        echo "✅ Seeding process completed successfully without modifying existing data!\n\n";

        return $this->summary;
    }

    /**
     * 1. Patients Seeder (Diverse demographics & authentic medical histories)
     */
    private function seedPatients(): array
    {
        echo "[1/6] Seeding Primary Healthcare Patients...\n";

        $patients = [
            [
                'patient_id' => 'P-2026-001',
                'first_name' => 'Ricardo',
                'middle_name' => 'Dela Cruz',
                'last_name' => 'Alcantara',
                'birth_date' => '1968-04-12',
                'gender' => 'Male',
                'blood_type' => 'O+',
                'contact' => '09173456781',
                'email' => 'ricardo.alcantara@example.ph',
                'address' => '78 Rizal Avenue, Grace Park West',
                'barangay' => 'Barangay 8',
                'emergency_contact' => 'Corazon Alcantara (Spouse)',
                'emergency_contact_number' => '09173456782',
                'allergies' => 'Penicillin',
                'medical_history' => 'Hypertension Stage 2 (Dx 2018); Dyslipidemia',
                'registration_date' => '2026-01-15',
                'status' => 'active'
            ],
            [
                'patient_id' => 'P-2026-002',
                'first_name' => 'Jocelyn',
                'middle_name' => 'Villanueva',
                'last_name' => 'Morales',
                'birth_date' => '1996-09-23',
                'gender' => 'Female',
                'blood_type' => 'A+',
                'contact' => '09184567892',
                'email' => 'jocelyn.morales@example.ph',
                'address' => '104 5th Avenue, Grace Park East',
                'barangay' => 'Barangay 12',
                'emergency_contact' => 'Eduardo Morales (Husband)',
                'emergency_contact_number' => '09184567893',
                'allergies' => 'None known',
                'medical_history' => 'G1P0 Pregnancy at 28 weeks AOG; Mild iron-deficiency anemia',
                'registration_date' => '2026-02-10',
                'status' => 'active'
            ],
            [
                'patient_id' => 'P-2026-003',
                'first_name' => 'Eduardo',
                'middle_name' => 'Soriano',
                'last_name' => 'Manalo',
                'birth_date' => '1982-11-05',
                'gender' => 'Male',
                'blood_type' => 'B+',
                'contact' => '09195678903',
                'email' => 'eduardo.manalo@example.ph',
                'address' => '214 Samson Road, Sangandaan',
                'barangay' => 'Barangay 77',
                'emergency_contact' => 'Carmelita Manalo (Sister)',
                'emergency_contact_number' => '09195678904',
                'allergies' => 'Sulfa drugs',
                'medical_history' => 'Type 2 Diabetes Mellitus (Dx 2021); Mild peripheral neuropathy',
                'registration_date' => '2026-02-28',
                'status' => 'active'
            ],
            [
                'patient_id' => 'P-2026-004',
                'first_name' => 'Patricia Mae',
                'middle_name' => 'Galang',
                'last_name' => 'Reyes',
                'birth_date' => '2004-03-18',
                'gender' => 'Female',
                'blood_type' => 'AB+',
                'contact' => '09206789014',
                'email' => 'patricia.reyes@example.ph',
                'address' => '321 C-3 Road, Maypajo',
                'barangay' => 'Barangay 80',
                'emergency_contact' => 'Lourdes Reyes (Mother)',
                'emergency_contact_number' => '09206789015',
                'allergies' => 'Shellfish',
                'medical_history' => 'Bronchial Asthma since childhood; seasonal allergic rhinitis',
                'registration_date' => '2026-03-05',
                'status' => 'active'
            ],
            [
                'patient_id' => 'P-2026-005',
                'first_name' => 'Benigno',
                'middle_name' => 'Cruz',
                'last_name' => 'Tolentino',
                'birth_date' => '1955-08-30',
                'gender' => 'Male',
                'blood_type' => 'O+',
                'contact' => '09217890125',
                'email' => 'benigno.tolentino@example.ph',
                'address' => '56 Morning Breeze Subdivision',
                'barangay' => 'Barangay 82',
                'emergency_contact' => 'Francis Tolentino (Son)',
                'emergency_contact_number' => '09217890126',
                'allergies' => 'Aspirin',
                'medical_history' => 'Osteoarthritis bilateral knees; Benign Prostatic Hyperplasia (BPH)',
                'registration_date' => '2026-03-12',
                'status' => 'active'
            ],
            [
                'patient_id' => 'P-2026-006',
                'first_name' => 'Teresa',
                'middle_name' => 'Lim',
                'last_name' => 'Ramos',
                'birth_date' => '1989-12-14',
                'gender' => 'Female',
                'blood_type' => 'O+',
                'contact' => '09228901236',
                'email' => 'teresa.ramos@example.ph',
                'address' => '89 Deparo Road, Bagong Barrio',
                'barangay' => 'Barangay 8',
                'emergency_contact' => 'Manuel Ramos (Husband)',
                'emergency_contact_number' => '09228901237',
                'allergies' => 'None known',
                'medical_history' => 'Non-toxic multinodular goiter (euthyroid); regular thyroid ultrasound monitoring',
                'registration_date' => '2026-04-02',
                'status' => 'active'
            ]
        ];

        $patientMap = [];
        foreach ($patients as $p) {
            $inserted = $this->insertIfMissing('patients', 'patient_id', $p, 'patients');
            if ($inserted && !empty($inserted['id'])) {
                $patientMap[$p['patient_id']] = (int)$inserted['id'];
            }
        }

        // Also fetch previously existing patients into the map
        $allPatients = $this->db->select('patients', [], ['select' => 'id,patient_id'], true);
        foreach ($allPatients as $row) {
            if (!empty($row['patient_id']) && !isset($patientMap[$row['patient_id']])) {
                $patientMap[$row['patient_id']] = (int)$row['id'];
            }
        }

        return $patientMap;
    }

    /**
     * 2. Appointments, Triage, Consultations & Prescriptions
     */
    private function seedAppointmentsAndTriage(array $patientMap): void
    {
        echo "[2/6] Seeding Clinical Appointments, Triage & Consultations...\n";

        // Map primary doctor (ID 3), nurse (ID 4), dentist (ID 5)
        $doctorId = 3;
        $nurseId = 4;
        $dentistId = 5;

        $p1 = $patientMap['P-2026-001'] ?? 88;
        $p2 = $patientMap['P-2026-002'] ?? 84;
        $p3 = $patientMap['P-2026-003'] ?? 85;
        $p4 = $patientMap['P-2026-004'] ?? 86;
        $p5 = $patientMap['P-2026-005'] ?? 83;

        // Appointments
        $appointments = [
            [
                'appointment_id' => 'APT-202610-001',
                'patient_id' => $p1,
                'employee_id' => $doctorId,
                'service_type' => 'Hypertension Follow-up',
                'appointment_date' => '2026-10-05',
                'appointment_time' => '09:00:00',
                'status' => 'approved',
                'priority' => 'medium',
                'notes' => 'Quarterly cardiovascular review and maintenance prescription renewal.',
                'reminder_sent' => true
            ],
            [
                'appointment_id' => 'APT-202610-002',
                'patient_id' => $p2,
                'employee_id' => $nurseId,
                'service_type' => 'Prenatal Checkup',
                'appointment_date' => '2026-10-06',
                'appointment_time' => '10:30:00',
                'status' => 'approved',
                'priority' => 'high',
                'notes' => '3rd trimester fundal height measurement & fetal heartbeat auscultation.',
                'reminder_sent' => true
            ],
            [
                'appointment_id' => 'APT-202610-003',
                'patient_id' => $p3,
                'employee_id' => $doctorId,
                'service_type' => 'Diabetes Clinic Review',
                'appointment_date' => '2026-10-07',
                'appointment_time' => '08:30:00',
                'status' => 'pending',
                'priority' => 'medium',
                'notes' => 'HbA1c laboratory review and dietary counseling.',
                'reminder_sent' => false
            ],
            [
                'appointment_id' => 'APT-202610-004',
                'patient_id' => $p4,
                'employee_id' => $dentistId,
                'service_type' => 'Dental Prophylaxis',
                'appointment_date' => '2026-10-08',
                'appointment_time' => '14:00:00',
                'status' => 'pending',
                'priority' => 'low',
                'notes' => 'Routine dental scaling and oral hygiene assessment.',
                'reminder_sent' => false
            ],
            [
                'appointment_id' => 'APT-202610-005',
                'patient_id' => $p5,
                'employee_id' => $doctorId,
                'service_type' => 'Geriatric Health Screening',
                'appointment_date' => '2026-10-09',
                'appointment_time' => '11:00:00',
                'status' => 'approved',
                'priority' => 'medium',
                'notes' => 'Senior citizen comprehensive health assessment & mobility check.',
                'reminder_sent' => true
            ]
        ];

        foreach ($appointments as $apt) {
            $this->insertIfMissing('appointments', 'appointment_id', $apt);
        }

        // Triage Queue
        $triageItems = [
            [
                'queue_number' => 'Q-20261003-002',
                'patient_id' => $p1,
                'reason_for_visit' => 'Medical Consultation',
                'status' => 'completed',
                'check_in_time' => '2026-10-03T08:30:00+00:00'
            ],
            [
                'queue_number' => 'Q-20261003-003',
                'patient_id' => $p2,
                'reason_for_visit' => 'Prenatal Checkup',
                'status' => 'completed',
                'check_in_time' => '2026-10-03T09:15:00+00:00'
            ],
            [
                'queue_number' => 'Q-20261003-004',
                'patient_id' => $p3,
                'reason_for_visit' => 'Medical Consultation',
                'status' => 'in_triage',
                'check_in_time' => '2026-10-03T10:00:00+00:00'
            ],
            [
                'queue_number' => 'Q-20261003-005',
                'patient_id' => $p4,
                'reason_for_visit' => 'Dental Services',
                'status' => 'waiting',
                'check_in_time' => '2026-10-03T10:45:00+00:00'
            ]
        ];

        foreach ($triageItems as $tq) {
            $this->insertIfMissing('triage_queue', 'queue_number', $tq);
        }

        // Triage Assessments (Vitals)
        $assessments = [
            [
                'triage_id' => 'TRG-202610-001',
                'patient_id' => $p1,
                'nurse_id' => $nurseId,
                'blood_pressure' => '138/88',
                'heart_rate' => 78,
                'temperature' => 36.6,
                'respiratory_rate' => 18,
                'oxygen_saturation' => 98,
                'weight' => 74.5,
                'height' => 168.0,
                'symptoms' => 'Occasional morning occipital headache, no chest pain or shortness of breath.',
                'priority' => 'medium',
                'allergies' => 'Penicillin',
                'medications' => 'Amlodipine 5mg OD, Losartan 50mg OD',
                'notes' => 'Blood pressure mildly elevated above target; referred to Doctor for medication titration.',
                'status' => 'consulted'
            ],
            [
                'triage_id' => 'TRG-202610-002',
                'patient_id' => $p2,
                'nurse_id' => $nurseId,
                'blood_pressure' => '115/75',
                'heart_rate' => 84,
                'temperature' => 36.8,
                'respiratory_rate' => 16,
                'oxygen_saturation' => 99,
                'weight' => 61.2,
                'height' => 155.0,
                'symptoms' => 'Routine prenatal checkup, positive fetal movement, mild pedal edema at end of day.',
                'priority' => 'medium',
                'allergies' => 'None',
                'medications' => 'Ferrous Sulfate + Folic Acid 1 tab OD',
                'notes' => 'Maternal vitals stable. Urine protein negative on dipstick.',
                'status' => 'consulted'
            ]
        ];

        foreach ($assessments as $ass) {
            $this->insertIfMissing('assessment', 'triage_id', $ass);
        }

        // Consultations
        $consultations = [
            [
                'consultation_id' => 'CONS-2026-0004',
                'patient_id' => $p1,
                'employee_id' => $doctorId,
                'date' => '2026-10-03',
                'time' => '09:00:00',
                'diagnosis' => 'Essential (Primary) Hypertension, Uncontrolled',
                'icd_code' => 'I10',
                'symptoms' => 'Morning occipital tension, elevated clinic BP readings averaging 138/88 mmHg.',
                'treatment_plan' => 'Maintain Amlodipine 5mg OD; adjust Losartan from 50mg to 100mg OD. Low-salt low-fat DASH diet emphasized. Regular morning home BP logging.',
                'notes' => 'Patient advised on dietary sodium reduction and scheduled for 4-week BP re-check.',
                'follow_up_date' => '2026-10-31',
                'status' => 'completed'
            ],
            [
                'consultation_id' => 'CONS-2026-0005',
                'patient_id' => $p3,
                'employee_id' => $doctorId,
                'date' => '2026-10-02',
                'time' => '14:20:00',
                'diagnosis' => 'Type 2 Diabetes Mellitus without complications',
                'icd_code' => 'E11.9',
                'symptoms' => 'Polyuria, mild polydipsia, FBS 142 mg/dL on recent monitoring.',
                'treatment_plan' => 'Metformin 500mg BID with meals. Reinforce carbohydrate counting and 30-minute daily brisk walking.',
                'notes' => 'Fundoscopic and monofilament foot exams intact. Patient provided with diabetes dietary diary.',
                'follow_up_date' => '2026-11-02',
                'status' => 'completed'
            ]
        ];

        $consultationMap = [];
        foreach ($consultations as $c) {
            $ins = $this->insertIfMissing('consultations', 'consultation_id', $c);
            if ($ins && !empty($ins['id'])) {
                $consultationMap[$c['consultation_id']] = (int)$ins['id'];
            }
        }

        // Prescriptions
        $prescriptions = [
            [
                'prescription_id' => 'RX-2026-000041',
                'patient_id' => $p1,
                'employee_id' => $doctorId,
                'consultation_id' => $consultationMap['CONS-2026-0004'] ?? null,
                'date' => '2026-10-03',
                'medications' => json_encode([
                    [
                        'id' => 1,
                        'name' => 'Losartan Potassium',
                        'dosage' => '100mg',
                        'frequency' => 'Once daily in the morning',
                        'duration' => '30 days',
                        'quantity' => 30
                    ],
                    [
                        'id' => 2,
                        'name' => 'Amlodipine Besylate',
                        'dosage' => '5mg',
                        'frequency' => 'Once daily in the evening',
                        'duration' => '30 days',
                        'quantity' => 30
                    ]
                ]),
                'notes' => 'Take with water after meals. Report any dizziness, ankle swelling, or persistent cough.',
                'status' => 'dispensed',
                'dispensed_by' => 1,
                'dispensed_at' => '2026-10-03T10:05:00+00:00'
            ],
            [
                'prescription_id' => 'RX-2026-000042',
                'patient_id' => $p3,
                'employee_id' => $doctorId,
                'consultation_id' => $consultationMap['CONS-2026-0005'] ?? null,
                'date' => '2026-10-02',
                'medications' => json_encode([
                    [
                        'id' => 1,
                        'name' => 'Metformin Hydrochloride',
                        'dosage' => '500mg',
                        'frequency' => 'Twice daily with breakfast and dinner',
                        'duration' => '30 days',
                        'quantity' => 60
                    ]
                ]),
                'notes' => 'Take strictly with food to minimize gastrointestinal discomfort.',
                'status' => 'dispensed',
                'dispensed_by' => 1,
                'dispensed_at' => '2026-10-02T15:10:00+00:00'
            ]
        ];

        foreach ($prescriptions as $rx) {
            $this->insertIfMissing('prescriptions', 'prescription_id', $rx);
        }
    }

    /**
     * 3. Children, Immunizations & Child Nutrition
     */
    private function seedChildrenAndImmunizations(): array
    {
        echo "[3/6] Seeding Child Health, EPI Immunizations & Nutrition Records...\n";

        $children = [
            [
                'child_id' => 'CHD-2026-010',
                'first_name' => 'Mateo',
                'middle_name' => 'Aquino',
                'last_name' => 'Dela Cruz',
                'gender' => 'Male',
                'birth_date' => '2026-01-20',
                'birth_weight' => 3.35,
                'birth_height' => 50.0,
                'blood_type' => 'O+',
                'address' => '42 Gen. San Miguel St, Sangandaan',
                'barangay' => 'Barangay 8',
                'mother_name' => 'Elena Aquino Dela Cruz',
                'mother_contact' => '09176543210',
                'mother_occupation' => 'Bank Teller',
                'father_name' => 'Mark Dela Cruz',
                'father_contact' => '09187654329',
                'father_occupation' => 'Mechanical Technician',
                'family_history' => 'No known congenital abnormalities; non-contributory.',
                'allergies' => 'None known',
                'health_center' => 'Bagong Barrio Health Center',
                'registration_date' => '2026-01-22',
                'status' => 'active',
                'nutrition_status' => 'Normal',
                'vaccine_compliance' => 85,
                'last_visit' => '2026-07-20'
            ],
            [
                'child_id' => 'CHD-2026-011',
                'first_name' => 'Althea',
                'middle_name' => 'Santos',
                'last_name' => 'Bautista',
                'gender' => 'Female',
                'birth_date' => '2025-11-15',
                'birth_weight' => 2.95,
                'birth_height' => 48.5,
                'blood_type' => 'A+',
                'address' => '88 10th Avenue, Grace Park',
                'barangay' => 'Barangay 12',
                'mother_name' => 'Maria Santos Bautista',
                'mother_contact' => '09198765431',
                'mother_occupation' => 'Homemaker',
                'father_name' => 'Renato Bautista',
                'father_contact' => '09209876542',
                'father_occupation' => 'Delivery Driver',
                'family_history' => 'Maternal bronchial asthma history.',
                'allergies' => 'None known',
                'health_center' => 'Bagong Barrio Health Center',
                'registration_date' => '2025-11-18',
                'status' => 'active',
                'nutrition_status' => 'Normal',
                'vaccine_compliance' => 100,
                'last_visit' => '2026-08-15'
            ],
            [
                'child_id' => 'CHD-2026-012',
                'first_name' => 'Ethan Miguel',
                'middle_name' => 'Castillo',
                'last_name' => 'Villanueva',
                'gender' => 'Male',
                'birth_date' => '2025-08-04',
                'birth_weight' => 3.10,
                'birth_height' => 49.0,
                'blood_type' => 'B+',
                'address' => '153 P. Zamora St, Maypajo',
                'barangay' => 'Barangay 80',
                'mother_name' => 'Giselle Castillo Villanueva',
                'mother_contact' => '09210987653',
                'mother_occupation' => 'Call Center Agent',
                'father_name' => 'Paolo Villanueva',
                'father_contact' => '09221098764',
                'father_occupation' => 'Graphic Designer',
                'family_history' => 'Non-contributory.',
                'allergies' => 'None known',
                'health_center' => 'Maypajo Health Center',
                'registration_date' => '2025-08-07',
                'status' => 'active',
                'nutrition_status' => 'Normal',
                'vaccine_compliance' => 95,
                'last_visit' => '2026-08-04'
            ]
        ];

        $childMap = [];
        foreach ($children as $ch) {
            $ins = $this->insertIfMissing('children', 'child_id', $ch, 'children');
            if ($ins && !empty($ins['id'])) {
                $childMap[$ch['child_id']] = (int)$ins['id'];
            }
        }

        // Fetch all children IDs to link immunizations
        $allChildren = $this->db->select('children', [], ['select' => 'id,child_id'], true);
        foreach ($allChildren as $row) {
            if (!empty($row['child_id']) && !isset($childMap[$row['child_id']])) {
                $childMap[$row['child_id']] = (int)$row['id'];
            }
        }

        $c1 = $childMap['CHD-2026-010'] ?? ($childMap['CHD-2026-001'] ?? 21);
        $c2 = $childMap['CHD-2026-011'] ?? ($childMap['CHD-2026-002'] ?? 22);

        // EPI Immunizations
        $immunizations = [
            [
                'child_id' => $c1,
                'vaccine' => 'BCG',
                'dose' => 1,
                'date_administered' => '2026-01-21',
                'next_due_date' => '2026-03-04',
                'batch_number' => 'BCG-2026-001',
                'administered_by' => 'Sarah Cruz (Midwife)',
                'health_center' => 'Bagong Barrio Health Center',
                'notes' => 'Intradermal injection at right deltoid; clean recovery, post-injection wheal noted.'
            ],
            [
                'child_id' => $c1,
                'vaccine' => 'Hepatitis B',
                'dose' => 1,
                'date_administered' => '2026-01-21',
                'next_due_date' => '2026-03-04',
                'batch_number' => 'HEPB-2026-003',
                'administered_by' => 'Sarah Cruz (Midwife)',
                'health_center' => 'Bagong Barrio Health Center',
                'notes' => 'Intramuscular injection at anterolateral thigh within 24 hours of birth.'
            ],
            [
                'child_id' => $c1,
                'vaccine' => 'Pentavalent (DPT-HepB-Hib)',
                'dose' => 1,
                'date_administered' => '2026-03-04',
                'next_due_date' => '2026-04-01',
                'batch_number' => 'PENTA-2026-012',
                'administered_by' => 'Grace Mendoza (Imm. Lead)',
                'health_center' => 'Bagong Barrio Health Center',
                'notes' => 'Dose 1 administered at 6 weeks of age. Child tolerated well, advice on paracetamol for low fever given.'
            ],
            [
                'child_id' => $c1,
                'vaccine' => 'Oral Polio Vaccine (OPV)',
                'dose' => 1,
                'date_administered' => '2026-03-04',
                'next_due_date' => '2026-04-01',
                'batch_number' => 'OPV-2026-008',
                'administered_by' => 'Grace Mendoza (Imm. Lead)',
                'health_center' => 'Bagong Barrio Health Center',
                'notes' => '2 drops oral administered; retained completely without spitting.'
            ],
            [
                'child_id' => $c2,
                'vaccine' => 'Measles-Rubella (MR)',
                'dose' => 1,
                'date_administered' => '2026-08-15',
                'next_due_date' => '2026-11-15',
                'batch_number' => 'MR-2026-005',
                'administered_by' => 'Sarah Cruz (Midwife)',
                'health_center' => 'Bagong Barrio Health Center',
                'notes' => 'Subcutaneous injection at 9 months of age. MMR booster scheduled at 12 months.'
            ]
        ];

        foreach ($immunizations as $imm) {
            // Verify if same child + vaccine + dose exists
            $exists = $this->db->select('immunizations', [
                'child_id' => $imm['child_id'],
                'vaccine' => $imm['vaccine'],
                'dose' => $imm['dose']
            ], ['limit' => 1], true);

            if (empty($exists)) {
                $this->db->query('immunizations', 'POST', $imm, [], [], true);
                if (!isset($this->summary['immunizations'])) $this->summary['immunizations'] = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];
                $this->summary['immunizations']['inserted']++;
            } else {
                if (!isset($this->summary['immunizations'])) $this->summary['immunizations'] = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];
                $this->summary['immunizations']['skipped']++;
            }
        }

        // Growth Measurements
        $growthData = [
            [
                'child_id' => $c1,
                'measurement_date' => '2026-07-20',
                'weight' => 7.6,
                'height' => 67.5,
                'head_circumference' => 43.0,
                'notes' => '6-month well-child assessment; growth trajectory corresponds to WHO 50th percentile.'
            ],
            [
                'child_id' => $c2,
                'measurement_date' => '2026-08-15',
                'weight' => 8.4,
                'height' => 71.2,
                'head_circumference' => 44.5,
                'notes' => '9-month growth monitoring; transition to complementary feeding proceeding smoothly.'
            ]
        ];

        foreach ($growthData as $gm) {
            $exists = $this->db->select('growth_measurements', [
                'child_id' => $gm['child_id'],
                'measurement_date' => $gm['measurement_date']
            ], ['limit' => 1], true);

            if (empty($exists)) {
                $this->db->query('growth_measurements', 'POST', $gm, [], [], true);
                if (!isset($this->summary['growth_measurements'])) $this->summary['growth_measurements'] = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];
                $this->summary['growth_measurements']['inserted']++;
            } else {
                if (!isset($this->summary['growth_measurements'])) $this->summary['growth_measurements'] = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];
                $this->summary['growth_measurements']['skipped']++;
            }
        }

        // Nutrition Assessments
        $nutritionAssessments = [
            [
                'child_id' => $c1,
                'assessment_date' => '2026-07-20',
                'weight' => 7.6,
                'height' => 67.5,
                'bmi' => 16.7,
                'weight_percentile' => 52,
                'height_percentile' => 50,
                'nutrition_status' => 'normal',
                'risk_level' => 'low',
                'assessment_notes' => 'Well-nourished infant, active, responsive, normal muscle tone and skin turgor.',
                'plan_of_action' => 'Continue exclusive breastfeeding with gradual introduction of mashed vegetables, lugaw, and fruits.',
                'supplements' => 'Vitamin A supplementation given at 6 months; Micronutrient powder (MNP) sachets provided.',
                'next_assessment_date' => '2026-10-20',
                'assessed_by' => 'Carla Ramos (Nutritionist)',
                'status' => 'active'
            ],
            [
                'child_id' => $c2,
                'assessment_date' => '2026-08-15',
                'weight' => 8.4,
                'height' => 71.2,
                'bmi' => 16.6,
                'weight_percentile' => 48,
                'height_percentile' => 46,
                'nutrition_status' => 'normal',
                'risk_level' => 'low',
                'assessment_notes' => 'Age-appropriate growth velocity. No micronutrient deficiency signs.',
                'plan_of_action' => 'Maintain diversified complementary feeding 3-4 times daily with breastfeeding.',
                'supplements' => 'Routine iron drops and vitamin A.',
                'next_assessment_date' => '2026-11-15',
                'assessed_by' => 'Carla Ramos (Nutritionist)',
                'status' => 'active'
            ]
        ];

        foreach ($nutritionAssessments as $na) {
            $exists = $this->db->select('nutrition_assessments', [
                'child_id' => $na['child_id'],
                'assessment_date' => $na['assessment_date']
            ], ['limit' => 1], true);

            if (empty($exists)) {
                $this->db->query('nutrition_assessments', 'POST', $na, [], [], true);
                if (!isset($this->summary['nutrition_assessments'])) $this->summary['nutrition_assessments'] = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];
                $this->summary['nutrition_assessments']['inserted']++;
            } else {
                if (!isset($this->summary['nutrition_assessments'])) $this->summary['nutrition_assessments'] = ['inserted' => 0, 'skipped' => 0, 'failed' => 0];
                $this->summary['nutrition_assessments']['skipped']++;
            }
        }

        return $childMap;
    }

    /**
     * 4. Environmental Sanitation, Permits & Inspections
     */
    private function seedSanitationPermitsAndInspections(): void
    {
        echo "[4/6] Seeding Environmental Sanitation Permits & Inspections...\n";

        $inspectorId = 10; // Liza Cruz (Sanitation Inspector)

        $permits = [
            [
                'permit_id' => 'SP-2026-1001',
                'applicant' => 'Ronaldo C. Mendoza',
                'business_name' => 'Aquasafe Purified Water Refilling Station',
                'business_type' => 'Water Refilling Station',
                'address' => '234 8th Avenue, Grace Park',
                'owner_name' => 'Ronaldo C. Mendoza',
                'contact' => '09171234891',
                'email' => 'aquasafe.caloocan@example.ph',
                'fee' => 1800.00,
                'paid' => true,
                'payment_method' => 'Cash',
                'payment_reference' => 'OR-2026-08912',
                'status' => 'approved',
                'inspector_id' => $inspectorId,
                'inspection_date' => '2026-09-18',
                'approved_date' => '2026-09-20',
                'expiry_date' => '2027-09-20',
                'notes' => 'Passed microbiological and physical-chemical potable water standards.'
            ],
            [
                'permit_id' => 'SP-2026-1002',
                'applicant' => 'Elena D. Santos',
                'business_name' => 'Aling Nena\'s Traditional Pan de Sal Bakery',
                'business_type' => 'Bakery & Food Processing',
                'address' => '56 Samson Road, Sangandaan',
                'owner_name' => 'Elena D. Santos',
                'contact' => '09182345892',
                'email' => 'alingnena.bakery@example.ph',
                'fee' => 2200.00,
                'paid' => true,
                'payment_method' => 'GCash',
                'payment_reference' => 'GC-20260922-8921',
                'status' => 'approved',
                'inspector_id' => $inspectorId,
                'inspection_date' => '2026-09-22',
                'approved_date' => '2026-09-24',
                'expiry_date' => '2027-09-24',
                'notes' => 'Food handlers possess valid health cards; stainless steel food preparation tables verified.'
            ],
            [
                'permit_id' => 'SP-2026-1003',
                'applicant' => 'Danilo V. Cruz',
                'business_name' => 'Bulalohan sa Caloocan Eatery',
                'business_type' => 'Food Establishment',
                'address' => '112 Rizal Avenue Extension',
                'owner_name' => 'Danilo V. Cruz',
                'contact' => '09193456893',
                'email' => 'bulalohan.caloocan@example.ph',
                'fee' => 2500.00,
                'paid' => true,
                'payment_method' => 'Cash',
                'payment_reference' => 'OR-2026-09144',
                'status' => 'approved',
                'inspector_id' => $inspectorId,
                'inspection_date' => '2026-09-25',
                'approved_date' => '2026-09-27',
                'expiry_date' => '2027-09-27',
                'notes' => 'Functional grease trap installed; proper waste segregation compliant.'
            ],
            [
                'permit_id' => 'SP-2026-1004',
                'applicant' => 'Carmencita G. Flores',
                'business_name' => 'QuickWash Commercial Laundromat',
                'business_type' => 'Commercial Laundromat',
                'address' => '78 A. Mabini St, Maypajo',
                'owner_name' => 'Carmencita G. Flores',
                'contact' => '09204567894',
                'email' => 'quickwash.mabini@example.ph',
                'fee' => 1500.00,
                'paid' => false,
                'payment_method' => null,
                'payment_reference' => null,
                'status' => 'pending',
                'inspector_id' => $inspectorId,
                'inspection_date' => '2026-10-08',
                'approved_date' => null,
                'expiry_date' => null,
                'notes' => 'Under initial site assessment for wastewater discharge compliance.'
            ]
        ];

        $permitMap = [];
        foreach ($permits as $p) {
            $ins = $this->insertIfMissing('permits', 'permit_id', $p, 'permits');
            if ($ins && !empty($ins['id'])) {
                $permitMap[$p['permit_id']] = (int)$ins['id'];
            }
        }

        // Inspections
        $inspections = [
            [
                'inspection_id' => 'INS-2026-1001',
                'permit_id' => $permitMap['SP-2026-1001'] ?? 37,
                'inspector_id' => $inspectorId,
                'scheduled_date' => '2026-09-18',
                'scheduled_time' => '09:30:00',
                'conducted_date' => '2026-09-18T10:15:00+00:00',
                'findings' => json_encode([
                    ['id' => 'water_supply', 'category' => 'Water Supply & Potability', 'status' => 'compliant', 'notes' => 'Monthly bacteriological test passed.'],
                    ['id' => 'food_safety', 'category' => 'Food Safety & Handling', 'status' => 'compliant', 'notes' => 'Clean bottling area, sealed caps.'],
                    ['id' => 'wastewater', 'category' => 'Wastewater & Drainage System', 'status' => 'compliant', 'notes' => 'Reverse osmosis discharge routed to approved drain.'],
                    ['id' => 'solid_waste', 'category' => 'Solid Waste Segregation & Disposal', 'status' => 'compliant', 'notes' => 'Color-coded bins present.'],
                    ['id' => 'pest_control', 'category' => 'Vector & Vermin Control', 'status' => 'compliant', 'notes' => 'Insect light traps active.'],
                    ['id' => 'sanitary_facilities', 'category' => 'Sanitary Facilities & Handwashing', 'status' => 'compliant', 'notes' => 'Foot-operated dispenser.'],
                    ['id' => 'personnel_health', 'category' => 'Personnel Hygiene & Health Cards', 'status' => 'compliant', 'notes' => 'All 3 staff have valid health certificates.'],
                    ['id' => 'premises_ventilation', 'category' => 'Premises Cleanliness & Ventilation', 'status' => 'compliant', 'notes' => 'Air conditioned and dust-free.']
                ]),
                'overall_status' => 'compliant',
                'recommendations' => 'Maintain daily temperature & chlorine test logbook.',
                'status' => 'completed',
                'completed_at' => '2026-09-18T11:00:00+00:00',
                'notes' => 'Excellent compliance standard.'
            ],
            [
                'inspection_id' => 'INS-2026-1002',
                'permit_id' => $permitMap['SP-2026-1002'] ?? 37,
                'inspector_id' => $inspectorId,
                'scheduled_date' => '2026-09-22',
                'scheduled_time' => '13:30:00',
                'conducted_date' => '2026-09-22T14:10:00+00:00',
                'findings' => json_encode([
                    ['id' => 'water_supply', 'category' => 'Water Supply & Potability', 'status' => 'compliant', 'notes' => 'Direct municipal line.'],
                    ['id' => 'food_safety', 'category' => 'Food Safety & Handling', 'status' => 'compliant', 'notes' => 'Flour storage pallets elevated 15cm off floor.'],
                    ['id' => 'wastewater', 'category' => 'Wastewater & Drainage System', 'status' => 'compliant', 'notes' => 'Floor drains covered.'],
                    ['id' => 'solid_waste', 'category' => 'Solid Waste Segregation & Disposal', 'status' => 'compliant', 'notes' => 'Bins with tight-fitting lids.'],
                    ['id' => 'pest_control', 'category' => 'Vector & Vermin Control', 'status' => 'compliant', 'notes' => 'Monthly contract with licensed PCO.'],
                    ['id' => 'sanitary_facilities', 'category' => 'Sanitary Facilities & Handwashing', 'status' => 'compliant', 'notes' => 'Handwashing station inside bakery.'],
                    ['id' => 'personnel_health', 'category' => 'Personnel Hygiene & Health Cards', 'status' => 'compliant', 'notes' => 'Hairnets, aprons, and health cards present.'],
                    ['id' => 'premises_ventilation', 'category' => 'Premises Cleanliness & Ventilation', 'status' => 'compliant', 'notes' => 'Exhaust hoods functional over ovens.']
                ]),
                'overall_status' => 'compliant',
                'recommendations' => 'Ensure flour storage area remains protected from humidity.',
                'status' => 'completed',
                'completed_at' => '2026-09-22T15:00:00+00:00',
                'notes' => 'Sanitation permit approval recommended.'
            ]
        ];

        foreach ($inspections as $ins) {
            $this->insertIfMissing('inspections', 'inspection_id', $ins);
        }

        // Renewals
        $renewals = [
            [
                'renewal_id' => 'REN-2026-1001',
                'permit_id' => $permitMap['SP-2026-1001'] ?? 37,
                'applicant' => 'Ronaldo C. Mendoza',
                'business_type' => 'Water Refilling Station',
                'current_fee' => 1800.00,
                'renewal_fee' => 1800.00,
                'status' => 'approved',
                'payment_method' => 'Cash',
                'payment_reference' => 'OR-2026-08912',
                'date_applied' => '2026-09-15',
                'date_approved' => '2026-09-20',
                'new_expiry_date' => '2027-09-20',
                'notes' => 'Annual renewal processed; laboratory testing certificate verified.'
            ],
            [
                'renewal_id' => 'REN-2026-1002',
                'permit_id' => $permitMap['SP-2026-1002'] ?? 37,
                'applicant' => 'Elena D. Santos',
                'business_type' => 'Bakery & Food Processing',
                'current_fee' => 2200.00,
                'renewal_fee' => 2200.00,
                'status' => 'approved',
                'payment_method' => 'GCash',
                'payment_reference' => 'GC-20260922-8921',
                'date_applied' => '2026-09-20',
                'date_approved' => '2026-09-24',
                'new_expiry_date' => '2027-09-24',
                'notes' => 'Annual sanitation clearance renewed.'
            ]
        ];

        foreach ($renewals as $ren) {
            $this->insertIfMissing('renewals', 'renewal_id', $ren);
        }
    }

    /**
     * 5. Septage, Wastewater & Service Invoices
     */
    private function seedWastewaterAndSeptage(): void
    {
        echo "[5/6] Seeding Septage, Wastewater Infrastructure & Service Invoices...\n";

        // Service Providers
        $providers = [
            [
                'provider_id' => 'PRV-2026-001',
                'name' => 'Metro Enviro-Clean Septic Services Inc.',
                'contact' => '09178901234',
                'email' => 'dispatch@metroenviroclean.ph',
                'address' => 'Km 14 McArthur Highway, Caloocan City',
                'license_number' => 'DENR-EMB-WMS-2025-081',
                'specialization' => 'desludging',
                'rating' => 4.9,
                'status' => 'active',
                'equipment_count' => 6,
                'completed_jobs' => 248,
                'response_time' => '24-48 hours',
                'certification' => 'ISO 14001:2015 Environmental Management',
                'joined_date' => '2025-01-10',
                'notes' => 'Primary contracted municipal vacuum tanker fleet operator.'
            ],
            [
                'provider_id' => 'PRV-2026-002',
                'name' => 'GreenEarth Desludging & Sanitation Corp.',
                'contact' => '09189012345',
                'email' => 'operations@greenearth-sanitation.ph',
                'address' => '28 C-3 Road, Dagat-Dagatan, Caloocan City',
                'license_number' => 'DENR-EMB-WMS-2025-114',
                'specialization' => 'maintenance',
                'rating' => 4.7,
                'status' => 'active',
                'equipment_count' => 4,
                'completed_jobs' => 175,
                'response_time' => 'Same-day emergency response',
                'certification' => 'DOH Certified Septage Hauler',
                'joined_date' => '2025-03-15',
                'notes' => 'Specializes in commercial grease traps & confined-space tank repairs.'
            ]
        ];

        foreach ($providers as $prov) {
            $this->insertIfMissing('service_providers', 'provider_id', $prov, 'service_providers');
        }

        // Additional Septic Tanks
        $tanks = [
            [
                'tank_id' => 'ST-261001-01',
                'owner_name' => 'Barangay 8 Multi-Purpose Hall',
                'address' => 'Barangay 8 Hall Complex, 4th Avenue',
                'barangay' => 'Barangay 8',
                'latitude' => 14.6515,
                'longitude' => 120.9840,
                'capacity' => '5000 Liters',
                'type' => 'Concrete',
                'installation_year' => 2022,
                'last_maintenance' => '2026-08-10',
                'maintenance_frequency' => 24,
                'status' => 'good',
                'notes' => 'LGU Government building 3-chamber septic vault; easily accessible from road.'
            ],
            [
                'tank_id' => 'ST-261001-02',
                'owner_name' => 'St. Gabriel Parish Community Center',
                'address' => '120 7th Avenue, Grace Park',
                'barangay' => 'Barangay 12',
                'latitude' => 14.6550,
                'longitude' => 120.9875,
                'capacity' => '4000 Liters',
                'type' => 'Concrete',
                'installation_year' => 2020,
                'last_maintenance' => '2025-11-15',
                'maintenance_frequency' => 36,
                'status' => 'good',
                'notes' => 'Dual-compartment septic tank serving parochial school & parish offices.'
            ],
            [
                'tank_id' => 'ST-261001-03',
                'owner_name' => 'Sangandaan Wet Market Association',
                'address' => 'Market Complex, Samson Road',
                'barangay' => 'Barangay 77',
                'latitude' => 14.6582,
                'longitude' => 120.9760,
                'capacity' => '10000 Liters',
                'type' => 'Concrete',
                'installation_year' => 2023,
                'last_maintenance' => '2026-09-01',
                'maintenance_frequency' => 12,
                'status' => 'good',
                'notes' => 'High-capacity commercial grease trap and secondary anaerobic digestion vault.'
            ]
        ];

        foreach ($tanks as $st) {
            $this->insertIfMissing('septic_tanks', 'tank_id', $st);
        }

        // Service Requests
        $serviceRequests = [
            [
                'request_id' => 'SR-2026-0006',
                'tank_id' => 'ST-261001-01',
                'owner_name' => 'Barangay 8 Multi-Purpose Hall',
                'address' => 'Barangay 8 Hall Complex, 4th Avenue',
                'barangay' => 'Barangay 8',
                'service_type' => 'desludging',
                'preferred_date' => '2026-08-10',
                'preferred_time' => '10:00:00',
                'assigned_to' => 'Ramon Flores',
                'provider_id' => 'PRV-2026-001',
                'status' => 'completed',
                'priority' => 'medium',
                'notes' => 'Bi-annual government facility scheduled desludging completed.',
                'rating' => 5
            ],
            [
                'request_id' => 'SR-2026-0007',
                'tank_id' => 'ST-261001-03',
                'owner_name' => 'Sangandaan Wet Market Association',
                'address' => 'Market Complex, Samson Road',
                'barangay' => 'Barangay 77',
                'service_type' => 'maintenance',
                'preferred_date' => '2026-10-12',
                'preferred_time' => '08:00:00',
                'assigned_to' => 'Ramon Flores',
                'provider_id' => 'PRV-2026-002',
                'status' => 'approved',
                'priority' => 'high',
                'notes' => 'Scheduled commercial grease baffle inspection and effluent filter replacement.',
                'rating' => null
            ]
        ];

        foreach ($serviceRequests as $sr) {
            $this->insertIfMissing('service_requests', 'request_id', $sr, 'service_requests');
        }

        // Maintenance Records
        $maintenance = [
            [
                'service_id' => 'MR-2026-001',
                'tank_id' => 'ST-261001-01',
                'owner_name' => 'Barangay 8 Multi-Purpose Hall',
                'address' => 'Barangay 8 Hall Complex, 4th Avenue',
                'service_type' => 'desludging',
                'scheduled_date' => '2026-08-10',
                'scheduled_time' => '10:00:00',
                'technician' => 'Ramon Flores',
                'provider_id' => 'PRV-2026-001',
                'status' => 'completed',
                'completed_date' => '2026-08-10',
                'completed_time' => '11:45:00',
                'findings' => 'Scum layer 32cm, sludge layer 45cm. Effluent outlet tee baffle intact and functional.',
                'recommendations' => 'Schedule next desludging in 24 months. Effluent discharge within city environmental parameters.',
                'notes' => 'Full pump-out executed. Hauled to Maynilad Dagat-Dagatan septage treatment facility.',
                'cost' => 3500.00,
                'rating' => 5
            ]
        ];

        foreach ($maintenance as $mr) {
            $this->insertIfMissing('maintenance_records', 'service_id', $mr);
        }

        // Wastewater Invoices
        $invoices = [
            [
                'invoice_id' => 'INV-2026-007',
                'client_name' => 'Barangay 8 Multi-Purpose Hall',
                'tank_id' => 'ST-261001-01',
                'service_request_id' => 'SR-2026-0006',
                'provider_id' => 'PRV-2026-001',
                'service_type' => 'Desludging',
                'amount' => 3500.00,
                'tax' => 0.00,
                'total_amount' => 3500.00,
                'status' => 'paid',
                'payment_method' => 'Landbank Check',
                'payment_reference' => 'LBP-CHK-2026-0810-01',
                'invoice_date' => '2026-08-10',
                'due_date' => '2026-08-25',
                'paid_at' => '2026-08-12T09:30:00+00:00',
                'notes' => 'Official City Sanitation Septage Clearance OSS-2026-0810 issued.'
            ],
            [
                'invoice_id' => 'INV-2026-008',
                'client_name' => 'Sangandaan Wet Market Association',
                'tank_id' => 'ST-261001-03',
                'service_request_id' => 'SR-2026-0007',
                'provider_id' => 'PRV-2026-002',
                'service_type' => 'Maintenance',
                'amount' => 5000.00,
                'tax' => 0.00,
                'total_amount' => 5000.00,
                'status' => 'pending',
                'payment_method' => null,
                'payment_reference' => null,
                'invoice_date' => '2026-10-02',
                'due_date' => '2026-10-20',
                'paid_at' => null,
                'notes' => 'Advance billing for scheduled commercial baffle overhaul.'
            ]
        ];

        foreach ($invoices as $inv) {
            $this->insertIfMissing('wastewater_invoices', 'invoice_id', $inv);
        }
    }

    /**
     * 6. Epidemiological Disease Surveillance & Early Warning
     */
    private function seedDiseaseSurveillance(): void
    {
        echo "[6/6] Seeding Epidemiological Surveillance Cases & Alerts...\n";

        $cases = [
            [
                'case_code' => 'CAS-2026-DEN-001',
                'disease' => 'Dengue Fever',
                'patient_name' => 'Mark Anthony Ramos',
                'age' => 14,
                'gender' => 'Male',
                'address' => '45 Libis Baesa',
                'barangay' => 'Barangay 8',
                'contact_number' => '09172348911',
                'symptoms' => 'High fever, retro-orbital pain, myalgia, petechiae on both forearms',
                'onset_date' => '2026-09-24',
                'reporting_facility' => 'Caloocan City Medical Center',
                'status' => 'Resolved',
                'severity' => 'Moderate',
                'reported_by' => 'Dr. Santos',
                'investigator_id' => 18, // Sofia Lim
                'investigation_notes' => 'NS1 rapid test positive. Neighborhood larvicidal application and fogging conducted on Sept 26.'
            ],
            [
                'case_code' => 'CAS-2026-DEN-002',
                'disease' => 'Dengue Fever',
                'patient_name' => 'Hannah Nicole Gomez',
                'age' => 9,
                'gender' => 'Female',
                'address' => '72 6th Avenue, Grace Park',
                'barangay' => 'Barangay 8',
                'contact_number' => '09183458922',
                'symptoms' => 'Sudden onset fever, headache, vomiting, epistaxis (nosebleed)',
                'onset_date' => '2026-09-26',
                'reporting_facility' => 'Bagong Barrio Health Center',
                'status' => 'Active',
                'severity' => 'Critical',
                'reported_by' => 'Nurse Ana Reyes',
                'investigator_id' => 18,
                'investigation_notes' => 'Dengue with warning signs; platelet count 75,000/uL. Admitted for IV hydration.'
            ],
            [
                'case_code' => 'CAS-2026-LEP-001',
                'disease' => 'Leptospirosis',
                'patient_name' => 'Danilo E. Hernandez',
                'age' => 41,
                'gender' => 'Male',
                'address' => '102 C-3 Road, Maypajo',
                'barangay' => 'Barangay 80',
                'contact_number' => '09194568933',
                'symptoms' => 'Calf muscle tenderness, conjunctival suffusion, chills after wading in floodwaters',
                'onset_date' => '2026-09-28',
                'reporting_facility' => 'Caloocan City Medical Center',
                'status' => 'Active',
                'severity' => 'Moderate',
                'reported_by' => 'Dr. Juan Dela Cruz',
                'investigator_id' => 19, // James Rivera
                'investigation_notes' => 'History of wading in floodwaters during Habagat rains without protective boots. Doxycycline therapy started.'
            ],
            [
                'case_code' => 'CAS-2026-AGE-001',
                'disease' => 'Acute Gastroenteritis',
                'patient_name' => 'Clarissa M. Pineda',
                'age' => 28,
                'gender' => 'Female',
                'address' => '23 Rizal Ave Ext',
                'barangay' => 'Barangay 12',
                'contact_number' => '09205678944',
                'symptoms' => 'Watery diarrhea 5x/day, abdominal cramping, mild dehydration',
                'onset_date' => '2026-09-30',
                'reporting_facility' => 'Bagong Barrio Health Center',
                'status' => 'Resolved',
                'severity' => 'Mild',
                'reported_by' => 'Nurse Ana Reyes',
                'investigator_id' => 18,
                'investigation_notes' => 'Stool exam negative for cholera vibrio; oral rehydration therapy and zinc supplementation successful.'
            ],
            [
                'case_code' => 'CAS-2026-HFM-001',
                'disease' => 'Hand-Foot-and-Mouth Disease (HFMD)',
                'patient_name' => 'Lucas Gabriel Cruz',
                'age' => 4,
                'gender' => 'Male',
                'address' => '89 Samson Road',
                'barangay' => 'Barangay 77',
                'contact_number' => '09216789055',
                'symptoms' => 'Maculopapular rash on palms and soles, painful buccal ulcers, low-grade fever',
                'onset_date' => '2026-10-01',
                'reporting_facility' => 'Sangandaan Health Center',
                'status' => 'Active',
                'severity' => 'Mild',
                'reported_by' => 'Sarah Cruz (Midwife)',
                'investigator_id' => 19,
                'investigation_notes' => 'Daycare center advisory issued; home isolation for 7 days advised.'
            ]
        ];

        foreach ($cases as $c) {
            $this->insertIfMissing('surveillance_cases', 'case_code', $c, 'surveillance_cases');
        }

        // Surveillance Alerts
        $alerts = [
            [
                'alert_code' => 'ALT-DEN-2640-01',
                'disease' => 'Dengue Fever',
                'barangay' => 'Barangay 8',
                'cases' => 5,
                'threshold' => 3,
                'severity' => 'Critical',
                'status' => 'Active',
                'timestamp' => '2026-10-01T08:00:00+00:00',
                'escalation_level' => 2,
                'assigned_to' => 'Sofia Lim (Surveillance Lead)',
                'message' => 'Dengue cluster confirmed in Barangay 8: 5 confirmed cases in past 14 days exceeding epidemic threshold (3 cases). Targeted misting and search-and-destroy cleanup launched.',
                'response_actions' => '1. Community indoor and outdoor misting conducted; 2. Distribution of Olyset mosquito nets; 3. House-to-house fever surveillance.'
            ],
            [
                'alert_code' => 'ALT-LEP-2640-02',
                'disease' => 'Leptospirosis',
                'barangay' => 'Barangay 80',
                'cases' => 3,
                'threshold' => 2,
                'severity' => 'High',
                'status' => 'Active',
                'timestamp' => '2026-10-02T14:30:00+00:00',
                'escalation_level' => 1,
                'assigned_to' => 'James Rivera (Coordinator)',
                'message' => 'Post-flood Leptospirosis warning in low-lying zones of Barangay 80. Prophylactic Doxycycline distribution mobilized at health center.',
                'response_actions' => '1. Post-exposure prophylaxis mobilized; 2. Floodwater advisories broadcasted via Barangay public address system.'
            ]
        ];

        foreach ($alerts as $alt) {
            $this->insertIfMissing('surveillance_alerts', 'alert_code', $alt);
        }
    }

    /**
     * 7. Official LGU Health & Sanitation Announcements
     */
    private function seedAnnouncements(): void
    {
        $announcements = [
            [
                'title' => 'Chikiting Ligtas 2026: Supplemental Measles-Rubella & Polio Vaccination Campaign',
                'category' => 'Immunization Drive',
                'audience' => 'All Staff',
                'body' => 'The City Health Department hereby announces the launch of the Chikiting Ligtas nationwide supplemental immunization drive starting October 15, 2026. All health center personnel, midwives, and barangay health workers (BHWs) are directed to coordinate with assigned day care centers and public elementary schools for house-to-house and fixed-site vaccination of all children aged 0-59 months.',
                'author' => 'Maria Santos (Health Center Director)',
                'file_url' => null,
                'is_active' => true
            ],
            [
                'title' => 'Sanitation Code Advisory: Q4 Sanitary Permit & Health Certificate Renewal',
                'category' => 'Environmental Sanitation',
                'audience' => 'All Staff',
                'body' => 'In accordance with the Caloocan City Sanitation Code, all food establishments, commercial bakeries, water refilling stations, and wet market stalls are advised that the pre-renewal inspection period for 2027 Sanitary Permits opens on November 3, 2026. All food handlers must present updated chest X-ray and stool examination results for health certificate renewals.',
                'author' => 'Pedro Garcia (Sanitation Director)',
                'file_url' => null,
                'is_active' => true
            ]
        ];

        foreach ($announcements as $ann) {
            $this->insertIfMissing('announcements', 'title', $ann);
        }
    }
}
