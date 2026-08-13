<?php
// api/reports/data.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/PermissionService.php';

use App\Services\PermissionService;

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

    $db = Database::getInstance();

    // 1. Fetch Real Active Employees from Supabase
    $employeesRaw = [];
    try {
        $employeesRaw = $db->select('employees');
    } catch (Throwable $e) {
        $employeesRaw = [];
    }

    $employeeList = [];
    $employeeMap = [];
    foreach ($employeesRaw as $emp) {
        $name = trim($emp['full_name'] ?? '');
        if (empty($name) || str_contains($name, '@')) {
            $name = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
        }
        if (empty($name) || str_contains($name, '@')) {
            $handle = explode('@', $emp['email'] ?? ($emp['username'] ?? 'Staff'))[0];
            $name = ucwords(str_replace(['.', '_', '-'], ' ', $handle));
        }

        $roleTitle = !empty($emp['role_description']) ? $emp['role_description'] : (ucwords(str_replace(['-', '_'], ' ', $emp['role'] ?? 'Staff Member')));
        $id = $emp['id'] ?? 0;
        $rawDept = strtolower($emp['department'] ?? ($emp['role'] ?? 'health center'));

        $deptTitle = match(true) {
            str_contains($rawDept, 'sanitation') => 'Sanitation Permits',
            str_contains($rawDept, 'surveillance') || str_contains($rawDept, 'epidemiol') => 'Health Surveillance',
            str_contains($rawDept, 'immuniz') || str_contains($rawDept, 'nutrition') => 'Immunization & Nutrition',
            str_contains($rawDept, 'water') || str_contains($rawDept, 'waste') => 'Wastewater Services',
            default => 'Health Center Services'
        };

        $employeeMap[$id] = $name;
        $employeeList[] = [
            'id' => $id,
            'name' => $name,
            'role' => $emp['role'] ?? 'Staff',
            'role_description' => $roleTitle,
            'department' => $deptTitle
        ];
    }

    // 2. Fetch Real Operational Records across all Core Health Departments
    $reportRows = [];

    // A. Consultations & Medical Records (Health Center Services)
    try {
        $consultations = $db->select('consultations');
        foreach ($consultations as $c) {
            $empId = $c['employee_id'] ?? 0;
            $inspectorName = $employeeMap[$empId] ?? ($c['doctor_name'] ?? 'Staff Doctor');
            $statusRaw = strtolower($c['status'] ?? 'completed');
            $status = in_array($statusRaw, ['completed', 'resolved']) ? 'Compliant' : (in_array($statusRaw, ['in_progress', 'pending']) ? 'Pending' : 'Urgent');
            
            $reportRows[] = [
                'id' => 'CON-' . ($c['id'] ?? rand(100,999)),
                'facility' => 'Health Center Services',
                'inspector' => $inspectorName,
                'date' => substr($c['created_at'] ?? ($c['date'] ?? date('Y-m-d')), 0, 10),
                'score' => $status === 'Compliant' ? 95 : ($status === 'Pending' ? 80 : 65),
                'status' => $status,
                'details' => 'Patient Consultation - ' . ($c['diagnosis'] ?? 'General Outpatient')
            ];
        }
    } catch (Throwable $e) {}

    // B. Sanitation Permits & Inspections
    try {
        $permits = $db->select('permits');
        foreach ($permits as $p) {
            $empId = $p['inspector_id'] ?? 0;
            $inspectorName = $employeeMap[$empId] ?? 'Sanitation Inspector';
            $statusRaw = strtolower($p['status'] ?? 'active');
            $status = in_array($statusRaw, ['approved', 'active', 'compliant', 'issued']) ? 'Compliant' : (in_array($statusRaw, ['pending', 'under_review']) ? 'Pending' : 'Non-Compliant');
            
            $reportRows[] = [
                'id' => 'PER-' . ($p['id'] ?? rand(100,999)),
                'facility' => 'Sanitation Permits',
                'inspector' => $inspectorName,
                'date' => substr($p['created_at'] ?? ($p['issue_date'] ?? date('Y-m-d')), 0, 10),
                'score' => $status === 'Compliant' ? 92 : ($status === 'Pending' ? 75 : 60),
                'status' => $status,
                'details' => 'Sanitation Permit Clearance #' . ($p['permit_number'] ?? ($p['id'] ?? ''))
            ];
        }
    } catch (Throwable $e) {}

    try {
        $inspections = $db->select('inspections');
        foreach ($inspections as $insp) {
            $empId = $insp['inspector_id'] ?? 0;
            $inspectorName = $employeeMap[$empId] ?? 'Sanitation Officer';
            $overall = strtolower($insp['overall_status'] ?? ($insp['status'] ?? 'compliant'));
            $status = in_array($overall, ['compliant', 'passed']) ? 'Compliant' : (in_array($overall, ['partially_compliant', 'scheduled']) ? 'Pending' : 'Urgent');

            $reportRows[] = [
                'id' => 'INS-' . ($insp['id'] ?? rand(100,999)),
                'facility' => 'Sanitation Permits',
                'inspector' => $inspectorName,
                'date' => substr($insp['created_at'] ?? ($insp['scheduled_date'] ?? date('Y-m-d')), 0, 10),
                'score' => $status === 'Compliant' ? 90 : ($status === 'Pending' ? 78 : 55),
                'status' => $status,
                'details' => 'Sanitation Establishment Inspection'
            ];
        }
    } catch (Throwable $e) {}

    // C. Disease Surveillance Cases
    try {
        $cases = $db->select('surveillance_cases');
        foreach ($cases as $case) {
            $statusRaw = strtolower($case['status'] ?? 'suspected');
            $status = in_array($statusRaw, ['resolved', 'closed', 'cleared']) ? 'Compliant' : (in_array($statusRaw, ['investigating', 'suspected']) ? 'Pending' : 'Urgent');

            $reportRows[] = [
                'id' => 'SURV-' . ($case['id'] ?? rand(100,999)),
                'facility' => 'Health Surveillance',
                'inspector' => $case['reported_by'] ?? ($case['investigator_id'] ?? 'Surveillance Officer'),
                'date' => substr($case['created_at'] ?? date('Y-m-d'), 0, 10),
                'score' => $status === 'Compliant' ? 98 : ($status === 'Pending' ? 82 : 45),
                'status' => $status,
                'details' => 'Disease Surveillance Case: ' . ($case['disease'] ?? 'Epidemiologic Alert')
            ];
        }
    } catch (Throwable $e) {}

    // Sort report rows descending by date
    usort($reportRows, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    // 3. Fetch Real Activity Logs for Recent Reports Activity
    $recentActivity = [];
    try {
        $logs = $db->select('activity_logs');
        usort($logs, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        foreach (array_slice($logs, 0, 10) as $log) {
            $empId = $log['user_id'] ?? 0;
            $actorName = $employeeMap[$empId] ?? ($log['user_name'] ?? 'System User');
            $recentActivity[] = [
                'name' => $log['action'] ?? 'Report Activity Generated',
                'type' => $log['module'] ?? 'System',
                'date' => substr($log['created_at'] ?? date('Y-m-d'), 0, 10),
                'status' => 'Generated',
                'user' => $actorName
            ];
        }
    } catch (Throwable $e) {}

    echo json_encode([
        'success' => true,
        'employees' => $employeeList,
        'report_rows' => $reportRows,
        'recent_reports' => $recentActivity,
        'total_count' => count($reportRows),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    error_log('API Report Data Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch report data',
        'error' => $e->getMessage()
    ]);
}
