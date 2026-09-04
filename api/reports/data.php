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
                'details' => 'Patient Consultation - ' . ($c['diagnosis'] ?? 'General Outpatient'),
                'report_type' => 'health_center'
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
                'details' => 'Sanitation Permit Clearance #' . ($p['permit_number'] ?? ($p['id'] ?? '')),
                'report_type' => 'sanitation'
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
                'details' => 'Sanitation Establishment Inspection',
                'report_type' => 'sanitation'
            ];
        }
    } catch (Throwable $e) {}

    // C. Immunization & Nutrition
    try {
        $immunizations = $db->select('immunizations');
        foreach ($immunizations as $imm) {
            $status = 'Compliant';
            $reportRows[] = [
                'id' => 'IMM-' . ($imm['id'] ?? rand(100,999)),
                'facility' => 'Immunization & Nutrition',
                'inspector' => $imm['administered_by'] ?? 'Immunization Specialist',
                'date' => substr($imm['date_administered'] ?? ($imm['created_at'] ?? date('Y-m-d')), 0, 10),
                'score' => 96,
                'status' => $status,
                'details' => 'Immunization Dose: ' . ($imm['vaccine'] ?? 'Scheduled Vaccine'),
                'report_type' => 'immunization'
            ];
        }
    } catch (Throwable $e) {}

    // D. Wastewater Management & Services
    try {
        $invoices = $db->select('wastewater_invoices');
        foreach ($invoices as $inv) {
            $statusRaw = strtolower($inv['status'] ?? 'paid');
            $status = in_array($statusRaw, ['paid', 'completed', 'settled']) ? 'Compliant' : (in_array($statusRaw, ['pending', 'unpaid']) ? 'Pending' : 'Urgent');
            $reportRows[] = [
                'id' => 'WST-' . ($inv['id'] ?? rand(100,999)),
                'facility' => 'Wastewater Services',
                'inspector' => 'Wastewater Officer',
                'date' => substr($inv['invoice_date'] ?? ($inv['created_at'] ?? date('Y-m-d')), 0, 10),
                'score' => $status === 'Compliant' ? 94 : ($status === 'Pending' ? 76 : 50),
                'status' => $status,
                'details' => 'Wastewater Service Fee #' . ($inv['invoice_id'] ?? ($inv['id'] ?? '')),
                'report_type' => 'wastewater'
            ];
        }
    } catch (Throwable $e) {}

    // E. Disease Surveillance Cases
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
                'details' => 'Disease Surveillance Case: ' . ($case['disease'] ?? 'Epidemiologic Alert'),
                'report_type' => 'surveillance'
            ];
        }
    } catch (Throwable $e) {}

    // Sort report rows descending by date
    usort($reportRows, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    // 3. Role & Department Scoping
    $userRoleDesc  = strtolower(trim($_SESSION['role_description'] ?? $_SESSION['user']['role_description'] ?? ''));
    $userRole      = strtolower(trim($_SESSION['role'] ?? $_SESSION['user']['role'] ?? ''));
    $currentUserId = (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));
    $isAdmin       = $permService->isAdminRole($userRoleDesc) || $permService->isAdminRole($userRole);
    $isHeadRole    = !$isAdmin && ($permService->isHeadOrAdminRole($userRoleDesc) || $permService->isHeadOrAdminRole($userRole));

    require_once __DIR__ . '/../../app/services/DepartmentResolver.php';
    $deptResolver = \App\Services\DepartmentResolver::getInstance();
    $userDeptName = $deptResolver->resolveDepartmentName();

    if (!$isAdmin) {
        $reportRows = array_values(array_filter($reportRows, function($row) use ($isHeadRole, $userDeptName) {
            return strcasecmp($row['facility'] ?? '', $userDeptName) === 0;
        }));
    }

    $recentActivity = [];
    try {
        $logs = $db->select('activity_logs');

        // Filter: ONLY report generation events, NEVER authentication logs, with hierarchical role scoping
        $reportLogs = array_filter($logs, function($log) use ($isAdmin, $isHeadRole, $userRoleDesc, $currentUserId) {
            $module = strtolower($log['module'] ?? '');
            $action = strtolower($log['action'] ?? '');
            $desc   = strtolower($log['details'] ?? ($log['description'] ?? ''));
            $logUserId = (int)($log['user_id'] ?? 0);

            // 1. Strictly exclude any authentication / security logs
            $isAuth = in_array($module, ['auth', 'authentication', 'login', 'security', 'session'])
                   || str_contains($action, 'login')
                   || str_contains($action, 'logout')
                   || str_contains($action, 'sign-in')
                   || str_contains($action, 'sign-out')
                   || str_contains($action, 'password')
                   || str_contains($action, '2fa')
                   || str_contains($action, 'otp')
                   || str_contains($desc, 'logged in')
                   || str_contains($desc, 'logged out');

            if ($isAuth) {
                return false;
            }

            // 2. Must be a report generation or export action
            $isReport = in_array($module, ['reports', 'report generator', 'report management', 'custom report', 'analytics report'])
                     || str_contains($action, 'report')
                     || str_contains($action, 'export')
                     || str_contains($desc, 'generated report')
                     || str_contains($desc, 'exported');

            if (!$isReport) {
                return false;
            }

            // 3. Role Scoping
            if ($isAdmin) {
                return true; // System Administrator sees all generated reports
            }

            if ($isHeadRole) {
                if (str_contains($userRoleDesc, 'sanitation')) {
                    return str_contains($module, 'sanitat') || str_contains($desc, 'sanitat') || $logUserId === $currentUserId;
                }
                if (str_contains($userRoleDesc, 'health center') || str_contains($userRoleDesc, 'medical') || str_contains($userRoleDesc, 'director')) {
                    return str_contains($module, 'health') || str_contains($desc, 'health') || str_contains($module, 'consult') || $logUserId === $currentUserId;
                }
                if (str_contains($userRoleDesc, 'surveillance') || str_contains($userRoleDesc, 'epidemiol')) {
                    return str_contains($module, 'surveill') || str_contains($desc, 'surveill') || str_contains($desc, 'disease') || $logUserId === $currentUserId;
                }
                if (str_contains($userRoleDesc, 'immuniz') || str_contains($userRoleDesc, 'nutrition')) {
                    return str_contains($module, 'immuniz') || str_contains($desc, 'vaccin') || str_contains($desc, 'nutri') || $logUserId === $currentUserId;
                }
                if (str_contains($userRoleDesc, 'water') || str_contains($userRoleDesc, 'waste')) {
                    return str_contains($module, 'waste') || str_contains($desc, 'septic') || $logUserId === $currentUserId;
                }
                return true;
            }

            // Regular staff only sees report generation logs executed by themselves
            return $logUserId === $currentUserId;
        });

        usort($reportLogs, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });

        foreach ($reportLogs as $log) {
            $empId = $log['user_id'] ?? 0;
            $actorName = $employeeMap[$empId] ?? ($log['user_name'] ?? 'Staff Member');
            $actorRole = $log['role'] ?? 'Staff Member';

            $rawAction = $log['action'] ?? '';
            $cleanName = str_replace(['Generated Report: ', 'Generated Report ', 'Generated '], '', $rawAction);
            if (empty($cleanName)) {
                $cleanName = 'Compliance & Operational Report';
            }

            $details = $log['details'] ?? ($log['description'] ?? '');
            $typeStr = 'Custom Report';
            if (str_contains($details, 'PDF') || str_contains($rawAction, 'PDF')) {
                $typeStr = 'PDF Export';
            } elseif (str_contains($details, 'Excel') || str_contains($details, 'XLS') || str_contains($rawAction, 'Excel')) {
                $typeStr = 'Excel Export';
            } elseif (str_contains($details, 'Word') || str_contains($details, 'DOC') || str_contains($rawAction, 'Word')) {
                $typeStr = 'Word Export';
            } elseif (str_contains($details, 'CSV') || str_contains($rawAction, 'CSV')) {
                $typeStr = 'CSV Export';
            }

            $recentActivity[] = [
                'id'        => $log['id'] ?? 0,
                'name'      => $cleanName,
                'type'      => $typeStr,
                'department'=> $log['module'] ?? 'Reports',
                'details'   => $details,
                'date'      => !empty($log['created_at']) ? date('M d, Y h:i A', strtotime($log['created_at'])) : date('M d, Y'),
                'raw_date'  => $log['created_at'] ?? date('Y-m-d H:i:s'),
                'status'    => 'Generated',
                'user'      => $actorName,
                'role'      => $actorRole
            ];
        }
    } catch (Throwable $e) {}

    echo json_encode([
        'success' => true,
        'employees' => $employeeList,
        'report_rows' => $reportRows,
        'recent_reports' => $recentActivity,
        'total_count' => count($reportRows),
        'timestamp' => date('Y-m-d H:i:s'),
        'user_scope' => [
            'is_admin' => $isAdmin,
            'is_director' => $isHeadRole && !$isAdmin,
            'is_staff' => !$isAdmin && !$isHeadRole,
            'department' => $userDeptName ?? 'All Departments'
        ]
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
