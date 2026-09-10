<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Event;

use App\Shared\Domain\Event\DomainEvent;
use DateTimeImmutable;

/**
 * Common shape for every event a {@see \App\Wallet\Domain\Wallet\Wallet} records.
 *
 * @psalm-immutable
 */
abstract readonly class WalletDomainEvent implements DomainEvent
{
    /**
     * @param non-empty-string $walletId
     */
    public function __construct(
        public string $walletId,
        public DateTimeImmutable $occurredOn,
    ) {
    }

    public function aggregateId(): string
    {
        return $this->walletId;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }
}
