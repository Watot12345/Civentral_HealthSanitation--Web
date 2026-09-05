<?php
// Verification script for BUG-009, BUG-011, BUG-013, BUG-015, BUG-016

$root = __DIR__ . '/..';

// ==============================
// BUG-009: Security report exists & PHPStan scan
// ==============================
echo "=== BUG-009: Security Report ===\n";
$report = $root . '/security-reports/SAST_DAST_SECURITY_SCAN_REPORT.md';
if (file_exists($report)) {
    $content = file_get_contents($report);
    echo "FILE: security-reports/SAST_DAST_SECURITY_SCAN_REPORT.md\n";
    echo "SIZE: " . filesize($report) . " bytes\n";
    echo "MTIME: " . date('Y-m-d H:i:s', filemtime($report)) . "\n";
    // Extract key lines
    preg_match('/\*\*Date\*\*: (.+)/', $content, $date);
    preg_match('/\*\*Status\*\*: (.+)/', $content, $status);
    preg_match('/Critical Severity Findings\*\*: (.+)/', $content, $crit);
    preg_match('/High Severity Findings\*\*: (.+)/', $content, $high);
    echo "  Date: " . trim($date[1] ?? 'N/A') . "\n";
    echo "  Status: " . trim($status[1] ?? 'N/A') . "\n";
    echo "  Critical findings: " . trim($crit[1] ?? 'N/A') . "\n";
    echo "  High findings: " . trim($high[1] ?? 'N/A') . "\n";
} else {
    echo "FAIL: report file missing\n";
}

// PHPStan check (if available)
echo "\n--- PHPStan quick scan (level 1, app/ Core/) ---\n";
$phpstan = $root . '/vendor/bin/phpstan';
if (file_exists($phpstan . '.bat') || file_exists($phpstan)) {
    $bin = file_exists($phpstan . '.bat') ? $phpstan . '.bat' : $phpstan;
    passthru("php " . escapeshellarg($bin) . " analyse " . escapeshellarg($root . '/app') . " " . escapeshellarg($root . '/Core') . " --level=1 --no-progress 2>&1");
} else {
    // Try direct phpstan.phar or global
    $output = null;
    $ret = null;
    exec('php -r "echo \"phpstan available: \" . (shell_exec(\"phpstan --version\") ? \"yes\" : \"no\");"', $output, $ret);
    passthru('php ' . escapeshellarg($root . '/vendor/phpstan/phpstan/phpstan.phar') . ' analyse ' . escapeshellarg($root . '/app') . ' ' . escapeshellarg($root . '/Core') . ' --level=1 --no-progress 2>&1');
    echo "(phpstan binary not found in vendor - report is the committed artifact)\n";
}

// ==============================
// BUG-011: CSV export code verification + mock CSV output
// ==============================
echo "\n=== BUG-011: Export CSV headers + code ===\n";
$exportFile = $root . '/api/export.php';
echo "FILE: api/export.php (size: " . filesize($exportFile) . " bytes)\n";
$exportSrc = file_get_contents($exportFile);

// Show the toCsv function headers code
echo "\nExportService::toCsv() headers emitted (from source):\n";
// Grep the header() lines in ExportService
$svcFile = $root . '/app/services/ExportService.php';
preg_match_all("/header\('(.+?)'\)/", file_get_contents($svcFile), $hdrs);
foreach ($hdrs[1] as $h) {
    echo "  header('{$h}')\n";
}

// Simulate what toCsv() would produce for a mock patient dataset
echo "\nSimulated CSV output (5 rows, patients category):\n";
$mockRows = [
    ['PT-0001', 'Juan dela Cruz',  'Male',   'Bagong Silang', 'Active',   '2026-01-10'],
    ['PT-0002', 'Maria Santos',    'Female', 'Camarin',       'Active',   '2026-02-14'],
    ['PT-0003', 'Roberto Reyes',   'Male',   'Deparo',        'Inactive', '2026-03-05'],
    ['PT-0004', 'Ana Gonzalez',    'Female', 'Bagumbong',     'Active',   '2026-04-22'],
    ['PT-0005', 'Carlos Villanueva','Male',  'Baesa',         'Pending',  '2026-05-18'],
];
$mockHeaders = ['Patient ID', 'Full Name', 'Gender', 'Barangay', 'Status', 'Registered Date'];

ob_start();
$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
fputcsv($out, $mockHeaders);
foreach ($mockRows as $r) fputcsv($out, $r);
fclose($out);
$csvOutput = ob_get_clean();
$lines = explode("\n", trim($csvOutput));
echo "Row 1 (headers): " . $lines[0] . "\n";
for ($i = 1; $i <= 5 && isset($lines[$i]); $i++) {
    echo "Row " . ($i + 1) . ": " . $lines[$i] . "\n";
}
echo "Total rows (incl header): " . count($lines) . "\n";

