<?php

declare(strict_types=1);

namespace App\Wallet\Application\Port;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Domain\Wallet\WalletId;

/**
 * Outbound port for the system of record a wallet is reconciled against — a
 * payment-service-provider statement, a bank feed, or, in this build, the sum of
 * the wallet's own immutable ledger entries. The adapter lives in infrastructure.
 */
interface ReconciliationSource
{
    /**
     * The balance this source believes the wallet should hold.
     *
     * @param Currency $currency the wallet's currency, so the result is well-formed
     */
    public function authoritativeBalanceFor(WalletId $walletId, Currency $currency): Money;
}
