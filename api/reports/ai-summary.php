<?php
// api/reports/ai-summary.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/services/GeminiAiService.php';
require_once __DIR__ . '/../../app/services/PermissionService.php';

use App\Services\PermissionService;

try {
    // Permission check
    $permService = PermissionService::getInstance();
    if (!$permService->hasPermission('reports.view') && !$permService->hasPermission('analytics.view')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden: Permission denied for report summary generation.'
        ]);
        exit;
    }

    $module = $_REQUEST['module'] ?? '';
    $dept = !empty($module) && $module !== 'all' ? $module : ($_REQUEST['department'] ?? $_REQUEST['facility'] ?? 'all');
    
    $startDate = !empty($_REQUEST['start_date']) ? substr(trim($_REQUEST['start_date']), 0, 10) : '';
    $endDate   = !empty($_REQUEST['end_date'])   ? substr(trim($_REQUEST['end_date']), 0, 10)   : '';
    if (!empty($startDate) && !empty($endDate)) {
        $range = "{$startDate} to {$endDate}";
    } elseif (!empty($_REQUEST['range'])) {
        $range = $_REQUEST['range'];
    } else {
        $range = '30d';
    }

    $bypassCache = (isset($_REQUEST['refresh']) && in_array((string)$_REQUEST['refresh'], ['1', 'true'], true))
        || isset($_REQUEST['nocache']);

    $db = Database::getInstance();

    // Query live metrics from Supabase database tables based on selected department
    $total = 0;
    $compliant = 0;
    $urgent = 0;
    $pending = 0;

    // Filter helper if dates specified
    $filterDate = function($date) use ($startDate, $endDate) {
        if (!$startDate && !$endDate) return true;
        if (!$date) return true;
        $d = substr($date, 0, 10);
        if ($startDate && $d < $startDate) return false;
        if ($endDate && $d > $endDate) return false;
        return true;
    };

    try {
        if (in_array(strtolower($dept), ['health center', 'health_center', 'health center services'])) {
            try { $consultations = $db->select('consultations'); } catch (Throwable $e) { $consultations = []; }
            try { $appointments = $db->select('appointments'); } catch (Throwable $e) { $appointments = []; }
            try { $triage = $db->select('triage'); } catch (Throwable $e) { $triage = []; }
            if ($startDate || $endDate) {
                $consultations = array_filter($consultations, fn($c) => $filterDate($c['date'] ?? ($c['created_at'] ?? null)));
                $appointments = array_filter($appointments, fn($a) => $filterDate($a['date'] ?? ($a['created_at'] ?? null)));
                $triage = array_filter($triage, fn($t) => $filterDate($t['date'] ?? ($t['created_at'] ?? null)));
            }
            $total = count($consultations) + count($appointments);
            $compliant = count(array_filter($consultations, fn($c) => in_array(strtolower($c['status'] ?? ''), ['completed', 'resolved'])));
            $pending = count(array_filter($appointments, fn($a) => in_array(strtolower($a['status'] ?? ''), ['pending', 'scheduled'])));
            $urgent = count(array_filter($triage, fn($t) => in_array(strtolower($t['priority'] ?? ''), ['emergency', 'urgent', 'high', '1', '2'])));
        } elseif (in_array(strtolower($dept), ['sanitation', 'sanitation permits'])) {
            $permits = $db->select('permits');
            $inspections = $db->select('inspections');
            if ($startDate || $endDate) {
                $permits = array_filter($permits, fn($p) => $filterDate($p['created_at'] ?? ($p['issue_date'] ?? null)));
                $inspections = array_filter($inspections, fn($i) => $filterDate($i['inspection_date'] ?? ($i['created_at'] ?? null)));
            }
            $total = count($permits) + count($inspections);
            $compliant = count(array_filter($permits, fn($p) => in_array($p['status'] ?? '', ['Approved', 'Active', 'Compliant'])));
            $pending = count(array_filter($permits, fn($p) => in_array($p['status'] ?? '', ['Pending', 'Under Review'])));
            $urgent = count(array_filter($inspections, fn($i) => ($i['result'] ?? '') === 'Failed' || ($i['status'] ?? '') === 'Urgent'));
        } elseif (in_array(strtolower($dept), ['surveillance', 'health surveillance'])) {
            $cases = $db->select('surveillance_cases');
            $alerts = $db->select('surveillance_alerts');
            if ($startDate || $endDate) {
                $cases = array_filter($cases, fn($c) => $filterDate($c['onset_date'] ?? ($c['created_at'] ?? null)));
                $alerts = array_filter($alerts, fn($a) => $filterDate($a['created_at'] ?? null));
            }
            $total = count($cases) + count($alerts);
            $compliant = count(array_filter($cases, fn($c) => ($c['status'] ?? '') === 'Resolved'));
            $pending = count(array_filter($cases, fn($c) => in_array($c['status'] ?? '', ['Investigating', 'Suspected'])));
            $urgent = count(array_filter($alerts, fn($a) => ($a['status'] ?? '') === 'Active'));
        } else {
            // Aggregate overall
            $permits = $db->select('permits');
            $consultations = $db->select('consultations');
            $cases = $db->select('surveillance_cases');
            if ($startDate || $endDate) {
                $permits = array_filter($permits, fn($p) => $filterDate($p['created_at'] ?? ($p['issue_date'] ?? null)));
                $consultations = array_filter($consultations, fn($c) => $filterDate($c['date'] ?? ($c['created_at'] ?? null)));
                $cases = array_filter($cases, fn($c) => $filterDate($c['onset_date'] ?? ($c['created_at'] ?? null)));
            }
            $total = count($permits) + count($consultations) + count($cases);
            $compliant = count(array_filter($permits, fn($p) => in_array($p['status'] ?? '', ['Approved', 'Active']))) + count(array_filter($consultations, fn($c) => ($c['status'] ?? '') === 'Completed'));
            $urgent = count(array_filter($cases, fn($c) => in_array($c['status'] ?? '', ['Active', 'Confirmed', 'Investigating'])));
            $pending = max(0, $total - $compliant - $urgent);
        }
    } catch (Throwable $dbErr) {
        // Fallback default metrics if database query is empty
        $total = isset($_REQUEST['total']) ? (int)$_REQUEST['total'] : 45;
        $compliant = isset($_REQUEST['compliant']) ? (int)$_REQUEST['compliant'] : 38;
        $urgent = isset($_REQUEST['urgent']) ? (int)$_REQUEST['urgent'] : 3;
        $pending = isset($_REQUEST['pending']) ? (int)$_REQUEST['pending'] : 4;
    }

    // Accept overrides from frontend payload if passed explicitly
    if (isset($_REQUEST['total']) && (int)$_REQUEST['total'] > 0) {
        $total = (int)$_REQUEST['total'];
        $compliant = (int)($_REQUEST['compliant'] ?? $compliant);
        $urgent = (int)($_REQUEST['urgent'] ?? $urgent);
        $pending = (int)($_REQUEST['pending'] ?? $pending);
    }

    $complianceRate = $total > 0 ? round(($compliant / $total) * 100, 1) : 94.5;

    $metrics = [
        'total' => $total,
        'compliant' => $compliant,
        'urgent' => $urgent,
        'pending' => $pending,
        'compliance_rate' => $complianceRate
    ];

    $gemini = new GeminiAiService();
    $summary = $gemini->generateReportSummary($dept, $metrics, $range, $bypassCache);

    echo json_encode([
        'success' => true,
        'department' => $summary['department'],
        'date_range' => $range,
        'metrics' => $metrics,
        'summary' => $summary['executive_summary'],
        'key_findings' => $summary['key_findings'],
        'recommendations' => $summary['recommendations'],
        'risk_level' => $summary['risk_level'],
        'ai_generated' => $summary['ai_generated'],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    error_log('API AI Summary Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate report summary',
        'error' => $e->getMessage()
    ]);
}
