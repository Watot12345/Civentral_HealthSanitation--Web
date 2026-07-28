<?php

declare(strict_types=1);

/**
 * api/index.php
 *
 * Single front controller for the REST API. Point your web server
 * (or a .htaccess / nginx rewrite) at this file for every request under
 * /api/*, e.g.:
 *
 *   RewriteEngine On
 *   RewriteCond %{REQUEST_FILENAME} !-f
 *   RewriteRule ^ index.php [QSA,L]
 *
 * Routes below map 1:1 to the REST surface requested:
 *   GET    /employees
 *   GET    /employees/{id}
 *   POST   /employees
 *   PUT    /employees/{id}
 *   DELETE /employees/{id}
 *   GET    /employees/search
 *   GET    /employees/statistics
 *   PATCH  /employees/{id}/status
 *   POST   /employees/{id}/reset-password
 *   POST   /employees/bulk-delete
 *   POST   /employees/bulk-update
 *   GET    /roles
 *   GET    /roles/{id}
 *   PUT    /roles/{id}
 *   GET    /permissions
 *   GET    /activity-logs
 *   DELETE /activity-logs
 */

require_once __DIR__ . '/../core/bootstrap.php';

use App\Controllers\ActivityLogController;
use App\Controllers\EmployeeController;
use App\Controllers\RoleController;
use Core\Request;
use Core\Router;

$request = new Request();
$router = new Router();

// Static/collection routes must be registered before the `{id}` wildcard
// route so e.g. /employees/search is not swallowed by /employees/{id}.
$router->get('/employees/search', [EmployeeController::class, 'search']);
$router->get('/employees/statistics', [EmployeeController::class, 'statistics']);
$router->post('/employees/bulk-delete', [EmployeeController::class, 'bulkDelete']);
$router->post('/employees/bulk-update', [EmployeeController::class, 'bulkUpdate']);
$router->post('/employees/{id}/reset-password', [EmployeeController::class, 'resetPassword']);
$router->patch('/employees/{id}/status', [EmployeeController::class, 'toggleStatus']);

$router->get('/employees', [EmployeeController::class, 'index']);
$router->post('/employees', [EmployeeController::class, 'store']);
$router->get('/employees/{id}', [EmployeeController::class, 'show']);
$router->put('/employees/{id}', [EmployeeController::class, 'update']);
$router->delete('/employees/{id}', [EmployeeController::class, 'destroy']);

$router->get('/permissions', [RoleController::class, 'permissions']);
$router->get('/roles', [RoleController::class, 'index']);
$router->get('/roles/{id}', [RoleController::class, 'show']);
$router->put('/roles/{id}', [RoleController::class, 'update']);

$router->get('/activity-logs', [ActivityLogController::class, 'index']);
$router->delete('/activity-logs', [ActivityLogController::class, 'clear']);

// Strip a leading /api prefix if the web server passes the full path through.
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = preg_replace('#^/api#', '', parse_url($uri, PHP_URL_PATH) ?: '/') ?: '/';

$router->dispatch($request, $uri);