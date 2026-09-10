<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Wallet;

use App\Shared\Domain\Exception\NotFound;
use App\Shared\Domain\ValueObject\Currency;

/**
 * The collection of wallets, from the domain's point of view. The Doctrine
 * implementation lives in the infrastructure layer.
 */
interface Wallets
{
    public function nextIdentity(): WalletId;

    /**
     * @throws NotFound when no wallet has that id
     */
    public function get(WalletId $id): Wallet;

    /**
     * A holder has at most one wallet per currency.
     */
    public function findByHolderAndCurrency(WalletHolderId $holderId, Currency $currency): ?Wallet;

    /**
     * Persist the aggregate and every ledger entry it produced in this unit of work.
     */
    public function save(Wallet $wallet): void;
}
