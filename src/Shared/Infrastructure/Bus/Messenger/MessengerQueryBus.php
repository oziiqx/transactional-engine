<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Messenger;

use App\Shared\Application\Query\Query;
use App\Shared\Application\Query\QueryBus;
use App\Shared\Infrastructure\Bus\Messenger\Exception\MessengerExceptionUnwrapper;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Adapter binding the {@see QueryBus} port to Messenger's `query.bus`.
 * {@see HandleTrait} enforces the single-handler / single-result contract.
 */
final class MessengerQueryBus implements QueryBus
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    public function ask(Query $query): mixed
    {
        try {
            /** @var mixed */
            return $this->handle($query);
        } catch (HandlerFailedException $exception) {
            throw MessengerExceptionUnwrapper::unwrap($exception);
        }
    }
}
