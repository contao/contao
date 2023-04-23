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

use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Twig\Error\RuntimeError;

class UnwrapTwigExceptionListener
{
    private static array $exceptionsToUnwrap = [
        NoContentResponseException::class,
        RedirectResponseException::class,
    ];

    /**
     * If an exception is encountered while rendering a Twig template, Twig
     * will wrap the exception in a Twig\Error\RuntimeError. For cases that we
     * want the exception to bubble, we need to unwrap the original exception
     * again.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        if (
            !($throwable = $event->getThrowable()) instanceof RuntimeError ||
            ($previous = $throwable->getPrevious()) === null
        ) {
            return;
        }

        foreach (self::$exceptionsToUnwrap as $exceptionToUnwrap) {
            if ($previous instanceof $exceptionToUnwrap) {
                $event->setThrowable($previous);

                return;
            }
        }
    }
}
