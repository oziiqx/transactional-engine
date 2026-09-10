<?php

declare(strict_types=1);

namespace App\Payment\Domain\Payment;

use App\Shared\Domain\ValueObject\Currency;
use App\Shared\Domain\ValueObject\Money;
use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;

/**
 * A completed refund against a payment. Stored as a JSONB array element on the
 * payment row.
 *
 * @psalm-immutable
 */
final readonly class Refund implements JsonSerializable
{
    public function __construct(
        public string $id,
        public Money $amount,
        public string $reason,
        public ?string $gatewayReference,
        public DateTimeImmutable $at,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? null;
        $reason = $data['reason'] ?? null;
        $reference = $data['gatewayReference'] ?? null;
        $amountMinor = $data['amountMinor'] ?? null;
        $currency = $data['currency'] ?? null;
        $at = $data['at'] ?? null;

        return new self(
            \is_string($id) && $id !== '' ? $id : '-',
            Money::of(
                \is_int($amountMinor) ? $amountMinor : 0,
                Currency::from(\is_string($currency) && $currency !== '' ? $currency : 'EUR'),
            ),
            \is_string($reason) && $reason !== '' ? $reason : 'unspecified',
            \is_string($reference) && $reference !== '' ? $reference : null,
            new DateTimeImmutable(\is_string($at) && $at !== '' ? $at : 'now'),
        );
    }

    /**
     * @return array{id: string, amountMinor: int, currency: string, reason: string, gatewayReference: ?string, at: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'amountMinor' => $this->amount->minorAmount,
            'currency' => $this->amount->currency->value,
            'reason' => $this->reason,
            'gatewayReference' => $this->gatewayReference,
            'at' => $this->at->format(DateTimeInterface::ATOM),
        ];
    }
}
