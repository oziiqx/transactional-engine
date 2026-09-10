<?php

declare(strict_types=1);

namespace App\Shared\Application\Lock;

use App\Shared\Domain\Exception\DomainException;

/**
 * The requested resource is already locked by another process. Maps to HTTP 423
 * Locked — the caller should back off and retry.
 */
final class LockUnavailable extends DomainException
{
    /**
     * @param non-empty-string $resource
     */
    public static function forResource(string $resource): self
    {
        return new self(\sprintf('Resource "%s" is currently locked by another operation.', $resource));
    }

    public function errorCode(): string
    {
        return 'lock.unavailable';
    }

    public function httpStatus(): int
    {
        return 423;
    }
}
