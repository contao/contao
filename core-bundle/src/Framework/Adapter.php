<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Framework;

/**
 * Wraps legacy classes and delegates the method calls, which allows mocking
 * these classes in the unit tests.
 *
 * @internal Do not use this class in your code; use ContaoFramework::getAdapter() instead
 */
class Adapter
{
    /**
     * @var string
     */
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * Calls a method of the adapted class.
     */
    public function __call(string $name, array $arguments = [])
    {
        return \call_user_func_array([$this->class, $name], $arguments);
    }
}
