<?php

declare(strict_types=1);

namespace App\Wallet\Application\ChangeLifecycle;

enum WalletLifecycleAction: string
{
    case Freeze = 'freeze';
    case Unfreeze = 'unfreeze';
    case Close = 'close';

    /**
     * @return non-empty-list<non-empty-string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
