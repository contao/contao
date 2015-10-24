<?php

namespace Contao\Fixtures;

class RequestToken
{
    public static function initialize()
    {
        // do nothing
    }

    public static function get()
    {
        return 'foobar';
    }

    public static function validate()
    {
        return true;
    }
}
