<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures;

class IteratorAggregateStub implements \IteratorAggregate
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
}
