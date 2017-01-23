<?php

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

/**
 * Wraps unmockable classes and delegates the method calls.
 *
 * @author Yanick Witschi <https://github.com/toflar>
 *
 * @internal Do not instantiate this class in your code; use ContaoFramework::getAdapter() instead
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
     * @param string $class
     */
    public function __construct($class)
    {
        $this->class = $class;
    }

    /**
     * Calls a method of the adapted class.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, array $arguments = [])
    {
        return call_user_func_array([$this->class, $name], $arguments);
    }
}
