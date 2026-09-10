<?php

declare(strict_types=1);

namespace App\Wallet\Application\ReadModel;

/**
 * One ledger entry as returned by the read side. `amount` / `balanceAfter` are
 * decimal strings; `recordedAt` is ISO-8601.
 *
 * @psalm-immutable
 */
final readonly class LedgerEntryView
{
    public function __construct(
        public string $id,
        public int $sequence,
        public string $direction,
        public string $amount,
        public string $balanceAfter,
        public string $currency,
        public string $reason,
        public ?string $reference,
        public string $recordedAt,
    ) {
    }
}
