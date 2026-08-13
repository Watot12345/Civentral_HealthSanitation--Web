<?php
// app/Controllers/NutritionController.php

require_once __DIR__ . '/../../Core/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/NutritionAssessment.php';

class NutritionController
{
    private NutritionAssessment $model;

    private const VALID_STATUSES   = ['normal', 'moderate', 'critical'];
    private const VALID_RISK       = ['low', 'medium', 'high'];
    private const VALID_REC_STATUS = ['active', 'completed', 'critical'];

    public function __construct(?NutritionAssessment $model = null)
    {
        $this->model = $model ?? new NutritionAssessment();
    }

    // -------------------------------------------------------
    // GET /api/nutrition.php
    // -------------------------------------------------------
    public function index(): void
    {
        $filters = [];
        if (!empty($_GET['child_id']) && is_numeric($_GET['child_id'])) {
            $filters['child_id'] = (int)$_GET['child_id'];
        }
        if (!empty($_GET['nutrition_status']) && in_array($_GET['nutrition_status'], self::VALID_STATUSES)) {
            $filters['nutrition_status'] = $_GET['nutrition_status'];
        }
        if (!empty($_GET['risk_level']) && in_array($_GET['risk_level'], self::VALID_RISK)) {
            $filters['risk_level'] = $_GET['risk_level'];
        }

        $data = $this->model->all($filters);
        Response::success('Nutrition assessments retrieved', $data, 200, ['total' => count($data)]);
    }

    // -------------------------------------------------------
    // GET /api/nutrition.php?id=X  – single record
    // -------------------------------------------------------
    public function show(int $id): void
    {
        $record = $this->model->find($id);
        if (!$record) {
            Response::error('Nutrition assessment not found', 404);
        }
        Response::success('Nutrition assessment retrieved', $record);
    }

    // -------------------------------------------------------
    // POST /api/nutrition.php
    // -------------------------------------------------------
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $errors = $this->validate($data, ['child_id', 'weight', 'height']);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $record = $this->prepareData($data);

        try {
            $result = $this->model->create($record);

            // Sync children.nutrition_status for KPI accuracy
            $inserted = is_array($result) && !empty($result[0]) ? $result[0] : $result;
            if (!empty($inserted['child_id'])) {
                $this->model->syncChildNutritionStatus(
                    (int)$inserted['child_id'],
                    $record['nutrition_status']
                );
            }

            Response::success('Nutrition assessment saved successfully', $result, 201);
        } catch (Throwable $e) {
            error_log('NutritionController::store error: ' . $e->getMessage());
            Response::error('Failed to save assessment: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // PATCH /api/nutrition.php?id=X
    // PATCH /api/nutrition.php?id=X&action=emergency
    // -------------------------------------------------------
    public function update(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('Nutrition assessment not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($data)) {
            Response::error('No data provided', 400);
        }

        $action = $_GET['action'] ?? '';
        if ($action === 'emergency') {
            // Only update plan_of_action and set status to critical
            $record = [
                'plan_of_action'    => trim(strip_tags($data['plan_of_action'] ?? '')),
                'nutrition_status'  => 'critical',
                'risk_level'        => 'high',
                'status'            => 'critical',
            ];
        } else {
            $errors = $this->validateMeasurements($data);
            if (!empty($errors)) {
                Response::error('Validation failed', 422, $errors);
            }
            $record = $this->prepareData($data, true);
        }

        try {
            $result = $this->model->update($id, $record);

            // Sync children.nutrition_status if status changed
            if (!empty($record['nutrition_status'])) {
                $this->model->syncChildNutritionStatus(
                    (int)$existing['child_id'],
                    $record['nutrition_status']
                );
            }

            Response::success('Nutrition assessment updated successfully', $result);
        } catch (Throwable $e) {
            error_log('NutritionController::update error: ' . $e->getMessage());
            Response::error('Failed to update assessment: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // DELETE /api/nutrition.php?id=X
    // -------------------------------------------------------
    public function destroy(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('Nutrition assessment not found', 404);
        }

        if ($this->model->delete($id)) {
            Response::success('Nutrition assessment deleted successfully');
        } else {
            Response::error('Failed to delete assessment', 500);
        }
    }

    // -------------------------------------------------------
    // Validation / data helpers
    // -------------------------------------------------------
    private function validate(array $data, array $required): array
    {
        $errors = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || (string)$data[$field] === '') {
                $errors[] = "{$field} is required";
            }
        }
        return array_merge($errors, $this->validateMeasurements($data));
    }

    private function validateMeasurements(array $data): array
    {
        $errors = [];
        if (isset($data['weight'])) {
            $w = (float)$data['weight'];
            if ($w < 0.1 || $w > 999) $errors[] = 'Weight must be between 0.1 and 999 kg';
        }
        if (isset($data['height'])) {
            $h = (float)$data['height'];
            if ($h < 20 || $h > 999) $errors[] = 'Height must be between 20 and 999 cm';
        }
        if (isset($data['nutrition_status']) && !in_array($data['nutrition_status'], self::VALID_STATUSES)) {
            $errors[] = 'Invalid nutrition_status';
        }
        if (isset($data['risk_level']) && !in_array($data['risk_level'], self::VALID_RISK)) {
            $errors[] = 'Invalid risk_level';
        }
        return $errors;
    }

    private function prepareData(array $data, bool $isUpdate = false): array
    {
        $record = [];
        $stringFields = ['assessment_notes', 'plan_of_action', 'assessed_by'];

        if (!$isUpdate && isset($data['child_id'])) {
            $record['child_id'] = (int)$data['child_id'];
        }
        if (isset($data['date']))              $record['assessment_date']      = $this->sanitizeDate($data['date']);
        if (isset($data['weight']))            $record['weight']               = (float)$data['weight'];
        if (isset($data['height']))            $record['height']               = (float)$data['height'];
        if (isset($data['nutrition_status']))  $record['nutrition_status']     = $data['nutrition_status'];
        if (isset($data['risk_level']))        $record['risk_level']           = $data['risk_level'];
        if (isset($data['next_assessment']))   $record['next_assessment_date'] = $this->sanitizeDate($data['next_assessment']);
        if (isset($data['status']) && in_array($data['status'], self::VALID_REC_STATUS)) {
            $record['status'] = $data['status'];
        }

        foreach ($stringFields as $f) {
            if (isset($data[$f])) $record[$f] = trim(strip_tags((string)$data[$f]));
        }

        // supplements – store as JSON string
        if (isset($data['supplements'])) {
            $supp = is_array($data['supplements']) ? $data['supplements'] : json_decode($data['supplements'], true);
            $record['supplements'] = json_encode($supp ?? []);
        }

        // Set default status for new records
        if (!$isUpdate && !isset($record['status'])) {
            $record['status'] = $record['nutrition_status'] === 'critical' ? 'critical' : 'active';
        }

        return $record;
    }

    private function sanitizeDate(mixed $value): string
    {
        $ts = strtotime((string)$value);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
