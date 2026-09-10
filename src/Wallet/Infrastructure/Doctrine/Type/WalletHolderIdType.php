<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Doctrine\Type;

use App\Shared\Infrastructure\Doctrine\Type\EntityIdType;
use App\Wallet\Domain\Wallet\WalletHolderId;

/**
 * @extends EntityIdType<WalletHolderId>
 */
final class WalletHolderIdType extends EntityIdType
{
    protected function idClass(): string
    {
        return WalletHolderId::class;
    }
}
