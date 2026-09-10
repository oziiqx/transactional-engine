<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Throwable;

/**
 * Readiness probe. Returns 200 only when the datastore is reachable, so an
 * orchestrator can hold traffic until the container can actually serve it.
 */
#[AsController]
final readonly class HealthController
{
    public function __construct(
        private Connection $connection
    ) {
    }

    public function ready(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1');
            $database = 'up';
        } catch (Throwable) {
            $database = 'down';
        }

        $healthy = $database === 'up';

        return new JsonResponse(
            [
                'status' => $healthy ? 'pass' : 'fail',
                'checks' => [
                    'database' => $database,
                ],
            ],
            $healthy ? 200 : 503,
        );
    }
}
