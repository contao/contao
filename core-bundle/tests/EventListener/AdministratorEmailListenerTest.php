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

use Contao\Config;
use Contao\CoreBundle\EventListener\AdministratorEmailListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdministratorEmailListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['BE_MOD']['system']['settings']['tables'] = ['tl_settings'];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['BE_MOD']);
    }

    public function testDoesNotAddMessageIfAdminEmailIsSet(): void
    {
        $configAdapter = $this->mockAdapter(['get']);
        $configAdapter
            ->method('get')
            ->with('adminEmail')
            ->willReturn('foobar@example.com')
        ;

        $framework = $this->mockContaoFramework([Config::class => $configAdapter]);
        $listener = $this->createAdministratorEmailListener($framework);

        $this->assertNull($listener());
    }

    public function testShowsMessageWithoutLinkIfSettingsModuleIsDisabled(): void
    {
        unset($GLOBALS['BE_MOD']['system']['settings']);

        $listener = $this->createAdministratorEmailListener();

        $this->assertSame('<p class="tl_error">ERR.noAdminEmail</p>', $listener());
    }

    public function testShowsMessageWithoutLinkIfSettingsModuleIsDisallowed(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'settings')
            ->willReturn(false)
        ;

        $listener = $this->createAdministratorEmailListener(security: $security);

        $this->assertSame('<p class="tl_error">ERR.noAdminEmail</p>', $listener());
    }

    public function testShowsMessageWithLink(): void
    {
        $listener = $this->createAdministratorEmailListener();

        $this->assertSame('<p class="tl_error">ERR.noAdminEmailUrl</p>', $listener());
    }

    private function createAdministratorEmailListener(ContaoFramework|null $framework = null, Security|null $security = null): AdministratorEmailListener
    {
        if (!$framework) {
            $configAdapter = $this->mockAdapter(['get']);
            $configAdapter
                ->method('get')
                ->with('adminEmail')
                ->willReturn(null)
            ;

            $framework = $this->mockContaoFramework([Config::class => $configAdapter]);
        }

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnMap([
                ['ERR.noAdminEmailUrl', ['settingsUrl' => 'https://example.com'], 'contao_default', null, 'ERR.noAdminEmailUrl'],
                ['ERR.noAdminEmail', [], 'contao_default', null, 'ERR.noAdminEmail'],
            ])
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturn('https://example.com')
        ;

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn(new Request())
        ;

        if (!$security) {
            $security = $this->createMock(Security::class);
            $security
                ->method('isGranted')
                ->with(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'settings')
                ->willReturn(true)
            ;
        }

        return new AdministratorEmailListener($framework, $translator, $router, $requestStack, $security);
    }
}
