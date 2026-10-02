<?php
// app/Models/PermitDocument.php

class PermitDocument
{
    private Database $db;
    private string $table = 'permit_documents';
    
    public function __construct(Database $db)
    {
        $this->db = $db;
    }
    
    public function all(array $options = []): array
    {
        $options['order'] = $options['order'] ?? 'uploaded_at.desc';
        
        try {
            return $this->db->select($this->table, [], $options);
        } catch (Throwable $e) {
            error_log('PermitDocument::all() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return $result[0] ?? null;
        } catch (Throwable $e) {
            error_log('PermitDocument::find() Error: ' . $e->getMessage());
            return null;
        }
    }
    
    public function findByPermitId(string|int $permitId): array
    {
        try {
            return $this->db->select(
                $this->table, 
                ['permit_id' => $permitId], 
                ['order' => 'uploaded_at.desc']
            );
        } catch (Throwable $e) {
            error_log('PermitDocument::findByPermitId() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    public function exists(int $permitId, string $documentType, string $fileName): bool
    {
        try {
            $result = $this->db->select($this->table, [
                'permit_id' => $permitId,
                'document_type' => $documentType,
                'file_name' => $fileName
            ]);
            return !empty($result);
        } catch (Throwable $e) {
            error_log('PermitDocument::exists() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function create(array $data): array
    {
        try {
            // Normalize document_type to match Postgres CHECK constraint
            if (!empty($data['document_type'])) {
                $docTypeMap = [
                    'sanitary permit' => 'sanitary_permit',
                    'business permit' => 'business_permit',
                    'fire safety' => 'fire_safety',
                    'zoning clearance' => 'zoning_clearance',
                    'environmental compliance' => 'environmental_compliance',
                    'building permit' => 'building_permit',
                    'tax clearance' => 'tax_clearance'
                ];
                $normalized = strtolower(trim(str_replace('_', ' ', $data['document_type'])));
                $data['document_type'] = $docTypeMap[$normalized] ?? (in_array($data['document_type'], ['sanitary_permit','business_permit','fire_safety','zoning_clearance','environmental_compliance','building_permit','tax_clearance','other']) ? $data['document_type'] : 'other');
            } else {
                $data['document_type'] = 'sanitary_permit';
            }

            // Set defaults and timestamps (ensure valid employee ID fallback, never 0)
            $defaultUploader = 1;
            if (!empty($_SESSION['employee_id']) && (int)$_SESSION['employee_id'] > 0) {
                $defaultUploader = (int)$_SESSION['employee_id'];
            } elseif (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
                $defaultUploader = (int)$_SESSION['user_id'];
            }
            $data['uploaded_by'] = (!empty($data['uploaded_by']) && (int)$data['uploaded_by'] > 0) ? (int)$data['uploaded_by'] : $defaultUploader;
            if (!empty($data['verified']) && empty($data['verified_by'])) {
                $data['verified_by'] = $defaultUploader;
                $data['verified_at'] = date('Y-m-d H:i:sP');
            }
            $data['document_id'] = $data['document_id'] ?? $this->generateDocumentId();
            $data['status'] = $data['status'] ?? 'pending';
            $data['verified'] = $data['verified'] ?? false;
            $data['uploaded_at'] = date('Y-m-d H:i:sP');
            $data['updated_at'] = date('Y-m-d H:i:sP');
            
            $inserted = $this->db->insert($this->table, $data, true);
            $row = is_array($inserted) && isset($inserted[0]) && is_array($inserted[0]) ? $inserted[0] : (is_array($inserted) ? $inserted : []);
            
            return [
                'id' => (int)($row['id'] ?? ($data['id'] ?? 0)),
                'document_id' => $row['document_id'] ?? ($data['document_id'] ?? ''),
                'permit_id' => (int)($row['permit_id'] ?? ($data['permit_id'] ?? 0)),
                'applicant' => $row['applicant'] ?? ($data['applicant'] ?? ''),
                'document_type' => $row['document_type'] ?? ($data['document_type'] ?? ''),
                'file_name' => $row['file_name'] ?? ($data['file_name'] ?? ''),
                'file_path' => $row['file_path'] ?? ($data['file_path'] ?? ''),
                'file_size' => (int)($row['file_size'] ?? ($data['file_size'] ?? 0)),
                'file_type' => $row['file_type'] ?? ($data['file_type'] ?? ''),
                'mime_type' => $row['mime_type'] ?? ($data['mime_type'] ?? ''),
                'uploaded_by' => $row['uploaded_by'] ?? ($data['uploaded_by'] ?? 0),
                'status' => $row['status'] ?? ($data['status'] ?? 'pending'),
                'verified' => (bool)($row['verified'] ?? ($data['verified'] ?? false)),
                'qr_code' => $row['qr_code'] ?? ($data['qr_code'] ?? null),
                'notes' => $row['notes'] ?? ($data['notes'] ?? ''),
                'expiry_date' => $row['expiry_date'] ?? ($data['expiry_date'] ?? null),
                'uploaded_at' => $row['uploaded_at'] ?? ($data['uploaded_at'] ?? date('Y-m-d H:i:sP')),
                'updated_at' => $row['updated_at'] ?? ($data['updated_at'] ?? date('Y-m-d H:i:sP'))
            ];
        } catch (Exception $e) {
            error_log('PermitDocument::create() Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function update(string|int $id, array $data): array
    {
        // Only model sets timestamps
        $data['updated_at'] = date('Y-m-d H:i:sP');
        return $this->db->update($this->table, $data, ['id' => $id], true);
    }
    
    public function delete(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('PermitDocument::delete() Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate a more reliable document ID
     * Format: DOC-250727-AB12EF
     */
    public function generateDocumentId(): string
    {
        $random = bin2hex(random_bytes(3));
        return sprintf(
            'DOC-%s-%s',
            date('ymd'),
            strtoupper(substr($random, 0, 6))
        );
    }
    
    /**
     * Search documents with filters
     */
    public function search(array $criteria = [], int $limit = 10, int $offset = 0): array
    {
        $filters = [];
        $options = [
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'uploaded_at.desc'
        ];
        
        // Build filters
        if (!empty($criteria['status'])) {
            $filters['status'] = $criteria['status'];
        }
        
        if (!empty($criteria['document_type'])) {
            $filters['document_type'] = $criteria['document_type'];
        }
        
        if (!empty($criteria['permit_id'])) {
            $filters['permit_id'] = $criteria['permit_id'];
        }

        if (!empty($criteria['expiry_date']) && is_array($criteria['expiry_date'])) {
            $filters['expiry_date'] = $criteria['expiry_date'];
        }
        
        try {
            return $this->db->select($this->table, $filters, $options);
        } catch (Throwable $e) {
            error_log('PermitDocument::search() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get documents expiring soon
     * Only returns pending documents expiring within X days
     */
    public function getExpiringSoon(int $days = 30): array
    {
        try {
            $cutoff = date('Y-m-d', strtotime("+{$days} days"));
            $today = date('Y-m-d');
            
            return $this->db->select($this->table, [
                'status' => 'pending',
                'expiry_date' => [
                    'gte' => $today,
                    'lte' => $cutoff
                ]
            ], ['order' => 'expiry_date.asc']);
        } catch (Throwable $e) {
            error_log('PermitDocument::getExpiringSoon() Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get comprehensive statistics
     */
    public function getStats(): array
    {
        try {
            $allDocuments = $this->all();
            
            $stats = [
                'total' => count($allDocuments),
                'verified' => 0,
                'pending' => 0,
                'expired' => 0,
                'has_qr' => 0,
                'expiring_soon' => 0,
                'total_size' => 0
            ];
            
            $today = time();
            $thirtyDays = 30 * 86400;
            
            foreach ($allDocuments as $doc) {
                $status = $doc['status'] ?? 'pending';
                
                // Count by status
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
                
                // Count QR codes
                if (!empty($doc['qr_code'])) {
                    $stats['has_qr']++;
                }
                
                // Sum file sizes
                $stats['total_size'] += (int)($doc['file_size'] ?? 0);
                
                // Count expiring soon (pending + expiring within 30 days)
                if ($status === 'pending' && !empty($doc['expiry_date'])) {
                    $expiry = strtotime($doc['expiry_date']);
                    $daysLeft = ($expiry - $today) / 86400;
                    if ($daysLeft > 0 && $daysLeft <= 30) {
                        $stats['expiring_soon']++;
                    }
                }
            }
            
            return $stats;
        } catch (Throwable $e) {
            error_log('PermitDocument::getStats() Error: ' . $e->getMessage());
            return [
                'total' => 0,
                'verified' => 0,
                'pending' => 0,
                'expired' => 0,
                'has_qr' => 0,
                'expiring_soon' => 0,
                'total_size' => 0
            ];
        }
    }

    /**
     * Helper to auto-generate verified Sanitation Permit document with unique QR Code
     */
    public function autoGenerateForPermit(array $permit, ?string $receiptNumber = null): ?array
    {
        try {
            $permitId = (int)($permit['id'] ?? 0);
            if ($permitId <= 0) {
                return null;
            }

            $permitCode = $permit['permit_id'] ?? ('SP-' . date('Y') . '-' . str_pad((string)$permitId, 3, '0', STR_PAD_LEFT));
            $applicantName = $permit['applicant'] ?? ($permit['business_name'] ?? 'Authorized Business Owner');
            $qrCode = 'QR-SAN-' . date('Y') . '-' . str_pad((string)$permitId, 4, '0', STR_PAD_LEFT);
            $fileName = 'Sanitation_Permit_' . $permitCode . '.pdf';

            // Check if existing document exists for this permit
            $existing = $this->db->select($this->table, [
                'permit_id' => $permitId,
                'document_type' => 'sanitary_permit'
            ]);

            if (!empty($existing)) {
                return $existing[0];
            }

            $validityDays = 365;
            $expiryDate = !empty($permit['expiry_date']) ? $permit['expiry_date'] : date('Y-m-d', strtotime("+{$validityDays} days"));

            return $this->create([
                'permit_id'     => $permitId,
                'applicant'     => $applicantName,
                'document_type' => 'sanitary_permit',
                'file_name'     => $fileName,
                'file_path'     => 'permits/' . $fileName,
                'file_size'     => 148500,
                'file_type'     => 'pdf',
                'mime_type'     => 'application/pdf',
                'status'        => 'verified',
                'verified'      => true,
                'qr_code'       => $qrCode,
                'expiry_date'   => $expiryDate,
                'notes'         => 'Official Sanitation Permit with QR Code generated' . ($receiptNumber ? ' (OR #' . $receiptNumber . ')' : '')
            ]);
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'duplicate key') || str_contains($e->getMessage(), '409')) {
                try {
                    $existing = $this->db->select($this->table, [
                        'permit_id' => $permitId,
                        'document_type' => 'sanitary_permit'
                    ]);
                    if (!empty($existing)) {
                        return $existing[0];
                    }
                } catch (Throwable $ignored) {}
            }
            error_log('PermitDocument::autoGenerateForPermit() Notice: ' . $e->getMessage());
            return null;
        }
    }
}