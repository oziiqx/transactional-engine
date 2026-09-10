<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Ledger;

/**
 * Which way money moved in a ledger entry, from the wallet's point of view.
 */
enum LedgerDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    /**
     * Sign to apply to the (always positive) entry amount when folding entries
     * into a balance.
     *
     * @return int<-1, 1>
     */
    public function sign(): int
    {
        return match ($this) {
            self::Credit => 1,
            self::Debit => -1,
        };
    }
}
