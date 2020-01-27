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

use function array_filter;
use function array_key_exists;
use function array_map;
use function stat;

class PageTypeIterator implements \Iterator, \Countable
{
    /** @var int */
    private $index = 0;

    /** @var array<PageTypeInterface> */
    private $pageTypes = [];

    /** @param array<PageTypeInterface> $pageTypes */
    private function __construct(array $pageTypes = [])
    {
        foreach ($pageTypes as $pageType) {
            $this->append($pageType);
        }
    }

    /** @param array<PageTypeInterface> $pageTypes */
    public static function fromArray(array $pageTypes): self
    {
        return new self($pageTypes);
    }

    public static function fromList(PageTypeInterface ... $pageTypes): self
    {
        $iterator = new self();
        $iterator->pageTypes = $pageTypes;

        return $iterator;
    }

    private function append(PageTypeInterface $pageType): void
    {
        $this->pageTypes[] = $pageType;
    }

    public function next(): void
    {
        $this->index++;
    }

    public function valid(): bool
    {
        return array_key_exists($this->index, $this->pageTypes);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function current(): PageTypeInterface
    {
        return $this->pageTypes[$this->index];
    }

    public function key(): int
    {
        return $this->index;
    }

    public function count(): int
    {
        return count($this->pageTypes);
    }

    public function filter(callable $callback): self
    {
        $iterator = new self;
        $iterator->pageTypes = array_filter($this->pageTypes, $callback);

        return $iterator;
    }

    /** @return array<string> */
    public function getNames(): array
    {
        return array_map(
            static function (PageTypeInterface $pageType) {
                return $pageType->getName();
            },
            $this->pageTypes
        );
    }
}
