<?php
// app/exceptions/SettingsException.php

namespace App\Exceptions;

use Exception;
use Throwable;

class SettingsException extends Exception
{
    protected string $logId;
    protected array $context;

    public function __construct(string $message = "", int $code = 400, ?Throwable $previous = null, array $context = [])
    {
        $this->logId = 'ERR-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getLogId(): string
    {
        return $this->logId;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
