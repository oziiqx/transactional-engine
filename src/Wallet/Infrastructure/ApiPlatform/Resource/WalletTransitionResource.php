<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\Resource;

use App\Wallet\Application\ChangeLifecycle\WalletLifecycleAction;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Write-only input for `POST /api/wallets/{id}/transitions`.
 */
final class WalletTransitionResource
{
    /**
     * @param string|null $action freeze|unfreeze|close
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [WalletLifecycleAction::class, 'values'])]
        public ?string $action = null,
    ) {
    }
}
