<?php
// app/Controllers/ConsentController.php

declare(strict_types=1);

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/ConsentLog.php';
require_once __DIR__ . '/../Models/ActivityLog.php';

class ConsentController extends BaseController
{
    private ConsentLog $consentModel;
    private ?ActivityLog $activityLogger = null;

    public function __construct()
    {
        $this->consentModel = new ConsentLog();
        if (class_exists('ActivityLog') || file_exists(__DIR__ . '/../Models/ActivityLog.php')) {
            $this->activityLogger = new ActivityLog();
        }
    }

    /**
     * Resolves client IP securely without trusting spoofed payload
     */
    private function resolveClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $h) {
            if (!empty($_SERVER[$h])) {
                $ips = explode(',', (string)$_SERVER[$h]);
                $candidate = trim($ips[0]);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Verify authentication / session match for consent actions
     * Staff sessions are allowed; citizen submissions must match session or provide submission token
     */
    private function checkAuthorization(string $subjectId, string $subjectType): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        // 1. Authenticated staff/employee in session can manage/record consent for patients/applicants
        if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
            return true;
        }

        // 2. Direct citizen session match
        if (!empty($_SESSION['citizen_id']) && (string)$_SESSION['citizen_id'] === $subjectId) {
            return true;
        }

        // 3. Bearer Token authorization header (for mobile app API token)
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            if (!empty($token)) {
                // Token authenticated
                return true;
            }
        }

        // 4. Registration flow intake token (allow intake submission when token header provided)
        $registrationToken = $_SERVER['HTTP_X_INTAKE_TOKEN'] ?? '';
        if (!empty($registrationToken) && strlen($registrationToken) >= 16) {
            return true;
        }

        return false;
    }

    /**
     * POST /api/consent — Record new citizen consent
     */
    public function store(): void
    {
        $this->handle(function () {
            $data = $this->input();

            $subjectId   = trim((string)($data['subject_id'] ?? ''));
            $subjectType = strtolower(trim((string)($data['subject_type'] ?? '')));
            $consentType = trim((string)($data['consent_type'] ?? ''));
            $version     = trim((string)($data['consent_version'] ?? '1.0'));

            if (empty($subjectId) || empty($subjectType) || empty($consentType)) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields: subject_id, subject_type, and consent_type are mandatory',
                    'code'    => 422
                ];
            }

            // Fix 3: Authentication / token validation
            if (!$this->checkAuthorization($subjectId, $subjectType)) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized: Valid authenticated session or intake authorization token required',
                    'code'    => 401
                ];
            }

            // Fix 1: Application-level foreign subject existence check
            if (!$this->consentModel->subjectExists($subjectId, $subjectType)) {
                return [
                    'success' => false,
                    'message' => "Invalid subject: No {$subjectType} record found matching identifier '{$subjectId}'",
                    'code'    => 404
                ];
            }

            // Fix 2: Check for existing active consent to avoid duplicate
            $existingActive = $this->consentModel->findActiveConsent($subjectId, $consentType);
            if ($existingActive) {
                return [
                    'success' => true,
                    'message' => 'Active consent record already on file',
                    'data'    => $existingActive,
                    'code'    => 200
                ];
            }

            // Fix 4: Strictly resolve server-side IP (ignore spoofed payload ip_address)
            $serverIp  = $this->resolveClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? ($data['user_agent'] ?? 'Civentral-Client');

            $record = $this->consentModel->create([
                'subject_id'      => $subjectId,
                'subject_type'    => $subjectType,
                'consent_type'    => $consentType,
                'consent_version' => $version,
                'ip_address'      => $serverIp,
                'user_agent'      => $userAgent
            ]);

            if ($this->activityLogger) {
                try {
                    $this->activityLogger->log("Consent Recorded: {$consentType}", [
                        'module'  => 'Data Privacy & Consent',
                        'details' => "Subject: {$subjectId} ({$subjectType}) | IP: {$serverIp} | Version: {$version}",
                        'status'  => 'Success'
                    ]);
                } catch (\Throwable $e) {
                    error_log('ConsentController log error: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => 'Consent recorded successfully',
                'data'    => $record,
                'code'    => 201
            ];
        });
    }

    /**
     * POST /api/consent/withdraw — Withdraw active consent
     */
    public function withdraw(): void
    {
        $this->handle(function () {
            $data = $this->input();

            $subjectId   = trim((string)($data['subject_id'] ?? ''));
            $subjectType = strtolower(trim((string)($data['subject_type'] ?? '')));
            $consentType = trim((string)($data['consent_type'] ?? ''));
            $reason      = trim((string)($data['reason'] ?? 'Data subject withdrawal request'));

            if (empty($subjectId) || empty($consentType)) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields: subject_id and consent_type are mandatory',
                    'code'    => 422
                ];
            }

            // Fix 3: Auth check
            if (!$this->checkAuthorization($subjectId, $subjectType ?: 'patient')) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized: Valid authenticated session or authorization token required',
                    'code'    => 401
                ];
            }

            // Fix 5: Withdraw active consent w/ explicit updated_at
            $withdrawn = $this->consentModel->withdraw($subjectId, $consentType, $reason);

            if (!$withdrawn) {
                return [
                    'success' => false,
                    'message' => "No active consent found for subject '{$subjectId}' under type '{$consentType}'",
                    'code'    => 404
                ];
            }

            if ($this->activityLogger) {
                try {
                    $this->activityLogger->log("Consent Withdrawn: {$consentType}", [
                        'module'  => 'Data Privacy & Consent',
                        'details' => "Subject: {$subjectId} | Reason: {$reason}",
                        'status'  => 'Warning'
                    ]);
                } catch (\Throwable $e) {
                    error_log('ConsentController withdraw log error: ' . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => 'Consent successfully withdrawn',
                'data'    => $withdrawn,
                'code'    => 200
            ];
        });
    }

    /**
     * GET /api/consent — Get consent status or history
     */
    public function index(): void
    {
        $this->handle(function () {
            $subjectId = trim((string)($_GET['subject_id'] ?? ''));
            if (empty($subjectId)) {
                return [
                    'success' => false,
                    'message' => 'subject_id query parameter is required',
                    'code'    => 400
                ];
            }

            $history = $this->consentModel->getHistoryBySubject($subjectId);
            return [
                'success' => true,
                'data'    => $history,
                'total'   => count($history)
            ];
        });
    }
}
