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
        $request->cookies->set('BE_USER_AUTH', 'da6c1abd61155f4ce98c6b5f1fbbf0ebeb43638e');

        $event = new GetResponseEvent(
            $this->mockKernel(),
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
            $this->mockKernel(),
            new Request(),
            HttpKernelInterface::MASTER_REQUEST
        );

        $listener = new BypassMaintenanceListener($this->mockSession(), false);
        $listener->onKernelRequest($event);

        $this->assertFalse($event->getRequest()->attributes->has('_bypass_maintenance'));
    }
}
