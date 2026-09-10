<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\CurrencyMismatch;
use App\Shared\Domain\Exception\InvariantViolation;
use JsonSerializable;
use Stringable;

/**
 * An exact monetary amount: a signed integer number of minor units bound to a
 * {@see Currency}. $12.34 USD is `Money::of(1234, Currency::USD)`.
 *
 * There is no floating point anywhere in this class. Arithmetic across currencies
 * throws {@see CurrencyMismatch}. Splitting an amount is always lossless — the
 * parts returned by {@see allocate()} sum back to exactly the original.
 *
 * @psalm-immutable
 */
final readonly class Money implements Stringable, JsonSerializable
{
    /**
     * Upper bound on the magnitude of the minor amount. Well below PHP_INT_MAX so
     * that intermediate products in {@see allocate()} cannot silently become floats.
     */
    public const int MAX_MINOR_AMOUNT = 1_000_000_000_000_000;

    private function __construct(
        public int $minorAmount,
        public Currency $currency,
    ) {
    }

    /**
     * @throws InvariantViolation when the amount is outside the safe range
     */
    public static function of(int $minorAmount, Currency $currency): self
    {
        if (\abs($minorAmount) > self::MAX_MINOR_AMOUNT) {
            throw InvariantViolation::of(
                'money.out_of_range',
                \sprintf('Monetary amount %d exceeds the supported range.', $minorAmount),
            );
        }

        return new self($minorAmount, $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    /**
     * Parse a plain decimal string ("12.34", "-0.05", "100") in the given currency.
     *
     * @throws InvariantViolation when the string is malformed or carries more
     *                             fraction digits than the currency permits
     */
    public static function fromDecimalString(string $amount, Currency $currency): self
    {
        if (\preg_match('/^(?<sign>-)?(?<whole>\d+)(?:\.(?<frac>\d+))?$/', $amount, $m) !== 1) {
            throw InvariantViolation::of(
                'money.malformed',
                \sprintf('"%s" is not a valid decimal amount.', $amount),
            );
        }

        $digits = $currency->fractionDigits();
        $frac = $m['frac'] ?? '';

        if (\strlen($frac) > $digits) {
            throw InvariantViolation::of(
                'money.too_precise',
                \sprintf('%s amounts allow at most %d fraction digit(s).', $currency->value, $digits),
                ['currency' => $currency->value, 'maxFractionDigits' => $digits],
            );
        }

        $minor = (int) $m['whole'] * $currency->subunitFactor();
        if ($digits > 0) {
            $minor += (int) str_pad($frac, $digits, '0', \STR_PAD_RIGHT);
        }

        return self::of(($m['sign'] ?? '') === '-' ? -$minor : $minor, $currency);
    }

    /**
     * @throws CurrencyMismatch
     * @throws InvariantViolation
     */
    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::of($this->guard($this->minorAmount + $other->minorAmount, 'add'), $this->currency);
    }

    /**
     * @throws CurrencyMismatch
     * @throws InvariantViolation
     */
    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return self::of($this->guard($this->minorAmount - $other->minorAmount, 'subtract'), $this->currency);
    }

    /**
     * @throws InvariantViolation
     */
    public function multipliedBy(int $factor): self
    {
        return self::of($this->guard($this->minorAmount * $factor, 'multipliedBy'), $this->currency);
    }

    public function negated(): self
    {
        return new self(-$this->minorAmount, $this->currency);
    }

    public function absolute(): self
    {
        return new self(\abs($this->minorAmount), $this->currency);
    }

    /**
     * Split this amount across N buckets weighted by integer ratios, with no
     * residual: `sum(allocate($r)) === $this`. Rounding remainder is handed out
     * one minor unit at a time to the earliest non-zero buckets.
     *
     * @param non-empty-list<int> $ratios
     *
     * @return non-empty-list<self>
     *
     * @throws InvariantViolation when any ratio is negative or every ratio is zero
     */
    public function allocate(array $ratios): array
    {
        $total = 0;
        foreach ($ratios as $ratio) {
            if ($ratio < 0) {
                throw InvariantViolation::of('money.negative_ratio', 'Allocation ratios must not be negative.');
            }
            $total += $ratio;
        }

        if ($total === 0) {
            throw InvariantViolation::of('money.zero_ratios', 'At least one allocation ratio must be positive.');
        }

        $shares = [];
        $remainder = $this->minorAmount;
        foreach ($ratios as $ratio) {
            $share = \intdiv($this->guard($this->minorAmount * $ratio, 'allocate'), $total);
            $shares[] = $share;
            $remainder -= $share;
        }

        $step = $remainder <=> 0;
        $count = \count($ratios);
        for ($i = 0; $remainder !== 0; ++$i) {
            $slot = $i % $count;
            if ($ratios[$slot] !== 0) {
                $shares[$slot] += $step;
                $remainder -= $step;
            }
        }

        return array_map(fn (int $minor): self => new self($minor, $this->currency), $shares);
    }

    /**
     * @param positive-int $parts
     *
     * @return non-empty-list<self>
     *
     * @throws InvariantViolation
     */
    public function split(int $parts): array
    {
        return $this->allocate(array_fill(0, $parts, 1));
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    public function isPositive(): bool
    {
        return $this->minorAmount > 0;
    }

    public function isNegative(): bool
    {
        return $this->minorAmount < 0;
    }

    /**
     * Equal amount *and* currency. USD 0 does not equal EUR 0.
     */
    public function equals(self $other): bool
    {
        return $this->minorAmount === $other->minorAmount
            && $this->currency === $other->currency;
    }

    /**
     * @throws CurrencyMismatch
     *
     * @return int<-1, 1>
     */
    public function compareTo(self $other): int
    {
        $this->assertSameCurrency($other);

        return $this->minorAmount <=> $other->minorAmount;
    }

    /**
     * @throws CurrencyMismatch
     */
    public function greaterThan(self $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * @throws CurrencyMismatch
     */
    public function greaterThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) >= 0;
    }

    /**
     * @throws CurrencyMismatch
     */
    public function lessThan(self $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * @throws CurrencyMismatch
     */
    public function lessThanOrEqualTo(self $other): bool
    {
        return $this->compareTo($other) <= 0;
    }

    /**
     * @return numeric-string
     */
    public function toDecimalString(): string
    {
        $digits = $this->currency->fractionDigits();
        $sign = $this->minorAmount < 0 ? '-' : '';
        $abs = \abs($this->minorAmount);

        if ($digits === 0) {
            /** @var numeric-string */
            return $sign . (string) $abs;
        }

        $factor = $this->currency->subunitFactor();

        /** @var numeric-string */
        return \sprintf('%s%d.%0' . $digits . 'd', $sign, \intdiv($abs, $factor), $abs % $factor);
    }

    public function __toString(): string
    {
        return $this->toDecimalString() . ' ' . $this->currency->value;
    }

    /**
     * @return array{amount: numeric-string, currency: non-empty-string, minorUnits: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->toDecimalString(),
            'currency' => $this->currency->value,
            'minorUnits' => $this->minorAmount,
        ];
    }

    /**
     * @throws CurrencyMismatch
     */
    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw CurrencyMismatch::between($this->currency, $other->currency);
        }
    }

    /**
     * @param non-empty-string $operation
     *
     * @throws InvariantViolation when the operation overflowed the safe integer range
     */
    private function guard(int|float $value, string $operation): int
    {
        if (\is_float($value) || \abs($value) > self::MAX_MINOR_AMOUNT) {
            throw InvariantViolation::of(
                'money.overflow',
                \sprintf('Operation "%s" overflowed the supported monetary range.', $operation),
            );
        }

        return $value;
    }
}
