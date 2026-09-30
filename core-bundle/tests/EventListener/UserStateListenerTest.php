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
use Contao\Config;
use Contao\CoreBundle\EventListener\UserStateListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

class UserStateListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_USERNAME']);
    }

    public function testSetsTheUsername(): void
    {
        $user = $this->createClassWithPropertiesMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('foobar')
        ;

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = $this->createStub(Request::class);
        $translator = $this->createStub(LocaleAwareInterface::class);

        $kernel = $this->createStub(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->assertFalse(isset($GLOBALS['TL_USERNAME']));

        $listener = new UserStateListener($this->createStub(ContaoFramework::class), $security, $translator);
        $listener($event);

        $this->assertTrue(isset($GLOBALS['TL_USERNAME']));
        $this->assertSame('foobar', $GLOBALS['TL_USERNAME']);
    }

    public function testSetsTheBackendUserConfig(): void
    {
        $properties = [
            'showHelp' => true,
            'useRTE' => true,
            'useCE' => true,
            'doNotCollapse' => true,
            'thumbnails' => true,
        ];

        $user = $this->createClassWithPropertiesStub(BackendUser::class, $properties);

        $config = $this->createAdapterMock(['set']);
        $config
            ->expects($this->exactly(5))
            ->method('set')
            ->with(
                $this->callback(static fn ($k) => isset($properties[$k])),
                true,
            )
        ;

        $framework = $this->createContaoFrameworkStub([Config::class => $config]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = $this->createStub(Request::class);
        $translator = $this->createStub(LocaleAwareInterface::class);

        $kernel = $this->createStub(KernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new UserStateListener($framework, $security, $translator);
        $listener($event);
    }

    public function testSetsTheLocale(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class, [
            'language' => 'de',
        ]);

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

        $listener = new UserStateListener($this->createStub(ContaoFramework::class), $security, $translator);
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

        $listener = new UserStateListener($this->createStub(ContaoFramework::class), $security, $translator);
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

        $listener = new UserStateListener($this->createStub(ContaoFramework::class), $security, $translator);
        $listener($event);
    }
}
