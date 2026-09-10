<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

/**
 * Port for handing an {@see IntegrationMessage} to the asynchronous transport.
 * Used by domain-event listeners that need to notify the outside world.
 */
interface IntegrationMessageBus
{
    public function dispatch(IntegrationMessage $message): void;
}
