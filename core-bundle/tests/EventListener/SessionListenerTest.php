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

use Contao\CoreBundle\EventListener\SessionListener;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;
use Symfony\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;

/**
 * Tests the SessionListener class.
 */
class SessionListenerTest extends TestCase
{
    /**
     * Tests the object instantiation.
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf('Contao\CoreBundle\EventListener\SessionListener', $this->getListener());
    }

    /**
     * Tests that the onKernelRequest call is forwarded.
     */
    public function testForwardsTheOnKernelRequestCall(): void
    {
        $event = $this->createMock(GetResponseEvent::class);

        $inner = $this->createMock(BaseSessionListener::class);
        $inner
            ->expects($this->once())
            ->method('onKernelRequest')
            ->with($event)
        ;

        $listener = $this->getListener($inner);
        $listener->onKernelRequest($event);
    }

    /**
     * Tests that the onKernelRequest call is forwarded.
     */
    public function testForwardsTheOnFinishRequestCall(): void
    {
        if (!method_exists(BaseSessionListener::class, 'onFinishRequest')) {
            $this->markTestSkipped('The onFinishRequest method has only been added in Symfony 3.4.12.');
        }

        $event = $this->createMock(FinishRequestEvent::class);

        $inner = $this->createMock(BaseSessionListener::class);
        $inner
            ->expects($this->once())
            ->method('onFinishRequest')
            ->with($event)
        ;

        $listener = $this->getListener($inner);
        $listener->onFinishRequest($event);
    }

    /**
     * Tests that the session is saved upon kernel response.
     */
    public function testSavesTheSessionUponKernelResponse(): void
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

        $inner = $this->createMock(BaseSessionListener::class);
        $inner
            ->expects($this->never())
            ->method('onKernelResponse')
            ->with($event)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);
        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isFrontendMasterRequest')
            ->willReturn(true)
        ;

        $listener = $this->getListener($inner, $framework, $scopeMatcher);
        $listener->onKernelResponse($event);
    }

    /**
     * Tests that the session is not saved upon kernel response if the Contao framework is not initialized.
     */
    public function testDoesNotSaveTheSessionUponKernelRequestIfTheFrameworkIsNotInitialized(): void
    {
        if (!method_exists(BaseSessionListener::class, 'onKernelResponse')) {
            $this->markTestSkipped('The onKernelResponse method has only been added in Symfony 3.4.4.');
        }

        $event = $this->createMock(FilterResponseEvent::class);
        $event
            ->expects($this->never())
            ->method('getRequest')
        ;

        $inner = $this->createMock(BaseSessionListener::class);
        $inner
            ->expects($this->once())
            ->method('onKernelResponse')
            ->with($event)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);
        $framework
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->never())
            ->method('isFrontendMasterRequest')
        ;

        $listener = $this->getListener($inner, $framework, $scopeMatcher);
        $listener->onKernelResponse($event);
    }

    /**
     * Tests that the session is not saved upon kernel response if not a front end master request.
     */
    public function testDoesNotSaveTheSessionUponKernelRequestIfNotAFrontendMasterRequest(): void
    {
        if (!method_exists(BaseSessionListener::class, 'onKernelResponse')) {
            $this->markTestSkipped('The onKernelResponse method has only been added in Symfony 3.4.4.');
        }

        $event = $this->createMock(FilterResponseEvent::class);
        $event
            ->expects($this->never())
            ->method('getRequest')
        ;

        $inner = $this->createMock(BaseSessionListener::class);
        $inner
            ->expects($this->once())
            ->method('onKernelResponse')
            ->with($event)
        ;

        $framework = $this->createMock(ContaoFrameworkInterface::class);
        $framework
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isFrontendMasterRequest')
            ->willReturn(false)
        ;

        $listener = $this->getListener($inner, $framework, $scopeMatcher);
        $listener->onKernelResponse($event);
    }

    /**
     * Tests that the subscribed events are returned.
     */
    public function testReturnsTheSubscribedEvents(): void
    {
        $this->assertSame(
            AbstractSessionListener::getSubscribedEvents(),
            $this->getListener()->getSubscribedEvents()
        );
    }

    /**
     * Returns the session listener object.
     *
     * @param BaseSessionListener|null      $inner
     * @param ContaoFrameworkInterface|null $framework
     * @param ScopeMatcher|null             $scopeMatcher
     *
     * @return SessionListener
     */
    private function getListener(BaseSessionListener $inner = null, ContaoFrameworkInterface $framework = null, ScopeMatcher $scopeMatcher = null): SessionListener
    {
        if (null === $inner) {
            $inner = $this->createMock(BaseSessionListener::class);
        }

        if (null === $framework) {
            $framework = $this->createMock(ContaoFrameworkInterface::class);
        }

        if (null === $scopeMatcher) {
            $scopeMatcher = $this->createMock(ScopeMatcher::class);
        }

        return new SessionListener($inner, $framework, $scopeMatcher);
    }
}
