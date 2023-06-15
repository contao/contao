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
use Contao\CoreBundle\EventListener\Menu\BackendMenuListener;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendMenuListenerTest extends TestCase
{
    public function testBuildsTheMainMenu(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('navigation')
            ->willReturn($this->getNavigation())
        ;

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMenuListener(
            $security,
            $this->createMock(RouterInterface::class),
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoFramework::class)
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('mainMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertCount(2, $children);
        $this->assertSame(['category1', 'category2'], array_keys($children));

        // Category 1
        $this->assertSame('Category 1', $children['category1']->getLabel());
        $this->assertSame([], $children['category1']->getAttributes());
        $this->assertSame(['id' => 'category1'], $children['category1']->getChildrenAttributes());
        $this->assertSame(['translation_domain' => false], $children['category1']->getExtras());

        $this->assertSame(
            [
                'class' => 'group-category1 custom-class',
                'title' => 'Category 1 Title',
                'data-action' => 'contao--toggle-navigation#toggle:prevent',
                'data-contao--toggle-navigation-category-param' => 'category1',
                'aria-controls' => 'category1',
                'aria-expanded' => 'true',
            ],
            $children['category1']->getLinkAttributes()
        );

        $grandChildren = $children['category1']->getChildren();

        $this->assertCount(2, $grandChildren);
        $this->assertSame(['node1', 'node2'], array_keys($grandChildren));

        // Node 1
        $this->assertSame('Node 1', $grandChildren['node1']->getLabel());
        $this->assertSame('/node1', $grandChildren['node1']->getUri());
        $this->assertSame(['class' => 'node1', 'title' => 'Node 1 Title'], $grandChildren['node1']->getLinkAttributes());
        $this->assertSame(['translation_domain' => false], $grandChildren['node1']->getExtras());

        // Node 1
        $this->assertSame('Node 2', $grandChildren['node2']->getLabel());
        $this->assertSame('/node2', $grandChildren['node2']->getUri());
        $this->assertSame(['class' => 'node2', 'title' => 'Node 2 Title'], $grandChildren['node2']->getLinkAttributes());
        $this->assertSame(['translation_domain' => false], $grandChildren['node2']->getExtras());

        // Category 2
        $this->assertSame('Category 2', $children['category2']->getLabel());
        $this->assertSame(['class' => 'collapsed'], $children['category2']->getAttributes());
        $this->assertSame(['id' => 'category2'], $children['category2']->getChildrenAttributes());
        $this->assertSame(['translation_domain' => false], $children['category2']->getExtras());

        $this->assertSame(
            [
                'class' => 'group-category2',
                'title' => 'Category 2 Title',
                'data-action' => 'contao--toggle-navigation#toggle:prevent',
                'data-contao--toggle-navigation-category-param' => 'category2',
                'aria-controls' => 'category2',
                'aria-expanded' => 'false',
            ],
            $children['category2']->getLinkAttributes()
        );
    }

    public function testDoesNotBuildTheMainMenuIfNoUserIsGiven(): void
    {
        $security = $this->createMock(Security::class);
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
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMenuListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoFramework::class)
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testDoesNotBuildTheMainMenuIfTheNameDoesNotMatch(): void
    {
        $security = $this->createMock(Security::class);
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

        $listener = new BackendMenuListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoFramework::class)
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testBuildsTheHeaderMenu(): void
    {
        /** @var BackendUser $user */
        $user = $this->mockClassWithProperties(BackendUser::class);
        $user->name = 'Foo Bar';
        $user->username = 'foo';
        $user->email = 'foo@bar.com';

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(
                static function (string $name, array $options = []): string {
                    if ('contao_backend_alerts' === $name) {
                        return '/contao/alerts';
                    }

                    return '/contao?'.http_build_query($options);
                }
            )
        ;

        $request = Request::create('https://localhost/contao?do=pages&ref=123456');
        $request->attributes->set('_contao_referer_id', 'bar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $systemMessages = $this->mockAdapter(['getSystemMessages']);
        $systemMessages
            ->expects($this->once())
            ->method('getSystemMessages')
            ->willReturn('<p class="tl_error">Foo</p>')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('headerMenu'));

        $listener = new BackendMenuListener(
            $security,
            $router,
            $requestStack,
            $this->getTranslator(),
            $this->mockContaoFramework([Backend::class => $systemMessages])
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('headerMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertSame(['manual', 'alerts', 'color-scheme', 'submenu', 'burger'], array_keys($children));

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
            $children['manual']->getLinkAttributes()
        );

        // Alerts
        $this->assertSame('<a href="/contao/alerts" class="icon-alert" title="MSC.systemMessages" onclick="Backend.openModalIframe({\'title\':\'MSC.systemMessages\',\'url\':this.href});return false">MSC.systemMessages</a><sup>1</sup>', $children['alerts']->getLabel());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['alerts']->getExtras());

        // Color scheme
        $this->assertSame('color-scheme', $children['color-scheme']->getLabel());
        $this->assertSame('#', $children['color-scheme']->getUri());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['color-scheme']->getExtras());

        $this->assertSame(
            [
                'class' => 'icon-color-scheme',
                'title' => '',
                'data-controller' => 'contao--color-scheme',
                'data-contao--color-scheme-target' => 'label',
                'data-contao--color-scheme-i18n-value' => '{"dark":"MSC.darkMode","light":"MSC.lightMode"}',
            ],
            $children['color-scheme']->getLinkAttributes()
        );

        // Submenu
        $this->assertSame('<button type="button">MSC.user foo</button>', $children['submenu']->getLabel());
        $this->assertSame(['class' => 'submenu'], $children['submenu']->getAttributes());
        $this->assertSame(['class' => 'profile'], $children['submenu']->getLabelAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['submenu']->getExtras());

        $grandChildren = $children['submenu']->getChildren();

        $this->assertCount(4, $grandChildren);
        $this->assertSame(['info', 'login', 'security', 'favorites'], array_keys($grandChildren));

        // Info
        $this->assertSame('<strong>Foo Bar</strong> foo@bar.com', $grandChildren['info']->getLabel());
        $this->assertSame(['class' => 'info'], $grandChildren['info']->getAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $grandChildren['info']->getExtras());

        // Login
        $this->assertSame('MSC.profile', $grandChildren['login']->getLabel());
        $this->assertSame('/contao?do=login&ref=bar', $grandChildren['login']->getUri());
        $this->assertSame(['class' => 'icon-profile'], $grandChildren['login']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['login']->getExtras());

        // Security
        $this->assertSame('MSC.security', $grandChildren['security']->getLabel());
        $this->assertSame('/contao?do=security&ref=bar', $grandChildren['security']->getUri());
        $this->assertSame(['class' => 'icon-security'], $grandChildren['security']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['security']->getExtras());

        // Favorites
        $this->assertSame('MSC.favorites', $grandChildren['favorites']->getLabel());
        $this->assertSame('/contao?do=favorites&ref=bar', $grandChildren['favorites']->getUri());
        $this->assertSame(['class' => 'icon-favorites'], $grandChildren['favorites']->getLinkAttributes());
        $this->assertSame(['translation_domain' => 'contao_default'], $grandChildren['favorites']->getExtras());

        // Burger
        $this->assertSame('<button type="button" id="burger"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12h18M3 6h18M3 18h18"/></svg></button>', $children['burger']->getLabel());
        $this->assertSame(['class' => 'burger'], $children['burger']->getAttributes());
        $this->assertSame(['safe_label' => true, 'translation_domain' => false], $children['burger']->getExtras());
    }

    public function testDoesNotBuildTheHeaderMenuIfNoUserIsGiven(): void
    {
        $security = $this->createMock(Security::class);
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

        $listener = new BackendMenuListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoFramework::class)
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testDoesNotBuildTheHeaderMenuIfTheNameDoesNotMatch(): void
    {
        $security = $this->createMock(Security::class);
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

        $listener = new BackendMenuListener(
            $security,
            $router,
            new RequestStack(),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoFramework::class)
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    /**
     * @return array<string, array<string, array<string, array<string, bool|string>>|string>>
     */
    private function getNavigation(): array
    {
        return [
            'category1' => [
                'label' => 'Category 1',
                'title' => 'Category 1 Title',
                'href' => '/',
                'class' => 'group-category1 node-expanded trail custom-class',
                'modules' => [
                    'node1' => [
                        'label' => 'Node 1',
                        'title' => 'Node 1 Title',
                        'href' => '/node1',
                        'class' => 'node1',
                        'isActive' => true,
                    ],
                    'node2' => [
                        'label' => 'Node 2',
                        'title' => 'Node 2 Title',
                        'href' => '/node2',
                        'class' => 'node2',
                        'isActive' => false,
                    ],
                ],
            ],
            'category2' => [
                'label' => 'Category 2',
                'title' => 'Category 2 Title',
                'href' => '/',
                'class' => 'group-category2 node-collapsed',
                'modules' => [],
            ],
        ];
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
