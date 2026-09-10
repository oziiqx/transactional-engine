<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Event;

/**
 * @psalm-immutable
 */
final readonly class WalletFrozen extends WalletDomainEvent
{
    public function eventName(): string
    {
        return 'wallet.frozen';
    }

    public function payload(): array
    {
        return [
            'walletId' => $this->walletId,
        ];
    }
}
