<?php
// app/Controllers/ActivityLogController.php

require_once __DIR__ . '/../../Core/BaseController.php';
require_once __DIR__ . '/../Models/ActivityLog.php';

class ActivityLogController extends BaseController
{
    private ActivityLog $logModel;

    public function __construct()
    {
        $this->logModel = new ActivityLog();
    }

    /**
     * GET /activity-logs — list recent logs
     */
    public function index(): void
    {
        $this->handle(function () {
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
            $logs = $this->logModel->all([
                'order' => 'created_at.desc',
                'limit' => $limit,
            ]);

            return [
                'success' => true,
                'data'    => $logs,
                'total'   => $this->logModel->count(),
            ];
        });
    }

    /**
     * DELETE /activity-logs — clear all logs
     */
    public function clear(): void
    {
        $this->handle(function () {
            $this->logModel->clearAll();

            return [
                'success' => true,
                'message' => 'Activity logs cleared',
            ];
        });
    }
}
