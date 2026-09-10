<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

use App\Shared\Domain\Exception\NotFound;
use App\Shared\Domain\ValueObject\IdempotencyKey;

/**
 * The collection of payments. The Doctrine implementation is in infrastructure.
 */
interface Payments
{
    public function nextIdentity(): PaymentId;

    public function nextRefundIdentity(): RefundId;

    /**
     * @throws NotFound when no payment has that id
     */
    public function get(PaymentId $id): Payment;

    /**
     * Resolve a payment by the caller's idempotency key, if one was already
     * created under it.
     */
    public function findByIdempotencyKey(IdempotencyKey $key): ?Payment;

    public function save(Payment $payment): void;
}
