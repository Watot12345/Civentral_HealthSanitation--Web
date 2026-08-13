<?php
// app/Controllers/GrowthController.php

require_once __DIR__ . '/../../Core/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/GrowthMeasurement.php';

class GrowthController
{
    private GrowthMeasurement $model;

    public function __construct(?GrowthMeasurement $model = null)
    {
        $this->model = $model ?? new GrowthMeasurement();
    }

    // -------------------------------------------------------
    // GET /api/growth.php?child_id=X  – list for one child
    // GET /api/growth.php             – list all (admin use)
    // -------------------------------------------------------
    public function index(): void
    {
        $childId = isset($_GET['child_id']) && is_numeric($_GET['child_id'])
            ? (int)$_GET['child_id']
            : null;

        if ($childId) {
            $data = $this->model->allForChild($childId);
        } else {
            // Return all measurements (used by growth_charts on initial load)
            try {
                $db   = Database::getInstance();
                $data = $db->select('growth_measurements', [], ['order' => 'measurement_date.asc']);
            } catch (Throwable $e) {
                Response::error('Failed to fetch growth data', 500);
            }
        }

        Response::success('Growth measurements retrieved', $data, 200, ['total' => count($data)]);
    }

    // -------------------------------------------------------
    // POST /api/growth.php
    // -------------------------------------------------------
    public function store(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $errors = $this->validate($data, ['child_id', 'weight', 'height']);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        $record = [
            'child_id'          => (int)$data['child_id'],
            'measurement_date'  => $this->sanitizeDate($data['date'] ?? date('Y-m-d')),
            'weight'            => (float)$data['weight'],
            'height'            => (float)$data['height'],
            'head_circumference'=> isset($data['head_circumference']) && $data['head_circumference'] !== ''
                                        ? (float)$data['head_circumference']
                                        : null,
            'notes'             => isset($data['notes']) ? trim(strip_tags($data['notes'])) : null,
        ];

        try {
            $result = $this->model->create($record);
            Response::success('Growth measurement added successfully', $result, 201);
        } catch (Throwable $e) {
            error_log('GrowthController::store error: ' . $e->getMessage());
            Response::error('Failed to save growth measurement: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // PUT /api/growth.php?id=X
    // -------------------------------------------------------
    public function update(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('Growth measurement not found', 404);
        }

        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($data)) {
            Response::error('No data provided', 400);
        }

        $record = [];
        if (isset($data['date']))               $record['measurement_date']   = $this->sanitizeDate($data['date']);
        if (isset($data['weight']))             $record['weight']             = (float)$data['weight'];
        if (isset($data['height']))             $record['height']             = (float)$data['height'];
        if (array_key_exists('head_circumference', $data)) {
            $record['head_circumference'] = $data['head_circumference'] !== '' ? (float)$data['head_circumference'] : null;
        }
        if (isset($data['notes']))              $record['notes']              = trim(strip_tags($data['notes']));

        $errors = $this->validateMeasurements($record);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        try {
            $result = $this->model->update($id, $record);
            Response::success('Growth measurement updated successfully', $result);
        } catch (Throwable $e) {
            error_log('GrowthController::update error: ' . $e->getMessage());
            Response::error('Failed to update growth measurement: ' . $e->getMessage(), 500);
        }
    }

    // -------------------------------------------------------
    // DELETE /api/growth.php?id=X
    // -------------------------------------------------------
    public function destroy(int $id): void
    {
        $existing = $this->model->find($id);
        if (!$existing) {
            Response::error('Growth measurement not found', 404);
        }

        if ($this->model->delete($id)) {
            Response::success('Growth measurement deleted successfully');
        } else {
            Response::error('Failed to delete growth measurement', 500);
        }
    }

    // -------------------------------------------------------
    // Validation helpers
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
            if ($w < 0.1 || $w > 999.99) {
                $errors[] = 'Weight must be between 0.1 and 999.99 kg';
            }
        }
        if (isset($data['height'])) {
            $h = (float)$data['height'];
            if ($h < 20 || $h > 999.99) {
                $errors[] = 'Height must be between 20 and 999.99 cm';
            }
        }
        if (isset($data['head_circumference']) && $data['head_circumference'] !== null && $data['head_circumference'] !== '') {
            $hc = (float)$data['head_circumference'];
            if ($hc < 1 || $hc > 100) {
                $errors[] = 'Head circumference must be between 1 and 100 cm';
            }
        }
        return $errors;
    }

    private function sanitizeDate(mixed $value): string
    {
        $ts = strtotime((string)$value);
        return $ts ? date('Y-m-d', $ts) : date('Y-m-d');
    }
}
