<?php
// tests/policies_test.php
// Dependency-free tests for the policy layer. Run:  php tests/policies_test.php
// Policies are pure (no DB/session/HTTP), so no fixtures or mocks are needed.

require_once __DIR__ . '/../app/Policies/InspectionPolicy.php';
require_once __DIR__ . '/../app/Policies/SepticTankPolicy.php';
require_once __DIR__ . '/../app/Policies/SepticSiphoningPolicy.php';

use App\Policies\InspectionPolicy as IP;
use App\Policies\SepticTankPolicy as SP;
use App\Policies\SepticSiphoningPolicy as SSP;

$passed = 0;
$failed = [];

function check(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
    } else {
        $failed[] = $name;
    }
}

$admin     = ['role_text' => 'system administrator', 'user_id' => 1, 'employee_id' => 1];
$director  = ['role_text' => 'sanitation director',  'user_id' => 2, 'employee_id' => 2];
$inspector = ['role_text' => 'inspector',            'user_id' => 10, 'employee_id' => 10];
$sanInsp   = ['role_text' => 'sanitation inspector', 'user_id' => 10, 'employee_id' => 10];
$clerk     = ['role_text' => 'permit clerk',         'user_id' => 11, 'employee_id' => 11];
$nobody    = ['role_text' => '',                     'user_id' => 99, 'employee_id' => 99];
$today = '2026-10-17';

// ---- Actor classification ---------------------------------------------
check('admin is management', IP::isManagement($admin));
check('director is management', IP::isManagement($director));
check('inspector is not management', !IP::isManagement($inspector));
check('clerk can view all rows', IP::canViewAll($clerk));
check('clerk cannot schedule', !IP::isManagement($clerk));
check('inspector cannot view all', !IP::canViewAll($inspector));
check('"sanitation inspector" is still scoped to own work', !IP::canViewAll($sanInsp));
check('empty role fails closed', !IP::canViewAll($nobody));

// ---- Access -------------------------------------------------------------
$mine   = ['inspector_id' => 10, 'status' => 'scheduled'];
$theirs = ['inspector_id' => 55, 'status' => 'scheduled'];
$done   = ['inspector_id' => 10, 'status' => 'completed'];
check('inspector can access own scheduled', IP::canAccess($inspector, $mine));
check('inspector cannot access others', !IP::canAccess($inspector, $theirs));
check('inspector cannot access own completed', !IP::canAccess($inspector, $done));
check('director accesses anything', IP::canAccess($director, $theirs));

// ---- Scheduling ---------------------------------------------------------
$paid   = ['id' => 5, 'paid' => true];
$unpaid = ['id' => 5, 'paid' => false];
check('director schedules paid permit', IP::canSchedule($director, $paid, false, '2026-10-20', $today)->allowed);
check('same-day scheduling allowed', IP::canSchedule($director, $paid, false, $today, $today)->allowed);
check('inspector cannot schedule', IP::canSchedule($inspector, $paid, false, '2026-10-20', $today)->code === 403);
check('missing permit is 404', IP::canSchedule($director, null, false, '2026-10-20', $today)->code === 404);
check('unpaid permit blocked', IP::canSchedule($director, $unpaid, false, '2026-10-20', $today)->code === 400);
check('past date blocked', IP::canSchedule($director, $paid, false, '2026-10-01', $today)->code === 422);
check('garbage date blocked', IP::canSchedule($director, $paid, false, '2026-02-31', $today)->code === 422);
check('open inspection blocks duplicate', IP::canSchedule($director, $paid, true, '2026-10-20', $today)->code === 409);

// ---- Conduct / update / status / delete ---------------------------------
check('inspector conducts own', IP::canConduct($inspector, $mine)->allowed);
check('inspector cannot conduct others', IP::canConduct($inspector, $theirs)->denied());
check('cannot conduct completed', IP::canConduct($director, $done)->denied());
check('cannot edit completed', IP::canUpdate($director, $done)->code === 409);
check('inspector cannot edit schedule', IP::canUpdate($inspector, $mine)->denied());
check('director cancels scheduled', IP::canChangeStatus($director, $mine, 'cancelled')->allowed);
check('cannot complete via status endpoint', IP::canChangeStatus($director, $mine, 'completed')->denied());
check('cannot reopen completed', IP::canChangeStatus($director, $done, 'scheduled')->code === 409);
check('invalid status rejected', IP::canChangeStatus($director, $mine, 'bogus')->code === 400);
check('scheduled inspection deletable by director', IP::canDelete($director, $mine)->allowed);
check('completed inspection never deletable', IP::canDelete($admin, $done)->code === 409);
check('inspector cannot delete', IP::canDelete($inspector, $mine)->denied());

