<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\UnwrapTwigExceptionListener;
use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Error\RuntimeError;

class UnwrapTwigExceptionListenerTest extends TestCase
{
    /**
     * @dataProvider provideExceptionsToUnwrap
     */
    public function testUnwrapsException(\Exception $exception): void
    {
        $wrappedException = new RuntimeError(
            'An exception has been thrown during rendering of a template.',
            -1,
            null,
            $exception
        );

        $event = new ExceptionEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $wrappedException,
        );

        (new UnwrapTwigExceptionListener())($event);

        $this->assertSame($exception, $event->getThrowable(), 'exception should be unwrapped');
    }

    public function provideExceptionsToUnwrap(): \Generator
    {
        yield 'NoContentResponseException' => [
            new NoContentResponseException(),
        ];

        yield 'RedirectResponseException' => [
            new RedirectResponseException('/foo'),
        ];
    }

    /**
     * @dataProvider provideThrowablesToIgnore
     */
    public function testIgnoresOtherExceptions(\Throwable $throwable): void
    {
        $event = new ExceptionEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );

        (new UnwrapTwigExceptionListener())($event);

        $this->assertSame($throwable, $event->getThrowable(), 'throwable should be left untouched');
    }

    public function provideThrowablesToIgnore(): \Generator
    {
        $exception = new \LogicException('Something went wrong.');

        yield 'arbitrary exception' => [$exception];

        yield 'Twig RuntimeError with arbitrary exception' => [
            new RuntimeError(
                'An exception has been thrown during rendering of a template.',
                -1,
                null,
                $exception
            ),
        ];
    }
}
