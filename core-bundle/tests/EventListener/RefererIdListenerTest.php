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

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\EventListener\RefererIdListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RefererIdListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\RefererIdListener', $listener);
    }

    public function testAddsTheTokenToTheRequest(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_contao_referer_id'));
        $this->assertSame('testValue', $request->attributes->get('_contao_referer_id'));
    }

    public function testDoesNotAddTheTokenInFrontEndScope(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_contao_referer_id'));
    }

    public function testDoesNotAddTheTokenToASubrequest(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_contao_referer_id'));
    }

    public function testAddsTheSameTokenToSubsequestRequests(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_contao_referer_id'));
        $this->assertSame('testValue', $request->attributes->get('_contao_referer_id'));

        $listener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_contao_referer_id'));
        $this->assertSame('testValue', $request->attributes->get('_contao_referer_id'));
    }

    /**
     * Mocks a token manager.
     *
     * @return CsrfTokenManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockTokenManager(): CsrfTokenManagerInterface
    {
        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $tokenManager
            ->method('getToken')
            ->willReturn(new CsrfToken('_csrf', 'testValue'))
        ;

        $tokenManager
            ->method('refreshToken')
            ->willReturnOnConsecutiveCalls(new CsrfToken('_csrf', 'testValue'), new CsrfToken('_csrf', 'foo'))
        ;

        return $tokenManager;
    }
}
