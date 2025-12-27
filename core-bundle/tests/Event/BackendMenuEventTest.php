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
use PHPUnit\Framework\TestCase;

class BackendMenuEventTest extends TestCase
{
    public function testReturnsTheMenuItemFactory(): void
    {
        $factory = $this->createStub(FactoryInterface::class);
        $tree = $this->createStub(ItemInterface::class);
        $event = new MenuEvent($factory, $tree);

        $this->assertSame($factory, $event->getFactory());
    }

    public function testReturnsTheMenuItemTree(): void
    {
        $factory = $this->createStub(FactoryInterface::class);
        $tree = $this->createStub(ItemInterface::class);
        $event = new MenuEvent($factory, $tree);

        $this->assertSame($tree, $event->getTree());
    }
}
