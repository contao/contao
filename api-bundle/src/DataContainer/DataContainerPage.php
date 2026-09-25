<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\DataContainer;

use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Contao\ApiBundle\Dto\DataContainerRecord;

/**
 * @implements \IteratorAggregate<DataContainerRecord>
 * @implements PartialPaginatorInterface<DataContainerRecord>
 */
final class DataContainerPage implements \IteratorAggregate, PartialPaginatorInterface
{
    public function __construct(
        private readonly array $records,
        private readonly int $page,
    ) {
    }

    public function getCurrentPage(): float
    {
        return $this->page;
    }

    public function getItemsPerPage(): float
    {
        return 30;
    }

    public function count(): int
    {
        return \count($this->records);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->records;
    }
}
