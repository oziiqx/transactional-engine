<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\Resource;

/**
 * Output shape for `GET /api/wallets/{id}/ledger-entries`: a cursor-paginated
 * page of history with `nextCursor` carried in the body next to the entries.
 */
final class LedgerPageResource
{
    /**
     * @param list<LedgerEntryResource> $items
     */
    public function __construct(
        public array $items = [],
        public ?string $nextCursor = null,
        public bool $hasMore = false,
    ) {
    }
}
