<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

/**
 * A referenced aggregate does not exist. Maps to HTTP 404 Not Found.
 */
final class NotFound extends DomainException
{
    private function __construct(
        private readonly string $type,
        private readonly string $id,
    ) {
        parent::__construct(\sprintf('%s "%s" was not found.', $type, $id));
    }

    /**
     * @param string $type   human label, e.g. "Wallet"
     */
    public static function of(string $type, string $id): self
    {
        return new self($type, $id);
    }

    public function errorCode(): string
    {
        return 'resource.not_found';
    }

    public function httpStatus(): int
    {
        return 404;
    }

    public function context(): array
    {
        return [
            'type' => $this->type,
            'id' => $this->id,
        ];
    }
}
