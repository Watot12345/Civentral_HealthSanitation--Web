<?php
// api/reports/schedule.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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

function computeNextRun(string $startDate, string $time, string $frequency): string {
    $combined = "{$startDate} {$time}:00";
    $targetTime = strtotime($combined);
    $now = time();

    if ($targetTime === false) {
        $targetTime = $now;
    }

    // If initial scheduled time is in the past, calculate next upcoming interval
    while ($targetTime <= $now) {
        switch (strtolower($frequency)) {
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
                $targetTime = strtotime('+1 week', $targetTime);
                break;
        }
    }

    return date('Y-m-d H:i:s', $targetTime);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $schedules = getSchedules($storageFile);
    $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 1);
    $userName = $_SESSION['user_full_name'] ?? ($_SESSION['full_name'] ?? 'System User');

    // ─── GET: List all scheduled reports ──────────────────────────
    if ($method === 'GET') {
        echo json_encode([
            'success' => true,
            'count' => count($schedules),
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
            $now = time();
            $processed = 0;
            $mailService = new MailService();

            foreach ($schedules as &$item) {
                if (($item['status'] ?? 'active') !== 'active') {
                    continue;
                }

                $nextRun = strtotime($item['next_run_at'] ?? 'now');
                if ($nextRun <= $now || !empty($input['force_id']) && $input['force_id'] === $item['id']) {
                    // Dispatch report email
                    $subject = "Automated Health Report Delivery: {$item['report_title']} ({$item['frequency']})";
                    $bodyHtml = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #B4D4FF; border-radius: 12px;'>
                        <div style='background: #176B87; color: white; padding: 16px; border-radius: 8px 8px 0 0; text-align: center;'>
                            <h2 style='margin:0;'>Civentral Automated Report</h2>
                            <p style='margin:4px 0 0 0; font-size: 12px;'>Caloocan City Health & Sanitation Office</p>
                        </div>
                        <div style='padding: 20px; color: #334155;'>
                            <p>Hello,</p>
                            <p>Your scheduled <strong>{$item['frequency']}</strong> report for <strong>{$item['department']}</strong> has been automatically generated.</p>
                            <ul>
                                <li><strong>Report Title:</strong> {$item['report_title']}</li>
                                <li><strong>Format:</strong> {$item['format']}</li>
                                <li><strong>Generated At:</strong> " . date('Y-m-d H:i:s') . "</li>
                                <li><strong>Frequency:</strong> {$item['frequency']}</li>
                            </ul>
                            <p>You may also access live analytics in the Civentral Executive Portal.</p>
                        </div>
                        <div style='background: #f8fafc; padding: 12px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                            Civentral Health & Sanitation Management System · Automated Scheduled Dispatch
                        </div>
                    </div>";

                    foreach ($item['recipients'] as $recip) {
                        $mailService->sendNotificationEmail($recip, 'Designated Officer', $subject, $bodyHtml);
                    }

                    $item['last_run_at'] = date('Y-m-d H:i:s');
                    $item['next_run_at'] = computeNextRun(date('Y-m-d'), date('H:i'), $item['frequency']);
                    $processed++;
                }
            }
            unset($item);

            saveSchedules($storageFile, $schedules);

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
        $frequency   = trim($input['frequency'] ?? 'Weekly');
        $startDate   = trim($input['start_date'] ?? date('Y-m-d'));
        $time        = trim($input['time'] ?? '08:00');
        $rawRecips   = $input['recipients'] ?? '';
        $format      = strtoupper(trim($input['format'] ?? 'PDF'));
        $department  = trim($input['department'] ?? 'All Core Departments');
        $reportTitle = trim($input['report_title'] ?? 'Compliance & Operational Report');

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
            $frequency = 'Weekly';
        }

        $nextRun = computeNextRun($startDate, $time, $frequency);

        $scheduleId = 'sched_' . bin2hex(random_bytes(6));
        $newSchedule = [
            'id'           => $scheduleId,
            'report_title' => $reportTitle,
            'department'   => $department,
            'frequency'    => $frequency,
            'start_date'   => $startDate,
            'time'         => $time,
            'recipients'   => array_values(array_unique($validEmails)),
            'format'       => $format,
            'status'       => 'active',
            'created_by'   => $userName,
            'created_at'   => date('Y-m-d H:i:s'),
            'last_run_at'  => null,
            'next_run_at'  => $nextRun
        ];

        $schedules[] = $newSchedule;
        saveSchedules($storageFile, $schedules);

        // Audit Trail entry
        $logModel = new ActivityLog();
        $logModel->log("Created automated report schedule", [
            'user_name' => $userName,
            'role'      => $_SESSION['role'] ?? 'Staff Member',
            'module'    => 'Reporting System',
            'details'   => "Scheduled {$frequency} {$format} report '{$reportTitle}' for " . implode(', ', $newSchedule['recipients']),
            'status'    => 'Success',
        ]);

        // Send confirmation notification email to recipients
        $mailService = new MailService();
        $confirmSubject = "Schedule Confirmed: Automated {$frequency} {$reportTitle}";
        $confirmHtml = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #B4D4FF; border-radius: 12px;'>
            <div style='background: #176B87; color: white; padding: 18px; border-radius: 8px 8px 0 0; text-align: center;'>
                <h2 style='margin:0;'>Report Schedule Confirmed</h2>
                <p style='margin:4px 0 0 0; font-size: 12px;'>Civentral Health & Sanitation Portal · Caloocan LGU</p>
            </div>
            <div style='padding: 24px; color: #334155; line-height: 1.6;'>
                <p>Hello,</p>
                <p>An automated recurring report delivery schedule has been successfully configured:</p>
                <table style='width: 100%; border-collapse: collapse; margin: 16px 0; font-size: 14px;'>
                    <tr style='background: #f1f5f9;'><td style='padding: 8px; font-weight: bold;'>Report:</td><td style='padding: 8px;'>{$reportTitle}</td></tr>
                    <tr><td style='padding: 8px; font-weight: bold;'>Department:</td><td style='padding: 8px;'>{$department}</td></tr>
                    <tr style='background: #f1f5f9;'><td style='padding: 8px; font-weight: bold;'>Frequency:</td><td style='padding: 8px;'>{$frequency} at {$time}</td></tr>
                    <tr><td style='padding: 8px; font-weight: bold;'>Delivery Format:</td><td style='padding: 8px;'>{$format}</td></tr>
                    <tr style='background: #f1f5f9;'><td style='padding: 8px; font-weight: bold;'>Next Run:</td><td style='padding: 8px;'>{$nextRun}</td></tr>
                    <tr><td style='padding: 8px; font-weight: bold;'>Configured By:</td><td style='padding: 8px;'>{$userName}</td></tr>
                </table>
                <p style='font-size: 13px; color: #64748b;'>Reports will be delivered automatically to this email address on the scheduled interval.</p>
            </div>
            <div style='background: #f8fafc; padding: 12px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                Civentral Health & Sanitation Management Information System · Caloocan City
            </div>
        </div>";

        foreach ($newSchedule['recipients'] as $recip) {
            $mailService->sendNotificationEmail($recip, 'Report Subscriber', $confirmSubject, $confirmHtml);
        }

        echo json_encode([
            'success' => true,
            'message' => "Report successfully scheduled for {$frequency} delivery!",
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
