<?php

namespace Contao\Fixtures;

class Dbafs
{
    public static function syncFiles()
    {
        return 'sync.log';
    }

    public static function shouldBeSynchronized()
    {
        return false;
    }
}
