<?php
// app/repositories/SettingsRepository.php

namespace App\Repositories;

require_once __DIR__ . '/../../config/database.php';
use Database;
use Throwable;
use RuntimeException;

class SettingsRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Retrieve all settings
     */
    public function all(): array
    {
        try {
            return $this->db->select('system_settings', [], ['order' => 'key.asc']);
        } catch (Throwable $e) {
            error_log("SettingsRepository::all error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Find single setting by key
     */
    public function findByKey(string $key): ?array
    {
        try {
            $results = $this->db->select('system_settings', ['key' => $key], ['limit' => 1]);
            return !empty($results[0]) ? $results[0] : null;
        } catch (Throwable $e) {
            error_log("SettingsRepository::findByKey error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get settings by category name
     */
    public function getByCategory(string $categoryName): array
    {
        try {
            $categories = $this->db->select('setting_categories', ['name' => $categoryName], ['limit' => 1]);
            if (empty($categories[0])) {
                return [];
            }
            $categoryId = $categories[0]['id'];
            return $this->db->select('system_settings', ['category_id' => $categoryId], ['order' => 'key.asc']);
        } catch (Throwable $e) {
            error_log("SettingsRepository::getByCategory error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Insert or Update a single setting key
     */
    public function saveKey(string $key, mixed $value, array $meta = []): bool
    {
        try {
            $existing = $this->findByKey($key);

            $data = [
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : (string)$value,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if (isset($meta['data_type'])) $data['data_type'] = $meta['data_type'];
            if (isset($meta['description'])) $data['description'] = $meta['description'];
            if (isset($meta['is_encrypted'])) $data['is_encrypted'] = (bool)$meta['is_encrypted'];
            if (isset($meta['is_editable'])) $data['is_editable'] = (bool)$meta['is_editable'];
            if (isset($meta['validation_rules'])) $data['validation_rules'] = $meta['validation_rules'];

            if ($existing) {
                if (isset($existing['is_editable']) && $existing['is_editable'] === false) {
                    throw new RuntimeException("Setting [{$key}] is read-only server environment setting.");
                }
                $this->db->update('system_settings', $data, ['key' => $key], true);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                if (isset($meta['category_id'])) {
                    $data['category_id'] = $meta['category_id'];
                } else {
                    $parts = explode('.', $key);
                    $catName = $parts[0] ?? 'general';
                    $cats = $this->db->select('setting_categories', ['name' => $catName], ['limit' => 1]);
                    if (!empty($cats[0]['id'])) {
                        $data['category_id'] = $cats[0]['id'];
                    }
                }
                $this->db->insert('system_settings', $data, true);
            }

            return true;
        } catch (Throwable $e) {
            error_log("SettingsRepository::saveKey error: " . $e->getMessage());
            throw new RuntimeException("Failed to save setting [{$key}]: " . $e->getMessage());
        }
    }

    /**
     * Delete setting key
     */
    public function deleteKey(string $key): bool
    {
        try {
            $this->db->delete('system_settings', ['key' => $key], true);
            return true;
        } catch (Throwable $e) {
            error_log("SettingsRepository::deleteKey error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save snapshot into settings_versions
     */
    public function createVersionSnapshot(array $snapshot, string $createdBy, ?string $summary = null): array
    {
        try {
            $versions = $this->db->select('settings_versions', [], ['order' => 'version_number.desc', 'limit' => 1]);
            $nextVersion = !empty($versions[0]['version_number']) ? ((int)$versions[0]['version_number'] + 1) : 1;

            $data = [
                'version_number' => $nextVersion,
                'snapshot_json' => json_encode($snapshot),
                'changes_summary' => $summary ?? 'Settings updated',
                'created_by' => $createdBy,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            return $this->db->insert('settings_versions', $data, true);
        } catch (Throwable $e) {
            error_log("SettingsRepository::createVersionSnapshot error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * List all version snapshots
     */
    public function getVersions(int $limit = 20): array
    {
        try {
            return $this->db->select('settings_versions', [], ['order' => 'version_number.desc', 'limit' => $limit]);
        } catch (Throwable $e) {
            error_log("SettingsRepository::getVersions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get specific version snapshot by ID or version_number
     */
    public function getVersion(int $versionNumber): ?array
    {
        try {
            $results = $this->db->select('settings_versions', ['version_number' => $versionNumber], ['limit' => 1]);
            return !empty($results[0]) ? $results[0] : null;
        } catch (Throwable $e) {
            error_log("SettingsRepository::getVersion error: " . $e->getMessage());
            return null;
        }
    }
}
