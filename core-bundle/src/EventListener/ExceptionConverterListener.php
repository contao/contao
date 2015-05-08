<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
        'Contao\\CoreBundle\\Exception\\AccessDeniedException'           => 'AccessDeniedHttpException',
        'Contao\\CoreBundle\\Exception\\ForwardPageNotFoundException'    => 'InternalServerErrorHttpException',
        'Contao\\CoreBundle\\Exception\\IncompleteInstallationException' => 'InternalServerErrorHttpException',
        'Contao\\CoreBundle\\Exception\\InsecureInstallationException'   => 'InternalServerErrorHttpException',
        'Contao\\CoreBundle\\Exception\\InvalidRequestTokenException'    => 'BadRequestHttpException',
        'Contao\\CoreBundle\\Exception\\NoActivePageFoundException'      => 'InternalServerErrorHttpException',
        'Contao\\CoreBundle\\Exception\\NoLayoutSpecifiedException'      => 'InternalServerErrorHttpException',
        'Contao\\CoreBundle\\Exception\\NoRootPageFoundException'        => 'InternalServerErrorHttpException',
        'Contao\\CoreBundle\\Exception\\PageNotFoundException'           => 'NotFoundHttpException',
        'Contao\\CoreBundle\\Exception\\ServiceUnavailableException'     => 'ServiceUnavailableHttpException',
    ];

    /**
     * Maps known exceptions to HTTP exceptions.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $class     = get_class($exception);

        if (!isset($this->mapper[$class])) {
            return;
        }

        if (null !== ($httpException = $this->convertToHttpException($exception, $this->mapper[$class]))) {
            $event->setException($httpException);
        }
    }

    /**
     * Converts an exception to an HTTP exception.
     *
     * @param \Exception $exception The exception object
     * @param string     $target    The target exception
     *
     * @return HttpExceptionInterface|null The HTTP exception or null
     */
    private function convertToHttpException(\Exception $exception, $target)
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
                return new ServiceUnavailableHttpException(null, $exception->getMessage(), $exception);
        }

        return null;
    }
}
