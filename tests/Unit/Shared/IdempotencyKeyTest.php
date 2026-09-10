<?php

declare(strict_types=1);

use App\Shared\Domain\Exception\InvariantViolation;
use App\Shared\Domain\ValueObject\IdempotencyKey;

it('accepts a reasonable token', function (): void {
    $key = IdempotencyKey::fromString('order-2026-0001-abc');

    expect((string) $key)->toBe('order-2026-0001-abc');
});

it('rejects tokens that are too short', function (): void {
    IdempotencyKey::fromString('short');
})->throws(InvariantViolation::class);

it('rejects tokens with control characters', function (): void {
    IdempotencyKey::fromString("valid-looking\x00key");
})->throws(InvariantViolation::class);

it('compares in constant time', function (): void {
    $a = IdempotencyKey::fromString('same-token-value');
    $b = IdempotencyKey::fromString('same-token-value');

    expect($a->equals($b))->toBeTrue();
});
