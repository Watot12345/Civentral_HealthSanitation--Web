<?php
// models/Permit.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../helpers/EncryptionHelper.php';

class Permit
{
    private Database $db;
    
    // Fields that should be masked for citizens
    private array $sensitiveFields = [
        'owner_name',
        'contact',
        'email',
        'payment_reference'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all permits with optional filters
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array
    {
        $options = [
            'order' => 'created_at.desc',
            'offset' => ($page - 1) * $limit,
            'limit' => $limit
        ];

        // Build filters for Supabase
        $apiFilters = [];
        if (!empty($filters['status'])) {
            $apiFilters['status'] = $filters['status'];
        }
        if (!empty($filters['business_type'])) {
            $apiFilters['business_type'] = $filters['business_type'];
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $options['or'] = "(applicant.ilike.*{$search}*,permit_id.ilike.*{$search}*,business_name.ilike.*{$search}*)";
        }

        // Get total count
        $total = $this->db->count('permits', $apiFilters);
        
        // Get paginated results
        $permits = $this->db->select('permits', $apiFilters, $options);
        $permits = EncryptionHelper::decryptRows('permits', $permits);

        return [
            'permits' => $permits,
            'total' => $total,
            'page' => $page,
            'total_pages' => ceil($total / $limit),
            'limit' => $limit
        ];
    }

    /**
     * Get single permit by ID with documents
     */
    public function getById(int $id): ?array
    {
        $filters = ['id' => $id];
        $permits = $this->db->select('permits', $filters);
        
        if (empty($permits)) {
            return null;
        }

        $permit = EncryptionHelper::decryptModel('permits', $permits[0]);

        // Get associated documents
        $docFilters = ['permit_id' => $id];
        $permit['documents'] = $this->db->select('permit_documents', $docFilters);
        
        // Get inspection history
        $inspectionFilters = ['permit_id' => $id];
        $permit['inspections'] = $this->db->select('inspections', $inspectionFilters, [
            'order' => 'scheduled_date.desc'
        ]);

        return $permit;
    }

    /**
     * Create new permit
     */
    public function create(array $data): array
    {
        // Generate permit ID
        $data['permit_id'] = $this->generatePermitId();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $encryptedData = EncryptionHelper::encryptModel('permits', $data);
        return $this->db->insert('permits', $encryptedData, true);
    }

    /**
     * Update permit
     */
    public function update(int $id, array $data): array
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $encryptedData = EncryptionHelper::encryptModel('permits', $data);
        return $this->db->update('permits', $encryptedData, ['id' => $id], true);
    }

    /**
     * Delete permit
     */
    public function delete(int $id): bool
    {
        $this->db->delete('permits', ['id' => $id], true);
        return true;
    }

    /**
     * Get permit statistics
     */
    public function getStats(): array
    {
        $stats = [
            'total' => $this->db->count('permits'),
            'active' => $this->db->count('permits', ['status' => 'active']),
            'pending' => $this->db->count('permits', ['status' => 'pending']),
            'under_review' => $this->db->count('permits', ['status' => 'under_review']),
            'expired' => $this->db->count('permits', ['status' => 'expired']),
            'rejected' => $this->db->count('permits', ['status' => 'rejected']),
        ];

        // Get total revenue
        $result = $this->db->select('permits', [], ['select' => 'fee']);
        $stats['total_revenue'] = array_sum(array_column($result, 'fee'));

        return $stats;
    }

    /**
     * Renew permit
     */
    public function renew(int $id, array $renewalData): array
    {
        $permit = $this->getById($id);
        if (!$permit) {
            throw new Exception('Permit not found');
        }

        $updateData = [
            'status' => 'active',
            'fee' => $renewalData['fee'] ?? $permit['fee'],
            'paid' => true,
            'payment_method' => $renewalData['payment_method'] ?? null,
            'approved_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
            'notes' => $renewalData['notes'] ?? $permit['notes'],
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->update('permits', $updateData, ['id' => $id], true);
    }

    /**
     * Apply data masking for citizen view
     */
    public function maskSensitiveData(array $data, string $role = 'citizen'): array
    {
        if ($role === 'admin' || $role === 'employee') {
            return $data;
        }

        // Mask single permit
        if (isset($data['id'])) {
            return $this->maskPermit($data);
        }

        // Mask array of permits
        return array_map([$this, 'maskPermit'], $data);
    }

    /**
     * Mask individual permit data
     */
    private function maskPermit(array $permit): array
    {
        foreach ($this->sensitiveFields as $field) {
            if (isset($permit[$field])) {
                $permit[$field] = $this->maskValue($permit[$field], $field);
            }
        }
        return $permit;
    }

    /**
     * Mask a value based on field type
     */
    private function maskValue(string $value, string $field): string
    {
        switch ($field) {
            case 'owner_name':
                // Show first letter and last letter, mask middle
                $len = strlen($value);
                if ($len <= 2) return $value;
                return $value[0] . str_repeat('*', $len - 2) . $value[$len - 1];
                
            case 'contact':
                // Mask middle digits of phone number
                if (strlen($value) >= 7) {
                    return substr($value, 0, 3) . '****' . substr($value, -3);
                }
                return '****';
                
            case 'email':
                // Mask email local part
                $parts = explode('@', $value);
                if (count($parts) === 2) {
                    $localPart = $parts[0];
                    $maskedLocal = strlen($localPart) > 2 
                        ? $localPart[0] . str_repeat('*', strlen($localPart) - 2) . substr($localPart, -1)
                        : '***';
                    return $maskedLocal . '@' . $parts[1];
                }
                return '****@****';
                
            case 'payment_reference':
                // Mask payment reference
                return 'REF-****' . substr($value, -4);
                
            default:
                return '****';
        }
    }

    /**
     * Generate unique permit ID
     */
    private function generatePermitId(): string
    {
        $year = date('Y');
        $count = $this->db->count('permits') + 1;
        return 'SP-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get documents for a permit
     */
    public function getDocuments(int $permitId): array
    {
        return $this->db->select('permit_documents', ['permit_id' => $permitId], [
            'order' => 'uploaded_at.desc'
        ]);
    }

    /**
     * Add document to permit
     */
    public function addDocument(int $permitId, array $documentData): array
    {
        $documentData['permit_id'] = $permitId;
        $documentData['uploaded_at'] = date('Y-m-d H:i:s');
        
        return $this->db->insert('permit_documents', $documentData, true);
    }
}