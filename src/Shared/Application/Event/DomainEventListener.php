<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

/**
 * Marker for in-process reactions to domain events.
 *
 * Implementations expose one or more `__invoke(SomeDomainEvent $event): void`
 * methods and are tagged onto `event.bus`. A listener's job is small: update a
 * read model, or translate the domain event into an {@see IntegrationMessage}
 * that the messenger routes to a queue.
 */
interface DomainEventListener
{
}
