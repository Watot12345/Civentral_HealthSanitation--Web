<?php
date_default_timezone_set('Asia/Manila');
// api/drugs.php - Complete Health Center Drug Formulary

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// ============================================================
// COMPLETE HEALTH CENTER DRUG FORMULARY
// Organized by categories with common health center drugs
// ============================================================
$drugs = [
    // ============================================================
    // 1. ANALGESICS & PAIN RELIEF
    // ============================================================
    ['id' => 1, 'name' => 'Paracetamol', 'category' => 'Analgesic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 500, 'description' => 'For mild to moderate pain and fever'],
    ['id' => 2, 'name' => 'Paracetamol Syrup', 'category' => 'Analgesic', 'strength' => '15mg/kg', 'form' => 'Syrup', 'stock' => 100, 'description' => 'For children - fever and pain relief'],
    ['id' => 3, 'name' => 'Ibuprofen', 'category' => 'NSAID', 'strength' => '400mg', 'form' => 'Tablet', 'stock' => 300, 'description' => 'For pain, inflammation, and fever'],
    ['id' => 4, 'name' => 'Mefenamic Acid', 'category' => 'NSAID', 'strength' => '500mg', 'form' => 'Capsule', 'stock' => 150, 'description' => 'For menstrual pain and mild to moderate pain'],
    ['id' => 5, 'name' => 'Aspirin', 'category' => 'Antiplatelet', 'strength' => '81mg', 'form' => 'Tablet', 'stock' => 200, 'description' => 'For pain, fever, and prevention of blood clots'],
    
    // ============================================================
    // 2. ANTIBIOTICS
    // ============================================================
    ['id' => 6, 'name' => 'Amoxicillin', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Capsule', 'stock' => 100, 'description' => 'Broad-spectrum antibiotic for bacterial infections'],
    ['id' => 7, 'name' => 'Amoxicillin Syrup', 'category' => 'Antibiotic', 'strength' => '125mg/5ml', 'form' => 'Syrup', 'stock' => 80, 'description' => 'For children - bacterial infections'],
    ['id' => 8, 'name' => 'Azithromycin', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 80, 'description' => 'For respiratory and skin infections'],
    ['id' => 9, 'name' => 'Ciprofloxacin', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 70, 'description' => 'For urinary tract and respiratory infections'],
    ['id' => 10, 'name' => 'Doxycycline', 'category' => 'Antibiotic', 'strength' => '100mg', 'form' => 'Capsule', 'stock' => 60, 'description' => 'For bacterial infections and acne'],
    ['id' => 11, 'name' => 'Metronidazole', 'category' => 'Antibiotic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 90, 'description' => 'For anaerobic bacterial and parasitic infections'],
    
    // ============================================================
    // 3. CARDIOVASCULAR
    // ============================================================
    ['id' => 12, 'name' => 'Amlodipine', 'category' => 'Antihypertensive', 'strength' => '5mg', 'form' => 'Tablet', 'stock' => 150, 'description' => 'For high blood pressure and angina'],
    ['id' => 13, 'name' => 'Losartan', 'category' => 'Antihypertensive', 'strength' => '50mg', 'form' => 'Tablet', 'stock' => 120, 'description' => 'For high blood pressure and heart failure'],
    ['id' => 14, 'name' => 'Lisinopril', 'category' => 'Antihypertensive', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 100, 'description' => 'For high blood pressure and heart failure'],
    ['id' => 15, 'name' => 'Atorvastatin', 'category' => 'Statin', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 110, 'description' => 'For high cholesterol and prevention of heart disease'],
    ['id' => 16, 'name' => 'Propranolol', 'category' => 'Beta Blocker', 'strength' => '40mg', 'form' => 'Tablet', 'stock' => 100, 'description' => 'For high blood pressure, angina, and migraine prevention'],
    ['id' => 17, 'name' => 'Clopidogrel', 'category' => 'Antiplatelet', 'strength' => '75mg', 'form' => 'Tablet', 'stock' => 80, 'description' => 'For prevention of blood clots'],
    
    // ============================================================
    // 4. RESPIRATORY
    // ============================================================
    ['id' => 18, 'name' => 'Salbutamol', 'category' => 'Bronchodilator', 'strength' => '100mcg', 'form' => 'Inhaler', 'stock' => 80, 'description' => 'For asthma and COPD - quick relief'],
    ['id' => 19, 'name' => 'Salbutamol Syrup', 'category' => 'Bronchodilator', 'strength' => '2mg', 'form' => 'Syrup', 'stock' => 80, 'description' => 'For children - asthma and wheezing'],
    ['id' => 20, 'name' => 'Budesonide', 'category' => 'Corticosteroid', 'strength' => '200mcg', 'form' => 'Inhaler', 'stock' => 60, 'description' => 'For asthma - maintenance therapy'],
    ['id' => 21, 'name' => 'Montelukast', 'category' => 'Leukotriene Receptor Antagonist', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 70, 'description' => 'For asthma and allergic rhinitis'],
    ['id' => 22, 'name' => 'Bromhexine', 'category' => 'Mucolytic', 'strength' => '8mg', 'form' => 'Tablet', 'stock' => 120, 'description' => 'For cough with thick phlegm'],
    ['id' => 23, 'name' => 'Bromhexine Syrup', 'category' => 'Mucolytic', 'strength' => '4mg/5ml', 'form' => 'Syrup', 'stock' => 100, 'description' => 'For children - cough with phlegm'],
    ['id' => 24, 'name' => 'Prednisone', 'category' => 'Corticosteroid', 'strength' => '5mg', 'form' => 'Tablet', 'stock' => 90, 'description' => 'For severe allergic reactions and inflammation'],
    
    // ============================================================
    // 5. GASTROINTESTINAL
    // ============================================================
    ['id' => 25, 'name' => 'Omeprazole', 'category' => 'PPI', 'strength' => '20mg', 'form' => 'Capsule', 'stock' => 90, 'description' => 'For acid reflux and peptic ulcer'],
    ['id' => 26, 'name' => 'Pantoprazole', 'category' => 'PPI', 'strength' => '40mg', 'form' => 'Tablet', 'stock' => 80, 'description' => 'For acid reflux and GERD'],
    ['id' => 27, 'name' => 'Loperamide', 'category' => 'Antidiarrheal', 'strength' => '2mg', 'form' => 'Capsule', 'stock' => 120, 'description' => 'For acute diarrhea'],
    ['id' => 28, 'name' => 'Domperidone', 'category' => 'Antiemetic', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 100, 'description' => 'For nausea, vomiting, and gastric motility'],
    ['id' => 29, 'name' => 'Metoclopramide', 'category' => 'Antiemetic', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 80, 'description' => 'For nausea and gastric motility disorders'],
    ['id' => 30, 'name' => 'Buscopan', 'category' => 'Antispasmodic', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 100, 'description' => 'For abdominal cramps and spasms'],
    
    // ============================================================
    // 6. ANTIHISTAMINES
    // ============================================================
    ['id' => 31, 'name' => 'Cetirizine', 'category' => 'Antihistamine', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 250, 'description' => 'For allergic rhinitis and hives'],
    ['id' => 32, 'name' => 'Loratadine', 'category' => 'Antihistamine', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 200, 'description' => 'For allergic rhinitis and hives - non-drowsy'],
    ['id' => 33, 'name' => 'Cetirizine Syrup', 'category' => 'Antihistamine', 'strength' => '5mg/5ml', 'form' => 'Syrup', 'stock' => 150, 'description' => 'For children - allergies'],
    ['id' => 34, 'name' => 'Diphenhydramine', 'category' => 'Antihistamine', 'strength' => '25mg', 'form' => 'Tablet', 'stock' => 100, 'description' => 'For severe allergic reactions and insomnia'],
    
    // ============================================================
    // 7. ANTIDIABETIC
    // ============================================================
    ['id' => 35, 'name' => 'Metformin', 'category' => 'Antidiabetic', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 200, 'description' => 'For type 2 diabetes'],
    ['id' => 36, 'name' => 'Gliclazide', 'category' => 'Antidiabetic', 'strength' => '30mg', 'form' => 'Tablet', 'stock' => 80, 'description' => 'For type 2 diabetes - sulfonylurea'],
    ['id' => 37, 'name' => 'Insulin', 'category' => 'Antidiabetic', 'strength' => '100IU/ml', 'form' => 'Injection', 'stock' => 50, 'description' => 'For type 1 diabetes and severe type 2'],
    
    // ============================================================
    // 8. VITAMINS & SUPPLEMENTS
    // ============================================================
    ['id' => 38, 'name' => 'Multivitamins', 'category' => 'Supplement', 'strength' => 'Once daily', 'form' => 'Tablet', 'stock' => 300, 'description' => 'General health and wellness supplement'],
    ['id' => 39, 'name' => 'Folic Acid', 'category' => 'Supplement', 'strength' => '1mg', 'form' => 'Tablet', 'stock' => 250, 'description' => 'For pregnant women and anemia prevention'],
    ['id' => 40, 'name' => 'Ferrous Sulfate', 'category' => 'Supplement', 'strength' => '325mg', 'form' => 'Tablet', 'stock' => 180, 'description' => 'For iron deficiency anemia'],
    ['id' => 41, 'name' => 'Calcium Carbonate', 'category' => 'Supplement', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 150, 'description' => 'For bone health and calcium deficiency'],
    ['id' => 42, 'name' => 'Vitamin B Complex', 'category' => 'Supplement', 'strength' => 'Once daily', 'form' => 'Tablet', 'stock' => 200, 'description' => 'For energy and nervous system health'],
    ['id' => 43, 'name' => 'Vitamin C', 'category' => 'Supplement', 'strength' => '500mg', 'form' => 'Tablet', 'stock' => 250, 'description' => 'For immune system health'],
    ['id' => 44, 'name' => 'Prenatal Vitamins', 'category' => 'Supplement', 'strength' => 'Once daily', 'form' => 'Tablet', 'stock' => 200, 'description' => 'For pregnant women - essential nutrients'],
    
    // ============================================================
    // 9. CENTRAL NERVOUS SYSTEM
    // ============================================================
    ['id' => 45, 'name' => 'Escitalopram', 'category' => 'Antidepressant', 'strength' => '10mg', 'form' => 'Tablet', 'stock' => 60, 'description' => 'For depression and anxiety disorders'],
    ['id' => 46, 'name' => 'Sumatriptan', 'category' => 'Antimigraine', 'strength' => '50mg', 'form' => 'Tablet', 'stock' => 50, 'description' => 'For acute migraine attacks'],
    ['id' => 47, 'name' => 'Diazepam', 'category' => 'Anxiolytic', 'strength' => '5mg', 'form' => 'Tablet', 'stock' => 40, 'description' => 'For anxiety and muscle spasms - controlled substance'],
    
    // ============================================================
    // 10. THYROID
    // ============================================================
    ['id' => 48, 'name' => 'Levothyroxine', 'category' => 'Thyroid', 'strength' => '50mcg', 'form' => 'Tablet', 'stock' => 90, 'description' => 'For hypothyroidism - thyroid hormone replacement'],
    ['id' => 49, 'name' => 'Levothyroxine', 'category' => 'Thyroid', 'strength' => '100mcg', 'form' => 'Tablet', 'stock' => 70, 'description' => 'For hypothyroidism - higher dose'],
    
    // ============================================================
    // 11. OTHERS
    // ============================================================
    ['id' => 50, 'name' => 'Allopurinol', 'category' => 'Antigout', 'strength' => '100mg', 'form' => 'Tablet', 'stock' => 80, 'description' => 'For gout and uric acid management'],
    ['id' => 51, 'name' => 'Hydrocortisone Cream', 'category' => 'Topical Corticosteroid', 'strength' => '1%', 'form' => 'Cream', 'stock' => 100, 'description' => 'For skin inflammation and allergic reactions'],
    ['id' => 52, 'name' => 'Silver Sulfadiazine', 'category' => 'Topical Antibiotic', 'strength' => '1%', 'form' => 'Cream', 'stock' => 60, 'description' => 'For burn wounds and skin infections'],
    ['id' => 53, 'name' => 'ORS', 'category' => 'Rehydration', 'strength' => '1 packet', 'form' => 'Powder', 'stock' => 500, 'description' => 'For diarrhea and dehydration'],
];

// ============================================================
// GET DRUG BY ID
// ============================================================
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$drugId = null;
if (count($parts) >= 3 && is_numeric($parts[2])) {
    $drugId = (int)$parts[2];
}

if ($drugId) {
    $drug = array_filter($drugs, function($d) use ($drugId) {
        return $d['id'] === $drugId;
    });
    $drug = array_values($drug);
    
    if (!empty($drug)) {
        echo json_encode(['success' => true, 'data' => $drug[0]]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Drug not found']);
    }
    exit;
}

// ============================================================
// HANDLE SEARCH, FILTER, AND CATEGORIES
// ============================================================
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;

$results = $drugs;

// Filter by category
if ($category) {
    $results = array_filter($results, function($drug) use ($category) {
        return strtolower($drug['category']) === strtolower($category);
    });
    $results = array_values($results);
}

// Search by name, category, or description
if ($search) {
    $searchLower = strtolower($search);
    $results = array_filter($results, function($drug) use ($searchLower) {
        return strpos(strtolower($drug['name']), $searchLower) !== false ||
               strpos(strtolower($drug['category']), $searchLower) !== false ||
               strpos(strtolower($drug['description'] ?? ''), $searchLower) !== false;
    });
    $results = array_values($results);
}

// Apply limit
if ($limit > 0) {
    $results = array_slice($results, 0, $limit);
}

// ============================================================
// GET ALL CATEGORIES (for filter dropdown)
// ============================================================
$getCategories = isset($_GET['categories']) && $_GET['categories'] === 'true';

if ($getCategories) {
    $categories = array_unique(array_column($drugs, 'category'));
    sort($categories);
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
    exit;
}

// ============================================================
// RETURN RESPONSE
// ============================================================
echo json_encode([
    'success' => true,
    'data' => $results,
    'total' => count($results),
    'categories' => array_unique(array_column($drugs, 'category')),
    'message' => ''
]);
?>