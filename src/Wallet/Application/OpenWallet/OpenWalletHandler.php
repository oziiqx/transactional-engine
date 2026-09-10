<?php

declare(strict_types=1);

namespace App\Wallet\Application\OpenWallet;

use App\Shared\Application\Command\CommandHandler;
use App\Shared\Domain\Clock\Clock;
use App\Shared\Domain\Exception\ConflictingState;
use App\Shared\Domain\ValueObject\Currency;
use App\Wallet\Domain\Wallet\Wallet;
use App\Wallet\Domain\Wallet\WalletHolderId;
use App\Wallet\Domain\Wallet\WalletId;
use App\Wallet\Domain\Wallet\Wallets;

final readonly class OpenWalletHandler implements CommandHandler
{
    public function __construct(
        private Wallets $wallets,
        private Clock $clock,
    ) {
    }

    public function __invoke(OpenWalletCommand $command): void
    {
        $currency = Currency::fromCode($command->currency);
        $holderId = WalletHolderId::fromString($command->holderId);

        if ($this->wallets->findByHolderAndCurrency($holderId, $currency) !== null) {
            throw ConflictingState::of(
                'wallet.already_exists',
                \sprintf('Holder %s already has a %s wallet.', $holderId->value, $currency->value),
                ['holderId' => $holderId->value, 'currency' => $currency->value],
            );
        }

        $this->wallets->save(Wallet::open(
            WalletId::fromString($command->walletId),
            $holderId,
            $currency,
            $this->clock->now(),
        ));
    }
}
