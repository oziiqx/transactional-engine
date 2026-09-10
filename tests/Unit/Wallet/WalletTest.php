<?php

declare(strict_types=1);

use App\Shared\Domain\Exception\ConflictingState;
use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Domain\Event\WalletClosed;
use App\Wallet\Domain\Event\WalletCredited;
use App\Wallet\Domain\Event\WalletOpened;
use App\Wallet\Domain\Event\WalletReconciled;
use App\Wallet\Domain\Exception\InsufficientFunds;
use App\Wallet\Domain\Exception\WalletCurrencyMismatch;
use App\Wallet\Domain\Exception\WalletNotActive;
use App\Wallet\Domain\Ledger\LedgerDirection;
use App\Wallet\Domain\Ledger\LedgerReason;
use App\Wallet\Domain\Wallet\Wallet;
use App\Wallet\Domain\Wallet\WalletHolderId;
use App\Wallet\Domain\Wallet\WalletId;
use App\Wallet\Domain\Wallet\WalletStatus;

function freshWallet(Currency $currency = Currency::EUR): Wallet
{
    $wallet = Wallet::open(WalletId::generate(), WalletHolderId::generate(), $currency, at());
    $wallet->pullDomainEvents();

    return $wallet;
}

describe('opening', function (): void {
    it('starts active with a zero balance', function (): void {
        $wallet = Wallet::open(WalletId::generate(), WalletHolderId::generate(), Currency::EUR, at());

        expect($wallet->status())->toBe(WalletStatus::Active)
            ->and($wallet->balance())->toEqualMoney(money(0))
            ->and($wallet->lastSequence())->toBe(0);
    });

    it('drains its event buffer once pulled', function (): void {
        $wallet = Wallet::open(WalletId::generate(), WalletHolderId::generate(), Currency::EUR, at());

        expect($wallet->pullDomainEvents())->toHaveCount(1)
            ->and($wallet->pullDomainEvents())->toBe([]);
    });

    it('records a WalletOpened event', function (): void {
        $wallet = Wallet::open(WalletId::generate(), WalletHolderId::generate(), Currency::EUR, at());

        expect($wallet->pullDomainEvents()[0])->toBeInstanceOf(WalletOpened::class);
    });
});

describe('credit', function (): void {
    it('increases the balance and appends a sequenced ledger entry', function (): void {
        $wallet = freshWallet();

        $entry = $wallet->credit(money(2_500), LedgerReason::Deposit, 'dep-1', null, at());

        expect($wallet->balance())->toEqualMoney(money(2_500))
            ->and($entry->sequence())->toBe(1)
            ->and($entry->direction())->toBe(LedgerDirection::Credit)
            ->and($entry->balanceAfter())->toEqualMoney(money(2_500))
            ->and($wallet->pullDomainEvents()[0])->toBeInstanceOf(WalletCredited::class);
    });

    it('folds a sequence of movements into the balance', function (): void {
        $wallet = freshWallet();

        $wallet->credit(money(10_000), LedgerReason::Deposit, null, null, at());
        $wallet->debit(money(3_500), LedgerReason::Withdrawal, null, null, at());
        $wallet->credit(money(1_000), LedgerReason::TransferIn, null, null, at());

        $entries = $wallet->releaseUncommittedEntries();

        expect($wallet->balance())->toEqualMoney(money(7_500))
            ->and($entries)->toHaveCount(3)
            ->and(array_map(static fn ($e): int => $e->sequence(), $entries))->toBe([1, 2, 3])
            ->and($entries[2]->balanceAfter())->toEqualMoney(money(7_500));
    });

    it('rejects a non-positive amount', function (): void {
        freshWallet()->credit(money(0), LedgerReason::Deposit, null, null, at());
    })->throws(InvariantViolation::class);

    it('rejects an amount in the wrong currency', function (): void {
        freshWallet(Currency::EUR)->credit(
            Money::of(100, Currency::USD),
            LedgerReason::Deposit,
            null,
            null,
            at(),
        );
    })->throws(WalletCurrencyMismatch::class);
});

