<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Domain\Wallet\WalletId;

/**
 * A debit would take the wallet below zero. This engine does not permit
 * overdrafts. Maps to HTTP 422 Unprocessable Entity.
 */
final class InsufficientFunds extends DomainException
{
    private function __construct(
        private readonly WalletId $walletId,
        private readonly Money $balance,
        private readonly Money $requested,
    ) {
        parent::__construct(\sprintf(
            'Wallet %s holds %s but %s was requested.',
            $walletId->value,
            $balance,
            $requested,
        ));
    }

    public static function forDebit(WalletId $walletId, Money $balance, Money $requested): self
    {
        return new self($walletId, $balance, $requested);
    }

    public function errorCode(): string
    {
        return 'wallet.insufficient_funds';
    }

    public function context(): array
    {
        return [
            'walletId' => $this->walletId->value,
            'balance' => $this->balance->toDecimalString(),
            'requested' => $this->requested->toDecimalString(),
            'currency' => $this->balance->currency->value,
        ];
    }
}
