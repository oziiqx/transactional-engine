<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * A value or command would put an aggregate into a state its rules forbid.
 * Maps to HTTP 422 Unprocessable Entity.
 */
final class InvariantViolation extends DomainException
{
    /**
     * @param non-empty-string $code
     * @param array<string, scalar|array<array-key, scalar>|null> $context
     */
    private function __construct(
        string $message,
        private readonly string $code,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param non-empty-string $message
     */
    public static function because(string $message): self
    {
        return new self($message, 'invariant.violation');
    }

    /**
     * @param non-empty-string $code
     * @param non-empty-string $message
     * @param array<string, scalar|array<array-key, scalar>|null> $context
     */
    public static function of(string $code, string $message, array $context = []): self
    {
        return new self($message, $code, $context);
    }

    public function errorCode(): string
    {
        return $this->code;
    }

    public function context(): array
    {
        return $this->context;
    }
}
