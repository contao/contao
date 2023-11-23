<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\MenuEvent;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\TestCase;

class BackendMenuEventTest extends TestCase
{
    public function testReturnsTheMenuItemFactory(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $tree = $this->createMock(ItemInterface::class);
        $event = new MenuEvent($factory, $tree);

        $this->assertSame($factory, $event->getFactory());
    }

    public function testReturnsTheMenuItemTree(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $tree = $this->createMock(ItemInterface::class);
        $event = new MenuEvent($factory, $tree);

        $this->assertSame($tree, $event->getTree());
    }

    public function testMovesTheLastMenuItemToSecondIndex(): void
    {
        $factory = new MenuFactory();
        $tree = new MenuItem('content', $factory);
        $event = new MenuEvent($factory, $tree);

        $newIndex = 2;
        $this->createMenuItems($tree, $factory);

        // Save last item for testing
        $lastMenuItem = $tree->getLastChild();

        $event->moveItem($tree, $lastMenuItem, $newIndex);

        // Reset keys to get the test object by index
        $children = array_values($tree->getChildren());
        $testItem = $children[$newIndex];

        $this->assertSame($lastMenuItem, $testItem);
    }

    private function createMenuItems(MenuItem $node, FactoryInterface $factory): void
    {
        for ($i = 0; $i < 9; ++$i) {
            $node->addChild(new MenuItem('child_'.$i, $factory));
        }
    }
}
