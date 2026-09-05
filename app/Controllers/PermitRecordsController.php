<?php
// controllers/PermitRecordsController.php  (legacy records controller — renamed to avoid class-name collision)

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/PermitRecords.php';

class PermitRecordsController extends BaseController
{
    private PermitRecords $permitModel;
    
    public function __construct()
    {
        $this->permitModel = new PermitRecords();
    }

    /**
     * GET /api/permits
     * Get all permits (paginated)
     */
    public function index(): void
    {
        $this->handle(function () {
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'business_type' => $_GET['type'] ?? null,
                'search' => $_GET['search'] ?? null,
            ];
            
            // Remove null filters
            $filters = array_filter($filters);
            
            $result = $this->permitModel->getAll($filters, $page, $limit);
            
            return [
                'success' => true,
                'data' => $result['permits'],
                'page' => $result['page'],
                'total' => $result['total'],
                'total_pages' => $result['total_pages'],
                'limit' => $result['limit']
            ];
        });
    }

    /**
     * GET /api/permits/stats
     * Get permit statistics
     */
    public function stats(): void
    {
        $this->handle(function () {
            $stats = $this->permitModel->getStats();
            
            return [
                'success' => true,
                'data' => $stats
            ];
        });
    }

    /**
     * GET /api/permits/{id}
     * Get single permit with documents
     */
    public function show(int $id): void
    {
        $this->handle(function () use ($id) {
            $permit = $this->permitModel->getById($id);
            
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            return [
                'success' => true,
                'data' => $permit
            ];
        });
    }

    /**
     * POST /api/permits
     * Create new permit
     */
    public function store(): void
    {
        $this->handle(function () {
            $data = $this->input();
            
            // Validate required fields
            $required = ['applicant', 'business_type', 'address', 'owner_name', 'contact', 'fee'];
            $errors = [];
            
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = ucfirst($field) . ' is required';
                }
            }
            
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'data' => $errors,
                    'code' => 422
                ];
            }
            
            $permit = $this->permitModel->create($data);
            
            return [
                'success' => true,
                'message' => 'Permit created successfully',
                'data' => $permit[0] ?? $permit,
                'code' => 201
            ];
        });
    }

    /**
     * PUT/PATCH /api/permits/{id}
     * Update permit
     */
    public function update(int $id): void
    {
        $this->handle(function () use ($id) {
            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            $data = $this->input();
            $updated = $this->permitModel->update($id, $data);
            
            return [
                'success' => true,
                'message' => 'Permit updated successfully',
                'data' => $updated[0] ?? $updated
            ];
        });
    }

    /**
     * DELETE /api/permits/{id}
     * Delete permit
     */
    public function destroy(int $id): void
    {
        $this->handle(function () use ($id) {
            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            $this->permitModel->delete($id);
            
            return [
                'success' => true,
                'message' => 'Permit deleted successfully'
            ];
        });
    }

    /**
     * POST /api/permits/{id}/renew
     * Renew an expired permit
     */
    public function renew(int $id): void
    {
        $this->handle(function () use ($id) {
            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            if ($permit['status'] !== 'expired') {
                return [
                    'success' => false,
                    'message' => 'Only expired permits can be renewed',
                    'code' => 400
                ];
            }
            
            $data = $this->input();
            $renewed = $this->permitModel->renew($id, $data);
            
            return [
                'success' => true,
                'message' => 'Permit renewed successfully',
                'data' => $renewed[0] ?? $renewed
            ];
        });
    }

    /**
     * GET /api/permits/{id}/documents
     * Get documents for a permit
     */
    public function documents(int $id): void
    {
        $this->handle(function () use ($id) {
            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            $documents = $this->permitModel->getDocuments($id);
            
            return [
                'success' => true,
                'data' => $documents
            ];
        });
    }

    /**
     * POST /api/permits/{id}/documents
     * Upload document for a permit
     */
    public function uploadDocument(int $id): void
    {
        $this->handle(function () use ($id) {
            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            $data = $this->input();
            
            $document = $this->permitModel->addDocument($id, $data);
            
            return [
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document[0] ?? $document,
                'code' => 201
            ];
        });
    }
}
