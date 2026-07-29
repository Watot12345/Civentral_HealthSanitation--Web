<?php
// app/repositories/FeatureRepository.php

namespace App\Repositories;

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../cache/CacheManager.php';

use Database;
use App\Cache\CacheManager;
use Throwable;

class FeatureRepository
{
    private Database $db;
    private CacheManager $cache;
    private static ?array $flagsMap = null;

    public function __construct()
    {
        $this->db = \Database::getInstance();
        $this->cache = new CacheManager();
    }

    /**
     * Get all feature flags map
     */
    private function getMap(): array
    {
        if (self::$flagsMap !== null) {
            return self::$flagsMap;
        }

        $cached = $this->cache->get('feature_flags_map');
        if (is_array($cached)) {
            self::$flagsMap = $cached;
            return self::$flagsMap;
        }

        $all = $this->all();
        $map = [];
        foreach ($all as $item) {
            $map[$item['key']] = (bool)$item['enabled'];
        }

        self::$flagsMap = $map;
        $this->cache->set('feature_flags_map', $map, 3600);

        return self::$flagsMap;
    }

    /**
     * Get all feature flags
     */
    public function all(): array
    {
        try {
            return $this->db->select('feature_flags', [], ['order' => 'key.asc']);
        } catch (Throwable $e) {
            error_log("FeatureRepository::all error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get single feature flag state from memory/cache map
     */
    public function isEnabled(string $key, bool $default = false): bool
    {
        $map = $this->getMap();
        if (array_key_exists($key, $map)) {
            return (bool)$map[$key];
        }
        return $default;
    }

    /**
     * Toggle or set feature flag state
     */
    public function setFlag(string $key, bool $enabled, ?string $name = null, ?string $description = null): bool
    {
        try {
            $existing = $this->db->select('feature_flags', ['key' => $key], ['limit' => 1]);
            $data = [
                'key' => $key,
                'enabled' => $enabled,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($name !== null) $data['flag_name'] = $name;
            if ($description !== null) $data['description'] = $description;

            if (!empty($existing[0])) {
                $this->db->update('feature_flags', $data, ['key' => $key], true);
            } else {
                $data['flag_name'] = $name ?? ucwords(str_replace('.', ' ', $key));
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('feature_flags', $data, true);
            }

            self::$flagsMap = null;
            $this->cache->delete('feature_flags_map');

            return true;
        } catch (Throwable $e) {
            error_log("FeatureRepository::setFlag error: " . $e->getMessage());
            return false;
        }
    }
}
