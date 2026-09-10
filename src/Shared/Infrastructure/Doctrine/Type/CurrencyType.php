<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Type;

use App\Shared\Domain\ValueObject\Currency;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\Type;

/**
 * Maps {@see Currency} to a fixed `CHAR(3)` ISO-4217 alphabetic code.
 * Registered under the name `currency` in config/packages/doctrine.yaml.
 */
final class CurrencyType extends Type
{
    /**
     * @param array<string, mixed> $column
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL(['length' => 3, 'fixed' => true]);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Currency
    {
        if ($value === null || $value instanceof Currency) {
            return $value;
        }

        $code = \is_string($value) ? $value : \get_debug_type($value);

        return Currency::tryFrom($code) ?? throw ValueNotConvertible::new($code, Currency::class);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Currency) {
            return $value->value;
        }

        if (\is_string($value) && Currency::tryFrom($value) instanceof Currency) {
            return $value;
        }

        throw ValueNotConvertible::new(\get_debug_type($value), Currency::class);
    }
}
