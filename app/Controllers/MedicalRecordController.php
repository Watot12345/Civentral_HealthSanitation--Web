<?php
// app/Controllers/MedicalRecordController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/MedicalRecord.php';
require_once __DIR__ . '/../Models/Patient.php';
require_once __DIR__ . '/../Models/Employee.php';
require_once __DIR__ . '/../Constants/Permissions.php';

use App\Constants\Permissions;

class MedicalRecordController extends BaseController
{
    private MedicalRecord $recordModel;
    private Patient $patientModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->recordModel = new MedicalRecord();
        $this->patientModel = new Patient();
        $this->employeeModel = new Employee();
    }

    public function index(): void
    {
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_VIEW);

        $this->handle(function() {
            $records = $this->recordModel->all(['order' => 'date.desc,created_at.desc']);
            return [
                'success' => true,
                'data' => array_map([$this, 'mapToFrontend'], $records),
                'total' => count($records)
            ];
        });
    }

    public function show(string|int $id): void
    {
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_VIEW);

        $this->handle(function() use ($id) {
            $record = $this->recordModel->find($id);
            if (!$record) {
                return ['success' => false, 'message' => 'Medical record not found', 'code' => 404];
            }

            return ['success' => true, 'data' => $this->mapToFrontend($record)];
        });
    }

    public function store(): void
    {
        $this->validateCsrf();
        $this->requireDepartment('health center services');
        $this->requireCapability(Permissions::PATIENTS_CREATE);

        $data = $this->input();

        $this->handle(function() use ($data) {
            $dbData = $this->mapToDb($data);
            $error = $this->validateDbData($dbData);
            if ($error) {
                return ['success' => false, 'message' => $error, 'code' => 400];
            }

            $result = $this->recordModel->create($dbData);
            $record = !empty($result[0]) ? $this->mapToFrontend($result[0]) : $result;

            return [
                'success' => true,
                'message' => 'Medical record created successfully',
                'data'    => $record,
                'record'  => $record,
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
            $existing = $this->recordModel->find($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Medical record not found', 'code' => 404];
            }

            $dbData = $this->mapToDb($data, $existing);
            $error = $this->validateDbData($dbData);
            if ($error) {
                return ['success' => false, 'message' => $error, 'code' => 400];
            }

            $result = $this->recordModel->updateById($id, $dbData);
            $record = !empty($result[0]) ? $this->mapToFrontend($result[0]) : $result;

            return [
                'success' => true,
                'message' => 'Medical record updated successfully',
                'data'    => $record,
                'record'  => $record,
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
            if (!$this->recordModel->find($id)) {
                return ['success' => false, 'message' => 'Medical record not found', 'code' => 404];
            }

            $this->recordModel->deleteById($id);
            return [
                'success' => true,
                'message' => 'Medical record deleted successfully',
                'action'  => 'delete',
                'id'      => $id
            ];
        });
    }

    private function validateDbData(array $data): ?string
    {
        if (empty($data['patient_id'])) return 'Patient is required';
        if (empty($data['record_type'])) return 'Record type is required';
        if (empty($data['date'])) return 'Date is required';
        if (empty($data['description'])) return 'Description is required';
        if (empty($data['created_by'])) return 'Recorded by is required';
        return null;
    }

    private function mapToDb(array $data, ?array $existing = null): array
    {
        $existingMeta = $this->decodeJson($existing['attachments'] ?? null);
        $fileNames = $data['attachments'] ?? ($existingMeta['files'] ?? []);
        if (is_string($fileNames)) {
            $fileNames = array_values(array_filter(array_map('trim', explode(',', $fileNames))));
        }

        $sharedWith = $data['shared_with'] ?? ($existingMeta['shared_with'] ?? []);
        if (is_string($sharedWith)) {
            $sharedWith = array_values(array_filter(array_map('trim', explode(',', $sharedWith))));
        }

        $meta = [
            'title' => trim($data['title'] ?? ($existingMeta['title'] ?? '')),
            'doctor' => trim($data['doctor'] ?? ($existingMeta['doctor'] ?? '')),
            'status' => $data['status'] ?? ($existingMeta['status'] ?? 'completed'),
            'shared_with' => $sharedWith,
            'files' => $fileNames
        ];

        return [
            'patient_id' => (int)($data['patient_id'] ?? ($existing['patient_id'] ?? 0)),
            'record_type' => $data['record_type'] ?? ($existing['record_type'] ?? ''),
            'date' => $data['date'] ?? ($existing['date'] ?? date('Y-m-d')),
            'description' => trim($data['description'] ?? ($existing['description'] ?? '')),
            'attachments' => $meta,
            'created_by' => (int)($data['created_by'] ?? ($existing['created_by'] ?? 1))
        ];
    }

    private function mapToFrontend(array $record): array
    {
        $patient = $this->patientModel->find((string)$record['patient_id']);
        $meta = $this->decodeJson($record['attachments'] ?? null);

        $patientName = 'Patient #' . ($record['patient_id'] ?? '');
        $patientCode = '';
        if ($patient) {
            $patientName = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
            $patientCode = $patient['patient_id'] ?? '';
        }

        $doctor = $meta['doctor'] ?? '';
        if (!$doctor && !empty($record['created_by'])) {
            $doctor = $this->employeeModel->getFullName($record['created_by']);
        }

        return [
            'id' => $record['id'],
            'patient_id' => $record['patient_id'],
            'patient_code' => $patientCode,
            'patient_name' => $patientName,
            'patient_avatar' => $this->initials($patientName),
            'record_type' => $record['record_type'],
            'title' => $meta['title'] ?: ucfirst($record['record_type']) . ' Record',
            'description' => $record['description'],
            'date' => $record['date'],
            'doctor' => $doctor,
            'status' => $meta['status'] ?? 'completed',
            'shared_with' => $meta['shared_with'] ?? [],
            'attachments' => $meta['files'] ?? [],
            'created_by' => $record['created_by'] ?? null,
            'created_at' => $record['created_at'] ?? null
        ];
    }

    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (!$value) return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : ['files' => []];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $letters = '';
        foreach ($parts as $part) {
            if ($part !== '') $letters .= strtoupper($part[0]);
        }
        return substr($letters ?: 'P', 0, 2);
    }
}
