<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use App\Wallet\Application\ReconcileWallet\ReconcileWalletCommand;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletResource;

/**
 * `POST /api/wallets/{id}/reconciliations` — run one reconciliation pass on demand
 * (the same command a scheduled batch dispatches).
 */
final readonly class ReconcileWalletProcessor extends WalletWriteProcessor
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WalletResource
    {
        $walletId = $this->walletIdFrom($uriVariables);

        $this->commandBus->dispatch(new ReconcileWalletCommand($walletId));

        return $this->representationOf($walletId);
    }
}
