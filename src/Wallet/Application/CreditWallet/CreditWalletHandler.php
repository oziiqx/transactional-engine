<?php

declare(strict_types=1);

namespace App\Wallet\Application\CreditWallet;

use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Clock\Clock;
use App\Wallet\Application\Support\MovementInput;
use App\Wallet\Domain\Wallet\Wallets;

final readonly class CreditWalletHandler implements CommandHandler
{
    public function __construct(
        private Wallets $wallets,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreditWalletCommand $command): void
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
        $wallet->credit($input->amount, $input->reason, $input->reference, $input->idempotencyKey, $this->clock->now());

        $this->wallets->save($wallet);
    }
}
