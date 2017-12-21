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

use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ResponseExceptionListener
{
    /**
     * Sets the response from the exception.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        $exception = $event->getException();

        if (!$exception instanceof ResponseException) {
            return;
        }

        $event->setResponse($exception->getResponse());
    }
}
