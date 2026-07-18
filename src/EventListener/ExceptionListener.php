<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Contracts\Service\Attribute\Required;

final class ExceptionListener
{
    #[Required]
    public LoggerInterface $logger;

    #[AsEventListener]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpException) {
            $this->logger->error('Unhandled exception', ['exception' => $exception]);
            $event->setResponse(new JsonResponse([
                'status' => 500,
                'message' => 'Internal Server Error',
            ], 500));

            return;
        }

        $previous = $exception->getPrevious();

        if ($previous instanceof ValidationFailedException) {
            $violations = $previous->getViolations();
            $errors = [];

            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            $event->setResponse(new JsonResponse([
                'status' => $exception->getStatusCode(),
                'message' => 'Validation failed',
                'errors' => $errors,
            ], $exception->getStatusCode()));

            return;
        }

        $event->setResponse(new JsonResponse([
            'status' => $exception->getStatusCode(),
            'message' => $exception->getMessage(),
        ], $exception->getStatusCode()));
    }
}
