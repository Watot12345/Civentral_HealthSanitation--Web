<?php
// config/database.php

require_once __DIR__ . '/../Core/Env.php';

class Database
{
    private static ?Database $instance = null;
    private static mixed $curlHandle = null;

    private string $url;
    private string $anonKey;
    private string $serviceKey;

    private function __construct()
    {
        Env::load();

        $this->url        = Env::get('SUPABASE_URL');
        $this->anonKey     = Env::get('SUPABASE_KEY');
        $this->serviceKey = Env::get('SUPABASE_SERVICE_KEY');

        if (!$this->url || !$this->anonKey) {
            throw new RuntimeException('Supabase credentials are not configured.');
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Executes a request against PostgREST.
     */
    public function query(
        
        string $table,
        string $method = 'GET',
        ?array $data = null,
        array $filters = [],
        array $options = [],
        bool $useServiceKey = false
    ): array {
        $endpoint = "{$this->url}/rest/v1/{$table}";

        $queryParams = [];
        
        // Handle filters with different operators
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                if (array_keys($value) !== range(0, count($value) - 1)) {
                    foreach ($value as $operator => $operatorValue) {
                        $queryParams[] = $key . '=' . $operator . '.' . rawurlencode((string) $operatorValue);
                    }
                } elseif (!empty($value)) {
                    $encodedValues = array_map(
                        static fn($item): string => rawurlencode((string) $item),
                        $value
                    );
                    $queryParams[] = $key . '=in.(' . implode(',', $encodedValues) . ')';
                }
            } elseif (is_string($value) && preg_match('/^(eq|gt|gte|lt|lte|neq|like|ilike|in|is)\..*/', $value)) {
                $queryParams[] = $key . '=' . rawurlencode($value);
            } else {
                $queryParams[] = $key . '=eq.' . rawurlencode((string) $value);
            }
        }

        if (!empty($options['select'])) {
            $queryParams[] = 'select=' . rawurlencode($options['select']);
        }
        if (!empty($options['order'])) {
            $queryParams[] = 'order=' . rawurlencode($options['order']);
        }
        
        // ONLY add limit and offset if they are explicitly set
        if (isset($options['limit']) && $options['limit'] !== null) {
            $queryParams[] = 'limit=' . (int) $options['limit'];
        }
        if (isset($options['offset']) && $options['offset'] !== null) {
            $queryParams[] = 'offset=' . (int) $options['offset'];
        }
        
        if (!empty($options['or'])) {
            $queryParams[] = 'or=' . rawurlencode($options['or']);
        }

        if (!empty($queryParams)) {
            $endpoint .= '?' . implode('&', $queryParams);
        }

        $key = $useServiceKey ? $this->serviceKey : $this->anonKey;

        $headers = [
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ];

        if ($method === 'POST' || $method === 'PATCH') {
            $headers[] = 'Prefer: return=representation';
        }

        if (self::$curlHandle === null || (!is_resource(self::$curlHandle) && !(self::$curlHandle instanceof \CurlHandle))) {
            self::$curlHandle = curl_init();
            curl_setopt(self::$curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt(self::$curlHandle, CURLOPT_TIMEOUT, 15);
            curl_setopt(self::$curlHandle, CURLOPT_TCP_KEEPALIVE, 1);
            curl_setopt(self::$curlHandle, CURLOPT_TCP_KEEPIDLE, 120);
            curl_setopt(self::$curlHandle, CURLOPT_TCP_KEEPINTVL, 60);
            curl_setopt(self::$curlHandle, CURLOPT_FORBID_REUSE, false);
            curl_setopt(self::$curlHandle, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt(self::$curlHandle, CURLOPT_SSL_VERIFYHOST, 2);
        }

        $ch = self::$curlHandle;
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, '');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            // In case of broken connection, reset handle
            if (is_resource(self::$curlHandle) || self::$curlHandle instanceof \CurlHandle) {
                @curl_close(self::$curlHandle);
            }
            self::$curlHandle = null;
            throw new RuntimeException("Database connection error: {$curlError}");
        }

        if ($httpCode >= 400) {
            error_log("Supabase error [{$httpCode}] on {$table}: {$response}");
            $errorDetails = '';
            $decodedError = json_decode($response, true);
            if (is_array($decodedError) && !empty($decodedError['message'])) {
                $errorDetails = ': ' . $decodedError['message'];
                if (!empty($decodedError['details'])) {
                    $errorDetails .= ' (' . $decodedError['details'] . ')';
                }
            } else {
                $errorDetails = ': ' . $response;
            }
            throw new RuntimeException("Database request failed with status {$httpCode}{$errorDetails}");
        }

        $decoded = json_decode($response, true);
        return $decoded ?? [];
    }

