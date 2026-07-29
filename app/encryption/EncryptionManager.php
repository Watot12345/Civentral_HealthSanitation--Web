<?php
// app/encryption/EncryptionManager.php

namespace App\Encryption;

require_once __DIR__ . '/../../Core/Env.php';
use RuntimeException;

class EncryptionManager
{
    private string $cipher = 'aes-256-cbc';
    private string $key;

    public function __construct(?string $key = null)
    {
        \Env::load();
        $rawKey = $key ?? \Env::get('MASTER_ENCRYPTION_KEY') ?? \Env::get('SUPABASE_KEY') ?? 'HSMS_CALOOCAN_SECRET_MASTER_KEY_2026';
        $this->key = hash('sha256', $rawKey, true);
    }

    /**
     * Encrypt plaintext string using AES-256-CBC
     */
    public function encrypt(string $value): string
    {
        if (empty($value)) {
            return '';
        }

        // Avoid double encryption
        if (str_starts_with($value, 'ENC::')) {
            return $value;
        }

        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Encryption operation failed.');
        }

        return 'ENC::' . base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt encrypted string
     */
    public function decrypt(string $payload): string
    {
        if (empty($payload)) {
            return '';
        }

        if (!str_starts_with($payload, 'ENC::')) {
            return $payload; // Return as plain string if not encrypted
        }

        $rawPayload = base64_decode(substr($payload, 5));
        $ivLength = openssl_cipher_iv_length($this->cipher);

        if (strlen($rawPayload) < $ivLength) {
            return $payload;
        }

        $iv = substr($rawPayload, 0, $ivLength);
        $ciphertext = substr($rawPayload, $ivLength);

        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->key, 0, $iv);

        return $decrypted !== false ? $decrypted : $payload;
    }
}
