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

function computeNextRun(string $startDate, string $time, string $frequency, bool $advanceIfPast = false): string {
    $combined = "{$startDate} {$time}:00";
    $targetTime = strtotime($combined);
    $now = time();

    if ($targetTime === false) {
        $targetTime = $now;
    }

    if ($advanceIfPast) {
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
    }

    return date('Y-m-d H:i:s', $targetTime);
}

function processDueSchedules(array &$schedules, string $storageFile): int {
    $now = time();
    $processed = 0;
    $mailService = new MailService();
    $updated = false;

    foreach ($schedules as &$item) {
        if (($item['status'] ?? 'active') !== 'active') {
            continue;
        }

        $nextRunTs = strtotime($item['next_run_at'] ?? '');
        if ($nextRunTs && $nextRunTs <= $now) {
            $format = $item['format'] ?? 'PDF';
            $title = $item['report_title'] ?? 'Scheduled Health Report';
            $dept = $item['department'] ?? 'Health & Sanitation';
            $freq = $item['frequency'] ?? 'Weekly';

            $repCategory = $item['report_type'] ?? 'unified';
            $downloadUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Civentral_HealthSanitation--Web/api/export.php?category=" . urlencode($repCategory) . "&format=" . strtolower($format);

            $subject = "Automated Health & Sanitation Report Delivery: {$title} ({$freq})";
            $bodyHtml = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #B4D4FF; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(23,107,135,0.1);'>
                <div style='background: linear-gradient(135deg, #176B87 0%, #0F4A5E 100%); color: #ffffff; padding: 28px 24px; text-align: center;'>
                    <img src='cid:civentral_logo' alt='Civentral Logo' style='height: 52px; width: auto; max-width: 180px; margin-bottom: 12px; display: inline-block;' />
                    <h2 style='margin:0; font-size:20px; font-weight:800; letter-spacing:0.5px;'>Civentral Automated Report Delivery</h2>
                    <p style='margin:4px 0 0 0; font-size: 12px; color:#B4D4FF;'>Caloocan City Health & Sanitation Office</p>
                </div>
                <div style='padding: 24px; color: #334155; line-height: 1.6;'>
                    <p style='margin-top:0;'>Hello,</p>
                    <p>Your scheduled <strong>{$freq}</strong> report for <strong>{$dept}</strong> has executed as of <strong>" . date('Y-m-d H:i:s') . "</strong>.</p>
                    <div style='background: #EEF5FF; border-left: 4px solid #176B87; padding: 16px; border-radius: 8px; margin: 20px 0;'>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #334155;'>
                            <tr><td style='padding: 6px 0; font-weight: bold; width: 140px;'>Report Title:</td><td>{$title}</td></tr>
                            <tr><td style='padding: 6px 0; font-weight: bold;'>Department:</td><td>{$dept}</td></tr>
                            <tr><td style='padding: 6px 0; font-weight: bold;'>Delivery Format:</td><td><strong style='color:#176B87;'>{$format}</strong></td></tr>
                            <tr><td style='padding: 6px 0; font-weight: bold;'>Execution Time:</td><td>" . date('Y-m-d H:i:s') . "</td></tr>
                        </table>
                    </div>

                    <div style='text-align: center; margin: 28px 0;'>
                        <a href='" . htmlspecialchars($downloadUrl) . "' target='_blank' style='display: inline-block; background: linear-gradient(135deg, #176B87 0%, #0F4A5E 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(23,107,135,0.35);'>
                            📥 Download {$format} Report Document
                        </a>
                    </div>
                    <p style='font-size: 12px; color: #64748b; text-align: center;'>Click the button above to download your {$format} report directly.</p>
                </div>
                <div style='background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                    Civentral Health & Sanitation MIS · Automated Scheduled Dispatch
                </div>
            </div>";

            if (!empty($item['recipients']) && is_array($item['recipients'])) {
                foreach ($item['recipients'] as $recip) {
                    $mailService->sendNotificationEmail($recip, 'Report Recipient', $subject, $bodyHtml);
                }
            }

            $item['last_run_at'] = date('Y-m-d H:i:s');
            $item['next_run_at'] = computeNextRun($item['start_date'] ?? date('Y-m-d'), $item['time'] ?? '08:00', $item['frequency'] ?? 'Weekly', true);
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    $schedules = getSchedules($storageFile);
    $userId = $_SESSION['user_id'] ?? ($_SESSION['user']['id'] ?? 1);
    $userName = $_SESSION['user_full_name'] ?? ($_SESSION['full_name'] ?? 'System User');

    // Automatically trigger any schedules whose target date & time has arrived
    processDueSchedules($schedules, $storageFile);

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

        // Calculate next run date & time directly based on selected date & time
        $nextRun = computeNextRun($startDate, $time, $frequency, false);

        $scheduleId = 'sched_' . bin2hex(random_bytes(6));
        $newSchedule = [
            'id'                 => $scheduleId,
            'report_title'       => $reportTitle,
            'department'         => $department,
            'report_type'        => $input['report_type'] ?? 'unified',
            'report_start_date'  => $input['report_start_date'] ?? '',
            'report_end_date'    => $input['report_end_date'] ?? '',
            'include_visuals'    => !empty($input['include_visuals']) ? 1 : 0,
            'frequency'          => $frequency,
            'start_date'         => $startDate,
            'time'               => $time,
            'recipients'         => array_values(array_unique($validEmails)),
            'format'             => $format,
            'status'             => 'active',
            'created_by'         => $userName,
            'created_at'         => date('Y-m-d H:i:s'),
            'last_run_at'        => null,
            'next_run_at'        => $nextRun
        ];

        $schedules[] = $newSchedule;
        saveSchedules($storageFile, $schedules);

        // Run processDueSchedules in case the scheduled time is due right now
        processDueSchedules($schedules, $storageFile);

        // Dispatch report email directly to all specified recipients immediately
        $mailService = new MailService();
        $reportTypeVal = $input['report_type'] ?? 'unified';
        $reportTypeLabels = [
            'unified'       => 'Unified Global Report (All Modules)',
            'health_center' => 'Health Center Services',
            'sanitation'    => 'Sanitation Permits',
            'immunization'  => 'Immunization & Nutrition',
            'wastewater'    => 'Wastewater Services',
            'surveillance'  => 'Health Surveillance'
        ];
        $reportTypeLabel = $reportTypeLabels[$reportTypeVal] ?? 'Unified Report';
        $reportStartDate = !empty($input['report_start_date']) ? $input['report_start_date'] : date('Y-m-d', strtotime('-30 days'));
        $reportEndDate   = !empty($input['report_end_date']) ? $input['report_end_date'] : date('Y-m-d');
        $includeVisuals  = !empty($input['include_visuals']);

        $downloadUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/Civentral_HealthSanitation--Web/api/export.php?category=" . urlencode($reportTypeVal) . "&format=" . strtolower($format);

        $emailSubject = "Civentral Health Report: {$reportTitle} ({$format})";
        $emailHtml = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #B4D4FF; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(23,107,135,0.1);'>
            <div style='background: linear-gradient(135deg, #176B87 0%, #0F4A5E 100%); color: #ffffff; padding: 28px 24px; text-align: center;'>
                <img src='cid:civentral_logo' alt='Civentral Logo' style='height: 52px; width: auto; max-width: 180px; margin-bottom: 12px; display: inline-block;' />
                <h1 style='margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 1px;'>Civentral Health & Sanitation MIS</h1>
                <p style='margin: 4px 0 0 0; font-size: 12px; color: #B4D4FF;'>Caloocan City Health Office · Official Executive Report</p>
            </div>
            <div style='padding: 24px; color: #334155; line-height: 1.6;'>
                <p style='font-size: 14px; margin-top: 0;'>Hello,</p>
                <p style='font-size: 14px;'>Your requested report <strong>" . htmlspecialchars($reportTitle) . "</strong> has been generated and is ready for download.</p>
                
                <div style='background: #EEF5FF; border-left: 4px solid #176B87; padding: 16px; border-radius: 8px; margin: 20px 0;'>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #334155;'>
                        <tr><td style='padding: 6px 0; font-weight: 600; width: 150px;'>Report Module:</td><td>" . htmlspecialchars($reportTypeLabel) . "</td></tr>
                        <tr><td style='padding: 6px 0; font-weight: 600;'>Report Date Range:</td><td>" . htmlspecialchars($reportStartDate) . " to " . htmlspecialchars($reportEndDate) . "</td></tr>
                        <tr><td style='padding: 6px 0; font-weight: 600;'>Visual Graphs:</td><td>" . ($includeVisuals ? 'Included (Charts & Graphs)' : 'Tabular Summary Only') . "</td></tr>
                        <tr><td style='padding: 6px 0; font-weight: 600;'>Export Format:</td><td><strong style='color:#176B87;'>" . htmlspecialchars($format) . "</strong></td></tr>
                        <tr><td style='padding: 6px 0; font-weight: 600;'>Schedule Frequency:</td><td>" . htmlspecialchars($frequency) . " (Next: " . htmlspecialchars($nextRun) . ")</td></tr>
                    </table>
                </div>

                <div style='text-align: center; margin: 28px 0;'>
                    <a href='" . htmlspecialchars($downloadUrl) . "' target='_blank' style='display: inline-block; background: linear-gradient(135deg, #176B87 0%, #0F4A5E 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 12px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 14px rgba(23,107,135,0.35);'>
                        📥 Download " . htmlspecialchars($format) . " Report Document
                    </a>
                </div>

                <p style='font-size: 12px; color: #64748b; text-align: center;'>Click the button above to download your " . htmlspecialchars($format) . " document directly.</p>
            </div>
            <div style='background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                Civentral Health & Sanitation Management Information System · Caloocan City LGU
            </div>
        </div>";

        $dispatchedCount = 0;
        foreach ($newSchedule['recipients'] as $recipEmail) {
            if ($mailService->sendNotificationEmail($recipEmail, 'Report Recipient', $emailSubject, $emailHtml)) {
                $dispatchedCount++;
            }
        }

        // Audit Trail entry
        $logModel = new ActivityLog();
        $logModel->log("Created automated report schedule & dispatched email", [
            'user_name' => $userName,
            'role'      => $_SESSION['role'] ?? 'Staff Member',
            'module'    => 'Reporting System',
            'details'   => "Scheduled {$frequency} {$format} report '{$reportTitle}' and sent to " . implode(', ', $newSchedule['recipients']),
            'status'    => 'Success',
        ]);

        echo json_encode([
            'success' => true,
            'message' => "Report schedule created & email delivered to " . implode(', ', $newSchedule['recipients']) . "!",
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
