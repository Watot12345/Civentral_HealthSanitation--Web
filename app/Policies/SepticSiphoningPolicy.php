<?php
// app/Policies/SepticSiphoningPolicy.php

namespace App\Policies;

require_once __DIR__ . '/PolicyResult.php';

/**
 * Septic Tank Siphoning Policy Domain Service.
 *
 * Operationalizes Philippine Environmental Sanitation Laws & Academic Panel Standards:
 *  - Presidential Decree No. 856 (Code on Sanitation of the Philippines - Chapter XVII)
 *  - Republic Act No. 9275 (Philippine Clean Water Act of 2004 - Sections 8 & 27)
 *  - DOH Administrative Order No. 2019-0047 (National Policy on Septage Management)
 *  - DENR Administrative Order No. 2005-10 (IRR of RA 9275)
 *  - Metro Manila Concessionaire Obligations (Maynilad / Manila Water under MWSS-RO)
 *
 * Implements pure domain policy logic with zero side effects: deterministic,
 * immutable, and 100% unit testable without database or session dependencies.
 */
final class SepticSiphoningPolicy
{
    // ==================================================================
    // 1. SCHEDULE REQUIREMENTS (Standard Schedule & Sizing)
    // ==================================================================
    public const SIPHONING_MIN_INTERVAL_YEARS   = 3; // 36 months for 5+ occupants
    public const SIPHONING_MAX_INTERVAL_YEARS   = 5; // 60 months for 2-4 occupants
    public const SIPHONING_MIN_INTERVAL_MONTHS  = 36;
    public const SIPHONING_MAX_INTERVAL_MONTHS  = 60;
    public const LARGE_HOUSEHOLD_THRESHOLD      = 5; // 5+ residents triggers 3-year cycle

    /**
     * Concessionaire Hotlines & Free Quinquennial Desludging Provisions.
     * Residents pay for this via the Environmental / Sewerage Charge on monthly water bills.
     */
    public const CONCESSIONAIRE_HOTLINES = [
        'maynilad' => [
            'key'            => 'maynilad',
            'name'           => 'Maynilad Water Services, Inc.',
            'hotline'        => '1626',
            'coverage'       => 'West Zone (Quezon City West, Manila, Caloocan, Valenzuela, Malabon, Navotas, Pasay, Paranaque, Muntinlupa, Las Pinas)',
            'cycle_years'    => 5,
            'cost_note'      => 'FREE regular siphoning every 5 years (included in monthly water bill environmental charge).',
        ],
        'manila_water' => [
            'key'            => 'manila_water',
            'name'           => 'Manila Water Company',
            'hotline'        => '1627',
            'coverage'       => 'East Zone (Quezon City East, Marikina, Pasig, Mandaluyong, San Juan, Taguig, Makati, Rizal Province)',
            'cycle_years'    => 5,
            'cost_note'      => 'FREE regular siphoning every 5 years (included in monthly water bill environmental charge).',
        ],
    ];

    // ==================================================================
    // 2. WARNING SIGNS REQUIRING IMMEDIATE SIPHONING ("Siphon NOW")
    // ==================================================================
    public const EMERGENCY_SIGNS = [
        'slow_drain' => [
            'key'         => 'slow_drain',
            'title'       => 'Slow Drainage',
            'description' => 'Toilet or sink drains very slow.',
            'hazard'      => 'High hydraulic resistance from accumulated settled solids / scum layer.',
            'severity'    => 'critical',
        ],
        'foul_odor' => [
            'key'         => 'foul_odor',
            'title'       => 'Foul Odor',
            'description' => 'Bad smell coming from the toilet or septic tank cover.',
            'hazard'      => 'Release of noxious anaerobic biogases (Hydrogen Sulfide H2S, Methane CH4).',
            'severity'    => 'critical',
        ],
        'backflow' => [
            'key'         => 'backflow',
            'title'       => 'Water Backflow',
            'description' => 'Water flows back into the toilet.',
            'hazard'      => 'Complete hydraulic failure; high risk of domestic wastewater pathogens.',
            'severity'    => 'critical',
        ],
    ];

