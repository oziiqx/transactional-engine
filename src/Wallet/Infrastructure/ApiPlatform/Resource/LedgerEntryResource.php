<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\Resource;

/**
 * Representation of one ledger entry. Only ever returned inside a
 * {@see LedgerPageResource}; it has no operations of its own.
 */
final class LedgerEntryResource
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
