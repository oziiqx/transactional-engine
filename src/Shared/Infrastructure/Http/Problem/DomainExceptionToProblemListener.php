<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Problem;

use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders every {@see DomainException} as an RFC 7807 `application/problem+json`
 * document, with the domain's stable `code` and `context` as extension members.
 * Runs ahead of API Platform's own error handling.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 96)]
final readonly class DomainExceptionToProblemListener
{
    /**
     * @param non-empty-string $problemBaseUri
     */
    public function __construct(
        private string $problemBaseUri = 'https://docs.transactional-engine.dev/problems/',
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof DomainException) {
            return;
        }

        $status = $throwable->httpStatus();

        $problem = [
            'type' => $this->problemBaseUri . $throwable->errorCode(),
            'title' => Response::$statusTexts[$status] ?? 'Domain rule violation',
            'status' => $status,
            'detail' => $throwable->getMessage(),
            'code' => $throwable->errorCode(),
        ];

        if ($throwable->context() !== []) {
            $problem['context'] = $throwable->context();
        }

        $event->setResponse(new JsonResponse(
            $problem,
            $status,
            ['Content-Type' => 'application/problem+json'],
        ));
    }
}
