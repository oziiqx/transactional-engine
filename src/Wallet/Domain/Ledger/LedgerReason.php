<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Ledger;

/**
 * The business reason a ledger entry exists. Drives reporting and tells
 * reconciliation which entries it is allowed to generate.
 */
enum LedgerReason: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case PaymentCapture = 'payment_capture';
    case PaymentRefund = 'payment_refund';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Fee = 'fee';
    case ReconciliationAdjustment = 'reconciliation_adjustment';

    public function isSystemGenerated(): bool
    {
        return $this === self::ReconciliationAdjustment;
    }

    /**
     * Reasons a client is allowed to attach to a movement it requests. The
     * system keeps {@see ReconciliationAdjustment} to itself.
     *
     * @return non-empty-list<non-empty-string>
     */
    public static function clientAssignable(): array
    {
        $values = [];
        foreach (self::cases() as $reason) {
            if (! $reason->isSystemGenerated()) {
                $values[] = $reason->value;
            }
        }

        \assert($values !== []);

        return $values;
    }
}
