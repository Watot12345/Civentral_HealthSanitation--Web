<?php
// api/reports/data.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/PermissionService.php';
require_once __DIR__ . '/../../app/services/DepartmentResolver.php';

use App\Services\PermissionService;
use App\Services\DepartmentResolver;

try {
    $permService = PermissionService::getInstance();
    if (!$permService->hasPermission('reports.view') && !$permService->hasPermission('analytics.view')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden: Permission denied for viewing reports.'
        ]);
        exit;
    }

    $userRoleDesc = trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? '');
    $userRole     = trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? '');
    $isAdmin      = $permService->isAdminRole($userRoleDesc) || $permService->isAdminRole($userRole);

    $deptResolver = DepartmentResolver::getInstance();
    $assignedDept = $deptResolver->resolveDepartmentName();
    $deptType = match($assignedDept) {
        'Health Center Services'   => 'health_center',
        'Sanitation Permits'       => 'sanitation',
        'Immunization & Nutrition' => 'immunization',
        'Wastewater Services'     => 'wastewater',
        'Health Surveillance'     => 'surveillance',
        default                   => 'sanitation'
    };

    $db = Database::getInstance();
    
    $requestedModule = $_GET['module'] ?? 'unified';
    if (!$isAdmin) {
        // Non-admins can only view their own department module report, unified is admin-only
        $module = $deptType;
    } else {
        $module = $requestedModule;
    }

    $startDate = !empty($_GET['start_date']) ? substr(trim($_GET['start_date']), 0, 10) : '1970-01-01';
    $endDate   = !empty($_GET['end_date'])   ? substr(trim($_GET['end_date']), 0, 10)   : '2099-12-31';

    $reportRows = [];
    $kpis = [
        'total' => 0,
        'compliant' => 0,
        'pending' => 0,
        'urgent' => 0
    ];

    // Filter helper
    $filterDate = function($date) use ($startDate, $endDate) {
        if (!$date) return true;
        $d = substr($date, 0, 10);
        return ($d >= $startDate && $d <= $endDate);
    };

    // 1. Health Center Services
    if ($module === 'health_center' || $module === 'unified') {
        $consultations = [];
        try { $consultations = $db->select('consultations'); } catch (Throwable $e) {}

        $hTotal = 0; $hCompliant = 0; $hPending = 0; $hUrgent = 0;
        foreach ($consultations as $c) {
            $date = $c['date'] ?? ($c['created_at'] ?? null);
            if (!$filterDate($date)) continue;

            $statusRaw = strtolower($c['status'] ?? 'completed');
            if (in_array($statusRaw, ['completed', 'resolved', 'treated'])) {
                $statusLabel = 'Compliant';
                $hCompliant++;
            } elseif (in_array($statusRaw, ['in_progress', 'pending', 'admitted', 'referred'])) {
                $statusLabel = 'Pending';
                $hPending++;
            } else {
                $statusLabel = 'Urgent';
                $hUrgent++;
            }
            $hTotal++;

            $diag = trim($c['diagnosis'] ?? 'General Consultation');
            if (empty($diag)) $diag = 'General Consultation';
            $treatment = trim($c['treatment_plan'] ?? ($c['notes'] ?? ''));

            $reportRows[] = [
                'category' => 'Health Center Services',
                'item'     => $c['consultation_id'] ?? ('CONS-' . $c['id']),
                'details'  => $diag . ($treatment ? ' (' . $treatment . ')' : ''),
                'date'     => substr($c['date'] ?? ($c['created_at'] ?? ''), 0, 10),
                'status'   => $statusLabel,
                'metric'   => 'Patient Consultation'
            ];
        }

        if ($module === 'health_center') {
            $kpis = [
                'total' => $hTotal,
                'compliant' => $hCompliant,
                'pending' => $hPending,
                'urgent' => $hUrgent
            ];
        } else {
            $kpis['total'] += $hTotal;
            $kpis['compliant'] += $hCompliant;
            $kpis['pending'] += $hPending;
            $kpis['urgent'] += $hUrgent;
        }
    }

    // 2. Sanitation Permits & Inspections
    if ($module === 'sanitation' || $module === 'unified') {
        $inspections = [];
        try { $inspections = $db->select('inspections'); } catch (Throwable $e) {}

        $sTotal = 0; $sCompliant = 0; $sPending = 0; $sUrgent = 0;
        foreach ($inspections as $i) {
            $date = $i['conducted_date'] ?? ($i['scheduled_date'] ?? ($i['created_at'] ?? null));
            if (!$filterDate($date)) continue;

            $overall = strtolower($i['overall_status'] ?? ($i['status'] ?? 'compliant'));
            if (in_array($overall, ['compliant', 'passed'])) {
                $statusLabel = 'Compliant';
                $sCompliant++;
            } elseif (in_array($overall, ['partially_compliant', 'scheduled', 'pending'])) {
                $statusLabel = 'Pending';
                $sPending++;
            } else {
                $statusLabel = 'Urgent';
                $sUrgent++;
            }
            $sTotal++;

            $notes = trim($i['notes'] ?? ($i['recommendations'] ?? ''));
            $reportRows[] = [
                'category' => 'Sanitation Permits',
                'item'     => $i['inspection_id'] ?? ('INS-' . $i['id']),
                'details'  => 'Permit #' . ($i['permit_id'] ?? 'N/A') . ($notes ? ' - ' . $notes : ''),
                'date'     => substr($date, 0, 10),
                'status'   => $statusLabel,
                'metric'   => 'Sanitary Inspection'
            ];
        }

        if ($module === 'sanitation') {
            $kpis = [
                'total' => $sTotal,
                'compliant' => $sCompliant,
                'pending' => $sPending,
                'urgent' => $sUrgent
            ];
        } else {
            $kpis['total'] += $sTotal;
            $kpis['compliant'] += $sCompliant;
            $kpis['pending'] += $sPending;
            $kpis['urgent'] += $sUrgent;
        }
    }

    // 3. Immunization & Nutrition
    if ($module === 'immunization' || $module === 'unified') {
        $immunizations = [];
        try { $immunizations = $db->select('immunizations'); } catch (Throwable $e) {}

        $imTotal = 0; $imCompliant = 0; $imPending = 0; $imUrgent = 0;
        foreach ($immunizations as $imm) {
            $date = $imm['date_administered'] ?? ($imm['created_at'] ?? null);
            if (!$filterDate($date)) continue;

            $vac = trim($imm['vaccine'] ?? 'General Vaccine');
            $statusLabel = 'Compliant';
            $imCompliant++;
            $imTotal++;

            $center = trim($imm['health_center'] ?? 'Health Center');
            $admin = trim($imm['administered_by'] ?? 'Staff');
            $reportRows[] = [
                'category' => 'Immunization & Nutrition',
                'item'     => $vac . ' (Dose ' . ($imm['dose'] ?? '1') . ')',
                'details'  => 'Administered at ' . $center . ' by ' . $admin,
                'date'     => substr($date, 0, 10),
                'status'   => $statusLabel,
                'metric'   => 'Immunization Dose'
            ];
        }

        if ($module === 'immunization') {
            $kpis = [
                'total' => $imTotal,
                'compliant' => $imCompliant,
                'pending' => $imPending,
                'urgent' => $imUrgent
            ];
        } else {
            $kpis['total'] += $imTotal;
            $kpis['compliant'] += $imCompliant;
            $kpis['pending'] += $imPending;
            $kpis['urgent'] += $imUrgent;
        }
    }

    // 4. Wastewater Services
    if ($module === 'wastewater' || $module === 'unified') {
        $invoices = [];
        try { $invoices = $db->select('wastewater_invoices'); } catch (Throwable $e) {}

        $wTotal = 0; $wCompliant = 0; $wPending = 0; $wUrgent = 0;
        foreach ($invoices as $inv) {
            $date = $inv['invoice_date'] ?? ($inv['created_at'] ?? null);
            if (!$filterDate($date)) continue;

            $statusRaw = strtolower($inv['status'] ?? 'paid');
            if (in_array($statusRaw, ['paid', 'completed', 'settled'])) {
                $statusLabel = 'Compliant';
                $wCompliant++;
            } elseif (in_array($statusRaw, ['pending', 'unpaid'])) {
                $statusLabel = 'Pending';
                $wPending++;
            } else {
                $statusLabel = 'Urgent';
                $wUrgent++;
            }
            $wTotal++;

            $client = trim($inv['client_name'] ?? 'Client');
            $serv = trim($inv['service_type'] ?? 'Maintenance');
            $amount = (float)($inv['total_amount'] ?? $inv['amount'] ?? 0);
            $reportRows[] = [
                'category' => 'Wastewater Services',
                'item'     => $inv['invoice_id'] ?? ('INV-' . $inv['id']),
                'details'  => $client . ' (' . $serv . ') - PHP ' . number_format($amount, 2),
                'date'     => substr($date, 0, 10),
                'status'   => $statusLabel,
                'metric'   => 'Billing Invoice'
            ];
        }

        if ($module === 'wastewater') {
            $kpis = [
                'total' => $wTotal,
                'compliant' => $wCompliant,
                'pending' => $wPending,
                'urgent' => $wUrgent
            ];
        } else {
            $kpis['total'] += $wTotal;
            $kpis['compliant'] += $wCompliant;
            $kpis['pending'] += $wPending;
            $kpis['urgent'] += $wUrgent;
        }
    }

    // 5. Health Surveillance
    if ($module === 'surveillance' || $module === 'unified') {
        $cases = [];
        try { $cases = $db->select('surveillance_cases'); } catch (Throwable $e) {}

        $suTotal = 0; $suCompliant = 0; $suPending = 0; $suUrgent = 0;
        foreach ($cases as $case) {
            $date = $case['onset_date'] ?? ($case['created_at'] ?? null);
            if (!$filterDate($date)) continue;

            $statusRaw = strtolower($case['status'] ?? 'suspected');
            if (in_array($statusRaw, ['resolved', 'closed', 'cleared', 'recovered'])) {
                $statusLabel = 'Compliant';
                $suCompliant++;
            } elseif (in_array($statusRaw, ['investigating', 'suspected'])) {
                $statusLabel = 'Pending';
                $suPending++;
            } else {
                $statusLabel = 'Urgent';
                $suUrgent++;
            }
            $suTotal++;

            $dis = trim($case['disease'] ?? 'Unknown');
            $patient = trim($case['patient_name'] ?? 'Anonymous');
            $brgy = trim($case['barangay'] ?? '');
            $reportRows[] = [
                'category' => 'Health Surveillance',
                'item'     => $case['case_code'] ?? ('CS-' . $case['id']),
                'details'  => $dis . ' - ' . $patient . ($brgy ? ' (Brgy ' . $brgy . ')' : ''),
                'date'     => substr($date, 0, 10),
                'status'   => $statusLabel,
                'metric'   => 'Disease Case'
            ];
        }

        if ($module === 'surveillance') {
            $kpis = [
                'total' => $suTotal,
                'compliant' => $suCompliant,
                'pending' => $suPending,
                'urgent' => $suUrgent
            ];
        } else {
            $kpis['total'] += $suTotal;
            $kpis['compliant'] += $suCompliant;
            $kpis['pending'] += $suPending;
            $kpis['urgent'] += $suUrgent;
        }
    }
    
    // Log generation for audit purposes
    try {
        $db->insert('activity_logs', [
            'user_id' => $_SESSION['user']['id'] ?? 0,
            'action' => 'Generated Report',
            'module' => 'Reports',
            'details' => "Generated aggregated report for module '{$module}' ({$startDate} to {$endDate}).",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Throwable $e) {}

    echo json_encode([
        'success' => true,
        'report_rows' => $reportRows,
        'kpis' => $kpis
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal Server Error: ' . $e->getMessage()
    ]);
}
