<?php

declare(strict_types=1);

namespace App\Shared\Application\Query;

/**
 * Marker for query handlers. Implementations expose a single
 * `__invoke(SomeQuery $query): SomeReadModel` method and are tagged onto
 * `query.bus` by the container.
 */
interface QueryHandler
{
}
