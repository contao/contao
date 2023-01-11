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

use Contao\CoreBundle\EventListener\ExceptionConverterListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InsufficientAuthenticationException;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\ServiceUnavailableException;
use Contao\CoreBundle\Fixtures\Exception\DerivedPageNotFoundException;
use Contao\UnusedArgumentsException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ExceptionConverterListenerTest extends TestCase
{
    public function testConvertsAccessDeniedExceptions(): void
    {
        $event = $this->getResponseEvent(new AccessDeniedException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(AccessDeniedHttpException::class, $exception);
        $this->assertInstanceOf(AccessDeniedException::class, $exception->getPrevious());
    }

    public function testConvertsForwardPageNotFoundExceptions(): void
    {
        $event = $this->getResponseEvent(new ForwardPageNotFoundException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(ForwardPageNotFoundException::class, $exception->getPrevious());
    }

    public function testConvertsInsecureInstallationExceptions(): void
    {
        $event = $this->getResponseEvent(new InsecureInstallationException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(InsecureInstallationException::class, $exception->getPrevious());
    }

    public function testConvertsInsufficientAuthenticationExceptions(): void
    {
        $event = $this->getResponseEvent(new InsufficientAuthenticationException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(UnauthorizedHttpException::class, $exception);
        $this->assertInstanceOf(InsufficientAuthenticationException::class, $exception->getPrevious());
    }

    public function testConvertsInvalidRequestTokenExceptions(): void
    {
        $event = $this->getResponseEvent(new InvalidRequestTokenException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(BadRequestHttpException::class, $exception);
        $this->assertInstanceOf(InvalidRequestTokenException::class, $exception->getPrevious());
    }

    public function testConvertsNoActivePageFoundExceptions(): void
    {
        $event = $this->getResponseEvent(new NoActivePageFoundException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertInstanceOf(NoActivePageFoundException::class, $exception->getPrevious());
    }

    public function testConvertsNoLayoutSpecifiedExceptions(): void
    {
        $event = $this->getResponseEvent(new NoLayoutSpecifiedException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(InternalServerErrorHttpException::class, $exception);
        $this->assertInstanceOf(NoLayoutSpecifiedException::class, $exception->getPrevious());
    }

    public function testConvertsNoRootPageFoundExceptions(): void
    {
        $event = $this->getResponseEvent(new NoRootPageFoundException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertInstanceOf(NoRootPageFoundException::class, $exception->getPrevious());
    }

    public function testConvertsPageNotFoundExceptions(): void
    {
        $event = $this->getResponseEvent(new PageNotFoundException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertInstanceOf(PageNotFoundException::class, $exception->getPrevious());
    }

    public function testConvertsServiceUnavailableExceptions(): void
    {
        $event = $this->getResponseEvent(new ServiceUnavailableException(''));

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(ServiceUnavailableHttpException::class, $exception);
        $this->assertInstanceOf(ServiceUnavailableException::class, $exception->getPrevious());
    }

    public function testDoesNotConvertUnknownExceptions(): void
    {
        $e = new \RuntimeException();
        $event = $this->getResponseEvent($e);

        $listener = new ExceptionConverterListener();
        $listener($event);

        $this->assertSame($e, $event->getThrowable());
    }

    public function testConvertsDerivedPageNotFoundExceptions(): void
    {
        $event = $this->getResponseEvent(new DerivedPageNotFoundException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertInstanceOf(PageNotFoundException::class, $exception->getPrevious());
    }

    public function testConvertsUnusedArgumentsExceptions(): void
    {
        $event = $this->getResponseEvent(new UnusedArgumentsException());

        $listener = new ExceptionConverterListener();
        $listener($event);

        $exception = $event->getThrowable();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertInstanceOf(UnusedArgumentsException::class, $exception->getPrevious());
    }

    private function getResponseEvent(\Exception $exception): ExceptionEvent
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = new Request();

        return new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
    }
}
