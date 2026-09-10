<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Wallet;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\Exception\ConflictingState;
use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\IdempotencyKey;
use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Domain\Event\WalletClosed;
use App\Wallet\Domain\Event\WalletCredited;
use App\Wallet\Domain\Event\WalletDebited;
use App\Wallet\Domain\Event\WalletFrozen;
use App\Wallet\Domain\Event\WalletOpened;
use App\Wallet\Domain\Event\WalletReconciled;
use App\Wallet\Domain\Event\WalletUnfrozen;
use App\Wallet\Domain\Exception\InsufficientFunds;
use App\Wallet\Domain\Exception\WalletCurrencyMismatch;
use App\Wallet\Domain\Exception\WalletNotActive;
use App\Wallet\Domain\Ledger\LedgerDirection;
use App\Wallet\Domain\Ledger\LedgerEntry;
use App\Wallet\Domain\Ledger\LedgerEntryId;
use App\Wallet\Domain\Ledger\LedgerReason;
use DateTimeImmutable;

/**
 * The consistency boundary for a single holder's balance in a single currency.
 *
 * Invariants the aggregate guarantees:
 *  - the balance equals the fold of every ledger entry ever posted;
 *  - the balance never goes negative through a normal debit (no overdraft);
 *  - money only moves while the wallet is ACTIVE;
 *  - the wallet only closes at a zero balance;
 *  - ledger sequence numbers are gap-free and monotonic.
 *
 * The aggregate keeps a running balance and the entries created in the current
 * unit of work; the full history is a read concern and lives in a projection.
 */
final class Wallet extends AggregateRoot
{
    /**
     * @var list<LedgerEntry>
     */
    private array $uncommittedEntries = [];

    private function __construct(
        private readonly WalletId $id,
        private readonly WalletHolderId $holderId,
        private readonly Currency $currency,
        private Money $balance,
        private WalletStatus $status,
        private int $lastSequence,
        private readonly DateTimeImmutable $openedAt,
        private ?DateTimeImmutable $closedAt,
    ) {
    }

    public static function open(
        WalletId $id,
        WalletHolderId $holderId,
        Currency $currency,
        DateTimeImmutable $at,
    ): self {
        $wallet = new self(
            $id,
            $holderId,
            $currency,
            Money::zero($currency),
            WalletStatus::Active,
            0,
            $at,
            null,
        );

        $wallet->recordThat(new WalletOpened($id->value, $holderId->value, $currency->value, $at));

        return $wallet;
    }

    /**
     * @param non-empty-string|null $reference
     *
     * @throws WalletNotActive
     * @throws WalletCurrencyMismatch
     * @throws InvariantViolation
     */
    public function credit(
        Money $amount,
        LedgerReason $reason,
        ?string $reference,
        ?IdempotencyKey $idempotencyKey,
        DateTimeImmutable $at,
    ): LedgerEntry {
        $this->guardActive();
        $this->guardCurrency($amount);
        $this->guardPositive($amount);

        $entry = $this->appendEntry(LedgerDirection::Credit, $amount, $reason, $reference, $idempotencyKey?->value, $at);

        $this->recordThat(new WalletCredited(
            $this->id->value,
            $entry->id()->value,
            $entry->sequence(),
            $amount->minorAmount,
            $this->currency->value,
            $this->balance->minorAmount,
            $reason->value,
            $reference,
            $at,
        ));

        return $entry;
    }

    /**
     * @param non-empty-string|null $reference
     *
     * @throws WalletNotActive
     * @throws WalletCurrencyMismatch
     * @throws InsufficientFunds
     * @throws InvariantViolation
     */
    public function debit(
        Money $amount,
        LedgerReason $reason,
        ?string $reference,
        ?IdempotencyKey $idempotencyKey,
        DateTimeImmutable $at,
    ): LedgerEntry {
        $this->guardActive();
        $this->guardCurrency($amount);
        $this->guardPositive($amount);

        if ($amount->greaterThan($this->balance)) {
            throw InsufficientFunds::forDebit($this->id, $this->balance, $amount);
        }

        $entry = $this->appendEntry(LedgerDirection::Debit, $amount, $reason, $reference, $idempotencyKey?->value, $at);

        $this->recordThat(new WalletDebited(
            $this->id->value,
            $entry->id()->value,
            $entry->sequence(),
            $amount->minorAmount,
            $this->currency->value,
            $this->balance->minorAmount,
            $reason->value,
            $reference,
            $at,
        ));

        return $entry;
    }

