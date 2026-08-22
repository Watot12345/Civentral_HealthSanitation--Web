<?php
// app/Controllers/InspectionController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Inspection.php';
require_once __DIR__ . '/../Models/Permit.php';
require_once __DIR__ . '/../Models/Employee.php';

class InspectionController extends BaseController
{
    private Inspection $inspectionModel;
    private Permit $permitModel;
    private Employee $employeeModel;

    public function __construct()
    {
        $this->inspectionModel = new Inspection();
        $this->permitModel = new Permit();
        $this->employeeModel = new Employee();
    }

    public function index(): void
    {
        $this->handle(function() {
            $rawInspections = $this->inspectionModel->all(['order' => 'created_at.desc']);

            $inspections = array_map(function ($i) {
                return $this->enrichInspection($i);
            }, $rawInspections);

            return [
                'success' => true,
                'data' => $inspections,
                'total' => count($inspections)
            ];
        });
    }

    public function paginated(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['q'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $result = trim($_GET['result'] ?? '');
        $inspector = trim($_GET['inspector'] ?? '');
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $this->handle(function() use ($page, $limit, $offset, $search, $status, $result, $inspector, $dateFrom, $dateTo) {
            $allInspections = $this->inspectionModel->all(['order' => 'created_at.desc']);
            $filtered = [];

            // Need permit data for filtering by applicant
            $permitsMap = $this->getPermitsMap();

            foreach ($allInspections as $i) {
                $permit = $permitsMap[$i['permit_id'] ?? 0] ?? null;

                $passesStatus = empty($status) || ($i['status'] ?? '') === $status;
                $passesResult = empty($result) || ($i['overall_status'] ?? '') === $result;
                $inspectionDate = substr((string)($i['scheduled_date'] ?? ''), 0, 10);
                $passesDateFrom = empty($dateFrom) || $inspectionDate >= $dateFrom;
                $passesDateTo = empty($dateTo) || $inspectionDate <= $dateTo;

                // Inspector filter by ID (since inspector is numeric FK)
                $passesInspector = true;
                if (!empty($inspector)) {
                    $inspectorName = $this->getInspectorName($i['inspector_id'] ?? null);
                    $passesInspector = stripos($inspectorName, $inspector) !== false;
                }

                $passesSearch = true;
                if (!empty($search)) {
                    $needle = strtolower($search);
                    $applicant = $permit ? strtolower($permit['applicant'] ?? '') : '';
                    $inspectorName = strtolower($this->getInspectorName($i['inspector_id'] ?? null));
                    $haystack = $applicant . ' ' .
                        strtolower($i['inspection_id'] ?? '') . ' ' .
                        strtolower($permit['permit_id'] ?? '') . ' ' .
                        $inspectorName;
                    $passesSearch = str_contains($haystack, $needle);
                }

                if ($passesStatus && $passesResult && $passesInspector && $passesSearch && $passesDateFrom && $passesDateTo) {
                    $filtered[] = $i;
                }
            }

            $total = count($filtered);
            $paginated = array_slice($filtered, $offset, $limit);

            $inspections = array_map(function ($i) {
                return $this->enrichInspection($i);
            }, $paginated);

            return [
                'success' => true,
                'data' => $inspections,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => max(1, ceil($total / $limit))
            ];
        });
    }

    public function show(string $id): void
    {
        $this->handle(function() use ($id) {
            $inspection = $this->inspectionModel->find($id);

            if (!$inspection) {
                return [
                    'success' => false,
                    'message' => 'Inspection not found',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'data' => $this->enrichInspection($inspection)
            ];
        });
    }

    public function store(): void
    {
        $data = $this->input();

        $this->handle(function() use ($data) {
            // Validate required fields
            $required = ['permit_id', 'inspector_id', 'scheduled_date', 'scheduled_time'];
            $missing = [];
            foreach ($required as $field) {
                if (empty($data[$field]) && $data[$field] !== '0') {
                    $missing[] = $field;
                }
            }
            if (!empty($missing)) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields: ' . implode(', ', $missing),
                    'code' => 400
                ];
            }

            // Verify permit exists
            $permit = $this->permitModel->find($data['permit_id']);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }

            $dbData = $this->prepareDbData($data);

            if (empty($dbData['inspection_id'])) {
                $dbData['inspection_id'] = $this->inspectionModel->generateInspectionId();
            }

            $result = $this->inspectionModel->create($dbData);

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $inspCode = $dbData['inspection_id'] ?? ($result['inspection_id'] ?? 'INSP');
                    $permitCode = $data['permit_id'] ?? 'Permit';
                    $logger->log("Scheduled Sanitary Inspection ({$inspCode})", [
                        'module'  => 'Sanitation Permits',
                        'details' => "Permit: {$permitCode} | Date: " . ($dbData['scheduled_date'] ?? date('Y-m-d')),
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            return [
                'success' => true,
                'message' => 'Inspection scheduled successfully',
                'data' => $result,
                'code' => 201
            ];
        });
    }

    public function update(string $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $inspection = $this->inspectionModel->find($id);
            if (!$inspection) {
                return [
                    'success' => false,
                    'message' => 'Inspection not found',
                    'code' => 404
                ];
            }

            $dbData = $this->prepareDbData($data, true);
            $result = $this->inspectionModel->updateById($id, $dbData);

            return [
                'success' => true,
                'message' => 'Inspection updated successfully',
                'data' => $result
            ];
        });
    }

    public function conduct(string $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $inspection = $this->inspectionModel->find($id);
            if (!$inspection) {
                return [
                    'success' => false,
                    'message' => 'Inspection not found',
                    'code' => 404
                ];
            }

            if ($inspection['status'] !== 'scheduled') {
                return [
                    'success' => false,
                    'message' => 'Only scheduled inspections can be conducted',
                    'code' => 400
                ];
            }

            $updateData = [
                'status' => 'completed',
                'completed_at' => date('Y-m-d H:i:sP'),
                'conducted_date' => date('Y-m-d H:i:sP'),
                'updated_at' => date('Y-m-d H:i:sP')
            ];

            // Set findings as JSON array
            if (isset($data['findings'])) {
                $updateData['findings'] = json_encode($data['findings']);
            }

            // Set overall status
            if (isset($data['overall_status'])) {
                $validStatuses = ['compliant', 'partially_compliant', 'non_compliant'];
                $overall = strtolower(trim($data['overall_status']));
                $updateData['overall_status'] = in_array($overall, $validStatuses) ? $overall : 'partially_compliant';
            }

            if (isset($data['recommendations'])) {
                $updateData['recommendations'] = trim($data['recommendations']);
            }

            if (isset($data['attachments'])) {
                $updateData['attachments'] = json_encode($data['attachments']);
            }

            if (isset($data['notes'])) {
                $updateData['notes'] = trim($data['notes']);
            }

            if (isset($data['follow_up_date'])) {
                $updateData['follow_up_date'] = !empty($data['follow_up_date']) ? $data['follow_up_date'] : null;
            }

            $result = $this->inspectionModel->updateById($id, $updateData);

            // Sync associated permit status in real-time
            $permitId = (int)($inspection['permit_id'] ?? 0);
            if ($permitId > 0) {
                try {
                    $permitUpdate = [
                        'updated_at' => date('Y-m-d H:i:sP'),
                        'inspection_date' => date('Y-m-d')
                    ];

                    $overall = $updateData['overall_status'] ?? 'partially_compliant';
                    if ($overall === 'compliant') {
                        $permitUpdate['status'] = 'approved';
                        $permitUpdate['approved_date'] = date('Y-m-d');
                        $expiry = new DateTime('+1 year');
                        $permitUpdate['expiry_date'] = $expiry->format('Y-m-d');
                        $permitUpdate['rejection_reason'] = null;
                    } elseif ($overall === 'non_compliant') {
                        $permitUpdate['status'] = 'rejected';
                        if (!empty($updateData['recommendations'])) {
                            $permitUpdate['rejection_reason'] = $updateData['recommendations'];
                        }
                    } else { // partially_compliant
                        $permitUpdate['status'] = 'under_review';
                    }

                    $this->permitModel->updateById($permitId, $permitUpdate);
                } catch (\Throwable $pe) {
                    error_log('Failed to sync permit status on inspection conduct: ' . $pe->getMessage());
                }
            }

            if (file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
                require_once __DIR__ . '/../Models/ActivityLog.php';
                try {
                    $logger = new ActivityLog();
                    $inspCode = $inspection['inspection_id'] ?? 'INSP';
                    $statusLabel = match($updateData['overall_status'] ?? '') {
                        'compliant' => 'Compliant (Passed)',
                        'non_compliant' => 'Non-Compliant (Failed)',
                        'partially_compliant' => 'Partially Compliant (Conditional)',
                        default => 'Completed'
                    };
                    $logger->log("Conducted Sanitation Inspection ({$inspCode})", [
                        'module'  => 'Sanitation Permits',
                        'details' => "Outcome: {$statusLabel}",
                        'status'  => 'Success'
                    ]);
                } catch (Throwable $e) {}
            }

            return [
                'success' => true,
                'message' => 'Inspection #' . $inspection['inspection_id'] . ' completed successfully',
                'data' => $result
            ];
        });
    }

    public function updateStatus(string $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $inspection = $this->inspectionModel->find($id);
            if (!$inspection) {
                return [
                    'success' => false,
                    'message' => 'Inspection not found',
                    'code' => 404
                ];
            }

            $status = strtolower(trim($data['status'] ?? ''));
            $validStatuses = ['scheduled', 'completed', 'cancelled'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid status value. Valid: ' . implode(', ', $validStatuses),
                    'code' => 400
                ];
            }

            $updateData = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:sP')
            ];

            if ($status === 'cancelled' && isset($data['notes'])) {
                $updateData['notes'] = trim($data['notes']);
            }

            $result = $this->inspectionModel->updateById($id, $updateData);

            return [
                'success' => true,
                'message' => 'Inspection status updated to ' . ucfirst($status),
                'data' => $result
            ];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function() use ($id) {
            $inspection = $this->inspectionModel->find($id);
            if (!$inspection) {
                return [
                    'success' => false,
                    'message' => 'Inspection not found',
                    'code' => 404
                ];
            }

            $success = $this->inspectionModel->deleteById($id);

            return [
                'success' => $success,
                'message' => $success ? 'Inspection deleted successfully' : 'Failed to delete inspection'
            ];
        });
    }

    public function search(): void
    {
        $query = strtolower($_GET['q'] ?? '');

        $this->handle(function() use ($query) {
            if (empty($query)) {
                return [
                    'success' => false,
                    'message' => 'Search query is required',
                    'code' => 400
                ];
            }

            $rawInspections = $this->inspectionModel->all();
            $permitsMap = $this->getPermitsMap();

            $results = array_values(array_filter($rawInspections, function($i) use ($query, $permitsMap) {
                $permit = $permitsMap[$i['permit_id'] ?? 0] ?? null;
                $inspectorName = strtolower($this->getInspectorName($i['inspector_id'] ?? null));

                return str_contains(strtolower($i['inspection_id'] ?? ''), $query) ||
                       str_contains(strtolower($permit['permit_id'] ?? ''), $query) ||
                       str_contains(strtolower($permit['applicant'] ?? ''), $query) ||
                       str_contains($inspectorName, $query);
            }));

            $results = array_map(function ($i) {
                return $this->enrichInspection($i);
            }, $results);

            return [
                'success' => true,
                'data' => $results,
                'total' => count($results)
            ];
        });
    }

    public function stats(): void
    {
        $this->handle(function() {
            $rawInspections = $this->inspectionModel->all();

            $total = count($rawInspections);
            $scheduled = count(array_filter($rawInspections, fn($i) => ($i['status'] ?? '') === 'scheduled'));
            $completed = count(array_filter($rawInspections, fn($i) => ($i['status'] ?? '') === 'completed'));
            $cancelled = count(array_filter($rawInspections, fn($i) => ($i['status'] ?? '') === 'cancelled'));
            $compliant = count(array_filter($rawInspections, fn($i) => ($i['overall_status'] ?? '') === 'compliant'));
            $partialCompliant = count(array_filter($rawInspections, fn($i) => ($i['overall_status'] ?? '') === 'partially_compliant'));
            $nonCompliant = count(array_filter($rawInspections, fn($i) => ($i['overall_status'] ?? '') === 'non_compliant'));

            // Follow-ups: inspections with follow_up recommendations or that need re-inspection
            $followUps = count(array_filter($rawInspections, function($i) {
                return ($i['overall_status'] ?? '') === 'non_compliant' ||
                       ($i['overall_status'] ?? '') === 'partially_compliant';
            }));

            return [
                'success' => true,
                'data' => [
                    'total' => $total,
                    'scheduled' => $scheduled,
                    'completed' => $completed,
                    'cancelled' => $cancelled,
                    'follow_ups' => $followUps,
                    'compliant' => $compliant,
                    'partial_compliant' => $partialCompliant,
                    'non_compliant' => $nonCompliant
                ]
            ];
        });
    }

    private function prepareDbData(array $data, bool $isUpdate = false): array
    {
        $dbData = [];

        if (isset($data['inspection_id'])) {
            $dbData['inspection_id'] = trim($data['inspection_id']);
        }

        if (isset($data['permit_id'])) {
            $dbData['permit_id'] = (int)$data['permit_id'];
        } elseif (!$isUpdate) {
            $dbData['permit_id'] = 0;
        }

        if (isset($data['inspector_id'])) {
            $dbData['inspector_id'] = (int)$data['inspector_id'];
        }

        if (isset($data['scheduled_date'])) {
            $dbData['scheduled_date'] = $data['scheduled_date'];
        }

        if (isset($data['scheduled_time'])) {
            $dbData['scheduled_time'] = $data['scheduled_time'];
        }

        if (isset($data['conducted_date'])) {
            $dbData['conducted_date'] = $data['conducted_date'];
        }

        if (isset($data['findings'])) {
            $dbData['findings'] = is_string($data['findings']) ? $data['findings'] : json_encode($data['findings']);
        }

        if (isset($data['overall_status'])) {
            $status = strtolower(trim($data['overall_status']));
            $validStatuses = ['compliant', 'partially_compliant', 'non_compliant'];
            $dbData['overall_status'] = in_array($status, $validStatuses) ? $status : 'partially_compliant';
        }

        if (isset($data['recommendations'])) {
            $dbData['recommendations'] = trim($data['recommendations']);
        }

        if (isset($data['attachments'])) {
            $dbData['attachments'] = is_string($data['attachments']) ? $data['attachments'] : json_encode($data['attachments']);
        }

        if (isset($data['status'])) {
            $status = strtolower(trim($data['status']));
            $validStatuses = ['scheduled', 'completed', 'cancelled'];
            $dbData['status'] = in_array($status, $validStatuses) ? $status : 'scheduled';
        } elseif (!$isUpdate) {
            $dbData['status'] = 'scheduled';
        }

        if (isset($data['notes'])) {
            $dbData['notes'] = trim($data['notes']);
        }

        $dbData['updated_at'] = date('Y-m-d H:i:sP');

        return $dbData;
    }

    private function enrichInspection(array $i): array
    {
        // Get permit data
        $permit = null;
        $permitId = $i['permit_id'] ?? 0;
        if ($permitId) {
            $permit = $this->permitModel->find($permitId);
        }

        // Get inspector name
        $inspectorId = $i['inspector_id'] ?? null;
        $inspectorName = $this->getInspectorName($inspectorId);

        // Decode findings JSON
        $findings = [];
        if (!empty($i['findings'])) {
            $decoded = is_string($i['findings']) ? json_decode($i['findings'], true) : $i['findings'];
            $findings = is_array($decoded) ? $decoded : [];
        }

        // Build address from permit if available
        $address = $permit['address'] ?? '';

        return [
            'id' => (int)($i['id'] ?? 0),
            'inspection_id' => $i['inspection_id'] ?? '',
            'permit_id' => (int)($permitId),
            'permit_number' => $permit['permit_id'] ?? '',
            'applicant' => $permit['applicant'] ?? 'Unknown',
            'business_type' => $permit['business_type'] ?? '',
            'address' => $address,
            'inspector_id' => (int)($inspectorId ?? 0),
            'inspector_name' => $inspectorName,
            'scheduled_date' => $i['scheduled_date'] ?? '',
            'scheduled_time' => $i['scheduled_time'] ?? '',
            'conducted_date' => $i['conducted_date'] ?? null,
            'findings' => $findings,
            'overall_status' => $i['overall_status'] ?? null,
            'recommendations' => $i['recommendations'] ?? '',
            'attachments' => !empty($i['attachments']) ? (is_string($i['attachments']) ? json_decode($i['attachments'], true) : $i['attachments']) : [],
            'status' => strtolower($i['status'] ?? 'scheduled'),
            'completed_at' => $i['completed_at'] ?? null,
            'follow_up_date' => $i['follow_up_date'] ?? null,
            'notes' => $i['notes'] ?? '',
            'created_at' => $i['created_at'] ?? '',
            'updated_at' => $i['updated_at'] ?? ''
        ];
    }

    private function getInspectorName($inspectorId): string
    {
        if (!$inspectorId) return 'Unassigned';
        try {
            $employee = $this->employeeModel->find($inspectorId);
            return $employee ? ($employee['full_name'] ?? 'Inspector #' . $inspectorId) : 'Inspector #' . $inspectorId;
        } catch (\Exception $e) {
            return 'Inspector #' . $inspectorId;
        }
    }

    private function getPermitsMap(): array
    {
        try {
            $allPermits = $this->permitModel->all();
            $map = [];
            foreach ($allPermits as $p) {
                $map[$p['id']] = $p;
            }
            return $map;
        } catch (\Exception $e) {
            return [];
        }
    }
}
