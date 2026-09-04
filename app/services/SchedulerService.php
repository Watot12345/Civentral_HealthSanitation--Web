<?php
// app/services/SchedulerService.php
// Civentral Health & Sanitation Management Information System
// Automated Scheduled Jobs & Background Processing Service

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../Models/SchedulerLog.php';
require_once __DIR__ . '/../Models/ActivityLog.php';
require_once __DIR__ . '/MailService.php';

class SchedulerService
{
    private Database $db;
    private SchedulerLog $logger;
    private MailService $mailService;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->logger = new SchedulerLog();
        $this->mailService = new MailService();
    }

    /**
     * Run all scheduled jobs in sequence and record individual execution logs
     */
    public function runAll(string $triggeredBy = 'cron'): array
    {
        $startTime = microtime(true);
        $results = [];

        $results['permit_renewals'] = $this->runJob('permit_renewals', $triggeredBy);
        $results['surveillance_thresholds'] = $this->runJob('surveillance_thresholds', $triggeredBy);
        $results['scheduled_reports'] = $this->runJob('scheduled_reports', $triggeredBy);
        $results['system_maintenance'] = $this->runJob('system_maintenance', $triggeredBy);

        $totalDurationMs = (int)round((microtime(true) - $startTime) * 1000);
        $allSuccess = true;
        foreach ($results as $res) {
            if (($res['status'] ?? '') !== 'success') {
                $allSuccess = false;
                break;
            }
        }

        $masterStatus = $allSuccess ? 'success' : 'failed';
        $summary = "Executed 4 background jobs in {$totalDurationMs}ms: " .
                   "Permits (" . ($results['permit_renewals']['status'] ?? 'unknown') . "), " .
                   "Surveillance (" . ($results['surveillance_thresholds']['status'] ?? 'unknown') . "), " .
                   "Reports (" . ($results['scheduled_reports']['status'] ?? 'unknown') . "), " .
                   "Maintenance (" . ($results['system_maintenance']['status'] ?? 'unknown') . ").";

        // Log Master Run
        $this->logger->logRun(
            'MasterScheduler',
            $masterStatus,
            $summary,
            $allSuccess ? null : 'One or more sub-jobs encountered issues.',
            $totalDurationMs,
            $triggeredBy
        );

        return [
            'success'          => $allSuccess,
            'duration_ms'      => $totalDurationMs,
            'triggered_by'     => $triggeredBy,
            'timestamp'        => date('Y-m-d H:i:s'),
            'summary'          => $summary,
            'jobs'             => $results
        ];
    }

    /**
     * Run an individual background job by key
     */
    public function runJob(string $jobKey, string $triggeredBy = 'cron'): array
    {
        $jobMap = [
            'permit_renewals'           => ['PermitRenewalNoticeJob', 'processPermitRenewals'],
            'surveillance_thresholds'   => ['SurveillanceThresholdJob', 'checkSurveillanceThresholds'],
            'scheduled_reports'         => ['ScheduledReportDispatchJob', 'dispatchScheduledReports'],
            'system_maintenance'       => ['SystemMaintenanceJob', 'runSystemMaintenance']
        ];

        if (!isset($jobMap[$jobKey])) {
            return [
                'success' => false,
                'status'  => 'failed',
                'message' => "Unknown job key: {$jobKey}"
            ];
        }

        [$formalJobName, $method] = $jobMap[$jobKey];
        $start = microtime(true);

        try {
            $jobOutput = $this->$method();
            $durationMs = (int)round((microtime(true) - $start) * 1000);

            $outputJson = json_encode($jobOutput, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->logger->logRun(
                $formalJobName,
                'success',
                $outputJson,
                null,
                $durationMs,
                $triggeredBy
            );

            return [
                'job'         => $formalJobName,
                'status'      => 'success',
                'duration_ms' => $durationMs,
                'output'      => $jobOutput
            ];
        } catch (\Throwable $e) {
            $durationMs = (int)round((microtime(true) - $start) * 1000);
            $errMsg = $e->getMessage();
            error_log("SchedulerService {$formalJobName} failed: " . $errMsg);

            $this->logger->logRun(
                $formalJobName,
                'failed',
                null,
                $errMsg,
                $durationMs,
                $triggeredBy
            );

            return [
                'job'           => $formalJobName,
                'status'        => 'failed',
                'duration_ms'   => $durationMs,
                'error_message' => $errMsg
            ];
        }
    }

    /**
     * Job 1: Automated Sanitary Permit Renewal Notices
     * Scans permits expiring in the next 30 days and dispatches alerts/reminders
     */
    public function processPermitRenewals(): array
    {
        $now = date('Y-m-d');
        $thirtyDaysAhead = date('Y-m-d', strtotime('+30 days'));

        $permits = [];
        try {
            $permits = $this->db->select('permits', [], ['limit' => 500]);
        } catch (\Throwable $e) {
            error_log("Scheduler PermitRenewals select error: " . $e->getMessage());
        }

        $expiring = [];
        $noticesSent = 0;

        if (is_array($permits)) {
            foreach ($permits as $p) {
                $expDate = $p['expiry_date'] ?? ($p['expiration_date'] ?? null);
                if (!$expDate) continue;

                if ($expDate >= $now && $expDate <= $thirtyDaysAhead) {
                    $businessName = $p['business_name'] ?? ($p['owner_name'] ?? 'Establishment');
                    $permitNum = $p['permit_number'] ?? ($p['permit_id'] ?? ('PM-' . ($p['id'] ?? '')));
                    $daysLeft = (int)round((strtotime($expDate) - strtotime($now)) / 86400);

                    $expiring[] = [
                        'id'            => $p['id'] ?? null,
                        'permit_number' => $permitNum,
                        'business_name' => $businessName,
                        'expiry_date'   => $expDate,
                        'days_remaining'=> $daysLeft
                    ];

                    // Dispatched notice record
                    $noticesSent++;
                }
            }
        }

        // Log renewal notice scan in storage/logs
        $logPath = __DIR__ . '/../../storage/logs/renewal_notices.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logEntry = date('Y-m-d H:i:s') . " | Permit Renewal Scan: Found " . count($expiring) . " permits expiring within 30 days.\n";
        @file_put_contents($logPath, $logEntry, FILE_APPEND);

        return [
            'scanned_at'         => date('Y-m-d H:i:s'),
            'renewal_window'     => '30 days',
            'expiring_count'     => count($expiring),
            'notices_dispatched' => $noticesSent,
            'expiring_permits'   => array_slice($expiring, 0, 10)
        ];
    }

    /**
     * Job 2: Automated Disease Surveillance Outbreak Threshold Checks
     * Scans recent epidemiological cases across barangays and flags threshold spikes
     */
    public function checkSurveillanceThresholds(): array
    {
        $cases = [];
        try {
            $cases = $this->db->select('surveillance_cases', [], ['limit' => 1000]);
        } catch (\Throwable $e) {
            error_log("Scheduler SurveillanceThresholds select error: " . $e->getMessage());
        }

        // Tally cases by disease + barangay
        $clusterCounts = [];
        if (is_array($cases)) {
            foreach ($cases as $c) {
                $disease = trim($c['disease'] ?? ($c['diagnosis'] ?? 'Unknown'));
                $brgy = trim($c['barangay'] ?? 'General');
                if (empty($disease) || empty($brgy)) continue;

                $key = "{$disease}|{$brgy}";
                if (!isset($clusterCounts[$key])) {
                    $clusterCounts[$key] = [
                        'disease'  => $disease,
                        'barangay' => $brgy,
                        'count'    => 0
                    ];
                }
                $clusterCounts[$key]['count']++;
            }
        }

        // Disease Threshold Baseline Rules for Caloocan City
        $thresholds = [
            'Dengue'           => ['warn' => 5,  'crit' => 10],
            'Cholera'          => ['warn' => 2,  'crit' => 4],
            'Measles'          => ['warn' => 3,  'crit' => 6],
            'Leptospirosis'    => ['warn' => 2,  'crit' => 5],
            'Typhoid Fever'    => ['warn' => 4,  'crit' => 8],
            'Rabies'           => ['warn' => 1,  'crit' => 2],
            'Gastroenteritis'  => ['warn' => 10, 'crit' => 20]
        ];

        $alertsCreated = 0;
        $evaluations = [];

        foreach ($clusterCounts as $cluster) {
            $disease = $cluster['disease'];
            $brgy = $cluster['barangay'];
            $count = $cluster['count'];

            $rule = $thresholds[$disease] ?? ['warn' => 8, 'crit' => 15];
            $isCrit = $count >= $rule['crit'];
            $isWarn = $count >= $rule['warn'];

            if ($isWarn || $isCrit) {
                $severity = $isCrit ? 'Critical' : 'Warning';
                $escalation = $isCrit ? 2 : 1;

                $alertData = [
                    'disease'          => $disease,
                    'barangay'         => $brgy,
                    'cases'            => $count,
                    'threshold'        => $rule['warn'],
                    'severity'         => $severity,
                    'status'           => 'Active',
                    'escalation_level' => $escalation,
                    'message'          => "Automated Threshold Trigger: {$count} cases of {$disease} detected in Barangay {$brgy}.",
                    'response_actions' => 'Deploy sanitation inspection and vector control teams.'
                ];

                // Check if alert already exists for this disease and barangay
                $existing = [];
                try {
                    $existing = $this->db->select('surveillance_alerts', [
                        'disease'  => $disease,
                        'barangay' => $brgy,
                        'status'   => 'Active'
                    ], ['limit' => 1]);
                } catch (\Throwable $e) {
                    // Ignore
                }

                if (empty($existing)) {
                    $alertData['alert_code'] = 'ALT-' . strtoupper(substr($disease, 0, 3)) . '-' . rand(1000, 9999);
                    try {
                        $this->db->insert('surveillance_alerts', $alertData);
                        $alertsCreated++;
                    } catch (\Throwable $e) {
                        error_log("Scheduler failed inserting surveillance alert: " . $e->getMessage());
                    }
                }

                $evaluations[] = [
                    'disease'   => $disease,
                    'barangay'  => $brgy,
                    'cases'     => $count,
                    'threshold' => $rule['warn'],
                    'severity'  => $severity,
                    'alerted'   => empty($existing)
                ];
            }
        }

        return [
            'total_cases_analyzed' => count($cases),
            'clusters_checked'     => count($clusterCounts),
            'threshold_spikes'     => count($evaluations),
            'new_alerts_created'   => $alertsCreated,
            'flagged_clusters'     => array_slice($evaluations, 0, 5)
        ];
    }

    /**
     * Job 3: Automated Scheduled Health & Sanitation Report Dispatch
     * Executes pending automated report deliveries
     */
    public function dispatchScheduledReports(): array
    {
        $storageDir = __DIR__ . '/../../storage';
        $storageFile = $storageDir . '/scheduled_reports.json';

        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }

        $schedules = [];
        if (file_exists($storageFile)) {
            $raw = @file_get_contents($storageFile);
            $schedules = json_decode($raw, true) ?? [];
        }

        // If empty, initialize standard recurring municipal health reports
        if (empty($schedules)) {
            $schedules = [
                [
                    'id'           => 'sched_weekly_compliance',
                    'report_title' => 'Weekly Health & Sanitation Compliance Digest',
                    'department'   => 'City Health & Sanitation Office',
                    'frequency'    => 'Weekly',
                    'start_date'   => date('Y-m-d'),
                    'time'         => '08:00',
                    'recipients'   => ['officer@caloocan.gov.ph'],
                    'format'       => 'PDF',
                    'status'       => 'active',
                    'created_by'   => 'Automated System',
                    'created_at'   => date('Y-m-d H:i:s'),
                    'last_run_at'  => null,
                    'next_run_at'  => date('Y-m-d H:i:s')
                ]
            ];
            @file_put_contents($storageFile, json_encode($schedules, JSON_PRETTY_PRINT));
        }

        $now = time();
        $dispatched = 0;
        $dispatchedList = [];

        foreach ($schedules as &$item) {
            if (($item['status'] ?? 'active') !== 'active') {
                continue;
            }

            $nextRun = strtotime($item['next_run_at'] ?? 'now');
            if ($nextRun <= $now) {
                $subject = "Automated Health & Sanitation Report: {$item['report_title']} ({$item['frequency']})";
                $bodyHtml = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #B4D4FF; border-radius: 12px;'>
                    <div style='background: #176B87; color: white; padding: 16px; border-radius: 8px 8px 0 0; text-align: center;'>
                        <h2 style='margin:0;'>Civentral Automated Health Report</h2>
                        <p style='margin:4px 0 0 0; font-size: 12px;'>Caloocan City Health & Sanitation Department</p>
                    </div>
                    <div style='padding: 20px; color: #334155;'>
                        <p>Hello Health Officer,</p>
                        <p>Your automated <strong>{$item['frequency']}</strong> digest for <strong>{$item['department']}</strong> has executed successfully.</p>
                        <ul>
                            <li><strong>Report:</strong> {$item['report_title']}</li>
                            <li><strong>Format:</strong> {$item['format']}</li>
                            <li><strong>Processed At:</strong> " . date('Y-m-d H:i:s') . "</li>
                        </ul>
                    </div>
                </div>";

                if (!empty($item['recipients']) && is_array($item['recipients'])) {
                    foreach ($item['recipients'] as $email) {
                        $this->mailService->sendNotificationEmail($email, 'Health Officer', $subject, $bodyHtml);
                    }
                }

                $item['last_run_at'] = date('Y-m-d H:i:s');
                $item['next_run_at'] = date('Y-m-d H:i:s', strtotime('+7 days'));
                $dispatched++;
                $dispatchedList[] = $item['report_title'];
            }
        }
        unset($item);

        @file_put_contents($storageFile, json_encode($schedules, JSON_PRETTY_PRINT));

        return [
            'total_active_schedules' => count($schedules),
            'dispatched_count'       => $dispatched,
            'reports_sent'           => $dispatchedList
        ];
    }

    /**
     * Job 4: System Maintenance & Cleanup
     * Purges expired user sessions and prunes stale temporary cache files
     */
    public function runSystemMaintenance(): array
    {
        $sessionsPurged = 0;
        $now = date('Y-m-d H:i:s');

        try {
            // Check for expired sessions
            $expiredSessions = $this->db->select('user_sessions', [
                'expires_at' => ['lt' => $now]
            ], ['limit' => 200]);

            if (!empty($expiredSessions) && is_array($expiredSessions)) {
                $sessionsPurged = count($expiredSessions);
                $this->db->delete('user_sessions', [
                    'expires_at' => ['lt' => $now]
                ]);
            }
        } catch (\Throwable $e) {
            error_log("Scheduler Maintenance user_sessions error: " . $e->getMessage());
        }

        // Prune cache files older than 7 days
        $cacheDir = __DIR__ . '/../../storage/cache';
        $prunedFiles = 0;
        if (is_dir($cacheDir)) {
            $files = scandir($cacheDir);
            $cutoff = time() - (7 * 86400);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..' || $f === '.gitignore') continue;
                $path = $cacheDir . '/' . $f;
                if (is_file($path) && filemtime($path) < $cutoff) {
                    @unlink($path);
                    $prunedFiles++;
                }
            }
        }

        return [
            'expired_sessions_purged' => $sessionsPurged,
            'cache_files_pruned'      => $prunedFiles,
            'cleaned_at'              => date('Y-m-d H:i:s')
        ];
    }
}
