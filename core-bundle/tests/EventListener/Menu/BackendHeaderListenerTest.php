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
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\TwigRenderer;
use Knp\Menu\Twig\MenuExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class BackendHeaderListenerTest extends TestCase
{
    public function testBuildsTheHeaderMenu(): void
    {
        $user = $this->createClassWithPropertiesStub(BackendUser::class);
        $user->id = 1;
        $user->name = 'Foo <"> Bar';
        $user->username = 'fo<">o';
        $user->email = '"fo>o"@bar.com';

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
        $this->assertSame(['target' => '_blank'], $children['manual']->getLinkAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_ICON => 'manual', 'safe_label' => true, 'title' => 'MSC.manual', 'translation_domain' => false], $children['manual']->getExtras());

        // Alerts
        $this->assertSame('MSC.systemMessages', $children['alerts']->getLabel());
        $this->assertSame('/contao/alerts', $children['alerts']->getUri());
        $this->assertSame([BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE => '@Contao/backend/menu/_alerts.html.twig', 'alerts_count' => 1, 'title' => 'MSC.systemMessages', 'translation_domain' => false], $children['alerts']->getExtras());

        // Submenu
        $this->assertSame('fo<">o', $children['submenu']->getLabel());
        $this->assertSame(['class' => 'submenu'], $children['submenu']->getAttributes());
        $this->assertSame(['class' => 'profile'], $children['submenu']->getLabelAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE => '@Contao/backend/menu/item/_profile.html.twig', 'translation_domain' => false], $children['submenu']->getExtras());
        $this->assertSame([], $children['submenu']->getChildrenAttributes());

        $grandChildren = $children['submenu']->getChildren();

        $this->assertCount(5, $grandChildren);
        $this->assertSame(['info', 'login', 'security', 'favorites', 'color-scheme'], array_keys($grandChildren));

        // Info
        $this->assertSame('Foo <"> Bar', $grandChildren['info']->getLabel());
        $this->assertSame(['class' => 'info'], $grandChildren['info']->getAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE => '@Contao/backend/menu/item/_info.html.twig', 'detail' => '"fo>o"@bar.com', 'translation_domain' => false], $grandChildren['info']->getExtras());

        // Login
        $this->assertSame('MSC.profile', $grandChildren['login']->getLabel());
        $this->assertSame('/contao?do=login&act=edit&id=1&nb=1', $grandChildren['login']->getUri());
        $this->assertSame([], $grandChildren['login']->getLinkAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_ICON => 'profile', BackendMenuBuilder::EXTRA_HAS_DIVIDER => true, 'translation_domain' => 'contao_default'], $grandChildren['login']->getExtras());

        // Security
        $this->assertSame('MSC.security', $grandChildren['security']->getLabel());
        $this->assertSame('/contao?do=security', $grandChildren['security']->getUri());
        $this->assertSame([], $grandChildren['security']->getLinkAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_ICON => 'security', 'translation_domain' => 'contao_default'], $grandChildren['security']->getExtras());

        // Color scheme
        $this->assertSame('MSC.lightMode', $grandChildren['color-scheme']->getLabel());
        $this->assertSame(['data-controller' => 'contao--color-scheme', 'data-contao--color-scheme-i18n-value' => '{"dark":"MSC.darkMode","light":"MSC.lightMode"}'], $grandChildren['color-scheme']->getAttributes());
        $this->assertSame(['class' => 'color-scheme'], $grandChildren['color-scheme']->getLabelAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE => '@Contao/backend/menu/item/_color_scheme.html.twig', BackendMenuBuilder::EXTRA_HAS_DIVIDER => true, 'translation_domain' => false], $grandChildren['color-scheme']->getExtras());

        // Favorites
        $this->assertSame('MSC.favorites', $grandChildren['favorites']->getLabel());
        $this->assertSame('/contao?do=favorites', $grandChildren['favorites']->getUri());
        $this->assertSame([], $grandChildren['favorites']->getLinkAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_ICON => 'favorites', 'translation_domain' => 'contao_default'], $grandChildren['favorites']->getExtras());

        // Burger
        $this->assertSame('MSC.showMainNavigation', $children['burger']->getLabel());
        $this->assertSame(['class' => 'burger'], $children['burger']->getAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_CONTENT_TEMPLATE => '@Contao/backend/menu/item/_navigation_toggle.html.twig', 'translation_domain' => false], $children['burger']->getExtras());

        $html = $this->createRenderer()->render($tree, ['allow_safe_labels' => true]);
        $this->assertStringContainsString('class="icon-manual"', $html);
        $this->assertStringContainsString('<li class="submenu">', $html);
        $this->assertStringContainsString('<span class="profile">', $html);
        $this->assertStringContainsString('id="profileButton"', $html);
        $this->assertStringContainsString('id="profileMenu" data-controller="contao--toggle-receiver"', $html);
        $this->assertStringContainsString('data-controller="contao--color-scheme"', $html);
        $this->assertStringContainsString('id="burger"', $html);
        $this->assertStringContainsString('class="icon-profile"', $html);
        $this->assertStringContainsString('class="icon-alert"', $html);
        $this->assertStringContainsString('<sup>1</sup>', $html);
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

    private function createRenderer(): TwigRenderer
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../contao/templates', 'Contao');
        $loader->addPath(__DIR__.'/../../../../vendor/knplabs/knp-menu-bundle/templates', 'KnpMenu');
        $loader->addPath(__DIR__.'/../../../../vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views');

        $twig = new Environment($loader);
        $twig->addExtension(new MenuExtension());
        $twig->addExtension(new TranslationExtension($this->getTranslator()));

        return new TwigRenderer($twig, '@Contao/backend/menu/_header.html.twig', new Matcher());
    }
}
