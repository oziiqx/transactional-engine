<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\InvariantViolation;

/**
 * The subset of ISO 4217 currencies this engine settles in.
 *
 * The backing value is the alphabetic code; each case also carries its numeric
 * code and the number of fraction digits (minor units) the currency uses, which
 * is what {@see Money} needs to render and parse decimal strings correctly.
 */
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case CHF = 'CHF';
    case PLN = 'PLN';
    case JPY = 'JPY';
    case SEK = 'SEK';
    case NOK = 'NOK';

    /**
     * Number of decimal places in the currency's standard denomination.
     * JPY has none; the rest here use two.
     */
    public function fractionDigits(): int
    {
        return match ($this) {
            self::JPY => 0,
            default => 2,
        };
    }

    /**
     * 10 ** fractionDigits — the number of minor units in one major unit.
     */
    public function subunitFactor(): int
    {
        return 10 ** $this->fractionDigits();
    }

    /**
     * @return numeric-string ISO 4217 numeric code, zero-padded to 3 digits
     */
    public function numericCode(): string
    {
        return match ($this) {
            self::USD => '840',
            self::EUR => '978',
            self::GBP => '826',
            self::CHF => '756',
            self::PLN => '985',
            self::JPY => '392',
            self::SEK => '752',
            self::NOK => '578',
        };
    }

    /**
     * @param non-empty-string $code
     *
     * @throws InvariantViolation when the code is not a supported currency
     */
    public static function fromCode(string $code): self
    {
        return self::tryFrom(strtoupper($code))
            ?? throw InvariantViolation::of(
                'currency.unsupported',
                \sprintf('"%s" is not a supported settlement currency.', $code),
                ['supported' => self::codes()],
            );
    }

    /**
     * @return non-empty-list<non-empty-string>
     */
    public static function codes(): array
    {
        return array_map(static fn (self $c): string => $c->value, self::cases());
    }

    public function equals(self $other): bool
    {
        return $this === $other;
    }
}
