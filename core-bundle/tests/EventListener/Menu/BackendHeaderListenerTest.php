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

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendHeaderListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendHeaderListenerTest extends TestCase
{
    public function testBuildsTheHeaderMenu(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->id = 1;
        $user->name = 'Foo Bar';
        $user->username = 'foo';
        $user->email = 'foo@bar.com';

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(
                static function (string $name, array $options = []): string {
                    if ('contao_backend_alerts' === $name) {
                        return '/contao/alerts';
                    }

                    return '/contao?'.http_build_query($options);
                },
            )
        ;

        $systemMessages = $this->createAdapterMock(['getSystemMessages']);
        $systemMessages
            ->expects($this->once())
            ->method('getSystemMessages')
            ->willReturn('<p class="tl_error">Foo</p>')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('headerMenu'));

        $listener = new BackendHeaderListener(
            $security,
            $router,
            $this->getTranslator(),
            $this->createContaoFrameworkStub([Backend::class => $systemMessages]),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('headerMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertSame(['manual', 'alerts', 'submenu', 'burger'], array_keys($children));

        // Manual
        $this->assertSame('MSC.manual', $children['manual']->getLabel());
        $this->assertSame('https://to.contao.org/manual', $children['manual']->getUri());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['alerts']->getExtras());

        $this->assertSame(
            [
                'class' => 'icon-manual',
                'title' => 'MSC.manual',
                'target' => '_blank',
            ],
            $children['manual']->getLinkAttributes(),
        );

        // Alerts
        $this->assertSame('<a href="/contao/alerts" class="icon-alert" title="MSC.systemMessages" data-turbo-prefetch="false" onclick="Backend.openModalIframe({\'title\':\'MSC.systemMessages\',\'url\':this.href});return false">MSC.systemMessages</a><sup>1</sup>', $children['alerts']->getLabel());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['alerts']->getExtras());

        // Submenu
        $this->assertSame('<button id="profileButton" type="button" title="MSC.showProfile" data-controller="contao--toggle-handler" data-action="contao--toggle-handler#toggle:prevent" data-contao--toggle-handler-active-title-value="MSC.hideProfile" data-contao--toggle-handler-inactive-title-value="MSC.showProfile" data-contao--toggle-handler-contao--toggle-receiver-outlet="#profileMenu">foo</button>', $children['submenu']->getLabel());
        $this->assertSame(['class' => 'submenu'], $children['submenu']->getAttributes());
        $this->assertSame(['class' => 'profile'], $children['submenu']->getLabelAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['submenu']->getExtras());
        $this->assertSame(
            [
                'id' => 'profileMenu',
                'data-controller' => 'contao--toggle-receiver',
                'data-contao--toggle-receiver-active-class' => 'active',
                'data-action' => 'click@document->contao--toggle-receiver#documentClick keydown.esc@document->contao--toggle-receiver#close',
                'data-contao--toggle-receiver-contao--toggle-handler-outlet' => '#profileButton',
            ],
            $children['submenu']->getChildrenAttributes(),
        );

        $grandChildren = $children['submenu']->getChildren();

        $this->assertCount(5, $grandChildren);
        $this->assertSame(['info', 'login', 'security', 'favorites', 'color-scheme'], array_keys($grandChildren));

        // Info
        $this->assertSame('<strong>Foo Bar</strong> foo@bar.com', $grandChildren['info']->getLabel());
        $this->assertSame(['class' => 'info'], $grandChildren['info']->getAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $grandChildren['info']->getExtras());

        // Login
        $this->assertSame('MSC.profile', $grandChildren['login']->getLabel());
        $this->assertSame('/contao?do=login&act=edit&id=1&nb=1', $grandChildren['login']->getUri());
        $this->assertSame(['class' => 'icon-profile'], $grandChildren['login']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['login']->getExtras());

        // Security
        $this->assertSame('MSC.security', $grandChildren['security']->getLabel());
        $this->assertSame('/contao?do=security', $grandChildren['security']->getUri());
        $this->assertSame(['class' => 'icon-security'], $grandChildren['security']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['security']->getExtras());

        // Color scheme
        $this->assertSame('<button class="icon-color-scheme" type="button" data-contao--color-scheme-target="label" data-action="contao--color-scheme#toggle:prevent">MSC.lightMode</button>', $grandChildren['color-scheme']->getLabel());
        $this->assertSame(['class' => 'separator', 'data-controller' => 'contao--color-scheme', 'data-contao--color-scheme-i18n-value' => '{"dark":"MSC.darkMode","light":"MSC.lightMode"}'], $grandChildren['color-scheme']->getAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $grandChildren['color-scheme']->getExtras());

        // Favorites
        $this->assertSame('MSC.favorites', $grandChildren['favorites']->getLabel());
        $this->assertSame('/contao?do=favorites', $grandChildren['favorites']->getUri());
        $this->assertSame(['class' => 'icon-favorites'], $grandChildren['favorites']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['favorites']->getExtras());

        // Burger
        $this->assertSame('<button id="burger" type="button" title="MSC.showMainNavigation" data-controller="contao--toggle-handler" data-action="contao--toggle-handler#toggle:prevent" data-contao--toggle-handler-active-title-value="MSC.hideMainNavigation" data-contao--toggle-handler-inactive-title-value="MSC.showMainNavigation" data-contao--toggle-handler-contao--toggle-receiver-outlet="#left"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg></button>', $children['burger']->getLabel());
        $this->assertSame(['class' => 'burger'], $children['burger']->getAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['burger']->getExtras());
    }

    public function testDoesNotBuildTheHeaderMenuIfNoUserIsGiven(): void
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('getUser')
            ->willReturn(null)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('headerMenu'));

        $listener = new BackendHeaderListener(
            $security,
            $router,
            $this->createStub(TranslatorInterface::class),
            $this->createStub(ContaoFramework::class),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testDoesNotBuildTheHeaderMenuIfTheNameDoesNotMatch(): void
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('getUser')
            ->willReturn(null)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendHeaderListener(
            $security,
            $router,
            $this->createStub(TranslatorInterface::class),
            $this->createStub(ContaoFramework::class),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    private function getTranslator(): TranslatorInterface
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;

        return $translator;
    }
}
