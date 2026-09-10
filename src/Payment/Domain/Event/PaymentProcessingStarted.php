<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final readonly class PaymentProcessingStarted extends PaymentDomainEvent
{
    /**
     * @param positive-int     $attemptNumber
     */
    public function __construct(
        string $paymentId,
        public int $attemptNumber,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($paymentId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'payment.processing_started';
    }

    public function payload(): array
    {
        return [
            'paymentId' => $this->paymentId,
            'attemptNumber' => $this->attemptNumber,
        ];
    }
}
