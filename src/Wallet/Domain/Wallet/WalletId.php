<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Wallet;

use App\Shared\Domain\Aggregate\EntityId;

/**
 * Identity of a {@see Wallet} aggregate.
 *
 * @psalm-immutable
 */
final readonly class WalletId extends EntityId
{
}
