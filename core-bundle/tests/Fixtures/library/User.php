<?php

namespace Contao\Fixtures;

abstract class User
{
    public function __toString()
    {
        return 'foo';
    }

    public function __get($key)
    {

    }

    public function getTable()
    {

    }
}
