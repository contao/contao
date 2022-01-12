<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Dca\Schema;

/**
 * @template T of SchemaInterface
 * @implements \IteratorAggregate<string, T>
 */
abstract class SchemaCollection extends Schema implements \IteratorAggregate
{
    public function isEmpty(): bool
    {
        return [] === $this->data->all();
    }

    /**
     * @return array<T>
     */
    public function children(): array
    {
        return array_map(fn ($key) => $this->getSchema($key, $this->getChildSchema()), array_map('\strval', array_keys($this->all())));
    }

    /**
     * @return \Iterator<T>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->children());
    }

    /**
     * @return class-string<T>
     */
    abstract protected function getChildSchema(): string;
}
