<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Doctrine\Repository;

use App\Shared\Domain\Exception\NotFound;
use App\Shared\Domain\ValueObject\Currency;
use App\Wallet\Domain\Wallet\Wallet;
use App\Wallet\Domain\Wallet\WalletHolderId;
use App\Wallet\Domain\Wallet\WalletId;
use App\Wallet\Domain\Wallet\Wallets;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Doctrine adapter for the {@see Wallets} port.
 *
 * `save()` persists the aggregate plus the ledger rows it produced in this unit
 * of work, then flushes. The surrounding `doctrine_transaction` messenger
 * middleware provides the atomic boundary; the flush here just makes the write
 * visible to the domain-event collector before commit.
 */
final class DoctrineWallets implements Wallets
{
    /**
     * @var EntityRepository<Wallet>
     */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        $this->repository = $entityManager->getRepository(Wallet::class);
    }

    public function nextIdentity(): WalletId
    {
        return WalletId::generate();
    }

    public function get(WalletId $walletId): Wallet
    {
        return $this->repository->find($walletId)
            ?? throw NotFound::of('Wallet', $walletId->value);
    }

    public function findByHolderAndCurrency(WalletHolderId $holderId, Currency $currency): ?Wallet
    {
        return $this->repository->findOneBy([
            'holderId' => $holderId,
            'currency' => $currency,
        ]);
    }

    public function save(Wallet $wallet): void
    {
        $this->entityManager->persist($wallet);

        foreach ($wallet->releaseUncommittedEntries() as $entry) {
            $this->entityManager->persist($entry);
        }

        $this->entityManager->flush();
    }
}
