<?php
// database/seeders/seed_growth_measurements.php
// Seeds comprehensive, realistic WHO-benchmarked longitudinal growth measurements
// for children in the database so that growth charts show complete trajectories.

require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/../../config/database.php';

echo "=== Seeding Growth Measurements for Children ===\n";

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

// Map children by child_id code and numeric id
$childCodeMap = [];
$childIdMap = [];
foreach ($children as $c) {
    if (!empty($c['child_id'])) {
        $childCodeMap[$c['child_id']] = (int)$c['id'];
    }
    $childIdMap[(int)$c['id']] = $c;
}

// Milestone templates (age in days -> expected parameters)
// We will generate longitudinal checkup data for each child based on DOB and gender
$milestonesMale = [
    ['days' => 0,   'w' => 3.30, 'h' => 50.0, 'hc' => 34.5, 'note' => 'Birth delivery baseline assessment. Vital signs stable, healthy newborn.'],
    ['days' => 43,  'w' => 4.65, 'h' => 55.2, 'hc' => 37.2, 'note' => '6-week routine immunization visit. Normal weight gain velocity, active feeding.'],
    ['days' => 71,  'w' => 5.60, 'h' => 59.0, 'hc' => 39.0, 'note' => '10-week wellness checkup. Social smile present, good head control.'],
    ['days' => 99,  'w' => 6.50, 'h' => 62.1, 'hc' => 40.5, 'note' => '14-week checkup. Excellent physical growth tracking on WHO 50th percentile.'],
    ['days' => 182, 'w' => 7.65, 'h' => 67.5, 'hc' => 43.0, 'note' => '6-month well-child assessment. Introduction of semi-solid complementary feeding.'],
    ['days' => 273, 'w' => 8.70, 'h' => 71.8, 'hc' => 44.6, 'note' => '9-month growth monitoring. Good muscle tone, transition to solids proceeding well.'],
    ['days' => 365, 'w' => 9.75, 'h' => 76.0, 'hc' => 46.0, 'note' => '12-month 1-year birthday milestone checkup! Walking with assistance, excellent growth.'],
    ['days' => 456, 'w' => 10.40,'h' => 79.5, 'hc' => 46.8, 'note' => '15-month toddler routine checkup. Active, vocal, steady progression on WHO trajectory.'],
    ['days' => 547, 'w' => 11.20,'h' => 83.0, 'hc' => 47.6, 'note' => '18-month routine wellness visit. Running, climbing, age-appropriate milestones.']
];

$milestonesFemale = [
    ['days' => 0,   'w' => 3.15, 'h' => 49.2, 'hc' => 33.8, 'note' => 'Birth delivery baseline assessment. Normal full-term newborn.'],
    ['days' => 43,  'w' => 4.35, 'h' => 54.5, 'hc' => 36.5, 'note' => '6-week checkup and EPI immunization visit. Sustained weight velocity.'],
    ['days' => 71,  'w' => 5.30, 'h' => 58.0, 'hc' => 38.4, 'note' => '10-week checkup. Responsive, tracking objects, good head control.'],
    ['days' => 99,  'w' => 6.10, 'h' => 61.2, 'hc' => 39.8, 'note' => '14-week assessment. Alert, tracking along WHO 50th percentile.'],
    ['days' => 182, 'w' => 7.25, 'h' => 66.0, 'hc' => 42.4, 'note' => '6-month well-child assessment. Introduction of semi-solid complementary foods.'],
    ['days' => 273, 'w' => 8.35, 'h' => 71.0, 'hc' => 44.4, 'note' => '9-month growth monitoring; transition to complementary feeding proceeding smoothly.'],
    ['days' => 365, 'w' => 9.15, 'h' => 75.0, 'hc' => 45.6, 'note' => '12-month 1-year milestone checkup! Responsive, crawling actively, normal growth.'],
    ['days' => 456, 'w' => 9.90, 'h' => 78.8, 'hc' => 46.4, 'note' => '15-month toddler checkup. Good dietary variety, walking steadily.'],
    ['days' => 547, 'w' => 10.70,'h' => 82.2, 'hc' => 47.2, 'note' => '18-month pediatric monitoring. Language development on track, thriving.']
];

$totalInserted = 0;
$totalSkipped = 0;
$now = new DateTime('2026-10-05');

foreach ($children as $c) {
    $cId = (int)$c['id'];
    $cCode = $c['child_id'] ?? "CH-{$cId}";
    $cName = trim(($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? ''));
    $gender = strtolower($c['gender'] ?? 'male') === 'female' ? 'female' : 'male';
    $birthDateStr = $c['birth_date'] ?? '2025-06-01';
    $birthDate = new DateTime($birthDateStr);

    $diffDays = (int)$now->diff($birthDate)->days;
    if ($birthDate > $now) $diffDays = 0;

    echo "\nProcessing Child #{$cId} ({$cCode} - {$cName}): Gender: {$gender}, DOB: {$birthDateStr}, Age: {$diffDays} days\n";

    // Fetch existing growth measurements for this child
    $existing = [];
    try {
        $existing = $db->select('growth_measurements', ['child_id' => $cId]);
    } catch (\Throwable $e) {}

    $existingDates = [];
    foreach ($existing as $ex) {
        $existingDates[substr($ex['measurement_date'], 0, 10)] = true;
    }

    $template = ($gender === 'female') ? $milestonesFemale : $milestonesMale;

    // Slight child-specific variation based on child ID to make trajectories distinct and natural
    $varSeed = ($cId % 5) - 2; // -2, -1, 0, 1, 2
    $wOffset = round($varSeed * 0.12, 2);
    $hOffset = round($varSeed * 0.4, 1);

    $childInserts = 0;

    foreach ($template as $m) {
        if ($m['days'] > $diffDays) {
            // Milestone is in the future
            continue;
        }

        $mDateObj = clone $birthDate;
        $mDateObj->modify("+{$m['days']} days");
        $mDateStr = $mDateObj->format('Y-m-d');

        if (isset($existingDates[$mDateStr])) {
            $totalSkipped++;
            continue;
        }

        $w = round(max(2.5, $m['w'] + $wOffset), 2);
        $h = round(max(45.0, $m['h'] + $hOffset), 1);
        $hc = round(max(32.0, $m['hc'] + ($varSeed * 0.15)), 1);

        $payload = [
            'child_id'           => $cId,
            'measurement_date'   => $mDateStr,
            'weight'             => $w,
            'height'             => $h,
            'head_circumference' => $hc,
            'notes'              => $m['note']
        ];

        try {
            $db->query('growth_measurements', 'POST', $payload, [], [], true);
            $totalInserted++;
            $childInserts++;
            $existingDates[$mDateStr] = true;
            echo "  + Inserted {$mDateStr}: Weight={$w}kg, Height={$h}cm, HC={$hc}cm\n";
        } catch (\Throwable $e) {
            echo "  ! Error inserting measurement for {$mDateStr}: " . $e->getMessage() . "\n";
        }
    }

    echo "  -> Added {$childInserts} measurement(s) for {$cName}.\n";
}

echo "\n============================================\n";
echo "Growth Measurements Seeding Complete!\n";
echo "Total Inserted: {$totalInserted}\n";
echo "Total Skipped:  {$totalSkipped}\n";
echo "============================================\n";
