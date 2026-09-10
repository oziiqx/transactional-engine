<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

use App\Shared\Domain\ValueObject\Currency;

/**
 * An operation combined two monetary amounts denominated in different currencies.
 * This is always a modelling error at the call site, surfaced as HTTP 422.
 */
final class CurrencyMismatch extends DomainException
{
    private function __construct(
        private readonly Currency $left,
        private readonly Currency $right,
    ) {
        parent::__construct(\sprintf(
            'Cannot operate on %s and %s amounts together; convert first.',
            $left->value,
            $right->value,
        ));
    }

    public static function between(Currency $left, Currency $right): self
    {
        return new self($left, $right);
    }

    public function errorCode(): string
    {
        return 'money.currency_mismatch';
    }

    public function context(): array
    {
        return ['left' => $this->left->value, 'right' => $this->right->value];
    }
}
