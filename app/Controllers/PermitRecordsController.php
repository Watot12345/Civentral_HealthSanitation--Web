<?php
// controllers/PermitRecordsController.php  (legacy records controller — renamed to avoid class-name collision)

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/PermitRecords.php';
require_once __DIR__ . '/../Constants/Permissions.php';

use App\Constants\Permissions;

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
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_VIEW);

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 10);
            
            $filters = [
                'status' => $_GET['status'] ?? null,
                'business_type' => $_GET['type'] ?? $_GET['business_type'] ?? null,
                'search' => $_GET['search'] ?? $_GET['q'] ?? null,
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
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_VIEW);

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
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_VIEW);

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
            $this->validateCsrf();
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_CREATE);

            $data = $this->input();
            unset($data['csrf_token']);
            
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
            $record = $permit[0] ?? $permit;
            
            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Created Permit Record", [
                        'module'  => 'Sanitation Permits',
                        'details' => "Permit ID: #{$record['id']} | Applicant: " . ($record['applicant'] ?? 'N/A'),
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {}
            }
            
            return [
                'success' => true,
                'action' => 'create',
                'record' => $record,
                'message' => 'Permit created successfully',
                'data' => $record,
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
            $this->validateCsrf();
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_APPROVE);

            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            $data = $this->input();
            unset($data['csrf_token']);
            $updated = $this->permitModel->update($id, $data);
            $record = $updated[0] ?? $updated;
            
            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Updated Permit Record", [
                        'module'  => 'Sanitation Permits',
                        'details' => "Permit ID: #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {}
            }
            
            return [
                'success' => true,
                'action' => 'update',
                'record' => $record,
                'message' => 'Permit updated successfully',
                'data' => $record
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
            $this->validateCsrf();
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_APPROVE);

            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            $this->permitModel->delete($id);
            
            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Deleted Permit Record", [
                        'module'  => 'Sanitation Permits',
                        'details' => "Permit ID: #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {}
            }
            
            return [
                'success' => true,
                'action' => 'delete',
                'id' => $id,
                'record' => ['id' => $id],
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
            $this->validateCsrf();
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_APPROVE);

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
            unset($data['csrf_token']);
            $renewed = $this->permitModel->renew($id, $data);
            $record = $renewed[0] ?? $renewed;
            
            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $logger->log("Renewed Permit Record", [
                        'module'  => 'Sanitation Permits',
                        'details' => "Permit ID: #{$id}",
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {}
            }
            
            return [
                'success' => true,
                'action' => 'update',
                'record' => $record,
                'message' => 'Permit renewed successfully',
                'data' => $record
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
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_VIEW);

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
            $this->validateCsrf();
            $this->requireDepartment('sanitation');
            $this->requireCapability(Permissions::PERMITS_CREATE);

            $permit = $this->permitModel->getById($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }
            
            $data = $this->input();
            unset($data['csrf_token']);
            
            $document = $this->permitModel->addDocument($id, $data);
            $record = $document[0] ?? $document;
            
            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $docId = $record['id'] ?? 'N/A';
                    $logger->log("Uploaded Permit Document", [
                        'module'  => 'Sanitation Permits',
                        'details' => "Permit ID: #{$id} | Document ID: #{$docId}",
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {}
            }
            
            return [
                'success' => true,
                'action' => 'create',
                'record' => $record,
                'message' => 'Document uploaded successfully',
                'data' => $record,
                'code' => 201
            ];
        });
    }
}
