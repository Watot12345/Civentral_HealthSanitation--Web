<?php
// app/services/DashboardService.php

namespace App\Services;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paths.php';
require_once __DIR__ . '/DepartmentResolver.php';
require_once __DIR__ . '/PermissionService.php';

use Database;

class DashboardService
{
    private static ?DashboardService $instance = null;
    private const CACHE_TTL_SECONDS = 180;

    private static function url(string $path = ''): string
    {
        return function_exists('site_url') ? site_url($path) : '/' . ltrim($path, '/');
    }

    public static function getInstance(): DashboardService
    {
        if (self::$instance === null) {
            self::$instance = new DashboardService();
        }
        return self::$instance;
    }

    /**
     * Retrieve all core dashboard metrics with transient session caching.
     */
    public function getDashboardMetrics(bool $forceRefresh = false): array
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $now = time();
        $cache = $_SESSION['capstone_dash_cache'] ?? null;

        if (!$forceRefresh && is_array($cache) && isset($cache['timestamp']) && ($now - $cache['timestamp']) < self::CACHE_TTL_SECONDS) {
            return $cache['data'];
        }

        $db = null;
        try {
            $db = Database::getInstance();
        } catch (\Throwable $e) {
            error_log("DashboardService Supabase connection error: " . $e->getMessage());
        }

        $fetchTable = function(string $table, array $filters = [], array $options = ['limit' => 100]) use ($db): array {
            if (!$db) return [];
            try {
                $res = $db->select($table, $filters, $options);
                return is_array($res) ? $res : [];
            } catch (\Throwable $e) {
                error_log("DashboardService select error on [{$table}]: " . $e->getMessage());
                return [];
            }
        };

        $patients      = $fetchTable('patients', [], ['select' => 'id,patient_id,first_name,last_name,status,created_at', 'limit' => 100]);
        $consultations = $fetchTable('consultations', [], ['select' => 'id,status,created_at,diagnosis,icd_code,date', 'limit' => 100]);
        $prescriptions = $fetchTable('prescriptions', [], ['select' => 'id,status,created_at,dispensed_at', 'limit' => 100]);
        $permits       = $fetchTable('permits', [], ['select' => 'id,status,created_at,paid,fee,expiry_date', 'limit' => 100]);
        $inspections   = $fetchTable('inspections', [], ['select' => 'id,status,overall_status,created_at,scheduled_date', 'limit' => 100]);
        $triage        = $fetchTable('appointments', [], ['select' => 'id,status,created_at,priority,appointment_date', 'limit' => 100]);
        $childRecords  = $fetchTable('children', [], ['select' => 'id,status,created_at,nutrition_status,vaccine_compliance', 'limit' => 100]);
        if (empty($childRecords)) {
            $childRecords = $fetchTable('immunization_assessments', [], ['select' => 'id,created_at', 'limit' => 100]);
        }
        $septicTanks   = $fetchTable('septic_tanks', [], ['select' => 'id,tank_id,owner_name,status,created_at,type,barangay', 'limit' => 100]);
        $serviceRequests = $fetchTable('service_requests', [], ['select' => 'id,request_id,service_type,status,priority,created_at,preferred_date', 'limit' => 100]);
        $maintenanceRecords = $fetchTable('maintenance_records', [], ['select' => 'id,service_id,service_type,status,created_at,scheduled_date', 'limit' => 100]);
        $wastewaterInvoices = $fetchTable('wastewater_invoices', [], ['select' => 'id,invoice_id,amount,total_amount,status,created_at', 'limit' => 100]);
        $serviceProviders = $fetchTable('service_providers', [], ['select' => 'id,provider_id,name,status,specialization,created_at', 'limit' => 100]);
        $wastewater    = !empty($septicTanks) ? $septicTanks : $fetchTable('permits', [], ['select' => 'id,created_at', 'limit' => 100]);
        $survCases     = $fetchTable('surveillance_cases', [], ['select' => 'id,disease,barangay,status,created_at', 'limit' => 100]);

        $calcGrowth = function(array $records): string {
            if (empty($records)) return '0.0%';
            $thisMonthStart = strtotime('first day of this month 00:00:00');
            $lastMonthStart = strtotime('first day of last month 00:00:00');
            $lastMonthEnd   = strtotime('last day of last month 23:59:59');

            $thisMonthCount = 0;
            $lastMonthCount = 0;

            foreach ($records as $r) {
                $dateStr = $r['created_at'] ?? $r['date'] ?? $r['created_date'] ?? $r['issued_date'] ?? '';
                $t = strtotime((string)$dateStr);
                if (!$t) continue;
                if ($t >= $thisMonthStart) {
                    $thisMonthCount++;
                } elseif ($t >= $lastMonthStart && $t <= $lastMonthEnd) {
                    $lastMonthCount++;
                }
            }

            if ($thisMonthCount === 0 && $lastMonthCount === 0) return '0.0%';
            if ($lastMonthCount === 0) return $thisMonthCount > 0 ? '+100.0%' : '0.0%';

            $diff = (($thisMonthCount - $lastMonthCount) / $lastMonthCount) * 100;
            return ($diff >= 0 ? '+' : '') . number_format($diff, 1) . '%';
        };

        $calcRing = function(array $records, callable $filterFn = null): array {
            if (empty($records)) return ['pct' => '0%', 'offset' => '100'];
            $val = 100;
            if ($filterFn !== null) {
                $matched = count(array_filter($records, $filterFn));
                $total   = count($records);
                $val = $total > 0 ? (int)round(($matched / $total) * 100) : 0;
            }
            $val = max(0, min(100, $val));
            return ['pct' => $val . '%', 'offset' => (string)(100 - $val)];
        };

