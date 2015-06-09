<?php

namespace Contao\Fixtures;

class Config
{
    private static $instance;

    protected function __construct()
    {
        $GLOBALS['TL_CONFIG']['timeZone'] = 'GMT';
        $GLOBALS['TL_CONFIG']['characterSet'] = 'UTF-8';
    }

    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public static function set($key, $value)
    {
        $GLOBALS['TL_CONFIG'][$key] = $value;
    }

    public static function get($key)
    {
        if (isset($GLOBALS['TL_CONFIG'][$key])) {
            return $GLOBALS['TL_CONFIG'][$key];
        }

        return null;
    }

    public static function has($key)
    {
        return array_key_exists($key, $GLOBALS['TL_CONFIG']);
    }

    public static function preload()
    {
        // do nothing
    }

    public static function isComplete()
    {
        return true;
    }
}
