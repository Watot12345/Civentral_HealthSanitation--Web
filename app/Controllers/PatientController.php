<?php
// app/Controllers/PatientController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Patient.php';

class PatientController extends BaseController
{
    private Patient $patientModel;
    
    public function __construct()
    {
        $this->patientModel = new Patient();
    }
    
    public function index(): void
    {
        $patients = $this->patientModel->all(['order' => 'created_at.desc']);
        $patients = array_map([$this, 'mapToFrontend'], $patients);
        
        $this->handle(function() use ($patients) {
            return [
                'success' => true,
                'data' => $patients,
                'total' => count($patients)
            ];
        });
    }
    
    public function show(string $id): void
    {
        $patient = $this->patientModel->find($id);
        
        $this->handle(function() use ($patient) {
            if (!$patient) {
                return ['success' => false, 'message' => 'Patient not found', 'code' => 404];
            }
            return ['success' => true, 'data' => $this->mapToFrontend($patient)];
        });
    }
    
    public function store(): void
    {
        $data = $this->input();
        
        // DEBUG
        error_log('📝 Store patient data: ' . json_encode($data));
        
        $this->handle(function() use ($data) {
            if (!empty($data['patient_id']) && $this->patientModel->findByPatientId($data['patient_id'])) {
                return ['success' => false, 'message' => 'Patient ID already exists', 'code' => 409];
            }
            
            // Map data to database format - FOR NEW PATIENT (isNew = true)
            $dbData = $this->mapToDb($data, true);
            
            // Ensure required fields
            $dbData['registration_date'] = $dbData['registration_date'] ?? date('Y-m-d');
            $dbData['status'] = $dbData['status'] ?? 'active';
            
            // Generate patient_id if not provided
            if (empty($dbData['patient_id'])) {
                $lastPatient = $this->patientModel->all(['order' => 'id.desc', 'limit' => 1]);
                $lastId = 0;
                if (!empty($lastPatient)) {
                    $lastPatientId = $lastPatient[0]['patient_id'] ?? '';
                    if (preg_match('/P-2024-(\d+)/', $lastPatientId, $matches)) {
                        $lastId = (int)$matches[1];
                    }
                }
                $dbData['patient_id'] = 'P-2024-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
            }
            
            // Validate required fields
            if (empty($dbData['first_name']) || empty($dbData['last_name'])) {
                return ['success' => false, 'message' => 'First name and last name are required', 'code' => 400];
            }
            
            if (empty($dbData['contact'])) {
                return ['success' => false, 'message' => 'Contact number is required', 'code' => 400];
            }

            $dbData['contact'] = preg_replace('/\D+/', '', (string)$dbData['contact']);
            if (!preg_match('/^\d{12}$/', $dbData['contact'])) {
                return ['success' => false, 'message' => 'Contact number must contain exactly 12 digits', 'code' => 422];
            }
            
            // Make sure birth_date is set for new patient
            if (empty($dbData['birth_date'])) {
                // Emergency fallback - use age or default
                if (!empty($data['age'])) {
                    $age = (int)$data['age'];
                    $dbData['birth_date'] = date('Y-m-d', strtotime("-$age years"));
                } else {
                    $dbData['birth_date'] = '1990-01-01';
                }
            }
            
            error_log('📝 Store dbData: ' . json_encode($dbData));
            
            $result = $this->patientModel->create($dbData);
            
            if (class_exists('ActivityLog') || file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $fullName = trim(($dbData['first_name'] ?? '') . ' ' . ($dbData['last_name'] ?? ''));
                    $pid = $dbData['patient_id'] ?? ($result['patient_id'] ?? '');
                    $logger->log("Registered New Patient: {$fullName}", [
                        'module'  => 'Health Center Services',
                        'details' => "Patient ID: {$pid} | Barangay: " . ($dbData['barangay'] ?? 'N/A'),
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {
                    error_log('PatientController store log error: ' . $e->getMessage());
                }
            }
            
            return ['success' => true, 'message' => 'Patient created successfully', 'data' => $result, 'code' => 201];
        });
    }
    
    public function update(string $id): void
    {
        $data = $this->input();
        
        // DEBUG
        error_log('UPDATE patient id=' . $id . ' data=' . json_encode($data));
        
        $this->handle(function() use ($id, $data) {
            $patient = $this->patientModel->find($id);
            if (!$patient) {
                return ['success' => false, 'message' => 'Patient not found', 'code' => 404];
            }
            
            // Map data to database format - FOR EXISTING PATIENT (isNew = false)
            $dbData = $this->mapToDb($data, false);
            
            // DEBUG
            error_log('UPDATE dbData=' . json_encode($dbData));

            if (isset($dbData['contact'])) {
                $dbData['contact'] = preg_replace('/\D+/', '', (string)$dbData['contact']);
                if (!preg_match('/^\d{12}$/', $dbData['contact'])) {
                    return ['success' => false, 'message' => 'Contact number must contain exactly 12 digits', 'code' => 422];
                }
            }
            
            $result = $this->patientModel->updateById($id, $dbData);
            
            if (class_exists('ActivityLog') || file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $fullName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    $logger->log("Updated Patient Record: {$fullName}", [
                        'module'  => 'Health Center Services',
                        'details' => "Updated patient record #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {
                    error_log('PatientController update log error: ' . $e->getMessage());
                }
            }
            
            return ['success' => true, 'message' => 'Patient updated successfully', 'data' => $result];
        });
    }
    
    public function destroy(string $id): void
    {
        $this->handle(function() use ($id) {
            $patient = $this->patientModel->find($id);
            if (!$patient) {
                return ['success' => false, 'message' => 'Patient not found', 'code' => 404];
            }
            $success = $this->patientModel->deleteById($id);
            
            if ($success && (class_exists('ActivityLog') || file_exists(__DIR__ . '/../Models/ActivityLog.php'))) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $fullName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    $logger->log("Deleted Patient Record: {$fullName}", [
                        'module'  => 'Health Center Services',
                        'details' => "Removed patient record #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {
                    error_log('PatientController destroy log error: ' . $e->getMessage());
                }
            }
            
            return ['success' => $success, 'message' => $success ? 'Patient deleted successfully' : 'Failed to delete patient'];
        });
    }
    
    public function search(): void
    {
        $query = $_GET['q'] ?? '';
        $this->handle(function() use ($query) {
            if (empty($query)) {
                return ['success' => false, 'message' => 'Search query is required', 'code' => 400];
            }
            $results = $this->patientModel->search($query);
            $results = array_map([$this, 'mapToFrontend'], $results);
            return ['success' => true, 'data' => $results, 'total' => count($results)];
        });
    }

    private function mapToFrontend(array $patient): array
    {
        if (!isset($patient['age']) && isset($patient['birth_date'])) {
            $dob = new DateTime($patient['birth_date']);
            $now = new DateTime();
            $patient['age'] = $now->diff($dob)->y;
        }
        
        if (isset($patient['medical_history'])) {
            $history = is_string($patient['medical_history']) 
                ? json_decode($patient['medical_history'], true) 
                : $patient['medical_history'];
            $patient['conditions'] = $history['conditions'] ?? 'None';
        } else {
            $patient['conditions'] = 'None';
        }
        
        if (!isset($patient['last_visit'])) {
            $patient['last_visit'] = isset($patient['updated_at']) 
                ? substr($patient['updated_at'], 0, 10) 
                : date('Y-m-d');
        }
        
        return $patient;
    }

    private function mapToDb(array $data, bool $isNew = false): array
    {
        // Only include fields that exist in the database
        $allowedFields = [
            'first_name', 'last_name', 'middle_name', 'email', 'contact',
            'gender', 'birth_date', 'blood_type', 'status', 'barangay',
            'address', 'emergency_contact', 'allergies', 'medical_history',
            'patient_id', 'registration_date'
        ];
        
        $dbData = [];
        
        // Always copy allowed fields if they have a value
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields) && $value !== null && $value !== '') {
                $dbData[$key] = $value;
            }
        }
        
        // Convert conditions to medical_history JSON
        if (!empty($data['conditions'])) {
            $dbData['medical_history'] = json_encode(['conditions' => $data['conditions']]);
        }
        
        // Handle birth_date - DIFFERENT FOR NEW VS EXISTING
        if ($isNew) {
            // FOR NEW PATIENTS: birth_date is REQUIRED
            if (!empty($data['age'])) {
                $age = (int)$data['age'];
                $dbData['birth_date'] = date('Y-m-d', strtotime("-$age years"));
            } elseif (!empty($data['birth_date'])) {
                $dbData['birth_date'] = $data['birth_date'];
            } else {
                // Default fallback for new patients
                $dbData['birth_date'] = '1990-01-01';
            }
        } else {
            // FOR EXISTING PATIENTS: only update if provided
            if (empty($dbData['birth_date']) && !empty($data['age'])) {
                $age = (int)$data['age'];
                $dbData['birth_date'] = date('Y-m-d', strtotime("-$age years"));
            }
            
            // Remove empty birth_date to keep existing value in database
            if (empty($dbData['birth_date'])) {
                unset($dbData['birth_date']);
            }
        }
        
        // DEBUG
        error_log('mapToDb output (isNew=' . ($isNew ? 'true' : 'false') . '): ' . json_encode($dbData));
        
        return $dbData;
    }
}