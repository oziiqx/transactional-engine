<?php

declare(strict_types=1);

namespace App\Shared\Domain\Aggregate;

use App\Shared\Domain\Event\DomainEvent;

/**
 * Base class for the single entry point of a consistency boundary.
 *
 * An aggregate records the events its behaviour produced; the persistence adapter
 * drains them with {@see pullDomainEvents()} once the unit of work has flushed,
 * and the messenger middleware relays them after commit. The aggregate itself
 * never dispatches anything and never touches infrastructure.
 */
abstract class AggregateRoot
{
    /**
     * @var list<DomainEvent>
     */
    private array $pendingEvents = [];

    /**
     * Returns every event recorded since the last call and empties the buffer.
     *
     * @return list<DomainEvent>
     */
    final public function pullDomainEvents(): array
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];

        return $events;
    }

    final public function hasPendingEvents(): bool
    {
        return $this->pendingEvents !== [];
    }

    final protected function recordThat(DomainEvent $event): void
    {
        $this->pendingEvents[] = $event;
    }
}
