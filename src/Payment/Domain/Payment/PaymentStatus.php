<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

/**
 * The payment state machine.
 *
 *   PENDING ──▶ PROCESSING ──▶ COMPLETED ──▶ PARTIALLY_REFUNDED ──▶ REFUNDED
 *                   │  ▲            │                  │                 ▲
 *                   ▼  │            └──────────────────┴─────────────────┘
 *                 FAILED ──(retry)──┘
 *
 * FAILED is not terminal: a failed payment can be retried until the attempt
 * budget is spent. REFUNDED is the only terminal state.
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Pending => [self::Processing],
            self::Processing => [self::Completed, self::Failed],
            self::Failed => [self::Processing],
            self::Completed => [self::PartiallyRefunded, self::Refunded],
            self::PartiallyRefunded => [self::PartiallyRefunded, self::Refunded],
            self::Refunded => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return \in_array($target, $this->allowedNext(), true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Refunded;
    }

    /**
     * Money has changed hands and can be refunded.
     */
    public function isSettled(): bool
    {
        return match ($this) {
            self::Completed, self::PartiallyRefunded, self::Refunded => true,
            default => false,
        };
    }

    public function isRetryable(): bool
    {
        return $this === self::Failed;
    }
}
