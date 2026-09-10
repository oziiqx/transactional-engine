<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Idempotency;

use App\Shared\Application\Idempotency\IdempotencyStore;
use App\Shared\Application\Idempotency\OpenResult;
use App\Shared\Application\Idempotency\RecordedResponse;
use App\Shared\Domain\ValueObject\IdempotencyKey;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Idempotency store backed by a shared cache (Redis in production) plus a
 * distributed lock.
 *
 * - The lock makes "is another request with this key running right now?" a
 *   race-free question.
 * - The cache row holds the request fingerprint while in flight and the full
 *   response once complete, so a later replay returns byte-for-byte the same body.
 */
final class CacheBackedIdempotencyStore implements IdempotencyStore, ResetInterface
{
    private const int RESERVATION_TTL = 120;

    private const int RESPONSE_TTL = 86_400;

    /**
     * @var array<string, LockInterface>
     */
    private array $heldLocks = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cacheIdempotency,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function open(IdempotencyKey $key, string $operation, string $requestFingerprint): OpenResult
    {
        $cacheKey = $this->cacheKey($key, $operation);
        $item = $this->cacheIdempotency->getItem($cacheKey);

        if ($item->isHit()) {
            /** @var array{fingerprint: string, response?: array{statusCode: int, body: string, headers: array<string, string>}} $record */
            $record = $item->get();

            if (! \hash_equals($record['fingerprint'], $requestFingerprint)) {
                return OpenResult::fingerprintMismatch();
            }

            if (isset($record['response'])) {
                return OpenResult::replay(RecordedResponse::fromArray($record['response']));
            }

            return OpenResult::inFlight();
        }

        $lock = $this->lockFactory->createLock($cacheKey, self::RESERVATION_TTL);

        if (! $lock->acquire()) {
            return OpenResult::inFlight();
        }

        $this->heldLocks[$cacheKey] = $lock;

        $item->set([
            'fingerprint' => $requestFingerprint,
        ])
            ->expiresAfter(self::RESERVATION_TTL);
        $this->cacheIdempotency->save($item);

        return OpenResult::proceed();
    }

    public function close(IdempotencyKey $key, string $operation, RecordedResponse $response): void
    {
        $cacheKey = $this->cacheKey($key, $operation);
        $item = $this->cacheIdempotency->getItem($cacheKey);

        /** @var array{fingerprint: string} $record */
        $record = $item->isHit() ? $item->get() : [
            'fingerprint' => '',
        ];
        $record['response'] = $response->toArray();

        $item->set($record)->expiresAfter(self::RESPONSE_TTL);
        $this->cacheIdempotency->save($item);

        $this->release($cacheKey);
    }

    public function discard(IdempotencyKey $key, string $operation): void
    {
        $cacheKey = $this->cacheKey($key, $operation);
        $this->cacheIdempotency->deleteItem($cacheKey);
        $this->release($cacheKey);
    }

    public function reset(): void
    {
        foreach (array_keys($this->heldLocks) as $cacheKey) {
            $this->release($cacheKey);
        }
    }

    /**
     * @return non-empty-string
     */
    private function cacheKey(IdempotencyKey $key, string $operation): string
    {
        return 'idem.' . \hash('xxh128', $operation . "\0" . $key->value);
    }

    private function release(string $cacheKey): void
    {
        if (isset($this->heldLocks[$cacheKey])) {
            $this->heldLocks[$cacheKey]->release();
            unset($this->heldLocks[$cacheKey]);
        }
    }
}
