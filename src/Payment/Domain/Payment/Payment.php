<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

use App\Payment\Domain\Event\PaymentCompleted;
use App\Payment\Domain\Event\PaymentFailed;
use App\Payment\Domain\Event\PaymentInitiated;
use App\Payment\Domain\Event\PaymentProcessingStarted;
use App\Payment\Domain\Event\PaymentRefunded;
use App\Payment\Domain\Exception\PaymentTransitionRejected;
use App\Payment\Domain\Exception\RefundExceedsCapturedAmount;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\IdempotencyKey;
use App\Shared\Domain\ValueObject\Money;
use DateTimeImmutable;

/**
 * An idempotent payment driven by an explicit state machine
 * ({@see PaymentStatus}). Processing happens out of band: an event dispatched on
 * {@see initiate()} is relayed to a queue, a worker calls the gateway, and the
 * outcome comes back through {@see complete()} or {@see fail()}. A failed payment
 * can be retried until its attempt budget is spent.
 *
 * Idempotency: the aggregate carries the caller's {@see IdempotencyKey}, which is
 * unique in the database. Re-issuing `initiate` with the same key resolves to the
 * original payment instead of creating a second one.
 *
 * @final Not declared `final` only so Doctrine can subclass it for lazy hydration.
 */
class Payment extends AggregateRoot
{
    /**
     * @var list<PaymentAttempt>
     */
    private array $attempts = [];

    /**
     * @var list<Refund>
     */
    private array $refunds = [];

    private int $version = 1;

    /**
     * @param non-empty-string $merchantReference
     */
    private function __construct(
        private readonly PaymentId $id,
        private readonly IdempotencyKey $idempotencyKey,
        private readonly string $merchantReference,
        private readonly PayerId $payerId,
        private readonly Money $amount,
        private readonly PaymentMethod $method,
        private PaymentStatus $status,
        private Money $capturedAmount,
        private Money $refundedAmount,
        private ?FailureReason $lastFailureReason,
        private readonly DateTimeImmutable $initiatedAt,
        private ?DateTimeImmutable $completedAt,
    ) {
    }

    /**
     * @param non-empty-string $merchantReference
     *
     * @throws InvariantViolation when the amount is not strictly positive
     */
    public static function initiate(
        PaymentId $id,
        IdempotencyKey $idempotencyKey,
        string $merchantReference,
        PayerId $payerId,
        Money $amount,
        PaymentMethod $method,
        DateTimeImmutable $at,
    ): self {
        if (! $amount->isPositive()) {
            throw InvariantViolation::of('payment.non_positive_amount', 'A payment amount must be strictly positive.');
        }

        $payment = new self(
            $id,
            $idempotencyKey,
            $merchantReference,
            $payerId,
            $amount,
            $method,
            PaymentStatus::Pending,
            Money::zero($amount->currency),
            Money::zero($amount->currency),
            null,
            $at,
            null,
        );

        $payment->recordThat(new PaymentInitiated(
            $id->value,
            $idempotencyKey->value,
            $merchantReference,
            $payerId->value,
            $amount->minorAmount,
            $amount->currency->value,
            $method->value,
            $at,
        ));

        return $payment;
    }

    /**
     * Move the payment into PROCESSING for a (first or retry) gateway call.
     *
     * @param positive-int $maxAttempts
     *
     * @throws PaymentTransitionRejected when not startable, or the retry budget is spent
     */
    public function beginProcessing(int $maxAttempts, DateTimeImmutable $at): void
    {
        if ($this->status === PaymentStatus::Failed && ! $this->canRetry($maxAttempts)) {
            throw PaymentTransitionRejected::of($this->id, $this->status, 'retry');
        }

        $this->transitionTo(PaymentStatus::Processing, 'process');
        $this->recordThat(new PaymentProcessingStarted($this->id->value, $this->nextAttemptNumber(), $at));
    }

    /**
     * @param non-empty-string $gatewayReference
     *
     * @throws PaymentTransitionRejected
     */
    public function complete(string $gatewayReference, DateTimeImmutable $at): void
    {
        $this->transitionTo(PaymentStatus::Completed, 'complete');

        $this->attempts[] = new PaymentAttempt(
            $this->nextAttemptNumber(),
            AttemptOutcome::Succeeded,
            $gatewayReference,
            null,
            $at,
        );

        $this->capturedAmount = $this->amount;
        $this->completedAt = $at;
        $this->lastFailureReason = null;

        $this->recordThat(new PaymentCompleted(
            $this->id->value,
            $this->merchantReference,
            $this->payerId->value,
            $this->amount->minorAmount,
            $this->amount->currency->value,
            $gatewayReference,
            $at,
        ));
    }

