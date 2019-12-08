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
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @internal Do not inherit from this class; decorate the "contao.menu.backend_menu_builder" service instead
     */
    public function __construct(FactoryInterface $factory, EventDispatcherInterface $eventDispatcher)
    {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Creates a new root node and dispatches an event to fill it with child nodes.
     */
    public function create(): ItemInterface
    {
        $tree = $this->factory->createItem('root');

        $this->eventDispatcher->dispatch(new MenuEvent($this->factory, $tree), ContaoCoreEvents::BACKEND_MENU_BUILD);

        return $tree;
    }
}
