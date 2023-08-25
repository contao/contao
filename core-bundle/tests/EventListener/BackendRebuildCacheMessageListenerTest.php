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

use Contao\CoreBundle\EventListener\BackendRebuildCacheMessageListener;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Tests\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendRebuildCacheMessageListenerTest extends TestCase
{
    /**
     * @dataProvider provideRequestAndDirty
     */
    public function testDoesNotAddMessageIfNotBackendRequestOrAppCacheIsNotDirty(bool $backendRequest, bool $dirty): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn($backendRequest)
        ;

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->method('hasItem')
            ->with(BackendRebuildCacheMessageListener::CACHE_DIRTY_FLAG)
            ->willReturn($dirty)
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('getSession')
        ;

        $listener = new BackendRebuildCacheMessageListener(
            $scopeMatcher,
            $cacheItemPool,
            $this->createMock(TranslatorInterface::class),
        );

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener($event);
    }

    public function provideRequestAndDirty(): \Generator
    {
        yield [false, true];
        yield [true, false];
        yield [false, false];
    }

    public function testAddsMessage(): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->method('isBackendRequest')
            ->willReturn(true)
        ;

        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $cacheItemPool
            ->method('hasItem')
            ->with(BackendRebuildCacheMessageListener::CACHE_DIRTY_FLAG)
            ->willReturn(true)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->with('ERR.applicationCache', [], 'contao_default')
            ->willReturn('message')
        ;

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with('contao.BE.info', 'message')
        ;

        $session = $this->createMock(Session::class);
        $session
            ->method('getFlashBag')
            ->willReturn($flashBag)
        ;

        $request = $this->createMock(Request::class);
        $request
            ->method('getSession')
            ->willReturn($session)
        ;

        $listener = new BackendRebuildCacheMessageListener(
            $scopeMatcher,
            $cacheItemPool,
            $translator,
        );

        $event = new RequestEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );

        $listener($event);
    }
}
