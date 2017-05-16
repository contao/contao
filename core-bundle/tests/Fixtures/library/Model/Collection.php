<?php

namespace Contao\Fixtures\Model;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private $models = [];

    public function __construct(array $models)
    {
        $this->models = $models;
    }

    public function count()
    {
        return count($this->models);
    }

    public function offsetExists($offset)
    {
        return isset($this->models[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->models[$offset];
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('This collection is immutable');
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException('This collection is immutable');
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->models);
    }
}
