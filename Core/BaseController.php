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
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
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
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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

    protected function handle(callable $callback): void
    {
        try {
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
