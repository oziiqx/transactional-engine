<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvariantViolation;
use Stringable;

/**
 * A client-supplied token that makes a state-changing request safe to retry.
 *
 * The same key replayed against the same operation must yield the same outcome
 * and must never apply the effect twice.
 *
 * @psalm-immutable
 */
final readonly class IdempotencyKey implements Stringable
{
    public const int MIN_LENGTH = 8;
    public const int MAX_LENGTH = 255;

    private function __construct(public string $value)
    {
    }

    /**
     * @throws InvariantViolation when the token is too short, too long or
     *                             contains control characters
     */
    public static function fromString(string $value): self
    {
        $length = \strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvariantViolation::of(
                'idempotency_key.length',
                \sprintf(
                    'An idempotency key must be between %d and %d characters, got %d.',
                    self::MIN_LENGTH,
                    self::MAX_LENGTH,
                    $length,
                ),
            );
        }

        if (\preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw InvariantViolation::of(
                'idempotency_key.charset',
                'An idempotency key must not contain control characters.',
            );
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return \hash_equals($this->value, $other->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
