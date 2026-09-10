<?php

declare(strict_types=1);

namespace App\Wallet\Application\Support;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\IdempotencyKey;
use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Domain\Ledger\LedgerReason;
use App\Wallet\Domain\Wallet\WalletId;

/**
 * Turns the primitive fields of a credit/debit command into the value objects the
 * {@see \App\Wallet\Domain\Wallet\Wallet} aggregate expects. Keeps that mapping in
 * one place instead of duplicated across the two handlers.
 *
 * @psalm-immutable
 */
final readonly class MovementInput
{
    /**
     * @param non-empty-string|null $reference
     */
    private function __construct(
        public WalletId $walletId,
        public Money $amount,
        public LedgerReason $reason,
        public ?string $reference,
        public ?IdempotencyKey $idempotencyKey,
    ) {
    }

    /**
     * @param non-empty-string $walletId
     * @param numeric-string   $amount
     * @param non-empty-string $currency
     * @param non-empty-string $reason
     */
    public static function fromParts(
        string $walletId,
        string $amount,
        string $currency,
        string $reason,
        ?string $reference,
        ?string $idempotencyKey,
    ): self {
        return new self(
            WalletId::fromString($walletId),
            Money::fromDecimalString($amount, Currency::fromCode($currency)),
            LedgerReason::from($reason),
            self::blankToNull($reference),
            ($token = self::blankToNull($idempotencyKey)) !== null
                ? IdempotencyKey::fromString($token)
                : null,
        );
    }

    /**
     * @return non-empty-string|null
     */
    private static function blankToNull(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }
}
