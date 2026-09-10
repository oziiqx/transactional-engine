<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Domain\Clock\Clock;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;

/**
 * Production clock. Delegates to the PSR-20 clock the framework provides so that
 * `symfony/clock`'s time mocking still works in tests that boot the kernel.
 */
final readonly class SystemClock implements Clock, ClockInterface
{
    public function __construct(private ClockInterface $psrClock)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->psrClock->now();
    }
}
