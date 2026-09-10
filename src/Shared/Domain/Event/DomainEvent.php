<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

use DateTimeImmutable;

/**
 * Something that happened in the domain, stated in the past tense.
 *
 * Concrete events are `final readonly` structs of primitives and value objects.
 * They are recorded by an aggregate ({@see \App\Shared\Domain\Aggregate\AggregateRoot}),
 * persisted with the write, and relayed to subscribers after the transaction commits.
 */
interface DomainEvent
{
    /**
     * Identifier of the aggregate that produced the event.
     */
    public function aggregateId(): string;

    /**
     * Wall-clock instant the event occurred, captured from the application clock
     * at the moment the aggregate recorded it.
     */
    public function occurredOn(): DateTimeImmutable;

    /**
     * Stable dotted name in `context.past_tense` form, e.g. `payment.completed`.
     * Used for routing, outbox rows and audit logs; never changes once shipped.
     *
     * @return non-empty-string
     */
    public function eventName(): string;

    /**
     * Transport-safe body: only scalars, nulls and nested arrays of the same.
     * This is what crosses the process boundary onto the message queue.
     *
     * @return array<string, scalar|array<array-key, mixed>|null>
     */
    public function payload(): array;
}
