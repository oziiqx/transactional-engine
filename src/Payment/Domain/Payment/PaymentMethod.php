<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

enum PaymentMethod: string
{
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case WalletBalance = 'wallet_balance';

    /**
     * Whether settlement for this method is expected to be immediate once the
     * processor accepts it.
     */
    public function settlesSynchronously(): bool
    {
        return $this !== self::BankTransfer;
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
