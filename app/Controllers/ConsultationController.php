<?php
// app/Controllers/ConsultationController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Consultation.php';
require_once __DIR__ . '/../Models/Patient.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Models/Appointment.php';

class ConsultationController extends BaseController
{
    private Consultation $consultationModel;
    private Patient $patientModel;
    private Employee $employeeModel;
    private Appointment $appointmentModel;

    public function __construct()
    {
        $this->consultationModel = new Consultation();
        $this->patientModel = new Patient();
        $this->employeeModel = new Employee();
        $this->appointmentModel = new Appointment();
    }

    public function index(): void
    {
        $rawConsultations = $this->consultationModel->all(['order' => 'date.desc,created_at.desc']);
        $patientsMap = $this->getPatientsMap();
        $employeesMap = $this->getEmployeesMap();

        $consultations = array_map(function ($c) use ($patientsMap, $employeesMap) {
            return $this->enrichConsultation($c, $patientsMap, $employeesMap);
        }, $rawConsultations);

        $this->handle(function() use ($consultations) {
            return [
                'success' => true,
                'data' => $consultations,
                'total' => count($consultations)
            ];
        });
    }

    public function show(string $id): void
    {
        $consultation = $this->consultationModel->find($id);
        
        $this->handle(function() use ($consultation) {
            if (!$consultation) {
                return [
                    'success' => false,
                    'message' => 'Consultation not found',
                    'code' => 404
                ];
            }

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();
            
            return [
                'success' => true,
                'data' => $this->enrichConsultation($consultation, $patientsMap, $employeesMap)
            ];
        });
    }

