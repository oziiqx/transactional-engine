<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Lock;

use App\Shared\Application\Lock\Lock;
use App\Shared\Application\Lock\LockUnavailable;
use Symfony\Component\Lock\LockInterface;

final readonly class SymfonyLock implements Lock
{
    public function __construct(
        private LockInterface $lock,
        private string $resource,
    ) {
    }

    public function acquire(): void
    {
        if (! $this->lock->acquire()) {
            throw LockUnavailable::forResource($this->resource);
        }
    }

    public function whileHeld(callable $critical): mixed
    {
        $this->acquire();

        try {
            return $critical();
        } finally {
            $this->release();
        }
    }

    public function release(): void
    {
        $this->lock->release();
    }
}
