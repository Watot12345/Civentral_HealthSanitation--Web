<?php
// api/immunization.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Models/Child.php';
require_once __DIR__ . '/../app/Controllers/ChildController.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

try {
    $childModel = new Child();
    $controller = new ChildController($childModel);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Find the position of this script in the URL path to handle base URLs correctly
    $scriptPos = array_search('immunization.php', $parts, true);
    
    $targetId = $childId ?? (isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null);

    switch ($method) {
        case 'GET':
            if (isset($_GET['stats'])) {
                $controller->stats();
            } elseif ($targetId && isset($_GET['export']) && $_GET['export'] === 'pdf') {
                require_once __DIR__ . '/../vendor/autoload.php';
                require_once __DIR__ . '/../app/services/ExportService.php';
                require_once __DIR__ . '/../app/Models/ActivityLog.php';

                $child = $childModel->find((string)$targetId);
                if (!$child) {
                    Response::error('Child record not found', 404);
                }

                $db = Database::getInstance();
                $doses = [];
                try {
                    $doses = $db->select('immunizations', ['child_id' => $targetId], ['order' => 'date_administered.asc']);
                } catch (\Throwable $e) {
                    $doses = [];
                }

                $headers = ['Vaccine', 'Dose #', 'Date Administered', 'Administered By', 'Health Center', 'Batch Number'];
                $rows = [];
                foreach ($doses as $d) {
                    $rows[] = [
                        $d['vaccine'] ?? 'N/A',
                        $d['dose'] ?? '1',
                        $d['date_administered'] ?? '—',
                        $d['administered_by'] ?? 'Staff',
                        $d['health_center'] ?? 'Caloocan Health Center',
                        $d['batch_number'] ?? '—'
                    ];
                }
                if (empty($rows)) {
                    $rows[] = ['No immunizations recorded yet', '—', '—', '—', '—', '—'];
                }

                $childName = trim(($child['first_name'] ?? '') . ' ' . ($child['last_name'] ?? ''));
                $childCode = $child['child_id'] ?? "CH-{$targetId}";

                try {
                    $log = new ActivityLog();
                    $log->log("Generated Report: Child Immunization Record", [
                        'module'  => 'Immunization & Nutrition',
                        'details' => "Exported PDF Immunization Record for {$childName} ({$childCode})",
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $logEx) {}

                \App\Services\ExportService::toPdf(
                    ['headers' => $headers, 'rows' => $rows],
                    "Child Immunization Card — {$childName} ({$childCode})",
                    "child_{$targetId}_immunization.pdf"
                );
            } elseif ($targetId) {
                $controller->show($targetId);
            } elseif (isset($_GET['page'])) {
                $controller->paginated();
            } else {
                $controller->index();
            }
            break;

        case 'POST':
            $isVaccinationRecord = (isset($_GET['action']) && in_array($_GET['action'], ['record', 'vaccination'], true));
            $inputJson = json_decode(file_get_contents('php://input'), true);
            $data = is_array($inputJson) ? array_merge($_POST, $inputJson) : $_POST;

            // Check if payload looks like a vaccination record even if action query param was omitted
            if (!$isVaccinationRecord && (isset($data['vaccine']) || isset($data['vaccine_type']) || (isset($data['dose_number']) && isset($data['administered_date'])))) {
                $isVaccinationRecord = true;
            }

            if ($isVaccinationRecord) {
                $db = Database::getInstance();
                
                // Resolve child_id / target child
                $childId = $data['child_id'] ?? $_GET['id'] ?? $data['id'] ?? null;
                $patientName = trim($data['patient_name'] ?? $data['child_name'] ?? '');

                if (empty($childId) && !empty($patientName)) {
                    // Try to look up child by ID code or name
                    try {
                        $searchRes = $db->select('children', [], [
                            'or' => "(child_id.eq.{$patientName},first_name.ilike.%{$patientName}%,last_name.ilike.%{$patientName}%)",
                            'limit' => 1
                        ]);
                        if (!empty($searchRes)) {
                            $childId = $searchRes[0]['id'];
                        } else {
                            // If child record not found, create a new child record for this patient name
                            $nameParts = explode(' ', $patientName, 2);
                            $newChild = $childModel->create([
                                'first_name' => $nameParts[0],
                                'last_name' => $nameParts[1] ?? '',
                                'status' => 'active',
                                'vaccine_compliance' => 0
                            ]);
                            $childId = $newChild['id'] ?? ($newChild[0]['id'] ?? null);
                        }
                    } catch (\Throwable $e) {
                        error_log('Error looking up or creating child for vaccination: ' . $e->getMessage());
                    }
                } elseif (!empty($childId) && !is_numeric($childId)) {
                    // Passed a string child_id like 'CH-001'
                    try {
                        $c = $childModel->findByChildId((string)$childId);
                        if ($c && !empty($c['id'])) {
                            $childId = (int)$c['id'];
                        }
                    } catch (\Throwable $e) {}
                }

                if (empty($childId)) {
                    Response::error('Child/Patient is required to record vaccination', 422);
                }

                // Resolve vaccine name
                $vaccine = trim($data['vaccine'] ?? $data['name'] ?? $data['vaccine_name'] ?? $data['vaccine_type'] ?? '');
                if (empty($vaccine)) {
                    Response::error('Vaccine name/type is required', 422);
                }

                // Resolve dose number
                $rawDose = $data['dose'] ?? $data['dose_number'] ?? 1;
                $dose = 1;
                if (is_numeric($rawDose)) {
                    $dose = max(1, (int)$rawDose);
                } elseif (preg_match('/\d+/', (string)$rawDose, $matches)) {
                    $dose = max(1, (int)$matches[0]);
                }

                // Resolve dates
                $rawAdminDate = $data['date_administered'] ?? $data['administered_date'] ?? $data['date'] ?? date('Y-m-d');
                $dateAdministered = !empty($rawAdminDate) ? date('Y-m-d', strtotime($rawAdminDate)) : date('Y-m-d');

                $rawNextDue = $data['next_due_date'] ?? $data['next_due'] ?? null;
                $nextDueDate = !empty($rawNextDue) ? date('Y-m-d', strtotime($rawNextDue)) : null;

                $batchNumber = !empty($data['batch_number'] ?? $data['batch'] ?? null) ? trim($data['batch_number'] ?? $data['batch']) : null;
                $administeredBy = !empty($data['administered_by'] ?? $data['admin_by'] ?? $data['by'] ?? null) ? trim($data['administered_by'] ?? $data['admin_by'] ?? $data['by']) : null;
                $healthCenter = !empty($data['health_center'] ?? $data['facility'] ?? null) ? trim($data['health_center'] ?? $data['facility']) : 'Caloocan Main Health Center';
                $notes = !empty($data['notes']) ? trim($data['notes']) : null;

                $record = [
                    'child_id'          => (int)$childId,
                    'vaccine'           => $vaccine,
                    'dose'              => $dose,
                    'date_administered'  => $dateAdministered,
                    'next_due_date'      => $nextDueDate,
                    'batch_number'       => $batchNumber,
                    'administered_by'    => $administeredBy,
                    'health_center'      => $healthCenter,
                    'notes'              => $notes,
                ];

                try {
                    $res = $db->insert('immunizations', $record);

                    // Recalculate child's vaccine compliance score
                    try {
                        $allDoses = $db->select('immunizations', ['child_id' => (int)$childId]);
                        $doseCount = is_array($allDoses) ? count($allDoses) : 1;
                        // Standard childhood target is ~10 primary doses in EPI schedule
                        $compliance = min(100, (int)round(($doseCount / 10) * 100));
                        $db->update('children', ['vaccine_compliance' => $compliance], ['id' => (int)$childId]);
                    } catch (\Throwable $ce) {
                        error_log('Notice updating child vaccine compliance: ' . $ce->getMessage());
                    }

                    Response::success('Vaccination recorded successfully', $res, 201);
                } catch (\Throwable $e) {
                    error_log('Error inserting immunization: ' . $e->getMessage());
                    Response::error('Failed to record vaccination: ' . $e->getMessage(), 500);
                }
            } else {
                $controller->store();
            }
            break;

        case 'PUT':
        case 'PATCH':
            if (!$targetId) {
                Response::error('Child ID is required for update', 400);
            }
            $controller->update($targetId);
            break;

        default:
            Response::error('Method not allowed', 405);
    }

} catch (\Throwable $e) {
    error_log('Immunization API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error: ' . $e->getMessage()
    ]);
    exit;
}