    public function store(): void
    {
        $data = $this->input();
        
        error_log('STORE called with: ' . json_encode($data));

        $this->handle(function() use ($data) {
            if (empty($data['patient_id'])) {
                return ['success' => false, 'message' => 'Patient selection is required', 'code' => 400];
            }

            // Enforce vital signs requirement from Settings
            $requireVitals = class_exists('Settings') ? (bool)Settings::get('modules.health_center.require_vital_signs', true) : true;
            if ($requireVitals) {
                $vitalError = $this->validateVitalSigns($data['vital_signs'] ?? null);
                if ($vitalError) {
                    return ['success' => false, 'message' => $vitalError, 'code' => 422];
                }
            }

            $rawAppointmentId = $data['appointment_id'] ?? null;
            $triageId = $data['triage_id'] ?? null;

            // Extract triage ID if appointment_id is a TRG- string (e.g. TRG-52)
            if (!empty($rawAppointmentId) && is_string($rawAppointmentId) && str_starts_with($rawAppointmentId, 'TRG-')) {
                if (empty($triageId)) {
                    $triageId = substr($rawAppointmentId, 4);
                }
            }

            $dbData = $this->prepareDbData($data);
            
            // Respect prescription and referral feature toggles
            $enablePrescriptions = class_exists('Settings') ? (bool)Settings::get('modules.health_center.enable_prescriptions', true) : true;
            if (!$enablePrescriptions && !empty($dbData['prescriptions'])) {
                $dbData['prescriptions'] = null;
            }

            $enableReferrals = class_exists('Settings') ? (bool)Settings::get('modules.health_center.enable_referrals', true) : true;
            if (!$enableReferrals && !empty($dbData['referral_to'])) {
                $dbData['referral_to'] = null;
            }

            error_log('STORE dbData: ' . json_encode($dbData));

            if (empty($dbData['consultation_id'])) {
                $dbData['consultation_id'] = $this->consultationModel->generateConsultationId();
            }

            $result = $this->consultationModel->create($dbData);
            
            error_log('STORE result: ' . json_encode($result));

            // Update appointment status to completed if numeric appointment_id is provided
            if (!empty($rawAppointmentId) && is_numeric($rawAppointmentId)) {
                try {
                    error_log('Updating appointment ' . $rawAppointmentId . ' to completed');
                    $this->appointmentModel->updateStatus((string)$rawAppointmentId, 'completed');
                    error_log('Appointment status updated successfully');
                } catch (Throwable $e) {
                    error_log('Failed to update appointment status: ' . $e->getMessage());
                }
            }

            // Update triage/assessment status to consulted if triage_id is provided or extracted
            if (!empty($triageId)) {
                try {
                    error_log('Updating triage ' . $triageId . ' to consulted');
                    require_once __DIR__ . '/../Models/Triage.php';
                    $triageModel = new Triage();
                    $triageModel->updateStatus($triageId, 'consulted');
                } catch (Throwable $e) {
                    error_log('Failed to update triage status: ' . $e->getMessage());
                }
            }

            // Primary Source Health Surveillance Bridge (Auto-detect & sync to surveillance_cases)
            try {
                require_once __DIR__ . '/../services/ClinicalSurveillanceService.php';
                $survService = new ClinicalSurveillanceService();
                $survService->syncConsultation($dbData);
            } catch (Throwable $e) {
                error_log('Consultation Health Surveillance Bridge error: ' . $e->getMessage());
            }

            return ['success' => true, 'message' => 'Consultation created successfully', 'data' => $result, 'code' => 201];
        });
    }
    public function update(string $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $consultation = $this->consultationModel->find($id);
            if (!$consultation) {
                return [
                    'success' => false,
                    'message' => 'Consultation not found',
                    'code' => 404
                ];
            }

            if (strtolower($consultation['status'] ?? '') === 'completed') {
                return [
                    'success' => false,
                    'message' => 'This consultation transaction is completed and finalized. Edits are not permitted.',
                    'code' => 403
                ];
            }

            $vitalError = $this->validateVitalSigns($data['vital_signs'] ?? null);
            if ($vitalError) {
                return ['success' => false, 'message' => $vitalError, 'code' => 422];
            }

            $dbData = $this->prepareDbData($data, true);
            $result = $this->consultationModel->updateById($id, $dbData);

            // Primary Source Health Surveillance Bridge (Auto-update or remove surveillance case on diagnosis change)
            try {
                require_once __DIR__ . '/../services/ClinicalSurveillanceService.php';
                $survService = new ClinicalSurveillanceService();
                $mergedData = array_merge($consultation, $dbData);
                $survService->syncConsultation($mergedData);
            } catch (Throwable $e) {
                error_log('Consultation Health Surveillance update bridge error: ' . $e->getMessage());
            }

            return [
                'success' => true,
                'message' => 'Consultation updated successfully',
                'data' => $result
            ];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function() use ($id) {
            $consultation = $this->consultationModel->find($id);
            if (!$consultation) {
                return [
                    'success' => false,
                    'message' => 'Consultation not found',
                    'code' => 404
                ];
            }

            // Remove associated surveillance case
            try {
                require_once __DIR__ . '/../services/ClinicalSurveillanceService.php';
                $survService = new ClinicalSurveillanceService();
                $survService->removeConsultationCase($consultation);
            } catch (Throwable $e) {
                error_log('Consultation Health Surveillance delete bridge error: ' . $e->getMessage());
            }

            $success = $this->consultationModel->deleteById($id);

            return [
                'success' => $success,
                'message' => $success ? 'Consultation deleted successfully' : 'Failed to delete consultation'
            ];
        });
    }

    public function search(): void
    {
        $query = strtolower($_GET['q'] ?? '');

        $this->handle(function() use ($query) {
            if (empty($query)) {
                return [
                    'success' => false,
                    'message' => 'Search query is required',
                    'code' => 400
                ];
            }

            $rawConsultations = $this->consultationModel->all();
            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();

            $enriched = array_map(fn($c) => $this->enrichConsultation($c, $patientsMap, $employeesMap), $rawConsultations);

            $results = array_values(array_filter($enriched, function($c) use ($query) {
                return str_contains(strtolower($c['patient_name'] ?? ''), $query) ||
                       str_contains(strtolower($c['consultation_id'] ?? ''), $query) ||
                       str_contains(strtolower($c['diagnosis'] ?? ''), $query) ||
                       str_contains(strtolower($c['icd_code'] ?? ''), $query) ||
                       str_contains(strtolower($c['symptoms'] ?? ''), $query) ||
                       str_contains(strtolower($c['doctor_name'] ?? ''), $query);
            }));

            return [
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ];
        });
    }

    private function validateVitalSigns($vitalSigns): ?string
    {
        if ($vitalSigns === null || $vitalSigns === '') {
            return null;
        }

        if (is_string($vitalSigns)) {
            $vitalSigns = json_decode($vitalSigns, true);
        }

        if (!is_array($vitalSigns)) {
            return 'Vital Signs must be provided as valid fields';
        }

        $bp = trim((string)($vitalSigns['bp'] ?? ''));
        $hr = trim((string)($vitalSigns['hr'] ?? ''));
        $temp = trim((string)($vitalSigns['temp'] ?? ''));
        $weight = trim((string)($vitalSigns['weight'] ?? ''));

        if ($bp !== '') {
            if (!preg_match('/^\d{2,3}\/\d{2,3}$/', $bp)) {
                return 'Blood pressure must use the format 120/80';
            }
            [$systolic, $diastolic] = array_map('intval', explode('/', $bp));
            if ($systolic < 50 || $systolic > 300 || $diastolic < 30 || $diastolic > 200) {
                return 'Blood pressure must be between 50/30 and 300/200 mmHg';
            }
        }

        if ($hr !== '' && (!preg_match('/^\d+$/', $hr) || (int)$hr < 20 || (int)$hr > 250)) {
            return 'Heart rate must be between 20 and 250 bpm';
        }
        if ($temp !== '' && (!preg_match('/^\d+(\.\d{1,2})?$/', $temp) || (float)$temp < 25 || (float)$temp > 45)) {
            return 'Temperature must be between 25 and 45 C';
        }
        if ($weight !== '' && (!preg_match('/^\d+(\.\d{1,2})?$/', $weight) || (float)$weight < 0.1 || (float)$weight > 500)) {
            return 'Weight must be between 0.1 and 500 kg';
        }

        return null;
    }

   private function prepareDbData(array $data, bool $isUpdate = false): array
{
    $dbData = [];

    // Only include fields that have values
    $allowedFields = [
        'consultation_id', 'patient_id', 'employee_id', 'appointment_id',
        'date', 'time', 'diagnosis', 'icd_code', 'symptoms',
        'vital_signs', 'treatment_plan', 'notes', 'follow_up_date', 'status'
    ];

    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields) && $value !== null && $value !== '') {
            $dbData[$key] = $value;
        }
    }

    // Ensure appointment_id is a valid integer or unset (e.g. TRG-52 string is not an integer ID in appointments table)
    if (isset($dbData['appointment_id'])) {
        $appId = (string)$dbData['appointment_id'];
        if (is_numeric($appId)) {
            $dbData['appointment_id'] = (int)$appId;
        } else {
            unset($dbData['appointment_id']);
        }
    }

    // Set defaults for new records
    if (!$isUpdate) {
        if (empty($dbData['date'])) $dbData['date'] = date('Y-m-d');
        if (empty($dbData['time'])) $dbData['time'] = date('H:i:s');
        if (empty($dbData['status'])) $dbData['status'] = 'in_progress';
        if (empty($dbData['employee_id'])) $dbData['employee_id'] = 1;
    }

    // Convert vital_signs to JSON if array
    if (isset($dbData['vital_signs']) && is_array($dbData['vital_signs'])) {
        $dbData['vital_signs'] = json_encode($dbData['vital_signs']);
    }

    // Handle treatment/treatment_plan mapping
    if (isset($data['treatment']) && !isset($dbData['treatment_plan'])) {
        $dbData['treatment_plan'] = trim($data['treatment']);
    }

    // DO NOT send updated_at - Supabase handles this
    unset($dbData['updated_at']);

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

    private function enrichConsultation(array $c, array $patientsMap, array $employeesMap): array
    {
        $patientId = $c['patient_id'] ?? null;
        $patient = $patientsMap[$patientId] ?? null;

        if ($patient) {
            $firstName = $patient['first_name'] ?? '';
            $lastName = $patient['last_name'] ?? '';
            $patientName = trim("$firstName $lastName");
            $patientCode = $patient['patient_id'] ?? "P-$patientId";

            $initials = '';
            if (!empty($firstName)) $initials .= strtoupper(substr($firstName, 0, 1));
            if (!empty($lastName)) $initials .= strtoupper(substr($lastName, 0, 1));
            $avatar = !empty($initials) ? $initials : 'PT';
        } else {
            $patientName = "Patient #{$patientId}";
            $patientCode = "P-{$patientId}";
            $avatar = "PT";
        }

        $employeeId = $c['employee_id'] ?? null;
        $employee = $employeesMap[$employeeId] ?? null;

        if ($employee) {
            $docFirst = $employee['first_name'] ?? '';
            $docLast = $employee['last_name'] ?? '';
            $docTitle = $employee['title'] ?? $employee['role'] ?? 'Dr.';
            $doctorName = trim("$docTitle $docFirst $docLast");
            if (empty(trim("$docFirst $docLast"))) {
                $doctorName = $employee['name'] ?? $employee['username'] ?? "Employee #{$employeeId}";
            }
        } else {
            $doctorName = "Attending Physician #" . ($employeeId ?? '1');
        }

        // Parse vital signs if JSON
        $vitalSigns = $c['vital_signs'] ?? null;
        if (is_string($vitalSigns)) {
            $decoded = json_decode($vitalSigns, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $vitalSigns = $decoded;
            }
        }

        return [
            'id' => (int)($c['id'] ?? 0),
            'consultation_id' => $c['consultation_id'] ?? '',
            'patient_id' => (int)($c['patient_id'] ?? 0),
            'patient_name' => $patientName,
            'patient_code' => $patientCode,
            'patient_avatar' => $avatar,
            'employee_id' => (int)($c['employee_id'] ?? 0),
            'doctor_name' => $doctorName,
            'appointment_id' => !empty($c['appointment_id']) ? (int)$c['appointment_id'] : null,
            'date' => $c['date'] ?? '',
            'time' => !empty($c['time']) ? substr($c['time'], 0, 8) : '',
            'diagnosis' => $c['diagnosis'] ?? '',
            'icd_code' => $c['icd_code'] ?? '',
            'symptoms' => $c['symptoms'] ?? '',
            'vital_signs' => $vitalSigns,
            'treatment_plan' => $c['treatment_plan'] ?? ($c['treatment'] ?? ''),
            'treatment' => $c['treatment_plan'] ?? ($c['treatment'] ?? ''),
            'notes' => $c['notes'] ?? '',
            'follow_up_date' => $c['follow_up_date'] ?? null,
            'follow_up' => $c['follow_up_date'] ?? null,
            'status' => $c['status'] ?? 'completed',
            'created_at' => $c['created_at'] ?? '',
            'updated_at' => $c['updated_at'] ?? ''
        ];
    }
}