// ---- Report validation --------------------------------------------------
check('missing outcome rejected', IP::validateReport([], $today)->code === 422);
check('unknown outcome rejected', IP::validateReport(['overall_status' => 'great'], $today)->denied());
check('compliant passes', IP::validateReport(['overall_status' => 'compliant'], $today)->allowed);
check('partial needs follow-up', IP::validateReport(['overall_status' => 'partially_compliant'], $today)->denied());
check('partial with future follow-up passes', IP::validateReport(['overall_status' => 'partially_compliant', 'follow_up_date' => '2026-11-01'], $today)->allowed);
check('partial with past follow-up rejected', IP::validateReport(['overall_status' => 'partially_compliant', 'follow_up_date' => '2026-01-01'], $today)->denied());
check('non-compliant needs reason', IP::validateReport(['overall_status' => 'non_compliant'], $today)->denied());
check('non-compliant with reason passes', IP::validateReport(['overall_status' => 'non_compliant', 'recommendations' => '[REJECTION CRITERIA: x]'], $today)->allowed);
check('outcome is case-insensitive', IP::validateReport(['overall_status' => ' COMPLIANT '], $today)->allowed);
check('permit status: compliant', IP::permitStatusFor('compliant') === 'approved');
check('permit status: non-compliant', IP::permitStatusFor('non_compliant') === 'rejected');
check('permit status: partial', IP::permitStatusFor('partially_compliant') === 'under_review');
check('denial maps to response shape', IP::canDelete($inspector, $mine)->toResponse()['success'] === false);

// ---- Septic tank validation --------------------------------------------
$now = new DateTimeImmutable('2026-10-17');
$goodTank = ['owner_name' => 'A', 'address' => 'B', 'capacity' => '1200L', 'type' => 'Concrete',
             'installation_year' => 2020, 'maintenance_frequency' => 12, 'status' => 'good'];
check('valid tank passes', SP::validate($goodTank, false, $now)->allowed);
check('owner required on create', SP::validate(['address' => 'x'], false, $now)->denied());
check('address required on create', SP::validate(['owner_name' => 'x'], false, $now)->denied());
check('partial update without owner passes', SP::validate(['notes' => 'hi'], true, $now)->allowed);
check('blank owner on update rejected', SP::validate(['owner_name' => ' '], true, $now)->denied());
check('unknown type rejected', SP::validate(['type' => 'Brick'], true, $now)->denied());
check('type is case-insensitive', SP::validate(['type' => 'concrete'], true, $now)->allowed);
check('canonicalType normalises', SP::canonicalType('fiberglass') === 'Fiberglass');
check('"Other" type allowed (matches DB CHECK)', SP::validate(['type' => 'Other'], true, $now)->allowed);
check('unknown status rejected', SP::validate(['status' => 'broken'], true, $now)->denied());
check('decommissioned status allowed', SP::validate(['status' => 'decommissioned'], true, $now)->allowed);
check('capacity "4000 Liters" ok', SP::validate(['capacity' => '4000 Liters'], true, $now)->allowed);
check('capacity zero rejected', SP::validate(['capacity' => '0L'], true, $now)->denied());
check('capacity text rejected', SP::validate(['capacity' => 'big'], true, $now)->denied());
check('capacity m3 converts', SP::capacityLiters('2.5 m3') === 2500.0);
check('frequency 36 months ok (seed data)', SP::validate(['maintenance_frequency' => 36], true, $now)->allowed);
check('frequency 0 rejected', SP::validate(['maintenance_frequency' => 0], true, $now)->denied());
check('frequency 999 rejected', SP::validate(['maintenance_frequency' => 999], true, $now)->denied());
check('future installation year rejected', SP::validate(['installation_year' => 2099], true, $now)->denied());
check('future last_maintenance rejected', SP::validate(['last_maintenance' => '2027-01-01'], true, $now)->denied());
check('bad latitude rejected', SP::validate(['latitude' => 120], true, $now)->denied());
check('error map carries field names', isset(SP::validate(['type' => 'Brick'], true, $now)->errors['type']));

