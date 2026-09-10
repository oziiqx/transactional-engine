<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

use App\Shared\Domain\Exception\InvariantViolation;
use JsonSerializable;

/**
 * Why a processing attempt failed, as reported by the gateway. Retryable failures
 * (a timeout, a temporary decline) are distinguished from permanent ones (a
 * stolen card) so the processor can stop wasting attempts.
 *
 * @psalm-immutable
 */
final readonly class FailureReason implements JsonSerializable
{
    /**
     * @param non-empty-string $code
     * @param non-empty-string $message
     */
    private function __construct(
        public string $code,
        public string $message,
        public bool $retryable,
    ) {
    }

    /**
     * @param non-empty-string $code
     * @param non-empty-string $message
     */
    public static function retryable(string $code, string $message): self
    {
        return new self($code, $message, true);
    }

    /**
     * @param non-empty-string $code
     * @param non-empty-string $message
     */
    public static function permanent(string $code, string $message): self
    {
        return new self($code, $message, false);
    }

    /**
     * @param array{code?: mixed, message?: mixed, retryable?: mixed} $data
     *
     * @throws InvariantViolation on a malformed payload
     */
    public static function fromArray(array $data): self
    {
        $code = \is_string($data['code'] ?? null) && $data['code'] !== '' ? $data['code'] : null;
        $message = \is_string($data['message'] ?? null) && $data['message'] !== '' ? $data['message'] : null;

        if ($code === null || $message === null) {
            throw InvariantViolation::of('payment.failure_reason_malformed', 'Stored failure reason is malformed.');
        }

        return new self($code, $message, (bool) ($data['retryable'] ?? false));
    }

    /**
     * @return array{code: non-empty-string, message: non-empty-string, retryable: bool}
     */
    public function jsonSerialize(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'retryable' => $this->retryable,
        ];
    }
}
