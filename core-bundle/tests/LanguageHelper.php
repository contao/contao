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
 * Simple language string adapter that will return the language array keys as result.
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
     * Create a new instance with the given stack..
     *
     * @param array $stack
     */
    public function __construct(array $stack = array())
    {
        $this->stack = $stack;
    }

    function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * Mock method always returning true.
     *
     * @param mixed $offset
     *
     * @return bool Always true
     */
    public function offsetExists($offset)
    {
        return true;
    }

    /**
     * Create a sub object with the given name.
     *
     * @param mixed $offset
     *
     * @return LanguageHelper
     */
    public function offsetGet($offset)
    {
        return new LanguageHelper(array_merge($this->stack, array($offset)));
    }

    /**
     * Unsupported, throws exception.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('The language helper is just for retrieving, not for setting.');
    }

    /**
     * Unsupported, throws exception.
     *
     * @param mixed $offset
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('The language helper is just for retrieving, not for setting.');
    }

    public function __toString()
    {
        return implode('.', $this->stack);
    }
}
