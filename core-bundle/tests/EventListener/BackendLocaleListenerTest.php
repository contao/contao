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

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\BackendLocaleListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class BackendLocaleListenerTest extends TestCase
{
    public function testSetsTheLocale(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->language = 'de';

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->once())
            ->method('setLocale')
            ->with('de')
        ;

        $translator = $this->createMock(LocaleAwareInterface::class);
        $translator
            ->expects($this->once())
            ->method('setLocale')
            ->with('de')
        ;

        $kernel = $this->createStub(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new BackendLocaleListener($security, $translator);
        $listener($event);
    }

    public function testDoesNotSetTheLocaleIfNotABackendUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createStub(FrontendUser::class))
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('setLocale')
        ;

        $kernel = $this->createStub(KernelInterface::class);
        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);
        $translator = $this->createStub(LocaleAwareInterface::class);

        $listener = new BackendLocaleListener($security, $translator);
        $listener($event);
    }

    public function testDoesNotSetTheLocaleIfNoUserLanguage(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('setLocale')
        ;

        $kernel = $this->createStub(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $translator = $this->createStub(LocaleAwareInterface::class);

        $listener = new BackendLocaleListener($security, $translator);
        $listener($event);
    }
}
