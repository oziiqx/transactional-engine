<?php

declare(strict_types=1);

namespace App\Wallet\Application\ChangeLifecycle;

use App\Shared\Application\Command\Command;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Move a wallet along its lifecycle: freeze, unfreeze or close it.
 */
final readonly class ChangeWalletLifecycleCommand implements Command
{
    /**
     * @param non-empty-string $walletId
     * @param non-empty-string $action   one of freeze|unfreeze|close
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public string $walletId,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [WalletLifecycleAction::class, 'values'])]
        public string $action,
    ) {
    }
}
