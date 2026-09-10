<?php

declare(strict_types=1);

namespace App\Shared\Application\Event;

/**
 * Marker for messages that are meant to leave the process.
 *
 * Domain events stay in-process and private to their context. When another
 * context (or an external consumer) needs to know something happened, a listener
 * builds an integration message from the domain event and dispatches it; the
 * messenger routing config sends everything implementing this interface to a
 * durable transport.
 */
interface IntegrationMessage
{
    /**
     * Schema version of the message body, bumped on breaking changes so
     * consumers can branch.
     */
    public function schemaVersion(): int;
}
