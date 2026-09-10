<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

/**
 * Marker for command handlers. Implementations expose a single
 * `__invoke(SomeCommand $command): void` method; the container tags them onto
 * `command.bus` automatically (see config/services.yaml).
 */
interface CommandHandler
{
}
