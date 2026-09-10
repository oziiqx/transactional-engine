<?php

declare(strict_types=1);

namespace App\Payment\Domain\Exception;

use App\Payment\Domain\Payment\PaymentId;
use App\Payment\Domain\Payment\PaymentStatus;
use App\Shared\Domain\Exception\DomainException;

/**
 * An operation asked the payment to move to a state its machine does not allow
 * from where it is (e.g. completing a payment that was never processed, refunding
 * one that failed). Maps to HTTP 409 Conflict.
 */
final class PaymentTransitionRejected extends DomainException
{
    private function __construct(
        string $message,
        private readonly PaymentId $paymentId,
        private readonly PaymentStatus $from,
        private readonly string $action,
    ) {
        parent::__construct($message);
    }

    public static function of(PaymentId $paymentId, PaymentStatus $from, string $action): self
    {
        return new self(
            \sprintf('Payment %s cannot "%s" from state "%s".', $paymentId->value, $action, $from->value),
            $paymentId,
            $from,
            $action,
        );
    }

    public static function forRefund(PaymentId $paymentId, PaymentStatus $from): self
    {
        return self::of($paymentId, $from, 'refund');
    }

    public function errorCode(): string
    {
        return 'payment.transition_rejected';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function context(): array
    {
        return [
            'paymentId' => $this->paymentId->value,
            'state' => $this->from->value,
            'action' => $this->action,
        ];
    }
}
