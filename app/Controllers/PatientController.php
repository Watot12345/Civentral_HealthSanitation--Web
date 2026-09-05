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
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 200) : 50;
        $offset = isset($_GET['offset']) ? max((int)$_GET['offset'], 0) : 0;

        $options = [
            'order'  => 'created_at.desc',
            'limit'  => $limit,
            'offset' => $offset
        ];

        $patients = $this->patientModel->all($options);
        $patients = array_map([$this, 'mapToFrontend'], $patients);
        
        $this->handle(function() use ($patients, $limit, $offset) {
            return [
                'success' => true,
                'data'    => $patients,
                'total'   => count($patients),
                'limit'   => $limit,
                'offset'  => $offset
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
            
            // Duplicate Patient Check (First Name + Last Name match)
            $firstName = strtolower(trim($dbData['first_name'] ?? ''));
            $lastName = strtolower(trim($dbData['last_name'] ?? ''));
            if (!empty($firstName) && !empty($lastName)) {
                $allPatients = $this->patientModel->all();
                foreach ($allPatients as $p) {
                    $pFirst = strtolower(trim($p['first_name'] ?? ''));
                    $pLast = strtolower(trim($p['last_name'] ?? ''));
                    if ($pFirst === $firstName && $pLast === $lastName) {
                        $existingCode = $p['patient_id'] ?? ('P-' . $p['id']);
                        $existingFullName = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                        return [
                            'success' => false,
                            'is_duplicate' => true,
                            'existing_code' => $existingCode,
                            'existing_name' => $existingFullName,
                            'message' => "Warning: Patient record already exists! ({$existingFullName} - {$existingCode}). Please search and filter existing records instead of registering duplicates.",
                            'code' => 409
                        ];
                    }
                }
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
        $this->handle(function() {
            return [
                'success' => false, 
                'message' => 'Deletion of patient records is disabled to preserve clinical history and compliance records.', 
                'code' => 403
            ];
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
            $dbData['medical_history'] = json_encode(['conditions' => $this->sanitizeFormula((string)$data['conditions'])]);
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
        
        // Sanitize string fields & enforce length caps
        $dbData = $this->sanitizePatientData($dbData);

        return $dbData;
    }

    public function import(): void
    {
        $input = $this->input();
        $rows = $input['rows'] ?? (array_keys($input) === range(0, count($input) - 1) ? $input : [$input]);

        $this->handle(function() use ($rows) {
            if (empty($rows)) {
                return ['success' => false, 'message' => 'No patient rows provided for import', 'code' => 400];
            }

            $imported = [];
            $skipped = 0;
            $errors = [];

            // Pre-fetch all existing patients for duplicate check
            $allPatients = $this->patientModel->all();
            $existingNames = [];
            foreach ($allPatients as $p) {
                $nameKey = strtolower(trim($p['first_name'] ?? '')) . '|' . strtolower(trim($p['last_name'] ?? ''));
                $existingNames[$nameKey] = true;
            }

            $lastId = 0;
            $lastPatient = $this->patientModel->all(['order' => 'id.desc', 'limit' => 1]);
            if (!empty($lastPatient)) {
                $lastPatientId = $lastPatient[0]['patient_id'] ?? '';
                if (preg_match('/P-2024-(\d+)/', $lastPatientId, $matches)) {
                    $lastId = (int)$matches[1];
                }
            }

            foreach ($rows as $index => $row) {
                $rowNum = $index + 1;
                $dbData = $this->mapToDb($row, true);
                
                $firstName = strtolower(trim($dbData['first_name'] ?? ''));
                $lastName  = strtolower(trim($dbData['last_name'] ?? ''));

                if (empty($firstName) || empty($lastName)) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: First name and last name are required.";
                    continue;
                }

                // Check for formula injection in raw input vs sanitized
                $rawFirstName = $row['first_name'] ?? '';
                $rawLastName  = $row['last_name'] ?? '';
                $wasFormulaSanitized = preg_match('/^[\=\+\-\@\t\r]/', trim($rawFirstName)) || preg_match('/^[\=\+\-\@\t\r]/', trim($rawLastName));

                $nameKey = $firstName . '|' . $lastName;
                if (isset($existingNames[$nameKey])) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: Patient " . trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) . " already exists. Skipped.";
                    continue;
                }

                // Format contact & gender
                $dbData['gender'] = !empty($dbData['gender']) ? ucfirst(strtolower($dbData['gender'])) : 'Male';
                $contactRaw = preg_replace('/\D+/', '', (string)($dbData['contact'] ?? ''));
                if (strlen($contactRaw) === 11 && str_starts_with($contactRaw, '09')) {
                    $contactRaw = '63' . substr($contactRaw, 1);
                } elseif (strlen($contactRaw) === 10 && str_starts_with($contactRaw, '9')) {
                    $contactRaw = '63' . $contactRaw;
                }
                if (strlen($contactRaw) !== 12) {
                    $contactRaw = '639' . str_pad(substr(crc32($nameKey), 0, 9), 9, '0', STR_PAD_LEFT);
                }
                $dbData['contact'] = $contactRaw;

                // Auto-generate patient_id
                $lastId++;
                $dbData['patient_id'] = 'P-2024-' . str_pad($lastId, 3, '0', STR_PAD_LEFT);
                $dbData['registration_date'] = date('Y-m-d');
                $dbData['status'] = !empty($dbData['status']) ? $dbData['status'] : 'active';

                try {
                    $created = $this->patientModel->create($dbData);
                    $existingNames[$nameKey] = true;
                    $imported[] = $created;

                    if ($wasFormulaSanitized) {
                        $errors[] = "Row {$rowNum}: Formula payload detected and sanitized before database insertion for " . trim(($dbData['first_name'] ?? '') . ' ' . ($dbData['last_name'] ?? '')) . ".";
                    }
                } catch (Throwable $e) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: Database insert failed - " . $e->getMessage();
                }
            }

            if (class_exists('ActivityLog') || file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $cnt = count($imported);
                    $logger->log("Batch Imported Patients: {$cnt} records", [
                        'module'  => 'Health Center Services',
                        'details' => "Imported: {$cnt} | Skipped: {$skipped}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            return [
                'success'        => true,
                'imported_count' => count($imported),
                'skipped_count'  => $skipped,
                'errors'         => $errors,
                'data'           => $imported,
                'message'        => count($imported) . " patient(s) imported successfully (" . $skipped . " skipped)."
            ];
        });
    }

    private function sanitizeFormula(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') return '';

        // Check if text starts with dangerous spreadsheet formula triggers: =, +, -, @, \t, \r
        if (preg_match('/^[\=\+\-\@\t\r]/', $trimmed)) {
            // Prefix with single quote and strip formula symbols to prevent execution
            $trimmed = "'" . preg_replace('/^[\=\+\-\@\t\r]+/', '', $trimmed);
        }
        return $trimmed;
    }

    private function sanitizePatientData(array $data): array
    {
        $lengthLimits = [
            'first_name'  => 50,
            'last_name'   => 50,
            'middle_name' => 50,
            'email'       => 100,
            'barangay'    => 100,
            'address'     => 255,
            'blood_type'  => 5,
            'gender'      => 10,
            'status'      => 20
        ];

        foreach ($data as $key => $val) {
            if (is_string($val)) {
                $sanitized = $this->sanitizeFormula($val);
                if (isset($lengthLimits[$key])) {
                    $sanitized = mb_substr($sanitized, 0, $lengthLimits[$key], 'UTF-8');
                }
                $data[$key] = $sanitized;
            }
        }

        return $data;
    }
}