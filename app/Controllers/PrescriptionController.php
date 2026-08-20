<?php
// app/Controllers/PrescriptionController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Prescription.php';
require_once __DIR__ . '/../Models/Patient.php';
require_once __DIR__ . '/../Models/Employee.php';

class PrescriptionController extends BaseController
{
    private $prescriptionModel;
    private $patientModel;
    private $employeeModel;
    
    public function __construct()
    {
        $this->prescriptionModel = new Prescription();
        $this->patientModel = new Patient();
        $this->employeeModel = new Employee();
    }
    
    public function index(): void
    {
        $this->handle(function() {
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 5;
            $offset = ($page - 1) * $limit;
            
            // Get filter parameters
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            
            // ============================================================
            // STEP 1: Get paginated data
            // ============================================================
            $options = [
                'order' => 'created_at.desc',
                'limit' => $limit,
                'offset' => $offset
            ];
            
            if ($status) {
                $options['status'] = 'eq.' . $status;
            }
            
            if ($search) {
                $patientIds = $this->getPatientIdsMatching($search);
                if (!empty($patientIds) && $patientIds !== '0') {
                    $options['or'] = '(prescription_id.ilike.*' . $search . '*,patient_id.in.(' . $patientIds . '))';
                } else {
                    $options['or'] = 'prescription_id.ilike.*' . $search . '*';
                }
            }
            
            try {
                $prescriptions = $this->prescriptionModel->all($options);
                error_log("Fetched " . count($prescriptions) . " prescriptions");
            } catch (\Exception $e) {
                error_log("Failed to fetch prescriptions: " . $e->getMessage());
                $prescriptions = [];
            }
            
            // ============================================================
            // STEP 2: Get TOTAL count (without limit/offset)
            // ============================================================
            try {
                $countOptions = [];
                
                if ($status) {
                    $countOptions['status'] = 'eq.' . $status;
                }
                
                if ($search) {
                    $patientIds = $this->getPatientIdsMatching($search);
                    if (!empty($patientIds) && $patientIds !== '0') {
                        $countOptions['or'] = '(prescription_id.ilike.*' . $search . '*,patient_id.in.(' . $patientIds . '))';
                    } else {
                        $countOptions['or'] = 'prescription_id.ilike.*' . $search . '*';
                    }
                }
                
                // Get ALL records for counting (no limit/offset)
                $allPrescriptions = $this->prescriptionModel->all($countOptions);
                $total = count($allPrescriptions);
                error_log("Total count: " . $total);
                
            } catch (\Exception $e) {
                error_log("Failed to get total count: " . $e->getMessage());
                $total = count($prescriptions);
            }
            
            // ============================================================
            // STEP 3: Enrich data
            // ============================================================
            if (!empty($prescriptions)) {
                $prescriptions = $this->enrichPrescriptionsBatch($prescriptions);
            }
            
            // ============================================================
            // STEP 4: Return WITH total, page, limit, totalPages
            // ============================================================
            $result = [
                'success' => true,
                'message' => '',
                'data' => $prescriptions,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $total > 0 ? ceil($total / $limit) : 0
            ];
            
            // Debug: Log what we're returning
            error_log("API Response: " . json_encode([
                'total' => $result['total'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages'],
                'data_count' => count($result['data'])
            ]));
            
            return $result;
        });
    }
    
    private function getPatientIdsMatching(string $search): string
    {
        try {
            // Search patients by name and return IDs
            $patients = $this->patientModel->all([
                'or' => '(first_name.ilike.*' . $search . '*,last_name.ilike.*' . $search . '*)',
                'select' => 'id'
            ]);
            
            if (empty($patients)) {
                return '0';
            }
            
            $ids = array_column($patients, 'id');
            return implode(',', $ids);
        } catch (\Exception $e) {
            error_log("Failed to search patients: " . $e->getMessage());
            return '0';
        }
    }
    
    public function show(string $id): void
    {
        $this->handle(function() use ($id) {
            $prescription = $this->prescriptionModel->find($id);
            
            if (!$prescription) {
                return [
                    'success' => false,
                    'message' => 'Prescription not found',
                    'code' => 404
                ];
            }
            
            // Enrich with related data
            $prescription = $this->enrichPrescription($prescription);
            
            return [
                'success' => true,
                'data' => $prescription
            ];
        });
    }
    
    public function store(): void
    {
        $data = $this->input();
        
        $this->handle(function() use ($data) {
            // Validate required fields
            if (empty($data['patient_id'])) {
                return [
                    'success' => false,
                    'message' => 'Patient is required',
                    'code' => 400
                ];
            }
            
            if (empty($data['employee_id'])) {
                return [
                    'success' => false,
                    'message' => 'Doctor/Employee is required',
                    'code' => 400
                ];
            }
            
            if (empty($data['medications']) || !is_array($data['medications']) || count($data['medications']) === 0) {
                return [
                    'success' => false,
                    'message' => 'At least one medication is required',
                    'code' => 400
                ];
            }
            
            // Verify patient exists
            $patient = $this->patientModel->find($data['patient_id']);
            if (!$patient) {
                return [
                    'success' => false,
                    'message' => 'Patient not found',
                    'code' => 404
                ];
            }
            
            // Verify employee exists
            $employee = $this->employeeModel->find($data['employee_id']);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Doctor/Employee not found',
                    'code' => 404
                ];
            }
            
            // Map frontend fields to DB schema
            $dbData = $this->mapToDb($data);
            
            // Set default values
            $dbData['date'] = $dbData['date'] ?? date('Y-m-d');
            $dbData['status'] = $dbData['status'] ?? 'pending';
            
            $result = $this->prescriptionModel->create($dbData);
            
            // Enrich the created prescription
            $result = $this->enrichPrescription($result);

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $rxCode = $result['prescription_id'] ?? ($result['id'] ?? 'RX');
                    $pName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    $logger->log("Issued Prescription ({$rxCode}) for {$pName}", [
                        'module'  => 'Health Center Services',
                        'details' => "Medications: " . count($data['medications'] ?? []) . " item(s) prescribed",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }
            
            return [
                'success' => true,
                'message' => 'Prescription created successfully',
                'data' => $result,
                'code' => 201
            ];
        });
    }
    
