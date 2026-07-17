<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ExceptionListener
{
    #[AsEventListener]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $statusCode = 500;
        $message = 'Internal Server Error';

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getStatusCode();
            $message = $event->getThrowable()->getMessage();
        }

        $event->setResponse(new JsonResponse([
            'status' => $statusCode,
            'message' => $message
        ]));
    }
}
