<?php

namespace Contao\CoreBundle\Test\Fixtures\Adapter;

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
