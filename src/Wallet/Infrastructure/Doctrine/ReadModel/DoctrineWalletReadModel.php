<?php

declare(strict_types=1);

namespace App\Wallet\Infrastructure\Doctrine\ReadModel;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use App\Wallet\Application\ReadModel\LedgerEntryPage;
use App\Wallet\Application\ReadModel\LedgerEntryView;
use App\Wallet\Application\ReadModel\WalletReadModel;
use App\Wallet\Application\ReadModel\WalletView;
use App\Wallet\Domain\Wallet\WalletId;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Connection;

/**
 * Read side of the Wallet context: raw SQL through DBAL, no ORM hydration. The
 * write model's own tables are queried directly — denormalised enough to serve
 * reads without a separate projection.
 */
final readonly class DoctrineWalletReadModel implements WalletReadModel
{
    private const string CURSOR_PREFIX = 'seq:';

    public function __construct(
        private Connection $connection
    ) {
    }

    public function find(WalletId $walletId): ?WalletView
    {
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT id, holder_id, currency, balance_minor, status,
                       last_sequence, opened_at, closed_at
                FROM wallets
                WHERE id = :id
                SQL,
            [
                'id' => $walletId->value,
            ],
        );

        return $row === false ? null : $this->toWalletView($row);
    }

    public function ledgerHistory(WalletId $walletId, int $limit, ?string $cursor): LedgerEntryPage
    {
        $conditions = 'wallet_id = :walletId';
        $params = [
            'walletId' => $walletId->value,
        ];

        if ($cursor !== null) {
            $conditions .= ' AND sequence < :cursor';
            $params['cursor'] = $this->decodeCursor($cursor);
        }

        // $limit is a validated int<1,100>; fetch one extra row to detect a next page.
        $rows = $this->connection->fetchAllAssociative(
            \sprintf(
                'SELECT id, sequence, direction, amount_minor, amount_currency,
                        balance_after_minor, reason, reference, recorded_at
                 FROM wallet_ledger_entries
                 WHERE %s
                 ORDER BY sequence DESC
                 LIMIT %d',
                $conditions,
                $limit + 1,
            ),
            $params,
        );

        $hasMore = \count($rows) > $limit;

        $items = [];
        foreach (\array_slice($rows, 0, $limit) as $row) {
            $items[] = $this->toLedgerEntryView($row);
        }

        $nextCursor = $hasMore && $items !== []
            ? $this->encodeCursor($items[\count($items) - 1]->sequence)
            : null;

        return new LedgerEntryPage($items, $nextCursor);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toWalletView(array $row): WalletView
    {
        $currency = Currency::from($this->str($row['currency'] ?? null));
        $minor = $this->int($row['balance_minor'] ?? null);
        $closedAt = $row['closed_at'] ?? null;

        return new WalletView(
            id: $this->str($row['id'] ?? null),
            holderId: $this->str($row['holder_id'] ?? null),
            currency: $currency->value,
            balance: Money::of($minor, $currency)->toDecimalString(),
            balanceMinor: max(0, $minor),
            status: $this->str($row['status'] ?? null),
            ledgerEntries: max(0, $this->int($row['last_sequence'] ?? null)),
            openedAt: $this->iso($row['opened_at'] ?? null),
            closedAt: $closedAt === null ? null : $this->iso($closedAt),
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toLedgerEntryView(array $row): LedgerEntryView
    {
        $currency = Currency::from($this->str($row['amount_currency'] ?? null));
        $reference = $row['reference'] ?? null;

        return new LedgerEntryView(
            id: $this->str($row['id'] ?? null),
            sequence: max(1, $this->int($row['sequence'] ?? null)),
            direction: $this->str($row['direction'] ?? null),
            amount: Money::of($this->int($row['amount_minor'] ?? null), $currency)->toDecimalString(),
            balanceAfter: Money::of($this->int($row['balance_after_minor'] ?? null), $currency)->toDecimalString(),
            currency: $currency->value,
            reason: $this->str($row['reason'] ?? null),
            reference: $reference === null ? null : $this->str($reference),
            recordedAt: $this->iso($row['recorded_at'] ?? null),
        );
    }

    /**
     * @return int<0, max>
     */
    private function decodeCursor(string $cursor): int
    {
        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);

        if ($decoded === false || ! str_starts_with($decoded, self::CURSOR_PREFIX)) {
            return 0;
        }

        return max(0, (int) substr($decoded, \strlen(self::CURSOR_PREFIX)));
    }

    /**
     * @return non-empty-string
     */
    private function encodeCursor(int $sequence): string
    {
        $encoded = rtrim(strtr(base64_encode(self::CURSOR_PREFIX . $sequence), '+/', '-_'), '=');

        return $encoded === '' ? '0' : $encoded;
    }

    private function str(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }

    private function int(mixed $value): int
    {
        return \is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @return non-empty-string
     */
    private function iso(mixed $value): string
    {
        $raw = $this->str($value);

        return (new DateTimeImmutable($raw === '' ? 'now' : $raw))->format(DateTimeInterface::ATOM);
    }
}
