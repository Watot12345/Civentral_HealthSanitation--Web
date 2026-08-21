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

            // Set defaults and timestamps
            $defaultUploader = $_SESSION['employee_id'] ?? $_SESSION['user_id'] ?? 1;
            $data['uploaded_by'] = !empty($data['uploaded_by']) ? (int)$data['uploaded_by'] : (int)$defaultUploader;
            if (!empty($data['verified']) && empty($data['verified_by'])) {
                $data['verified_by'] = (int)$defaultUploader;
                $data['verified_at'] = date('Y-m-d H:i:sP');
            }
            $data['document_id'] = $data['document_id'] ?? $this->generateDocumentId();
            $data['status'] = $data['status'] ?? 'pending';
            $data['verified'] = $data['verified'] ?? false;
            $data['uploaded_at'] = date('Y-m-d H:i:sP');
            $data['updated_at'] = date('Y-m-d H:i:sP');
            
            $this->db->insert($this->table, $data, true);
            
            // Always return a predictable structured array from the input data
            // The DB insert side effect is what matters; the return format must be consistent
            return [
                'id' => $data['id'] ?? 0,
                'document_id' => $data['document_id'] ?? '',
                'permit_id' => $data['permit_id'] ?? 0,
                'applicant' => $data['applicant'] ?? '',
                'document_type' => $data['document_type'] ?? '',
                'file_name' => $data['file_name'] ?? '',
                'file_path' => $data['file_path'] ?? '',
                'file_size' => $data['file_size'] ?? 0,
                'file_type' => $data['file_type'] ?? '',
                'mime_type' => $data['mime_type'] ?? '',
                'uploaded_by' => $data['uploaded_by'] ?? 0,
                'status' => $data['status'] ?? 'pending',
                'verified' => $data['verified'] ?? false,
                'qr_code' => $data['qr_code'] ?? null,
                'notes' => $data['notes'] ?? '',
                'expiry_date' => $data['expiry_date'] ?? null,
                'uploaded_at' => $data['uploaded_at'] ?? date('Y-m-d H:i:sP'),
                'updated_at' => $data['updated_at'] ?? date('Y-m-d H:i:sP')
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
}