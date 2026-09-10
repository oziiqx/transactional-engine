<?php

declare(strict_types=1);

namespace App\Wallet\Application\CreditWallet;

use App\Shared\Application\Command\Command;
use App\Shared\Domain\ValueObject\Currency;
use App\Wallet\Domain\Ledger\LedgerReason;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Post a credit (money in) to a wallet's ledger.
 */
final readonly class CreditWalletCommand implements Command
{
    /**
     * @param string        $amount    plain decimal, e.g. "25.00"
     * @param string|null $reference       external correlation id
     * @param string|null $idempotencyKey  makes the request replay-safe
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public string $walletId,
        #[Assert\NotBlank]
        #[Assert\Regex('/^\d+(\.\d+)?$/', message: 'Amount must be a non-negative decimal string.')]
        public string $amount,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [Currency::class, 'codes'])]
        public string $currency,
        #[Assert\NotBlank]
        #[Assert\Choice(callback: [LedgerReason::class, 'clientAssignable'])]
        public string $reason,
        #[Assert\Length(min: 1, max: 255)]
        public ?string $reference = null,
        #[Assert\Length(min: 8, max: 255)]
        public ?string $idempotencyKey = null,
    ) {
    }
}
