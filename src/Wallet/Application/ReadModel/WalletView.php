<?php

declare(strict_types=1);

namespace App\Wallet\Application\ReadModel;

/**
 * Flat, serialization-ready projection of a wallet for the read side. Built by the
 * {@see WalletReadModel} straight from SQL — no aggregate, no ORM hydration.
 *
 * `balance` is a decimal string ("125.00"); `openedAt`/`closedAt` are ISO-8601.
 *
 * @psalm-immutable
 */
final readonly class WalletView
{
    public function __construct(
        public string $id,
        public string $holderId,
        public string $currency,
        public string $balance,
        public int $balanceMinor,
        public string $status,
        public int $ledgerEntries,
        public string $openedAt,
        public ?string $closedAt,
    ) {
    }
}