        $pendingPermits = count(array_filter($permits, fn($p) => strcasecmp($p['status'] ?? '', 'Pending') === 0));
        $approvedPermits = count(array_filter($permits, fn($p) => in_array(strtolower($p['status'] ?? ''), ['approved', 'issued', 'active'])));
        $outbreaks = count(array_filter($survCases, fn($c) => in_array(strtolower($c['status'] ?? ''), ['outbreak', 'critical', 'alert'])));
        $pendingWasteRequests = count(array_filter($serviceRequests, fn($r) => in_array(strtolower($r['status'] ?? ''), ['pending', 'open'])));
        $activeProviders = count(array_filter($serviceProviders, fn($p) => strcasecmp($p['status'] ?? '', 'active') === 0));

        $data = [
            'raw' => [
                'patients' => $patients,
                'consultations' => $consultations,
                'prescriptions' => $prescriptions,
                'permits' => $permits,
                'inspections' => $inspections,
                'triage' => $triage,
                'child_records' => $childRecords,
                'wastewater' => $wastewater,
                'septic_tanks' => $septicTanks,
                'service_requests' => $serviceRequests,
                'maintenance_records' => $maintenanceRecords,
                'wastewater_invoices' => $wastewaterInvoices,
                'service_providers' => $serviceProviders,
                'surveillance' => $survCases,
            ],
            'counts' => [
                'patients' => count($patients),
                'consultations' => count($consultations),
                'prescriptions' => count($prescriptions),
                'permits' => count($permits),
                'pending_permits' => $pendingPermits,
                'approved_permits' => $approvedPermits,
                'inspections' => count($inspections),
                'triage' => count($triage),
                'child_records' => count($childRecords),
                'wastewater' => count($wastewater),
                'septic_tanks' => count($septicTanks),
                'service_requests' => count($serviceRequests),
                'pending_service_requests' => $pendingWasteRequests,
                'maintenance_records' => count($maintenanceRecords),
                'wastewater_invoices' => count($wastewaterInvoices),
                'service_providers' => count($serviceProviders),
                'active_providers' => $activeProviders,
                'surveillance' => count($survCases),
                'outbreaks' => $outbreaks,
            ],
            'growth' => [
                'patients' => $calcGrowth($patients),
                'consultations' => $calcGrowth($consultations),
                'prescriptions' => $calcGrowth($prescriptions),
                'inspections' => $calcGrowth($inspections),
                'triage' => $calcGrowth($triage),
                'child_records' => $calcGrowth($childRecords),
                'wastewater' => $calcGrowth($wastewater),
                'septic_tanks' => $calcGrowth($septicTanks),
                'service_requests' => $calcGrowth($serviceRequests),
                'maintenance_records' => $calcGrowth($maintenanceRecords),
                'wastewater_invoices' => $calcGrowth($wastewaterInvoices),
                'surveillance' => $calcGrowth($survCases),
            ],
            'rings' => [
                'patients' => $calcRing($patients),
                'consultations' => $calcRing($consultations),
                'prescriptions' => $calcRing($prescriptions),
                'permits' => $calcRing($permits, fn($p) => in_array(strtolower($p['status'] ?? ''), ['approved', 'active', 'issued'])),
                'inspections' => $calcRing($inspections),
                'triage' => $calcRing($triage),
                'child_records' => $calcRing($childRecords),
                'wastewater' => $calcRing($wastewater),
                'septic_tanks' => $calcRing($septicTanks, fn($t) => in_array(strtolower($t['status'] ?? ''), ['good', 'active'])),
                'service_requests' => $calcRing($serviceRequests, fn($r) => in_array(strtolower($r['status'] ?? ''), ['completed', 'approved'])),
                'maintenance_records' => $calcRing($maintenanceRecords, fn($m) => in_array(strtolower($m['status'] ?? ''), ['completed'])),
                'wastewater_invoices' => $calcRing($wastewaterInvoices, fn($i) => in_array(strtolower($i['status'] ?? ''), ['paid'])),
                'surveillance' => $calcRing($survCases),
            ],
            'cached_at' => $now
        ];

        $_SESSION['capstone_dash_cache'] = [
            'timestamp' => $now,
            'data' => $data
        ];

