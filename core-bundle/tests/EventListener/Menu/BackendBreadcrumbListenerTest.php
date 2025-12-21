<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\Menu\BackendBreadcrumbListener;
use Contao\CoreBundle\Tests\TestCase;
use Knp\Menu\MenuFactory;
use Symfony\Bundle\SecurityBundle\Security;

class BackendBreadcrumbListenerTest extends TestCase
{
    public function testBuildsTheBreadcrumbMenu(): void
    {
        $user = $this->createMock(BackendUser::class);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('breadcrumbMenu'));

        $dcaUrlAnalyzer = $this->createMock(DcaUrlAnalyzer::class);
        $dcaUrlAnalyzer
            ->expects($this->once())
            ->method('getTrail')
            ->willReturn($this->getTreeTrail())
        ;

        $listener = new BackendBreadcrumbListener($security, $dcaUrlAnalyzer);
        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('breadcrumbMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertCount(4, $children);
        $this->assertSame(
            ['current_path_0', 'collapsed_path_1', 'current_trail_1', 'current_path_1'],
            array_keys($children),
        );

        $collapsedChildren = $children['collapsed_path_1']->getChildren();

        $this->assertSame(['collapsed_path_0'], array_keys($collapsedChildren));
        $this->assertSame('Website name', $collapsedChildren['collapsed_path_0']->getLabel());
        $this->assertSame('/contao?do=article&table=tl_article&pn=1', $collapsedChildren['collapsed_path_0']->getUri());

        $this->assertSame('Homepage', $children['current_trail_1']->getLabel());
        $this->assertSame('/contao?do=article&table=tl_article&pn=2', $children['current_trail_1']->getUri());
        $this->assertSame(['translation_domain' => false], $children['current_trail_1']->getExtras());

        $this->assertSame('Content One', $children['current_path_1']->getLabel());
        $this->assertSame('/contao?do=article&table=tl_content', $children['current_path_1']->getUri());
        $this->assertSame(['translation_domain' => false], $children['current_path_1']->getExtras());

        $siblings = $children['current_path_1']->getChildren();

        $this->assertSame('Content One', $siblings['collapsed_path_0']->getLabel());
        $this->assertNull($siblings['collapsed_path_0']->getUri());

        $this->assertSame('Content Two', $siblings['collapsed_path_1']->getLabel());
        $this->assertSame('/contao?do=article&id=2&table=tl_content', $siblings['collapsed_path_1']->getUri());
    }

    private function getTreeTrail(): array
    {
        return [
            0 => [
                'url' => '/contao?do=article&table=tl_article',
                'label' => 'Articles',
                'treeTrail' => null,
                'treeSiblings' => null,
            ],
            1 => [
                'url' => '/contao?do=article&table=tl_content',
                'label' => 'Content One',
                'treeTrail' => [
                    0 => [
                        'url' => '/contao?do=article&table=tl_article&pn=1',
                        'label' => 'Website name',
                    ],
                    1 => [
                        'url' => '/contao?do=article&table=tl_article&pn=2',
                        'label' => 'Homepage',
                    ]
                ],
                'treeSiblings' => [
                    0 => [
                        'url' => '/contao?do=article&id=1&table=tl_content',
                        'label' => 'Content One',
                        'active' => true,
                    ],
                    1 => [
                        'url' => '/contao?do=article&id=2&table=tl_content',
                        'label' => 'Content Two',
                        'active' => false,
                    ],
                    2 => [
                        'url' => '/contao?do=article&id=3&table=tl_content',
                        'label' => 'Content Three',
                        'active' => false,
                    ],
                    3 => [
                        'url' => '/contao?do=article&id=4&table=tl_content',
                        'label' => 'Content Four',
                        'active' => false,
                    ],
                ],
            ],
        ];
    }
}
