<?php
// config/navigation.php

use App\Constants\Permissions;

return [
    'sections' => [
        [
            'key'        => 'main_controls',
            'label'      => 'Main Controls',
            'items'      => [
                [
                    'key'         => 'dashboard',
                    'label'       => 'Dashboard Overview', // Dynamic label override handled in NavigationService
                    'icon'        => 'fa-solid fa-table-columns',
                    'route'       => 'pages/dashboard.php',
                    'permission'  => Permissions::DASHBOARD_VIEW,
                    'match_paths' => ['dashboard.php', 'module_activity.php', 'alerts.php', 'system_health.php'],
                ],
                [
                    'key'         => 'analytics',
                    'label'       => 'Analytics',
                    'icon'        => 'fa-solid fa-chart-line',
                    'route'       => 'pages/ai_insights.php',
                    'permission'  => Permissions::ANALYTICS_VIEW,
                    'match_paths' => ['ai_insights.php', 'trend_analysis.php', 'predictive.php', 'performance.php'],
                ],
                [
                    'key'         => 'reports',
                    'label'       => 'Reports',
                    'icon'        => 'fa-solid fa-file-pen',
                    'route'       => 'pages/custom_report.php',
                    'permission'  => Permissions::REPORTS_VIEW,
                    'match_paths' => ['custom_report.php', 'export.php', 'report_templates.php'],
                ],
                [
                    'key'         => 'compliance',
                    'label'       => 'Compliance & Violations',
                    'icon'        => 'fa-solid fa-gavel',
                    'route'       => 'pages/compliance_monitoring.php',
                    'permission'  => Permissions::COMPLIANCE_VIEW,
                    'match_paths' => ['compliance_monitoring.php', 'violation_tracking.php', 'corrective_actions.php', 'regulatory_compliance.php'],
                ],
            ],
        ],
        [
            'key'        => 'operational_modules',
            'label'      => 'Operational Modules',
            'modules'    => [
                [
                    'key'         => 'healthCenter',
                    'label'       => 'Health Center Services',
                    'icon'        => 'fa-solid fa-hospital',
                    'match_path'  => 'modules/healthservices',
                    'permissions' => [Permissions::PATIENTS_VIEW, Permissions::CONSULTATIONS_VIEW, Permissions::TRIAGE_VIEW, Permissions::PRESCRIPTIONS_VIEW],
                    'children'    => [
                        [
                            'label'      => 'Patient Management',
                            'icon'       => 'fa-solid fa-users',
                            'route'      => 'modules/healthservices/patients.php',
                            'permission' => Permissions::PATIENTS_VIEW,
                        ],
                        [
                            'label'      => 'Consultations',
                            'icon'       => 'fa-solid fa-stethoscope',
                            'route'      => 'modules/healthservices/consultations.php',
                            'permission' => Permissions::CONSULTATIONS_VIEW,
                        ],
                        [
                            'label'      => 'Medical Records',
                            'icon'       => 'fa-solid fa-folder',
                            'route'      => 'modules/healthservices/medical_records.php',
                            'permission' => Permissions::PATIENTS_VIEW,
                        ],
                        [
                            'label'      => 'Appointments',
                            'icon'       => 'fa-solid fa-calendar-check',
                            'route'      => 'modules/healthservices/appointments.php',
                            'permission' => Permissions::PATIENTS_VIEW,
                        ],
                        [
                            'label'      => 'Triage',
                            'icon'       => 'fa-solid fa-heart-pulse',
                            'route'      => 'modules/healthservices/triage.php',
                            'permission' => Permissions::TRIAGE_VIEW,
                        ],
                        [
                            'label'      => 'Prescriptions',
                            'icon'       => 'fa-solid fa-prescription-bottle',
                            'route'      => 'modules/healthservices/prescriptions.php',
                            'permission' => Permissions::PRESCRIPTIONS_VIEW,
                        ],
                        [
                            'label'      => 'Referrals',
                            'icon'       => 'fa-solid fa-arrow-right-arrow-left',
                            'route'      => 'modules/healthservices/referrals.php',
                            'permission' => Permissions::PATIENTS_VIEW,
                        ],
                    ],
                ],
                [
                    'key'         => 'sanitation',
                    'label'       => 'Sanitation Permits',
                    'icon'        => 'fa-solid fa-clipboard-check',
                    'match_path'  => 'modules/sanitation',
                    'permissions' => [Permissions::PERMITS_VIEW, Permissions::INSPECTIONS_VIEW, Permissions::INSPECTIONS_CONDUCT],
                    'children'    => [
                        [
                            'label'      => 'Permit Applications',
                            'icon'       => 'fa-solid fa-file-pen',
                            'route'      => 'modules/sanitation/permit_applications.php',
                            'permission' => Permissions::PERMITS_VIEW,
                        ],
                        [
                            'label'      => 'Inspections',
                            'icon'       => 'fa-solid fa-search',
                            'route'      => 'modules/sanitation/inspections.php',
                            'permissions'=> [Permissions::INSPECTIONS_VIEW, Permissions::INSPECTIONS_CONDUCT],
                        ],
                        [
                            'label'      => 'Permit Records',
                            'icon'       => 'fa-solid fa-folder',
                            'route'      => 'modules/sanitation/permit_records.php',
                            'permission' => Permissions::PERMITS_VIEW,
                        ],
                        [
                            'label'      => 'Payments',
                            'icon'       => 'fa-solid fa-money-bill-wave',
                            'route'      => 'modules/sanitation/payments.php',
                            'permission' => Permissions::PERMITS_VIEW,
                        ],
                        [
                            'label'      => 'Documents',
                            'icon'       => 'fa-solid fa-file',
                            'route'      => 'modules/sanitation/documents.php',
                            'permission' => Permissions::PERMITS_VIEW,
                        ],
                        [
                            'label'      => 'Renewals',
                            'icon'       => 'fa-solid fa-rotate',
                            'route'      => 'modules/sanitation/renewals.php',
                            'permission' => Permissions::PERMITS_VIEW,
                        ],
                    ],
                ],
                [
                    'key'         => 'immunization',
                    'label'       => 'Immunization & Nutrition',
                    'icon'        => 'fa-solid fa-syringe',
                    'match_path'  => 'modules/immunization',
                    'permissions' => [Permissions::IMMUNIZATION_VIEW],
                    'children'    => [
                        [
                            'label'      => 'Child Records',
                            'icon'       => 'fa-solid fa-child',
                            'route'      => 'modules/immunization/child_records.php',
                            'permission' => Permissions::IMMUNIZATION_VIEW,
                        ],
                        [
                            'label'      => 'Vaccination Tracking',
                            'icon'       => 'fa-solid fa-vial',
                            'route'      => 'modules/immunization/vaccination_tracking.php',
                            'permission' => Permissions::IMMUNIZATION_VIEW,
                        ],
                        [
                            'label'      => 'Growth Charts',
                            'icon'       => 'fa-solid fa-chart-line',
                            'route'      => 'modules/immunization/growth_charts.php',
                            'permission' => Permissions::IMMUNIZATION_VIEW,
                        ],
                        [
                            'label'      => 'Vaccine Inventory',
                            'icon'       => 'fa-solid fa-boxes',
                            'route'      => 'modules/immunization/vaccine_inventory.php',
                            'permission' => Permissions::IMMUNIZATION_VIEW,
                        ],
                        [
                            'label'      => 'Nutrition Assessment',
                            'icon'       => 'fa-solid fa-apple-alt',
                            'route'      => 'modules/immunization/nutrition_assessment.php',
                            'permission' => Permissions::IMMUNIZATION_VIEW,
                        ],
                    ],
                ],
                [
                    'key'         => 'wastewater',
                    'label'       => 'Wastewater Services',
                    'icon'        => 'fa-solid fa-droplet',
                    'match_path'  => 'modules/services',
                    'permissions' => [Permissions::PERMITS_VIEW, Permissions::INSPECTIONS_VIEW],
                    'children'    => [
                        [
                            'label'      => 'Facility Inspection',
                            'icon'       => 'fa-solid fa-building-circle-check',
                            'route'      => 'modules/services/facility_inspection.php',
                            'permission' => Permissions::INSPECTIONS_VIEW,
                        ],
                        [
                            'label'      => 'Discharge Permits',
                            'icon'       => 'fa-solid fa-file-contract',
                            'route'      => 'modules/services/discharge_permits.php',
                            'permission' => Permissions::PERMITS_VIEW,
                        ],
                    ],
                ],
                [
                    'key'         => 'surveillance',
                    'label'       => 'Health Surveillance',
                    'icon'        => 'fa-solid fa-shield-virus',
                    'match_path'  => 'modules/surveillence',
                    'permissions' => [Permissions::COMPLIANCE_VIEW, Permissions::ANALYTICS_VIEW, Permissions::PATIENTS_VIEW, Permissions::INSPECTIONS_VIEW],
                    'children'    => [
                        [
                            'label'      => 'Outbreak Monitoring',
                            'icon'       => 'fa-solid fa-virus',
                            'route'      => 'modules/surveillence/outbreak_monitoring.php',
                            'permission' => Permissions::COMPLIANCE_VIEW,
                        ],
                        [
                            'label'      => 'Disease Tracking',
                            'icon'       => 'fa-solid fa-chart-pie',
                            'route'      => 'modules/surveillence/disease_tracking.php',
                            'permission' => Permissions::ANALYTICS_VIEW,
                        ],
                    ],
                ],
            ],
        ],
        [
            'key'        => 'system_management',
            'label'      => 'System Management',
            'permissions'=> [Permissions::USERS_VIEW, Permissions::ROLES_MANAGE, Permissions::LOGS_VIEW, Permissions::SETTINGS_MANAGE],
            'items'      => [
                [
                    'key'         => 'user_management',
                    'label'       => 'User Management',
                    'icon'        => 'fa-solid fa-users-gear',
                    'route'       => 'management/user_management.php',
                    'permissions' => [Permissions::USERS_VIEW, Permissions::ROLES_MANAGE],
                    'match_paths' => ['user_management.php'],
                ],
                [
                    'key'         => 'system_logs',
                    'label'       => 'System Logs',
                    'icon'        => 'fa-solid fa-clock-rotate-left',
                    'route'       => 'management/system_logs.php',
                    'permission'  => Permissions::LOGS_VIEW,
                    'match_paths' => ['system_logs.php'],
                ],
                [
                    'key'         => 'settings',
                    'label'       => 'Settings',
                    'icon'        => 'fa-solid fa-gear',
                    'route'       => 'management/settings.php',
                    'permission'  => Permissions::SETTINGS_MANAGE,
                    'match_paths' => ['settings.php'],
                ],
            ],
        ],
    ],
];
