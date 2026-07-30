<?php
// app/services/NavigationService.php

namespace App\Services;

use App\Constants\Permissions;

class NavigationService
{
    private static ?NavigationService $instance = null;
    private PermissionService $permissionService;
    private DepartmentResolver $departmentResolver;
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->permissionService = PermissionService::getInstance();
        $this->departmentResolver = DepartmentResolver::getInstance();
        $this->config = $config ?? (file_exists(__DIR__ . '/../../config/navigation.php') ? require __DIR__ . '/../../config/navigation.php' : []);
    }

    public static function getInstance(): NavigationService
    {
        if (self::$instance === null) {
            self::$instance = new NavigationService();
        }
        return self::$instance;
    }

    /**
     * Resolve dynamic dashboard header title.
     */
    public function getDashboardTitle(): string
    {
        if ($this->permissionService->hasPermission(Permissions::ROLES_MANAGE)) {
            return 'System Overview';
        }

        $dept = $this->departmentResolver->resolveDepartmentName();
        if (!empty($dept) && strcasecmp($dept, 'Administration') !== 0 && strcasecmp($dept, 'General Department') !== 0) {
            return "{$dept} Dashboard";
        }

        return 'Dashboard Overview';
    }

    /**
     * Determine dynamic post-login landing page URL based on user permissions.
     */
    public function getLandingPage(): string
    {
        if ($this->permissionService->hasPermission(Permissions::ROLES_MANAGE)) {
            return 'pages/dashboard.php';
        }

        if ($this->permissionService->hasPermission(Permissions::PATIENTS_VIEW)) {
            return 'pages/dashboard.php';
        }

        if ($this->permissionService->hasPermission(Permissions::PERMITS_VIEW)) {
            return 'pages/dashboard.php';
        }

        if ($this->permissionService->hasPermission(Permissions::IMMUNIZATION_VIEW)) {
            return 'pages/dashboard.php';
        }

        return 'pages/dashboard.php';
    }

    /**
     * Build filtered, permission-driven navigation sections for sidebar.
     */
    public function getFilteredNavigation(): array
    {
        $sections = $this->config['sections'] ?? [];
        $filtered = [];

        foreach ($sections as $section) {
            // Check section level permissions if defined
            if (!empty($section['permissions']) && !$this->permissionService->hasAnyPermission($section['permissions'])) {
                continue;
            }

            $filteredSection = [
                'key'   => $section['key'],
                'label' => $section['label'],
            ];

            // Direct Items
            if (!empty($section['items'])) {
                $filteredItems = [];
                foreach ($section['items'] as $item) {
                    if (!empty($item['permission']) && !$this->permissionService->hasPermission($item['permission'])) {
                        continue;
                    }
                    if (!empty($item['permissions']) && !$this->permissionService->hasAnyPermission($item['permissions'])) {
                        continue;
                    }

                    // Dynamically set label for dashboard item
                    if (($item['key'] ?? '') === 'dashboard') {
                        $item['label'] = $this->getDashboardTitle();
                    }

                    $filteredItems[] = $item;
                }

                if (!empty($filteredItems)) {
                    $filteredSection['items'] = $filteredItems;
                }
            }

            // Grouped Operational Modules
            if (!empty($section['modules'])) {
                $filteredModules = [];
                foreach ($section['modules'] as $module) {
                    if (!empty($module['permissions']) && !$this->permissionService->hasAnyPermission($module['permissions'])) {
                        continue;
                    }

                    // Filter child links
                    $children = [];
                    if (!empty($module['children'])) {
                        foreach ($module['children'] as $child) {
                            if (!empty($child['permission']) && !$this->permissionService->hasPermission($child['permission'])) {
                                continue;
                            }
                            if (!empty($child['permissions']) && !$this->permissionService->hasAnyPermission($child['permissions'])) {
                                continue;
                            }
                            $children[] = $child;
                        }
                    }

                    if (!empty($children)) {
                        $module['children'] = $children;
                        $filteredModules[] = $module;
                    }
                }

                if (!empty($filteredModules)) {
                    $filteredSection['modules'] = $filteredModules;
                }
            }

            if (isset($filteredSection['items']) || isset($filteredSection['modules'])) {
                $filtered[] = $filteredSection;
            }
        }

        return $filtered;
    }

    /**
     * Generate dynamic breadcrumb array based on path.
     */
    public function getBreadcrumbs(string $currentPath): array
    {
        $breadcrumbs = [
            ['label' => 'Home', 'route' => 'pages/dashboard.php']
        ];

        $filename = basename($currentPath);
        $cleanName = ucwords(str_replace(['_', '.php'], [' ', ''], $filename));

        if ($filename !== 'dashboard.php') {
            $breadcrumbs[] = ['label' => $cleanName, 'route' => ''];
        }

        return $breadcrumbs;
    }
}
