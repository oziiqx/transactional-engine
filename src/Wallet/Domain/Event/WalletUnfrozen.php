<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Event;

/**
 * @psalm-immutable
 */
final readonly class WalletUnfrozen extends WalletDomainEvent
{
    public function eventName(): string
    {
        return 'wallet.unfrozen';
    }

    public function payload(): array
    {
        return ['walletId' => $this->walletId];
    }
}
