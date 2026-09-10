<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\EventSubscriber;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Infrastructure\Bus\DomainEventBuffer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Harvests domain events from every aggregate touched by the current flush and
 * parks them in the {@see DomainEventBuffer}. Nothing is dispatched here — that
 * happens after commit, in {@see \App\Shared\Infrastructure\Bus\Messenger\Middleware\RelayRecordedDomainEventsMiddleware}.
 *
 * `onFlush` (rather than `postFlush`) is used so that aggregates scheduled for
 * deletion are still reachable through the unit of work.
 */
#[AsDoctrineListener(event: Events::onFlush)]
final readonly class CollectDomainEventsSubscriber
{
    public function __construct(private DomainEventBuffer $buffer)
    {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        $scheduled = [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ];

        foreach ($scheduled as $entity) {
            if ($entity instanceof AggregateRoot && $entity->hasPendingEvents()) {
                $this->buffer->record(...$entity->pullDomainEvents());
            }
        }
    }
}
