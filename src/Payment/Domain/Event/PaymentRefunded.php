<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final readonly class PaymentRefunded extends PaymentDomainEvent
{
    public function __construct(
        string $paymentId,
        public string $refundId,
        public int $amountMinor,
        public string $currency,
        public int $totalRefundedMinor,
        public bool $fullyRefunded,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($paymentId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'payment.refunded';
    }

    public function payload(): array
    {
        return [
            'paymentId' => $this->paymentId,
            'refundId' => $this->refundId,
            'amountMinor' => $this->amountMinor,
            'currency' => $this->currency,
            'totalRefundedMinor' => $this->totalRefundedMinor,
            'fullyRefunded' => $this->fullyRefunded,
        ];
    }
}
