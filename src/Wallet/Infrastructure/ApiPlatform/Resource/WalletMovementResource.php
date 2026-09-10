<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\Resource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Write-only input for `POST /api/wallets/{id}/credits` and `.../debits`.
 *
 * The idempotency token is read from the `Idempotency-Key` request header by the
 * processor, not from this body, so a proxy retry replays it automatically.
 */
final class WalletMovementResource
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Regex('/^\d+(\.\d+)?$/')]
        public ?string $amount = null,
        #[Assert\NotBlank]
        public ?string $currency = null,
        #[Assert\NotBlank]
        public ?string $reason = null,
        public ?string $reference = null,
    ) {
    }
}
