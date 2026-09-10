<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use DateTimeImmutable;

/**
 * The fan-out point: a completed payment triggers a wallet credit, invoice
 * generation and a merchant webhook — all asynchronously.
 *
 * @psalm-immutable
 */
final readonly class PaymentCompleted extends PaymentDomainEvent
{
    public function __construct(
        string $paymentId,
        public string $merchantReference,
        public string $payerId,
        public int $amountMinor,
        public string $currency,
        public string $gatewayReference,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($paymentId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'payment.completed';
    }

    public function payload(): array
    {
        return [
            'paymentId' => $this->paymentId,
            'merchantReference' => $this->merchantReference,
            'payerId' => $this->payerId,
            'amountMinor' => $this->amountMinor,
            'currency' => $this->currency,
            'gatewayReference' => $this->gatewayReference,
        ];
    }
}
