<?php
// app/Controllers/ReferralController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Referral.php';
require_once __DIR__ . '/../Models/Patient.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Constants/Permissions.php';

use App\Constants\Permissions;

class ReferralController extends BaseController
{
    private $referralModel;
    private $patientModel;
    private $employeeModel;

    public function __construct()
    {
        $this->referralModel = new Referral();
        $this->patientModel = new Patient();
        $this->employeeModel = new Employee();
    }

    public function index(): void
    {
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::CONSULTATIONS_VIEW);

        $this->handle(function() {
            try {
                // Get all referrals
                $referrals = $this->referralModel->all(['order' => 'created_at.desc']);
                
                // If no referrals, return empty array
                if (empty($referrals)) {
                    return [
                        'success' => true,
                        'data' => [],
                        'total' => 0
                    ];
                }
                
                // Enrich with patient and doctor names
                $enrichedReferrals = [];
                foreach ($referrals as $referral) {
                    try {
                        $enrichedReferrals[] = $this->enrichReferral($referral);
                    } catch (Throwable $e) {
                        // If enrichment fails for one referral, still include it with basic data
                        error_log('Error enriching referral ID ' . ($referral['id'] ?? 'unknown') . ': ' . $e->getMessage());
                        $enrichedReferrals[] = $referral;
                    }
                }
                
                return [
                    'success' => true,
                    'data' => $enrichedReferrals,
                    'total' => count($enrichedReferrals)
                ];
            } catch (Exception $e) {
                error_log('ReferralController index error: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Failed to load referrals: ' . $e->getMessage(),
                    'data' => [],
                    'total' => 0
                ];
            }
        });
    }

    public function show(string|int $id): void
    {
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::CONSULTATIONS_VIEW);

        $this->handle(function() use ($id) {
            $referral = $this->referralModel->find($id);
            
            if (!$referral) {
                return [
                    'success' => false,
                    'message' => 'Referral not found',
                    'code' => 404
                ];
            }
            
            $referral = $this->enrichReferral($referral);
            
            return [
                'success' => true,
                'data' => $referral
            ];
        });
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::CONSULTATIONS_CREATE);

        $data = $this->input();
        
        $this->handle(function() use ($data) {
            // Validate required fields
            if (empty($data['patient_id'])) {
                return ['success' => false, 'message' => 'Patient is required', 'code' => 400];
            }
            if (empty($data['from_doctor_id'])) {
                return ['success' => false, 'message' => 'Referring doctor is required', 'code' => 400];
            }
            if (empty($data['reason'])) {
                return ['success' => false, 'message' => 'Reason for referral is required', 'code' => 400];
            }
            
            // Map frontend 'critical' to database 'emergency'
            if (isset($data['urgency']) && $data['urgency'] === 'critical') {
                $data['urgency'] = 'emergency';
            }
            
            // Set default values
            $data['status'] = $data['status'] ?? 'pending';
            $data['referral_type'] = $data['referral_type'] ?? 'specialist';
            
            unset($data['csrf_token']);
            
            $result = $this->referralModel->create($data);
            $enriched = $this->enrichReferral($result);
            
            return [
                'success' => true,
                'message' => 'Referral created successfully',
                'data'    => $enriched,
                'record'  => $enriched,
                'action'  => 'create',
                'code'    => 201
            ];
        });
    }

    public function update(string|int $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_EDIT);

        $data = $this->input();
        
        $this->handle(function() use ($id, $data) {
            $existing = $this->referralModel->find($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Referral not found', 'code' => 404];
            }
            
            // Map frontend 'critical' to database 'emergency'
            if (isset($data['urgency']) && $data['urgency'] === 'critical') {
                $data['urgency'] = 'emergency';
            }
            
            unset($data['csrf_token']);
            
            $result = $this->referralModel->update($id, $data);
            $enriched = $this->enrichReferral($this->referralModel->find($id) ?: array_merge($existing, $data));
            
            return [
                'success' => true,
                'message' => 'Referral updated successfully',
                'data'    => $enriched,
                'record'  => $enriched,
                'action'  => 'update',
                'id'      => $id
            ];
        });
    }

    public function updateStatus(string|int $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_EDIT);

        $data = $this->input();
        $status = $data['status'] ?? null;
        
        $this->handle(function() use ($id, $status) {
            if (!$status || !in_array($status, ['pending', 'accepted', 'completed', 'rejected', 'cancelled'])) {
                return ['success' => false, 'message' => 'Invalid status', 'code' => 400];
            }
            
            $existing = $this->referralModel->find($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Referral not found', 'code' => 404];
            }
            
            $updateData = ['status' => $status];
            
            // Set timestamps based on status
            if ($status === 'accepted') {
                $updateData['accepted_at'] = date('Y-m-d H:i:s');
            } elseif ($status === 'completed') {
                $updateData['completed_at'] = date('Y-m-d H:i:s');
            }
            
            $result = $this->referralModel->update($id, $updateData);
            $enriched = $this->enrichReferral($this->referralModel->find($id) ?: array_merge($existing, $updateData));
            
            return [
                'success' => true,
                'message' => 'Referral status updated to ' . $status,
                'data'    => $enriched,
                'record'  => $enriched,
                'action'  => 'update',
                'id'      => $id
            ];
        });
    }

    public function destroy(string|int $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_DELETE);

        $this->handle(function() use ($id) {
            $existing = $this->referralModel->find($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Referral not found', 'code' => 404];
            }
            
            $success = $this->referralModel->delete($id);
            
            return [
                'success' => $success,
                'message' => $success ? 'Referral deleted successfully' : 'Failed to delete referral',
                'action'  => 'delete',
                'id'      => $id
            ];
        });
    }

    private function enrichReferral(array $referral): array
    {
        // Get patient name
        if (isset($referral['patient_id'])) {
            try {
                $patient = $this->patientModel->find($referral['patient_id']);
                if ($patient) {
                    $referral['patient_name'] = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
                    $referral['patient_avatar'] = strtoupper(substr($patient['first_name'] ?? '', 0, 1) . substr($patient['last_name'] ?? '', 0, 1));
                }
            } catch (Exception $e) {
                error_log('Error fetching patient: ' . $e->getMessage());
            }
        }
        
        // Get from doctor
        if (isset($referral['from_doctor_id'])) {
            try {
                $doctor = $this->employeeModel->find($referral['from_doctor_id']);
                if ($doctor) {
                    $referral['from_doctor'] = $doctor['full_name'] ?? 'Unknown';
                }
            } catch (Exception $e) {
                error_log('Error fetching doctor: ' . $e->getMessage());
            }
        }
        
        // Get to doctor (specialist)
        if (isset($referral['to_doctor_id']) && $referral['to_doctor_id']) {
            try {
                $specialist = $this->employeeModel->find($referral['to_doctor_id']);
                if ($specialist) {
                    $referral['to_specialist'] = $specialist['full_name'] ?? 'Unknown';
                    $referral['specialty'] = $specialist['role_description'] ?? 'Specialist';
                }
            } catch (Exception $e) {
                error_log('Error fetching specialist: ' . $e->getMessage());
            }
        }
        
        // Map 'emergency' to 'critical' for frontend
        if (isset($referral['urgency']) && $referral['urgency'] === 'emergency') {
            $referral['urgency'] = 'critical';
        }
        
        // Format date
        if (isset($referral['created_at'])) {
            $referral['date'] = date('Y-m-d', strtotime($referral['created_at']));
            $referral['date_formatted'] = date('M d, Y', strtotime($referral['created_at']));
        }
        
        return $referral;
    }
}
?>