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

    protected function validateCsrf(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent() && PHP_SAPI !== 'cli') {
            @session_start();
        }

        if (PHP_SAPI === 'cli' && empty($_SESSION['csrf_token'])) {
            return;
        }

        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        $inputToken = null;
        $input = $this->input();
        if (is_array($input) && isset($input['csrf_token'])) {
            $inputToken = $input['csrf_token'];
        }
        $token = $headerToken ?: $inputToken;
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (empty($sessionToken) || empty($token) || !hash_equals($sessionToken, (string)$token)) {
            Response::error('Invalid or missing CSRF token', 403);
        }
    }

    protected function ensureAuthenticated(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent() && PHP_SAPI !== 'cli') {
            @session_start();
        }
        if (empty($_SESSION['user_id']) && empty($_SESSION['employee_id'])) {
            Response::error('Authentication required', 401);
        }
    }

    protected function requireCapability(string $slug, string $context = ''): void
    {
        $this->ensureAuthenticated();
        if (class_exists('\\App\\Middleware\\AuthorizationMiddleware')) {
            \App\Middleware\AuthorizationMiddleware::authorize($slug, $context);
        } elseif (file_exists(__DIR__ . '/../app/Middleware/AuthorizationMiddleware.php')) {
            require_once __DIR__ . '/../app/Middleware/AuthorizationMiddleware.php';
            \App\Middleware\AuthorizationMiddleware::authorize($slug, $context);
        }
    }

    protected function requireDepartment(string $department, string $context = ''): void
    {
        $this->ensureAuthenticated();
        if (class_exists('\\App\\Middleware\\AuthorizationMiddleware')) {
            \App\Middleware\AuthorizationMiddleware::authorizeDepartment($department, $context);
        } elseif (file_exists(__DIR__ . '/../app/Middleware/AuthorizationMiddleware.php')) {
            require_once __DIR__ . '/../app/Middleware/AuthorizationMiddleware.php';
            \App\Middleware\AuthorizationMiddleware::authorizeDepartment($department, $context);
        }
    }

    protected function crudSuccess(string $action, mixed $record = null, string $message = '', int $httpCode = 200, array $extra = []): never
    {
        Response::crudSuccess($action, $record, $message, $httpCode, $extra);
    }

    protected function validationError(array $errors, string $message = 'Validation failed.'): never
    {
        Response::validationError($errors, $message);
    }

    /**
     * Prevents rapid duplicate form submissions (idempotency guard / anti-spam).
     * Blocks duplicate mutating actions (POST, PUT, PATCH, DELETE) within a 1.5-second debounce window.
     */
    protected function preventDoubleSubmission(float $windowSeconds = 1.5): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent() && PHP_SAPI !== 'cli') {
            @session_start();
        }

        $userId = $_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? (function_exists('session_id') ? session_id() : 'cli');
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $rawInput = file_get_contents('php://input');
        $hash = md5($userId . ':' . $uri . ':' . $rawInput . ':' . serialize($_POST));

        $lockKey = 'submit_lock_' . $hash;
        $now = microtime(true);

        if (isset($_SESSION[$lockKey]) && ($now - (float)$_SESSION[$lockKey]) < $windowSeconds) {
            Response::error('Duplicate submission detected. Please do not double-click.', 429);
        }

        $_SESSION[$lockKey] = $now;

        // Cleanup older locks to keep session lean
        foreach ($_SESSION as $k => $v) {
            if (str_starts_with($k, 'submit_lock_') && is_float($v) && ($now - $v) > 10) {
                unset($_SESSION[$k]);
            }
        }
    }

    protected function handle(callable $callback): void
    {
        try {
            $this->preventDoubleSubmission();
            $result = $callback();
            
            $success = $result['success'] ?? true;
            $message = $result['message'] ?? '';
            $data = $result['data'] ?? null;
            
            if (!$success) {
                $code = $result['code'] ?? 400;
            } else {
                $code = $result['code'] ?? 200;
            }

            // Any additional fields returned by the controller (e.g. page,
            // total, total_pages, limit for paginated endpoints, or errors for validation) 
            // get passed through instead of being silently dropped.
            $extra = array_diff_key($result, array_flip(['success', 'message', 'data', 'code']));
            
            Response::json($success, $message, $data, $code, $extra);
        } catch (Throwable $e) {
            error_log("Controller Error: " . $e->getMessage());
            Response::error('Internal server error: ' . $e->getMessage(), 500);
        }
    }
}
