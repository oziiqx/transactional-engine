<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__, 2) . '/.env');

$kernel = new Kernel('test', true);
$kernel->boot();

$container = $kernel->getContainer();
$serviceContainer = $container->has('test.service_container')
    ? $container->get('test.service_container')
    : $container;

/** @var ManagerRegistry $registry */
$registry = $serviceContainer->get('doctrine');

return $registry->getManager();
