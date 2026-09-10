<?php

declare(strict_types=1);

use App\Shared\Domain\Exception\CurrencyMismatch;
use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;

describe('construction', function (): void {
    it('is built from minor units', function (): void {
        expect(Money::of(1234, Currency::USD))->toBeMoneyOf(1234, 'USD');
    });

    it('rejects amounts beyond the safe range', function (): void {
        Money::of(Money::MAX_MINOR_AMOUNT + 1, Currency::EUR);
    })->throws(InvariantViolation::class);

    it('parses two-decimal currencies', function (): void {
        expect(Money::fromDecimalString('12.34', Currency::EUR))->toBeMoneyOf(1234, 'EUR')
            ->and(Money::fromDecimalString('-0.05', Currency::EUR))->toBeMoneyOf(-5, 'EUR')
            ->and(Money::fromDecimalString('100', Currency::EUR))->toBeMoneyOf(10_000, 'EUR');
    });

    it('parses zero-decimal currencies', function (): void {
        expect(Money::fromDecimalString('500', Currency::JPY))->toBeMoneyOf(500, 'JPY');
    });

    it('rejects more fraction digits than the currency allows', function (): void {
        Money::fromDecimalString('1.234', Currency::USD);
    })->throws(InvariantViolation::class);

    it('rejects malformed decimal strings', function (string $value): void {
        Money::fromDecimalString($value, Currency::USD);
    })->throws(InvariantViolation::class)->with(['', '1,5', 'abc', '1.', '.5', '1e3']);
});

describe('arithmetic', function (): void {
    it('adds and subtracts within a currency', function (): void {
        $ten = Money::of(1000, Currency::USD);
        $three = Money::of(300, Currency::USD);

        expect($ten->add($three))->toBeMoneyOf(1300, 'USD')
            ->and($ten->subtract($three))->toBeMoneyOf(700, 'USD');
    });

    it('refuses to mix currencies', function (): void {
        Money::of(1000, Currency::USD)->add(Money::of(1000, Currency::EUR));
    })->throws(CurrencyMismatch::class);

    it('multiplies by an integer factor', function (): void {
        expect(Money::of(199, Currency::USD)->multipliedBy(3))->toBeMoneyOf(597, 'USD');
    });

    it('detects overflow instead of silently returning a float', function (): void {
        Money::of(Money::MAX_MINOR_AMOUNT, Currency::USD)->multipliedBy(1_000);
    })->throws(InvariantViolation::class);
});

describe('allocation', function (): void {
    it('splits without losing a minor unit', function (): void {
        $parts = Money::of(1001, Currency::USD)->allocate([1, 1, 1]);

        expect($parts)->toHaveCount(3)
            ->and($parts[0])->toBeMoneyOf(334, 'USD')
            ->and($parts[1])->toBeMoneyOf(334, 'USD')
            ->and($parts[2])->toBeMoneyOf(333, 'USD');
    });

    it('always sums back to the original', function (int $amount, array $ratios): void {
        $original = Money::of($amount, Currency::EUR);

        $total = array_reduce(
            $original->allocate($ratios),
            static fn (Money $carry, Money $part): Money => $carry->add($part),
            Money::zero(Currency::EUR),
        );

        expect($total)->toEqualMoney($original);
    })->with([
        'even' => [1000, [1, 1, 1, 1]],
        'weighted' => [1000, [7, 2, 1]],
        'negative amount' => [-733, [1, 1, 1]],
        'zero buckets kept zero' => [999, [0, 3, 0, 1]],
    ]);

    it('rejects negative ratios', function (): void {
        Money::of(100, Currency::USD)->allocate([1, -1]);
    })->throws(InvariantViolation::class);

    it('rejects all-zero ratios', function (): void {
        Money::of(100, Currency::USD)->allocate([0, 0]);
    })->throws(InvariantViolation::class);
});

describe('comparison and formatting', function (): void {
    it('compares only within a currency', function (): void {
        expect(Money::of(500, Currency::USD)->greaterThan(Money::of(499, Currency::USD)))->toBeTrue();
    });

    it('treats equality as amount plus currency', function (): void {
        expect(Money::zero(Currency::USD)->equals(Money::zero(Currency::EUR)))->toBeFalse();
    });

    it('renders a decimal string and a label', function (): void {
        expect(Money::of(-1234, Currency::USD)->toDecimalString())->toBe('-12.34')
            ->and((string) Money::of(500, Currency::JPY))->toBe('500 JPY');
    });

    it('serialises to a stable json shape', function (): void {
        expect(Money::of(1234, Currency::USD)->jsonSerialize())->toBe([
            'amount' => '12.34',
            'currency' => 'USD',
            'minorUnits' => 1234,
        ]);
    });
});
