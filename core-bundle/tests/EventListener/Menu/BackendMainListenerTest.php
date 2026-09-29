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
use Contao\CoreBundle\EventListener\Menu\BackendMainListener;
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Bundle\MenuBundle\KnpMenuBundle;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Runtime\EscaperRuntime;
use Twig\TwigFunction;

class BackendMainListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['BE_MOD']);
    }

    public function testBuildsTheMainMenu(): void
    {
        $GLOBALS['BE_MOD'] = [
            'group' => [
                'module1' => [],
                'module2' => [],
            ],
        ];

        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturn('__link__')
        ;

        $messageCatalogue = $this->createStub(MessageCatalogueInterface::class);
        $messageCatalogue
            ->method('has')
            ->willReturn(true)
        ;

        $translator = $this->createStub(Translator::class);
        $translator
            ->method('getCatalogue')
            ->willReturn($messageCatalogue)
        ;

        $translator
            ->method('trans')
            ->willReturnMap([
                ['MSC.collapseNode', [], 'contao_default', 'collapse'],
                ['MSC.expandNode', [], 'contao_default', 'expand'],
                ['MOD.group.0', [], 'contao_default', 'Group'],
                ['MOD.group.1', [], 'contao_default', 'Group Title'],
                ['MOD.module1.0', [], 'contao_default', 'Module 1'],
                ['MOD.module1.1', [], 'contao_default', 'Module 1 Title'],
                ['MOD.module2.0', [], 'contao_default', 'Module 2'],
                ['MOD.module2.1', [], 'contao_default', 'Module 2 Title'],
            ])
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMainListener(
            $security,
            $this->createStub(RequestStack::class),
            $urlGenerator,
            $translator,
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('mainMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['group'], array_keys($children));

        $this->assertSame('Group', $children['group']->getLabel());
        $this->assertSame([], $children['group']->getAttributes());
        $this->assertSame([], $children['group']->getChildrenAttributes());
        $this->assertSame(['translation_domain' => false], $children['group']->getExtras());
        $this->assertSame([], $children['group']->getLinkAttributes());

        $grandChildren = $children['group']->getChildren();

        $this->assertCount(2, $grandChildren);
        $this->assertSame(['module1', 'module2'], array_keys($grandChildren));

        // Node 1
        $this->assertSame('Module 1', $grandChildren['module1']->getLabel());
        $this->assertSame('__link__', $grandChildren['module1']->getUri());
        $this->assertSame([], $grandChildren['module1']->getLinkAttributes());
        $this->assertSame(['title' => 'Module 1 Title', 'translation_domain' => false], $grandChildren['module1']->getExtras());

        // Node 1
        $this->assertSame('Module 2', $grandChildren['module2']->getLabel());
        $this->assertSame('__link__', $grandChildren['module2']->getUri());
        $this->assertSame([], $grandChildren['module2']->getLinkAttributes());
        $this->assertSame(['title' => 'Module 2 Title', 'translation_domain' => false], $grandChildren['module2']->getExtras());
    }

    public function testMarksTheCurrentModule(): void
    {
        $GLOBALS['BE_MOD'] = [
            'group' => [
                'module1' => [],
                'module2' => [],
            ],
        ];

        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request(['do' => 'module2']));

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMainListener(
            $security,
            $requestStack,
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
        );

        $listener($event);

        $group = $event->getTree()->getChild('group');

        $this->assertSame([], $group->getLinkAttributes());
        $this->assertFalse((bool) $group->getChild('module1')->isCurrent());
        $this->assertTrue($group->getChild('module2')->isCurrent());
    }

    public function testDoesNotRenderEmptyModuleGroups(): void
    {
        $GLOBALS['BE_MOD'] = [
            'group1' => [
                'module1' => [],
                'module2' => [],
            ],
            'group2' => [],
        ];

        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMainListener(
            $security,
            $this->createStub(RequestStack::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('mainMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['group1'], array_keys($children));

        $grandChildren = $children['group1']->getChildren();

        $this->assertCount(2, $grandChildren);
        $this->assertSame(['module1', 'module2'], array_keys($grandChildren));
    }

    public function testFallsBackToStringModuleTranslation(): void
    {
        $GLOBALS['BE_MOD'] = [
            'group' => [
                'module1' => [],
                'module2' => [],
            ],
        ];

        $security = $this->createStub(Security::class);
        $security
            ->method('isGranted')
            ->willReturn(true)
        ;

        $messageCatalogue = $this->createStub(MessageCatalogueInterface::class);
        $messageCatalogue
            ->method('has')
            ->willReturnCallback(static fn (string $id) => !str_ends_with($id, '.0') && !str_ends_with($id, '.1'))
        ;

        $translator = $this->createStub(Translator::class);
        $translator
            ->method('getCatalogue')
            ->willReturn($messageCatalogue)
        ;

        $translator
            ->method('trans')
            ->willReturnMap([
                ['MSC.collapseNode', [], 'contao_default', 'collapse'],
                ['MSC.expandNode', [], 'contao_default', 'expand'],
                ['MOD.group', [], 'contao_default', 'Group'],
                ['MOD.module1', [], 'contao_default', 'Module 1'],
                ['MOD.module2', [], 'contao_default', 'Module 2'],
            ])
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('mainMenu'));

        $listener = new BackendMainListener(
            $security,
            $this->createStub(RequestStack::class),
            $this->createStub(UrlGeneratorInterface::class),
            $translator,
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('mainMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertSame('Group', $children['group']->getLabel());

        $grandChildren = $children['group']->getChildren();

        $this->assertSame('Module 1', $grandChildren['module1']->getLabel());
        $this->assertSame('Module 2', $grandChildren['module2']->getLabel());
    }

    public function testDoesNotBuildTheMainMenuIfTheNameDoesNotMatch(): void
    {
        $GLOBALS['BE_MOD'] = [
            'group' => [
                'module1' => [],
                'module2' => [],
            ],
        ];

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->never())
            ->method('isGranted')
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->never())
            ->method('generate')
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendMainListener(
            $security,
            $this->createStub(RequestStack::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(Translator::class),
        );

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
        $this->assertStringContainsString('class="navigation"', $html);
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

        $bundlePath = new KnpMenuBundle()->getPath();
        $loader->addPath($bundlePath.'/templates', 'KnpMenu');
        $loader->addPath(\dirname($bundlePath).'/knp-menu/src/Knp/Menu/Resources/views');

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
        $twig->addFunction(new TwigFunction('attrs', static fn (HtmlAttributes|iterable|string|null $attributes = null): HtmlAttributes => new HtmlAttributes($attributes)));
        $twig->getRuntime(EscaperRuntime::class)->addSafeClass(HtmlAttributes::class, ['html']);
        $twig->addFunction(new TwigFunction(
            'backend_icon',
            static function (string $src, string $alt = '', HtmlAttributes|null $attributes = null): string {
                $dark = new HtmlAttributes($attributes)->addClass('color-scheme--dark');
                $light = new HtmlAttributes($attributes)->addClass('color-scheme--light');

                return \sprintf(
                    '<img src="%s" alt="%s"%s><img src="%s" alt="%s"%s>',
                    $src,
                    $alt,
                    $dark->toString(),
                    $src,
                    $alt,
                    $light->toString(),
                );
            },
            ['is_safe' => ['html']],
        ));

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
}