    public function update(string $id): void
    {
        $data = $this->input();
        
        $this->handle(function() use ($id, $data) {
            $prescription = $this->prescriptionModel->find($id);
            
            if (!$prescription) {
                return [
                    'success' => false,
                    'message' => 'Prescription not found',
                    'code' => 404
                ];
            }
            
            // Map frontend fields to DB schema
            $dbData = $this->mapToDb($data);
            
            $result = $this->prescriptionModel->updateById($id, $dbData);
            
            // Enrich the updated prescription
            $result = $this->enrichPrescription($result);
            
            return [
                'success' => true,
                'message' => 'Prescription updated successfully',
                'data' => $result
            ];
        });
    }
    
    public function destroy(string $id): void
    {
        $this->handle(function() use ($id) {
            $prescription = $this->prescriptionModel->find($id);
            
            if (!$prescription) {
                return [
                    'success' => false,
                    'message' => 'Prescription not found',
                    'code' => 404
                ];
            }
            
            // Instead of hard delete, update status to cancelled
            $result = $this->prescriptionModel->updateById($id, ['status' => 'cancelled']);
            
            return [
                'success' => true,
                'message' => 'Prescription cancelled successfully',
                'data' => $result
            ];
        });
    }
    
    public function dispense(): void
    {
        $data = $this->input();
        
        $this->handle(function() use ($data) {
            if (empty($data['id'])) {
                return [
                    'success' => false,
                    'message' => 'Prescription ID is required',
                    'code' => 400
                ];
            }
            
            if (empty($data['employee_id'])) {
                return [
                    'success' => false,
                    'message' => 'Employee ID is required',
                    'code' => 400
                ];
            }
            
            $prescription = $this->prescriptionModel->find($data['id']);
            
            if (!$prescription) {
                return [
                    'success' => false,
                    'message' => 'Prescription not found',
                    'code' => 404
                ];
            }
            
            if ($prescription['status'] === 'dispensed') {
                return [
                    'success' => false,
                    'message' => 'Prescription already dispensed',
                    'code' => 400
                ];
            }
            
            if ($prescription['status'] === 'cancelled') {
                return [
                    'success' => false,
                    'message' => 'Cannot dispense cancelled prescription',
                    'code' => 400
                ];
            }
            
            // Use the model's dispense method
            $result = $this->prescriptionModel->dispense($data['id'], $data['employee_id']);
            
            // Enrich the result
            $result = $this->enrichPrescription($result);
            
            return [
                'success' => true,
                'message' => 'Prescription dispensed successfully',
                'data' => $result
            ];
        });
    }
    
