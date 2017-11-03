<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\ExceptionConverterListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ForwardPageNotFoundException;
use Contao\CoreBundle\Exception\IncompleteInstallationException;
use Contao\CoreBundle\Exception\InsecureInstallationException;
use Contao\CoreBundle\Exception\InvalidRequestTokenException;
use Contao\CoreBundle\Exception\NoActivePageFoundException;
use Contao\CoreBundle\Exception\NoLayoutSpecifiedException;
use Contao\CoreBundle\Exception\NoRootPageFoundException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Tests\Fixtures\Exception\DerivedPageNotFoundException;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class ExceptionConverterListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new ExceptionConverterListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ExceptionConverterListener', $listener);
    }

    public function testConvertsAccessDeniedExceptions(): void
    {
        $event = $this->mockResponseEvent(new AccessDeniedException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\AccessDeniedException', $exception->getPrevious());
    }

    public function testConvertsForwardPageNotFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new ForwardPageNotFoundException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\ForwardPageNotFoundException', $exception->getPrevious());
    }

    public function testConvertsIncompleteInstallationExceptions(): void
    {
        $event = $this->mockResponseEvent(new IncompleteInstallationException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\IncompleteInstallationException', $exception->getPrevious());
    }

    public function testConvertsInsecureInstallationExceptions(): void
    {
        $event = $this->mockResponseEvent(new InsecureInstallationException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\InsecureInstallationException', $exception->getPrevious());
    }

    public function testConvertsInvalidRequestTokenExceptions(): void
    {
        $event = $this->mockResponseEvent(new InvalidRequestTokenException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\InvalidRequestTokenException', $exception->getPrevious());
    }

    public function testConvertsNoActivePageFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new NoActivePageFoundException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoActivePageFoundException', $exception->getPrevious());
    }

    public function testConvertsNoLayoutSpecifiedExceptions(): void
    {
        $event = $this->mockResponseEvent(new NoLayoutSpecifiedException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoLayoutSpecifiedException', $exception->getPrevious());
    }

    public function testConvertsNoRootPageFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new NoRootPageFoundException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoRootPageFoundException', $exception->getPrevious());
    }

    public function testConvertsPageNotFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new PageNotFoundException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\PageNotFoundException', $exception->getPrevious());
    }

    public function testConvertsServiceUnavailableExceptions(): void
    {
        $event = $this->mockResponseEvent(new ServiceUnavailableException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException', $exception);
        $this->assertInstanceOf('Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException', $exception->getPrevious());
    }

    public function testConvertsUnknownExceptions(): void
    {
        $event = $this->mockResponseEvent(new \RuntimeException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('RuntimeException', $exception);
    }

    public function testConvertsDerivedPageNotFoundExceptions(): void
    {
        $event = $this->mockResponseEvent(new DerivedPageNotFoundException());

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\PageNotFoundException', $exception->getPrevious());
    }

    /**
     * Mocks a response event.
     *
     * @param \Exception $exception
     *
     * @return GetResponseForExceptionEvent
     */
    private function mockResponseEvent(\Exception $exception): GetResponseForExceptionEvent
    {
        $kernel = $this->createMock(KernelInterface::class);
        $request = new Request();

        return new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, $exception);
    }
}