    /**
     * Bring the wallet in line with an externally authoritative balance,
     * appending a system adjustment entry when they differ. Works on frozen
     * wallets; refuses closed ones.
     *
     * @throws WalletNotActive
     * @throws WalletCurrencyMismatch
     */
    public function reconcileAgainst(Money $authoritativeBalance, DateTimeImmutable $at): ?LedgerEntry
    {
        if ($this->status->isTerminal()) {
            throw WalletNotActive::forLifecycleChange($this->id, $this->status);
        }

        $this->guardCurrency($authoritativeBalance);

        $previousBalance = $this->balance;

        if ($authoritativeBalance->equals($previousBalance)) {
            $this->recordThat(new WalletReconciled(
                $this->id->value,
                $previousBalance->minorAmount,
                $authoritativeBalance->minorAmount,
                0,
                $this->currency->value,
                $at,
            ));

            return null;
        }

        $discrepancy = $authoritativeBalance->subtract($previousBalance);
        $direction = $discrepancy->isPositive() ? LedgerDirection::Credit : LedgerDirection::Debit;

        $entry = $this->appendEntry(
            $direction,
            $discrepancy->absolute(),
            LedgerReason::ReconciliationAdjustment,
            'reconciliation',
            null,
            $at,
        );

        $this->recordThat(new WalletReconciled(
            $this->id->value,
            $previousBalance->minorAmount,
            $authoritativeBalance->minorAmount,
            $discrepancy->minorAmount,
            $this->currency->value,
            $at,
        ));

        return $entry;
    }

    /**
     * @throws WalletNotActive
     */
    public function freeze(DateTimeImmutable $at): void
    {
        if ($this->status === WalletStatus::Frozen) {
            return;
        }

        if ($this->status->isTerminal()) {
            throw WalletNotActive::forLifecycleChange($this->id, $this->status);
        }

        $this->status = WalletStatus::Frozen;
        $this->recordThat(new WalletFrozen($this->id->value, $at));
    }

    /**
     * @throws WalletNotActive
     */
    public function unfreeze(DateTimeImmutable $at): void
    {
        if ($this->status === WalletStatus::Active) {
            return;
        }

        if ($this->status->isTerminal()) {
            throw WalletNotActive::forLifecycleChange($this->id, $this->status);
        }

        $this->status = WalletStatus::Active;
        $this->recordThat(new WalletUnfrozen($this->id->value, $at));
    }

    /**
     * @throws ConflictingState when the balance is not zero
     */
    public function close(DateTimeImmutable $at): void
    {
        if ($this->status->isTerminal()) {
            return;
        }

        if (!$this->balance->isZero()) {
            throw ConflictingState::of(
                'wallet.non_zero_balance',
                \sprintf('Wallet %s cannot be closed while it holds %s.', $this->id->value, $this->balance),
                ['walletId' => $this->id->value, 'balance' => $this->balance->toDecimalString()],
            );
        }

        $this->status = WalletStatus::Closed;
        $this->closedAt = $at;
        $this->recordThat(new WalletClosed($this->id->value, $at));
    }

    public function id(): WalletId
    {
        return $this->id;
    }

    public function holderId(): WalletHolderId
    {
        return $this->holderId;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function balance(): Money
    {
        return $this->balance;
    }

    public function status(): WalletStatus
    {
        return $this->status;
    }

    public function lastSequence(): int
    {
        return $this->lastSequence;
    }

    public function openedAt(): DateTimeImmutable
    {
        return $this->openedAt;
    }

    public function closedAt(): ?DateTimeImmutable
    {
        return $this->closedAt;
    }

    /**
     * Hand the persistence adapter the ledger rows created in this unit of work
     * and clear the internal buffer.
     *
     * @return list<LedgerEntry>
     */
    public function releaseUncommittedEntries(): array
    {
        $entries = $this->uncommittedEntries;
        $this->uncommittedEntries = [];

        return $entries;
    }

    /**
     * @param non-empty-string|null $reference
     * @param non-empty-string|null $idempotencyKey
     */
    private function appendEntry(
        LedgerDirection $direction,
        Money $amount,
        LedgerReason $reason,
        ?string $reference,
        ?string $idempotencyKey,
        DateTimeImmutable $at,
    ): LedgerEntry {
        $signedAmount = $direction === LedgerDirection::Credit ? $amount : $amount->negated();
        $newBalance = $this->balance->add($signedAmount);

        $entry = LedgerEntry::post(
            LedgerEntryId::generate(),
            $this->id,
            $this->nextSequence(),
            $direction,
            $amount,
            $newBalance,
            $reason,
            $reference,
            $idempotencyKey,
            $at,
        );

        $this->balance = $newBalance;
        $this->uncommittedEntries[] = $entry;

        return $entry;
    }

    /**
     * @return positive-int
     */
    private function nextSequence(): int
    {
        /** @var positive-int */
        return ++$this->lastSequence;
    }

    /**
     * @throws WalletNotActive
     */
    private function guardActive(): void
    {
        if (!$this->status->allowsMovement()) {
            throw WalletNotActive::forMovement($this->id, $this->status);
        }
    }

    /**
     * @throws WalletCurrencyMismatch
     */
    private function guardCurrency(Money $amount): void
    {
        if ($amount->currency !== $this->currency) {
            throw WalletCurrencyMismatch::of($this->id, $this->currency, $amount->currency);
        }
    }

    /**
     * @throws InvariantViolation
     */
    private function guardPositive(Money $amount): void
    {
        if (!$amount->isPositive()) {
            throw InvariantViolation::of(
                'wallet.non_positive_amount',
                'A ledger movement must be a strictly positive amount.',
            );
        }
    }
}