    public function search(): void
    {
        $query = $_GET['q'] ?? '';
        
        $this->handle(function() use ($query) {
            if (empty($query)) {
                return [
                    'success' => false,
                    'message' => 'Search query is required',
                    'code' => 400
                ];
            }
            
            $queryLower = strtolower($query);
            
            // Search prescriptions by ID
            $prescriptions = $this->prescriptionModel->all([
                'order' => 'created_at.desc',
                'or' => '(prescription_id.ilike.*' . $query . '*)'
            ]);
            
            // Search patients by name
            $patients = $this->patientModel->all([
                'or' => '(first_name.ilike.*' . $query . '*,last_name.ilike.*' . $query . '*)'
            ]);
            $patientIds = array_column($patients, 'id');
            
            // If we found patients, get their prescriptions
            if (!empty($patientIds)) {
                $patientPrescriptions = $this->prescriptionModel->all([
                    'order' => 'created_at.desc',
                    'patient_id' => 'in.(' . implode(',', $patientIds) . ')'
                ]);
                $prescriptions = array_merge($prescriptions, $patientPrescriptions);
            }
            
            // Remove duplicates
            $prescriptions = array_unique($prescriptions, SORT_REGULAR);
            
            // Enrich with batched data
            $prescriptions = $this->enrichPrescriptionsBatch($prescriptions);
            
            // Filter by medications in PHP (since JSON search is limited)
            $results = array_values(array_filter($prescriptions, function($p) use ($queryLower) {
                $patientName = strtolower($p['patient_name'] ?? '');
                $rxId = strtolower($p['prescription_id'] ?? '');
                $medications = $p['medications'] ?? [];
                
                if (str_contains($patientName, $queryLower) || 
                    str_contains($rxId, $queryLower) ||
                    $this->medicationsContain($medications, $queryLower)) {
                    return true;
                }
                return false;
            }));
            
            return [
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ];
        });
    }
    
    private function enrichPrescription(array $prescription): array
    {
        // Get patient name
        if (isset($prescription['patient_id']) && !isset($prescription['patient_name'])) {
            $patient = $this->patientModel->find($prescription['patient_id']);
            if ($patient) {
                $prescription['patient_name'] = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                $prescription['patient_avatar'] = $this->generateAvatar($patient['first_name'] ?? '', $patient['last_name'] ?? '');
            } else {
                $prescription['patient_name'] = 'Unknown';
                $prescription['patient_avatar'] = '??';
            }
        }
        
        // Get doctor/employee name — employees table only has full_name (no first/last split)
        if (isset($prescription['employee_id']) && !isset($prescription['doctor_name'])) {
            $employee = $this->employeeModel->find($prescription['employee_id']);
            if ($employee) {
                $prescription['doctor_name'] = $employee['full_name'] ?? 'Unknown';
            } else {
                $prescription['doctor_name'] = 'Unknown';
            }
        }
        
        // Get dispensed by name — same, use full_name
        if (isset($prescription['dispensed_by']) && $prescription['dispensed_by'] && !isset($prescription['dispensed_by_name'])) {
            $dispenser = $this->employeeModel->find($prescription['dispensed_by']);
            if ($dispenser) {
                $prescription['dispensed_by_name'] = $dispenser['full_name'] ?? 'Unknown';
            }
        }
        
        // Decode medications JSON if it's a string
        if (isset($prescription['medications']) && is_string($prescription['medications'])) {
            $prescription['medications'] = json_decode($prescription['medications'], true) ?: [];
        }
        
        // Format dispensed_at
        if (isset($prescription['dispensed_at']) && $prescription['dispensed_at'] && !isset($prescription['dispensed_at_formatted'])) {
            $prescription['dispensed_at_formatted'] = $this->formatDateTime($prescription['dispensed_at']);
        }
        
        return $prescription;
    }
    
