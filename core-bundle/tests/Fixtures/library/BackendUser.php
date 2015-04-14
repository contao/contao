<?php

namespace Contao\Fixtures;

use Contao\User;

class BackendUser extends User
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
