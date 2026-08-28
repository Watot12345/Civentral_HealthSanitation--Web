<?php
// app/Controllers/TriageController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Triage.php';
require_once __DIR__ . '/../Models/Patient.php';
require_once __DIR__ . '/../Models/Employee.php';

class TriageController extends BaseController
{
    private Triage $triageModel;
    private Patient $patientModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->triageModel = new Triage();
        $this->patientModel = new Patient();
        $this->employeeModel = new Employee();
    }

    public function index(): void
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 1000;

        $rawTriage = $this->triageModel->all(['order' => 'created_at.desc']);
        $patientsMap = $this->getPatientsMap();
        $employeesMap = $this->getEmployeesMap();

        $triageList = array_map(function ($t) use ($patientsMap, $employeesMap) {
            return $this->enrichTriage($t, $patientsMap, $employeesMap);
        }, $rawTriage);

        $total = count($triageList);
        $totalPages = max(1, (int)ceil($total / $limit));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $limit;
        $paginated = array_slice($triageList, $offset, $limit);

        $this->handle(function() use ($paginated, $total, $page, $totalPages, $limit) {
            return [
                'success' => true,
                'data' => $paginated,
                'total' => $total,
                'page' => $page,
                'total_pages' => $totalPages,
                'limit' => $limit
            ];
        });
    }

    public function show(string|int $id): void
    {
        $triage = $this->triageModel->find($id);

        $this->handle(function() use ($triage) {
            if (!$triage) {
                return [
                    'success' => false,
                    'message' => 'Triage record not found',
                    'code' => 404
                ];
            }

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();

            return [
                'success' => true,
                'data' => $this->enrichTriage($triage, $patientsMap, $employeesMap)
            ];
        });
    }

    public function store(): void
    {
        $data = $this->input();

        $this->handle(function() use ($data) {
            if (empty($data['patient_id'])) {
                return [
                    'success' => false,
                    'message' => 'Patient selection is required',
                    'code' => 400
                ];
            }

            if (empty($data['priority'])) {
                return [
                    'success' => false,
                    'message' => 'Priority level is required',
                    'code' => 400
                ];
            }

            $vitalError = $this->validateVitalSigns($data);
            if ($vitalError) {
                return ['success' => false, 'message' => $vitalError, 'code' => 422];
            }

            $dbData = $this->prepareDbData($data);

            if (empty($dbData['triage_id'])) {
                $dbData['triage_id'] = $this->triageModel->generateTriageId();
            }

            $result = $this->triageModel->create($dbData);

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $tCode = $dbData['triage_id'] ?? ($result['triage_id'] ?? 'TRG');
                    $prio = ucfirst($dbData['priority'] ?? 'Standard');
                    $logger->log("Recorded Patient Triage Assessment ({$tCode})", [
                        'module'  => 'Health Center Services',
                        'details' => "Priority: {$prio} | Chief Complaint: " . ($dbData['chief_complaint'] ?? 'General Triage'),
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            return [
                'success' => true,
                'message' => 'Triage record created successfully',
                'data' => $result,
                'code' => 201
            ];
        });
    }

    public function update(string|int $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $existing = $this->triageModel->find($id);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'Triage record not found',
                    'code' => 404
                ];
            }

            $vitalError = $this->validateVitalSigns($data);
            if ($vitalError) {
                return ['success' => false, 'message' => $vitalError, 'code' => 422];
            }

            $dbData = $this->prepareDbData($data);
            $result = $this->triageModel->updateById($id, $dbData);

            return [
                'success' => true,
                'message' => 'Triage record updated successfully',
                'data' => $result
            ];
        });
    }

    public function updateStatus(string|int $id): void
    {
        $data = $this->input();
        $status = $data['status'] ?? $_GET['status'] ?? null;

        $this->handle(function() use ($id, $status) {
            if (!$status || !in_array($status, ['pending', 'triaged', 'consulted', 'cancelled'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid status value provided',
                    'code' => 400
                ];
            }

            $existing = $this->triageModel->find($id);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'Triage record not found',
                    'code' => 404
                ];
            }

            $result = $this->triageModel->updateStatus($id, $status);

            return [
                'success' => true,
                'message' => 'Triage status updated to ' . $status,
                'data' => $result
            ];
        });
    }

    public function destroy(string|int $id): void
    {
        $this->handle(function() use ($id) {
            $existing = $this->triageModel->find($id);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'Triage record not found',
                    'code' => 404
                ];
            }

            $success = $this->triageModel->deleteById($id);

            return [
                'success' => $success,
                'message' => $success ? 'Triage record deleted successfully' : 'Failed to delete triage record'
            ];
        });
    }

    /**
     * Get queue statistics
     */
    public function queueStats(): void
    {
        $this->handle(function() {
            $rawTriage = $this->triageModel->all(['order' => 'created_at.asc']);
            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();

            $enriched = array_map(function ($t) use ($patientsMap, $employeesMap) {
                return $this->enrichTriage($t, $patientsMap, $employeesMap);
            }, $rawTriage);

            // Separate by status
            $waiting = array_values(array_filter($enriched, fn($t) => $t['status'] === 'waiting'));
            $inTriage = array_values(array_filter($enriched, fn($t) => $t['status'] === 'in_triage'));
            $completed = array_values(array_filter($enriched, fn($t) => $t['status'] === 'sent_to_doctor' || $t['status'] === 'completed'));
            $cancelled = array_values(array_filter($enriched, fn($t) => $t['status'] === 'cancelled'));

            // Sort waiting by priority (critical first, then high, medium, low)
            $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            usort($waiting, function($a, $b) use ($priorityOrder) {
                return ($priorityOrder[$a['priority']] ?? 99) - ($priorityOrder[$b['priority']] ?? 99);
            });

            // Re-assign queue numbers
            $queueNum = 1;
            foreach ($waiting as &$w) {
                $w['queue_number'] = $queueNum++;
            }

            return [
                'success' => true,
                'data' => [
                    'waiting' => $waiting,
                    'in_triage' => $inTriage,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'stats' => [
                        'total_waiting' => count($waiting),
                        'total_in_triage' => count($inTriage),
                        'total_completed' => count($completed),
                        'total_cancelled' => count($cancelled),
                        'total' => count($enriched),
                        'critical_count' => count(array_filter($waiting, fn($t) => $t['priority'] === 'critical')),
                        'high_count' => count(array_filter($waiting, fn($t) => $t['priority'] === 'high')),
                        'medium_count' => count(array_filter($waiting, fn($t) => $t['priority'] === 'medium')),
                        'low_count' => count(array_filter($waiting, fn($t) => $t['priority'] === 'low')),
                    ]
                ]
            ];
        });
    }

    /**
     * Call next patient - moves the highest priority waiting patient to "triaged" status
     */
    public function callNext(): void
    {
        $this->handle(function() {
            $rawTriage = $this->triageModel->all();
            
            // Find waiting patients
            $waiting = array_values(array_filter($rawTriage, fn($t) => ($t['status'] ?? 'pending') === 'pending'));
            
            if (empty($waiting)) {
                return [
                    'success' => false,
                    'message' => 'No patients waiting in queue',
                    'code' => 404
                ];
            }

            // Sort by priority (critical first)
            $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            usort($waiting, function($a, $b) use ($priorityOrder) {
                $pa = $priorityOrder[$a['priority'] ?? 'medium'] ?? 99;
                $pb = $priorityOrder[$b['priority'] ?? 'medium'] ?? 99;
                if ($pa === $pb) {
                    // Earlier created_at first
                    return strtotime($a['created_at'] ?? 'now') - strtotime($b['created_at'] ?? 'now');
                }
                return $pa - $pb;
            });

            $nextPatient = $waiting[0];
            $this->triageModel->updateStatus($nextPatient['id'], 'triaged');

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();
            $enriched = $this->enrichTriage($nextPatient, $patientsMap, $employeesMap);

            return [
                'success' => true,
                'message' => 'Called next patient: ' . ($enriched['patient_name'] ?? 'Unknown'),
                'data' => $enriched
            ];
        });
    }

    private function validateVitalSigns(array $data): ?string
    {
        $bp = trim((string)($data['blood_pressure'] ?? ''));
        if ($bp !== '') {
            if (!preg_match('/^\d{2,3}\/\d{2,3}$/', $bp)) return 'Blood pressure must use the format 120/80';
            [$systolic, $diastolic] = array_map('intval', explode('/', $bp));
            if ($systolic < 50 || $systolic > 300 || $diastolic < 30 || $diastolic > 200) return 'Blood pressure must be between 50/30 and 300/200 mmHg';
        }

        $ranges = [
            'heart_rate' => [20, 250, 'Heart rate must be between 20 and 250 bpm'],
            'temperature' => [25, 45, 'Temperature must be between 25 and 45 C'],
            'respiratory_rate' => [5, 80, 'Respiratory rate must be between 5 and 80'],
            'oxygen_saturation' => [50, 100, 'O2 saturation must be between 50 and 100%'],
            'weight' => [0.1, 500, 'Weight must be between 0.1 and 500 kg'],
            'height' => [30, 250, 'Height must be between 30 and 250 cm'],
            'blood_sugar' => [20, 1000, 'Blood sugar must be between 20 and 1000 mg/dL'],
            'gcs_eye' => [1, 4, 'GCS eye score must be between 1 and 4'],
            'gcs_verbal' => [1, 5, 'GCS verbal score must be between 1 and 5'],
            'gcs_motor' => [1, 6, 'GCS motor score must be between 1 and 6']
        ];

        foreach ($ranges as $field => [$minimum, $maximum, $message]) {
            $value = $data[$field] ?? '';
            if ($value === '') continue;
            if (!is_numeric($value) || (float)$value < $minimum || (float)$value > $maximum) return $message;
        }

        return null;
    }

    private function prepareDbData(array $data): array
    {
        $dbData = [];

        if (isset($data['triage_id'])) $dbData['triage_id'] = trim((string)$data['triage_id']);
        if (isset($data['patient_id'])) $dbData['patient_id'] = (int)$data['patient_id'];
        if (isset($data['nurse_id'])) $dbData['nurse_id'] = (int)$data['nurse_id'];
        else $dbData['nurse_id'] = 1;
        if (isset($data['doctor_id']) && $data['doctor_id'] !== '') $dbData['doctor_id'] = (int)$data['doctor_id'];
        if (isset($data['doctor_assigned'])) $dbData['doctor_assigned'] = trim((string)$data['doctor_assigned']);

        if (isset($data['blood_pressure'])) $dbData['blood_pressure'] = trim((string)$data['blood_pressure']);
        if (isset($data['heart_rate']) && $data['heart_rate'] !== '') $dbData['heart_rate'] = (int)$data['heart_rate'];
        if (isset($data['temperature']) && $data['temperature'] !== '') $dbData['temperature'] = (float)$data['temperature'];
        if (isset($data['respiratory_rate']) && $data['respiratory_rate'] !== '') $dbData['respiratory_rate'] = (int)$data['respiratory_rate'];
        if (isset($data['oxygen_saturation']) && $data['oxygen_saturation'] !== '') $dbData['oxygen_saturation'] = (int)$data['oxygen_saturation'];
        if (isset($data['weight']) && $data['weight'] !== '') $dbData['weight'] = (float)$data['weight'];
        if (isset($data['height']) && $data['height'] !== '') $dbData['height'] = (float)$data['height'];
        if (isset($data['blood_sugar']) && $data['blood_sugar'] !== '') $dbData['blood_sugar'] = (float)$data['blood_sugar'];
        if (isset($data['blood_sugar_type'])) $dbData['blood_sugar_type'] = trim((string)$data['blood_sugar_type']);
        if (isset($data['gcs_eye'])) $dbData['gcs_eye'] = (int)$data['gcs_eye'];
        if (isset($data['gcs_verbal'])) $dbData['gcs_verbal'] = (int)$data['gcs_verbal'];
        if (isset($data['gcs_motor'])) $dbData['gcs_motor'] = (int)$data['gcs_motor'];

        if (isset($data['symptoms'])) {
            $dbData['symptoms'] = is_array($data['symptoms']) ? implode(', ', $data['symptoms']) : trim((string)$data['symptoms']);
        }
        if (isset($data['priority'])) $dbData['priority'] = strtolower(trim((string)$data['priority']));
        if (isset($data['allergies'])) $dbData['allergies'] = trim((string)$data['allergies']);
        if (isset($data['medications'])) $dbData['medications'] = trim((string)$data['medications']);
        if (isset($data['notes'])) $dbData['notes'] = trim((string)$data['notes']);
        if (isset($data['chief_complaint']) && !isset($dbData['notes'])) {
            $dbData['notes'] = trim((string)$data['chief_complaint']);
        }
        if (isset($data['consultation_id']) && $data['consultation_id'] !== '') $dbData['consultation_id'] = (int)$data['consultation_id'];
        if (isset($data['status'])) $dbData['status'] = strtolower(trim((string)$data['status']));

        return $dbData;
    }

    private function getPatientsMap(): array
    {
        try {
            $patients = $this->patientModel->all();
            $map = [];
            foreach ($patients as $p) {
                if (isset($p['id'])) {
                    $map[$p['id']] = $p;
                }
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function getEmployeesMap(): array
    {
        try {
            $employees = $this->employeeModel->all();
            $map = [];
            foreach ($employees as $e) {
                if (isset($e['id'])) {
                    $map[$e['id']] = $e;
                }
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }

    private function enrichTriage(array $triage, array $patientsMap, array $employeesMap): array
    {
        $patientId = $triage['patient_id'] ?? null;
        $patient = $patientsMap[$patientId] ?? null;

        if ($patient) {
            $firstName = $patient['first_name'] ?? '';
            $lastName = $patient['last_name'] ?? '';
            $patientName = trim($firstName . ' ' . $lastName);
            if (empty($patientName)) {
                $patientName = $patient['name'] ?? ('Patient #' . $patientId);
            }
            $triage['patient_name'] = $patientName;
            $triage['patient_code'] = $patient['patient_id'] ?? ('P-' . $patientId);
            $triage['gender'] = $patient['gender'] ?? 'Unspecified';

            if (isset($patient['birth_date'])) {
                try {
                    $dob = new DateTime($patient['birth_date']);
                    $now = new DateTime();
                    $triage['age'] = $now->diff($dob)->y;
                } catch (Throwable $e) {
                    $triage['age'] = $patient['age'] ?? 'N/A';
                }
            } else {
                $triage['age'] = $patient['age'] ?? 'N/A';
            }

            $parts = explode(' ', $patientName);
            $initials = '';
            foreach ($parts as $p) {
                if (!empty($p)) $initials .= strtoupper($p[0]);
            }
            $triage['patient_avatar'] = substr($initials, 0, 2) ?: 'P';
        } else {
            $triage['patient_name'] = 'Patient #' . ($patientId ?? 'N/A');
            $triage['patient_code'] = 'P-' . ($patientId ?? '00');
            $triage['gender'] = 'Unspecified';
            $triage['age'] = 'N/A';
            $triage['patient_avatar'] = 'P';
        }

        $nurseId = $triage['nurse_id'] ?? null;
        $nurse = $employeesMap[$nurseId] ?? null;
        if ($nurse) {
            $nurseName = trim(($nurse['first_name'] ?? '') . ' ' . ($nurse['last_name'] ?? ''));
            $triage['nurse_name'] = !empty($nurseName) ? 'Nurse ' . $nurseName : ($nurse['name'] ?? 'Nurse #' . $nurseId);
        } else {
            $triage['nurse_name'] = 'Nurse #' . ($nurseId ?? '1');
        }

        // Parse symptoms into array if string
        if (isset($triage['symptoms']) && is_string($triage['symptoms'])) {
            $symptomsArr = array_filter(array_map('trim', explode(',', $triage['symptoms'])));
            $triage['symptoms_list'] = array_values($symptomsArr);
        } else {
            $triage['symptoms_list'] = is_array($triage['symptoms'] ?? null) ? $triage['symptoms'] : [];
        }

        $triage['chief_complaint'] = $triage['notes'] ?? '';

        // Map DB status to frontend status
        $dbStatus = strtolower($triage['status'] ?? 'pending');
        $statusMap = [
            'pending' => 'waiting',
            'in_triage' => 'in_triage',
            'triaged' => 'sent_to_doctor',
            'sent_to_doctor' => 'sent_to_doctor',
            'in_consultation' => 'in_consultation',
            'consulted' => 'completed',
            'completed' => 'completed',
            'cancelled' => 'cancelled'
        ];
        $triage['status'] = $statusMap[$dbStatus] ?? 'sent_to_doctor';

        return $triage;
    }
}