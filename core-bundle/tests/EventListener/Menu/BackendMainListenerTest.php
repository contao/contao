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
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;

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
        $this->assertSame(['id' => 'category1'], $children['category1']->getChildrenAttributes());
        $this->assertSame(['translation_domain' => false], $children['category1']->getExtras());

        $this->assertSame(
            [
                'class' => 'group-category1 custom-class',
                'title' => 'Category 1 Title',
                'data-action' => 'contao--toggle-navigation#toggle:prevent',
                'data-contao--toggle-navigation-category-param' => 'category1',
                'aria-controls' => 'category1',
                'data-turbo-prefetch' => 'false',
                'aria-expanded' => 'true',
            ],
            $children['category1']->getLinkAttributes(),
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
                'data-turbo-prefetch' => 'false',
                'aria-expanded' => 'false',
            ],
            $children['category2']->getLinkAttributes(),
        );
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
