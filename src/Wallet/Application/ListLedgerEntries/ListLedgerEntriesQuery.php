<?php

declare(strict_types=1);

namespace App\Wallet\Application\ListLedgerEntries;

use App\Shared\Application\Query\Query;
use App\Wallet\Application\ReadModel\LedgerEntryPage;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @implements Query<LedgerEntryPage>
 */
final readonly class ListLedgerEntriesQuery implements Query
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public string $walletId,
        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 25,
        #[Assert\Length(min: 1, max: 128)]
        public ?string $cursor = null,
    ) {
    }
}
