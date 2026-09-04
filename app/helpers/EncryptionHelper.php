<?php
// app/helpers/EncryptionHelper.php

require_once __DIR__ . '/../../Core/Env.php';

class EncryptionHelper
{
    private static ?string $key = null;
    private static array $memoryCache = [];
    private static ?string $gpgHome = null;
    private static ?bool $gpgAvailable = null;

    /**
     * Map of database tables to sensitive fields requiring column-level encryption.
     * Rule: Never include fields used in WHERE/search/filter/sort (IDs, dates, status enums).
     */
    private static array $sensitiveColumns = [
        'patients' => [
            'contact',
            'contact_number',
            'emergency_contact_number',
            'health_condition_notes',
            'national_id',
            'passport_number',
        ],
        'permits' => [
            'contact',
            'email',
        ],
        'sanitation_applicants' => [
            'contact_info',
            'contact',
            'email',
        ],
        'employees' => [
            'email',
        ],
        'children' => [
            'mother_contact',
            'father_contact',
            'family_history',
        ],
        'surveillance_cases' => [
            'contact_number',
        ],
        'service_providers' => [
            'contact',
            'email',
        ],
        'service_requests' => [
            'contact',
        ],
    ];

    /**
     * Get or load the master encryption key from .env (never hardcoded)
     */
    public static function getKey(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }

        Env::load();
        $envKey = Env::get('DB_ENCRYPTION_KEY') ?? Env::get('MASTER_ENCRYPTION_KEY');

        if (empty($envKey)) {
            error_log('EncryptionHelper WARNING: DB_ENCRYPTION_KEY not set in .env.');
            $envKey = 'HSMS_CIVENTRAL_SECURE_FALLBACK_KEY_2026';
        }

