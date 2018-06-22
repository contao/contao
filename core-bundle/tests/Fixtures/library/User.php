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
        // ignore
    }

    public function getTable()
    {
        // ignore
    }

    public function save()
    {
        // ignore
    }

    public function getUsername()
    {
        return $this->username;
    }

    public static function loadUserByUsername($username)
    {
        return new static();
    }
}
