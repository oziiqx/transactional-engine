<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Ledger;

use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Domain\Wallet\WalletId;
use DateTimeImmutable;

/**
 * One immutable line in a wallet's append-only ledger.
 *
 * Entries are never updated or deleted. A wallet's balance is defined as the fold
 * of every entry's signed amount; {@see balanceAfter} is that running total
 * materialised at write time so the read side never has to re-sum the history.
 */
final class LedgerEntry
{
    /**
     * @param positive-int      $sequence        1-based, gap-free, monotonic per wallet
     * @param non-empty-string  $amount          the movement, always a positive value
     * @param non-empty-string|null $reference   external correlation id (e.g. a payment id)
     * @param non-empty-string|null $idempotencyKey caller token that produced this entry
     */
    private function __construct(
        private readonly LedgerEntryId $id,
        private readonly WalletId $walletId,
        private readonly int $sequence,
        private readonly LedgerDirection $direction,
        private readonly Money $amount,
        private readonly Money $balanceAfter,
        private readonly LedgerReason $reason,
        private readonly ?string $reference,
        private readonly ?string $idempotencyKey,
        private readonly DateTimeImmutable $recordedAt,
    ) {
    }

    /**
     * @param positive-int          $sequence
     * @param non-empty-string|null $reference
     * @param non-empty-string|null $idempotencyKey
     */
    public static function post(
        LedgerEntryId $id,
        WalletId $walletId,
        int $sequence,
        LedgerDirection $direction,
        Money $amount,
        Money $balanceAfter,
        LedgerReason $reason,
        ?string $reference,
        ?string $idempotencyKey,
        DateTimeImmutable $recordedAt,
    ): self {
        return new self(
            $id,
            $walletId,
            $sequence,
            $direction,
            $amount,
            $balanceAfter,
            $reason,
            $reference,
            $idempotencyKey,
            $recordedAt,
        );
    }

    public function id(): LedgerEntryId
    {
        return $this->id;
    }

    public function walletId(): WalletId
    {
        return $this->walletId;
    }

    /**
     * @return positive-int
     */
    public function sequence(): int
    {
        /** @var positive-int */
        return $this->sequence;
    }

    public function direction(): LedgerDirection
    {
        return $this->direction;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    /**
     * The entry amount with its direction applied: positive for a credit,
     * negative for a debit.
     */
    public function signedAmount(): Money
    {
        return $this->direction === LedgerDirection::Credit
            ? $this->amount
            : $this->amount->negated();
    }

    public function balanceAfter(): Money
    {
        return $this->balanceAfter;
    }

    public function reason(): LedgerReason
    {
        return $this->reason;
    }

    public function reference(): ?string
    {
        return $this->reference;
    }

    public function idempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function recordedAt(): DateTimeImmutable
    {
        return $this->recordedAt;
    }
}
