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

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ServiceUnavailableException;
use Contao\UnusedArgumentsException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * @internal
 */
class ExceptionConverterListener
{
    private const MAPPER = [
        AccessDeniedException::class => 'AccessDeniedHttpException',
        ForwardPageNotFoundException::class => 'InternalServerErrorHttpException',
        InsecureInstallationException::class => 'InternalServerErrorHttpException',
        InsufficientAuthenticationException::class => 'UnauthorizedHttpException',
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

        if (null !== ($httpException = $this->convertToHttpException($exception, $class))) {
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
        switch ($target) {
            case 'AccessDeniedHttpException':
                return new AccessDeniedHttpException($exception->getMessage(), $exception);

            case 'BadRequestHttpException':
                return new BadRequestHttpException($exception->getMessage(), $exception);

            case 'InternalServerErrorHttpException':
                return new InternalServerErrorHttpException($exception->getMessage(), $exception);

            case 'NotFoundHttpException':
                return new NotFoundHttpException($exception->getMessage(), $exception);

            case 'ServiceUnavailableHttpException':
                return new ServiceUnavailableHttpException('', $exception->getMessage(), $exception);

            case 'UnauthorizedHttpException':
                return new UnauthorizedHttpException('', $exception->getMessage(), $exception);
        }

        return null;
    }
}
