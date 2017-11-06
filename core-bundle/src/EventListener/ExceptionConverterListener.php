<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ExceptionConverterListener
{
    /**
     * @var array
     */
    private static $mapper = [
        AccessDeniedException::class => 'AccessDeniedHttpException',
        ForwardPageNotFoundException::class => 'InternalServerErrorHttpException',
        IncompleteInstallationException::class => 'InternalServerErrorHttpException',
        InsecureInstallationException::class => 'InternalServerErrorHttpException',
        InternalServerErrorException::class => 'InternalServerErrorHttpException',
        InvalidRequestTokenException::class => 'InternalServerErrorHttpException',
        NoActivePageFoundException::class => 'InternalServerErrorHttpException',
        NoLayoutSpecifiedException::class => 'InternalServerErrorHttpException',
        NoRootPageFoundException::class => 'InternalServerErrorHttpException',
        PageNotFoundException::class => 'NotFoundHttpException',
        ServiceUnavailableException::class => 'ServiceUnavailableHttpException',
        // Deprecated since Contao 4.1, to be removed in Contao 5.0
        'Contao\CoreBundle\Exception\ServiceUnavailableException' => 'ServiceUnavailableHttpException',
    ];

    /**
     * Maps known exceptions to HTTP exceptions.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();
        $class = $this->getTargetClass($exception);

        if (null === $class) {
            return;
        }

        if (null !== ($httpException = $this->convertToHttpException($exception, $class))) {
            $event->setException($httpException);
        }
    }

    /**
     * Maps the extension to a target class.
     *
     * @param \Exception $exception
     *
     * @return string|null
     */
    private function getTargetClass(\Exception $exception): ?string
    {
        foreach (self::$mapper as $source => $target) {
            if ($exception instanceof $source) {
                return $target;
            }
        }

        return null;
    }

    /**
     * Converts an exception to an HTTP exception.
     *
     * @param \Exception $exception
     * @param string     $target
     *
     * @return HttpException|null
     */
    private function convertToHttpException(\Exception $exception, $target): ?HttpException
    {
        switch ($target) {
            case 'AccessDeniedHttpException':
                return new AccessDeniedHttpException($exception->getMessage(), $exception);

            case 'InternalServerErrorHttpException':
                return new InternalServerErrorHttpException($exception->getMessage(), $exception);

            case 'NotFoundHttpException':
                return new NotFoundHttpException($exception->getMessage(), $exception);

            case 'ServiceUnavailableHttpException':
                return new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);
        }

        return null;
    }
}
