<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Converts exceptions to HTTP exceptions.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ExceptionConverterListener
{
    /**
     * @var array
     */
    private $mapper = [
        'Contao\CoreBundle\Exception\AccessDeniedException' => 'AccessDeniedHttpException',
        'Contao\CoreBundle\Exception\ForwardPageNotFoundException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\IncompleteInstallationException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\InsecureInstallationException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\InternalServerErrorException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\InvalidRequestTokenException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\NoActivePageFoundException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\NoLayoutSpecifiedException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\NoRootPageFoundException' => 'InternalServerErrorHttpException',
        'Contao\CoreBundle\Exception\PageNotFoundException' => 'NotFoundHttpException',
        'Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException' => 'ServiceUnavailableHttpException',
        // Deprecated since Contao 4.1, to be removed in Contao 5.0
        'Contao\CoreBundle\Exception\ServiceUnavailableException' => 'ServiceUnavailableHttpException',
    ];

    /**
     * Maps known exceptions to HTTP exceptions.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
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
    private function getTargetClass(\Exception $exception)
    {
        foreach ($this->mapper as $source => $target) {
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
    private function convertToHttpException(\Exception $exception, $target)
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
