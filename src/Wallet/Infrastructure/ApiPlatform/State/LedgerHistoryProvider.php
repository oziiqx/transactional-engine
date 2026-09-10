<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Application\Query\QueryBus;
use App\Wallet\Application\ListLedgerEntries\ListLedgerEntriesQuery;
use App\Wallet\Application\ReadModel\LedgerEntryView;
use App\Wallet\Infrastructure\ApiPlatform\Resource\LedgerEntryResource;
use App\Wallet\Infrastructure\ApiPlatform\Resource\LedgerPageResource;

/**
 * Serves `GET /api/wallets/{walletId}/ledger-entries?limit=&cursor=`.
 *
 * @implements ProviderInterface<LedgerPageResource>
 */
final readonly class LedgerHistoryProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus $queryBus
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LedgerPageResource
    {
        $walletId = (string) ($uriVariables['id'] ?? $uriVariables['walletId'] ?? '');

        /** @var array<string, string> $filters */
        $filters = \is_array($context['filters'] ?? null) ? $context['filters'] : [];

        $limit = (int) ($filters['limit'] ?? 25);
        $cursor = ($filters['cursor'] ?? '') !== '' ? (string) $filters['cursor'] : null;

        $page = $this->queryBus->ask(new ListLedgerEntriesQuery($walletId, $limit, $cursor));

        return new LedgerPageResource(
            items: array_map(
                static fn (LedgerEntryView $view): LedgerEntryResource => new LedgerEntryResource(
                    id: $view->id,
                    sequence: $view->sequence,
                    direction: $view->direction,
                    amount: $view->amount,
                    balanceAfter: $view->balanceAfter,
                    currency: $view->currency,
                    reason: $view->reason,
                    reference: $view->reference,
                    recordedAt: $view->recordedAt,
                ),
                $page->items,
            ),
            nextCursor: $page->nextCursor,
            hasMore: $page->hasMore(),
        );
    }
}
