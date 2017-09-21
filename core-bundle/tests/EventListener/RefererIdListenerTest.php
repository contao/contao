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

        $event = new GetResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_contao_referer_id'));
        $this->assertSame('testValue', $request->attributes->get('_contao_referer_id'));
    }

    public function testDoesNotAddTheTokenInFrontEndScope(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_FRONTEND);

        $event = new GetResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_contao_referer_id'));
    }

    public function testDoesNotAddTheTokenToASubrequest(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $event = new GetResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertFalse($request->attributes->has('_contao_referer_id'));
    }

    public function testAddsTheSameTokenToSubsequestRequests(): void
    {
        $request = new Request();
        $request->attributes->set('_scope', ContaoCoreBundle::SCOPE_BACKEND);

        $event = new GetResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new RefererIdListener($this->mockTokenManager(), $this->mockScopeMatcher());
        $listener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_contao_referer_id'));
        $this->assertSame('testValue', $request->attributes->get('_contao_referer_id'));

        $listener->onKernelRequest($event);

        $this->assertTrue($request->attributes->has('_contao_referer_id'));
        $this->assertSame('testValue', $request->attributes->get('_contao_referer_id'));
    }
}
