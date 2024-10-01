<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ServiceUnavailableException;
use Contao\UnusedArgumentsException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * The priority must be higher than the one of the response exception listener
 * (defaults to 64).
 *
 * @internal
 */
#[AsEventListener(priority: 96)]
class ExceptionConverterListener
{
    private const MAPPER = [
        ForwardPageNotFoundException::class => 'InternalServerErrorHttpException',
        InsecureInstallationException::class => 'InternalServerErrorHttpException',
        InternalServerErrorException::class => 'InternalServerErrorHttpException',
        InvalidRequestTokenException::class => 'BadRequestHttpException',
        NoActivePageFoundException::class => 'NotFoundHttpException',
        NoLayoutSpecifiedException::class => 'InternalServerErrorHttpException',
        NoRootPageFoundException::class => 'NotFoundHttpException',
        PageNotFoundException::class => 'NotFoundHttpException',
        ServiceUnavailableException::class => 'ServiceUnavailableHttpException',
        UnusedArgumentsException::class => 'NotFoundHttpException',
    ];

    /**
     * Maps known exceptions to HTTP exceptions.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $class = $this->getTargetClass($exception);

        if (null === $class) {
            return;
        }

        if ($httpException = $this->convertToHttpException($exception, $class)) {
            $event->setThrowable($httpException);
        }
    }

    private function getTargetClass(\Throwable $exception): string|null
    {
        foreach (self::MAPPER as $source => $target) {
            if ($exception instanceof $source) {
                return $target;
            }
        }

        return null;
    }

    private function convertToHttpException(\Throwable $exception, string $target): HttpException|null
    {
        return match ($target) {
            'BadRequestHttpException' => new BadRequestHttpException($exception->getMessage(), $exception),
            'InternalServerErrorHttpException' => new InternalServerErrorHttpException($exception->getMessage(), $exception),
            'NotFoundHttpException' => new NotFoundHttpException($exception->getMessage(), $exception),
            'ServiceUnavailableHttpException' => new ServiceUnavailableHttpException('', $exception->getMessage(), $exception),
            default => null,
        };
    }
}
