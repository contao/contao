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
        $user = $this->createStub(BackendUser::class);

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

        $listener = new BackendBreadcrumbListener(
            $security,
            $dcaUrlAnalyzer,
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertSame('breadcrumbMenu', $tree->getName());

        $children = $tree->getChildren();

        $this->assertCount(3, $children);

        $this->assertSame(
            ['current_0', 'ancestor_trail', 'current_1'],
            array_keys($children),
        );

        $collapsedChildren = $children['ancestor_trail']->getChildren();

        $this->assertSame(['ancestor_trail_0', 'ancestor_trail_1'], array_keys($collapsedChildren));
        $this->assertSame('Homepage', $collapsedChildren['ancestor_trail_0']->getLabel());
        $this->assertSame('Website name', $collapsedChildren['ancestor_trail_1']->getLabel());
        $this->assertSame('/contao?do=article&table=tl_article&pn=2', $collapsedChildren['ancestor_trail_0']->getUri());
        $this->assertSame('/contao?do=article&table=tl_article&pn=1', $collapsedChildren['ancestor_trail_1']->getUri());

        $this->assertSame('Content One', $children['current_1']->getLabel());
        $this->assertSame(['render_dropdown' => true], $children['current_1']->getExtras());

        $siblings = $children['current_1']->getChildren();

        $this->assertSame('Content One', $siblings['sibling_0']->getLabel());
        $this->assertTrue($siblings['sibling_0']->isCurrent());

        $this->assertSame('Content Two', $siblings['sibling_1']->getLabel());
        $this->assertSame('/contao?do=article&id=2&table=tl_content', $siblings['sibling_1']->getUri());
    }

    public function testDoesNotBuildTheBreadcrumbMenuIfNoUserIsGiven(): void
    {
        $security = $this->createStub(Security::class);
        $security
            ->method('getUser')
            ->willReturn(null)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('breadcrumbMenu'));

        $listener = new BackendBreadcrumbListener(
            $security,
            $this->createStub(DcaUrlAnalyzer::class),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }

    public function testDoesNotBuildTheBreadcrumbMenuIfTreeNameIsWrong(): void
    {
        $user = $this->createStub(BackendUser::class);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('foo'));

        $listener = new BackendBreadcrumbListener(
            $security,
            $this->createStub(DcaUrlAnalyzer::class),
        );

        $listener($event);

        $tree = $event->getTree();

        $this->assertNotSame('breadcrumbMenu', $tree->getName());
    }

    private function getTreeTrail(): array
    {
        return [
            [
                'url' => '/contao?do=article&table=tl_article',
                'label' => 'Articles',
                'treeTrail' => null,
                'treeSiblings' => null,
            ],
            [
                'url' => '/contao?do=article&table=tl_content',
                'label' => 'Content One',
                'treeTrail' => [
                    [
                        'url' => '/contao?do=article&table=tl_article&pn=1',
                        'label' => 'Website name',
                    ],
                    [
                        'url' => '/contao?do=article&table=tl_article&pn=2',
                        'label' => 'Homepage',
                    ],
                ],
                'treeSiblings' => [
                    [
                        'url' => '/contao?do=article&id=1&table=tl_content',
                        'label' => 'Content One',
                        'active' => true,
                    ],
                    [
                        'url' => '/contao?do=article&id=2&table=tl_content',
                        'label' => 'Content Two',
                        'active' => false,
                    ],
                    [
                        'url' => '/contao?do=article&id=3&table=tl_content',
                        'label' => 'Content Three',
                        'active' => false,
                    ],
                    [
                        'url' => '/contao?do=article&id=4&table=tl_content',
                        'label' => 'Content Four',
                        'active' => false,
                    ],
                ],
            ],
        ];
    }
}
