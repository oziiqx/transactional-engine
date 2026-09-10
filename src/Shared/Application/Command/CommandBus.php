<?php

declare(strict_types=1);

namespace App\Shared\Application\Command;

use App\Shared\Domain\Exception\DomainException;

/**
 * Port the application layer uses to dispatch commands. The Messenger-backed
 * adapter lives in the infrastructure layer.
 */
interface CommandBus
{
    /**
     * Handle the command synchronously inside a database transaction.
     *
     * @throws DomainException when a business rule rejects the command
     */
    public function dispatch(Command $command): void;
}
