<?php

declare(strict_types=1);

use App\Payment\Domain\Event\PaymentCompleted;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\PaymentInitiated;
use App\Payment\Domain\Exception\PaymentTransitionRejected;
use App\Payment\Domain\Exception\RefundExceedsCapturedAmount;
use App\Payment\Domain\Payment\FailureReason;
use App\Payment\Domain\Payment\PayerId;
use App\Payment\Domain\Payment\Payment;
use App\Payment\Domain\Payment\PaymentId;
use App\Payment\Domain\Payment\PaymentMethod;
use App\Payment\Domain\Payment\PaymentStatus;
use App\Payment\Domain\Payment\RefundId;
use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\IdempotencyKey;
use App\Shared\Domain\ValueObject\Money;

const PAYMENT_MAX_ATTEMPTS = 3;

function pendingPayment(int $minor = 5_000): Payment
{
    $payment = Payment::initiate(
        PaymentId::generate(),
        IdempotencyKey::fromString('idem-' . bin2hex(random_bytes(6))),
        'order-42',
        PayerId::generate(),
        money($minor),
        PaymentMethod::Card,
        at(),
    );
    $payment->pullDomainEvents();

    return $payment;
}

function processingPayment(int $minor = 5_000): Payment
{
    $payment = pendingPayment($minor);
    $payment->beginProcessing(PAYMENT_MAX_ATTEMPTS, at());
    $payment->pullDomainEvents();

    return $payment;
}

function completedPayment(int $minor = 5_000): Payment
{
    $payment = processingPayment($minor);
    $payment->complete('gw-ok-1', at());
    $payment->pullDomainEvents();

    return $payment;
}

describe('initiation', function (): void {
    it('starts pending and records the event', function (): void {
        $payment = Payment::initiate(
            PaymentId::generate(),
            IdempotencyKey::fromString('idem-key-abc123'),
            'order-1',
            PayerId::generate(),
            money(9_900),
            PaymentMethod::Card,
            at(),
        );

        expect($payment->status())->toBe(PaymentStatus::Pending)
            ->and($payment->capturedAmount())->toEqualMoney(money(0))
            ->and($payment->pullDomainEvents()[0])->toBeInstanceOf(PaymentInitiated::class);
    });

    it('rejects a non-positive amount', function (): void {
        Payment::initiate(
            PaymentId::generate(),
            IdempotencyKey::fromString('idem-key-abc123'),
            'order-1',
            PayerId::generate(),
            money(0),
            PaymentMethod::Card,
            at(),
        );
    })->throws(InvariantViolation::class);
});

describe('state machine', function (): void {
    it('moves pending -> processing -> completed', function (): void {
        $payment = pendingPayment();

        $payment->beginProcessing(PAYMENT_MAX_ATTEMPTS, at());
        expect($payment->status())->toBe(PaymentStatus::Processing);

        $payment->complete('gw-123', at());
        expect($payment->status())->toBe(PaymentStatus::Completed)
            ->and($payment->capturedAmount())->toEqualMoney(money(5_000))
            ->and($payment->attemptCount())->toBe(1)
            ->and($payment->pullDomainEvents()[1] ?? null)->toBeInstanceOf(PaymentCompleted::class);
    });

    it('cannot complete a payment that never started processing', function (): void {
        pendingPayment()->complete('gw-123', at());
    })->throws(PaymentTransitionRejected::class);

    it('records a failed attempt and allows a retry', function (): void {
        $payment = processingPayment();

        $payment->fail(FailureReason::retryable('timeout', 'Gateway timed out'), PAYMENT_MAX_ATTEMPTS, at());
        expect($payment->status())->toBe(PaymentStatus::Failed)
            ->and($payment->attemptCount())->toBe(1);

        $failed = $payment->pullDomainEvents()[0];
        expect($failed)->toBeInstanceOf(PaymentFailed::class)
            ->and($failed->retryable)->toBeTrue()
            ->and($failed->isDeadLettered())->toBeFalse();

        $payment->beginProcessing(PAYMENT_MAX_ATTEMPTS, at());
        expect($payment->status())->toBe(PaymentStatus::Processing);
    });

    it('stops retrying once the attempt budget is spent', function (): void {
        $payment = processingPayment();

        foreach (range(1, PAYMENT_MAX_ATTEMPTS) as $n) {
            $payment->fail(FailureReason::retryable('decline', 'Temporarily declined'), PAYMENT_MAX_ATTEMPTS, at());
            if ($n < PAYMENT_MAX_ATTEMPTS) {
                $payment->beginProcessing(PAYMENT_MAX_ATTEMPTS, at());
            }
        }

        expect($payment->attemptCount())->toBe(PAYMENT_MAX_ATTEMPTS);
        $payment->beginProcessing(PAYMENT_MAX_ATTEMPTS, at());
    })->throws(PaymentTransitionRejected::class);

    it('does not retry a permanent failure', function (): void {
        $payment = processingPayment();
        $payment->fail(FailureReason::permanent('stolen_card', 'Card reported stolen'), PAYMENT_MAX_ATTEMPTS, at());

        $payment->beginProcessing(PAYMENT_MAX_ATTEMPTS, at());
    })->throws(PaymentTransitionRejected::class);
});

describe('refunds', function (): void {
    it('fully refunds a completed payment', function (): void {
        $payment = completedPayment(4_000);

        $payment->refund(RefundId::generate(), money(4_000), 'customer_request', 'gw-rf-1', at());

        expect($payment->status())->toBe(PaymentStatus::Refunded)
            ->and($payment->refundableAmount())->toEqualMoney(money(0))
            ->and($payment->refunds())->toHaveCount(1);
    });

    it('tracks partial refunds and settles on the last one', function (): void {
        $payment = completedPayment(1_000);

        $payment->refund(RefundId::generate(), money(300), 'partial', null, at());
        expect($payment->status())->toBe(PaymentStatus::PartiallyRefunded)
            ->and($payment->refundableAmount())->toEqualMoney(money(700));

        $payment->refund(RefundId::generate(), money(700), 'rest', null, at());
        expect($payment->status())->toBe(PaymentStatus::Refunded);
    });

    it('refuses a refund larger than the refundable amount', function (): void {
        completedPayment(1_000)->refund(RefundId::generate(), money(1_001), 'too_much', null, at());
    })->throws(RefundExceedsCapturedAmount::class);

    it('refuses to refund a payment that did not settle', function (): void {
        $payment = processingPayment();
        $payment->fail(FailureReason::permanent('lost', 'Lost'), PAYMENT_MAX_ATTEMPTS, at());

        $payment->refund(RefundId::generate(), money(10), 'nope', null, at());
    })->throws(PaymentTransitionRejected::class);

    it('refuses a refund in a different currency', function (): void {
        completedPayment(1_000)->refund(
            RefundId::generate(),
            Money::of(100, Currency::USD),
            'wrong_ccy',
            null,
            at(),
        );
    })->throws(InvariantViolation::class);
});
