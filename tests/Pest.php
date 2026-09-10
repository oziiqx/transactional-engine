<?php

declare(strict_types=1);

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Tests\Functional\FunctionalTestCase;
use App\Tests\Integration\IntegrationTestCase;

/*
 * Unit tests are pure PHP: no base class, no container, no I/O. Integration and
 * functional tests inherit a kernel-aware case that wraps each test in a
 * rolled-back transaction.
 */
uses(IntegrationTestCase::class)->in('Integration');
uses(FunctionalTestCase::class)->in('Functional');

expect()->extend('toEqualMoney', function (Money $expected): void {
    /** @var Money $actual */
    $actual = $this->value;

    expect($actual)->toBeInstanceOf(Money::class)
        ->and($actual->equals($expected))->toBeTrue();
});

expect()->extend('toBeMoneyOf', function (int $minorAmount, string $currency): void {
    /** @var Money $actual */
    $actual = $this->value;

    expect($actual->minorAmount)->toBe($minorAmount)
        ->and($actual->currency->value)->toBe($currency);
});

/**
 * A fixed instant for deterministic domain tests.
 */
function at(string $moment = '2026-01-01T12:00:00+00:00'): DateTimeImmutable
{
    return new DateTimeImmutable($moment);
}

function money(int $minor, Currency $currency = Currency::EUR): Money
{
    return Money::of($minor, $currency);
}

/**
 * @return non-empty-string
 */
function uuid(): string
{
    return Symfony\Component\Uid\Uuid::v7()->toRfc4122();
}
