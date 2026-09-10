<?php

declare(strict_types=1);

namespace App\Shared\Application\Idempotency;

/**
 * Outcome of {@see IdempotencyStore::open()}.
 *
 * @psalm-immutable
 */
final readonly class OpenResult
{
    private function __construct(
        public OpenOutcome $outcome,
        public ?RecordedResponse $replayedResponse = null,
    ) {
    }

    /**
     * The caller won the reservation and must execute the operation.
     */
    public static function proceed(): self
    {
        return new self(OpenOutcome::Proceed);
    }

    /**
     * The operation already completed under this key; return the stored response.
     */
    public static function replay(RecordedResponse $response): self
    {
        return new self(OpenOutcome::Replay, $response);
    }

    /**
     * A request with the same key is still running.
     */
    public static function inFlight(): self
    {
        return new self(OpenOutcome::InFlight);
    }

    /**
     * The key was reused with a different request body.
     */
    public static function fingerprintMismatch(): self
    {
        return new self(OpenOutcome::FingerprintMismatch);
    }
}
