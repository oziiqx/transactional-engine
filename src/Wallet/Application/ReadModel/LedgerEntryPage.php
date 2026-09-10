<?php

declare(strict_types=1);

namespace App\Wallet\Application\ReadModel;

/**
 * One page of ledger history, newest entry first, with an opaque forward cursor.
 *
 * @psalm-immutable
 */
final readonly class LedgerEntryPage
{
    /**
     * @param list<LedgerEntryView> $items
     * @param string|null $nextCursor pass back as `cursor` for the next page
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }

    public static function empty(): self
    {
        return new self([], null);
    }
}
