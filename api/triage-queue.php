<?php
// app/Controllers/TriageQueueController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/TriageQueue.php';
require_once __DIR__ . '/../Models/Patient.php';

class TriageQueueController extends BaseController
{
    private TriageQueue $queueModel;
    private Patient $patientModel;

    public function __construct()
    {
        parent::__construct();
        $this->queueModel = new TriageQueue();
        $this->patientModel = new Patient();
    }

    /**
     * GET /api/triage-queue.php
     * List today's queue
     */
    public function index(): void
    {
        try {
            $queue = $this->queueModel->getTodayQueue();
            
            // Enrich with patient names
            $enriched = array_map(function($item) {
                return $this->enrichQueueItem($item);
            }, $queue);

            $this->json([
                'success' => true,
                'data' => $enriched,
                'total' => count($enriched)
            ]);
        } catch (Throwable $e) {
            error_log('TriageQueueController::index Error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch queue'
            ], 500);
        }
    }

    /**
     * GET /api/triage-queue.php?id=123
     * Show single queue record
     */
    public function show(string|int $id): void
    {
        try {
            $item = $this->queueModel->find($id);
            
            if (!$item) {
                $this->json([
                    'success' => false,
                    'message' => 'Queue record not found'
                ], 404);
                return;
            }

            $this->json([
                'success' => true,
                'data' => $this->enrichQueueItem($item)
            ]);
        } catch (Throwable $e) {
            error_log('TriageQueueController::show Error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch queue record'
            ], 500);
        }
    }

    /**
     * POST /api/triage-queue.php
     * Create new check-in
     */
    public function store(): void
    {
        try {
            $data = $this->getInput();
            
            $patientId = (int)($data['patient_id'] ?? 0);
            
            if (!$patientId) {
                $this->json([
                    'success' => false,
                    'message' => 'Patient ID is required'
                ], 400);
                return;
            }

            // Check if patient already checked in today
            if ($this->queueModel->isPatientCheckedInToday($patientId)) {
                // Return the existing queue number instead of error
                $todayQueue = $this->queueModel->getTodayQueue();
                foreach ($todayQueue as $item) {
                    if ((int)($item['patient_id'] ?? 0) === $patientId) {
                        $this->json([
                            'success' => true,
                            'message' => 'Patient already checked in today',
                            'queue_number' => $item['queue_number'] ?? null,
                            'data' => $this->enrichQueueItem($item)
                        ]);
                        return;
                    }
                }
            }

            // Verify patient exists
            $patient = $this->patientModel->find($patientId);
            if (!$patient) {
                $this->json([
                    'success' => false,
                    'message' => 'Patient not found in system'
                ], 404);
                return;
            }

            // Generate queue number if not provided
            $queueNumber = $data['queue_number'] ?? $this->queueModel->generateQueueNumber();
            $registrationMethod = $data['registration_method'] ?? 'staff';

            $queueData = [
                'patient_id' => $patientId,
                'queue_number' => $queueNumber,
                'check_in_time' => date('Y-m-d H:i:s'),
                'status' => 'waiting'
            ];

            // Add registration_method if your table has this column
            // $queueData['registration_method'] = $registrationMethod;

            $result = $this->queueModel->create($queueData);

            $this->json([
                'success' => true,
                'message' => 'Patient checked in successfully',
                'queue_number' => $result['queue_number'] ?? $queueNumber,
                'data' => $this->enrichQueueItem($result)
            ], 201);

        } catch (Throwable $e) {
            error_log('TriageQueueController::store Error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Failed to check in patient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT/PATCH /api/triage-queue.php?id=123&action=status
     * Update queue status
     */
    public function updateStatus(string|int $id): void
    {
        try {
            $data = $this->getInput();
            $status = $data['status'] ?? $_GET['status'] ?? null;
            
            $validStatuses = ['waiting', 'in_triage', 'completed'];
            
            if (!$status || !in_array($status, $validStatuses)) {
                $this->json([
                    'success' => false,
                    'message' => 'Invalid status. Must be: ' . implode(', ', $validStatuses)
                ], 400);
                return;
            }

            $existing = $this->queueModel->find($id);
            if (!$existing) {
                $this->json([
                    'success' => false,
                    'message' => 'Queue record not found'
                ], 404);
                return;
            }

            $result = $this->queueModel->updateStatus($id, $status);

            $this->json([
                'success' => true,
                'message' => 'Queue status updated to ' . $status,
                'data' => $this->enrichQueueItem($result)
            ]);

        } catch (Throwable $e) {
            error_log('TriageQueueController::updateStatus Error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * GET /api/triage-queue.php?action=next
     * Get next waiting patient
     */
    public function nextPatient(): void
    {
        try {
            $todayQueue = $this->queueModel->getTodayQueue();
            
            // Filter waiting patients
            $waiting = array_values(array_filter($todayQueue, function($q) {
                return ($q['status'] ?? '') === 'waiting';
            }));
            
            if (empty($waiting)) {
                $this->json([
                    'success' => false,
                    'message' => 'No patients waiting in queue'
                ], 404);
                return;
            }

            // Get first patient (earliest check-in)
            $nextPatient = $this->enrichQueueItem($waiting[0]);

            $this->json([
                'success' => true,
                'message' => 'Next patient: ' . ($nextPatient['patient_name'] ?? 'Unknown'),
                'data' => $nextPatient
            ]);

        } catch (Throwable $e) {
            error_log('TriageQueueController::nextPatient Error: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Failed to get next patient'
            ], 500);
        }
    }

    /**
     * Enrich queue item with patient details
     */
    private function enrichQueueItem(array $item): array
    {
        $patientId = $item['patient_id'] ?? null;
        
        if ($patientId) {
            try {
                $patient = $this->patientModel->find($patientId);
                if ($patient) {
                    $firstName = $patient['first_name'] ?? '';
                    $lastName = $patient['last_name'] ?? '';
                    $item['patient_name'] = trim($firstName . ' ' . $lastName) ?: ($patient['name'] ?? 'Patient #' . $patientId);
                    $item['patient_code'] = $patient['patient_id'] ?? ('P-' . $patientId);
                    $item['patient_gender'] = $patient['gender'] ?? 'Unspecified';
                    
                    // Calculate age
                    if (isset($patient['birth_date'])) {
                        try {
                            $dob = new DateTime($patient['birth_date']);
                            $now = new DateTime();
                            $item['patient_age'] = $now->diff($dob)->y;
                        } catch (Throwable $e) {
                            $item['patient_age'] = $patient['age'] ?? 'N/A';
                        }
                    } else {
                        $item['patient_age'] = $patient['age'] ?? 'N/A';
                    }
                } else {
                    $item['patient_name'] = 'Patient #' . $patientId;
                    $item['patient_code'] = 'P-' . $patientId;
                }
            } catch (Throwable $e) {
                $item['patient_name'] = 'Patient #' . $patientId;
                $item['patient_code'] = 'P-' . $patientId;
            }
        }

        return $item;
    }

    /**
     * Get JSON input data
     */
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $_POST ?: [];
        }
        
        return $data ?: [];
    }

    /**
     * Send JSON response
     */
    private function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}