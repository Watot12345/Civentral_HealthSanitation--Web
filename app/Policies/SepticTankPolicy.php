<?php
// app/Policies/SepticTankPolicy.php

namespace App\Policies;

require_once __DIR__ . '/PolicyResult.php';

/**
 * Business rules for septic tank registration, maintenance scheduling and
 * service-request intake. Pure logic: callers pass arrays and a clock.
 *
 * The numeric thresholds below are *system defaults* chosen to match the
 * registration form and seed data. They are not quoted from a statute; if the
 * city ordinance sets different intervals, change the constants here and the
 * whole system follows.
 */
final class SepticTankPolicy
{
    // Mirrors the CHECK constraints on public.septic_tanks.
    public const TYPES    = ['Concrete', 'Plastic', 'Fiberglass', 'Other'];
    public const STATUSES = ['good', 'needs_maintenance', 'critical', 'decommissioned'];

    /** Interval used when a tank has no maintenance_frequency recorded. */
    public const DEFAULT_INTERVAL_MONTHS = 12;
    public const MIN_INTERVAL_MONTHS     = 1;
    public const MAX_INTERVAL_MONTHS     = 60;

    /** Desludging / Siphoning schedule standards (PD 856 & Panel Guidelines). */
    public const SIPHONING_MIN_INTERVAL_YEARS   = 3; // 36 months for 5+ occupants
    public const SIPHONING_MAX_INTERVAL_YEARS   = 5; // 60 months for 2-4 occupants
    public const SIPHONING_MIN_INTERVAL_MONTHS  = 36;
    public const SIPHONING_MAX_INTERVAL_MONTHS  = 60;
    public const LARGE_HOUSEHOLD_THRESHOLD      = 5; // 5+ people -> 3-year cycle

    /** Metro Manila / Quezon City Concessionaire Hotlines for FREE regular desludging. */
    public const CONCESSIONAIRE_HOTLINES = [
        'maynilad' => [
            'name'           => 'Maynilad Water Services',
            'hotline'        => '1626',
            'coverage'       => 'West Zone (Quezon City West, Manila, Caloocan, etc.)',
            'cycle_years'    => 5,
            'cost_note'      => 'FREE regular desludging every 5 years (included in monthly water bill environmental charge)',
        ],
        'manila_water' => [
            'name'           => 'Manila Water Company',
            'hotline'        => '1627',
            'coverage'       => 'East Zone (Quezon City East, Marikina, Pasig, Mandaluyong, etc.)',
            'cycle_years'    => 5,
            'cost_note'      => 'FREE regular desludging every 5 years (included in monthly water bill environmental charge)',
        ],
    ];

    /** Critical signs requiring immediate siphoning ("Siphon NOW"). */
    public const CRITICAL_SIPHONING_SIGNS = [
        'slow_drain' => [
            'key'         => 'slow_drain',
            'title'       => 'Slow Drainage',
            'description' => 'Toilet or sink drains very slow',
            'severity'    => 'critical',
        ],
        'foul_odor' => [
            'key'         => 'foul_odor',
            'title'       => 'Foul Odor',
            'description' => 'Bad smell coming from the toilet or septic tank cover',
            'severity'    => 'critical',
        ],
        'backflow' => [
            'key'         => 'backflow',
            'title'       => 'Wastewater Backflow',
            'description' => 'Water flows back into the toilet',
            'severity'    => 'critical',
        ],
    ];

    /** Commercial / private vacuum tanker benchmark pricing. */
    public const PRIVATE_SIPHONING_RATES = [
        'min_php' => 3500,
        'max_php' => 6000,
        'basis'   => 'per vacuum truck load (DENR-accredited hauler)',
    ];

    /** A tank is flagged "due soon" this many days before its due date. */
    public const DUE_SOON_DAYS = 30;

    /** Past due by more than this many months escalates needs_maintenance -> critical. */
    public const CRITICAL_OVERDUE_MONTHS = 6;

    public const MIN_INSTALLATION_YEAR = 1950;

