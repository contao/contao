<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\BackendMenuListener;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class BackendMenuListenerTest extends TestCase
{
    public function testCreatesANodeListFromTheBackendUserMenuArray(): void
    {
        $user = $this->createPartialMock(BackendUser::class, ['hasAccess', 'navigation']);
        $user
            ->method('navigation')
            ->willReturn([
                'category1' => [
                    'label' => 'Category 1',
                    'title' => 'Category 1 Title',
                    'href' => '/',
                    'class' => 'node-expanded trail custom-class',
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
                    'class' => 'node-collapsed',
                    'modules' => [],
                ],
            ])
        ;

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendMenuListener($security);
        $listener->onBuild($event);

        $tree = $event->getTree();

        // Test root node
        $this->assertCount(2, $tree->getChildren());

        // Test category node
        $categoryNode = $tree->getChild('category1');
        $this->assertNotNull($categoryNode);
        $this->assertInstanceOf(ItemInterface::class, $categoryNode);
        $this->assertCount(2, $categoryNode->getChildren());
        $this->assertSame('custom-class', $categoryNode->getAttribute('class'));

        // Test module node
        $moduleNode = $categoryNode->getChild('node1');
        $this->assertNotNull($moduleNode);
        $this->assertInstanceOf(ItemInterface::class, $moduleNode);
        $this->assertCount(0, $moduleNode->getChildren());

        // Test expanded/collapsed
        $childNode = $tree->getChild('category1');
        $this->assertNotNull($childNode);
        $this->assertTrue($childNode->getDisplayChildren());

        $childNode = $tree->getChild('category2');
        $this->assertNotNull($childNode);
        $this->assertFalse($childNode->getDisplayChildren());

        // Test active/not active
        $childNode = $categoryNode->getChild('node1');
        $this->assertNotNull($childNode);
        $this->assertTrue($childNode->isCurrent());
        $this->assertSame('node1', $childNode->getAttribute('class'));

        $childNode = $categoryNode->getChild('node2');
        $this->assertNotNull($childNode);
        $this->assertFalse($childNode->isCurrent());
        $this->assertSame('node2', $childNode->getAttribute('class'));
    }

    public function testDoesNotModifyTheTreeIfNoBackendUserIsGiven(): void
    {
        $user = $this->createMock(UserInterface::class);

        $security = $this->createMock(Security::class);
        $security
            ->method('getUser')
            ->willReturn($user)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendMenuListener($security);
        $listener->onBuild($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }
}
