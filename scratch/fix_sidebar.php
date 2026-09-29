<?php
$file = 'd:\\xampp\\htdocs\\Civentral_HealthSanitation--Web\\includes\\sidebar.php';
$content = file_get_contents($file);

$start = strpos($content, "          <a href=\"<?= site_url('modules/services/maintenance.php')");
$sr_start = strpos($content, "          <a href=\"<?= site_url('modules/services/service_requests.php')", $start);
$end = strpos($content, '</a>', $sr_start) + 4;

if ($start === false || $sr_start === false) {
    echo "ERROR: blocks not found\n";
    exit(1);
}

$new = '          <a href="<?= site_url(\'modules/services/services_management.php\') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, \'services_management.php\') !== false || strpos($currentPath, \'maintenance.php\') !== false || strpos($currentPath, \'service_requests.php\') !== false) ? \'bg-brand-light text-brand-dark\' : \'text-slate-500 hover:bg-brand-light hover:text-brand-dark\'; ?>">' . "\r\n" .
       '            <i class="fa-solid fa-list-check text-[10px] opacity-50"></i> ' . "\r\n" .
       '            <span>Service Requests &amp; Maintenance</span>' . "\r\n" .
       '          </a>';

$content = substr($content, 0, $start) . $new . substr($content, $end);
file_put_contents($file, $content);
echo "done, new len=" . strlen($content) . "\n";
