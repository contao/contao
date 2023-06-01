<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

class MenuEvent extends Event
{
    public function __construct(
        private FactoryInterface $factory,
        private ItemInterface $tree,
    ) {
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getTree(): ItemInterface
    {
        return $this->tree;
    }
}
