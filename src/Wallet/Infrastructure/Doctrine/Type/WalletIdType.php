<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Doctrine\Type;

use App\Shared\Infrastructure\Doctrine\Type\EntityIdType;
use App\Wallet\Domain\Wallet\WalletId;

/**
 * @extends EntityIdType<WalletId>
 */
final class WalletIdType extends EntityIdType
{
    protected function idClass(): string
    {
        return WalletId::class;
    }
}
