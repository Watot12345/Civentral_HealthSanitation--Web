<?php
// api/reports/schedule.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../Core/Env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Models/ActivityLog.php';
require_once __DIR__ . '/../../app/Models/SchedulerLog.php';
require_once __DIR__ . '/../../app/services/MailService.php';

$storageDir = __DIR__ . '/../../storage';
$storageFile = $storageDir . '/scheduled_reports.json';

if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

function getSchedules(string $file): array {
    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [];
}

function saveSchedules(string $file, array $schedules): bool {
    return (bool)@file_put_contents($file, json_encode($schedules, JSON_PRETTY_PRINT));
}

function computeNextRun(string $startDate, string $time, string $frequency, bool $advanceIfPast = true): string {
    $timeClean = trim($time);
    if (strlen($timeClean) === 5) {
        $timeClean .= ':00';
    }
    
    if (empty($startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
        $startDate = date('Y-m-d');
    }

    $combined = "{$startDate} {$timeClean}";
    $targetTime = strtotime($combined);
    $now = time();

    if ($targetTime === false) {
        $targetTime = $now;
    }

    $freq = strtolower(trim($frequency));

    if ($advanceIfPast && $targetTime <= $now) {
        while ($targetTime <= $now) {
            switch ($freq) {
                case 'daily':
                    $targetTime = strtotime('+1 day', $targetTime);
                    break;
                case 'weekly':
                    $targetTime = strtotime('+1 week', $targetTime);
                    break;
                case 'monthly':
                    $targetTime = strtotime('+1 month', $targetTime);
                    break;
                case 'quarterly':
                    $targetTime = strtotime('+3 months', $targetTime);
                    break;
                default:
                    $targetTime = strtotime('+1 day', $targetTime);
                    break;
            }
        }
    }

    return date('Y-m-d H:i:s', $targetTime);
}

function processDueSchedules(array &$schedules, string $storageFile): int {
    $now = time();
    $processed = 0;
    $mailService = new MailService();
    $updated = false;

    foreach ($schedules as &$item) {
        $status = strtolower($item['status'] ?? 'active');
        if ($status === 'cancelled' || $status === 'disabled') {
            continue;
        }

        $nextRunTs = strtotime($item['next_run_at'] ?? '');
        // Condition: Is Next Execution Time <= Current Timestamp?
        if ($nextRunTs && $nextRunTs <= $now) {
            $format = $item['format'] ?? 'PDF';
            $title = $item['report_title'] ?? 'Scheduled Health Report';
            $freq = $item['frequency'] ?? 'Daily';
            $repCategory = $item['report_type'] ?? 'unified';

            $deptMap = [
                'health_center' => 'Health Center Services',
                'sanitation'    => 'Sanitation Permits',
                'immunization'  => 'Immunization & Nutrition',
                'wastewater'    => 'Wastewater Services',
                'surveillance'  => 'Health Surveillance',
                'unified'       => 'All Core Departments'
            ];

            $dept = $item['department'] ?? '';
            if (empty($dept) || $dept === 'All Core Departments') {
                if (isset($deptMap[$repCategory])) {
                    $dept = $deptMap[$repCategory];
                    $item['department'] = $dept;
                } else {
                    $dept = 'Health Center Services';
                }
            }
            $downloadUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Civentral_HealthSanitation--Web/api/export.php?category=" . urlencode($repCategory) . "&format=" . strtolower($format) . "&report_title=" . urlencode($title);

            $subject = "Civentral Report: {$title} ({$freq}) - Automated Dispatch";
            $bodyHtml = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #B4D4FF; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(23,107,135,0.1);'>
                <div style='background: linear-gradient(135deg, #176B87 0%, #0F4A5E 100%); color: #ffffff; padding: 28px 24px; text-align: center;'>
                    <img src='cid:civentral_logo' alt='Civentral Logo' style='height: 85px; width: auto; max-width: 280px; margin-bottom: 14px; display: inline-block;' />
                    <h2 style='margin:0; font-size:20px; font-weight:800; letter-spacing:0.5px;'>Civentral Automated Report Delivery</h2>
                    <p style='margin:4px 0 0 0; font-size: 12px; color:#B4D4FF;'>Caloocan City Health & Sanitation Office</p>
                </div>
                <div style='padding: 24px; color: #334155; line-height: 1.6;'>
                    <p style='margin-top:0;'>Hello,</p>
                    <p>Your scheduled report <strong>" . htmlspecialchars($title) . "</strong> ({$freq}) for <strong>{$dept}</strong> has executed as of <strong>" . date('Y-m-d H:i:s') . "</strong>.</p>
                    <div style='background: #EEF5FF; border-left: 4px solid #176B87; padding: 16px; border-radius: 8px; margin: 20px 0;'>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #334155;'>
                            <tr><td style='padding: 6px 0; font-weight: bold; width: 140px;'>Schedule Title:</td><td><strong>" . htmlspecialchars($title) . "</strong></td></tr>
                            <tr><td style='padding: 6px 0; font-weight: bold;'>Department:</td><td>{$dept}</td></tr>
                            <tr><td style='padding: 6px 0; font-weight: bold;'>Delivery Format:</td><td><strong style='color:#176B87;'>{$format}</strong></td></tr>
                            <tr><td style='padding: 6px 0; font-weight: bold;'>Execution Status:</td><td><strong style='color:#16a34a;'>EXECUTED / SENT</strong></td></tr>
                            <tr><td style='padding: 6px 0; font-weight: bold;'>Execution Time:</td><td>" . date('Y-m-d H:i:s') . "</td></tr>
                        </table>
                    </div>

                    <div style='text-align: center; margin: 28px 0;'>
                        <a href='" . htmlspecialchars($downloadUrl) . "' target='_blank' style='display: inline-block; background: linear-gradient(135deg, #176B87 0%, #0F4A5E 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(23,107,135,0.35);'>
                            📥 Download " . htmlspecialchars($title) . " ({$format})
                        </a>
                    </div>
                    <p style='font-size: 12px; color: #64748b; text-align: center;'>Click the button above to download your {$format} report directly.</p>
                </div>
                <div style='background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                    Civentral Health & Sanitation MIS · Automated Scheduled Dispatch
                </div>
            </div>";

            // 1. Run Action / AI Task / Dispatch Email & SMS
            if (!empty($item['recipients']) && is_array($item['recipients'])) {
                foreach ($item['recipients'] as $recip) {
                    $mailService->sendNotificationEmail($recip, 'Report Recipient', $subject, $bodyHtml);
                }
            }

            // 2. Update Status in DB / JSON ──► EXECUTED / SENT
            $item['status'] = 'executed';
            $item['last_status'] = 'executed';
            $item['last_run_at'] = date('Y-m-d H:i:s');
            $item['last_execution_summary'] = "Generated {$format} report & sent to " . implode(', ', (array)($item['recipients'] ?? []));
            $item['execution_count'] = ($item['execution_count'] ?? 0) + 1;

            // Recalculate next run for recurring schedules
            $item['next_run_at'] = computeNextRun($item['start_date'] ?? date('Y-m-d'), $item['time'] ?? '08:00', $item['frequency'] ?? 'Daily', true);

            // 3. Log to ActivityLog & SchedulerLog
            try {
                $schedLogger = new SchedulerLog();
                $schedLogger->logRun(
                    'ScheduledReportDispatchJob',
                    'success',
                    "Executed scheduled report '{$title}' ({$format}) for " . implode(', ', (array)($item['recipients'] ?? [])) . ".",
                    null,
                    45,
                    'scheduler:auto'
                );
            } catch (\Throwable $e) {
                error_log("SchedulerLog write error: " . $e->getMessage());
            }

            $processed++;
            $updated = true;
        }
    }
    unset($item);

    if ($updated) {
        saveSchedules($storageFile, $schedules);
    }

    return $processed;
}

function sortSchedulesActiveFirst(array $schedules): array {
    usort($schedules, function ($a, $b) {
        $aStatus = strtolower($a['status'] ?? 'active');
        $bStatus = strtolower($b['status'] ?? 'active');
        $aLastStatus = strtolower($a['last_status'] ?? '');
        $bLastStatus = strtolower($b['last_status'] ?? '');

        $aExecuted = ($aStatus === 'executed' || $aStatus === 'sent' || $aStatus === 'completed' || $aLastStatus === 'executed');
        $bExecuted = ($bStatus === 'executed' || $bStatus === 'sent' || $bStatus === 'completed' || $bLastStatus === 'executed');

        // Active first (aExecuted = false comes before bExecuted = true)
        if (!$aExecuted && $bExecuted) return -1;
        if ($aExecuted && !$bExecuted) return 1;

        // Both are Active: soonest upcoming next_run_at first
        if (!$aExecuted && !$bExecuted) {
            $aTs = !empty($a['next_run_at']) ? strtotime($a['next_run_at']) : PHP_INT_MAX;
            $bTs = !empty($b['next_run_at']) ? strtotime($b['next_run_at']) : PHP_INT_MAX;
            return $aTs <=> $bTs;
        }

        // Both are Executed: most recently executed first
        $aLast = !empty($a['last_run_at']) ? strtotime($a['last_run_at']) : 0;
        $bLast = !empty($b['last_run_at']) ? strtotime($b['last_run_at']) : 0;
        return $bLast <=> $aLast;
    });

    return $schedules;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $schedules = getSchedules($storageFile);
    $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 1);
    $userName = $_SESSION['user_full_name'] ?? ($_SESSION['full_name'] ?? 'System User');

    // ─── GET: List all scheduled reports & process due schedules ──
    if ($method === 'GET') {
        $processedCount = processDueSchedules($schedules, $storageFile);
        $schedules = getSchedules($storageFile);
        $schedules = sortSchedulesActiveFirst($schedules);

        echo json_encode([
            'success' => true,
            'count' => count($schedules),
            'processed_count' => $processedCount,
            'current_timestamp' => date('Y-m-d H:i:s'),
            'schedules' => $schedules
        ]);
        exit;
    }

    // ─── DELETE: Cancel / Remove schedule ─────────────────────────
    if ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_GET;
        $id = $input['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Schedule ID required.']);
            exit;
        }

        $found = false;
        $schedules = array_values(array_filter($schedules, function($item) use ($id, &$found) {
            if ($item['id'] === $id) {
                $found = true;
                return false;
            }
            return true;
        }));

        if ($found) {
            saveSchedules($storageFile, $schedules);
            $logModel = new ActivityLog();
            $logModel->log("Cancelled report schedule", [
                'user_name' => $userName,
                'role'      => $_SESSION['role'] ?? 'Staff',
                'module'    => 'Reporting System',
                'details'   => "Cancelled schedule ID: {$id}",
                'status'    => 'Success',
            ]);
            echo json_encode(['success' => true, 'message' => 'Schedule successfully removed.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
        }
        exit;
    }

    // ─── POST: Create or Execute schedules ─────────────────────────
    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? $_POST;

        $action = $input['action'] ?? 'create';

        // Sub-action: Run pending / triggered schedules
        if ($action === 'run_pending') {
            $processed = processDueSchedules($schedules, $storageFile);

            $schedLogger = new SchedulerLog();
            $schedLogger->logRun(
                'ScheduledReportDispatchJob',
                'success',
                "Processed {$processed} scheduled report(s) via schedule API.",
                null,
                0,
                'api:reports_schedule'
            );

            echo json_encode([
                'success' => true,
                'message' => "Processed {$processed} scheduled report(s).",
                'processed_count' => $processed
            ]);
            exit;
        }

        // Standard action: Create new schedule
        $frequency   = trim($input['frequency'] ?? 'Daily');
        $startDate   = trim($input['start_date'] ?? date('Y-m-d'));
        $time        = trim($input['time'] ?? '08:00');
        $rawRecips   = $input['recipients'] ?? '';
        $format      = strtoupper(trim($input['format'] ?? 'PDF'));
        $reportType  = trim($input['report_type'] ?? 'unified');
        $reportTitle = trim($input['report_title'] ?? 'Compliance & Operational Report');

        $deptMap = [
            'health_center' => 'Health Center Services',
            'sanitation'    => 'Sanitation Permits',
            'immunization'  => 'Immunization & Nutrition',
            'wastewater'    => 'Wastewater Services',
            'surveillance'  => 'Health Surveillance',
            'unified'       => 'All Core Departments'
        ];

        $rawDept = trim($input['department'] ?? '');
        if (!empty($rawDept) && $rawDept !== 'All Core Departments') {
            $department = $rawDept;
        } else {
            $department = $deptMap[$reportType] ?? ($rawDept ?: 'Health Center Services');
        }

        // Parse and validate recipient emails
        if (is_array($rawRecips)) {
            $recipientsList = $rawRecips;
        } else {
            $recipientsList = array_filter(array_map('trim', preg_split('/[,;\s]+/', (string)$rawRecips)));
        }

        $validEmails = [];
        foreach ($recipientsList as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = strtolower($email);
            }
        }

        if (empty($validEmails)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Please provide at least one valid recipient email address.'
            ]);
            exit;
        }

        $allowedFreq = ['Daily', 'Weekly', 'Monthly', 'Quarterly'];
        if (!in_array($frequency, $allowedFreq, true)) {
            $frequency = 'Daily';
        }

        // Calculate next run date & time properly based on selected date, time, and frequency
        $nextRun = computeNextRun($startDate, $time, $frequency, true);

        $scheduleId = 'sched_' . bin2hex(random_bytes(6));
        $newSchedule = [
            'id'                     => $scheduleId,
            'report_title'           => $reportTitle,
            'department'             => $department,
            'report_type'            => $reportType,
            'report_start_date'      => $input['report_start_date'] ?? '',
            'report_end_date'        => $input['report_end_date'] ?? '',
            'include_visuals'        => !empty($input['include_visuals']) ? 1 : 0,
            'frequency'              => $frequency,
            'start_date'             => $startDate,
            'time'                   => $time,
            'recipients'             => array_values(array_unique($validEmails)),
            'format'                 => $format,
            'status'                 => 'active',
            'created_by'             => $userName,
            'created_at'             => date('Y-m-d H:i:s'),
            'last_run_at'            => null,
            'next_run_at'            => $nextRun,
            'last_status'            => '',
            'last_execution_summary' => '',
            'execution_count'        => 0
        ];

        $schedules[] = $newSchedule;
        saveSchedules($storageFile, $schedules);

        // Audit Trail entry
        $logModel = new ActivityLog();
        $logModel->log("Created automated report schedule", [
            'user_name' => $userName,
            'role'      => $_SESSION['role'] ?? 'Staff Member',
            'module'    => 'Reporting System',
            'details'   => "Scheduled {$frequency} {$format} report '{$reportTitle}' for " . implode(', ', $newSchedule['recipients']) . " next run at {$nextRun}",
            'status'    => 'Success',
        ]);

        $nextRunFormatted = date('M j, Y g:i A', strtotime($nextRun));
        $msg = "Schedule saved! Status is ACTIVE. Next automated execution will run at {$nextRunFormatted} for " . implode(', ', $newSchedule['recipients']) . ".";

        echo json_encode([
            'success'  => true,
            'message'  => $msg,
            'schedule' => $newSchedule
        ]);
        exit;
    }

} catch (\Throwable $e) {
    error_log('Scheduled Report Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error while processing scheduled report.'
    ]);
}
