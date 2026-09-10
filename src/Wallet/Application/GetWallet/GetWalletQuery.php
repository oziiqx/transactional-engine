<?php

declare(strict_types=1);

namespace App\Wallet\Application\GetWallet;

use App\Shared\Application\Query\Query;
use App\Wallet\Application\ReadModel\WalletView;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements Query<WalletView>
 */
final readonly class GetWalletQuery implements Query
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public string $walletId,
    ) {
    }
}
