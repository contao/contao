<?php

namespace Contao\Fixtures;

class BackendUser extends \Contao\User
{
    public $isAdmin = true;

    public static function getInstance()
    {
        return new self();
    }

    public function authenticate()
    {
        return true;
    }

    public function setUserFromDb()
    {
        // do nothing
    }
}
