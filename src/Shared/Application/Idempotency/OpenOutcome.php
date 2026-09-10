<?php

declare(strict_types=1);

namespace App\Shared\Application\Idempotency;

enum OpenOutcome
{
    case Proceed;
    case Replay;
    case InFlight;
    case FingerprintMismatch;
}
