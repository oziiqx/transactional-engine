<?php

declare(strict_types=1);

namespace App\Payment\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

/**
 * @psalm-immutable
 */
abstract readonly class PaymentDomainEvent implements DomainEvent
{
    public function __construct(
        public string $paymentId,
        public DateTimeImmutable $occurredOn,
    ) {
    }

    public function aggregateId(): string
    {
        return $this->paymentId;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
