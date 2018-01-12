<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\Event;

class MenuEvent extends Event
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ItemInterface
     */
    private $tree;

    /**
     * @param FactoryInterface $factory
     * @param ItemInterface    $tree
     */
    public function __construct(FactoryInterface $factory, ItemInterface $tree)
    {
        $this->factory = $factory;
        $this->tree = $tree;
    }

    /**
     * Returns the menu item factory.
     *
     * @return FactoryInterface
     */
    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * Returns the menu item tree.
     *
     * @return ItemInterface
     */
    public function getTree(): ItemInterface
    {
        return $this->tree;
    }
}
