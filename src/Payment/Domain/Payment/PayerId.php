<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

use App\Shared\Domain\Aggregate\EntityId;

/**
 * Reference to the party being charged. Opaque to this context.
 *
 * @psalm-immutable
 */
final readonly class PayerId extends EntityId
{
}
