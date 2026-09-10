<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Command\Command;
use App\Wallet\Application\CreditWallet\CreditWalletCommand;
use App\Wallet\Application\DebitWallet\DebitWalletCommand;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletMovementResource;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletResource;

/**
 * `POST /api/wallets/{walletId}/credits` and `.../debits`. One processor, the
 * direction comes from the operation's `movement_direction` extra property.
 */
final readonly class WalletMovementProcessor extends WalletWriteProcessor
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WalletResource
    {
        \assert($data instanceof WalletMovementResource);

        $walletId = $this->walletIdFrom($uriVariables);
        $direction = $operation->getExtraProperties()['movement_direction'] ?? 'credit';

        $this->commandBus->dispatch($this->command(
            $direction === 'debit',
            $walletId,
            $data,
            $this->idempotencyKeyFrom($context),
        ));

        return $this->representationOf($walletId);
    }

    /**
     * @param non-empty-string|null $idempotencyKey
     */
    private function command(bool $isDebit, string $walletId, WalletMovementResource $data, ?string $idempotencyKey): Command
    {
        $amount = (string) $data->amount;
        $currency = (string) $data->currency;
        $reason = (string) $data->reason;

        return $isDebit
            ? new DebitWalletCommand($walletId, $amount, $currency, $reason, $data->reference, $idempotencyKey)
            : new CreditWalletCommand($walletId, $amount, $currency, $reason, $data->reference, $idempotencyKey);
    }
}
