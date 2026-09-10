<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Port for publishing domain events onto the in-process event bus. Called by the
 * infrastructure relay after the write transaction commits, never by the domain.
 */
interface EventBus
{
    public function publish(DomainEvent ...$events): void;
}