    // ==================================================================
    // 3. LEGAL & ENVIRONMENTAL COMPLIANCE STANDARDS
    // ==================================================================
    public const LEGAL_STANDARDS = [
        'can_you_siphon_yourself' => 'Yes, but it must be legal.',
        'diy_prohibition'         => 'Do not do it yourself. Do not hire unaccredited people.',
        'mandatory_accreditation' => 'You must hire a DENR-accredited company with a vacuum truck. They must dispose of the waste in a treatment plant.',
        'illegal_dumping'         => 'Dumping it in a canal or river is illegal.',
        'statutory_reference'     => 'Republic Act No. 9275 (Clean Water Act of 2004) Section 27 & PD 856 (Sanitation Code)',
        'private_cost_range'      => [
            'min_php' => 3500,
            'max_php' => 6000,
            'unit'    => 'per vacuum truck load',
            'basis'   => 'Standard prevailing commercial market rate for legitimate accredited vacuum tankers'
        ]
    ];

    // ==================================================================
    // DOMAIN POLICY METHODS (Schedule, Diagnostics & Sizing)
    // ==================================================================

    /**
     * Compute recommended frequency in months based on household occupancy:
     * - 5+ occupants: 3 years (36 months)
     * - 2-4 occupants (and 1 occupant): 5 years (60 months)
     */
    public static function recommendedFrequencyMonths(int $occupants): int
    {
        return $occupants >= self::LARGE_HOUSEHOLD_THRESHOLD
            ? self::SIPHONING_MIN_INTERVAL_MONTHS
            : self::SIPHONING_MAX_INTERVAL_MONTHS;
    }

    /**
     * Compute recommended frequency in years:
     * - 5+ occupants: 3 years
     * - 2-4 occupants: 5 years
     */
    public static function recommendedFrequencyYears(int $occupants): int
    {
        return (int)(self::recommendedFrequencyMonths($occupants) / 12);
    }

    /**
     * Human-readable rule explanation based on household size.
     */
    public static function getHouseholdRule(int $occupants): string
    {
        if ($occupants >= self::LARGE_HOUSEHOLD_THRESHOLD) {
            return sprintf(
                'Do it every 3 years if 5+ people live in the house (%d occupants reported: accelerated sludge accumulation).',
                $occupants
            );
        }
        return sprintf(
            'Do it every 5 years if only 2-4 people live in the house (%d occupant%s reported: standard domestic load).',
            $occupants,
            $occupants === 1 ? '' : 's'
        );
    }

    /**
     * Evaluate diagnostic warning signs. Any positive match triggers immediate triage.
     *
     * @param array<int,string|array> $reportedSigns Keys or sign entries
     * @return array{is_emergency:bool,severity:string,detected_signs:array,urgency_message:string}
     */
    public static function evaluateDiagnostics(array $reportedSigns): array
    {
        $detected = [];
        foreach ($reportedSigns as $item) {
            $key = is_array($item) ? ($item['key'] ?? '') : (string)$item;
            if (isset(self::EMERGENCY_SIGNS[$key])) {
                $detected[$key] = self::EMERGENCY_SIGNS[$key];
            }
        }

        $isEmergency = !empty($detected);
        return [
            'is_emergency'    => $isEmergency,
            'severity'        => $isEmergency ? 'critical' : 'normal',
            'detected_signs'  => array_values($detected),
            'urgency_message' => $isEmergency
                ? 'SIPHON NOW: Severe warning signs detected (' . implode(', ', array_column($detected, 'title')) . '). Immediate desludging required before routine schedule!'
                : 'No emergency warning signs detected. Routine cycle applies.'
        ];
    }

    /**
     * Full evaluation combining occupancy, maintenance history, and reported signs.
     *
     * @param int $occupants Number of household residents.
     * @param string|null $lastMaintenance Date of last desludging (Y-m-d).
     * @param array $reportedSigns Active warning symptoms.
     * @param \DateTimeImmutable|null $now Reference clock (defaults to now).
     * @return array
     */
    /** Warning threshold: <= 90 days remaining flags DUE_SOON ("Book Soon"). */
    public const DUE_SOON_THRESHOLD_DAYS = 90;

