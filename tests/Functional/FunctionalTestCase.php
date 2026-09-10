<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base case for tests that drive the application through its real HTTP stack:
 * routing, middleware, API Platform, serialization and the RFC 7807 error format.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->catchExceptions(false);
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function jsonRequest(
        string $method,
        string $uri,
        array $payload = [],
        array $headers = [],
    ): Response {
        $this->client->request(
            $method,
            $uri,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                ...$headers,
            ],
            content: $payload === [] ? null : json_encode($payload, \JSON_THROW_ON_ERROR),
        );

        return $this->client->getResponse();
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(Response $response): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        return $data;
    }
}
