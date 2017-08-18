<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\AddToSearchIndexListener;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests the AddToSearchIndexListener class.
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class AddToSearchIndexListenerTest extends TestCase
{
    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->framework = $this->createMock(ContaoFrameworkInterface::class);

        $frontendAdapter = $this
            ->getMockBuilder(Adapter::class)
            ->disableOriginalConstructor()
            ->setMethods(['indexPageIfApplicable'])
            ->getMock()
        ;

        $frontendAdapter
            ->method('indexPageIfApplicable')
            ->willReturn(null)
        ;

        $this->framework
            ->method('getAdapter')
            ->willReturn($frontendAdapter)
        ;
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $listener = new AddToSearchIndexListener($this->framework);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\AddToSearchIndexListener', $listener);
    }

    /**
     * Tests that the listener does nothing if the Contao framework is not booted.
     */
    public function testWithoutContaoFramework()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does use the response if the Contao framework is booted.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testWithContaoFramework()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $event = $this->mockPostResponseEvent();

        $event
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $listener->onKernelTerminate($event);
    }

    /**
     * Tests that the listener does nothing if the request is a fragment.
     */
    public function testWithFragment()
    {
        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $listener = new AddToSearchIndexListener($this->framework);
        $event = $this->mockPostResponseEvent('_fragment/foo/bar');

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $listener->onKernelTerminate($event);
    }

    /**
     * Returns a PostResponseEvent mock object.
     *
     * @param string|null $requestUri
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|PostResponseEvent
     */
    private function mockPostResponseEvent($requestUri = null)
    {
        $request = new Request();

        if (null !== $requestUri) {
            $request->server->set('REQUEST_URI', $requestUri);
        }

        return $this
            ->getMockBuilder(PostResponseEvent::class)
            ->setConstructorArgs([
                $this->createMock(KernelInterface::class),
                $request,
                new Response(),
            ])
            ->setMethods(['getResponse'])
            ->getMock()
        ;
    }
}
