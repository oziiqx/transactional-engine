<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use App\Wallet\Application\OpenWallet\OpenWalletCommand;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletResource;
use Symfony\Component\Uid\Uuid;

/**
 * `POST /api/wallets` — opens a wallet. The id may be supplied by the client
 * (making the call idempotent) or generated here.
 */
final readonly class OpenWalletProcessor extends WalletWriteProcessor
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WalletResource
    {
        \assert($data instanceof WalletResource);

        $walletId = $data->id ?? Uuid::v7()->toRfc4122();

        $this->commandBus->dispatch(new OpenWalletCommand(
            $walletId,
            (string) $data->holderId,
            (string) $data->currency,
        ));

        return $this->representationOf($walletId);
    }
}
