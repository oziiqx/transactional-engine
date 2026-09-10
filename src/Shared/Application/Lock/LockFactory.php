<?php

declare(strict_types=1);

namespace App\Shared\Application\Lock;

/**
 * Port for obtaining a distributed mutex. Handlers that must not run concurrently
 * for the same resource (a reconciliation pass, a payment capture) depend on this
 * rather than on any locking library.
 */
interface LockFactory
{
    /**
     * @param string $resource stable key identifying what is being guarded
     * @param float            $ttlSeconds auto-release horizon if the holder dies
     */
    public function create(string $resource, float $ttlSeconds = 30.0): Lock;
}
