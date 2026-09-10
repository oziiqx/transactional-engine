<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\IdempotencyKey;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Maps {@see IdempotencyKey} to a `VARCHAR(255)` column.
 */
final class IdempotencyKeyType extends Type
{
    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => IdempotencyKey::MAX_LENGTH]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?IdempotencyKey
    {
        if ($value === null || $value instanceof IdempotencyKey) {
            return $value;
        }

        if (!\is_string($value)) {
            throw ValueNotConvertible::new(\get_debug_type($value), IdempotencyKey::class);
        }

        try {
            return IdempotencyKey::fromString($value);
        } catch (InvariantViolation) {
            throw ValueNotConvertible::new($value, IdempotencyKey::class);
        }
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof IdempotencyKey => $value->value,
            \is_string($value) => $value,
            default => throw ValueNotConvertible::new(\get_debug_type($value), IdempotencyKey::class),
        };
    }
}
