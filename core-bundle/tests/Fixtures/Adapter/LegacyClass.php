<?php

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Fixtures\Adapter;

class LegacyClass
{
    public $constructorArgs = [];

    public function __construct($arg1 = null, $arg2 = null)
    {
        $this->constructorArgs = [$arg1, $arg2];
    }

    public static function staticMethod($arg1 = null, $arg2 = null)
    {
        return ['staticMethod', $arg1, $arg2];
    }
}
