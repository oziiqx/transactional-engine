<?php

declare(strict_types=1);

namespace App\Wallet\Application\ReadModel;

use App\Wallet\Domain\Wallet\WalletId;

/**
 * The read side of the Wallet context. Implemented over plain DBAL against the
 * same tables the write side owns (a projection would be introduced only if read
 * throughput demanded it — the shape here is already denormalised enough).
 */
interface WalletReadModel
{
    public function find(WalletId $walletId): ?WalletView;

    /**
     * Ledger history, newest first.
     *
     * @param string|null $cursor opaque token from a previous page
     */
    public function ledgerHistory(WalletId $walletId, int $limit, ?string $cursor): LedgerEntryPage;
}
