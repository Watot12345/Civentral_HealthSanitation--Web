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
     * Inspect file bytes using finfo_file() and verify against extension
     */
    public static function validate(string $filePath, string $originalFilename, array $allowedExtensions = ['json', 'csv', 'pdf', 'xlsx']): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return [
                'valid'   => false,
                'message' => 'Upload file does not exist or is unreadable.'
            ];
        }

        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            return [
                'valid'   => false,
                'message' => "File extension '.{$ext}' is not permitted. Allowed: " . implode(', ', $allowedExtensions)
            ];
        }

        // Deep byte inspection using finfo_file()
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (!$detectedMime) {
            return [
                'valid'   => false,
                'message' => 'Could not inspect file MIME type.'
            ];
        }

        $expectedMimes = self::$mimeMap[$ext] ?? [];
        if (empty($expectedMimes)) {
            return [
                'valid'   => false,
                'message' => "Unrecognized file type '.{$ext}' has no MIME mapping configured."
            ];
        }

        if (!in_array($detectedMime, $expectedMimes, true)) {
            // Strict fallback for JSON/CSV text variants — text/html or executable scripts are explicitly rejected
            $allowedTextFallbacks = ['text/plain', 'text/json', 'text/csv', 'text/comma-separated-values'];
            if (($ext === 'json' || $ext === 'csv') && in_array($detectedMime, $allowedTextFallbacks, true)) {
                return ['valid' => true, 'mime' => $detectedMime, 'extension' => $ext];
            }

            return [
                'valid'   => false,
                'message' => "MIME type mismatch: extension '.{$ext}' claimed, but actual byte content is '{$detectedMime}'."
            ];
        }

        return [
            'valid'     => true,
            'mime'      => $detectedMime,
            'extension' => $ext
        ];
    }
}
