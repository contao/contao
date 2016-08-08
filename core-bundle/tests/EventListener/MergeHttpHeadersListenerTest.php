<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test\EventListener;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\EventListener\MergeHttpHeadersListener;
use Contao\CoreBundle\Test\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the MergeHttpHeadersListenerTest class.
 *
 * @author Yanick Witschi <https:/github.com/toflar>
 */
class MergeHttpHeadersListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testInstantiation()
    {
        /** @var ContaoFrameworkInterface $framework */
        $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');
        $listener = new MergeHttpHeadersListener($framework);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\MergeHttpHeadersListener', $listener);
    }

    /**
     * Tests that the listener is skipped if the framework is not initialized.
     */
    public function testListenerIsSkippedIfFrameworkNotInitialized()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        /** @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $listener = new MergeHttpHeadersListener($framework);
        $listener->setHeaders(['Content-Type: text/html']);
        $listener->onKernelResponse($responseEvent);

        $this->assertFalse($responseEvent->getResponse()->headers->has('Content-Type'));
    }

    /**
     * Tests that the headers sent using header() are merged into the response object.
     */
    public function testHeadersAreMerged()
    {
        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            new Response()
        );

        /** @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $listener = new MergeHttpHeadersListener($framework);
        $listener->setHeaders(['Content-Type: text/html']);
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Content-Type'));
        $this->assertSame('text/html', $response->headers->get('Content-Type'));
    }

    /**
     * Tests that multi-value headers are not overriden.
     */
    public function testMultiValueHeadersAreNotOverriden()
    {
        $response = new Response();
        $response->headers->set('Set-Cookie', 'content');

        $responseEvent = new FilterResponseEvent(
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
            $response
        );

        /** @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject $framework */
        $framework = $this->getMock('Contao\CoreBundle\Framework\ContaoFrameworkInterface');

        $framework
            ->expects($this->once())
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $listener = new MergeHttpHeadersListener($framework);
        $listener->setHeaders(['set-cookie: new-content']); // test a lower-case key here
        $listener->onKernelResponse($responseEvent);

        $response = $responseEvent->getResponse();

        $this->assertTrue($response->headers->has('Set-Cookie'));

        $allHeaders = $response->headers->get('Set-Cookie', null, false);

        $this->assertSame('content', $allHeaders[0]);
        $this->assertSame('new-content', $allHeaders[1]);
    }

    /**
     * Tests that multi-value headers can be added and removed.
     */
    public function testAddingAndRemovingMultiHeaders()
    {
        $listener = new MergeHttpHeadersListener($this->mockContaoFramework());

        $this->assertEquals(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'cache-control',
            ]
        );

        $listener->removeMultiHeader('cache-control');

        $this->assertEquals(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
            ]
        );

        $listener->addMultiHeader('dummy');

        $this->assertEquals(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'dummy',
            ]
        );

        $listener->setMultiHeader(['set-cookie', 'link', 'vary', 'pragma', 'cache-control']);

        $this->assertEquals(
            $listener->getMultiHeaders(),
            [
                'set-cookie',
                'link',
                'vary',
                'pragma',
                'cache-control',
            ]
        );
    }
}
