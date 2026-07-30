<?php
// app/Middleware/AuthorizationMiddleware.php

namespace App\Middleware;

use App\Services\PermissionService;

class AuthorizationMiddleware
{
    /**
     * Policy check: returns true if permitted.
     */
    public static function can(string $slug): bool
    {
        return PermissionService::getInstance()->hasPermission($slug);
    }

    /**
     * Authorize execution for page or API route. Throws or redirects if unpermitted.
     */
    public static function authorize(string $slug, string $context = ''): void
    {
        $permService = PermissionService::getInstance();

        if (!$permService->hasPermission($slug)) {
            $permService->logUnauthorizedAttempt($slug, $context);

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // API json response if header or URI indicates API endpoint
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApi = str_contains($uri, '/api/') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            if ($isApi) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Forbidden: Missing required permission [{$slug}]",
                    'code'    => 403
                ]);
                exit;
            }

            $_SESSION['flash_error'] = 'Access Denied: You do not have permission to perform that action.';
            header('Location: ' . site_url('pages/dashboard.php'));
            exit;
        }
    }

    /**
     * Authorize department access for module execution. Redirects or returns 403 if restricted.
     */
    public static function authorizeDepartment(string $moduleDepartment, string $context = ''): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (!canAccessDepartment($moduleDepartment)) {
            $userDept = getCurrentUserDepartment();
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $isApi = str_contains($uri, '/api/') || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

            if ($isApi) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Forbidden: Department access restricted. Your assigned department is '{$userDept}'.",
                    'code'    => 403
                ]);
                exit;
            }

            $_SESSION['flash_error'] = "Access Denied: You do not have access to the '{$moduleDepartment}' department modules. Your assigned department is '{$userDept}'.";
            header('Location: ' . site_url('pages/dashboard.php'));
            exit;
        }
    }
}

