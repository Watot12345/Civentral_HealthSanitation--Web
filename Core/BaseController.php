<?php
// Core/BaseController.php

require_once __DIR__ . '/Response.php';

abstract class BaseController
{
    protected function input(): array
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return $data ?? $_POST;
    }

    protected function handle(callable $callback): void
{
    try {
        $result = $callback();
        
        if (isset($result['success']) && !$result['success']) {
            $code = $result['code'] ?? 400;
            Response::error($result['message'] ?? 'Error', $code);
        }
        
        $success = $result['success'] ?? true;
        $message = $result['message'] ?? '';
        $data = $result['data'] ?? null;
        $code = $result['code'] ?? 200;

        // Any additional fields returned by the controller (e.g. page,
        // total, total_pages, limit for paginated endpoints) get passed
        // through instead of being silently dropped.
        $extra = array_diff_key($result, array_flip(['success', 'message', 'data', 'code']));
        
        Response::json($success, $message, $data, $code, $extra);
    } catch (Throwable $e) {
        error_log("Controller Error: " . $e->getMessage());
        Response::error('Internal server error: ' . $e->getMessage(), 500);
    }
}
}
