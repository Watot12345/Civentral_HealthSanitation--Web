<?php
// app/Policies/InspectionPolicy.php

namespace App\Policies;

require_once __DIR__ . '/PolicyResult.php';

/**
 * Business and authorization rules for sanitary inspections.
 *
 * Pure logic: no session, database, or HTTP access. Callers pass in plain
 * arrays, so every rule can be unit-tested (see tests/policies_test.php).
 *
 * $actor shape: ['role_text' => string, 'user_id' => int, 'employee_id' => int]
 *   role_text is the lower-cased "role_description role" session string.
 *
 * Capability checks (inspections.view / inspections.conduct) remain the job of
 * Permissions + AuthorizationMiddleware. This policy decides the *contextual*
 * questions a permission slug cannot answer: whose inspection is it, what state
 * is it in, and is the request allowed to proceed given the permit.
 */
final class InspectionPolicy
{
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const OUTCOME_COMPLIANT           = 'compliant';
    public const OUTCOME_PARTIALLY_COMPLIANT = 'partially_compliant';
    public const OUTCOME_NON_COMPLIANT       = 'non_compliant';

    public const STATUSES = [self::STATUS_SCHEDULED, self::STATUS_COMPLETED, self::STATUS_CANCELLED];
    public const OUTCOMES = [
        self::OUTCOME_COMPLIANT,
        self::OUTCOME_PARTIALLY_COMPLIANT,
        self::OUTCOME_NON_COMPLIANT,
    ];

    // ------------------------------------------------------------------
    // Actor classification
    // ------------------------------------------------------------------

    /** System administrators and the Sanitation Director manage the schedule. */
    public static function isManagement(array $actor): bool
    {
        $role = self::role($actor);
        return str_contains($role, 'admin') || str_contains($role, 'director');
    }

    public static function isInspector(array $actor): bool
    {
        return str_contains(self::role($actor), 'inspector');
    }

    /**
     * Who may see every inspection row (not just their own assignments).
     *
     * An inspector who is not management is always scoped to their own work,
     * even if their title also contains a broad word such as "sanitation".
     * An unknown/empty role fails closed.
     */
    public static function canViewAll(array $actor): bool
    {
        if (self::isManagement($actor)) {
            return true;
        }
        if (self::isInspector($actor)) {
            return false;
        }

        $role = self::role($actor);
        if ($role === '') {
            return false;
        }

        foreach (['clerk', 'staff', 'officer', 'head', 'supervisor', 'manager', 'sanitation'] as $keyword) {
            if (str_contains($role, $keyword)) {
                return true;
            }
        }
        return false;
    }

    // ------------------------------------------------------------------
    // Read / write decisions
    // ------------------------------------------------------------------

    /** Inspectors see only inspections assigned to them that are still scheduled. */
    public static function canAccess(array $actor, array $inspection): bool
    {
        if (self::canViewAll($actor)) {
            return true;
        }
        return self::isAssignedTo($actor, $inspection)
            && self::statusOf($inspection) === self::STATUS_SCHEDULED;
    }

    /**
     * @param array|null $permit              The permit being inspected.
     * @param bool       $hasOpenInspection   True if the permit already has a scheduled inspection.
     * @param string     $scheduledDate       Y-m-d requested date.
     * @param string     $today               Y-m-d "today" (injected for testability).
     */
    public static function canSchedule(
        array $actor,
        ?array $permit,
        bool $hasOpenInspection,
        string $scheduledDate,
        string $today
    ): PolicyResult {
        if (!self::isManagement($actor)) {
            return PolicyResult::deny('Only administrators and sanitation directors can schedule inspections');
        }
        if ($permit === null) {
            return PolicyResult::deny('Permit not found', 404);
        }
        if (empty($permit['paid'])) {
            return PolicyResult::deny('Permit fee must be paid before scheduling an inspection', 400);
        }
        if (!self::isValidDate($scheduledDate)) {
            return PolicyResult::deny('Scheduled date is not a valid date', 422, ['scheduled_date' => 'Use the format YYYY-MM-DD']);
        }
        if ($scheduledDate < $today) {
            return PolicyResult::deny('Inspections cannot be scheduled in the past', 422, ['scheduled_date' => 'Date is in the past']);
        }
        if ($hasOpenInspection) {
            return PolicyResult::deny('This permit already has a scheduled inspection', 409);
        }
        return PolicyResult::allow();
    }

    /** Editing the schedule details: management only, and never after the report is filed. */
    public static function canUpdate(array $actor, array $inspection): PolicyResult
    {
        if (!self::isManagement($actor)) {
            return PolicyResult::deny('Only administrators and sanitation directors can edit inspections');
        }
        return self::guardNotFinalised($inspection, 'edited');
    }

