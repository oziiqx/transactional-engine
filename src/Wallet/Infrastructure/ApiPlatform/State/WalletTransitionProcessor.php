<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use App\Wallet\Application\ChangeLifecycle\ChangeWalletLifecycleCommand;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletResource;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletTransitionResource;

/**
 * `POST /api/wallets/{walletId}/transitions` — freeze / unfreeze / close.
 */
final readonly class WalletTransitionProcessor extends WalletWriteProcessor
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WalletResource
    {
        \assert($data instanceof WalletTransitionResource);

        $walletId = $this->walletIdFrom($uriVariables);

        $this->commandBus->dispatch(new ChangeWalletLifecycleCommand($walletId, (string) $data->action));

        return $this->representationOf($walletId);
    }
}