    public function select(string $table, array $filters = [], array $options = []): array
    {
        return $this->query($table, 'GET', null, $filters, $options);
    }

    /**
     * Fetch multiple tables concurrently in a single parallel HTTP round-trip (ultra-fast)
     * Supports both indexed arrays of table names: ['patients', 'consultations']
     * and associative configs: ['patients' => ['select' => 'id,created_at', 'limit' => 1000]]
     */
    public function multiSelect(array $tables): array
    {
        if (empty($tables)) return [];
        $mh = curl_multi_init();
        $handles = [];
        $key = $this->anonKey;
        $headers = [
            'apikey: ' . $key,
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
        ];

        foreach ($tables as $keyOrTable => $config) {
            $tableName = is_array($config) ? ($config['table'] ?? (string)$keyOrTable) : (string)$config;
            $resultKey = is_string($keyOrTable) && !is_numeric($keyOrTable) ? $keyOrTable : $tableName;

            $endpoint = "{$this->url}/rest/v1/{$tableName}";
            $queryParams = [];

            if (is_array($config)) {
                // Filters
                if (!empty($config['filters']) && is_array($config['filters'])) {
                    foreach ($config['filters'] as $fKey => $fVal) {
                        if (is_array($fVal)) {
                            if (array_keys($fVal) !== range(0, count($fVal) - 1)) {
                                foreach ($fVal as $op => $opVal) {
                                    $queryParams[] = $fKey . '=' . $op . '.' . rawurlencode((string)$opVal);
                                }
                            } elseif (!empty($fVal)) {
                                $enc = array_map(static fn($i): string => rawurlencode((string)$i), $fVal);
                                $queryParams[] = $fKey . '=in.(' . implode(',', $enc) . ')';
                            }
                        } elseif (is_string($fVal) && preg_match('/^(eq|gt|gte|lt|lte|neq|like|ilike|in|is)\..*/', $fVal)) {
                            $queryParams[] = $fKey . '=' . rawurlencode($fVal);
                        } else {
                            $queryParams[] = $fKey . '=eq.' . rawurlencode((string)$fVal);
                        }
                    }
                }

                $select = $config['select'] ?? '*';
                $queryParams[] = 'select=' . rawurlencode($select);

                if (!empty($config['order'])) {
                    $queryParams[] = 'order=' . rawurlencode($config['order']);
                }
                if (isset($config['limit']) && $config['limit'] !== null) {
                    $queryParams[] = 'limit=' . (int)$config['limit'];
                }
                if (isset($config['offset']) && $config['offset'] !== null) {
                    $queryParams[] = 'offset=' . (int)$config['offset'];
                }
            } else {
                $queryParams[] = 'select=*';
            }

            if (!empty($queryParams)) {
                $endpoint .= '?' . implode('&', $queryParams);
            }

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_multi_add_handle($mh, $ch);
            $handles[$resultKey] = $ch;
        }

        $running = null;
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 0.1);
            }
        } while ($running > 0 && $status === CURLM_OK);

        $results = [];
        foreach ($handles as $resultKey => $ch) {
            $content = curl_multi_getcontent($ch);
            $decoded = json_decode($content, true);
            $results[$resultKey] = is_array($decoded) ? $decoded : [];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $results;
    }

    public function insert(string $table, array $data, bool $useServiceKey = false): array
    {
        return $this->query($table, 'POST', $data, [], [], $useServiceKey);
    }

    public function update(string $table, array $data, array $filters, bool $useServiceKey = false): array
    {
        return $this->query($table, 'PATCH', $data, $filters, [], $useServiceKey);
    }

    public function delete(string $table, array $filters, bool $useServiceKey = false): array
    {
        return $this->query($table, 'DELETE', null, $filters, [], $useServiceKey);
    }
    
    /**
     * Get count of records - removes limit/offset for accurate counting
     */
    public function count(string $table, array $filters = [], array $options = []): int
    {
        try {
            // IMPORTANT: Remove limit and offset for counting
            unset($options['limit']);
            unset($options['offset']);
            unset($options['order']);
            
            // Only select 'id' to reduce data transfer
            $options['select'] = 'id';
            
            $results = $this->select($table, $filters, $options);
            return count($results);
        } catch (\Exception $e) {
            error_log("Database count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Uploads a file to Supabase Storage bucket and returns its public CDN URL.
     */
    public function uploadStorage(string $bucket, string $remotePath, string $localFilePath, string $mimeType = 'image/jpeg'): ?string
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
        } catch (\Throwable $e) {
            error_log("Supabase Storage Exception: " . $e->getMessage());
        }

        return null;
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
        } catch (\Throwable $e) {
            error_log("Supabase listBuckets error: " . $e->getMessage());
        }
        return [];
    }

    /**
     * Fetches real-time Supabase Object Storage metrics across all buckets with caching.
     */
    public function getStorageMetrics(bool $forceRefresh = false): array
    {
        $cacheFile = __DIR__ . '/../storage/cache/supabase_storage_metrics.json';
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
        } catch (\Throwable $e) {
            error_log("Supabase getStorageMetrics error: " . $e->getMessage());
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

    /**
     * Measures Supabase PostgreSQL response latency and live record statistics with caching.
     */
    public function getDatabaseMetrics(bool $forceRefresh = false): array
    {
        $cacheFile = __DIR__ . '/../storage/cache/supabase_db_metrics.json';
        if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < 60)) {
            $cached = @json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['total_records'])) {
                return $cached;
            }
        }

        try {
            $startTime = microtime(true);
            
            // Ping latency test
            $testPing = $this->query('barangays', 'GET', null, [], ['select' => 'id', 'limit' => 1]);
            $latencyMs = max(1, (int)round((microtime(true) - $startTime) * 1000));

            $systemTables = [
                'employees', 'roles', 'permissions', 'role_permissions',
                'patients', 'appointments', 'consultations', 'assessment',
                'prescriptions', 'referrals', 'medical_records', 'triage_queue',
                'permits', 'inspections', 'permit_documents', 'payments',
                'renewals', 'renewal_history', 'children', 'immunizations',
                'immunization_assessments', 'service_providers', 'septic_tanks',
                'service_requests', 'maintenance_records', 'wastewater_invoices',
                'surveillance_cases', 'surveillance_index_cases', 'surveillance_alerts',
                'surveillance_intel_queue', 'surveillance_intel_log', 'barangays',
                'setting_categories', 'system_settings', 'feature_flags',
                'settings_versions', 'activity_logs', 'announcements'
            ];

            $multiConfig = [];
            foreach ($systemTables as $tbl) {
                $multiConfig[$tbl] = ['select' => 'id', 'limit' => 10000];
            }

            $tableResults = $this->multiSelect($multiConfig);
            $totalRecords = 0;
            $tableStats = [];

            foreach ($tableResults as $tbl => $rows) {
                $count = count($rows);
                $totalRecords += $count;
                $tableStats[$tbl] = $count;
            }

            $result = [
                'success' => true,
                'latency_ms' => $latencyMs,
                'status' => 'healthy',
                'total_records' => $totalRecords,
                'table_count' => count($systemTables),
                'active_tables_count' => count(array_filter($tableStats, fn($c) => $c > 0)),
                'tables' => $tableStats,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!is_dir(dirname($cacheFile))) {
                @mkdir(dirname($cacheFile), 0755, true);
            }
            @file_put_contents($cacheFile, json_encode($result));

            return $result;
        } catch (\Throwable $e) {
            error_log("Supabase getDatabaseMetrics error: " . $e->getMessage());
            return [
                'success' => false,
                'latency_ms' => 0,
                'status' => 'unreachable',
                'error' => $e->getMessage(),
                'total_records' => 0,
                'table_count' => 0,
                'active_tables_count' => 0,
                'tables' => [],
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }
    }
}