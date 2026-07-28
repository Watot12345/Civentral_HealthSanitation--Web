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
            if (is_string($value) && preg_match('/^(eq|gt|gte|lt|lte|neq|like|ilike|in|is)\..*/', $value)) {
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
}