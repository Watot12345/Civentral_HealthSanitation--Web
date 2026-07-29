<?php
// app/repositories/AuditRepository.php

namespace App\Repositories;

require_once __DIR__ . '/../Models/ActivityLog.php';
use Throwable;

class AuditRepository
{
    private \ActivityLog $activityLog;

    public function __construct()
    {
        $this->activityLog = new \ActivityLog();
    }

    /**
     * Record detailed audit log for setting changes
     */
    public function logChange(
        string $action,
        string $settingKey,
        mixed $oldValue,
        mixed $newValue,
        array $userContext = []
    ): bool {
        try {
            $formattedOld = is_array($oldValue) ? json_encode($oldValue) : (string)$oldValue;
            $formattedNew = is_array($newValue) ? json_encode($newValue) : (string)$newValue;

            // Mask sensitive fields in log output
            if (preg_match('/(password|secret|key|token)/i', $settingKey)) {
                $formattedOld = '••••••••';
                $formattedNew = '••••••••';
            }

            $details = "Setting [{$settingKey}] changed. Old: '{$formattedOld}' -> New: '{$formattedNew}'";

            $this->activityLog->log($action, [
                'module' => 'Settings Engine',
                'details' => $details,
                'status' => 'Success',
                'user_id' => $userContext['user_id'] ?? null,
                'employee_id' => $userContext['employee_id'] ?? null,
                'role' => $userContext['role'] ?? null,
                'ip_address' => $userContext['ip_address'] ?? null,
                'device' => $userContext['device'] ?? null,
            ]);

            return true;
        } catch (Throwable $e) {
            error_log("AuditRepository::logChange error: " . $e->getMessage());
            return false;
        }
    }
}
