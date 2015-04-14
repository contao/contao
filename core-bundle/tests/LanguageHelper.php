<?php

/**
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Test;

/**
 * Returns the language array keys as array.
 *
 * @author Christian Schiffler <https://github.com/discordier>
 */
class LanguageHelper implements \ArrayAccess
{
    /**
     * @var array
     */
    protected $stack;

    /**
     * Constructor.
     *
     * @param array $stack
     */
    public function __construct(array $stack = [])
    {
        $this->stack = $stack;
    }

    /**
     * Returns the current language helper.
     *
     * @param string $key The key
     *
     * @return LanguageHelper The language helper object
     */
    function __get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * Returns true.
     *
     * @param mixed $offset The offset
     *
     * @return bool True
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     * Creates a sub object with the given name.
     *
     * @param mixed $offset The offset
     *
     * @return LanguageHelper
     */
    public function offsetGet($offset)
    {
        return new LanguageHelper(array_merge($this->stack, [$offset]));
    }

    /**
     * Throws an exception.
     *
     * @param mixed $offset The offset
     * @param mixed $value  The value
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('The language helper is just for retrieving, not for setting.');
    }

    /**
     * Throws an exception.
     *
     * @param mixed $offset The offset
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('The language helper is just for retrieving, not for setting.');
    }

    /**
     * Returns the combined stack as string.
     *
     * @return string The combined stack
     */
    public function __toString()
    {
        return implode('.', $this->stack);
    }
}
