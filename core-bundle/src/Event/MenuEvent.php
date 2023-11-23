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
    private FactoryInterface $factory;
    private ItemInterface $tree;

    public function __construct(FactoryInterface $factory, ItemInterface $tree)
    {
        $this->factory = $factory;
        $this->tree = $tree;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getTree(): ItemInterface
    {
        return $this->tree;
    }

    public function moveItem(?ItemInterface $parentNode, ItemInterface $node, int $newIndex): void
    {
        if (null === $parentNode) {
            return;
        }

        if (!$parentNode->hasChildren()) {
            return;
        }

        $name = $node->getName();
        $arrChildren = $parentNode->getChildren();

        if (!\array_key_exists($name, $arrChildren)) {
            return;
        }

        $arrNew = $arrChildren;

        // Get offset of the menu item and splice it into $arrNew
        $intOffset = array_search($name, array_keys($arrChildren), true);
        $arrChildren = array_splice($arrNew, 0, $intOffset);

        // Split current menu items again to insert the item at the given index
        $arrBuffer = array_splice($arrChildren, 0, $newIndex);
        $arrChildren = array_merge_recursive($arrBuffer, $arrNew, $arrChildren);

        $parentNode->reorderChildren(array_keys($arrChildren));
    }
}
