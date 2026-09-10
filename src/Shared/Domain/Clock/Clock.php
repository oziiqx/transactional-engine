<?php

declare(strict_types=1);

namespace App\Shared\Domain\Clock;

use DateTimeImmutable;

/**
 * The domain's only source of "now". Handlers pass `$clock->now()` into aggregate
 * behaviour so that time is an explicit input and every rule is deterministically
 * testable.
 */
interface Clock
{
    public function now(): DateTimeImmutable;
}
