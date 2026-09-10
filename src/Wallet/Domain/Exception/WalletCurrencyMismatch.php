<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\ValueObject\Currency;
use App\Wallet\Domain\Wallet\WalletId;

/**
 * An amount in one currency was posted to a wallet denominated in another.
 * Conversion is an application concern and must happen before the movement.
 */
final class WalletCurrencyMismatch extends DomainException
{
    private function __construct(
        private readonly WalletId $walletId,
        private readonly Currency $walletCurrency,
        private readonly Currency $amountCurrency,
    ) {
        parent::__construct(\sprintf(
            'Wallet %s is denominated in %s; a %s amount cannot be posted to it.',
            $walletId->value,
            $walletCurrency->value,
            $amountCurrency->value,
        ));
    }

    public static function of(WalletId $walletId, Currency $walletCurrency, Currency $amountCurrency): self
    {
        return new self($walletId, $walletCurrency, $amountCurrency);
    }

    public function errorCode(): string
    {
        return 'wallet.currency_mismatch';
    }

    public function context(): array
    {
        return [
            'walletId' => $this->walletId->value,
            'walletCurrency' => $this->walletCurrency->value,
            'amountCurrency' => $this->amountCurrency->value,
        ];
    }
}