    private const SEVERITY = ['good' => 0, 'needs_maintenance' => 1, 'critical' => 2];

    // ------------------------------------------------------------------
    // Registration / update validation
    // ------------------------------------------------------------------

    /**
     * Validate tank fields. On create, owner and address are mandatory; on
     * update only the fields actually present are checked.
     */
    public static function validate(array $data, bool $isUpdate, \DateTimeImmutable $now): PolicyResult
    {
        $errors = [];
        $has = static fn(string $key): bool => array_key_exists($key, $data);

        foreach (['owner_name', 'address'] as $required) {
            if ((!$isUpdate || $has($required)) && trim((string)($data[$required] ?? '')) === '') {
                $errors[$required] = 'Required';
            }
        }

        if ($has('type') && $data['type'] !== null && $data['type'] !== ''
            && self::canonicalType((string)$data['type']) === null) {
            $errors['type'] = 'Must be one of: ' . implode(', ', self::TYPES);
        }

        if ($has('status') && $data['status'] !== null && $data['status'] !== ''
            && !in_array($data['status'], self::STATUSES, true)) {
            $errors['status'] = 'Must be one of: ' . implode(', ', self::STATUSES);
        }

        if ($has('capacity') && $data['capacity'] !== null && $data['capacity'] !== ''
            && self::capacityLiters((string)$data['capacity']) === null) {
            $errors['capacity'] = 'Must be a positive volume, e.g. 1200L or 4000 Liters';
        }

        if ($has('maintenance_frequency') && $data['maintenance_frequency'] !== null && $data['maintenance_frequency'] !== '') {
            $months = filter_var($data['maintenance_frequency'], FILTER_VALIDATE_INT);
            if ($months === false || $months < self::MIN_INTERVAL_MONTHS || $months > self::MAX_INTERVAL_MONTHS) {
                $errors['maintenance_frequency'] = sprintf('Must be %d to %d months', self::MIN_INTERVAL_MONTHS, self::MAX_INTERVAL_MONTHS);
            }
        }

        if ($has('installation_year') && $data['installation_year'] !== null && $data['installation_year'] !== '') {
            $year = filter_var($data['installation_year'], FILTER_VALIDATE_INT);
            if ($year === false || $year < self::MIN_INSTALLATION_YEAR || $year > (int)$now->format('Y')) {
                $errors['installation_year'] = sprintf('Must be between %d and %s', self::MIN_INSTALLATION_YEAR, $now->format('Y'));
            }
        }

        if ($has('last_maintenance') && $data['last_maintenance'] !== null && $data['last_maintenance'] !== '') {
            $date = self::parseDate((string)$data['last_maintenance']);
            if ($date === null || $date > $now) {
                $errors['last_maintenance'] = 'Must be a valid date that is not in the future';
            }
        }

        foreach (['latitude' => 90.0, 'longitude' => 180.0] as $field => $limit) {
            if ($has($field) && $data[$field] !== null && $data[$field] !== '') {
                if (!is_numeric($data[$field]) || abs((float)$data[$field]) > $limit) {
                    $errors[$field] = "Must be a number between -{$limit} and {$limit}";
                }
            }
        }

        if (!empty($errors)) {
            return PolicyResult::deny('Validation failed: ' . implode('; ', array_map(
                static fn($f, $m) => "{$f} - {$m}",
                array_keys($errors),
                $errors
            )), 422, $errors);
        }
        return PolicyResult::allow();
    }

    /** Case-insensitive match against the allowed types; null when unknown. */
    public static function canonicalType(string $type): ?string
    {
        foreach (self::TYPES as $allowed) {
            if (strcasecmp($allowed, trim($type)) === 0) {
                return $allowed;
            }
        }
        return null;
    }

