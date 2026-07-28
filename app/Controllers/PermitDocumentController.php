<?php
// app/Controllers/PermitDocumentController.php

require_once __DIR__ . '/../../Core/Response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/PermitDocument.php';
require_once __DIR__ . '/../Models/Employee.php';

class PermitDocumentController
{
    private PermitDocument $documentModel;
    private Employee $employeeModel;
    
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 100;
    private const DEFAULT_PAGE = 1;
    private const STATUS_PENDING = 'pending';
    private const STATUS_VERIFIED = 'verified';
    private const STATUS_EXPIRED = 'expired';
    private const VALID_STATUSES = ['pending', 'verified', 'expired'];
    
    public function __construct(
        ?PermitDocument $documentModel = null,
        ?Employee $employeeModel = null
    ) {
        $this->documentModel = $documentModel ?? new PermitDocument(Database::getInstance());
        $this->employeeModel = $employeeModel ?? new Employee(Database::getInstance());
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
        $documents = $this->documentModel->all(['order' => 'uploaded_at.desc']);
        $formatted = $this->formatDocuments($documents);
        
        Response::success('Documents retrieved successfully', $formatted, 200, [
            'total' => count($formatted)
        ]);
    }
    
    public function paginated(): void
    {
        $page = max(1, (int)($this->getQueryParam('page', '1')));
        $limit = max(1, min(self::MAX_LIMIT, (int)($this->getQueryParam('limit', (string)self::DEFAULT_LIMIT))));
        $offset = ($page - 1) * $limit;
        
        $status = $this->getQueryParamOrNull('status');
        $type = $this->getQueryParamOrNull('type');
        $search = $this->getQueryParamOrNull('q');
        
        $documents = $this->documentModel->search([
            'status' => $status,
            'document_type' => $type,
            'search' => $search
        ], $limit, $offset);
        
        $formatted = $this->formatDocuments($documents);
        
        Response::success('Documents retrieved', $formatted, 200, [
            'page' => $page,
            'limit' => $limit,
            'total' => count($formatted)
        ]);
    }
    
    public function show(string $id): void
    {
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            Response::error('Document not found', 404);
        }
        
