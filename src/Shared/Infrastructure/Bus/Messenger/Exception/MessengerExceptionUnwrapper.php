<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Bus\Messenger\Exception;

use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Throwable;

/**
 * Pulls the first real exception out of Messenger's {@see HandlerFailedException}
 * wrapper so the rest of the application only ever sees domain-level failures.
 */
final class MessengerExceptionUnwrapper
{
    public static function unwrap(HandlerFailedException $exception): Throwable
    {
        $wrapped = $exception->getWrappedExceptions();

        return $wrapped === [] ? $exception : array_values($wrapped)[0];
    }
}
