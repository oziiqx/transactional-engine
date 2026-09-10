<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Wallet\Application\ReadModel\WalletView;
use App\Wallet\Infrastructure\ApiPlatform\State\LedgerHistoryProvider;
use App\Wallet\Infrastructure\ApiPlatform\State\OpenWalletProcessor;
use App\Wallet\Infrastructure\ApiPlatform\State\ReconcileWalletProcessor;
use App\Wallet\Infrastructure\ApiPlatform\State\WalletMovementProcessor;
use App\Wallet\Infrastructure\ApiPlatform\State\WalletProvider;
use App\Wallet\Infrastructure\ApiPlatform\State\WalletTransitionProcessor;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The wallet REST/GraphQL surface. Reads come from the read model; every write is
 * a thin operation that translates its input into a CQRS command and then returns
 * the wallet's fresh representation.
 */
#[ApiResource(
    shortName: 'Wallet',
    operations: [
        new Get(
            uriTemplate: '/wallets/{id}',
            provider: WalletProvider::class,
        ),
        new Get(
            uriTemplate: '/wallets/{id}/ledger-entries',
            paginationEnabled: false,
            output: LedgerPageResource::class,
            provider: LedgerHistoryProvider::class,
        ),
        new Post(
            uriTemplate: '/wallets',
            status: 201,
            processor: OpenWalletProcessor::class,
        ),
        new Post(
            uriTemplate: '/wallets/{id}/credits',
            status: 200,
            input: WalletMovementResource::class,
            read: false,
            processor: WalletMovementProcessor::class,
            extraProperties: [
                'movement_direction' => 'credit',
            ],
        ),
        new Post(
            uriTemplate: '/wallets/{id}/debits',
            status: 200,
            input: WalletMovementResource::class,
            read: false,
            processor: WalletMovementProcessor::class,
            extraProperties: [
                'movement_direction' => 'debit',
            ],
        ),
        new Post(
            uriTemplate: '/wallets/{id}/transitions',
            status: 200,
            input: WalletTransitionResource::class,
            read: false,
            processor: WalletTransitionProcessor::class,
        ),
        new Post(
            uriTemplate: '/wallets/{id}/reconciliations',
            status: 200,
            input: false,
            read: false,
            processor: ReconcileWalletProcessor::class,
        ),
    ],
)]
final class WalletResource
{
    public function __construct(
        public ?string $id = null,
        #[Assert\NotBlank]
        #[Assert\Uuid(strict: true)]
        public ?string $holderId = null,
        #[Assert\NotBlank]
        public ?string $currency = null,
        public ?string $balance = null,
        public ?int $balanceMinor = null,
        public ?string $status = null,
        public ?int $ledgerEntries = null,
        public ?string $openedAt = null,
        public ?string $closedAt = null,
    ) {
    }

    public static function fromView(WalletView $view): self
    {
        return new self(
            id: $view->id,
            holderId: $view->holderId,
            currency: $view->currency,
            balance: $view->balance,
            balanceMinor: $view->balanceMinor,
            status: $view->status,
            ledgerEntries: $view->ledgerEntries,
            openedAt: $view->openedAt,
            closedAt: $view->closedAt,
        );
    }
}
