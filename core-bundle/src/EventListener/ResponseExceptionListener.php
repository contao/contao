<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener;

use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Creates a response from an exception.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ResponseExceptionListener
{
    /**
     * Sets the response from the exception.
     *
     * @param GetResponseForExceptionEvent $event The event object
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof ResponseException) {
            return;
        }

        $event->setResponse($exception->getResponse());
    }
}
