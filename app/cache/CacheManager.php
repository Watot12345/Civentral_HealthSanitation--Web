<?php
// app/cache/CacheManager.php

namespace App\Cache;

class CacheManager
{
    private static array $memoryCache = [];
    private string $cacheDir;
    private int $defaultTtl = 3600;

    public function __construct(?string $cacheDir = null)
    {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../../storage/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get item from Memory Cache first, then File Cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // 1. Check Memory Cache
        if (array_key_exists($key, self::$memoryCache)) {
            $item = self::$memoryCache[$key];
            if ($item['expires_at'] === 0 || $item['expires_at'] >= time()) {
                return $item['value'];
            }
            unset(self::$memoryCache[$key]);
        }

        // 2. Check File Cache
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            $content = @file_get_contents($filePath);
            if ($content !== false) {
                $data = @json_decode($content, true);
                if (is_array($data) && isset($data['expires_at'])) {
                    if ($data['expires_at'] === 0 || $data['expires_at'] >= time()) {
                        // Store in memory cache for current request lifecycle
                        self::$memoryCache[$key] = $data;
                        return $data['value'];
                    }
                    @unlink($filePath);
                }
            }
        }

        return $default;
    }

    /**
     * Set item in Memory and File Cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTtl;
        $expiresAt = $ttl > 0 ? time() + $ttl : 0;

        $payload = [
            'key' => $key,
            'value' => $value,
            'expires_at' => $expiresAt,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Store in Memory
        self::$memoryCache[$key] = $payload;

        // Store in File atomically
        $filePath = $this->getFilePath($key);
        $tmpPath = $filePath . '.' . uniqid('tmp_', true);
        $json = json_encode($payload, JSON_PRETTY_PRINT);

        if (@file_put_contents($tmpPath, $json, LOCK_EX) !== false) {
            return @rename($tmpPath, $filePath);
        }

        return false;
    }

    /**
     * Check if key exists and is valid
     */
    public function has(string $key): bool
    {
        return $this->get($key, '__NOT_FOUND__') !== '__NOT_FOUND__';
    }

    /**
     * Delete key from cache
     */
    public function delete(string $key): bool
    {
        unset(self::$memoryCache[$key]);
        $filePath = $this->getFilePath($key);
        if (file_exists($filePath)) {
            return @unlink($filePath);
        }
        return true;
    }

    /**
     * Clear all cached files
     */
    public function clear(): bool
    {
        self::$memoryCache = [];
        $files = glob($this->cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        return true;
    }

    private function getFilePath(string $key): string
    {
        $hashed = md5($key);
        return $this->cacheDir . '/' . $hashed . '.cache';
    }
}
