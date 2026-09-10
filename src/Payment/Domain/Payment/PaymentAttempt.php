<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

/**
 * An immutable record of one settled processing attempt. Attempts are stored as a
 * JSONB array on the payment row — they are history, never queried in isolation.
 *
 * @psalm-immutable
 */
final readonly class PaymentAttempt implements JsonSerializable
{
    /**
     * @param positive-int $number
     */
    public function __construct(
        public int $number,
        public AttemptOutcome $outcome,
        public ?string $gatewayReference,
        public ?FailureReason $failureReason,
        public DateTimeImmutable $at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $number = $data['number'] ?? null;
        $outcome = $data['outcome'] ?? null;
        $reference = $data['gatewayReference'] ?? null;
        $failure = $data['failureReason'] ?? null;
        $at = $data['at'] ?? null;

        return new self(
            \is_int($number) && $number >= 1 ? $number : 1,
            \is_string($outcome) ? AttemptOutcome::from($outcome) : AttemptOutcome::Failed,
            \is_string($reference) && $reference !== '' ? $reference : null,
            \is_array($failure) ? FailureReason::fromArray($failure) : null,
            new DateTimeImmutable(\is_string($at) && $at !== '' ? $at : 'now'),
        );
    }

    /**
     * @return array{number: int, outcome: string, gatewayReference: ?string, failureReason: ?array<string, mixed>, at: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'number' => $this->number,
            'outcome' => $this->outcome->value,
            'gatewayReference' => $this->gatewayReference,
            'failureReason' => $this->failureReason?->jsonSerialize(),
            'at' => $this->at->format(DateTimeInterface::ATOM),
        ];
    }
}