    /** Parse "1200L", "4000 Liters", "2.5 m3" into litres; null if invalid or not positive. */
    public static function capacityLiters(string $capacity): ?float
    {
        if (!preg_match('/^\s*(\d+(?:\.\d+)?)\s*(l|lit(?:er|re)s?|m3|m³|cu\.?\s*m)?\s*$/i', $capacity, $m)) {
            return null;
        }
        $value = (float)$m[1];
        if ($value <= 0) {
            return null;
        }
        $unit = strtolower($m[2] ?? '');
        return preg_match('/^(m3|m³|cu)/', $unit) ? $value * 1000 : $value;
    }

    // ------------------------------------------------------------------
    // Maintenance scheduling
    // ------------------------------------------------------------------

    /**
     * Work out when a tank is due for desludging and how urgent it is.
     *
     * Reference date: last_maintenance, else created_at, else Jan 1 of the
     * installation year. Due date = reference + interval months.
     * The effective status is the worse of the stored status and the schedule,
     * so a manually raised "critical" is never silently downgraded.
     *
     * @return array{status:string,next_due:?string,days_overdue:int,due_soon:bool,interval_months:int,basis:string}
     */
    public static function evaluateMaintenance(array $tank, \DateTimeImmutable $now): array
    {
        $stored = (string)($tank['status'] ?? 'good');
        $interval = filter_var($tank['maintenance_frequency'] ?? null, FILTER_VALIDATE_INT);
        if ($interval === false || $interval < self::MIN_INTERVAL_MONTHS) {
            $interval = self::DEFAULT_INTERVAL_MONTHS;
        }

        if ($stored === 'decommissioned') {
            return ['status' => 'decommissioned', 'next_due' => null, 'days_overdue' => 0, 'due_soon' => false,
                    'interval_months' => $interval, 'basis' => 'decommissioned'];
        }

        [$reference, $basis] = self::referenceDate($tank);
        if ($reference === null) {
            return ['status' => self::worse($stored, 'needs_maintenance'), 'next_due' => null, 'days_overdue' => 0,
                    'due_soon' => false, 'interval_months' => $interval, 'basis' => 'no_history'];
        }

        $due = $reference->modify("+{$interval} months");
        $daysOverdue = $now > $due ? (int)$due->diff($now)->days : 0;

        $computed = 'good';
        if ($now > $due) {
            $computed = $now > $due->modify('+' . self::CRITICAL_OVERDUE_MONTHS . ' months')
                ? 'critical'
                : 'needs_maintenance';
        }

        return [
            'status'          => self::worse($stored, $computed),
            'next_due'        => $due->format('Y-m-d'),
            'days_overdue'    => $daysOverdue,
            'due_soon'        => $computed === 'good' && $now >= $due->modify('-' . self::DUE_SOON_DAYS . ' days'),
            'interval_months' => $interval,
            'basis'           => $basis,
        ];
    }

    /**
     * Return the tanks with `status` replaced by the effective status and a
     * `maintenance` block added. The stored status in the database is untouched.
     */
    public static function applyMaintenancePolicy(array $tanks, \DateTimeImmutable $now): array
    {
        foreach ($tanks as &$tank) {
            $evaluation = self::evaluateMaintenance($tank, $now);
            $tank['stored_status'] = $tank['status'] ?? 'good';
            $tank['status']        = $evaluation['status'];
            $tank['maintenance']   = $evaluation;
        }
        unset($tank);
        return $tanks;
    }

    /** Status counts in the shape SepticTank::countByStatus() returns. */
    public static function summarize(array $tanks): array
    {
        $counts = ['good' => 0, 'needs_maintenance' => 0, 'critical' => 0, 'decommissioned' => 0];
        foreach ($tanks as $tank) {
            $status = $tank['status'] ?? 'good';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }
        $counts['total'] = array_sum($counts);
        return $counts;
    }

    // ------------------------------------------------------------------
    // Service requests
    // ------------------------------------------------------------------

