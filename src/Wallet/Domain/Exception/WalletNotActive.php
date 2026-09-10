<?php

declare(strict_types=1);

namespace App\Wallet\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;
use App\Wallet\Domain\Wallet\WalletId;
use App\Wallet\Domain\Wallet\WalletStatus;

/**
 * A money movement or lifecycle change was attempted on a wallet whose status
 * forbids it (frozen, or closed). Maps to HTTP 409 Conflict.
 */
final class WalletNotActive extends DomainException
{
    private function __construct(
        string $message,
        private readonly WalletId $walletId,
        private readonly WalletStatus $status,
    ) {
        parent::__construct($message);
    }

    public static function forMovement(WalletId $walletId, WalletStatus $status): self
    {
        return new self(
            \sprintf('Wallet %s is %s and cannot move money.', $walletId->value, $status->value),
            $walletId,
            $status,
        );
    }

    public static function forLifecycleChange(WalletId $walletId, WalletStatus $status): self
    {
        return new self(
            \sprintf('Wallet %s is %s; the requested change is not allowed.', $walletId->value, $status->value),
            $walletId,
            $status,
        );
    }

    public function errorCode(): string
    {
        return 'wallet.not_active';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function context(): array
    {
        return ['walletId' => $this->walletId->value, 'status' => $this->status->value];
    }
}
