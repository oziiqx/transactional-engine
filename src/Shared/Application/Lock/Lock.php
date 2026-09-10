<?php

declare(strict_types=1);

namespace App\Shared\Application\Lock;

/**
 * A held-or-not distributed lock.
 */
interface Lock
{
    /**
     * Take the lock without blocking.
     *
     * @throws LockUnavailable when another holder has it
     */
    public function acquire(): void;

    /**
     * Run the callback while holding the lock, releasing it afterwards even if
     * the callback throws.
     *
     * @template T
     *
     * @param callable(): T $critical
     *
     * @return T
     *
     * @throws LockUnavailable when the lock cannot be taken
     */
    public function whileHeld(callable $critical): mixed;

    public function release(): void;
}