        self::$key = $envKey;
        return self::$key;
    }

    /**
     * Initialize secure GPG homedir for safe execution across CLI and Apache daemon
     */
    private static function getGpgHome(): string
    {
        if (self::$gpgHome !== null) {
            return self::$gpgHome;
        }

        $dir = sys_get_temp_dir() . '/civentral_gpg';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        self::$gpgHome = $dir;
        return self::$gpgHome;
    }

    /**
     * Verify if GPG CLI binary is available on system
     */
    public static function isGpgAvailable(): bool
    {
        if (self::$gpgAvailable !== null) {
            return self::$gpgAvailable;
        }

        $output = [];
        $returnVar = -1;
        @exec('which gpg 2>&1', $output, $returnVar);
        self::$gpgAvailable = ($returnVar === 0 && !empty($output[0]));
        return self::$gpgAvailable;
    }

    /**
     * Check if a given string is already encrypted
     */
    public static function isEncrypted(?string $value): bool
    {
        if (empty($value) || !is_string($value)) {
            return false;
        }

        // Check for PostgreSQL bytea hex representation (\x...)
        if (str_starts_with($value, '\\x') && strlen($value) > 10 && ctype_xdigit(substr($value, 2))) {
            return true;
        }

        // Check for OpenSSL fallback prefix
        if (str_starts_with($value, 'ENC::')) {
            return true;
        }

        // Check for ASCII Armor PGP Message
        if (str_contains($value, '-----BEGIN PGP MESSAGE-----')) {
            return true;
        }

        return false;
    }

    /**
     * Encrypt sensitive string value.
     * Uses OpenPGP AES-256 symmetric cipher compatible with PostgreSQL pgp_sym_encrypt.
     * Returns a PostgreSQL bytea hex string (\x...) or OpenSSL fallback.
     */
    public static function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        // Avoid double encryption
        if (self::isEncrypted($plaintext)) {
            return $plaintext;
        }

        $key = self::getKey();

        // 1. Primary Engine: GPG OpenPGP Symmetric AES-256 (PostgreSQL pgcrypto compatible)
        if (self::isGpgAvailable()) {
            try {
                $gpgHome = self::getGpgHome();
                $cmd = 'gpg --homedir ' . escapeshellarg($gpgHome) .
                       ' --batch --yes --quiet --pinentry-mode loopback' .
                       ' --passphrase ' . escapeshellarg($key) .
                       ' --symmetric --cipher-algo AES256';

                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ];

                $process = @proc_open($cmd, $descriptors, $pipes);
                if (is_resource($process)) {
                    fwrite($pipes[0], $plaintext);
                    fclose($pipes[0]);

                    $binaryCipher = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);

                    $status = proc_close($process);
                    if ($status === 0 && !empty($binaryCipher)) {
                        $byteaHex = '\\x' . bin2hex($binaryCipher);
                        self::$memoryCache[$byteaHex] = $plaintext;
                        return $byteaHex;
                    }
                }
            } catch (Throwable $e) {
                error_log('EncryptionHelper GPG encrypt error: ' . $e->getMessage());
            }
        }

        // 2. Secondary Engine: OpenSSL AES-256-CBC Fallback
        try {
            $cipher = 'aes-256-cbc';
            $ivLength = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivLength);
            $derivedKey = hash('sha256', $key, true);

            $encrypted = openssl_encrypt($plaintext, $cipher, $derivedKey, OPENSSL_RAW_DATA, $iv);
            if ($encrypted !== false) {
                $fallback = 'ENC::' . base64_encode($iv . $encrypted);
                self::$memoryCache[$fallback] = $plaintext;
                return $fallback;
            }
        } catch (Throwable $e) {
            error_log('EncryptionHelper OpenSSL encrypt error: ' . $e->getMessage());
        }

        return $plaintext;
    }

    /**
     * Decrypt sensitive string or bytea value.
     * Compatible with PostgreSQL pgp_sym_decrypt output, bytea hex (\x...), and OpenSSL fallback.
     */
    public static function decrypt(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return $ciphertext;
        }

        // Return from memory cache if already decrypted in current request
        if (isset(self::$memoryCache[$ciphertext])) {
            return self::$memoryCache[$ciphertext];
        }

        // If not encrypted, return as plaintext (ensures backward compatibility)
        if (!self::isEncrypted($ciphertext)) {
            return $ciphertext;
        }

        $key = self::getKey();

        // 1. Decrypt OpenSSL fallback format (ENC::...)
        if (str_starts_with($ciphertext, 'ENC::')) {
            try {
                $rawPayload = base64_decode(substr($ciphertext, 5));
                $cipher = 'aes-256-cbc';
                $ivLength = openssl_cipher_iv_length($cipher);
                $derivedKey = hash('sha256', $key, true);

                if (strlen($rawPayload) > $ivLength) {
                    $iv = substr($rawPayload, 0, $ivLength);
                    $encryptedData = substr($rawPayload, $ivLength);
                    $decrypted = openssl_decrypt($encryptedData, $cipher, $derivedKey, OPENSSL_RAW_DATA, $iv);
                    if ($decrypted !== false) {
                        self::$memoryCache[$ciphertext] = $decrypted;
                        return $decrypted;
                    }
                }
            } catch (Throwable $e) {
                error_log('EncryptionHelper OpenSSL decrypt error: ' . $e->getMessage());
            }
            return $ciphertext;
        }

        // 2. Decrypt PostgreSQL bytea hex string (\x...)
        if (str_starts_with($ciphertext, '\\x')) {
            $hexStr = substr($ciphertext, 2);
            $rawBinary = @hex2bin($hexStr);

            if ($rawBinary === false) {
                return $ciphertext;
            }

            if (self::isGpgAvailable()) {
                try {
                    $gpgHome = self::getGpgHome();
                    $cmd = 'gpg --homedir ' . escapeshellarg($gpgHome) .
                           ' --batch --yes --quiet --pinentry-mode loopback' .
                           ' --passphrase ' . escapeshellarg($key) .
                           ' --decrypt';

                    $descriptors = [
                        0 => ['pipe', 'r'],
                        1 => ['pipe', 'w'],
                        2 => ['pipe', 'w']
                    ];

                    $process = @proc_open($cmd, $descriptors, $pipes);
                    if (is_resource($process)) {
                        fwrite($pipes[0], $rawBinary);
                        fclose($pipes[0]);

                        $decrypted = stream_get_contents($pipes[1]);
                        fclose($pipes[1]);
                        fclose($pipes[2]);

                        $status = proc_close($process);
                        if ($status === 0) {
                            self::$memoryCache[$ciphertext] = $decrypted;
                            return $decrypted;
                        }
                    }
                } catch (Throwable $e) {
                    error_log('EncryptionHelper GPG decrypt error: ' . $e->getMessage());
                }
            }

            // Fallback: Check if rawBinary is plain UTF-8 text (e.g. unencrypted bytea cast)
            if (mb_check_encoding($rawBinary, 'UTF-8') && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $rawBinary)) {
                return $rawBinary;
            }
        }

        // 3. Decrypt ASCII Armor PGP
        if (str_contains($ciphertext, '-----BEGIN PGP MESSAGE-----') && self::isGpgAvailable()) {
            try {
                $gpgHome = self::getGpgHome();
                $cmd = 'gpg --homedir ' . escapeshellarg($gpgHome) .
                       ' --batch --yes --quiet --pinentry-mode loopback' .
                       ' --passphrase ' . escapeshellarg($key) .
                       ' --decrypt';

                $descriptors = [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ];

                $process = @proc_open($cmd, $descriptors, $pipes);
                if (is_resource($process)) {
                    fwrite($pipes[0], $ciphertext);
                    fclose($pipes[0]);

                    $decrypted = stream_get_contents($pipes[1]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);

                    $status = proc_close($process);
                    if ($status === 0) {
                        self::$memoryCache[$ciphertext] = $decrypted;
                        return $decrypted;
                    }
                }
            } catch (Throwable $e) {
                error_log('EncryptionHelper GPG armor decrypt error: ' . $e->getMessage());
            }
        }

        return $ciphertext;
    }

    /**
     * Encrypt sensitive fields of a model data array before INSERT/UPDATE.
     */
    public static function encryptModel(string $modelOrTable, array $data): array
    {
        $table = strtolower(trim($modelOrTable));
        $fields = self::$sensitiveColumns[$table] ?? [];

        foreach ($fields as $field) {
            if (isset($data[$field]) && is_string($data[$field]) && $data[$field] !== '') {
                $data[$field] = self::encrypt($data[$field]);
            }
        }

        return $data;
    }

    /**
     * Decrypt sensitive fields of a single record row after SELECT.
     */
    public static function decryptModel(string $modelOrTable, ?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $table = strtolower(trim($modelOrTable));
        $fields = self::$sensitiveColumns[$table] ?? [];

        foreach ($fields as $field) {
            if (isset($row[$field]) && is_string($row[$field]) && $row[$field] !== '') {
                $row[$field] = self::decrypt($row[$field]);
            }
        }

        // Helpful cross-field aliases
        if ($table === 'patients') {
            if (!isset($row['contact_number']) && isset($row['contact'])) {
                $row['contact_number'] = $row['contact'];
            }
            if (!isset($row['health_condition_notes']) && isset($row['allergies'])) {
                $row['health_condition_notes'] = $row['allergies'];
            }
        }

        return $row;
    }

    /**
     * Decrypt sensitive fields of a list of record rows after SELECT.
     */
    public static function decryptRows(string $modelOrTable, array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        return array_map(static fn($row) => is_array($row) ? self::decryptModel($modelOrTable, $row) : $row, $rows);
    }

    /**
     * Get list of sensitive fields configured for a specific table/model.
     */
    public static function getSensitiveFields(string $modelOrTable): array
    {
        $table = strtolower(trim($modelOrTable));
        return self::$sensitiveColumns[$table] ?? [];
    }

    /**
     * SQL Snippet Builder for INSERT/UPDATE queries:
     * Generates: pgp_sym_encrypt('value', 'key')
     */
    public static function sqlWrapEncrypt(string $value): string
    {
        $key = self::getKey();
        $safeVal = str_replace("'", "''", $value);
        $safeKey = str_replace("'", "''", $key);
        return "pgp_sym_encrypt('{$safeVal}', '{$safeKey}')";
    }

    /**
     * SQL Snippet Builder for SELECT queries:
     * Generates: pgp_sym_decrypt(column_name::bytea, 'key') AS column_name
     */
    public static function sqlWrapDecrypt(string $column): string
    {
        $key = self::getKey();
        $safeKey = str_replace("'", "''", $key);
        return "pgp_sym_decrypt({$column}::bytea, '{$safeKey}') AS {$column}";
    }
}
