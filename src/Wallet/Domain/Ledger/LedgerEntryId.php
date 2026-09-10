<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Ledger;

use App\Shared\Domain\Aggregate\EntityId;

/**
 * Identity of a single immutable {@see LedgerEntry}.
 *
 * @psalm-immutable
 */
final readonly class LedgerEntryId extends EntityId
{
}
