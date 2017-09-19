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

use Contao\CoreBundle\EventListener\ResponseExceptionListener;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the ResponseExceptionListener class.
 */
class ResponseExceptionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $listener = new ResponseExceptionListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ResponseExceptionListener', $listener);
    }

    /**
     * Tests passing a response exception.
     */
    public function testAddsAResponseToTheEvent(): void
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new ResponseException(new Response('Foo'))
        );

        $listener = new ResponseExceptionListener();
        $listener->onKernelException($event);

        $this->assertTrue($event->hasResponse());

        $response = $event->getResponse();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Foo', $response->getContent());
    }

    /**
     * Tests passing a non-response exception.
     */
    public function testDoesNotAddAResponseToTheEventIfTheExceptionIsNotAResponseException(): void
    {
        $event = new GetResponseForExceptionEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new \RuntimeException()
        );

        $listener = new ResponseExceptionListener();
        $listener->onKernelException($event);

        $this->assertFalse($event->hasResponse());
    }
}
