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
        $this->queueModel = new TriageQueue();
        $this->patientModel = new Patient();
    }

    public function index(): void
    {
        $this->handle(function() {
            $queue = $this->queueModel->getTodayQueue();
            
            // Enrich with patient names
            $enriched = array_map(function($item) {
                return $this->enrichQueueItem($item);
            }, $queue);

            return [
                'success' => true,
                'data' => $enriched,
                'total' => count($enriched)
            ];
        });
    }

    public function show(string|int $id): void
    {
        $this->handle(function() use ($id) {
            $item = $this->queueModel->find($id);
            
            if (!$item) {
                return [
                    'success' => false,
                    'message' => 'Queue record not found',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'data' => $this->enrichQueueItem($item)
            ];
        });
    }

    public function store(): void
    {
        $data = $this->input();

        $this->handle(function() use ($data) {
            $patientId = (int)($data['patient_id'] ?? 0);
            
            if (!$patientId) {
                return [
                    'success' => false,
                    'message' => 'Patient ID is required',
                    'code' => 400
                ];
            }

            // Check if patient already checked in today
            if ($this->queueModel->isPatientCheckedInToday($patientId)) {
                return [
                    'success' => false,
                    'message' => 'Patient already checked in today',
                    'code' => 409
                ];
            }

            // Verify patient exists
            $patient = $this->patientModel->find($patientId);
            if (!$patient) {
                return [
                    'success' => false,
                    'message' => 'Patient not found',
                    'code' => 404
                ];
            }

            $queueData = [
                'patient_id' => $patientId,
                'queue_number' => $data['queue_number'] ?? $this->queueModel->generateQueueNumber(),
                'check_in_time' => date('Y-m-d H:i:s'),
                'status' => 'waiting'
            ];

            $result = $this->queueModel->create($queueData);

            return [
                'success' => true,
                'message' => 'Patient checked in successfully',
                'queue_number' => $result['queue_number'] ?? $queueData['queue_number'],
                'data' => $result,
                'code' => 201
            ];
        });
    }

    public function updateStatus(string|int $id): void
    {
        $data = $this->input();
        $status = $data['status'] ?? null;

        $this->handle(function() use ($id, $status) {
            $validStatuses = ['waiting', 'in_triage', 'completed'];
            
            if (!$status || !in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid status. Must be: ' . implode(', ', $validStatuses),
                    'code' => 400
                ];
            }

            $existing = $this->queueModel->find($id);
            if (!$existing) {
                return [
                    'success' => false,
                    'message' => 'Queue record not found',
                    'code' => 404
                ];
            }

            $result = $this->queueModel->updateStatus($id, $status);

            return [
                'success' => true,
                'message' => 'Queue status updated to ' . $status,
                'data' => $result
            ];
        });
    }

    /**
     * Call next patient - get the next waiting patient
     */
    public function nextPatient(): void
    {
        $this->handle(function() {
            $todayQueue = $this->queueModel->getTodayQueue();
            
            // Filter waiting patients
            $waiting = array_values(array_filter($todayQueue, fn($q) => $q['status'] === 'waiting'));
            
            if (empty($waiting)) {
                return [
                    'success' => false,
                    'message' => 'No patients waiting in queue',
                    'code' => 404
                ];
            }

            // Get first patient (earliest check-in) and mark as called
            $next = $waiting[0];
            $this->queueModel->updateStatus($next['id'], 'in_triage');
            $updated = $this->queueModel->find($next['id']) ?? $next;
            $nextPatient = $this->enrichQueueItem($updated);

            return [
                'success' => true,
                'message' => 'Next patient: ' . ($nextPatient['patient_name'] ?? 'Unknown'),
                'data' => $nextPatient
            ];
        });
    }

    private function enrichQueueItem(array $item): array
    {
        $patientId = $item['patient_id'] ?? null;
        
        if ($patientId) {
            $patient = $this->patientModel->find($patientId);
            if ($patient) {
                $firstName = $patient['first_name'] ?? '';
                $lastName = $patient['last_name'] ?? '';
                $item['patient_name'] = trim($firstName . ' ' . $lastName) ?: ($patient['name'] ?? 'Patient #' . $patientId);
                $item['patient_code'] = $patient['patient_id'] ?? ('P-' . $patientId);
            } else {
                $item['patient_name'] = 'Patient #' . $patientId;
                $item['patient_code'] = 'P-' . $patientId;
            }
        }

        return $item;
    }
}