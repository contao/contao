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
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Firewall\SwitchUserListener;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator as BaseLogoutUrlGenerator;

class BackendLogoutListenerTest extends ContaoTestCase
{
    public function testAddsTheLogoutButtonWithSwitchUserToken(): void
    {
        $originalToken = $this->createStub(UsernamePasswordToken::class);
        $originalToken
            ->method('getUserIdentifier')
            ->willReturn('k.jones')
        ;

        $switchUserToken = $this->createStub(SwitchUserToken::class);
        $switchUserToken
            ->method('getOriginalToken')
            ->willReturn($originalToken)
        ;

        $this->assertLogoutButton($switchUserToken, 'MSC.switchBT', '/contao?do=user&_switch_user=_exit', ['k.jones']);
    }

    public function testAddsTheLogoutButtonWithRegularToken(): void
    {
        $this->assertLogoutButton($this->createStub(UsernamePasswordToken::class), 'MSC.logoutBT', '/contao/logout', []);
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
            $this->createStub(RouterInterface::class),
            $this->createStub(BaseLogoutUrlGenerator::class),
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
            $this->createStub(RouterInterface::class),
            $this->createStub(BaseLogoutUrlGenerator::class),
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
            $this->createStub(RouterInterface::class),
            $this->createStub(BaseLogoutUrlGenerator::class),
        );

        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(0, $children);
    }

    private function assertLogoutButton(TokenInterface $token, string $label, string $url, array $translationParams): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true)
        ;

        $security
            ->expects($this->atLeastOnce())
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
        );

        $listener($event);

        $children = $event->getTree()->getChild('submenu')->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['logout'], array_keys($children));

        $this->assertSame($label, $children['logout']->getLabel());
        $this->assertSame($url, $children['logout']->getUri());
        $this->assertSame(
            [
                BackendMenuBuilder::EXTRA_ICON => 'exit.svg',
                BackendMenuBuilder::EXTRA_HAS_DIVIDER => true,
                'translation_params' => $translationParams,
                'translation_domain' => 'contao_default',
            ],
            $children['logout']->getExtras(),
        );

        $this->assertSame(
            [
                'accesskey' => 'q',
                'data-turbo-prefetch' => 'false',
            ],
            $children['logout']->getLinkAttributes(),
        );
    }
}
