<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Session\Attribute;

use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBag;

/**
 * Provides an array access adapter for a session attribute bag.
 *
 * @implements \ArrayAccess<mixed, mixed>
 */
class ArrayAttributeBag extends AttributeBag implements \ArrayAccess
{
    /**
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function &offsetGet($offset)
    {
        return $this->attributes[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
