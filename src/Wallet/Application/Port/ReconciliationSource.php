<?php

declare(strict_types=1);

namespace App\Wallet\Application\Port;

use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Domain\Wallet\WalletId;

/**
 * Outbound port for the system of record a wallet is reconciled against — a
 * payment-service-provider statement, a bank feed, or an internal double-entry
 * ledger. The adapter lives in the infrastructure layer.
 */
interface ReconciliationSource
{
    /**
     * The balance this source believes the wallet should hold, in the wallet's
     * own currency.
     */
    public function authoritativeBalanceFor(WalletId $walletId): Money;
}