    /**
     * Full evaluation combining occupancy, maintenance history, and reported signs.
     *
     * State Machine Lifecycle:
     *  - INPUT: Last Siphoned Date + Household Size
     *  - CALCULATE: Next Due = Last + 3 yrs (if 5+ people) ELSE + 5 yrs
     *  - CHECK STATUS vs Today:
     *      > 90 days left  => OK (Green badge, do nothing)
     *      <= 90 days left => DUE_SOON (Yellow alert, "Book Soon", show Maynilad 1626 / Manila Water 1627)
     *      Past due        => OVERDUE (Red URGENT alert, "Overdue!", show Private option P3.5k-6k)
     *      Warning signs   => SIPHON_NOW (Emergency hazard override)
     *
     * @param int $occupants Number of household residents.
     * @param string|null $lastMaintenance Date of last desludging (Y-m-d).
     * @param array $reportedSigns Active warning symptoms.
     * @param \DateTimeImmutable|null $now Reference clock (defaults to now).
     * @return array
     */
    public static function evaluateSchedule(
        int $occupants,
        ?string $lastMaintenance = null,
        array $reportedSigns = [],
        ?\DateTimeImmutable $now = null
    ): array {
        $now = $now ?? new \DateTimeImmutable('now');
        $intervalMonths = self::recommendedFrequencyMonths($occupants);
        $intervalYears = (int)($intervalMonths / 12);
        $diagnostic = self::evaluateDiagnostics($reportedSigns);

        $lastDate = self::parseDate((string)$lastMaintenance);
        $nextDue = null;
        $daysRemaining = null;
        $daysOverdue = 0;
        $isOverdue = false;

        if ($lastDate !== null) {
            $due = $lastDate->modify("+{$intervalMonths} months");
            $nextDue = $due->format('Y-m-d');
            
            if ($now > $due) {
                $isOverdue = true;
                $daysOverdue = (int)$due->diff($now)->days;
                $daysRemaining = -$daysOverdue;
            } else {
                $daysRemaining = (int)$now->diff($due)->days;
                $daysOverdue = 0;
            }
        }

        // Determine State Machine Tier & Actions
        if ($diagnostic['is_emergency']) {
            $tier = 'SIPHON_NOW';
            $status = 'siphon_now';
            $badgeColor = 'red';
            $badgeText = 'SIPHON NOW!';
            $actionLabel = 'Red URGENT alert: "SIPHON NOW!" - Emergency biohazard override.';
            $actionInstruction = 'Immediate hazard detected. Do not wait for routine cycle. Book accredited vacuum tanker immediately.';
            $showConcessionaires = true;
            $showPrivateOption = true;
        } elseif ($isOverdue || $lastDate === null) {
            $tier = 'OVERDUE';
            $status = 'overdue';
            $badgeColor = 'red';
            $badgeText = 'Overdue!';
            $actionLabel = 'Red URGENT alert, "Overdue!", show Private option P3.5k-6k';
            $actionInstruction = $lastDate === null
                ? 'No maintenance history recorded. Septic tank is overdue for siphoning inspection.'
                : sprintf('Overdue by %d day%s! Expedited service recommended via DENR-accredited private vacuum tanker (₱3,500 – ₱6,000 per truck).', $daysOverdue, $daysOverdue === 1 ? '' : 's');
            $showConcessionaires = true;
            $showPrivateOption = true;
        } elseif ($daysRemaining <= self::DUE_SOON_THRESHOLD_DAYS) {
            // <= 90 days left
            $tier = 'DUE_SOON';
            $status = 'due_soon';
            $badgeColor = 'yellow';
            $badgeText = 'Book Soon';
            $actionLabel = 'Yellow alert, "Book Soon", show Maynilad 1626 / Manila Water 1627';
            $actionInstruction = sprintf('Book Soon! Siphoning is due in %d day%s. In Quezon City, call Maynilad (1626) or Manila Water (1627) for FREE siphoning.', $daysRemaining, $daysRemaining === 1 ? '' : 's');
            $showConcessionaires = true;
            $showPrivateOption = false;
        } else {
            // > 90 days left
            $tier = 'OK';
            $status = 'ok';
            $badgeColor = 'green';
            $badgeText = 'OK';
            $actionLabel = 'Green badge, do nothing';
            $actionInstruction = sprintf('Septic tank is healthy and within compliant operational cycle (%d days left). Do nothing at this time.', $daysRemaining);
            $showConcessionaires = false;
            $showPrivateOption = false;
        }

        return [
            'status'                  => $status,
            'tier'                    => $tier,
            'badge_color'             => $badgeColor,
            'badge_text'              => $badgeText,
            'action_label'            => $actionLabel,
            'action_instruction'      => $actionInstruction,
            'show_concessionaires'    => $showConcessionaires,
            'show_private_option'     => $showPrivateOption,
            'is_emergency'            => $diagnostic['is_emergency'],
            'is_overdue'              => $isOverdue,
            'occupants'               => $occupants,
            'interval_months'         => $intervalMonths,
            'interval_years'          => $intervalYears,
            'household_rule'          => self::getHouseholdRule($occupants),
            'last_maintenance'        => $lastDate ? $lastDate->format('Y-m-d') : null,
            'next_due'                => $nextDue,
            'days_remaining'          => $daysRemaining,
            'days_overdue'            => $daysOverdue,
            'detected_signs'          => $diagnostic['detected_signs'],
            'concessionaires'         => self::CONCESSIONAIRE_HOTLINES,
            'legal_standards'         => self::LEGAL_STANDARDS,
        ];
    }

