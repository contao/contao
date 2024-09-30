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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Error\RuntimeError;

/**
 * The priority must be higher than the Symfony exception converter listener
 * (defaults to 96) and higher than the Sentry error listener (defaults to 128).
 */
#[AsEventListener(priority: 256)]
class UnwrapTwigExceptionListener
{
    /**
     * If an exception is encountered while rendering a Twig template, Twig will wrap
     * the exception in a Twig\Error\RuntimeError. In case of our response exceptions,
     * we need them to bubble though. Therefore, we unwrap them again here.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if (!$throwable instanceof RuntimeError) {
            return;
        }

        $previous = $throwable->getPrevious();

        if (!$previous instanceof ResponseException) {
            return;
        }

        $event->setThrowable($previous);
    }
}
