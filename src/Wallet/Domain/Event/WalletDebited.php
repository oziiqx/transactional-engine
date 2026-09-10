<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Event;

use DateTimeImmutable;

/**
 * @psalm-immutable
 */
final readonly class WalletDebited extends WalletDomainEvent
{
    /**
     * @param non-empty-string      $walletId
     * @param non-empty-string      $ledgerEntryId
     * @param positive-int          $sequence
     * @param non-empty-string      $currency
     * @param non-empty-string      $reason
     * @param non-empty-string|null $reference
     */
    public function __construct(
        string $walletId,
        public string $ledgerEntryId,
        public int $sequence,
        public int $amountMinor,
        public string $currency,
        public int $balanceAfterMinor,
        public string $reason,
        public ?string $reference,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($walletId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'wallet.debited';
    }

    public function payload(): array
    {
        return [
            'walletId' => $this->walletId,
            'ledgerEntryId' => $this->ledgerEntryId,
            'sequence' => $this->sequence,
            'amountMinor' => $this->amountMinor,
            'currency' => $this->currency,
            'balanceAfterMinor' => $this->balanceAfterMinor,
            'reason' => $this->reason,
            'reference' => $this->reference,
        ];
    }
}
