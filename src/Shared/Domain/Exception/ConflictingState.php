<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * The aggregate is not in a state that permits the requested transition
 * (e.g. refunding a payment that never completed). Maps to HTTP 409 Conflict.
 */
final class ConflictingState extends DomainException
{
    /**
     * @param non-empty-string $code
     * @param array<string, scalar|array<array-key, scalar>|null> $context
     */
    private function __construct(
        string $message,
        private readonly string $code,
        private readonly array $context = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @param non-empty-string $code
     * @param non-empty-string $message
     * @param array<string, scalar|array<array-key, scalar>|null> $context
     */
    public static function of(string $code, string $message, array $context = []): self
    {
        return new self($message, $code, $context);
    }

    /**
     * @param non-empty-string $transition
     * @param non-empty-string $currentState
     */
    public static function cannotTransition(string $transition, string $currentState): self
    {
        return new self(
            \sprintf('Cannot "%s" while in state "%s".', $transition, $currentState),
            'state.conflict',
            ['transition' => $transition, 'currentState' => $currentState],
        );
    }

    public function errorCode(): string
    {
        return $this->code;
    }

    public function httpStatus(): int
    {
        return 409;
    }

    public function context(): array
    {
        return $this->context;
    }
}
