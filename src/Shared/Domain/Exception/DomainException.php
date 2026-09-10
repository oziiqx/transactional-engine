<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use RuntimeException;

/**
 * Base type for every error that represents a broken business rule.
 *
 * Infrastructure translates these into transport responses (RFC 7807 for HTTP,
 * a nacked message for the queue) using {@see httpStatus()} and {@see errorCode()}.
 * Application and domain code may let them propagate freely — they are part of the
 * ubiquitous language, not bugs.
 */
abstract class DomainException extends RuntimeException
{
    /**
     * A stable, machine-readable slug (e.g. "wallet.insufficient_funds").
     * Clients switch on this, never on the message text.
     *
     * @return non-empty-string
     */
    abstract public function errorCode(): string;

    /**
     * HTTP status this failure maps to. Overridden by conflict/not-found style
     * errors; the default is 422 Unprocessable Entity.
     *
     * @return int<400, 499>
     */
    public function httpStatus(): int
    {
        return 422;
    }

    /**
     * Extra context safe to expose to the caller. Never include secrets or PII.
     *
     * @return array<string, scalar|array<array-key, scalar>|null>
     */
    public function context(): array
    {
        return [];
    }
}
