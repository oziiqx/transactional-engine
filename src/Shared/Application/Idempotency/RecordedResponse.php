<?php

declare(strict_types=1);

namespace App\Shared\Application\Idempotency;

/**
 * The stored outcome of a completed idempotent request.
 *
 * @psalm-immutable
 */
final readonly class RecordedResponse
{
    /**
     * @param int<100, 599>         $statusCode
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers = [],
    ) {
    }

    /**
     * @return array{statusCode: int, body: string, headers: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'statusCode' => $this->statusCode,
            'body' => $this->body,
            'headers' => $this->headers,
        ];
    }

    /**
     * @param array{statusCode: int, body: string, headers: array<string, string>} $data
     */
    public static function fromArray(array $data): self
    {
        /** @var int<100, 599> $status */
        $status = $data['statusCode'];

        return new self($status, $data['body'], $data['headers']);
    }
}
