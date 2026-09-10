<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Shared\Application\Command\CommandBus;
use App\Shared\Application\Query\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base case for tests that exercise handlers against a real database and a real
 * Messenger bus. `dama/doctrine-test-bundle` wraps every test in a transaction
 * that is rolled back on teardown, so tests stay isolated and fast.
 */
abstract class IntegrationTestCase extends KernelTestCase
{
    protected ContainerInterface $container;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->container = static::getContainer();
        $this->entityManager = $this->service(EntityManagerInterface::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Prevent state bleeding between tests via the shared identity map.
        $this->entityManager->clear();
    }

    public function commandBus(): CommandBus
    {
        return $this->service(CommandBus::class);
    }

    public function queryBus(): QueryBus
    {
        return $this->service(QueryBus::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    protected function service(string $id): object
    {
        $service = $this->container->get($id);
        \assert($service instanceof $id);

        return $service;
    }
}