    private function enrichPrescriptionsBatch(array $prescriptions): array
    {
        if (empty($prescriptions)) {
            return $prescriptions;
        }
        
        // Collect all unique patient and employee IDs
        $patientIds = array_unique(array_filter(array_column($prescriptions, 'patient_id')));
        $employeeIds = array_unique(array_filter(array_column($prescriptions, 'employee_id')));
        $dispenserIds = array_unique(array_filter(array_column($prescriptions, 'dispensed_by')));
        
        // Batch fetch all patients
        $patients = [];
        if (!empty($patientIds)) {
            try {
                $patientsData = $this->patientModel->all(['id' => 'in.(' . implode(',', $patientIds) . ')']);
                foreach ($patientsData as $patient) {
                    $patients[$patient['id']] = $patient;
                }
            } catch (\Exception $e) {
                error_log("Failed to fetch patients batch: " . $e->getMessage());
            }
        }
        
        // Batch fetch all employees
        $employees = [];
        if (!empty($employeeIds)) {
            try {
                $employeesData = $this->employeeModel->all(['id' => 'in.(' . implode(',', $employeeIds) . ')']);
                foreach ($employeesData as $employee) {
                    $employees[$employee['id']] = $employee;
                }
            } catch (\Exception $e) {
                error_log("Failed to fetch employees batch: " . $e->getMessage());
            }
        }
        
        // Batch fetch all dispensers
        $dispensers = [];
        if (!empty($dispenserIds)) {
            try {
                $dispensersData = $this->employeeModel->all(['id' => 'in.(' . implode(',', $dispenserIds) . ')']);
                foreach ($dispensersData as $dispenser) {
                    $dispensers[$dispenser['id']] = $dispenser;
                }
            } catch (\Exception $e) {
                error_log("Failed to fetch dispensers batch: " . $e->getMessage());
            }
        }
        
        // Enrich all prescriptions with batched data
        foreach ($prescriptions as &$prescription) {
            // Enrich patient
            if (isset($prescription['patient_id']) && !isset($prescription['patient_name'])) {
                $patient = $patients[$prescription['patient_id']] ?? null;
                if ($patient) {
                    $prescription['patient_name'] = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    $prescription['patient_avatar'] = $this->generateAvatar($patient['first_name'] ?? '', $patient['last_name'] ?? '');
                }
            }
            
            // Enrich doctor — employees table uses full_name (no first/last split)
            if (isset($prescription['employee_id']) && !isset($prescription['doctor_name'])) {
                $employee = $employees[$prescription['employee_id']] ?? null;
                if ($employee) {
                    $prescription['doctor_name'] = $employee['full_name'] ?? 'Unknown';
                } else {
                    $prescription['doctor_name'] = 'Unknown';
                }
            }
            
            // Enrich dispenser — same, use full_name
            if (isset($prescription['dispensed_by']) && $prescription['dispensed_by'] && !isset($prescription['dispensed_by_name'])) {
                $dispenser = $dispensers[$prescription['dispensed_by']] ?? null;
                if ($dispenser) {
                    $prescription['dispensed_by_name'] = $dispenser['full_name'] ?? 'Unknown';
                }
            }
            
            // Decode medications JSON if it's a string
            if (isset($prescription['medications']) && is_string($prescription['medications'])) {
                $prescription['medications'] = json_decode($prescription['medications'], true) ?: [];
            }
            
            // Format dispensed_at
            if (isset($prescription['dispensed_at']) && !isset($prescription['dispensed_at_formatted'])) {
                $prescription['dispensed_at_formatted'] = $this->formatDateTime($prescription['dispensed_at']);
            }
        }
        
        return $prescriptions;
    }
    
    private function mapToDb(array $data): array
    {
        $dbData = $data;
        
        // Map frontend fields to DB fields
        $fieldMapping = [
            'rx_patient' => 'patient_id',
            'rx_doctor' => 'employee_id',
            'rx_date' => 'date',
            'rx_notes' => 'notes',
            'rx_consultation' => 'consultation_id'
        ];
        
        foreach ($fieldMapping as $frontend => $backend) {
            if (isset($data[$frontend])) {
                $dbData[$backend] = $data[$frontend];
                unset($dbData[$frontend]);
            }
        }
        
        // Handle medications
        if (isset($data['medications']) && is_array($data['medications'])) {
            $dbData['medications'] = $data['medications'];
        }
        
        // Remove frontend-only fields
        unset($dbData['rx_drug_select']);
        unset($dbData['rx_dosage']);
        unset($dbData['rx_frequency']);
        unset($dbData['rx_duration']);
        
        return $dbData;
    }
    
    private function generateAvatar(string $firstName, string $lastName): string
    {
        $first = strtoupper(substr($firstName, 0, 1));
        $last = strtoupper(substr($lastName, 0, 1));
        return $first . $last;
    }
    
    private function formatDateTime(string $datetime): string
    {
        $date = new \DateTime($datetime);
        return $date->format('M d, Y h:i A');
    }
    
    private function medicationsContain(array $medications, string $query): bool
    {
        foreach ($medications as $med) {
            if (is_array($med) && str_contains(strtolower($med['name'] ?? ''), $query)) {
                return true;
            }
        }
        return false;
    }
}