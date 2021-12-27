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

class FrontendMenuEvent extends Event
{
    private FactoryInterface $factory;
    private ItemInterface $tree;
    private int $pid;
    private int $level;
    private array $options;

    public function __construct(FactoryInterface $factory, ItemInterface $tree, int $pid, int $level, array $options)
    {
        $this->factory = $factory;
        $this->tree = $tree;
        $this->pid = $pid;
        $this->level = $level;
        $this->options = $options;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getTree(): ItemInterface
    {
        return $this->tree;
    }

    /**
     * Returns the page ID of the parent page, i.e. the root page from which the children are selected.
     * May be "0" in combination with $options['pages'] (array<int>).
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * Get the current level of the navigation where "1" = top level.
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Custom options being set in the navigation module.
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
