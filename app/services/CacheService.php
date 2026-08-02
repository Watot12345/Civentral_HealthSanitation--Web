<?php
// app/services/CacheService.php

require_once __DIR__ . '/../../Core/Env.php';

class CacheService
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?: __DIR__ . '/../../storage/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Retrieve cached value if valid, or return default
     */
    public function get(string $key, $default = null)
    {
        Env::load();
        $enabled = Env::get('ENABLE_CACHE');
        if ($enabled === 'false' || $enabled === '0' || $enabled === false) {
            return $default; // Bypass cache for developers when disabled in .env
        }

        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $raw = @file_get_contents($file);
        if (!$raw) {
            return $default;
        }

        $payload = json_decode($raw, true);
        if (!$payload || !isset($payload['expires_at'])) {
            return $default;
        }

        if (time() > $payload['expires_at']) {
            @unlink($file); // Expired cache entry
            return $default;
        }

        return $payload['data'] ?? $default;
    }

    /**
     * Store item in cache with TTL in seconds (default: 300s = 5 mins)
     */
    public function set(string $key, $data, int $ttlSeconds = 300): bool
    {
        $file = $this->getFilePath($key);
        $payload = [
            'key'        => $key,
            'created_at' => time(),
            'expires_at' => time() + $ttlSeconds,
            'data'       => $data
        ];

        return (bool)@file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * Remember pattern: Return cached data or execute callback to generate and cache
     */
    public function remember(string $key, int $ttlSeconds, callable $callback)
    {
        $cached = $this->get($key);
        if ($cached !== null) {
            return [
                'data' => $cached,
                'hit'  => true
            ];
        }

        $fresh = $callback();
        $this->set($key, $fresh, $ttlSeconds);

        return [
            'data' => $fresh,
            'hit'  => false
        ];
    }

    /**
     * Clear all cached files
     */
    public function flush(): void
    {
        $files = glob($this->cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $f) {
                @unlink($f);
            }
        }
    }

    private function getFilePath(string $key): string
    {
        $safeName = md5($key) . '.cache';
        return $this->cacheDir . '/' . $safeName;
    }
}
