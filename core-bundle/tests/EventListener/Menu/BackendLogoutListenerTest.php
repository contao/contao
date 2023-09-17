<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Menu;

use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendLogoutListener;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator as BaseLogoutUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendLogoutListenerTest extends ContaoTestCase
{
    /**
     * @dataProvider getLogoutData
     */
    public function testAddsTheLogoutButton(TokenInterface $token, string $label, string $url): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $security
            ->expects($this->exactly(2))
            ->method('getToken')
            ->willReturn($token)
        ;

        $router = $this->createMock(RouterInterface::class);

        if ($token instanceof SwitchUserToken) {
            $router
                ->expects($this->once())
                ->method('generate')
                ->with('contao_backend', ['do' => 'user', '_switch_user' => SwitchUserListener::EXIT_VALUE])
                ->willReturn('/contao?do=user&_switch_user=_exit')
            ;
        } else {
            $router
                ->expects($this->never())
                ->method('generate')
            ;
        }

        $urlGenerator = $this->createMock(BaseLogoutUrlGenerator::class);

        if (!$token instanceof SwitchUserToken) {
            $urlGenerator
                ->expects($this->once())
                ->method('getLogoutUrl')
                ->willReturn('/contao/logout')
            ;
        } else {
            $urlGenerator
                ->expects($this->never())
                ->method('getLogoutUrl')
            ;
        }

        $factory = new MenuFactory();

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($factory->createItem('submenu'));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendLogoutListener(
            $security,
            $router,
            $urlGenerator,
            $this->getTranslator(),
        );

        $listener($event);

        $children = $event->getTree()->getChild('submenu')->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['logout'], array_keys($children));

        $this->assertSame($label, $children['logout']->getLabel());
        $this->assertSame($url, $children['logout']->getUri());
        $this->assertSame(['translation_domain' => false], $children['logout']->getExtras());

        $this->assertSame(
            [
                'class' => 'icon-logout',
                'accesskey' => 'q',
            ],
            $children['logout']->getLinkAttributes(),
        );
    }

    public function getLogoutData(): \Generator
    {
        $switchUserToken = $this->createMock(SwitchUserToken::class);
        $switchUserToken
            ->method('getOriginalToken')
            ->willReturn($this->createMock(UsernamePasswordToken::class))
        ;

        yield [$this->createMock(UsernamePasswordToken::class), 'MSC.logoutBT', '/contao/logout'];
        yield [$switchUserToken, 'MSC.switchBT', '/contao?do=user&_switch_user=_exit'];
    }

    public function testDoesNotAddTheLogoutButtonIfTheUserRoleIsNotGranted(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(false)
        ;

        $factory = new MenuFactory();

        $menu = $factory->createItem('headerMenu');
        $menu->addChild($factory->createItem('submenu'));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendLogoutListener(
            $security,
            $this->createMock(RouterInterface::class),
            $this->createMock(BaseLogoutUrlGenerator::class),
            $this->getTranslator(),
        );

        $listener($event);

        $children = $event->getTree()->getChild('submenu')->getChildren();

        $this->assertCount(0, $children);
    }

    public function testDoesNotAddTheLogoutButtonIfTheNameDoesNotMatch(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $factory = new MenuFactory();

        $menu = $factory->createItem('mainMenu');
        $menu->addChild($factory->createItem('submenu'));

        $event = new MenuEvent($factory, $menu);

        $listener = new BackendLogoutListener(
            $security,
            $this->createMock(RouterInterface::class),
            $this->createMock(BaseLogoutUrlGenerator::class),
            $this->getTranslator(),
        );

        $listener($event);

        $children = $event->getTree()->getChild('submenu')->getChildren();

        $this->assertCount(0, $children);
    }

    public function testDoesNotAddTheLogoutButtonIfThereIsNoSubmenu(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $factory = new MenuFactory();
        $menu = $factory->createItem('headerMenu');
        $event = new MenuEvent($factory, $menu);

        $listener = new BackendLogoutListener(
            $security,
            $this->createMock(RouterInterface::class),
            $this->createMock(BaseLogoutUrlGenerator::class),
            $this->getTranslator(),
        );

        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(0, $children);
    }

    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;

        return $translator;
    }
}
