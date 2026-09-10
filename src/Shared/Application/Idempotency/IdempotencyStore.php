<?php

declare(strict_types=1);

namespace App\Shared\Application\Idempotency;

use App\Shared\Domain\ValueObject\IdempotencyKey;

/**
 * Transport-level replay protection for state-changing requests.
 *
 * Aggregates that own hard money movements (payments) additionally enforce
 * idempotency with a unique database constraint; this store is the first, cheap
 * line of defence that also lets a retried request read back its first response.
 */
interface IdempotencyStore
{
    /**
     * Attempt to reserve the key for the given operation.
     *
     * @param non-empty-string $operation      logical endpoint, e.g. "payments.authorize"
     * @param non-empty-string $requestFingerprint hash of the canonical request body
     */
    public function open(IdempotencyKey $key, string $operation, string $requestFingerprint): OpenResult;

    /**
     * Persist the final response so later replays can return it verbatim.
     *
     * @param non-empty-string $operation
     */
    public function close(IdempotencyKey $key, string $operation, RecordedResponse $response): void;

    /**
     * Release a reservation that failed before producing a response, so the
     * client may legitimately retry.
     *
     * @param non-empty-string $operation
     */
    public function discard(IdempotencyKey $key, string $operation): void;
}
