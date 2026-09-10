<?php

declare(strict_types=1);

namespace App\Wallet\Application\OpenWallet;

use App\Shared\Application\Command\Command;
use App\Shared\Domain\ValueObject\Currency;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Open a new wallet for a holder in a given currency. The id is client-supplied
 * so the operation is safe to retry and the caller knows the id up front.
 */
final readonly class OpenWalletCommand implements Command
{
    /**
     * @param string $currency ISO 4217 alphabetic code
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public string $walletId,
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public string $holderId,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [Currency::class, 'codes'])]
        public string $currency,
    ) {
    }
}
