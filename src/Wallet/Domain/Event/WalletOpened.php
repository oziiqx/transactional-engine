<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Event;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final readonly class WalletOpened extends WalletDomainEvent
{
    /**
     * @param non-empty-string $walletId
     * @param non-empty-string $holderId
     * @param non-empty-string $currency
     */
    public function __construct(
        string $walletId,
        public string $holderId,
        public string $currency,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($walletId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'wallet.opened';
    }

    public function payload(): array
    {
        return [
            'walletId' => $this->walletId,
            'holderId' => $this->holderId,
            'currency' => $this->currency,
        ];
    }
}
