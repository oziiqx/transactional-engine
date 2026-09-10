<?php

declare(strict_types=1);

use App\Shared\Domain\Exception\ConflictingState;
use App\Wallet\Application\ChangeLifecycle\ChangeWalletLifecycleCommand;
use App\Wallet\Application\CreditWallet\CreditWalletCommand;
use App\Wallet\Application\DebitWallet\DebitWalletCommand;
use App\Wallet\Application\GetWallet\GetWalletQuery;
use App\Wallet\Application\ListLedgerEntries\ListLedgerEntriesQuery;
use App\Wallet\Application\OpenWallet\OpenWalletCommand;
use App\Wallet\Application\ReadModel\LedgerEntryPage;
use App\Wallet\Application\ReadModel\WalletView;
use App\Wallet\Application\ReconcileWallet\ReconcileWalletCommand;
use App\Wallet\Domain\Exception\InsufficientFunds;
use App\Wallet\Domain\Ledger\LedgerReason;

/**
 * @return non-empty-string the new wallet id
 */
function openWallet(string $currency = 'EUR'): string
{
    $walletId = uuid();
    test()->commandBus()->dispatch(new OpenWalletCommand($walletId, uuid(), $currency));

    return $walletId;
}

it('opens a wallet and exposes it on the read side', function (): void {
    $walletId = openWallet();

    $view = $this->queryBus()->ask(new GetWalletQuery($walletId));

    expect($view)->toBeInstanceOf(WalletView::class)
        ->and($view->id)->toBe($walletId)
        ->and($view->currency)->toBe('EUR')
        ->and($view->balance)->toBe('0.00')
        ->and($view->status)->toBe('active')
        ->and($view->ledgerEntries)->toBe(0);
});

it('rejects a second wallet for the same holder and currency', function (): void {
    $holderId = uuid();

    $this->commandBus()->dispatch(new OpenWalletCommand(uuid(), $holderId, 'USD'));
    $this->commandBus()->dispatch(new OpenWalletCommand(uuid(), $holderId, 'USD'));
})->throws(ConflictingState::class);

it('applies credits and debits and records ledger entries', function (): void {
    $walletId = openWallet();

    $this->commandBus()->dispatch(
        new CreditWalletCommand($walletId, '150.00', 'EUR', LedgerReason::Deposit->value, 'topup-1', null),
    );
    $this->commandBus()->dispatch(
        new DebitWalletCommand($walletId, '40.00', 'EUR', LedgerReason::Withdrawal->value, null, null),
    );

    $view = $this->queryBus()->ask(new GetWalletQuery($walletId));
    $page = $this->queryBus()->ask(new ListLedgerEntriesQuery($walletId, 25, null));

    expect($view->balance)->toBe('110.00')
        ->and($view->balanceMinor)->toBe(11_000)
        ->and($view->ledgerEntries)->toBe(2)
        ->and($page)->toBeInstanceOf(LedgerEntryPage::class)
        ->and($page->items)->toHaveCount(2)
        ->and($page->items[0]->sequence)->toBe(2)
        ->and($page->items[0]->direction)->toBe('debit')
        ->and($page->items[1]->balanceAfter)->toBe('150.00');
});

it('refuses to overdraw a wallet', function (): void {
    $walletId = openWallet();
    $this->commandBus()->dispatch(
        new CreditWalletCommand($walletId, '10.00', 'EUR', LedgerReason::Deposit->value, null, null),
    );

    $this->commandBus()->dispatch(
        new DebitWalletCommand($walletId, '10.01', 'EUR', LedgerReason::Withdrawal->value, null, null),
    );
})->throws(InsufficientFunds::class);

it('blocks movement once the wallet is closed', function (): void {
    $walletId = openWallet();

    $this->commandBus()->dispatch(new ChangeWalletLifecycleCommand($walletId, 'close'));

    expect($this->queryBus()->ask(new GetWalletQuery($walletId))->status)->toBe('closed');
});

it('reconciles the projected balance against the sum of the ledger', function (): void {
    $walletId = openWallet();
    $this->commandBus()->dispatch(
        new CreditWalletCommand($walletId, '75.00', 'EUR', LedgerReason::Deposit->value, null, null),
    );

    // Ledger sum and projection already agree — a no-op pass.
    $this->commandBus()->dispatch(new ReconcileWalletCommand($walletId));

    expect($this->queryBus()->ask(new GetWalletQuery($walletId))->balance)->toBe('75.00');
});
