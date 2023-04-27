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

use Contao\CoreBundle\Exception\ResponseException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Error\RuntimeError;

class UnwrapTwigExceptionListener
{
    /**
     * If an exception is encountered while rendering a Twig template, Twig
     * will wrap the exception in a Twig\Error\RuntimeError. In case of our
     * response exceptions, we need them to bubble, though. Therefore, we
     * unwrap them again, here.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if (
            !($throwable = $event->getThrowable()) instanceof RuntimeError ||
            !is_a($previous = $throwable->getPrevious(), ResponseException::class)
        ) {
            return;
        }

        $event->setThrowable($previous);
    }
}
