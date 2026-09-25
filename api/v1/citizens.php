<?php
// api/v1/citizens.php
// Microservice Simulation: Master Citizen Registry API (Core Integration 1)

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$citizenId = trim($_GET['citizen_id'] ?? $_GET['id'] ?? '');

// Parse URI if routed like /api/v1/citizens/CTZN-PH-2026-008912
if (empty($citizenId)) {
    $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (preg_match('#/api/v1/citizens/([A-Za-z0-9\-]+)#', $uriPath, $matches)) {
        $citizenId = $matches[1];
    }
}

if (empty($citizenId)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required parameter: citizen_id'
    ]);
    exit;
}

// Mock Master Citizen Registry Database
$citizenRegistryDatabase = [
    'CTZN-PH-2026-008912' => [
        'citizen_id' => 'CTZN-PH-2026-008912',
        'first_name' => 'Althea',
        'middle_name' => 'Santos',
        'last_name' => 'Cruz',
        'birth_date' => '2024-05-14',
        'gender' => 'Female',
        'blood_type' => 'O+',
        'contact_number' => '639368587433',
        'address' => '124 Rizal Ave, Barangay Poblacion 1',
        'guardian_name' => 'Elena Santos Cruz',
        'civil_status' => 'Child'
    ],
    'CTZN-PH-2026-001234' => [
        'citizen_id' => 'CTZN-PH-2026-001234',
        'first_name' => 'Juan',
        'middle_name' => 'Bautista',
        'last_name' => 'Dela Cruz',
        'birth_date' => '1995-10-24',
        'gender' => 'Male',
        'blood_type' => 'A+',
        'contact_number' => '639171234567',
        'address' => '45 Mabini St, Barangay San Jose',
        'guardian_name' => 'N/A',
        'civil_status' => 'Married'
    ],
    'CTZN-PH-2026-005678' => [
        'citizen_id' => 'CTZN-PH-2026-005678',
        'first_name' => 'Maria Clara',
        'middle_name' => 'De Los Santos',
        'last_name' => 'Reyes',
        'birth_date' => '1998-03-12',
        'gender' => 'Female',
        'blood_type' => 'B+',
        'contact_number' => '639209876543',
        'address' => '88 Bonifacio Dr, Barangay Central',
        'guardian_name' => 'N/A',
        'civil_status' => 'Single'
    ]
];

if (isset($citizenRegistryDatabase[$citizenId])) {
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $citizenRegistryDatabase[$citizenId],
        'simulated_microservice' => 'Master Citizen Registry (Tier 1 Core)'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    // Generate dynamic fallback for demo query flexibility
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => [
            'citizen_id' => $citizenId,
            'first_name' => 'Simulated Resident',
            'middle_name' => 'M.',
            'last_name' => 'Sample',
            'birth_date' => '2000-01-01',
            'gender' => 'Male',
            'blood_type' => 'O+',
            'contact_number' => '639000000000',
            'address' => 'Block 5 Lot 12, LGU Resettlement Area',
            'guardian_name' => 'N/A',
            'civil_status' => 'Single'
        ],
        'simulated_microservice' => 'Master Citizen Registry (Tier 1 Dynamic Mock)'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
