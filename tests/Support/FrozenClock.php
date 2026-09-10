<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Domain\Clock\Clock;
use DateTimeImmutable;

/**
 * Deterministic {@see Clock} for unit tests. Time only moves when the test tells
 * it to.
 */
final class FrozenClock implements Clock
{
    private DateTimeImmutable $now;

    public function __construct(string $now = '2026-01-01T00:00:00+00:00')
    {
        $this->now = new DateTimeImmutable($now);
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advanceBy(string $interval): void
    {
        $this->now = $this->now->add(new \DateInterval($interval));
    }

    public function setTo(string $now): void
    {
        $this->now = new DateTimeImmutable($now);
    }
}
