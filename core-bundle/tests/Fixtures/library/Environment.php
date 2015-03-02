<?php

namespace Contao\Fixtures;

class Environment
{
    private static $cache = [];

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
}
