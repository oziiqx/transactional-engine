<?php

declare(strict_types=1);

namespace App\Payment\Domain\Exception;

use App\Payment\Domain\Payment\PaymentId;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\ValueObject\Money;

/**
 * A refund would return more than what is still refundable on the payment
 * (captured amount minus what has already been refunded). Maps to HTTP 422.
 */
final class RefundExceedsCapturedAmount extends DomainException
{
    private function __construct(
        private readonly PaymentId $paymentId,
        private readonly Money $refundable,
        private readonly Money $requested,
    ) {
        parent::__construct(\sprintf(
            'Payment %s has %s left to refund but %s was requested.',
            $paymentId->value,
            $refundable,
            $requested,
        ));
    }

    public static function of(PaymentId $paymentId, Money $refundable, Money $requested): self
    {
        return new self($paymentId, $refundable, $requested);
    }

    public function errorCode(): string
    {
        return 'payment.refund_exceeds_captured';
    }

    public function context(): array
    {
        return [
            'paymentId' => $this->paymentId->value,
            'refundable' => $this->refundable->toDecimalString(),
            'requested' => $this->requested->toDecimalString(),
            'currency' => $this->refundable->currency->value,
        ];
    }
}
