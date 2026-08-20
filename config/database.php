<?php
// config/database.php

require_once __DIR__ . '/../Core/Env.php';

class Database
{
    private static ?Database $instance = null;

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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
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
}