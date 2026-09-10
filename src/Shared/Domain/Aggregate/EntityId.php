<?php

declare(strict_types=1);

namespace App\Shared\Domain\Aggregate;

use App\Shared\Domain\Exception\InvariantViolation;
use ReflectionClass;
use Stringable;
use Symfony\Component\Uid\Uuid;

/**
 * Base class for strongly-typed aggregate identifiers.
 *
 * Each context declares a one-line subclass (`final readonly class WalletId extends
 * EntityId {}`). Identifiers are time-ordered UUIDv7 values, so they sort by
 * creation time and keep B-tree indexes compact.
 *
 * @psalm-immutable
 */
abstract readonly class EntityId implements Stringable
{
    /**
     * @throws InvariantViolation when the value is not a well-formed UUID
     */
    final public function __construct(
        public string $value
    ) {
        if (! Uuid::isValid($value)) {
            throw InvariantViolation::of(
                'identifier.malformed',
                \sprintf('"%s" is not a valid %s.', $value, static::label()),
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): static
    {
        return new static(Uuid::v7()->toRfc4122());
    }

    /**
     * @throws InvariantViolation
     */
    public static function fromString(string $value): static
    {
        return new static($value);
    }

    public function equals(self $other): bool
    {
        return $other::class === static::class && $other->value === $this->value;
    }

    /**
     * @return non-empty-string
     */
    protected static function label(): string
    {
        /** @var non-empty-string */
        return (new ReflectionClass(static::class))->getShortName();
    }
}
