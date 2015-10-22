<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

/**
 * Wraps unmockable classes and delegates the method calls.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 * @internal
 */
class Adapter
{
    /**
     * @var string
     */
    private $class;

    /**
     * Constructor.
     *
     * @param string $class The fully qualified class name
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Calls a method of the adapted class.
     *
     * @param string $name      The method name
     * @param array  $arguments Optional arguments
     *
     * @return mixed The return value of the original method
     */
    public function __call($name, array $arguments = [])
    {
        return call_user_func_array([$this->class, $name], $arguments);
    }
}