    /**
     * @param array|null $tank           The registered tank, or null when the request names none.
     * @param array|null $activeRequest  An open request already holding this tank, if any.
     */
    public static function canRequestService(?array $tank, ?array $activeRequest, ?string $requestedDate = null): PolicyResult
    {
        if ($tank !== null && ($tank['status'] ?? '') === 'decommissioned') {
            return PolicyResult::deny("Tank {$tank['tank_id']} is decommissioned and cannot receive service requests", 409);
        }
        if ($activeRequest !== null) {
            $tankId = $tank['tank_id'] ?? ($activeRequest['tank_id'] ?? 'this tank');
            $existingDate = substr((string)($activeRequest['preferred_date'] ?? $activeRequest['created_at'] ?? ''), 0, 10);
            $existingReq = $activeRequest['request_id'] ?? 'Request';
            return PolicyResult::deny(
                "Duplicate request: Tank {$tankId} already has an active service request ({$existingReq}) for {$existingDate}.",
                409
            );
        }
        return PolicyResult::allow();
    }

    // ------------------------------------------------------------------
    // Siphoning Guidelines & Policy Rules (Panel & Environmental Standard)
    // ------------------------------------------------------------------

    /**
     * Recommended desludging frequency based on household occupancy:
     * - 5+ occupants: 3 years (36 months)
     * - 2-4 occupants (and 1 occupant): 5 years (60 months)
     */
    public static function recommendedSiphoningIntervalMonths(int $occupants): int
    {
        return $occupants >= self::LARGE_HOUSEHOLD_THRESHOLD
            ? self::SIPHONING_MIN_INTERVAL_MONTHS
            : self::SIPHONING_MAX_INTERVAL_MONTHS;
    }

    /**
     * Evaluate when a tank needs siphoning and whether urgent signs trigger immediate service.
     *
     * @param int $occupants Number of residents in the household.
     * @param string|null $lastMaintenance Date of last siphoning/desludging (Y-m-d).
     * @param array $reportedSigns List of active signs (e.g. ['slow_drain', 'foul_odor', 'backflow']).
     * @param \DateTimeImmutable|null $now Reference date.
     * @return array
     */
    public static function evaluateSiphoningSchedule(
        int $occupants,
        ?string $lastMaintenance = null,
        array $reportedSigns = [],
        ?\DateTimeImmutable $now = null
    ): array {
        $now = $now ?? new \DateTimeImmutable('now');
        $intervalMonths = self::recommendedSiphoningIntervalMonths($occupants);
        $intervalYears = (int)($intervalMonths / 12);

        $householdRule = $occupants >= self::LARGE_HOUSEHOLD_THRESHOLD
            ? "Do it every 3 years if 5+ people live in the house (High bio-load)."
            : "Do it every 5 years if only 2-4 people live in the house (Standard schedule).";

        // Check for urgent signs
        $detectedSigns = [];
        foreach ($reportedSigns as $sign) {
            $key = is_array($sign) ? ($sign['key'] ?? '') : (string)$sign;
            if (isset(self::CRITICAL_SIPHONING_SIGNS[$key])) {
                $detectedSigns[] = self::CRITICAL_SIPHONING_SIGNS[$key];
            }
        }
        $isEmergency = !empty($detectedSigns);

        $lastDate = self::parseDate((string)$lastMaintenance);
        $nextDue = null;
        $daysOverdue = 0;
        $isOverdue = false;

        if ($lastDate !== null) {
            $due = $lastDate->modify("+{$intervalMonths} months");
            $nextDue = $due->format('Y-m-d');
            if ($now > $due) {
                $isOverdue = true;
                $daysOverdue = (int)$due->diff($now)->days;
            }
        }

        $status = 'scheduled';
        $urgencyMessage = "On routine schedule every {$intervalYears} years ({$intervalMonths} months).";

        if ($isEmergency) {
            $status = 'siphon_now';
            $urgencyMessage = 'SIPHON NOW: Severe warning signs detected (' . implode(', ', array_column($detectedSigns, 'title')) . '). Do not wait for the scheduled cycle!';
        } elseif ($isOverdue) {
            $status = 'overdue';
            $urgencyMessage = "OVERDUE for siphoning by {$daysOverdue} days. Desludging required.";
        }

        return [
            'status'                  => $status,
            'is_emergency'            => $isEmergency,
            'is_overdue'              => $isOverdue,
            'occupants'               => $occupants,
            'interval_months'         => $intervalMonths,
            'interval_years'          => $intervalYears,
            'household_rule'          => $householdRule,
            'last_maintenance'        => $lastDate ? $lastDate->format('Y-m-d') : null,
            'next_due'                => $nextDue,
            'days_overdue'            => $daysOverdue,
            'detected_signs'          => $detectedSigns,
            'urgency_message'         => $urgencyMessage,
            'concessionaires'         => self::CONCESSIONAIRE_HOTLINES,
            'private_cost_range'      => self::PRIVATE_SIPHONING_RATES,
            'legal_accreditation'     => 'DENR-accredited company with certified vacuum truck required; disposal strictly at an authorized septage treatment plant.',
            'illegal_dumping_warning' => 'Dumping septage into canals, esteros, or rivers is strictly illegal under RA 9275 (Clean Water Act).'
        ];
    }

