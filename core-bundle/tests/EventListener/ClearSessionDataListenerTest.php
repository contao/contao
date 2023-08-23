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

use Contao\CoreBundle\EventListener\ClearSessionDataListener;
use Contao\CoreBundle\Session\Attribute\AutoExpiringAttribute;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;

class ClearSessionDataListenerTest extends TestCase
{
    public function testClearsTheLoginData(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = new Request();
        $request->setSession($session);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $session->set(Security::AUTHENTICATION_ERROR, 'error');
        $session->set(Security::LAST_USERNAME, 'foobar');

        $listener = new ClearSessionDataListener();
        $listener($event);

        $this->assertFalse($session->has(Security::AUTHENTICATION_ERROR));
        $this->assertFalse($session->has(Security::LAST_USERNAME));
    }

    public function testClearsAutoExpiringAttributes(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $request = new Request();
        $request->setSession($session);

        $event = new ResponseEvent(
            $this->createMock(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            new Response(),
        );

        $nonExpired = new AutoExpiringAttribute(
            20,
            'foobar',
            new \DateTime('-10 seconds'),
        );

        $expired = new AutoExpiringAttribute(
            5,
            'foobar',
            new \DateTime('-10 seconds'),
        );

        $session->set('non-expired-attribute', $nonExpired);
        $session->set('expired-attribute', $expired);

        $listener = new ClearSessionDataListener();
        $listener($event);

        $this->assertTrue($session->has('non-expired-attribute'));
        $this->assertFalse($session->has('expired-attribute'));
    }
}
