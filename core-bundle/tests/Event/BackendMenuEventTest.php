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
        $event = new MenuEvent(
            $this->createMock(FactoryInterface::class),
            $this->createMock(ItemInterface::class)
        );

        $this->assertInstanceOf('Contao\CoreBundle\Event\MenuEvent', $event);
    }

    public function testReturnsTheMenuItemFactory(): void
    {
        $factory = $this->createMock(FactoryInterface::class);
        $event = new MenuEvent($factory, $this->createMock(ItemInterface::class));

        $this->assertSame($factory, $event->getFactory());
    }

    public function testReturnsTheMenuItemTree(): void
    {
        $tree = $this->createMock(ItemInterface::class);
        $event = new MenuEvent($this->createMock(FactoryInterface::class), $tree);

        $this->assertSame($tree, $event->getTree());
    }
}
