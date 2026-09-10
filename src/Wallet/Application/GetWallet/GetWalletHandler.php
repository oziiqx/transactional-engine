<?php

declare(strict_types=1);

namespace App\Wallet\Application\GetWallet;

use App\Shared\Application\Query\QueryHandler;
use App\Shared\Domain\Exception\NotFound;
use App\Wallet\Application\ReadModel\WalletReadModel;
use App\Wallet\Application\ReadModel\WalletView;
use App\Wallet\Domain\Wallet\WalletId;

final readonly class GetWalletHandler implements QueryHandler
{
    public function __construct(
        private WalletReadModel $readModel
    ) {
    }

    public function __invoke(GetWalletQuery $query): WalletView
    {
        return $this->readModel->find(WalletId::fromString($query->walletId))
            ?? throw NotFound::of('Wallet', $query->walletId);
    }
}
