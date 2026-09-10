<?php

declare(strict_types=1);

namespace App\Wallet\Application\ChangeLifecycle;

use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Clock\Clock;
use App\Wallet\Domain\Wallet\WalletId;
use App\Wallet\Domain\Wallet\Wallets;

final readonly class ChangeWalletLifecycleHandler implements CommandHandler
{
    public function __construct(
        private Wallets $wallets,
        private Clock $clock,
    ) {
    }

    public function __invoke(ChangeWalletLifecycleCommand $command): void
    {
        $wallet = $this->wallets->get(WalletId::fromString($command->walletId));
        $now = $this->clock->now();

        match (WalletLifecycleAction::from($command->action)) {
            WalletLifecycleAction::Freeze => $wallet->freeze($now),
            WalletLifecycleAction::Unfreeze => $wallet->unfreeze($now),
            WalletLifecycleAction::Close => $wallet->close($now),
        };

        $this->wallets->save($wallet);
    }
}
