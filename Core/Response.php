<?php
// Core/Response.php

class Response
{
    public static function json(bool $success, string $message = '', mixed $data = null, int $httpCode = 200, array $extra = []): never
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }

        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $extra));

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_write_close();
        }

        exit(0);
    }

    public static function success(string $message = 'Operation completed successfully.', mixed $data = null, int $httpCode = 200, array $extra = []): never
    {
        self::json(true, $message, $data, $httpCode, $extra);
    }

    public static function error(string $message = 'An error occurred.', mixed $httpCode = 400, mixed $data = null): never
    {
        if (is_array($httpCode) && is_int($data)) {
            $tmp = $httpCode;
            $httpCode = $data;
            $data = $tmp;
        } elseif (!is_int($httpCode)) {
            $httpCode = 400;
        }
        self::json(false, $message, $data, (int)$httpCode);
    }

    public static function crudSuccess(string $action, mixed $record = null, string $message = '', int $httpCode = 200, array $extra = []): never
    {
        $id = null;
        if (is_array($record)) {
            $id = $record['id'] ?? null;
        } elseif (is_numeric($record) || is_string($record)) {
            $id = $record;
        }

        $payload = array_merge([
            'action' => $action,
            'record' => $record,
            'id'     => $id,
        ], $extra);

        $defaultMsg = ucfirst($action) . ' completed successfully.';
        self::json(true, $message ?: $defaultMsg, $record, $httpCode, $payload);
    }

    public static function validationError(array $errors, string $message = 'Validation failed.'): never
    {
        self::json(false, $message, null, 422, ['errors' => $errors]);
    }
}