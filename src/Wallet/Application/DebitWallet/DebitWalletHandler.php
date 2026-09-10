<?php

declare(strict_types=1);

namespace App\Wallet\Application\DebitWallet;

use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Clock\Clock;
use App\Wallet\Application\Support\MovementInput;
use App\Wallet\Domain\Wallet\Wallets;

final readonly class DebitWalletHandler implements CommandHandler
{
    public function __construct(
        private Wallets $wallets,
        private Clock $clock,
    ) {
    }

    public function __invoke(DebitWalletCommand $command): void
    {
        $input = MovementInput::fromParts(
            $command->walletId,
            $command->amount,
            $command->currency,
            $command->reason,
            $command->reference,
            $command->idempotencyKey,
        );

        $wallet = $this->wallets->get($input->walletId);
        $wallet->debit($input->amount, $input->reason, $input->reference, $input->idempotencyKey, $this->clock->now());

        $this->wallets->save($wallet);
    }
}
