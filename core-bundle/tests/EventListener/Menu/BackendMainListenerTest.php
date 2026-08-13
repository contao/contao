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
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator;

class BackendMainListenerTest extends TestCase
{
    protected function tearDown(): void
    {
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
        $this->assertSame(['id' => 'group'], $children['group']->getChildrenAttributes());
        $this->assertSame(['translation_domain' => false], $children['group']->getExtras());

        $this->assertSame(
            [
                'class' => 'group-group',
                'title' => 'collapse',
                'data-action' => 'contao--toggle-navigation#toggle:prevent',
                'data-contao--toggle-navigation-category-param' => 'group',
                'data-contao--tooltips-target' => 'tooltip',
                'aria-controls' => 'group',
                'data-turbo-prefetch' => 'false',
                'aria-expanded' => 'true',
            ],
            $children['group']->getLinkAttributes(),
        );

        $grandChildren = $children['group']->getChildren();

        $this->assertCount(2, $grandChildren);
        $this->assertSame(['module1', 'module2'], array_keys($grandChildren));

        // Node 1
        $this->assertSame('Module 1', $grandChildren['module1']->getLabel());
        $this->assertSame('__link__', $grandChildren['module1']->getUri());
        $this->assertSame(['class' => 'navigation module1', 'title' => 'Module 1 Title', 'data-contao--tooltips-target' => 'tooltip'], $grandChildren['module1']->getLinkAttributes());
        $this->assertSame(['translation_domain' => false], $grandChildren['module1']->getExtras());

        // Node 1
        $this->assertSame('Module 2', $grandChildren['module2']->getLabel());
        $this->assertSame('__link__', $grandChildren['module2']->getUri());
        $this->assertSame(['class' => 'navigation module2', 'title' => 'Module 2 Title', 'data-contao--tooltips-target' => 'tooltip'], $grandChildren['module2']->getLinkAttributes());
        $this->assertSame(['translation_domain' => false], $grandChildren['module2']->getExtras());
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
}
