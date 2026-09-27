<?php
date_default_timezone_set('Asia/Manila');
// api/drugs.php - Health Center Medicine Formulary & Inventory API

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

$storageDir = __DIR__ . '/../storage';
$storageFile = $storageDir . '/medicine_inventory.json';

if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

// Default base formulary drugs with realistic expiration dates & lot numbers
$defaultDrugs = [
    ['id' => 1, 'name' => 'Paracetamol', 'category' => 'Analgesic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 500, 'expiration_date' => '2027-11-30', 'batch_number' => 'LOT-2026-001', 'description' => 'For mild to moderate pain and fever'],
    ['id' => 2, 'name' => 'Paracetamol Syrup', 'category' => 'Analgesic', 'strength' => '15mg/kg', 'form' => 'Syrup', 'stock' => 100, 'expiration_date' => '2027-08-15', 'batch_number' => 'LOT-2026-002', 'description' => 'For children - fever and pain relief'],
    ['id' => 3, 'name' => 'Ibuprofen', 'category' => 'NSAID', 'strength' => '400mg', 'form' => 'Tablet', 'stock' => 300, 'expiration_date' => '2027-12-10', 'batch_number' => 'LOT-2026-003', 'description' => 'For pain, inflammation, and fever'],
    ['id' => 4, 'name' => 'Mefenamic Acid', 'category' => 'NSAID', 'strength' => '500mg', 'form' => 'Capsule', 'stock' => 150, 'expiration_date' => '2027-05-20', 'batch_number' => 'LOT-2026-004', 'description' => 'For menstrual pain and mild to moderate pain'],
    ['id' => 5, 'name' => 'Aspirin', 'category' => 'Antiplatelet', 'strength' => '81mg', 'form' => 'Tablet', 'stock' => 200, 'expiration_date' => '2028-01-15', 'batch_number' => 'LOT-2026-005', 'description' => 'For pain, fever, and prevention of blood clots'],
    ['id' => 6, 'name' => 'Amoxicillin', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Capsule', 'stock' => 100, 'expiration_date' => '2027-09-30', 'batch_number' => 'LOT-2026-006', 'description' => 'Broad-spectrum antibiotic for bacterial infections'],
    ['id' => 7, 'name' => 'Amoxicillin Syrup', 'category' => 'Antibiotic', 'strength' => '125mg/5ml', 'form' => 'Syrup', 'stock' => 80, 'expiration_date' => '2026-11-30', 'batch_number' => 'LOT-2026-007', 'description' => 'For children - bacterial infections'],
    ['id' => 8, 'name' => 'Azithromycin', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 80, 'expiration_date' => '2027-10-25', 'batch_number' => 'LOT-2026-008', 'description' => 'For respiratory and skin infections'],
    ['id' => 9, 'name' => 'Ciprofloxacin', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 70, 'expiration_date' => '2027-07-14', 'batch_number' => 'LOT-2026-009', 'description' => 'For urinary tract and respiratory infections'],
    ['id' => 10, 'name' => 'Doxycycline', 'category' => 'Antibiotic', 'strength' => '100mg', 'form' => 'Capsule', 'stock' => 60, 'expiration_date' => '2028-02-28', 'batch_number' => 'LOT-2026-010', 'description' => 'For bacterial infections and acne'],
    ['id' => 11, 'name' => 'Metronidazole', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 90, 'expiration_date' => '2027-04-18', 'batch_number' => 'LOT-2026-011', 'description' => 'For anaerobic bacterial and parasitic infections'],
    ['id' => 12, 'name' => 'Amlodipine', 'category' => 'Antihypertensive', 'strength' => '5mg', 'form' => 'Tablet', 'stock' => 150, 'expiration_date' => '2028-06-30', 'batch_number' => 'LOT-2026-012', 'description' => 'For high blood pressure and angina'],
    ['id' => 13, 'name' => 'Losartan', 'category' => 'Antihypertensive', 'strength' => '50mg', 'form' => 'Tablet', 'stock' => 120, 'expiration_date' => '2028-03-15', 'batch_number' => 'LOT-2026-013', 'description' => 'For high blood pressure and heart failure'],
    ['id' => 14, 'name' => 'Lisinopril', 'category' => 'Antihypertensive', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 100, 'expiration_date' => '2027-11-12', 'batch_number' => 'LOT-2026-014', 'description' => 'For high blood pressure and heart failure'],
    ['id' => 15, 'name' => 'Atorvastatin', 'category' => 'Statin', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 110, 'expiration_date' => '2028-04-20', 'batch_number' => 'LOT-2026-015', 'description' => 'For high cholesterol and prevention of heart disease'],
    ['id' => 16, 'name' => 'Propranolol', 'category' => 'Beta Blocker', 'strength' => '40mg', 'form' => 'Tablet', 'stock' => 100, 'expiration_date' => '2027-12-05', 'batch_number' => 'LOT-2026-016', 'description' => 'For high blood pressure, angina, and migraine prevention'],
    ['id' => 17, 'name' => 'Clopidogrel', 'category' => 'Antiplatelet', 'strength' => '75mg', 'form' => 'Tablet', 'stock' => 80, 'expiration_date' => '2028-05-10', 'batch_number' => 'LOT-2026-017', 'description' => 'For prevention of blood clots'],
    ['id' => 18, 'name' => 'Salbutamol', 'category' => 'Bronchodilator', 'strength' => '100mcg', 'form' => 'Inhaler', 'stock' => 80, 'expiration_date' => '2027-09-01', 'batch_number' => 'LOT-2026-018', 'description' => 'For asthma and COPD - quick relief'],
    ['id' => 19, 'name' => 'Salbutamol Syrup', 'category' => 'Bronchodilator', 'strength' => '2mg', 'form' => 'Syrup', 'stock' => 80, 'expiration_date' => '2027-06-15', 'batch_number' => 'LOT-2026-019', 'description' => 'For children - asthma and wheezing'],
    ['id' => 20, 'name' => 'Budesonide', 'category' => 'Corticosteroid', 'strength' => '200mcg', 'form' => 'Inhaler', 'stock' => 60, 'expiration_date' => '2028-01-30', 'batch_number' => 'LOT-2026-020', 'description' => 'For asthma - maintenance therapy'],
    ['id' => 21, 'name' => 'Montelukast', 'category' => 'Leukotriene Receptor Antagonist', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 70, 'expiration_date' => '2027-10-18', 'batch_number' => 'LOT-2026-021', 'description' => 'For asthma and allergic rhinitis'],
    ['id' => 22, 'name' => 'Bromhexine', 'category' => 'Mucolytic', 'strength' => '8mg', 'form' => 'Tablet', 'stock' => 120, 'expiration_date' => '2027-08-22', 'batch_number' => 'LOT-2026-022', 'description' => 'For cough with thick phlegm'],
    ['id' => 23, 'name' => 'Bromhexine Syrup', 'category' => 'Mucolytic', 'strength' => '4mg/5ml', 'form' => 'Syrup', 'stock' => 100, 'expiration_date' => '2027-05-14', 'batch_number' => 'LOT-2026-023', 'description' => 'For children - cough with phlegm'],
    ['id' => 24, 'name' => 'Prednisone', 'category' => 'Corticosteroid', 'strength' => '5mg', 'form' => 'Tablet', 'stock' => 90, 'expiration_date' => '2028-03-01', 'batch_number' => 'LOT-2026-024', 'description' => 'For severe allergic reactions and inflammation'],
    ['id' => 25, 'name' => 'Omeprazole', 'category' => 'PPI', 'strength' => '20mg', 'form' => 'Capsule', 'stock' => 90, 'expiration_date' => '2027-11-15', 'batch_number' => 'LOT-2026-025', 'description' => 'For acid reflux and peptic ulcer'],
    ['id' => 26, 'name' => 'Pantoprazole', 'category' => 'PPI', 'strength' => '40mg', 'form' => 'Tablet', 'stock' => 80, 'expiration_date' => '2028-02-12', 'batch_number' => 'LOT-2026-026', 'description' => 'For acid reflux and GERD'],
    ['id' => 27, 'name' => 'Loperamide', 'category' => 'Antidiarrheal', 'strength' => '2mg', 'form' => 'Capsule', 'stock' => 120, 'expiration_date' => '2027-12-31', 'batch_number' => 'LOT-2026-027', 'description' => 'For acute diarrhea'],
    ['id' => 28, 'name' => 'Domperidone', 'category' => 'Antiemetic', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 100, 'expiration_date' => '2027-09-09', 'batch_number' => 'LOT-2026-028', 'description' => 'For nausea, vomiting, and gastric motility'],
    ['id' => 29, 'name' => 'Metoclopramide', 'category' => 'Antiemetic', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 80, 'expiration_date' => '2028-01-20', 'batch_number' => 'LOT-2026-029', 'description' => 'For nausea and gastric motility disorders'],
    ['id' => 30, 'name' => 'Buscopan', 'category' => 'Antispasmodic', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 100, 'expiration_date' => '2027-10-10', 'batch_number' => 'LOT-2026-030', 'description' => 'For abdominal cramps and spasms'],
    ['id' => 31, 'name' => 'Cetirizine', 'category' => 'Antihistamine', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 250, 'expiration_date' => '2028-05-30', 'batch_number' => 'LOT-2026-031', 'description' => 'For allergic rhinitis and hives'],
    ['id' => 32, 'name' => 'Loratadine', 'category' => 'Antihistamine', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 200, 'expiration_date' => '2028-04-15', 'batch_number' => 'LOT-2026-032', 'description' => 'For allergic rhinitis and hives - non-drowsy'],
    ['id' => 33, 'name' => 'Cetirizine Syrup', 'category' => 'Antihistamine', 'strength' => '5mg/5ml', 'form' => 'Syrup', 'stock' => 150, 'expiration_date' => '2027-07-28', 'batch_number' => 'LOT-2026-033', 'description' => 'For children - allergies'],
    ['id' => 34, 'name' => 'Diphenhydramine', 'category' => 'Antihistamine', 'strength' => '25mg', 'form' => 'Tablet', 'stock' => 100, 'expiration_date' => '2027-11-01', 'batch_number' => 'LOT-2026-034', 'description' => 'For severe allergic reactions and insomnia'],
    ['id' => 35, 'name' => 'Metformin', 'category' => 'Antidiabetic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 200, 'expiration_date' => '2028-06-15', 'batch_number' => 'LOT-2026-035', 'description' => 'For type 2 diabetes'],
    ['id' => 36, 'name' => 'Gliclazide', 'category' => 'Antidiabetic', 'strength' => '30mg', 'form' => 'Tablet', 'stock' => 80, 'expiration_date' => '2027-12-01', 'batch_number' => 'LOT-2026-036', 'description' => 'For type 2 diabetes - sulfonylurea'],
    ['id' => 37, 'name' => 'Insulin', 'category' => 'Antidiabetic', 'strength' => '100IU/ml', 'form' => 'Injection', 'stock' => 50, 'expiration_date' => '2027-01-31', 'batch_number' => 'LOT-2026-037', 'description' => 'For type 1 diabetes and severe type 2'],
    ['id' => 38, 'name' => 'Multivitamins', 'category' => 'Supplement', 'strength' => 'Once daily', 'form' => 'Tablet', 'stock' => 300, 'expiration_date' => '2028-08-30', 'batch_number' => 'LOT-2026-038', 'description' => 'General health and wellness supplement'],
    ['id' => 39, 'name' => 'Folic Acid', 'category' => 'Supplement', 'strength' => '1mg', 'form' => 'Tablet', 'stock' => 250, 'expiration_date' => '2028-07-20', 'batch_number' => 'LOT-2026-039', 'description' => 'For pregnant women and anemia prevention'],
    ['id' => 40, 'name' => 'Ferrous Sulfate', 'category' => 'Supplement', 'strength' => '325mg', 'form' => 'Tablet', 'stock' => 180, 'expiration_date' => '2027-10-05', 'batch_number' => 'LOT-2026-040', 'description' => 'For iron deficiency anemia'],
    ['id' => 41, 'name' => 'Calcium Carbonate', 'category' => 'Supplement', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 150, 'expiration_date' => '2028-03-31', 'batch_number' => 'LOT-2026-041', 'description' => 'For bone health and calcium deficiency'],
    ['id' => 42, 'name' => 'Vitamin B Complex', 'category' => 'Supplement', 'strength' => 'Once daily', 'form' => 'Tablet', 'stock' => 200, 'expiration_date' => '2028-05-15', 'batch_number' => 'LOT-2026-042', 'description' => 'For energy and nervous system health'],
    ['id' => 43, 'name' => 'Vitamin C', 'category' => 'Supplement', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 250, 'expiration_date' => '2028-09-30', 'batch_number' => 'LOT-2026-043', 'description' => 'For immune system health'],
    ['id' => 44, 'name' => 'Prenatal Vitamins', 'category' => 'Supplement', 'strength' => 'Once daily', 'form' => 'Tablet', 'stock' => 200, 'expiration_date' => '2028-04-10', 'batch_number' => 'LOT-2026-044', 'description' => 'For pregnant women - essential nutrients'],
    ['id' => 45, 'name' => 'Escitalopram', 'category' => 'Antidepressant', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 60, 'expiration_date' => '2027-11-20', 'batch_number' => 'LOT-2026-045', 'description' => 'For depression and anxiety disorders'],
    ['id' => 46, 'name' => 'Sumatriptan', 'category' => 'Antimigraine', 'strength' => '50mg', 'form' => 'Tablet', 'stock' => 50, 'expiration_date' => '2027-08-30', 'batch_number' => 'LOT-2026-046', 'description' => 'For acute migraine attacks'],
    ['id' => 47, 'name' => 'Diazepam', 'category' => 'Anxiolytic', 'strength' => '5mg', 'form' => 'Tablet', 'stock' => 40, 'expiration_date' => '2027-06-25', 'batch_number' => 'LOT-2026-047', 'description' => 'For anxiety and muscle spasms - controlled substance'],
    ['id' => 48, 'name' => 'Levothyroxine', 'category' => 'Thyroid', 'strength' => '50mcg', 'form' => 'Tablet', 'stock' => 90, 'expiration_date' => '2028-01-10', 'batch_number' => 'LOT-2026-048', 'description' => 'For hypothyroidism - thyroid hormone replacement'],
    ['id' => 49, 'name' => 'Levothyroxine', 'category' => 'Thyroid', 'strength' => '100mcg', 'form' => 'Tablet', 'stock' => 70, 'expiration_date' => '2027-12-15', 'batch_number' => 'LOT-2026-049', 'description' => 'For hypothyroidism - higher dose'],
    ['id' => 50, 'name' => 'Allopurinol', 'category' => 'Antigout', 'strength' => '100mg', 'form' => 'Tablet', 'stock' => 80, 'expiration_date' => '2028-02-28', 'batch_number' => 'LOT-2026-050', 'description' => 'For gout and uric acid management'],
    ['id' => 51, 'name' => 'Hydrocortisone Cream', 'category' => 'Topical Corticosteroid', 'strength' => '1%', 'form' => 'Cream', 'stock' => 100, 'expiration_date' => '2027-09-15', 'batch_number' => 'LOT-2026-051', 'description' => 'For skin inflammation and allergic reactions'],
    ['id' => 52, 'name' => 'Silver Sulfadiazine', 'category' => 'Topical Antibiotic', 'strength' => '1%', 'form' => 'Cream', 'stock' => 60, 'expiration_date' => '2027-07-20', 'batch_number' => 'LOT-2026-052', 'description' => 'For burn wounds and skin infections'],
    ['id' => 53, 'name' => 'ORS', 'category' => 'Rehydration', 'strength' => '1 packet', 'form' => 'Powder', 'stock' => 500, 'expiration_date' => '2028-10-31', 'batch_number' => 'LOT-2026-053', 'description' => 'For diarrhea and dehydration']
];

function getStoredDrugs(string $file, array $default): array {
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $data = json_decode($raw, true);
        if (is_array($data) && !empty($data)) {
            return $data;
        }
    }
    // Initialize file with default data
    @file_put_contents($file, json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $default;
}

function saveStoredDrugs(string $file, array $data): bool {
    return (bool)@file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================================
// POST: ADD NEW MEDICINE (Doctor, Director, Dentist, Admin)
// ============================================================
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $postData = json_decode($rawInput, true) ?: $_POST;

    $userRole = strtolower(trim($_SESSION['role_description'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? ''));
    $isAuthorized = (
        empty($_SESSION['role']) || // Allow fallback if session auth handles at page level
        str_contains($userRole, 'doctor') ||
        str_contains($userRole, 'director') ||
        str_contains($userRole, 'dentist') ||
        str_contains($userRole, 'admin') ||
        str_contains($userRole, 'physician')
    );

    if (!$isAuthorized) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Doctor, Director, and Dentist can add medicines to inventory.']);
        exit;
    }

    $name = trim($postData['name'] ?? '');
    $category = trim($postData['category'] ?? ($postData['type'] ?? 'General'));
    $strength = trim($postData['strength'] ?? ($postData['grams'] ?? '500mg'));
    $form = trim($postData['form'] ?? 'Tablet');
    $stock = isset($postData['stock']) ? (int)$postData['stock'] : 100;
    $expDate = trim($postData['expiration_date'] ?? ($postData['expiry_date'] ?? ''));
    $batchNo = trim($postData['batch_number'] ?? ($postData['lot_no'] ?? ''));
    $description = trim($postData['description'] ?? '');

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Medicine name is required.']);
        exit;
    }

    if (empty($expDate)) {
        $expDate = date('Y-m-d', strtotime('+1 year'));
    }

    if (empty($batchNo)) {
        $batchNo = 'LOT-' . date('Y') . '-' . str_pad((string)rand(1, 999), 3, '0', STR_PAD_LEFT);
    }

    $currentDrugs = getStoredDrugs($storageFile, $defaultDrugs);

    $maxId = 0;
    foreach ($currentDrugs as $d) {
        if (isset($d['id']) && (int)$d['id'] > $maxId) {
            $maxId = (int)$d['id'];
        }
    }
    $newId = $maxId + 1;

    $newItem = [
        'id' => $newId,
        'name' => $name,
        'category' => $category,
        'strength' => $strength,
        'form' => $form,
        'stock' => $stock,
        'expiration_date' => $expDate,
        'batch_number' => $batchNo,
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s')
    ];

    array_unshift($currentDrugs, $newItem);
    saveStoredDrugs($storageFile, $currentDrugs);

    echo json_encode([
        'success' => true,
        'message' => 'Medicine successfully added to inventory.',
        'data' => $newItem
    ]);
    exit;
}

// ============================================================
// DELETE: DELETE MEDICINE FROM INVENTORY
// ============================================================
if ($method === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $targetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($targetId > 0) {
        $currentDrugs = getStoredDrugs($storageFile, $defaultDrugs);
        $filtered = array_filter($currentDrugs, fn($d) => (int)$d['id'] !== $targetId);
        saveStoredDrugs($storageFile, array_values($filtered));
        echo json_encode(['success' => true, 'message' => 'Medicine deleted successfully.']);
        exit;
    }
}

// ============================================================
// GET: FETCH DRUGS & FILTER BY CATEGORY, STRENGTH/GRAMS, SEARCH
// ============================================================
$drugs = getStoredDrugs($storageFile, $defaultDrugs);

// GET SINGLE DRUG BY ID
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$drugId = null;
if (count($parts) >= 3 && is_numeric($parts[2])) {
    $drugId = (int)$parts[2];
} elseif (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $drugId = (int)$_GET['id'];
}

if ($drugId) {
    $found = array_filter($drugs, fn($d) => (int)$d['id'] === $drugId);
    $found = array_values($found);
    if (!empty($found)) {
        echo json_encode(['success' => true, 'data' => $found[0]]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Medicine not found.']);
    }
    exit;
}

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? ($_GET['type'] ?? '');
$strength = $_GET['strength'] ?? ($_GET['grams'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

$results = $drugs;

if ($category) {
    $results = array_filter($results, function($d) use ($category) {
        return strtolower($d['category'] ?? '') === strtolower($category);
    });
    $results = array_values($results);
}

if ($strength) {
    $results = array_filter($results, function($d) use ($strength) {
        return strtolower($d['strength'] ?? '') === strtolower($strength);
    });
    $results = array_values($results);
}

if ($search) {
    $searchLower = strtolower($search);
    $results = array_filter($results, function($d) use ($searchLower) {
        return strpos(strtolower($d['name'] ?? ''), $searchLower) !== false ||
               strpos(strtolower($d['category'] ?? ''), $searchLower) !== false ||
               strpos(strtolower($d['strength'] ?? ''), $searchLower) !== false ||
               strpos(strtolower($d['batch_number'] ?? ''), $searchLower) !== false ||
               strpos(strtolower($d['description'] ?? ''), $searchLower) !== false;
    });
    $results = array_values($results);
}

if ($limit > 0) {
    $results = array_slice($results, 0, $limit);
}

// Get unique categories and strengths/grams
$categories = array_unique(array_filter(array_column($drugs, 'category')));
sort($categories);

$strengths = array_unique(array_filter(array_column($drugs, 'strength')));
sort($strengths);

echo json_encode([
    'success' => true,
    'data' => $results,
    'total' => count($results),
    'categories' => array_values($categories),
    'strengths' => array_values($strengths),
    'types' => array_values($categories),
    'grams' => array_values($strengths),
    'message' => ''
]);