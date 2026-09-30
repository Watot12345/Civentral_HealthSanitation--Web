<?php
// app/Controllers/AppointmentController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Constants/Permissions.php';
require_once __DIR__ . '/../Models/Appointment.php';
require_once __DIR__ . '/../Models/Patient.php';
require_once __DIR__ . '/../Models/Employee.php';

use App\Constants\Permissions;

class AppointmentController extends BaseController
{
    private Appointment $appointmentModel;
    private Patient $patientModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->appointmentModel = new Appointment();
        $this->patientModel = new Patient();
        $this->employeeModel = new Employee();
    }

    public function index(): void
    {
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_VIEW);

        $rawAppointments = $this->appointmentModel->all(['order' => 'appointment_date.desc,appointment_time.asc,created_at.desc']);
        $patientsMap = $this->getPatientsMap();
        $employeesMap = $this->getEmployeesMap();

        $appointments = array_map(function ($a) use ($patientsMap, $employeesMap) {
            return $this->enrichAppointment($a, $patientsMap, $employeesMap);
        }, $rawAppointments);

        $this->handle(function() use ($appointments) {
            return [
                'success' => true,
                'data' => $appointments,
                'total' => count($appointments)
            ];
        });
    }

    public function show(string $id): void
    {
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_VIEW);

        $appointment = $this->appointmentModel->find($id);

        $this->handle(function() use ($appointment) {
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found',
                    'code' => 404
                ];
            }

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();

            return [
                'success' => true,
                'data' => $this->enrichAppointment($appointment, $patientsMap, $employeesMap)
            ];
        });
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_CREATE);

        $data = $this->input();

        $this->handle(function() use ($data) {
            // Check if appointment booking is enabled
            $onlineAptsEnabled = class_exists('Settings') ? (bool)Settings::get('modules.health_center.enable_online_appointments', true) : true;
            if (!$onlineAptsEnabled) {
                return [
                    'success' => false,
                    'message' => 'Online appointment scheduling is temporarily disabled by the administrator.',
                    'code' => 403
                ];
            }

            if (empty($data['patient_id'])) {
                return [
                    'success' => false,
                    'message' => 'Patient selection is required',
                    'code' => 400
                ];
            }

            if (empty($data['service_type'])) {
                return [
                    'success' => false,
                    'message' => 'Service type is required',
                    'code' => 400
                ];
            }

            $dbData = $this->prepareDbData($data);

            // Enforce max appointments per day quota
            $targetDate = $dbData['appointment_date'] ?? date('Y-m-d');
            $maxPerDay = class_exists('Settings') ? (int)Settings::get('modules.health_center.max_appointments_per_day', 50) : 50;
            if ($maxPerDay > 0) {
                $existingCount = $this->appointmentModel->countByDate($targetDate);
                if ($existingCount >= $maxPerDay) {
                    return [
                        'success' => false,
                        'message' => "Daily appointment quota ({$maxPerDay} appointments) has been reached for {$targetDate}. Please select another date.",
                        'code' => 422
                    ];
                }
            }

            if (empty($dbData['appointment_id'])) {
                $dbData['appointment_id'] = $this->appointmentModel->generateAppointmentId();
            }

            $result = $this->appointmentModel->create($dbData);
            if (is_array($result) && isset($result[0]) && is_array($result[0])) {
                $result = $result[0];
            }

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $aptCode = $dbData['appointment_id'] ?? ($result['appointment_id'] ?? 'APT');
                    $st = $dbData['service_type'] ?? 'Consultation';
                    $logger->log("Scheduled Clinic Appointment ({$aptCode})", [
                        'module'  => 'Health Center Services',
                        'details' => "Service: {$st} | Date: " . ($dbData['appointment_date'] ?? date('Y-m-d')),
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();
            $merged = array_merge($dbData, is_array($result) ? $result : []);
            if (!empty($result['id'])) {
                $merged['id'] = $result['id'];
            }
            $enriched = $this->enrichAppointment($merged, $patientsMap, $employeesMap);

            return [
                'success' => true,
                'message' => 'Appointment created successfully',
                'data'    => $enriched,
                'record'  => $enriched,
                'action'  => 'create',
                'id'      => $enriched['id'] ?? null,
                'code'    => 201
            ];
        });
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_EDIT);

        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $appointment = $this->appointmentModel->find($id);
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found',
                    'code' => 404
                ];
            }

            $dbData = $this->prepareDbData($data, true);
            $result = $this->appointmentModel->updateById($id, $dbData);
            if (is_array($result) && isset($result[0]) && is_array($result[0])) {
                $result = $result[0];
            }

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();
            $updated = $this->appointmentModel->find($id) ?: array_merge($appointment, $dbData, is_array($result) ? $result : []);
            $enriched = $this->enrichAppointment($updated, $patientsMap, $employeesMap);

            return [
                'success' => true,
                'message' => 'Appointment updated successfully',
                'data'    => $enriched,
                'record'  => $enriched,
                'action'  => 'update',
                'id'      => $id
            ];
        });
    }

    public function updateStatus(string $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_EDIT);

        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $appointment = $this->appointmentModel->find($id);
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found',
                    'code' => 404
                ];
            }

            $status = strtolower(trim($data['status'] ?? ''));
            $validStatuses = ['pending', 'approved', 'scheduled', 'completed', 'cancelled', 'no_show', 'reassignment_pending', 'sent_to_other_doctor', 'not_available_pending', 'not_available_reassigned'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid status value',
                    'code' => 400
                ];
            }

            $result = $this->appointmentModel->updateStatus($id, $status);

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();
            $updated = $this->appointmentModel->find($id);
            $enriched = $this->enrichAppointment($updated ?: array_merge($appointment, ['status' => $status]), $patientsMap, $employeesMap);

            return [
                'success' => true,
                'message' => 'Appointment status updated to ' . ucfirst(str_replace('_', ' ', $status)),
                'data'    => $enriched,
                'record'  => $enriched,
                'action'  => 'update',
                'id'      => $id
            ];
        });
    }

    private function findAppointmentRecord(string|int $id): ?array
    {
        $idStr = (string)$id;

        // 1. Check appointments model by numeric ID or string appointment_id
        if (is_numeric($id)) {
            $apt = $this->appointmentModel->find((int)$id);
            if ($apt) {
                $apt['source_type'] = 'appointment';
                return $apt;
            }
        }
        if (method_exists($this->appointmentModel, 'findByAppointmentId')) {
            $apt = $this->appointmentModel->findByAppointmentId($idStr);
            if ($apt) {
                $apt['source_type'] = 'appointment';
                return $apt;
            }
        }

        // 2. Check triage / assessment model
        $numericId = (int)preg_replace('/^TRG-/i', '', $idStr);
        if ($numericId > 0 && file_exists(__DIR__ . '/../Models/Triage.php')) {
            require_once __DIR__ . '/../Models/Triage.php';
            try {
                $triageModel = new Triage();
                $t = $triageModel->find($numericId) ?: (method_exists($triageModel, 'findByTriageId') ? $triageModel->findByTriageId($idStr) : null);
                if ($t) {
                    $t['source_type'] = 'triage';
                    return $t;
                }
            } catch (Throwable $e) {}
        }

        // 3. Check triage_queue model
        if ($numericId > 0 && file_exists(__DIR__ . '/../Models/TriageQueue.php')) {
            require_once __DIR__ . '/../Models/TriageQueue.php';
            try {
                $triageQueueModel = new TriageQueue();
                $t = $triageQueueModel->find($numericId) ?: (method_exists($triageQueueModel, 'findByQueueNumber') ? $triageQueueModel->findByQueueNumber($idStr) : null);
                if ($t) {
                    $t['source_type'] = 'triage_queue';
                    return $t;
                }
            } catch (Throwable $e) {}
        }

        return null;
    }

    public function requestReassignment(string $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');

        $data = $this->input();
        $reasonCode = trim($data['reason'] ?? $data['unavailability_reason'] ?? 'IN_EMERGENCY_CONSULT');
        $customNotes = trim($data['notes'] ?? $data['unavailability_notes'] ?? '');

        $reasonMap = [
            'IN_EMERGENCY_CONSULT'  => 'In Emergency / Priority Consult',
            'FIELD_DUTY_STATIONED'  => 'On Field / Satellite Duty',
            'DAILY_CAPACITY_REACHED' => 'Daily Patient Quota Reached',
            'OFF_DUTY_LEAVE'        => 'Off-Duty / Approved Leave',
            'SPECIALTY_MISMATCH'    => 'Referral Required to Specialist'
        ];
        $reasonLabel = $reasonMap[$reasonCode] ?? ($reasonCode ? str_replace('_', ' ', $reasonCode) : 'In Emergency / Priority Consult');

        $this->handle(function() use ($id, $reasonCode, $reasonLabel, $customNotes) {
            $appointment = $this->findAppointmentRecord($id);
            if (!$appointment) {
                return ['success' => false, 'message' => 'Appointment or Triage record not found', 'code' => 404];
            }

            $sourceType = $appointment['source_type'] ?? 'appointment';
            $realId = $appointment['id'] ?? $id;

            $cleanNotes = preg_replace('/\[(REASSIGNMENT_PENDING|SENT_TO_OTHER_DOCTOR|NOT_AVAILABLE:[^\]]*)\]\s*/', '', $appointment['notes'] ?? '');
            $noteHeader = "[REASSIGNMENT_PENDING: {$reasonLabel}]";
            $fullNotes = "{$noteHeader} Doctor marked Not Available: {$reasonLabel}." . ($customNotes ? " Details: {$customNotes}." : '') . ($cleanNotes ? " {$cleanNotes}" : '');

            if ($sourceType === 'triage') {
                require_once __DIR__ . '/../Models/Triage.php';
                $triageModel = new Triage();
                $triageModel->updateById($realId, [
                    'status' => 'reassignment_pending',
                    'notes'  => $fullNotes
                ]);
            } elseif ($sourceType === 'triage_queue') {
                require_once __DIR__ . '/../Models/TriageQueue.php';
                $triageQueueModel = new TriageQueue();
                $triageQueueModel->updateById($realId, [
                    'status'       => 'reassignment_pending',
                    'queue_status' => 'reassignment_pending',
                    'notes'        => $fullNotes
                ]);
            } else {
                try {
                    $this->appointmentModel->updateById($realId, [
                        'status' => 'reassignment_pending',
                        'notes'  => $fullNotes
                    ]);
                } catch (Throwable $e) {
                    $this->appointmentModel->updateById($realId, ['notes' => $fullNotes]);
                }
            }

            // Synchronize across all table records for this patient/record
            $patientId = $appointment['patient_id'] ?? null;
            if ($patientId) {
                try {
                    require_once __DIR__ . '/../Models/Triage.php';
                    $tModel = new Triage();
                    if ($sourceType === 'triage') {
                        $tModel->updateById($realId, ['status' => 'reassignment_pending', 'notes' => $fullNotes]);
                    } else {
                        $pTriages = $tModel->getByPatientId($patientId);
                        if (!empty($pTriages)) {
                            foreach ($pTriages as $pt) {
                                if (in_array(strtolower($pt['status'] ?? ''), ['pending', 'triaged', 'waiting', 'in_triage', 'reassignment_pending'])) {
                                    $tModel->updateById($pt['id'], ['status' => 'reassignment_pending', 'notes' => $fullNotes]);
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {}

                try {
                    require_once __DIR__ . '/../Models/TriageQueue.php';
                    $tqModel = new TriageQueue();
                    if ($sourceType === 'triage_queue') {
                        $tqModel->updateById($realId, ['status' => 'reassignment_pending', 'queue_status' => 'reassignment_pending', 'notes' => $fullNotes]);
                    } else {
                        $pQueues = $tqModel->getByPatientId($patientId);
                        if (!empty($pQueues)) {
                            foreach ($pQueues as $pq) {
                                if (in_array(strtolower($pq['status'] ?? ''), ['pending', 'triaged', 'waiting', 'in_queue', 'reassignment_pending'])) {
                                    $tqModel->updateById($pq['id'], ['status' => 'reassignment_pending', 'queue_status' => 'reassignment_pending', 'notes' => $fullNotes]);
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {}

                try {
                    $this->appointmentModel->updateById($realId, ['status' => 'reassignment_pending', 'notes' => $fullNotes]);
                } catch (Throwable $e) {}
            }

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();
            $updated = $this->findAppointmentRecord($id) ?: array_merge($appointment, ['status' => 'reassignment_pending', 'notes' => $fullNotes]);
            $enriched = $this->enrichAppointment($updated, $patientsMap, $employeesMap);
            $enriched['unavailability_reason'] = $reasonCode;
            $enriched['unavailability_reason_label'] = $reasonLabel;

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Doctor Not Available ({$reasonLabel}) - Re-assignment Requested (#{$realId})", [
                        'module'  => 'Health Center Services',
                        'details' => "Doctor marked Not Available ({$reasonLabel}) for patient {$enriched['patient_name']}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            return [
                'success' => true,
                'message' => "Doctor marked Not Available ({$reasonLabel}). Re-assignment notification sent to Nurse Intake Staff.",
                'data'    => $enriched,
                'record'  => $enriched
            ];
        });
    }

    public function completeReassignment(string $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');

        $data = $this->input();
        $newDoctorId = $data['new_doctor_id'] ?? null;
        $newDoctorName = $data['new_doctor_name'] ?? null;

        $this->handle(function() use ($id, $newDoctorId, $newDoctorName) {
            $appointment = $this->findAppointmentRecord($id);
            if (!$appointment) {
                return ['success' => false, 'message' => 'Appointment or Triage record not found', 'code' => 404];
            }

            $sourceType = $appointment['source_type'] ?? 'appointment';
            $realId = $appointment['id'] ?? $id;

            if ($sourceType === 'triage') {
                require_once __DIR__ . '/../Models/Triage.php';
                $triageModel = new Triage();
                $cleanNotes = preg_replace('/\[(REASSIGNMENT_PENDING:[^\]]+|REASSIGNMENT_PENDING|SENT_TO_OTHER_DOCTOR)\]\s*/', '', $appointment['notes'] ?? '');
                $notes = '[SENT_TO_OTHER_DOCTOR] Nurse re-assigned patient to ' . ($newDoctorName ?: "Doctor #{$newDoctorId}") . '. ' . $cleanNotes;
                $updateData = ['status' => 'sent_to_doctor', 'notes' => $notes];
                if ($newDoctorId) $updateData['doctor_id'] = (int)$newDoctorId;
                if ($newDoctorName) $updateData['doctor_assigned'] = $newDoctorName;
                $triageModel->updateById($realId, $updateData);
            } elseif ($sourceType === 'triage_queue') {
                require_once __DIR__ . '/../Models/TriageQueue.php';
                $triageQueueModel = new TriageQueue();
                $cleanNotes = preg_replace('/\[(REASSIGNMENT_PENDING:[^\]]+|REASSIGNMENT_PENDING|SENT_TO_OTHER_DOCTOR)\]\s*/', '', $appointment['notes'] ?? '');
                $notes = '[SENT_TO_OTHER_DOCTOR] Nurse re-assigned patient to ' . ($newDoctorName ?: "Doctor #{$newDoctorId}") . '. ' . $cleanNotes;
                $updateData = ['status' => 'sent_to_doctor', 'queue_status' => 'sent_to_doctor', 'notes' => $notes];
                if ($newDoctorId) $updateData['doctor_id'] = (int)$newDoctorId;
                if ($newDoctorName) $updateData['doctor_assigned'] = $newDoctorName;
                $triageQueueModel->updateById($realId, $updateData);
            } else {
                try {
                    $updateFields = [
                        'status' => 'sent_to_other_doctor',
                        'notes'  => ($appointment['notes'] ?? '') . ' | Doctor marked Not Available. Nurse re-assigned patient to ' . ($newDoctorName ?: "Doctor #{$newDoctorId}") . '.'
                    ];
                    if ($newDoctorId) {
                        $updateFields['employee_id'] = (int)$newDoctorId;
                        $updateFields['doctor_id'] = (int)$newDoctorId;
                    }
                    $this->appointmentModel->updateById($realId, $updateFields);
                } catch (Throwable $e) {
                    $cleanNotes = preg_replace('/\[(REASSIGNMENT_PENDING:[^\]]+|REASSIGNMENT_PENDING|SENT_TO_OTHER_DOCTOR)\]\s*/', '', $appointment['notes'] ?? '');
                    $notes = '[SENT_TO_OTHER_DOCTOR] Nurse re-assigned patient to ' . ($newDoctorName ?: "Doctor #{$newDoctorId}") . '. ' . $cleanNotes;
                    $updateFields = ['notes' => $notes, 'status' => 'sent_to_other_doctor'];
                    if ($newDoctorId) $updateFields['employee_id'] = (int)$newDoctorId;
                    $this->appointmentModel->updateById($realId, $updateFields);
                }
            }

            // Cross-table synchronization
            $patientId = $appointment['patient_id'] ?? null;
            if ($patientId) {
                try {
                    require_once __DIR__ . '/../Models/Triage.php';
                    $tModel = new Triage();
                    if ($sourceType === 'triage') {
                        $tModel->updateById($realId, ['status' => 'sent_to_doctor', 'doctor_assigned' => $newDoctorName, 'doctor_id' => $newDoctorId]);
                    } else {
                        $pTriages = $tModel->getByPatientId($patientId);
                        if (!empty($pTriages)) {
                            foreach ($pTriages as $pt) {
                                if (in_array(strtolower($pt['status'] ?? ''), ['reassignment_pending', 'pending', 'triaged', 'waiting', 'in_triage'])) {
                                    $tModel->updateById($pt['id'], ['status' => 'sent_to_doctor', 'doctor_assigned' => $newDoctorName, 'doctor_id' => $newDoctorId]);
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {}

                try {
                    require_once __DIR__ . '/../Models/TriageQueue.php';
                    $tqModel = new TriageQueue();
                    if ($sourceType === 'triage_queue') {
                        $tqModel->updateById($realId, ['status' => 'sent_to_doctor', 'queue_status' => 'sent_to_doctor', 'doctor_assigned' => $newDoctorName, 'doctor_id' => $newDoctorId]);
                    } else {
                        $pQueues = $tqModel->getByPatientId($patientId);
                        if (!empty($pQueues)) {
                            foreach ($pQueues as $pq) {
                                if (in_array(strtolower($pq['status'] ?? ''), ['reassignment_pending', 'pending', 'triaged', 'waiting', 'in_queue'])) {
                                    $tqModel->updateById($pq['id'], ['status' => 'sent_to_doctor', 'queue_status' => 'sent_to_doctor', 'doctor_assigned' => $newDoctorName, 'doctor_id' => $newDoctorId]);
                                }
                            }
                        }
                    }
                } catch (Throwable $e) {}

                try {
                    $this->appointmentModel->updateById($realId, ['status' => 'sent_to_other_doctor', 'employee_id' => $newDoctorId]);
                } catch (Throwable $e) {}
            }

            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();
            $updated = $this->findAppointmentRecord($id) ?: array_merge($appointment, ['status' => 'sent_to_other_doctor']);
            $enriched = $this->enrichAppointment($updated, $patientsMap, $employeesMap);

            return [
                'success' => true,
                'message' => 'Nurse successfully re-assigned patient to ' . ($newDoctorName ?: 'new doctor') . '. Status updated to Not Available - Sent to Other Doctor.',
                'data'    => $enriched,
                'record'  => $enriched
            ];
        });
    }

    public function destroy(string $id): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_DELETE);

        $this->handle(function() use ($id) {
            $appointment = $this->appointmentModel->find($id);
            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found',
                    'code' => 404
                ];
            }

            $success = $this->appointmentModel->deleteById($id);

            return [
                'success' => $success,
                'message' => $success ? 'Appointment deleted successfully' : 'Failed to delete appointment',
                'action'  => 'delete',
                'id'      => $id
            ];
        });
    }

    public function search(): void
    {
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_VIEW);

        $query = strtolower($_GET['q'] ?? '');

        $this->handle(function() use ($query) {
            if (empty($query)) {
                return [
                    'success' => false,
                    'message' => 'Search query is required',
                    'code' => 400
                ];
            }

            $rawAppointments = $this->appointmentModel->all();
            $patientsMap = $this->getPatientsMap();
            $employeesMap = $this->getEmployeesMap();

            $enriched = array_map(fn($a) => $this->enrichAppointment($a, $patientsMap, $employeesMap), $rawAppointments);

            $results = array_values(array_filter($enriched, function($a) use ($query) {
                return str_contains(strtolower($a['patient_name'] ?? ''), $query) ||
                       str_contains(strtolower($a['appointment_id'] ?? ''), $query) ||
                       str_contains(strtolower($a['service_type'] ?? ''), $query) ||
                       str_contains(strtolower($a['doctor_name'] ?? ''), $query) ||
                       str_contains(strtolower($a['notes'] ?? ''), $query);
            }));

            return [
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ];
        });
    }

    private function prepareDbData(array $data, bool $isUpdate = false): array
    {
        $dbData = [];

        if (isset($data['appointment_id'])) {
            $dbData['appointment_id'] = trim($data['appointment_id']);
        }

        if (isset($data['patient_id'])) {
            $dbData['patient_id'] = (int)$data['patient_id'];
        }

        if (isset($data['employee_id'])) {
            $dbData['employee_id'] = (int)$data['employee_id'];
        } elseif (!$isUpdate && empty($dbData['employee_id'])) {
            $dbData['employee_id'] = 1;
        }

        if (isset($data['service_type'])) {
            $dbData['service_type'] = trim($data['service_type']);
        } elseif (isset($data['type'])) {
            $dbData['service_type'] = trim($data['type']);
        }

        if (isset($data['appointment_date'])) {
            $dbData['appointment_date'] = $data['appointment_date'];
        } elseif (isset($data['date'])) {
            $dbData['appointment_date'] = $data['date'];
        } elseif (!$isUpdate) {
            $dbData['appointment_date'] = date('Y-m-d');
        }

        if (isset($data['appointment_time'])) {
            $dbData['appointment_time'] = $data['appointment_time'];
        } elseif (isset($data['time'])) {
            $dbData['appointment_time'] = $data['time'];
        } elseif (!$isUpdate) {
            $dbData['appointment_time'] = date('H:i:s');
        }

        if (isset($data['status'])) {
            $status = strtolower(trim($data['status']));
            $validStatuses = ['pending', 'approved', 'scheduled', 'completed', 'cancelled', 'no_show'];
            $dbData['status'] = in_array($status, $validStatuses) ? $status : 'pending';
        } elseif (!$isUpdate) {
            $dbData['status'] = 'pending';
        }

        if (isset($data['priority'])) {
            $priority = strtolower(trim($data['priority']));
            $validPriorities = ['critical', 'high', 'medium', 'low'];
            $dbData['priority'] = in_array($priority, $validPriorities) ? $priority : 'medium';
        } elseif (!$isUpdate) {
            $dbData['priority'] = 'medium';
        }

        if (isset($data['notes'])) {
            $dbData['notes'] = trim($data['notes']);
        }

        if (isset($data['reminder_sent'])) {
            $dbData['reminder_sent'] = (bool)$data['reminder_sent'];
        }

        $dbData['updated_at'] = date('Y-m-d H:i:sP');

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

    private function enrichAppointment(array $a, array $patientsMap, array $employeesMap): array
    {
        $patientId = $a['patient_id'] ?? null;
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

        $employeeId = $a['employee_id'] ?? null;
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

        $dateStr = $a['appointment_date'] ?? ($a['date'] ?? date('Y-m-d'));
        $timeStr = $a['appointment_time'] ?? ($a['time'] ?? '09:00:00');

        $status = strtolower($a['status'] ?? 'pending');
        $unavailabilityReasonLabel = $a['unavailability_reason_label'] ?? null;

        if (!empty($a['notes'])) {
            if (str_contains($a['notes'], '[REASSIGNMENT_PENDING')) {
                $status = 'reassignment_pending';
                if (preg_match('/\[REASSIGNMENT_PENDING:\s*([^\]]+)\]/', $a['notes'], $m)) {
                    $unavailabilityReasonLabel = trim($m[1]);
                }
            } elseif (str_contains($a['notes'], '[SENT_TO_OTHER_DOCTOR]')) {
                $status = 'sent_to_other_doctor';
            }
        }

        if (empty($unavailabilityReasonLabel) && !empty($a['unavailability_reason'])) {
            $reasonMap = [
                'IN_EMERGENCY_CONSULT'  => 'In Emergency / Priority Consult',
                'FIELD_DUTY_STATIONED'  => 'On Field / Satellite Duty',
                'DAILY_CAPACITY_REACHED' => 'Daily Patient Quota Reached',
                'OFF_DUTY_LEAVE'        => 'Off-Duty / Approved Leave',
                'SPECIALTY_MISMATCH'    => 'Referral Required to Specialist'
            ];
            $unavailabilityReasonLabel = $reasonMap[$a['unavailability_reason']] ?? str_replace('_', ' ', $a['unavailability_reason']);
        }

        return [
            'id' => (int)($a['id'] ?? 0),
            'appointment_id' => $a['appointment_id'] ?? '',
            'patient_id' => (int)($a['patient_id'] ?? 0),
            'patient_name' => $patientName,
            'patient_code' => $patientCode,
            'patient_avatar' => $avatar,
            'employee_id' => (int)($a['employee_id'] ?? 1),
            'doctor_name' => $doctorName,
            'service_type' => $a['service_type'] ?? ($a['type'] ?? 'General Checkup'),
            'type' => $a['service_type'] ?? ($a['type'] ?? 'General Checkup'),
            'appointment_date' => $dateStr,
            'date' => $dateStr,
            'appointment_time' => !empty($timeStr) ? substr($timeStr, 0, 8) : '09:00:00',
            'time' => !empty($timeStr) ? date('h:i A', strtotime($timeStr)) : '09:00 AM',
            'status' => $status,
            'priority' => strtolower($a['priority'] ?? 'medium'),
            'notes' => $a['notes'] ?? '',
            'unavailability_reason_label' => $unavailabilityReasonLabel ?: 'In Emergency / Priority Consult',
            'reminder_sent' => (bool)($a['reminder_sent'] ?? false),
            'created_at' => $a['created_at'] ?? '',
            'updated_at' => $a['updated_at'] ?? ''
        ];
    }
}
