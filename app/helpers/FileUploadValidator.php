<?php
// app/helpers/FileUploadValidator.php

declare(strict_types=1);

class FileUploadValidator
{
    /**
     * Allowed MIME map by extension category
     */
    private static array $mimeMap = [
        'json' => ['application/json', 'text/plain', 'text/json'],
        'csv'  => ['text/csv', 'text/plain', 'application/csv', 'text/x-comma-separated-values', 'text/comma-separated-values'],
        'txt'  => ['text/plain'],
        'pdf'  => ['application/pdf'],
        'png'  => ['image/png'],
        'jpg'  => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-zip-compressed'],
        'xls'  => ['application/vnd.ms-excel', 'application/msexcel']
    ];

    /**
     * Inspect file bytes using finfo_file(), verify size guardrails, extension allowlist, and structural integrity
     */
    public static function validate(
        string $filePath,
        string $originalFilename,
        array $allowedExtensions = ['json', 'csv', 'pdf', 'xlsx'],
        int $maxSizeBytes = 10485760 // 10MB default
    ): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'valid'   => false,
                'status'  => 400,
                'message' => 'Upload file does not exist or is unreadable.'
            ];
        }

        // 1. File size guardrail (max 10MB)
        $fileSize = filesize($filePath);
        if ($fileSize > $maxSizeBytes) {
            $maxMB = round($maxSizeBytes / (1024 * 1024), 1);
            $actualMB = round($fileSize / (1024 * 1024), 2);
            return [
                'valid'   => false,
                'status'  => 413,
                'message' => "File size ({$actualMB} MB) exceeds maximum allowed limit of {$maxMB} MB."
            ];
        }

        // 2. Extension check
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return [
                'valid'   => false,
                'status'  => 422,
                'message' => "File extension '.{$ext}' is not permitted. Allowed: " . implode(', ', $allowedExtensions)
            ];
        }

        // 3. Deep byte inspection using finfo_file()
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (!$detectedMime) {
            return [
                'valid'   => false,
                'status'  => 422,
                'message' => 'Could not inspect file MIME type.'
            ];
        }

        $expectedMimes = self::$mimeMap[$ext] ?? [];
        if (empty($expectedMimes)) {
            return [
                'valid'   => false,
                'status'  => 422,
                'message' => "Unrecognized file type '.{$ext}' has no MIME mapping configured."
            ];
        }

        if (!in_array($detectedMime, $expectedMimes, true)) {
            // Strict fallback for JSON/CSV text variants — executable scripts/binaries are explicitly rejected
            $allowedTextFallbacks = ['text/plain', 'text/json', 'text/csv', 'text/comma-separated-values'];
            if (($ext === 'json' || $ext === 'csv') && in_array($detectedMime, $allowedTextFallbacks, true)) {
                // fall through to structural content check
            } else {
                return [
                    'valid'   => false,
                    'status'  => 422,
                    'message' => "MIME type mismatch: extension '.{$ext}' claimed, but actual byte content is '{$detectedMime}'."
                ];
            }
        }

        // 4. Structural content validation for JSON and CSV
        if ($ext === 'json') {
            $content = file_get_contents($filePath);
            json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'valid'   => false,
                    'status'  => 422,
                    'message' => 'Malformed JSON structure: ' . json_last_error_msg()
                ];
            }
        } elseif ($ext === 'csv') {
            $handle = fopen($filePath, 'r');
            if ($handle) {
                $firstRow = fgetcsv($handle);
                fclose($handle);
                if ($firstRow === false || empty(array_filter($firstRow))) {
                    return [
                        'valid'   => false,
                        'status'  => 422,
                        'message' => 'Malformed or empty CSV structure: no valid column headers detected.'
                    ];
                }
            }
        }

        return [
            'valid'      => true,
            'status'     => 200,
            'mime'       => $detectedMime,
            'extension'  => $ext,
            'size_bytes' => $fileSize
        ];
    }
}
