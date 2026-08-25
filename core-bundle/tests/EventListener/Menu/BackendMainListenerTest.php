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

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendMainListener;
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\TwigRenderer;
use Knp\Menu\Twig\MenuExtension;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class BackendMainListenerTest extends TestCase
{
    public function testBuildsTheMainMenu(): void
    {
        $user = $this->createMock(BackendUser::class);
        $user
            ->expects($this->once())
            ->method('navigation')
            ->willReturn($this->getNavigation())
        ;

        $security = $this->createStub(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMainListener($security);
        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('mainMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertCount(2, $children);
        $this->assertSame(['category1', 'category2'], array_keys($children));

        // Category 1
        $this->assertSame('Category 1', $children['category1']->getLabel());
        $this->assertSame([], $children['category1']->getAttributes());
        $this->assertSame([], $children['category1']->getChildrenAttributes());
        $this->assertSame(['translation_domain' => false], $children['category1']->getExtras());

        $this->assertSame(
            ['class' => 'custom-class'],
            $children['category1']->getLinkAttributes(),
        );

        $grandChildren = $children['category1']->getChildren();

        $this->assertCount(2, $grandChildren);
        $this->assertSame(['node1', 'node2'], array_keys($grandChildren));

        // Node 1
        $this->assertSame('Node 1', $grandChildren['node1']->getLabel());
        $this->assertSame('/node1', $grandChildren['node1']->getUri());
        $this->assertSame([], $grandChildren['node1']->getLinkAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_ICON => 'node1', 'title' => 'Node 1 Title', 'translation_domain' => false], $grandChildren['node1']->getExtras());

        // Node 1
        $this->assertSame('Node 2', $grandChildren['node2']->getLabel());
        $this->assertSame('/node2', $grandChildren['node2']->getUri());
        $this->assertSame([], $grandChildren['node2']->getLinkAttributes());
        $this->assertSame([BackendMenuBuilder::EXTRA_ICON => 'node2', 'title' => 'Node 2 Title', 'translation_domain' => false], $grandChildren['node2']->getExtras());

        // Category 2
        $this->assertSame('Category 2', $children['category2']->getLabel());
        $this->assertSame([], $children['category2']->getAttributes());
        $this->assertSame([], $children['category2']->getChildrenAttributes());
        $this->assertSame(['translation_domain' => false], $children['category2']->getExtras());

        $this->assertSame([], $children['category2']->getLinkAttributes());
    }

    public function testDoesNotBuildTheMainMenuIfNoUserIsGiven(): void
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
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMainListener($security);
        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testDoesNotBuildTheMainMenuIfTheNameDoesNotMatch(): void
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

        $listener = new BackendMainListener($security);
        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testRendersPlainMenuItemsWithBackendNavigationAttributes(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('mainMenu')->setChildrenAttribute('class', 'menu_level_0');
        $group = $factory
            ->createItem('custom')
            ->setLabel('Custom')
            ->setChildrenAttribute('id', 'custom-children')
        ;

        $module = $factory
            ->createItem('module')
            ->setLabel('Module')
            ->setUri('/module')
            ->setExtra(BackendMenuBuilder::EXTRA_ICON, 'module')
        ;

        $legacyModule = $factory
            ->createItem('legacy-module')
            ->setLabel('Legacy module')
            ->setUri('/legacy')
            ->setLinkAttribute('class', 'legacy-class')
            ->setLinkAttribute('title', 'Legacy title')
        ;

        $menu->addChild($group);
        $group->addChild($module);
        $group->addChild($legacyModule);

        $html = $this->createRenderer(['custom' => 0])->render($menu, ['branch_class' => 'branch', 'leaf_class' => 'leaf']);

        $this->assertStringContainsString('class="collapsed first last branch"', $html);
        $this->assertStringContainsString('class="group-custom"', $html);
        $this->assertStringContainsString('data-action="contao--toggle-navigation#toggle:prevent"', $html);
        $this->assertStringContainsString('data-contao--toggle-navigation-category-param="custom"', $html);
        $this->assertStringContainsString('aria-controls="custom-children"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('title="MSC.expandNode"', $html);
        $this->assertStringContainsString('<ul id="custom-children" class="menu_level_1">', $html);
        $this->assertStringContainsString('class="navigation module"', $html);
        $this->assertStringContainsString('title="Module"', $html);
        $this->assertStringContainsString('class="navigation legacy-class"', $html);
        $this->assertStringContainsString('class="first leaf"', $html);
        $this->assertStringContainsString('title="Legacy title"', $html);
        $this->assertStringContainsString('data-contao--tooltips-target="tooltip"', $html);
    }

    public function testDoesNotApplyAutomaticGroupBehaviorToATopLevelLeaf(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('mainMenu');
        $item = $factory
            ->createItem('standalone')
            ->setLabel('Standalone')
            ->setUri('/standalone')
        ;

        $menu->addChild($item);

        $html = $this->createRenderer([])->render($menu);

        $this->assertStringContainsString('href="/standalone"', $html);
        $this->assertStringContainsString('class="navigation"', $html);
        $this->assertStringNotContainsString('contao--toggle-navigation', $html);
    }

    public function testCanDisableAutomaticGroupBehaviorForATopLevelParent(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('mainMenu');
        $item = $factory
            ->createItem('standalone')
            ->setLabel('Standalone')
            ->setUri('/standalone')
            ->setExtra(BackendMenuBuilder::EXTRA_IS_GROUP, false)
        ;

        $item->addChild('child')->setUri('/child');
        $menu->addChild($item);

        $html = $this->createRenderer([])->render($menu);

        $this->assertStringContainsString('href="/standalone"', $html);
        $this->assertStringContainsString('class="navigation"', $html);
        $this->assertStringContainsString('class="has-children first last"', $html);
        $this->assertStringNotContainsString('contao--toggle-navigation', $html);
    }

    private function createRenderer(array $backendModules): TwigRenderer
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../../../contao/templates', 'Contao');
        $loader->addPath(__DIR__.'/../../../../vendor/knplabs/knp-menu-bundle/templates', 'KnpMenu');
        $loader->addPath(__DIR__.'/../../../../vendor/knplabs/knp-menu/src/Knp/Menu/Resources/views');

        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => $id)
        ;

        $twig = new Environment($loader);
        $twig->addExtension(new MenuExtension());
        $twig->addExtension(new TranslationExtension($translator));
        $twig->addFunction(new TwigFunction('path', static fn (): string => '/backend'));
        $twig->addGlobal('app', $this->createAppVariable($backendModules));

        return new TwigRenderer($twig, '@Contao/backend/menu/_main.html.twig', new Matcher());
    }

    private function createAppVariable(array $backendModules): AppVariable
    {
        $bag = new AttributeBag('_contao_backend_attributes');
        $bag->setName('contao_backend');

        $session = new Session(new MockArraySessionStorage());
        $session->registerBag($bag);
        $session->start();

        $bag->set('backend_modules', $backendModules);

        $request = new Request();
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $app = new AppVariable();
        $app->setRequestStack($requestStack);

        return $app;
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
}