    /** An inspector may file the report only for their own scheduled inspection. */
    public static function canConduct(array $actor, array $inspection): PolicyResult
    {
        if (!self::canAccess($actor, $inspection)) {
            return PolicyResult::deny('You can only conduct inspections assigned to you');
        }
        if (self::statusOf($inspection) !== self::STATUS_SCHEDULED) {
            return PolicyResult::deny('Only scheduled inspections can be conducted', 400);
        }
        return PolicyResult::allow();
    }

    /**
     * Status transitions are limited to scheduled <-> cancelled. Completion is
     * reachable only through conduct(), so a report can never be "completed"
     * without findings and an outcome.
     */
    public static function canChangeStatus(array $actor, array $inspection, string $newStatus): PolicyResult
    {
        if (!self::isManagement($actor)) {
            return PolicyResult::deny('Only administrators and sanitation directors can change inspection status');
        }
        if (!in_array($newStatus, self::STATUSES, true)) {
            return PolicyResult::deny('Invalid status value. Valid: ' . implode(', ', self::STATUSES), 400);
        }
        $finalised = self::guardNotFinalised($inspection, 'changed');
        if ($finalised->denied()) {
            return $finalised;
        }
        if ($newStatus === self::STATUS_COMPLETED) {
            return PolicyResult::deny('An inspection can only be completed by submitting its report', 400);
        }
        return PolicyResult::allow();
    }

    /** Completed reports are part of the audit trail and cannot be deleted. */
    public static function canDelete(array $actor, array $inspection): PolicyResult
    {
        if (!self::isManagement($actor)) {
            return PolicyResult::deny('Only administrators and sanitation directors can delete inspections');
        }
        if (self::statusOf($inspection) === self::STATUS_COMPLETED) {
            return PolicyResult::deny('Completed inspections are part of the audit trail and cannot be deleted', 409);
        }
        return PolicyResult::allow();
    }

    // ------------------------------------------------------------------
    // Report (conduct) rules
    // ------------------------------------------------------------------

    /**
     * Server-side enforcement of the rules the inspection form already shows:
     *  - an outcome is mandatory and must be a known value
     *  - partially compliant => a re-inspection (follow-up) date is required
     *  - non compliant       => recommendations must state the rejection reason
     */
    public static function validateReport(array $data, string $today): PolicyResult
    {
        $outcome = strtolower(trim((string)($data['overall_status'] ?? '')));
        if (!in_array($outcome, self::OUTCOMES, true)) {
            return PolicyResult::deny(
                'An inspection outcome is required. Valid: ' . implode(', ', self::OUTCOMES),
                422,
                ['overall_status' => 'Required']
            );
        }

        if ($outcome === self::OUTCOME_PARTIALLY_COMPLIANT) {
            $followUp = trim((string)($data['follow_up_date'] ?? ''));
            if ($followUp === '') {
                return PolicyResult::deny('A follow-up re-inspection date is required for a partially compliant result', 422, ['follow_up_date' => 'Required']);
            }
            if (!self::isValidDate($followUp) || $followUp < $today) {
                return PolicyResult::deny('Follow-up date must be today or later', 422, ['follow_up_date' => 'Invalid or in the past']);
            }
        }

        if ($outcome === self::OUTCOME_NON_COMPLIANT && trim((string)($data['recommendations'] ?? '')) === '') {
            return PolicyResult::deny('A rejection reason is required for a non-compliant result', 422, ['recommendations' => 'Required']);
        }

        return PolicyResult::allow();
    }

    /** Permit status that follows from an inspection outcome. */
    public static function permitStatusFor(string $outcome): string
    {
        return match ($outcome) {
            self::OUTCOME_COMPLIANT     => 'approved',
            self::OUTCOME_NON_COMPLIANT => 'rejected',
            default                     => 'under_review',
        };
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private static function guardNotFinalised(array $inspection, string $verb): PolicyResult
    {
        $status = self::statusOf($inspection);
        if ($status === self::STATUS_COMPLETED) {
            return PolicyResult::deny("Completed inspections are locked and cannot be {$verb}", 409);
        }
        return PolicyResult::allow();
    }

    private static function isAssignedTo(array $actor, array $inspection): bool
    {
        $assigned = (int)($inspection['inspector_id'] ?? 0);
        if ($assigned === 0) {
            return false;
        }
        return $assigned === (int)($actor['user_id'] ?? 0)
            || $assigned === (int)($actor['employee_id'] ?? 0);
    }

    private static function statusOf(array $inspection): string
    {
        return strtolower((string)($inspection['status'] ?? self::STATUS_SCHEDULED));
    }

    private static function role(array $actor): string
    {
        return strtolower(trim((string)($actor['role_text'] ?? '')));
    }

    private static function isValidDate(string $value): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }
}
