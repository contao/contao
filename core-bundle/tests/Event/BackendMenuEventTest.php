<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\MenuEvent;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\TestCase;

class BackendMenuEventTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $tree = $this->createMock(ItemInterface::class);
        $event = new MenuEvent($factory, $tree);

        $this->assertInstanceOf('Contao\CoreBundle\Event\MenuEvent', $event);
    }

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
}
