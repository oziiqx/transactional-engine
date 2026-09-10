<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final readonly class PaymentFailed extends PaymentDomainEvent
{
    /**
     * @param positive-int     $attemptsMade
     */
    public function __construct(
        string $paymentId,
        public string $failureCode,
        public string $failureMessage,
        public bool $retryable,
        public int $attemptsMade,
        public bool $attemptsExhausted,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($paymentId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'payment.failed';
    }

    /**
     * True when the processor should stop retrying: either the failure is
     * permanent or the attempt budget is spent.
     */
    public function isDeadLettered(): bool
    {
        return ! $this->retryable || $this->attemptsExhausted;
    }

    public function payload(): array
    {
        return [
            'paymentId' => $this->paymentId,
            'failureCode' => $this->failureCode,
            'failureMessage' => $this->failureMessage,
            'retryable' => $this->retryable,
            'attemptsMade' => $this->attemptsMade,
            'attemptsExhausted' => $this->attemptsExhausted,
        ];
    }
}
