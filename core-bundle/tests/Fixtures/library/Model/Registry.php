<?php

namespace Contao\Fixtures\Model;

class Registry
{
    private static $instance;

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function count()
    {
        return 5;
    }
}
