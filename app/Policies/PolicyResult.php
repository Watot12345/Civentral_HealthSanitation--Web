<?php
// app/Policies/PolicyResult.php

namespace App\Policies;

/**
 * Immutable outcome of a policy decision.
 *
 * Policies never touch HTTP, sessions or the database; they only return one of
 * these. Controllers decide how to turn a denial into a response, which keeps
 * the rules unit-testable and the controllers thin.
 */
final class PolicyResult
{
    private function __construct(
        public readonly bool $allowed,
        public readonly string $message = '',
        public readonly int $code = 200,
        public readonly array $errors = []
    ) {
    }

    public static function allow(): self
    {
        return new self(true);
    }

    /**
     * @param array<string,string> $errors Optional field => message map (validation denials).
     */
    public static function deny(string $message, int $code = 403, array $errors = []): self
    {
        return new self(false, $message, $code, $errors);
    }

    public function denied(): bool
    {
        return !$this->allowed;
    }

    /**
     * Shape understood by BaseController::handle() for failed requests.
     */
    public function toResponse(): array
    {
        $response = [
            'success' => false,
            'message' => $this->message,
            'code'    => $this->code,
        ];
        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }
        return $response;
    }
}
