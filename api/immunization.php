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

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $parts = explode('/', trim($path, '/'));

    // Find the position of this script in the URL path to handle base URLs correctly
    $scriptPos = array_search('immunization.php', $parts, true);
    
    $targetId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    switch ($method) {
        case 'GET':
            AuthorizationMiddleware::authorize(Permissions::IMMUNIZATION_VIEW, 'Immunization API View');

            if (isset($_GET['action']) && $_GET['action'] === 'referrals') {
                require_once __DIR__ . '/../app/Models/ImmunizationReferral.php';
                $referralModel = new ImmunizationReferral($db ?? Database::getInstance());
                $pending = $referralModel->getPending();
                Response::success('Pending immunization referrals retrieved', $pending);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'search_patients') {
                $db = Database::getInstance();
                $q = trim((string)($_GET['q'] ?? ''));
                $results = [];

                // 1. Search Health Center Services patients
                try {
                    $patients = $db->select('patients', [], [
                        'or' => "(first_name.ilike.%{$q}%,last_name.ilike.%{$q}%,patient_id.ilike.%{$q}%)",
                        'limit' => 10
                    ]);
                    foreach ($patients as $p) {
                        $age = !empty($p['date_of_birth']) ? (int)date_diff(date_create($p['date_of_birth']), date_create('today'))->y : 30;
                        $ptType = ($age >= 60) ? 'senior' : (($age < 18) ? 'child' : 'adult');
                        $results[] = [
                            'id'           => $p['id'],
                            'patient_id'   => $p['id'],
                            'child_id'     => null,
                            'source'       => 'health_center',
                            'patient_type' => $ptType,
                            'name'         => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
                            'code'         => $p['patient_id'] ?? "PAT-{$p['id']}",
                            'birth_date'   => $p['date_of_birth'] ?? '',
                            'gender'       => $p['gender'] ?? '',
                            'barangay'     => $p['barangay'] ?? ($p['address'] ?? '')
                        ];
                    }
                } catch (\Throwable $e) {}

                // 2. Search Children records
                try {
                    $children = $db->select('children', [], [
                        'or' => "(first_name.ilike.%{$q}%,last_name.ilike.%{$q}%,child_id.ilike.%{$q}%)",
                        'limit' => 10
                    ]);
                    foreach ($children as $c) {
                        $results[] = [
                            'id'           => $c['id'],
                            'patient_id'   => null,
                            'child_id'     => $c['id'],
                            'source'       => 'pediatric',
                            'patient_type' => 'child',
                            'name'         => trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
                            'code'         => $c['child_id'] ?? "CHD-{$c['id']}",
                            'birth_date'   => $c['birth_date'] ?? ($c['date_of_birth'] ?? ''),
                            'gender'       => $c['gender'] ?? '',
                            'barangay'     => $c['barangay'] ?? ''
                        ];
                    }
                } catch (\Throwable $e) {}

                Response::success('Patients retrieved', $results);
            } elseif (isset($_GET['patient_id']) && is_numeric($_GET['patient_id'])) {
                $pId = (int)$_GET['patient_id'];
                $db = Database::getInstance();
                $patient = $db->select('patients', ['id' => $pId]);
                if (empty($patient)) {
                    Response::error('Patient not found', 404);
                }
                $pData = $patient[0];
                try {
                    $pData['vaccinations'] = $db->select('immunizations', ['patient_id' => $pId], ['order' => 'date_administered.asc']);
                } catch (\Throwable $e) {
                    $pData['vaccinations'] = [];
                }
                Response::success('Patient retrieved with vaccinations', $pData);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'export_tcl') {
                require_once __DIR__ . '/../vendor/autoload.php';
                require_once __DIR__ . '/../app/services/ExportService.php';
                require_once __DIR__ . '/../app/Models/ActivityLog.php';

                $db = Database::getInstance();
                $format = strtolower($_GET['format'] ?? 'excel');
                $category = strtolower($_GET['category'] ?? 'all');
                $filterVaccine = trim((string)($_GET['vaccine'] ?? ''));

                $queryFilters = [];
                if (!empty($filterVaccine)) {
                    $queryFilters['vaccine'] = $filterVaccine;
                }
                $doses = [];
                try {
                    $doses = $db->select('immunizations', $queryFilters, ['order' => 'date_administered.desc', 'limit' => 2000]);
                } catch (\Throwable $e) {
                    $doses = [];
                }

                $childrenMap = [];
                try {
                    $allChildren = $db->select('children', [], ['limit' => 2000]);
                    foreach ($allChildren as $c) {
                        $childrenMap[$c['id']] = $c;
                    }
                } catch (\Throwable $e) {}

                $patientsMap = [];
                try {
                    $allPatients = $db->select('patients', [], ['limit' => 2000]);
                    foreach ($allPatients as $p) {
                        $patientsMap[$p['id']] = $p;
                    }
                } catch (\Throwable $e) {}

                $headers = [
                    'Record ID',
                    'Patient/Child ID',
                    'Patient Name',
                    'Category',
                    'Vaccine',
                    'Dose #',
                    'Date Administered',
                    'Next Due Date',
                    'Batch Number',
                    'Administered By',
                    'Health Center',
                    'Status'
                ];

                $rows = [];
                foreach ($doses as $d) {
                    $childId = $d['child_id'] ?? null;
                    $patientId = $d['patient_id'] ?? null;
                    $pType = $d['patient_type'] ?? (!empty($childId) ? 'child' : 'adult');

                    if ($category !== 'all' && strtolower($pType) !== $category) {
                        continue;
                    }

                    $patientName = 'Unknown';
                    $patientCode = '—';
                    if (!empty($childId) && isset($childrenMap[$childId])) {
                        $c = $childrenMap[$childId];
                        $patientName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
                        $patientCode = $c['child_id'] ?? "CH-{$childId}";
                    } elseif (!empty($patientId) && isset($patientsMap[$patientId])) {
                        $p = $patientsMap[$patientId];
                        $patientName = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                        $patientCode = $p['patient_id'] ?? "P-{$patientId}";
                    }

                    $rows[] = [
                        $d['id'] ?? '',
                        $patientCode,
                        $patientName,
                        ucfirst($pType),
                        $d['vaccine'] ?? '',
                        $d['dose'] ?? 1,
                        !empty($d['date_administered']) ? $d['date_administered'] : '—',
                        !empty($d['next_due_date']) ? $d['next_due_date'] : '—',
                        $d['batch_number'] ?? '—',
                        $d['administered_by'] ?? 'Staff',
                        $d['health_center'] ?? 'Caloocan Health Center',
                        ucfirst($d['status'] ?? 'completed')
                    ];
                }

                if (empty($rows)) {
                    $rows[] = ['—', '—', 'No vaccination records found matching criteria', '—', '—', '—', '—', '—', '—', '—', '—', '—'];
                }

                $stamp = date('Y-m-d');
                $title = "Immunization Target Client List (TCL) — {$stamp}";

                try {
                    $log = new ActivityLog();
                    $log->log("Exported Target Client List", [
                        'module'  => 'Immunization & Nutrition',
                        'format'  => $format,
                        'records' => count($rows)
                    ]);
                } catch (\Throwable $logEx) {}

                if ($format === 'csv') {
                    \App\Services\ExportService::toCsv(['headers' => $headers, 'rows' => $rows], "immunization_tcl_{$stamp}.csv");
                } elseif ($format === 'pdf') {
                    \App\Services\ExportService::toPdf(['headers' => $headers, 'rows' => $rows], $title, "immunization_tcl_{$stamp}.pdf");
                } else {
                    \App\Services\ExportService::toExcel(['headers' => $headers, 'rows' => $rows], $title, "immunization_tcl_{$stamp}.xlsx");
                }
            } elseif (isset($_GET['action']) && $_GET['action'] === 'export_child_masterlist') {
                require_once __DIR__ . '/../vendor/autoload.php';
                require_once __DIR__ . '/../app/services/ExportService.php';
                require_once __DIR__ . '/../app/Models/ActivityLog.php';

                $db = Database::getInstance();
                $format = strtolower($_GET['format'] ?? 'excel');
                $filterStatus = trim((string)($_GET['status'] ?? ''));
                $filterBarangay = trim((string)($_GET['barangay'] ?? ''));

                $queryFilters = [];
                if (!empty($filterStatus)) {
                    $queryFilters['status'] = $filterStatus;
                }
                if (!empty($filterBarangay)) {
                    $queryFilters['barangay'] = $filterBarangay;
                }

                $children = [];
                try {
                    $children = $db->select('children', $queryFilters, ['order' => 'last_name.asc,first_name.asc', 'limit' => 2000]);
                } catch (\Throwable $e) {
                    $children = [];
                }

                $headers = [
                    'Child ID',
                    'Full Name',
                    'Gender',
                    'Birth Date',
                    'Barangay',
                    'Mother Name',
                    'Father Name',
                    'Nutrition Status',
                    'Vaccine Compliance',
                    'Registration Date',
                    'Status'
                ];

                $rows = [];
                foreach ($children as $c) {
                    $rows[] = [
                        $c['child_id'] ?? ('CH-' . $c['id']),
                        trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')),
                        ucfirst($c['gender'] ?? '—'),
                        $c['birth_date'] ?? '—',
                        $c['barangay'] ?? '—',
                        $c['mother_name'] ?? '—',
                        $c['father_name'] ?? '—',
                        $c['nutrition_status'] ?? 'Normal',
                        isset($c['vaccine_compliance']) ? ($c['vaccine_compliance'] . '%') : '—',
                        $c['registration_date'] ?? '—',
                        ucfirst($c['status'] ?? 'Active')
                    ];
                }

                if (empty($rows)) {
                    $rows[] = ['—', 'No child records found', '—', '—', '—', '—', '—', '—', '—', '—', '—'];
                }

                $stamp = date('Y-m-d');
                $title = "Child Health & Nutrition Masterlist — {$stamp}";

                try {
                    $log = new ActivityLog();
                    $log->log("Exported Child Masterlist", [
                        'module'  => 'Immunization & Nutrition',
                        'format'  => $format,
                        'records' => count($rows)
                    ]);
                } catch (\Throwable $logEx) {}

                if ($format === 'csv') {
                    \App\Services\ExportService::toCsv(['headers' => $headers, 'rows' => $rows], "child_masterlist_{$stamp}.csv");
                } elseif ($format === 'pdf') {
                    \App\Services\ExportService::toPdf(['headers' => $headers, 'rows' => $rows], $title, "child_masterlist_{$stamp}.pdf");
                } else {
                    \App\Services\ExportService::toExcel(['headers' => $headers, 'rows' => $rows], $title, "child_masterlist_{$stamp}.xlsx");
                }
            } elseif (isset($_GET['stats'])) {
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
            // 1. Handle Doctor Consultation Referral Creation
            if (isset($_GET['action']) && in_array($_GET['action'], ['referral', 'create_referral'], true)) {
                $inputJson = json_decode(file_get_contents('php://input'), true);
                $data = is_array($inputJson) ? array_merge($_POST, $inputJson) : $_POST;

                $db = Database::getInstance();
                require_once __DIR__ . '/../app/Models/ImmunizationReferral.php';
                $referralModel = new ImmunizationReferral($db);

                $patientName = trim($data['patient_name'] ?? '');
                if (empty($patientName) && !empty($data['patient_id'])) {
                    try {
                        $p = $db->select('patients', ['id' => (int)$data['patient_id']]);
                        if (!empty($p)) {
                            $patientName = trim(($p[0]['first_name'] ?? '') . ' ' . ($p[0]['last_name'] ?? ''));
                        }
                    } catch (\Throwable $e) {}
                }
                if (empty($patientName) && !empty($data['child_id'])) {
                    try {
                        $c = $db->select('children', ['id' => (int)$data['child_id']]);
                        if (!empty($c)) {
                            $patientName = trim(($c[0]['first_name'] ?? '') . ' ' . ($c[0]['last_name'] ?? ''));
                        }
                    } catch (\Throwable $e) {}
                }
                if (empty($patientName)) {
                    $patientName = !empty($data['patient_id']) ? ('Patient #' . $data['patient_id']) : (!empty($data['child_id']) ? ('Child #' . $data['child_id']) : '');
                }

                $vaccineRequested = trim($data['vaccine_requested'] ?? $data['vaccine'] ?? '');

                if (empty($patientName) || empty($vaccineRequested)) {
                    Response::error('Patient Name and Requested Vaccine are required', 422);
                }

                $newReferral = $referralModel->create([
                    'patient_id'        => !empty($data['patient_id']) ? (int)$data['patient_id'] : null,
                    'child_id'          => !empty($data['child_id']) ? (int)$data['child_id'] : null,
                    'patient_name'      => $patientName,
                    'patient_type'      => $data['patient_type'] ?? (!empty($data['child_id']) ? 'child' : 'adult'),
                    'referred_by'       => $data['referred_by'] ?? ($_SESSION['employee_name'] ?? 'Doctor'),
                    'consultation_id'   => !empty($data['consultation_id']) ? (int)$data['consultation_id'] : null,
                    'vaccine_requested' => $vaccineRequested,
                    'urgency'           => $data['urgency'] ?? 'routine',
                    'notes'             => $data['notes'] ?? null,
                    'status'            => 'pending'
                ]);

                if (file_exists(__DIR__ . '/../app/Models/ActivityLog.php')) {
                    require_once __DIR__ . '/../app/Models/ActivityLog.php';
                    try {
                        $logger = new \ActivityLog();
                        $logger->log("Created Immunization Referral for {$patientName}", [
                            'module'  => 'Health Center Services',
                            'details' => "Vaccine: {$vaccineRequested} | Urgency: " . ($data['urgency'] ?? 'routine'),
                            'status'  => 'Success'
                        ]);
                    } catch (\Throwable $e) {}
                }

                Response::success('Immunization referral created successfully', $newReferral, 201);
            }

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
                
                // Resolve target patient (supports both child_id and patient_id)
                $childId = $data['child_id'] ?? null;
                $patientId = $data['patient_id'] ?? null;
                $patientType = $data['patient_type'] ?? (!empty($patientId) ? 'adult' : 'child');
                $patientName = trim($data['patient_name'] ?? $data['child_name'] ?? '');

                // Fallback resolution if patient ID was not explicitly given
                if (empty($childId) && empty($patientId) && !empty($patientName)) {
                    // 1. Try to find in Health Center Services patients
                    try {
                        $pSearch = $db->select('patients', [], [
                            'or' => "(patient_id.eq.{$patientName},first_name.ilike.%{$patientName}%,last_name.ilike.%{$patientName}%)",
                            'limit' => 1
                        ]);
                        if (!empty($pSearch)) {
                            $patientId = $pSearch[0]['id'];
                            $patientType = 'adult';
                        }
                    } catch (\Throwable $e) {}

                    // 2. If not found in adult patients, search children
                    if (empty($patientId)) {
                        try {
                            $cSearch = $db->select('children', [], [
                                'or' => "(child_id.eq.{$patientName},first_name.ilike.%{$patientName}%,last_name.ilike.%{$patientName}%)",
                                'limit' => 1
                            ]);
                            if (!empty($cSearch)) {
                                $childId = $cSearch[0]['id'];
                                $patientType = 'child';
                            } else {
                                // Create new child record as fallback
                                $nameParts = explode(' ', $patientName, 2);
                                $newChild = $childModel->create([
                                    'first_name' => $nameParts[0],
                                    'last_name' => $nameParts[1] ?? '',
                                    'status' => 'active',
                                    'vaccine_compliance' => 0
                                ]);
                                $childId = $newChild['id'] ?? ($newChild[0]['id'] ?? null);
                                $patientType = 'child';
                            }
                        } catch (\Throwable $e) {
                            error_log('Error looking up or creating child for vaccination: ' . $e->getMessage());
                        }
                    }
                }

                if (empty($childId) && empty($patientId)) {
                    Response::error('A valid Child or Adult/Senior Patient is required to record vaccination', 422);
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
                    'vaccine'           => $vaccine,
                    'dose'              => $dose,
                    'date_administered' => $dateAdministered,
                    'next_due_date'     => $nextDueDate,
                    'batch_number'      => $batchNumber,
                    'administered_by'   => $administeredBy,
                    'health_center'     => $healthCenter,
                    'notes'             => $notes
                ];

                if (!empty($childId)) {
                    $record['child_id'] = (int)$childId;
                }

                try {
                    $res = $db->insert('immunizations', $record);

                    // If linked to child, recalculate EPI compliance score
                    if (!empty($childId)) {
                        try {
                            $compliance = calculateVaccineCompliance($db, (int)$childId);
                            $db->update('children', [
                                'vaccine_compliance' => $compliance,
                                'last_visit'         => $dateAdministered
                            ], ['id' => (int)$childId]);
                        } catch (\Throwable $ce) {
                            error_log('Notice updating child vaccine compliance: ' . $ce->getMessage());
                        }
                    }

                    // Auto-complete referral if referral_id was provided
                    if (!empty($data['referral_id'])) {
                        try {
                            require_once __DIR__ . '/../app/Models/ImmunizationReferral.php';
                            $refModel = new ImmunizationReferral($db);
                            $refModel->complete((int)$data['referral_id']);
                        } catch (\Throwable $re) {}
                    }

                    // Deduct stock from vaccine inventory if available
                    try {
                        $invMatches = $db->select('vaccine_inventory', [], [
                            'vaccine_name' => "ilike.%{$vaccine}%",
                            'limit'        => 1
                        ]);
                        if (!empty($invMatches) && isset($invMatches[0]['id'])) {
                            $invItem = $invMatches[0];
                            $currQty = (int)($invItem['quantity'] ?? 0);
                            if ($currQty > 0) {
                                $newQty = $currQty - 1;
                                $db->update('vaccine_inventory', ['quantity' => $newQty], ['id' => $invItem['id']]);
                            }
                        }
                    } catch (\Throwable $ive) {}

                    if (file_exists(__DIR__ . '/../app/Models/ActivityLog.php')) {
                        require_once __DIR__ . '/../app/Models/ActivityLog.php';
                        try {
                            $logger = new \ActivityLog();
                            $targetDesc = !empty($patientId) ? "Patient ID: {$patientId} ({$patientType})" : "Child ID: {$childId}";
                            $logger->log("Recorded Vaccination: {$vaccine} (Dose {$dose})", [
                                'module'  => 'Immunization & Nutrition',
                                'details' => "{$targetDesc} | Administered By: " . ($administeredBy ?? 'Staff') . " | Health Center: {$healthCenter}",
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