<?php

declare(strict_types=1);

use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\Currency;

it('exposes fraction digits and subunit factors', function (): void {
    expect(Currency::USD->fractionDigits())->toBe(2)
        ->and(Currency::USD->subunitFactor())->toBe(100)
        ->and(Currency::JPY->fractionDigits())->toBe(0)
        ->and(Currency::JPY->subunitFactor())->toBe(1);
});

it('resolves from a code, case-insensitively', function (): void {
    expect(Currency::fromCode('eur'))->toBe(Currency::EUR);
});

it('rejects unsupported codes', function (): void {
    Currency::fromCode('XYZ');
})->throws(InvariantViolation::class);

it('maps every case to a 3-digit ISO numeric code', function (Currency $currency): void {
    expect($currency->numericCode())->toMatch('/^\d{3}$/');
})->with(Currency::cases());
