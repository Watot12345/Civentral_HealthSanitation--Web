 <?php
// app/Models/Child.php

require_once __DIR__ . '/../../config/database.php';

class Child
{
    private Database $db;
    private string $table = 'children';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function all(array $options = []): array
    {
        $this->syncUnder5Patients();
        if (empty($options['order'])) {
            $options['order'] = 'created_at.desc';
        }
        try {
            return $this->db->select($this->table, [], $options);
        } catch (Throwable $e) {
            error_log('Child Model Error (all): ' . $e->getMessage());
            return [];
        }
    }

    public function find(string|int $id): ?array
    {
        try {
            $result = $this->db->select($this->table, ['id' => $id]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Child Model Error (find): ' . $e->getMessage());
            return null;
        }
    }

    public function findByChildId(string $childId): ?array
    {
        try {
            $result = $this->db->select($this->table, ['child_id' => $childId]);
            return !empty($result) ? $result[0] : null;
        } catch (Throwable $e) {
            error_log('Child Model Error (findByChildId): ' . $e->getMessage());
            return null;
        }
    }

    public function create(array $data): array
    {
        if (empty($data['child_id'])) {
            $data['child_id'] = $this->generateChildId();
        }
        if (empty($data['status'])) {
            $data['status'] = 'active';
        }
        if (empty($data['registration_date'])) {
            $data['registration_date'] = date('Y-m-d');
        }
        if (empty($data['vaccine_compliance'])) {
            $data['vaccine_compliance'] = 0;
        }
        return $this->db->insert($this->table, $data);
    }

    public function update(string|int $id, array $data): array
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    public function delete(string|int $id): bool
    {
        try {
            $this->db->delete($this->table, ['id' => $id]);
            return true;
        } catch (Throwable $e) {
            error_log('Child Model Error (delete): ' . $e->getMessage());
            return false;
        }
    }

    public function search(array $criteria = [], int $limit = 10, int $offset = 0): array
    {
        $this->syncUnder5Patients();
        $filters = [];
        $options = [
            'limit' => $limit,
            'offset' => $offset,
            'order' => 'created_at.desc'
        ];

        if (!empty($criteria['status'])) {
            $filters['status'] = $criteria['status'];
        }

        if (!empty($criteria['gender'])) {
            $filters['gender'] = $criteria['gender'];
        }

        if (!empty($criteria['nutrition_status'])) {
            $filters['nutrition_status'] = $criteria['nutrition_status'];
        }

        if (!empty($criteria['barangay'])) {
            $filters['barangay'] = $criteria['barangay'];
        }

        if (!empty($criteria['registration_date']) && is_array($criteria['registration_date'])) {
            $filters['registration_date'] = $criteria['registration_date'];
        }

        if (!empty($criteria['search'])) {
            $searchTerm = strtolower($criteria['search']);
            $options['or'] = "(first_name.ilike.%{$searchTerm}%,last_name.ilike.%{$searchTerm}%,child_id.ilike.%{$searchTerm}%,mother_name.ilike.%{$searchTerm}%)";
        }

        try {
            return $this->db->select($this->table, $filters, $options);
        } catch (Throwable $e) {
            error_log('Child Model Error (search): ' . $e->getMessage());
            return [];
        }
    }

    public function count(array $filters = []): int
    {
        $this->syncUnder5Patients();
        try {
            return $this->db->count($this->table, $filters);
        } catch (Throwable $e) {
            error_log('Child Model Error (count): ' . $e->getMessage());
            return 0;
        }
    }

    public function getStats(): array
    {
        try {
            $all = $this->all();
            $total = count($all);
            $active = 0;
            $critical = 0;
            $normal = 0;
            $vaccineCompliant = 0;

            foreach ($all as $child) {
                $status = $child['status'] ?? '';
                $nutrition = $child['nutrition_status'] ?? '';
                $compliance = (int)($child['vaccine_compliance'] ?? 0);

                if ($status === 'active') {
                    $active++;
                }

                if ($nutrition === 'Critical') {
                    $critical++;
                }

                if ($nutrition === 'Normal') {
                    $normal++;
                }

                if ($compliance >= 80) {
                    $vaccineCompliant++;
                }
            }

            return [
                'total' => $total,
                'active' => $active,
                'critical_nutrition' => $critical,
                'normal_nutrition' => $normal,
                'vaccine_compliant' => $vaccineCompliant
            ];
        } catch (Throwable $e) {
            error_log('Child Model Error (getStats): ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'critical_nutrition' => 0,
                'normal_nutrition' => 0,
                'vaccine_compliant' => 0
            ];
        }
    }

    public function generateChildId(): string
    {
        $count = $this->db->count($this->table) + 1;
        return 'CHD-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Dynamically bridges pediatric patients (age <= 5) who registered or were assessed in Triage
     * into the Child Records table (EPI Immunization & Growth registry).
     */
    public function syncUnder5Patients(): void
    {
        try {
            $fiveYearsAgo = date('Y-m-d', strtotime('-5 years'));
            $pediatricPatients = $this->db->select('patients', [
                'birth_date' => ['gte' => $fiveYearsAgo]
            ]);

            foreach ($pediatricPatients as $p) {
                $pId = $p['id'] ?? null;
                $pName = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                if (!$pId || empty($pName)) continue;

                // Find any triage assessment vitals (weight, height) recorded for this patient
                $assessments = $this->db->select('assessment', ['patient_id' => $pId]);
                $latestAssess = !empty($assessments) ? $assessments[0] : null;
                $assessedWeight = $latestAssess['weight'] ?? null;
                $assessedHeight = $latestAssess['height'] ?? null;

                // Check if already in children table by matching first_name, last_name, birth_date
                $existing = $this->db->select($this->table, [
                    'first_name' => $p['first_name'],
                    'last_name' => $p['last_name'],
                    'birth_date' => $p['birth_date']
                ]);

                if (empty($existing)) {
                    $emergency = $p['emergency_contact'] ?? '';
                    $cleanMother = !empty($emergency) ? preg_replace('/\s*\([^)]*\)/', '', $emergency) : ($p['last_name'] . ' (Mother)');
                    
                    $childData = [
                        'child_id' => $this->generateChildId(),
                        'first_name' => $p['first_name'],
                        'last_name' => $p['last_name'],
                        'middle_name' => $p['middle_name'] ?? null,
                        'gender' => $p['gender'] ?? 'Male',
                        'birth_date' => $p['birth_date'],
                        'birth_weight' => $assessedWeight ?: null,
                        'birth_height' => $assessedHeight ?: null,
                        'blood_type' => $p['blood_type'] ?? 'O+',
                        'address' => $p['address'] ?? 'Caloocan City',
                        'barangay' => $p['barangay'] ?? 'Barangay 2',
                        'mother_name' => trim($cleanMother) ?: 'Mother',
                        'mother_contact' => $p['emergency_contact_number'] ?? $p['contact'] ?? null,
                        'health_center' => 'Main Health Center',
                        'registration_date' => $p['registration_date'] ?? date('Y-m-d'),
                        'status' => 'active',
                        'nutrition_status' => 'Normal',
                        'vaccine_compliance' => 0,
                        'last_visit' => date('Y-m-d')
                    ];
                    $inserted = $this->db->insert($this->table, $childData);
                    $childDbId = $inserted['id'] ?? null;
                } else {
                    $childDbId = $existing[0]['id'] ?? null;
                    // Update weight/height and real vaccine compliance for existing record
                    if ($childDbId) {
                        try {
                            $vaxCount = $this->db->count('immunizations', ['child_id' => $childDbId]);
                        } catch (Throwable $e) {
                            $vaxCount = 0;
                        }
                        $realCompliance = min(100, (int)round(($vaxCount / 15) * 100));
                        
                        $updates = ['vaccine_compliance' => $realCompliance];
                        if (empty($existing[0]['birth_weight']) && $assessedWeight) {
                            $updates['birth_weight'] = $assessedWeight;
                        }
                        if (empty($existing[0]['birth_height']) && $assessedHeight) {
                            $updates['birth_height'] = $assessedHeight;
                        }
                        $this->db->update($this->table, $updates, ['id' => $childDbId]);
                    }
                }

                // Log baseline nutrition assessment if weight/height exist
                if ($childDbId && ($assessedWeight || $assessedHeight)) {
                    try {
                        $existingNutri = $this->db->select('nutrition_assessments', ['child_id' => $childDbId]);
                        if (empty($existingNutri)) {
                            $bmi = ($assessedWeight && $assessedHeight) ? round($assessedWeight / pow($assessedHeight / 100, 2), 1) : null;
                            $this->db->insert('nutrition_assessments', [
                                'child_id' => $childDbId,
                                'assessment_date' => date('Y-m-d'),
                                'weight' => (float)$assessedWeight,
                                'height' => (float)$assessedHeight,
                                'bmi' => $bmi,
                                'nutrition_status' => 'normal',
                                'risk_level' => 'low',
                                'assessment_notes' => 'Baseline intake from Triage Assessment',
                                'assessed_by' => 'Triage Nurse Intake',
                                'status' => 'active'
                            ]);
                        }
                    } catch (Throwable $ge) {
                        error_log('Child sync baseline nutrition error: ' . $ge->getMessage());
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('Child syncUnder5Patients error: ' . $e->getMessage());
        }
    }
}