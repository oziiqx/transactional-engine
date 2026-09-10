<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Wallet;

use App\Shared\Domain\Aggregate\EntityId;

/**
 * Reference to whoever owns a wallet (a customer or a merchant in the wider
 * platform). This context treats it as an opaque identifier.
 *
 * @psalm-immutable
 */
final readonly class WalletHolderId extends EntityId
{
}
