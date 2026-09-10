<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Clock;

use App\Shared\Domain\Clock\Clock;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

/**
 * Production clock. Delegates to the PSR-20 clock the framework provides (so
 * `symfony/clock`'s time mocking still works under the kernel) and normalises the
 * result to UTC — the domain and the database only ever deal in UTC instants.
 */
final readonly class SystemClock implements Clock, ClockInterface
{
    private DateTimeZone $utc;

    public function __construct(
        private ClockInterface $psrClock
    ) {
        $this->utc = new DateTimeZone('UTC');
    }

    public function now(): DateTimeImmutable
    {
        return $this->psrClock->now()->setTimezone($this->utc);
    }
}
