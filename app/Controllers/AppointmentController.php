<?php
// app/Controllers/AppointmentController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Appointment.php';
require_once __DIR__ . '/../Models/Patient.php';
require_once __DIR__ . '/../Models/Employee.php';

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
        $data = $this->input();

        $this->handle(function() use ($data) {
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

            if (empty($dbData['appointment_id'])) {
                $dbData['appointment_id'] = $this->appointmentModel->generateAppointmentId();
            }

            $result = $this->appointmentModel->create($dbData);

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

            return [
                'success' => true,
                'message' => 'Appointment created successfully',
                'data' => $result,
                'code' => 201
            ];
        });
    }

    public function update(string $id): void
    {
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

            return [
                'success' => true,
                'message' => 'Appointment updated successfully',
                'data' => $result
            ];
        });
    }

    public function updateStatus(string $id): void
    {
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
            $validStatuses = ['pending', 'approved', 'completed', 'cancelled', 'no_show'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid status value',
                    'code' => 400
                ];
            }

            $result = $this->appointmentModel->updateStatus($id, $status);

            return [
                'success' => true,
                'message' => 'Appointment status updated to ' . ucfirst($status),
                'data' => $result
            ];
        });
    }

    public function destroy(string $id): void
    {
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
                'message' => $success ? 'Appointment deleted successfully' : 'Failed to delete appointment'
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
            $validStatuses = ['pending', 'approved', 'completed', 'cancelled', 'no_show'];
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
            'status' => strtolower($a['status'] ?? 'pending'),
            'priority' => strtolower($a['priority'] ?? 'medium'),
            'notes' => $a['notes'] ?? '',
            'reminder_sent' => (bool)($a['reminder_sent'] ?? false),
            'created_at' => $a['created_at'] ?? '',
            'updated_at' => $a['updated_at'] ?? ''
        ];
    }
}
