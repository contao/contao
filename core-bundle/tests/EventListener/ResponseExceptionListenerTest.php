<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\EventListener\ResponseExceptionListener;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the ResponseExceptionListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ResponseExceptionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        $listener = new ResponseExceptionListener();

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\ResponseExceptionListener', $listener);
    }

    /**
     * Tests passing a response exception.
     */
    public function testResponseException()
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

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Foo', $response->getContent());
    }

    /**
     * Tests passing a non-response exception.
     */
    public function testNonResponseException()
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
