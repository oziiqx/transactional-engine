<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final readonly class PaymentInitiated extends PaymentDomainEvent
{
    public function __construct(
        string $paymentId,
        public string $idempotencyKey,
        public string $merchantReference,
        public string $payerId,
        public int $amountMinor,
        public string $currency,
        public string $method,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($paymentId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'payment.initiated';
    }

    public function payload(): array
    {
        return [
            'paymentId' => $this->paymentId,
            'idempotencyKey' => $this->idempotencyKey,
            'merchantReference' => $this->merchantReference,
            'payerId' => $this->payerId,
            'amountMinor' => $this->amountMinor,
            'currency' => $this->currency,
            'method' => $this->method,
        ];
    }
}
