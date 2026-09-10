<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Stores a monetary minor-unit amount as `BIGINT` and hydrates it back as a
 * native PHP `int`.
 *
 * Doctrine's built-in `bigint` type returns a *string* in PHP (to stay safe on
 * 32-bit builds). This engine is 64-bit only and {@see \App\Shared\Domain\ValueObject\Money}
 * caps amounts well inside `PHP_INT_MAX`, so an `int` is both safe and far more
 * convenient for the domain.
 */
final class MoneyAmountType extends Type
{
    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null || \is_int($value)) {
            return $value;
        }

        if (\is_string($value) && \preg_match('/^-?\d++$/', $value) === 1) {
            return (int) $value;
        }

        throw ValueNotConvertible::new(\get_debug_type($value), 'int');
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null || \is_int($value)) {
            return $value;
        }

        throw ValueNotConvertible::new(\get_debug_type($value), 'int');
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }
}
