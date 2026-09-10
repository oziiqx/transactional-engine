<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

/**
 * An intent to change state, named as an imperative ("RegisterWallet").
 *
 * Commands are immutable DTOs of primitives. They are validated at the transport
 * boundary, handled by exactly one {@see CommandHandler}, and return nothing.
 */
interface Command
{
}