        Response::success('Document retrieved successfully', $this->formatDocument($document));
    }
    
    public function store(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            
            $errors = $this->validate($data, ['permit_id', 'document_type', 'file_name', 'file_path', 'uploaded_by']);
            
            if (!empty($errors)) {
                Response::error('Validation failed', 422, $errors);
            }
            
            $preparedData = $this->prepareDbData($data);
            
            if ($this->documentModel->exists((int)$preparedData['permit_id'], $preparedData['document_type'], $preparedData['file_name'])) {
                Response::error('A document with the same name, type, and permit already exists.', 409);
            }
            
            $result = $this->documentModel->create($preparedData);
            
            if (empty($result) || !isset($result['id'])) {
                Response::error('Failed to create document - no data returned', 500);
            }
            
            Response::success('Document uploaded successfully', $this->formatDocument($result), 201);
        } catch (\Throwable $e) {
            error_log('Store error: ' . $e->getMessage());
            Response::error('Failed to upload document: ' . $e->getMessage(), 500);
        }
    }
    
    public function update(string $id): void
    {
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            Response::error('Document not found', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        if (empty($data)) {
            Response::error('No data provided', 400);
        }
        
        $preparedData = $this->prepareDbData($data, true);
        $result = $this->documentModel->update($id, $preparedData);
        
        Response::success('Document updated successfully', $this->formatDocument($result));
    }
    
    public function verify(string $id): void
    {
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            Response::error('Document not found', 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        
        $updateData = [
            'verified' => true,
            'status' => self::STATUS_VERIFIED,
            'verified_by' => !empty($data['verified_by']) ? (int)$data['verified_by'] : null,
            'verified_at' => date('Y-m-d H:i:sP')
        ];
        
        if (empty($document['qr_code'])) {
            $updateData['qr_code'] = $this->documentModel->generateDocumentId();
        }
        
        $result = $this->documentModel->update($id, $updateData);
        
        Response::success('Document verified successfully', $this->formatDocument($result));
    }
    
    public function destroy(string $id): void
    {
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            Response::error('Document not found', 404);
        }
        
        $success = $this->documentModel->delete($id);
        
        if ($success) {
            Response::success('Document deleted successfully');
        } else {
            Response::error('Failed to delete document', 500);
        }
    }
    
    public function search(): void
    {
        $query = $this->getQueryParam('q', '');
        
        if (empty($query)) {
            Response::error('Search query is required', 400);
        }
        
        $documents = $this->documentModel->search(['search' => $query]);
        $formatted = $this->formatDocuments($documents);
        
        Response::success('Search completed', $formatted, 200, [
            'total' => count($formatted)
        ]);
    }
    
    public function stats(): void
    {
        $stats = $this->documentModel->getStats();
        Response::success('Statistics retrieved successfully', $stats);
    }
    
    public function getByPermit(string $permitId): void
    {
        $documents = $this->documentModel->findByPermitId($permitId);
        $formatted = $this->formatDocuments($documents);
        
        Response::success('Documents for permit retrieved', $formatted, 200, [
            'total' => count($formatted)
        ]);
    }
    
    public function getExpiringSoon(): void
    {
        $days = max(1, (int)($this->getQueryParam('days', '30')));
        $documents = $this->documentModel->getExpiringSoon($days);
        $formatted = $this->formatDocuments($documents);
        
        Response::success('Expiring documents retrieved', $formatted, 200, [
            'total' => count($formatted)
        ]);
    }
    
    private function formatDocuments(array $documents): array
    {
        if (empty($documents)) {
            return [];
        }
        
        $uploaderIds = []; 
        foreach ($documents as $doc) {
            if (!empty($doc['uploaded_by'])) {
                $uploaderIds[] = (int)$doc['uploaded_by'];
            }
        }
        
        $uniqueIds = array_unique($uploaderIds);
        $employees = [];
        
        if (!empty($uniqueIds) && method_exists($this->employeeModel, 'findMultiple')) {
            $employees = $this->employeeModel->findMultiple($uniqueIds);
        }
        
        $employeeLookup = [];
        foreach ($employees as $emp) {
            $employeeLookup[$emp['id']] = $emp['full_name'] ?? "Employee #{$emp['id']}";
        }
        
        return array_map(function ($doc) use ($employeeLookup) {
            return $this->formatSingleDocument($doc, $employeeLookup);
        }, $documents);
    }
    
    private function formatDocument(array $doc): array
    {
        $uploaderName = 'Unknown';
        if (!empty($doc['uploaded_by'])) {
            $employee = $this->employeeModel->find($doc['uploaded_by']);
            if ($employee) {
                $uploaderName = $employee['full_name'] ?? "Employee #{$doc['uploaded_by']}";
            }
        }
        
        return $this->formatSingleDocument($doc, [(int)$doc['uploaded_by'] => $uploaderName]);
    }
    
    private function formatSingleDocument(array $doc, array $employeeLookup): array
    {
        $uploadedById = (int)($doc['uploaded_by'] ?? 0);
        
        return [
            'id' => (int)($doc['id'] ?? 0),
            'document_id' => $doc['document_id'] ?? '',
            'permit_id' => (int)($doc['permit_id'] ?? 0),
            'applicant' => $doc['applicant'] ?? '',
            'document_type' => $doc['document_type'] ?? '',
            'file_name' => $doc['file_name'] ?? '',
            'file_path' => $doc['file_path'] ?? '',
            'file_size' => (int)($doc['file_size'] ?? 0),
            'file_type' => $doc['file_type'] ?? '',
            'mime_type' => $doc['mime_type'] ?? '',
            'uploaded_by' => [
                'id' => $uploadedById,
                'name' => $employeeLookup[$uploadedById] ?? 'Unknown'
            ],
            'uploaded_at' => $doc['uploaded_at'] ?? '',
            'verified' => (bool)($doc['verified'] ?? false),
            'verified_by' => (int)($doc['verified_by'] ?? 0),
            'verified_at' => $doc['verified_at'] ?? null,
            'status' => strtolower($doc['status'] ?? self::STATUS_PENDING),
            'expiry_date' => $doc['expiry_date'] ?? null,
            'qr_code' => $doc['qr_code'] ?? null,
            'notes' => $doc['notes'] ?? '',
            'created_at' => $doc['uploaded_at'] ?? '',
            'updated_at' => $doc['updated_at'] ?? ''
        ];
    }
    
    private function prepareDbData(array $data, bool $isUpdate = false): array
    {
        $dbData = [];
        
        $stringFields = ['document_id', 'applicant', 'document_type', 'file_name', 'file_path', 'file_type', 'mime_type', 'qr_code', 'notes'];
        foreach ($stringFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = $this->sanitizeString($data[$field]);
            } elseif (!$isUpdate && $field === 'document_type') {
                $dbData[$field] = 'other';
            }
        }
        
        $intFields = ['permit_id', 'uploaded_by', 'verified_by'];
        foreach ($intFields as $field) {
            if (isset($data[$field])) {
                $dbData[$field] = $this->sanitizeInteger($data[$field]);
            }
        }
        
        if (isset($data['verified'])) {
            $dbData['verified'] = $this->sanitizeBoolean($data['verified']);
        }
        
        if (isset($data['status'])) {
            $dbData['status'] = $this->sanitizeStatus($data['status']);
        }
        
        if (isset($data['expiry_date'])) {
            $dbData['expiry_date'] = $this->sanitizeDate($data['expiry_date']);
        }
        
        if (isset($data['verified_at'])) {
            $dbData['verified_at'] = $this->sanitizeDate($data['verified_at']);
        }
        
        if (isset($data['file_size'])) {
            $dbData['file_size'] = $this->sanitizeInteger($data['file_size']);
        }
        
        return $dbData;
    }
    
    private function sanitizeString(mixed $value): string
    {
        return trim(strip_tags((string)$value));
    }
    
    private function sanitizeInteger(mixed $value): int
    {
        return (int)$value;
    }
    
    private function sanitizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        return (bool)$value;
    }
    
    private function sanitizeStatus(mixed $status): string
    {
        $status = strtolower(trim((string)$status));
        return in_array($status, self::VALID_STATUSES) ? $status : self::STATUS_PENDING;
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
        
        if (isset($data['status']) && !in_array((string)$data['status'], self::VALID_STATUSES)) {
            $errors[] = "Invalid status. Must be one of: " . implode(', ', self::VALID_STATUSES);
        }
        
        if (isset($data['expiry_date']) && !empty($data['expiry_date'])) {
            if (!strtotime((string)$data['expiry_date'])) {
                $errors[] = "Invalid expiry_date format";
            }
        }
        
        return $errors;
    }
}