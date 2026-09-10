<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\ApiPlatform\State;

use ApiPlatform\State\ProcessorInterface;
use App\Shared\Application\Command\CommandBus;
use App\Shared\Application\Query\QueryBus;
use App\Wallet\Application\GetWallet\GetWalletQuery;
use App\Wallet\Infrastructure\ApiPlatform\Resource\WalletResource;
use Symfony\Component\HttpFoundation\Request;

/**
 * Shared plumbing for the write operations on a wallet: dispatch a command, then
 * return the wallet's fresh representation from the read side.
 *
 * @implements ProcessorInterface<mixed, WalletResource>
 */
abstract readonly class WalletWriteProcessor implements ProcessorInterface
{
    public function __construct(
        protected CommandBus $commandBus,
        protected QueryBus $queryBus,
    ) {
    }

    protected function representationOf(string $walletId): WalletResource
    {
        return WalletResource::fromView($this->queryBus->ask(new GetWalletQuery($walletId)));
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return non-empty-string|null
     */
    protected function idempotencyKeyFrom(array $context): ?string
    {
        $request = $context['request'] ?? null;

        if (! $request instanceof Request) {
            return null;
        }

        $key = $request->headers->get('Idempotency-Key');

        return $key !== null && $key !== '' ? $key : null;
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    protected function walletIdFrom(array $uriVariables): string
    {
        return (string) ($uriVariables['walletId'] ?? $uriVariables['id'] ?? '');
    }
}
