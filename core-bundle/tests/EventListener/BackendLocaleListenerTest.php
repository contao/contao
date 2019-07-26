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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

class BackendLocaleListenerTest extends TestCase
{
    public function testSetsTheLocale(): void
    {
        /** @var BackendUser&MockObject $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
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

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('setLocale')
            ->with('de')
        ;

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);

        $GLOBALS['TL_LANGUAGE'] = 'en';

        $listener = new BackendLocaleListener($security, $translator);
        $listener->onKernelRequest($event);

        $this->assertSame('de', $GLOBALS['TL_LANGUAGE']);

        unset($GLOBALS['TL_LANGUAGE']);
    }

    public function testDoesNotSetTheLocaleIfNotABackendUser(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(FrontendUser::class))
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('setLocale')
        ;

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST);
        $translator = $this->createMock(TranslatorInterface::class);

        $listener = new BackendLocaleListener($security, $translator);
        $listener->onKernelRequest($event);
    }

    public function testDoesNotSetTheLocaleIfNoUserLanguage(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($security)
        ;

        $request = $this->createMock(Request::class);
        $request
            ->expects($this->never())
            ->method('setLocale')
        ;

        $kernel = $this->createMock(KernelInterface::class);
        $event = new GetResponseEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST);
        $translator = $this->createMock(TranslatorInterface::class);

        $listener = new BackendLocaleListener($security, $translator);
        $listener->onKernelRequest($event);
    }
}