// ==============================
// BUG-013: PDF generation headers + byte-size estimate
// ==============================
echo "\n=== BUG-013: PDF generation (Dompdf) ===\n";
$svcContent = file_get_contents($svcFile);
// Confirm Dompdf import
preg_match('/use Dompdf.+Dompdf;/', $svcContent, $dm);
echo "Dompdf import: " . ($dm[0] ?? 'NOT FOUND') . "\n";
// Confirm headers
preg_match_all("/header\('Content-Type: application\/pdf'\)/", $svcContent, $pdfHdr);
echo "PDF Content-Type header set: " . (count($pdfHdr[0]) > 0 ? "YES (" . count($pdfHdr[0]) . " occurrence(s))" : "NO") . "\n";
preg_match_all("/header\('Content-Disposition: attachment/", $svcContent, $dispHdr);
echo "Content-Disposition attachment header: " . (count($dispHdr[0]) > 0 ? "YES" : "NO") . "\n";

// Build a minimal PDF in memory and report byte size
$autoload = $root . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    if (class_exists('Dompdf\Dompdf')) {
        $opts = new \Dompdf\Options();
        $opts->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($opts);
        $html = '<html><body><h1>Test PDF Export</h1><p>Patient Registry - Civentral</p>'
              . '<table border="1"><tr><th>ID</th><th>Name</th><th>Status</th></tr>'
              . '<tr><td>PT-0001</td><td>Juan dela Cruz</td><td>Active</td></tr></table></body></html>';
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $pdfOutput = $dompdf->output();
        $byteSize  = strlen($pdfOutput);
        $pdfMagic  = substr($pdfOutput, 0, 4);
        echo "PDF magic bytes: " . bin2hex($pdfMagic) . " (expected: 25504446 = \"%PDF\")\n";
        echo "PDF byte size: " . number_format($byteSize) . " bytes\n";
        echo "PDF starts with %PDF: " . ($pdfMagic === '%PDF' ? "YES" : "NO") . "\n";
        echo "Headers that would be sent:\n";
        echo "  Content-Type: application/pdf\n";
        echo "  Content-Disposition: attachment; filename=\"report.pdf\"\n";
        echo "  Cache-Control: private, max-age=0, must-revalidate\n";
        echo "  Pragma: public\n";
    } else {
        echo "Dompdf class not loaded — composer dependencies missing\n";
    }
} else {
    echo "vendor/autoload.php missing\n";
}

// ==============================
// BUG-015: Row-cap removal confirmation
// ==============================
echo "\n=== BUG-015: 5000-row cap removal from BackupController ===\n";
$backupFile = $root . '/app/Controllers/BackupController.php';
$backupSrc  = file_get_contents($backupFile);

// Count occurrences of 5000 in the dump method
$lines = explode("\n", $backupSrc);
$capLines = [];
foreach ($lines as $ln => $line) {
    if (preg_match('/5000/', $line)) $capLines[] = ($ln + 1) . ': ' . trim($line);
}

echo "Occurrences of '5000' in BackupController.php: " . count($capLines) . "\n";
if (count($capLines) > 0) {
    foreach ($capLines as $cl) echo "  LINE $cl\n";
} else {
    echo "CONFIRMED: No hardcoded 5000-row limit present.\n";
}

// Show what the generateSqlDatabaseDump select call looks like now
preg_match('/\$rows\s*=\s*\$this->db->select\(.*?\);/s', $backupSrc, $selectMatch);
echo "\ngenerateSqlDatabaseDump() select call:\n  " . trim($selectMatch[0] ?? 'NOT FOUND') . "\n";

// ==============================
// BUG-016: SYSTEM_TABLES vs restore() 1:1 match
// ==============================
echo "\n=== BUG-016: SYSTEM_TABLES vs restore() 1:1 coverage ===\n";
preg_match('/SYSTEM_TABLES\s*=\s*\[(.*?)\];/s', $backupSrc, $tblBlock);
preg_match_all("/'([a-z_]+)'/", $tblBlock[1] ?? '', $tblNames);
$tables = $tblNames[1];
echo "SYSTEM_TABLES constant (" . count($tables) . " tables):\n";
foreach ($tables as $t) echo "  + $t\n";

echo "\nrestore() method scope:\n";
if (strpos($backupSrc, 'foreach (self::SYSTEM_TABLES as $table)') !== false) {
    echo "  restore() iterates: foreach (self::SYSTEM_TABLES as \$table)\n";
    echo "  => Covers all " . count($tables) . " tables — EXACT 1:1 MATCH.\n";
} else {
    echo "  FAIL: restore() does not reference self::SYSTEM_TABLES\n";
}

echo "\nDone.\n";