// ---- Septic tank maintenance evaluation --------------------------------
$fresh = SP::evaluateMaintenance(['last_maintenance' => '2026-09-01', 'maintenance_frequency' => 12, 'status' => 'good'], $now);
check('recent service is good', $fresh['status'] === 'good' && $fresh['next_due'] === '2027-09-01');
$dueSoon = SP::evaluateMaintenance(['last_maintenance' => '2025-11-01', 'maintenance_frequency' => 12, 'status' => 'good'], $now);
check('due within 30 days flags due_soon', $dueSoon['status'] === 'good' && $dueSoon['due_soon'] === true);
$overdue = SP::evaluateMaintenance(['last_maintenance' => '2025-06-01', 'maintenance_frequency' => 12, 'status' => 'good'], $now);
check('past due becomes needs_maintenance', $overdue['status'] === 'needs_maintenance' && $overdue['days_overdue'] > 0);
$veryOverdue = SP::evaluateMaintenance(['last_maintenance' => '2024-01-01', 'maintenance_frequency' => 12, 'status' => 'good'], $now);
check('long overdue becomes critical', $veryOverdue['status'] === 'critical');
$manualCritical = SP::evaluateMaintenance(['last_maintenance' => '2026-09-01', 'maintenance_frequency' => 12, 'status' => 'critical'], $now);
check('manual critical never downgraded', $manualCritical['status'] === 'critical');
$decom = SP::evaluateMaintenance(['last_maintenance' => '2010-01-01', 'status' => 'decommissioned'], $now);
check('decommissioned stays decommissioned', $decom['status'] === 'decommissioned' && $decom['next_due'] === null);
$noFreq = SP::evaluateMaintenance(['last_maintenance' => '2026-09-01', 'status' => 'good'], $now);
check('missing frequency uses default', $noFreq['interval_months'] === SP::DEFAULT_INTERVAL_MONTHS);
$noHistory = SP::evaluateMaintenance(['status' => 'good'], $now);
check('no history at all needs maintenance', $noHistory['status'] === 'needs_maintenance' && $noHistory['basis'] === 'no_history');
$fromCreated = SP::evaluateMaintenance(['created_at' => '2026-08-01T00:00:00+08:00', 'maintenance_frequency' => 12, 'status' => 'good'], $now);
check('falls back to created_at', $fromCreated['basis'] === 'created_at' && $fromCreated['status'] === 'good');
$seed36 = SP::evaluateMaintenance(['last_maintenance' => '2025-11-15', 'maintenance_frequency' => 36, 'status' => 'good'], $now);
check('36-month seed tank is good', $seed36['status'] === 'good');

$applied = SP::applyMaintenancePolicy([
    ['id' => 1, 'status' => 'good', 'last_maintenance' => '2024-01-01', 'maintenance_frequency' => 12],
    ['id' => 2, 'status' => 'good', 'last_maintenance' => '2026-09-01', 'maintenance_frequency' => 12],
    ['id' => 3, 'status' => 'decommissioned'],
], $now);
check('apply keeps stored status', $applied[0]['stored_status'] === 'good' && $applied[0]['status'] === 'critical');
$summary = SP::summarize($applied);
check('summary counts effective status', $summary['critical'] === 1 && $summary['good'] === 1 && $summary['decommissioned'] === 1 && $summary['total'] === 3);

// ---- Service requests ---------------------------------------------------
$tank = ['tank_id' => 'ST-1', 'status' => 'good'];
check('no active request allowed', SP::canRequestService($tank, null)->allowed);
check('unregistered/no tank allowed', SP::canRequestService(null, null)->allowed);
$dup = SP::canRequestService($tank, ['request_id' => 'REQ-9', 'preferred_date' => '2026-10-20']);
check('active request blocked 409', $dup->code === 409 && str_contains($dup->message, 'REQ-9') && str_contains($dup->message, 'ST-1'));
check('decommissioned tank blocked', SP::canRequestService(['tank_id' => 'ST-2', 'status' => 'decommissioned'], null)->code === 409);
// ---- Siphoning policy & standards (Panel guidelines) ---------------------
check('5+ occupants gets 3 years (36 mos)', SP::recommendedSiphoningIntervalMonths(5) === 36);
check('8 occupants gets 3 years (36 mos)', SP::recommendedSiphoningIntervalMonths(8) === 36);
check('4 occupants gets 5 years (60 mos)', SP::recommendedSiphoningIntervalMonths(4) === 60);
check('2 occupants gets 5 years (60 mos)', SP::recommendedSiphoningIntervalMonths(2) === 60);
check('1 occupant gets 5 years (60 mos)', SP::recommendedSiphoningIntervalMonths(1) === 60);

$routineSiphon = SP::evaluateSiphoningSchedule(3, '2025-01-01', [], $now);
check('routine siphoning is scheduled', $routineSiphon['status'] === 'scheduled' && !$routineSiphon['is_emergency']);
check('3 occupants interval is 60 months', $routineSiphon['interval_months'] === 60);

