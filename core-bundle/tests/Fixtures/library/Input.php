<?php

namespace Contao\Fixtures;

class Input
{
    protected static $data = array(
        'GET' => array(),
        'POST' => array()
    );

    public static function get()
    {
        return null;
    }

    public static function post($strKey)
    {
        if (isset(self::$data['POST'][$strKey])) {
            return self::$data['POST'][$strKey];
        }
        return false;
    }

    public static function cookie()
    {
        return null;
    }

    public static function initialize()
    {
        // do nothing
    }

    public static function setPost($strKey, $value)
    {
        self::$data['POST'][$strKey] = $value;

    }
}
