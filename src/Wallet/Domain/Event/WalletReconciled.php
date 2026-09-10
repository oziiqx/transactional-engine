<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Event;

use DateTimeImmutable;

/**
 * Recorded after every reconciliation pass, whether or not an adjustment was
 * needed. A non-zero {@see discrepancyMinor} means the projected balance had
 * drifted from the authoritative one and a system entry was posted to correct it.
 *
 * @psalm-immutable
 */
final readonly class WalletReconciled extends WalletDomainEvent
{
    public function __construct(
        string $walletId,
        public int $previousBalanceMinor,
        public int $authoritativeBalanceMinor,
        public int $discrepancyMinor,
        public string $currency,
        DateTimeImmutable $occurredOn,
    ) {
        parent::__construct($walletId, $occurredOn);
    }

    public function eventName(): string
    {
        return 'wallet.reconciled';
    }

    public function wasAdjusted(): bool
    {
        return $this->discrepancyMinor !== 0;
    }

    public function payload(): array
    {
        return [
            'walletId' => $this->walletId,
            'previousBalanceMinor' => $this->previousBalanceMinor,
            'authoritativeBalanceMinor' => $this->authoritativeBalanceMinor,
            'discrepancyMinor' => $this->discrepancyMinor,
            'currency' => $this->currency,
            'adjusted' => $this->wasAdjusted(),
        ];
    }
}
