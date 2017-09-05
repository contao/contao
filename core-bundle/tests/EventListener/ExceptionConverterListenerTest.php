<?php

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
use Contao\CoreBundle\Tests\TestCase;
use Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the ExceptionConverterListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ExceptionConverterListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new ExceptionConverterListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ExceptionConverterListener', $listener);
    }

    /**
     * Tests converting an AccessDeniedException exception.
     */
    public function testConvertsAccessDeniedExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new AccessDeniedException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\AccessDeniedException', $exception->getPrevious());
    }

    /**
     * Tests converting an ForwardPageNotFoundException exception.
     */
    public function testConvertsForwardPageNotFoundExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new ForwardPageNotFoundException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\ForwardPageNotFoundException', $exception->getPrevious());
    }

    /**
     * Tests converting an IncompleteInstallationException exception.
     */
    public function testConvertsIncompleteInstallationExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new IncompleteInstallationException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);

        $this->assertInstanceOf(
            'Contao\CoreBundle\Exception\IncompleteInstallationException',
            $exception->getPrevious()
        );
    }

    /**
     * Tests converting an InsecureInstallationException exception.
     */
    public function testConvertsInsecureInstallationExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new InsecureInstallationException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\InsecureInstallationException', $exception->getPrevious());
    }

    /**
     * Tests converting an InvalidRequestTokenException exception.
     */
    public function testConvertsInvalidRequestTokenExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new InvalidRequestTokenException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\InvalidRequestTokenException', $exception->getPrevious());
    }

    /**
     * Tests converting an NoActivePageFoundException exception.
     */
    public function testConvertsNoActivePageFoundExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new NoActivePageFoundException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoActivePageFoundException', $exception->getPrevious());
    }

    /**
     * Tests converting an NoLayoutSpecifiedException exception.
     */
    public function testConvertsNoLayoutSpecifiedExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new NoLayoutSpecifiedException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoLayoutSpecifiedException', $exception->getPrevious());
    }

    /**
     * Tests converting an NoRootPageFoundException exception.
     */
    public function testConvertsNoRootPageFoundExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new NoRootPageFoundException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Contao\CoreBundle\Exception\InternalServerErrorHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\NoRootPageFoundException', $exception->getPrevious());
    }

    /**
     * Tests converting an PageNotFoundException exception.
     */
    public function testConvertsPageNotFoundExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new PageNotFoundException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\PageNotFoundException', $exception->getPrevious());
    }

    /**
     * Tests converting an ServiceUnavailableException exception.
     */
    public function testConvertsServiceUnavailableExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new ServiceUnavailableException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException', $exception);

        $this->assertInstanceOf(
            'Lexik\Bundle\MaintenanceBundle\Exception\ServiceUnavailableException',
            $exception->getPrevious()
        );
    }

    /**
     * Tests converting an unknown exception.
     */
    public function testConvertsUnknownExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new \RuntimeException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('RuntimeException', $exception);
    }

    /**
     * Tests converting the derived PageNotFoundException exception.
     */
    public function testConvertsDerivedPageNotFoundExceptions()
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new DerivedPageNotFoundException()
        );

        $listener = new ExceptionConverterListener();
        $listener->onKernelException($event);

        $exception = $event->getException();

        $this->assertInstanceOf('Symfony\Component\HttpKernel\Exception\NotFoundHttpException', $exception);
        $this->assertInstanceOf('Contao\CoreBundle\Exception\PageNotFoundException', $exception->getPrevious());
    }
}
