<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Lock;

use App\Shared\Application\Lock\Lock;
use App\Shared\Application\Lock\LockFactory;
use Symfony\Component\Lock\LockFactory as SymfonyComponentLockFactory;

/**
 * Adapter binding the {@see LockFactory} port to Symfony's Lock component
 * (Redis-backed in every non-test environment — see config/packages/lock.yaml).
 */
final readonly class SymfonyLockFactory implements LockFactory
{
    public function __construct(
        private SymfonyComponentLockFactory $lockFactory
    ) {
    }

    public function create(string $resource, float $ttlSeconds = 30.0): Lock
    {
        return new SymfonyLock($this->lockFactory->createLock($resource, $ttlSeconds), $resource);
    }
}
