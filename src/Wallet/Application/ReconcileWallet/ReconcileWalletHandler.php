<?php

declare(strict_types=1);

namespace App\Wallet\Application\ReconcileWallet;

use App\Shared\Application\Command\CommandHandler;
use App\Shared\Application\Lock\LockFactory;
use App\Shared\Domain\Clock\Clock;
use App\Wallet\Application\Port\ReconciliationSource;
use App\Wallet\Domain\Wallet\WalletId;
use App\Wallet\Domain\Wallet\Wallets;

/**
 * Reconciles one wallet under a per-wallet distributed lock, so a scheduled batch
 * and an on-demand request can never run a pass for the same wallet at once.
 */
final readonly class ReconcileWalletHandler implements CommandHandler
{
    public function __construct(
        private Wallets $wallets,
        private ReconciliationSource $source,
        private LockFactory $lockFactory,
        private Clock $clock,
    ) {
    }

    public function __invoke(ReconcileWalletCommand $command): void
    {
        $walletId = WalletId::fromString($command->walletId);

        $this->lockFactory
            ->create(\sprintf('wallet.reconcile.%s', $walletId->value), ttlSeconds: 120.0)
            ->whileHeld(function () use ($walletId): void {
                $wallet = $this->wallets->get($walletId);

                $wallet->reconcileAgainst(
                    $this->source->authoritativeBalanceFor($walletId, $wallet->currency()),
                    $this->clock->now(),
                );

                $this->wallets->save($wallet);
            });
    }
}
