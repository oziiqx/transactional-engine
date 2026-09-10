<?php

declare(strict_types=1);

namespace App\Wallet\Application\ListLedgerEntries;

use App\Shared\Application\Query\QueryHandler;
use App\Shared\Domain\Exception\NotFound;
use App\Wallet\Application\ReadModel\LedgerEntryPage;
use App\Wallet\Application\ReadModel\WalletReadModel;
use App\Wallet\Domain\Wallet\WalletId;

final readonly class ListLedgerEntriesHandler implements QueryHandler
{
    public function __construct(
        private WalletReadModel $readModel
    ) {
    }

    public function __invoke(ListLedgerEntriesQuery $query): LedgerEntryPage
    {
        $walletId = WalletId::fromString($query->walletId);

        if ($this->readModel->find($walletId) === null) {
            throw NotFound::of('Wallet', $query->walletId);
        }

        return $this->readModel->ledgerHistory($walletId, $query->limit, $query->cursor);
    }
}
