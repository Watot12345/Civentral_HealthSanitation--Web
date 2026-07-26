<?php
// app/Controllers/PermitController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/Permit.php';

class PermitController extends BaseController
{
    private Permit $permitModel;

    public function __construct()
    {
        $this->permitModel = new Permit();
    }

    public function index(): void
    {
        $this->handle(function() {
            $rawPermits = $this->permitModel->all(['order' => 'created_at.desc']);

            $permits = array_map(function ($p) {
                return $this->enrichPermit($p);
            }, $rawPermits);

            return [
                'success' => true,
                'data' => $permits,
                'total' => count($permits)
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
        $type = trim($_GET['type'] ?? '');

        $this->handle(function() use ($page, $limit, $offset, $search, $status, $type) {
            $filters = [];
            $options = [
                'order' => 'created_at.desc',
                'limit' => $limit,
                'offset' => $offset
            ];

            // Apply filters
            if (!empty($status)) {
                $filters['status'] = 'eq.' . $status;
            }

            $allPermits = $this->permitModel->all(['order' => 'created_at.desc']);
            $filtered = [];

            foreach ($allPermits as $p) {
                $passesStatus = empty($status) || ($p['status'] ?? '') === $status;
                $passesType = empty($type) || ($p['business_type'] ?? '') === $type;

                $passesSearch = true;
                if (!empty($search)) {
                    $needle = strtolower($search);
                    $haystack = strtolower(
                        ($p['applicant'] ?? '') . ' ' .
                        ($p['permit_id'] ?? '') . ' ' .
                        ($p['business_type'] ?? '') . ' ' .
                        ($p['owner_name'] ?? '')
                    );
                    $passesSearch = str_contains($haystack, $needle);
                }

                if ($passesStatus && $passesType && $passesSearch) {
                    $filtered[] = $p;
                }
            }

            $total = count($filtered);
            $paginated = array_slice($filtered, $offset, $limit);

            $permits = array_map(function ($p) {
                return $this->enrichPermit($p);
            }, $paginated);

            return [
                'success' => true,
                'data' => $permits,
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
            $permit = $this->permitModel->find($id);

            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }

            return [
                'success' => true,
                'data' => $this->enrichPermit($permit)
            ];
        });
    }

    public function store(): void
    {
        $data = $this->input();

        $this->handle(function() use ($data) {
            // Validate required fields
            $required = ['applicant', 'business_type', 'address', 'owner_name', 'contact', 'fee'];
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

            $dbData = $this->prepareDbData($data);

            if (empty($dbData['permit_id'])) {
                $dbData['permit_id'] = $this->permitModel->generatePermitId();
            }

            $result = $this->permitModel->create($dbData);

            return [
                'success' => true,
                'message' => 'Permit application submitted successfully',
                'data' => $result,
                'code' => 201
            ];
        });
    }

    public function update(string $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $permit = $this->permitModel->find($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }

            $dbData = $this->prepareDbData($data, true);
            $result = $this->permitModel->updateById($id, $dbData);

            return [
                'success' => true,
                'message' => 'Permit updated successfully',
                'data' => $result
            ];
        });
    }

    public function updateStatus(string $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $permit = $this->permitModel->find($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }

            $status = strtolower(trim($data['status'] ?? ''));
            $validStatuses = ['pending', 'under_review', 'approved', 'rejected', 'expired'];
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

            // Set dates based on status
            if ($status === 'under_review' && empty($permit['inspection_date'])) {
                $updateData['inspection_date'] = date('Y-m-d');
            }
            if ($status === 'approved') {
                $updateData['approved_date'] = date('Y-m-d');
                // Set expiry to 1 year from now
                $expiry = new DateTime('+1 year');
                $updateData['expiry_date'] = $expiry->format('Y-m-d');
            }

            // Set inspector if provided
            if (!empty($data['inspector_id'])) {
                $updateData['inspector_id'] = (int)$data['inspector_id'];
            }

            // Set notes if provided
            if (isset($data['notes'])) {
                $updateData['notes'] = trim($data['notes']);
            }

            $result = $this->permitModel->updateById($id, $updateData);

            return [
                'success' => true,
                'message' => 'Permit status updated to ' . ucfirst(str_replace('_', ' ', $status)),
                'data' => $result
            ];
        });
    }

    public function review(string $id): void
    {
        $data = $this->input();

        $this->handle(function() use ($id, $data) {
            $permit = $this->permitModel->find($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }

            $status = strtolower(trim($data['status'] ?? $permit['status']));
            $validStatuses = ['pending', 'under_review', 'approved', 'rejected', 'expired'];
            if (!in_array($status, $validStatuses)) {
                return [
                    'success' => false,
                    'message' => 'Invalid status value',
                    'code' => 400
                ];
            }

            $updateData = [
                'status' => $status,
                'notes' => trim($data['notes'] ?? $permit['notes'] ?? ''),
                'inspector_id' => !empty($data['inspector_id']) ? (int)$data['inspector_id'] : ($permit['inspector_id'] ?? null),
                'updated_at' => date('Y-m-d H:i:sP')
            ];

            if ($status === 'approved') {
                $updateData['approved_date'] = date('Y-m-d');
                $expiry = new DateTime('+1 year');
                $updateData['expiry_date'] = $expiry->format('Y-m-d');
            }

            $result = $this->permitModel->updateById($id, $updateData);

            return [
                'success' => true,
                'message' => 'Permit #' . $permit['permit_id'] . ' reviewed successfully',
                'data' => $result
            ];
        });
    }

    public function destroy(string $id): void
    {
        $this->handle(function() use ($id) {
            $permit = $this->permitModel->find($id);
            if (!$permit) {
                return [
                    'success' => false,
                    'message' => 'Permit not found',
                    'code' => 404
                ];
            }

            $success = $this->permitModel->deleteById($id);

            return [
                'success' => $success,
                'message' => $success ? 'Permit deleted successfully' : 'Failed to delete permit'
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

            $rawPermits = $this->permitModel->all();

            $results = array_values(array_filter($rawPermits, function($p) use ($query) {
                return str_contains(strtolower($p['permit_id'] ?? ''), $query) ||
                       str_contains(strtolower($p['applicant'] ?? ''), $query) ||
                       str_contains(strtolower($p['business_type'] ?? ''), $query) ||
                       str_contains(strtolower($p['owner_name'] ?? ''), $query) ||
                       str_contains(strtolower($p['address'] ?? ''), $query);
            }));

            $results = array_map(function ($p) {
                return $this->enrichPermit($p);
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
            $rawPermits = $this->permitModel->all();

            $total = count($rawPermits);
            $pending = count(array_filter($rawPermits, fn($p) => ($p['status'] ?? '') === 'pending'));
            $underReview = count(array_filter($rawPermits, fn($p) => ($p['status'] ?? '') === 'under_review'));
            $approved = count(array_filter($rawPermits, fn($p) => ($p['status'] ?? '') === 'approved'));
            $rejected = count(array_filter($rawPermits, fn($p) => ($p['status'] ?? '') === 'rejected'));
            $expired = count(array_filter($rawPermits, fn($p) => ($p['status'] ?? '') === 'expired'));
            $totalRevenue = array_sum(array_column($rawPermits, 'fee'));

            return [
                'success' => true,
                'data' => [
                    'total' => $total,
                    'pending' => $pending,
                    'under_review' => $underReview,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'expired' => $expired,
                    'total_revenue' => $totalRevenue
                ]
            ];
        });
    }

    private function prepareDbData(array $data, bool $isUpdate = false): array
    {
        $dbData = [];

        if (isset($data['permit_id'])) {
            $dbData['permit_id'] = trim($data['permit_id']);
        }

        if (isset($data['applicant'])) {
            $dbData['applicant'] = trim($data['applicant']);
        }

        if (isset($data['business_name'])) {
            $dbData['business_name'] = trim($data['business_name']);
        }

        if (isset($data['business_type'])) {
            $dbData['business_type'] = trim($data['business_type']);
        } elseif (!$isUpdate) {
            $dbData['business_type'] = 'Food Establishment';
        }

        if (isset($data['address'])) {
            $dbData['address'] = trim($data['address']);
        }

        if (isset($data['owner_name'])) {
            $dbData['owner_name'] = trim($data['owner_name']);
        }

        if (isset($data['contact'])) {
            $dbData['contact'] = trim($data['contact']);
        }

        if (isset($data['email'])) {
            $dbData['email'] = trim($data['email']);
        }

        if (isset($data['fee'])) {
            $dbData['fee'] = (float)$data['fee'];
        } elseif (!$isUpdate) {
            $dbData['fee'] = 0.00;
        }

        if (isset($data['paid'])) {
            $dbData['paid'] = (bool)$data['paid'];
        }

        if (isset($data['payment_method'])) {
            $dbData['payment_method'] = trim($data['payment_method']);
        }

        if (isset($data['payment_reference'])) {
            $dbData['payment_reference'] = trim($data['payment_reference']);
        }

        if (isset($data['status'])) {
            $status = strtolower(trim($data['status']));
            $validStatuses = ['pending', 'under_review', 'approved', 'rejected', 'expired'];
            $dbData['status'] = in_array($status, $validStatuses) ? $status : 'pending';
        } elseif (!$isUpdate) {
            $dbData['status'] = 'pending';
        }

        if (isset($data['inspector_id'])) {
            $dbData['inspector_id'] = (int)$data['inspector_id'];
        }

        if (isset($data['inspection_date'])) {
            $dbData['inspection_date'] = $data['inspection_date'];
        }

        if (isset($data['approved_date'])) {
            $dbData['approved_date'] = $data['approved_date'];
        }

        if (isset($data['expiry_date'])) {
            $dbData['expiry_date'] = $data['expiry_date'];
        }

        if (isset($data['notes'])) {
            $dbData['notes'] = trim($data['notes']);
        }

        $dbData['updated_at'] = date('Y-m-d H:i:sP');

        return $dbData;
    }

    private function enrichPermit(array $p): array
    {
        return [
            'id' => (int)($p['id'] ?? 0),
            'permit_id' => $p['permit_id'] ?? '',
            'applicant' => $p['applicant'] ?? '',
            'business_name' => $p['business_name'] ?? '',
            'business_type' => $p['business_type'] ?? '',
            'address' => $p['address'] ?? '',
            'owner_name' => $p['owner_name'] ?? '',
            'contact' => $p['contact'] ?? '',
            'email' => $p['email'] ?? '',
            'fee' => (float)($p['fee'] ?? 0),
            'paid' => (bool)($p['paid'] ?? false),
            'payment_method' => $p['payment_method'] ?? '',
            'payment_reference' => $p['payment_reference'] ?? '',
            'status' => strtolower($p['status'] ?? 'pending'),
            'inspector_id' => (int)($p['inspector_id'] ?? 0),
            'inspection_date' => $p['inspection_date'] ?? null,
            'approved_date' => $p['approved_date'] ?? null,
            'expiry_date' => $p['expiry_date'] ?? null,
            'notes' => $p['notes'] ?? '',
            'created_at' => $p['created_at'] ?? '',
            'updated_at' => $p['updated_at'] ?? ''
        ];
    }
}