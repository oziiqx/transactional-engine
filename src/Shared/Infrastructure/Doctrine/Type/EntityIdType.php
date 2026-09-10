<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Shared\Domain\Aggregate\EntityId;
use App\Shared\Domain\Exception\InvariantViolation;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Base for every strongly-typed identifier column. On PostgreSQL this maps to the
 * native `uuid` type, so identifiers index and compare efficiently.
 *
 * A context adds a one-line subclass and registers it:
 *
 *     final class WalletIdType extends EntityIdType
 *     {
 *         protected function idClass(): string { return WalletId::class; }
 *     }
 *
 * @template T of EntityId
 */
abstract class EntityIdType extends Type
{
    /**
     * @return class-string<T>
     */
    abstract protected function idClass(): string;

    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    /**
     * @return T|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?EntityId
    {
        $class = $this->idClass();

        if ($value === null || $value instanceof $class) {
            return $value;
        }

        if (!\is_string($value)) {
            throw ValueNotConvertible::new(\get_debug_type($value), $class);
        }

        try {
            return $class::fromString($value);
        } catch (InvariantViolation) {
            throw ValueNotConvertible::new($value, $class);
        }
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof EntityId => $value->value,
            \is_string($value) => $value,
            default => throw ValueNotConvertible::new(\get_debug_type($value), $this->idClass()),
        };
    }
}
