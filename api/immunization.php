<?php
date_default_timezone_set('Asia/Manila');
// api/immunization.php

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Core/Response.php';
require_once __DIR__ . '/../app/Constants/Permissions.php';
require_once __DIR__ . '/../app/Middleware/AuthorizationMiddleware.php';
require_once __DIR__ . '/../app/Models/Child.php';
require_once __DIR__ . '/../app/Controllers/ChildController.php';

use App\Constants\Permissions;
use App\Middleware\AuthorizationMiddleware;

if (PHP_SAPI !== 'cli') {
    // Handle CORS
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    header('Content-Type: application/json');
}

/**
 * Helper function to calculate vaccine compliance based on distinct required EPI antigens.
 * Standard EPI schedule consists of 14 target doses:
 * - BCG (1)
 * - Hepatitis B (1)
 * - Pentavalent / DPT-HepB-Hib (3)
 * - OPV (3)
 * - IPV (1)
 * - PCV (3)
 * - MMR / Measles (2)
 */
function calculateVaccineCompliance(Database $db, int $childId): int
{
    try {
        $allDoses = $db->select('immunizations', ['child_id' => $childId]);
        if (empty($allDoses) || !is_array($allDoses)) {
            return 0;
        }

        $targetDoses = [
            'bcg'         => 1,
            'hepb'        => 1,
            'pentavalent' => 3,
            'opv'         => 3,
            'ipv'         => 1,
            'pcv'         => 3,
            'mmr'         => 2
        ];
        $totalRequiredDoses = array_sum($targetDoses); // 14

        $achievedDoses = [
            'bcg'         => [],
            'hepb'        => [],
            'pentavalent' => [],
            'opv'         => [],
            'ipv'         => [],
            'pcv'         => [],
            'mmr'         => []
        ];

        foreach ($allDoses as $doseRow) {
            $vaccineName = strtolower(trim((string)($doseRow['vaccine'] ?? '')));
            $rawDose = $doseRow['dose'] ?? 1;
            $doseNum = 1;
            if (is_numeric($rawDose)) {
                $doseNum = max(1, (int)$rawDose);
            } elseif (preg_match('/\d+/', (string)$rawDose, $matches)) {
                $doseNum = max(1, (int)$matches[0]);
            }

            $category = null;
            if (str_contains($vaccineName, 'penta') || str_contains($vaccineName, 'dpt')) {
                $category = 'pentavalent';
            } elseif (str_contains($vaccineName, 'bcg')) {
                $category = 'bcg';
            } elseif (str_contains($vaccineName, 'hepb') || str_contains($vaccineName, 'hepatitis')) {
                $category = 'hepb';
            } elseif (str_contains($vaccineName, 'ipv') || str_contains($vaccineName, 'inactivated polio')) {
                $category = 'ipv';
            } elseif (str_contains($vaccineName, 'opv') || str_contains($vaccineName, 'oral polio') || str_contains($vaccineName, 'polio')) {
                $category = 'opv';
            } elseif (str_contains($vaccineName, 'pcv') || str_contains($vaccineName, 'pneumococcal')) {
                $category = 'pcv';
            } elseif (str_contains($vaccineName, 'mmr') || str_contains($vaccineName, 'measles')) {
                $category = 'mmr';
            }

            if ($category && isset($targetDoses[$category])) {
                $cappedDose = min($doseNum, $targetDoses[$category]);
                $achievedDoses[$category][$cappedDose] = true;
            }
        }

        $validCreditedCount = 0;
        foreach ($achievedDoses as $dosesMap) {
            $validCreditedCount += count($dosesMap);
        }

        return min(100, (int)round(($validCreditedCount / $totalRequiredDoses) * 100));
    } catch (\Throwable $e) {
        error_log('Compliance calculation error: ' . $e->getMessage());
        return 0;
    }
}

if (PHP_SAPI !== 'cli' || isset($_SERVER['REQUEST_METHOD'])) {
    try {
        // 1. Authorize department access for the Immunization API
        AuthorizationMiddleware::authorizeDepartment('immunization & nutrition', 'Immunization API');

    $childModel = new Child();
    $controller = new ChildController($childModel);

    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Find the position of this script in the URL path to handle base URLs correctly
    $scriptPos = array_search('immunization.php', $parts, true);
    
    $targetId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    switch ($method) {
        case 'GET':
            AuthorizationMiddleware::authorize(Permissions::IMMUNIZATION_VIEW, 'Immunization API View');

            if (isset($_GET['stats'])) {
                $controller->stats();
            } elseif ($targetId && isset($_GET['export']) && $_GET['export'] === 'pdf') {
                require_once __DIR__ . '/../vendor/autoload.php';
                require_once __DIR__ . '/../app/services/ExportService.php';
                require_once __DIR__ . '/../app/Models/ActivityLog.php';

                $child = $childModel->find($targetId);
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
                $controller->show((string)$targetId);
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
                AuthorizationMiddleware::authorize(Permissions::IMMUNIZATION_CREATE, 'Immunization API Record Dose');

                // Validate CSRF token if session exists
                if (session_status() === PHP_SESSION_NONE && !headers_sent() && PHP_SAPI !== 'cli') {
                    @session_start();
                }
                $headerCsrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
                $bodyCsrf = $data['csrf_token'] ?? null;
                $csrfToken = $headerCsrf ?: $bodyCsrf;
                if (!empty($_SESSION['csrf_token']) && (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'], (string)$csrfToken))) {
                    Response::error('Invalid or missing CSRF token', 403);
                }

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

                    // Recalculate child's vaccine compliance score based on EPI antigen schedule
                    try {
                        $compliance = calculateVaccineCompliance($db, (int)$childId);
                        $db->update('children', [
                            'vaccine_compliance' => $compliance,
                            'last_visit'         => $dateAdministered
                        ], ['id' => (int)$childId]);
                    } catch (\Throwable $ce) {
                        error_log('Notice updating child vaccine compliance: ' . $ce->getMessage());
                    }

                    if (file_exists(__DIR__ . '/../app/Models/ActivityLog.php')) {
                        require_once __DIR__ . '/../app/Models/ActivityLog.php';
                        try {
                            $logger = new \ActivityLog();
                            $logger->log("Recorded Vaccination: {$vaccine} (Dose {$dose})", [
                                'module'  => 'Immunization & Nutrition',
                                'details' => "Child ID: {$childId} | Administered By: " . ($administeredBy ?? 'Staff') . " | Health Center: {$healthCenter}",
                                'status'  => 'Success'
                            ]);
                        } catch (\Throwable $le) {}
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
            $controller->update((string)$targetId);
            break;

        case 'DELETE':
            if (!$targetId) {
                Response::error('Child ID is required for deletion', 400);
            }
            $controller->destroy((string)$targetId);
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
}