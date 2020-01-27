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

class PageTypeIterator implements \Iterator, \Countable
{
    private $index = 0;

    private $pageTypes;

    private function __construct(array $pageTypes)
    {
        $this->pageTypes = $pageTypes;
    }

    public static function fromArray(array $pageTypes): self
    {
    }

    public static function fromList(PageTypeInterface ... $pageTypes): self
    {
        return new self($pageTypes);
    }

    public function next()
    {
        // TODO: Implement next() method.
    }

    public function valid()
    {
        // TODO: Implement valid() method.
    }

    public function rewind()
    {
        // TODO: Implement rewind() method.
    }

    public function current()
    {
        // TODO: Implement current() method.
    }

    public function key()
    {
        // TODO: Implement key() method.
    }

    public function count()
    {
        // TODO: Implement count() method.
    }
}