    /**
     * Full structured guidelines as stated by environmental health panels and Philippine sanitation law.
     */
    public static function getSiphoningGuidelines(): array
    {
        return [
            'title' => 'Septic Tank Siphoning - When and Can You Do It?',
            'section_1_schedule' => [
                'title'             => '1. When to siphon again',
                'standard_schedule' => 'Every 3-5 years.',
                'large_household'   => 'Do it every 3 years if 5+ people live in the house.',
                'small_household'   => 'Do it every 5 years if only 2-4 people live in the house.',
                'quezon_city_free'  => 'In Quezon City: Call Maynilad (1626) or Manila Water (1627) for FREE siphoning every 5 years. You already pay for it in your monthly bill.',
                'hotlines'          => self::CONCESSIONAIRE_HOTLINES,
            ],
            'section_2_emergency_signs' => [
                'title'       => '2. Siphon NOW if you have these signs',
                'description' => 'Immediate action required — do not wait for the routine cycle:',
                'signs'       => array_values(self::CRITICAL_SIPHONING_SIGNS),
            ],
            'section_3_legality' => [
                'title'                 => '3. Can you siphon it yourself?',
                'answer'                => 'Yes, but it must be legal.',
                'diy_prohibition'       => 'Do not do it yourself. Do not hire unaccredited people.',
                'mandatory_accredited'  => 'You must hire a DENR-accredited company with a vacuum truck. They must dispose of the waste in a treatment plant.',
                'illegal_dumping'       => 'Dumping it in a canal or river is illegal.',
                'private_cost'          => 'Private cost: P3,500 to P6,000 per truck.',
                'private_cost_range'    => self::PRIVATE_SIPHONING_RATES,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    /** @return array{0:?\DateTimeImmutable,1:string} */
    private static function referenceDate(array $tank): array
    {
        $last = self::parseDate((string)($tank['last_maintenance'] ?? ''));
        if ($last !== null) {
            return [$last, 'last_maintenance'];
        }
        $created = self::parseDate(substr((string)($tank['created_at'] ?? ''), 0, 10));
        if ($created !== null) {
            return [$created, 'created_at'];
        }
        $year = filter_var($tank['installation_year'] ?? null, FILTER_VALIDATE_INT);
        if ($year !== false && $year >= self::MIN_INSTALLATION_YEAR) {
            return [new \DateTimeImmutable(sprintf('%04d-01-01', $year)), 'installation_year'];
        }
        return [null, 'none'];
    }

    private static function worse(string $a, string $b): string
    {
        $rankA = self::SEVERITY[$a] ?? 0;
        $rankB = self::SEVERITY[$b] ?? 0;
        return $rankA >= $rankB ? ($a === '' ? 'good' : $a) : $b;
    }

    private static function parseDate(string $value): ?\DateTimeImmutable
    {
        $value = substr(trim($value), 0, 10);
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return ($d !== false && $d->format('Y-m-d') === $value) ? $d : null;
    }
}
