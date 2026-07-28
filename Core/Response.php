<?php
// Core/Response.php

class Response
{
    public static function json(bool $success, string $message = '', mixed $data = null, int $httpCode = 200, array $extra = []): never
    {
        http_response_code($httpCode);
        
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ], $extra));

        exit;
    }

    public static function success(string $message = 'Operation completed successfully.', mixed $data = null, int $httpCode = 200, array $extra = []): never
    {
        self::json(true, $message, $data, $httpCode, $extra);
    }

    public static function error(string $message = 'An error occurred.', int $httpCode = 400, mixed $data = null): never
    {
        self::json(false, $message, $data, $httpCode);
    }
}