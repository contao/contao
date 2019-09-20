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

use Contao\CoreBundle\EventListener\BypassMaintenanceListener;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BypassMaintenanceListenerTest extends TestCase
{
    public function testAddsTheRequestAttribute(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST);

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('hasBackendUser')
            ->willReturn(true)
        ;

        $listener = new BypassMaintenanceListener($tokenChecker);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->getRequest()->attributes->get('_bypass_maintenance'));
    }

    public function testDoesNotAddTheRequestAttributeIfThereIsNoBackendUser(): void
    {
        $kernel = $this->createMock(KernelInterface::class);
        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST);

        $tokenChecker = $this->createMock(TokenChecker::class);
        $tokenChecker
            ->expects($this->once())
            ->method('hasBackendUser')
            ->willReturn(false)
        ;

        $listener = new BypassMaintenanceListener($tokenChecker);
        $listener->onKernelRequest($event);

        $this->assertFalse($event->getRequest()->attributes->has('_bypass_maintenance'));
    }
}
