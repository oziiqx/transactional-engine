<?php

declare(strict_types=1);

namespace App\Wallet\Application\ReconcileWallet;

use App\Shared\Application\Command\Command;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Run a reconciliation pass for one wallet: compare the projected balance to the
 * authoritative source and post a system adjustment entry if they diverge.
 * Usually dispatched by a scheduled batch, but exposed for on-demand runs too.
 */
final readonly class ReconcileWalletCommand implements Command
{
    /**
     * @param non-empty-string $walletId
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public string $walletId,
    ) {
    }
}
