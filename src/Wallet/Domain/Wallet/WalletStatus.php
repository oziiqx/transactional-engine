<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Wallet;

/**
 * Lifecycle of a wallet.
 *
 *   ACTIVE ──freeze──▶ FROZEN ──unfreeze──▶ ACTIVE
 *   ACTIVE ──close───▶ CLOSED   (only at a zero balance; terminal)
 *   FROZEN ──close───▶ CLOSED   (only at a zero balance; terminal)
 */
enum WalletStatus: string
{
    case Active = 'active';
    case Frozen = 'frozen';
    case Closed = 'closed';

    public function allowsMovement(): bool
    {
        return $this === self::Active;
    }

    public function isTerminal(): bool
    {
        return $this === self::Closed;
    }
}
