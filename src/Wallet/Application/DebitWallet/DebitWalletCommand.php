<?php

declare(strict_types=1);

namespace App\Wallet\Application\DebitWallet;

use App\Shared\Application\Command\Command;
use App\Shared\Domain\ValueObject\Currency;
use App\Wallet\Domain\Ledger\LedgerReason;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Post a debit (money out) to a wallet's ledger. Fails if the wallet cannot
 * cover it — there is no overdraft.
 */
final readonly class DebitWalletCommand implements Command
{
    /**
     * @param non-empty-string      $walletId
     * @param numeric-string        $amount
     * @param non-empty-string      $currency
     * @param non-empty-string      $reason
     * @param non-empty-string|null $reference
     * @param non-empty-string|null $idempotencyKey
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
