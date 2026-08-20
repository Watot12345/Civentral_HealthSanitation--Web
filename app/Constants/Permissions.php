<?php
// app/Constants/Permissions.php

namespace App\Constants;

class Permissions
{
    // Main Controls (4)
    public const DASHBOARD_VIEW   = 'dashboard.view';
    public const ANALYTICS_VIEW   = 'analytics.view';
    public const REPORTS_VIEW     = 'reports.view';
    public const COMPLIANCE_VIEW  = 'compliance.view';

    // Role-specific Dashboard Views (5)
    public const HEALTH_CENTER_DASHBOARD  = 'dashboard.health_center';
    public const SANITATION_DASHBOARD     = 'dashboard.sanitation';
    public const IMMUNIZATION_DASHBOARD   = 'dashboard.immunization';
    public const SURVEILLANCE_DASHBOARD   = 'dashboard.surveillance';
    public const SYSTEM_ADMIN_DASHBOARD   = 'dashboard.system_admin';

    // Health Center Services (10)
    public const PATIENTS_VIEW        = 'patients.view';
    public const PATIENTS_CREATE      = 'patients.create';
    public const PATIENTS_EDIT        = 'patients.edit';
    public const PATIENTS_DELETE      = 'patients.delete';
    public const CONSULTATIONS_VIEW   = 'consultations.view';
    public const CONSULTATIONS_CREATE = 'consultations.create';
    public const TRIAGE_VIEW          = 'triage.view';
    public const TRIAGE_CREATE        = 'triage.create';
    public const PRESCRIPTIONS_VIEW   = 'prescriptions.view';
    public const PRESCRIPTIONS_CREATE = 'prescriptions.create';

    // Sanitation Permits (5)
    public const PERMITS_VIEW        = 'permits.view';
    public const PERMITS_CREATE      = 'permits.create';
    public const PERMITS_APPROVE     = 'permits.approve';
    public const INSPECTIONS_VIEW    = 'inspections.view';
    public const INSPECTIONS_CONDUCT = 'inspections.conduct';

    // Immunization & Nutrition (3)
    public const IMMUNIZATION_VIEW   = 'immunization.view';
    public const IMMUNIZATION_CREATE = 'immunization.create';
    public const IMMUNIZATION_EDIT   = 'immunization.edit';

    // Wastewater Services (4)
    public const WASTEWATER_VIEW   = 'wastewater.view';
    public const WASTEWATER_CREATE = 'wastewater.create';
    public const WASTEWATER_EDIT   = 'wastewater.edit';
    public const WASTEWATER_MANAGE = 'wastewater.manage';

    // Health Surveillance (4)
    public const SURVEILLANCE_VIEW   = 'surveillance.view';
    public const SURVEILLANCE_CREATE = 'surveillance.create';
    public const SURVEILLANCE_EDIT   = 'surveillance.edit';
    public const SURVEILLANCE_MANAGE = 'surveillance.manage';

    // System Management (7)
    public const USERS_VIEW      = 'users.view';
    public const USERS_CREATE    = 'users.create';
    public const USERS_EDIT      = 'users.edit';
    public const USERS_DELETE    = 'users.delete';
    public const ROLES_MANAGE    = 'roles.manage';
    public const SETTINGS_MANAGE = 'settings.manage';
    public const LOGS_VIEW       = 'logs.view';

    /**
     * Get all permission slugs as a flat array.
     */
    public static function all(): array
    {
        return [
            self::DASHBOARD_VIEW,
            self::ANALYTICS_VIEW,
            self::REPORTS_VIEW,
            self::COMPLIANCE_VIEW,
            self::PATIENTS_VIEW,
            self::PATIENTS_CREATE,
            self::PATIENTS_EDIT,
            self::PATIENTS_DELETE,
            self::CONSULTATIONS_VIEW,
            self::CONSULTATIONS_CREATE,
            self::TRIAGE_VIEW,
            self::TRIAGE_CREATE,
            self::PRESCRIPTIONS_VIEW,
            self::PRESCRIPTIONS_CREATE,
            self::PERMITS_VIEW,
            self::PERMITS_CREATE,
            self::PERMITS_APPROVE,
            self::INSPECTIONS_VIEW,
            self::INSPECTIONS_CONDUCT,
            self::IMMUNIZATION_VIEW,
            self::IMMUNIZATION_CREATE,
            self::IMMUNIZATION_EDIT,
            self::WASTEWATER_VIEW,
            self::WASTEWATER_CREATE,
            self::WASTEWATER_EDIT,
            self::WASTEWATER_MANAGE,
            self::SURVEILLANCE_VIEW,
            self::SURVEILLANCE_CREATE,
            self::SURVEILLANCE_EDIT,
            self::SURVEILLANCE_MANAGE,
            self::USERS_VIEW,
            self::USERS_CREATE,
            self::USERS_EDIT,
            self::USERS_DELETE,
            self::ROLES_MANAGE,
            self::SETTINGS_MANAGE,
            self::LOGS_VIEW,
        ];
    }
}
