<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\PageType;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PageTypeRegistry implements \IteratorAggregate
{
    /** @var PageTypeInterface[] */
    private $pageTypes = [];

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function register(PageTypeInterface $pageType): self
    {
        $this->pageTypes[$pageType->getName()] = $pageType;

        return $this;
    }

    public function has(string $type): bool
    {
        return array_key_exists($type, $this->pageTypes);
    }

    public function get(string $type): PageTypeInterface
    {
        if (!$this->has($type)) {
            throw UnknownPageTypeException::ofType($type);
        }

        return $this->pageTypes[$type];
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->pageTypes);
    }
}