    /**
     * Complete statutory policy representation matching panel requirements.
     */
    public static function getPolicyDetails(): array
    {
        return [
            'title'    => 'Septic Tank Siphoning Policy',
            'subtitle' => 'Schedule Requirements, Warning Signs & Legal Standards',
            'framework' => [
                'statutes' => [
                    'PD 856'  => 'Code on Sanitation of the Philippines (Chapter XVII)',
                    'RA 9275' => 'Philippine Clean Water Act of 2004 (Section 8 & Section 27)',
                    'DAO 2005-10' => 'Implementing Rules and Regulations of the Clean Water Act',
                    'DOH AO 2019-0047' => 'National Policy on Septage Management and Desludging Cycles',
                ],
            ],
            'section_1_schedule' => [
                'number'            => '1',
                'title'             => 'When to Siphon Again',
                'standard_schedule' => 'Every 3-5 years.',
                'large_household'   => 'Do it every 3 years if 5+ people live in the house.',
                'small_household'   => 'Do it every 5 years if only 2-4 people live in the house.',
                'quezon_city_free'  => 'In Quezon City: Call Maynilad (1626) or Manila Water (1627) for FREE siphoning every 5 years. You already pay for it in your monthly bill.',
                'hotlines'          => self::CONCESSIONAIRE_HOTLINES,
            ],
            'section_2_warning_signs' => [
                'number'      => '2',
                'title'       => 'Siphon NOW if you have these signs',
                'directive'   => 'Immediate emergency desludging required; overrides routine 3–5 year calendar interval.',
                'signs'       => array_values(self::EMERGENCY_SIGNS),
            ],
            'section_3_legal_standards' => [
                'number'                => '3',
                'title'                 => 'Can you siphon it yourself?',
                'answer'                => 'Yes, but it must be legal.',
                'prohibition'           => 'Do not do it yourself. Do not hire unaccredited people.',
                'accreditation'         => 'You must hire a DENR-accredited company with a vacuum truck. They must dispose of the waste in a treatment plant.',
                'prohibited_dumping'    => 'Dumping it in a canal or river is illegal.',
                'private_cost_benchmark'=> 'Private cost: P3,500 to P6,000 per truck.',
                'cost_range'            => self::LEGAL_STANDARDS['private_cost_range'],
            ],
        ];
    }

    private static function parseDate(string $value): ?\DateTimeImmutable
    {
        $value = substr(trim($value), 0, 10);
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return ($d !== false && $d->format('Y-m-d') === $value) ? $d : null;
    }
}
