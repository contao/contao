<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Framework;

/**
 * Wraps legacy classes and delegates the method calls, which allows mocking
 * these classes in the unit tests.
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
    public function __construct(string $class)
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
    public function __call(string $name, array $arguments = [])
    {
        return \call_user_func_array([$this->class, $name], $arguments);
    }
}
