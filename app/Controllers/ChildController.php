<?php
// app/Controllers/ChildController.php

require_once __DIR__ . '/../../Core/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/Child.php';

class ChildController
{
    private Child $childModel;

    private const DEFAULT_LIMIT = 5;
    private const MAX_LIMIT = 100;
    private const DEFAULT_PAGE = 1;
    private const VALID_GENDERS = ['Male', 'Female'];
    private const VALID_STATUSES = ['active', 'inactive'];
    private const VALID_NUTRITION_STATUSES = ['Normal', 'Moderate', 'Critical', 'Overweight'];

    public function __construct(?Child $childModel = null)
    {
        $this->childModel = $childModel ?? new Child();
    }

    private function getQueryParam(string $key, mixed $default = ''): string
    {
        $value = $_GET[$key] ?? $default;
        if (is_array($value)) {
            return $default;
        }
        return (string)$value;
    }

    private function getQueryParamOrNull(string $key): ?string
    {
        $value = $_GET[$key] ?? null;
        if ($value === null || $value === '' || is_array($value)) {
            return null;
        }
        return (string)$value;
    }

    public function index(): void
    {
        $children = $this->childModel->all();
        Response::success('Children retrieved successfully', $children, 200, [
            'total' => count($children)
        ]);
    }

    public function paginated(): void
    {
        $page = max(1, (int)($this->getQueryParam('page', '1')));
        $limit = max(1, min(self::MAX_LIMIT, (int)($this->getQueryParam('limit', (string)self::DEFAULT_LIMIT))));
        $offset = ($page - 1) * $limit;

        $status = $this->getQueryParamOrNull('status');
        $gender = $this->getQueryParamOrNull('gender');
        $nutritionStatus = $this->getQueryParamOrNull('nutrition_status');
        $barangay = $this->getQueryParamOrNull('barangay');
        $search = $this->getQueryParamOrNull('q');

        $children = $this->childModel->search([
            'status' => $status,
            'gender' => $gender,
            'nutrition_status' => $nutritionStatus,
            'barangay' => $barangay,
            'search' => $search
        ], $limit, $offset);

        $total = $this->childModel->count([
            'status' => $status,
            'gender' => $gender,
            'nutrition_status' => $nutritionStatus,
            'barangay' => $barangay
        ]);
        $totalPages = max(1, ceil($total / $limit));

        Response::success('Children retrieved', $children, 200, [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
            'offset' => $offset
        ]);
    }

    public function show(string $id): void
    {
        $child = $this->childModel->find($id);

        if (!$child) {
            Response::error('Child not found', 404);
        }

        Response::success('Child retrieved successfully', $child);
    }

    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

            $errors = $this->validate($data, [
                'first_name', 'last_name', 'gender', 'birth_date', 'address', 'barangay', 'mother_name', 'health_center'
            ]);

            if (!empty($errors)) {
                Response::error('Validation failed', 422, $errors);
            }

            $preparedData = $this->prepareDbData($data);
            $result = $this->childModel->create($preparedData);

            // Database::insert() returns an array of records, not a single record
            $insertedRecord = is_array($result) && !empty($result) && is_array($result[0]) ? $result[0] : $result;
            
            if (empty($insertedRecord) || !isset($insertedRecord['id'])) {
                Response::error('Failed to register child', 500);
            }

            Response::success('Child registered successfully', $result, 201);
        } catch (\Throwable $e) {
            error_log('Child store error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            Response::error('Failed to register child: ' . $e->getMessage(), 500);
        }
    }

    public function update(string $id): void
    {
        $child = $this->childModel->find($id);

        if (!$child) {
            Response::error('Child not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        if (empty($data)) {
            Response::error('No data provided', 400);
        }

        $preparedData = $this->prepareDbData($data, true);
        $result = $this->childModel->update($id, $preparedData);

        Response::success('Child updated successfully', $result);
    }

    public function destroy(string $id): void
    {
        $child = $this->childModel->find($id);

        if (!$child) {
            Response::error('Child not found', 404);
        }

        $success = $this->childModel->delete($id);

        if ($success) {
            Response::success('Child deleted successfully');
        } else {
            Response::error('Failed to delete child', 500);
        }
    }

    public function stats(): void
    {
        $stats = $this->childModel->getStats();
        Response::success('Statistics retrieved successfully', $stats);
    }

    private function prepareDbData(array $data, bool $isUpdate = false): array
    {
        $dbData = [];

        $stringFields = [
            'child_id', 'first_name', 'last_name', 'middle_name', 'gender', 'blood_type',
            'address', 'barangay', 'mother_name', 'mother_contact', 'mother_occupation',
            'father_name', 'father_contact', 'father_occupation', 'family_history',
            'allergies', 'health_center', 'status', 'nutrition_status'
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = $this->sanitizeString($data[$field]);
            }
        }

        $dateFields = ['birth_date', 'registration_date', 'last_visit'];
        foreach ($dateFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = $this->sanitizeDate($data[$field]);
            }
        }

        $floatFields = ['birth_weight', 'birth_height'];
        foreach ($floatFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = (float)$data[$field];
            }
        }

        if (isset($data['vaccine_compliance'])) {
            $dbData['vaccine_compliance'] = (int)$data['vaccine_compliance'];
        }

        return $dbData;
    }

    private function sanitizeString(mixed $value): string
    {
        return trim(strip_tags((string)$value));
    }

    private function sanitizeDate(mixed $value): ?string
    {
        if (empty($value)) return null;
        $timestamp = strtotime((string)$value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function validate(array $data, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (empty($data[$field]) && $data[$field] !== '0')) {
                $errors[] = "{$field} is required";
            }
        }

        if (isset($data['gender']) && !in_array((string)$data['gender'], self::VALID_GENDERS)) {
            $errors[] = "Invalid gender. Must be: " . implode(', ', self::VALID_GENDERS);
        }

        if (isset($data['status']) && !in_array((string)$data['status'], self::VALID_STATUSES)) {
            $errors[] = "Invalid status. Must be: " . implode(', ', self::VALID_STATUSES);
        }

        if (isset($data['nutrition_status']) && !in_array((string)$data['nutrition_status'], self::VALID_NUTRITION_STATUSES)) {
            $errors[] = "Invalid nutrition status. Must be: " . implode(', ', self::VALID_NUTRITION_STATUSES);
        }

        if (isset($data['vaccine_compliance'])) {
            $compliance = (int)$data['vaccine_compliance'];
            if ($compliance < 0 || $compliance > 100) {
                $errors[] = "Vaccine compliance must be between 0 and 100";
            }
        }

        return $errors;
    }
}