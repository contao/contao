<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Menu;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BackendMenuBuilder
{
    /**
     * @internal
     */
    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function buildMainMenu(): ItemInterface
    {
        $tree = $this->factory
            ->createItem('mainMenu')
            ->setChildrenAttribute('class', 'menu_level_0')
        ;

        $this->eventDispatcher->dispatch(new MenuEvent($this->factory, $tree), ContaoCoreEvents::BACKEND_MENU_BUILD);

        return $tree;
    }

    public function buildHeaderMenu(): ItemInterface
    {
        $tree = $this->factory
            ->createItem('headerMenu')
            ->setChildrenAttribute('id', 'tmenu')
        ;

        $this->eventDispatcher->dispatch(new MenuEvent($this->factory, $tree), ContaoCoreEvents::BACKEND_MENU_BUILD);

        return $tree;
    }
}
