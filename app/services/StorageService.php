<?php
// app/services/StorageService.php

namespace App\Services;

require_once __DIR__ . '/../../Core/Env.php';

use Env;
use RuntimeException;
use Throwable;

class StorageService
{
    private static ?StorageService $instance = null;

    private string $url;
    private string $anonKey;
    private string $serviceKey;

    private function __construct()
    {
        Env::load();

        $this->url        = Env::get('SUPABASE_URL') ?? '';
        $this->anonKey    = Env::get('SUPABASE_KEY') ?? '';
        $this->serviceKey = Env::get('SUPABASE_SERVICE_KEY') ?? '';

        if (!$this->url || !$this->anonKey) {
            throw new RuntimeException('Supabase credentials are not configured for StorageService.');
        }
    }

    public static function getInstance(): StorageService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Formats bytes into human-readable representation (B, KB, MB, GB, TB).
     */
    public static function formatBytes(float|int $bytes, int $precision = 1): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $floorBase = floor($base);
        $pow = pow(1024, $floorBase);
        $unitIndex = min((int)$floorBase, count($units) - 1);
        $val = $bytes / $pow;
        return round($val, $precision) . ' ' . $units[$unitIndex];
    }

    /**
     * Uploads a file to Supabase Storage bucket and returns its public CDN URL.
     */
    public function upload(string $bucket, string $remotePath, string $localFilePath, string $mimeType = 'image/jpeg'): ?string
    {
        try {
            $key = !empty($this->serviceKey) ? $this->serviceKey : $this->anonKey;
            $cleanPath = ltrim($remotePath, '/');
            $endpoint = "{$this->url}/storage/v1/object/{$bucket}/{$cleanPath}";

            $fileData = @file_get_contents($localFilePath);
            if ($fileData === false) {
                return null;
            }

            // Standardize MIME types matching Supabase bucket rules (e.g., Image/jpg, image/png, image/jpeg)
            $mimeTypesToTry = array_unique(array_filter([
                'Image/jpg',
                'image/png',
                $mimeType,
                'image/jpeg',
                'image/jpg',
                'application/octet-stream'
            ]));

            foreach ($mimeTypesToTry as $typeToTry) {
                $headers = [
                    'apikey: ' . $key,
                    'Authorization: Bearer ' . $key,
                    'Content-Type: ' . $typeToTry,
                    'x-upsert: true',
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $endpoint);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 300) {
                    return "{$this->url}/storage/v1/object/public/{$bucket}/{$cleanPath}";
                }

                $decodedResp = json_decode($response, true);
                if (!is_array($decodedResp) || ($decodedResp['error'] ?? '') !== 'invalid_mime_type') {
                    error_log("Supabase Storage Upload to bucket '{$bucket}' failed [{$httpCode}]: {$response}");
                    break;
                }
            }
        } catch (Throwable $e) {
            error_log("Supabase Storage Exception: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Lists all registered Supabase Storage buckets.
     */
    public function listBuckets(): array
    {
        try {
            $key = !empty($this->serviceKey) ? $this->serviceKey : $this->anonKey;
            $endpoint = "{$this->url}/storage/v1/bucket";

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'apikey: ' . $key,
                'Authorization: Bearer ' . $key
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true) ?: [];
            }
        } catch (Throwable $e) {
            error_log("Supabase listBuckets error: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Fetches real-time Supabase Object Storage metrics across all buckets with caching.
     */
    public function getMetrics(bool $forceRefresh = false): array
    {
        $cacheFile = __DIR__ . '/../../storage/cache/supabase_storage_metrics.json';
        if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
            $cached = @json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['buckets'])) {
                return $cached;
            }
        }

        try {
            $key = !empty($this->serviceKey) ? $this->serviceKey : $this->anonKey;
            $buckets = $this->listBuckets();

            $totalBytes = 0;
            $totalFiles = 0;
            $bucketBreakdown = [];

            if (!empty($buckets)) {
                $mh = curl_multi_init();
                $handles = [];

                foreach ($buckets as $b) {
                    $bName = $b['name'] ?? $b['id'];
                    $c = curl_init("{$this->url}/storage/v1/object/list/{$bName}");
                    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($c, CURLOPT_POST, true);
                    curl_setopt($c, CURLOPT_POSTFIELDS, json_encode([
                        'prefix' => '',
                        'limit' => 1000,
                        'offset' => 0,
                        'sortBy' => ['column' => 'name', 'order' => 'asc']
                    ]));
                    curl_setopt($c, CURLOPT_HTTPHEADER, [
                        'apikey: ' . $key,
                        'Authorization: Bearer ' . $key,
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($c, CURLOPT_TIMEOUT, 6);
                    curl_multi_add_handle($mh, $c);
                    $handles[$bName] = [
                        'handle' => $c,
                        'meta' => $b
                    ];
                }

                $running = null;
                do {
                    $status = curl_multi_exec($mh, $running);
                    if ($running > 0) {
                        curl_multi_select($mh, 0.05);
                    }
                } while ($running > 0 && $status === CURLM_OK);

                foreach ($handles as $bName => $item) {
                    $c = $item['handle'];
                    $bMeta = $item['meta'];
                    $content = curl_multi_getcontent($c);
                    $objs = json_decode($content, true) ?: [];
                    curl_multi_remove_handle($mh, $c);
                    curl_close($c);

                    $bBytes = 0;
                    $bFiles = 0;
                    $objectsList = [];

                    foreach ($objs as $obj) {
                        $objName = $obj['name'] ?? '';
                        if ($objName === '.emptyFolderPlaceholder') {
                            continue;
                        }
                        $size = (int)($obj['metadata']['size'] ?? 0);
                        $bBytes += $size;
                        $bFiles++;
                        $objectsList[] = [
                            'name' => $objName,
                            'size' => $size,
                            'size_formatted' => self::formatBytes($size),
                            'mime_type' => $obj['metadata']['mimetype'] ?? 'application/octet-stream',
                            'updated_at' => $obj['updated_at'] ?? $obj['created_at'] ?? null,
                            'public_url' => "{$this->url}/storage/v1/object/public/{$bName}/" . ltrim($objName, '/')
                        ];
                    }

                    $totalBytes += $bBytes;
                    $totalFiles += $bFiles;

                    $bucketBreakdown[] = [
                        'id' => $bMeta['id'] ?? $bName,
                        'name' => $bName,
                        'is_public' => (bool)($bMeta['public'] ?? true),
                        'files_count' => $bFiles,
                        'size_bytes' => $bBytes,
                        'size_formatted' => self::formatBytes($bBytes),
                        'created_at' => $bMeta['created_at'] ?? null,
                        'objects' => $objectsList
                    ];
                }
                curl_multi_close($mh);
            }

            // Supabase Free Tier limit: 1.0 GB (1,073,741,824 bytes)
            $quotaBytes = 1073741824; 
            $usagePercent = $quotaBytes > 0 ? round(($totalBytes / $quotaBytes) * 100, 2) : 0;
            if ($totalBytes > 0 && $usagePercent < 0.01) {
                $usagePercent = 0.01;
            }

            $result = [
                'success' => true,
                'project_url' => $this->url,
                'total_files' => $totalFiles,
                'total_bytes' => $totalBytes,
                'total_formatted' => self::formatBytes($totalBytes),
                'quota_bytes' => $quotaBytes,
                'quota_formatted' => self::formatBytes($quotaBytes),
                'usage_percent' => $usagePercent,
                'buckets_count' => count($bucketBreakdown),
                'buckets' => $bucketBreakdown,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!is_dir(dirname($cacheFile))) {
                @mkdir(dirname($cacheFile), 0755, true);
            }
            @file_put_contents($cacheFile, json_encode($result));

            return $result;
        } catch (Throwable $e) {
            error_log("Supabase StorageService getMetrics error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'project_url' => $this->url,
                'total_files' => 0,
                'total_bytes' => 0,
                'total_formatted' => '0 B',
                'quota_bytes' => 1073741824,
                'quota_formatted' => '1.0 GB',
                'usage_percent' => 0,
                'buckets_count' => 0,
                'buckets' => [],
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
    }
}
