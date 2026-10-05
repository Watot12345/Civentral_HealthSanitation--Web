<?php
// database/seeders/seed_missing_immunizations.php
// Backfills realistic EPI vaccine dose records for children in the database

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../api/immunization.php';

echo "=== Seeding Vaccine Dose Records for Children ===\n";

$db = Database::getInstance();

try {
    $children = $db->select('children', [], ['order' => 'id.asc']);
} catch (\Throwable $e) {
    echo "Error fetching children: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($children)) {
    echo "No children found in database to seed.\n";
    exit(0);
}

$standardSchedule = [
    ['vaccine' => 'BCG', 'dose' => 1, 'age_days' => 1, 'batch' => 'BCG-2026-001', 'site' => 'Right deltoid'],
    ['vaccine' => 'Hepatitis B', 'dose' => 1, 'age_days' => 1, 'batch' => 'HEPB-2026-003', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 1, 'age_days' => 45, 'batch' => 'PENTA-2026-012', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 1, 'age_days' => 45, 'batch' => 'OPV-2026-008', 'site' => 'Oral drops'],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 1, 'age_days' => 45, 'batch' => 'PCV-2026-004', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 2, 'age_days' => 75, 'batch' => 'PENTA-2026-015', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 2, 'age_days' => 75, 'batch' => 'OPV-2026-011', 'site' => 'Oral drops'],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 2, 'age_days' => 75, 'batch' => 'PCV-2026-007', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Pentavalent (DPT-HepB-Hib)', 'dose' => 3, 'age_days' => 105, 'batch' => 'PENTA-2026-019', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Oral Polio Vaccine (OPV)', 'dose' => 3, 'age_days' => 105, 'batch' => 'OPV-2026-014', 'site' => 'Oral drops'],
    ['vaccine' => 'Inactivated Polio Vaccine (IPV)', 'dose' => 1, 'age_days' => 105, 'batch' => 'IPV-2026-002', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Pneumococcal Conjugate Vaccine (PCV)', 'dose' => 3, 'age_days' => 105, 'batch' => 'PCV-2026-010', 'site' => 'Anterolateral thigh'],
    ['vaccine' => 'Measles-Rubella (MR)', 'dose' => 1, 'age_days' => 270, 'batch' => 'MR-2026-005', 'site' => 'Subcutaneous arm'],
    ['vaccine' => 'Measles-Mumps-Rubella (MMR)', 'dose' => 2, 'age_days' => 365, 'batch' => 'MMR-2026-008', 'site' => 'Subcutaneous arm'],
];

$totalInserted = 0;

foreach ($children as $c) {
    $cId = (int)$c['id'];
    $cName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
    $birthDateStr = $c['birth_date'] ?? '2025-06-01';
    $birthDate = new DateTime($birthDateStr);
    $today = new DateTime();
    $ageDays = max(1, (int)$today->diff($birthDate)->days);

    echo "Checking Child #{$cId}: {$cName} (Birth: {$birthDateStr}, Age: {$ageDays} days)...\n";

    // Fetch existing doses
    $existing = [];
    try {
        $existing = $db->select('immunizations', ['child_id' => $cId]);
    } catch (\Throwable $e) {}

    $existingKeys = [];
    foreach ($existing as $ex) {
        $vName = strtolower(trim((string)($ex['vaccine'] ?? '')));
        $dNum = (int)($ex['dose'] ?? 1);
        $existingKeys[$vName . '|' . $dNum] = true;
    }

    echo "  Existing dose count: " . count($existing) . "\n";

    // Set clinically realistic target doses based on child's age and health status
    // Children over 9-12 months with good health will have Green compliance (12-14 doses: 86%-100%)
    if ($ageDays >= 365) {
        // Toddlers 1 yr+ : 13 or 14 doses (93% - 100% Fully Immunized)
        $targetDoseCount = ($cId % 2 === 0) ? 14 : 13;
    } elseif ($ageDays >= 270) {
        // Infants 9-11 months: 12 or 13 doses (86% - 93% high compliance)
        $targetDoseCount = ($cId % 3 === 0) ? 14 : (($cId % 2 === 0) ? 13 : 12);
    } elseif ($ageDays >= 180) {
        // Infants 6-8 months: 8 to 11 doses (57% - 79% on track)
        $targetDoseCount = 8 + ($cId % 4);
    } else {
        // Early infants: 3 to 5 doses
        $targetDoseCount = min(5, max(2, (int)round(($ageDays / 45) * 3)));
    }

    $childInserted = 0;
    for ($i = 0; $i < $targetDoseCount; $i++) {
        $item = $standardSchedule[$i];
        $vKey = strtolower(trim($item['vaccine'])) . '|' . $item['dose'];

        if (isset($existingKeys[$vKey])) {
            continue; // Already administered
        }

        // Calculate administered date based on birth date + age_days
        $admDate = clone $birthDate;
        $admDate->modify('+' . $item['age_days'] . ' days');
        if ($admDate > $today) {
            $admDate = clone $today;
        }

        $nextDueDate = null;
        if ($i + 1 < 14) {
            $nextItem = $standardSchedule[$i + 1];
            $nextDate = clone $birthDate;
            $nextDate->modify('+' . $nextItem['age_days'] . ' days');
            $nextDueDate = $nextDate->format('Y-m-d');
        }

        $newRecord = [
            'child_id'          => $cId,
            'vaccine'           => $item['vaccine'],
            'dose'              => $item['dose'],
            'date_administered' => $admDate->format('Y-m-d'),
            'next_due_date'     => $nextDueDate,
            'batch_number'      => $item['batch'],
            'administered_by'   => 'Sarah Cruz (Midwife)',
            'health_center'     => $c['health_center'] ?? 'Bagong Barrio Health Center',
            'notes'             => "Administered at {$item['site']} during routine Barangay EPI Bakuna Day."
        ];

        try {
            $db->insert('immunizations', $newRecord);
            $childInserted++;
            $totalInserted++;
        } catch (\Throwable $e) {
            echo "    Failed inserting {$item['vaccine']} dose {$item['dose']}: " . $e->getMessage() . "\n";
        }
    }

    // Recalculate and update exact compliance in children table
    try {
        $newCompliance = calculateVaccineCompliance($db, $cId);
        $db->update('children', [
            'vaccine_compliance' => $newCompliance,
            'last_visit'         => date('Y-m-d')
        ], ['id' => $cId]);
        echo "  -> Added {$childInserted} new doses. Updated Compliance: {$newCompliance}%\n";
    } catch (\Throwable $e) {
        echo "  -> Notice updating compliance: " . $e->getMessage() . "\n";
    }
}

echo "=== Complete! Total vaccine doses inserted: {$totalInserted} ===\n";
