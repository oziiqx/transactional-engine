<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Reconciliation;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Application\Port\ReconciliationSource;
use App\Wallet\Domain\Ledger\LedgerDirection;
use App\Wallet\Domain\Wallet\WalletId;
use Doctrine\DBAL\Connection;

/**
 * Reconciliation source that treats the wallet's own append-only ledger as the
 * ground truth: it re-derives the balance by summing every entry's signed amount
 * in SQL, independently of the denormalised `balance_minor` column the write
 * model maintains.
 *
 * If the two ever diverge (a bug, a partial write, manual tampering), the
 * reconciliation pass posts a system adjustment entry and emits
 * {@see \App\Wallet\Domain\Event\WalletReconciled} with the discrepancy.
 */
final readonly class LedgerSumReconciliationSource implements ReconciliationSource
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function authoritativeBalanceFor(WalletId $walletId, Currency $currency): Money
    {
        $sum = $this->connection->fetchOne(
            <<<'SQL'
                SELECT COALESCE(
                    SUM(CASE WHEN direction = :credit THEN amount_minor ELSE -amount_minor END),
                    0
                )
                FROM wallet_ledger_entries
                WHERE wallet_id = :walletId
                SQL,
            [
                'credit' => LedgerDirection::Credit->value,
                'walletId' => $walletId->value,
            ],
        );

        return Money::of((int) (\is_scalar($sum) ? $sum : 0), $currency);
    }
}
