<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

use App\Shared\Domain\Aggregate\EntityId;

/**
 * @psalm-immutable
 */
final readonly class PaymentId extends EntityId
{
}
