<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Menu;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Menu\BackendMenuBuilder;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BackendMenuBuilderTest extends TestCase
{
    public function testBuildsTheMainMenu(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(MenuEvent::class), ContaoCoreEvents::BACKEND_MENU_BUILD)
        ;

        $builder = new BackendMenuBuilder(new MenuFactory(), $eventDispatcher);
        $tree = $builder->buildMainMenu();

        $this->assertSame('mainMenu', $tree->getName());
        $this->assertSame(['class' => 'menu_level_0'], $tree->getChildrenAttributes());
    }

    public function testBuildsTheHeaderMenu(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(MenuEvent::class), ContaoCoreEvents::BACKEND_MENU_BUILD)
        ;

        $builder = new BackendMenuBuilder(new MenuFactory(), $eventDispatcher);
        $tree = $builder->buildHeaderMenu();

        $this->assertSame('headerMenu', $tree->getName());
        $this->assertSame(['id' => 'tmenu'], $tree->getChildrenAttributes());
    }

    public function testBuildsTheLoginMenu(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(MenuEvent::class), ContaoCoreEvents::BACKEND_MENU_BUILD)
        ;

        $builder = new BackendMenuBuilder(new MenuFactory(), $eventDispatcher);
        $tree = $builder->buildLoginMenu();

        $this->assertSame('loginMenu', $tree->getName());
    }

    public function testBuildsTheBreadcrumbMenu(): void
    {
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->isInstanceOf(MenuEvent::class), ContaoCoreEvents::BACKEND_MENU_BUILD)
        ;

        $builder = new BackendMenuBuilder(new MenuFactory(), $eventDispatcher);
        $tree = $builder->buildBreadcrumbMenu();

        $this->assertSame('breadcrumbMenu', $tree->getName());
        $this->assertSame(['id' => 'breadcrumb'], $tree->getChildrenAttributes());
    }
}