    /**
     * @param positive-int $maxAttempts
     *
     * @throws PaymentTransitionRejected
     */
    public function fail(FailureReason $reason, int $maxAttempts, DateTimeImmutable $at): void
    {
        $this->transitionTo(PaymentStatus::Failed, 'fail');

        $this->attempts[] = new PaymentAttempt(
            $this->nextAttemptNumber(),
            AttemptOutcome::Failed,
            null,
            $reason,
            $at,
        );
        $this->lastFailureReason = $reason;

        $attemptsMade = \count($this->attempts);

        $this->recordThat(new PaymentFailed(
            $this->id->value,
            $reason->code,
            $reason->message,
            $reason->retryable,
            $this->positive($attemptsMade),
            $attemptsMade >= $maxAttempts,
            $at,
        ));
    }

    /**
     * @param non-empty-string      $reason
     * @param non-empty-string|null $gatewayReference
     *
     * @throws PaymentTransitionRejected
     * @throws RefundExceedsCapturedAmount
     * @throws InvariantViolation
     */
    public function refund(
        RefundId $refundId,
        Money $amount,
        string $reason,
        ?string $gatewayReference,
        DateTimeImmutable $at,
    ): Refund {
        if (! $this->status->isSettled() || $this->status === PaymentStatus::Refunded) {
            throw PaymentTransitionRejected::forRefund($this->id, $this->status);
        }

        if ($amount->currency !== $this->amount->currency) {
            throw InvariantViolation::of('payment.refund_currency_mismatch', 'A refund must use the payment currency.');
        }

        if (! $amount->isPositive()) {
            throw InvariantViolation::of('payment.non_positive_refund', 'A refund amount must be strictly positive.');
        }

        $refundable = $this->refundableAmount();
        if ($amount->greaterThan($refundable)) {
            throw RefundExceedsCapturedAmount::of($this->id, $refundable, $amount);
        }

        $this->refundedAmount = $this->refundedAmount->add($amount);
        $fullyRefunded = $this->refundedAmount->equals($this->capturedAmount);
        $this->status = $fullyRefunded ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;

        $refund = new Refund($refundId->value, $amount, $reason, $gatewayReference, $at);
        $this->refunds[] = $refund;

        $this->recordThat(new PaymentRefunded(
            $this->id->value,
            $refundId->value,
            $amount->minorAmount,
            $amount->currency->value,
            $this->refundedAmount->minorAmount,
            $fullyRefunded,
            $at,
        ));

        return $refund;
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function idempotencyKey(): IdempotencyKey
    {
        return $this->idempotencyKey;
    }

    /**
     * @return non-empty-string
     */
    public function merchantReference(): string
    {
        return $this->merchantReference;
    }

    public function payerId(): PayerId
    {
        return $this->payerId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function method(): PaymentMethod
    {
        return $this->method;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function capturedAmount(): Money
    {
        return $this->capturedAmount;
    }

    public function refundedAmount(): Money
    {
        return $this->refundedAmount;
    }

    public function refundableAmount(): Money
    {
        return $this->capturedAmount->subtract($this->refundedAmount);
    }

    public function lastFailureReason(): ?FailureReason
    {
        return $this->lastFailureReason;
    }

    public function attemptCount(): int
    {
        return \count($this->attempts);
    }

    /**
     * @return list<PaymentAttempt>
     */
    public function attempts(): array
    {
        return $this->attempts;
    }

    /**
     * @return list<Refund>
     */
    public function refunds(): array
    {
        return $this->refunds;
    }

    public function initiatedAt(): DateTimeImmutable
    {
        return $this->initiatedAt;
    }

    public function completedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function version(): int
    {
        return $this->version;
    }

    /**
     * @throws PaymentTransitionRejected
     */
    private function transitionTo(PaymentStatus $target, string $action): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw PaymentTransitionRejected::of($this->id, $this->status, $action);
        }

        $this->status = $target;
    }

    /**
     * @param positive-int $maxAttempts
     */
    private function canRetry(int $maxAttempts): bool
    {
        if (\count($this->attempts) >= $maxAttempts) {
            return false;
        }

        return $this->lastFailureReason === null || $this->lastFailureReason->retryable;
    }

    /**
     * @return positive-int
     */
    private function nextAttemptNumber(): int
    {
        return $this->positive(\count($this->attempts) + 1);
    }

    /**
     * @return positive-int
     */
    private function positive(int $value): int
    {
        /** @var positive-int */
        return max(1, $value);
    }
}
