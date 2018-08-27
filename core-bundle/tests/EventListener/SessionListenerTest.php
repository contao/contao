<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\SessionListener;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\HttpKernel\Header\HeaderStorageInterface;
use Contao\CoreBundle\HttpKernel\Header\MemoryHeaderStorage;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;

/**
 * Tests the SessionListener class.
 *
 * @author Leo Feyer <https:/github.com/leofeyer>
 */
class SessionListenerTest extends TestCase
{
    /**
     * @var BaseSessionListener|\PHPUnit_Framework_MockObject_MockObject
     */
    private $inner;

    /**
     * @var ContaoFrameworkInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $framework;

    /**
     * @var ScopeMatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeMatcher;

    /**
     * @var HeaderStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $headerStorage;

    /**
     * @var SessionListener
     */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->inner = $this->createMock(BaseSessionListener::class);
        $this->framework = $this->createMock(ContaoFrameworkInterface::class);
        $this->scopeMatcher = $this->createMock(ScopeMatcher::class);
        $this->headerStorage = new MemoryHeaderStorage();

        $this->listener = new SessionListener(
            $this->inner,
            $this->framework,
            $this->scopeMatcher,
            $this->headerStorage
        );
    }

    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated()
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\SessionListener', $this->listener);
    }

    /**
     * Tests that the onKernelRequest call is forwarded.
     */
    public function testForwardsTheOnKernelRequestCall()
    {
        $event = $this->createMock(GetResponseEvent::class);

        $this->inner
            ->expects($this->once())
            ->method('onKernelRequest')
            ->with($event)
        ;

        $this->listener->onKernelRequest($event);
    }

    /**
     * Tests that the onKernelRequest call is forwarded.
     */
    public function testForwardsTheOnFinishRequestCall()
    {
        if (!method_exists(BaseSessionListener::class, 'onFinishRequest')) {
            $this->markTestSkipped('The onFinishRequest method has only been added in Symfony 3.4.12.');
        }

        $event = $this->createMock(FinishRequestEvent::class);

        $this->inner
            ->expects($this->once())
            ->method('onFinishRequest')
            ->with($event)
        ;

        $this->listener->onFinishRequest($event);
    }

    /**
     * Tests that the session is saved upon kernel response.
     */
    public function testSavesTheSessionUponKernelResponse()
    {
        if (!method_exists(BaseSessionListener::class, 'onKernelResponse')) {
            $this->markTestSkipped('The onKernelResponse method has only been added in Symfony 3.4.4.');
        }

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('isStarted')
            ->willReturn(true)
        ;

        $session
            ->expects($this->once())
            ->method('save')
        ;

        $request = new Request();
        $request->setSession($session);

        $event = $this->createMock(FilterResponseEvent::class);
        $event
            ->expects($this->once())
            ->method('getRequest')
            ->willReturn($request)
        ;

        $event
            ->expects($this->any())
            ->method('getResponse')
            ->willReturn(new Response())
        ;

        $this->inner
            ->expects($this->never())
            ->method('onKernelResponse')
            ->with($event)
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        $this->listener->onKernelResponse($event);
    }

    /**
     * Tests that the session is not saved upon kernel response if the Contao framework is not initialized.
     */
    public function testDoesNotSaveTheSessionUponKernelRequestIfTheFrameworkIsNotInitialized()
    {
        if (!method_exists(BaseSessionListener::class, 'onKernelResponse')) {
            $this->markTestSkipped('The onKernelResponse method has only been added in Symfony 3.4.');
        }

        $event = $this->createMock(FilterResponseEvent::class);
        $event
            ->expects($this->never())
            ->method('getRequest')
        ;

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $this->inner
            ->expects($this->once())
            ->method('onKernelResponse')
            ->with($event)
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $this->scopeMatcher
            ->expects($this->never())
            ->method('isFrontendMasterRequest')
        ;

        $this->listener->onKernelResponse($event);
    }

    /**
     * Tests that the session is not saved upon kernel response if not a front end master request.
     */
    public function testDoesNotSaveTheSessionUponKernelRequestIfNotAFrontendMasterRequest()
    {
        if (!method_exists(BaseSessionListener::class, 'onKernelResponse')) {
            $this->markTestSkipped('The onKernelResponse method has only been added in Symfony 3.4.');
        }

        $event = $this->createMock(FilterResponseEvent::class);
        $event
            ->expects($this->never())
            ->method('getRequest')
        ;

        $event
            ->expects($this->never())
            ->method('getResponse')
        ;

        $this->inner
            ->expects($this->once())
            ->method('onKernelResponse')
            ->with($event)
        ;

        $this->framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $this->scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMasterRequest')
            ->willReturn(false)
        ;

        $this->listener->onKernelResponse($event);
    }

    /**
     * Tests that the subscribed events are returned.
     */
    public function testReturnsTheSubscribedEvents()
    {
        $this->assertSame(
            AbstractSessionListener::getSubscribedEvents(),
            SessionListener::getSubscribedEvents()
        );
    }
}
