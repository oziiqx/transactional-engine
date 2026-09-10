<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus;

use App\Shared\Domain\Event\DomainEvent;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Per-request / per-message holding area for domain events.
 *
 * A Doctrine listener fills it while the unit of work flushes; the command-bus
 * middleware drains it once the transaction has committed. Implements
 * {@see ResetInterface} so a long-running worker starts each message clean.
 */
final class DomainEventBuffer implements ResetInterface
{
    /**
     * @var list<DomainEvent>
     */
    private array $events = [];

    public function record(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->events[] = $event;
        }
    }

    /**
     * @return list<DomainEvent>
     */
    public function drain(): array
    {
        $events = $this->events;
        $this->events = [];

        return $events;
    }

    public function isEmpty(): bool
    {
        return $this->events === [];
    }

    public function reset(): void
    {
        $this->events = [];
    }
}
