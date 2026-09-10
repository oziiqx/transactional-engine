<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Application\Query\QueryBus;
use App\Wallet\Application\GetWallet\GetWalletQuery;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletResource;

/**
 * Serves `GET /api/wallets/{id}` by asking the read side. A missing wallet throws
 * {@see \App\Shared\Domain\Exception\NotFound}, which the problem-details listener
 * renders as 404.
 *
 * @implements ProviderInterface<WalletResource>
 */
final readonly class WalletProvider implements ProviderInterface
{
    public function __construct(
        private QueryBus $queryBus
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WalletResource
    {
        $walletId = (string) ($uriVariables['id'] ?? '');

        return WalletResource::fromView($this->queryBus->ask(new GetWalletQuery($walletId)));
    }
}
