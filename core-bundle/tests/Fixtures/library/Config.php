<?php

namespace Contao\Fixtures;

class Config
{
    private static $instance;
    private static $cache = [];

    public static function getInstance()
    {
        if (null === static::$instance)
        {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public static function set($key, $value)
    {
        static::$cache[$key] = $value;
    }

    public static function get($key)
    {
        if (isset(static::$cache[$key]))
        {
            return static::$cache[$key];
        }

        return null;
    }

    public static function has($key)
    {
        return isset(static::$cache[$key]);
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
