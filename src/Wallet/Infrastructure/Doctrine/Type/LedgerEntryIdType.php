<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Doctrine\Type;

use App\Shared\Infrastructure\Doctrine\Type\EntityIdType;
use App\Wallet\Domain\Ledger\LedgerEntryId;

/**
 * @extends EntityIdType<LedgerEntryId>
 */
final class LedgerEntryIdType extends EntityIdType
{
    protected function idClass(): string
    {
        return LedgerEntryId::class;
    }
}
