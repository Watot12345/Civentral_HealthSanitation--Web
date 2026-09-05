<?php
// api/export.php
// Server-side Data Export Endpoint (CSV, Excel, PDF)

require_once __DIR__ . '/../Core/Env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/services/ExportService.php';

Env::load();

$category = $_GET['category'] ?? ($_POST['category'] ?? 'patients');
$format   = strtolower($_GET['format'] ?? ($_POST['format'] ?? 'csv'));
$dateFrom = $_GET['date_from'] ?? ($_POST['date_from'] ?? null);
$dateTo   = $_GET['date_to'] ?? ($_POST['date_to'] ?? null);

$db = Database::getInstance();
$headers = [];
$rows = [];
$title = 'Municipal Data Report';
$filename = 'report_' . date('Ymd_His');

switch (strtolower($category)) {
    case 'sanitation':
    case 'permits':
        $title = 'Sanitary Permits Report';
        $filename = 'sanitary_permits_' . date('Ymd');
        $headers = ['Permit Number', 'Applicant / Business Name', 'Barangay', 'Status', 'Issued Date'];
        $dbData = $db->select('permits', [], ['limit' => 1000, 'order' => 'id.desc']);
        foreach ($dbData as $p) {
            $rows[] = [
                $p['permit_number'] ?? $p['id'] ?? '',
                $p['business_name'] ?? $p['applicant'] ?? 'N/A',
                $p['barangay'] ?? '',
                $p['status'] ?? 'Active',
                $p['created_at'] ?? date('Y-m-d')
            ];
        }
        break;

    case 'wastewater':
    case 'services':
        $title = 'Septage & Service Requests Report';
        $filename = 'service_requests_' . date('Ymd');
        $headers = ['Request Code', 'Property / Applicant', 'Barangay', 'Service Type', 'Status', 'Requested Date'];
        $dbData = $db->select('service_requests', [], ['limit' => 1000, 'order' => 'id.desc']);
        foreach ($dbData as $s) {
            $rows[] = [
                $s['request_code'] ?? $s['id'] ?? '',
                $s['applicant_name'] ?? 'Property Owner',
                $s['barangay'] ?? '',
                $s['service_type'] ?? 'Desludging',
                $s['status'] ?? 'Pending',
                $s['created_at'] ?? date('Y-m-d')
            ];
        }
        break;

    case 'surveillance':
    case 'epidemic':
        $title = 'Disease Surveillance Cases Report';
        $filename = 'disease_surveillance_' . date('Ymd');
        $headers = ['Case ID', 'Disease', 'Barangay', 'Severity', 'Status', 'Reported Date'];
        $dbData = $db->select('surveillance_cases', [], ['limit' => 1000, 'order' => 'id.desc']);
        foreach ($dbData as $c) {
            $rows[] = [
                $c['case_code'] ?? $c['id'] ?? '',
                $c['disease'] ?? 'Dengue',
                $c['barangay'] ?? '',
                $c['severity'] ?? 'Moderate',
                $c['status'] ?? 'Active',
                $c['created_at'] ?? date('Y-m-d')
            ];
        }
        break;

    case 'patients':
    case 'healthcenter':
    default:
        $title = 'Patient Registry Report';
        $filename = 'patient_registry_' . date('Ymd');
        $headers = ['Patient ID', 'Full Name', 'Gender', 'Barangay', 'Status', 'Registered Date'];
        $dbData = $db->select('patients', [], ['limit' => 1000, 'order' => 'id.desc']);
        foreach ($dbData as $pt) {
            $name = trim(($pt['first_name'] ?? '') . ' ' . ($pt['last_name'] ?? ''));
            $rows[] = [
                $pt['patient_id'] ?? $pt['id'] ?? '',
                $name ?: 'Anonymous Patient',
                $pt['gender'] ?? 'Unspecified',
                $pt['barangay'] ?? '',
                $pt['status'] ?? 'Active',
                $pt['created_at'] ?? date('Y-m-d')
            ];
        }
        break;
}

$exportData = [
    'headers' => $headers,
    'rows'    => $rows
];

if ($format === 'pdf') {
    \App\Services\ExportService::toPdf($exportData, $title, $filename . '.pdf');
} elseif ($format === 'excel' || $format === 'xlsx') {
    \App\Services\ExportService::toExcel($exportData, $title, $filename . '.xlsx');
} else {
    \App\Services\ExportService::toCsv($exportData, $filename . '.csv');
}
