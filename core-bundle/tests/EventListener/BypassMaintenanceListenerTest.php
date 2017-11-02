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

use Contao\CoreBundle\EventListener\BypassMaintenanceListener;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class BypassMaintenanceListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new BypassMaintenanceListener($this->mockSession(), false);

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\BypassMaintenanceListener', $listener);
    }

    public function testAddsTheRequestAttribute(): void
    {
        $request = new Request();
        $request->cookies->set('BE_USER_AUTH', 'e15514a266be75c17ed284935ededa5a2c17ac85');

        $event = new GetResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new BypassMaintenanceListener($this->mockSession(), false);
        $listener->onKernelRequest($event);

        $this->assertTrue($event->getRequest()->attributes->get('_bypass_maintenance'));
    }

    public function testDoesNotAddTheRequestAttributeIfThereIsNoBackEndUser(): void
    {
        $event = new GetResponseEvent(
            $this->createMock(KernelInterface::class),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new BypassMaintenanceListener($this->mockSession(), false);
        $listener->onKernelRequest($event);

        $this->assertFalse($event->getRequest()->attributes->has('_bypass_maintenance'));
    }
}
