<?php

declare(strict_types=1);

namespace App\Shared\Application\Query;

/**
 * Port the application and presentation layers use to run queries against the
 * read side.
 */
interface QueryBus
{
    /**
     * @template TResult
     *
     * @param Query<TResult> $query
     *
     * @return TResult
     */
    public function ask(Query $query): mixed;
}