$emergencySiphon = SP::evaluateSiphoningSchedule(2, '2026-09-01', ['slow_drain', 'backflow'], $now);
check('slow drain & backflow triggers siphon_now', $emergencySiphon['status'] === 'siphon_now' && $emergencySiphon['is_emergency']);
check('emergency lists detected signs', count($emergencySiphon['detected_signs']) === 2);

$odorOnly = SP::evaluateSiphoningSchedule(6, '2026-08-01', ['foul_odor'], $now);
check('foul odor alone triggers siphon_now', $odorOnly['status'] === 'siphon_now');
check('6 occupants uses 36 months', $odorOnly['interval_months'] === 36);

$overdueSiphon = SP::evaluateSiphoningSchedule(5, '2020-01-01', [], $now);
check('old siphoning triggers overdue', $overdueSiphon['status'] === 'overdue' && $overdueSiphon['is_overdue']);

$guidelines = SP::getSiphoningGuidelines();
check('guidelines has part 1 schedule', isset($guidelines['section_1_schedule']['standard_schedule']));
check('guidelines has Maynilad 1626', $guidelines['section_1_schedule']['hotlines']['maynilad']['hotline'] === '1626');
check('guidelines has Manila Water 1627', $guidelines['section_1_schedule']['hotlines']['manila_water']['hotline'] === '1627');
check('guidelines has 3 critical signs', count($guidelines['section_2_emergency_signs']['signs']) === 3);
check('guidelines has DENR accreditation requirement', str_contains($guidelines['section_3_legality']['mandatory_accredited'], 'DENR-accredited'));
check('guidelines has private cost range P3,500 - P6,000', $guidelines['section_3_legality']['private_cost_range']['min_php'] === 3500 && $guidelines['section_3_legality']['private_cost_range']['max_php'] === 6000);

check('SSP 5 occupants gives 3 years', SSP::recommendedFrequencyYears(5) === 3);
check('SSP 3 occupants gives 5 years', SSP::recommendedFrequencyYears(3) === 5);
check('SSP household rule mentions 3 years for 5+', str_contains(SSP::getHouseholdRule(6), 'every 3 years'));
check('SSP diagnostic empty gives no emergency', !SSP::evaluateDiagnostics([])['is_emergency']);
check('SSP diagnostic slow_drain gives emergency', SSP::evaluateDiagnostics(['slow_drain'])['is_emergency']);
check('SSP diagnostic backflow gives critical severity', SSP::evaluateDiagnostics(['backflow'])['severity'] === 'critical');

$details = SSP::getPolicyDetails();
check('SSP details contains statutory framework', isset($details['framework']['statutes']['PD 856']));
check('SSP details contains Clean Water Act', isset($details['framework']['statutes']['RA 9275']));
check('SSP details title matches requirement', $details['title'] === 'Septic Tank Siphoning Policy');

// ---- State Machine Lifecycle (>90d OK, <=90d DUE_SOON, past due OVERDUE) --
// 1. > 90 days left => OK (Green badge, do nothing)
$okState = SSP::evaluateSchedule(4, '2025-01-01', [], $now);
check('Tier >90d is OK', $okState['tier'] === 'OK');
check('Tier >90d badge is green', $okState['badge_color'] === 'green');
check('Tier >90d action is do nothing', str_contains($okState['action_label'], 'do nothing'));

// 2. <= 90 days left => DUE_SOON (Yellow alert, "Book Soon")
// 2026-10-17 today. For 5 occupants (36 months), last date 2023-11-20 => due 2026-11-20 (approx 34 days left)
$dueSoonState = SSP::evaluateSchedule(5, '2023-11-20', [], $now);
check('Tier <=90d is DUE_SOON', $dueSoonState['tier'] === 'DUE_SOON');
check('Tier <=90d badge is yellow', $dueSoonState['badge_color'] === 'yellow');
check('Tier <=90d label is Book Soon', $dueSoonState['badge_text'] === 'Book Soon');
check('Tier <=90d shows concessionaires', $dueSoonState['show_concessionaires'] === true);

// 3. Past due => OVERDUE (Red URGENT alert, show private option P3.5k-6k)
$overdueState = SSP::evaluateSchedule(5, '2022-01-01', [], $now);
check('Tier past due is OVERDUE', $overdueState['tier'] === 'OVERDUE');
check('Tier past due badge is red', $overdueState['badge_color'] === 'red');
check('Tier past due label is Overdue!', $overdueState['badge_text'] === 'Overdue!');
check('Tier past due shows private option', $overdueState['show_private_option'] === true);

echo "Passed: {$passed}\n";
if ($failed) {
    echo "FAILED (" . count($failed) . "):\n - " . implode("\n - ", $failed) . "\n";
    exit(1);
}
echo "All policy tests passed.\n";