describe('debit', function (): void {
    it('refuses to overdraw the wallet', function (): void {
        $wallet = freshWallet();
        $wallet->credit(money(1_000), LedgerReason::Deposit, null, null, at());

        $wallet->debit(money(1_001), LedgerReason::Withdrawal, null, null, at());
    })->throws(InsufficientFunds::class);

    it('allows draining the wallet to exactly zero', function (): void {
        $wallet = freshWallet();
        $wallet->credit(money(1_000), LedgerReason::Deposit, null, null, at());
        $wallet->debit(money(1_000), LedgerReason::Withdrawal, null, null, at());

        expect($wallet->balance())->toEqualMoney(money(0));
    });
});

describe('lifecycle', function (): void {
    it('blocks movement while frozen and resumes after unfreeze', function (): void {
        $wallet = freshWallet();
        $wallet->freeze(at());

        expect(fn () => $wallet->credit(money(1), LedgerReason::Deposit, null, null, at()))
            ->toThrow(WalletNotActive::class);

        $wallet->unfreeze(at());
        $wallet->credit(money(1), LedgerReason::Deposit, null, null, at());

        expect($wallet->balance())->toEqualMoney(money(1));
    });

    it('treats a repeated freeze as a no-op', function (): void {
        $wallet = freshWallet();
        $wallet->freeze(at());
        $wallet->pullDomainEvents();
        $wallet->freeze(at());

        expect($wallet->pullDomainEvents())->toBe([]);
    });

    it('will not close a wallet that still holds money', function (): void {
        $wallet = freshWallet();
        $wallet->credit(money(5), LedgerReason::Deposit, null, null, at());

        $wallet->close(at());
    })->throws(ConflictingState::class);

    it('closes at a zero balance and then blocks all movement', function (): void {
        $wallet = freshWallet();
        $wallet->close(at());

        expect($wallet->status())->toBe(WalletStatus::Closed)
            ->and($wallet->pullDomainEvents()[0])->toBeInstanceOf(WalletClosed::class);

        expect(fn () => $wallet->credit(money(1), LedgerReason::Deposit, null, null, at()))
            ->toThrow(WalletNotActive::class);
    });
});

describe('reconciliation', function (): void {
    it('records an event with no adjustment when the balance already matches', function (): void {
        $wallet = freshWallet();
        $wallet->credit(money(4_200), LedgerReason::Deposit, null, null, at());
        $wallet->releaseUncommittedEntries();
        $wallet->pullDomainEvents();

        $entry = $wallet->reconcileAgainst(money(4_200), at());
        $events = $wallet->pullDomainEvents();

        expect($entry)->toBeNull()
            ->and($events[0])->toBeInstanceOf(WalletReconciled::class)
            ->and($events[0]->wasAdjusted())->toBeFalse();
    });

    it('posts a credit adjustment when the projection drifted low', function (): void {
        $wallet = freshWallet();
        $wallet->credit(money(1_000), LedgerReason::Deposit, null, null, at());

        $entry = $wallet->reconcileAgainst(money(1_250), at());

        expect($entry?->direction())->toBe(LedgerDirection::Credit)
            ->and($entry?->reason())->toBe(LedgerReason::ReconciliationAdjustment)
            ->and($wallet->balance())->toEqualMoney(money(1_250));
    });

    it('posts a debit adjustment when the projection drifted high', function (): void {
        $wallet = freshWallet();
        $wallet->credit(money(1_000), LedgerReason::Deposit, null, null, at());

        $entry = $wallet->reconcileAgainst(money(700), at());

        expect($entry?->direction())->toBe(LedgerDirection::Debit)
            ->and($wallet->balance())->toEqualMoney(money(700));
    });

    it('refuses to reconcile a closed wallet', function (): void {
        $wallet = freshWallet();
        $wallet->close(at());

        $wallet->reconcileAgainst(money(0), at());
    })->throws(WalletNotActive::class);
});