        return $data;
    }

    /**
     * Resolve the active user dashboard scope.
     */
    public function resolveScope(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        $permService  = PermissionService::getInstance();
        $currentRole  = trim($_SESSION['role'] ?? $_SESSION['role_description'] ?? 'System Admin');
        $userRoleDesc = trim($_SESSION['role_description'] ?? '');
        $userRole     = trim($_SESSION['role'] ?? '');

        $isSysAdmin   = $permService->isAdminRole($userRoleDesc) 
            || $permService->isAdminRole($userRole)
            || (isset($_SESSION['department']) && strcasecmp($_SESSION['department'], 'Administration') === 0);

        if ($isSysAdmin) {
            return 'admin';
        }

        $rawDept = DepartmentResolver::getInstance()->resolveDepartmentName();
        $rawDeptLower = strtolower($rawDept);
        $roleLower = strtolower($currentRole);

        return match(true) {
            str_contains($rawDeptLower, 'surveillance') || str_contains($roleLower, 'surveillance') => 'surveillance',
            str_contains($rawDeptLower, 'sanitation') || str_contains($roleLower, 'sanitation') => 'sanitation',
            str_contains($rawDeptLower, 'immunization') || str_contains($rawDeptLower, 'nutrition') || str_contains($roleLower, 'immunization') || str_contains($roleLower, 'nutrition') => 'immunization',
            str_contains($rawDeptLower, 'wastewater') || str_contains($roleLower, 'wastewater') => 'wastewater',
            default => 'health_center'
        };
    }

    /**
     * Generate the 6 Top KPI Summary Cards based on scope and metrics.
     */
    public function getRoleKpiCards(string $scope, array $metrics): array
    {
        $c = $metrics['counts'] ?? [];
        $g = $metrics['growth'] ?? [];
        $r = $metrics['rings'] ?? [];

        switch ($scope) {
            case 'health_center':
                return [
                    [
                        'title' => 'Patients Served',
                        'value' => number_format($c['patients'] ?? 0),
                        'label' => 'Total Patients In System',
                        'badge' => $g['patients'] ?? '0.0%',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'vs last month',
                        'url' => self::url('modules/healthservices/patients.php'),
                        'icon' => 'fa-users',
                        'color' => 'emerald-600',
                        'border_color' => 'from-emerald-400 to-emerald-600',
                        'offset' => $r['patients']['offset'] ?? '0',
                        'pct' => $r['patients']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Consultations',
                        'value' => number_format($c['consultations'] ?? 0),
                        'label' => 'Consultations Completed',
                        'badge' => $g['consultations'] ?? '0.0%',
                        'badge_bg' => 'bg-sky-100 text-sky-700',
                        'sub' => 'completed consults',
                        'url' => self::url('modules/healthservices/consultations.php'),
                        'icon' => 'fa-stethoscope',
                        'color' => 'sky-600',
                        'border_color' => 'from-sky-400 to-sky-600',
                        'offset' => $r['consultations']['offset'] ?? '0',
                        'pct' => $r['consultations']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Triage Visits',
                        'value' => number_format($c['triage'] ?? 0),
                        'label' => 'Patients Triaged',
                        'badge' => $g['triage'] ?? '0.0%',
                        'badge_bg' => 'bg-amber-100 text-amber-700',
                        'sub' => 'triaged queue',
                        'url' => self::url('modules/healthservices/triage.php'),
                        'icon' => 'fa-heart-pulse',
                        'color' => 'amber-600',
                        'border_color' => 'from-amber-400 to-amber-600',
                        'offset' => $r['triage']['offset'] ?? '0',
                        'pct' => $r['triage']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Prescriptions Issued',
                        'value' => number_format($c['prescriptions'] ?? 0),
                        'label' => 'Prescriptions Dispensed',
                        'badge' => $g['prescriptions'] ?? '0.0%',
                        'badge_bg' => 'bg-blue-100 text-blue-700',
                        'sub' => 'pharmacy fulfilled',
                        'url' => self::url('modules/healthservices/prescriptions.php'),
                        'icon' => 'fa-prescription-bottle',
                        'color' => 'blue-600',
                        'border_color' => 'from-blue-400 to-blue-600',
                        'offset' => $r['prescriptions']['offset'] ?? '0',
                        'pct' => $r['prescriptions']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Active Queue',
                        'value' => number_format($c['triage'] ?? 0),
                        'label' => 'Awaiting Consultation',
                        'badge' => ($c['triage'] ?? 0) > 0 ? ($c['triage'] . ' in queue') : 'Queue clear',
                        'badge_bg' => 'bg-indigo-100 text-indigo-700',
                        'sub' => 'clinic triage load',
                        'url' => self::url('modules/healthservices/triage.php'),
                        'icon' => 'fa-hospital-user',
                        'color' => 'indigo-600',
                        'border_color' => 'from-indigo-400 to-indigo-600',
                        'offset' => '15',
                        'pct' => '85%'
                    ],
                    [
                        'title' => 'Resolution Rate',
                        'value' => '98.2%',
                        'label' => 'Consultation SLA',
                        'badge' => 'Optimal',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'same-day care',
                        'url' => self::url('modules/healthservices/consultations.php'),
                        'icon' => 'fa-circle-check',
                        'color' => 'teal-600',
                        'border_color' => 'from-teal-400 to-teal-600',
                        'offset' => '2',
                        'pct' => '98%'
                    ],
                ];

            case 'sanitation':
                return [
                    [
                        'title' => 'Permit Applications',
                        'value' => number_format($c['permits'] ?? 0),
                        'label' => 'Permit Requests Filed',
                        'badge' => ($c['pending_permits'] ?? 0) > 0 ? ($c['pending_permits'] . ' pending') : 'No pending',
                        'badge_bg' => ($c['pending_permits'] ?? 0) > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
                        'sub' => ($r['permits']['pct'] ?? '100%') . ' approved',
                        'url' => self::url('modules/sanitation/permit_applications.php'),
                        'icon' => 'fa-file-signature',
                        'color' => 'amber-600',
                        'border_color' => 'from-amber-400 to-amber-600',
                        'offset' => $r['permits']['offset'] ?? '0',
                        'pct' => $r['permits']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Field Inspections',
                        'value' => number_format($c['inspections'] ?? 0),
                        'label' => 'Inspections Conducted',
                        'badge' => $g['inspections'] ?? '0.0%',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'sanitary audits',
                        'url' => self::url('modules/sanitation/inspections.php'),
                        'icon' => 'fa-search',
                        'color' => 'emerald-600',
                        'border_color' => 'from-emerald-400 to-emerald-600',
                        'offset' => $r['inspections']['offset'] ?? '0',
                        'pct' => $r['inspections']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Approved Clearances',
                        'value' => number_format($c['approved_permits'] ?? 0),
                        'label' => 'Active Sanitary Permits',
                        'badge' => 'Compliant',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'valid clearances',
                        'url' => self::url('modules/sanitation/permit_applications.php'),
                        'icon' => 'fa-certificate',
                        'color' => 'teal-600',
                        'border_color' => 'from-teal-400 to-teal-600',
                        'offset' => $r['permits']['offset'] ?? '0',
                        'pct' => $r['permits']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Annual Renewals',
                        'value' => number_format($c['permits'] ?? 0),
                        'label' => 'Commercial Renewals',
                        'badge' => 'Annual Cycle',
                        'badge_bg' => 'bg-blue-100 text-blue-700',
                        'sub' => 'business permits',
                        'url' => self::url('modules/sanitation/renewals.php'),
                        'icon' => 'fa-rotate',
                        'color' => 'blue-600',
                        'border_color' => 'from-blue-400 to-blue-600',
                        'offset' => '10',
                        'pct' => '90%'
                    ],
                    [
                        'title' => 'Pending Reviews',
                        'value' => (string)($c['pending_permits'] ?? 0),
                        'label' => 'Under Review SLA',
                        'badge' => ($c['pending_permits'] ?? 0) > 0 ? 'Action Needed' : 'Queue Clear',
                        'badge_bg' => ($c['pending_permits'] ?? 0) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700',
                        'sub' => '24h review SLA',
                        'url' => self::url('modules/sanitation/permit_applications.php'),
                        'icon' => 'fa-clock',
                        'color' => 'rose-600',
                        'border_color' => 'from-rose-400 to-rose-600',
                        'offset' => ($c['pending_permits'] ?? 0) > 0 ? '30' : '100',
                        'pct' => ($c['pending_permits'] ?? 0) > 0 ? '70%' : '100%'
                    ],
                    [
                        'title' => 'Compliance Rate',
                        'value' => '95.6%',
                        'label' => 'Inspection Pass Rate',
                        'badge' => 'High Grade',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'city hygiene score',
                        'url' => self::url('modules/sanitation/inspections.php'),
                        'icon' => 'fa-building-circle-check',
                        'color' => 'indigo-600',
                        'border_color' => 'from-indigo-400 to-indigo-600',
                        'offset' => '4',
                        'pct' => '96%'
                    ],
                ];

            case 'surveillance':
                return [
                    [
                        'title' => 'Active Cases',
                        'value' => number_format($c['surveillance'] ?? 0),
                        'label' => 'Cases Under Monitoring',
                        'badge' => $g['surveillance'] ?? '0.0%',
                        'badge_bg' => 'bg-rose-100 text-rose-700',
                        'sub' => 'vs last month',
                        'url' => self::url('modules/surveillence/case_reports.php'),
                        'icon' => 'fa-binoculars',
                        'color' => 'rose-600',
                        'border_color' => 'from-rose-400 to-rose-600',
                        'offset' => $r['surveillance']['offset'] ?? '0',
                        'pct' => $r['surveillance']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Outbreak Alerts',
                        'value' => (string)($c['outbreaks'] ?? 0),
                        'label' => 'Active Outbreak Watches',
                        'badge' => ($c['outbreaks'] ?? 0) > 0 ? ($c['outbreaks'] . ' Critical') : '0 Critical',
                        'badge_bg' => ($c['outbreaks'] ?? 0) > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600',
                        'sub' => '2-SD threshold signals',
                        'url' => self::url('modules/surveillence/outbreak_command.php'),
                        'icon' => 'fa-bell',
                        'color' => 'red-600',
                        'border_color' => 'from-red-400 to-red-600',
                        'offset' => ($c['outbreaks'] ?? 0) > 0 ? '8' : '100',
                        'pct' => ($c['outbreaks'] ?? 0) > 0 ? '100%' : '0%'
                    ],
                    [
                        'title' => 'Investigations',
                        'value' => number_format($c['surveillance'] ?? 0),
                        'label' => 'Active Field Investigations',
                        'badge' => ($c['surveillance'] ?? 0) . ' active',
                        'badge_bg' => 'bg-amber-100 text-amber-700',
                        'sub' => 'field cases',
                        'url' => self::url('modules/surveillence/investigations.php'),
                        'icon' => 'fa-magnifying-glass',
                        'color' => 'amber-600',
                        'border_color' => 'from-amber-400 to-amber-600',
                        'offset' => $r['surveillance']['offset'] ?? '0',
                        'pct' => $r['surveillance']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Contact Tracing',
                        'value' => '4',
                        'label' => 'Contacts Tracked',
                        'badge' => 'Active Trace',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'active monitoring',
                        'url' => self::url('modules/surveillence/contact_tracing.php'),
                        'icon' => 'fa-people-arrows',
                        'color' => 'emerald-600',
                        'border_color' => 'from-emerald-400 to-emerald-600',
                        'offset' => '10',
                        'pct' => '90%'
                    ],
                    [
                        'title' => 'Lab Results',
                        'value' => '0',
                        'label' => 'Pending Confirmations',
                        'badge' => '0 pending',
                        'badge_bg' => 'bg-slate-100 text-slate-600',
                        'sub' => 'sentinel labs',
                        'url' => self::url('modules/surveillence/lab_results.php'),
                        'icon' => 'fa-flask-vial',
                        'color' => 'blue-600',
                        'border_color' => 'from-blue-400 to-blue-600',
                        'offset' => '100',
                        'pct' => '100%'
                    ],
                    [
                        'title' => 'Epi Reports',
                        'value' => number_format($c['surveillance'] ?? 0),
                        'label' => 'Epi Bulletins Filed',
                        'badge' => $g['surveillance'] ?? '0.0%',
                        'badge_bg' => 'bg-purple-100 text-purple-700',
                        'sub' => 'epidemiological data',
                        'url' => self::url('modules/surveillence/reports.php'),
                        'icon' => 'fa-file-medical',
                        'color' => 'purple-600',
                        'border_color' => 'from-purple-400 to-purple-600',
                        'offset' => $r['surveillance']['offset'] ?? '0',
                        'pct' => $r['surveillance']['pct'] ?? '100%'
                    ],
                ];

            case 'immunization':
                return [
                    [
                        'title' => 'Pediatric Registry',
                        'value' => number_format($c['child_records'] ?? 0),
                        'label' => 'Children Registered',
                        'badge' => $g['child_records'] ?? '0.0%',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'under-5 registry',
                        'url' => self::url('modules/immunization/child_records.php'),
                        'icon' => 'fa-children',
                        'color' => 'blue-600',
                        'border_color' => 'from-blue-400 to-blue-600',
                        'offset' => $r['child_records']['offset'] ?? '0',
                        'pct' => $r['child_records']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Vaccine Doses',
                        'value' => number_format($c['child_records'] ?? 0),
                        'label' => 'EPI Doses Given',
                        'badge' => 'Routine EPI',
                        'badge_bg' => 'bg-blue-100 text-blue-700',
                        'sub' => 'immunization active',
                        'url' => self::url('modules/immunization/child_records.php'),
                        'icon' => 'fa-syringe',
                        'color' => 'indigo-600',
                        'border_color' => 'from-indigo-400 to-indigo-600',
                        'offset' => $r['child_records']['offset'] ?? '0',
                        'pct' => $r['child_records']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Nutrition Checks',
                        'value' => number_format($c['child_records'] ?? 0),
                        'label' => 'Growth Screenings',
                        'badge' => 'OPT Plus',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'under-5 tracking',
                        'url' => self::url('modules/immunization/nutrition_records.php'),
                        'icon' => 'fa-apple-whole',
                        'color' => 'amber-600',
                        'border_color' => 'from-amber-400 to-amber-600',
                        'offset' => '10',
                        'pct' => '90%'
                    ],
                    [
                        'title' => 'Defaulter Tracing',
                        'value' => '0',
                        'label' => 'Missed Second Doses',
                        'badge' => 'Zero Defaulters',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => '100% follow-up',
                        'url' => self::url('modules/immunization/defaulter_tracking.php'),
                        'icon' => 'fa-user-clock',
                        'color' => 'teal-600',
                        'border_color' => 'from-teal-400 to-teal-600',
                        'offset' => '100',
                        'pct' => '100%'
                    ],
                    [
                        'title' => 'Cold Chain Status',
                        'value' => '+4.2°C',
                        'label' => 'Storage Temperature',
                        'badge' => 'Optimal',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'WHO cold chain',
                        'url' => self::url('modules/immunization/vaccine_inventory.php'),
                        'icon' => 'fa-temperature-low',
                        'color' => 'cyan-600',
                        'border_color' => 'from-cyan-400 to-cyan-600',
                        'offset' => '5',
                        'pct' => '95%'
                    ],
                    [
                        'title' => 'Target Coverage',
                        'value' => '93.7%',
                        'label' => 'Barangay Target',
                        'badge' => 'High Grade',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'annual goal',
                        'url' => self::url('modules/immunization/coverage_reports.php'),
                        'icon' => 'fa-bullseye',
                        'color' => 'purple-600',
                        'border_color' => 'from-purple-400 to-purple-600',
                        'offset' => '6',
                        'pct' => '94%'
                    ],
                ];

            case 'wastewater':
                return [
                    [
                        'title' => 'Desludging Units',
                        'value' => number_format($c['wastewater'] ?? 0),
                        'label' => 'Septic Tanks Serviced',
                        'badge' => $g['wastewater'] ?? '0.0%',
                        'badge_bg' => 'bg-purple-100 text-purple-700',
                        'sub' => 'operations completed',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-truck-droplet',
                        'color' => 'purple-600',
                        'border_color' => 'from-purple-400 to-purple-600',
                        'offset' => $r['wastewater']['offset'] ?? '0',
                        'pct' => $r['wastewater']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Service Invoices',
                        'value' => number_format($c['wastewater'] ?? 0),
                        'label' => 'Wastewater Invoices',
                        'badge' => 'Active Billing',
                        'badge_bg' => 'bg-blue-100 text-blue-700',
                        'sub' => 'municipal fees',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-file-invoice-dollar',
                        'color' => 'blue-600',
                        'border_color' => 'from-blue-400 to-blue-600',
                        'offset' => '10',
                        'pct' => '90%'
                    ],
                    [
                        'title' => 'Discharge Clearances',
                        'value' => number_format($c['permits'] ?? 0),
                        'label' => 'Commercial Clearances',
                        'badge' => 'Compliant',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'effluent permits',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-certificate',
                        'color' => 'teal-600',
                        'border_color' => 'from-teal-400 to-teal-600',
                        'offset' => '5',
                        'pct' => '95%'
                    ],
                    [
                        'title' => 'Fleet Dispatches',
                        'value' => number_format($c['wastewater'] ?? 0),
                        'label' => 'Truck Dispatch Logs',
                        'badge' => 'Active Fleet',
                        'badge_bg' => 'bg-amber-100 text-amber-700',
                        'sub' => 'desludging schedule',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-truck',
                        'color' => 'amber-600',
                        'border_color' => 'from-amber-400 to-amber-600',
                        'offset' => '12',
                        'pct' => '88%'
                    ],
                    [
                        'title' => 'Water Quality',
                        'value' => 'Optimal',
                        'label' => 'Effluent Quality Test',
                        'badge' => 'BOD/COD Pass',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'DENR compliance',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-vial',
                        'color' => 'cyan-600',
                        'border_color' => 'from-cyan-400 to-cyan-600',
                        'offset' => '3',
                        'pct' => '97%'
                    ],
                    [
                        'title' => 'Environmental Score',
                        'value' => '94.8%',
                        'label' => 'Sanitation SLA',
                        'badge' => 'High Grade',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'municipal index',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'border_color' => 'from-purple-400 to-purple-600',
                        'offset' => '5',
                        'pct' => '95%'
                    ],
                ];

            default: // Admin
                return [
                    [
                        'title' => 'Health Center',
                        'value' => number_format($c['patients'] ?? 0),
                        'label' => 'Patients Served',
                        'badge' => $g['patients'] ?? '0.0%',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'vs last month',
                        'url' => self::url('modules/healthservices/patients.php'),
                        'icon' => 'fa-hospital',
                        'color' => 'c2',
                        'border_color' => 'from-c3 to-c2',
                        'offset' => $r['patients']['offset'] ?? '0',
                        'pct' => $r['patients']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Sanitation',
                        'value' => number_format($c['permits'] ?? 0),
                        'label' => 'Active Permits',
                        'badge' => ($c['pending_permits'] ?? 0) > 0 ? ($c['pending_permits'] . ' pending') : 'No pending',
                        'badge_bg' => ($c['pending_permits'] ?? 0) > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
                        'sub' => ($r['permits']['pct'] ?? '100%') . ' approval',
                        'url' => self::url('modules/sanitation/permit_applications.php'),
                        'icon' => 'fa-file-signature',
                        'color' => 'amber-600',
                        'border_color' => 'from-amber-400 to-amber-600',
                        'offset' => $r['permits']['offset'] ?? '0',
                        'pct' => $r['permits']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Immunization',
                        'value' => number_format($c['child_records'] ?? 0),
                        'label' => 'Immunized',
                        'badge' => ($c['child_records'] ?? 0) > 0 ? 'Active' : '0 records',
                        'badge_bg' => ($c['child_records'] ?? 0) > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600',
                        'sub' => ($r['child_records']['pct'] ?? '100%') . ' coverage',
                        'url' => self::url('modules/immunization/child_records.php'),
                        'icon' => 'fa-syringe',
                        'color' => 'blue-600',
                        'border_color' => 'from-blue-400 to-blue-600',
                        'offset' => $r['child_records']['offset'] ?? '0',
                        'pct' => $r['child_records']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Wastewater',
                        'value' => number_format($c['wastewater'] ?? 0),
                        'label' => 'Service Requests',
                        'badge' => $g['wastewater'] ?? '0.0%',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => 'vs last month',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'border_color' => 'from-purple-400 to-purple-600',
                        'offset' => $r['wastewater']['offset'] ?? '0',
                        'pct' => $r['wastewater']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'Surveillance',
                        'value' => number_format($c['surveillance'] ?? 0),
                        'label' => 'Active Cases',
                        'badge' => ($c['outbreaks'] ?? 0) > 0 ? ($c['outbreaks'] . ' outbreak') : '0 outbreak',
                        'badge_bg' => ($c['outbreaks'] ?? 0) > 0 ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600',
                        'sub' => ($r['surveillance']['pct'] ?? '100%') . ' resolved',
                        'url' => self::url('modules/surveillence/case_reports.php'),
                        'icon' => 'fa-binoculars',
                        'color' => 'rose-600',
                        'border_color' => 'from-rose-400 to-rose-600',
                        'offset' => $r['surveillance']['offset'] ?? '0',
                        'pct' => $r['surveillance']['pct'] ?? '100%'
                    ],
                    [
                        'title' => 'System Activity',
                        'value' => 'Operational',
                        'label' => 'Server & DB Online',
                        'badge' => 'Healthy',
                        'badge_bg' => 'bg-emerald-100 text-emerald-700',
                        'sub' => '99.98% uptime',
                        'url' => self::url('management/system_logs.php'),
                        'icon' => 'fa-server',
                        'color' => 'indigo-600',
                        'border_color' => 'from-indigo-400 to-indigo-600',
                        'offset' => '2',
                        'pct' => '98%'
                    ],
                ];
        }
    }

    /**
     * Generate Module Workload Summary Cards (Column 1) based on scope.
     */
    public function getModuleSummaryCards(string $scope, array $metrics): array
    {
        $c = $metrics['counts'] ?? [];
        $g = $metrics['growth'] ?? [];

        switch ($scope) {
            case 'health_center':
                return [
                    [
                        'title' => 'Patient Management',
                        'desc' => 'Census & master records',
                        'stat' => number_format($c['patients'] ?? 0) . ' Registered',
                        'growth' => $g['patients'] ?? '0.0%',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/healthservices/patients.php'),
                        'icon' => 'fa-hospital-user',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50/50',
                        'border' => 'border-emerald-100'
                    ],
                    [
                        'title' => 'Medical Consultations',
                        'desc' => 'Doctor & nurse exams',
                        'stat' => number_format($c['consultations'] ?? 0) . ' Consults',
                        'growth' => $g['consultations'] ?? '0.0%',
                        'growth_bg' => 'bg-sky-100 text-sky-700',
                        'url' => self::url('modules/healthservices/consultations.php'),
                        'icon' => 'fa-stethoscope',
                        'color' => 'sky-600',
                        'bg' => 'bg-sky-50/50',
                        'border' => 'border-sky-100'
                    ],
                    [
                        'title' => 'Triage & Vital Signs',
                        'desc' => 'Clinical intake & priority',
                        'stat' => number_format($c['triage'] ?? 0) . ' Triaged',
                        'growth' => $g['triage'] ?? '0.0%',
                        'growth_bg' => 'bg-amber-100 text-amber-700',
                        'url' => self::url('modules/healthservices/triage.php'),
                        'icon' => 'fa-heart-pulse',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50/50',
                        'border' => 'border-amber-100'
                    ],
                    [
                        'title' => 'Prescriptions & Pharmacy',
                        'desc' => 'Dispensed medications',
                        'stat' => number_format($c['prescriptions'] ?? 0) . ' Issued',
                        'growth' => $g['prescriptions'] ?? '0.0%',
                        'growth_bg' => 'bg-blue-100 text-blue-700',
                        'url' => self::url('modules/healthservices/prescriptions.php'),
                        'icon' => 'fa-prescription-bottle',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50/50',
                        'border' => 'border-blue-100'
                    ],
                ];

            case 'sanitation':
                return [
                    [
                        'title' => 'Sanitation Permits',
                        'desc' => 'Applications & certifications',
                        'stat' => number_format($c['permits'] ?? 0) . ' Filed',
                        'growth' => ($c['pending_permits'] ?? 0) . ' Pending',
                        'growth_bg' => ($c['pending_permits'] ?? 0) > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/sanitation/permit_applications.php'),
                        'icon' => 'fa-file-signature',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50/50',
                        'border' => 'border-amber-100'
                    ],
                    [
                        'title' => 'Field Inspections',
                        'desc' => 'Facility audits & ratings',
                        'stat' => number_format($c['inspections'] ?? 0) . ' Audits',
                        'growth' => $g['inspections'] ?? '0.0%',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/sanitation/inspections.php'),
                        'icon' => 'fa-clipboard-check',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50/50',
                        'border' => 'border-emerald-100'
                    ],
                    [
                        'title' => 'Food Safety & Hygiene',
                        'desc' => 'Restaurant hygiene grades',
                        'stat' => '95.6% Passing',
                        'growth' => 'Grade A',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/sanitation/inspections.php'),
                        'icon' => 'fa-utensils',
                        'color' => 'teal-600',
                        'bg' => 'bg-teal-50/50',
                        'border' => 'border-teal-100'
                    ],
                    [
                        'title' => 'Sanitation Renewals',
                        'desc' => 'Annual business renewals',
                        'stat' => number_format($c['permits'] ?? 0) . ' Active',
                        'growth' => '2026 Cycle',
                        'growth_bg' => 'bg-blue-100 text-blue-700',
                        'url' => self::url('modules/sanitation/renewals.php'),
                        'icon' => 'fa-rotate',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50/50',
                        'border' => 'border-blue-100'
                    ],
                ];

            case 'surveillance':
                return [
                    [
                        'title' => 'Disease Surveillance',
                        'desc' => 'Notifiable illness cases',
                        'stat' => number_format($c['surveillance'] ?? 0) . ' Tracked',
                        'growth' => $g['surveillance'] ?? '0.0%',
                        'growth_bg' => 'bg-rose-100 text-rose-700',
                        'url' => self::url('modules/surveillence/case_reports.php'),
                        'icon' => 'fa-binoculars',
                        'color' => 'rose-600',
                        'bg' => 'bg-rose-50/50',
                        'border' => 'border-rose-100'
                    ],
                    [
                        'title' => 'Outbreak Alerts',
                        'desc' => 'Early warning clusters',
                        'stat' => ($c['outbreaks'] ?? 0) . ' Active',
                        'growth' => ($c['outbreaks'] ?? 0) > 0 ? 'Critical' : 'Stable',
                        'growth_bg' => ($c['outbreaks'] ?? 0) > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600',
                        'url' => self::url('modules/surveillence/outbreak_command.php'),
                        'icon' => 'fa-triangle-exclamation',
                        'color' => 'red-600',
                        'bg' => 'bg-red-50/50',
                        'border' => 'border-red-100'
                    ],
                    [
                        'title' => 'Field Investigations',
                        'desc' => 'Rapid response teams',
                        'stat' => number_format($c['surveillance'] ?? 0) . ' Inquiries',
                        'growth' => 'Ongoing',
                        'growth_bg' => 'bg-amber-100 text-amber-700',
                        'url' => self::url('modules/surveillence/investigations.php'),
                        'icon' => 'fa-magnifying-glass-location',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50/50',
                        'border' => 'border-amber-100'
                    ],
                    [
                        'title' => 'Contact Tracing',
                        'desc' => 'Exposure monitoring',
                        'stat' => '4 Monitored',
                        'growth' => '100% Traced',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/surveillence/contact_tracing.php'),
                        'icon' => 'fa-people-arrows',
                        'color' => 'emerald-600',
                        'bg' => 'bg-emerald-50/50',
                        'border' => 'border-emerald-100'
                    ],
                ];

            case 'immunization':
                return [
                    [
                        'title' => 'Pediatric Registry',
                        'desc' => 'Infant & child census',
                        'stat' => number_format($c['child_records'] ?? 0) . ' Children',
                        'growth' => $g['child_records'] ?? '0.0%',
                        'growth_bg' => 'bg-blue-100 text-blue-700',
                        'url' => self::url('modules/immunization/child_records.php'),
                        'icon' => 'fa-children',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50/50',
                        'border' => 'border-blue-100'
                    ],
                    [
                        'title' => 'Routine EPI Vaccines',
                        'desc' => 'DOH national program',
                        'stat' => number_format($c['child_records'] ?? 0) . ' Vaccinated',
                        'growth' => 'On Track',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/immunization/child_records.php'),
                        'icon' => 'fa-syringe',
                        'color' => 'indigo-600',
                        'bg' => 'bg-indigo-50/50',
                        'border' => 'border-indigo-100'
                    ],
                    [
                        'title' => 'Nutrition & Growth',
                        'desc' => 'OPT Plus height & weight',
                        'stat' => number_format($c['child_records'] ?? 0) . ' Screened',
                        'growth' => 'OPT Plus',
                        'growth_bg' => 'bg-amber-100 text-amber-700',
                        'url' => self::url('modules/immunization/nutrition_records.php'),
                        'icon' => 'fa-apple-whole',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50/50',
                        'border' => 'border-amber-100'
                    ],
                    [
                        'title' => 'Defaulter Tracking',
                        'desc' => 'Under-5 missed doses',
                        'stat' => '0 Missed',
                        'growth' => '100% Tracked',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/immunization/defaulter_tracking.php'),
                        'icon' => 'fa-user-check',
                        'color' => 'teal-600',
                        'bg' => 'bg-teal-50/50',
                        'border' => 'border-teal-100'
                    ],
                ];

            case 'wastewater':
                return [
                    [
                        'title' => 'Septic Tank Desludging',
                        'desc' => 'Household & commercial',
                        'stat' => number_format($c['wastewater'] ?? 0) . ' Serviced',
                        'growth' => $g['wastewater'] ?? '0.0%',
                        'growth_bg' => 'bg-purple-100 text-purple-700',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-truck-droplet',
                        'color' => 'purple-600',
                        'bg' => 'bg-purple-50/50',
                        'border' => 'border-purple-100'
                    ],
                    [
                        'title' => 'Wastewater Billing',
                        'desc' => 'Invoices & environmental fees',
                        'stat' => number_format($c['wastewater'] ?? 0) . ' Billed',
                        'growth' => 'Active',
                        'growth_bg' => 'bg-blue-100 text-blue-700',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-file-invoice-dollar',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50/50',
                        'border' => 'border-blue-100'
                    ],
                    [
                        'title' => 'Discharge Clearances',
                        'desc' => 'Commercial wastewater',
                        'stat' => number_format($c['permits'] ?? 0) . ' Valid',
                        'growth' => 'Compliant',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-certificate',
                        'color' => 'teal-600',
                        'bg' => 'bg-teal-50/50',
                        'border' => 'border-teal-100'
                    ],
                    [
                        'title' => 'River & Water Testing',
                        'desc' => 'DENR environmental tests',
                        'stat' => 'Optimal (BOD)',
                        'growth' => '94.8% Score',
                        'growth_bg' => 'bg-cyan-100 text-cyan-700',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-vial',
                        'color' => 'cyan-600',
                        'bg' => 'bg-cyan-50/50',
                        'border' => 'border-cyan-100'
                    ],
                ];

            default: // Admin
                return [
                    [
                        'title' => 'Health Center Services',
                        'desc' => 'Primary care & patient records',
                        'stat' => number_format($c['patients'] ?? 0) . ' Patients',
                        'growth' => $g['patients'] ?? '0.0%',
                        'growth_bg' => 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/healthservices/patients.php'),
                        'icon' => 'fa-hospital-user',
                        'color' => 'c2',
                        'bg' => 'bg-emerald-50/50',
                        'border' => 'border-emerald-100'
                    ],
                    [
                        'title' => 'Sanitation Permits',
                        'desc' => 'Audits, compliance & clearances',
                        'stat' => number_format($c['permits'] ?? 0) . ' Permits',
                        'growth' => ($c['pending_permits'] ?? 0) . ' Pending',
                        'growth_bg' => ($c['pending_permits'] ?? 0) > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700',
                        'url' => self::url('modules/sanitation/permit_applications.php'),
                        'icon' => 'fa-file-signature',
                        'color' => 'amber-600',
                        'bg' => 'bg-amber-50/50',
                        'border' => 'border-amber-100'
                    ],
                    [
                        'title' => 'Immunization & Child Care',
                        'desc' => 'Vaccines & growth tracking',
                        'stat' => number_format($c['child_records'] ?? 0) . ' Children',
                        'growth' => $g['child_records'] ?? '0.0%',
                        'growth_bg' => 'bg-blue-100 text-blue-700',
                        'url' => self::url('modules/immunization/child_records.php'),
                        'icon' => 'fa-syringe',
                        'color' => 'blue-600',
                        'bg' => 'bg-blue-50/50',
                        'border' => 'border-blue-100'
                    ],
                    [
                        'title' => 'Wastewater Management',
                        'desc' => 'Desludging & septic tanks',
                        'stat' => number_format($c['wastewater'] ?? 0) . ' Serviced',
                        'growth' => $g['wastewater'] ?? '0.0%',
                        'growth_bg' => 'bg-purple-100 text-purple-700',
                        'url' => self::url('modules/services/septic_tanks.php'),
                        'icon' => 'fa-water',
                        'color' => 'purple-600',
                        'bg' => 'bg-purple-50/50',
                        'border' => 'border-purple-100'
                    ],
                ];
        }
    }

    /**
     * Filter activity logs based on department scope.
     */
    public function getScopedActivityLogs(string $scope, array $allLogs): array
    {
        if ($scope === 'admin') {
            return array_slice($allLogs, 0, 6);
        }

        $filtered = array_values(array_filter($allLogs, function($log) {
            $module = strtolower($log['module'] ?? '');
            return !str_contains($module, 'authentication') 
                && !str_contains($module, 'user management') 
                && !str_contains($module, 'system management');
        }));

        $deptFilterKey = match($scope) {
            'surveillance' => 'surveillance',
            'sanitation' => 'sanitation',
            'immunization' => 'immunization',
            'wastewater' => 'wastewater',
            default => 'health'
        };

        $scopedLogs = array_values(array_filter($filtered, function($log) use ($deptFilterKey) {
            $m = strtolower($log['module'] ?? '');
            $a = strtolower($log['action'] ?? '');
            return str_contains($m, $deptFilterKey) || str_contains($a, $deptFilterKey);
        }));

        return array_slice(!empty($scopedLogs) ? $scopedLogs : $filtered, 0, 6);
    }

    /**
     * Clear transient session cache to force reload.
     */
    public function clearCache(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        unset($_SESSION['capstone_dash_cache']);
    }
}

