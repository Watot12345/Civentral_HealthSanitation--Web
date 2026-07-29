<?php
// app/helpers/Settings.php

require_once __DIR__ . '/../services/SettingsService.php';
use App\Services\SettingsService;

class Settings
{
    /**
     * Get setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return SettingsService::getInstance()->get($key, $default);
    }

    /**
     * Set setting value by key
     */
    public static function set(string $key, mixed $value, array $meta = [], ?array $userContext = []): bool
    {
        return SettingsService::getInstance()->set($key, $value, $meta, $userContext);
    }

    /**
     * Bulk update settings
     */
    public static function bulkUpdate(array $settings, ?array $userContext = []): bool
    {
        return SettingsService::getInstance()->bulkUpdate($settings, $userContext);
    }

    /**
     * Clear settings cache
     */
    public static function clearCache(): bool
    {
        return SettingsService::getInstance()->clearCache();
    }

    /**
     * Reload settings into cache
     */
    public static function reload(): bool
    {
        return SettingsService::getInstance()->reload();
    }

    /**
     * Reset settings to seed defaults
     */
    public static function reset(?string $category = null): bool
    {
        return SettingsService::getInstance()->reset($category);
    }

    /**
     * Export settings as JSON
     */
    public static function export(): string
    {
        return SettingsService::getInstance()->export();
    }

    /**
     * Import settings from JSON string
     */
    public static function import(string $jsonContent, ?array $userContext = []): bool
    {
        return SettingsService::getInstance()->import($jsonContent, $userContext);
    }
}
